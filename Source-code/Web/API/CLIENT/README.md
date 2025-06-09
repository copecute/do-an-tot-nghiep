# Client Demo cho API EduDexQ

Client này được xây dựng đơn giản để test API của hệ thống EduDexQ. Giao diện đơn giản, không cần đẹp, chỉ để kiểm tra chức năng.

## Các trang

1. **login.html**: Trang đăng nhập thí sinh bằng mã sinh viên và số báo danh
2. **home.html**: Trang chủ hiển thị thông tin thí sinh và đề thi
3. **lam-bai.html**: Trang làm bài thi
4. **ket-qua.html**: Trang hiển thị kết quả bài thi
5. **index.html**: Trang chuyển hướng đến trang đăng nhập

## Cách sử dụng

1. Mở trang `login.html` hoặc `index.html`
2. Đăng nhập bằng mã sinh viên và số báo danh
3. Xem thông tin đề thi và bắt đầu làm bài
4. Làm bài thi và nộp bài
5. Xem kết quả bài thi

## Lưu ý

- Client này sử dụng localStorage để lưu trữ thông tin đăng nhập và kết quả thi
- Không có tính năng bảo mật, chỉ để test API
- Đường dẫn API được cấu hình cố định là `/API/...`

## Các API được sử dụng

1. **Xác thực thí sinh**: `/API/xac-thuc-thi-sinh/`
2. **Lấy đề thi**: `/API/lam-bai/`
3. **Nộp bài**: `/API/nop-bai/` 