@startuml
actor "Người dùng" as User
boundary "Giao diện" as UI
control "Hệ thống" as System
database "Cơ sở dữ liệu" as DB

User -> UI: Nhập thông tin đăng nhập
UI -> System: Gửi thông tin đăng nhập
System -> DB: Kiểm tra tài khoản
DB --> System: Trả về kết quả xác minh

alt Đăng nhập thành công
    System -> UI: Đăng nhập thành công
    UI -> User: Chuyển đến trang chính
else Đăng nhập không thành công
    System -> UI: Thông báo lỗi
    UI -> User: Hiển thị lỗi đăng nhập
end

User -> UI: Chọn "Đổi mật khẩu"
UI -> System: Gửi mật khẩu cũ và mới
System -> DB: Xác thực mật khẩu cũ

alt Mật khẩu đúng
    System -> DB: Cập nhật mật khẩu mới
    DB --> System: Xác nhận cập nhật thành công
    System -> UI: Thông báo đổi mật khẩu thành công
    UI -> User: Hiển thị thành công
else Mật khẩu sai
    System -> UI: Thông báo lỗi
    UI -> User: Hiển thị lỗi
end

User -> UI: Chọn "Đăng xuất"
UI -> System: Gửi yêu cầu đăng xuất
System -> UI: Xác nhận đăng xuất
UI -> User: Quay lại trang đăng nhập
@enduml
