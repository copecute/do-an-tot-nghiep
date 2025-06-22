<?php
require_once '../include/config.php';

if (!isset($_GET['kyThiId']) || empty($_GET['kyThiId'])) {
    $_SESSION['flash_message'] = 'id kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}
$kyThiId = $_GET['kyThiId'];

// lấy thông tin kỳ thi
$stmt = $pdo->prepare('SELECT k.*, m.tenMonHoc FROM kyThi k JOIN monHoc m ON k.monHocId = m.id WHERE k.id = ?');
$stmt->execute([$kyThiId]);
$kyThi = $stmt->fetch();
if (!$kyThi) {
    $_SESSION['flash_message'] = 'không tìm thấy kỳ thi!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

// tổng số thí sinh
$stmt = $pdo->prepare('SELECT COUNT(*) FROM soBaoDanh WHERE kyThiId = ?');
$stmt->execute([$kyThiId]);
$soThiSinh = $stmt->fetchColumn();

// tổng số đề thi
$stmt = $pdo->prepare('SELECT COUNT(*) FROM deThi WHERE kyThiId = ?');
$stmt->execute([$kyThiId]);
$soDeThi = $stmt->fetchColumn();

// tổng số bài thi đã nộp
$stmt = $pdo->prepare('SELECT COUNT(*) FROM baiThi WHERE deThiId IN (SELECT id FROM deThi WHERE kyThiId = ?)');
$stmt->execute([$kyThiId]);
$soBaiNop = $stmt->fetchColumn();

// điểm trung bình, cao nhất, thấp nhất
$stmt = $pdo->prepare('SELECT AVG(diem) as diemTB, MAX(diem) as diemMax, MIN(diem) as diemMin FROM baiThi WHERE deThiId IN (SELECT id FROM deThi WHERE kyThiId = ?)');
$stmt->execute([$kyThiId]);
$thongKeDiem = $stmt->fetch();

// top 10 thí sinh điểm cao nhất
$stmt = $pdo->prepare('
    SELECT sv.maSinhVien, sv.hoTen, bt.diem, bt.thoiGianNop
    FROM baiThi bt
    JOIN soBaoDanh sbd ON bt.soBaoDanhId = sbd.id
    JOIN sinhVien sv ON sbd.sinhVienId = sv.id
    WHERE sbd.kyThiId = ?
    ORDER BY bt.diem DESC, bt.thoiGianNop ASC
    LIMIT 10
');
$stmt->execute([$kyThiId]);
$topThiSinh = $stmt->fetchAll();

include '../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Thống kê kỳ thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="mb-4 d-flex justify-content-end">
        <a href="/quan-ly-ky-thi/thong-ke-xuat.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success">
            <i class="fas fa-print me-1"></i> xuất báo cáo
        </a>
    </div>
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-primary"><?php echo $soThiSinh; ?></div>
                    <div class="text-muted">tổng số thí sinh</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-success"><?php echo $soDeThi; ?></div>
                    <div class="text-muted">tổng số đề thi</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-info"><?php echo $soBaiNop; ?></div>
                    <div class="text-muted">bài thi đã nộp</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-warning"><?php echo number_format($thongKeDiem['diemTB'], 2); ?></div>
                    <div class="text-muted">điểm trung bình</div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">điểm cao nhất/thấp nhất</div>
                <div class="card-body">
                    <div>điểm cao nhất: <b><?php echo number_format($thongKeDiem['diemMax'], 2); ?></b></div>
                    <div>điểm thấp nhất: <b><?php echo number_format($thongKeDiem['diemMin'], 2); ?></b></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow">
                <div class="card-header bg-success text-white">top 10 thí sinh điểm cao nhất</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>stt</th>
                                    <th>mã sinh viên</th>
                                    <th>họ tên</th>
                                    <th>điểm</th>
                                    <th>thời gian nộp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topThiSinh as $i => $row): ?>
                                <tr>
                                    <td><?php echo $i+1; ?></td>
                                    <td><?php echo htmlspecialchars($row['maSinhVien']); ?></td>
                                    <td><?php echo htmlspecialchars($row['hoTen']); ?></td>
                                    <td><?php echo number_format($row['diem'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['thoiGianNop']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($topThiSinh)): ?>
                                <tr><td colspan="5" class="text-center">chưa có dữ liệu</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/layouts/footer.php'; ?> 