<?php
require_once '../include/config.php';

header('Content-Type: application/json');

if (!isset($_GET['nganhId']) || empty($_GET['nganhId'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số nganhId']);
    exit;
}

$nganhId = $_GET['nganhId'];

try {
    $stmt = $pdo->prepare('SELECT id, tenMonHoc FROM monHoc WHERE nganhId = ? ORDER BY tenMonHoc');
    $stmt->execute([$nganhId]);
    $monHoc = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'monHoc' => $monHoc]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?> 