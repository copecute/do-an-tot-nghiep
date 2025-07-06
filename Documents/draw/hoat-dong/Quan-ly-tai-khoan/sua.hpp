@startuml
|Quản trị viên|
start
:Đăng nhập vào hệ thống;
:Chọn "Quản lý tài khoản";
:Chọn tài khoản cần sửa;

|Hệ thống|
:Hiển thị thông tin tài khoản;

|Quản trị viên|
:Chỉnh sửa thông tin;
:Nhấn "Lưu thay đổi";

|Hệ thống|
:Kiểm tra hợp lệ;

if (Hợp lệ?) then (Không)
    :Hiển thị lỗi;
    :Quay lại chỉnh sửa;
else (Có)
    :Cập nhật thông tin;
    :Thông báo "Cập nhật thành công";
endif

stop
@enduml
