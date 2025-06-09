<?php
// bắt đầu session nếu chưa bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// xóa tất cả biến session
$_SESSION = [];

// hủy session
session_destroy();

// thiết lập thông báo cho trang tiếp theo
session_start();
$_SESSION['flash_message'] = 'Đăng xuất thành công!';
$_SESSION['flash_type'] = 'success';

// chuyển hướng đến trang đăng nhập
header('Location: dang-nhap.php');
exit;
?>