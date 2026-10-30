SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- 1. CÁC BẢNG ĐỘC LẬP & THÔNG TIN CƠ BẢN
-- --------------------------------------------------------

CREATE TABLE `cua_hang` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ten_cua_hang` varchar(150) NOT NULL,
  `so_dien_thoai` varchar(15) DEFAULT NULL,
  `dia_chi` varchar(255) NOT NULL,
  `kinh_do` decimal(10,7) DEFAULT NULL,
  `vi_do` decimal(10,7) DEFAULT NULL,
  `gio_mo_cua` time NOT NULL DEFAULT '06:00:00',
  `gio_dong_cua` time NOT NULL DEFAULT '21:30:00',
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1: Hoat dong, 0: Tam dong cua',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nha_cung_cap` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_ncc` varchar(30) NOT NULL,
  `ten_nha_cung_cap` varchar(150) NOT NULL,
  `nguoi_lien_he` varchar(100) DEFAULT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `ma_so_thue` varchar(30) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1: Hoat dong, 0: Ngung hop tac',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nha_cung_cap_ma_ncc_unique` (`ma_ncc`),
  KEY `nha_cung_cap_trang_thai_index` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_giam_gia` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_khuyen_mai` varchar(30) NOT NULL,
  `ten_chuong_trinh` varchar(150) NOT NULL,
  `loai_giam_gia` enum('phan_tram','tien_mat') NOT NULL,
  `gia_tri_giam` decimal(12,2) NOT NULL,
  `don_hang_toi_thieu` decimal(12,2) NOT NULL DEFAULT 0.00,
  `giam_toi_da` decimal(12,2) DEFAULT NULL,
  `ngay_bat_dau` datetime NOT NULL,
  `ngay_ket_thuc` datetime NOT NULL,
  `so_luong` int(11) NOT NULL,
  `da_su_dung` int(11) NOT NULL DEFAULT 0,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_giam_gia_ma_khuyen_mai_unique` (`ma_khuyen_mai`),
  KEY `ma_giam_gia_thoi_gian_index` (`ngay_bat_dau`,`ngay_ket_thuc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `danh_muc` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ten_danh_muc` varchar(100) NOT NULL,
  `duong_dan_seo` varchar(120) NOT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `danh_muc_cha_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `danh_muc_duong_dan_seo_unique` (`duong_dan_seo`),
  KEY `danh_muc_danh_muc_cha_id_foreign` (`danh_muc_cha_id`),
  CONSTRAINT `danh_muc_danh_muc_cha_id_foreign` FOREIGN KEY (`danh_muc_cha_id`) REFERENCES `danh_muc` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. TÀI KHOẢN & ĐỊA CHỈ
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL, -- Cho phép NULL nếu khách đăng ký bằng Email
  `email` varchar(100) DEFAULT NULL, -- Cho phép NULL nếu khách đăng ký bằng Số điện thoại
  `password` varchar(255) NOT NULL,
  `role` enum('khach_hang','nhan_vien','quan_ly','admin') NOT NULL DEFAULT 'khach_hang',
  `store_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Chi nhanh truc thuoc neu la nhan vien/quan ly',
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`),
  KEY `users_status_index` (`status`),
  KEY `users_store_id_index` (`store_id`),
  CONSTRAINT `users_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `cua_hang` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dia_chi_giao_hang` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ten_nguoi_nhan` varchar(100) NOT NULL,
  `so_dien_thoai_nhan` varchar(15) NOT NULL,
  `dia_chi_chi_tiet` varchar(255) NOT NULL,
  `phuong_xa` varchar(100) NOT NULL,
  `quan_huyen` varchar(100) NOT NULL,
  `tinh_thanh` varchar(100) NOT NULL,
  `la_mac_dinh` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dia_chi_giao_hang_user_id_foreign` (`user_id`),
  CONSTRAINT `dia_chi_giao_hang_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. SẢN PHẨM & QUY ĐỊNH GIÁ CHI NHÁNH
-- --------------------------------------------------------

CREATE TABLE `san_pham` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_va_vach` varchar(50) DEFAULT NULL,
  `ten_san_pham` varchar(200) NOT NULL,
  `duong_dan_seo` varchar(220) NOT NULL,
  `danh_muc_id` bigint(20) UNSIGNED NOT NULL,
  `gia_ban` decimal(12,2) NOT NULL COMMENT 'Gia niem yet mac dinh',
  `gia_giam` decimal(12,2) DEFAULT NULL,
  `don_vi_tinh` varchar(20) NOT NULL COMMENT 'kg, gram, goi, chai, khay',
  `quy_cach` varchar(100) DEFAULT NULL COMMENT 'Vi du: Khay 500g, Bo 300g',
  `dieu_kien_bao_quan` enum('thuong','mat','dong_lanh') NOT NULL DEFAULT 'thuong' COMMENT 'Phuc vu dong goi va giao hang',
  `han_su_dung_ngay` int(11) DEFAULT NULL COMMENT 'So ngay su dung toi da tu luc dong goi/nhap',
  `mo_ta` text DEFAULT NULL,
  `hinh_anh_chinh` varchar(255) DEFAULT NULL,
  `la_tuoi_song` tinyint(1) NOT NULL DEFAULT 0,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `san_pham_duong_dan_seo_unique` (`duong_dan_seo`),
  UNIQUE KEY `san_pham_ma_va_vach_unique` (`ma_va_vach`),
  KEY `san_pham_danh_muc_id_foreign` (`danh_muc_id`),
  KEY `san_pham_trang_thai_index` (`trang_thai`),
  KEY `san_pham_la_tuoi_song_index` (`la_tuoi_song`),
  CONSTRAINT `san_pham_danh_muc_id_foreign` FOREIGN KEY (`danh_muc_id`) REFERENCES `danh_muc` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hinh_anh_san_pham` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `duong_dan_anh` varchar(255) NOT NULL,
  `thu_tu` int(11) NOT NULL DEFAULT 0,
  `la_anh_chinh` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hinh_anh_san_pham_san_pham_id_index` (`san_pham_id`),
  CONSTRAINT `hinh_anh_san_pham_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `gia_san_pham_cua_hang` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `gia_ban` decimal(12,2) NOT NULL COMMENT 'Gia ban thuc te rieng cho chi nhanh',
  `gia_giam` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cuahang_sanpham_gia` (`cua_hang_id`,`san_pham_id`),
  CONSTRAINT `fk_gia_cua_hang` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gia_san_pham` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. KHO HÀNG & LÔ HÀNG (FEFO)
-- --------------------------------------------------------

CREATE TABLE `phieu_nhap` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_phieu` varchar(30) NOT NULL,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `nha_cung_cap_id` bigint(20) UNSIGNED NOT NULL,
  `nguoi_tao_id` bigint(20) UNSIGNED NOT NULL,
  `nguoi_duyet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tong_tien` decimal(14,2) NOT NULL DEFAULT 0.00,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0: Cho duyet, 1: Da nhap kho, 2: Da huy',
  `ngay_nhap` timestamp NULL DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phieu_nhap_ma_phieu_unique` (`ma_phieu`),
  KEY `phieu_nhap_nguoi_tao_id_foreign` (`nguoi_tao_id`),
  KEY `phieu_nhap_nguoi_duyet_id_foreign` (`nguoi_duyet_id`),
  KEY `phieu_nhap_trang_thai_index` (`trang_thai`),
  KEY `phieu_nhap_cua_hang_id_index` (`cua_hang_id`),
  KEY `phieu_nhap_nha_cung_cap_id_index` (`nha_cung_cap_id`),
  KEY `phieu_nhap_ngay_nhap_index` (`ngay_nhap`),
  CONSTRAINT `phieu_nhap_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`),
  CONSTRAINT `phieu_nhap_nguoi_duyet_id_foreign` FOREIGN KEY (`nguoi_duyet_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phieu_nhap_nguoi_tao_id_foreign` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `users` (`id`),
  CONSTRAINT `phieu_nhap_nha_cung_cap_id_foreign` FOREIGN KEY (`nha_cung_cap_id`) REFERENCES `nha_cung_cap` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lo_hang` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_lo` varchar(50) NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `nha_cung_cap_id` bigint(20) UNSIGNED DEFAULT NULL,
  `phieu_nhap_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ngay_san_xuat` date DEFAULT NULL,
  `ngay_het_han` date NOT NULL,
  `so_luong_nhap` decimal(12,2) NOT NULL,
  `so_luong_con_lai` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gia_nhap` decimal(12,2) NOT NULL,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1: Con su dung, 0: Het hang/Huy/Het han',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lo_hang_ma_lo_unique` (`ma_lo`),
  KEY `lo_hang_cua_hang_id_foreign` (`cua_hang_id`),
  KEY `lo_hang_nha_cung_cap_id_foreign` (`nha_cung_cap_id`),
  KEY `lo_hang_phieu_nhap_id_foreign` (`phieu_nhap_id`),
  KEY `idx_fefo_query` (`san_pham_id`,`cua_hang_id`,`trang_thai`,`ngay_het_han`),
  KEY `lo_hang_ngay_het_han_index` (`ngay_het_han`),
  KEY `lo_hang_trang_thai_index` (`trang_thai`),
  CONSTRAINT `lo_hang_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`),
  CONSTRAINT `lo_hang_nha_cung_cap_id_foreign` FOREIGN KEY (`nha_cung_cap_id`) REFERENCES `nha_cung_cap` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lo_hang_phieu_nhap_id_foreign` FOREIGN KEY (`phieu_nhap_id`) REFERENCES `phieu_nhap` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lo_hang_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_tiet_phieu_nhap` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phieu_nhap_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `lo_hang_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Lien ket truc tiep voi ban ghi lo hang da tao',
  `ma_lo` varchar(50) DEFAULT NULL,
  `so_luong` decimal(12,2) NOT NULL,
  `don_gia_nhap` decimal(12,2) NOT NULL,
  `thanh_tien` decimal(14,2) NOT NULL,
  `ngay_san_xuat` date DEFAULT NULL,
  `ngay_het_han` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chi_tiet_phieu_nhap_phieu_nhap_id_index` (`phieu_nhap_id`),
  KEY `chi_tiet_phieu_nhap_san_pham_id_index` (`san_pham_id`),
  KEY `chi_tiet_phieu_nhap_lo_hang_id_foreign` (`lo_hang_id`),
  KEY `chi_tiet_phieu_nhap_ngay_het_han_index` (`ngay_het_han`),
  CONSTRAINT `chi_tiet_phieu_nhap_phieu_nhap_id_foreign` FOREIGN KEY (`phieu_nhap_id`) REFERENCES `phieu_nhap` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chi_tiet_phieu_nhap_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`),
  CONSTRAINT `chi_tiet_phieu_nhap_lo_hang_id_foreign` FOREIGN KEY (`lo_hang_id`) REFERENCES `lo_hang` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ton_kho` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `so_luong_ton` decimal(12,2) NOT NULL DEFAULT 0.00,
  `so_luong_tam_giu` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'So luong dang nam trong don cho xu ly',
  `nguong_canh_bao_thap` decimal(10,2) NOT NULL DEFAULT 10.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cuahang_sanpham` (`cua_hang_id`,`san_pham_id`),
  KEY `ton_kho_cua_hang_id_index` (`cua_hang_id`),
  KEY `ton_kho_san_pham_id_index` (`san_pham_id`),
  CONSTRAINT `ton_kho_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ton_kho_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. GIỎ HÀNG & ĐƠN HÀNG
