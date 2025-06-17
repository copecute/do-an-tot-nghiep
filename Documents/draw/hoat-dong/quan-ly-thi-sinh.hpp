@startuml
|Người dùng|
start
:Đăng nhập hệ thống;
:Chọn chức năng "Quản lý kỳ thi";
:Chọn kỳ thi cụ thể;
:chọn chức năng "quản lý thí sinh";

'=== THÊM THÍ SINH ===
if (Muốn thêm thí sinh?) then (Có)
    :Chọn “Thêm thí sinh”;

    |Hệ thống|
    :Hiển thị form nhập thông tin;

    |Người dùng|
    :Nhập mã sinh viên, họ tên;
    :Nhấn "Lưu";

    |Hệ thống|
    :Kiểm tra trùng mã SV và dữ liệu;

    if (Mã SV trùng?) then (Có)
        :Thông báo lỗi;
        :Quay lại form;
    else
        if (Thiếu thông tin?) then (Có)
            :Thông báo lỗi;
            :Quay lại form;
        else
            :Tạo thí sinh + sinh số báo danh;
            :Hiển thị "Thêm thành công";
        endif
    endif
endif

'=== SỬA THÔNG TIN THÍ SINH ===
|Người dùng|
if (Muốn sửa thí sinh?) then (Có)
    :Chọn thí sinh để sửa;

    |Hệ thống|
    :Hiển thị form thông tin thí sinh;

    |Người dùng|
    :Cập nhật họ tên (không sửa mã SV);
    :Nhấn "Lưu";

    |Hệ thống|
    :Kiểm tra dữ liệu;

    if (Dữ liệu không hợp lệ?) then (Có)
        :Hiển thị lỗi: "Thông tin sai/thiếu";
        :Quay lại form;
    else
        :Cập nhật dữ liệu;
        :Hiển thị "Cập nhật thành công";
    endif
endif

'=== XOÁ THÍ SINH ===
|Người dùng|
if (Muốn xoá thí sinh?) then (Có)
    :Chọn thí sinh để xoá;

    |Hệ thống|
    :Hiển thị hộp xác nhận;

    |Người dùng|
    if (Xác nhận xoá?) then (Có)
        |Hệ thống|
        if (Đã làm bài thi?) then (Có)
            :Hiển thị lỗi: "Không thể xoá";
        else
            :Xoá khỏi danh sách;
            :Hiển thị "Xoá thành công";
        endif
    else (Không)
        :Huỷ thao tác xoá;
    endif
endif

'=== NHẬP/XUẤT THÍ SINH ===
|Người dùng|
if (Muốn nhập/xuất thí sinh?) then (Có)
    :Chọn “Nhập/Xuất danh sách”;

    |Hệ thống|
    :Hiển thị tùy chọn Nhập / Xuất;

    if (Chọn Nhập?) then (Có)
        :Tải lên file CSV/Excel;

        |Hệ thống|
        :Kiểm tra định dạng và dữ liệu;

        if (Lỗi định dạng / trùng mã?) then (Có)
            :Hiển thị lỗi và dòng lỗi;
        else
            :Nhập thành công + sinh SBD;
            :Hiển thị "Nhập thành công";
        endif
    else
        :Tạo file danh sách;
        :Cung cấp link tải;
        :Hiển thị "Xuất thành công";
    endif
endif

|Người dùng|
stop
@enduml
