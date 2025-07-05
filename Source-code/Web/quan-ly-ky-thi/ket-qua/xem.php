<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id bài thi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID bài thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$baiThiId = $_GET['id'];
$baiThi = null;
$thiSinh = null;
$deThi = null;
$kyThi = null;
$error = null;

// kiểm tra phân quyền
$isAdmin = ($_SESSION['vai_tro'] == 'admin');

try {
    // lấy thông tin bài thi và thông tin liên quan
    $stmt = $pdo->prepare('
        SELECT b.*, 
            d.tenDeThi, d.kyThiId, 
            s.soBaoDanh, 
            sv.maSinhVien, sv.hoTen,
            k.tenKyThi, k.monHocId, k.nguoiTaoId,
            m.tenMonHoc
        FROM baiThi b 
        JOIN deThi d ON b.deThiId = d.id
        JOIN soBaoDanh s ON b.soBaoDanhId = s.id
        JOIN sinhVien sv ON s.sinhVienId = sv.id
        JOIN kyThi k ON d.kyThiId = k.id
        JOIN monHoc m ON k.monHocId = m.id
        WHERE b.id = ?
    ');
    $stmt->execute([$baiThiId]);
    $baiThi = $stmt->fetch();

    if (!$baiThi) {
        $_SESSION['flash_message'] = 'Không tìm thấy bài thi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    // kiểm tra quyền truy cập
    if (!$isAdmin && $baiThi['nguoiTaoId'] != $_SESSION['user_id']) {
        $_SESSION['flash_message'] = 'Bạn không có quyền truy cập bài thi này!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}
$page_title = "Kết quả: " . $baiThi['hoTen'];
include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang Chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản Lý Kỳ Thi</a></li>
        <li class="breadcrumb-item">
            <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $baiThi['kyThiId']; ?>">Kỳ Thi: <?php echo htmlspecialchars($baiThi['kyThiId']); ?></a>
        </li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/ket-qua/?kyThiId=<?php echo $baiThi['kyThiId']; ?>">Kết Quả Thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Xem Kết Quả</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Chi Tiết Bài Thi</h5>
                        <?php if ($baiThi): ?>
                        <p class="text-muted mb-0">
                            Kỳ Thi: <?php echo htmlspecialchars($baiThi['tenKyThi']); ?> | 
                            Môn Học: <?php echo htmlspecialchars($baiThi['tenMonHoc']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="btn-group">
                        <?php if ($baiThi): ?>
                        <a href="/quan-ly-ky-thi/ket-qua/?kyThiId=<?php echo htmlspecialchars($baiThi['kyThiId']); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay Lại
                        </a>
                        <?php else: ?>
                        <a href="/quan-ly-ky-thi" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay Lại
                        </a>
                        <?php endif; ?>
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

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($baiThi): ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Thông Tin Thí Sinh</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="150">Họ Tên:</th>
                                            <td><?php echo htmlspecialchars($baiThi['hoTen']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Mã Sinh Viên:</th>
                                            <td><?php echo htmlspecialchars($baiThi['maSinhVien']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Số Báo Danh:</th>
                                            <td><?php echo htmlspecialchars($baiThi['soBaoDanh']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Thông Tin Bài Thi</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="150">Đề Thi:</th>
                                            <td><?php echo htmlspecialchars($baiThi['tenDeThi']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Thời Gian Nộp:</th>
                                            <td><?php echo $baiThi['thoiGianNop'] ? date('d/m/Y H:i:s', strtotime($baiThi['thoiGianNop'])) : 'Chưa Nộp'; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Số Câu Đúng:</th>
                                            <td><?php echo $baiThi['soCauDung'] . '/' . $baiThi['tongSoCau']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Điểm:</th>
                                            <td>
                                                <span class="badge <?php echo $baiThi['diem'] >= 5 ? 'bg-success' : 'bg-danger'; ?> fs-6">
                                                    <?php echo number_format($baiThi['diem'], 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Kết Quả:</th>
                                            <td>
                                                <?php if ($baiThi['diem'] >= 5): ?>
                                                    <span class="text-success fw-bold">Đạt</span>
                                                <?php else: ?>
                                                    <span class="text-danger fw-bold">Không Đạt</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../include/layouts/footer.php'; ?>
