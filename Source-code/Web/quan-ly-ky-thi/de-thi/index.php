<?php
require_once '../../include/config.php';
$page_title = "Quản Lý Đề Thi";
// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';
$kyThiId = isset($_GET['kyThiId']) ? $_GET['kyThiId'] : null;
if (!$kyThiId) {
    $_SESSION['flash_message'] = 'Thiếu id kỳ thi!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

// Kiểm tra quyền truy cập kỳ thi
if ($isAdmin) {
    $stmt = $pdo->prepare('SELECT k.*, m.tenMonHoc FROM kyThi k JOIN monHoc m ON k.monHocId = m.id WHERE k.id = ?');
    $stmt->execute([$kyThiId]);
} else {
    $stmt = $pdo->prepare('SELECT k.*, m.tenMonHoc FROM kyThi k JOIN monHoc m ON k.monHocId = m.id WHERE k.id = ? AND k.nguoiTaoId = ?');
    $stmt->execute([$kyThiId, $_SESSION['user_id']]);
}
$kyThi = $stmt->fetch();
if (!$kyThi) {
    $_SESSION['flash_message'] = 'Không tìm thấy kỳ thi hoặc bạn không có quyền!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

// lấy danh sách đề thi
$stmt = $pdo->prepare('
    SELECT d.*, 
        (SELECT COUNT(*) FROM deThiCauHoi dc WHERE dc.deThiId = d.id) as soCauHoi,
        (SELECT COUNT(*) FROM baiThi b JOIN soBaoDanh s ON b.soBaoDanhId = s.id WHERE b.deThiId = d.id) as soBaiThi
    FROM deThi d 
    WHERE d.kyThiId = ?
    ORDER BY d.id DESC
');
$stmt->execute([$kyThiId]);
$dsDeThi = $stmt->fetchAll();

// Xử lý filter, tìm kiếm, sắp xếp, phân trang phía server
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if (!in_array($perPage, [5,10,20,50])) $perPage = 10;
// Lọc theo tìm kiếm
$filtered = array_filter($dsDeThi, function($row) use ($search) {
    if (!$search) return true;
    $text = strtolower($row['tenDeThi']);
    return strpos($text, strtolower($search)) !== false;
});
// Sắp xếp
usort($filtered, function($a, $b) use ($sort) {
    switch ($sort) {
        case 'az': return strcasecmp($a['tenDeThi'], $b['tenDeThi']);
        case 'za': return strcasecmp($b['tenDeThi'], $a['tenDeThi']);
        case 'oldest': return $a['id'] - $b['id'];
        case 'newest': default: return $b['id'] - $a['id'];
    }
});
$totalRows = count($filtered);
$totalPages = ceil($totalRows / $perPage);
$offset = ($page-1)*$perPage;
$dsDeThiPage = array_slice($filtered, $offset, $perPage);
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
        <li class="breadcrumb-item active" aria-current="page">Quản Lý Đề Thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Quản Lý Đề Thi</h5>
                        <p class="text-muted mb-0">
                            Kỳ Thi: <?php echo htmlspecialchars($kyThi['tenKyThi']); ?> | 
                            Môn Học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="/quan-ly-ky-thi/de-thi/tao.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success">
                            <i class="fas fa-plus"></i> Tạo Đề Thi
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                            <?php 
                                echo $_SESSION['flash_message'];
                                unset($_SESSION['flash_message']);
                                unset($_SESSION['flash_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="get" class="row g-2 mb-3">
                        <input type="hidden" name="kyThiId" value="<?php echo htmlspecialchars($kyThiId); ?>">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm tên đề thi...">
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
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Lọc</button>
                        </div>
                        <div class="col-md-2 text-end">
                            <span class="text-muted">Tổng: <?php echo $totalRows; ?> đề thi</span>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">STT</th>
                                    <th>Tên Đề Thi</th>
                                    <th>Hình Thức Tạo</th>
                                    <th>Thời Gian Làm</th>
                                    <th>Số Câu Hỏi</th>
                                    <th>Số Bài Nộp</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dsDeThiPage)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Không có đề thi nào phù hợp!</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dsDeThiPage as $deThi): ?>
                                        <tr>
                                            <td><?php echo $stt++; ?></td>
                                            <td><?php echo htmlspecialchars($deThi['tenDeThi']); ?></td>
                                            <td>
                                                <?php if ($deThi['isTuDong']): ?>
                                                    <span class="badge bg-success">Tự Động</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Thủ Công</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $deThi['thoiGian']; ?> phút</td>
                                            <td><?php echo $deThi['soCauHoi']; ?></td>
                                            <td><?php echo $deThi['soBaiThi']; ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/quan-ly-ky-thi/de-thi/xem.php?id=<?php echo $deThi['id']; ?>" 
                                                        class="btn btn-sm btn-info" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Xem Đề Thi">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/quan-ly-ky-thi/de-thi/sua.php?id=<?php echo $deThi['id']; ?>" 
                                                        class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Sửa Đề Thi">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Xóa Đề Thi"
                                                        onclick="xacNhanXoa(<?php echo $deThi['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
                        Hiển thị <?php echo min($offset+1, $totalRows); ?> - <?php echo min($offset+$perPage, $totalRows); ?> trong tổng số <?php echo $totalRows; ?> đề thi
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function xacNhanXoa(id) {
    if (confirm('Bạn có chắc chắn muốn xóa đề thi này?')) {
        window.location.href = `/quan-ly-ky-thi/de-thi/xoa.php?id=${id}`;
    }
}
</script>

<?php include '../../include/layouts/footer.php'; ?> 