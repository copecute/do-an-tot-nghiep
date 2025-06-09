<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$error = '';
$success = '';

// lấy danh sách môn học
try {
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

// xử lý thêm thể loại
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monHocId = $_POST['monHocId'] ?? '';
    $tenTheLoai = trim($_POST['tenTheLoai'] ?? '');

    // validate dữ liệu
    if (empty($monHocId) || empty($tenTheLoai)) {
        $error = 'vui lòng nhập đầy đủ thông tin!';
    } else {
        try {
            // kiểm tra thể loại đã tồn tại trong môn học chưa
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM theLoaiCauHoi WHERE monHocId = ? AND tenTheLoai = ?');
            $stmt->execute([$monHocId, $tenTheLoai]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = 'thể loại này đã tồn tại trong môn học!';
            } else {
                // thêm thể loại mới
                $stmt = $pdo->prepare('INSERT INTO theLoaiCauHoi (monHocId, tenTheLoai) VALUES (?, ?)');
                $stmt->execute([$monHocId, $tenTheLoai]);

                $_SESSION['flash_message'] = 'thêm thể loại thành công!';
                $_SESSION['flash_type'] = 'success';
                header('Location: /ngan-hang-cau-hoi/the-loai');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'lỗi: ' . $e->getMessage();
        }
    }
}

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/ngan-hang-cau-hoi">Ngân hàng câu hỏi</a></li>
        <li class="breadcrumb-item"><a href="/ngan-hang-cau-hoi/the-loai">Quản lý thể loại</a></li>
        <li class="breadcrumb-item active" aria-current="page">Thêm thể loại</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">thêm thể loại câu hỏi mới</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="monHocId" class="form-label">môn học <span class="text-danger">*</span></label>
                            <select class="form-select" id="monHocId" name="monHocId" required>
                                <option value="">chọn môn học</option>
                                <?php foreach ($dsMonHoc as $monHoc): ?>
                                    <option value="<?php echo $monHoc['id']; ?>" <?php echo isset($_POST['monHocId']) && $_POST['monHocId'] == $monHoc['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($monHoc['tenMonHoc'] . ' - ' . $monHoc['tenNganh'] . ' - ' . $monHoc['tenKhoa']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tenTheLoai" class="form-label">tên thể loại <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tenTheLoai" name="tenTheLoai" value="<?php echo isset($_POST['tenTheLoai']) ? htmlspecialchars($_POST['tenTheLoai']) : ''; ?>" required>
                        </div>

                        <div class="text-end">
                            <a href="/ngan-hang-cau-hoi/the-loai" class="btn btn-secondary me-2">hủy</a>
                            <button type="submit" class="btn btn-primary">thêm thể loại</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../include/layouts/footer.php'; ?> 