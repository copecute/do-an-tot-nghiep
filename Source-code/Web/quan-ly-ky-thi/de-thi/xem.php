<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id đề thi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'id đề thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$deThiId = $_GET['id'];

try {
    // lấy thông tin đề thi
    $stmt = $pdo->prepare('
        SELECT d.*, k.tenKyThi, m.tenMonHoc, t.hoTen as nguoiTao,
            (SELECT COUNT(*) FROM baiThi b JOIN soBaoDanh s ON b.soBaoDanhId = s.id WHERE b.deThiId = d.id) as soBaiThi
        FROM deThi d 
        JOIN kyThi k ON d.kyThiId = k.id
        JOIN monHoc m ON k.monHocId = m.id
        JOIN taiKhoan t ON d.nguoiTaoId = t.id
        WHERE d.id = ? AND d.nguoiTaoId = ?
    ');
    $stmt->execute([$deThiId, $_SESSION['user_id']]);
    $deThi = $stmt->fetch();

    if (!$deThi) {
        $_SESSION['flash_message'] = 'không tìm thấy đề thi!';
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
    $error = 'lỗi: ' . $e->getMessage();
}

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $deThi['kyThiId']; ?>">Quản lý đề thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Xem đề thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">chi tiết đề thi</h5>
                        <p class="text-muted mb-0">
                            kỳ thi: <?php echo htmlspecialchars($deThi['tenKyThi']); ?> | 
                            môn học: <?php echo htmlspecialchars($deThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $deThi['kyThiId']; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> quay lại
                    </a>
                </div>
                <div class="card-body">
                    <!-- thông tin cơ bản -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="mb-3">thông tin cơ bản:</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td style="width: 150px">tên đề thi:</td>
                                    <td><?php echo htmlspecialchars($deThi['tenDeThi']); ?></td>
                                </tr>
                                <tr>
                                    <td>hình thức tạo:</td>
                                    <td>
                                        <?php if ($deThi['isTuDong']): ?>
                                            <span class="badge bg-success">tự động</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">thủ công</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>thời gian làm:</td>
                                    <td><?php echo $deThi['thoiGian']; ?> phút</td>
                                </tr>
                                <tr>
                                    <td>số câu hỏi:</td>
                                    <td><?php echo count($dsCauHoi); ?> câu</td>
                                </tr>
                                <tr>
                                    <td>số bài thi:</td>
                                    <td><?php echo $deThi['soBaiThi']; ?> bài</td>
                                </tr>
                                <tr>
                                    <td>người tạo:</td>
                                    <td><?php echo htmlspecialchars($deThi['nguoiTao']); ?></td>
                                </tr>
                                <tr>
                                    <td>ngày tạo:</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($deThi['ngayTao'])); ?></td>
                                </tr>
                            </table>
                        </div>

                        <?php if ($deThi['isTuDong']): ?>
                        <div class="col-md-6">
                            <h6 class="mb-3">cấu hình tạo đề:</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td colspan="2">tỷ lệ độ khó:</td>
                                </tr>
                                <tr>
                                    <td style="width: 150px">- dễ:</td>
                                    <td><?php echo $deThi['tyLeDe']; ?>%</td>
                                </tr>
                                <tr>
                                    <td>- trung bình:</td>
                                    <td><?php echo $deThi['tyLeTrungBinh']; ?>%</td>
                                </tr>
                                <tr>
                                    <td>- khó:</td>
                                    <td><?php echo $deThi['tyLeKho']; ?>%</td>
                                </tr>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- thống kê -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="mb-3">thống kê theo độ khó:</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td style="width: 150px">dễ:</td>
                                    <td>
                                        <?php echo $thongKeDoKho['de']; ?> câu
                                        (<?php echo round($thongKeDoKho['de'] / count($dsCauHoi) * 100); ?>%)
                                    </td>
                                </tr>
                                <tr>
                                    <td>trung bình:</td>
                                    <td>
                                        <?php echo $thongKeDoKho['trungbinh']; ?> câu
                                        (<?php echo round($thongKeDoKho['trungbinh'] / count($dsCauHoi) * 100); ?>%)
                                    </td>
                                </tr>
                                <tr>
                                    <td>khó:</td>
                                    <td>
                                        <?php echo $thongKeDoKho['kho']; ?> câu
                                        (<?php echo round($thongKeDoKho['kho'] / count($dsCauHoi) * 100); ?>%)
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <?php if (!empty($thongKeTheLoai)): ?>
                        <div class="col-md-6">
                            <h6 class="mb-3">thống kê theo thể loại:</h6>
                            <table class="table table-sm">
                                <?php foreach ($thongKeTheLoai as $theLoai): ?>
                                <tr>
                                    <td style="width: 150px"><?php echo htmlspecialchars($theLoai['tenTheLoai']); ?>:</td>
                                    <td>
                                        <?php echo $theLoai['soCau']; ?> câu
                                        (<?php echo round($theLoai['soCau'] / count($dsCauHoi) * 100); ?>%)
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- danh sách câu hỏi -->
                    <div class="mb-4">
                        <h6 class="mb-3">danh sách câu hỏi:</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px">#</th>
                                        <th>nội dung</th>
                                        <th style="width: 120px">độ khó</th>
                                        <th style="width: 150px">thể loại</th>
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
                                                'trungbinh' => 'bg-warning',
                                                'kho' => 'bg-danger'
                                            ][$cauHoi['doKho']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo $cauHoi['doKho']; ?>
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
        </div>
    </div>
</div>

<?php include '../../include/layouts/footer.php'; ?>