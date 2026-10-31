<?php

namespace Database\Seeders;

use App\Models\MaGiamGia;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MaGiamGiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $coupons = [
            [
                'ma_khuyen_mai' => 'FRESH10',
                'ten_chuong_trinh' => 'Ưu đãi 10% Thực phẩm tươi sạch mỗi ngày',
                'loai_giam_gia' => 'phan_tram',
                'gia_tri_giam' => 10,
                'don_hang_toi_thieu' => 200000,
                'giam_toi_da' => 50000,
                'ngay_bat_dau' => $now->copy()->subDays(5),
                'ngay_ket_thuc' => $now->copy()->addDays(60),
                'so_luong' => 500,
                'da_su_dung' => 42,
                'trang_thai' => 1,
            ],
            [
                'ma_khuyen_mai' => 'GIAM50K',
                'ten_chuong_trinh' => 'Giảm ngay 50.000đ cho đơn hàng gia đình từ 350.000đ',
                'loai_giam_gia' => 'tien_mat',
                'gia_tri_giam' => 50000,
                'don_hang_toi_thieu' => 350000,
                'giam_toi_da' => 50000,
                'ngay_bat_dau' => $now->copy()->subDays(2),
                'ngay_ket_thuc' => $now->copy()->addDays(30),
                'so_luong' => 200,
                'da_su_dung' => 18,
                'trang_thai' => 1,
            ],
            [
                'ma_khuyen_mai' => 'FREESHIP',
                'ten_chuong_trinh' => 'Hỗ trợ 30.000đ phí giao hàng siêu tốc 2h',
                'loai_giam_gia' => 'tien_mat',
                'gia_tri_giam' => 30000,
                'don_hang_toi_thieu' => 250000,
                'giam_toi_da' => 30000,
                'ngay_bat_dau' => $now->copy()->subDays(10),
                'ngay_ket_thuc' => $now->copy()->addDays(90),
                'so_luong' => 1000,
                'da_su_dung' => 125,
                'trang_thai' => 1,
            ],
            [
                'ma_khuyen_mai' => 'CHAOMUNG',
                'ten_chuong_trinh' => 'Tặng 15% mừng thành viên mới gia nhập Fresh Food',
                'loai_giam_gia' => 'phan_tram',
                'gia_tri_giam' => 15,
                'don_hang_toi_thieu' => 150000,
                'giam_toi_da' => 70000,
                'ngay_bat_dau' => $now->copy()->subDays(30),
                'ngay_ket_thuc' => $now->copy()->addDays(120),
                'so_luong' => 2000,
                'da_su_dung' => 310,
                'trang_thai' => 1,
            ],
            [
                'ma_khuyen_mai' => 'SALMON20',
                'ten_chuong_trinh' => 'Đại tiệc Cá Hồi Nauy giảm 20%',
                'loai_giam_gia' => 'phan_tram',
                'gia_tri_giam' => 20,
                'don_hang_toi_thieu' => 500000,
                'giam_toi_da' => 150000,
                'ngay_bat_dau' => $now->copy()->subDays(1),
                'ngay_ket_thuc' => $now->copy()->addDays(14),
                'so_luong' => 100,
                'da_su_dung' => 15,
                'trang_thai' => 1,
            ],
        ];

        foreach ($coupons as $coupon) {
            MaGiamGia::updateOrCreate(
                ['ma_khuyen_mai' => $coupon['ma_khuyen_mai']],
                $coupon
            );
        }
    }
}
