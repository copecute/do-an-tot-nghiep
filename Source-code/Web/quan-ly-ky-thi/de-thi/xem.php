<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id đề thi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID đề thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$deThiId = $_GET['id'];
$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';

try {
    // lấy thông tin đề thi
    $sql = 'SELECT d.*, k.tenKyThi, m.tenMonHoc, t.hoTen as nguoiTao,
            (SELECT COUNT(*) FROM baiThi b JOIN soBaoDanh s ON b.soBaoDanhId = s.id WHERE b.deThiId = d.id) as soBaiThi
        FROM deThi d 
        JOIN kyThi k ON d.kyThiId = k.id
        JOIN monHoc m ON k.monHocId = m.id
        JOIN taiKhoan t ON d.nguoiTaoId = t.id
        WHERE d.id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deThiId]);
    $deThi = $stmt->fetch();
    if (!$deThi) {
        $_SESSION['flash_message'] = 'Không tìm thấy đề thi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }
    // Kiểm tra quyền: admin thì pass, giáo viên chỉ xem đề mình tạo
    if (!$isAdmin && $deThi['nguoiTaoId'] != $_SESSION['user_id']) {
        $_SESSION['flash_message'] = 'Bạn không có quyền xem đề thi này!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    // lấy danh sách câu hỏi của đề thi
    $stmt = $pdo->prepare('
        SELECT c.*, t.tenTheLoai
        FROM deThiCauHoi dc
        JOIN cauHoi c ON dc.cauHoiId = c.id
        LEFT JOIN theLoaiCauHoi t ON c.theLoaiId = t.id
        WHERE dc.deThiId = ?
        ORDER BY dc.id
    ');
    $stmt->execute([$deThiId]);
    $dsCauHoi = $stmt->fetchAll();

    // thống kê theo độ khó
    $thongKeDoKho = [
        'de' => 0,
        'trungbinh' => 0,
        'kho' => 0
    ];

    // thống kê theo thể loại
    $thongKeTheLoai = [];

    foreach ($dsCauHoi as $cauHoi) {
        // thống kê độ khó
        $thongKeDoKho[$cauHoi['doKho']]++;

        // thống kê thể loại
        if ($cauHoi['theLoaiId']) {
            if (!isset($thongKeTheLoai[$cauHoi['theLoaiId']])) {
                $thongKeTheLoai[$cauHoi['theLoaiId']] = [
                    'tenTheLoai' => $cauHoi['tenTheLoai'],
                    'soCau' => 0
                ];
            }
            $thongKeTheLoai[$cauHoi['theLoaiId']]['soCau']++;
        }
    }

} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

$page_title = "Đề thi: " . $deThi['tenDeThi'];
include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang Chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản Lý Kỳ Thi</a></li>
        <li class="breadcrumb-item">
            <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $deThi['kyThiId']; ?>">Kỳ Thi: <?php echo htmlspecialchars($deThi['kyThiId']); ?></a>
        </li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $deThi['kyThiId']; ?>">Quản Lý Đề Thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Xem Đề Thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
                <!-- Thông tin đề thi -->
                <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông Tin Đề Thi</h5>
                    <div>
                        <a href="/quan-ly-ky-thi/de-thi/export-word.php?id=<?php echo $deThi['id']; ?>" class="btn btn-outline-primary" target="_blank">
                            <i class="fas fa-file-word"></i> In đề thi (Word)
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-2"><strong>Tên Đề Thi:</strong> <?php echo htmlspecialchars($deThi['tenDeThi']); ?></div>
                    <div class="mb-2"><strong>Hình Thức Tạo:</strong> <?php if ($deThi['isTuDong']): ?><span class="badge bg-success">Tự Động</span><?php else: ?><span class="badge bg-primary">Thủ Công</span><?php endif; ?></div>
                    <div class="mb-2"><strong>Thời Gian Làm:</strong> <?php echo $deThi['thoiGian']; ?> phút</div>
                    <div class="mb-2"><strong>Số Câu Hỏi:</strong> <?php echo count($dsCauHoi); ?> câu</div>
                    <div class="mb-2"><strong>Số Bài Thi:</strong> <?php echo $deThi['soBaiThi']; ?> bài</div>
                    <div class="mb-2"><strong>Người Tạo:</strong> <?php echo htmlspecialchars($deThi['nguoiTao']); ?></div>
                    <div class="mb-2"><strong>Ngày Tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($deThi['ngayTao'])); ?></div>
                </div>
            </div>
        </div>
        <!-- Thống kê độ khó -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Thống Kê Theo Độ Khó</h6>
                </div>
                <div class="card-body">
                    <?php $tongCau = max(1, count($dsCauHoi)); ?>
                    <div class="mb-2">Dễ: <span class="badge bg-success"><?php echo $thongKeDoKho['de']; ?> câu</span> (<?php echo round($thongKeDoKho['de'] / $tongCau * 100); ?>%)
                        <div class="progress mt-1" style="height:8px;"><div class="progress-bar bg-success" style="width:<?php echo $thongKeDoKho['de'] / $tongCau * 100; ?>%"></div></div>
                    </div>
                    <div class="mb-2">Trung Bình: <span class="badge bg-warning text-dark"><?php echo $thongKeDoKho['trungbinh']; ?> câu</span> (<?php echo round($thongKeDoKho['trungbinh'] / $tongCau * 100); ?>%)
                        <div class="progress mt-1" style="height:8px;"><div class="progress-bar bg-warning" style="width:<?php echo $thongKeDoKho['trungbinh'] / $tongCau * 100; ?>%"></div></div>
                    </div>
                    <div class="mb-2">Khó: <span class="badge bg-danger"><?php echo $thongKeDoKho['kho']; ?> câu</span> (<?php echo round($thongKeDoKho['kho'] / $tongCau * 100); ?>%)
                        <div class="progress mt-1" style="height:8px;"><div class="progress-bar bg-danger" style="width:<?php echo $thongKeDoKho['kho'] / $tongCau * 100; ?>%"></div></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Thống kê thể loại -->
        <div class="col-md-6 mb-4">
            <?php if (!empty($thongKeTheLoai)): ?>
            <div class="card shadow h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-layer-group me-2"></i>Thống Kê Theo Thể Loại</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($thongKeTheLoai as $theLoai): ?>
                    <div class="mb-2"><?php echo htmlspecialchars($theLoai['tenTheLoai']); ?>: <span class="badge bg-info text-dark"><?php echo $theLoai['soCau']; ?> câu</span> (<?php echo round($theLoai['soCau'] / $tongCau * 100); ?>%)
                        <div class="progress mt-1" style="height:8px;"><div class="progress-bar bg-info" style="width:<?php echo $theLoai['soCau'] / $tongCau * 100; ?>%"></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Danh sách câu hỏi -->
    <div class="card shadow mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i>Danh Sách Câu Hỏi</h6>
            <span class="text-muted">Tổng: <?php echo count($dsCauHoi); ?> câu</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>Nội Dung</th>
                            <th style="width: 120px">Độ Khó</th>
                            <th style="width: 150px">Thể Loại</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dsCauHoi as $index => $cauHoi): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($cauHoi['noiDung']); ?></td>
                            <td>
                                <?php
                                $badgeClass = [
                                    'de' => 'bg-success',
                                    'trungbinh' => 'bg-warning text-dark',
                                    'kho' => 'bg-danger'
                                ][$cauHoi['doKho']] ?? 'bg-secondary';
                                $doKhoMap = [
                                    'de' => 'Dễ',
                                    'trungbinh' => 'Trung Bình',
                                    'kho' => 'Khó'
                                ];
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo $doKhoMap[$cauHoi['doKho']] ?? ucfirst($cauHoi['doKho']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($cauHoi['tenTheLoai'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../include/layouts/footer.php'; ?>