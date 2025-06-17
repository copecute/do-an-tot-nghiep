@startuml
|Thí sinh|
start
:Xác thực thành công;
:Truy cập giao diện làm bài;

|Client|
:Hiển thị câu hỏi, tùy chọn và thời gian;

repeat
  |Thí sinh|
  :Chọn đáp án;
  |Client|
  :Lưu đáp án tạm thời;
repeat while (Chưa nộp hoặc hết giờ?)
  |Client|
  :Gửi bài làm lên Server;

  |Server|
  :Chấm điểm, tính kết quả;

  if (Thành công?) then (Có)
    :Gửi kết quả về;
    |Client|
    :Hiển thị điểm và số câu đúng;
  else
    |Server|
    :Thông báo lỗi;
  endif

  :Ghi trạng thái "Đã nộp";

stop
@enduml