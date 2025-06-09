# API Hệ thống Thi trắc nghiệm EduDexQ

API này cung cấp các endpoint để thí sinh có thể xác thực, làm bài thi và nộp bài.

## Danh sách API

### 1. Xác thực thí sinh

**Endpoint:** `/API/xac-thuc-thi-sinh/`

**Phương thức:** `POST`

**Mô tả:** API này dùng để xác thực thí sinh bằng mã sinh viên và số báo danh. Nếu thành công, API sẽ trả về thông tin thí sinh và đề thi.

**Request Body:**
```json
{
  "maSinhVien": "2209620321",
  "soBaoDanh": "0010010001"
}
```

**Phản hồi thành công (200):**
```json
{
  "success": true,
  "message": "xác thực thành công",
  "data": {
    "thiSinh": {
      "soBaoDanhId": 1,
      "sinhVienId": 1,
      "hoTen": "Nguyễn Văn A",
      "maSinhVien": "SV001",
      "soBaoDanh": "00101001"
    },
    "kyThi": {
      "id": 1,
      "tenKyThi": "Kỳ thi giữa kỳ",
      "monHoc": "Lập trình web",
      "thoiGianBatDau": "2025-06-01 08:00:00",
      "thoiGianKetThuc": "2025-06-01 10:00:00"
    },
    "deThi": {
      "id": 1,
      "tenDeThi": "Đề thi 01",
      "soCau": 30,
      "thoiGianLamBai": 60,
      "daLamBai": false
    }
  }
}
```

**Phản hồi lỗi (401):**
```json
{
  "success": false,
  "message": "thông tin đăng nhập không chính xác",
  "data": null
}
```

### 2. Lấy đề thi

**Endpoint:** `/API/lam-bai/`

**Phương thức:** `GET`

**Mô tả:** API này dùng để lấy danh sách câu hỏi và đáp án của đề thi.

**Query Parameters:**
- `soBaoDanhId`: ID số báo danh của thí sinh
- `deThiId`: ID đề thi

**Phản hồi thành công (200):**
```json
{
  "success": true,
  "message": "lấy đề thi thành công",
  "data": {
    "thiSinh": {
      "soBaoDanhId": 1,
      "sinhVienId": 1,
      "hoTen": "Nguyễn Văn A",
      "maSinhVien": "SV001",
      "soBaoDanh": "00101001"
    },
    "kyThi": {
      "id": 1,
      "tenKyThi": "Kỳ thi giữa kỳ",
      "monHoc": "Lập trình web",
      "thoiGianBatDau": "2025-06-01 08:00:00",
      "thoiGianKetThuc": "2025-06-01 10:00:00"
    },
    "deThi": {
      "id": 1,
      "tenDeThi": "Đề thi 01",
      "soCau": 30,
      "thoiGianLamBai": 60
    },
    "cauHoi": [
      {
        "cauHoi": {
          "id": 1,
          "noiDung": "HTML là viết tắt của?",
          "doKho": "de",
          "theLoai": "Cơ bản"
        },
        "dapAn": [
          {
            "id": 1,
            "noiDung": "Hyper Text Markup Language"
          },
          {
            "id": 2,
            "noiDung": "High Text Machine Language"
          },
          {
            "id": 3,
            "noiDung": "Hyper Text Machine Language"
          },
          {
            "id": 4,
            "noiDung": "High Text Markup Language"
          }
        ]
      },
      // ... các câu hỏi khác
    ]
  }
}
```

### 3. Nộp bài thi

**Endpoint:** `/API/nop-bai/`

**Phương thức:** `POST`

**Mô tả:** API này dùng để nộp bài thi và nhận kết quả.

**Request Body:**
```json
{
  "soBaoDanhId": 1,
  "deThiId": 1,
  "dapAn": {
    "1": 1,
    "2": 5,
    "3": 9,
    "4": 13
    // ... các câu trả lời khác, format: "cauHoiId": dapAnId
  }
}
```

**Phản hồi thành công (200):**
```json
{
  "success": true,
  "message": "nộp bài thành công",
  "data": {
    "thiSinh": {
      "soBaoDanhId": 1,
      "sinhVienId": 1,
      "hoTen": "Nguyễn Văn A",
      "maSinhVien": "SV001",
      "soBaoDanh": "00101001"
    },
    "kyThi": {
      "id": 1,
      "tenKyThi": "Kỳ thi giữa kỳ",
      "monHoc": "Lập trình web"
    },
    "deThi": {
      "id": 1,
      "tenDeThi": "Đề thi 01"
    },
    "ketQua": {
      "baiThiId": 1,
      "thoiGianNop": "2025-06-01 09:30:00",
      "soCauDung": 25,
      "tongSoCau": 30,
      "diem": 8.33,
      "chiTiet": {
        "1": true,
        "2": false,
        "3": true
        // ... kết quả từng câu, format: "cauHoiId": true/false
      }
    }
  }
}
```

## Mã lỗi

- **200**: Thành công
- **400**: Lỗi dữ liệu đầu vào
- **401**: Xác thực thất bại
- **403**: Không có quyền truy cập (ví dụ: kỳ thi chưa bắt đầu hoặc đã kết thúc)
- **404**: Không tìm thấy dữ liệu
- **405**: Phương thức không được hỗ trợ
- **500**: Lỗi hệ thống

## Ví dụ sử dụng

### Xác thực thí sinh

```javascript
fetch('https://example.com/API/xac-thuc-thi-sinh/', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    maSinhVien: 'SV001',
    soBaoDanh: '00101001'
  })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### Lấy đề thi

```javascript
fetch('https://example.com/API/lam-bai/?soBaoDanhId=1&deThiId=1')
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### Nộp bài thi

```javascript
fetch('https://example.com/API/nop-bai/', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    soBaoDanhId: 1,
    deThiId: 1,
    dapAn: {
      1: 1,
      2: 5,
      3: 9
      // ... các câu trả lời khác
    }
  })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
``` 