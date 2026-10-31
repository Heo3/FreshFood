<?php

namespace Database\Seeders;

use App\Models\CuaHang;
use Illuminate\Database\Seeder;

class CuaHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = [
            [
                'ten_cua_hang' => 'Fresh Food Quận 1 - Hai Bà Trưng',
                'so_dien_thoai' => '02838221199',
                'dia_chi' => '128 Hai Bà Trưng, Phường Đa Kao, Quận 1, TP. Hồ Chí Minh',
                'kinh_do' => 106.6975000,
                'vi_do' => 10.7854000,
                'gio_mo_cua' => '06:00:00',
                'gio_dong_cua' => '22:00:00',
                'trang_thai' => 1,
            ],
            [
                'ten_cua_hang' => 'Fresh Food Quận 7 - Phú Mỹ Hưng',
                'so_dien_thoai' => '02854112288',
                'dia_chi' => '85 Nguyễn Đức Cảnh, Phường Tân Phong, Quận 7, TP. Hồ Chí Minh',
                'kinh_do' => 106.7118000,
                'vi_do' => 10.7291000,
                'gio_mo_cua' => '06:30:00',
                'gio_dong_cua' => '21:30:00',
                'trang_thai' => 1,
            ],
            [
                'ten_cua_hang' => 'Fresh Food Bình Thạnh - Landmark Plus',
                'so_dien_thoai' => '02836208833',
                'dia_chi' => '208 Nguyễn Hữu Cảnh, Phường 22, Quận Bình Thạnh, TP. Hồ Chí Minh',
                'kinh_do' => 106.7219000,
                'vi_do' => 10.7936000,
                'gio_mo_cua' => '06:00:00',
                'gio_dong_cua' => '22:00:00',
                'trang_thai' => 1,
            ],
            [
                'ten_cua_hang' => 'Fresh Food Cầu Giấy - Trần Thái Tông',
                'so_dien_thoai' => '02437951166',
                'dia_chi' => '72 Trần Thái Tông, Phường Dịch Vọng Hậu, Quận Cầu Giấy, TP. Hà Nội',
                'kinh_do' => 105.7876000,
                'vi_do' => 21.0322000,
                'gio_mo_cua' => '06:00:00',
                'gio_dong_cua' => '21:30:00',
                'trang_thai' => 1,
            ],
        ];

        foreach ($stores as $store) {
            CuaHang::updateOrCreate(
                ['ten_cua_hang' => $store['ten_cua_hang']],
                $store
            );
        }
    }
}
