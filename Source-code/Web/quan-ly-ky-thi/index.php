<?php
require_once '../include/config.php';
$page_title = "Quản Lý Kỳ Thi";

$error = '';
$success = '';
// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn Cần Đăng Nhập Để Truy Cập Trang Này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';

// lấy danh sách kỳ thi
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$where = [];
$params = [];
$paramCount = 0;

if (!$isAdmin) {
    $paramCount++;
    $where[] = 'k.nguoiTaoId = :param' . $paramCount;
    $params[':param' . $paramCount] = $_SESSION['user_id'];
}

// Filter tìm kiếm tên kỳ thi
if (!empty($_GET['q'])) {
    $paramCount++;
    $where[] = 'k.tenKyThi LIKE :param' . $paramCount;
    $params[':param' . $paramCount] = '%' . $_GET['q'] . '%';
}

// Filter theo môn học
if (!empty($_GET['monHoc'])) {
    $paramCount++;
    $where[] = 'k.monHocId = :param' . $paramCount;
    $params[':param' . $paramCount] = $_GET['monHoc'];
}

// Filter theo trạng thái
if (!empty($_GET['status'])) {
    $now = date('Y-m-d H:i:s');
    switch ($_GET['status']) {
        case 'chua_bat_dau':
            $paramCount++;
            $where[] = 'k.thoiGianBatDau > :param' . $paramCount;
            $params[':param' . $paramCount] = $now;
            break;
        case 'dang_dien_ra':
            $paramCount++;
            $where[] = 'k.thoiGianBatDau <= :param' . $paramCount . ' AND k.thoiGianKetThuc >= :param' . ($paramCount + 1);
            $params[':param' . $paramCount] = $now;
            $paramCount++;
            $params[':param' . $paramCount] = $now;
            break;
        case 'da_ket_thuc':
            $paramCount++;
            $where[] = 'k.thoiGianKetThuc < :param' . $paramCount;
            $params[':param' . $paramCount] = $now;
            break;
    }
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Xử lý sắp xếp
$orderBy = 'k.thoiGianBatDau DESC'; // Mặc định mới nhất
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'oldest':
            $orderBy = 'k.thoiGianBatDau ASC';
            break;
        case 'name_asc':
            $orderBy = 'k.tenKyThi ASC';
            break;
        case 'name_desc':
            $orderBy = 'k.tenKyThi DESC';
            break;
        default:
            $orderBy = 'k.thoiGianBatDau DESC';
    }
}

try {
    // Đếm tổng số kỳ thi
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM kyThi k 
        JOIN monHoc m ON k.monHocId = m.id 
        JOIN taiKhoan t ON k.nguoiTaoId = t.id
        $whereSql
    ");
    $stmt->execute($params);
    $totalRows = $stmt->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);
    $offset = ($page - 1) * $perPage;
    
    // Lấy danh sách kỳ thi với phân trang
    $stmt = $pdo->prepare("
        SELECT k.*, m.tenMonHoc, t.hoTen as nguoiTao,
            (SELECT COUNT(*) FROM deThi d WHERE d.kyThiId = k.id) as soDeThi,
            (SELECT COUNT(*) FROM soBaoDanh s WHERE s.kyThiId = k.id) as soThiSinh
        FROM kyThi k 
        JOIN monHoc m ON k.monHocId = m.id 
        JOIN taiKhoan t ON k.nguoiTaoId = t.id
        $whereSql
        ORDER BY $orderBy
        LIMIT :offset, :perpage
    ");
    
    // Bind tất cả tham số
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    // Bind các tham số LIMIT
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perpage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $dsKyThi = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
    $dsKyThi = [];
    $totalPages = 1;
}

include '../include/layouts/header.php';
?>
<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang Chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Quản Lý Kỳ Thi</li>
    </ol>
