<?php

namespace Database\Factories;

use App\Models\CuaHang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CuaHang>
 */
class CuaHangFactory extends Factory
{
    protected $model = CuaHang::class;

    public function definition(): array
    {
        return [
            'ten_cua_hang' => $this->faker->company.' Store',
            'so_dien_thoai' => $this->faker->phoneNumber,
            'dia_chi' => $this->faker->address,
            'kinh_do' => $this->faker->randomFloat(7, -180, 180),
            'vi_do' => $this->faker->randomFloat(7, -90, 90),
            'gio_mo_cua' => '08:00',
            'gio_dong_cua' => '22:00',
            'trang_thai' => 1, // active store
        ];
    }
}
