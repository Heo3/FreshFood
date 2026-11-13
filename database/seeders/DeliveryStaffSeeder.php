<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DeliveryStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shippers = [
            // Chi nhánh 1 - Quận 1
            [
                'name' => 'Nguyễn Văn Tải (Tài xế Xe Tải Q1)',
                'email' => 'taixe.quan1@freshfood.vn',
                'phone' => '0901234561',
                'password' => Hash::make('password'),
                'role' => 'giao_hang',
                'store_id' => 1,
                'loai_phuong_tien' => 'xe_tai',
                'bien_so_xe' => '51D-882.34',
                'tai_trong_kg' => 1500,
                'trang_thai_giao' => 'san_sang',
                'status' => 1,
            ],
            [
                'name' => 'Lê Văn Tốc (Shipper Xe Máy Q1)',
                'email' => 'shipper1.quan1@freshfood.vn',
                'phone' => '0912333441',
                'password' => Hash::make('password'),
                'role' => 'giao_hang',
                'store_id' => 1,
                'loai_phuong_tien' => 'xe_may',
                'bien_so_xe' => '59-K1 234.56',
                'tai_trong_kg' => 50,
                'trang_thai_giao' => 'san_sang',
                'status' => 1,
            ],
            [
                'name' => 'Phạm Gia Huy (Shipper Xe Máy Q1)',
                'email' => 'shipper2.quan1@freshfood.vn',
                'phone' => '0918555661',
                'password' => Hash::make('password'),
                'role' => 'giao_hang',
                'store_id' => 1,
                'loai_phuong_tien' => 'xe_may',
                'bien_so_xe' => '59-L2 678.90',
                'tai_trong_kg' => 50,
                'trang_thai_giao' => 'san_sang',
                'status' => 1,
            ],

            // Chi nhánh 2 - Quận 7
            [
                'name' => 'Trần Trọng Tải (Tài xế Xe Tải Q7)',
                'email' => 'taixe.quan7@freshfood.vn',
                'phone' => '0909888772',
                'password' => Hash::make('password'),
                'role' => 'giao_hang',
                'store_id' => 2,
                'loai_phuong_tien' => 'xe_tai',
                'bien_so_xe' => '51C-556.78',
                'tai_trong_kg' => 2500,
                'trang_thai_giao' => 'san_sang',
                'status' => 1,
            ],
            [
                'name' => 'Hoàng Đình Nam (Shipper Xe Máy Q7)',
                'email' => 'shipper1.quan7@freshfood.vn',
                'phone' => '0934777882',
                'password' => Hash::make('password'),
                'role' => 'giao_hang',
                'store_id' => 2,
                'loai_phuong_tien' => 'xe_may',
                'bien_so_xe' => '59-T1 112.23',
                'tai_trong_kg' => 50,
                'trang_thai_giao' => 'san_sang',
                'status' => 1,
            ],

            // Chi nhánh 3 - Bình Thạnh
            [
                'name' => 'Võ Văn Tải (Tài xế Xe Tải Bình Thạnh)',
                'email' => 'taixe.binhthanh@freshfood.vn',
                'phone' => '0977666553',
                'password' => Hash::make('password'),
                'role' => 'giao_hang',
                'store_id' => 3,
                'loai_phuong_tien' => 'xe_tai',
                'bien_so_xe' => '51D-998.12',
                'tai_trong_kg' => 2000,
                'trang_thai_giao' => 'san_sang',
                'status' => 1,
            ],
            [
                'name' => 'Đỗ Quốc Bảo (Shipper Xe Máy Bình Thạnh)',
                'email' => 'shipper1.binhthanh@freshfood.vn',
                'phone' => '0977999003',
                'password' => Hash::make('password'),
                'role' => 'giao_hang',
                'store_id' => 3,
                'loai_phuong_tien' => 'xe_may',
                'bien_so_xe' => '59-S3 445.56',
                'tai_trong_kg' => 50,
                'trang_thai_giao' => 'san_sang',
                'status' => 1,
            ],
        ];

        foreach ($shippers as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}
