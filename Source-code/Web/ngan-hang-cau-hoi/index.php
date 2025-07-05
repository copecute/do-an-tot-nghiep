<?php
require_once '../include/config.php';
$page_title = "Ngân Hàng Câu Hỏi";
// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$error = '';
$success = '';

// Lấy danh sách khoa
try {
    $stmt = $pdo->query('SELECT * FROM khoa ORDER BY tenKhoa');
    $dsKhoa = $stmt->fetchAll();
} catch (PDOException $e) {
    $dsKhoa = [];
}

// Lấy danh sách ngành
try {
    $stmt = $pdo->query('SELECT n.*, k.tenKhoa FROM nganh n JOIN khoa k ON n.khoaId = k.id ORDER BY k.tenKhoa, n.tenNganh');
    $dsNganh = $stmt->fetchAll();
} catch (PDOException $e) {
    $dsNganh = [];
}

// Lấy danh sách môn học
try {
    $stmt = $pdo->query('SELECT m.*, n.tenNganh, k.tenKhoa FROM monHoc m JOIN nganh n ON m.nganhId = n.id JOIN khoa k ON n.khoaId = k.id ORDER BY k.tenKhoa, n.tenNganh, m.tenMonHoc');
    $dsMonHoc = $stmt->fetchAll();
} catch (PDOException $e) {
    $dsMonHoc = [];
}

// Lấy danh sách thể loại câu hỏi cho filter
try {
    $stmt = $pdo->query('SELECT t.*, m.tenMonHoc FROM theLoaiCauHoi t JOIN monHoc m ON t.monHocId = m.id ORDER BY m.tenMonHoc, t.tenTheLoai');
    $dsTheLoai = $stmt->fetchAll();
} catch (PDOException $e) {
    $dsTheLoai = [];
}

// Xử lý filter
$where = [];
$params = [];

if (isset($_GET['khoa']) && $_GET['khoa'] !== '') {
    $where[] = 'k.id = ?';
    $params[] = $_GET['khoa'];
}
if (isset($_GET['nganh']) && $_GET['nganh'] !== '') {
    $where[] = 'n.id = ?';
    $params[] = $_GET['nganh'];
}
if (isset($_GET['monHoc']) && $_GET['monHoc'] !== '') {
    $where[] = 'm.id = ?';
    $params[] = $_GET['monHoc'];
}
if (isset($_GET['theLoai']) && $_GET['theLoai'] !== '') {
    $where[] = 'c.theLoaiId = ?';
    $params[] = $_GET['theLoai'];
}
if (isset($_GET['doKho']) && $_GET['doKho'] !== '') {
    $where[] = 'c.doKho = ?';
    $params[] = $_GET['doKho'];
}
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $where[] = 'c.noiDung LIKE ?';
    $params[] = '%' . $_GET['search'] . '%';
}

// Sắp xếp
$sort = $_GET['sort'] ?? 'newest';
$orderBy = 'c.id DESC';
switch ($sort) {
    case 'az': $orderBy = 'c.noiDung ASC'; break;
    case 'za': $orderBy = 'c.noiDung DESC'; break;
    case 'oldest': $orderBy = 'c.id ASC'; break;
    case 'newest': default: $orderBy = 'c.id DESC'; break;
}

// Phân trang
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
if (!in_array($perPage, [5,10,20,50])) $perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Đếm tổng số câu hỏi
try {
    $sqlCount = 'SELECT COUNT(*) FROM cauHoi c JOIN monHoc m ON c.monHocId = m.id JOIN nganh n ON m.nganhId = n.id JOIN khoa k ON n.khoaId = k.id';
    if ($where) $sqlCount .= ' WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare($sqlCount);
    $stmt->execute($params);
    $totalRows = $stmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);
    $offset = ($page-1)*$perPage;
} catch (PDOException $e) {
    $totalRows = 0; $totalPages = 1; $offset = 0;
}

// Lấy danh sách câu hỏi
try {
    $sql = '
        SELECT c.*, m.tenMonHoc, t.tenTheLoai,
            (SELECT COUNT(*) FROM dapAn WHERE cauHoiId = c.id AND laDapAn = 1) as soDapAnDung,
            (SELECT COUNT(*) FROM dapAn WHERE cauHoiId = c.id) as tongSoDapAn
        FROM cauHoi c
        JOIN monHoc m ON c.monHocId = m.id
        JOIN nganh n ON m.nganhId = n.id
        JOIN khoa k ON n.khoaId = k.id
        LEFT JOIN theLoaiCauHoi t ON c.theLoaiId = t.id
    ';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY ' . $orderBy . ' LIMIT ?, ?';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $i => $v) $stmt->bindValue($i+1, $v);
    $stmt->bindValue(count($params)+1, $offset, PDO::PARAM_INT);
    $stmt->bindValue(count($params)+2, $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $dsCauHoi = $stmt->fetchAll();
} catch (PDOException $e) {
    $dsCauHoi = [];
}

