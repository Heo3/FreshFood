SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table structure for table `users` (Đã đổi từ nguoi_dung)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Đổi từ ho_ten',
  `phone` varchar(15) NOT NULL COMMENT 'Đổi từ so_dien_thoai, dùng đăng nhập',
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Đổi từ mat_khau',
  `role` enum('khach_hang','nhan_vien','quan_ly','admin') NOT NULL DEFAULT 'khach_hang' COMMENT 'Đổi từ vai_tro',
  `store_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Đổi từ cua_hang_id',
  `loyalty_points` int(11) NOT NULL DEFAULT 0 COMMENT 'Đổi từ diem_tich_luy',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Đổi từ trang_thai',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Các bảng giữ nguyên cấu trúc
-- --------------------------------------------------------
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cua_hang` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ten_cua_hang` varchar(150) NOT NULL,
  `so_dien_thoai` varchar(15) DEFAULT NULL,
  `dia_chi` varchar(255) NOT NULL,
  `kinh_do` decimal(10,7) DEFAULT NULL,
  `vi_do` decimal(10,7) DEFAULT NULL,
  `gio_mo_cua` time NOT NULL DEFAULT '06:00:00',
  `gio_dong_cua` time NOT NULL DEFAULT '21:30:00',
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `danh_muc` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ten_danh_muc` varchar(100) NOT NULL,
  `duong_dan_seo` varchar(120) NOT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `danh_muc_cha_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dia_chi_giao_hang` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Đổi từ nguoi_dung_id',
  `ten_nguoi_nhan` varchar(100) NOT NULL,
  `so_dien_thoai_nhan` varchar(15) NOT NULL,
  `dia_chi_chi_tiet` varchar(255) NOT NULL,
  `phuong_xa` varchar(100) NOT NULL,
  `quan_huyen` varchar(100) NOT NULL,
  `tinh_thanh` varchar(100) NOT NULL,
  `la_mac_dinh` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `don_hang` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_don_hang` varchar(30) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Đổi từ nguoi_dung_id',
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
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `gio_hang` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Đổi từ nguoi_dung_id',
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `so_luong` decimal(10,2) NOT NULL DEFAULT 1.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hinh_anh_san_pham` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `duong_dan_anh` varchar(255) NOT NULL,
  `thu_tu` int(11) NOT NULL DEFAULT 0,
  `la_anh_chinh` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
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
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lo_hang` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_giam_gia` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_khuyen_mai` varchar(30) NOT NULL,
  `loai_giam_gia` enum('phan_tram','tien_mat') NOT NULL,
  `gia_tri_giam` decimal(12,2) NOT NULL,
  `don_hang_toi_thieu` decimal(12,2) NOT NULL DEFAULT 0.00,
  `giam_toi_da` decimal(12,2) DEFAULT NULL,
  `ngay_bat_dau` datetime NOT NULL,
  `ngay_ket_thuc` datetime NOT NULL,
  `so_luong` int(11) NOT NULL,
  `da_su_dung` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nha_cung_cap` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_ncc` varchar(30) NOT NULL,
  `ten_nha_cung_cap` varchar(150) NOT NULL,
  `nguoi_lien_he` varchar(100) DEFAULT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `ma_so_thue` varchar(30) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phieu_nhap` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_phieu` varchar(30) NOT NULL,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `nha_cung_cap_id` bigint(20) UNSIGNED NOT NULL,
  `nguoi_tao_id` bigint(20) UNSIGNED NOT NULL,
  `nguoi_duyet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tong_tien` decimal(14,2) NOT NULL DEFAULT 0.00,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 0,
  `ngay_nhap` timestamp NULL DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phieu_xuat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_phieu` varchar(30) NOT NULL,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `loai_xuat` tinyint(4) NOT NULL,
  `don_hang_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cua_hang_nhan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nha_cung_cap_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nguoi_tao_id` bigint(20) UNSIGNED NOT NULL,
  `nguoi_duyet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 0,
  `tong_so_luong` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tong_gia_tri` decimal(14,2) NOT NULL DEFAULT 0.00,
  `ly_do` text DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `xuat_luc` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `san_pham` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_va_vach` varchar(50) DEFAULT NULL,
  `ten_san_pham` varchar(200) NOT NULL,
  `duong_dan_seo` varchar(220) NOT NULL,
  `danh_muc_id` bigint(20) UNSIGNED NOT NULL,
  `gia_ban` decimal(12,2) NOT NULL,
  `gia_giam` decimal(12,2) DEFAULT NULL,
  `don_vi_tinh` varchar(20) NOT NULL,
  `quy_cach` varchar(100) DEFAULT NULL,
  `mo_ta` text DEFAULT NULL,
  `hinh_anh_chinh` varchar(255) DEFAULT NULL,
  `la_tuoi_song` tinyint(1) NOT NULL DEFAULT 0,
  `trang_thai` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `thong_bao` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Đổi từ nguoi_dung_id',
  `tieu_de` varchar(150) NOT NULL,
  `noi_dung` text NOT NULL,
  `duong_dan` varchar(255) DEFAULT NULL,
  `loai_thong_bao` enum('don_hang','khuyen_mai','he_thong') NOT NULL,
  `da_doc` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ton_kho` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cua_hang_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `so_luong_ton` decimal(12,2) NOT NULL DEFAULT 0.00,
  `so_luong_tam_giu` decimal(12,2) NOT NULL DEFAULT 0.00,
  `nguong_canh_bao_thap` decimal(10,2) NOT NULL DEFAULT 10.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_tiet_don_hang` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `don_hang_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `ten_san_pham` varchar(200) NOT NULL,
  `don_vi_tinh` varchar(20) DEFAULT NULL,
  `don_gia` decimal(12,2) NOT NULL,
  `so_luong` decimal(10,2) NOT NULL,
  `thanh_tien` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_tiet_phieu_nhap` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `phieu_nhap_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `ma_lo` varchar(50) DEFAULT NULL,
  `so_luong` decimal(12,2) NOT NULL,
  `don_gia_nhap` decimal(12,2) NOT NULL,
  `thanh_tien` decimal(14,2) NOT NULL,
  `ngay_san_xuat` date DEFAULT NULL,
  `ngay_het_han` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_tiet_phieu_xuat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `phieu_xuat_id` bigint(20) UNSIGNED NOT NULL,
  `san_pham_id` bigint(20) UNSIGNED NOT NULL,
  `so_luong` decimal(12,2) NOT NULL,
  `gia_xuat` decimal(12,2) DEFAULT NULL,
  `thanh_tien` decimal(14,2) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_tiet_xuat_kho` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `chi_tiet_phieu_xuat_id` bigint(20) UNSIGNED NOT NULL,
  `lo_hang_id` bigint(20) UNSIGNED NOT NULL,
  `so_luong` decimal(12,2) NOT NULL,
  `gia_xuat` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Indexes
-- --------------------------------------------------------
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_status_index` (`status`),
  ADD KEY `users_store_id_index` (`store_id`);

