<?php
require_once 'include/config.php';

// chuyển hướng nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// xử lý form đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDangNhap = $_POST['tenDangNhap'] ?? '';
    $matKhau = $_POST['matKhau'] ?? '';
    
    if (empty($tenDangNhap) || empty($matKhau)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu';
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
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

include 'include/layouts/header.php';
?>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-75">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="text-center mb-4">
                <i class="fas fa-book-open text-primary" style="font-size: 3rem;"></i>
                <h2 class="mt-3 mb-4">Đăng nhập EduDexQ</h2>
            </div>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="tenDangNhap" name="tenDangNhap" 
                                       placeholder="Tên đăng nhập" required 
                                       value="<?php echo htmlspecialchars($_POST['tenDangNhap'] ?? ''); ?>">
                                <label for="tenDangNhap">
                                    <i class="fas fa-user me-1 text-muted"></i> Tên đăng nhập
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="matKhau" name="matKhau" 
                                       placeholder="Mật khẩu" required>
                                <label for="matKhau">
                                    <i class="fas fa-lock me-1 text-muted"></i> Mật khẩu
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
</script>

<?php include 'include/layouts/footer.php'; ?> 