<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$error = '';
$success = '';

// lấy danh sách thể loại câu hỏi
try {
    $stmt = $pdo->query('
        SELECT t.*, m.tenMonHoc, n.tenNganh, k.tenKhoa,
            (SELECT COUNT(*) FROM cauHoi WHERE theLoaiId = t.id) as soCauHoi
        FROM theLoaiCauHoi t
        JOIN monHoc m ON t.monHocId = m.id
        JOIN nganh n ON m.nganhId = n.id
        JOIN khoa k ON n.khoaId = k.id
        ORDER BY k.tenKhoa, n.tenNganh, m.tenMonHoc, t.tenTheLoai
    ');
    $dsTheLoai = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
    $dsTheLoai = [];
}

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/ngan-hang-cau-hoi">Ngân hàng câu hỏi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Quản lý thể loại</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Quản lý thể loại câu hỏi</h2>
        <div>
            <a href="/ngan-hang-cau-hoi" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> quay lại
            </a>
            <a href="/ngan-hang-cau-hoi/the-loai/add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> thêm thể loại mới
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?>">
            <?php 
                echo $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
            ?>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="80">ID</th>
                            <th>tên thể loại</th>
                            <th width="200">môn học</th>
                            <th width="200">ngành</th>
                            <th width="200">khoa</th>
                            <th width="120">số câu hỏi</th>
                            <th width="150">thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsTheLoai)): ?>
                            <tr>
                                <td colspan="7" class="text-center">chưa có thể loại câu hỏi nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dsTheLoai as $theLoai): ?>
                                <tr>
                                    <td><?php echo $theLoai['id']; ?></td>
                                    <td><?php echo htmlspecialchars($theLoai['tenTheLoai']); ?></td>
                                    <td><?php echo htmlspecialchars($theLoai['tenMonHoc']); ?></td>
                                    <td><?php echo htmlspecialchars($theLoai['tenNganh']); ?></td>
                                    <td><?php echo htmlspecialchars($theLoai['tenKhoa']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            <?php echo $theLoai['soCauHoi']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/ngan-hang-cau-hoi/the-loai/edit.php?id=<?php echo $theLoai['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/ngan-hang-cau-hoi/the-loai/delete.php?id=<?php echo $theLoai['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('bạn có chắc chắn muốn xóa thể loại này?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
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

<?php include '../../include/layouts/footer.php'; ?> 