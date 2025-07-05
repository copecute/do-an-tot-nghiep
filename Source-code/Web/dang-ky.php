<?php
//                       _oo0oo_
//                      o8888888o
//                      88" . "88
//                      (| -_- |)
//                      0\  =  /0
//                    ___/`---'\___
//                  .' \\|     |// '.
//                 / \\|||  :  |||// \
//                / _||||| -:- |||||- \
//               |   | \\\  -  /// |   |
//               | \_|  ''\---/''  |_/ |
//               \  .-\__  '-'  ___/-. /
//             ___'. .'  /--.--\  `. .'___
//          ."" '<  `.___\_<|>_/___.' >' "".
//         | | :  `- \`.;`\ _ /`;.`/ - ` : | |
//         \  \ `_.   \_ __\ /__ _/   .-` /  /
//     =====`-.____`.___ \_____/___.-`___.-'=====
//                       `=---='
//
//     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//            amen đà phật, không bao giờ BUG
//     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
require_once 'include/config.php';

// tắt đăng ký
header('location: dang-nhap.php');
exit;


$page_title = "Đăng ký tài khoản";
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

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4 fw-bold" style="letter-spacing:1px;">ĐĂNG KÝ TÀI KHOẢN</h3>
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center"> <?php echo $error; ?> </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success text-center"> <?php echo $success; ?> </div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="mb-3 input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="tenDangNhap" name="tenDangNhap" placeholder="Tên đăng nhập *" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3 input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="matKhau" name="matKhau" placeholder="Mật khẩu *" required>
                            </div>
                            <div class="col-md-6 mb-3 input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="xacNhanMatKhau" name="xacNhanMatKhau" placeholder="Xác nhận mật khẩu *" required>
                            </div>
                        </div>
                        <div class="mb-3 input-group">
                            <span class="input-group-text"><i class="fas fa-signature"></i></span>
                            <input type="text" class="form-control" id="hoTen" name="hoTen" placeholder="Họ và tên *" required>
                        </div>
                        <div class="mb-3 input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email">
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">Đăng ký</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <span>Đã có tài khoản? <a href="dang-nhap.php">Đăng nhập</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.card { border-radius: 18px; }
.input-group-text { background: #f5f5f5; }
.btn-lg { font-size: 1.1rem; padding: 0.75rem 1.25rem; }
</style>

<?php include 'include/layouts/footer.php'; ?> 