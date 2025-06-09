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

    // lấy danh sách đề thi
    $stmt = $pdo->prepare('
        SELECT d.*, 
            (SELECT COUNT(*) FROM deThiCauHoi dc WHERE dc.deThiId = d.id) as soCauHoi,
            (SELECT COUNT(*) FROM baiThi b JOIN soBaoDanh s ON b.soBaoDanhId = s.id WHERE b.deThiId = d.id) as soBaiThi
        FROM deThi d 
        WHERE d.kyThiId = ?
        ORDER BY d.id DESC
    ');
    $stmt->execute([$kyThiId]);
    $dsDeThi = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
}

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Quản lý đề thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">quản lý đề thi</h5>
                        <p class="text-muted mb-0">
                            kỳ thi: <?php echo htmlspecialchars($kyThi['tenKyThi']); ?> | 
                            môn học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="/quan-ly-ky-thi/de-thi/tao.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success">
                            <i class="fas fa-plus"></i> tạo đề thi
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

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead> 
                                <tr>
                                    <th>tên đề thi</th>
                                    <th>hình thức tạo</th>
                                    <th>thời gian làm</th>
                                    <th>số câu hỏi</th>
                                    <th>số bài nộp</th>
                                    <th>thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dsDeThi)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">chưa có đề thi nào!</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dsDeThi as $deThi): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($deThi['tenDeThi']); ?></td>
                                            <td>
                                                <?php if ($deThi['isTuDong']): ?>
                                                    <span class="badge bg-success">tự động</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">thủ công</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $deThi['thoiGian']; ?> phút</td>
                                            <td><?php echo $deThi['soCauHoi']; ?></td>
                                            <td><?php echo $deThi['soBaiThi']; ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/quan-ly-ky-thi/de-thi/xem.php?id=<?php echo $deThi['id']; ?>" 
                                                        class="btn btn-sm btn-info" 
                                                        data-bs-toggle="tooltip" 
                                                        title="xem đề thi">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/quan-ly-ky-thi/de-thi/sua.php?id=<?php echo $deThi['id']; ?>" 
                                                        class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="tooltip" 
                                                        title="sửa đề thi">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="tooltip" 
                                                        title="xóa đề thi"
                                                        onclick="xacNhanXoa(<?php echo $deThi['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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

<script>
function xacNhanXoa(id) {
    if (confirm('bạn có chắc chắn muốn xóa đề thi này?')) {
        window.location.href = `/quan-ly-ky-thi/de-thi/xoa.php?id=${id}`;
    }
}
</script>

<?php include '../../include/layouts/footer.php'; ?> 