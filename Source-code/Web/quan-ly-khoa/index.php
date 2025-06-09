<?php
require_once '../include/config.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['vai_tro'] !== 'admin') {
    $_SESSION['flash_message'] = 'Bạn không có quyền truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

// Xử lý thêm khoa mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $tenKhoa = trim($_POST['tenKhoa'] ?? '');
        
        if (empty($tenKhoa)) {
            $error = 'Vui lòng nhập tên khoa';
        } else {
            try {
                // Kiểm tra xem khoa đã tồn tại chưa
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM khoa WHERE tenKhoa = ?');
                $stmt->execute([$tenKhoa]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error = 'Khoa này đã tồn tại';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO khoa (tenKhoa) VALUES (?)');
                    $stmt->execute([$tenKhoa]);
                    
                    $success = 'Thêm khoa thành công!';
                }
            } catch (PDOException $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
    // Xử lý cập nhật khoa
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? '';
        $tenKhoa = trim($_POST['tenKhoa'] ?? '');
        
        if (empty($tenKhoa)) {
            $error = 'Vui lòng nhập tên khoa';
        } else {
            try {
                // Kiểm tra xem tên khoa mới có trùng với khoa khác không
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM khoa WHERE tenKhoa = ? AND id != ?');
                $stmt->execute([$tenKhoa, $id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error = 'Khoa này đã tồn tại';
                } else {
                    $stmt = $pdo->prepare('UPDATE khoa SET tenKhoa = ? WHERE id = ?');
                    $stmt->execute([$tenKhoa, $id]);
                    
                    $success = 'Cập nhật khoa thành công!';
                }
            } catch (PDOException $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
    // Xử lý xóa khoa
    elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? '';
        
        try {
            // Kiểm tra xem khoa có ngành nào không
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM nganh WHERE khoaId = ?');
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error = 'Không thể xóa khoa này vì đã có ngành thuộc khoa!';
            } else {
                $stmt = $pdo->prepare('DELETE FROM khoa WHERE id = ?');
                $stmt->execute([$id]);
                
                $success = 'Xóa khoa thành công!';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách khoa
try {
    $stmt = $pdo->query('SELECT * FROM khoa ORDER BY tenKhoa');
    $dsKhoa = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
    $dsKhoa = [];
}

include '../include/layouts/header.php';
?>
<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Quản lý khoa</li>
    </ol>
</nav>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý khoa</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalThemKhoa">
            <i class="fas fa-plus"></i> Thêm khoa mới
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="80">ID</th>
                            <th>Tên khoa</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsKhoa)): ?>
                            <tr>
                                <td colspan="3" class="text-center">Chưa có khoa nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dsKhoa as $khoa): ?>
                                <tr>
                                    <td><?php echo $khoa['id']; ?></td>
                                    <td><?php echo htmlspecialchars($khoa['tenKhoa']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalSuaKhoa" 
                                                data-id="<?php echo $khoa['id']; ?>"
                                                data-ten="<?php echo htmlspecialchars($khoa['tenKhoa']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalXoaKhoa"
                                                data-id="<?php echo $khoa['id']; ?>"
                                                data-ten="<?php echo htmlspecialchars($khoa['tenKhoa']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<!-- Modal Thêm Khoa -->
<div class="modal fade" id="modalThemKhoa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm khoa mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="tenKhoa" class="form-label">Tên khoa</label>
                        <input type="text" class="form-control" id="tenKhoa" name="tenKhoa" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa Khoa -->
<div class="modal fade" id="modalSuaKhoa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa thông tin khoa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="suaKhoaId">
                    <div class="mb-3">
                        <label for="suaTenKhoa" class="form-label">Tên khoa</label>
                        <input type="text" class="form-control" id="suaTenKhoa" name="tenKhoa" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xóa Khoa -->
<div class="modal fade" id="modalXoaKhoa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa khoa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa khoa "<span id="tenKhoaXoa"></span>"?</p>
                <p class="text-danger mb-0">Lưu ý: Chỉ có thể xóa khoa chưa có ngành nào.</p>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="xoaKhoaId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Xử lý modal sửa khoa
document.querySelectorAll('[data-bs-target="#modalSuaKhoa"]').forEach(button => {
    button.addEventListener('click', event => {
        const id = button.getAttribute('data-id');
        const ten = button.getAttribute('data-ten');
        document.getElementById('suaKhoaId').value = id;
        document.getElementById('suaTenKhoa').value = ten;
    });
});

// Xử lý modal xóa khoa
document.querySelectorAll('[data-bs-target="#modalXoaKhoa"]').forEach(button => {
    button.addEventListener('click', event => {
        const id = button.getAttribute('data-id');
        const ten = button.getAttribute('data-ten');
        document.getElementById('xoaKhoaId').value = id;
        document.getElementById('tenKhoaXoa').textContent = ten;
    });
});
</script>

<?php include '../include/layouts/footer.php'; ?>
