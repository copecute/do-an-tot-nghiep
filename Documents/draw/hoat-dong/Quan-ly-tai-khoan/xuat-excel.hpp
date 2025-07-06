@startuml
|Quản trị viên|
start
:Đăng nhập vào hệ thống;
:Chọn "Quản lý tài khoản";
:Nhấn nút "Xuất Excel";

|Hệ thống|
:Truy vấn danh sách tài khoản;
:Tạo file Excel;

|Quản trị viên|
:Tải file xuống;

stop
@enduml
