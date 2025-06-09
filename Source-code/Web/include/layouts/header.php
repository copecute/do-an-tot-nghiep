<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduDexQ - Hệ Thống Quản Lý Đề Thi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/styles/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-book-open text-primary"></i>
                <span class="d-none d-sm-inline">EduDexQ</span>
                <span class="d-inline d-sm-none">EDQ</span>
            </a>

            <div class="navbar-actions order-lg-last">
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown user-dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button"
                        id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['ho_ten']); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/doi-mat-khau.php"><i class="fas fa-key me-2"></i>Đổi mật
                                khẩu</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="/dang-xuat.php"><i
                                    class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                    </ul>
                </div>
                <?php else: ?>
                <a href="/dang-nhap.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-sign-in-alt me-1"></i><span class="d-none d-sm-inline">Đăng nhập</span>
                </a>
                <?php endif; ?>

                <button class="navbar-toggler ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fas fa-bars text-primary"></i>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '/index.php') ? 'active' : ''; ?>"
                            href="/">
                            <i class="fas fa-home me-1"></i> Trang chủ
                        </a>
                    </li>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['vai_tro'] == 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-tai-khoan') !== false || strpos($_SERVER['REQUEST_URI'], '/quan-ly-khoa') !== false || strpos($_SERVER['REQUEST_URI'], '/quan-ly-nganh') !== false || strpos($_SERVER['REQUEST_URI'], '/quan-ly-mon-hoc') !== false) ? 'active' : ''; ?>"
                            href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs me-1"></i> Quản trị
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-tai-khoan') !== false) ? 'active' : ''; ?>"
                                    href="/quan-ly-tai-khoan"><i class="fas fa-users me-2"></i>Quản lý tài khoản</a>
                            </li>
                            <li><a class="dropdown-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-khoa') !== false) ? 'active' : ''; ?>"
                                    href="/quan-ly-khoa"><i class="fas fa-university me-2"></i>Quản lý khoa</a></li>
                            <li><a class="dropdown-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-nganh') !== false) ? 'active' : ''; ?>"
                                    href="/quan-ly-nganh"><i class="fas fa-graduation-cap me-2"></i>Quản lý ngành</a>
                            </li>
                            <li><a class="dropdown-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-mon-hoc') !== false) ? 'active' : ''; ?>"
                                    href="/quan-ly-mon-hoc"><i class="fas fa-book me-2"></i>Quản lý môn học</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['vai_tro'] == 'admin' || $_SESSION['vai_tro'] == 'giaovien')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo (strpos($_SERVER['REQUEST_URI'], '/ngan-hang-cau-hoi') !== false || strpos($_SERVER['REQUEST_URI'], '/quan-ly-ky-thi') !== false) ? 'active' : ''; ?>"
                            href="#" id="teacherDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-chalkboard-teacher me-1"></i> Giáo viên
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="teacherDropdown">
                            <li><a class="dropdown-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/ngan-hang-cau-hoi') !== false) ? 'active' : ''; ?>"
                                    href="/ngan-hang-cau-hoi"><i class="fas fa-question-circle me-2"></i>Ngân hàng câu
                                    hỏi</a></li>
                            <li><a class="dropdown-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-ky-thi') !== false) ? 'active' : ''; ?>"
                                    href="/quan-ly-ky-thi"><i class="fas fa-file-alt me-2"></i>Quản lý kỳ thi</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Toast container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="margin-top: 25px;">
        <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="toast align-items-center text-white bg-<?php echo $_SESSION['flash_type']; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    <?php if ($_SESSION['flash_type'] == 'success'): ?>
                        <i class="fas fa-check-circle me-2"></i>
                    <?php elseif ($_SESSION['flash_type'] == 'danger'): ?>
                        <i class="fas fa-exclamation-circle me-2"></i>
                    <?php elseif ($_SESSION['flash_type'] == 'warning'): ?>
                        <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php else: ?>
                        <i class="fas fa-info-circle me-2"></i>
                    <?php endif; ?>
                    <?php 
                    echo $_SESSION['flash_message']; 
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="content-wrapper">
        <div class="container">