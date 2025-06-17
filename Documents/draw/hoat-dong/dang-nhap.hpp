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

|Người dùng|
if (Muốn đổi mật khẩu?) then (Có)
    :Chọn "Đổi mật khẩu" từ menu người dùng;
    :Nhập mật khẩu cũ và mật khẩu mới;
    :Nhấn "Lưu thay đổi";
    
    |Hệ thống|
    :Kiểm tra mật khẩu cũ và hợp lệ của mật khẩu mới;
    if (Hợp lệ?) then (Không)
        :Thông báo lỗi;
        :Quay lại form đổi mật khẩu;
    else (Có)
        :Cập nhật mật khẩu mới;
        :Thông báo thành công;
    endif
endif

|Người dùng|
if (Muốn đăng xuất?) then (Có)
    :Chọn "Đăng xuất" từ menu người dùng;

    |Hệ thống|
    :Xóa phiên làm việc;
    :Chuyển về màn hình đăng nhập;
endif

|Người dùng|
stop
@enduml
