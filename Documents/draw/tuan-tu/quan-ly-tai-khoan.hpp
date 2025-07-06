@startuml
actor "Quản trị viên" as Admin
boundary "Giao diện" as UI
control "Hệ thống" as System
database "Cơ sở dữ liệu" as DB

' Thêm tài khoản
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

' Sửa tài khoản
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

' Xoá tài khoản
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

' Tìm kiếm & lọc tài khoản
Admin -> UI: Nhập từ khoá hoặc bộ lọc
UI -> System: Gửi yêu cầu tìm kiếm
System -> DB: Truy vấn dữ liệu theo điều kiện
DB --> System: Trả về kết quả lọc
System -> UI: Hiển thị danh sách lọc
UI -> Admin: Hiển thị kết quả tìm kiếm

' Nhập Excel
Admin -> UI: Chọn "Nhập Excel"
UI -> System: Gửi file Excel
System -> System: Phân tích và kiểm tra dữ liệu
alt Dữ liệu hợp lệ
    System -> DB: Thêm các tài khoản từ file
    DB --> System: Xác nhận thêm
    System -> UI: Thông báo "Nhập thành công"
    UI -> Admin: Hiển thị kết quả
else Dữ liệu lỗi
    System -> UI: Hiển thị thông báo lỗi
    UI -> Admin: Cho phép chỉnh sửa lại file
end

' Xuất Excel
Admin -> UI: Chọn "Xuất Excel"
UI -> System: Gửi yêu cầu xuất
System -> DB: Lấy danh sách tài khoản
DB --> System: Trả dữ liệu
System -> System: Tạo file Excel
System -> UI: Cung cấp link tải file
UI -> Admin: Cho phép tải về

@enduml
