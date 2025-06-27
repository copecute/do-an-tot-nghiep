<?php
require_once '../../include/config.php';
require_once '../cors.php';
// chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'chỉ hỗ trợ phương thức POST',
        'data' => null
    ]);
    exit;
}

// lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

// kiểm tra dữ liệu đầu vào
if (!isset($data['maSinhVien']) || !isset($data['soBaoDanh']) || empty($data['maSinhVien']) || empty($data['soBaoDanh'])) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'thiếu thông tin đăng nhập (mã sinh viên hoặc số báo danh)',
        'data' => null
    ]);
    exit;
}

$maSinhVien = $data['maSinhVien'];
$soBaoDanh = $data['soBaoDanh'];

try {
    // kiểm tra thông tin đăng nhập
    $stmt = $pdo->prepare('
        SELECT s.id as soBaoDanhId, s.kyThiId, sv.id as sinhVienId, sv.hoTen, 
            k.tenKyThi, k.thoiGianBatDau, k.thoiGianKetThuc, m.tenMonHoc, m.id as monHocId
        FROM soBaoDanh s
        JOIN sinhVien sv ON s.sinhVienId = sv.id
        JOIN kyThi k ON s.kyThiId = k.id
        JOIN monHoc m ON k.monHocId = m.id
        WHERE sv.maSinhVien = ? AND s.soBaoDanh = ?
    ');
    $stmt->execute([$maSinhVien, $soBaoDanh]);
    $thiSinh = $stmt->fetch();

    if (!$thiSinh) {
        http_response_code(401); // Unauthorized
        echo json_encode([
            'success' => false,
            'message' => 'thông tin đăng nhập không chính xác',
            'data' => null
        ]);
        exit;
    }

    // kiểm tra thời gian thi
    $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    $thoiGianBatDau = new DateTime($thiSinh['thoiGianBatDau'], new DateTimeZone('Asia/Ho_Chi_Minh'));
    $thoiGianKetThuc = new DateTime($thiSinh['thoiGianKetThuc'], new DateTimeZone('Asia/Ho_Chi_Minh'));

    if ($now < $thoiGianBatDau) {
        http_response_code(403); // Forbidden
        echo json_encode([
            'success' => false,
            'message' => 'kỳ thi chưa bắt đầu',
            'data' => [
                'thoiGianBatDau' => $thiSinh['thoiGianBatDau'],
                'thoiGianHienTai' => $now->format('Y-m-d H:i:s'),
                'timezone' => 'Asia/Ho_Chi_Minh (GMT+7)'
            ]
        ]);
        exit;
    }

    if ($now > $thoiGianKetThuc) {
        http_response_code(403); // Forbidden
        echo json_encode([
            'success' => false,
            'message' => 'kỳ thi đã kết thúc',
            'data' => [
                'thoiGianKetThuc' => $thiSinh['thoiGianKetThuc'],
                'thoiGianHienTai' => $now->format('Y-m-d H:i:s'),
                'timezone' => 'Asia/Ho_Chi_Minh (GMT+7)'
            ]
        ]);
        exit;
    }

    // kiểm tra thí sinh đã làm bài chưa
    $stmt = $pdo->prepare('
        SELECT b.id, b.deThiId, b.thoiGianNop, b.diem, d.tenDeThi, d.thoiGian as thoiGianLamBai
        FROM baiThi b
        JOIN deThi d ON b.deThiId = d.id
        WHERE b.soBaoDanhId = ?
    ');
    $stmt->execute([$thiSinh['soBaoDanhId']]);
    $baiThi = $stmt->fetch();

    // nếu đã làm bài
    if ($baiThi) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'thí sinh đã làm bài',
            'data' => [
                'thiSinh' => [
                    'soBaoDanhId' => $thiSinh['soBaoDanhId'],
                    'sinhVienId' => $thiSinh['sinhVienId'],
                    'hoTen' => $thiSinh['hoTen'],
                    'maSinhVien' => $maSinhVien,
                    'soBaoDanh' => $soBaoDanh
                ],
                'kyThi' => [
                    'id' => $thiSinh['kyThiId'],
                    'tenKyThi' => $thiSinh['tenKyThi'],
                    'monHoc' => $thiSinh['tenMonHoc'],
                    'thoiGianBatDau' => $thiSinh['thoiGianBatDau'],
                    'thoiGianKetThuc' => $thiSinh['thoiGianKetThuc']
                ],
                'baiThi' => [
                    'id' => $baiThi['id'],
                    'deThiId' => $baiThi['deThiId'],
                    'tenDeThi' => $baiThi['tenDeThi'],
                    'thoiGianNop' => $baiThi['thoiGianNop'],
                    'diem' => $baiThi['diem'],
                    'daLamBai' => true
                ]
            ]
        ]);
        exit;
    }

    // nếu chưa làm bài, chọn đề thi ngẫu nhiên
    $stmt = $pdo->prepare('
        SELECT d.id, d.tenDeThi, d.soCau, d.thoiGian
        FROM deThi d
        WHERE d.kyThiId = ?
    ');
    $stmt->execute([$thiSinh['kyThiId']]);
    $dsDeThi = $stmt->fetchAll();

    if (empty($dsDeThi)) {
        http_response_code(404); // Not Found
        echo json_encode([
            'success' => false,
            'message' => 'không có đề thi nào cho kỳ thi này',
            'data' => null
        ]);
        exit;
    }

    // chọn đề thi ngẫu nhiên
    $deThi = $dsDeThi[array_rand($dsDeThi)];

    // trả về thông tin thí sinh và đề thi
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Xác thực thành công',
        'data' => [
            'thiSinh' => [
                'soBaoDanhId' => $thiSinh['soBaoDanhId'],
                'sinhVienId' => $thiSinh['sinhVienId'],
                'hoTen' => $thiSinh['hoTen'],
                'maSinhVien' => $maSinhVien,
                'soBaoDanh' => $soBaoDanh
            ],
            'kyThi' => [
                'id' => $thiSinh['kyThiId'],
                'tenKyThi' => $thiSinh['tenKyThi'],
                'monHoc' => $thiSinh['tenMonHoc'],
                'thoiGianBatDau' => $thiSinh['thoiGianBatDau'],
                'thoiGianKetThuc' => $thiSinh['thoiGianKetThuc']
            ],
            'deThi' => [
                'id' => $deThi['id'],
                'tenDeThi' => $deThi['tenDeThi'],
                'soCau' => $deThi['soCau'],
                'thoiGianLamBai' => $deThi['thoiGian'], // phút
                'daLamBai' => false
            ]
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'lỗi hệ thống: ' . $e->getMessage(),
        'data' => null
    ]);
    exit;
}