-- --------------------------------------------------------

CREATE TABLE `gio_hang` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `so_luong` decimal(10,2) NOT NULL DEFAULT 1.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gio_hang_user_cuahang_sanpham_unique` (`user_id`,`cua_hang_id`,`san_pham_id`),
  KEY `gio_hang_cua_hang_id_foreign` (`cua_hang_id`),
  KEY `gio_hang_san_pham_id_foreign` (`san_pham_id`),
  CONSTRAINT `gio_hang_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gio_hang_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gio_hang_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `don_hang` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_don_hang` varchar(30) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `dia_chi_giao_hang_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ma_giam_gia_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tong_tien_hang` decimal(12,2) NOT NULL,
  `phi_giao_hang` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tien_giam_gia` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tong_thanhtoan` decimal(12,2) NOT NULL,
  `phuong_thuc_thanh_toan` enum('cod','momo','vnpay','chuyen_khoan') NOT NULL DEFAULT 'cod',
  `ma_giao_dich` varchar(100) DEFAULT NULL,
  `trang_thai_thanh_toan` enum('chua_thanh_toan','da_thanh_toan','hoan_tien') NOT NULL DEFAULT 'chua_thanh_toan',
  `trang_thai_don_hang` enum('cho_xac_nhan','dang_chuan_bi','dang_giao','da_giao','da_huy') NOT NULL DEFAULT 'cho_xac_nhan',
  `ten_nguoi_nhan` varchar(100) NOT NULL,
  `so_dien_thoai_nhan` varchar(15) NOT NULL,
  `dia_chi_giao` varchar(255) NOT NULL,
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `don_hang_ma_don_hang_unique` (`ma_don_hang`),
  KEY `don_hang_user_id_foreign` (`user_id`),
  KEY `don_hang_dia_chi_giao_hang_id_foreign` (`dia_chi_giao_hang_id`),
  KEY `don_hang_ma_giam_gia_id_foreign` (`ma_giam_gia_id`),
  KEY `don_hang_trang_thai_don_hang_index` (`trang_thai_don_hang`),
  KEY `don_hang_trang_thai_thanh_toan_index` (`trang_thai_thanh_toan`),
  KEY `don_hang_cua_hang_id_index` (`cua_hang_id`),
  CONSTRAINT `don_hang_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`),
  CONSTRAINT `don_hang_dia_chi_giao_hang_id_foreign` FOREIGN KEY (`dia_chi_giao_hang_id`) REFERENCES `dia_chi_giao_hang` (`id`) ON DELETE SET NULL,
  CONSTRAINT `don_hang_ma_giam_gia_id_foreign` FOREIGN KEY (`ma_giam_gia_id`) REFERENCES `ma_giam_gia` (`id`) ON DELETE SET NULL,
  CONSTRAINT `don_hang_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_tiet_don_hang` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `don_hang_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `ten_san_pham` varchar(200) NOT NULL,
  `hinh_anh_snapshot` varchar(255) DEFAULT NULL COMMENT 'Luu anh tai thoi diem mua de tranh sai lech khi sua SP',
  `don_vi_tinh` varchar(20) DEFAULT NULL,
  `don_gia` decimal(12,2) NOT NULL,
  `gia_von` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Gia von trung binh/lo de tinh bien loi nhuan gop',
  `so_luong` decimal(10,2) NOT NULL,
  `thanh_tien` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chi_tiet_don_hang_don_hang_id_index` (`don_hang_id`),
  KEY `chi_tiet_don_hang_san_pham_id_index` (`san_pham_id`),
  CONSTRAINT `chi_tiet_don_hang_don_hang_id_foreign` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chi_tiet_don_hang_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. XUẤT KHO, PHÂN BỔ THEO LÔ & KIỂM KÊ
-- --------------------------------------------------------

CREATE TABLE `phieu_xuat` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_phieu` varchar(30) NOT NULL,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `loai_xuat` tinyint(4) NOT NULL COMMENT '1: Xuat ban hang, 2: Chuyen kho noi bo, 3: Tra NCC, 4: Xuat huy (hong/het han)',
  `don_hang_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cua_hang_nhan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nha_cung_cap_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nguoi_tao_id` bigint(20) UNSIGNED NOT NULL,
  `nguoi_duyet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0: Cho duyet, 1: Da xuat, 2: Da huy',
  `tong_so_luong` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tong_gia_tri` decimal(14,2) NOT NULL DEFAULT 0.00,
  `ly_do` text DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `xuat_luc` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phieu_xuat_ma_phieu_unique` (`ma_phieu`),
  KEY `phieu_xuat_don_hang_id_foreign` (`don_hang_id`),
  KEY `phieu_xuat_cua_hang_nhan_id_foreign` (`cua_hang_nhan_id`),
  KEY `phieu_xuat_nha_cung_cap_id_foreign` (`nha_cung_cap_id`),
  KEY `phieu_xuat_nguoi_tao_id_foreign` (`nguoi_tao_id`),
  KEY `phieu_xuat_nguoi_duyet_id_foreign` (`nguoi_duyet_id`),
  KEY `phieu_xuat_loai_xuat_index` (`loai_xuat`),
  KEY `phieu_xuat_trang_thai_index` (`trang_thai`),
  KEY `idx_ch_loai` (`cua_hang_id`,`loai_xuat`),
  KEY `phieu_xuat_xuat_luc_index` (`xuat_luc`),
  CONSTRAINT `phieu_xuat_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`),
  CONSTRAINT `phieu_xuat_cua_hang_nhan_id_foreign` FOREIGN KEY (`cua_hang_nhan_id`) REFERENCES `cua_hang` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phieu_xuat_don_hang_id_foreign` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phieu_xuat_nguoi_duyet_id_foreign` FOREIGN KEY (`nguoi_duyet_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phieu_xuat_nguoi_tao_id_foreign` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `users` (`id`),
  CONSTRAINT `phieu_xuat_nha_cung_cap_id_foreign` FOREIGN KEY (`nha_cung_cap_id`) REFERENCES `nha_cung_cap` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_tiet_phieu_xuat` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phieu_xuat_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `so_luong` decimal(12,2) NOT NULL,
  `gia_xuat` decimal(12,2) DEFAULT NULL,
  `thanh_tien` decimal(14,2) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chi_tiet_phieu_xuat_phieu_xuat_id_index` (`phieu_xuat_id`),
  KEY `chi_tiet_phieu_xuat_san_pham_id_index` (`san_pham_id`),
  CONSTRAINT `chi_tiet_phieu_xuat_phieu_xuat_id_foreign` FOREIGN KEY (`phieu_xuat_id`) REFERENCES `phieu_xuat` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chi_tiet_phieu_xuat_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_tiet_xuat_kho` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `chi_tiet_phieu_xuat_id` bigint(20) UNSIGNED NOT NULL,
  `lo_hang_id` bigint(20) UNSIGNED NOT NULL,
  `so_luong` decimal(12,2) NOT NULL,
  `gia_xuat` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chi_tiet_xuat_kho_chi_tiet_phieu_xuat_id_index` (`chi_tiet_phieu_xuat_id`),
  KEY `chi_tiet_xuat_kho_lo_hang_id_index` (`lo_hang_id`),
  CONSTRAINT `chi_tiet_xuat_kho_chi_tiet_phieu_xuat_id_foreign` FOREIGN KEY (`chi_tiet_phieu_xuat_id`) REFERENCES `chi_tiet_phieu_xuat` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chi_tiet_xuat_kho_lo_hang_id_foreign` FOREIGN KEY (`lo_hang_id`) REFERENCES `lo_hang` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phieu_kiem_ke` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_phieu` varchar(30) NOT NULL,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `nguoi_tao_id` bigint(20) UNSIGNED NOT NULL,
  `nguoi_duyet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ngay_kiem_ke` datetime NOT NULL,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0: Dang kiem ke, 1: Da can bang kho, 2: Da huy',
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phieu_kiem_ke_ma_phieu_unique` (`ma_phieu`),
  KEY `phieu_kiem_ke_cua_hang_id_foreign` (`cua_hang_id`),
  KEY `phieu_kiem_ke_nguoi_tao_id_foreign` (`nguoi_tao_id`),
  KEY `phieu_kiem_ke_nguoi_duyet_id_foreign` (`nguoi_duyet_id`),
  KEY `phieu_kiem_ke_trang_thai_index` (`trang_thai`),
  CONSTRAINT `phieu_kiem_ke_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`),
  CONSTRAINT `phieu_kiem_ke_nguoi_duyet_id_foreign` FOREIGN KEY (`nguoi_duyet_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phieu_kiem_ke_nguoi_tao_id_foreign` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_tiet_kiem_ke` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phieu_kiem_ke_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `lo_hang_id` bigint(20) UNSIGNED DEFAULT NULL,
  `so_luong_he_thong` decimal(12,2) NOT NULL,
  `so_luong_thuc_te` decimal(12,2) NOT NULL,
  `so_luong_lech` decimal(12,2) GENERATED ALWAYS AS (`so_luong_thuc_te` - `so_luong_he_thong`) STORED,
  `ly_do_lech` varchar(255) DEFAULT NULL COMMENT 'Hao hut tu nhien, dap nat, mat mat, sai so can do',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chi_tiet_kiem_ke_phieu_kiem_ke_id_index` (`phieu_kiem_ke_id`),
  KEY `chi_tiet_kiem_ke_san_pham_id_index` (`san_pham_id`),
  KEY `chi_tiet_kiem_ke_lo_hang_id_foreign` (`lo_hang_id`),
  CONSTRAINT `chi_tiet_kiem_ke_phieu_kiem_ke_id_foreign` FOREIGN KEY (`phieu_kiem_ke_id`) REFERENCES `phieu_kiem_ke` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chi_tiet_kiem_ke_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`),
  CONSTRAINT `chi_tiet_kiem_ke_lo_hang_id_foreign` FOREIGN KEY (`lo_hang_id`) REFERENCES `lo_hang` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `the_kho` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `lo_hang_id` bigint(20) UNSIGNED DEFAULT NULL,
  `loai_thao_tac` enum('nhap_kho','xuat_ban','xuat_huy','xuat_chuyen','nhap_chuyen','can_bang_kiem_ke') NOT NULL,
  `so_luong_thay_doi` decimal(12,2) NOT NULL COMMENT 'So duong neu cong kho, so am neu tru kho',
  `ton_sau_thay_doi` decimal(12,2) NOT NULL,
  `ma_chung_tu` varchar(50) NOT NULL COMMENT 'Luu ma phieu xuat, phieu nhap hoac phieu kiem ke tuong ung',
  `nguoi_thuc_hien_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `the_kho_cua_hang_san_pham_index` (`cua_hang_id`,`san_pham_id`),
  KEY `the_kho_lo_hang_id_index` (`lo_hang_id`),
  KEY `the_kho_ma_chung_tu_index` (`ma_chung_tu`),
  KEY `the_kho_created_at_index` (`created_at`),
  CONSTRAINT `the_kho_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`),
  CONSTRAINT `the_kho_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`),
  CONSTRAINT `the_kho_lo_hang_id_foreign` FOREIGN KEY (`lo_hang_id`) REFERENCES `lo_hang` (`id`) ON DELETE SET NULL,
  CONSTRAINT `the_kho_nguoi_thuc_hien_id_foreign` FOREIGN KEY (`nguoi_thuc_hien_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. TƯƠNG TÁC: ĐÁNH GIÁ & THÔNG BÁO
