<?php
require_once '../include/config.php';

header('Content-Type: application/json');

if (!isset($_GET['khoaId']) || empty($_GET['khoaId'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số khoaId']);
    exit;
}

$khoaId = $_GET['khoaId'];

try {
    $stmt = $pdo->prepare('SELECT id, tenNganh FROM nganh WHERE khoaId = ? ORDER BY tenNganh');
    $stmt->execute([$khoaId]);
    $nganh = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'nganh' => $nganh]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>
