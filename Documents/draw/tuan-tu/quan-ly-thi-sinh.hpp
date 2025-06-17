@startuml
actor "Người dùng" as User
boundary "Giao diện" as UI
control "Hệ thống" as System
database "CSDL" as DB

User -> UI: Đăng nhập
User -> UI: Chọn "Quản lý kỳ thi"
User -> UI: Chọn kỳ thi cụ thể
User -> UI: Chọn "Quản lý thí sinh"

User -> UI: Chọn "Thêm thí sinh"
UI -> System: Yêu cầu hiển thị form
System -> UI: Hiển thị form nhập thông tin

User -> UI: Nhập mã SV, họ tên
User -> UI: Nhấn "Lưu"
UI -> System: Gửi dữ liệu thí sinh
System -> DB: Kiểm tra trùng mã SV

alt Mã SV trùng
    System -> UI: Thông báo lỗi
    UI -> User: Quay lại form
else Không trùng
    System -> System: Kiểm tra thiếu thông tin

    alt Thiếu thông tin
        System -> UI: Thông báo lỗi
        UI -> User: Quay lại form
    else Đủ thông tin
        System -> DB: Lưu thí sinh + sinh số báo danh
        DB --> System: Xác nhận
        System -> UI: Hiển thị "Thêm thành công"
        UI -> User
    end
end

User -> UI: Chọn thí sinh để sửa
UI -> System: Yêu cầu dữ liệu
System -> DB: Lấy thông tin thí sinh
DB --> System
System -> UI: Hiển thị form sửa

User -> UI: Cập nhật họ tên
User -> UI: Nhấn "Lưu"
UI -> System: Gửi dữ liệu cập nhật
System -> System: Kiểm tra hợp lệ

alt Dữ liệu không hợp lệ
    System -> UI: Hiển thị lỗi
    UI -> User
else Hợp lệ
    System -> DB: Cập nhật thông tin
    DB --> System
    System -> UI: Hiển thị "Cập nhật thành công"
    UI -> User
end

User -> UI: Chọn thí sinh để xoá
UI -> System: Hiển thị hộp xác nhận
User -> UI: Xác nhận xoá

alt Xác nhận
    UI -> System: Gửi yêu cầu xoá
    System -> DB: Kiểm tra đã làm bài?

    alt Đã làm bài
        System -> UI: Thông báo lỗi "Không thể xoá"
        UI -> User
    else Chưa làm bài
        System -> DB: Xoá khỏi danh sách
        DB --> System
        System -> UI: Hiển thị "Xoá thành công"
        UI -> User
    end
else Huỷ thao tác
    UI -> User: Huỷ xoá
end

User -> UI: Chọn "Nhập/Xuất danh sách"
UI -> System: Hiển thị lựa chọn

alt Nhập danh sách
    User -> UI: Tải lên file CSV/Excel
    UI -> System: Gửi file
    System -> System: Kiểm tra định dạng / mã SV

    alt Lỗi định dạng / trùng mã
        System -> UI: Hiển thị lỗi + dòng lỗi
        UI -> User
    else Dữ liệu hợp lệ
        System -> DB: Nhập dữ liệu + sinh SBD
        DB --> System
        System -> UI: Hiển thị "Nhập thành công"
        UI -> User
    end
else Xuất danh sách
    System -> DB: Tạo file danh sách
    DB --> System
    System -> UI: Cung cấp link tải + thông báo "Xuất thành công"
    UI -> User
end

@enduml