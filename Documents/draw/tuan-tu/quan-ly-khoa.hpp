@startuml
actor "Quản trị viên" as Admin
boundary "Giao diện" as UI
control "Hệ thống" as System
database "CSDL" as DB

Admin -> UI: Chọn "Thêm khoa"
UI -> System: Yêu cầu hiển thị form
System -> UI: Hiển thị form nhập tên khoa

Admin -> UI: Nhập tên khoa và nhấn "Thêm"
UI -> System: Gửi tên khoa
System -> DB: Kiểm tra tên khoa tồn tại

alt Tên khoa đã tồn tại
    System -> UI: Hiển thị lỗi "Tên khoa đã tồn tại"
    UI -> Admin: Yêu cầu nhập lại
else Tên khoa hợp lệ
    System -> DB: Thêm mới khoa
    DB --> System: Xác nhận thêm
    System -> UI: Thông báo "Thêm khoa thành công"
    UI -> Admin: Hiển thị kết quả
end

Admin -> UI: Chọn khoa cần sửa
UI -> System: Yêu cầu dữ liệu khoa
System -> DB: Lấy thông tin khoa
DB --> System: Trả dữ liệu
System -> UI: Hiển thị form sửa

Admin -> UI: Cập nhật thông tin và nhấn "Lưu"
UI -> System: Gửi dữ liệu cập nhật
System -> DB: Kiểm tra tên trùng và tính hợp lệ

alt Dữ liệu không hợp lệ hoặc tên trùng
    System -> UI: Thông báo lỗi
    UI -> Admin: Yêu cầu chỉnh sửa lại
else Dữ liệu hợp lệ
    System -> DB: Cập nhật khoa
    DB --> System: Xác nhận cập nhật
    System -> UI: Thông báo "Cập nhật thành công"
    UI -> Admin: Hiển thị kết quả
end

Admin -> UI: Chọn khoa cần xoá
UI -> System: Gửi yêu cầu xoá
System -> DB: Kiểm tra ràng buộc (có ngành liên kết?)

alt Có ngành liên kết
    System -> UI: Thông báo "Không thể xoá"
    UI -> Admin: Hiển thị lỗi
else Không có ngành liên kết
    System -> DB: Xoá khoa
    DB --> System: Xác nhận xoá
    System -> UI: Thông báo "Xoá thành công"
    UI -> Admin: Hiển thị kết quả
end
@enduml
