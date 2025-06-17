@startuml
actor "Quản trị viên" as Admin
boundary "Giao diện" as UI
control "Hệ thống" as System
database "CSDL" as DB

Admin -> UI: Chọn "Thêm ngành"
UI -> System: Yêu cầu hiển thị form
System -> UI: Hiển thị form nhập thông tin

Admin -> UI: Nhập tên ngành và nhấn "Thêm"
UI -> System: Gửi tên ngành
System -> DB: Kiểm tra tên ngành tồn tại

alt Tên ngành đã tồn tại
    System -> UI: Thông báo lỗi "Tên ngành đã tồn tại"
    UI -> Admin: Hiển thị lỗi
else Tên ngành hợp lệ
    System -> DB: Thêm ngành vào CSDL
    DB --> System: Xác nhận thêm
    System -> UI: Thông báo "Thêm ngành thành công"
    UI -> Admin: Hiển thị kết quả
end

Admin -> UI: Chọn ngành cần sửa
UI -> System: Yêu cầu thông tin ngành
System -> DB: Lấy dữ liệu ngành
DB --> System: Trả về dữ liệu ngành
System -> UI: Hiển thị form sửa

Admin -> UI: Cập nhật và nhấn "Lưu thay đổi"
UI -> System: Gửi dữ liệu sửa
System -> DB: Kiểm tra hợp lệ và tên trùng

alt Không hợp lệ hoặc tên trùng
    System -> UI: Thông báo lỗi
    UI -> Admin: Hiển thị lỗi
else Dữ liệu hợp lệ
    System -> DB: Cập nhật ngành
    DB --> System: Xác nhận cập nhật
    System -> UI: Thông báo "Cập nhật thành công"
    UI -> Admin: Hiển thị kết quả
end

Admin -> UI: Chọn ngành cần xoá
UI -> System: Gửi yêu cầu xoá
System -> DB: Kiểm tra ràng buộc dữ liệu

alt Có môn học liên kết
    System -> UI: Thông báo lỗi "Không thể xoá ngành vì có dữ liệu liên kết"
    UI -> Admin: Hiển thị lỗi
else Không có ràng buộc
    System -> DB: Xoá ngành
    DB --> System: Xác nhận xoá
    System -> UI: Thông báo "Xoá ngành thành công"
    UI -> Admin: Hiển thị kết quả
end
@enduml
