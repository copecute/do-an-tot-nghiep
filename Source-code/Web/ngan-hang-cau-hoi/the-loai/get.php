<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// kiểm tra tham số môn học
if (!isset($_GET['monHocId']) || empty($_GET['monHocId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing subject id']);
    exit;
}

$monHocId = $_GET['monHocId'];

try {
    // lấy danh sách thể loại theo môn học
    $stmt = $pdo->prepare('SELECT id, tenTheLoai FROM theLoaiCauHoi WHERE monHocId = ? ORDER BY tenTheLoai');
    $stmt->execute([$monHocId]);
    $dsTheLoai = $stmt->fetchAll();

    header('Content-Type: application/json');
    echo json_encode($dsTheLoai);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 