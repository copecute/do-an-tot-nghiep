<?php
require_once '../../include/config.php';
$page_title = "Nhập/Xuất Khoa";
include '../../include/layouts/header.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['vai_tro'] !== 'admin') {
    header('Location: /dang-nhap.php');
    exit;
}

$error = '';
$success = '';

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
            for ($i = 1; $i < count($rows); $i++) { // bắt đầu từ dòng 2
                $row = $rows[$i];
                $tenKhoa = trim($row[1] ?? '');
                $skipReason = '';
                if (!$tenKhoa) $skipReason = 'Thiếu tên khoa';
                // Kiểm tra trùng tên khoa
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM khoa WHERE tenKhoa = ?');
                $stmt->execute([$tenKhoa]);
                $exists = $stmt->fetchColumn();
                if ($exists) $skipReason = 'Tên khoa đã tồn tại';
                if ($skipReason) {
                    $skipped++;
                    $skippedRows[] = ['row' => $i+1, 'reason' => $skipReason, 'data' => $row];
                    continue;
                }
                // Thêm khoa
                $stmt = $pdo->prepare('INSERT INTO khoa (tenKhoa) VALUES (?)');
                $stmt->execute([$tenKhoa]);
                $count++;
            }
            $pdo->commit();
            $success = 'Đã nhập thành công ' . $count . ' khoa!';
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
        <li class="breadcrumb-item"><a href="/quan-ly-khoa">Quản Lý Khoa</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nhập/Xuất Excel</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex align-items-center">
            <i class="fas fa-file-excel me-2 text-success"></i>
            <span class="fw-bold fs-4">Công Cụ Nhập/Xuất Khoa</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-white fw-bold fs-5">Nhập Dữ Liệu</div>
                        <div class="card-body">
                            <div class="mb-2">Nhập dữ liệu khoa từ file Excel.</div>
                            <form method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="file_excel" class="form-label">Chọn file Excel</label>
                                    <input type="file" class="form-control" id="file_excel" name="file_excel" accept=".xlsx,.xls">
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="/quan-ly-khoa/excel/mau.php" class="btn btn-success" id="btnTaiMauNhap"><i class="fas fa-download me-1"></i> Tải Mẫu Nhập</a>
                                    <button type="submit" class="btn btn-primary" id="btnNhapDuLieu"><i class="fas fa-upload me-1"></i> Nhập Dữ Liệu</button>
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
                            <div class="mb-3">Xuất dữ liệu khoa ra file Excel.</div>
                            <a href="/quan-ly-khoa/excel/xuat.php" class="btn btn-success" id="btnXuatDuLieu"><i class="fas fa-download me-1"></i> Xuất Dữ Liệu</a>
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
                                <li>Mỗi dòng là một khoa, không để trống cột nào.</li>
                                <li>STT chỉ để tham khảo, không cần nhập vào hệ thống.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
addLoadingAndRedirect('btnXuatDuLieu');
const formNhapKhoaExcel = document.querySelector('form[enctype="multipart/form-data"]');
const btnNhapKhoaExcel = document.getElementById('btnNhapDuLieu');
if (formNhapKhoaExcel && btnNhapKhoaExcel) {
    formNhapKhoaExcel.addEventListener('submit', function(e) {
        btnNhapKhoaExcel.disabled = true;
        btnNhapKhoaExcel.innerHTML = `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`;
    });
}
</script>

<?php include '../../include/layouts/footer.php'; ?>
