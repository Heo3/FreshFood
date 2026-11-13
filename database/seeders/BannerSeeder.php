<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = [
            [
                'tieu_de' => 'Thực Phẩm Tươi Sạch Mỗi Ngày',
                'phu_de' => '100% Đạt Chuẩn VietGAP & Hữu Cơ An Toàn Tuyệt Đối',
                'hinh_anh' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1200&q=80',
                'duong_dan' => '/san-pham',
                'nhan_nut' => 'Khám phá ngay',
                'thu_tu' => 1,
                'trang_thai' => 1,
            ],
            [
                'tieu_de' => 'Giao Hỏa Tốc Trong 2 Giờ',
                'phu_de' => 'Rau củ hái sáng sớm - Thịt cá tươi sống bảo quản chuẩn lạnh',
                'hinh_anh' => 'https://images.unsplash.com/photo-1610832958506-aa56368176cf?auto=format&fit=crop&w=1200&q=80',
                'duong_dan' => '/san-pham?category=rau-cu-huu-co',
                'nhan_nut' => 'Đặt hàng ngay',
                'thu_tu' => 2,
                'trang_thai' => 1,
            ],
            [
                'tieu_de' => 'Tích Điểm VIP - Giảm Giá Thả Ga',
                'phu_de' => 'Tích 1 điểm cho mỗi 10k chi tiêu • 1 điểm quy đổi ngay 1k',
                'hinh_anh' => 'https://images.unsplash.com/photo-1506484381205-f7945653044d?auto=format&fit=crop&w=1200&q=80',
                'duong_dan' => '/san-pham',
                'nhan_nut' => 'Mua sắm & Tích điểm',
                'thu_tu' => 3,
                'trang_thai' => 1,
            ],
        ];

        foreach ($banners as $b) {
            Banner::updateOrCreate(['tieu_de' => $b['tieu_de']], $b);
        }
    }
}
