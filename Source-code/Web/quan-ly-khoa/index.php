<?php
require_once '../include/config.php';
$page_title = "Quản lý khoa";
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
                    
                    $_SESSION['flash_message'] = 'Thêm khoa thành công!';
                    $_SESSION['flash_type'] = 'success';
                    header('Location: index.php');
                    exit;
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
                    
                    $_SESSION['flash_message'] = 'Cập nhật khoa thành công!';
                    $_SESSION['flash_type'] = 'success';
                    header('Location: index.php');
                    exit;
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
                
                $_SESSION['flash_message'] = 'Xóa khoa thành công!';
                $_SESSION['flash_type'] = 'success';
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách khoa với phân trang và filter
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) ? (int)$_GET['perPage'] : 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$where = [];
$params = [];

// Filter tìm kiếm tên khoa
if (!empty($_GET['q'])) {
    $where[] = 'k.tenKhoa LIKE :q';
    $params[':q'] = '%' . $_GET['q'] . '%';
}

// Filter trạng thái (có/chưa có ngành)
if (!empty($_GET['status'])) {
    if ($_GET['status'] === 'active') {
        $where[] = 'EXISTS (SELECT 1 FROM nganh WHERE khoaId = k.id)';
    } elseif ($_GET['status'] === 'empty') {
        $where[] = 'NOT EXISTS (SELECT 1 FROM nganh WHERE khoaId = k.id)';
    }
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Xử lý sắp xếp
$orderBy = 'k.id DESC'; // Mặc định mới nhất
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'oldest':
            $orderBy = 'k.id ASC';
            break;
        case 'name_asc':
            $orderBy = 'k.tenKhoa ASC';
            break;
        case 'name_desc':
            $orderBy = 'k.tenKhoa DESC';
            break;
        default:
            $orderBy = 'k.id DESC';
    }
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM khoa k ' . $whereSql);
    $stmt->execute($params);
    $totalRows = $stmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);
    $offset = ($page - 1) * $perPage;
    
    $stmt = $pdo->prepare('SELECT k.*, 
        (SELECT COUNT(*) FROM nganh WHERE khoaId = k.id) as soNganh
        FROM khoa k ' . $whereSql . ' 
        ORDER BY ' . $orderBy . ' 
        LIMIT :offset, :perpage');
    
    foreach ($params as $key => $v) {
        $stmt->bindValue($key, $v);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perpage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $dsKhoa = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    $dsKhoa = [];
    $totalPages = 1;
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
        <div>
            <a href="/quan-ly-khoa/excel/nhap.php" class="btn btn-success me-2"><i class="fas fa-file-excel"></i> Nhập/Xuất Excel</a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalThemKhoa">
                <i class="fas fa-plus"></i> Thêm khoa mới
            </button>
        </div>
    </div>
    <!-- filter form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Tìm Kiếm</label>
                    <input type="text" class="form-control" id="search" name="q" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" placeholder="Nhập tên khoa...">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Trạng Thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tất Cả</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Có Ngành</option>
                        <option value="empty" <?php echo (isset($_GET['status']) && $_GET['status'] == 'empty') ? 'selected' : ''; ?>>Chưa Có Ngành</option>
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
                <div class="col-md-3">
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

    <?php if ($error): ?>
        <?php $_SESSION['flash_message'] = $error; $_SESSION['flash_type'] = 'danger'; ?>
    <?php endif; ?>

    <?php if ($success): ?>
        <?php $_SESSION['flash_message'] = $success; $_SESSION['flash_type'] = 'success'; ?>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="60">STT</th>
                            <th>Tên khoa</th>
                            <th width="120">Số ngành</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsKhoa)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Chưa có khoa nào</td>
                            </tr>
                        <?php else: ?>
                            <?php $stt = 1 + ($page-1)*$perPage; foreach ($dsKhoa as $khoa): ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td><?php echo htmlspecialchars($khoa['tenKhoa']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $khoa['soNganh'] > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo $khoa['soNganh']; ?> ngành
                                        </span>
                                    </td>
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
</div>

<!-- Modal Thêm Khoa -->
<div class="modal fade" id="modalThemKhoa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm khoa mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="formThemKhoa">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="tenKhoa" class="form-label">Tên khoa</label>
                        <input type="text" class="form-control" id="tenKhoa" name="tenKhoa" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary" id="btnThemKhoa">Thêm</button>
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
            <form method="post" id="formSuaKhoa">
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
                    <button type="submit" class="btn btn-primary" id="btnSuaKhoa">Lưu thay đổi</button>
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
            <form method="post" id="formXoaKhoa">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="xoaKhoaId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger" id="btnXoaKhoa">Xóa</button>
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

// Loading khi submit các form modal khoa
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
addLoadingOnSubmit('formThemKhoa', 'btnThemKhoa', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
addLoadingOnSubmit('formSuaKhoa', 'btnSuaKhoa', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
addLoadingOnSubmit('formXoaKhoa', 'btnXoaKhoa', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
</script>

<?php include '../include/layouts/footer.php'; ?>
