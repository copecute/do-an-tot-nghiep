<?php
require_once '../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id kỳ thi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'id kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$kyThiId = $_GET['id'];

$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';
if ($isAdmin) {
    $stmt = $pdo->prepare('SELECT * FROM kyThi WHERE id = ?');
    $stmt->execute([$kyThiId]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM kyThi WHERE id = ? AND nguoiTaoId = ?');
    $stmt->execute([$kyThiId, $_SESSION['user_id']]);
}
$kyThi = $stmt->fetch();
if (!$kyThi) {
    $_SESSION['flash_message'] = 'không tìm thấy kỳ thi hoặc bạn không có quyền xóa!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

try {
    // kiểm tra ràng buộc với bài thi
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count 
        FROM baiThi b 
        JOIN soBaoDanh s ON b.soBaoDanhId = s.id 
        WHERE s.kyThiId = ?
    ');
    $stmt->execute([$kyThiId]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        $_SESSION['flash_message'] = 'không thể xóa kỳ thi vì đã có bài thi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    // bắt đầu transaction
    $pdo->beginTransaction();

    // xóa các đề thi - câu hỏi
    $stmt = $pdo->prepare('
        DELETE FROM deThiCauHoi 
        WHERE deThiId IN (SELECT id FROM deThi WHERE kyThiId = ?)
    ');
    $stmt->execute([$kyThiId]);

    // xóa các đề thi
    $stmt = $pdo->prepare('DELETE FROM deThi WHERE kyThiId = ?');
    $stmt->execute([$kyThiId]);

    // xóa các số báo danh
    $stmt = $pdo->prepare('DELETE FROM soBaoDanh WHERE kyThiId = ?');
    $stmt->execute([$kyThiId]);

    // xóa kỳ thi
    $stmt = $pdo->prepare('DELETE FROM kyThi WHERE id = ?');
    $stmt->execute([$kyThiId]);

    $pdo->commit();

    $_SESSION['flash_message'] = 'xóa kỳ thi thành công!';
    $_SESSION['flash_type'] = 'success';
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = 'lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

if ($error) {
    $_SESSION['flash_message'] = $error;
    $_SESSION['flash_type'] = 'danger';
}
if ($success) {
    $_SESSION['flash_message'] = $success;
    $_SESSION['flash_type'] = 'success';
}

header('Location: /quan-ly-ky-thi');
exit; 