<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
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

// lấy thông tin kỳ thi
try {
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
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
}

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Kết quả thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">kết quả thi</h5>
                        <p class="text-muted mb-0">
                            kỳ thi: <?php echo htmlspecialchars($kyThi['tenKyThi']); ?> | 
                            môn học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="/quan-ly-ky-thi/ket-qua/xuat-excel.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> xuất excel
                        </a>
                        <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $kyThiId; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> quay lại
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                            <?php 
                                echo $_SESSION['flash_message'];
                                unset($_SESSION['flash_message']);
                                unset($_SESSION['flash_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($tongBaiThi > 0): ?>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">tổng số bài thi</h6>
                                        <h3 class="mb-0"><?php echo $tongBaiThi; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">điểm trung bình</h6>
                                        <h3 class="mb-0"><?php echo number_format($diemTrungBinh, 2); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">điểm cao nhất</h6>
                                        <h3 class="mb-0"><?php echo number_format($diemCaoNhat, 2); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">tỷ lệ đạt</h6>
                                        <h3 class="mb-0"><?php echo number_format($tyLeDat, 0); ?>%</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>số báo danh</th>
                                    <th>mã sinh viên</th>
                                    <th>họ tên</th>
                                    <th>ngành</th>
                                    <th>đề thi</th>
                                    <th>thời gian nộp</th>
                                    <th>số câu đúng</th>
                                    <th>điểm</th>
                                    <th>thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dsKetQua)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">chưa có bài thi nào!</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dsKetQua as $ketQua): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ketQua['soBaoDanh']); ?></td>
                                            <td><?php echo htmlspecialchars($ketQua['maSinhVien']); ?></td>
                                            <td><?php echo htmlspecialchars($ketQua['hoTen']); ?></td>
                                            <td><?php echo htmlspecialchars($ketQua['tenNganh'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($ketQua['tenDeThi']); ?></td>
                                            <td><?php echo $ketQua['thoiGianNop'] ? date('d/m/Y H:i:s', strtotime($ketQua['thoiGianNop'])) : ''; ?></td>
                                            <td><?php echo $ketQua['soCauDung'] . '/' . $ketQua['tongSoCau']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $ketQua['diem'] >= 5 ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo number_format($ketQua['diem'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/quan-ly-ky-thi/ket-qua/xem.php?id=<?php echo $ketQua['id']; ?>" 
                                                        class="btn btn-sm btn-info" 
                                                        data-bs-toggle="tooltip" 
                                                        title="xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../include/layouts/footer.php'; ?> 