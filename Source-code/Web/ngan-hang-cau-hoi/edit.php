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

// kiểm tra id câu hỏi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'id câu hỏi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /ngan-hang-cau-hoi');
    exit;
}

$cauHoiId = $_GET['id'];

// lấy thông tin câu hỏi
try {
    $stmt = $pdo->prepare('
        SELECT c.*, m.tenMonHoc 
        FROM cauHoi c 
        JOIN monHoc m ON c.monHocId = m.id 
        WHERE c.id = ?
    ');
    $stmt->execute([$cauHoiId]);
    $cauHoi = $stmt->fetch();

    if (!$cauHoi) {
        $_SESSION['flash_message'] = 'không tìm thấy câu hỏi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /ngan-hang-cau-hoi');
        exit;
    }

    // lấy danh sách đáp án
    $stmt = $pdo->prepare('SELECT * FROM dapAn WHERE cauHoiId = ? ORDER BY id');
    $stmt->execute([$cauHoiId]);
    $dsDapAn = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
}

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

// lấy danh sách thể loại của môn học hiện tại
try {
    $stmt = $pdo->prepare('SELECT * FROM theLoaiCauHoi WHERE monHocId = ? ORDER BY tenTheLoai');
    $stmt->execute([$cauHoi['monHocId']]);
    $dsTheLoai = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'lỗi: ' . $e->getMessage();
    $dsTheLoai = [];
}

// xử lý cập nhật câu hỏi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monHocId = $_POST['monHocId'] ?? '';
    $theLoaiId = !empty($_POST['theLoaiId']) ? $_POST['theLoaiId'] : null;
    $noiDung = trim($_POST['noiDung'] ?? '');
    $doKho = $_POST['doKho'] ?? '';
    $dapAn = $_POST['dapAn'] ?? [];
    $dapAnDung = $_POST['dapAnDung'] ?? '';
    $dapAnId = $_POST['dapAnId'] ?? []; // id của các đáp án hiện tại

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

            // cập nhật câu hỏi
            $stmt = $pdo->prepare('
                UPDATE cauHoi 
                SET monHocId = ?, theLoaiId = ?, noiDung = ?, doKho = ? 
                WHERE id = ?
            ');
            $stmt->execute([$monHocId, $theLoaiId, $noiDung, $doKho, $cauHoiId]);

            // xóa các đáp án cũ không còn trong danh sách
            if (!empty($dapAnId)) {
                $placeholders = str_repeat('?,', count($dapAnId) - 1) . '?';
                $stmt = $pdo->prepare("DELETE FROM dapAn WHERE cauHoiId = ? AND id NOT IN ($placeholders)");
                $params = array_merge([$cauHoiId], $dapAnId);
                $stmt->execute($params);
            } else {
                $stmt = $pdo->prepare('DELETE FROM dapAn WHERE cauHoiId = ?');
                $stmt->execute([$cauHoiId]);
            }

            // cập nhật hoặc thêm mới các đáp án
            $stmtUpdate = $pdo->prepare('UPDATE dapAn SET noiDung = ?, laDapAn = ? WHERE id = ?');
            $stmtInsert = $pdo->prepare('INSERT INTO dapAn (cauHoiId, noiDung, laDapAn) VALUES (?, ?, ?)');

            foreach ($dapAn as $index => $noiDungDapAn) {
                if (trim($noiDungDapAn) !== '') {
                    $laDapAn = ($index == $dapAnDung) ? 1 : 0;
                    
                    if (isset($dapAnId[$index])) {
                        // cập nhật đáp án cũ
                        $stmtUpdate->execute([trim($noiDungDapAn), $laDapAn, $dapAnId[$index]]);
                    } else {
                        // thêm đáp án mới
                        $stmtInsert->execute([$cauHoiId, trim($noiDungDapAn), $laDapAn]);
                    }
                }
            }

            $pdo->commit();
            
            $_SESSION['flash_message'] = 'cập nhật câu hỏi thành công!';
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
        <li class="breadcrumb-item active" aria-current="page">Sửa câu hỏi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">sửa câu hỏi</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post" id="formSuaCauHoi">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="monHocId" class="form-label">môn học <span class="text-danger">*</span></label>
                                <select class="form-select" id="monHocId" name="monHocId" required>
                                    <option value="">chọn môn học</option>
                                    <?php foreach ($dsMonHoc as $monHoc): ?>
                                        <option value="<?php echo $monHoc['id']; ?>" <?php echo $monHoc['id'] == $cauHoi['monHocId'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($monHoc['tenMonHoc'] . ' - ' . $monHoc['tenNganh']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="theLoaiId" class="form-label">thể loại</label>
                                <select class="form-select" id="theLoaiId" name="theLoaiId">
                                    <option value="">chọn thể loại</option>
                                    <?php foreach ($dsTheLoai as $theLoai): ?>
                                        <option value="<?php echo $theLoai['id']; ?>" <?php echo $theLoai['id'] == $cauHoi['theLoaiId'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($theLoai['tenTheLoai']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="noiDung" class="form-label">nội dung câu hỏi <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="noiDung" name="noiDung" rows="3" required><?php echo htmlspecialchars($cauHoi['noiDung']); ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="doKho" class="form-label">độ khó <span class="text-danger">*</span></label>
                                <select class="form-select" id="doKho" name="doKho" required>
                                    <option value="">chọn độ khó</option>
                                    <option value="de" <?php echo $cauHoi['doKho'] == 'de' ? 'selected' : ''; ?>>dễ</option>
                                    <option value="trungbinh" <?php echo $cauHoi['doKho'] == 'trungbinh' ? 'selected' : ''; ?>>trung bình</option>
                                    <option value="kho" <?php echo $cauHoi['doKho'] == 'kho' ? 'selected' : ''; ?>>khó</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label class="form-label">đáp án <span class="text-danger">*</span></label>
                            <div id="dsDapAn">
                                <?php foreach ($dsDapAn as $index => $dapAn): ?>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="radio" name="dapAnDung" value="<?php echo $index; ?>" <?php echo $dapAn['laDapAn'] ? 'checked' : ''; ?>>
                                        </div>
                                        <input type="hidden" name="dapAnId[<?php echo $index; ?>]" value="<?php echo $dapAn['id']; ?>">
                                        <input type="text" class="form-control" name="dapAn[]" value="<?php echo htmlspecialchars($dapAn['noiDung']); ?>" placeholder="nhập đáp án...">
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
                            <button type="submit" class="btn btn-primary">lưu thay đổi</button>
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
document.getElementById('formSuaCauHoi').addEventListener('submit', function(e) {
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