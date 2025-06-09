<?php
session_start();

// Cấu hình cơ sở dữ liệu
$host = 'localhost';
$dbname = 'edudexq';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    die('Không thể kết nối đến cơ sở dữ liệu: ' . $e->getMessage());
}

// Thiết lập múi giờ mặc định là Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');
?>
