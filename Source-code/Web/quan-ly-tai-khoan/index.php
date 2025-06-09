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

// Xử lý thêm tài khoản mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $tenDangNhap = trim($_POST['tenDangNhap'] ?? '');
        $matKhau = $_POST['matKhau'] ?? '';
        $hoTen = trim($_POST['hoTen'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $vaiTro = $_POST['vaiTro'] ?? '';
        
        if (empty($tenDangNhap) || empty($matKhau) || empty($hoTen) || empty($vaiTro)) {
            $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
        } elseif (strlen($matKhau) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ';
        } else {
            try {
                // Kiểm tra xem tên đăng nhập đã tồn tại chưa
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM taiKhoan WHERE tenDangNhap = ?');
                $stmt->execute([$tenDangNhap]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error = 'Tên đăng nhập đã tồn tại';
                } else {
                    // Hash mật khẩu
                    $hashedPassword = password_hash($matKhau, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare('INSERT INTO taiKhoan (tenDangNhap, matKhau, vaiTro, hoTen, email) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$tenDangNhap, $hashedPassword, $vaiTro, $hoTen, $email]);
                    
                    $success = 'Thêm tài khoản thành công!';
                }
            } catch (PDOException $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
    // Xử lý cập nhật tài khoản
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? '';
        $hoTen = trim($_POST['hoTen'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $vaiTro = $_POST['vaiTro'] ?? '';
        $matKhauMoi = $_POST['matKhau'] ?? '';
        $trangThai = isset($_POST['trangThai']) ? 1 : 0;
        
        if (empty($hoTen) || empty($vaiTro)) {
            $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
        } elseif (!empty($matKhauMoi) && strlen($matKhauMoi) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ';
        } else {
            try {
                if (!empty($matKhauMoi)) {
                    // Cập nhật cả mật khẩu
                    $hashedPassword = password_hash($matKhauMoi, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE taiKhoan SET hoTen = ?, email = ?, vaiTro = ?, matKhau = ?, trangThai = ? WHERE id = ?');
                    $stmt->execute([$hoTen, $email, $vaiTro, $hashedPassword, $trangThai, $id]);
                } else {
                    // Không cập nhật mật khẩu
                    $stmt = $pdo->prepare('UPDATE taiKhoan SET hoTen = ?, email = ?, vaiTro = ?, trangThai = ? WHERE id = ?');
                    $stmt->execute([$hoTen, $email, $vaiTro, $trangThai, $id]);
                }
                
                $success = 'Cập nhật tài khoản thành công!';
            } catch (PDOException $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
    // Xử lý xóa tài khoản
    elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? '';
        
        // Không cho phép xóa tài khoản đang đăng nhập
        if ($id == $_SESSION['user_id']) {
            $error = 'Không thể xóa tài khoản đang đăng nhập!';
        } else {
            try {
                // Kiểm tra xem tài khoản có liên quan đến kỳ thi không
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM kyThi WHERE nguoiTaoId = ?');
                $stmt->execute([$id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error = 'Không thể xóa tài khoản này vì đã có kỳ thi được tạo bởi tài khoản này!';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM taiKhoan WHERE id = ?');
                    $stmt->execute([$id]);
                    
                    $success = 'Xóa tài khoản thành công!';
                }
            } catch (PDOException $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
}

// Lấy danh sách tài khoản
try {
    $stmt = $pdo->query('SELECT * FROM taiKhoan ORDER BY vaiTro, hoTen');
    $dsTaiKhoan = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
    $dsTaiKhoan = [];
}

include '../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Quản lý tài khoản</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý tài khoản</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalThemTaiKhoan">
            <i class="fas fa-plus"></i> Thêm tài khoản mới
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
                            <th>Tên đăng nhập</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsTaiKhoan)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Chưa có tài khoản nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dsTaiKhoan as $taiKhoan): ?>
                                <tr>
                                    <td><?php echo $taiKhoan['id']; ?></td>
                                    <td><?php echo htmlspecialchars($taiKhoan['tenDangNhap']); ?></td>
                                    <td><?php echo htmlspecialchars($taiKhoan['hoTen']); ?></td>
                                    <td><?php echo htmlspecialchars($taiKhoan['email'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge <?php echo $taiKhoan['vaiTro'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                            <?php echo $taiKhoan['vaiTro'] === 'admin' ? 'Quản trị viên' : 'Giáo viên'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $taiKhoan['trangThai'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $taiKhoan['trangThai'] ? 'Hoạt động' : 'Đã khóa'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalSuaTaiKhoan"
                                                data-id="<?php echo $taiKhoan['id']; ?>"
                                                data-ten-dang-nhap="<?php echo htmlspecialchars($taiKhoan['tenDangNhap']); ?>"
                                                data-ho-ten="<?php echo htmlspecialchars($taiKhoan['hoTen']); ?>"
                                                data-email="<?php echo htmlspecialchars($taiKhoan['email'] ?? ''); ?>"
                                                data-vai-tro="<?php echo $taiKhoan['vaiTro']; ?>"
                                                data-trang-thai="<?php echo $taiKhoan['trangThai']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($taiKhoan['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalXoaTaiKhoan"
                                                    data-id="<?php echo $taiKhoan['id']; ?>"
                                                    data-ten-dang-nhap="<?php echo htmlspecialchars($taiKhoan['tenDangNhap']); ?>"
                                                    data-ho-ten="<?php echo htmlspecialchars($taiKhoan['hoTen']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Modal Thêm Tài Khoản -->
<div class="modal fade" id="modalThemTaiKhoan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm tài khoản mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="tenDangNhap" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tenDangNhap" name="tenDangNhap" required>
                    </div>
                    <div class="mb-3">
                        <label for="matKhau" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="matKhau" name="matKhau" required>
                        <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự</div>
                    </div>
                    <div class="mb-3">
                        <label for="hoTen" class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="hoTen" name="hoTen" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="vaiTro" class="form-label">Vai trò <span class="text-danger">*</span></label>
                        <select class="form-select" id="vaiTro" name="vaiTro" required>
                            <option value="giaovien">Giáo viên</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
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

<!-- Modal Sửa Tài Khoản -->
<div class="modal fade" id="modalSuaTaiKhoan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa thông tin tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="suaTaiKhoanId">
                    <div class="mb-3">
                        <label class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="suaTenDangNhap" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="suaMatKhau" class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control" id="suaMatKhau" name="matKhau">
                        <div class="form-text">Để trống nếu không muốn thay đổi mật khẩu</div>
                    </div>
                    <div class="mb-3">
                        <label for="suaHoTen" class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="suaHoTen" name="hoTen" required>
                    </div>
                    <div class="mb-3">
                        <label for="suaEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="suaEmail" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="suaVaiTro" class="form-label">Vai trò <span class="text-danger">*</span></label>
                        <select class="form-select" id="suaVaiTro" name="vaiTro" required>
                            <option value="giaovien">Giáo viên</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="suaTrangThai" name="trangThai">
                            <label class="form-check-label" for="suaTrangThai">Tài khoản đang hoạt động</label>
                        </div>
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

<!-- Modal Xóa Tài Khoản -->
<div class="modal fade" id="modalXoaTaiKhoan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa tài khoản của "<span id="tenTaiKhoanXoa"></span>"?</p>
                <p class="text-danger mb-0">Lưu ý: Chỉ có thể xóa tài khoản chưa tạo kỳ thi nào.</p>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="xoaTaiKhoanId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Xử lý modal sửa tài khoản
document.querySelectorAll('[data-bs-target="#modalSuaTaiKhoan"]').forEach(button => {
    button.addEventListener('click', event => {
        const id = button.getAttribute('data-id');
        const tenDangNhap = button.getAttribute('data-ten-dang-nhap');
        const hoTen = button.getAttribute('data-ho-ten');
        const email = button.getAttribute('data-email');
        const vaiTro = button.getAttribute('data-vai-tro');
        const trangThai = button.getAttribute('data-trang-thai') === '1';
        
        document.getElementById('suaTaiKhoanId').value = id;
        document.getElementById('suaTenDangNhap').value = tenDangNhap;
        document.getElementById('suaHoTen').value = hoTen;
        document.getElementById('suaEmail').value = email;
        document.getElementById('suaVaiTro').value = vaiTro;
        document.getElementById('suaTrangThai').checked = trangThai;
    });
});

// Xử lý modal xóa tài khoản
document.querySelectorAll('[data-bs-target="#modalXoaTaiKhoan"]').forEach(button => {
    button.addEventListener('click', event => {
        const id = button.getAttribute('data-id');
        const hoTen = button.getAttribute('data-ho-ten');
        document.getElementById('xoaTaiKhoanId').value = id;
        document.getElementById('tenTaiKhoanXoa').textContent = hoTen;
    });
});
</script>

<?php include '../include/layouts/footer.php'; ?>
