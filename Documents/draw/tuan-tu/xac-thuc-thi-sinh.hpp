@startuml
actor "Thí sinh" as TS
boundary "Giao diện Client" as Client
control "Máy chủ" as Server
database "CSDL" as DB

TS -> Client: Truy cập giao diện thi
Client -> TS: Hiển thị form xác thực

TS -> Client: Nhập mã SV + SBD và nhấn "Xác thực"
Client -> Server: Gửi mã SV + SBD

Server -> DB: Kiểm tra mã SV và SBD
DB --> Server: Kết quả xác thực

alt Thông tin hợp lệ
    Server -> Client: Trả về xác thực thành công
    Client -> TS: Hiển thị giao diện tổng quan kỳ thi
else Không hợp lệ
    Server -> Client: Trả về lỗi xác thực
    Client -> TS: Cho phép nhập lại
end
@enduml
