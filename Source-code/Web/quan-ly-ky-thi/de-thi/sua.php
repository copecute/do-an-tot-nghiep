<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id đề thi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'id đề thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$deThiId = $_GET['id'];

try {
    // lấy thông tin đề thi
    $stmt = $pdo->prepare('
        SELECT d.*, k.tenKyThi, m.tenMonHoc,
            (SELECT COUNT(*) FROM baiThi b JOIN soBaoDanh s ON b.soBaoDanhId = s.id WHERE b.deThiId = d.id) as soBaiThi
        FROM deThi d 
        JOIN kyThi k ON d.kyThiId = k.id
        JOIN monHoc m ON k.monHocId = m.id
        WHERE d.id = ? AND d.nguoiTaoId = ?
    ');
    $stmt->execute([$deThiId, $_SESSION['user_id']]);
    $deThi = $stmt->fetch();

    if (!$deThi) {
        $_SESSION['flash_message'] = 'không tìm thấy đề thi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    // kiểm tra nếu đã có bài thi thì không cho sửa
    if ($deThi['soBaiThi'] > 0) {
        $_SESSION['flash_message'] = 'không thể sửa đề thi vì đã có bài thi!';
        $_SESSION['flash_type'] = 'danger';
        header("Location: /quan-ly-ky-thi/de-thi/?kyThiId={$deThi['kyThiId']}");
        exit;
    }

    // xử lý form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tenDeThi = trim($_POST['tenDeThi'] ?? '');
        $thoiGian = intval($_POST['thoiGian'] ?? 0);

        // validate dữ liệu
        $errors = [];
        if (empty($tenDeThi)) {
            $errors[] = 'tên đề thi không được để trống!';
        }
        if ($thoiGian < 1) {
            $errors[] = 'thời gian làm bài phải lớn hơn 0!';
        }

        if (empty($errors)) {
            // cập nhật thông tin đề thi
            $stmt = $pdo->prepare('
                UPDATE deThi 
                SET tenDeThi = ?, thoiGian = ?
                WHERE id = ? AND nguoiTaoId = ?
            ');
            $stmt->execute([$tenDeThi, $thoiGian, $deThiId, $_SESSION['user_id']]);

            $_SESSION['flash_message'] = 'cập nhật đề thi thành công!';
            $_SESSION['flash_type'] = 'success';
            header("Location: /quan-ly-ky-thi/de-thi/?kyThiId={$deThi['kyThiId']}");
            exit;
        }
    }
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
}

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $deThi['kyThiId']; ?>">Quản lý đề thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Sửa đề thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">sửa đề thi</h5>
                        <p class="text-muted mb-0">
                            kỳ thi: <?php echo htmlspecialchars($deThi['tenKyThi']); ?> | 
                            môn học: <?php echo htmlspecialchars($deThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $deThi['kyThiId']; ?>" class="btn btn-outline-secondary">
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

                    <form method="POST">
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">tên đề thi:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="tenDeThi" required
                                    value="<?php echo htmlspecialchars($deThi['tenDeThi']); ?>"
                                    placeholder="nhập tên đề thi...">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">thời gian làm bài (phút):</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="thoiGian" required
                                    value="<?php echo $deThi['thoiGian']; ?>"
                                    min="1" placeholder="nhập thời gian làm bài...">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">số câu hỏi:</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" value="<?php echo $deThi['soCau']; ?>" readonly>
                                <small class="text-muted">không thể thay đổi số câu hỏi</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">hình thức tạo:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" 
                                    value="<?php echo $deThi['isTuDong'] ? 'tự động' : 'thủ công'; ?>" readonly>
                                <small class="text-muted">không thể thay đổi hình thức tạo đề</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> lưu thay đổi
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