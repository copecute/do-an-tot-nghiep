<?php
// Lấy danh sách thể loại theo môn học, trả về JSON
require_once '../../include/config.php';
header('Content-Type: application/json; charset=utf-8');

$monHocId = isset($_GET['monHocId']) ? intval($_GET['monHocId']) : 0;
if ($monHocId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare('SELECT id, tenTheLoai FROM theloaicauhoi WHERE monHocId = ? ORDER BY tenTheLoai ASC');
$stmt->execute([$monHocId]);
$ds = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($ds); 