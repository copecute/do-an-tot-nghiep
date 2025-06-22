<?php
require_once '../../include/config.php';
// nạp thư viện phpspreadsheet
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    header('Location: /dang-nhap.php');
    exit;
}

// lấy dữ liệu câu hỏi và đáp án
$stmt = $pdo->query('SELECT c.id, c.noiDung, c.doKho, c.monHocId, c.theLoaiId, m.tenMonHoc, t.tenTheLoai FROM cauHoi c JOIN monHoc m ON c.monHocId = m.id LEFT JOIN theLoaiCauHoi t ON c.theLoaiId = t.id ORDER BY c.id DESC');
$cauHoiList = $stmt->fetchAll();

// lấy đáp án cho từng câu hỏi
$dsDapAn = [];
$stmtDapAn = $pdo->prepare('SELECT * FROM dapAn WHERE cauHoiId = ? ORDER BY id');
foreach ($cauHoiList as $cauHoi) {
    $stmtDapAn->execute([$cauHoi['id']]);
    $dsDapAn[$cauHoi['id']] = $stmtDapAn->fetchAll();
}

// tạo file excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// tiêu đề lớn
$sheet->setCellValue('A1', 'DANH SÁCH NGÂN HÀNG CÂU HỎI');
$sheet->mergeCells('A1:J1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// hướng dẫn
$sheet->setCellValue('A2', 'Hướng dẫn:');
$sheet->mergeCells('A2:J2');
$sheet->getStyle('A2')->getFont()->setBold(true);
$sheet->setCellValue('A3', '1. Độ khó: de, trungbinh, kho');
$sheet->mergeCells('A3:J3');
$sheet->setCellValue('A4', '2. Đáp án đúng là số thứ tự (1-4)');
$sheet->mergeCells('A4:J4');
$sheet->setCellValue('A5', '3. Có thể để trống thể loại, đáp án 3, 4 nếu không dùng');
$sheet->mergeCells('A5:J5');

// header cột
$sheet->setCellValue('A7', 'Môn học');
$sheet->setCellValue('B7', 'Thể loại');
$sheet->setCellValue('C7', 'Nội dung');
$sheet->setCellValue('D7', 'Độ khó');
$sheet->setCellValue('E7', 'Đáp án 1');
$sheet->setCellValue('F7', 'Đáp án 2');
$sheet->setCellValue('G7', 'Đáp án 3');
$sheet->setCellValue('H7', 'Đáp án 4');
$sheet->setCellValue('I7', 'Đáp án đúng (1-4)');

// style header
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
$sheet->getStyle('A7:I7')->applyFromArray($headerStyle);

$row = 8;
foreach ($cauHoiList as $cauHoi) {
    $sheet->setCellValue('A'.$row, $cauHoi['tenMonHoc']);
    $sheet->setCellValue('B'.$row, $cauHoi['tenTheLoai']);
    $sheet->setCellValue('C'.$row, $cauHoi['noiDung']);
    $sheet->setCellValue('D'.$row, $cauHoi['doKho']);
    $dapan = $dsDapAn[$cauHoi['id']];
    $dapAnDung = '';
    for ($i = 0; $i < 4; $i++) {
        $sheet->setCellValue(chr(69+$i).$row, isset($dapan[$i]) ? $dapan[$i]['noiDung'] : '');
        if (isset($dapan[$i]) && $dapan[$i]['laDapAn']) {
            $dapAnDung = $i+1;
        }
    }
    $sheet->setCellValue('I'.$row, $dapAnDung);
    $row++;
}

// border cho toàn bộ bảng
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];
$sheet->getStyle('A7:I'.($row-1))->applyFromArray($dataStyle);

// auto width
foreach (range('A', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// xuất file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="ngan-hang-cau-hoi.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit; 