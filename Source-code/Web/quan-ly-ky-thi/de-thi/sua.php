<?php
require_once '../../include/config.php';
$page_title = "Sửa đề thi";
// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';
$deThiId = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);
if (!$deThiId) {
    $_SESSION['flash_message'] = 'Thiếu id đề thi!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

try {
    if ($isAdmin) {
        $stmt = $pdo->prepare('SELECT d.*, k.tenKyThi, k.monHocId, m.tenMonHoc, 
            (SELECT COUNT(*) FROM baiThi b JOIN soBaoDanh s ON b.soBaoDanhId = s.id WHERE b.deThiId = d.id) as soBaiThi
            FROM deThi d 
            JOIN kyThi k ON d.kyThiId = k.id
            JOIN monHoc m ON k.monHocId = m.id
            WHERE d.id = ?');
        $stmt->execute([$deThiId]);
    } else {
        $stmt = $pdo->prepare('SELECT d.*, k.tenKyThi, k.monHocId, m.tenMonHoc, 
            (SELECT COUNT(*) FROM baiThi b JOIN soBaoDanh s ON b.soBaoDanhId = s.id WHERE b.deThiId = d.id) as soBaiThi
            FROM deThi d 
            JOIN kyThi k ON d.kyThiId = k.id
            JOIN monHoc m ON k.monHocId = m.id
            WHERE d.id = ? AND k.nguoiTaoId = ?');
        $stmt->execute([$deThiId, $_SESSION['user_id']]);
    }
    $deThi = $stmt->fetch();

    if (!$deThi) {
        $_SESSION['flash_message'] = 'Không tìm thấy đề thi hoặc bạn không có quyền!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    // kiểm tra nếu đã có bài thi thì không cho sửa
    if ($deThi['soBaiThi'] > 0) {
        $_SESSION['flash_message'] = 'Không thể sửa đề thi vì đã có bài thi!';
        $_SESSION['flash_type'] = 'danger';
        header("Location: /quan-ly-ky-thi/de-thi/?kyThiId={$deThi['kyThiId']}");
        exit;
    }

    // Lấy danh sách câu hỏi hiện tại của đề thi
    $stmt = $pdo->prepare('
        SELECT c.*, t.tenTheLoai
        FROM deThiCauHoi dch
        JOIN cauHoi c ON dch.cauHoiId = c.id
        LEFT JOIN theLoaiCauHoi t ON c.theLoaiId = t.id
        WHERE dch.deThiId = ?
    ');
    $stmt->execute([$deThiId]);
    $dsCauHoiDeThi = $stmt->fetchAll();
    $idsCauHoiDeThi = array_column($dsCauHoiDeThi, 'id');

    // Lấy toàn bộ câu hỏi của môn học
    $stmt = $pdo->prepare('
        SELECT c.*, t.tenTheLoai
        FROM cauHoi c
        LEFT JOIN theLoaiCauHoi t ON c.theLoaiId = t.id
        WHERE c.monHocId = ?
        ORDER BY c.id DESC
    ');
    $stmt->execute([$deThi['monHocId']]);
    $dsCauHoi = $stmt->fetchAll();

    // xử lý form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tenDeThi = trim($_POST['tenDeThi'] ?? '');
        $thoiGian = intval($_POST['thoiGian'] ?? 0);
        $dsCauHoi = $_POST['dsCauHoi'] ?? [];
        $errors = [];
        if (empty($tenDeThi)) $errors[] = 'Tên đề thi không được để trống!';
        if ($thoiGian < 1) $errors[] = 'Thời gian làm bài phải lớn hơn 0!';
        if (empty($dsCauHoi)) $errors[] = 'Phải chọn ít nhất một câu hỏi cho đề thi!';
        if (empty($errors)) {
            // Nếu đang là tự động (isTuDong=1) mà có thao tác chọn câu hỏi thì chuyển sang thủ công
            $stmt = $pdo->prepare('
                UPDATE deThi 
                SET tenDeThi = ?, thoiGian = ?, soCau = ?, isTuDong = 0
                WHERE id = ? AND nguoiTaoId = ?
            ');
            $stmt->execute([$tenDeThi, $thoiGian, count($dsCauHoi), $deThiId, $_SESSION['user_id']]);
            // Xóa hết câu hỏi cũ
            $pdo->prepare('DELETE FROM deThiCauHoi WHERE deThiId = ?')->execute([$deThiId]);
            // Thêm lại câu hỏi mới
            $stmt = $pdo->prepare('INSERT INTO deThiCauHoi (deThiId, cauHoiId) VALUES (?, ?)');
            foreach ($dsCauHoi as $cauHoiId) {
                $stmt->execute([$deThiId, $cauHoiId]);
            }
            $_SESSION['flash_message'] = 'Cập nhật đề thi thành công! Đề đã chuyển sang chế độ thủ công.';
            $_SESSION['flash_type'] = 'success';
            header("Location: /quan-ly-ky-thi/de-thi/?kyThiId={$deThi['kyThiId']}");
            exit;
        }
    }
} catch (PDOException $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}

include '../../include/layouts/header.php';
?>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang Chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản Lý Kỳ Thi</a></li>
        <li class="breadcrumb-item">
            <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $deThi['kyThiId']; ?>">Kỳ thi: <?php echo htmlspecialchars($deThi['kyThiId']); ?></a>
        </li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $deThi['kyThiId']; ?>">Quản Lý Đề Thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Sửa Đề Thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Sửa Đề Thi</h5>
                        <p class="text-muted mb-0">
                            Kỳ thi: <?php echo htmlspecialchars($deThi['tenKyThi']); ?> | 
                            Môn học: <?php echo htmlspecialchars($deThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $deThi['kyThiId']; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Quay Lại
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <?php $_SESSION['flash_message'] = implode('<br>', $errors); $_SESSION['flash_type'] = 'danger'; ?>
                    <?php endif; ?>

                    <form method="POST" id="formSuaDe">
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tên đề thi:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="tenDeThi" required
                                    value="<?php echo htmlspecialchars($deThi['tenDeThi']); ?>"
                                    placeholder="Nhập tên đề thi...">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Thời gian làm bài (phút):</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="thoiGian" required
                                    value="<?php echo $deThi['thoiGian']; ?>"
                                    min="1" placeholder="Nhập thời gian làm bài...">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Số câu hỏi:</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="soCau" value="<?php echo $deThi['soCau']; ?>" readonly>
                                <small class="text-muted">Không thể thay đổi số câu hỏi</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Hình thức tạo:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" 
                                    value="<?php echo $deThi['isTuDong'] ? 'Tự động' : 'Thủ công'; ?>" readonly>
                                <small class="text-muted"><?php echo $deThi['isTuDong'] ? '' : 'Không thể thay đổi hình thức tạo đề';?></small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Danh sách câu hỏi:</label>
                            <div class="col-sm-9">
                                <button type="button" class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#modalChonCauHoi">
                                    <i class="fas fa-list"></i> Xem/Chọn câu hỏi
                                </button>
                                <input type="hidden" name="dsCauHoiJson" id="dsCauHoiJson">
                                <div id="hiddenDsCauHoiInputs"></div>
                                <small class="text-muted">Nhấn nút để xem/chọn lại câu hỏi cho đề thi.</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Lưu Thay Đổi
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chọn Câu Hỏi -->
<div class="modal fade" id="modalChonCauHoi" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn Câu Hỏi Cho Đề Thi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if ($deThi['isTuDong']): ?>
                <div id="canhBaoThuCong" class="alert alert-warning d-none" role="alert">
                    Nếu bạn chỉnh sửa danh sách câu hỏi, đề sẽ chuyển sang chế độ thủ công khi lưu!
                </div>
                <?php endif; ?>
                <ul class="nav nav-tabs mb-3" id="tabCauHoi" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-da-chon" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab">Đã chọn</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-tat-ca" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab">Tất cả câu hỏi</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                        <div id="dsCauHoiModalDaChon" class="list-group mb-2">
                            <!-- Danh sách đã chọn sẽ render ở đây -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab2" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover" id="bangCauHoiModal">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">Chọn</th>
                                        <th>Nội Dung</th>
                                        <th>Thể Loại</th>
                                        <th>Độ Khó</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dsCauHoi as $cauHoi): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input chon-cau-hoi-modal" type="checkbox" 
                                                    value="<?php echo $cauHoi['id']; ?>"
                                                    data-noidung="<?php echo htmlspecialchars($cauHoi['noiDung']); ?>">
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($cauHoi['noiDung']); ?></td>
                                        <td><?php echo htmlspecialchars($cauHoi['tenTheLoai'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                                $doKhoMap = ['de' => 'Dễ', 'trungbinh' => 'Trung bình', 'kho' => 'Khó'];
                                                echo isset($doKhoMap[$cauHoi['doKho']]) ? $doKhoMap[$cauHoi['doKho']] : htmlspecialchars($cauHoi['doKho']);
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btnXacNhanChonModal">Xác Nhận</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var cauHoiDaChon = <?php echo json_encode($idsCauHoiDeThi); ?>;
    var dsCauHoi = <?php echo json_encode($dsCauHoi); ?>;
    var isTuDong = <?php echo (int)$deThi['isTuDong']; ?>;
    var canShowWarning = true;

    // Render input hidden dsCauHoi[] để submit
    function renderHiddenInputs() {
        var container = document.getElementById('hiddenDsCauHoiInputs');
        container.innerHTML = '';
        cauHoiDaChon.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'dsCauHoi[]';
            input.value = id;
            container.appendChild(input);
        });
        document.getElementById('soCau').value = cauHoiDaChon.length;
        document.getElementById('dsCauHoiJson').value = JSON.stringify(cauHoiDaChon);
    }
    renderHiddenInputs();

    // Khi mở modal, reset cảnh báo
    var modal = document.getElementById('modalChonCauHoi');
    modal.addEventListener('show.bs.modal', function() {
        if (isTuDong) {
            canShowWarning = true;
            var canhBao = document.getElementById('canhBaoThuCong');
            if (canhBao) canhBao.classList.add('d-none');
        }
        var container = document.getElementById('dsCauHoiModalDaChon');
        container.innerHTML = '';
        cauHoiDaChon.forEach(function(id) {
            var ch = dsCauHoi.find(function(c) { return c.id == id; });
            if (ch) {
                var div = document.createElement('div');
                div.className = 'list-group-item d-flex justify-content-between align-items-center';
                div.setAttribute('data-id', id);
                div.innerHTML = '<span>' + ch.noiDung + '</span>' +
                    '<button type="button" class="btn btn-sm btn-danger btn-xoa-cau-hoi-modal"><i class="fas fa-times"></i></button>';
                container.appendChild(div);
            }
        });
        document.querySelectorAll('.chon-cau-hoi-modal').forEach(function(cb) {
            cb.checked = cauHoiDaChon.includes(cb.value);
        });
    });
    // Xóa câu hỏi ở tab đã chọn trong modal
    document.getElementById('dsCauHoiModalDaChon').addEventListener('click', function(e) {
        if (e.target.closest('.btn-xoa-cau-hoi-modal')) {
            var item = e.target.closest('.list-group-item');
            var id = item.getAttribute('data-id');
            cauHoiDaChon = cauHoiDaChon.filter(function(value) { return value != id; });
            renderHiddenInputs();
            // Uncheck ở tab tất cả
            document.querySelectorAll('.chon-cau-hoi-modal').forEach(function(cb) {
                if (cb.value == id) cb.checked = false;
            });
            item.remove();
            if (isTuDong && canShowWarning) {
                var canhBao = document.getElementById('canhBaoThuCong');
                if (canhBao) canhBao.classList.remove('d-none');
                canShowWarning = false;
            }
        }
    });
    // Tick chọn ở tab tất cả
    document.querySelectorAll('.chon-cau-hoi-modal').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var id = cb.value;
            if (cb.checked) {
                if (!cauHoiDaChon.includes(id)) cauHoiDaChon.push(id);
            } else {
                cauHoiDaChon = cauHoiDaChon.filter(function(value) { return value != id; });
            }
            renderHiddenInputs();
            if (isTuDong && canShowWarning) {
                var canhBao = document.getElementById('canhBaoThuCong');
                if (canhBao) canhBao.classList.remove('d-none');
                canShowWarning = false;
            }
        });
    });
    // Xác nhận chọn trong modal
    document.getElementById('btnXacNhanChonModal').addEventListener('click', function() {
        renderHiddenInputs();
        var modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) modalInstance.hide();
    });
    // Phân trang, tìm kiếm, lọc cho bảng modal
    (function() {
        var table = document.getElementById('bangCauHoiModal');
        if (!table) return;
        var rows = Array.from(table.querySelectorAll('tbody tr'));
        var pageSize = 10;
        var currentPage = 1;
        // Tạo ô tìm kiếm
        var searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control mb-2';
        searchInput.placeholder = 'Tìm kiếm nội dung, thể loại, độ khó...';
        table.parentNode.insertBefore(searchInput, table);
        // Tạo select lọc độ khó
        var filterSelect = document.createElement('select');
        filterSelect.className = 'form-select mb-2';
        filterSelect.innerHTML = '<option value="">Tất cả độ khó</option>' +
            '<option value="Dễ">Dễ</option>' +
            '<option value="Trung bình">Trung bình</option>' +
            '<option value="Khó">Khó</option>';
        table.parentNode.insertBefore(filterSelect, table);
        // Tạo phân trang
        var pagination = document.createElement('div');
        pagination.className = 'd-flex justify-content-center mt-2';
        table.parentNode.appendChild(pagination);
        function renderTable() {
            var keyword = searchInput.value.toLowerCase();
            var doKho = filterSelect.value;
            var filtered = rows.filter(function(row) {
                var text = row.textContent.toLowerCase();
                var doKhoText = row.children[3].textContent.trim();
                return text.includes(keyword) && (doKho === '' || doKhoText === doKho);
            });
            var totalPage = Math.ceil(filtered.length / pageSize) || 1;
            if (currentPage > totalPage) currentPage = totalPage;
            filtered.forEach(function(row, i) {
                row.style.display = (i >= (currentPage-1)*pageSize && i < currentPage*pageSize) ? '' : 'none';
            });
            // Render nút phân trang
            pagination.innerHTML = '';
            for (let i = 1; i <= totalPage; i++) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm ' + (i === currentPage ? 'btn-primary' : 'btn-outline-primary');
                btn.textContent = i;
                btn.onclick = function() { currentPage = i; renderTable(); };
                pagination.appendChild(btn);
            }
        }
        searchInput.addEventListener('input', function() { currentPage = 1; renderTable(); });
        filterSelect.addEventListener('change', function() { currentPage = 1; renderTable(); });
        renderTable();
    })();
});
</script>

<?php include '../../include/layouts/footer.php'; ?> 