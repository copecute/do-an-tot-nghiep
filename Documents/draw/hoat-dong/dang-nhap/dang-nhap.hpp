@startuml
|Người dùng|
start
:Truy cập vào hệ thống;
:Nhập tên đăng nhập và mật khẩu;
:Nhấn nút "Đăng nhập";

|Hệ thống|
:Kiểm tra thông tin đăng nhập;
if (Thông tin hợp lệ?) then (Không)
    :Hiển thị lỗi;
    :Trở về form đăng nhập;
else (Có)
    :Chuyển đến giao diện chính;
endif
stop
@enduml
