@startuml
actor "Người dùng" as User
boundary "Giao diện" as UI
control "Hệ thống" as System
database "CSDL" as DB

User -> UI: Đăng nhập
User -> UI: Chọn "Ngân hàng câu hỏi"
UI -> System: Yêu cầu hiển thị danh sách
System -> DB: Truy vấn danh sách câu hỏi
DB --> System: Trả về dữ liệu
System -> UI: Hiển thị danh sách

User -> UI: Chọn "Thêm câu hỏi"
UI -> System: Yêu cầu form nhập
System -> UI: Hiển thị form nhập

User -> UI: Nhập nội dung, đáp án, chọn đáp án đúng
User -> UI: Nhấn "Thêm câu hỏi"
UI -> System: Gửi dữ liệu câu hỏi
System -> System: Kiểm tra hợp lệ

alt Thiếu nội dung hoặc đáp án đúng
    System -> UI: Thông báo lỗi, giữ dữ liệu
    UI -> User: Hiển thị lỗi
else Hợp lệ
    System -> DB: Lưu câu hỏi
    DB --> System: Xác nhận lưu
    System -> UI: Hiển thị "Thêm thành công"
    UI -> User: Hiển thị kết quả
end

User -> UI: Chọn câu hỏi cần sửa
User -> UI: Nhấn "Sửa"
UI -> System: Yêu cầu dữ liệu
System -> DB: Lấy thông tin câu hỏi
DB --> System: Trả về dữ liệu
System -> UI: Hiển thị form sửa

User -> UI: Cập nhật nội dung, đáp án,...
User -> UI: Nhấn "Lưu thay đổi"
UI -> System: Gửi dữ liệu cập nhật
System -> System: Kiểm tra tính hợp lệ

alt Thông tin sai hoặc thiếu
    System -> UI: Hiển thị lỗi
    UI -> User: Quay lại form sửa
else Hợp lệ
    System -> DB: Cập nhật thông tin
    DB --> System: Xác nhận cập nhật
    System -> UI: Hiển thị "Sửa thành công"
    UI -> User: Thông báo kết quả
end

User -> UI: Chọn câu hỏi
User -> UI: Nhấn "Xoá"
UI -> System: Yêu cầu xác nhận
System -> UI: Hiển thị hộp thoại xác nhận
User -> UI: Xác nhận hoặc huỷ

alt Huỷ xoá
    UI -> User: Quay lại danh sách
else Xác nhận xoá
    UI -> System: Gửi yêu cầu xoá
    System -> DB: Kiểm tra ràng buộc với đề thi

    alt Đang dùng trong đề thi
        System -> UI: Thông báo "Không thể xoá"
        UI -> User: Hiển thị lỗi
    else Không bị ràng buộc
        System -> DB: Xoá câu hỏi
        DB --> System: Xác nhận xoá
        System -> UI: Hiển thị "Xoá thành công"
        UI -> User: Thông báo kết quả
    end
end
@enduml
