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
$dsThiSinh = []; // Khởi tạo mảng rỗng để tránh lỗi

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

} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
    error_log("Lỗi truy vấn danh sách thí sinh: " . $e->getMessage());
}

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Quản lý thí sinh</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">quản lý thí sinh</h5>
                        <p class="text-muted mb-0">
                            kỳ thi: <?php echo htmlspecialchars($kyThi['tenKyThi']); ?> | 
                            môn học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="/quan-ly-ky-thi/thi-sinh/them.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> thêm thí sinh
                        </a>
                        <a href="/quan-ly-ky-thi/thi-sinh/excel/nhap.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Nhập/Xuất excel
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

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>số báo danh</th>
                                    <th>mã sinh viên</th>
                                    <th>họ tên</th>
                                    <th>ngành</th>
                                    <th>số bài thi</th>
                                    <th>thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dsThiSinh)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">chưa có thí sinh nào!</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dsThiSinh as $thiSinh): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($thiSinh['soBaoDanh']); ?></td>
                                            <td><?php echo htmlspecialchars($thiSinh['maSinhVien']); ?></td>
                                            <td><?php echo htmlspecialchars($thiSinh['hoTen']); ?></td>
                                            <td><?php echo htmlspecialchars($thiSinh['tenNganh'] ?? ''); ?></td>
                                            <td><?php echo $thiSinh['soBaiThi']; ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/quan-ly-ky-thi/thi-sinh/sua.php?id=<?php echo $thiSinh['id']; ?>&kyThiId=<?php echo $kyThiId; ?>" 
                                                        class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="tooltip" 
                                                        title="sửa thông tin">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($thiSinh['soBaiThi'] == 0): ?>
                                                    <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="tooltip" 
                                                        title="xóa thí sinh"
                                                        onclick="xacNhanXoa(<?php echo $thiSinh['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
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
    if (confirm('bạn có chắc chắn muốn xóa thí sinh này?')) {
        window.location.href = `/quan-ly-ky-thi/thi-sinh/xoa.php?id=${id}&kyThiId=<?php echo $kyThiId; ?>`;
    }
}
</script>

<?php include '../../include/layouts/footer.php'; ?> 