ALTER TABLE `cache` ADD PRIMARY KEY (`key`), ADD KEY `cache_expiration_index` (`expiration`);
ALTER TABLE `cache_locks` ADD PRIMARY KEY (`key`), ADD KEY `cache_locks_expiration_index` (`expiration`);
ALTER TABLE `cua_hang` ADD PRIMARY KEY (`id`);
ALTER TABLE `danh_muc` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `danh_muc_duong_dan_seo_unique` (`duong_dan_seo`), ADD KEY `danh_muc_danh_muc_cha_id_foreign` (`danh_muc_cha_id`);
ALTER TABLE `dia_chi_giao_hang` ADD PRIMARY KEY (`id`), ADD KEY `dia_chi_giao_hang_user_id_foreign` (`user_id`);
ALTER TABLE `don_hang` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `don_hang_ma_don_hang_unique` (`ma_don_hang`), ADD KEY `don_hang_user_id_foreign` (`user_id`), ADD KEY `don_hang_dia_chi_giao_hang_id_foreign` (`dia_chi_giao_hang_id`), ADD KEY `don_hang_ma_giam_gia_id_foreign` (`ma_giam_gia_id`), ADD KEY `don_hang_trang_thai_don_hang_index` (`trang_thai_don_hang`), ADD KEY `don_hang_trang_thai_thanh_toan_index` (`trang_thai_thanh_toan`), ADD KEY `don_hang_cua_hang_id_index` (`cua_hang_id`);
ALTER TABLE `failed_jobs` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);
ALTER TABLE `gio_hang` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `gio_hang_user_id_san_pham_id_unique` (`user_id`,`san_pham_id`), ADD KEY `gio_hang_san_pham_id_foreign` (`san_pham_id`);
ALTER TABLE `hinh_anh_san_pham` ADD PRIMARY KEY (`id`), ADD KEY `hinh_anh_san_pham_san_pham_id_index` (`san_pham_id`);
ALTER TABLE `jobs` ADD PRIMARY KEY (`id`), ADD KEY `jobs_queue_index` (`queue`);
ALTER TABLE `job_batches` ADD PRIMARY KEY (`id`);
ALTER TABLE `lo_hang` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `lo_hang_ma_lo_unique` (`ma_lo`), ADD KEY `lo_hang_cua_hang_id_foreign` (`cua_hang_id`), ADD KEY `lo_hang_nha_cung_cap_id_foreign` (`nha_cung_cap_id`), ADD KEY `lo_hang_phieu_nhap_id_foreign` (`phieu_nhap_id`), ADD KEY `idx_fefo_query` (`san_pham_id`,`cua_hang_id`,`trang_thai`,`ngay_het_han`), ADD KEY `lo_hang_ngay_het_han_index` (`ngay_het_han`), ADD KEY `lo_hang_trang_thai_index` (`trang_thai`);
ALTER TABLE `ma_giam_gia` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `ma_giam_gia_ma_khuyen_mai_unique` (`ma_khuyen_mai`);
ALTER TABLE `migrations` ADD PRIMARY KEY (`id`);
ALTER TABLE `nha_cung_cap` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `nha_cung_cap_ma_ncc_unique` (`ma_ncc`), ADD KEY `nha_cung_cap_trang_thai_index` (`trang_thai`);
ALTER TABLE `password_reset_tokens` ADD PRIMARY KEY (`email`);
ALTER TABLE `phieu_nhap` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `phieu_nhap_ma_phieu_unique` (`ma_phieu`), ADD KEY `phieu_nhap_nguoi_tao_id_foreign` (`nguoi_tao_id`), ADD KEY `phieu_nhap_nguoi_duyet_id_foreign` (`nguoi_duyet_id`), ADD KEY `phieu_nhap_trang_thai_index` (`trang_thai`), ADD KEY `phieu_nhap_cua_hang_id_index` (`cua_hang_id`), ADD KEY `phieu_nhap_nha_cung_cap_id_index` (`nha_cung_cap_id`), ADD KEY `phieu_nhap_ngay_nhap_index` (`ngay_nhap`);
ALTER TABLE `phieu_xuat` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `phieu_xuat_ma_phieu_unique` (`ma_phieu`), ADD KEY `phieu_xuat_don_hang_id_foreign` (`don_hang_id`), ADD KEY `phieu_xuat_cua_hang_nhan_id_foreign` (`cua_hang_nhan_id`), ADD KEY `phieu_xuat_nha_cung_cap_id_foreign` (`nha_cung_cap_id`), ADD KEY `phieu_xuat_nguoi_tao_id_foreign` (`nguoi_tao_id`), ADD KEY `phieu_xuat_nguoi_duyet_id_foreign` (`nguoi_duyet_id`), ADD KEY `phieu_xuat_loai_xuat_index` (`loai_xuat`), ADD KEY `phieu_xuat_trang_thai_index` (`trang_thai`), ADD KEY `idx_ch_loai` (`cua_hang_id`,`loai_xuat`), ADD KEY `phieu_xuat_xuat_luc_index` (`xuat_luc`);
ALTER TABLE `san_pham` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `san_pham_duong_dan_seo_unique` (`duong_dan_seo`), ADD UNIQUE KEY `san_pham_ma_va_vach_unique` (`ma_va_vach`), ADD KEY `san_pham_danh_muc_id_foreign` (`danh_muc_id`), ADD KEY `san_pham_trang_thai_index` (`trang_thai`), ADD KEY `san_pham_la_tuoi_song_index` (`la_tuoi_song`);
ALTER TABLE `sessions` ADD PRIMARY KEY (`id`), ADD KEY `sessions_user_id_index` (`user_id`), ADD KEY `sessions_last_activity_index` (`last_activity`);
ALTER TABLE `thong_bao` ADD PRIMARY KEY (`id`), ADD KEY `thong_bao_user_id_da_doc_index` (`user_id`,`da_doc`), ADD KEY `thong_bao_loai_thong_bao_index` (`loai_thong_bao`);
ALTER TABLE `ton_kho` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `uk_cuahang_sanpham` (`cua_hang_id`,`san_pham_id`), ADD KEY `ton_kho_cua_hang_id_index` (`cua_hang_id`), ADD KEY `ton_kho_san_pham_id_index` (`san_pham_id`);
ALTER TABLE `chi_tiet_don_hang` ADD PRIMARY KEY (`id`), ADD KEY `chi_tiet_don_hang_don_hang_id_index` (`don_hang_id`), ADD KEY `chi_tiet_don_hang_san_pham_id_index` (`san_pham_id`);
ALTER TABLE `chi_tiet_phieu_nhap` ADD PRIMARY KEY (`id`), ADD KEY `chi_tiet_phieu_nhap_phieu_nhap_id_index` (`phieu_nhap_id`), ADD KEY `chi_tiet_phieu_nhap_san_pham_id_index` (`san_pham_id`), ADD KEY `chi_tiet_phieu_nhap_ngay_het_han_index` (`ngay_het_han`);
ALTER TABLE `chi_tiet_phieu_xuat` ADD PRIMARY KEY (`id`), ADD KEY `chi_tiet_phieu_xuat_phieu_xuat_id_index` (`phieu_xuat_id`), ADD KEY `chi_tiet_phieu_xuat_san_pham_id_index` (`san_pham_id`);
ALTER TABLE `chi_tiet_xuat_kho` ADD PRIMARY KEY (`id`), ADD KEY `chi_tiet_xuat_kho_chi_tiet_phieu_xuat_id_index` (`chi_tiet_phieu_xuat_id`), ADD KEY `chi_tiet_xuat_kho_lo_hang_id_index` (`lo_hang_id`);

