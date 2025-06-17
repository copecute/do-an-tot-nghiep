@startuml
actor "Quản trị viên" as Admin
boundary "Giao diện" as UI
control "Hệ thống" as System
database "Cơ sở dữ liệu" as DB

Admin -> UI: Chọn "Thêm tài khoản"
UI -> System: Yêu cầu hiển thị form
System -> UI: Hiển thị form nhập thông tin

Admin -> UI: Nhập thông tin tài khoản
UI -> System: Gửi thông tin tạo mới
System -> DB: Kiểm tra tài khoản tồn tại

alt Tài khoản hợp lệ
    System -> DB: Lưu tài khoản mới
    DB --> System: Xác nhận đã lưu
    System -> UI: Thông báo "Thêm thành công"
    UI -> Admin: Hiển thị kết quả
else Tài khoản không hợp lệ
    System -> UI: Thông báo lỗi
    UI -> Admin: Hiển thị lỗi
end

Admin -> UI: Chọn tài khoản cần sửa
UI -> System: Gửi yêu cầu thông tin
System -> DB: Lấy thông tin tài khoản
DB --> System: Trả dữ liệu
System -> UI: Hiển thị form chỉnh sửa

Admin -> UI: Nhập thông tin cập nhật
UI -> System: Gửi dữ liệu mới
System -> DB: Kiểm tra dữ liệu

alt Dữ liệu hợp lệ
    System -> DB: Cập nhật thông tin
    DB --> System: Xác nhận cập nhật
    System -> UI: Thông báo "Cập nhật thành công"
    UI -> Admin: Hiển thị kết quả
else Dữ liệu không hợp lệ
    System -> UI: Thông báo lỗi
    UI -> Admin: Hiển thị lỗi
end

Admin -> UI: Chọn tài khoản cần xoá
UI -> System: Gửi yêu cầu xoá
System -> DB: Kiểm tra điều kiện xoá

alt Không thể xoá
    System -> UI: Thông báo lỗi
    UI -> Admin: Hiển thị lỗi
else Có thể xoá
    System -> DB: Xoá tài khoản
    DB --> System: Xác nhận xoá
    System -> UI: Thông báo "Xoá thành công"
    UI -> Admin: Hiển thị kết quả
end
@enduml
