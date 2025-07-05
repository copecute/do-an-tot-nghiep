<?php
//                       _oo0oo_
//                      o8888888o
//                      88" . "88
//                      (| -_- |)
//                      0\  =  /0
//                    ___/`---'\___
//                  .' \\|     |// '.
//                 / \\|||  :  |||// \
//                / _||||| -:- |||||- \
//               |   | \\\  -  /// |   |
//               | \_|  ''\---/''  |_/ |
//               \  .-\__  '-'  ___/-. /
//             ___'. .'  /--.--\  `. .'___
//          ."" '<  `.___\_<|>_/___.' >' "".
//         | | :  `- \`.;`\ _ /`;.`/ - ` : | |
//         \  \ `_.   \_ __\ /__ _/   .-` /  /
//     =====`-.____`.___ \_____/___.-`___.-'=====
//                       `=---='
//
//     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//            amen đà phật, không bao giờ BUG
//     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
require_once 'include/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /dang-nhap.php');
    exit;
}

include 'include/layouts/header.php';
?>

<style>
.card.card-overlayed {
    position: relative;
    overflow: hidden;
    border-radius: 5px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}
.card.card-overlayed .card-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.22);
    backdrop-filter: blur(2.5px);
    z-index: 1;
    border-radius: inherit;
}
.card.card-overlayed .card-content {
    position: relative;
    z-index: 2;
}
.card.card-overlayed .card-title,
.card.card-overlayed .card-text,
.card.card-overlayed .btn,
.card.card-overlayed i {
    color: #fff !important;
}
.card.card-overlayed .btn {
    border-color: #fff !important;
}
.card.card-overlayed .btn:hover {
    background: rgba(255,255,255,0.12);
    color: #fff !important;
}
</style>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="row mt-4 justify-content-center">
            <?php if ($_SESSION['vai_tro'] == 'admin'): ?>
                <div class="col-md-4 mb-3">
                    <div class="card card-overlayed bg-primary">
                        <div class="card-overlay"></div>
                        <div class="card-body card-content">
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
                    <div class="card card-overlayed bg-success">
                        <div class="card-overlay"></div>
                        <div class="card-body card-content">
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
                    <div class="card card-overlayed bg-info">
                        <div class="card-overlay"></div>
                        <div class="card-body card-content">
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
                <div class="col-md-4 mb-3">
                    <div class="card card-overlayed bg-secondary">
                        <div class="card-overlay"></div>
                        <div class="card-body card-content">
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
                <div class="card card-overlayed bg-warning">
                    <div class="card-overlay"></div>
                    <div class="card-body card-content">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Quản lý câu hỏi</h5>
                                <p class="card-text">Thêm, sửa, xóa câu hỏi</p>
                            </div>
                            <i class="fas fa-question-circle fa-3x"></i>
                        </div>
                        <a href="/ngan-hang-cau-hoi" class="btn btn-outline-light mt-2">Truy cập</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card card-overlayed bg-danger">
                    <div class="card-overlay"></div>
                    <div class="card-body card-content">
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

<?php include 'include/layouts/footer.php'; ?>
