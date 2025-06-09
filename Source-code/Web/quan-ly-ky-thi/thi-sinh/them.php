<?php
require_once '../../include/config.php';

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
        SELECT k.*, m.tenMonHoc, n.id as nganhId, n.tenNganh
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

    // xử lý form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $maSinhVien = trim($_POST['maSinhVien'] ?? '');
        $hoTen = trim($_POST['hoTen'] ?? '');

        // validate dữ liệu
        $errors = [];
        if (empty($maSinhVien)) {
            $errors[] = 'mã sinh viên không được để trống!';
        }
        if (empty($hoTen)) {
            $errors[] = 'họ tên không được để trống!';
        }

        if (empty($errors)) {
            // kiểm tra mã sinh viên đã tồn tại chưa
            $stmt = $pdo->prepare('SELECT id FROM sinhVien WHERE maSinhVien = ?');
            $stmt->execute([$maSinhVien]);
            $sinhVienId = $stmt->fetchColumn();

            // bắt đầu transaction
            $pdo->beginTransaction();

            try {
                // nếu sinh viên chưa tồn tại thì thêm mới
                if (!$sinhVienId) {
                    $stmt = $pdo->prepare('
                        INSERT INTO sinhVien (maSinhVien, hoTen, nganhId)
                        VALUES (?, ?, ?)
                    ');
                    $stmt->execute([$maSinhVien, $hoTen, $kyThi['nganhId']]);
                    $sinhVienId = $pdo->lastInsertId();
                } else {
                    // cập nhật thông tin sinh viên nếu đã tồn tại
                    $stmt = $pdo->prepare('
                        UPDATE sinhVien
                        SET hoTen = ?, nganhId = ?
                        WHERE id = ?
                    ');
                    $stmt->execute([$hoTen, $kyThi['nganhId'], $sinhVienId]);
                }

                // kiểm tra sinh viên đã có số báo danh trong kỳ thi này chưa
                $stmt = $pdo->prepare('SELECT id FROM soBaoDanh WHERE sinhVienId = ? AND kyThiId = ?');
                $stmt->execute([$sinhVienId, $kyThiId]);
                if ($stmt->fetchColumn()) {
                    throw new Exception('sinh viên này đã có số báo danh trong kỳ thi!');
                }

                // tạo số báo danh tự động
                $soBaoDanh = generateSoBaoDanh($pdo, $kyThiId, $maSinhVien);

                // thêm số báo danh
                $stmt = $pdo->prepare('
                    INSERT INTO soBaoDanh (kyThiId, sinhVienId, soBaoDanh)
                    VALUES (?, ?, ?)
                ');
                $stmt->execute([$kyThiId, $sinhVienId, $soBaoDanh]);

                // lưu thay đổi
                $pdo->commit();

                $_SESSION['flash_message'] = 'thêm thí sinh thành công!';
                $_SESSION['flash_type'] = 'success';
                header("Location: /quan-ly-ky-thi/thi-sinh/?kyThiId=$kyThiId");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = $e->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
}

/**
 * hàm tạo số báo danh tự động
 * 
 * @param PDO $pdo
 * @param int $kyThiId
 * @param string $maSinhVien
 * @return string
 */
function generateSoBaoDanh($pdo, $kyThiId, $maSinhVien) {
    // lấy mã kỳ thi và mã môn học
    $stmt = $pdo->prepare('
        SELECT k.id, m.id as monHocId 
        FROM kyThi k
        JOIN monHoc m ON k.monHocId = m.id
        WHERE k.id = ?
    ');
    $stmt->execute([$kyThiId]);
    $kyThi = $stmt->fetch();
    
    // đếm số thí sinh hiện tại trong kỳ thi
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM soBaoDanh WHERE kyThiId = ?');
    $stmt->execute([$kyThiId]);
    $soThuTu = $stmt->fetchColumn() + 1;
    
    // định dạng số với padding số 0 ở đầu
    $kyThiIdFormat = str_pad($kyThi['id'], 3, '0', STR_PAD_LEFT);
    $monHocIdFormat = str_pad($kyThi['monHocId'], 3, '0', STR_PAD_LEFT);
    $soThuTuFormat = str_pad($soThuTu, 4, '0', STR_PAD_LEFT);
    
    // tạo số báo danh theo định dạng: {ky_thi_id}{idmonhoc}{so_thu_tu}
    $soBaoDanh = $kyThiIdFormat . $monHocIdFormat . $soThuTuFormat;
    
    // kiểm tra nếu số báo danh đã tồn tại thì tăng số thứ tự lên 1
    $stmt = $pdo->prepare('SELECT id FROM soBaoDanh WHERE soBaoDanh = ?');
    $stmt->execute([$soBaoDanh]);
    if ($stmt->fetchColumn()) {
        // tăng số thứ tự và thử lại
        return generateSoBaoDanh($pdo, $kyThiId, $maSinhVien);
    }
    
    return $soBaoDanh;
}

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/thi-sinh/?kyThiId=<?php echo $kyThiId; ?>">Quản lý thí sinh</a></li>
        <li class="breadcrumb-item active" aria-current="page">Thêm thí sinh</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">thêm thí sinh</h5>
                        <p class="text-muted mb-0">
                            kỳ thi: <?php echo htmlspecialchars($kyThi['tenKyThi']); ?> | 
                            môn học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <div>
                        <a href="/quan-ly-ky-thi/thi-sinh/excel/nhap.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success">
                            <i class="fas fa-file-import"></i> nhập excel
                        </a>
                        <a href="/quan-ly-ky-thi/thi-sinh/?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> quay lại
                        </a>
                    </div>
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

                    <form method="POST">
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">mã sinh viên:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="maSinhVien" required
                                    value="<?php echo htmlspecialchars($_POST['maSinhVien'] ?? ''); ?>"
                                    placeholder="nhập mã sinh viên...">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">họ tên:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="hoTen" required
                                    value="<?php echo htmlspecialchars($_POST['hoTen'] ?? ''); ?>"
                                    placeholder="nhập họ tên sinh viên...">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">ngành học:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" 
                                    value="<?php echo htmlspecialchars($kyThi['tenNganh']); ?>" readonly>
                                <small class="text-muted">ngành học được xác định theo môn học của kỳ thi</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> thêm thí sinh
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../include/layouts/footer.php'; ?> 