<?php
require_once '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="mau_tai_khoan.xlsx"');
header('Cache-Control: max-age=0');

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thông tin đầu file
$sheet->mergeCells('A1:E1');
$sheet->setCellValue('A1', 'MẪU NHẬP TÀI KHOẢN');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->mergeCells('A2:E2');
$sheet->setCellValue('A2', 'Ngày tạo: ' . date('d/m/Y H:i'));
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['italic' => true, 'size' => 11],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->mergeCells('A3:E6');
$sheet->setCellValue('A3', "Chỉ nhập các cột: Họ tên, Tên đăng nhập, Email, Vai trò.\nCột STT chỉ để tham khảo, không cần nhập vào hệ thống.\nMật khẩu mặc định là 123456.\nCột Vai trò: chỉ chọn 1 trong 2 giá trị 'admin' hoặc 'giaovien', không tự ghi khác.");
$sheet->getStyle('A3')->applyFromArray([
    'font' => ['size' => 10],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
]);
$sheet->getRowDimension(3)->setRowHeight(55);

// Tiêu đề cột
$sheet->setCellValue('A7', 'STT');
$sheet->setCellValue('B7', 'Họ tên');
$sheet->setCellValue('C7', 'Tên đăng nhập');
$sheet->setCellValue('D7', 'Email');
$sheet->setCellValue('E7', 'Vai trò');

// Dữ liệu mẫu
$sheet->setCellValue('A8', '1');
$sheet->setCellValue('B8', 'Nguyễn Văn A');
$sheet->setCellValue('C8', 'admin1');
$sheet->setCellValue('D8', 'admin1@email.com');
$sheet->setCellValue('E8', 'admin');

$sheet->setCellValue('A9', '2');
$sheet->setCellValue('B9', 'Trần Thị B');
$sheet->setCellValue('C9', 'gv01');
$sheet->setCellValue('D9', 'gv01@email.com');
$sheet->setCellValue('E9', 'giaovien');

// Trang trí header
$headerStyle = [
    'font' => [ 'bold' => true, 'size' => 12 ],
    'alignment' => [ 'horizontal' => Alignment::HORIZONTAL_CENTER ],
    'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => [ 'rgb' => 'D9E1F2' ] ],
    'borders' => [ 'allBorders' => [ 'borderStyle' => Border::BORDER_THIN, 'color' => [ 'rgb' => '000000' ] ] ],
];
$sheet->getStyle('A7:E7')->applyFromArray($headerStyle);

// Trang trí border cho toàn bảng
$borderStyle = [
    'borders' => [ 'allBorders' => [ 'borderStyle' => Border::BORDER_THIN, 'color' => [ 'rgb' => '888888' ] ] ]
];
$sheet->getStyle('A7:E9')->applyFromArray($borderStyle);

// Căn trái dữ liệu
$sheet->getStyle('A8:E9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A8:A9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set width
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(22);
$sheet->getColumnDimension('C')->setWidth(18);
$sheet->getColumnDimension('D')->setWidth(25);
$sheet->getColumnDimension('E')->setWidth(12);

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