include '../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang Chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Ngân Hàng Câu Hỏi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Quản Lý Ngân Hàng Câu Hỏi</h2>
        <div>
        <a href="/ngan-hang-cau-hoi/excel/nhap.php" class="btn btn-success me-2">
                <i class="fas fa-file-excel"></i> Nhập/Xuất Excel
            </a>
            <a href="/ngan-hang-cau-hoi/add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm Câu Hỏi Mới
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <?php $_SESSION['flash_message'] = $error; $_SESSION['flash_type'] = 'danger'; ?>
    <?php endif; ?>

    <?php if ($success): ?>
        <?php $_SESSION['flash_message'] = $success; $_SESSION['flash_type'] = 'success'; ?>
    <?php endif; ?>

    <!-- filter form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end" id="filterForm">
                <div class="col-md-2">
                    <label for="search" class="form-label">Tìm Kiếm</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Nội dung câu hỏi...">
                </div>
                <div class="col-md-2">
                    <label for="khoa" class="form-label">Khoa</label>
                    <select class="form-select" id="khoa" name="khoa">
                        <option value="">Tất Cả Khoa</option>
                        <?php foreach ($dsKhoa as $khoa): ?>
                            <option value="<?php echo $khoa['id']; ?>" <?php echo (isset($_GET['khoa']) && $_GET['khoa'] == $khoa['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($khoa['tenKhoa']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="nganh" class="form-label">Ngành</label>
                    <select class="form-select" id="nganh" name="nganh" disabled>
                        <option value="">Tất Cả Ngành</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="monHoc" class="form-label">Môn Học</label>
                    <select class="form-select" id="monHoc" name="monHoc" disabled>
                        <option value="">Tất Cả Môn Học</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="theLoai" class="form-label">Thể Loại</label>
                    <select class="form-select" id="theLoai" name="theLoai">
                        <option value="">Tất Cả Thể Loại</option>
                        <?php foreach ($dsTheLoai as $theLoai): ?>
                            <option value="<?php echo $theLoai['id']; ?>" <?php echo (isset($_GET['theLoai']) && $_GET['theLoai'] == $theLoai['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($theLoai['tenTheLoai'] . ' - ' . $theLoai['tenMonHoc']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label for="doKho" class="form-label">Độ Khó</label>
                    <select class="form-select" id="doKho" name="doKho">
                        <option value="">Tất Cả</option>
                        <option value="de" <?php echo (isset($_GET['doKho']) && $_GET['doKho'] == 'de') ? 'selected' : ''; ?>>Dễ</option>
                        <option value="trungbinh" <?php echo (isset($_GET['doKho']) && $_GET['doKho'] == 'trungbinh') ? 'selected' : ''; ?>>Trung Bình</option>
                        <option value="kho" <?php echo (isset($_GET['doKho']) && $_GET['doKho'] == 'kho') ? 'selected' : ''; ?>>Khó</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label for="sort" class="form-label">Sắp Xếp</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="newest" <?php if($sort==='newest') echo 'selected'; ?>>Mới nhất</option>
                        <option value="oldest" <?php if($sort==='oldest') echo 'selected'; ?>>Cũ nhất</option>
                        <option value="az" <?php if($sort==='az') echo 'selected'; ?>>A-Z</option>
                        <option value="za" <?php if($sort==='za') echo 'selected'; ?>>Z-A</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label for="perPage" class="form-label">Hiển Thị</label>
                    <select class="form-select" id="perPage" name="perPage">
                        <option value="5" <?php if($perPage==5) echo 'selected'; ?>>5 dòng</option>
                        <option value="10" <?php if($perPage==10) echo 'selected'; ?>>10 dòng</option>
                        <option value="20" <?php if($perPage==20) echo 'selected'; ?>>20 dòng</option>
                        <option value="50" <?php if($perPage==50) echo 'selected'; ?>>50 dòng</option>
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

    <!-- danh sách câu hỏi -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="60">STT</th>
                            <th>Nội Dung</th>
                            <th width="200">Môn Học</th>
                            <th width="200">Thể Loại</th>
                            <th width="100">Độ Khó</th>
                            <th width="100">Đáp Án</th>
                            <th width="150">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsCauHoi)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Chưa Có Câu Hỏi Nào</td>
                            </tr>
                        <?php else: ?>
                            <?php $stt = 1 + ($page-1)*$perPage; foreach ($dsCauHoi as $cauHoi): ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td>
                                        <?php 
                                            $noiDung = htmlspecialchars($cauHoi['noiDung']);
                                            echo strlen($noiDung) > 100 ? substr($noiDung, 0, 100) . '...' : $noiDung;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($cauHoi['tenMonHoc']); ?></td>
                                    <td><?php echo $cauHoi['tenTheLoai'] ? htmlspecialchars($cauHoi['tenTheLoai']) : '<em class="text-muted">Không Có</em>'; ?></td>
                                    <td>
                                        <?php
                                            $doKhoClass = [
                                                'de' => 'success',
                                                'trungbinh' => 'warning',
                                                'kho' => 'danger'
                                            ];
                                            $doKhoText = [
                                                'de' => 'Dễ',
                                                'trungbinh' => 'Trung Bình',
                                                'kho' => 'Khó'
                                            ];
                                        ?>
                                        <span class="badge bg-<?php echo $doKhoClass[$cauHoi['doKho']]; ?>">
                                            <?php echo $doKhoText[$cauHoi['doKho']]; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            <?php echo $cauHoi['soDapAnDung']; ?>/<?php echo $cauHoi['tongSoDapAn']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/ngan-hang-cau-hoi/edit.php?id=<?php echo $cauHoi['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/ngan-hang-cau-hoi/delete.php?id=<?php echo $cauHoi['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Phân trang -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Phân trang" class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php
                    $params = $_GET;
                    unset($params['page']);
                    $queryString = http_build_query($params);
                    $baseUrl = '?' . ($queryString ? $queryString . '&' : '');
                    ?>
                    <li class="page-item<?php if ($page <= 1) echo ' disabled'; ?>">
                        <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $page-1; ?>"><i class="fas fa-chevron-left"></i></a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                            <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item<?php if ($page >= $totalPages) echo ' disabled'; ?>">
                        <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $page+1; ?>"><i class="fas fa-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <div class="text-end text-muted mt-2">
                Hiển thị <?php echo min($offset+1, $totalRows); ?> - <?php echo min($offset+$perPage, $totalRows); ?> trong tổng số <?php echo $totalRows; ?> câu hỏi
            </div>
        </div>
    </div>
</div>

<script>
// Cascading dropdown ngành theo khoa (AJAX)
document.getElementById('khoa').addEventListener('change', function() {
    const khoaId = this.value;
    const nganhSelect = document.getElementById('nganh');
    const monHocSelect = document.getElementById('monHoc');
    nganhSelect.innerHTML = '<option value="">Tất Cả Ngành</option>';
    monHocSelect.innerHTML = '<option value="">Tất Cả Môn Học</option>';
    nganhSelect.disabled = true;
    monHocSelect.disabled = true;
    if (khoaId) {
        fetch(`/quan-ly-nganh/get.php?khoaId=${khoaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.nganh.forEach(nganh => {
                        const option = document.createElement('option');
                        option.value = nganh.id;
                        option.textContent = nganh.tenNganh;
                        nganhSelect.appendChild(option);
                    });
                    nganhSelect.disabled = false;
                }
            });
    }
});
// Cascading dropdown môn học theo ngành (AJAX)
document.getElementById('nganh').addEventListener('change', function() {
    const nganhId = this.value;
    const monHocSelect = document.getElementById('monHoc');
    monHocSelect.innerHTML = '<option value="">Tất Cả Môn Học</option>';
    monHocSelect.disabled = true;
    if (nganhId) {
        fetch(`/quan-ly-mon-hoc/get.php?nganhId=${nganhId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.monHoc.forEach(monHoc => {
                        const option = document.createElement('option');
                        option.value = monHoc.id;
                        option.textContent = monHoc.tenMonHoc;
                        monHocSelect.appendChild(option);
                    });
                    monHocSelect.disabled = false;
                }
            });
    }
});
// Nếu đã chọn sẵn khi load lại trang (giữ trạng thái filter)
window.addEventListener('DOMContentLoaded', function() {
    const khoaId = document.getElementById('khoa').value;
    const nganhId = '<?php echo isset($_GET['nganh']) ? $_GET['nganh'] : ''; ?>';
    const monHocId = '<?php echo isset($_GET['monHoc']) ? $_GET['monHoc'] : ''; ?>';
    if (khoaId) {
        fetch(`/quan-ly-nganh/get.php?khoaId=${khoaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const nganhSelect = document.getElementById('nganh');
                    data.nganh.forEach(nganh => {
                        const option = document.createElement('option');
                        option.value = nganh.id;
                        option.textContent = nganh.tenNganh;
                        if (nganhId && nganhId == nganh.id) option.selected = true;
                        nganhSelect.appendChild(option);
                    });
                    nganhSelect.disabled = false;
                    if (nganhId) {
                        fetch(`/quan-ly-mon-hoc/get.php?nganhId=${nganhId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const monHocSelect = document.getElementById('monHoc');
                                    data.monHoc.forEach(monHoc => {
                                        const option = document.createElement('option');
                                        option.value = monHoc.id;
                                        option.textContent = monHoc.tenMonHoc;
                                        if (monHocId && monHocId == monHoc.id) option.selected = true;
                                        monHocSelect.appendChild(option);
                                    });
                                    monHocSelect.disabled = false;
                                }
                            });
                    }
                }
            });
    }
});
</script>

<?php include '../include/layouts/footer.php'; ?>
