<?php

namespace Database\Factories;

use App\Models\DanhMuc;
use App\Models\SanPham;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SanPham>
 */
class SanPhamFactory extends Factory
{
    protected $model = SanPham::class;

    public function definition(): array
    {
        $danhMuc = DanhMuc::firstOrCreate(
            ['duong_dan_seo' => 'thuc-pham-mac-dinh'],
            ['ten_danh_muc' => 'Thực Phẩm Mặc Định']
        );

        return [
            'ma_va_vach' => strtoupper(Str::random(8)),
            'ten_san_pham' => $this->faker->words(2, true),
            'duong_dan_seo' => Str::slug($this->faker->words(3, true)).'-'.Str::random(5),
            'danh_muc_id' => $danhMuc->id,
            'gia_ban' => $this->faker->randomFloat(2, 10000, 500000),
            'gia_giam' => $this->faker->optional()->randomFloat(2, 5000, 100000),
            'don_vi_tinh' => 'kg',
            'quy_cach' => $this->faker->sentence,
            'dieu_kien_bao_quan' => $this->faker->randomElement(['thuong', 'mat', 'dong_lanh']),
            'han_su_dung_ngay' => $this->faker->numberBetween(30, 365),
            'mo_ta' => $this->faker->paragraph,
            'hinh_anh_chinh' => $this->faker->imageUrl(640, 480, 'food'),
            'la_tuoi_song' => $this->faker->boolean,
            'trang_thai' => 1,
        ];
    }
}
