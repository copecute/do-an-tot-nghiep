<?php
require_once '../../include/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die('Bạn cần đăng nhập để thực hiện thao tác này!');
}
$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Thiếu ID đề thi!');
}
$deThiId = $_GET['id'];

try {
    // Lấy thông tin đề thi kèm người tạo
    $sql = 'SELECT d.*, k.tenKyThi, m.tenMonHoc, t.hoTen as nguoiTao, d.nguoiTaoId FROM deThi d 
        JOIN kyThi k ON d.kyThiId = k.id
        JOIN monHoc m ON k.monHocId = m.id
        JOIN taiKhoan t ON d.nguoiTaoId = t.id
        WHERE d.id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deThiId]);
    $deThi = $stmt->fetch();
    if (!$deThi) die('Không tìm thấy đề thi!');
    // Kiểm tra quyền: admin thì pass, giáo viên chỉ export đề mình tạo
    if (!$isAdmin && $deThi['nguoiTaoId'] != $_SESSION['user_id']) {
        die('Bạn không có quyền export đề thi này!');
    }

    $stmt = $pdo->prepare('
        SELECT c.*, t.tenTheLoai
        FROM deThiCauHoi dc
        JOIN cauHoi c ON dc.cauHoiId = c.id
        LEFT JOIN theLoaiCauHoi t ON c.theLoaiId = t.id
        WHERE dc.deThiId = ?
        ORDER BY dc.id
    ');
    $stmt->execute([$deThiId]);
    $dsCauHoi = $stmt->fetchAll();
    // Lấy đáp án cho mỗi câu hỏi
    foreach ($dsCauHoi as &$cauHoi) {
        $stmt = $pdo->prepare('SELECT * FROM dapAn WHERE cauHoiId = ? ORDER BY id');
        $stmt->execute([$cauHoi['id']]);
        $cauHoi['dapan'] = $stmt->fetchAll();
    }
    unset($cauHoi);
} catch (PDOException $e) {
    die('Lỗi: ' . $e->getMessage());
}

header('Content-Type: application/msword');
header('Content-Disposition: attachment; filename="de-thi-' . $deThiId . '.doc"');
?>
<html>
<head>
    <meta charset="utf-8">
    <title>Đề thi: <?php echo htmlspecialchars($deThi['tenDeThi']); ?></title>
    <style>
        body { font-family: Times New Roman, Arial, sans-serif; font-size: 15px; }
        .khung-tieu-de {
            border: 1.5px solid #000; border-collapse: collapse; width: 100%; margin-bottom: 8px;
        }
        .khung-tieu-de td { border: none; padding: 6px 8px; vertical-align: top; }
        .khung-tieu-de tr:first-child td:first-child { border-right: 1.5px solid #000; }
        .tieu-de-trai { text-align: center; }
        .tieu-de-trai .truong { font-weight: bold; }
        .tieu-de-trai .main-title { font-size: 17px; font-weight: bold; margin: 4px 0; }
        .tieu-de-trai .sub-title { font-size: 14px; font-style: italic; }
        .tieu-de-phai { text-align: center; }
        .tieu-de-phai .de-thi { font-weight: bold; font-size: 16px; }
        .tieu-de-phai .mon { font-weight: bold; }
        .tieu-de-phai .time { font-size: 14px; font-style: italic; }
        .tieu-de-phai .line { border-bottom: 1px solid #000; margin: 8px 0 0 0; }
        .student-info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .student-info-table td { padding: 4px 6px; font-size: 15px; border: none; }
        .student-info-table .label { font-weight: bold; }
        .ma-de-box { border: 1.5px solid #000; padding: 2px 16px; font-weight: bold; display: inline-block; }
        .section-title { font-weight: bold; margin: 16px 0 8px 0; font-size: 15.5px; }
        .cauhoi { margin-bottom: 14px; }
        .cauhoi .noidung { font-weight: bold; }
        .meta { font-size: 15px; color: #555; }
        .footer { margin-top: 30px; font-size: 15px; }
        .het { text-align: center; font-weight: bold; margin: 30px 0 10px 0; }
        .note { font-size: 14px; margin-left: 10px; }
        .dapan { margin-left: 24px; }
        .dapan strong { font-weight: bold; }
    </style>
</head>
<body>
    <table class="khung-tieu-de">
        <tr>
            <td style="width: 50%; border-right: 1.5px solid #000;" class="tieu-de-trai">
                <div>BỘ GIÁO DỤC VÀ ĐÀO TẠO</div>
                <div class="truong">TRƯỜNG CĐCN BÁCH KHOA HÀ NỘI</div>
                <div class="main-title">ĐỀ CHÍNH THỨC</div>
                <div class="sub-title">(Đề có ...... trang)</div>
            </td>
            <td style="width: 50%; text-align: center;" class="tieu-de-phai">
                <div class="de-thi">ĐỀ THI <?php echo htmlspecialchars($deThi['tenKyThi']); ?></div>
                <div class="mon">Môn: <strong><?php echo htmlspecialchars($deThi['tenMonHoc']); ?></strong></div>
                <div class="time">Thời gian làm bài: <strong><?php echo $deThi['thoiGian']; ?></strong> phút, không kể thời gian phát đề</div>
                <div class="line">&nbsp;</div>
            </td>
        </tr>
    </table>
    <table class="student-info-table">
        <tr>
            <td class="label" style="width:40%">Họ tên thí sinh: <span style="font-weight:normal;">.......................................................</span></td>
            <td class="label" style="width:35%">Số báo danh: <span style="font-weight:normal;">....................</span></td>
            <td style="width:25%; text-align:right;">Mã đề thi <span class="ma-de-box">___</span></td>
        </tr>
    </table>
    <div class="section-title">PHẦN I. <span style="font-weight:bold;">Câu trắc nghiệm nhiều phương án lựa chọn.</span> Thí sinh trả lời từ câu 1 đến câu <?php echo count($dsCauHoi); ?>. Mỗi câu hỏi thí sinh chỉ chọn một phương án.</div>
    <?php foreach ($dsCauHoi as $i => $cauHoi): ?>
    <div class="cauhoi">
        <div class="noidung">Câu <?php echo $i+1; ?>: <?php echo htmlspecialchars($cauHoi['noiDung']); ?></div>
        <div class="meta">
            <?php if (!empty($cauHoi['dapan'])): ?>
                <?php foreach ($cauHoi['dapan'] as $j => $da): ?>
                    <div class="dapan"><strong><?php echo chr(65+$j); ?>.</strong> <?php echo htmlspecialchars($da['noiDung']); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="het">-------------- HẾT ---------------</div>
    <div class="footer">
        <div class="note">- Thí sinh không được sử dụng tài liệu;<br>- Cán bộ coi thi không giải thích gì thêm.</div>
    </div>
</body>
</html> 