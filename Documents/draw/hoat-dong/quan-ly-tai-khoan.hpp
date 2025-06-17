@startuml
|Quản trị viên|
start
:Đăng nhập vào hệ thống;
:Chọn chức năng "Quản lý tài khoản";

if (Muốn thêm tài khoản?) then (Có)
    :Chọn "Thêm tài khoản";
    |Hệ thống|
    :Hiển thị form nhập thông tin;

    |Quản trị viên|
    :Nhập đầy đủ thông tin;
    :Nhấn nút "Thêm";

    |Hệ thống|
    :Kiểm tra hợp lệ và tên đăng nhập;

    if (Hợp lệ?) then (Không)
        :Hiển thị lỗi;
        :Trở lại form nhập;
    else (Có)
        :Lưu vào CSDL;
        :Thông báo "Thêm thành công";
    endif
endif

|Quản trị viên|
if (Muốn sửa tài khoản?) then (Có)
    :Chọn tài khoản cần sửa;
    
    |Hệ thống|
    :Hiển thị thông tin tài khoản;

    |Quản trị viên|
    :Cập nhật thông tin;
    :Nhấn "Lưu thay đổi";

    |Hệ thống|
    :Kiểm tra dữ liệu;

    if (Hợp lệ?) then (Không)
        :Hiển thị lỗi;
        :Trở lại chỉnh sửa;
    else (Có)
        :Cập nhật thông tin;
        :Thông báo "Cập nhật thành công";
    endif
endif

|Quản trị viên|
if (Muốn xoá tài khoản?) then (Có)
    :Chọn tài khoản cần xoá;
    :Xác nhận xoá;

    |Hệ thống|
    :Kiểm tra điều kiện xoá;

    if (Được xoá?) then (Không)
        :Hiển thị lỗi;
    else (Có)
        :Xoá khỏi hệ thống;
        :Thông báo "Xoá thành công";
    endif
endif

|Quản trị viên|
stop
@enduml
