@startuml
|Quản trị viên|
start
:Đăng nhập vào hệ thống;
:Chọn chức năng "Quản lý ngành";

if (Muốn thêm ngành?) then (Có)
    :Chọn "Thêm ngành";

    |Hệ thống|
    :Hiển thị form nhập thông tin;

    |Quản trị viên|
    :Nhập tên ngành;
    :Nhấn nút "Thêm";

    |Hệ thống|
    :Kiểm tra tên ngành đã tồn tại?;

    if (Tên ngành trùng?) then (Có)
        :Hiển thị lỗi "Tên ngành đã tồn tại";
        :Yêu cầu nhập lại;
    else
        :Lưu vào CSDL;
        :Hiển thị "Thêm ngành thành công";
    endif
endif

|Quản trị viên|
if (Muốn sửa ngành?) then (Có)
    :Chọn ngành cần sửa;

    |Hệ thống|
    :Hiển thị thông tin ngành;

    |Quản trị viên|
    :Cập nhật thông tin;
    :Nhấn "Lưu thay đổi";

    |Hệ thống|
    :Kiểm tra hợp lệ và trùng tên;

    if (Không hợp lệ hoặc tên trùng?) then (Có)
        :Hiển thị lỗi;
        :Yêu cầu chỉnh sửa lại;
    else
        :Cập nhật dữ liệu;
        :Thông báo "Cập nhật thành công";
    endif
endif

|Quản trị viên|
if (Muốn xoá ngành?) then (Có)
    :Chọn ngành cần xoá;
    :Xác nhận "Xoá";

    |Hệ thống|
    :Kiểm tra ràng buộc dữ liệu;

    if (Có môn học liên kết?) then (Có)
        :Hiển thị lỗi "Không thể xoá ngành vì có dữ liệu liên kết";
    else
        :Xoá ngành;
        :Thông báo "Xoá ngành thành công";
    endif
endif

|Quản trị viên|
stop
@enduml
