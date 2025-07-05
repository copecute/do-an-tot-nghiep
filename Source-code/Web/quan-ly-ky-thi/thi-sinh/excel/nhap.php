<?php
require_once '../../../include/config.php';
require '../../../vendor/autoload.php';
$page_title = "Nhập thí sinh";
use PhpOffice\PhpSpreadsheet\IOFactory;

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id kỳ thi
if (!isset($_GET['kyThiId']) || empty($_GET['kyThiId'])) {
    $_SESSION['flash_message'] = 'ID kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$kyThiId = $_GET['kyThiId'];

$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';

try {
    // lấy thông tin kỳ thi
    if ($isAdmin) {
        $stmt = $pdo->prepare('
            SELECT k.*, m.tenMonHoc, m.id as monHocId
            FROM kyThi k 
            JOIN monHoc m ON k.monHocId = m.id
            WHERE k.id = ?
        ');
        $stmt->execute([$kyThiId]);
    } else {
        $stmt = $pdo->prepare('
            SELECT k.*, m.tenMonHoc, m.id as monHocId
            FROM kyThi k 
            JOIN monHoc m ON k.monHocId = m.id
            WHERE k.id = ? AND k.nguoiTaoId = ?
        ');
        $stmt->execute([$kyThiId, $_SESSION['user_id']]);
    }
    $kyThi = $stmt->fetch();

    if (!$kyThi) {
        $_SESSION['flash_message'] = 'Không tìm thấy kỳ thi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    $errors = [];
    $thongBao = '';
    $thanhCong = 0;
    $thatBai = 0;
    $skippedRows = [];

    // xử lý upload file
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
        $file = $_FILES['excelFile'];

        // kiểm tra lỗi upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'Kích thước file quá lớn!';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'File bị upload không hoàn chỉnh!';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'Không có file nào được upload!';
                    break;
                default:
                    $errors[] = 'Có lỗi xảy ra khi upload file!';
            }
        } else {
            // kiểm tra định dạng file
            $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
            if ($fileType !== 'xlsx' && $fileType !== 'xls') {
                $errors[] = 'Chỉ hỗ trợ file Excel (.xlsx, .xls)!';
            } else {
                try {
                    $spreadsheet = IOFactory::load($file['tmp_name']);
                    $worksheet = $spreadsheet->getActiveSheet();
                    
                    // lấy dữ liệu từ file Excel
                    $highestRow = $worksheet->getHighestRow();
                    
                    // bắt đầu từ dòng 8
                    $startRow = 8;
                    
                    // lặp qua từng dòng dữ liệu
                    for ($row = $startRow; $row <= $highestRow; $row++) {
                        // lấy dữ liệu từ các cột
                        $maSinhVien = trim($worksheet->getCell('A' . $row)->getValue() ?? '');
                        $hoTen = trim($worksheet->getCell('B' . $row)->getValue() ?? '');
                        $ghiChu = trim($worksheet->getCell('C' . $row)->getValue() ?? '');
                        
                        // bỏ qua dòng trống
                        if (empty($maSinhVien) || empty($hoTen)) {
                            $skippedRows[] = ['row' => $row, 'reason' => 'Thiếu mã sinh viên hoặc họ tên'];
                            continue;
                        }
                        
                        // kiểm tra sinh viên đã tồn tại chưa
                        $stmt = $pdo->prepare('SELECT id FROM sinhVien WHERE maSinhVien = ?');
                        $stmt->execute([$maSinhVien]);
                        $sinhVien = $stmt->fetch();
                        
                        if (!$sinhVien) {
                            // thêm mới sinh viên
                            $stmt = $pdo->prepare('INSERT INTO sinhVien (maSinhVien, hoTen) VALUES (?, ?)');
                            $stmt->execute([$maSinhVien, $hoTen]);
                            $sinhVienId = $pdo->lastInsertId();
                        } else {
                            $sinhVienId = $sinhVien['id'];
                        }
                        
                        // kiểm tra sinh viên đã có số báo danh trong kỳ thi chưa
                        $stmt = $pdo->prepare('SELECT id FROM soBaoDanh WHERE kyThiId = ? AND sinhVienId = ?');
                        $stmt->execute([$kyThiId, $sinhVienId]);
                        $soBaoDanh = $stmt->fetch();
                        
                        if (!$soBaoDanh) {
                            // tạo số báo danh mới
                            // lấy số thứ tự tiếp theo
                            $stmt = $pdo->prepare('
                                SELECT COUNT(*) as soLuong 
                                FROM soBaoDanh 
                                WHERE kyThiId = ?
                            ');
                            $stmt->execute([$kyThiId]);
                            $soLuong = $stmt->fetch()['soLuong'];
                            $soThuTu = $soLuong + 1;
                            
                            // tạo số báo danh theo định dạng: {ky_thi_id}{monhoc_id}{so_thu_tu}
                            $soBaoDanhMoi = sprintf('%03d%02d%03d', $kyThiId, $kyThi['monHocId'], $soThuTu);
                            
                            // thêm số báo danh
                            $stmt = $pdo->prepare('INSERT INTO soBaoDanh (kyThiId, sinhVienId, soBaoDanh) VALUES (?, ?, ?)');
                            $stmt->execute([$kyThiId, $sinhVienId, $soBaoDanhMoi]);
                            
                            $thanhCong++;
                        } else {
                            $thatBai++;
                            $skippedRows[] = ['row' => $row, 'reason' => 'Thí sinh đã tồn tại trong kỳ thi'];
                        }
                    }
                    
                    // thông báo kết quả
                    if ($thanhCong > 0) {
                        $thongBao = "Đã nhập thành công $thanhCong thí sinh";
                        if ($thatBai > 0) {
                            $thongBao .= ", $thatBai thí sinh đã tồn tại";
                        }
                        if (count($skippedRows) > 0) {
                            $thongBao .= '<ul style="font-size:13px">';
                            foreach ($skippedRows as $skip) {
                                $thongBao .= '<li>Dòng ' . $skip['row'] . ': ' . htmlspecialchars($skip['reason']) . '</li>';
                            }
                            $thongBao .= '</ul>';
                        }
                        $_SESSION['flash_message'] = $thongBao;
                        $_SESSION['flash_type'] = 'success';
                        
                        header("Location: /quan-ly-ky-thi/thi-sinh/?kyThiId=$kyThiId");
                        exit;
                    } else if ($thatBai > 0) {
                        $errors[] = "Tất cả $thatBai thí sinh đã tồn tại trong kỳ thi này";
                        if (count($skippedRows) > 0) {
                            $errMsg = '<ul style="font-size:13px">';
                            foreach ($skippedRows as $skip) {
                                $errMsg .= '<li>Dòng ' . $skip['row'] . ': ' . htmlspecialchars($skip['reason']) . '</li>';
                            }
                            $errMsg .= '</ul>';
                            $errors[] = $errMsg;
                        }
                    } else {
                        $errors[] = "Không có dữ liệu hợp lệ trong file";
                        if (count($skippedRows) > 0) {
                            $errMsg = '<ul style="font-size:13px">';
                            foreach ($skippedRows as $skip) {
                                $errMsg .= '<li>Dòng ' . $skip['row'] . ': ' . htmlspecialchars($skip['reason']) . '</li>';
                            }
                            $errMsg .= '</ul>';
                            $errors[] = $errMsg;
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = 'Lỗi xử lý file: ' . $e->getMessage();
                }
            }
        }
    }
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

include '../../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item">
            <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $kyThiId; ?>">Kỳ thi: <?php echo htmlspecialchars($kyThiId); ?></a>
        </li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/thi-sinh/?kyThiId=<?php echo $kyThiId; ?>">Quản lý thí sinh</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nhập/Xuất Excel</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex align-items-center">
            <i class="fas fa-file-excel me-2 text-success"></i>
            <span class="fw-bold fs-4">Công cụ nhập/xuất thí sinh</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-white fw-bold fs-5">Nhập dữ liệu</div>
                        <div class="card-body">
                            <div class="mb-2">Nhập danh sách thí sinh từ file Excel.</div>
                            <form method="POST" enctype="multipart/form-data" id="formNhapThiSinhExcel">
                                <div class="mb-3">
                                    <label for="excelFile" class="form-label">Chọn file Excel</label>
                                    <input type="file" class="form-control" id="excelFile" name="excelFile" required accept=".xlsx,.xls">
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="/quan-ly-ky-thi/thi-sinh/excel/mau.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success" id="btnTaiMauNhap"><i class="fas fa-download me-1"></i> Tải mẫu nhập</a>
                                    <button type="submit" class="btn btn-primary" id="btnNhapThiSinhExcel"><i class="fas fa-upload me-1"></i> Nhập dữ liệu</button>
                                </div>
                            </form>
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger mt-2">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-white fw-bold fs-5">Xuất dữ liệu</div>
                        <div class="card-body">
                            <div class="mb-3">Xuất danh sách thí sinh ra file Excel.</div>
                            <a href="/quan-ly-ky-thi/thi-sinh/excel/xuat.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success" id="btnXuatThiSinhExcel"><i class="fas fa-download me-1"></i> Xuất dữ liệu</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card mt-3">
                        <div class="card-header bg-white fw-bold fs-5"><i class="fas fa-info-circle text-info me-2"></i>Hướng dẫn nhập dữ liệu</div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Tải mẫu nhập để xem cấu trúc file Excel cần nhập.</li>
                                <li>Chỉ cần điền mã sinh viên và họ tên.</li>
                                <li>Ghi chú có thể để trống.</li>
                                <li>Không thay đổi cấu trúc file mẫu.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const formNhapThiSinhExcel = document.getElementById('formNhapThiSinhExcel');
const btnNhapThiSinhExcel = document.getElementById('btnNhapThiSinhExcel');
if (formNhapThiSinhExcel && btnNhapThiSinhExcel) {
    formNhapThiSinhExcel.addEventListener('submit', function(e) {
        btnNhapThiSinhExcel.disabled = true;
        btnNhapThiSinhExcel.innerHTML = `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`;
    });
}

function addLoadingAndRedirect(btnId) {
    const btn = document.getElementById(btnId);
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const oldHtml = btn.innerHTML;
            window.location.href = btn.getAttribute('href');
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
            btn.innerHTML = `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`;
            btn.setAttribute('disabled', 'disabled');
            setTimeout(function() {
                btn.innerHTML = oldHtml;
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
                btn.removeAttribute('disabled');
            }, 3000);
        });
    }
}
addLoadingAndRedirect('btnTaiMauNhap');
addLoadingAndRedirect('btnXuatThiSinhExcel');
</script>

<?php include '../../../include/layouts/footer.php'; ?>