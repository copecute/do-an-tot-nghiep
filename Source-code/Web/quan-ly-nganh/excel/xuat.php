<?php
require_once '../../include/config.php';
require_once '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="danh_sach_nganh.xlsx"');
header('Cache-Control: max-age=0');

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thông tin đầu file
$sheet->mergeCells('A1:C1');
$sheet->setCellValue('A1', 'DANH SÁCH NGÀNH');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->mergeCells('A2:C2');
$sheet->setCellValue('A2', 'Ngày xuất: ' . date('d/m/Y H:i'));
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['italic' => true, 'size' => 11],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->mergeCells('A3:C3');
$sheet->setCellValue('A3', 'STT chỉ để tham khảo, không phải id trong hệ thống.');
$sheet->getStyle('A3')->applyFromArray([
    'font' => ['size' => 10],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
]);

// Tiêu đề cột
$sheet->setCellValue('A5', 'STT');
$sheet->setCellValue('B5', 'Tên ngành');
$sheet->setCellValue('C5', 'Khoa');

// Lấy dữ liệu và xuất
try {
    $stmt = $pdo->query('
        SELECT n.tenNganh, k.tenKhoa
        FROM nganh n
        JOIN khoa k ON n.khoaId = k.id
        ORDER BY n.id DESC
    ');
    $rows = $stmt->fetchAll();
    $rowNum = 6;
    $stt = 1;
    foreach ($rows as $row) {
        $sheet->setCellValue('A'.$rowNum, $stt++);
        $sheet->setCellValue('B'.$rowNum, $row['tenNganh']);
        $sheet->setCellValue('C'.$rowNum, $row['tenKhoa']);
        $rowNum++;
    }
} catch (Exception $e) {
    $sheet->setCellValue('A6', 'Lỗi: ' . $e->getMessage());
}

// Trang trí header
$headerStyle = [
    'font' => [ 'bold' => true, 'size' => 12 ],
    'alignment' => [ 'horizontal' => Alignment::HORIZONTAL_CENTER ],
    'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => [ 'rgb' => 'D9E1F2' ] ],
    'borders' => [ 'allBorders' => [ 'borderStyle' => Border::BORDER_THIN, 'color' => [ 'rgb' => '000000' ] ] ],
];
$sheet->getStyle('A5:C5')->applyFromArray($headerStyle);

// Trang trí border cho toàn bảng
$lastRow = isset($rowNum) ? $rowNum-1 : 6;
$borderStyle = [
    'borders' => [ 'allBorders' => [ 'borderStyle' => Border::BORDER_THIN, 'color' => [ 'rgb' => '888888' ] ] ]
];
$sheet->getStyle('A5:C'.$lastRow)->applyFromArray($borderStyle);

// Căn trái dữ liệu
$sheet->getStyle('A6:C'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A6:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set width
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(25);

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
