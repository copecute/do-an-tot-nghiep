<?php
require_once 'include/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Vui lòng đăng nhập để đổi mật khẩu!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: dang-nhap.php');
    exit;
}

$error = '';
$success = '';

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matKhauCu = $_POST['matKhauCu'] ?? '';
    $matKhauMoi = $_POST['matKhauMoi'] ?? '';
    $xacNhanMatKhau = $_POST['xacNhanMatKhau'] ?? '';
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($matKhauCu) || empty($matKhauMoi) || empty($xacNhanMatKhau)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } elseif (strlen($matKhauMoi) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } elseif ($matKhauMoi !== $xacNhanMatKhau) {
        $error = 'Xác nhận mật khẩu không khớp!';
    } else {
        try {
            // Lấy thông tin tài khoản
            $stmt = $pdo->prepare('SELECT matKhau FROM taiKhoan WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Không tìm thấy thông tin tài khoản!';
            } elseif (!password_verify($matKhauCu, $user['matKhau'])) {
                $error = 'Mật khẩu hiện tại không chính xác!';
            } else {
                // Cập nhật mật khẩu mới
                $hashedPassword = password_hash($matKhauMoi, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE taiKhoan SET matKhau = ? WHERE id = ?');
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                
                $success = 'Đổi mật khẩu thành công!';
                
                // Lưu thông báo vào session để hiển thị sau khi chuyển hướng
                $_SESSION['flash_message'] = 'Đổi mật khẩu thành công!';
                $_SESSION['flash_type'] = 'success';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

include 'include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Đổi mật khẩu</li>
    </ol>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="text-center mb-4">
                <h2 class="mb-2">Đổi mật khẩu</h2>
                <p class="text-muted">Vui lòng nhập mật khẩu hiện tại và mật khẩu mới</p>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="matKhauCu" name="matKhauCu" 
                                       placeholder="Mật khẩu hiện tại" required>
                                <label for="matKhauCu">
                                    <i class="fas fa-lock me-1 text-muted"></i> Mật khẩu hiện tại
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="matKhauMoi" name="matKhauMoi" 
                                       placeholder="Mật khẩu mới" required 
                                       pattern=".{6,}" title="Mật khẩu phải có ít nhất 6 ký tự">
                                <label for="matKhauMoi">
                                    <i class="fas fa-key me-1 text-muted"></i> Mật khẩu mới
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Mật khẩu phải có ít nhất 6 ký tự
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="xacNhanMatKhau" name="xacNhanMatKhau" 
                                       placeholder="Xác nhận mật khẩu mới" required>
                                <label for="xacNhanMatKhau">
                                    <i class="fas fa-check-double me-1 text-muted"></i> Xác nhận mật khẩu mới
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Lưu thay đổi
                            </button>
                            <a href="/" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại trang chủ
                            </a>
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
    
    // kiểm tra mật khẩu xác nhận
    var matKhauMoi = document.getElementById('matKhauMoi')
    var xacNhanMatKhau = document.getElementById('xacNhanMatKhau')
    
    function validatePassword() {
        if (matKhauMoi.value != xacNhanMatKhau.value) {
            xacNhanMatKhau.setCustomValidity('Mật khẩu xác nhận không khớp')
        } else {
            xacNhanMatKhau.setCustomValidity('')
        }
    }
    
    matKhauMoi.onchange = validatePassword
    xacNhanMatKhau.onkeyup = validatePassword
})()

// toggle password visibility
document.querySelectorAll('input[type="password"]').forEach(input => {
    const toggleBtn = document.createElement('button')
    toggleBtn.type = 'button'
    toggleBtn.className = 'btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2'
    toggleBtn.innerHTML = '<i class="fas fa-eye"></i>'
    toggleBtn.style.zIndex = '5'
    
    toggleBtn.addEventListener('click', () => {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password'
        input.setAttribute('type', type)
        toggleBtn.innerHTML = `<i class="fas fa-eye${type === 'password' ? '' : '-slash'}"></i>`
    })
    
    input.parentElement.style.position = 'relative'
    input.parentElement.appendChild(toggleBtn)
})
</script>

<?php include 'include/layouts/footer.php'; ?>