</nav>
<div class="container-fluid py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý kỳ thi</h2>
        <div>
        <a href="/quan-ly-ky-thi/them.php" class="btn btn-primary" id="btnMoThemKyThi">
                            <i class="fas fa-plus"></i> Thêm Kỳ Thi
                        </a>
        </div>
    </div>

    <!-- filter form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Tìm Kiếm</label>
                    <input type="text" class="form-control" id="search" name="q" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" placeholder="Nhập tên kỳ thi...">
                </div>
                <div class="col-md-2">
                    <label for="monHoc" class="form-label">Môn Học</label>
                    <select class="form-select" id="monHoc" name="monHoc">
                        <option value="">Tất Cả Môn Học</option>
                        <?php 
                        // Lấy danh sách môn học cho filter
                        try {
                            $stmt = $pdo->query('SELECT DISTINCT m.id, m.tenMonHoc FROM monHoc m JOIN kyThi k ON m.id = k.monHocId WHERE k.nguoiTaoId = ? ORDER BY m.tenMonHoc');
                            $stmt->execute([$_SESSION['user_id']]);
                            $dsMonHocFilter = $stmt->fetchAll();
                            foreach ($dsMonHocFilter as $monHoc): ?>
                                <option value="<?php echo $monHoc['id']; ?>" <?php echo (isset($_GET['monHoc']) && $_GET['monHoc'] == $monHoc['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($monHoc['tenMonHoc']); ?>
                                </option>
                            <?php endforeach;
                        } catch (PDOException $e) {
                            // Xử lý lỗi nếu cần
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Trạng Thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tất Cả</option>
                        <option value="chua_bat_dau" <?php echo (isset($_GET['status']) && $_GET['status'] == 'chua_bat_dau') ? 'selected' : ''; ?>>Chưa Bắt Đầu</option>
                        <option value="dang_dien_ra" <?php echo (isset($_GET['status']) && $_GET['status'] == 'dang_dien_ra') ? 'selected' : ''; ?>>Đang Diễn Ra</option>
                        <option value="da_ket_thuc" <?php echo (isset($_GET['status']) && $_GET['status'] == 'da_ket_thuc') ? 'selected' : ''; ?>>Đã Kết Thúc</option>
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

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                            <?php 
                                // Viết hoa chữ đầu cho flash message
                                echo mb_convert_case($_SESSION['flash_message'], MB_CASE_TITLE, "UTF-8");
                                unset($_SESSION['flash_message']);
                                unset($_SESSION['flash_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <?php $_SESSION['flash_message'] = mb_convert_case($error, MB_CASE_TITLE, "UTF-8"); $_SESSION['flash_type'] = 'danger'; ?>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <?php $_SESSION['flash_message'] = mb_convert_case($success, MB_CASE_TITLE, "UTF-8"); $_SESSION['flash_type'] = 'success'; ?>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">STT</th>
                                    <th>Tên Kỳ Thi</th>
                                    <th>Môn Học</th>
                                    <th>Thời Gian Bắt Đầu</th>
                                    <th>Thời Gian Kết Thúc</th>
                                    <th width="120">Người Tạo</th>
                                    <th width="120">Trạng Thái</th>
                                    <th width="150">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dsKyThi)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Chưa Có Kỳ Thi Nào!</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $stt = 1 + ($page-1)*$perPage; foreach ($dsKyThi as $kyThi): ?>
                                        <tr>
                                            <td><?php echo $stt++; ?></td>
                                            <td><?php echo htmlspecialchars($kyThi['tenKyThi']); ?></td>
                                            <td><?php echo htmlspecialchars($kyThi['tenMonHoc']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($kyThi['thoiGianBatDau'])); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($kyThi['thoiGianKetThuc'])); ?></td>
                                            <td><?php echo htmlspecialchars($kyThi['nguoiTao']); ?></td>
                                            <td class="text-center">
                                                <?php
                                                $now = time();
                                                $batDau = strtotime($kyThi['thoiGianBatDau']);
                                                $ketThuc = strtotime($kyThi['thoiGianKetThuc']);
                                                if ($now < $batDau) {
                                                    echo '<span class="badge bg-secondary">Chưa Bắt Đầu</span>';
                                                } elseif ($now >= $batDau && $now <= $ketThuc) {
                                                    $conLai = $ketThuc - $now;
                                                    $phut = floor($conLai / 60);
                                                    echo '<span class="badge bg-info">Đang Diễn Ra<br><small>(Còn ' . $phut . ' phút)</small></span>';
                                                } else {
                                                    echo '<span class="badge bg-danger">Đã Kết Thúc</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $kyThi['id']; ?>" 
                                                    class="btn btn-sm btn-warning" 
                                                    data-bs-toggle="tooltip" 
                                                    title="Dashboard">
                                                    <i class="fas fa-tachometer-alt"></i>
                                                </a>
                                                <a href="/quan-ly-ky-thi/sua.php?id=<?php echo $kyThi['id']; ?>" 
                                                    class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="tooltip" 
                                                    title="Sửa Kỳ Thi">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="tooltip" 
                                                    title="Xóa Kỳ Thi"
                                                    onclick="xacNhanXoa(<?php echo $kyThi['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Phân trang -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Phân trang" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php
                                // Tạo URL với các tham số filter hiện tại
                                $currentParams = $_GET;
                                unset($currentParams['page']); // Loại bỏ page hiện tại
                                $queryString = http_build_query($currentParams);
                                $baseUrl = '?' . ($queryString ? $queryString . '&' : '');
                                
                                // Nút Previous
                                if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                // Hiển thị các trang
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo $baseUrl; ?>page=1">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Nút Next -->
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        
                        <!-- Thông tin phân trang -->
                        <div class="text-center text-muted">
                            Hiển thị <?php echo ($offset + 1); ?> - <?php echo min($offset + $perPage, $totalRows); ?> 
                            trong tổng số <?php echo $totalRows; ?> kỳ thi
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function xacNhanXoa(id) {
    if (confirm('Bạn Có Chắc Chắn Muốn Xóa Kỳ Thi Này?')) {
        window.location.href = `/quan-ly-ky-thi/xoa.php?id=${id}`;
    }
}
// loading khi bấm Thêm Kỳ Thi
const btnMoThemKyThi = document.getElementById('btnMoThemKyThi');
if (btnMoThemKyThi) {
    btnMoThemKyThi.addEventListener('click', function(e) {
        btnMoThemKyThi.disabled = true;
        btnMoThemKyThi.innerHTML = `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`;
    });
}
</script>

<?php include '../include/layouts/footer.php'; ?>
