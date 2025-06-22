<?php
require_once '../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id kỳ thi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'id kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$kyThiId = $_GET['id'];

try {
    // lấy thông tin kỳ thi
    $stmt = $pdo->prepare('
        SELECT k.*, m.tenMonHoc, t.hoTen as nguoiTao
        FROM kyThi k 
        JOIN monHoc m ON k.monHocId = m.id 
        JOIN taiKhoan t ON k.nguoiTaoId = t.id
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

    // lấy thống kê
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM deThi WHERE kyThiId = ?');
    $stmt->execute([$kyThiId]);
    $soDeThi = $stmt->fetch()['count'];

    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM soBaoDanh WHERE kyThiId = ?');
    $stmt->execute([$kyThiId]);
    $soThiSinh = $stmt->fetch()['count'];

    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count 
        FROM baiThi b 
        JOIN soBaoDanh s ON b.soBaoDanhId = s.id 
        WHERE s.kyThiId = ?
    ');
    $stmt->execute([$kyThiId]);
    $soBaiThi = $stmt->fetch()['count'];

    // lấy 5 bài thi mới nhất
    $stmt = $pdo->prepare('
        SELECT b.*, d.tenDeThi, s.soBaoDanh, sv.maSinhVien, sv.hoTen
        FROM baiThi b 
        JOIN deThi d ON b.deThiId = d.id
        JOIN soBaoDanh s ON b.soBaoDanhId = s.id
        JOIN sinhVien sv ON s.sinhVienId = sv.id
        WHERE s.kyThiId = ?
        ORDER BY b.thoiGianNop DESC
        LIMIT 5
    ');
    $stmt->execute([$kyThiId]);
    $dsBaiThiMoi = $stmt->fetchAll();

    // tính điểm trung bình
    $stmt = $pdo->prepare('
        SELECT AVG(diem) as diemTB 
        FROM baiThi b 
        JOIN soBaoDanh s ON b.soBaoDanhId = s.id 
        WHERE s.kyThiId = ?
    ');
    $stmt->execute([$kyThiId]);
    $diemTB = $stmt->fetch()['diemTB'] ?? 0;
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
}

include '../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Bảng điều khiển</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($kyThi['tenKyThi']); ?></h5>
                        <p class="text-muted mb-0">
                            môn học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?> | 
                            người tạo: <?php echo htmlspecialchars($kyThi['nguoiTao']); ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="/quan-ly-ky-thi/sua.php?id=<?php echo $kyThiId; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> sửa kỳ thi
                        </a>
                        <button type="button" class="btn btn-danger" onclick="xacNhanXoa(<?php echo $kyThiId; ?>)">
                            <i class="fas fa-trash"></i> xóa kỳ thi
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>thời gian bắt đầu:</strong> <?php echo date('d/m/Y H:i', strtotime($kyThi['thoiGianBatDau'])); ?></p>
                            <p><strong>thời gian kết thúc:</strong> <?php echo date('d/m/Y H:i', strtotime($kyThi['thoiGianKetThuc'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>trạng thái:</strong>
                                <?php
                                $now = time();
                                $start = strtotime($kyThi['thoiGianBatDau']);
                                $end = strtotime($kyThi['thoiGianKetThuc']);
                                if ($now < $start) {
                                    echo '<span class="badge bg-info">chưa bắt đầu</span>';
                                } elseif ($now > $end) {
                                    echo '<span class="badge bg-danger">đã kết thúc</span>';
                                } else {
                                    echo '<span class="badge bg-success">đang diễn ra</span>';
                                }
                                ?>
                            </p>
                            <p>
                                <strong>thời gian còn lại:</strong>
                                <span id="thoiGianConLai">
                                <?php
                                if ($now < $start) {
                                    $diff = $start - $now;
                                    echo 'còn ' . floor($diff/86400) . ' ngày ' . date('H:i:s', $diff);
                                } elseif ($now > $end) {
                                    echo 'đã kết thúc';
                                } else {
                                    $diff = $end - $now;
                                    echo 'còn ' . floor($diff/86400) . ' ngày ' . date('H:i:s', $diff);
                                }
                                ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <h1 class="display-4 text-primary mb-2"><?php echo $soDeThi; ?></h1>
                    <h6 class="text-muted">đề thi</h6>
                    <a href="/quan-ly-ky-thi/de-thi/index.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-primary btn-sm mt-3">
                        <i class="fas fa-file-alt"></i> quản lý đề thi
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <h1 class="display-4 text-success mb-2"><?php echo $soThiSinh; ?></h1>
                    <h6 class="text-muted">thí sinh</h6>
                    <a href="/quan-ly-ky-thi/thi-sinh/index.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success btn-sm mt-3">
                        <i class="fas fa-users"></i> quản lý thí sinh
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <h1 class="display-4 text-info mb-2"><?php echo $soBaiThi; ?></h1>
                    <h6 class="text-muted">bài thi đã nộp</h6>
                    <a href="/quan-ly-ky-thi/ket-qua/index.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-info btn-sm mt-3">
                        <i class="fas fa-chart-bar"></i> xem kết quả
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <h1 class="display-4 text-warning mb-2"><?php echo number_format($diemTB, 2); ?></h1>
                    <h6 class="text-muted">điểm trung bình</h6>
                    <a href="/quan-ly-ky-thi/thong-ke.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-warning btn-sm mt-3">
                        <i class="fas fa-chart-line"></i> xem thống kê
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($dsBaiThiMoi)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">bài thi mới nhất</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>thời gian nộp</th>
                                        <th>số báo danh</th>
                                        <th>họ tên</th>
                                        <th>đề thi</th>
                                        <th>số câu đúng</th>
                                        <th>điểm</th>
                                        <th>thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dsBaiThiMoi as $baiThi): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($baiThi['thoiGianNop'])); ?></td>
                                            <td><?php echo htmlspecialchars($baiThi['soBaoDanh']); ?></td>
                                            <td><?php echo htmlspecialchars($baiThi['hoTen']); ?></td>
                                            <td><?php echo htmlspecialchars($baiThi['tenDeThi']); ?></td>
                                            <td><?php echo $baiThi['soCauDung'] . '/' . $baiThi['tongSoCau']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $baiThi['diem'] >= 5 ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo number_format($baiThi['diem'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/quan-ly-ky-thi/ket-qua/xem.php?id=<?php echo $baiThi['id']; ?>" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="tooltip" 
                                                    title="xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function xacNhanXoa(id) {
    if (confirm('bạn có chắc chắn muốn xóa kỳ thi này?')) {
        window.location.href = `/quan-ly-ky-thi/xoa.php?id=${id}`;
    }
}

// cập nhật thời gian còn lại mỗi giây
function capNhatThoiGian() {
    const now = new Date().getTime();
    const start = new Date('<?php echo $kyThi['thoiGianBatDau']; ?>').getTime();
    const end = new Date('<?php echo $kyThi['thoiGianKetThuc']; ?>').getTime();
    
    let diff;
    let text;
    
    if (now < start) {
        diff = start - now;
        text = 'còn ';
    } else if (now > end) {
        text = 'đã kết thúc';
    } else {
        diff = end - now;
        text = 'còn ';
    }
    
    if (diff) {
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        text += days + ' ngày ' + 
            (hours < 10 ? '0' : '') + hours + ':' +
            (minutes < 10 ? '0' : '') + minutes + ':' +
            (seconds < 10 ? '0' : '') + seconds;
    }
    
    document.getElementById('thoiGianConLai').textContent = text;
}

// cập nhật ngay khi trang load xong
capNhatThoiGian();

// cập nhật mỗi giây
setInterval(capNhatThoiGian, 1000);
</script>

<?php include '../include/layouts/footer.php'; ?> 