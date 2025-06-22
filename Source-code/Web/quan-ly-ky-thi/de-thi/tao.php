<?php
require_once '../../include/config.php';

// kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để truy cập trang này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra id kỳ thi
if (!isset($_GET['kyThiId']) || empty($_GET['kyThiId'])) {
    $_SESSION['flash_message'] = 'id kỳ thi không hợp lệ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

$kyThiId = $_GET['kyThiId'];

try {
    // lấy thông tin kỳ thi
    $stmt = $pdo->prepare('
        SELECT k.*, m.tenMonHoc 
        FROM kyThi k 
        JOIN monHoc m ON k.monHocId = m.id 
        WHERE k.id = ? AND k.nguoiTaoId = ?
    ');
    $stmt->execute([$kyThiId, $_SESSION['user_id']]);
    $kyThi = $stmt->fetch();

    if (!$kyThi) {
        $_SESSION['flash_message'] = 'không tìm thấy kỳ thi!';
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
    $error = 'lỗi: ' . $e->getMessage();
}

include '../../include/layouts/header.php';
?>

<!-- thêm css của datatables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<!-- thêm jquery và datatables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<nav aria-label="breadcrumb" class="mx-4 my-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi">Quản lý kỳ thi</a></li>
        <li class="breadcrumb-item"><a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $kyThiId; ?>">Quản lý đề thi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tạo đề thi</li>
    </ol>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">tạo đề thi mới</h5>
                        <p class="text-muted mb-0">
                            kỳ thi: <?php echo htmlspecialchars($kyThi['tenKyThi']); ?> | 
                            môn học: <?php echo htmlspecialchars($kyThi['tenMonHoc']); ?>
                        </p>
                    </div>
                    <a href="/quan-ly-ky-thi/de-thi/?kyThiId=<?php echo $kyThiId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> quay lại
                    </a>
                </div>
                <div class="card-body">
                    <form id="formTaoDe" method="POST" action="xu-ly-tao.php">
                        <input type="hidden" name="kyThiId" value="<?php echo $kyThiId; ?>">
                        
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">tên đề thi:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="tenDeThi" required
                                    placeholder="nhập tên đề thi...">
                            </div>
                        </div>

                        <div class="row mb-3" id="soCauInput">
                            <label class="col-sm-3 col-form-label">số câu hỏi:</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="soCau" required min="1"
                                    placeholder="nhập số câu hỏi...">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">thời gian làm bài (phút):</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="thoiGian" required min="1"
                                    placeholder="nhập thời gian làm bài...">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label">chế độ tạo đề:</label>
                            <div class="col-sm-9">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="cheDo" name="isTuDong" checked>
                                    <label class="form-check-label" for="cheDo">
                                        tạo đề tự động
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- phần cấu hình tự động -->
                        <div id="cauHinhTuDong">
                            <h6 class="mb-3">cấu hình tạo đề tự động:</h6>
                            
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">tỷ lệ độ khó:</label>
                                <div class="col-sm-9">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">dễ (%):</label>
                                            <input type="number" class="form-control" name="tyLeDe" value="30" min="0" max="100">
                                            <small class="text-muted">có sẵn: <?php echo $thongKeCauHoi['de'] ?? 0; ?> câu</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">trung bình (%):</label>
                                            <input type="number" class="form-control" name="tyLeTrungBinh" value="40" min="0" max="100">
                                            <small class="text-muted">có sẵn: <?php echo $thongKeCauHoi['trungbinh'] ?? 0; ?> câu</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">khó (%):</label>
                                            <input type="number" class="form-control" name="tyLeKho" value="30" min="0" max="100">
                                            <small class="text-muted">có sẵn: <?php echo $thongKeCauHoi['kho'] ?? 0; ?> câu</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($dsTheLoai)): ?>
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">tỷ lệ thể loại:</label>
                                <div class="col-sm-9">
                                    <div class="row">
                                        <?php foreach ($dsTheLoai as $theLoai): ?>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label"><?php echo htmlspecialchars((string) $theLoai['tenTheLoai'] ?? ''); ?> (%):</label>
                                            <input type="number" class="form-control" 
                                                name="tyLeTheLoai[<?php echo $theLoai['id']; ?>]" value="0" min="0" max="100">
                                            <small class="text-muted">có sẵn: <?php echo $theLoai['soCau']; ?> câu</small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- phần chọn thủ công -->
                        <div id="chonThuCong" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">danh sách câu hỏi đã chọn: <span id="soCauDaChon">0</span></h6>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalChonCauHoi">
                                    <i class="fas fa-plus"></i> chọn câu hỏi
                                </button>
                            </div>
                            <div id="dsCauHoiDaChon" class="list-group">
                                <!-- danh sách câu hỏi đã chọn sẽ được thêm vào đây bằng javascript -->
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> tạo đề thi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal chọn câu hỏi -->
<div class="modal fade" id="modalChonCauHoi" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">chọn câu hỏi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="bangCauHoi">
                        <thead>
                            <tr>
                                <th style="width: 50px;">chọn</th>
                                <th>nội dung</th>
                                <th>thể loại</th>
                                <th>độ khó</th>
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
                                <td><?php echo htmlspecialchars($cauHoi['doKho']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">đóng</button>
                <button type="button" class="btn btn-primary" id="btnXacNhanChon">xác nhận</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // khởi tạo DataTable
    var table = $('#bangCauHoi').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json',
        },
        pageLength: 10,
        order: [[1, 'asc']]
    });

    // xử lý chuyển đổi chế độ tạo đề
    function toggleCheDo() {
        var isTuDong = $('#cheDo').is(':checked');
        if (isTuDong) {
            $('#cauHinhTuDong').slideDown();
            $('#chonThuCong').slideUp();
            $('#soCauInput').slideDown();
            $('input[name="soCau"]').prop('required', true);
        } else {
            $('#cauHinhTuDong').slideUp();
            $('#chonThuCong').slideDown();
            $('#soCauInput').slideUp();
            $('input[name="soCau"]').prop('required', false);
        }
    }

    // xử lý khi thay đổi switch
    $('#cheDo').change(toggleCheDo);
    
    // xử lý khi trang mới load
    toggleCheDo();

    // mảng lưu các câu hỏi đã chọn
    var cauHoiDaChon = [];

    // xử lý nút xác nhận chọn câu hỏi
    $('#btnXacNhanChon').click(function() {
        // lấy các câu hỏi được chọn
        $('.chon-cau-hoi:checked').each(function() {
            var id = $(this).val();
            var noiDung = $(this).data('noidung');
            
            // kiểm tra câu hỏi đã được chọn chưa
            if (!cauHoiDaChon.includes(id)) {
                cauHoiDaChon.push(id);
                
                // thêm câu hỏi vào danh sách hiển thị
                $('#dsCauHoiDaChon').append(`
                    <div class="list-group-item d-flex justify-content-between align-items-center" data-id="${id}">
                        <span>${noiDung}</span>
                        <button type="button" class="btn btn-sm btn-danger btn-xoa-cau-hoi">
                            <i class="fas fa-times"></i>
                        </button>
                        <input type="hidden" name="dsCauHoi[]" value="${id}">
                    </div>
                `);
            }
        });

        // cập nhật số câu đã chọn
        $('#soCauDaChon').text(cauHoiDaChon.length);
        $('input[name="soCau"]').val(cauHoiDaChon.length);
        
        // đóng modal
        $('#modalChonCauHoi').modal('hide');
        
        // bỏ chọn tất cả checkbox
        $('.chon-cau-hoi').prop('checked', false);
    });

    // xử lý xóa câu hỏi
    $(document).on('click', '.btn-xoa-cau-hoi', function() {
        var item = $(this).closest('.list-group-item');
        var id = item.data('id');
        
        // xóa khỏi mảng
        cauHoiDaChon = cauHoiDaChon.filter(function(value) {
            return value != id;
        });
        
        // xóa khỏi giao diện
        item.remove();
        
        // cập nhật số câu đã chọn
        $('#soCauDaChon').text(cauHoiDaChon.length);
        $('input[name="soCau"]').val(cauHoiDaChon.length);
    });

    // validate form trước khi submit
    $('#formTaoDe').submit(function(e) {
        var isTuDong = $('#cheDo').is(':checked');
        var soCau = isTuDong ? parseInt($('input[name="soCau"]').val()) : cauHoiDaChon.length;
        
        if (!isTuDong && cauHoiDaChon.length == 0) {
            e.preventDefault();
            alert('vui lòng chọn ít nhất một câu hỏi!');
            return false;
        }
        
        if (isTuDong) {
            if (soCau < 1) {
                e.preventDefault();
                alert('số câu hỏi phải lớn hơn 0!');
                return false;
            }

            var tongTyLe = parseInt($('input[name="tyLeDe"]').val()) + 
                          parseInt($('input[name="tyLeTrungBinh"]').val()) + 
                          parseInt($('input[name="tyLeKho"]').val());
            
            if (tongTyLe != 100) {
                e.preventDefault();
                alert('tổng tỷ lệ độ khó phải bằng 100%!');
                return false;
            }
        }
        
        return true;
    });
});
</script>

<?php include '../../include/layouts/footer.php'; ?> 