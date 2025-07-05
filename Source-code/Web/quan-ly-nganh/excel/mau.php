<?php
require_once '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="mau_nganh.xlsx"');
header('Cache-Control: max-age=0');

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thông tin đầu file
$sheet->mergeCells('A1:C1');
$sheet->setCellValue('A1', 'MẪU NHẬP NGÀNH');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->mergeCells('A2:C2');
$sheet->setCellValue('A2', 'Ngày tạo: ' . date('d/m/Y H:i'));
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['italic' => true, 'size' => 11],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->mergeCells('A3:C3');
$sheet->setCellValue('A3', 'Chỉ nhập các cột: Tên ngành, Khoa. Cột STT chỉ để tham khảo, không cần nhập vào hệ thống.');
$sheet->getStyle('A3')->applyFromArray([
    'font' => ['size' => 10],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
]);

// Tiêu đề cột
$sheet->setCellValue('A5', 'STT');
$sheet->setCellValue('B5', 'Tên ngành');
$sheet->setCellValue('C5', 'Khoa');

// Dữ liệu mẫu
$sheet->setCellValue('A6', '1');
$sheet->setCellValue('B6', 'Công nghệ thông tin');
$sheet->setCellValue('C6', 'Công nghệ thông tin');

$sheet->setCellValue('A7', '2');
$sheet->setCellValue('B7', 'Kế toán');
$sheet->setCellValue('C7', 'Kinh tế');

$sheet->setCellValue('A8', '3');
$sheet->setCellValue('B8', 'Quản trị kinh doanh');
$sheet->setCellValue('C8', 'Kinh tế');

// Trang trí header
$headerStyle = [
    'font' => [ 'bold' => true, 'size' => 12 ],
    'alignment' => [ 'horizontal' => Alignment::HORIZONTAL_CENTER ],
    'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => [ 'rgb' => 'D9E1F2' ] ],
    'borders' => [ 'allBorders' => [ 'borderStyle' => Border::BORDER_THIN, 'color' => [ 'rgb' => '000000' ] ] ],
];
$sheet->getStyle('A5:C5')->applyFromArray($headerStyle);

// Trang trí border cho toàn bảng
$borderStyle = [
    'borders' => [ 'allBorders' => [ 'borderStyle' => Border::BORDER_THIN, 'color' => [ 'rgb' => '888888' ] ] ]
];
$sheet->getStyle('A5:C8')->applyFromArray($borderStyle);

// Căn trái dữ liệu
$sheet->getStyle('A6:C8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A6:A8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set width
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(25);

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
