<?php
require_once '../../include/config.php';
require_once '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="danh_sach_khoa.xlsx"');
header('Cache-Control: max-age=0');

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thông tin đầu file
$sheet->mergeCells('A1:B1');
$sheet->setCellValue('A1', 'DANH SÁCH KHOA');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->mergeCells('A2:B2');
$sheet->setCellValue('A2', 'Ngày xuất: ' . date('d/m/Y H:i'));
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['italic' => true, 'size' => 11],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->mergeCells('A3:B3');
$sheet->setCellValue('A3', 'STT chỉ để tham khảo, không phải id trong hệ thống.');
$sheet->getStyle('A3')->applyFromArray([
    'font' => ['size' => 10],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
]);

// Tiêu đề cột
$sheet->setCellValue('A5', 'STT');
$sheet->setCellValue('B5', 'Tên khoa');

// Lấy dữ liệu và xuất
try {
    $stmt = $pdo->query('SELECT tenKhoa FROM khoa ORDER BY id DESC');
    $rows = $stmt->fetchAll();
    $rowNum = 6;
    $stt = 1;
    foreach ($rows as $row) {
        $sheet->setCellValue('A'.$rowNum, $stt++);
        $sheet->setCellValue('B'.$rowNum, $row['tenKhoa']);
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
$sheet->getStyle('A5:B5')->applyFromArray($headerStyle);

// Trang trí border cho toàn bảng
$lastRow = isset($rowNum) ? $rowNum-1 : 6;
$borderStyle = [
    'borders' => [ 'allBorders' => [ 'borderStyle' => Border::BORDER_THIN, 'color' => [ 'rgb' => '888888' ] ] ]
];
$sheet->getStyle('A5:B'.$lastRow)->applyFromArray($borderStyle);

// Căn trái dữ liệu
$sheet->getStyle('A6:B'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A6:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set width
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(30);

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
