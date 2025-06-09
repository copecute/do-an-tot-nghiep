<?php
require_once '../../include/config.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

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

// khởi tạo các biến thống kê
$tongBaiThi = 0;
$diemTrungBinh = 0;
$diemCaoNhat = 0;
$diemThapNhat = 10;
$soBaiDat = 0;
$tyLeDat = 0;
$dsKetQua = [];

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

    // lấy danh sách kết quả
    $stmt = $pdo->prepare('
        SELECT b.*, d.tenDeThi, s.soBaoDanh, sv.maSinhVien, sv.hoTen, n.tenNganh
        FROM baiThi b 
        JOIN deThi d ON b.deThiId = d.id
        JOIN soBaoDanh s ON b.soBaoDanhId = s.id
        JOIN sinhVien sv ON s.sinhVienId = sv.id
        LEFT JOIN nganh n ON sv.nganhId = n.id
        WHERE d.kyThiId = ?
        ORDER BY b.diem DESC, sv.hoTen
    ');
    $stmt->execute([$kyThiId]);
    $dsKetQua = $stmt->fetchAll();

    // tính thống kê
    $tongBaiThi = count($dsKetQua);

    if ($tongBaiThi > 0) {
        foreach ($dsKetQua as $ketQua) {
            $diemTrungBinh += $ketQua['diem'];
            $diemCaoNhat = max($diemCaoNhat, $ketQua['diem']);
            $diemThapNhat = min($diemThapNhat, $ketQua['diem']);
            if ($ketQua['diem'] >= 5) $soBaiDat++;
        }
        $diemTrungBinh /= $tongBaiThi;
        $tyLeDat = ($soBaiDat / $tongBaiThi) * 100;
    }

    // tạo file Excel sử dụng PhpSpreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // thiết lập tiêu đề file
    $sheet->setTitle('Kết quả thi');
    
    // thiết lập tiêu đề trang
    $sheet->setCellValue('A1', 'KẾT QUẢ THI');
    $sheet->mergeCells('A1:I1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // thông tin kỳ thi
    $sheet->setCellValue('A2', 'Kỳ thi: ' . $kyThi['tenKyThi']);
    $sheet->mergeCells('A2:I2');
    $sheet->setCellValue('A3', 'Môn học: ' . $kyThi['tenMonHoc']);
    $sheet->mergeCells('A3:I3');
    $sheet->setCellValue('A4', 'Ngày xuất: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A4:I4');
    
    // thống kê
    $sheet->setCellValue('A6', 'THỐNG KÊ');
    $sheet->mergeCells('A6:I6');
    $sheet->getStyle('A6')->getFont()->setBold(true);
    $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->setCellValue('A7', 'Tổng số bài thi:');
    $sheet->setCellValue('C7', $tongBaiThi);
    
    $sheet->setCellValue('E7', 'Điểm trung bình:');
    $sheet->setCellValue('G7', $diemTrungBinh);
    $sheet->getStyle('G7')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
    
    $sheet->setCellValue('A8', 'Điểm cao nhất:');
    $sheet->setCellValue('C8', $diemCaoNhat);
    $sheet->getStyle('C8')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
    
    $sheet->setCellValue('E8', 'Điểm thấp nhất:');
    $sheet->setCellValue('G8', $diemThapNhat);
    $sheet->getStyle('G8')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
    
    $sheet->setCellValue('A9', 'Số bài đạt:');
    $sheet->setCellValue('C9', $soBaiDat);
    
    $sheet->setCellValue('E9', 'Tỷ lệ đạt:');
    $sheet->setCellValue('G9', $tyLeDat . '%');
    $sheet->getStyle('G9')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
    
    // thiết lập tiêu đề cột
    $sheet->setCellValue('A11', 'STT');
    $sheet->setCellValue('B11', 'Số báo danh');
    $sheet->setCellValue('C11', 'Mã sinh viên');
    $sheet->setCellValue('D11', 'Họ tên');
    $sheet->setCellValue('E11', 'Ngành');
    $sheet->setCellValue('F11', 'Đề thi');
    $sheet->setCellValue('G11', 'Thời gian nộp');
    $sheet->setCellValue('H11', 'Số câu đúng');
    $sheet->setCellValue('I11', 'Điểm');
    
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
    $sheet->getStyle('A11:I11')->applyFromArray($headerStyle);
    
    // điền dữ liệu
    $row = 12;
    $stt = 1;
    foreach ($dsKetQua as $ketQua) {
        $sheet->setCellValue('A' . $row, $stt++);
        $sheet->setCellValue('B' . $row, $ketQua['soBaoDanh']);
        $sheet->setCellValue('C' . $row, $ketQua['maSinhVien']);
        $sheet->setCellValue('D' . $row, $ketQua['hoTen']);
        $sheet->setCellValue('E' . $row, $ketQua['tenNganh'] ?? '');
        $sheet->setCellValue('F' . $row, $ketQua['tenDeThi']);
        $sheet->setCellValue('G' . $row, $ketQua['thoiGianNop'] ? date('d/m/Y H:i:s', strtotime($ketQua['thoiGianNop'])) : '');
        $sheet->setCellValue('H' . $row, $ketQua['soCauDung'] . '/' . $ketQua['tongSoCau']);
        $sheet->setCellValue('I' . $row, $ketQua['diem']);
        
        // định dạng số
        $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        
        // tô màu điểm đạt/không đạt
        if ($ketQua['diem'] >= 5) {
            $sheet->getStyle('I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE');
        } else {
            $sheet->getStyle('I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFC7CE');
        }
        
        // căn giữa một số cột
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
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
    $sheet->getStyle('A11:I' . ($row - 1))->applyFromArray($dataStyle);
    
    // tự động điều chỉnh chiều rộng cột
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // thiết lập header để tải file
    $filename = "ket_qua_thi_" . date("Y_m_d") . ".xlsx";
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
    header("Location: /quan-ly-ky-thi/ket-qua/?kyThiId=$kyThiId");
    exit;
}
