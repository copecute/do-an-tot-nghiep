<?php
require_once '../../include/config.php';
$page_title = "Quản Lý Thí Sinh";
// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id kỳ thi
if (!isset($_GET['kyThiId']) || empty($_GET['kyThiId'])) {
    $_SESSION['flash_message'] = 'ID kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$kyThiId = $_GET['kyThiId'];
$dsThiSinh = [];
$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';

// lấy thông tin kỳ thi
try {
    if ($isAdmin) {
        $stmt = $pdo->prepare('
            SELECT k.*, m.tenMonHoc 
            FROM kyThi k 
            JOIN monHoc m ON k.monHocId = m.id 
            WHERE k.id = ?
        ');
        $stmt->execute([$kyThiId]);
    } else {
        $stmt = $pdo->prepare('
            SELECT k.*, m.tenMonHoc 
            FROM kyThi k 
            JOIN monHoc m ON k.monHocId = m.id 
            WHERE k.id = ? AND k.nguoiTaoId = ?
        ');
        $stmt->execute([$kyThiId, $_SESSION['user_id']]);
    }
    $kyThi = $stmt->fetch();

    if (!$kyThi) {
        $_SESSION['flash_message'] = 'Không tìm thấy kỳ thi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    // lấy danh sách thí sinh
    $stmt = $pdo->prepare('
        SELECT s.id, s.soBaoDanh, sv.maSinhVien, sv.hoTen,
            (SELECT COUNT(*) FROM baiThi b WHERE b.soBaoDanhId = s.id) as soBaiThi
        FROM soBaoDanh s 
        JOIN sinhVien sv ON s.sinhVienId = sv.id 
        WHERE s.kyThiId = ?
        ORDER BY s.soBaoDanh
    ');
    $stmt->execute([$kyThiId]);
    $dsThiSinh = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
    error_log("Lỗi truy vấn danh sách thí sinh: " . $e->getMessage());
}

// Thêm xử lý filter và phân trang phía server
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filterBaiThi = isset($_GET['baiThi']) ? $_GET['baiThi'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if (!in_array($perPage, [5,10,20,50])) $perPage = 10;
// Lọc theo tìm kiếm và số bài thi
$filtered = array_filter($dsThiSinh, function($row) use ($search, $filterBaiThi) {
    $match = true;
    if ($search) {
        $text = strtolower($row['soBaoDanh'] . ' ' . $row['maSinhVien'] . ' ' . $row['hoTen']);
        $match = $match && (strpos($text, strtolower($search)) !== false);
    }
    if ($filterBaiThi === '0') $match = $match && ($row['soBaiThi'] == 0);
    if ($filterBaiThi === '1') $match = $match && ($row['soBaiThi'] > 0);
    return $match;
});
// Sắp xếp
usort($filtered, function($a, $b) use ($sort) {
    switch ($sort) {
        case 'az': return strcasecmp($a['hoTen'], $b['hoTen']);
        case 'za': return strcasecmp($b['hoTen'], $a['hoTen']);
        case 'oldest': return strcmp($a['soBaoDanh'], $b['soBaoDanh']);
        case 'newest': default: return strcmp($b['soBaoDanh'], $a['soBaoDanh']);
    }
});
$totalRows = count($filtered);
$totalPages = ceil($totalRows / $perPage);
$offset = ($page-1)*$perPage;
$dsThiSinhPage = array_slice($filtered, $offset, $perPage);
$stt = 1 + ($page-1)*$perPage;

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang Chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản Lý Kỳ Thi</a></li>
        <li class="breadcrumb-item">
            <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $kyThi['id']; ?>">Kỳ thi: <?php echo htmlspecialchars($kyThi['id']); ?></a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Quản Lý Thí Sinh</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Quản Lý Thí Sinh</h5>
                        <p class="text-muted mb-0">
                            Kỳ thi: <?php echo htmlspecialchars($kyThi['tenKyThi']); ?> | 
                            Môn học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="/quan-ly-ky-thi/thi-sinh/them.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Thêm Thí Sinh
                        </a>
                        <a href="/quan-ly-ky-thi/thi-sinh/excel/nhap.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Nhập/Xuất Excel
                        </a>
                        <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $kyThiId; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay Lại
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                            <?php 
                                // Viết hoa chữ cái đầu cho flash message
                                echo ucfirst($_SESSION['flash_message']);
                                unset($_SESSION['flash_message']);
                                unset($_SESSION['flash_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="get" class="row g-2 mb-3">
                        <input type="hidden" name="kyThiId" value="<?php echo htmlspecialchars($kyThiId); ?>">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm mã SV, họ tên, số báo danh...">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="baiThi">
                                <option value="">Tất cả thí sinh</option>
                                <option value="0" <?php if($filterBaiThi==='0') echo 'selected'; ?>>Chưa có bài thi</option>
                                <option value="1" <?php if($filterBaiThi==='1') echo 'selected'; ?>>Đã có bài thi</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="sort">
                                <option value="newest" <?php if($sort==='newest') echo 'selected'; ?>>Mới nhất</option>
                                <option value="oldest" <?php if($sort==='oldest') echo 'selected'; ?>>Cũ nhất</option>
                                <option value="az" <?php if($sort==='az') echo 'selected'; ?>>Tên A-Z</option>
                                <option value="za" <?php if($sort==='za') echo 'selected'; ?>>Tên Z-A</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="perPage">
                                <option value="5" <?php if($perPage==5) echo 'selected'; ?>>5 dòng/trang</option>
                                <option value="10" <?php if($perPage==10) echo 'selected'; ?>>10 dòng/trang</option>
                                <option value="20" <?php if($perPage==20) echo 'selected'; ?>>20 dòng/trang</option>
                                <option value="50" <?php if($perPage==50) echo 'selected'; ?>>50 dòng/trang</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Lọc</button>
                        </div>
                        <div class="col-md-2 text-end">
                            <span class="text-muted">Tổng: <?php echo $totalRows; ?> thí sinh</span>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">STT</th>
                                    <th>Số Báo Danh</th>
                                    <th>Mã Sinh Viên</th>
                                    <th>Họ Tên</th>
                                    <th width="120">Số Bài Thi</th>
                                    <th width="150">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dsThiSinhPage)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Không có thí sinh nào phù hợp!</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dsThiSinhPage as $thiSinh): ?>
                                        <tr>
                                            <td><?php echo $stt++; ?></td>
                                            <td><?php echo htmlspecialchars($thiSinh['soBaoDanh']); ?></td>
                                            <td><?php echo htmlspecialchars($thiSinh['maSinhVien']); ?></td>
                                            <td><?php echo htmlspecialchars($thiSinh['hoTen']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-<?php echo $thiSinh['soBaiThi'] > 0 ? 'info' : 'secondary'; ?>">
                                                    <?php echo $thiSinh['soBaiThi']; ?> bài
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="/quan-ly-ky-thi/thi-sinh/sua.php?id=<?php echo $thiSinh['id']; ?>&kyThiId=<?php echo $kyThiId; ?>" 
                                                        class="btn btn-primary" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Sửa thông tin">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($thiSinh['soBaiThi'] == 0): ?>
                                                    <button type="button" 
                                                        class="btn btn-danger" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Xóa thí sinh"
                                                        onclick="xacNhanXoa(<?php echo $thiSinh['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
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
                        Hiển thị <?php echo min($offset+1, $totalRows); ?> - <?php echo min($offset+$perPage, $totalRows); ?> trong tổng số <?php echo $totalRows; ?> thí sinh
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function xacNhanXoa(id) {
    if (confirm('Bạn có chắc chắn muốn xóa thí sinh này?')) {
        window.location.href = `/quan-ly-ky-thi/thi-sinh/xoa.php?id=${id}&kyThiId=<?php echo $kyThiId; ?>`;
    }
}
</script>

<?php include '../../include/layouts/footer.php'; ?> 