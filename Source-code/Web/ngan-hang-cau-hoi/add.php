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
    $error = 'lỗi: ' . $e->getMessage();
    $dsMonHoc = [];
}

// xử lý thêm câu hỏi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monHocId = $_POST['monHocId'] ?? '';
    $theLoaiId = !empty($_POST['theLoaiId']) ? $_POST['theLoaiId'] : null;
    $noiDung = trim($_POST['noiDung'] ?? '');
    $doKho = $_POST['doKho'] ?? '';
    $dapAn = $_POST['dapAn'] ?? [];
    $dapAnDung = $_POST['dapAnDung'] ?? '';

    // validate dữ liệu
    if (empty($monHocId) || empty($noiDung) || empty($doKho) || empty($dapAn)) {
        $error = 'vui lòng nhập đầy đủ thông tin bắt buộc!';
    } elseif (count($dapAn) < 2) {
        $error = 'phải có ít nhất 2 đáp án!';
    } elseif ($dapAnDung === '') {
        $error = 'phải chọn 1 đáp án đúng!';
    } else {
        try {
            $pdo->beginTransaction();

            // thêm câu hỏi
            $stmt = $pdo->prepare('
                INSERT INTO cauHoi (monHocId, theLoaiId, noiDung, doKho) 
                VALUES (?, ?, ?, ?)
            ');
            $stmt->execute([$monHocId, $theLoaiId, $noiDung, $doKho]);
            $cauHoiId = $pdo->lastInsertId();

            // thêm các đáp án
            $stmt = $pdo->prepare('
                INSERT INTO dapAn (cauHoiId, noiDung, laDapAn) 
                VALUES (?, ?, ?)
            ');
            foreach ($dapAn as $index => $noiDungDapAn) {
                if (trim($noiDungDapAn) !== '') {
                    $laDapAn = ($index == $dapAnDung) ? 1 : 0;
                    $stmt->execute([$cauHoiId, trim($noiDungDapAn), $laDapAn]);
                }
            }

            $pdo->commit();
            
            $_SESSION['flash_message'] = 'thêm câu hỏi thành công!';
            $_SESSION['flash_type'] = 'success';
            header('Location: /ngan-hang-cau-hoi');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'lỗi: ' . $e->getMessage();
        }
    }
}

include '../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/ngan-hang-cau-hoi">Ngân hàng câu hỏi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Thêm câu hỏi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">thêm câu hỏi mới</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post" id="formThemCauHoi">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="monHocId" class="form-label">môn học <span class="text-danger">*</span></label>
                                <select class="form-select" id="monHocId" name="monHocId" required>
                                    <option value="">chọn môn học</option>
                                    <?php foreach ($dsMonHoc as $monHoc): ?>
                                        <option value="<?php echo $monHoc['id']; ?>" <?php echo isset($_POST['monHocId']) && $_POST['monHocId'] == $monHoc['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($monHoc['tenMonHoc'] . ' - ' . $monHoc['tenNganh']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="theLoaiId" class="form-label">thể loại</label>
                                <select class="form-select" id="theLoaiId" name="theLoaiId">
                                    <option value="">chọn thể loại</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="noiDung" class="form-label">nội dung câu hỏi <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="noiDung" name="noiDung" rows="3" required><?php echo isset($_POST['noiDung']) ? htmlspecialchars($_POST['noiDung']) : ''; ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="doKho" class="form-label">độ khó <span class="text-danger">*</span></label>
                                <select class="form-select" id="doKho" name="doKho" required>
                                    <option value="">chọn độ khó</option>
                                    <option value="de" <?php echo isset($_POST['doKho']) && $_POST['doKho'] == 'de' ? 'selected' : ''; ?>>dễ</option>
                                    <option value="trungbinh" <?php echo isset($_POST['doKho']) && $_POST['doKho'] == 'trungbinh' ? 'selected' : ''; ?>>trung bình</option>
                                    <option value="kho" <?php echo isset($_POST['doKho']) && $_POST['doKho'] == 'kho' ? 'selected' : ''; ?>>khó</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label class="form-label">đáp án <span class="text-danger">*</span></label>
                            <div id="dsDapAn">
                                <?php
                                $dapAn = isset($_POST['dapAn']) ? $_POST['dapAn'] : ['', ''];
                                $dapAnDung = isset($_POST['dapAnDung']) ? $_POST['dapAnDung'] : '';
                                foreach ($dapAn as $index => $noiDungDapAn):
                                ?>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="radio" name="dapAnDung" value="<?php echo $index; ?>" <?php echo $dapAnDung === (string)$index ? 'checked' : ''; ?>>
                                        </div>
                                        <input type="text" class="form-control" name="dapAn[]" value="<?php echo htmlspecialchars($noiDungDapAn); ?>" placeholder="nhập đáp án...">
                                        <?php if ($index > 1): ?>
                                            <button type="button" class="btn btn-outline-danger" onclick="xoaDapAn(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="themDapAn()">
                                <i class="fas fa-plus"></i> thêm đáp án
                            </button>
                        </div>

                        <div class="text-end mt-4">
                            <a href="/ngan-hang-cau-hoi" class="btn btn-secondary me-2">hủy</a>
                            <button type="submit" class="btn btn-primary">thêm câu hỏi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// load thể loại khi chọn môn học
document.getElementById('monHocId').addEventListener('change', function() {
    const monHocId = this.value;
    const theLoaiSelect = document.getElementById('theLoaiId');
    
    // xóa các option cũ
    theLoaiSelect.innerHTML = '<option value="">chọn thể loại</option>';
    
    if (monHocId) {
        // gọi ajax để lấy danh sách thể loại
        fetch(`/ngan-hang-cau-hoi/the-loai/get.php?monHocId=${monHocId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(theLoai => {
                    const option = document.createElement('option');
                    option.value = theLoai.id;
                    option.textContent = theLoai.tenTheLoai;
                    theLoaiSelect.appendChild(option);
                });
            })
            .catch(error => console.error('lỗi:', error));
    }
});

// thêm đáp án mới
function themDapAn() {
    const dsDapAn = document.getElementById('dsDapAn');
    const index = dsDapAn.children.length;
    
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <div class="input-group-text">
            <input type="radio" name="dapAnDung" value="${index}">
        </div>
        <input type="text" class="form-control" name="dapAn[]" placeholder="nhập đáp án...">
        <button type="button" class="btn btn-outline-danger" onclick="xoaDapAn(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    dsDapAn.appendChild(div);
}

// xóa đáp án
function xoaDapAn(button) {
    button.closest('.input-group').remove();
    
    // cập nhật lại index cho các radio
    const dsDapAn = document.getElementById('dsDapAn');
    const radios = dsDapAn.querySelectorAll('input[type="radio"]');
    radios.forEach((radio, index) => {
        radio.value = index;
    });
}

// validate form trước khi submit
document.getElementById('formThemCauHoi').addEventListener('submit', function(e) {
    const dapAn = document.querySelectorAll('input[name="dapAn[]"]');
    const dapAnDung = document.querySelector('input[name="dapAnDung"]:checked');
    
    // kiểm tra số lượng đáp án
    let countDapAn = 0;
    dapAn.forEach(input => {
        if (input.value.trim() !== '') countDapAn++;
    });
    
    if (countDapAn < 2) {
        e.preventDefault();
        alert('phải có ít nhất 2 đáp án!');
        return;
    }
    
    // kiểm tra đáp án đúng
    if (!dapAnDung) {
        e.preventDefault();
        alert('phải chọn 1 đáp án đúng!');
        return;
    }
});
</script>

<?php include '../include/layouts/footer.php'; ?> 