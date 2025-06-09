<?php
require_once '../../../include/config.php';
require '../../../vendor/autoload.php';

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

    // lấy danh sách thí sinh
    $stmt = $pdo->prepare('
        SELECT s.id, s.soBaoDanh, sv.maSinhVien, sv.hoTen, n.tenNganh,
            (SELECT COUNT(*) FROM baiThi b WHERE b.soBaoDanhId = s.id) as soBaiThi
        FROM soBaoDanh s 
        JOIN sinhVien sv ON s.sinhVienId = sv.id 
        LEFT JOIN nganh n ON sv.nganhId = n.id
        WHERE s.kyThiId = ?
        ORDER BY s.soBaoDanh
    ');
    $stmt->execute([$kyThiId]);
    $dsThiSinh = $stmt->fetchAll();

    // tạo file Excel sử dụng PhpSpreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // thiết lập tiêu đề file
    $sheet->setTitle('Danh sách thí sinh');
    
    // thiết lập tiêu đề trang
    $sheet->setCellValue('A1', 'DANH SÁCH THÍ SINH');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // thông tin kỳ thi
    $sheet->setCellValue('A2', 'Kỳ thi: ' . $kyThi['tenKyThi']);
    $sheet->mergeCells('A2:E2');
    $sheet->setCellValue('A3', 'Môn học: ' . $kyThi['tenMonHoc']);
    $sheet->mergeCells('A3:E3');
    $sheet->setCellValue('A4', 'Ngày xuất: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A4:E4');
    
    // thiết lập tiêu đề cột
    $sheet->setCellValue('A6', 'STT');
    $sheet->setCellValue('B6', 'Số báo danh');
    $sheet->setCellValue('C6', 'Mã sinh viên');
    $sheet->setCellValue('D6', 'Họ tên');
    $sheet->setCellValue('E6', 'Ngành');
    
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
    $sheet->getStyle('A6:E6')->applyFromArray($headerStyle);
    
    // điền dữ liệu
    $row = 7;
    $stt = 1;
    foreach ($dsThiSinh as $thiSinh) {
        $sheet->setCellValue('A' . $row, $stt++);
        $sheet->setCellValue('B' . $row, $thiSinh['soBaoDanh']);
        $sheet->setCellValue('C' . $row, $thiSinh['maSinhVien']);
        $sheet->setCellValue('D' . $row, $thiSinh['hoTen']);
        $sheet->setCellValue('E' . $row, $thiSinh['tenNganh'] ?? '');
        
        // căn giữa cột STT và số báo danh
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
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
    $sheet->getStyle('A6:E' . ($row - 1))->applyFromArray($dataStyle);
    
    // tự động điều chỉnh chiều rộng cột
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // thiết lập header để tải file
    $filename = "danh_sach_thi_sinh_" . date("Y_m_d") . ".xlsx";
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