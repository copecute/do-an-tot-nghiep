<?php
require_once '../../include/config.php';
$page_title = "Sửa thí sinh";
// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

// kiểm tra kyThiId
if (!isset($_GET['kyThiId']) || empty($_GET['kyThiId'])) {
    $_SESSION['flash_message'] = 'ID kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$id = $_GET['id'];
$kyThiId = $_GET['kyThiId'];

$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';

try {
    // lấy thông tin số báo danh và sinh viên
    if ($isAdmin) {
        $stmt = $pdo->prepare('
            SELECT s.*, sv.maSinhVien, sv.hoTen, k.tenKyThi, m.tenMonHoc,
                (SELECT COUNT(*) FROM baiThi b WHERE b.soBaoDanhId = s.id) as soBaiThi
            FROM soBaoDanh s
            JOIN sinhVien sv ON s.sinhVienId = sv.id
            JOIN kyThi k ON s.kyThiId = k.id
            JOIN monHoc m ON k.monHocId = m.id
            WHERE s.id = ?
        ');
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare('
            SELECT s.*, sv.maSinhVien, sv.hoTen, k.tenKyThi, m.tenMonHoc,
                (SELECT COUNT(*) FROM baiThi b WHERE b.soBaoDanhId = s.id) as soBaiThi
            FROM soBaoDanh s
            JOIN sinhVien sv ON s.sinhVienId = sv.id
            JOIN kyThi k ON s.kyThiId = k.id
            JOIN monHoc m ON k.monHocId = m.id
            WHERE s.id = ? AND k.nguoiTaoId = ?
        ');
        $stmt->execute([$id, $_SESSION['user_id']]);
    }
    $thiSinh = $stmt->fetch();

    if (!$thiSinh) {
        $_SESSION['flash_message'] = 'Không tìm thấy thí sinh!';
        $_SESSION['flash_type'] = 'danger';
        header("Location: /quan-ly-ky-thi/thi-sinh/?kyThiId=$kyThiId");
        exit;
    }

    // xử lý form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $hoTen = trim($_POST['hoTen'] ?? '');
        $maSinhVien = trim($_POST['maSinhVien'] ?? '');

        // validate dữ liệu
        $errors = [];
        if (empty($hoTen)) {
            $errors[] = 'Họ tên không được để trống!';
        }
        if (empty($maSinhVien)) {
            $errors[] = 'Mã sinh viên không được để trống!';
        }

        if (empty($errors)) {
            // bắt đầu transaction
            $pdo->beginTransaction();

            try {
                // kiểm tra nếu đổi mã sinh viên
                if ($maSinhVien !== $thiSinh['maSinhVien']) {
                    // kiểm tra mã sinh viên mới đã tồn tại chưa
                    $stmt = $pdo->prepare('SELECT id FROM sinhVien WHERE maSinhVien = ? AND id != ?');
                    $stmt->execute([$maSinhVien, $thiSinh['sinhVienId']]);
                    if ($stmt->fetchColumn()) {
                        throw new Exception('Mã sinh viên đã tồn tại!');
                    }
                }

                // cập nhật thông tin sinh viên
                $stmt = $pdo->prepare('
                    UPDATE sinhVien
                    SET hoTen = ?, maSinhVien = ?
                    WHERE id = ?
                ');
                $stmt->execute([$hoTen, $maSinhVien, $thiSinh['sinhVienId']]);

                // lưu thay đổi
                $pdo->commit();

                $_SESSION['flash_message'] = 'Cập nhật thông tin thí sinh thành công!';
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
    $error = 'Lỗi: ' . $e->getMessage();
}

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang Chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản Lý Kỳ Thi</a></li>
        <li class="breadcrumb-item">
            <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $kyThiId; ?>">Kỳ thi: <?php echo htmlspecialchars($kyThiId); ?></a>
        </li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/thi-sinh/?kyThiId=<?php echo $kyThiId; ?>">Quản Lý Thí Sinh</a></li>
        <li class="breadcrumb-item active" aria-current="page">Sửa Thí Sinh</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Sửa Thông Tin Thí Sinh</h5>
                        <p class="text-muted mb-0">
                            Kỳ Thi: <?php echo htmlspecialchars($thiSinh['tenKyThi']); ?> | 
                            Môn Học: <?php echo htmlspecialchars($thiSinh['tenMonHoc']); ?>
                        </p>
                    </div>
                    <a href="/quan-ly-ky-thi/thi-sinh/?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Quay Lại
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <?php $_SESSION['flash_message'] = implode('<br>', $errors); $_SESSION['flash_type'] = 'danger'; ?>
                    <?php endif; ?>

                    <form method="POST" id="formSuaThiSinh">
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Mã Sinh Viên:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="maSinhVien" required
                                    value="<?php echo htmlspecialchars($thiSinh['maSinhVien']); ?>"
                                    <?php echo ($thiSinh['soBaiThi'] > 0) ? 'readonly' : ''; ?>
                                    placeholder="Nhập mã sinh viên...">
                                <?php if ($thiSinh['soBaiThi'] > 0): ?>
                                    <small class="text-muted">Không thể thay đổi mã sinh viên vì đã có bài thi</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Họ Tên:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="hoTen" required
                                    value="<?php echo htmlspecialchars($thiSinh['hoTen']); ?>"
                                    placeholder="Nhập họ tên sinh viên...">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Số Báo Danh:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" 
                                    value="<?php echo htmlspecialchars($thiSinh['soBaoDanh']); ?>" readonly>
                                <small class="text-muted">Số báo danh không thể thay đổi</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary" id="btnSuaThiSinh">
                                    <i class="fas fa-save"></i> Lưu Thay Đổi
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const formSuaThiSinh = document.getElementById('formSuaThiSinh');
const btnSuaThiSinh = document.getElementById('btnSuaThiSinh');
if (formSuaThiSinh && btnSuaThiSinh) {
    formSuaThiSinh.addEventListener('submit', function(e) {
        btnSuaThiSinh.disabled = true;
        btnSuaThiSinh.innerHTML = `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`;
    });
}
</script>

<?php include '../../include/layouts/footer.php'; ?> 