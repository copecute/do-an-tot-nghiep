<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'EduDexQ - Hệ Thống thi cử'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A fully featured admin theme which can be used to build CRM, CMS, etc." />
    <meta name="author" content="Zoyothemes" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- App favicon -->
    <link rel="icon" href="/assets/images/favicon.png" type="image/x-icon">
    <!-- App css -->
    <link href="/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />
    <link href="/assets/css/copecute.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="/assets/font-awesome-6-pro/css/all.min.css">
    <script src="/assets/js/head.js"></script>
</head>
<!-- body start -->

<body data-menu-color="light" data-sidebar="default">
    <div id="toast"></div>
    <?php if (isset($_SESSION['flash_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                toast({
                    title: "<?php
                    if ($_SESSION['flash_type'] == 'success')
                        echo 'Thành công!';
                    elseif ($_SESSION['flash_type'] == 'danger')
                        echo 'Lỗi!';
                    elseif ($_SESSION['flash_type'] == 'warning')
                        echo 'Cảnh báo!';
                    else
                        echo 'Thông báo!';
                    ?>",
                message: "<?php echo addslashes($_SESSION['flash_message']); ?>",
                    type: "<?php
                    if ($_SESSION['flash_type'] == 'success')
                        echo 'success';
                    elseif ($_SESSION['flash_type'] == 'danger')
                        echo 'error';
                    elseif ($_SESSION['flash_type'] == 'warning')
                        echo 'warning';
                    else
                        echo 'info';
                    ?>",
                duration: 4000
            });
            });
        </script>
        <?php unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']); ?>
    <?php endif; ?>

    <!-- Begin page -->
    <div id="app-layout">
        <!-- Topbar Start -->
        <div class="topbar-custom">
            <div class="container-fluid">
                <div class="d-flex justify-content-between">
                    <ul class="list-unstyled topnav-menu mb-0 d-flex align-items-center">
                        <li>
                            <button class="button-toggle-menu nav-link">
                                <i class="fa-regular fa-bars" style="font-size: 22px;"></i>
                            </button>
                        </li>
                        <li class="d-none d-lg-block">
                            <?php
                            if (isset($_SESSION['ho_ten'])) {
                                date_default_timezone_set('Asia/Ho_Chi_Minh');
                                $hour = (int) date('H');
                                if ($hour >= 5 && $hour < 12) {
                                    $greeting = 'Chào buổi sáng';
                                } elseif ($hour >= 12 && $hour < 18) {
                                    $greeting = 'Chào buổi chiều';
                                } else {
                                    $greeting = 'Chào buổi tối';
                                }
                                echo '<h5 class="mb-0">' . $greeting . ', ' . htmlspecialchars($_SESSION['ho_ten']) . '</h5>';
                            }
                            ?>
                        </li>
                    </ul>

                    <div class="logo-center-responsive d-block d-lg-none">
                        <img src="/assets/images/logo.png" alt="" height="30">
                    </div>

                    <ul class="list-unstyled topnav-menu mb-0 d-flex align-items-center">
                        <!-- User Dropdown -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="dropdown notification-list topbar-dropdown">
                            <a class="nav-link dropdown-toggle nav-user me-0" data-bs-toggle="dropdown" href="#"
                                role="button" aria-haspopup="false" aria-expanded="false">
                                <img src="/assets/images/avatar.jpg" alt="user-image" class="rounded-circle" />
                            </a>
                            <div class="dropdown-menu dropdown-menu-end profile-dropdown">

                                <a type="button" class="dropdown-item notify-item" id="light-dark-mode">
                                    <i class="fa-regular fa-lightbulb-on"></i>
                                    <span></span>
                                </a>

                                <a class='dropdown-item notify-item' href='/doi-mat-khau.php'>
                                    <i class="fa-regular fa-lock"></i>
                                    <span>Đổi mật khẩu</span>
                                </a>

                                <div class="dropdown-divider"></div>
                                <a class='dropdown-item notify-item' href='/dang-xuat.php'>
                                    <i class="fa-regular fa-right-from-bracket"></i>
                                    <span>Đăng xuất</span>
                                </a>
                            </div>
                        </li>
                <?php else: ?>
                        <li>
                            <a class="btn btn-primary" href="/dang-nhap.php">
                                <i class="fa-regular fa-right-to-bracket"></i> Đăng nhập
                            </a>
                        </li>
                <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- end Topbar -->

        <!-- Left Sidebar Start -->
        <div class="app-sidebar-menu">
            <div class="h-100" data-simplebar>

                <!--- Sidemenu -->
                <div id="sidebar-menu">

                    <div class="logo-box">
                        <a class='logo logo-light' href='/'>
                            <span class="logo-sm">
                                <img src="/assets/images/logo.png" alt="" height="25">
                            </span>
                            <span class="logo-lg">
                                <img src="/assets/images/logo.png" alt="" height="45">
                            </span>
                        </a>
                        <a class='logo logo-dark' href='/'>
                            <span class="logo-sm">
                                <img src="/assets/images/logo.png" alt="" height="25">
                            </span>
                            <span class="logo-lg">
                                <img src="/assets/images/logo.png" alt="" height="45">
                            </span>
                        </a>
                    </div>

                    <ul id="side-menu">

                        <li class="menu-title">Menu</li>

                        <li>
                            <a href="/">
                                <i class="fa-regular fa-house"></i>
                                <span> Trang chủ </span>
                            </a>
                        </li>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['vai_tro'] == 'admin'): ?>
                            <li class="menu-title">Quản trị</li>

                            <li
                                class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-tai-khoan') !== false) ? 'menuitem-active' : ''; ?>">
                                <a href="/quan-ly-tai-khoan">
                                    <i class="fa-regular fa-users"></i>
                                    <span> Quản lý tài khoản </span>
                                </a>
                            </li>

                            <li
                                class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-khoa') !== false) ? 'menuitem-active' : ''; ?>">
                                <a href="/quan-ly-khoa">
                                    <i class="fa-regular fa-university"></i>
                                    <span> Quản lý khoa </span>
                                </a>
                            </li>

                            <li
                                class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-nganh') !== false) ? 'menuitem-active' : ''; ?>">
                                <a href="/quan-ly-nganh">
                                    <i class="fa-regular fa-graduation-cap"></i>
                                    <span> Quản lý ngành </span>
                                </a>
                            </li>

                            <li
                                class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-mon-hoc') !== false) ? 'menuitem-active' : ''; ?>">
                                <a href="/quan-ly-mon-hoc">
                                    <i class="fa-regular fa-book"></i>
                                    <span> Quản lý môn học </span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['user_id']) && ($_SESSION['vai_tro'] == 'admin' || $_SESSION['vai_tro'] == 'giaovien')): ?>
                            <li class="menu-title mt-2">Giáo viên</li>

                            <li
                                class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/ngan-hang-cau-hoi') !== false) ? 'menuitem-active' : ''; ?>">
                                <a href="/ngan-hang-cau-hoi">
                                    <i class="fa-regular fa-question-circle"></i>
                                    <span> Ngân hàng câu hỏi </span>
                                </a>
                            </li>

                            <li
                                class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/quan-ly-ky-thi') !== false) ? 'menuitem-active' : ''; ?>">
                                <a href="/quan-ly-ky-thi">
                                    <i class="fa-regular fa-calendar-alt"></i>
                                    <span> Quản lý kỳ thi </span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <li>
                                <a href="/dang-nhap.php">
                                <i class="fa-regular fa-right-to-bracket"></i>
                                    <span> Đăng nhập </span>
                                </a>
                            </li>
                    <?php endif; ?>

                    </ul>
                </div>
                <!-- End Sidebar -->

                <div class="clearfix"></div>

            </div>
        </div>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">