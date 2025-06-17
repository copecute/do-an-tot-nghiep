@startuml
|Quản trị viên|
start
:Đăng nhập vào hệ thống;
:Chọn chức năng "Quản lý khoa";

if (Muốn thêm khoa?) then (Có)
    :Chọn "Thêm khoa";
    
    |Hệ thống|
    :Hiển thị form nhập thông tin;

    |Quản trị viên|
    :Nhập tên khoa;
    :Nhấn nút "Thêm";

    |Hệ thống|
    :Kiểm tra tên khoa đã tồn tại?;

    if (Tên khoa trùng?) then (Có)
        :Hiển thị lỗi "Tên khoa đã tồn tại";
        :Yêu cầu nhập lại;
    else
        :Lưu vào CSDL;
        :Hiển thị "Thêm khoa thành công";
    endif
endif

|Quản trị viên|
if (Muốn sửa khoa?) then (Có)
    :Chọn khoa cần sửa;

    |Hệ thống|
    :Hiển thị thông tin khoa;

    |Quản trị viên|
    :Cập nhật thông tin;
    :Nhấn "Lưu thay đổi";

    |Hệ thống|
    :Kiểm tra tính hợp lệ và trùng tên;

    if (Không hợp lệ hoặc tên trùng?) then (Có)
        :Hiển thị lỗi;
        :Yêu cầu chỉnh sửa lại;
    else
        :Cập nhật dữ liệu;
        :Thông báo "Cập nhật thành công";
    endif
endif

|Quản trị viên|
if (Muốn xoá khoa?) then (Có)
    :Chọn khoa cần xoá;
    :Xác nhận "Xóa";

    |Hệ thống|
    :Kiểm tra ràng buộc dữ liệu;

    if (Có ngành liên kết?) then (Có)
        :Hiển thị lỗi "Không thể xoá";
    else
        :Xoá khoa;
        :Thông báo "Xoá thành công";
    endif
endif

|Quản trị viên|
stop
@enduml
