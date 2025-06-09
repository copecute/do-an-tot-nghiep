<?php
require_once '../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id câu hỏi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'id câu hỏi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /ngan-hang-cau-hoi');
    exit;
}

$cauHoiId = $_GET['id'];

try {
    $pdo->beginTransaction();

    // kiểm tra xem câu hỏi có trong đề thi nào không
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM deThiCauHoi WHERE cauHoiId = ?');
    $stmt->execute([$cauHoiId]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $_SESSION['flash_message'] = 'không thể xóa câu hỏi này vì đã được sử dụng trong đề thi!';
        $_SESSION['flash_type'] = 'danger';
    } else {
        // xóa các đáp án của câu hỏi
        $stmt = $pdo->prepare('DELETE FROM dapAn WHERE cauHoiId = ?');
        $stmt->execute([$cauHoiId]);

        // xóa câu hỏi
        $stmt = $pdo->prepare('DELETE FROM cauHoi WHERE id = ?');
        $stmt->execute([$cauHoiId]);

        $pdo->commit();

        $_SESSION['flash_message'] = 'xóa câu hỏi thành công!';
        $_SESSION['flash_type'] = 'success';
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = 'lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

header('Location: /ngan-hang-cau-hoi');
exit; 