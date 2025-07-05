<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để thực hiện thao tác này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'id không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

// kiểm tra kyThiId
if (!isset($_GET['kyThiId']) || empty($_GET['kyThiId'])) {
    $_SESSION['flash_message'] = 'id kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$id = $_GET['id'];
$kyThiId = $_GET['kyThiId'];

$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';

try {
    // kiểm tra số báo danh tồn tại và thuộc về người dùng
    if ($isAdmin) {
        $stmt = $pdo->prepare('SELECT s.*, k.nguoiTaoId FROM soBaoDanh s JOIN kyThi k ON s.kyThiId = k.id WHERE s.id = ?');
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare('SELECT s.*, k.nguoiTaoId FROM soBaoDanh s JOIN kyThi k ON s.kyThiId = k.id WHERE s.id = ? AND k.nguoiTaoId = ?');
        $stmt->execute([$id, $_SESSION['user_id']]);
    }
    $soBaoDanh = $stmt->fetch();

    if (!$soBaoDanh) {
        $_SESSION['flash_message'] = 'không tìm thấy thí sinh!';
        $_SESSION['flash_type'] = 'danger';
        header("Location: /quan-ly-ky-thi/thi-sinh/?kyThiId=$kyThiId");
        exit;
    }

    // kiểm tra thí sinh đã có bài thi chưa
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM baiThi WHERE soBaoDanhId = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['flash_message'] = 'không thể xóa thí sinh đã có bài thi!';
        $_SESSION['flash_type'] = 'danger';
        header("Location: /quan-ly-ky-thi/thi-sinh/?kyThiId=$kyThiId");
        exit;
    }

    // bắt đầu transaction
    $pdo->beginTransaction();

    // xóa số báo danh
    $stmt = $pdo->prepare('DELETE FROM soBaoDanh WHERE id = ?');
    $stmt->execute([$id]);

    // lưu thay đổi
    $pdo->commit();

    $_SESSION['flash_message'] = 'xóa thí sinh thành công!';
    $_SESSION['flash_type'] = 'success';
    
} catch (PDOException $e) {
    // hoàn tác thay đổi nếu có lỗi
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['flash_message'] = 'lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

// chuyển hướng về trang danh sách
header("Location: /quan-ly-ky-thi/thi-sinh/?kyThiId=$kyThiId");
exit; 