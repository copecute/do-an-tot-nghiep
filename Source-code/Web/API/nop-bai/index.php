<?php
require_once '../../include/config.php';

// thiết lập header cho API
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

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
if (!isset($data['soBaoDanhId']) || !isset($data['deThiId']) || empty($data['soBaoDanhId']) || empty($data['deThiId']) || !isset($data['dapAn']) || !is_array($data['dapAn'])) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'thiếu thông tin số báo danh, đề thi hoặc đáp án',
        'data' => null
    ]);
    exit;
}

$soBaoDanhId = $data['soBaoDanhId'];
$deThiId = $data['deThiId'];
$dapAnThiSinh = $data['dapAn']; // mảng đáp án của thí sinh: [cauHoiId => dapAnId]

try {
    // kiểm tra số báo danh và đề thi hợp lệ
    $stmt = $pdo->prepare('
        SELECT s.id as soBaoDanhId, s.kyThiId, sv.id as sinhVienId, sv.hoTen, sv.maSinhVien, s.soBaoDanh,
            k.tenKyThi, k.thoiGianBatDau, k.thoiGianKetThuc, m.tenMonHoc,
            d.id as deThiId, d.tenDeThi, d.soCau, d.thoiGian
        FROM soBaoDanh s
        JOIN sinhVien sv ON s.sinhVienId = sv.id
        JOIN kyThi k ON s.kyThiId = k.id
        JOIN monHoc m ON k.monHocId = m.id
        JOIN deThi d ON d.kyThiId = k.id
        WHERE s.id = ? AND d.id = ?
    ');
    $stmt->execute([$soBaoDanhId, $deThiId]);
    $thiSinh = $stmt->fetch();

    if (!$thiSinh) {
        http_response_code(404); // Not Found
        echo json_encode([
            'success' => false,
            'message' => 'không tìm thấy thông tin thí sinh hoặc đề thi',
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
        SELECT b.id, b.thoiGianNop, b.diem
        FROM baiThi b
        WHERE b.soBaoDanhId = ? AND b.deThiId = ?
    ');
    $stmt->execute([$soBaoDanhId, $deThiId]);
    $baiThi = $stmt->fetch();

    if ($baiThi) {
        http_response_code(403); // Forbidden
        echo json_encode([
            'success' => false,
            'message' => 'thí sinh đã làm bài này',
            'data' => [
                'baiThiId' => $baiThi['id'],
                'thoiGianNop' => $baiThi['thoiGianNop'],
                'diem' => $baiThi['diem']
            ]
        ]);
        exit;
    }

    // lấy danh sách câu hỏi và đáp án đúng của đề thi
    $stmt = $pdo->prepare('
        SELECT c.id as cauHoiId, d.id as dapAnId, d.laDapAn
        FROM deThiCauHoi dc
        JOIN cauHoi c ON dc.cauHoiId = c.id
        JOIN dapAn d ON d.cauHoiId = c.id
        WHERE dc.deThiId = ? AND d.laDapAn = 1
    ');
    $stmt->execute([$deThiId]);
    $dapAnDung = $stmt->fetchAll();

    // nếu không có câu hỏi hoặc đáp án
    if (empty($dapAnDung)) {
        // thử lấy từ danh sách câu hỏi ngẫu nhiên (đề thi tự động)
        $stmt = $pdo->prepare('
            SELECT c.id as cauHoiId, d.id as dapAnId, d.laDapAn
            FROM cauHoi c
            JOIN dapAn d ON d.cauHoiId = c.id
            WHERE c.monHocId = (SELECT monHocId FROM kyThi WHERE id = ?) AND d.laDapAn = 1
        ');
        $stmt->execute([$thiSinh['kyThiId']]);
        $dapAnDung = $stmt->fetchAll();
    }

    // đếm số câu đúng
    $soCauDung = 0;
    $tongSoCau = count($dapAnDung);
    $ketQuaChiTiet = [];

    foreach ($dapAnDung as $dapAn) {
        $cauHoiId = $dapAn['cauHoiId'];
        $dapAnDungId = $dapAn['dapAnId'];
        
        // kiểm tra đáp án của thí sinh
        if (isset($dapAnThiSinh[$cauHoiId]) && $dapAnThiSinh[$cauHoiId] == $dapAnDungId) {
            $soCauDung++;
            $ketQuaChiTiet[$cauHoiId] = true;
        } else {
            $ketQuaChiTiet[$cauHoiId] = false;
        }
    }

    // tính điểm
    $diem = $tongSoCau > 0 ? ($soCauDung / $tongSoCau) * 10 : 0;

    // lưu kết quả bài thi
    $stmt = $pdo->prepare('
        INSERT INTO baiThi (soBaoDanhId, deThiId, thoiGianNop, soCauDung, tongSoCau, diem)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$soBaoDanhId, $deThiId, $now->format('Y-m-d H:i:s'), $soCauDung, $tongSoCau, $diem]);
    $baiThiId = $pdo->lastInsertId();

    // trả về kết quả
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'nộp bài thành công',
        'data' => [
            'thiSinh' => [
                'soBaoDanhId' => $thiSinh['soBaoDanhId'],
                'sinhVienId' => $thiSinh['sinhVienId'],
                'hoTen' => $thiSinh['hoTen'],
                'maSinhVien' => $thiSinh['maSinhVien'],
                'soBaoDanh' => $thiSinh['soBaoDanh']
            ],
            'kyThi' => [
                'id' => $thiSinh['kyThiId'],
                'tenKyThi' => $thiSinh['tenKyThi'],
                'monHoc' => $thiSinh['tenMonHoc']
            ],
            'deThi' => [
                'id' => $thiSinh['deThiId'],
                'tenDeThi' => $thiSinh['tenDeThi']
            ],
            'ketQua' => [
                'baiThiId' => $baiThiId,
                'thoiGianNop' => $now->format('Y-m-d H:i:s'),
                'soCauDung' => $soCauDung,
                'tongSoCau' => $tongSoCau,
                'diem' => $diem,
                'chiTiet' => $ketQuaChiTiet
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