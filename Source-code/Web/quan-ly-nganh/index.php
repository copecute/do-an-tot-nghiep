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

// Xử lý thêm ngành mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $tenNganh = trim($_POST['tenNganh'] ?? '');
        $khoaId = $_POST['khoaId'] ?? '';
        
        if (empty($tenNganh) || empty($khoaId)) {
            $error = 'Vui lòng nhập đầy đủ thông tin ngành';
        } else {
            try {
                // Kiểm tra xem ngành đã tồn tại trong khoa chưa
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM nganh WHERE tenNganh = ? AND khoaId = ?');
                $stmt->execute([$tenNganh, $khoaId]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error = 'Ngành này đã tồn tại trong khoa';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO nganh (tenNganh, khoaId) VALUES (?, ?)');
                    $stmt->execute([$tenNganh, $khoaId]);
                    
                    $success = 'Thêm ngành thành công!';
                }
            } catch (PDOException $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
    // Xử lý cập nhật ngành
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? '';
        $tenNganh = trim($_POST['tenNganh'] ?? '');
        $khoaId = $_POST['khoaId'] ?? '';
        
        if (empty($tenNganh) || empty($khoaId)) {
            $error = 'Vui lòng nhập đầy đủ thông tin ngành';
        } else {
            try {
                // Kiểm tra xem tên ngành mới có trùng với ngành khác trong cùng khoa không
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM nganh WHERE tenNganh = ? AND khoaId = ? AND id != ?');
                $stmt->execute([$tenNganh, $khoaId, $id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error = 'Ngành này đã tồn tại trong khoa';
                } else {
                    $stmt = $pdo->prepare('UPDATE nganh SET tenNganh = ?, khoaId = ? WHERE id = ?');
                    $stmt->execute([$tenNganh, $khoaId, $id]);
                    
                    $success = 'Cập nhật ngành thành công!';
                }
            } catch (PDOException $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
    // Xử lý xóa ngành
    elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? '';
        
        try {
            // Kiểm tra xem ngành có môn học nào không
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM monHoc WHERE nganhId = ?');
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error = 'Không thể xóa ngành này vì đã có môn học thuộc ngành!';
            } else {
                $stmt = $pdo->prepare('DELETE FROM nganh WHERE id = ?');
                $stmt->execute([$id]);
                
                $success = 'Xóa ngành thành công!';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách khoa cho dropdown
try {
    $stmt = $pdo->query('SELECT * FROM khoa ORDER BY tenKhoa');
    $dsKhoa = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
    $dsKhoa = [];
}

// Lấy danh sách ngành với tên khoa
try {
    $stmt = $pdo->query('
        SELECT n.*, k.tenKhoa 
        FROM nganh n 
        JOIN khoa k ON n.khoaId = k.id 
        ORDER BY k.tenKhoa, n.tenNganh
    ');
    $dsNganh = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
    $dsNganh = [];
}

include '../include/layouts/header.php';
?>
<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Quản lý ngành</li>
    </ol>
</nav>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý ngành</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalThemNganh">
            <i class="fas fa-plus"></i> Thêm ngành mới
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
                            <th>Tên ngành</th>
                            <th>Khoa</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsNganh)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Chưa có ngành nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dsNganh as $nganh): ?>
                                <tr>
                                    <td><?php echo $nganh['id']; ?></td>
                                    <td><?php echo htmlspecialchars($nganh['tenNganh']); ?></td>
                                    <td><?php echo htmlspecialchars($nganh['tenKhoa']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalSuaNganh" 
                                                data-id="<?php echo $nganh['id']; ?>"
                                                data-ten="<?php echo htmlspecialchars($nganh['tenNganh']); ?>"
                                                data-khoa="<?php echo $nganh['khoaId']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalXoaNganh"
                                                data-id="<?php echo $nganh['id']; ?>"
                                                data-ten="<?php echo htmlspecialchars($nganh['tenNganh']); ?>"
                                                data-khoa="<?php echo htmlspecialchars($nganh['tenKhoa']); ?>">
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

<!-- Modal Thêm Ngành -->
<div class="modal fade" id="modalThemNganh" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm ngành mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="khoaId" class="form-label">Khoa <span class="text-danger">*</span></label>
                        <select class="form-select" id="khoaId" name="khoaId" required>
                            <option value="">Chọn khoa</option>
                            <?php foreach ($dsKhoa as $khoa): ?>
                                <option value="<?php echo $khoa['id']; ?>">
                                    <?php echo htmlspecialchars($khoa['tenKhoa']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tenNganh" class="form-label">Tên ngành <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tenNganh" name="tenNganh" required>
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

<!-- Modal Sửa Ngành -->
<div class="modal fade" id="modalSuaNganh" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa thông tin ngành</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="suaNganhId">
                    <div class="mb-3">
                        <label for="suaKhoaId" class="form-label">Khoa <span class="text-danger">*</span></label>
                        <select class="form-select" id="suaKhoaId" name="khoaId" required>
                            <option value="">Chọn khoa</option>
                            <?php foreach ($dsKhoa as $khoa): ?>
                                <option value="<?php echo $khoa['id']; ?>">
                                    <?php echo htmlspecialchars($khoa['tenKhoa']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="suaTenNganh" class="form-label">Tên ngành <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="suaTenNganh" name="tenNganh" required>
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

<!-- Modal Xóa Ngành -->
<div class="modal fade" id="modalXoaNganh" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa ngành</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa ngành "<span id="tenNganhXoa"></span>" thuộc khoa "<span id="tenKhoaXoa"></span>"?</p>
                <p class="text-danger mb-0">Lưu ý: Chỉ có thể xóa ngành chưa có môn học nào.</p>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="xoaNganhId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Xử lý modal sửa ngành
document.querySelectorAll('[data-bs-target="#modalSuaNganh"]').forEach(button => {
    button.addEventListener('click', event => {
        const id = button.getAttribute('data-id');
        const ten = button.getAttribute('data-ten');
        const khoa = button.getAttribute('data-khoa');
        document.getElementById('suaNganhId').value = id;
        document.getElementById('suaTenNganh').value = ten;
        document.getElementById('suaKhoaId').value = khoa;
    });
});

// Xử lý modal xóa ngành
document.querySelectorAll('[data-bs-target="#modalXoaNganh"]').forEach(button => {
    button.addEventListener('click', event => {
        const id = button.getAttribute('data-id');
        const ten = button.getAttribute('data-ten');
        const khoa = button.getAttribute('data-khoa');
        document.getElementById('xoaNganhId').value = id;
        document.getElementById('tenNganhXoa').textContent = ten;
        document.getElementById('tenKhoaXoa').textContent = khoa;
    });
});
</script>

<?php include '../include/layouts/footer.php'; ?>
