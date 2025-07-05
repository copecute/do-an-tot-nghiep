<?php
require_once '../include/config.php';
$page_title = "Quản lý ngành";
// Kiểm tra quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['vai_tro'] !== 'admin') {
    $_SESSION['flash_message'] = 'Bạn không có quyền truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../index.php');
    exit;
}

// Xử lý thêm ngành mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $tenNganh = trim($_POST['tenNganh'] ?? '');
        $khoaId = $_POST['khoaId'] ?? '';
        
        if (empty($tenNganh) || empty($khoaId)) {
            $_SESSION['flash_message'] = 'Vui lòng nhập đầy đủ thông tin ngành';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Kiểm tra xem ngành đã tồn tại trong khoa chưa
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM nganh WHERE tenNganh = ? AND khoaId = ?');
                $stmt->execute([$tenNganh, $khoaId]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $_SESSION['flash_message'] = 'Ngành này đã tồn tại trong khoa';
                    $_SESSION['flash_type'] = 'danger';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO nganh (tenNganh, khoaId) VALUES (?, ?)');
                    $stmt->execute([$tenNganh, $khoaId]);
                    $_SESSION['flash_message'] = 'Thêm ngành thành công!';
                    $_SESSION['flash_type'] = 'success';
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
    }
    // Xử lý cập nhật ngành
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? '';
        $tenNganh = trim($_POST['tenNganh'] ?? '');
        $khoaId = $_POST['khoaId'] ?? '';
        
        if (empty($tenNganh) || empty($khoaId)) {
            $_SESSION['flash_message'] = 'Vui lòng nhập đầy đủ thông tin ngành';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Kiểm tra xem tên ngành mới có trùng với ngành khác trong cùng khoa không
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM nganh WHERE tenNganh = ? AND khoaId = ? AND id != ?');
                $stmt->execute([$tenNganh, $khoaId, $id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $_SESSION['flash_message'] = 'Ngành này đã tồn tại trong khoa';
                    $_SESSION['flash_type'] = 'danger';
                } else {
                    $stmt = $pdo->prepare('UPDATE nganh SET tenNganh = ?, khoaId = ? WHERE id = ?');
                    $stmt->execute([$tenNganh, $khoaId, $id]);
                    $_SESSION['flash_message'] = 'Cập nhật ngành thành công!';
                    $_SESSION['flash_type'] = 'success';
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
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
                $_SESSION['flash_message'] = 'Không thể xóa ngành này vì đã có môn học thuộc ngành!';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $stmt = $pdo->prepare('DELETE FROM nganh WHERE id = ?');
                $stmt->execute([$id]);
                $_SESSION['flash_message'] = 'Xóa ngành thành công!';
                $_SESSION['flash_type'] = 'success';
            }
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
    }
}

// Lấy danh sách khoa cho dropdown
try {
    $stmt = $pdo->query('SELECT * FROM khoa ORDER BY tenKhoa');
    $dsKhoa = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    $dsKhoa = [];
}

// Lấy danh sách ngành với tên khoa và phân trang
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) ? (int)$_GET['perPage'] : 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$where = [];
$params = [];

// Filter tìm kiếm tên ngành
if (!empty($_GET['q'])) {
    $where[] = 'n.tenNganh LIKE ?';
    $params[] = '%' . $_GET['q'] . '%';
}

// Filter theo khoa
if (!empty($_GET['khoa'])) {
    $where[] = 'n.khoaId = ?';
    $params[] = $_GET['khoa'];
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Xử lý sắp xếp
$orderBy = 'n.id DESC'; // Mặc định mới nhất
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'oldest':
            $orderBy = 'n.id ASC';
            break;
        case 'name_asc':
            $orderBy = 'n.tenNganh ASC';
            break;
        case 'name_desc':
            $orderBy = 'n.tenNganh DESC';
            break;
        default:
            $orderBy = 'n.id DESC';
    }
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM nganh n ' . $whereSql);
    $stmt->execute($params);
    $totalRows = $stmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);
    $offset = ($page - 1) * $perPage;
    
    $stmt = $pdo->prepare('
        SELECT n.*, k.tenKhoa,
            (SELECT COUNT(*) FROM monHoc WHERE nganhId = n.id) as soMonHoc
        FROM nganh n 
        JOIN khoa k ON n.khoaId = k.id 
        ' . $whereSql . ' 
        ORDER BY ' . $orderBy . '
        LIMIT :offset, :perpage
    ');
    
    foreach ($params as $i => $v) {
        $stmt->bindValue($i+1, $v);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perpage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $dsNganh = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    $dsNganh = [];
    $totalPages = 1;
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
        <div>
            <a href="/quan-ly-nganh/excel/nhap.php" class="btn btn-success me-2"><i class="fas fa-file-excel"></i> Nhập/Xuất Excel</a>
            <button type="button" class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#modalThemNganh">
                <i class="fas fa-plus"></i> Thêm ngành mới
            </button>
        </div>
    </div>

    <!-- filter form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Tìm Kiếm</label>
                    <input type="text" class="form-control" id="search" name="q" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" placeholder="Nhập tên ngành...">
                </div>
                <div class="col-md-3">
                    <label for="khoa" class="form-label">Khoa</label>
                    <select class="form-select" id="khoa" name="khoa">
                        <option value="">Tất Cả Khoa</option>
                        <?php foreach ($dsKhoa as $khoa): ?>
                            <option value="<?php echo $khoa['id']; ?>" <?php echo (isset($_GET['khoa']) && $_GET['khoa'] == $khoa['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($khoa['tenKhoa']); ?>
                            </option>
                        <?php endforeach; ?>
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

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="60">STT</th>
                            <th>Tên ngành</th>
                            <th>Khoa</th>
                            <th width="120">Số môn học</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsNganh)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Chưa có ngành nào</td>
                            </tr>
                        <?php else: ?>
                            <?php $stt = 1 + ($page-1)*$perPage; foreach ($dsNganh as $nganh): ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td><?php echo htmlspecialchars($nganh['tenNganh']); ?></td>
                                    <td><?php echo htmlspecialchars($nganh['tenKhoa']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $nganh['soMonHoc'] > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo $nganh['soMonHoc']; ?> môn học
                                        </span>
                                    </td>
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

<!-- Modal Thêm Ngành -->
<div class="modal fade" id="modalThemNganh" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm ngành mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="formThemNganh">
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
                    <button type="submit" class="btn btn-primary" id="btnThemNganh">Thêm</button>
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
            <form method="post" id="formSuaNganh">
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
                    <button type="submit" class="btn btn-primary" id="btnSuaNganh">Lưu thay đổi</button>
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
            <form method="post" id="formXoaNganh">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="xoaNganhId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger" id="btnXoaNganh">Xóa</button>
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

// Loading khi submit các form modal ngành
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
addLoadingOnSubmit('formThemNganh', 'btnThemNganh', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
addLoadingOnSubmit('formSuaNganh', 'btnSuaNganh', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
addLoadingOnSubmit('formXoaNganh', 'btnXoaNganh', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
</script>

<?php include '../include/layouts/footer.php'; ?>
