@startuml
|Người dùng|
start
:Đăng nhập hệ thống;
:Chọn "Ngân hàng câu hỏi";

if (Muốn thêm câu hỏi?) then (Có)
    :Chọn "Thêm câu hỏi";
    |Hệ thống|
    :Hiển thị form nhập câu hỏi;

    |Người dùng|
    :Nhập nội dung, đáp án;
    :Chọn đáp án đúng;
    :Nhấn "Thêm câu hỏi";

    |Hệ thống|
    :Kiểm tra thông tin hợp lệ?;

    if (Thiếu nội dung hoặc đáp án đúng?) then (Có)
        :Thông báo lỗi;
        :Quay lại form, giữ dữ liệu;
    else
        :Lưu câu hỏi vào CSDL;
        :Hiển thị "Thêm thành công";
    endif
endif

if (Muốn sửa câu hỏi?) then (Có)
    :Chọn một câu hỏi;
    :Nhấn nút "Sửa";

    |Hệ thống|
    :Hiển thị form chỉnh sửa;

    |Người dùng|
    :Sửa nội dung, đáp án...;
    :Nhấn "Lưu thay đổi";

    |Hệ thống|
    :Kiểm tra tính hợp lệ?;

    if (Thông tin sai/thiếu?) then (Có)
        :Hiển thị lỗi: "Thông tin không hợp lệ";
        :Quay lại form sửa, giữ dữ liệu;
    else
        :Cập nhật trong CSDL;
        :Hiển thị "Sửa thành công";
    endif
endif

if (Muốn xoá câu hỏi?) then (Có)
    :Chọn một câu hỏi;
    :Nhấn nút "Xoá";

    |Hệ thống|
    :Hiển thị xác nhận xoá;

    |Người dùng|
    if (Hủy xoá?) then (Có)
        :Nhấn "Hủy";
        :Quay lại danh sách;
    else
        :Xác nhận xoá;

        |Hệ thống|
        :Kiểm tra câu hỏi có đang dùng?;

        if (Đang dùng trong đề thi?) then (Có)
            :Hiển thị "Không thể xoá do ràng buộc";
            :Quay lại danh sách;
        else
            :Xoá câu hỏi khỏi CSDL;
            :Hiển thị "Xoá thành công";
        endif
    endif
endif

if (Muốn nhập/xuất câu hỏi?) then (Có)
    :Chọn chức năng "Nhập/Xuất";

    |Hệ thống|
    :Hiển thị tùy chọn [Nhập] hoặc [Xuất];

    |Người dùng|
    if (Chọn "Nhập") then (Yes)
        :Chọn tệp file Excel;
        |Hệ thống|
        :Kiểm tra định dạng và nội dung file;

        if (Hợp lệ?) then (Không)
            :Hiển thị lỗi: "Sai định dạng hoặc thiếu dữ liệu";
        else
            :Lưu các câu hỏi vào CSDL;
            :Thông báo "Nhập thành công";
        endif
    else
        :Nhấn "Xuất";
        |Hệ thống|
        :Tạo file chứa danh sách câu hỏi;
        :Cung cấp link tải về;
        :Thông báo "Xuất thành công";
    endif
endif

stop
@enduml