-- --------------------------------------------------------
-- AUTO_INCREMENT
-- --------------------------------------------------------
ALTER TABLE `users` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;
ALTER TABLE `cua_hang` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
ALTER TABLE `danh_muc` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;
ALTER TABLE `dia_chi_giao_hang` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;
ALTER TABLE `don_hang` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `failed_jobs` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `gio_hang` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `hinh_anh_san_pham` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `jobs` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `lo_hang` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1442;
ALTER TABLE `ma_giam_gia` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `migrations` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
ALTER TABLE `nha_cung_cap` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `phieu_nhap` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `phieu_xuat` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `san_pham` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;
ALTER TABLE `thong_bao` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ton_kho` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=738;
ALTER TABLE `chi_tiet_don_hang` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `chi_tiet_phieu_nhap` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `chi_tiet_phieu_xuat` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `chi_tiet_xuat_kho` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Foreign keys
-- --------------------------------------------------------
ALTER TABLE `users` ADD CONSTRAINT `users_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `cua_hang` (`id`) ON DELETE SET NULL;
ALTER TABLE `danh_muc` ADD CONSTRAINT `danh_muc_danh_muc_cha_id_foreign` FOREIGN KEY (`danh_muc_cha_id`) REFERENCES `danh_muc` (`id`) ON DELETE SET NULL;
ALTER TABLE `dia_chi_giao_hang` ADD CONSTRAINT `dia_chi_giao_hang_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `don_hang` ADD CONSTRAINT `don_hang_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`), ADD CONSTRAINT `don_hang_dia_chi_giao_hang_id_foreign` FOREIGN KEY (`dia_chi_giao_hang_id`) REFERENCES `dia_chi_giao_hang` (`id`) ON DELETE SET NULL, ADD CONSTRAINT `don_hang_ma_giam_gia_id_foreign` FOREIGN KEY (`ma_giam_gia_id`) REFERENCES `ma_giam_gia` (`id`) ON DELETE SET NULL, ADD CONSTRAINT `don_hang_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
ALTER TABLE `gio_hang` ADD CONSTRAINT `gio_hang_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `gio_hang_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE;
ALTER TABLE `hinh_anh_san_pham` ADD CONSTRAINT `hinh_anh_san_pham_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE;
ALTER TABLE `lo_hang` ADD CONSTRAINT `lo_hang_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`), ADD CONSTRAINT `lo_hang_nha_cung_cap_id_foreign` FOREIGN KEY (`nha_cung_cap_id`) REFERENCES `nha_cung_cap` (`id`) ON DELETE SET NULL, ADD CONSTRAINT `lo_hang_phieu_nhap_id_foreign` FOREIGN KEY (`phieu_nhap_id`) REFERENCES `phieu_nhap` (`id`) ON DELETE SET NULL, ADD CONSTRAINT `lo_hang_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`);
ALTER TABLE `phieu_nhap` ADD CONSTRAINT `phieu_nhap_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`), ADD CONSTRAINT `phieu_nhap_nguoi_duyet_id_foreign` FOREIGN KEY (`nguoi_duyet_id`) REFERENCES `users` (`id`) ON DELETE SET NULL, ADD CONSTRAINT `phieu_nhap_nguoi_tao_id_foreign` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `users` (`id`), ADD CONSTRAINT `phieu_nhap_nha_cung_cap_id_foreign` FOREIGN KEY (`nha_cung_cap_id`) REFERENCES `nha_cung_cap` (`id`);
ALTER TABLE `phieu_xuat` ADD CONSTRAINT `phieu_xuat_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`), ADD CONSTRAINT `phieu_xuat_cua_hang_nhan_id_foreign` FOREIGN KEY (`cua_hang_nhan_id`) REFERENCES `cua_hang` (`id`) ON DELETE SET NULL, ADD CONSTRAINT `phieu_xuat_don_hang_id_foreign` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE SET NULL, ADD CONSTRAINT `phieu_xuat_nguoi_duyet_id_foreign` FOREIGN KEY (`nguoi_duyet_id`) REFERENCES `users` (`id`) ON DELETE SET NULL, ADD CONSTRAINT `phieu_xuat_nguoi_tao_id_foreign` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `users` (`id`), ADD CONSTRAINT `phieu_xuat_nha_cung_cap_id_foreign` FOREIGN KEY (`nha_cung_cap_id`) REFERENCES `nha_cung_cap` (`id`) ON DELETE SET NULL;
ALTER TABLE `san_pham` ADD CONSTRAINT `san_pham_danh_muc_id_foreign` FOREIGN KEY (`danh_muc_id`) REFERENCES `danh_muc` (`id`);
ALTER TABLE `thong_bao` ADD CONSTRAINT `thong_bao_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `ton_kho` ADD CONSTRAINT `ton_kho_cua_hang_id_foreign` FOREIGN KEY (`cua_hang_id`) REFERENCES `cua_hang` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `ton_kho_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE;
ALTER TABLE `chi_tiet_don_hang` ADD CONSTRAINT `chi_tiet_don_hang_don_hang_id_foreign` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `chi_tiet_don_hang_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`);
ALTER TABLE `chi_tiet_phieu_nhap` ADD CONSTRAINT `chi_tiet_phieu_nhap_phieu_nhap_id_foreign` FOREIGN KEY (`phieu_nhap_id`) REFERENCES `phieu_nhap` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `chi_tiet_phieu_nhap_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`);
ALTER TABLE `chi_tiet_phieu_xuat` ADD CONSTRAINT `chi_tiet_phieu_xuat_phieu_xuat_id_foreign` FOREIGN KEY (`phieu_xuat_id`) REFERENCES `phieu_xuat` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `chi_tiet_phieu_xuat_san_pham_id_foreign` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`);
ALTER TABLE `chi_tiet_xuat_kho` ADD CONSTRAINT `chi_tiet_xuat_kho_chi_tiet_phieu_xuat_id_foreign` FOREIGN KEY (`chi_tiet_phieu_xuat_id`) REFERENCES `chi_tiet_phieu_xuat` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `chi_tiet_xuat_kho_lo_hang_id_foreign` FOREIGN KEY (`lo_hang_id`) REFERENCES `lo_hang` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;