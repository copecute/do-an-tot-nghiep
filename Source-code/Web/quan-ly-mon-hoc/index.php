<?php
require_once '../include/config.php';
$page_title = "Quản lý môn học";
// Kiểm tra quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['vai_tro'] !== 'admin') {
    $_SESSION['flash_message'] = 'Bạn không có quyền truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../index.php');
    exit;
}

// Xử lý thêm môn học mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $tenMonHoc = trim($_POST['tenMonHoc'] ?? '');
        $nganhId = $_POST['nganhId'] ?? '';
        
        if (empty($tenMonHoc) || empty($nganhId)) {
            $_SESSION['flash_message'] = 'Vui lòng nhập đầy đủ thông tin môn học';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Kiểm tra xem môn học đã tồn tại trong ngành chưa
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM monHoc WHERE tenMonHoc = ? AND nganhId = ?');
                $stmt->execute([$tenMonHoc, $nganhId]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $_SESSION['flash_message'] = 'Môn học này đã tồn tại trong ngành';
                    $_SESSION['flash_type'] = 'danger';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO monHoc (tenMonHoc, nganhId) VALUES (?, ?)');
                    $stmt->execute([$tenMonHoc, $nganhId]);
                    
                    $_SESSION['flash_message'] = 'Thêm môn học thành công!';
                    $_SESSION['flash_type'] = 'success';
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
    }
    // Xử lý cập nhật môn học
    elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? '';
        $tenMonHoc = trim($_POST['tenMonHoc'] ?? '');
        $nganhId = $_POST['nganhId'] ?? '';
        
        if (empty($tenMonHoc) || empty($nganhId)) {
            $_SESSION['flash_message'] = 'Vui lòng nhập đầy đủ thông tin môn học';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                // Kiểm tra xem tên môn học mới có trùng với môn học khác trong cùng ngành không
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM monHoc WHERE tenMonHoc = ? AND nganhId = ? AND id != ?');
                $stmt->execute([$tenMonHoc, $nganhId, $id]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $_SESSION['flash_message'] = 'Môn học này đã tồn tại trong ngành';
                    $_SESSION['flash_type'] = 'danger';
                } else {
                    $stmt = $pdo->prepare('UPDATE monHoc SET tenMonHoc = ?, nganhId = ? WHERE id = ?');
                    $stmt->execute([$tenMonHoc, $nganhId, $id]);
                    
                    $_SESSION['flash_message'] = 'Cập nhật môn học thành công!';
                    $_SESSION['flash_type'] = 'success';
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
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
                $_SESSION['flash_message'] = 'Không thể xóa môn học này vì đã có câu hỏi thuộc môn học!';
                $_SESSION['flash_type'] = 'danger';
            } elseif ($countKyThi > 0) {
                $_SESSION['flash_message'] = 'Không thể xóa môn học này vì đã có kỳ thi thuộc môn học!';
                $_SESSION['flash_type'] = 'danger';
            } else {
                // Xóa các thể loại câu hỏi của môn học trước
                $stmt = $pdo->prepare('DELETE FROM theLoaiCauHoi WHERE monHocId = ?');
                $stmt->execute([$id]);
                
                // Sau đó xóa môn học
                $stmt = $pdo->prepare('DELETE FROM monHoc WHERE id = ?');
                $stmt->execute([$id]);
                
                $_SESSION['flash_message'] = 'Xóa môn học thành công!';
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

// Lấy danh sách ngành cho dropdown
try {
    $stmt = $pdo->query('SELECT n.*, k.tenKhoa FROM nganh n JOIN khoa k ON n.khoaId = k.id ORDER BY k.tenKhoa, n.tenNganh');
    $dsNganh = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    $dsNganh = [];
}

// Xử lý filter tìm kiếm và lọc
$filterKhoa = isset($_GET['khoa']) ? $_GET['khoa'] : '';
$filterNganh = isset($_GET['nganh']) ? $_GET['nganh'] : '';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Lấy danh sách môn học với tên ngành và khoa, phân trang, sắp xếp mới -> cũ, có filter
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) ? (int)$_GET['perPage'] : 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$where = [];
$params = [];

// Filter theo khoa
if ($filterKhoa) {
    $where[] = 'k.id = ?';
    $params[] = $filterKhoa;
}

// Filter theo ngành
if ($filterNganh) {
    $where[] = 'n.id = ?';
    $params[] = $filterNganh;
}

// Filter tìm kiếm tên môn học
if ($search) {
    $where[] = 'm.tenMonHoc LIKE ?';
    $params[] = "%$search%";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Xử lý sắp xếp
$orderBy = 'm.id DESC'; // Mặc định mới nhất
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'oldest':
            $orderBy = 'm.id ASC';
            break;
        case 'name_asc':
            $orderBy = 'm.tenMonHoc ASC';
            break;
        case 'name_desc':
            $orderBy = 'm.tenMonHoc DESC';
            break;
        default:
            $orderBy = 'm.id DESC';
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM monHoc m
        JOIN nganh n ON m.nganhId = n.id
        JOIN khoa k ON n.khoaId = k.id
        $whereSql
    ");
    $stmt->execute($params);
    $totalRows = $stmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);
    $offset = ($page - 1) * $perPage;
    
    $stmt = $pdo->prepare("
        SELECT m.*, n.tenNganh, k.tenKhoa,
            (SELECT COUNT(*) FROM cauHoi WHERE monHocId = m.id) as soCauHoi,
            (SELECT COUNT(*) FROM kyThi WHERE monHocId = m.id) as soKyThi
        FROM monHoc m
        JOIN nganh n ON m.nganhId = n.id
        JOIN khoa k ON n.khoaId = k.id
        $whereSql
        ORDER BY $orderBy
        LIMIT :offset, :perpage
    ");
    
    foreach ($params as $i => $v) {
        $stmt->bindValue($i+1, $v);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perpage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $dsMonHoc = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['flash_message'] = 'Lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    $dsMonHoc = [];
    $totalPages = 1;
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
        <div>
            <a href="/quan-ly-mon-hoc/excel/nhap.php" class="btn btn-success me-2"><i class="fas fa-file-excel"></i> Nhập/Xuất Excel</a>

            <button type="button" class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#modalThemMonHoc">
                <i class="fas fa-plus"></i> Thêm môn học mới
            </button>
        </div>
    </div>

    <!-- filter form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Tìm Kiếm</label>
                    <input type="text" class="form-control" id="search" name="q" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" placeholder="Nhập tên môn học...">
                </div>
                <div class="col-md-2">
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
                    <label for="nganh" class="form-label">Ngành</label>
                    <select class="form-select" id="nganh" name="nganh">
                        <option value="">Tất Cả Ngành</option>
                        <?php foreach ($dsNganh as $nganh): ?>
                            <option value="<?php echo $nganh['id']; ?>" <?php echo (isset($_GET['nganh']) && $_GET['nganh'] == $nganh['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nganh['tenNganh']); ?>
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
                            <th>Tên môn học</th>
                            <th>Ngành</th>
                            <th>Khoa</th>
                            <th width="120">Câu hỏi</th>
                            <th width="100">Kỳ thi</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsMonHoc)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Chưa có môn học nào</td>
                            </tr>
                        <?php else: ?>
                            <?php $stt = 1 + ($page-1)*$perPage; foreach ($dsMonHoc as $monHoc): ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td><?php echo htmlspecialchars($monHoc['tenMonHoc']); ?></td>
                                    <td><?php echo htmlspecialchars($monHoc['tenNganh']); ?></td>
                                    <td><?php echo htmlspecialchars($monHoc['tenKhoa']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            <?php echo $monHoc['soCauHoi']; ?> câu hỏi
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning">
                                            <?php echo $monHoc['soKyThi']; ?> kỳ thi
                                        </span>
                                    </td>
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

<!-- Modal Thêm Môn Học -->
<div class="modal fade" id="modalThemMonHoc" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm môn học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="formThemMonHoc">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="khoaId" class="form-label">Khoa <span class="text-danger">*</span></label>
                        <select class="form-select" id="khoaId" name="khoaId" required>
                            <option value="">Chọn khoa</option>
                            <?php foreach ($dsKhoa as $khoa): ?>
                                <option value="<?php echo $khoa['id']; ?>"><?php echo htmlspecialchars($khoa['tenKhoa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="nganhId" class="form-label">Ngành <span class="text-danger">*</span></label>
                        <select class="form-select" id="nganhId" name="nganhId" required>
                            <option value="">Chọn ngành</option>
                            <?php foreach ($dsNganh as $nganh): ?>
                                <option value="<?php echo $nganh['id']; ?>" data-khoa="<?php echo $nganh['khoaId']; ?>">
                                    <?php echo htmlspecialchars($nganh['tenNganh']); ?>
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
                    <button type="submit" class="btn btn-primary" id="btnThemMonHoc">Thêm</button>
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
            <form method="post" id="formSuaMonHoc">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="suaMonHocId">
                    <div class="mb-3">
                        <label for="suaKhoaId" class="form-label">Khoa <span class="text-danger">*</span></label>
                        <select class="form-select" id="suaKhoaId" name="khoaId" required>
                            <option value="">Chọn khoa</option>
                            <?php foreach ($dsKhoa as $khoa): ?>
                                <option value="<?php echo $khoa['id']; ?>"><?php echo htmlspecialchars($khoa['tenKhoa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="suaNganhId" class="form-label">Ngành <span class="text-danger">*</span></label>
                        <select class="form-select" id="suaNganhId" name="nganhId" required>
                            <option value="">Chọn ngành</option>
                            <?php foreach ($dsNganh as $nganh): ?>
                                <option value="<?php echo $nganh['id']; ?>" data-khoa="<?php echo $nganh['khoaId']; ?>">
                                    <?php echo htmlspecialchars($nganh['tenNganh']); ?>
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
                    <button type="submit" class="btn btn-primary" id="btnSuaMonHoc">Lưu thay đổi</button>
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
            <form method="post" id="formXoaMonHoc">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="xoaMonHocId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger" id="btnXoaMonHoc">Xóa</button>
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

// Loading khi submit các form modal môn học
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
addLoadingOnSubmit('formThemMonHoc', 'btnThemMonHoc', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
addLoadingOnSubmit('formSuaMonHoc', 'btnSuaMonHoc', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);
addLoadingOnSubmit('formXoaMonHoc', 'btnXoaMonHoc', `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`);

// Dropdown ngành: filter theo khoa
const dsNganh = <?php echo json_encode($dsNganh); ?>;
function filterNganhByKhoa(selectKhoaId, selectNganhId, selectedNganhId = null) {
    const khoaId = document.getElementById(selectKhoaId).value;
    const nganhSelect = document.getElementById(selectNganhId);
    nganhSelect.innerHTML = '<option value="">Chọn ngành</option>';
    dsNganh.forEach(nganh => {
        if (nganh.khoaId == khoaId) {
            const opt = document.createElement('option');
            opt.value = nganh.id;
            opt.textContent = nganh.tenNganh;
            if (selectedNganhId && nganh.id == selectedNganhId) opt.selected = true;
            nganhSelect.appendChild(opt);
        }
    });
}
// Thêm sự kiện cho modal thêm
if (document.getElementById('khoaId') && document.getElementById('nganhId')) {
    document.getElementById('khoaId').addEventListener('change', function() {
        filterNganhByKhoa('khoaId', 'nganhId');
    });
}
// Thêm sự kiện cho modal sửa
if (document.getElementById('suaKhoaId') && document.getElementById('suaNganhId')) {
    document.getElementById('suaKhoaId').addEventListener('change', function() {
        filterNganhByKhoa('suaKhoaId', 'suaNganhId');
    });
}
// Khi mở modal sửa, tự động set ngành đúng với khoa
const modalSuaMonHoc = document.getElementById('modalSuaMonHoc');
if (modalSuaMonHoc) {
    modalSuaMonHoc.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const ten = button.getAttribute('data-ten');
        const nganhId = button.getAttribute('data-nganh');
        // Tìm khoaId từ dsNganh
        let khoaId = '';
        dsNganh.forEach(nganh => { if (nganh.id == nganhId) khoaId = nganh.khoaId; });
        document.getElementById('suaMonHocId').value = id;
        document.getElementById('suaTenMonHoc').value = ten;
        document.getElementById('suaKhoaId').value = khoaId;
        filterNganhByKhoa('suaKhoaId', 'suaNganhId', nganhId);
    });
}
// Khi mở modal thêm, reset ngành
const modalThemMonHoc = document.getElementById('modalThemMonHoc');
if (modalThemMonHoc) {
    modalThemMonHoc.addEventListener('show.bs.modal', function () {
        document.getElementById('khoaId').value = '';
        filterNganhByKhoa('khoaId', 'nganhId');
    });
}

// Filter ngành ở form lọc
const filterKhoa = document.getElementById('filterKhoa');
const filterNganh = document.getElementById('filterNganh');
if (filterKhoa && filterNganh) {
    filterKhoa.addEventListener('change', function() {
        const khoaId = this.value;
        filterNganh.innerHTML = '<option value="">Tất cả ngành</option>';
        dsNganh.forEach(nganh => {
            if (!khoaId || nganh.khoaId == khoaId) {
                const opt = document.createElement('option');
                opt.value = nganh.id;
                opt.textContent = nganh.tenNganh;
                filterNganh.appendChild(opt);
            }
        });
    });
}
</script>

<?php include '../include/layouts/footer.php'; ?>
