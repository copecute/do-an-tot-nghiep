@startuml
actor "Người dùng" as User
boundary "Giao diện" as UI
control "Hệ thống" as System
database "CSDL" as DB

== Truy cập Quản lý kỳ thi ==
User -> UI: Đăng nhập
User -> UI: Chọn "Quản lý kỳ thi"
UI -> System: Yêu cầu danh sách kỳ thi
System -> DB: Truy vấn danh sách
DB --> System: Trả về dữ liệu
System -> UI: Hiển thị danh sách

User -> UI: Chọn "Thêm kỳ thi"
UI -> System: Yêu cầu form
System -> UI: Hiển thị form nhập

User -> UI: Nhập tên, môn, thời gian
User -> UI: Nhấn "Thêm kỳ thi"
UI -> System: Gửi dữ liệu
System -> System: Kiểm tra hợp lệ và trùng lịch

alt Thiếu thông tin hoặc trùng lịch
    System -> UI: Hiển thị lỗi
    UI -> User: Quay lại form, giữ dữ liệu
else Hợp lệ
    System -> DB: Lưu dữ liệu kỳ thi
    DB --> System: Xác nhận lưu
    System -> UI: Thông báo "Tạo thành công"
    UI -> User: Hiển thị kết quả
end

User -> UI: Chọn kỳ thi cần sửa
UI -> System: Yêu cầu thông tin
System -> DB: Truy xuất dữ liệu kỳ thi
DB --> System: Trả về thông tin
System -> UI: Hiển thị form sửa

User -> UI: Cập nhật thông tin
User -> UI: Nhấn "Lưu thay đổi"
UI -> System: Gửi dữ liệu cập nhật
System -> DB: Kiểm tra có thí sinh

alt Có thí sinh đăng ký
    System -> UI: Thông báo không thể sửa
    UI -> User: Hiển thị lỗi
else Chưa có thí sinh
    System -> System: Kiểm tra dữ liệu

    alt Dữ liệu không hợp lệ
        System -> UI: Thông báo lỗi
        UI -> User: Hiển thị lỗi
    else Hợp lệ
        System -> DB: Cập nhật dữ liệu
        DB --> System: Xác nhận
        System -> UI: Hiển thị "Cập nhật thành công"
        UI -> User: Thông báo kết quả
    end
end

User -> UI: Chọn kỳ thi để xoá
UI -> System: Yêu cầu xác nhận
System -> UI: Hiển thị hộp thoại xác nhận
User -> UI: Xác nhận hoặc huỷ

alt Huỷ xoá
    UI -> User: Hủy thao tác xoá
else Xác nhận xoá
    UI -> System: Gửi yêu cầu xoá
    System -> DB: Kiểm tra có thí sinh

    alt Có thí sinh
        System -> UI: Thông báo không thể xoá
        UI -> User: Hiển thị lỗi
    else Không có thí sinh
        System -> DB: Xoá kỳ thi
        DB --> System: Xác nhận xoá
        System -> UI: Hiển thị "Xoá thành công"
        UI -> User: Thông báo kết quả
    end
end

@enduml
