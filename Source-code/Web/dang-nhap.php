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
$page_title = "Đăng nhập hệ thống";
// chuyển hướng nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// xử lý form đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDangNhap = $_POST['tenDangNhap'] ?? '';
    $matKhau = $_POST['matKhau'] ?? '';
    
    if (empty($tenDangNhap) || empty($matKhau)) {
        $_SESSION['flash_message'] = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu';
        $_SESSION['flash_type'] = 'danger';
        header('Location: dang-nhap.php');
        exit;
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM taiKhoan WHERE tenDangNhap = ? AND trangThai = 1');
            $stmt->execute([$tenDangNhap]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($matKhau, $user['matKhau'])) {
                // đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['ten_dang_nhap'] = $user['tenDangNhap'];
                $_SESSION['ho_ten'] = $user['hoTen'];
                $_SESSION['vai_tro'] = $user['vaiTro'];
                
                $_SESSION['flash_message'] = 'Đăng nhập thành công!';
                $_SESSION['flash_type'] = 'success';
                
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['flash_message'] = 'Tên đăng nhập hoặc mật khẩu không đúng';
                $_SESSION['flash_type'] = 'danger';
                header('Location: dang-nhap.php');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = 'Lỗi hệ thống: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: dang-nhap.php');
            exit;
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
                    <h3 class="text-center mb-4 fw-bold" style="letter-spacing:1px;">ĐĂNG NHẬP HỆ THỐNG</h3>
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> text-center"> 
                            <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="" class="needs-validation" novalidate id="formDangNhap">
                        <div class="mb-3 input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="tenDangNhap" name="tenDangNhap" placeholder="Tên đăng nhập *" required value="<?php echo htmlspecialchars($_POST['tenDangNhap'] ?? ''); ?>">
                        </div>
                        <div class="mb-4 input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="matKhau" name="matKhau" placeholder="Mật khẩu *" required>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="btnDangNhap">
                                <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                            </button>
                        </div>
                    </form>
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
<script>
// form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
// loading khi bấm đăng nhập
const form = document.getElementById('formDangNhap');
const btn = document.getElementById('btnDangNhap');
form.addEventListener('submit', function(e) {
    if (form.checkValidity()) {
        btn.disabled = true;
        btn.innerHTML = `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span>\n            <span class=\"visually-hidden\" role=\"status\">Loading...</span>`;
    }
});
</script>

<?php include 'include/layouts/footer.php'; ?> 