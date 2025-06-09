<?php
require_once '../../../include/config.php';
require '../../../vendor/autoload.php';

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

try {
    // lấy thông tin kỳ thi
    $stmt = $pdo->prepare('
        SELECT k.*, m.tenMonHoc 
        FROM kyThi k 
        JOIN monHoc m ON k.monHocId = m.id 
        WHERE k.id = ? AND k.nguoiTaoId = ?
    ');
    $stmt->execute([$kyThiId, $_SESSION['user_id']]);
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
    $sheet->setTitle('Mẫu danh sách thí sinh');
    
    // thiết lập tiêu đề trang
    $sheet->setCellValue('A1', 'MẪU DANH SÁCH THÍ SINH');
    $sheet->mergeCells('A1:D1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // thông tin kỳ thi
    $sheet->setCellValue('A2', 'Kỳ thi: ' . $kyThi['tenKyThi']);
    $sheet->mergeCells('A2:D2');
    $sheet->setCellValue('A3', 'Môn học: ' . $kyThi['tenMonHoc']);
    $sheet->mergeCells('A3:D3');
    
    // hướng dẫn
    $sheet->setCellValue('A4', 'Hướng dẫn:');
    $sheet->mergeCells('A4:D4');
    $sheet->getStyle('A4')->getFont()->setBold(true);
    
    $sheet->setCellValue('A5', '1. Chỉ cần điền Mã sinh viên và Họ tên');
    $sheet->mergeCells('A5:D5');
    $sheet->setCellValue('A6', '2. Số báo danh sẽ được tạo tự động');
    $sheet->mergeCells('A6:D6');
    $sheet->setCellValue('A7', '3. Không thay đổi cấu trúc file mẫu');
    $sheet->mergeCells('A7:D7');
    
    // thiết lập tiêu đề cột
    $sheet->setCellValue('A9', 'STT');
    $sheet->setCellValue('B9', 'Mã sinh viên');
    $sheet->setCellValue('C9', 'Họ tên');
    $sheet->setCellValue('D9', 'Ghi chú');
    
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
    $sheet->getStyle('A9:D9')->applyFromArray($headerStyle);
    
    // điền dữ liệu mẫu
    $data = [
        [1, 'SV001', 'Nguyễn Văn A', 'Chỉ cần điền mã sinh viên và họ tên'],
        [2, 'SV002', 'Trần Thị B', 'Số báo danh sẽ được tạo tự động'],
        [3, '', '', 'Thêm các dòng theo nhu cầu'],
    ];
    
    $row = 10;
    foreach ($data as $rowData) {
        $sheet->setCellValue('A' . $row, $rowData[0]);
        $sheet->setCellValue('B' . $row, $rowData[1]);
        $sheet->setCellValue('C' . $row, $rowData[2]);
        $sheet->setCellValue('D' . $row, $rowData[3]);
        
        // căn giữa cột STT
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
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
    $sheet->getStyle('A9:D' . ($row - 1))->applyFromArray($dataStyle);
    
    // tô màu cho các ô cần nhập dữ liệu
    $fillStyle = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFF99'],
        ],
    ];
    $sheet->getStyle('B10:C12')->applyFromArray($fillStyle);
    
    // tự động điều chỉnh chiều rộng cột
    foreach (range('A', 'D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // thiết lập header để tải file
    $filename = "mau_danh_sach_thi_sinh.xlsx";
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