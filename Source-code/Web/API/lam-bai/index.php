<?php
require_once '../../include/config.php';

// thiết lập header cho API
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// chỉ chấp nhận phương thức GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'chỉ hỗ trợ phương thức GET',
        'data' => null
    ]);
    exit;
}

// kiểm tra tham số
if (!isset($_GET['soBaoDanhId']) || !isset($_GET['deThiId']) || empty($_GET['soBaoDanhId']) || empty($_GET['deThiId'])) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'thiếu thông tin số báo danh hoặc đề thi',
        'data' => null
    ]);
    exit;
}

$soBaoDanhId = $_GET['soBaoDanhId'];
$deThiId = $_GET['deThiId'];

try {
    // kiểm tra số báo danh và đề thi hợp lệ
    $stmt = $pdo->prepare('
        SELECT s.id as soBaoDanhId, s.kyThiId, sv.id as sinhVienId, sv.hoTen, sv.maSinhVien, s.soBaoDanh,
            k.tenKyThi, k.thoiGianBatDau, k.thoiGianKetThuc, m.tenMonHoc,
            d.id as deThiId, d.tenDeThi, d.soCau, d.thoiGian, d.isTuDong
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

    // lấy danh sách câu hỏi của đề thi
    if ($thiSinh['isTuDong']) {
        // nếu đề thi tự động, lấy câu hỏi theo cấu hình
        // đây là phần phức tạp, cần xử lý theo logic tạo đề tự động
        // tạm thời chỉ lấy câu hỏi ngẫu nhiên theo số lượng
        $stmt = $pdo->prepare('
            SELECT c.id, c.noiDung, c.doKho, c.theLoaiId, t.tenTheLoai
            FROM cauHoi c
            LEFT JOIN theLoaiCauHoi t ON c.theLoaiId = t.id
            WHERE c.monHocId = (SELECT monHocId FROM kyThi WHERE id = ?)
            ORDER BY RAND()
            LIMIT ?
        ');
        $stmt->execute([$thiSinh['kyThiId'], $thiSinh['soCau']]);
    } else {
        // nếu đề thi thủ công, lấy câu hỏi đã được chọn
        $stmt = $pdo->prepare('
            SELECT c.id, c.noiDung, c.doKho, c.theLoaiId, t.tenTheLoai
            FROM deThiCauHoi dc
            JOIN cauHoi c ON dc.cauHoiId = c.id
            LEFT JOIN theLoaiCauHoi t ON c.theLoaiId = t.id
            WHERE dc.deThiId = ?
        ');
        $stmt->execute([$deThiId]);
    }
    
    $dsCauHoi = $stmt->fetchAll();

    if (empty($dsCauHoi)) {
        http_response_code(404); // Not Found
        echo json_encode([
            'success' => false,
            'message' => 'không tìm thấy câu hỏi cho đề thi này',
            'data' => null
        ]);
        exit;
    }

    // lấy đáp án cho từng câu hỏi
    $cauHoiVaDapAn = [];
    foreach ($dsCauHoi as $cauHoi) {
        $stmt = $pdo->prepare('
            SELECT id, noiDung
            FROM dapAn
            WHERE cauHoiId = ?
            ORDER BY id
        ');
        $stmt->execute([$cauHoi['id']]);
        $dsDapAn = $stmt->fetchAll();

        // thêm câu hỏi và đáp án vào mảng kết quả
        $cauHoiVaDapAn[] = [
            'cauHoi' => [
                'id' => $cauHoi['id'],
                'noiDung' => $cauHoi['noiDung'],
                'doKho' => $cauHoi['doKho'],
                'theLoai' => $cauHoi['tenTheLoai']
            ],
            'dapAn' => $dsDapAn
        ];
    }

    // trả về thông tin đề thi và câu hỏi
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'lấy đề thi thành công',
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
                'monHoc' => $thiSinh['tenMonHoc'],
                'thoiGianBatDau' => $thiSinh['thoiGianBatDau'],
                'thoiGianKetThuc' => $thiSinh['thoiGianKetThuc']
            ],
            'deThi' => [
                'id' => $thiSinh['deThiId'],
                'tenDeThi' => $thiSinh['tenDeThi'],
                'soCau' => $thiSinh['soCau'],
                'thoiGianLamBai' => $thiSinh['thoiGian'] // phút
            ],
            'cauHoi' => $cauHoiVaDapAn
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