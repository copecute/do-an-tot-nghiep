@startuml
|Người dùng|
start
:Đăng nhập vào hệ thống;
:Chọn "Quản lý tài khoản";
|Hệ thống|
:Hiển thị giao diện quản lý tài khoản;
|Người dùng|
:Chọn tài khoản cần xoá;
:Nhấn "Xoá";

|Hệ thống|
:Hiển thị xác nhận xoá;

|Người dùng|
:Chọn "Xác nhận";

|Hệ thống|
:Kiểm tra điều kiện xoá;

if (Được xoá?) then (Không)
    :Hiển thị lỗi không thể xoá;
else (Có)
    :Xoá khỏi CSDL;
    :Thông báo "Xoá thành công";
endif

stop
@enduml
