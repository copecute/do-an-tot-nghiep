@startuml
|Quản trị viên|
start
:Đăng nhập hệ thống;
:Chọn chức năng "Quản lý môn học";

if (Muốn thêm môn học?) then (Có)
    :Chọn "Thêm môn học";
    
    |Hệ thống|
    :Hiển thị form nhập thông tin;
    
    |Quản trị viên|
    :Nhập tên, ngành,...;
    :Nhấn nút "Thêm";

    |Hệ thống|
    :Kiểm tra tên môn học đã tồn tại?;

    if (Tên bị trùng?) then (Có)
        :Hiển thị lỗi "Tên môn học đã tồn tại";
        :Yêu cầu nhập lại;
    else
        :Lưu vào CSDL;
        :Thông báo "Thêm thành công";
    endif
endif

|Quản trị viên|
if (Muốn sửa môn học?) then (Có)
    :Chọn "Sửa" trên danh sách;

    |Hệ thống|
    :Hiển thị form thông tin hiện tại;

    |Quản trị viên|
    :Chỉnh sửa thông tin;
    :Nhấn "Lưu thay đổi";

    |Hệ thống|
    :Kiểm tra trùng tên?;

    if (Tên bị trùng?) then (Có)
        :Hiển thị lỗi "Môn học đã tồn tại";
        :Quay lại form sửa;
    else
        :Cập nhật CSDL;
        :Thông báo "Cập nhật thành công";
    endif
endif

|Quản trị viên|
if (Muốn xoá môn học?) then (Có)
    :Chọn "Xóa" trên danh sách;
    :Xác nhận thao tác xoá;

    |Hệ thống|
    :Kiểm tra ràng buộc với kỳ thi;

    if (Có liên kết?) then (Có)
        :Hiển thị lỗi "Không thể xoá do ràng buộc";
    else
        :Xoá khỏi CSDL;
        :Thông báo "Xoá thành công";
    endif
endif
|Quản trị viên|
stop
@enduml
