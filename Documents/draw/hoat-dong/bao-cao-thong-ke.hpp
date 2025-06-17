@startuml
|Người dùng|
start
:Đăng nhập hệ thống;
:Chọn kỳ thi;
:Chọn “Xem báo cáo”;

|Hệ thống|
:Truy vấn dữ liệu bài thi;

if (Có dữ liệu?) then (Có)
    :Tổng hợp thống kê;
    :Hiển thị bảng, biểu đồ;

    |Người dùng|
    if (Muốn xuất báo cáo?) then (Có)
        :Nhấn “Xuất báo cáo”;
        
        |Hệ thống|
        :Hiển thị lựa chọn in hoặc xuất file;

        |Người dùng|
        :Chọn định dạng (PDF, Excel...);
        
        |Hệ thống|
        :Tạo file báo cáo;

        if (Tạo file thành công?) then (Có)
            :Hiển thị "Xuất thành công";
            :Cung cấp link tải về;
        else
            :Thông báo lỗi: "Xuất thất bại";
            :Quay lại giao diện báo cáo;
        endif

    endif
else
    :Hiển thị "Không có dữ liệu phù hợp";
    :Cho phép chọn lại điều kiện;
endif
|Người dùng|
stop
@enduml
