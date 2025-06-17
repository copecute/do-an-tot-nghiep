@startuml
|Người dùng|
start
:Đăng nhập hệ thống;
:Chọn chức năng "Quản lý kỳ thi";

if (Muốn thêm kỳ thi?) then (Có)
    :Chọn "Thêm kỳ thi";

    |Hệ thống|
    :Hiển thị form nhập thông tin;

    |Người dùng|
    :Nhập tên, môn, thời gian;
    :Nhấn "Thêm kỳ thi";

    |Hệ thống|
    :Kiểm tra hợp lệ và trùng lịch;
    if (Hợp lệ?) then (Không)
        :Hiển thị lỗi: thiếu/trùng lịch;
        :Quay lại form;
    else (Có)
        :Lưu dữ liệu;
        :Hiển thị "Tạo thành công";
    endif
endif
|Người dùng|
if (Muốn sửa kỳ thi?) then (Có)
    :Chọn kỳ thi để sửa;

    |Hệ thống|
    :Hiển thị thông tin kỳ thi;

    |Người dùng|
    :Cập nhật thông tin;
    :Nhấn "Lưu thay đổi";

    |Hệ thống|
    if (Đã có thí sinh?) then (Có)
        :Hiển thị lỗi: Không thể sửa;
    else (Chưa)
        :Kiểm tra dữ liệu;
        if (Hợp lệ?) then (Không)
            :Hiển thị lỗi nhập sai/thiếu;
        else (Có)
            :Cập nhật dữ liệu;
            :Hiển thị "Cập nhật thành công";
        endif
    endif
endif
|Người dùng|
if (Muốn xoá kỳ thi?) then (Có)
    :Chọn kỳ thi để xoá;

    |Hệ thống|
    :Hiển thị hộp xác nhận;

    |Người dùng|
    if (Xác nhận xóa?) then (Có)
        |Hệ thống|
        if (Đã có thí sinh?) then (Có)
            :Thông báo lỗi;
        else (Chưa)
            :Xoá khỏi CSDL;
            :Hiển thị "Xóa thành công";
        endif
    else (Không)
        :Hủy thao tác xoá;
    endif
endif
|Người dùng|
stop
@enduml
