@startuml
|Người dùng|
start
:Chọn chức năng Đăng nhập;

|Hệ thống|
:Hiển thị form đăng nhập;

|Người dùng|
:Nhập tên đăng nhập và mật khẩu;

|Hệ thống|
:Kiểm tra thông tin đăng nhập;
if (Thông tin hợp lệ?) then (Có)
    :Chuyển đến trang chính;
else (Không)
    :Thông báo lỗi;
    stop
endif

|Người dùng|
if (Muốn đổi thông tin cá nhân?) then (Có)
    :Nhập thông tin mới;
    |Hệ thống|
    :Kiểm tra tính hợp lệ;
    if (Hợp lệ?) then (Có)
        :Cập nhật thông tin;
        :Thông báo thành công;
    else (Không)
        :Thông báo lỗi;
    endif
endif

|Người dùng|
if (Muốn đổi mật khẩu?) then (Có)
    :Nhập mật khẩu cũ và mật khẩu mới;
    |Hệ thống|
    :Xác thực mật khẩu cũ;
    if (Mật khẩu đúng?) then (Có)
        :Cập nhật mật khẩu;
        :Thông báo thành công;
    else (Không)
        :Thông báo lỗi;
    endif
endif

|Người dùng|
stop
@enduml
