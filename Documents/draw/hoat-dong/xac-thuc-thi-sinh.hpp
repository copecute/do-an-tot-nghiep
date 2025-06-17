@startuml
|Thí sinh|
start
:Truy cập giao diện thi;
|Client|
:Hiển thị giao diện xác thực;
|Thí sinh|
:Nhập mã sinh viên + số báo danh;
:Nhấn "Xác thực";

|Client|
:Gửi thông tin lên máy chủ;

|Máy chủ|
:Kiểm tra thông tin;

if (Thông tin hợp lệ?) then (Có)
|Client|
  :Hiển thị giao diện tổng quan;
  else (không)
  |Máy chủ|
  :Cho phép nhập lại;
endif
stop
@enduml
