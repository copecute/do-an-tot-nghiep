-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 05, 2025 at 12:15 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edudexq`
--

-- --------------------------------------------------------

--
-- Table structure for table `baithi`
--

CREATE TABLE `baithi` (
  `id` int NOT NULL,
  `soBaoDanhId` int NOT NULL,
  `deThiId` int NOT NULL,
  `thoiGianNop` datetime DEFAULT NULL,
  `soCauDung` int NOT NULL,
  `tongSoCau` int NOT NULL,
  `diem` float NOT NULL,
  `ghiChu` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cauhoi`
--

CREATE TABLE `cauhoi` (
  `id` int NOT NULL,
  `noiDung` text NOT NULL,
  `doKho` enum('de','trungbinh','kho') NOT NULL,
  `monHocId` int NOT NULL,
  `theLoaiId` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dapan`
--

CREATE TABLE `dapan` (
  `id` int NOT NULL,
  `cauHoiId` int NOT NULL,
  `noiDung` text NOT NULL,
  `laDapAn` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dethi`
--

CREATE TABLE `dethi` (
  `id` int NOT NULL,
  `kyThiId` int NOT NULL,
  `tenDeThi` varchar(100) NOT NULL,
  `soCau` int NOT NULL,
  `thoiGian` int NOT NULL,
  `nguoiTaoId` int NOT NULL,
  `ngayTao` datetime DEFAULT CURRENT_TIMESTAMP,
  `isTuDong` tinyint(1) DEFAULT '1',
  `tyLeDe` int DEFAULT '0',
  `tyLeTrungBinh` int DEFAULT '0',
  `tyLeKho` int DEFAULT '0',
  `cauHinhTheLoai` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dethicauhoi`
--

CREATE TABLE `dethicauhoi` (
  `id` int NOT NULL,
  `deThiId` int NOT NULL,
  `cauHoiId` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `khoa`
--

CREATE TABLE `khoa` (
  `id` int NOT NULL,
  `tenKhoa` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kythi`
--

CREATE TABLE `kythi` (
  `id` int NOT NULL,
  `tenKyThi` varchar(100) NOT NULL,
  `thoiGianBatDau` datetime NOT NULL,
  `thoiGianKetThuc` datetime NOT NULL,
  `monHocId` int NOT NULL,
  `nguoiTaoId` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monhoc`
--

CREATE TABLE `monhoc` (
  `id` int NOT NULL,
  `tenMonHoc` varchar(100) NOT NULL,
  `nganhId` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nganh`
--

CREATE TABLE `nganh` (
  `id` int NOT NULL,
  `tenNganh` varchar(100) NOT NULL,
  `khoaId` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sinhvien`
--

CREATE TABLE `sinhvien` (
  `id` int NOT NULL,
  `maSinhVien` varchar(100) NOT NULL,
  `hoTen` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sobaodanh`
--

CREATE TABLE `sobaodanh` (
  `id` int NOT NULL,
  `kyThiId` int NOT NULL,
  `sinhVienId` int NOT NULL,
  `soBaoDanh` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taikhoan`
--

CREATE TABLE `taikhoan` (
  `id` int NOT NULL,
  `tenDangNhap` varchar(100) NOT NULL,
  `matKhau` varchar(255) NOT NULL,
  `vaiTro` enum('admin','giaovien') NOT NULL,
  `hoTen` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `trangThai` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `taikhoan`
--

INSERT INTO `taikhoan` (`id`, `tenDangNhap`, `matKhau`, `vaiTro`, `hoTen`, `email`, `trangThai`) VALUES
(1, 'admin', '$2y$10$dFh94gsjX4Vl7SC9mnrhde5CvBdZTD1Sr1rELrVQzhwOOaTLDGYTC', 'admin', 'Đàm Minh Giang', 'admin@minhgiang.pro', 1);

-- --------------------------------------------------------

--
-- Table structure for table `theloaicauhoi`
--

CREATE TABLE `theloaicauhoi` (
  `id` int NOT NULL,
  `tenTheLoai` varchar(100) NOT NULL,
  `monHocId` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `baithi`
--
ALTER TABLE `baithi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `soBaoDanhId` (`soBaoDanhId`),
  ADD KEY `deThiId` (`deThiId`);

--
-- Indexes for table `cauhoi`
--
ALTER TABLE `cauhoi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `monHocId` (`monHocId`),
  ADD KEY `theLoaiId` (`theLoaiId`);

--
-- Indexes for table `dapan`
--
ALTER TABLE `dapan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cauHoiId` (`cauHoiId`);

--
-- Indexes for table `dethi`
--
ALTER TABLE `dethi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kyThiId` (`kyThiId`),
  ADD KEY `nguoiTaoId` (`nguoiTaoId`);

--
-- Indexes for table `dethicauhoi`
--
ALTER TABLE `dethicauhoi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deThiId` (`deThiId`),
  ADD KEY `cauHoiId` (`cauHoiId`);

--
-- Indexes for table `khoa`
--
ALTER TABLE `khoa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kythi`
--
ALTER TABLE `kythi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `monHocId` (`monHocId`),
  ADD KEY `nguoiTaoId` (`nguoiTaoId`);

--
-- Indexes for table `monhoc`
--
ALTER TABLE `monhoc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nganhId` (`nganhId`);

--
-- Indexes for table `nganh`
--
ALTER TABLE `nganh`
  ADD PRIMARY KEY (`id`),
  ADD KEY `khoaId` (`khoaId`);

--
-- Indexes for table `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `maSinhVien` (`maSinhVien`);

--
-- Indexes for table `sobaodanh`
--
ALTER TABLE `sobaodanh`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `soBaoDanh` (`soBaoDanh`),
  ADD KEY `kyThiId` (`kyThiId`),
  ADD KEY `sinhVienId` (`sinhVienId`);

--
-- Indexes for table `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenDangNhap` (`tenDangNhap`);

--
-- Indexes for table `theloaicauhoi`
--
ALTER TABLE `theloaicauhoi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `monHocId` (`monHocId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `baithi`
--
ALTER TABLE `baithi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cauhoi`
--
ALTER TABLE `cauhoi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dapan`
--
ALTER TABLE `dapan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dethi`
--
ALTER TABLE `dethi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dethicauhoi`
--
ALTER TABLE `dethicauhoi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `khoa`
--
ALTER TABLE `khoa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kythi`
--
ALTER TABLE `kythi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monhoc`
--
ALTER TABLE `monhoc`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nganh`
--
ALTER TABLE `nganh`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sinhvien`
--
ALTER TABLE `sinhvien`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sobaodanh`
--
ALTER TABLE `sobaodanh`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `theloaicauhoi`
--
ALTER TABLE `theloaicauhoi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `baithi`
--
ALTER TABLE `baithi`
  ADD CONSTRAINT `baithi_ibfk_1` FOREIGN KEY (`soBaoDanhId`) REFERENCES `sobaodanh` (`id`),
  ADD CONSTRAINT `baithi_ibfk_2` FOREIGN KEY (`deThiId`) REFERENCES `dethi` (`id`);

--
-- Constraints for table `cauhoi`
--
ALTER TABLE `cauhoi`
  ADD CONSTRAINT `cauhoi_ibfk_1` FOREIGN KEY (`monHocId`) REFERENCES `monhoc` (`id`),
  ADD CONSTRAINT `cauhoi_ibfk_2` FOREIGN KEY (`theLoaiId`) REFERENCES `theloaicauhoi` (`id`);

--
-- Constraints for table `dapan`
--
ALTER TABLE `dapan`
  ADD CONSTRAINT `dapan_ibfk_1` FOREIGN KEY (`cauHoiId`) REFERENCES `cauhoi` (`id`);

--
-- Constraints for table `dethi`
--
ALTER TABLE `dethi`
  ADD CONSTRAINT `dethi_ibfk_1` FOREIGN KEY (`kyThiId`) REFERENCES `kythi` (`id`),
  ADD CONSTRAINT `dethi_ibfk_2` FOREIGN KEY (`nguoiTaoId`) REFERENCES `taikhoan` (`id`);

--
-- Constraints for table `dethicauhoi`
--
ALTER TABLE `dethicauhoi`
  ADD CONSTRAINT `dethicauhoi_ibfk_1` FOREIGN KEY (`deThiId`) REFERENCES `dethi` (`id`),
  ADD CONSTRAINT `dethicauhoi_ibfk_2` FOREIGN KEY (`cauHoiId`) REFERENCES `cauhoi` (`id`);

--
-- Constraints for table `kythi`
--
ALTER TABLE `kythi`
  ADD CONSTRAINT `kythi_ibfk_1` FOREIGN KEY (`monHocId`) REFERENCES `monhoc` (`id`),
  ADD CONSTRAINT `kythi_ibfk_2` FOREIGN KEY (`nguoiTaoId`) REFERENCES `taikhoan` (`id`);

--
-- Constraints for table `monhoc`
--
ALTER TABLE `monhoc`
  ADD CONSTRAINT `monhoc_ibfk_1` FOREIGN KEY (`nganhId`) REFERENCES `nganh` (`id`);

--
-- Constraints for table `nganh`
--
ALTER TABLE `nganh`
  ADD CONSTRAINT `nganh_ibfk_1` FOREIGN KEY (`khoaId`) REFERENCES `khoa` (`id`);

--
-- Constraints for table `sobaodanh`
--
ALTER TABLE `sobaodanh`
  ADD CONSTRAINT `sobaodanh_ibfk_1` FOREIGN KEY (`kyThiId`) REFERENCES `kythi` (`id`),
  ADD CONSTRAINT `sobaodanh_ibfk_2` FOREIGN KEY (`sinhVienId`) REFERENCES `sinhvien` (`id`);

--
-- Constraints for table `theloaicauhoi`
--
ALTER TABLE `theloaicauhoi`
  ADD CONSTRAINT `theloaicauhoi_ibfk_1` FOREIGN KEY (`monHocId`) REFERENCES `monhoc` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
