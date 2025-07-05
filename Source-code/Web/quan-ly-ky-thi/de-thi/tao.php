<?php
require_once '../../include/config.php';
$page_title = "Tạo đề thi";
// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

$isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';
$kyThiId = isset($_GET['kyThiId']) ? $_GET['kyThiId'] : (isset($_POST['kyThiId']) ? $_POST['kyThiId'] : null);
if (!$kyThiId) {
    $_SESSION['flash_message'] = 'Thiếu id kỳ thi!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}
if ($isAdmin) {
    $stmt = $pdo->prepare('SELECT * FROM kyThi WHERE id = ?');
    $stmt->execute([$kyThiId]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM kyThi WHERE id = ? AND nguoiTaoId = ?');
    $stmt->execute([$kyThiId, $_SESSION['user_id']]);
}
$kyThi = $stmt->fetch();
if (!$kyThi) {
    $_SESSION['flash_message'] = 'Không tìm thấy kỳ thi hoặc bạn không có quyền!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

try {
    // lấy thông tin kỳ thi
    if ($isAdmin) {
        $stmt = $pdo->prepare('SELECT k.*, m.tenMonHoc FROM kyThi k JOIN monHoc m ON k.monHocId = m.id WHERE k.id = ?');
        $stmt->execute([$kyThiId]);
    } else {
        $stmt = $pdo->prepare('SELECT k.*, m.tenMonHoc FROM kyThi k JOIN monHoc m ON k.monHocId = m.id WHERE k.id = ? AND k.nguoiTaoId = ?');
        $stmt->execute([$kyThiId, $_SESSION['user_id']]);
    }
    $kyThi = $stmt->fetch();

    if (!$kyThi) {
        $_SESSION['flash_message'] = 'Không tìm thấy kỳ thi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: /quan-ly-ky-thi');
        exit;
    }

    // lấy số lượng câu hỏi theo độ khó
    $stmt = $pdo->prepare('
        SELECT doKho, COUNT(*) as soCau 
        FROM cauHoi 
        WHERE monHocId = ? 
        GROUP BY doKho
    ');
    $stmt->execute([$kyThi['monHocId']]);
    $thongKeCauHoi = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // lấy danh sách thể loại
    $stmt = $pdo->prepare('
        SELECT id, tenTheLoai,
            (SELECT COUNT(*) FROM cauHoi WHERE theLoaiId = t.id) as soCau
        FROM theLoaiCauHoi t 
        WHERE monHocId = ?
        ORDER BY tenTheLoai
    ');
    $stmt->execute([$kyThi['monHocId']]);
    $dsTheLoai = $stmt->fetchAll();

    // lấy danh sách câu hỏi của môn học
    $stmt = $pdo->prepare('
        SELECT c.*, t.tenTheLoai
        FROM cauHoi c
        LEFT JOIN theLoaiCauHoi t ON c.theLoaiId = t.id
        WHERE c.monHocId = ?
        ORDER BY c.id DESC
    ');
    $stmt->execute([$kyThi['monHocId']]);
    $dsCauHoi = $stmt->fetchAll();
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
            <a href="/quan-ly-ky-thi/dashboard.php?id=<?php echo $kyThi['id']; ?>">Kỳ thi: <?php echo htmlspecialchars($kyThi['id']); ?></a>
        </li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $kyThiId; ?>">Quản Lý Đề Thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tạo Đề Thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Tạo Đề Thi Mới</h5>
                        <p class="text-muted mb-0">
                            Kỳ thi: <?php echo htmlspecialchars($kyThi['tenKyThi']); ?> | 
                            Môn học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Quay Lại
                    </a>
                </div>
                <div class="card-body">
                    <form id="formTaoDe" method="POST" action="xu-ly-tao.php">
                        <input type="hidden" name="kyThiId" value="<?php echo $kyThiId; ?>">
                        
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Tên Đề Thi:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="tenDeThi" required
                                    placeholder="Nhập tên đề thi...">
                            </div>
                        </div>

                        <div class="row mb-3" id="soCauInput">
                            <label class="col-sm-3 col-form-label">Số Câu Hỏi:</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="soCau" required min="1"
                                    placeholder="Nhập số câu hỏi...">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Thời Gian Làm Bài (phút):</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="thoiGian" required min="1"
                                    placeholder="Nhập thời gian làm bài...">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label">Chế Độ Tạo Đề:</label>
                            <div class="col-sm-9">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="cheDo" name="isTuDong" checked>
                                    <label class="form-check-label" for="cheDo">
                                        Tạo đề tự động
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Phần cấu hình tự động -->
                        <div id="cauHinhTuDong">
                            <h6 class="mb-3">Cấu Hình Tạo Đề Tự Động:</h6>
                            
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Tỷ Lệ Độ Khó:</label>
                                <div class="col-sm-9">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Dễ (%):</label>
                                            <input type="number" class="form-control" name="tyLeDe" value="30" min="0" max="100">
                                            <small class="text-muted">Có sẵn: <?php echo $thongKeCauHoi['de'] ?? 0; ?> câu</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Trung Bình (%):</label>
                                            <input type="number" class="form-control" name="tyLeTrungBinh" value="40" min="0" max="100">
                                            <small class="text-muted">Có sẵn: <?php echo $thongKeCauHoi['trungbinh'] ?? 0; ?> câu</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Khó (%):</label>
                                            <input type="number" class="form-control" name="tyLeKho" value="30" min="0" max="100">
                                            <small class="text-muted">Có sẵn: <?php echo $thongKeCauHoi['kho'] ?? 0; ?> câu</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($dsTheLoai)): ?>
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">Tỷ Lệ Thể Loại:</label>
                                <div class="col-sm-9">
                                    <div class="row">
                                        <?php foreach ($dsTheLoai as $theLoai): ?>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label"><?php echo htmlspecialchars((string) $theLoai['tenTheLoai'] ?? ''); ?> (%):</label>
                                            <input type="number" class="form-control" 
                                                name="tyLeTheLoai[<?php echo $theLoai['id']; ?>]" value="0" min="0" max="100">
                                            <small class="text-muted">Có sẵn: <?php echo $theLoai['soCau']; ?> câu</small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Phần chọn thủ công -->
                        <div id="chonThuCong" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Danh Sách Câu Hỏi Đã Chọn: <span id="soCauDaChon">0</span></h6>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalChonCauHoi">
                                    <i class="fas fa-plus"></i> Chọn Câu Hỏi
                                </button>
                            </div>
                            <div id="dsCauHoiDaChon" class="list-group">
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Tạo Đề Thi
                            </button>
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
                <h5 class="modal-title">Chọn Câu Hỏi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="bangCauHoi">
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
                                        <input class="form-check-input chon-cau-hoi" type="checkbox" 
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btnXacNhanChon">Xác Nhận</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // khởi tạo DataTable: BỎ QUA, không dùng nữa

    // xử lý chuyển đổi chế độ tạo đề
    function toggleCheDo() {
        var isTuDong = document.getElementById('cheDo').checked;
        document.getElementById('cauHinhTuDong').style.display = isTuDong ? '' : 'none';
        document.getElementById('chonThuCong').style.display = isTuDong ? 'none' : '';
        document.getElementById('soCauInput').style.display = isTuDong ? '' : 'none';
        document.querySelector('input[name="soCau"]').required = isTuDong;
    }
    document.getElementById('cheDo').addEventListener('change', toggleCheDo);
    toggleCheDo();

    // mảng lưu các câu hỏi đã chọn
    var cauHoiDaChon = [];

    // xử lý nút xác nhận chọn câu hỏi
    document.getElementById('btnXacNhanChon').addEventListener('click', function() {
        var checkboxes = document.querySelectorAll('.chon-cau-hoi:checked');
        checkboxes.forEach(function(cb) {
            var id = cb.value;
            var noiDung = cb.getAttribute('data-noidung');
            if (!cauHoiDaChon.includes(id)) {
                cauHoiDaChon.push(id);
                var div = document.createElement('div');
                div.className = 'list-group-item d-flex justify-content-between align-items-center';
                div.setAttribute('data-id', id);
                div.innerHTML = '<span>' + noiDung + '</span>' +
                    '<button type="button" class="btn btn-sm btn-danger btn-xoa-cau-hoi"><i class="fas fa-times"></i></button>' +
                    '<input type="hidden" name="dsCauHoi[]" value="' + id + '">';
                document.getElementById('dsCauHoiDaChon').appendChild(div);
            }
        });
        document.getElementById('soCauDaChon').textContent = cauHoiDaChon.length;
        document.querySelector('input[name="soCau"]').value = cauHoiDaChon.length;
        // đóng modal
        var modal = document.getElementById('modalChonCauHoi');
        if (modal) {
            var modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) modalInstance.hide();
        }
        // bỏ chọn tất cả checkbox
        document.querySelectorAll('.chon-cau-hoi').forEach(function(cb) { cb.checked = false; });
    });

    // xử lý xóa câu hỏi
    document.getElementById('dsCauHoiDaChon').addEventListener('click', function(e) {
        if (e.target.closest('.btn-xoa-cau-hoi')) {
            var item = e.target.closest('.list-group-item');
            var id = item.getAttribute('data-id');
            cauHoiDaChon = cauHoiDaChon.filter(function(value) { return value != id; });
            item.remove();
            document.getElementById('soCauDaChon').textContent = cauHoiDaChon.length;
            document.querySelector('input[name="soCau"]').value = cauHoiDaChon.length;
        }
    });

    // validate form trước khi submit
    document.getElementById('formTaoDe').addEventListener('submit', function(e) {
        var isTuDong = document.getElementById('cheDo').checked;
        var soCau = isTuDong ? parseInt(document.querySelector('input[name="soCau"]').value) : cauHoiDaChon.length;
        if (!isTuDong && cauHoiDaChon.length == 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất một câu hỏi!');
            return false;
        }
        if (isTuDong) {
            if (soCau < 1) {
                e.preventDefault();
                alert('Số câu hỏi phải lớn hơn 0!');
                return false;
            }
            var tyLeDe = parseInt(document.querySelector('input[name="tyLeDe"]').value);
            var tyLeTrungBinh = parseInt(document.querySelector('input[name="tyLeTrungBinh"]').value);
            var tyLeKho = parseInt(document.querySelector('input[name="tyLeKho"]').value);
            var tongTyLe = tyLeDe + tyLeTrungBinh + tyLeKho;
            if (tongTyLe != 100) {
                e.preventDefault();
                alert('Tổng tỷ lệ độ khó phải bằng 100%!');
                return false;
            }
        }
        return true;
    });

    // Cho phép click vào hàng để chọn checkbox
    var table = document.getElementById('bangCauHoi');
    if (table) {
        table.querySelectorAll('tbody tr').forEach(function(row) {
            row.addEventListener('click', function(e) {
                // Không toggle nếu click vào input hoặc label
                if (e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'label') return;
                var checkbox = row.querySelector('.chon-cau-hoi');
                if (checkbox) checkbox.checked = !checkbox.checked;
            });
        });
    }

    // Thêm tìm kiếm, lọc, phân trang JS thuần cho bảng câu hỏi
    (function() {
        var table = document.getElementById('bangCauHoi');
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