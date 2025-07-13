| Test Case                                           | Expected Result                                            | Date        | Tester          | Result                    | Pass/Fail |
|-----------------------------------------------------|------------------------------------------------------------|-------------|------------------|----------------------------|-----------|
| Đăng nhập hợp lệ                                   | Chuyển đến giao diện chính theo vai trò                   | 21/06/2025  | Đàm Minh Giang   | Đăng nhập thành công       | Pass      |
| Đăng nhập thiếu thông tin                          | Hiển thị lỗi yêu cầu nhập đủ tên đăng nhập/mật khẩu       | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Đăng nhập tài khoản bị khoá                        | Hiển thị lỗi tài khoản bị khoá                            | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Đổi mật khẩu sai                                   | Hiển thị lỗi mật khẩu hiện tại không chính xác            | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Đăng xuất                                          | Quay về trang đăng nhập                                   | 21/06/2025  | Đàm Minh Giang   | Đăng xuất thành công       | Pass      |
| Thêm khoa hợp lệ                                   | Thêm thành công, hiển thị trong danh sách                 | 21/06/2025  | Đàm Minh Giang   | Thêm thành công            | Pass      |
| Thêm khoa trùng tên                                | Hiển thị lỗi khoa đã tồn tại                              | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Xóa khoa đã có ngành                               | Hiển thị lỗi không thể xóa khoa có ngành                  | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Nhập Excel khoa thiếu trường                       | Bỏ qua dòng lỗi, thông báo số dòng lỗi                    | 21/06/2025  | Đàm Minh Giang   | Bỏ qua đúng                | Pass      |
| Truy cập quản lý khoa không phải admin             | Chuyển hướng về trang chủ, báo không có quyền             | 21/06/2025  | Đàm Minh Giang   | Chuyển hướng đúng          | Pass      |
| Thêm ngành hợp lệ                                  | Thêm thành công, hiển thị trong danh sách                 | 21/06/2025  | Đàm Minh Giang   | Thêm thành công            | Pass      |
| Thêm ngành trùng tên trong khoa                    | Hiển thị lỗi ngành đã tồn tại trong khoa                  | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Xóa ngành đã có môn học                            | Hiển thị lỗi không thể xóa ngành có môn học               | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Nhập Excel ngành tên khoa không khớp               | Bỏ qua dòng lỗi, thông báo số dòng lỗi                    | 21/06/2025  | Đàm Minh Giang   | Bỏ qua đúng                | Pass      |
| Thêm môn học hợp lệ                                | Thêm thành công, hiển thị trong danh sách                 | 21/06/2025  | Đàm Minh Giang   | Thêm thành công            | Pass      |
| Thêm môn học trùng tên trong ngành                 | Hiển thị lỗi môn học đã tồn tại trong ngành               | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Xóa môn học đã có câu hỏi                          | Hiển thị lỗi không thể xóa môn học có câu hỏi             | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Nhập Excel môn học thiếu trường                    | Bỏ qua dòng lỗi, thông báo số dòng lỗi                    | 21/06/2025  | Đàm Minh Giang   | Bỏ qua đúng                | Pass      |
| Thêm tài khoản hợp lệ                              | Thêm thành công, hiển thị trong danh sách                 | 21/06/2025  | Đàm Minh Giang   | Thêm thành công            | Pass      |
| Thêm tài khoản trùng tên đăng nhập                 | Hiển thị lỗi tên đăng nhập đã tồn tại                     | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Xóa tài khoản đang đăng nhập                       | Hiển thị lỗi không thể xóa tài khoản đang đăng nhập       | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Nhập Excel tài khoản thiếu trường                  | Bỏ qua dòng lỗi, thông báo số dòng lỗi                    | 21/06/2025  | Đàm Minh Giang   | Bỏ qua đúng                | Pass      |
| Thêm câu hỏi hợp lệ                                | Thêm thành công, hiển thị trong danh sách                 | 21/06/2025  | Đàm Minh Giang   | Thêm thành công            | Pass      |
| Thêm câu hỏi thiếu nội dung                        | Hiển thị lỗi thiếu nội dung                               | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Xóa câu hỏi đã liên kết kỳ thi                     | Hiển thị lỗi không cho phép xóa                           | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Nhập Excel câu hỏi tên môn học không khớp          | Bỏ qua dòng lỗi, thông báo số dòng lỗi                    | 21/06/2025  | Đàm Minh Giang   | Bỏ qua đúng                | Pass      |
| Thêm kỳ thi hợp lệ                                 | Thêm thành công, hiển thị trong danh sách                 | 21/06/2025  | Đàm Minh Giang   | Thêm thành công            | Pass      |
| Thêm kỳ thi thiếu thông tin                        | Hiển thị lỗi thiếu thông tin                              | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Xóa kỳ thi đã có đề thi/thí sinh                   | Hiển thị lỗi không cho phép xóa                           | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Thêm thí sinh hợp lệ                               | Thêm thành công, hiển thị trong danh sách                 | 21/06/2025  | Đàm Minh Giang   | Thêm thành công            | Pass      |
| Thêm thí sinh đã tồn tại trong kỳ thi              | Hiển thị lỗi thí sinh đã tồn tại                          | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Xóa thí sinh đã có bài thi                         | Hiển thị lỗi không cho phép xóa                           | 21/06/2025  | Đàm Minh Giang   | Cảnh báo đúng              | Pass      |
| Nhập Excel thí sinh thiếu trường                   | Bỏ qua dòng lỗi, thông báo số dòng lỗi                    | 21/06/2025  | Đàm Minh Giang   | Bỏ qua đúng                | Pass      |
| Xem kết quả kỳ thi hợp lệ                          | Hiển thị danh sách kết quả, thống kê đúng                 | 21/06/2025  | Đàm Minh Giang   | Hiển thị đúng              | Pass      |
| Xem kết quả kỳ thi không hợp lệ                    | Chuyển hướng về trang kỳ thi, hiển thị thông báo lỗi      | 21/06/2025  | Đàm Minh Giang   | Chuyển hướng đúng          | Pass      |
| Xuất Excel kết quả hợp lệ                          | Tải về file Excel kết quả đúng định dạng                  | 21/06/2025  | Đàm Minh Giang   | Xuất thành công            | Pass      |