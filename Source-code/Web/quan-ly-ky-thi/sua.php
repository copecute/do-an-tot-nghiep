<?php
require_once '../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$error = '';
$success = '';

// kiểm tra id kỳ thi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'id kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$kyThiId = $_GET['id'];

// lấy thông tin kỳ thi
try {
    $stmt = $pdo->prepare('SELECT * FROM kyThi WHERE id = ? AND nguoiTaoId = ?');
    $stmt->execute([$kyThiId, $_SESSION['user_id']]);
    $kyThi = $stmt->fetch();

    if (!$kyThi) {
        $_SESSION['flash_message'] = 'không tìm thấy kỳ thi hoặc bạn không có quyền sửa!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    // lấy danh sách môn học
    $stmt = $pdo->query('
        SELECT m.*, n.tenNganh, k.tenKhoa 
        FROM monHoc m 
        JOIN nganh n ON m.nganhId = n.id 
        JOIN khoa k ON n.khoaId = k.id 
        ORDER BY k.tenKhoa, n.tenNganh, m.tenMonHoc
    ');
    $dsMonHoc = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
    $dsMonHoc = [];
}

// xử lý cập nhật kỳ thi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenKyThi = trim($_POST['tenKyThi'] ?? '');
    $monHocId = $_POST['monHocId'] ?? '';
    $thoiGianBatDau = $_POST['thoiGianBatDau'] ?? '';
    $thoiGianKetThuc = $_POST['thoiGianKetThuc'] ?? '';

    // validate dữ liệu
    if (empty($tenKyThi) || empty($monHocId) || empty($thoiGianBatDau) || empty($thoiGianKetThuc)) {
        $error = 'vui lòng nhập đầy đủ thông tin!';
    } elseif (strtotime($thoiGianBatDau) >= strtotime($thoiGianKetThuc)) {
        $error = 'thời gian kết thúc phải sau thời gian bắt đầu!';
    } else {
        try {
            $stmt = $pdo->prepare('
                UPDATE kyThi 
                SET tenKyThi = ?, monHocId = ?, thoiGianBatDau = ?, thoiGianKetThuc = ? 
                WHERE id = ?
            ');
            $stmt->execute([$tenKyThi, $monHocId, $thoiGianBatDau, $thoiGianKetThuc, $kyThiId]);
            
            $_SESSION['flash_message'] = 'cập nhật kỳ thi thành công!';
            $_SESSION['flash_type'] = 'success';
            header('Location: /quan-ly-ky-thi');
            exit;
        } catch (PDOException $e) {
            $error = 'lỗi: ' . $e->getMessage();
        }
    }
}

include '../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Sửa kỳ thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">sửa kỳ thi</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post" id="formSuaKyThi">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="tenKyThi" class="form-label">tên kỳ thi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tenKyThi" name="tenKyThi" required
                                    value="<?php echo isset($_POST['tenKyThi']) ? htmlspecialchars($_POST['tenKyThi']) : htmlspecialchars($kyThi['tenKyThi']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="monHocId" class="form-label">môn học <span class="text-danger">*</span></label>
                                <select class="form-select" id="monHocId" name="monHocId" required>
                                    <option value="">chọn môn học</option>
                                    <?php foreach ($dsMonHoc as $monHoc): ?>
                                        <option value="<?php echo $monHoc['id']; ?>" 
                                            <?php echo (isset($_POST['monHocId']) && $_POST['monHocId'] == $monHoc['id']) || 
                                                (!isset($_POST['monHocId']) && $kyThi['monHocId'] == $monHoc['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($monHoc['tenMonHoc'] . ' - ' . $monHoc['tenNganh']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="thoiGianBatDau" class="form-label">thời gian bắt đầu <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="thoiGianBatDau" name="thoiGianBatDau" required
                                    value="<?php echo isset($_POST['thoiGianBatDau']) ? $_POST['thoiGianBatDau'] : date('Y-m-d\TH:i', strtotime($kyThi['thoiGianBatDau'])); ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="thoiGianKetThuc" class="form-label">thời gian kết thúc <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="thoiGianKetThuc" name="thoiGianKetThuc" required
                                    value="<?php echo isset($_POST['thoiGianKetThuc']) ? $_POST['thoiGianKetThuc'] : date('Y-m-d\TH:i', strtotime($kyThi['thoiGianKetThuc'])); ?>">
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="/quan-ly-ky-thi" class="btn btn-secondary me-2">hủy</a>
                            <button type="submit" class="btn btn-primary">lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formSuaKyThi').addEventListener('submit', function(e) {
    const thoiGianBatDau = new Date(document.getElementById('thoiGianBatDau').value);
    const thoiGianKetThuc = new Date(document.getElementById('thoiGianKetThuc').value);
    
    if (thoiGianBatDau >= thoiGianKetThuc) {
        e.preventDefault();
        alert('thời gian kết thúc phải sau thời gian bắt đầu!');
        return;
    }
});
</script>

<?php include '../include/layouts/footer.php'; ?> 