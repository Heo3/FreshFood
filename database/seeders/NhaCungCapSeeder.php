<?php

namespace Database\Seeders;

use App\Models\NhaCungCap;
use Illuminate\Database\Seeder;

class NhaCungCapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'ma_ncc' => 'NCC_DALAT',
                'ten_nha_cung_cap' => 'Hợp Tác Xã Nông Sản Hữu Cơ Đà Lạt',
                'nguoi_lien_he' => 'Trần Văn Hải',
                'so_dien_thoai' => '02633889911',
                'email' => 'contact@dalatfarmorganic.vn',
                'dia_chi' => 'Đèo Prenn, Phường 3, TP. Đà Lạt, Tỉnh Lâm Đồng',
                'ma_so_thue' => '5801234567',
                'ghi_chu' => 'Chuyên cung cấp rau củ quả VietGAP và Organic thu hoạch hàng ngày lúc 04h sáng.',
                'trang_thai' => 1,
            ],
            [
                'ma_ncc' => 'NCC_MIENTAY',
                'ten_nha_cung_cap' => 'Vựa Trái Cây Đặc Sản Miền Tây Tiền Giang',
                'nguoi_lien_he' => 'Nguyễn Thị Bích',
                'so_dien_thoai' => '02733991122',
                'email' => 'mientayfruits@gmail.com',
                'dia_chi' => 'Quốc lộ 1A, Huyện Cái Bè, Tỉnh Tiền Giang',
                'ma_so_thue' => '1201998877',
                'ghi_chu' => 'Cung cấp xoài cát Hòa Lộc, bưởi da xanh Bến Tre, thanh long ruột đỏ.',
                'trang_thai' => 1,
            ],
            [
                'ma_ncc' => 'NCC_PHANTHIET',
                'ten_nha_cung_cap' => 'Công Ty Hải Sản Tươi Sống Phan Thiết - Biển Đông',
                'nguoi_lien_he' => 'Lê Văn Nam',
                'so_dien_thoai' => '02523778899',
                'email' => 'phanthietseafood@biendong.vn',
                'dia_chi' => 'Cảng cá Phan Thiết, Phường Đức Thắng, TP. Phan Thiết, Bình Thuận',
                'ma_so_thue' => '3401556677',
                'ghi_chu' => 'Cung cấp hải sản đánh bắt trong ngày, vận chuyển xe lạnh về TP.HCM lúc 5h sáng.',
                'trang_thai' => 1,
            ],
            [
                'ma_ncc' => 'NCC_CP_MEAT',
                'ten_nha_cung_cap' => 'Trang Trại Chăn Nuôi Thịt Sạch CP & Masan MeatLife',
                'nguoi_lien_he' => 'Đặng Đình Khoa',
                'so_dien_thoai' => '02743887766',
                'email' => 'sales@meatlife-fresh.vn',
                'dia_chi' => 'Khu Công Nghiệp VSIP II, TP. Tân Uyên, Tỉnh Bình Dương',
                'ma_so_thue' => '3702112233',
                'ghi_chu' => 'Thịt heo quế, thịt bò mát công nghệ Oxy-Fresh đạt tiêu chuẩn BRC.',
                'trang_thai' => 1,
            ],
            [
                'ma_ncc' => 'NCC_SALMON_NORWAY',
                'ten_nha_cung_cap' => 'Công Ty TNHH Thực Phẩm Nhập Khẩu Lerøy Norway Việt Nam',
                'nguoi_lien_he' => 'Erik Larsen / Mai Lan',
                'so_dien_thoai' => '02838997788',
                'email' => 'import@leroy-freshsalmon.com',
                'dia_chi' => 'Số 15 Lê Thánh Tôn, Bến Nghé, Quận 1, TP. Hồ Chí Minh',
                'ma_so_thue' => '0316889900',
                'ghi_chu' => 'Nhập khẩu trực tiếp đường hàng không cá hồi tươi nguyên con Nauy 3 chuyến/tuần.',
                'trang_thai' => 1,
            ],
            [
                'ma_ncc' => 'NCC_DALAT_MILK',
                'ten_nha_cung_cap' => 'Công Ty Cổ Phần Sữa Tươi Cao Nguyên Dalatmilk',
                'nguoi_lien_he' => 'Vũ Thị Minh',
                'so_dien_thoai' => '02633844555',
                'email' => 'orders@dalatmilkfresh.vn',
                'dia_chi' => 'Xã Tu Tra, Huyện Đơn Dương, Tỉnh Lâm Đồng',
                'ma_so_thue' => '5800554433',
                'ghi_chu' => 'Sữa tươi thanh trùng không chất bảo quản, bơ lạt, sữa chua ăn hàng ngày.',
                'trang_thai' => 1,
            ],
        ];

        foreach ($suppliers as $ncc) {
            NhaCungCap::updateOrCreate(
                ['ma_ncc' => $ncc['ma_ncc']],
                $ncc
            );
        }
    }
}
