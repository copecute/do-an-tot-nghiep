@startuml
|Người dùng|
start
:Đăng nhập hệ thống;
:Chọn chức năng "Quản lý kỳ thi";
:Chọn kỳ thi cụ thể;
:Chọn chức năng "Quản lý đề thi";

if (Muốn thêm đề thi?) then (Có)
    :Chọn kỳ thi để thêm đề;
    :Chọn “Thêm đề thi”;

    |Hệ thống|
    :Hiển thị form tạo đề (tự động/thủ công);

    |Người dùng|
    :Nhập thông tin đề thi (số câu, thời gian...);
    :Nhấn “Tạo đề thi”;

    |Hệ thống|
    :Kiểm tra hợp lệ & số lượng câu hỏi;

    if (Không hợp lệ?) then (Đúng)
        :Hiển thị lỗi: thiếu dữ liệu / không đủ câu hỏi;
        :Quay lại form;
    else (Hợp lệ)
        :Lưu đề thi;
        :Hiển thị “Tạo đề thi thành công”;
    endif
endif

|Người dùng|
if (Muốn sửa đề thi?) then (Có)
    :Chọn đề thi để sửa;

    |Hệ thống|
    :Hiển thị thông tin đề thi;

    |Người dùng|
    :Cập nhật nội dung, thời gian,...;
    :Nhấn “Lưu thay đổi”;

    |Hệ thống|
    if (Đề đã có thí sinh làm bài?) then (Có)
        :Hiển thị lỗi: Không thể sửa;
    else (Chưa)
        :Kiểm tra hợp lệ;
        if (Không hợp lệ?) then (Đúng)
            :Hiển thị lỗi: thiếu/sai thông tin;
        else (Hợp lệ)
            :Cập nhật dữ liệu;
            :Hiển thị “Cập nhật thành công”;
        endif
    endif
endif

|Người dùng|
if (Muốn xóa đề thi?) then (Có)
    :Chọn đề thi để xóa;

    |Hệ thống|
    :Hiển thị hộp xác nhận;

    |Người dùng|
    if (Xác nhận xóa?) then (Có)
        |Hệ thống|
        if (Đề đã có thí sinh?) then (Có)
            :Thông báo lỗi;
        else (Chưa)
            :Xoá khỏi CSDL;
            :Hiển thị “Xóa đề thi thành công”;
        endif
    else (Không)
        :Hủy thao tác xóa;
    endif
endif

|Người dùng|
stop
@enduml
