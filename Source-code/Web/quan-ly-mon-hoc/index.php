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

// Xử lý thêm môn học mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $tenMonHoc = trim($_POST['tenMonHoc'] ?? '');
        $nganhId = $_POST['nganhId'] ?? '';
        
        if (empty($tenMonHoc) || empty($nganhId)) {
            $error = 'Vui lòng nhập đầy đủ thông tin môn học';
        } else {
            try {
                // Kiểm tra xem môn học đã tồn tại trong ngành chưa
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM monHoc WHERE tenMonHoc = ? AND nganhId = ?');
                $stmt->execute([$tenMonHoc, $nganhId]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error = 'Môn học này đã tồn tại trong ngành';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO monHoc (tenMonHoc, nganhId) VALUES (?, ?)');
                    $stmt->execute([$tenMonHoc, $nganhId]);
                    
                    $success = 'Thêm môn học thành công!';
                }
            } catch (PDOException $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
    // Xử lý cập nhật môn học
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? '';
        $tenMonHoc = trim($_POST['tenMonHoc'] ?? '');
        $nganhId = $_POST['nganhId'] ?? '';
        
        if (empty($tenMonHoc) || empty($nganhId)) {
            $error = 'Vui lòng nhập đầy đủ thông tin môn học';
        } else {
            try {
                // Kiểm tra xem tên môn học mới có trùng với môn học khác trong cùng ngành không
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM monHoc WHERE tenMonHoc = ? AND nganhId = ? AND id != ?');
                $stmt->execute([$tenMonHoc, $nganhId, $id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error = 'Môn học này đã tồn tại trong ngành';
                } else {
                    $stmt = $pdo->prepare('UPDATE monHoc SET tenMonHoc = ?, nganhId = ? WHERE id = ?');
                    $stmt->execute([$tenMonHoc, $nganhId, $id]);
                    
                    $success = 'Cập nhật môn học thành công!';
                }
            } catch (PDOException $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
    // Xử lý xóa môn học
    elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? '';
        
        try {
            // Kiểm tra xem môn học có câu hỏi nào không
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM cauHoi WHERE monHocId = ?');
            $stmt->execute([$id]);
            $countCauHoi = $stmt->fetchColumn();
            
            // Kiểm tra xem môn học có kỳ thi nào không
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM kyThi WHERE monHocId = ?');
            $stmt->execute([$id]);
            $countKyThi = $stmt->fetchColumn();
            
            if ($countCauHoi > 0) {
                $error = 'Không thể xóa môn học này vì đã có câu hỏi thuộc môn học!';
            } elseif ($countKyThi > 0) {
                $error = 'Không thể xóa môn học này vì đã có kỳ thi thuộc môn học!';
            } else {
                // Xóa các thể loại câu hỏi của môn học trước
                $stmt = $pdo->prepare('DELETE FROM theLoaiCauHoi WHERE monHocId = ?');
                $stmt->execute([$id]);
                
                // Sau đó xóa môn học
                $stmt = $pdo->prepare('DELETE FROM monHoc WHERE id = ?');
                $stmt->execute([$id]);
                
                $success = 'Xóa môn học thành công!';
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

// Lấy danh sách ngành cho dropdown
try {
    $stmt = $pdo->query('SELECT n.*, k.tenKhoa FROM nganh n JOIN khoa k ON n.khoaId = k.id ORDER BY k.tenKhoa, n.tenNganh');
    $dsNganh = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
    $dsNganh = [];
}

// Lấy danh sách môn học với tên ngành và khoa
try {
    $stmt = $pdo->query('
        SELECT m.*, n.tenNganh, k.tenKhoa 
        FROM monHoc m 
        JOIN nganh n ON m.nganhId = n.id 
        JOIN khoa k ON n.khoaId = k.id 
        ORDER BY k.tenKhoa, n.tenNganh, m.tenMonHoc
    ');
    $dsMonHoc = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
    $dsMonHoc = [];
}

include '../include/layouts/header.php';
?>
<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Quản lý môn học</li>
    </ol>
</nav>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý môn học</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalThemMonHoc">
            <i class="fas fa-plus"></i> Thêm môn học mới
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
                            <th>Tên môn học</th>
                            <th>Ngành</th>
                            <th>Khoa</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsMonHoc)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Chưa có môn học nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dsMonHoc as $monHoc): ?>
                                <tr>
                                    <td><?php echo $monHoc['id']; ?></td>
                                    <td><?php echo htmlspecialchars($monHoc['tenMonHoc']); ?></td>
                                    <td><?php echo htmlspecialchars($monHoc['tenNganh']); ?></td>
                                    <td><?php echo htmlspecialchars($monHoc['tenKhoa']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalSuaMonHoc" 
                                                data-id="<?php echo $monHoc['id']; ?>"
                                                data-ten="<?php echo htmlspecialchars($monHoc['tenMonHoc']); ?>"
                                                data-nganh="<?php echo $monHoc['nganhId']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalXoaMonHoc"
                                                data-id="<?php echo $monHoc['id']; ?>"
                                                data-ten="<?php echo htmlspecialchars($monHoc['tenMonHoc']); ?>"
                                                data-nganh="<?php echo htmlspecialchars($monHoc['tenNganh']); ?>"
                                                data-khoa="<?php echo htmlspecialchars($monHoc['tenKhoa']); ?>">
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

<!-- Modal Thêm Môn Học -->
<div class="modal fade" id="modalThemMonHoc" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm môn học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="nganhId" class="form-label">Ngành <span class="text-danger">*</span></label>
                        <select class="form-select" id="nganhId" name="nganhId" required>
                            <option value="">Chọn ngành</option>
                            <?php foreach ($dsNganh as $nganh): ?>
                                <option value="<?php echo $nganh['id']; ?>">
                                    <?php echo htmlspecialchars($nganh['tenNganh'] . ' - ' . $nganh['tenKhoa']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tenMonHoc" class="form-label">Tên môn học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tenMonHoc" name="tenMonHoc" required>
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

<!-- Modal Sửa Môn Học -->
<div class="modal fade" id="modalSuaMonHoc" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa thông tin môn học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="suaMonHocId">
                    <div class="mb-3">
                        <label for="suaNganhId" class="form-label">Ngành <span class="text-danger">*</span></label>
                        <select class="form-select" id="suaNganhId" name="nganhId" required>
                            <option value="">Chọn ngành</option>
                            <?php foreach ($dsNganh as $nganh): ?>
                                <option value="<?php echo $nganh['id']; ?>">
                                    <?php echo htmlspecialchars($nganh['tenNganh'] . ' - ' . $nganh['tenKhoa']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="suaTenMonHoc" class="form-label">Tên môn học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="suaTenMonHoc" name="tenMonHoc" required>
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

<!-- Modal Xóa Môn Học -->
<div class="modal fade" id="modalXoaMonHoc" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa môn học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa môn học "<span id="tenMonHocXoa"></span>" của ngành "<span id="tenNganhXoa"></span>" thuộc khoa "<span id="tenKhoaXoa"></span>"?</p>
                <p class="text-danger mb-0">Lưu ý: Chỉ có thể xóa môn học chưa có câu hỏi và kỳ thi nào.</p>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="xoaMonHocId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Xử lý modal sửa môn học
document.querySelectorAll('[data-bs-target="#modalSuaMonHoc"]').forEach(button => {
    button.addEventListener('click', event => {
        const id = button.getAttribute('data-id');
        const ten = button.getAttribute('data-ten');
        const nganh = button.getAttribute('data-nganh');
        document.getElementById('suaMonHocId').value = id;
        document.getElementById('suaTenMonHoc').value = ten;
        document.getElementById('suaNganhId').value = nganh;
    });
});

// Xử lý modal xóa môn học
document.querySelectorAll('[data-bs-target="#modalXoaMonHoc"]').forEach(button => {
    button.addEventListener('click', event => {
        const id = button.getAttribute('data-id');
        const ten = button.getAttribute('data-ten');
        const nganh = button.getAttribute('data-nganh');
        const khoa = button.getAttribute('data-khoa');
        document.getElementById('xoaMonHocId').value = id;
        document.getElementById('tenMonHocXoa').textContent = ten;
        document.getElementById('tenNganhXoa').textContent = nganh;
        document.getElementById('tenKhoaXoa').textContent = khoa;
    });
});
</script>

<?php include '../include/layouts/footer.php'; ?>
