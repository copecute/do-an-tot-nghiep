@startuml
|Quản trị viên|
start
:Đăng nhập vào hệ thống;
:Chọn "Quản lý tài khoản";
:Chọn "Thêm tài khoản";

|Hệ thống|
:Hiển thị form nhập thông tin;

|Quản trị viên|
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
