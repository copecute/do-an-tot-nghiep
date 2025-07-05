<?php
require_once '../../include/config.php';
$page_title = "Nhập Câu Hỏi";
include '../../include/layouts/header.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    header('Location: /dang-nhap.php');
    exit;
}

$error = '';
$success = '';

// lấy danh sách môn học và thể loại để map tên sang id
$dsMonHoc = [];
$dsTheLoai = [];
try {
    $stmt = $pdo->query('SELECT * FROM monHoc');
    foreach ($stmt->fetchAll() as $row) {
        $dsMonHoc[mb_strtolower(trim($row['tenMonHoc']))] = $row['id'];
    }
    $stmt = $pdo->query('SELECT * FROM theLoaiCauHoi');
    foreach ($stmt->fetchAll() as $row) {
        $key = $row['monHocId'] . '|' . mb_strtolower(trim($row['tenTheLoai']));
        $dsTheLoai[$key] = $row['id'];
    }
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// xử lý khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
        $file = $_FILES['file_excel']['tmp_name'];
        try {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            $pdo->beginTransaction();
            $count = 0;
            $skipped = 0;
            $skippedRows = [];
            for ($i = 9; $i < count($rows); $i++) { // bắt đầu từ dòng 10 (index 9)
                $row = $rows[$i];
                $tenMonHoc = mb_strtolower(trim($row[0] ?? ''));
                $tenTheLoai = mb_strtolower(trim($row[1] ?? ''));
                $noiDung = trim($row[2] ?? '');
                $doKho = trim($row[3] ?? '');
                $dapAn = [
                    trim($row[4] ?? ''),
                    trim($row[5] ?? ''),
                    trim($row[6] ?? ''),
                    trim($row[7] ?? '')
                ];
                $dapAnDung = intval($row[8] ?? 0) - 1;

                // Chuẩn hóa độ khó từ tiếng Việt sang mã hệ thống
                $doKhoMap = [
                    'dễ' => 'de',
                    'de' => 'de',
                    'trung bình' => 'trungbinh',
                    'trungbinh' => 'trungbinh',
                    'khó' => 'kho',
                    'kho' => 'kho'
                ];
                $doKhoLower = mb_strtolower($doKho);
                $doKho = $doKhoMap[$doKhoLower] ?? $doKho;

                $skipReason = '';
                if (!$tenMonHoc) $skipReason = 'Thiếu tên môn học';
                elseif (!$noiDung) $skipReason = 'Thiếu nội dung';
                elseif (!$doKho) $skipReason = 'Thiếu độ khó';
                elseif (!$dapAn[0] || !$dapAn[1]) $skipReason = 'Phải có ít nhất 2 đáp án';
                elseif ($dapAnDung < 0 || $dapAnDung > 3) $skipReason = 'Đáp án đúng không hợp lệ';

                // lấy id môn học
                $monHocId = $dsMonHoc[$tenMonHoc] ?? null;
                if (!$monHocId && !$skipReason) $skipReason = 'Tên môn học không khớp hệ thống';
                // lấy id thể loại (có thể null)
                $theLoaiId = null;
                if ($tenTheLoai) {
                    $key = $monHocId . '|' . $tenTheLoai;
                    $theLoaiId = $dsTheLoai[$key] ?? null;
                }
                if ($skipReason) {
                    $skipped++;
                    $skippedRows[] = ['row' => $i+1, 'reason' => $skipReason, 'data' => $row];
                    continue;
                }
                // thêm câu hỏi
                $stmt = $pdo->prepare('INSERT INTO cauHoi (monHocId, theLoaiId, noiDung, doKho) VALUES (?, ?, ?, ?)');
                $stmt->execute([$monHocId, $theLoaiId, $noiDung, $doKho]);
                $cauHoiId = $pdo->lastInsertId();
                // thêm đáp án
                $stmtDapAn = $pdo->prepare('INSERT INTO dapAn (cauHoiId, noiDung, laDapAn) VALUES (?, ?, ?)');
                foreach ($dapAn as $index => $nd) {
                    if ($nd) {
                        $stmtDapAn->execute([$cauHoiId, $nd, $index == $dapAnDung ? 1 : 0]);
                    }
                }
                $count++;
            }
            $pdo->commit();
            $success = 'Đã nhập thành công ' . $count . ' câu hỏi!';
            if ($skipped > 0) {
                $success .= ' Bỏ qua ' . $skipped . ' dòng lỗi.';
                $success .= '<ul style="font-size:13px">';
                foreach ($skippedRows as $skip) {
                    $success .= '<li>Dòng ' . $skip['row'] . ': ' . htmlspecialchars($skip['reason']) . '</li>';
                }
                $success .= '</ul>';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Lỗi khi nhập file: ' . $e->getMessage();
        }
    } else {
        $error = 'Vui lòng chọn file Excel!';
    }
}
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang Chủ</a></li>
        <li class="breadcrumb-item"><a href="/ngan-hang-cau-hoi">Ngân Hàng Câu Hỏi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nhập/Xuất Excel</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex align-items-center">
            <i class="fas fa-file-excel me-2 text-success"></i>
            <span class="fw-bold fs-4">Công Cụ Nhập/Xuất Câu Hỏi</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-white fw-bold fs-5">Nhập Dữ Liệu</div>
                        <div class="card-body">
                            <div class="mb-2">Nhập dữ liệu câu hỏi từ file Excel.</div>
                            <form method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="file_excel" class="form-label">Chọn file Excel</label>
                                    <input type="file" class="form-control" id="file_excel" name="file_excel" accept=".xlsx,.xls">
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="/ngan-hang-cau-hoi/excel/mau.php" class="btn btn-success" id="btnTaiMauNhap"><i class="fas fa-download me-1"></i> Tải Mẫu Nhập</a>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i> Nhập Dữ Liệu</button>
                                </div>
                            </form>
                            <?php if ($error): ?>
                                <div class="alert alert-danger mt-2"><?php echo $error; ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success mt-2"><?php echo $success; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-white fw-bold fs-5">Xuất Dữ Liệu</div>
                        <div class="card-body">
                            <div class="mb-3">Xuất dữ liệu câu hỏi ra file Excel.</div>
                            <a href="/ngan-hang-cau-hoi/excel/xuat.php" class="btn btn-success" id="btnXuatDuLieu"><i class="fas fa-download me-1"></i> Xuất Dữ Liệu</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card mt-3">
                        <div class="card-header bg-white fw-bold fs-5"><i class="fas fa-info-circle text-info me-2"></i>Hướng Dẫn Nhập Dữ Liệu</div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Tải mẫu nhập để xem cấu trúc file Excel cần nhập.</li>
                                <li>Mỗi câu hỏi cần có ít nhất 2 đáp án.</li>
                                <li>Độ khó chỉ nhận các giá trị: de, trungbinh, kho.</li>
                                <li>Đáp án đúng là số thứ tự của đáp án (1-4).</li>
                                <li>Tên môn học và thể loại phải đúng với hệ thống.</li>
                                <li>Có thể để trống thể loại, đáp án 3, 4 nếu không dùng.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Hiệu ứng loading cho nút tải mẫu nhập và xuất dữ liệu
function addLoadingAndRedirect(btnId) {
    const btn = document.getElementById(btnId);
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const oldHtml = btn.innerHTML;
            window.location.href = btn.getAttribute('href');
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> <span class="visually-hidden" role="status">Loading...</span>`;
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
addLoadingAndRedirect('btnXuatDuLieu');
// Hiệu ứng loading cho nút nhập dữ liệu
const formNhapCauHoiExcel = document.querySelector('form[enctype="multipart/form-data"]');
const btnNhapCauHoiExcel = formNhapCauHoiExcel ? formNhapCauHoiExcel.querySelector('button[type="submit"]') : null;
if (formNhapCauHoiExcel && btnNhapCauHoiExcel) {
    formNhapCauHoiExcel.addEventListener('submit', function(e) {
        btnNhapCauHoiExcel.disabled = true;
        btnNhapCauHoiExcel.innerHTML = `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`;
    });
}
</script>

<?php include '../../include/layouts/footer.php'; ?>
