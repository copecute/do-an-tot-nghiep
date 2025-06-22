<?php
require_once '../include/config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (!isset($_GET['kyThiId']) || empty($_GET['kyThiId'])) {
    die('thiếu id kỳ thi');
}
$kyThiId = $_GET['kyThiId'];

$stmt = $pdo->prepare('SELECT k.*, m.tenMonHoc FROM kyThi k JOIN monHoc m ON k.monHocId = m.id WHERE k.id = ?');
$stmt->execute([$kyThiId]);
$kyThi = $stmt->fetch();
if (!$kyThi) die('không tìm thấy kỳ thi');

$stmt = $pdo->prepare('SELECT COUNT(*) FROM soBaoDanh WHERE kyThiId = ?');
$stmt->execute([$kyThiId]);
$soThiSinh = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM deThi WHERE kyThiId = ?');
$stmt->execute([$kyThiId]);
$soDeThi = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM baiThi WHERE deThiId IN (SELECT id FROM deThi WHERE kyThiId = ?)');
$stmt->execute([$kyThiId]);
$soBaiNop = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT AVG(diem) as diemTB, MAX(diem) as diemMax, MIN(diem) as diemMin FROM baiThi WHERE deThiId IN (SELECT id FROM deThi WHERE kyThiId = ?)');
$stmt->execute([$kyThiId]);
$thongKeDiem = $stmt->fetch();

$stmt = $pdo->prepare('
    SELECT sv.maSinhVien, sv.hoTen, bt.diem, bt.thoiGianNop
    FROM baiThi bt
    JOIN soBaoDanh sbd ON bt.soBaoDanhId = sbd.id
    JOIN sinhVien sv ON sbd.sinhVienId = sv.id
    WHERE sbd.kyThiId = ?
    ORDER BY bt.diem DESC, bt.thoiGianNop ASC
    LIMIT 10
');
$stmt->execute([$kyThiId]);
$topThiSinh = $stmt->fetchAll();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'BÁO CÁO THỐNG KÊ KỲ THI');
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Kỳ thi: ' . $kyThi['tenKyThi']);
$sheet->mergeCells('A2:F2');
$sheet->setCellValue('A3', 'Môn học: ' . $kyThi['tenMonHoc']);
$sheet->mergeCells('A3:F3');
$sheet->setCellValue('A4', 'Ngày xuất: ' . date('d/m/Y H:i'));
$sheet->mergeCells('A4:F4');

$sheet->setCellValue('A6', 'Tổng số thí sinh');
$sheet->setCellValue('B6', $soThiSinh);
$sheet->setCellValue('A7', 'Tổng số đề thi');
$sheet->setCellValue('B7', $soDeThi);
$sheet->setCellValue('A8', 'Tổng số bài thi đã nộp');
$sheet->setCellValue('B8', $soBaiNop);
$sheet->setCellValue('A9', 'Điểm trung bình');
$sheet->setCellValue('B9', number_format($thongKeDiem['diemTB'], 2));
$sheet->setCellValue('A10', 'Điểm cao nhất');
$sheet->setCellValue('B10', number_format($thongKeDiem['diemMax'], 2));
$sheet->setCellValue('A11', 'Điểm thấp nhất');
$sheet->setCellValue('B11', number_format($thongKeDiem['diemMin'], 2));

$sheet->getStyle('A6:A11')->getFont()->setBold(true);

$sheet->setCellValue('A13', 'Top 10 thí sinh điểm cao nhất');
$sheet->mergeCells('A13:F13');
$sheet->getStyle('A13')->getFont()->setBold(true);

$sheet->setCellValue('A14', 'STT');
$sheet->setCellValue('B14', 'Mã sinh viên');
$sheet->setCellValue('C14', 'Họ tên');
$sheet->setCellValue('D14', 'Điểm');
$sheet->setCellValue('E14', 'Thời gian nộp');

$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'DDDDDD'],
    ],
];
$sheet->getStyle('A14:E14')->applyFromArray($headerStyle);

$row = 15;
foreach ($topThiSinh as $i => $r) {
    $sheet->setCellValue('A'.$row, $i+1);
    $sheet->setCellValue('B'.$row, $r['maSinhVien']);
    $sheet->setCellValue('C'.$row, $r['hoTen']);
    $sheet->setCellValue('D'.$row, number_format($r['diem'], 2));
    $sheet->setCellValue('E'.$row, $r['thoiGianNop']);
    $row++;
}
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];
$sheet->getStyle('A14:E'.($row-1))->applyFromArray($dataStyle);
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="bao_cao_thong_ke_ky_thi.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit; 