<?php
require_once '../../include/config.php';
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
    $error = 'lỗi: ' . $e->getMessage();
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
            for ($i = 1; $i < count($rows); $i++) { // bỏ dòng tiêu đề
                $row = $rows[$i];
                $tenMonHoc = mb_strtolower(trim($row[1] ?? ''));
                $tenTheLoai = mb_strtolower(trim($row[2] ?? ''));
                $noiDung = trim($row[3] ?? '');
                $doKho = trim($row[4] ?? '');
                $dapAn = [
                    trim($row[5] ?? ''),
                    trim($row[6] ?? ''),
                    trim($row[7] ?? ''),
                    trim($row[8] ?? '')
                ];
                $dapAnDung = intval($row[9] ?? 0) - 1;

                if (!$tenMonHoc || !$noiDung || !$doKho || !$dapAn[0] || !$dapAn[1] || $dapAnDung < 0 || $dapAnDung > 3) {
                    continue; // bỏ qua dòng lỗi
                }
                // lấy id môn học
                $monHocId = $dsMonHoc[$tenMonHoc] ?? null;
                if (!$monHocId) continue;
                // lấy id thể loại (có thể null)
                $theLoaiId = null;
                if ($tenTheLoai) {
                    $key = $monHocId . '|' . $tenTheLoai;
                    $theLoaiId = $dsTheLoai[$key] ?? null;
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
            $success = 'đã nhập thành công ' . $count . ' câu hỏi!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'lỗi khi nhập file: ' . $e->getMessage();
        }
    } else {
        $error = 'vui lòng chọn file excel!';
    }
}
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/ngan-hang-cau-hoi">Ngân hàng câu hỏi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nhập/Xuất excel</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex align-items-center">
            <i class="fas fa-file-excel me-2 text-success"></i>
            <span class="fw-bold fs-4">công cụ nhập/xuất câu hỏi</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-white fw-bold fs-5">nhập dữ liệu</div>
                        <div class="card-body">
                            <div class="mb-2">nhập dữ liệu câu hỏi từ file excel.</div>
                            <form method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="file_excel" class="form-label">chọn file excel</label>
                                    <input type="file" class="form-control" id="file_excel" name="file_excel" accept=".xlsx,.xls">
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="/ngan-hang-cau-hoi/excel/mau.php" class="btn btn-success"><i class="fas fa-download me-1"></i> tải mẫu nhập</a>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i> nhập dữ liệu</button>
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
                        <div class="card-header bg-white fw-bold fs-5">xuất dữ liệu</div>
                        <div class="card-body">
                            <div class="mb-3">xuất dữ liệu câu hỏi ra file excel.</div>
                            <a href="/ngan-hang-cau-hoi/excel/xuat.php" class="btn btn-success"><i class="fas fa-download me-1"></i> xuất dữ liệu</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card mt-3">
                        <div class="card-header bg-white fw-bold fs-5"><i class="fas fa-info-circle text-info me-2"></i>hướng dẫn nhập dữ liệu</div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>tải mẫu nhập để xem cấu trúc file excel cần nhập.</li>
                                <li>mỗi câu hỏi cần có ít nhất 2 đáp án.</li>
                                <li>độ khó chỉ nhận các giá trị: de, trungbinh, kho.</li>
                                <li>đáp án đúng là số thứ tự của đáp án (1-4).</li>
                                <li>tên môn học và thể loại phải đúng với hệ thống.</li>
                                <li>có thể để trống thể loại, đáp án 3, 4 nếu không dùng.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../include/layouts/footer.php'; ?>
