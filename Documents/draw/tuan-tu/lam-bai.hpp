@startuml
actor "Thí sinh" as TS
boundary "Giao diện Client" as Client
control "Máy chủ" as Server
database "CSDL" as DB

TS -> Client: Truy cập giao diện làm bài
Client -> Server: Yêu cầu lấy đề thi
Server -> DB: Truy vấn đề thi
DB --> Server: Gửi danh sách câu hỏi
Server --> Client: Gửi câu hỏi, thời gian, ID đề thi
Client -> TS: Hiển thị đề thi và bộ đếm thời gian

loop Trong quá trình làm bài
    TS -> Client: Chọn đáp án
    Client -> Client: Lưu đáp án tạm thời
end

alt Hết giờ hoặc TS nhấn "Nộp bài"
    TS -> Client: Nhấn "Nộp bài"
    Client -> Server: Gửi bài làm (mã đề, danh sách câu trả lời)
    Server -> DB: Lưu bài làm vào CSDL
    Server -> Server: Chấm điểm tự động
    Server -> DB: Ghi kết quả

    alt Chấm điểm thành công
        Server --> Client: Gửi điểm + số câu đúng
        Client -> TS: Hiển thị kết quả
    else Lỗi khi xử lý
        Server --> Client: Thông báo lỗi
        Client -> TS: Hiển thị thông báo lỗi
    end
end
@enduml
