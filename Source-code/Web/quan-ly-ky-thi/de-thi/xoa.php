<?php
require_once __DIR__ . '/../../include/config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để thực hiện thao tác này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'Thiếu ID đề thi!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi/de-thi/');
    exit;
}

$id = intval($_GET['id']);

// Lấy kyThiId của đề thi trước khi xóa
$stmt = $pdo->prepare('SELECT kyThiId FROM deThi WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
$kyThiId = $row ? $row['kyThiId'] : null;

try {
    // Kiểm tra có bài thi nào đã nộp với đề này chưa
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM baiThi WHERE deThiId = ?');
    $stmt->execute([$id]);
    $totalBaiThi = $stmt->fetch()['total'];

    if ($totalBaiThi > 0) {
        $_SESSION['flash_message'] = 'Không thể xóa đề thi vì đã có thí sinh nộp bài thi này!';
        $_SESSION['flash_type'] = 'danger';
    } else {
        // Xóa tất cả liên kết câu hỏi của đề thi này
        $stmt = $pdo->prepare('DELETE FROM deThiCauHoi WHERE deThiId = ?');
        $stmt->execute([$id]);

        // Sau đó xóa đề thi
        $stmt = $pdo->prepare('DELETE FROM deThi WHERE id = ?');
        $stmt->execute([$id]);

        $_SESSION['flash_message'] = 'Xóa đề thi thành công!';
        $_SESSION['flash_type'] = 'success';
    }
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Lỗi khi xóa đề thi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

// Chuyển hướng về danh sách đề thi của kỳ thi vừa xóa
if ($kyThiId) {
    header('Location: /quan-ly-ky-thi/de-thi/?kyThiId=' . $kyThiId);
} else {
    header('Location: /quan-ly-ky-thi/de-thi/');
}
exit; 