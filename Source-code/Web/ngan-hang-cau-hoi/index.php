<?php
require_once '../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$error = '';
$success = '';

// lấy danh sách môn học cho filter
try {
    $stmt = $pdo->query('
        SELECT m.*, n.tenNganh, k.tenKhoa 
        FROM monHoc m 
        JOIN nganh n ON m.nganhId = n.id 
        JOIN khoa k ON n.khoaId = k.id 
        ORDER BY k.tenKhoa, n.tenNganh, m.tenMonHoc
    ');
    $dsMonHoc = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
    $dsMonHoc = [];
}

// lấy danh sách thể loại câu hỏi cho filter
try {
    $stmt = $pdo->query('
        SELECT t.*, m.tenMonHoc 
        FROM theLoaiCauHoi t 
        JOIN monHoc m ON t.monHocId = m.id 
        ORDER BY m.tenMonHoc, t.tenTheLoai
    ');
    $dsTheLoai = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
    $dsTheLoai = [];
}

// xử lý filter
$where = [];
$params = [];

if (isset($_GET['monHoc']) && !empty($_GET['monHoc'])) {
    $where[] = 'c.monHocId = ?';
    $params[] = $_GET['monHoc'];
}

if (isset($_GET['theLoai']) && !empty($_GET['theLoai'])) {
    $where[] = 'c.theLoaiId = ?';
    $params[] = $_GET['theLoai'];
}

if (isset($_GET['doKho']) && !empty($_GET['doKho'])) {
    $where[] = 'c.doKho = ?';
    $params[] = $_GET['doKho'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where[] = 'c.noiDung LIKE ?';
    $params[] = '%' . $_GET['search'] . '%';
}

// lấy danh sách câu hỏi
try {
    $sql = '
        SELECT c.*, m.tenMonHoc, t.tenTheLoai,
            (SELECT COUNT(*) FROM dapAn WHERE cauHoiId = c.id AND laDapAn = 1) as soDapAnDung,
            (SELECT COUNT(*) FROM dapAn WHERE cauHoiId = c.id) as tongSoDapAn
        FROM cauHoi c
        JOIN monHoc m ON c.monHocId = m.id
        LEFT JOIN theLoaiCauHoi t ON c.theLoaiId = t.id
    ';
    
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    
    $sql .= ' ORDER BY m.tenMonHoc, c.id DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dsCauHoi = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
    $dsCauHoi = [];
}

include '../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Ngân hàng câu hỏi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Quản lý ngân hàng câu hỏi</h2>
        <div>
            <a href="/ngan-hang-cau-hoi/the-loai" class="btn btn-info me-2">
                <i class="fas fa-tags"></i> Quản lý thể loại
            </a>
            <a href="/ngan-hang-cau-hoi/add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm câu hỏi mới
            </a>
            <a href="/ngan-hang-cau-hoi/excel/nhap.php" class="btn btn-success me-2">
                <i class="fas fa-file-excel"></i> Nhập/Xuất excel
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- filter form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="monHoc" class="form-label">môn học</label>
                    <select class="form-select" id="monHoc" name="monHoc">
                        <option value="">tất cả môn học</option>
                        <?php foreach ($dsMonHoc as $monHoc): ?>
                            <option value="<?php echo $monHoc['id']; ?>" <?php echo (isset($_GET['monHoc']) && $_GET['monHoc'] == $monHoc['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($monHoc['tenMonHoc'] . ' - ' . $monHoc['tenNganh']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="theLoai" class="form-label">thể loại</label>
                    <select class="form-select" id="theLoai" name="theLoai">
                        <option value="">tất cả thể loại</option>
                        <?php foreach ($dsTheLoai as $theLoai): ?>
                            <option value="<?php echo $theLoai['id']; ?>" <?php echo (isset($_GET['theLoai']) && $_GET['theLoai'] == $theLoai['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($theLoai['tenTheLoai'] . ' - ' . $theLoai['tenMonHoc']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="doKho" class="form-label">độ khó</label>
                    <select class="form-select" id="doKho" name="doKho">
                        <option value="">tất cả</option>
                        <option value="de" <?php echo (isset($_GET['doKho']) && $_GET['doKho'] == 'de') ? 'selected' : ''; ?>>dễ</option>
                        <option value="trungbinh" <?php echo (isset($_GET['doKho']) && $_GET['doKho'] == 'trungbinh') ? 'selected' : ''; ?>>trung bình</option>
                        <option value="kho" <?php echo (isset($_GET['doKho']) && $_GET['doKho'] == 'kho') ? 'selected' : ''; ?>>khó</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">tìm kiếm</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="nhập nội dung câu hỏi...">
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
                            <th width="80">ID</th>
                            <th>nội dung</th>
                            <th width="200">môn học</th>
                            <th width="200">thể loại</th>
                            <th width="100">độ khó</th>
                            <th width="100">đáp án</th>
                            <th width="150">thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dsCauHoi)): ?>
                            <tr>
                                <td colspan="7" class="text-center">chưa có câu hỏi nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dsCauHoi as $cauHoi): ?>
                                <tr>
                                    <td><?php echo $cauHoi['id']; ?></td>
                                    <td>
                                        <?php 
                                            $noiDung = htmlspecialchars($cauHoi['noiDung']);
                                            echo strlen($noiDung) > 100 ? substr($noiDung, 0, 100) . '...' : $noiDung;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($cauHoi['tenMonHoc']); ?></td>
                                    <td><?php echo $cauHoi['tenTheLoai'] ? htmlspecialchars($cauHoi['tenTheLoai']) : '<em class="text-muted">không có</em>'; ?></td>
                                    <td>
                                        <?php
                                            $doKhoClass = [
                                                'de' => 'success',
                                                'trungbinh' => 'warning',
                                                'kho' => 'danger'
                                            ];
                                            $doKhoText = [
                                                'de' => 'dễ',
                                                'trungbinh' => 'trung bình',
                                                'kho' => 'khó'
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
                                        <a href="/ngan-hang-cau-hoi/delete.php?id=<?php echo $cauHoi['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('bạn có chắc chắn muốn xóa câu hỏi này?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
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

<?php include '../include/layouts/footer.php'; ?>
