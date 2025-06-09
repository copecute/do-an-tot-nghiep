<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id thể loại
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'id thể loại không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /ngan-hang-cau-hoi/the-loai');
    exit;
}

$theLoaiId = $_GET['id'];

try {
    // kiểm tra xem thể loại có câu hỏi nào không
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM cauHoi WHERE theLoaiId = ?');
    $stmt->execute([$theLoaiId]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $_SESSION['flash_message'] = 'không thể xóa thể loại này vì đã có câu hỏi thuộc thể loại!';
        $_SESSION['flash_type'] = 'danger';
    } else {
        // xóa thể loại
        $stmt = $pdo->prepare('DELETE FROM theLoaiCauHoi WHERE id = ?');
        $stmt->execute([$theLoaiId]);

        $_SESSION['flash_message'] = 'xóa thể loại thành công!';
        $_SESSION['flash_type'] = 'success';
    }
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

header('Location: /ngan-hang-cau-hoi/the-loai');
exit; 