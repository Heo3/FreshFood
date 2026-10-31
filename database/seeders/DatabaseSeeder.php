<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CuaHangSeeder::class,
            UserSeeder::class,
            DanhMucSeeder::class,
            NhaCungCapSeeder::class,
            SanPhamSeeder::class,
            LoHangSeeder::class,
            MaGiamGiaSeeder::class,
        ]);
    }
}
