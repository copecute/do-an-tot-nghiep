@startuml
actor "Người dùng" as User
boundary "Giao diện" as UI
control "Hệ thống" as System
database "CSDL" as DB

User -> UI: Đăng nhập hệ thống
User -> UI: Chọn kỳ thi
User -> UI: Chọn "Xem báo cáo"
UI -> System: Gửi yêu cầu truy vấn bài thi
System -> DB: Truy vấn dữ liệu bài thi
DB --> System: Dữ liệu trả về

alt Có dữ liệu
    System -> System: Tổng hợp thống kê
    System -> UI: Hiển thị bảng + biểu đồ
    UI -> User

    alt Người dùng muốn xuất báo cáo
        User -> UI: Nhấn "Xuất báo cáo"
        UI -> System: Yêu cầu xuất báo cáo
        System -> UI: Hiển thị lựa chọn in/xuất file
        User -> UI: Chọn định dạng (PDF, Excel...)
        UI -> System: Gửi định dạng

        System -> System: Tạo file báo cáo

        alt Tạo file thành công
            System -> UI: Thông báo "Xuất thành công"
            UI -> User: Cung cấp link tải
        else Thất bại
            System -> UI: Thông báo lỗi "Xuất thất bại"
            UI -> User: Quay lại giao diện báo cáo
        end
    end

else Không có dữ liệu
    System -> UI: Hiển thị "Không có dữ liệu phù hợp"
    UI -> User: Cho phép chọn lại điều kiện
end

@enduml
