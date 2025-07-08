@startuml
|Người dùng|
start
:Đăng nhập vào hệ thống;
:Chọn "Quản lý tài khoản";
|Hệ thống|
:Hiển thị giao diện quản lý tài khoản;
|Người dùng|
:Nhấn nút "Xuất Excel";

|Hệ thống|
:Truy vấn danh sách tài khoản;
:Tạo file Excel;

|Người dùng|
:Tải file xuống;

stop
@enduml
