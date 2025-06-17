@startuml
actor "Quản trị viên" as Admin
boundary "Giao diện" as UI
control "Hệ thống" as System
database "CSDL" as DB

Admin -> UI: Chọn "Thêm môn học"
UI -> System: Yêu cầu hiển thị form
System -> UI: Hiển thị form nhập thông tin

Admin -> UI: Nhập tên môn học, ngành,... và nhấn "Thêm"
UI -> System: Gửi dữ liệu môn học
System -> DB: Kiểm tra tên môn học trùng

alt Tên bị trùng
    System -> UI: Thông báo lỗi "Tên môn học đã tồn tại"
    UI -> Admin: Hiển thị lỗi
else Tên hợp lệ
    System -> DB: Lưu vào CSDL
    DB --> System: Xác nhận lưu
    System -> UI: Thông báo "Thêm thành công"
    UI -> Admin: Hiển thị kết quả
end

Admin -> UI: Chọn "Sửa" trong danh sách
UI -> System: Gửi yêu cầu dữ liệu
System -> DB: Lấy thông tin môn học
DB --> System: Trả về thông tin hiện tại
System -> UI: Hiển thị form sửa

Admin -> UI: Chỉnh sửa thông tin và nhấn "Lưu thay đổi"
UI -> System: Gửi thông tin mới
System -> DB: Kiểm tra trùng tên

alt Tên trùng
    System -> UI: Thông báo lỗi "Môn học đã tồn tại"
    UI -> Admin: Quay lại form sửa
else Hợp lệ
    System -> DB: Cập nhật dữ liệu
    DB --> System: Xác nhận cập nhật
    System -> UI: Thông báo "Cập nhật thành công"
    UI -> Admin: Hiển thị kết quả
end

Admin -> UI: Chọn "Xoá" và xác nhận
UI -> System: Gửi yêu cầu xoá
System -> DB: Kiểm tra ràng buộc với kỳ thi

alt Có ràng buộc
    System -> UI: Thông báo lỗi "Không thể xoá do ràng buộc"
    UI -> Admin: Hiển thị lỗi
else Không có ràng buộc
    System -> DB: Xoá môn học
    DB --> System: Xác nhận xoá
    System -> UI: Thông báo "Xoá thành công"
    UI -> Admin: Hiển thị kết quả
end
@enduml
