<?php
require_once 'include/config.php';
include 'include/layouts/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /dang-nhap.php');
    exit;
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title">Xin chào, <?php echo htmlspecialchars($_SESSION['ho_ten']); ?>!</h3>
                    <p class="card-text">Chào mừng bạn đến với hệ thống quản lý đề thi và ngân hàng câu hỏi EduDexQ.</p>
                </div>
            </div>
            
            <?php if ($_SESSION['vai_tro'] == 'admin'): ?>
            <div class="row mt-4">
                <div class="col-md-4 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Quản lý tài khoản</h5>
                                    <p class="card-text">Thêm, sửa, xóa tài khoản</p>
                                </div>
                                <i class="fas fa-users fa-3x"></i>
                            </div>
                            <a href="/quan-ly-tai-khoan" class="btn btn-outline-light mt-2">Truy cập</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Quản lý khoa</h5>
                                    <p class="card-text">Thêm, sửa, xóa khoa</p>
                                </div>
                                <i class="fas fa-university fa-3x"></i>
                            </div>
                            <a href="/quan-ly-khoa" class="btn btn-outline-light mt-2">Truy cập</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Quản lý ngành</h5>
                                    <p class="card-text">Thêm, sửa, xóa ngành</p>
                                </div>
                                <i class="fas fa-graduation-cap fa-3x"></i>
                            </div>
                            <a href="/quan-ly-nganh" class="btn btn-outline-light mt-2">Truy cập</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
            <div class="col-md-4 mb-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Quản lý môn học</h5>
                                    <p class="card-text">Thêm, sửa, xóa môn học</p>
                                </div>
                                <i class="fas fa-file-alt fa-3x"></i>
                            </div>
                            <a href="/quan-ly-mon-hoc" class="btn btn-outline-light mt-2">Truy cập</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
                <div class="col-md-4 mb-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Quản lý câu hỏi</h5>
                                    <p class="card-text">Thêm, sửa, xóa câu hỏi</p>
                                </div>
                                <i class="fas fa-question-circle fa-3x"></i>
                            </div>
                            <a href="/ngan-hang-cau-hoi" class="btn btn-outline-dark mt-2">Truy cập</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Quản lý kỳ thi</h5>
                                    <p class="card-text">Thêm, sửa, xóa kỳ thi</p>
                                </div>
                                <i class="fas fa-calendar-alt fa-3x"></i>
                            </div>
                            <a href="/quan-ly-ky-thi" class="btn btn-outline-light mt-2">Truy cập</a>
                        </div>
                    </div>
                </div>                
            </div>
            </div>
    </div>
</div>

<?php include 'include/layouts/footer.php'; ?>
