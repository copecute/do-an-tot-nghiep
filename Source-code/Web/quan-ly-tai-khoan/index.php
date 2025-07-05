<?php
require_once '../include/config.php';
$page_title = "Quản lý tài khoản";
// Kiểm tra quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['vai_tro'] !== 'admin') {
    $_SESSION['flash_message'] = 'Bạn không có quyền truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../index.php');
    exit;
}

// Xử lý thêm tài khoản mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $tenDangNhap = trim($_POST['tenDangNhap'] ?? '');
        $matKhau = $_POST['matKhau'] ?? '';
        $hoTen = trim($_POST['hoTen'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $vaiTro = $_POST['vaiTro'] ?? '';
        
        if (empty($tenDangNhap) || empty($matKhau) || empty($hoTen) || empty($vaiTro)) {
            $_SESSION['flash_message'] = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
            $_SESSION['flash_type'] = 'danger';
        } elseif (strlen($matKhau) < 6) {
            $_SESSION['flash_message'] = 'Mật khẩu phải có ít nhất 6 ký tự';
            $_SESSION['flash_type'] = 'danger';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_message'] = 'Email không hợp lệ';
            $_SESSION['flash_type'] = 'danger';
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
                    
                    $_SESSION['flash_message'] = 'Thêm tài khoản thành công!';
                    $_SESSION['flash_type'] = 'success';
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
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
                
                $_SESSION['flash_message'] = 'Cập nhật tài khoản thành công!';
                $_SESSION['flash_type'] = 'success';
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
    }
    // Xử lý xóa tài khoản
    elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? '';
        
        // Không cho phép xóa tài khoản đang đăng nhập
        if ($id == $_SESSION['user_id']) {
            $_SESSION['flash_message'] = 'Không thể xóa tài khoản đang đăng nhập!';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Kiểm tra xem tài khoản có liên quan đến kỳ thi không
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM kyThi WHERE nguoiTaoId = ?');
                $stmt->execute([$id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $_SESSION['flash_message'] = 'Không thể xóa tài khoản này vì đã có kỳ thi được tạo bởi tài khoản này!';
                    $_SESSION['flash_type'] = 'danger';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM taiKhoan WHERE id = ?');
                    $stmt->execute([$id]);
                    
                    $_SESSION['flash_message'] = 'Xóa tài khoản thành công!';
                    $_SESSION['flash_type'] = 'success';
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
    }
}

// Lấy danh sách tài khoản với phân trang và filter
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) ? (int)$_GET['perPage'] : 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$where = [];
$params = [];

// Filter tìm kiếm
if (!empty($_GET['q'])) {
    $where[] = '(t.tenDangNhap LIKE ? OR t.hoTen LIKE ? OR t.email LIKE ?)';
    $q = '%' . $_GET['q'] . '%';
    $params[] = $q; $params[] = $q; $params[] = $q;
}

// Filter vai trò
if (!empty($_GET['role'])) {
    $where[] = 't.vaiTro = ?';
    $params[] = $_GET['role'];
}

// Filter trạng thái
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = 't.trangThai = ?';
    $params[] = $_GET['status'];
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Xử lý sắp xếp
$orderBy = 't.id DESC'; // Mặc định mới nhất
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'oldest':
            $orderBy = 't.id ASC';
            break;
        case 'name_asc':
            $orderBy = 't.hoTen ASC';
            break;
        case 'name_desc':
            $orderBy = 't.hoTen DESC';
            break;
        default:
            $orderBy = 't.id DESC';
    }
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM taiKhoan t ' . $whereSql);
    $stmt->execute($params);
    $totalRows = $stmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);
    $offset = ($page - 1) * $perPage;
    
    $stmt = $pdo->prepare('SELECT t.*, 
        (SELECT COUNT(*) FROM kyThi WHERE nguoiTaoId = t.id) as soKyThi
        FROM taiKhoan t ' . $whereSql . ' 
        ORDER BY ' . $orderBy . ' 
        LIMIT :offset, :perpage');
    
    foreach ($params as $i => $v) {
        $stmt->bindValue($i+1, $v);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perpage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $dsTaiKhoan = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    $dsTaiKhoan = [];
    $totalPages = 1;
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
        <div>
            <a href="/quan-ly-tai-khoan/excel/nhap.php" class="btn btn-success me-2"><i class="fas fa-file-excel"></i> Nhập/Xuất Excel</a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalThemTaiKhoan">
                <i class="fas fa-plus"></i> Thêm tài khoản mới
            </button>
        </div>
    </div>
    <!-- filter form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Tìm Kiếm</label>
                    <input type="text" class="form-control" id="search" name="q" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" placeholder="Tên đăng nhập, họ tên, email...">
                </div>
                <div class="col-md-2">
                    <label for="role" class="form-label">Vai Trò</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">Tất Cả</option>
                        <option value="admin" <?php echo (isset($_GET['role']) && $_GET['role'] == 'admin') ? 'selected' : ''; ?>>Quản Trị Viên</option>
                        <option value="giaovien" <?php echo (isset($_GET['role']) && $_GET['role'] == 'giaovien') ? 'selected' : ''; ?>>Giáo Viên</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Trạng Thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tất Cả</option>
                        <option value="1" <?php echo (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : ''; ?>>Hoạt Động</option>
                        <option value="0" <?php echo (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : ''; ?>>Đã Khóa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sort" class="form-label">Sắp Xếp</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="newest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Mới Nhất</option>
                        <option value="oldest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'oldest') ? 'selected' : ''; ?>>Cũ Nhất</option>
                        <option value="name_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'name_asc') ? 'selected' : ''; ?>>Tên A-Z</option>
                        <option value="name_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'name_desc') ? 'selected' : ''; ?>>Tên Z-A</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="perPage" class="form-label">Hiển Thị</label>
                    <select class="form-select" id="perPage" name="perPage">
                        <option value="5" <?php echo (isset($_GET['perPage']) && $_GET['perPage'] == '5') ? 'selected' : ''; ?>>5 dòng</option>
                        <option value="10" <?php echo (isset($_GET['perPage']) && $_GET['perPage'] == '10') ? 'selected' : ''; ?>>10 dòng</option>
                        <option value="20" <?php echo (isset($_GET['perPage']) && $_GET['perPage'] == '20') ? 'selected' : ''; ?>>20 dòng</option>
                        <option value="50" <?php echo (isset($_GET['perPage']) && $_GET['perPage'] == '50') ? 'selected' : ''; ?>>50 dòng</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="60">STT</th>
                            <th>Tên đăng nhập</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th width="100">Kỳ thi</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsTaiKhoan)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Chưa có tài khoản nào</td>
                            </tr>
                        <?php else: ?>
                            <?php $stt = 1 + ($page-1)*$perPage; foreach ($dsTaiKhoan as $taiKhoan): ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
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
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            <?php echo $taiKhoan['soKyThi']; ?> kỳ thi
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

<?php if ($totalPages > 1): ?>
<nav aria-label="Page navigation" class="mt-3">
    <ul class="pagination justify-content-center">
        <?php
        // Tạo query string cho phân trang
        $queryParams = $_GET;
        unset($queryParams['page']);
        $queryString = http_build_query($queryParams);
        $queryString = $queryString ? '&' . $queryString : '';
        ?>
        <li class="page-item<?php if ($page <= 1) echo ' disabled'; ?>">
            <a class="page-link" href="?page=<?php echo $page-1 . $queryString; ?>" tabindex="-1">&laquo;</a>
        </li>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                <a class="page-link" href="?page=<?php echo $i . $queryString; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item<?php if ($page >= $totalPages) echo ' disabled'; ?>">
            <a class="page-link" href="?page=<?php echo $page+1 . $queryString; ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

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
            <form method="post" id="formThemTaiKhoan">
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
                    <button type="submit" class="btn btn-primary" id="btnThemTaiKhoan">Thêm</button>
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
            <form method="post" id="formSuaTaiKhoan">
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
                    <button type="submit" class="btn btn-primary" id="btnSuaTaiKhoan">Lưu thay đổi</button>
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
            <form method="post" id="formXoaTaiKhoan">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="xoaTaiKhoanId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger" id="btnXoaTaiKhoan">Xóa</button>
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

// Loading khi submit các form modal tài khoản
function addLoadingOnSubmit(formId, btnId, loadingHtml) {
    const form = document.getElementById(formId);
    const btn = document.getElementById(btnId);
    if (form && btn) {
        form.addEventListener('submit', function(e) {
            btn.disabled = true;
            btn.innerHTML = loadingHtml;
        });
    }
}
addLoadingOnSubmit('formThemTaiKhoan', 'btnThemTaiKhoan', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
addLoadingOnSubmit('formSuaTaiKhoan', 'btnSuaTaiKhoan', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
addLoadingOnSubmit('formXoaTaiKhoan', 'btnXoaTaiKhoan', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
</script>

<?php include '../include/layouts/footer.php'; ?>
