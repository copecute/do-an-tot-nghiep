<?php
require_once '../include/config.php';
$page_title = "Sửa câu hỏi";
// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$error = '';
$success = '';

// kiểm tra id câu hỏi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID câu hỏi không hợp lệ!';
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
        $_SESSION['flash_message'] = 'Không tìm thấy câu hỏi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /ngan-hang-cau-hoi');
        exit;
    }

    // lấy danh sách đáp án
    $stmt = $pdo->prepare('SELECT * FROM dapAn WHERE cauHoiId = ? ORDER BY id');
    $stmt->execute([$cauHoiId]);
    $dsDapAn = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
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
    $error = 'Lỗi: ' . $e->getMessage();
    $dsMonHoc = [];
}

// lấy danh sách thể loại của môn học hiện tại
try {
    $stmt = $pdo->prepare('SELECT * FROM theLoaiCauHoi WHERE monHocId = ? ORDER BY tenTheLoai');
    $stmt->execute([$cauHoi['monHocId']]);
    $dsTheLoai = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
    $dsTheLoai = [];
}

// xử lý cập nhật câu hỏi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monHocId = !empty($_POST['monHocId']) ? $_POST['monHocId'] : null;
    $theLoaiId = isset($_POST['theLoaiId']) ? trim($_POST['theLoaiId']) : null;
    $noiDung = trim($_POST['noiDung'] ?? '');
    $doKho = $_POST['doKho'] ?? '';
    $dapAn = $_POST['dapAn'] ?? [];
    $dapAnDung = $_POST['dapAnDung'] ?? '';
    $dapAnId = $_POST['dapAnId'] ?? [];

    // Xử lý theLoaiId: nếu rỗng thì null, nếu là chuỗi thì insert vào bảng theloaicauhoi lấy id
    if ($theLoaiId === '' || $theLoaiId === null) {
        $theLoaiId = null;
    } elseif (!is_numeric($theLoaiId)) {
        $stmt = $pdo->prepare("SELECT id FROM theloaicauhoi WHERE tenTheLoai = ? AND monHocId = ?");
        $stmt->execute([$theLoaiId, $monHocId]);
        $row = $stmt->fetch();
        if ($row) {
            $theLoaiId = $row['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO theloaicauhoi (tenTheLoai, monHocId) VALUES (?, ?)");
            $stmt->execute([$theLoaiId, $monHocId]);
            $theLoaiId = $pdo->lastInsertId();
        }
    }

    // validate dữ liệu
    if (empty($monHocId) || empty($noiDung) || empty($doKho) || empty($dapAn)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc!';
    } elseif (count($dapAn) < 2) {
        $error = 'Phải có ít nhất 2 đáp án!';
    } elseif ($dapAnDung === '') {
        $error = 'Phải chọn 1 đáp án đúng!';
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
            
            $_SESSION['flash_message'] = 'Cập nhật câu hỏi thành công!';
            $_SESSION['flash_type'] = 'success';
            header('Location: /ngan-hang-cau-hoi');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Lỗi: ' . $e->getMessage();
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Sửa Câu Hỏi</h5>
                    </div>
                    <a href="/ngan-hang-cau-hoi" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Quay Lại
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post" id="formSuaCauHoi">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="monHocId" class="form-label">Môn học <span class="text-danger">*</span></label>
                                <select class="form-select" id="monHocId" name="monHocId" required>
                                    <option value="">Chọn môn học</option>
                                    <?php foreach ($dsMonHoc as $monHoc): ?>
                                        <option value="<?php echo $monHoc['id']; ?>" <?php echo $monHoc['id'] == $cauHoi['monHocId'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($monHoc['tenMonHoc'] . ' - ' . $monHoc['tenNganh']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="theLoaiId" class="form-label">Thể loại</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control mb-1" id="theLoaiInput" placeholder="Chọn hoặc nhập thể loại" autocomplete="off">
                                    <div id="theLoaiDropdown" class="dropdown-menu w-100" style="max-height:200px;overflow:auto;"></div>
                                    <input type="hidden" name="theLoaiId" id="theLoaiId" value="<?php echo isset($cauHoi['theLoaiId']) ? htmlspecialchars($cauHoi['theLoaiId']) : '' ?>">
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="noiDung" class="form-label">Nội dung câu hỏi <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="noiDung" name="noiDung" rows="3" required><?php echo htmlspecialchars($cauHoi['noiDung']); ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="doKho" class="form-label">Độ khó <span class="text-danger">*</span></label>
                                <select class="form-select" id="doKho" name="doKho" required>
                                    <option value="">Chọn độ khó</option>
                                    <option value="de" <?php echo $cauHoi['doKho'] == 'de' ? 'selected' : ''; ?>>Dễ</option>
                                    <option value="trungbinh" <?php echo $cauHoi['doKho'] == 'trungbinh' ? 'selected' : ''; ?>>Trung bình</option>
                                    <option value="kho" <?php echo $cauHoi['doKho'] == 'kho' ? 'selected' : ''; ?>>Khó</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label class="form-label">Đáp án <span class="text-danger">*</span></label>
                            <div id="dsDapAn">
                                <?php foreach ($dsDapAn as $index => $dapAn): ?>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="radio" name="dapAnDung" value="<?php echo $index; ?>" <?php echo $dapAn['laDapAn'] ? 'checked' : ''; ?>>
                                        </div>
                                        <input type="hidden" name="dapAnId[<?php echo $index; ?>]" value="<?php echo $dapAn['id']; ?>">
                                        <input type="text" class="form-control" name="dapAn[]" value="<?php echo htmlspecialchars($dapAn['noiDung']); ?>" placeholder="Nhập đáp án...">
                                        <?php if ($index > 1): ?>
                                            <button type="button" class="btn btn-outline-danger" onclick="xoaDapAn(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="themDapAn()">
                                <i class="fas fa-plus"></i> Thêm đáp án
                            </button>
                        </div>

                        <div class="text-end mt-4">
                            <a href="/ngan-hang-cau-hoi" class="btn btn-secondary me-2">Hủy</a>
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
        alert('Phải có ít nhất 2 đáp án!');
        return;
    }
    
    // kiểm tra đáp án đúng
    if (!dapAnDung) {
        e.preventDefault();
        alert('Phải chọn 1 đáp án đúng!');
        return;
    }
});

// --- Tự động load thể loại khi đã có môn học được chọn ---
let dsTheLoai = [];
function fetchTheLoai(monHocId, selected) {
    if (!monHocId) return;
    fetch('/ngan-hang-cau-hoi/the-loai/get.php?monHocId=' + monHocId)
        .then(response => response.json())
        .then(data => {
            dsTheLoai = data;
            renderTheLoaiDropdown(selected);
        });
}
function renderTheLoaiDropdown(selected) {
    const dropdown = document.getElementById('theLoaiDropdown');
    dropdown.innerHTML = '';
    dsTheLoai.forEach(tl => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'dropdown-item';
        item.textContent = tl.tenTheLoai;
        item.dataset.id = tl.id;
        if (selected && (selected == tl.id || selected == tl.tenTheLoai)) item.classList.add('active');
        item.onclick = function() {
            document.getElementById('theLoaiInput').value = tl.tenTheLoai;
            document.getElementById('theLoaiId').value = tl.id;
            dropdown.classList.remove('show');
        };
        dropdown.appendChild(item);
    });
}
// Hiển thị dropdown khi focus input
const theLoaiInput = document.getElementById('theLoaiInput');
theLoaiInput.addEventListener('focus', function() {
    if (dsTheLoai.length) {
        renderTheLoaiDropdown(document.getElementById('theLoaiId').value);
        document.getElementById('theLoaiDropdown').classList.add('show');
    }
});
theLoaiInput.addEventListener('input', function() {
    const val = theLoaiInput.value.toLowerCase();
    const dropdown = document.getElementById('theLoaiDropdown');
    dropdown.innerHTML = '';
    let found = false;
    dsTheLoai.forEach(tl => {
        if (tl.tenTheLoai.toLowerCase().includes(val)) {
            found = true;
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'dropdown-item';
            item.textContent = tl.tenTheLoai;
            item.dataset.id = tl.id;
            item.onclick = function() {
                theLoaiInput.value = tl.tenTheLoai;
                document.getElementById('theLoaiId').value = tl.id;
                dropdown.classList.remove('show');
            };
            dropdown.appendChild(item);
        }
    });
    if (!found && val) {
        // Cho phép nhập mới
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'dropdown-item text-primary';
        item.textContent = 'Thêm mới: ' + theLoaiInput.value;
        item.onclick = function() {
            document.getElementById('theLoaiId').value = theLoaiInput.value;
            dropdown.classList.remove('show');
        };
        dropdown.appendChild(item);
    }
    dropdown.classList.add('show');
});
theLoaiInput.addEventListener('blur', function() {
    setTimeout(() => document.getElementById('theLoaiDropdown').classList.remove('show'), 200);
});
document.getElementById('monHocId').addEventListener('change', function() {
    theLoaiInput.value = '';
    document.getElementById('theLoaiId').value = '';
    fetchTheLoai(this.value, '');
});
// Khởi tạo nếu đã có môn học
window.addEventListener('DOMContentLoaded', function() {
    const monHocId = document.getElementById('monHocId').value;
    const theLoaiId = document.getElementById('theLoaiId').value;
    if (monHocId) fetchTheLoai(monHocId, theLoaiId);
    if (theLoaiId) {
        // Nếu là số, tìm tên trong dsTheLoai hoặc fetch từ server
        if (!isNaN(theLoaiId)) {
            fetch('/ngan-hang-cau-hoi/the-loai/get.php?monHocId=' + monHocId)
                .then(response => response.json())
                .then(data => {
                    const found = data.find(tl => tl.id == theLoaiId);
                    document.getElementById('theLoaiInput').value = found ? found.tenTheLoai : '';
                });
        } else {
            document.getElementById('theLoaiInput').value = theLoaiId;
        }
    }
});

// Thêm đáp án mới
function themDapAn() {
    const dsDapAn = document.getElementById('dsDapAn');
    const index = dsDapAn.children.length;
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <div class="input-group-text">
            <input type="radio" name="dapAnDung" value="${index}">
        </div>
        <input type="text" class="form-control" name="dapAn[]" placeholder="Nhập đáp án...">
        <button type="button" class="btn btn-outline-danger" onclick="xoaDapAn(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    dsDapAn.appendChild(div);
}
// Xóa đáp án
function xoaDapAn(button) {
    button.closest('.input-group').remove();
    // cập nhật lại index cho các radio
    const dsDapAn = document.getElementById('dsDapAn');
    const radios = dsDapAn.querySelectorAll('input[type="radio"]');
    radios.forEach((radio, index) => {
        radio.value = index;
    });
}
</script>

<?php include '../include/layouts/footer.php'; ?> 