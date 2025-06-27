<?php
require_once '../../include/config.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để thực hiện thao tác này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// tạo file mẫu Excel sử dụng PhpSpreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// thiết lập tiêu đề file
$sheet->setTitle('Mẫu nhập câu hỏi');

// tiêu đề trang
$sheet->setCellValue('A1', 'MẪU NHẬP NGÂN HÀNG CÂU HỎI');
$sheet->mergeCells('A1:I1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// hướng dẫn
$sheet->setCellValue('A2', 'Hướng dẫn:');
$sheet->mergeCells('A2:I2');
$sheet->getStyle('A2')->getFont()->setBold(true);
$sheet->setCellValue('A3', '1. Tên môn học và thể loại phải đúng với hệ thống (không phân biệt hoa thường)');
$sheet->mergeCells('A3:I3');
$sheet->setCellValue('A4', '2. Độ khó: Dễ, Trung bình, Khó (không phân biệt hoa thường)');
$sheet->mergeCells('A4:I4');
$sheet->setCellValue('A5', '3. Đáp án đúng là số thứ tự (1-4)');
$sheet->mergeCells('A5:I5');
$sheet->setCellValue('A6', '4. Bắt buộc phải có ít nhất 2 đáp án, không để trống nội dung câu hỏi');
$sheet->mergeCells('A6:I6');
$sheet->setCellValue('A7', '5. Có thể để trống thể loại, đáp án 3, 4 nếu không dùng');
$sheet->mergeCells('A7:I7');

// tiêu đề cột
$sheet->setCellValue('A9', 'Môn học');
$sheet->setCellValue('B9', 'Thể loại');
$sheet->setCellValue('C9', 'Nội dung');
$sheet->setCellValue('D9', 'Độ khó');
$sheet->setCellValue('E9', 'Đáp án 1');
$sheet->setCellValue('F9', 'Đáp án 2');
$sheet->setCellValue('G9', 'Đáp án 3');
$sheet->setCellValue('H9', 'Đáp án 4');
$sheet->setCellValue('I9', 'Đáp án đúng (1-4)');

// định dạng tiêu đề cột
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
$sheet->getStyle('A9:I9')->applyFromArray($headerStyle);

// dữ liệu mẫu
$data = [
    ['Lập trình căn bản', 'Chương 1', 'HTML là viết tắt của cụm từ nào?', 'Dễ', 'HyperText Markdown Language', 'Hyperlink and Text Markup Language', 'HyperText Markup Language', 'HyperTool Machine Language', '3'],
    ['Lập trình căn bản', '', 'Trong lập trình, vòng lặp nào sau đây không có điều kiện ở đầu vòng?', 'Trung bình', 'for', 'while', 'do...while', 'loop', '3'],
    ['Lập trình căn bản', '', 'Bit và Byte khác nhau thế nào?', 'Khó', 'Bit lớn hơn Byte', 'Byte bằng 2 Bit', 'Byte = 8 Bit', 'Bit = 16 Byte', '3'],
    ['', '', '', '', '', '', '', '', ''],
];
$row = 10;
foreach ($data as $rowData) {
    for ($col = 0; $col < 9; $col++) {
        $sheet->setCellValue(chr(65 + $col) . $row, $rowData[$col]);
    }
    $row++;
}

// định dạng dữ liệu
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];
$sheet->getStyle('A9:I' . ($row - 1))->applyFromArray($dataStyle);

// tô màu cho các ô cần nhập dữ liệu
$fillStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFFF99'],
    ],
];
$sheet->getStyle('A10:I12')->applyFromArray($fillStyle);

// tự động điều chỉnh chiều rộng cột
foreach (range('A', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// thiết lập header để tải file
$filename = "mau_nhap_cau_hoi.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit; 