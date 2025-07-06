@startuml
|Quản trị viên|
start
:Đăng nhập vào hệ thống;
:Chọn "Quản lý tài khoản";
:Chọn chức năng "Nhập Excel";
:Chọn file và nhấn "Nhập";

|Hệ thống|
:Kiểm tra định dạng và dữ liệu;

if (Hợp lệ?) then (Không)
    :Hiển thị lỗi;
else (Có)
    :Lưu dữ liệu vào CSDL;
    :Hiển thị "Nhập thành công";
endif

stop
@enduml
