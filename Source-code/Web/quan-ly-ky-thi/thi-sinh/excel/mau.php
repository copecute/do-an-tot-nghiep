<?php
require_once '../../../include/config.php';
require_once '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để thực hiện thao tác này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id kỳ thi
if (!isset($_GET['kyThiId']) || empty($_GET['kyThiId'])) {
    $_SESSION['flash_message'] = 'id kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$kyThiId = $_GET['kyThiId'];

$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';

try {
    // lấy thông tin kỳ thi
    if ($isAdmin) {
        $stmt = $pdo->prepare('
            SELECT k.*, m.tenMonHoc 
            FROM kyThi k 
            JOIN monHoc m ON k.monHocId = m.id 
            WHERE k.id = ?
        ');
        $stmt->execute([$kyThiId]);
    } else {
        $stmt = $pdo->prepare('
            SELECT k.*, m.tenMonHoc 
            FROM kyThi k 
            JOIN monHoc m ON k.monHocId = m.id 
            WHERE k.id = ? AND k.nguoiTaoId = ?
        ');
        $stmt->execute([$kyThiId, $_SESSION['user_id']]);
    }
    $kyThi = $stmt->fetch();

    if (!$kyThi) {
        $_SESSION['flash_message'] = 'không tìm thấy kỳ thi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    // tạo file mẫu Excel sử dụng PhpSpreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // thiết lập tiêu đề file
    $sheet->setTitle('Mẫu nhập thí sinh');
    
    // thiết lập tiêu đề trang
    $sheet->setCellValue('A1', 'MẪU NHẬP DANH SÁCH THÍ SINH');
    $sheet->mergeCells('A1:C1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // thông tin kỳ thi
    $sheet->setCellValue('A2', 'Kỳ thi: ' . $kyThi['tenKyThi']);
    $sheet->mergeCells('A2:C2');
    $sheet->setCellValue('A3', 'Môn học: ' . $kyThi['tenMonHoc']);
    $sheet->mergeCells('A3:C3');
    
    // hướng dẫn
    $sheet->setCellValue('A4', 'Hướng dẫn:');
    $sheet->mergeCells('A4:C4');
    $sheet->getStyle('A4')->getFont()->setBold(true);
    
    $sheet->setCellValue('A5', '1. Chỉ cần điền mã sinh viên và họ tên');
    $sheet->mergeCells('A5:C5');
    $sheet->setCellValue('A6', '2. Ghi chú có thể để trống');
    $sheet->mergeCells('A6:C6');
    
    // thiết lập tiêu đề cột
    $sheet->setCellValue('A7', 'Mã sinh viên');
    $sheet->setCellValue('B7', 'Họ tên');
    $sheet->setCellValue('C7', 'Ghi chú');
    
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
    $sheet->getStyle('A7:C7')->applyFromArray($headerStyle);
    
    // điền dữ liệu mẫu
    $data = [
        ['SV001', 'Nguyễn Văn A', 'Chỉ cần điền mã sinh viên và họ tên'],
        ['SV002', 'Trần Thị B', 'Ghi chú có thể để trống'],
        ['', '', 'Thêm các dòng theo nhu cầu'],
    ];
    
    $row = 8;
    foreach ($data as $rowData) {
        for ($col = 0; $col < 3; $col++) {
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
    $sheet->getStyle('A7:C' . ($row - 1))->applyFromArray($dataStyle);
    
    // tô màu cho các ô cần nhập dữ liệu
    $fillStyle = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFF99'],
        ],
    ];
    $sheet->getStyle('A8:C' . ($row - 1))->applyFromArray($fillStyle);
    
    // tự động điều chỉnh chiều rộng cột
    foreach (range('A', 'C') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // thiết lập header để tải file
    $filename = "mau_nhap_thi_sinh.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // tạo đối tượng writer và xuất file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    $_SESSION['flash_message'] = 'lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header("Location: /quan-ly-ky-thi/thi-sinh/?kyThiId=$kyThiId");
    exit;
}