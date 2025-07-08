@startuml
|Người dùng|
start
:Truy cập vào hệ thống;

|Hệ thống|
:Hiển thị form đăng nhập;

|Người dùng|
:Nhập tên đăng nhập và mật khẩu;
:Nhấn nút "Đăng nhập";

|Hệ thống|
:Nhận thông tin đăng nhập;
:Kiểm tra thông tin trong CSDL;

if (Thông tin hợp lệ?) then (Không)
    :Hiển thị thông báo lỗi;
    :Giữ lại thông tin đã nhập;
    :Quay lại form đăng nhập;
else (Có)
    :Tạo phiên làm việc;
    :Chuyển đến giao diện chính;
endif
stop
@enduml
