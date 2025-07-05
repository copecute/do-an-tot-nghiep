<?php
require_once '../../include/config.php';
require_once '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="danh_sach_tai_khoan.xlsx"');
header('Cache-Control: max-age=0');

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thông tin đầu file
$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1', 'DANH SÁCH TÀI KHOẢN');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->mergeCells('A2:F2');
$sheet->setCellValue('A2', 'Ngày xuất: ' . date('d/m/Y H:i'));
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['italic' => true, 'size' => 11],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->mergeCells('A3:E6');
$sheet->setCellValue('A3', "Chỉ xuất các cột: Họ tên, Tên đăng nhập, Email, Vai trò.\nSTT chỉ để tham khảo, không phải id trong hệ thống.\nKhông xuất cột mật khẩu.\nCột Vai trò chỉ có giá trị 'admin' hoặc 'giaovien'.");
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

// Lấy dữ liệu và xuất
try {
    $stmt = $pdo->query('SELECT hoTen, tenDangNhap, email, vaiTro FROM taiKhoan ORDER BY id DESC');
    $rows = $stmt->fetchAll();
    $rowNum = 8;
    $stt = 1;
    foreach ($rows as $row) {
        $sheet->setCellValue('A'.$rowNum, $stt++);
        $sheet->setCellValue('B'.$rowNum, $row['hoTen']);
        $sheet->setCellValue('C'.$rowNum, $row['tenDangNhap']);
        $sheet->setCellValue('D'.$rowNum, $row['email']);
        $sheet->setCellValue('E'.$rowNum, $row['vaiTro']);
        $rowNum++;
    }
} catch (Exception $e) {
    $sheet->setCellValue('A8', 'Lỗi: ' . $e->getMessage());
}

// Trang trí header
$headerStyle = [
    'font' => [ 'bold' => true, 'size' => 12 ],
    'alignment' => [ 'horizontal' => Alignment::HORIZONTAL_CENTER ],
    'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => [ 'rgb' => 'D9E1F2' ] ],
    'borders' => [ 'allBorders' => [ 'borderStyle' => Border::BORDER_THIN, 'color' => [ 'rgb' => '000000' ] ] ],
];
$sheet->getStyle('A7:E7')->applyFromArray($headerStyle);

// Trang trí border cho toàn bảng
$lastRow = isset($rowNum) ? $rowNum-1 : 8;
$borderStyle = [
    'borders' => [ 'allBorders' => [ 'borderStyle' => Border::BORDER_THIN, 'color' => [ 'rgb' => '888888' ] ] ]
];
$sheet->getStyle('A7:E'.$lastRow)->applyFromArray($borderStyle);

// Căn trái dữ liệu
$sheet->getStyle('A8:E'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A8:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set width
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(22);
$sheet->getColumnDimension('C')->setWidth(18);
$sheet->getColumnDimension('D')->setWidth(25);
$sheet->getColumnDimension('E')->setWidth(10);

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
