@startuml
actor "Người dùng" as User
boundary "Giao diện" as UI
control "Hệ thống" as System
database "CSDL" as DB

User -> UI: Đăng nhập
User -> UI: Chọn "Quản lý kỳ thi"
User -> UI: Chọn kỳ thi cụ thể
User -> UI: Chọn "Quản lý đề thi"

User -> UI: Chọn "Thêm đề thi"
UI -> System: Yêu cầu hiển thị form
System -> UI: Hiển thị form (tự động/thủ công)

User -> UI: Nhập số câu, thời gian...
User -> UI: Nhấn "Tạo đề thi"
UI -> System: Gửi thông tin đề thi
System -> System: Kiểm tra hợp lệ và số câu hỏi

alt Không hợp lệ
    System -> UI: Thông báo lỗi (thiếu dữ liệu / không đủ câu hỏi)
    UI -> User: Quay lại form
else Hợp lệ
    System -> DB: Lưu đề thi
    DB --> System: Xác nhận
    System -> UI: Thông báo "Tạo đề thi thành công"
    UI -> User: Hiển thị kết quả
end

User -> UI: Chọn đề thi để sửa
UI -> System: Yêu cầu thông tin đề thi
System -> DB: Lấy thông tin đề thi
DB --> System: Trả về dữ liệu
System -> UI: Hiển thị form chỉnh sửa

User -> UI: Cập nhật nội dung, thời gian...
User -> UI: Nhấn "Lưu thay đổi"
UI -> System: Gửi thông tin cập nhật
System -> DB: Kiểm tra đề đã có thí sinh làm bài?

alt Có thí sinh đã làm bài
    System -> UI: Thông báo lỗi "Không thể sửa"
    UI -> User: Hiển thị lỗi
else Chưa có thí sinh
    System -> System: Kiểm tra tính hợp lệ

    alt Không hợp lệ
        System -> UI: Thông báo lỗi "Dữ liệu không hợp lệ"
        UI -> User: Quay lại chỉnh sửa
    else Hợp lệ
        System -> DB: Cập nhật đề thi
        DB --> System: Xác nhận
        System -> UI: Hiển thị "Cập nhật thành công"
        UI -> User: Thông báo kết quả
    end
end

User -> UI: Chọn đề thi cần xoá
UI -> System: Yêu cầu xác nhận xoá
System -> UI: Hiển thị hộp thoại xác nhận
User -> UI: Xác nhận xoá

alt Xác nhận xoá
    UI -> System: Gửi yêu cầu xoá
    System -> DB: Kiểm tra đề đã có thí sinh

    alt Có thí sinh
        System -> UI: Thông báo lỗi "Không thể xoá"
        UI -> User: Hiển thị lỗi
    else Không có thí sinh
        System -> DB: Xoá đề thi
        DB --> System: Xác nhận
        System -> UI: Hiển thị "Xoá đề thi thành công"
        UI -> User: Thông báo kết quả
    end
else Huỷ thao tác
    UI -> User: Hủy xoá, quay lại
end

@enduml
