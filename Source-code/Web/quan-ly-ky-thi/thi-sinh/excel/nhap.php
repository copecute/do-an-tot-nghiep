<?php
require_once '../../../include/config.php';
require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id kỳ thi
if (!isset($_GET['kyThiId']) || empty($_GET['kyThiId'])) {
    $_SESSION['flash_message'] = 'id kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$kyThiId = $_GET['kyThiId'];

try {
    // lấy thông tin kỳ thi
    $stmt = $pdo->prepare('
        SELECT k.*, m.tenMonHoc, n.id as nganhId, n.tenNganh, m.id as monHocId
        FROM kyThi k 
        JOIN monHoc m ON k.monHocId = m.id
        JOIN nganh n ON m.nganhId = n.id
        WHERE k.id = ? AND k.nguoiTaoId = ?
    ');
    $stmt->execute([$kyThiId, $_SESSION['user_id']]);
    $kyThi = $stmt->fetch();

    if (!$kyThi) {
        $_SESSION['flash_message'] = 'không tìm thấy kỳ thi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    $errors = [];
    $thongBao = '';
    $thanhCong = 0;
    $thatBai = 0;

    // xử lý upload file
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
        $file = $_FILES['excelFile'];

        // kiểm tra lỗi upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'kích thước file quá lớn!';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'file bị upload không hoàn chỉnh!';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'không có file nào được upload!';
                    break;
                default:
                    $errors[] = 'có lỗi xảy ra khi upload file!';
            }
        } else {
            // kiểm tra định dạng file
            $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
            if ($fileType !== 'xlsx' && $fileType !== 'xls') {
                $errors[] = 'chỉ hỗ trợ file Excel (.xlsx, .xls)!';
            } else {
                try {
                    $spreadsheet = IOFactory::load($file['tmp_name']);
                    $worksheet = $spreadsheet->getActiveSheet();
                    
                    // lấy dữ liệu từ file Excel
                    $highestRow = $worksheet->getHighestRow();
                    
                    // bắt đầu từ dòng 10 (sau tiêu đề)
                    $startRow = 10;
                    
                    // lặp qua từng dòng dữ liệu
                    for ($row = $startRow; $row <= $highestRow; $row++) {
                        // lấy dữ liệu từ các cột
                        $maSinhVien = trim($worksheet->getCell('B' . $row)->getValue());
                        $hoTen = trim($worksheet->getCell('C' . $row)->getValue());
                        
                        // bỏ qua dòng trống
                        if (empty($maSinhVien) || empty($hoTen)) {
                            continue;
                        }
                        
                        // kiểm tra sinh viên đã tồn tại chưa
                        $stmt = $pdo->prepare('SELECT id FROM sinhVien WHERE maSinhVien = ?');
                        $stmt->execute([$maSinhVien]);
                        $sinhVien = $stmt->fetch();
                        
                        if (!$sinhVien) {
                            // thêm mới sinh viên
                            $stmt = $pdo->prepare('INSERT INTO sinhVien (maSinhVien, hoTen, nganhId) VALUES (?, ?, ?)');
                            $stmt->execute([$maSinhVien, $hoTen, $kyThi['nganhId']]);
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
                        }
                    }
                    
                    // thông báo kết quả
                    if ($thanhCong > 0) {
                        $thongBao = "đã nhập thành công $thanhCong thí sinh";
                        if ($thatBai > 0) {
                            $thongBao .= ", $thatBai thí sinh đã tồn tại";
                        }
                        $_SESSION['flash_message'] = $thongBao;
                        $_SESSION['flash_type'] = 'success';
                        
                        header("Location: /quan-ly-ky-thi/thi-sinh/?kyThiId=$kyThiId");
                        exit;
                    } else if ($thatBai > 0) {
                        $errors[] = "tất cả $thatBai thí sinh đã tồn tại trong kỳ thi này";
                    } else {
                        $errors[] = "không có dữ liệu hợp lệ trong file";
                    }
                } catch (Exception $e) {
                    $errors[] = 'lỗi xử lý file: ' . $e->getMessage();
                }
            }
        }
    }
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
}

include '../../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/thi-sinh/?kyThiId=<?php echo $kyThiId; ?>">Quản lý thí sinh</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nhập excel</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">nhập danh sách thí sinh từ excel</h5>
                        <p class="text-muted mb-0">
                            kỳ thi: <?php echo htmlspecialchars($kyThi['tenKyThi']); ?> | 
                            môn học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <a href="/quan-ly-ky-thi/thi-sinh/?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> quay lại
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">hướng dẫn nhập file excel</h6>
                                    <ol>
                                        <li>tải <a href="/quan-ly-ky-thi/thi-sinh/excel/mau.php?kyThiId=<?php echo $kyThiId; ?>" class="fw-bold">file mẫu</a> excel</li>
                                        <li>điền thông tin thí sinh theo mẫu</li>
                                        <li>chọn file và nhấn nút "nhập dữ liệu"</li>
                                    </ol>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> lưu ý:
                                        <ul class="mb-0">
                                            <li>chỉ cần điền mã sinh viên và họ tên</li>
                                            <li>số báo danh sẽ được tạo tự động</li>
                                            <li>không thay đổi cấu trúc file mẫu</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">tải lên file excel</h6>
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="excelFile" class="form-label">chọn file excel:</label>
                                            <input type="file" class="form-control" id="excelFile" name="excelFile" required accept=".xlsx, .xls">
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload"></i> nhập dữ liệu
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../include/layouts/footer.php'; ?>