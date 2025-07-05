<?php
require_once '../../include/config.php';

// kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'bạn cần đăng nhập để thực hiện thao tác này!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /dang-nhap.php');
    exit;
}

// kiểm tra phương thức gửi form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = 'phương thức không được hỗ trợ!';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /quan-ly-ky-thi');
    exit;
}

try {
    // lấy dữ liệu từ form
    $kyThiId = $_POST['kyThiId'] ?? '';
    $tenDeThi = $_POST['tenDeThi'] ?? '';
    $soCau = intval($_POST['soCau'] ?? 0);
    $thoiGian = intval($_POST['thoiGian'] ?? 0);
    $isTuDong = isset($_POST['isTuDong']);

    // validate dữ liệu
    if (empty($kyThiId) || empty($tenDeThi) || $soCau < 1 || $thoiGian < 1) {
        throw new Exception('vui lòng điền đầy đủ thông tin!');
    }

    // kiểm tra kỳ thi tồn tại và thuộc về người dùng
    $isAdmin = isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';
    if ($isAdmin) {
        $stmt = $pdo->prepare('SELECT k.*, m.id as monHocId FROM kyThi k JOIN monHoc m ON k.monHocId = m.id WHERE k.id = ?');
        $stmt->execute([$kyThiId]);
    } else {
        $stmt = $pdo->prepare('SELECT k.*, m.id as monHocId FROM kyThi k JOIN monHoc m ON k.monHocId = m.id WHERE k.id = ? AND k.nguoiTaoId = ?');
    $stmt->execute([$kyThiId, $_SESSION['user_id']]);
    }
    $kyThi = $stmt->fetch();

    if (!$kyThi) {
        throw new Exception('không tìm thấy kỳ thi!');
    }

    // kiểm tra tổng số câu hỏi có sẵn
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM cauHoi WHERE monHocId = ?');
    $stmt->execute([$kyThi['monHocId']]);
    $tongCauHoi = $stmt->fetchColumn();

    if ($tongCauHoi < $soCau) {
        throw new Exception("không đủ câu hỏi! (có sẵn: $tongCauHoi câu, yêu cầu: $soCau câu)");
    }

    // bắt đầu transaction
    $pdo->beginTransaction();

    // khởi tạo các biến cho đề thi
    $tyLeDe = 0;
    $tyLeTrungBinh = 0;
    $tyLeKho = 0;
    $cauHinhTheLoai = null;

    // xử lý theo chế độ tạo đề
    if ($isTuDong) {
        // lấy tỷ lệ độ khó
        $tyLeDe = intval($_POST['tyLeDe'] ?? 0);
        $tyLeTrungBinh = intval($_POST['tyLeTrungBinh'] ?? 0);
        $tyLeKho = intval($_POST['tyLeKho'] ?? 0);

        // validate tỷ lệ độ khó
        if ($tyLeDe + $tyLeTrungBinh + $tyLeKho !== 100) {
            throw new Exception('tổng tỷ lệ độ khó phải bằng 100%!');
        }

        // kiểm tra số câu hỏi theo độ khó
        $stmt = $pdo->prepare('
            SELECT doKho, COUNT(*) as soCau 
            FROM cauHoi 
            WHERE monHocId = ? 
            GROUP BY doKho
        ');
        $stmt->execute([$kyThi['monHocId']]);
        $thongKeCauHoi = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // tính số câu hỏi cần cho mỗi độ khó
        $soCauDe = round($soCau * $tyLeDe / 100);
        $soCauTrungBinh = round($soCau * $tyLeTrungBinh / 100);
        $soCauKho = $soCau - $soCauDe - $soCauTrungBinh;

        // kiểm tra số câu hỏi có sẵn theo độ khó
        if ($soCauDe > 0 && ($thongKeCauHoi['de'] ?? 0) < $soCauDe) {
            throw new Exception("không đủ câu hỏi độ khó dễ! (có sẵn: {$thongKeCauHoi['de']} câu, yêu cầu: $soCauDe câu)");
        }
        if ($soCauTrungBinh > 0 && ($thongKeCauHoi['trungbinh'] ?? 0) < $soCauTrungBinh) {
            throw new Exception("không đủ câu hỏi độ khó trung bình! (có sẵn: {$thongKeCauHoi['trungbinh']} câu, yêu cầu: $soCauTrungBinh câu)");
        }
        if ($soCauKho > 0 && ($thongKeCauHoi['kho'] ?? 0) < $soCauKho) {
            throw new Exception("không đủ câu hỏi độ khó khó! (có sẵn: {$thongKeCauHoi['kho']} câu, yêu cầu: $soCauKho câu)");
        }

        // lấy câu hỏi theo độ khó
        $dsCauHoi = [];

        if ($soCauDe > 0) {
            $stmt = $pdo->prepare('
                SELECT id FROM cauHoi 
                WHERE monHocId = :monHocId AND doKho = :doKho
                ORDER BY RAND() LIMIT :limit
            ');
            $stmt->bindValue(':monHocId', $kyThi['monHocId'], PDO::PARAM_INT);
            $stmt->bindValue(':doKho', 'de', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $soCauDe, PDO::PARAM_INT);
            $stmt->execute();
            $dsCauHoi = array_merge($dsCauHoi, $stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        if ($soCauTrungBinh > 0) {
            $stmt = $pdo->prepare('
                SELECT id FROM cauHoi 
                WHERE monHocId = :monHocId AND doKho = :doKho
                ORDER BY RAND() LIMIT :limit
            ');
            $stmt->bindValue(':monHocId', $kyThi['monHocId'], PDO::PARAM_INT);
            $stmt->bindValue(':doKho', 'trungbinh', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $soCauTrungBinh, PDO::PARAM_INT);
            $stmt->execute();
            $dsCauHoi = array_merge($dsCauHoi, $stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        if ($soCauKho > 0) {
            $stmt = $pdo->prepare('
                SELECT id FROM cauHoi 
                WHERE monHocId = :monHocId AND doKho = :doKho
                ORDER BY RAND() LIMIT :limit
            ');
            $stmt->bindValue(':monHocId', $kyThi['monHocId'], PDO::PARAM_INT);
            $stmt->bindValue(':doKho', 'kho', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $soCauKho, PDO::PARAM_INT);
            $stmt->execute();
            $dsCauHoi = array_merge($dsCauHoi, $stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        // xử lý tỷ lệ thể loại nếu có
        if (isset($_POST['tyLeTheLoai']) && is_array($_POST['tyLeTheLoai'])) {
            $tyLeTheLoai = $_POST['tyLeTheLoai'];
            $tongTyLe = array_sum($tyLeTheLoai);

            if ($tongTyLe > 0) {
                // kiểm tra số câu hỏi theo thể loại
                $stmt = $pdo->prepare('
                    SELECT t.id, COUNT(c.id) as soCau
                    FROM theLoaiCauHoi t
                    LEFT JOIN cauHoi c ON c.theLoaiId = t.id
                    WHERE t.monHocId = ?
                    GROUP BY t.id
                ');
                $stmt->execute([$kyThi['monHocId']]);
                $thongKeTheLoai = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                // kiểm tra từng thể loại
                foreach ($tyLeTheLoai as $theLoaiId => $tyLe) {
                    if ($tyLe > 0) {
                        $soCauTheLoai = round($soCau * $tyLe / 100);
                        if ($soCauTheLoai > 0 && ($thongKeTheLoai[$theLoaiId] ?? 0) < $soCauTheLoai) {
                            throw new Exception("không đủ câu hỏi cho thể loại! (có sẵn: {$thongKeTheLoai[$theLoaiId]} câu, yêu cầu: $soCauTheLoai câu)");
                        }
                    }
                }

                // lưu cấu hình thể loại
                $cauHinhTheLoai = json_encode($tyLeTheLoai);
                
                // lấy câu hỏi theo thể loại
                $dsCauHoiTheoTheLoai = [];
                
                foreach ($tyLeTheLoai as $theLoaiId => $tyLe) {
                    if ($tyLe > 0) {
                        $soCauTheLoai = round($soCau * $tyLe / 100);
                        
                        if ($soCauTheLoai > 0) {
                            $stmt = $pdo->prepare('
                                SELECT id FROM cauHoi 
                                WHERE monHocId = ? AND theLoaiId = ? 
                                ORDER BY RAND() LIMIT ?
                            ');
                            $stmt->execute([$kyThi['monHocId'], $theLoaiId, $soCauTheLoai]);
                            $dsCauHoiTheoTheLoai = array_merge(
                                $dsCauHoiTheoTheLoai, 
                                $stmt->fetchAll(PDO::FETCH_COLUMN)
                            );
                        }
                    }
                }

                // bổ sung câu hỏi còn thiếu
                if (count($dsCauHoiTheoTheLoai) > 0 && count($dsCauHoiTheoTheLoai) < $soCau) {
                    $soCauThieu = $soCau - count($dsCauHoiTheoTheLoai);
                    
                    if (!empty($dsCauHoiTheoTheLoai)) {
                        $placeholders = implode(',', array_fill(0, count($dsCauHoiTheoTheLoai), '?'));
                        $sql = "
                            SELECT id FROM cauHoi 
                            WHERE monHocId = ? AND id NOT IN ($placeholders)
                            ORDER BY RAND() LIMIT ?
                        ";
                        $params = array_merge(
                            [$kyThi['monHocId']], 
                            array_map('intval', $dsCauHoiTheoTheLoai),
                            [$soCauThieu]
                        );

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $dsCauHoiTheoTheLoai = array_merge(
                            $dsCauHoiTheoTheLoai,
                            $stmt->fetchAll(PDO::FETCH_COLUMN)
                        );
                    }
                }

                // sử dụng danh sách câu hỏi theo thể loại
                if (count($dsCauHoiTheoTheLoai) === $soCau) {
                    $dsCauHoi = $dsCauHoiTheoTheLoai;
                }
            }
        }
    } else {
        // lấy danh sách câu hỏi được chọn
        if (!isset($_POST['dsCauHoi']) || !is_array($_POST['dsCauHoi'])) {
            throw new Exception('vui lòng chọn câu hỏi!');
        }

        $dsCauHoi = $_POST['dsCauHoi'];

        // kiểm tra số lượng câu hỏi
        if (count($dsCauHoi) !== $soCau) {
            throw new Exception('số câu hỏi đã chọn không khớp với số câu của đề thi!');
        }

        // kiểm tra câu hỏi thuộc môn học
        if (!empty($dsCauHoi)) {
            $placeholders = implode(',', array_fill(0, count($dsCauHoi), '?'));
            $sql = "
                SELECT COUNT(*) FROM cauHoi 
                WHERE id IN ($placeholders)
                AND monHocId = ?
            ";
            $params = array_merge(
                array_map('intval', $dsCauHoi),
                [$kyThi['monHocId']]
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetchColumn() !== count($dsCauHoi)) {
                throw new Exception('có câu hỏi không thuộc môn học này!');
            }
        } else {
            throw new Exception('vui lòng chọn câu hỏi!');
        }
    }

    // tạo đề thi mới
    $stmt = $pdo->prepare('
        INSERT INTO deThi (
            kyThiId, tenDeThi, soCau, thoiGian, nguoiTaoId,
            isTuDong, tyLeDe, tyLeTrungBinh, tyLeKho, cauHinhTheLoai
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $kyThiId,
        $tenDeThi,
        $soCau,
        $thoiGian,
        $_SESSION['user_id'],
        $isTuDong ? 1 : 0,
        $tyLeDe,
        $tyLeTrungBinh,
        $tyLeKho,
        $cauHinhTheLoai
    ]);
    $deThiId = $pdo->lastInsertId();

    // thêm câu hỏi vào đề thi
    $stmt = $pdo->prepare('
        INSERT INTO deThiCauHoi (deThiId, cauHoiId) 
        VALUES (?, ?)
    ');

    foreach ($dsCauHoi as $cauHoiId) {
        $stmt->execute([$deThiId, $cauHoiId]);
    }

    // lưu thay đổi
    $pdo->commit();

    $_SESSION['flash_message'] = 'tạo đề thi thành công!';
    $_SESSION['flash_type'] = 'success';
    header("Location: /quan-ly-ky-thi/de-thi/?kyThiId=$kyThiId");
    exit;

} catch (Exception $e) {
    // hoàn tác thay đổi nếu có lỗi
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['flash_message'] = 'lỗi: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header("Location: /quan-ly-ky-thi/de-thi/tao.php?kyThiId=$kyThiId");
    exit;
} 