-- --------------------------------------------------------

CREATE TABLE `danh_gia_san_pham` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `don_hang_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Xac nhan da mua hang',
  `so_sao` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `noi_dung` text DEFAULT NULL,
  `hinh_anh` text DEFAULT NULL COMMENT 'JSON luu mang duong dan anh thuc te',
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1: Da duyet, 0: Cho duyet, -1: An do vi pham',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `danh_gia_san_pham_user_id_foreign` (`user_id`),
  KEY `danh_gia_san_pham_san_pham_id_foreign` (`san_pham_id`),
  KEY `danh_gia_san_pham_don_hang_id_foreign` (`don_hang_id`),
  KEY `danh_gia_san_pham_trang_thai_index` (`trang_thai`),
  CONSTRAINT `danh_gia_san_pham_don_hang_id_foreign` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE SET NULL,
  CONSTRAINT `danh_gia_san_pham_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE,
  CONSTRAINT `danh_gia_san_pham_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `thong_bao` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tieu_de` varchar(150) NOT NULL,
  `noi_dung` text NOT NULL,
  `duong_dan` varchar(255) DEFAULT NULL,
  `loai_thong_bao` enum('don_hang','khuyen_mai','he_thong') NOT NULL,
  `da_doc` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `thong_bao_user_id_da_doc_index` (`user_id`,`da_doc`),
  KEY `thong_bao_loai_thong_bao_index` (`loai_thong_bao`),
  CONSTRAINT `thong_bao_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. LARAVEL SYSTEM TABLES
-- --------------------------------------------------------

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)

-- --------------------------------------------------------
-- 9. QUẢN LÝ ĐỔI TRẢ HÀNG (Dành cho thực phẩm tươi sống / lỗi / hỏng)
-- --------------------------------------------------------

CREATE TABLE `phieu_doi_tra` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_phieu` varchar(30) NOT NULL,
  `don_hang_id` bigint(20) UNSIGNED NOT NULL,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `nguoi_tao_id` bigint(20) UNSIGNED NOT NULL,
  `nguoi_duyet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `loai_doi_tra` enum('doi_hang','hoan_tien') NOT NULL DEFAULT 'hoan_tien',
  `tong_tien_hoan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0: Cho xuyet, 1: Da xuat kho doi/hoan tien, 2: Tu choi, 3: Da huy',
  `ly_do` text NOT NULL,
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phieu_doi_tra_ma_phieu_unique` (`ma_phieu`),
  KEY `phieu_doi_tra_don_hang_id_foreign` (`don_hang_id`),
  KEY `phieu_doi_tra_cua_hang_id_foreign` (`cua_hang_id`),
  KEY `phieu_doi_tra_user_id_foreign` (`user_id`),
  KEY `phieu_doi_tra_trang_thai_index` (`trang_thai`),
  CONSTRAINT `phieu_doi_tra_don_hang_id_foreign` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`),
  CONSTRAINT `phieu_doi_tra_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`),
  CONSTRAINT `phieu_doi_tra_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_tiet_doi_tra` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phieu_doi_tra_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `lo_hang_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Lo hang nhan lai neu hang con dung duoc hoac tra NCC',
  `so_luong` decimal(12,2) NOT NULL,
  `don_gia` decimal(12,2) NOT NULL,
  `thanh_tien` decimal(12,2) NOT NULL,
  `tinh_trang_hang` enum('con_dung_duoc','hu_hong_huy','tra_ve_nha_cung_cap') NOT NULL DEFAULT 'hu_hong_huy',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chi_tiet_doi_tra_phieu_doi_tra_id_index` (`phieu_doi_tra_id`),
  KEY `chi_tiet_doi_tra_san_pham_id_index` (`san_pham_id`),
  KEY `chi_tiet_doi_tra_lo_hang_id_foreign` (`lo_hang_id`),
  CONSTRAINT `chi_tiet_doi_tra_phieu_doi_tra_id_foreign` FOREIGN KEY (`phieu_doi_tra_id`) REFERENCES `phieu_doi_tra` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chi_tiet_doi_tra_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`),
  CONSTRAINT `chi_tiet_doi_tra_lo_hang_id_foreign` FOREIGN KEY (`lo_hang_id`) REFERENCES `lo_hang` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 10. LỊCH SỬ TÍCH ĐIỂM VÀ SỬ DỤNG ĐIỂM THƯỞNG (Loyalty Program)
-- --------------------------------------------------------

CREATE TABLE `lich_su_tich_diem` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `don_hang_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Don hang phat sinh giao dịch điểm',
  `loai_giao_dich` enum('tich_diem','tieu_diem','hoan_diem','admin_dieu_chinh') NOT NULL,
  `so_diem_thay_doi` int(11) NOT NULL COMMENT 'So duong neu cong diem, so am neu tru diem',
  `so_diem_sau_giao_dich` int(11) NOT NULL,
  `ghi_chu` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lich_su_tich_diem_user_id_index` (`user_id`),
  KEY `lich_su_tich_diem_don_hang_id_foreign` (`don_hang_id`),
  KEY `lich_su_tich_diem_loai_giao_dich_index` (`loai_giao_dich`),
  CONSTRAINT `lich_su_tich_diem_user_id_index` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lich_su_tich_diem_don_hang_id_foreign` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;