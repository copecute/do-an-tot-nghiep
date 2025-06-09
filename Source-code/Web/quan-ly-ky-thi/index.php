<?php
require_once '../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// lấy danh sách kỳ thi
try {
    $stmt = $pdo->prepare('
        SELECT k.*, m.tenMonHoc, t.hoTen as nguoiTao,
            (SELECT COUNT(*) FROM deThi d WHERE d.kyThiId = k.id) as soDeThi,
            (SELECT COUNT(*) FROM soBaoDanh s WHERE s.kyThiId = k.id) as soThiSinh
        FROM kyThi k 
        JOIN monHoc m ON k.monHocId = m.id 
        JOIN taiKhoan t ON k.nguoiTaoId = t.id
        WHERE k.nguoiTaoId = ?
        ORDER BY k.thoiGianBatDau DESC
    ');
    $stmt->execute([$_SESSION['user_id']]);
    $dsKyThi = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
    $dsKyThi = [];
}

include '../include/layouts/header.php';
?>
<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Quản lý kỳ thi</li>
    </ol>
</nav>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">danh sách kỳ thi</h5>
                    <a href="/quan-ly-ky-thi/them.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> thêm kỳ thi
                    </a>
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
                                    <th>tên kỳ thi</th>
                                    <th>môn học</th>
                                    <th>thời gian bắt đầu</th>
                                    <th>thời gian kết thúc</th>
                                    <th>số đề thi</th>
                                    <th>số thí sinh</th>
                                    <th>người tạo</th>
                                    <th>thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dsKyThi)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">chưa có kỳ thi nào!</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dsKyThi as $kyThi): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($kyThi['tenKyThi']); ?></td>
                                            <td><?php echo htmlspecialchars($kyThi['tenMonHoc']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($kyThi['thoiGianBatDau'])); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($kyThi['thoiGianKetThuc'])); ?></td>
                                            <td><?php echo $kyThi['soDeThi']; ?></td>
                                            <td><?php echo $kyThi['soThiSinh']; ?></td>
                                            <td><?php echo htmlspecialchars($kyThi['nguoiTao']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $kyThi['id']; ?>" 
                                                        class="btn btn-sm btn-warning" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Dashboard">
                                                        <i class="fas fa-tachometer-alt"></i>
                                                    </a>
                                                    <a href="/quan-ly-ky-thi/sua.php?id=<?php echo $kyThi['id']; ?>" 
                                                        class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="tooltip" 
                                                        title="sửa kỳ thi">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="tooltip" 
                                                        title="xóa kỳ thi"
                                                        onclick="xacNhanXoa(<?php echo $kyThi['id']; ?>)">
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
    if (confirm('bạn có chắc chắn muốn xóa kỳ thi này?')) {
        window.location.href = `/quan-ly-ky-thi/xoa.php?id=${id}`;
    }
}
</script>

<?php include '../include/layouts/footer.php'; ?>
