<?php

namespace Database\Seeders;

use App\Models\CuaHang;
use App\Models\DiaChiGiaoHang;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cuaHang1 = CuaHang::first();
        $cuaHang2 = CuaHang::skip(1)->first() ?? $cuaHang1;
        $cuaHang3 = CuaHang::skip(2)->first() ?? $cuaHang1;

        $password = Hash::make('password123');

        // 1. Quản trị viên hệ thống (Admin)
        User::updateOrCreate(
            ['email' => 'admin@freshfood.vn'],
            [
                'name' => 'Nguyễn Quản Trị (Admin)',
                'phone' => '0901888999',
                'password' => $password,
                'role' => 'admin',
                'store_id' => null,
                'loyalty_points' => 0,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );

        // 2. Quản lý chi nhánh (Store Managers)
        User::updateOrCreate(
            ['email' => 'quanly.q1@freshfood.vn'],
            [
                'name' => 'Trần Văn Quản Lý (Q1)',
                'phone' => '0902111222',
                'password' => $password,
                'role' => 'quan_ly',
                'store_id' => $cuaHang1?->id,
                'loyalty_points' => 0,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'quanly.q7@freshfood.vn'],
            [
                'name' => 'Lê Thị Quản Lý (Q7)',
                'phone' => '0902333444',
                'password' => $password,
                'role' => 'quan_ly',
                'store_id' => $cuaHang2?->id,
                'loyalty_points' => 0,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );

        // 3. Nhân viên thu ngân / kho chi nhánh
        User::updateOrCreate(
            ['email' => 'nhanvien.kho1@freshfood.vn'],
            [
                'name' => 'Phạm Quốc Bảo (Kho Q1)',
                'phone' => '0903555666',
                'password' => $password,
                'role' => 'nhan_vien',
                'store_id' => $cuaHang1?->id,
                'loyalty_points' => 0,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'nhanvien.pos1@freshfood.vn'],
            [
                'name' => 'Hoàng Thu Ngân (Thu ngân Q1)',
                'phone' => '0903777888',
                'password' => $password,
                'role' => 'nhan_vien',
                'store_id' => $cuaHang1?->id,
                'loyalty_points' => 0,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'nhanvien.kho2@freshfood.vn'],
            [
                'name' => 'Đỗ Minh Tuấn (Kho Q7)',
                'phone' => '0903999000',
                'password' => $password,
                'role' => 'nhan_vien',
                'store_id' => $cuaHang2?->id,
                'loyalty_points' => 0,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );

        // 4. Khách hàng thân thiết mẫu
        $customers = [
            [
                'name' => 'Nguyễn Minh Anh',
                'email' => 'minhanh.nguyen@gmail.com',
                'phone' => '0912345671',
                'loyalty_points' => 450,
                'address' => [
                    'ten_nguoi_nhan' => 'Nguyễn Minh Anh',
                    'so_dien_thoai_nhan' => '0912345671',
                    'dia_chi_chi_tiet' => 'Chung cư Horizon, 214 Trần Quang Khải',
                    'phuong_xa' => 'Phường Tân Định',
                    'quan_huyen' => 'Quận 1',
                    'tinh_thanh' => 'TP. Hồ Chí Minh',
                    'la_mac_dinh' => true,
                ],
            ],
            [
                'name' => 'Võ Hoàng Yến',
                'email' => 'hoangyen.vo@gmail.com',
                'phone' => '0912345672',
                'loyalty_points' => 1200,
                'address' => [
                    'ten_nguoi_nhan' => 'Võ Hoàng Yến',
                    'so_dien_thoai_nhan' => '0912345672',
                    'dia_chi_chi_tiet' => 'Biệt thự Mỹ Thái 2, Phú Mỹ Hưng',
                    'phuong_xa' => 'Phường Tân Phú',
                    'quan_huyen' => 'Quận 7',
                    'tinh_thanh' => 'TP. Hồ Chí Minh',
                    'la_mac_dinh' => true,
                ],
            ],
            [
                'name' => 'Đặng Tuấn Kiệt',
                'email' => 'tuankiet.dang@gmail.com',
                'phone' => '0912345673',
                'loyalty_points' => 150,
                'address' => [
                    'ten_nguoi_nhan' => 'Đặng Tuấn Kiệt',
                    'so_dien_thoai_nhan' => '0912345673',
                    'dia_chi_chi_tiet' => 'Tòa Landmark 4, Vinhomes Central Park',
                    'phuong_xa' => 'Phường 22',
                    'quan_huyen' => 'Quận Bình Thạnh',
                    'tinh_thanh' => 'TP. Hồ Chí Minh',
                    'la_mac_dinh' => true,
                ],
            ],
            [
                'name' => 'Phan Thùy Trang',
                'email' => 'thuytrang.phan@gmail.com',
                'phone' => '0912345674',
                'loyalty_points' => 800,
                'address' => [
                    'ten_nguoi_nhan' => 'Phan Thùy Trang',
                    'so_dien_thoai_nhan' => '0912345674',
                    'dia_chi_chi_tiet' => '45 Lê Duẩn',
                    'phuong_xa' => 'Phường Bến Nghé',
                    'quan_huyen' => 'Quận 1',
                    'tinh_thanh' => 'TP. Hồ Chí Minh',
                    'la_mac_dinh' => true,
                ],
            ],
            [
                'name' => 'Trịnh Quốc Thắng',
                'email' => 'quocthang.trinh@gmail.com',
                'phone' => '0912345675',
                'loyalty_points' => 50,
                'address' => [
                    'ten_nguoi_nhan' => 'Trịnh Quốc Thắng',
                    'so_dien_thoai_nhan' => '0912345675',
                    'dia_chi_chi_tiet' => 'Tòa Keangnam Hanoi Landmark Tower',
                    'phuong_xa' => 'Phường Mễ Trì',
                    'quan_huyen' => 'Quận Nam Từ Liêm',
                    'tinh_thanh' => 'TP. Hà Nội',
                    'la_mac_dinh' => true,
                ],
            ],
        ];

        foreach ($customers as $c) {
            $user = User::updateOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'phone' => $c['phone'],
                    'password' => $password,
                    'role' => 'khach_hang',
                    'store_id' => null,
                    'loyalty_points' => $c['loyalty_points'],
                    'status' => 1,
                    'email_verified_at' => now(),
                ]
            );

            DiaChiGiaoHang::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'dia_chi_chi_tiet' => $c['address']['dia_chi_chi_tiet'],
                ],
                $c['address']
            );
        }
    }
}
