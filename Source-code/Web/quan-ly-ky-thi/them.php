<?php
require_once '../include/config.php';
$page_title = "Thêm Kỳ Thi";
// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn Cần Đăng Nhập Để Truy Cập Trang Này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$error = '';
$success = '';

// lấy danh sách môn học
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
    $error = 'Lỗi: ' . $e->getMessage();
    $dsMonHoc = [];
}

// lấy danh sách khoa cho dropdown
try {
    $stmt = $pdo->query('SELECT * FROM khoa ORDER BY tenKhoa');
    $dsKhoa = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
    $dsKhoa = [];
}

// xử lý thêm kỳ thi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenKyThi = trim($_POST['tenKyThi'] ?? '');
    $monHocId = $_POST['monHocId'] ?? '';
    $thoiGianBatDau = $_POST['thoiGianBatDau'] ?? '';
    $thoiGianKetThuc = $_POST['thoiGianKetThuc'] ?? '';

    // validate dữ liệu
    if (empty($tenKyThi) || empty($monHocId) || empty($thoiGianBatDau) || empty($thoiGianKetThuc)) {
        $error = 'Vui Lòng Nhập Đầy Đủ Thông Tin!';
    } elseif (strtotime($thoiGianBatDau) >= strtotime($thoiGianKetThuc)) {
        $error = 'Thời Gian Kết Thúc Phải Sau Thời Gian Bắt Đầu!';
    } else {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO kyThi (tenKyThi, monHocId, thoiGianBatDau, thoiGianKetThuc, nguoiTaoId) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$tenKyThi, $monHocId, $thoiGianBatDau, $thoiGianKetThuc, $_SESSION['user_id']]);
            
            $_SESSION['flash_message'] = 'Thêm Kỳ Thi Thành Công!';
            $_SESSION['flash_type'] = 'success';
            header('Location: /quan-ly-ky-thi');
            exit;
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

include '../include/layouts/header.php';
?>
<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang Chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản Lý Kỳ Thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Thêm Kỳ Thi</li>
    </ol>
</nav>
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Thêm Kỳ Thi Mới</h5>
                    </div>
                    <a href="/quan-ly-ky-thi" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Quay Lại
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <?php $_SESSION['flash_message'] = $error; $_SESSION['flash_type'] = 'danger'; ?>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <?php $_SESSION['flash_message'] = $success; $_SESSION['flash_type'] = 'success'; ?>
                    <?php endif; ?>

                    <form method="post" id="formThemKyThi">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="tenKyThi" class="form-label">Tên Kỳ Thi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tenKyThi" name="tenKyThi" required
                                    value="<?php echo isset($_POST['tenKyThi']) ? htmlspecialchars($_POST['tenKyThi']) : ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="khoaId" class="form-label">Khoa <span class="text-danger">*</span></label>
                                <select class="form-select" id="khoaId" name="khoaId" required>
                                    <option value="">Chọn Khoa</option>
                                    <?php foreach ($dsKhoa as $khoa): ?>
                                        <option value="<?php echo $khoa['id']; ?>" <?php echo isset($_POST['khoaId']) && $_POST['khoaId'] == $khoa['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($khoa['tenKhoa']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="nganhId" class="form-label">Ngành <span class="text-danger">*</span></label>
                                <select class="form-select" id="nganhId" name="nganhId" required disabled>
                                    <option value="">Chọn Ngành</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="monHocId" class="form-label">Môn Học <span class="text-danger">*</span></label>
                                <select class="form-select" id="monHocId" name="monHocId" required disabled>
                                    <option value="">Chọn Môn Học</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="thoiGianBatDau" class="form-label">Thời Gian Bắt Đầu <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="thoiGianBatDau" name="thoiGianBatDau" required
                                    value="<?php echo isset($_POST['thoiGianBatDau']) ? $_POST['thoiGianBatDau'] : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="thoiGianKetThuc" class="form-label">Thời Gian Kết Thúc <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="thoiGianKetThuc" name="thoiGianKetThuc" required
                                    value="<?php echo isset($_POST['thoiGianKetThuc']) ? $_POST['thoiGianKetThuc'] : ''; ?>">
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <a href="/quan-ly-ky-thi" class="btn btn-secondary me-2">Hủy</a>
                            <button type="submit" class="btn btn-primary" id="btnThemKyThi">Thêm Kỳ Thi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formThemKyThi').addEventListener('submit', function(e) {
    const thoiGianBatDau = new Date(document.getElementById('thoiGianBatDau').value);
    const thoiGianKetThuc = new Date(document.getElementById('thoiGianKetThuc').value);
    
    if (thoiGianBatDau >= thoiGianKetThuc) {
        e.preventDefault();
        alert('Thời Gian Kết Thúc Phải Sau Thời Gian Bắt Đầu!');
        return;
    }
    // loading khi submit
    var btn = document.getElementById('btnThemKyThi');
    btn.disabled = true;
    btn.innerHTML = `<span class=\"spinner-border spinner-border-sm\" aria-hidden=\"true\"></span> <span class=\"visually-hidden\" role=\"status\">Loading...</span>`;
});

// Xử lý cascading dropdown
document.getElementById('khoaId').addEventListener('change', function() {
    const khoaId = this.value;
    const nganhSelect = document.getElementById('nganhId');
    const monHocSelect = document.getElementById('monHocId');
    
    // Reset dropdown ngành và môn học
    nganhSelect.innerHTML = '<option value="">Chọn Ngành</option>';
    monHocSelect.innerHTML = '<option value="">Chọn Môn Học</option>';
    nganhSelect.disabled = true;
    monHocSelect.disabled = true;
    
    if (khoaId) {
        // Lấy danh sách ngành theo khoa
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
            })
            .catch(error => {
                console.error('Lỗi khi lấy danh sách ngành:', error);
            });
    }
});

document.getElementById('nganhId').addEventListener('change', function() {
    const nganhId = this.value;
    const monHocSelect = document.getElementById('monHocId');
    
    // Reset dropdown môn học
    monHocSelect.innerHTML = '<option value="">Chọn Môn Học</option>';
    monHocSelect.disabled = true;
    
    if (nganhId) {
        // Lấy danh sách môn học theo ngành
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
            })
            .catch(error => {
                console.error('Lỗi khi lấy danh sách môn học:', error);
            });
    }
});
</script>

<?php include '../include/layouts/footer.php'; ?> 