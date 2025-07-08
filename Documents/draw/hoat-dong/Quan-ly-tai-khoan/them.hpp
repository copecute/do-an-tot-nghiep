@startuml
|Người dùng|
start
:Đăng nhập vào hệ thống;
:Chọn "Quản lý tài khoản";
|Hệ thống|
:Hiển thị giao diện quản lý tài khoản;
|Người dùng|
:Chọn "Thêm tài khoản";

|Hệ thống|
:Hiển thị form nhập thông tin;

|Người dùng|
:Nhập thông tin tài khoản;
:Nhấn "Thêm";

|Hệ thống|
:Kiểm tra hợp lệ và tên đăng nhập;

if (Hợp lệ?) then (Không)
    :Hiển thị lỗi;
    :Quay lại form nhập;
else (Có)
    :Lưu vào CSDL;
    :Thông báo "Thêm thành công";
endif
stop

@enduml
