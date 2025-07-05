<?php
require_once '../../include/config.php';
$page_title = "Kết Quả Thi";
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

// kiểm tra phân quyền
$isAdmin = ($_SESSION['vai_tro'] == 'admin');

// khởi tạo các biến thống kê
$tongBaiThi = 0;
$diemTrungBinh = 0;
$diemCaoNhat = 0;
$diemThapNhat = 10;
$soBaiDat = 0;
$tyLeDat = 0;
$dsKetQua = [];

// lấy thông tin kỳ thi
try {
    if ($isAdmin) {
        // Admin có thể xem tất cả kỳ thi
        $stmt = $pdo->prepare('
            SELECT k.*, m.tenMonHoc 
            FROM kyThi k 
            JOIN monHoc m ON k.monHocId = m.id 
            WHERE k.id = ?
        ');
        $stmt->execute([$kyThiId]);
    } else {
        // Giáo viên chỉ xem được kỳ thi mình tạo
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

    // lấy danh sách kết quả
    $stmt = $pdo->prepare('
        SELECT b.*, d.tenDeThi, s.soBaoDanh, sv.maSinhVien, sv.hoTen
        FROM baiThi b 
        JOIN deThi d ON b.deThiId = d.id
        JOIN soBaoDanh s ON b.soBaoDanhId = s.id
        JOIN sinhVien sv ON s.sinhVienId = sv.id
        WHERE d.kyThiId = ?
        ORDER BY b.diem DESC, sv.hoTen
    ');
    $stmt->execute([$kyThiId]);
    $dsKetQua = $stmt->fetchAll();

    // tính thống kê
    $tongBaiThi = count($dsKetQua);

    if ($tongBaiThi > 0) {
        foreach ($dsKetQua as $ketQua) {
            $diemTrungBinh += $ketQua['diem'];
            $diemCaoNhat = max($diemCaoNhat, $ketQua['diem']);
            $diemThapNhat = min($diemThapNhat, $ketQua['diem']);
            if ($ketQua['diem'] >= 5) $soBaiDat++;
        }
        $diemTrungBinh /= $tongBaiThi;
        $tyLeDat = ($soBaiDat / $tongBaiThi) * 100;
    }
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

// Xử lý filter, tìm kiếm, sắp xếp, phân trang phía server
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'diem_desc';
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if (!in_array($perPage, [5,10,20,50])) $perPage = 10;

// Lọc theo tìm kiếm
$filtered = array_filter($dsKetQua, function($row) use ($search) {
    if (!$search) return true;
    $text = strtolower($row['hoTen'] . ' ' . $row['maSinhVien'] . ' ' . $row['soBaoDanh'] . ' ' . $row['tenDeThi']);
    return strpos($text, strtolower($search)) !== false;
});

// Sắp xếp
usort($filtered, function($a, $b) use ($sort) {
    switch ($sort) {
        case 'hoTen_az': return strcasecmp($a['hoTen'], $b['hoTen']);
        case 'hoTen_za': return strcasecmp($b['hoTen'], $a['hoTen']);
        case 'diem_asc': return $a['diem'] - $b['diem'];
        case 'diem_desc': default: return $b['diem'] - $a['diem'];
    }
});

$totalRows = count($filtered);
$totalPages = ceil($totalRows / $perPage);
$offset = ($page-1)*$perPage;
$dsKetQuaPage = array_slice($filtered, $offset, $perPage);
$stt = 1 + ($page-1)*$perPage;

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang Chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản Lý Kỳ Thi</a></li>
        <li class="breadcrumb-item">
            <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $kyThi['id']; ?>">Kỳ Thi: <?php echo htmlspecialchars($kyThi['id']); ?></a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Kết Quả Thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Kết Quả Thi</h5>
                        <p class="text-muted mb-0">
                            Kỳ Thi: <?php echo htmlspecialchars($kyThi['tenKyThi']); ?> | 
                            Môn Học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="/quan-ly-ky-thi/ket-qua/xuat-excel.php?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-success" id="btnXuatExcel">
                            <i class="fas fa-file-excel"></i> Xuất Excel
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
                                echo $_SESSION['flash_message'];
                                unset($_SESSION['flash_message']);
                                unset($_SESSION['flash_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($tongBaiThi > 0): ?>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Tổng Số Bài Thi</h6>
                                        <h3 class="mb-0"><?php echo $tongBaiThi; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Điểm Trung Bình</h6>
                                        <h3 class="mb-0"><?php echo number_format($diemTrungBinh, 2); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Điểm Cao Nhất</h6>
                                        <h3 class="mb-0"><?php echo number_format($diemCaoNhat, 2); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Tỷ Lệ Đạt</h6>
                                        <h3 class="mb-0"><?php echo number_format($tyLeDat, 0); ?>%</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="get" class="row g-2 mb-3">
                        <input type="hidden" name="kyThiId" value="<?php echo htmlspecialchars($kyThiId); ?>">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm tên, mã SV, số báo danh, đề thi...">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="sort">
                                <option value="diem_desc" <?php if($sort==='diem_desc') echo 'selected'; ?>>Điểm cao nhất</option>
                                <option value="diem_asc" <?php if($sort==='diem_asc') echo 'selected'; ?>>Điểm thấp nhất</option>
                                <option value="hoTen_az" <?php if($sort==='hoTen_az') echo 'selected'; ?>>Tên A-Z</option>
                                <option value="hoTen_za" <?php if($sort==='hoTen_za') echo 'selected'; ?>>Tên Z-A</option>
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
                            <span class="text-muted">Tổng: <?php echo $totalRows; ?> bài thi</span>
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
                                    <th>Đề Thi</th>
                                    <th>Thời Gian Nộp</th>
                                    <th>Số Câu Đúng</th>
                                    <th>Điểm</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dsKetQuaPage)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Không có bài thi nào phù hợp!</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dsKetQuaPage as $ketQua): ?>
                                        <tr>
                                            <td><?php echo $stt++; ?></td>
                                            <td><?php echo htmlspecialchars($ketQua['soBaoDanh']); ?></td>
                                            <td><?php echo htmlspecialchars($ketQua['maSinhVien']); ?></td>
                                            <td><?php echo htmlspecialchars($ketQua['hoTen']); ?></td>
                                            <td><?php echo htmlspecialchars($ketQua['tenDeThi']); ?></td>
                                            <td><?php echo $ketQua['thoiGianNop'] ? date('d/m/Y H:i:s', strtotime($ketQua['thoiGianNop'])) : ''; ?></td>
                                            <td><?php echo $ketQua['soCauDung'] . '/' . $ketQua['tongSoCau']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $ketQua['diem'] >= 5 ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo number_format($ketQua['diem'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/quan-ly-ky-thi/ket-qua/xem.php?id=<?php echo $ketQua['id']; ?>" 
                                                        class="btn btn-sm btn-info" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Xem Chi Tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
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
                        Hiển thị <?php echo min($offset+1, $totalRows); ?> - <?php echo min($offset+$perPage, $totalRows); ?> trong tổng số <?php echo $totalRows; ?> bài thi
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const btnXuatExcel = document.getElementById('btnXuatExcel');
if (btnXuatExcel) {
    btnXuatExcel.addEventListener('click', function(e) {
        e.preventDefault();
        const oldHtml = btnXuatExcel.innerHTML;
        window.location.href = btnXuatExcel.getAttribute('href');
        btnXuatExcel.classList.remove('btn-success');
        btnXuatExcel.classList.add('btn-primary');
        btnXuatExcel.innerHTML = `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`;
        btnXuatExcel.setAttribute('disabled', 'disabled');
        setTimeout(function() {
            btnXuatExcel.innerHTML = oldHtml;
            btnXuatExcel.classList.remove('btn-primary');
            btnXuatExcel.classList.add('btn-success');
            btnXuatExcel.removeAttribute('disabled');
        }, 3000);
    });
}
</script>

<?php include '../../include/layouts/footer.php'; ?> 