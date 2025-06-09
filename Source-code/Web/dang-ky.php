<?php
require_once 'include/config.php';

// chuyển hướng nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// xử lý form đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDangNhap = $_POST['tenDangNhap'] ?? '';
    $matKhau = $_POST['matKhau'] ?? '';
    $xacNhanMatKhau = $_POST['xacNhanMatKhau'] ?? '';
    $hoTen = $_POST['hoTen'] ?? '';
    $email = $_POST['email'] ?? '';
    $vaiTro = 'giaovien'; // vai trò mặc định là giáo viên
    
    // kiểm tra đầu vào
    if (empty($tenDangNhap) || empty($matKhau) || empty($xacNhanMatKhau) || empty($hoTen)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif ($matKhau !== $xacNhanMatKhau) {
        $error = 'Mật khẩu và xác nhận mật khẩu không khớp';
    } elseif (strlen($matKhau) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        try {
            // kiểm tra tên đăng nhập đã tồn tại chưa
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM taiKhoan WHERE tenDangNhap = ?');
            $stmt->execute([$tenDangNhap]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên đăng nhập khác';
            } else {
                // mã hóa mật khẩu
                $hashedPassword = password_hash($matKhau, PASSWORD_DEFAULT);
                
                // thêm người dùng mới
                $stmt = $pdo->prepare('INSERT INTO taiKhoan (tenDangNhap, matKhau, vaiTro, hoTen, email) VALUES (?, ?, ?, ?, ?)');
                $result = $stmt->execute([$tenDangNhap, $hashedPassword, $vaiTro, $hoTen, $email]);
                
                if ($result) {
                    $success = 'Đăng ký tài khoản thành công! Vui lòng đăng nhập.';
                    // chuyển hướng đến trang đăng nhập sau khi đăng ký thành công
                    $_SESSION['flash_message'] = 'Đăng ký tài khoản thành công! Vui lòng đăng nhập.';
                    $_SESSION['flash_type'] = 'success';
                    header('Location: dang-nhap.php');
                    exit;
                } else {
                    $error = 'Có lỗi xảy ra khi đăng ký tài khoản';
                }
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

include 'include/layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">Đăng Ký Tài Khoản</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="tenDangNhap" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tenDangNhap" name="tenDangNhap" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="matKhau" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="matKhau" name="matKhau" required>
                            <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="xacNhanMatKhau" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="xacNhanMatKhau" name="xacNhanMatKhau" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hoTen" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="hoTen" name="hoTen" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Đăng ký</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Đã có tài khoản? <a href="dang-nhap.php">Đăng nhập</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'include/layouts/footer.php'; ?> 