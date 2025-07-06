@startuml
|Người dùng|
start
:Đăng nhập vào hệ thống;
:Chọn "Đổi mật khẩu" từ menu cá nhân;
:Nhập mật khẩu cũ và mật khẩu mới;
:Nhấn "Lưu thay đổi";

|Hệ thống|
:Kiểm tra mật khẩu cũ và định dạng mật khẩu mới;
if (Hợp lệ?) then (Không)
    :Hiển thị lỗi;
    :Quay lại form đổi mật khẩu;
else (Có)
    :Cập nhật mật khẩu mới;
    :Thông báo đổi mật khẩu thành công;
endif
stop
@enduml
