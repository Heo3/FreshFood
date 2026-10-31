<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SanPham extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'san_pham';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ma_va_vach',
        'ten_san_pham',
        'duong_dan_seo',
        'danh_muc_id',
        'gia_ban',
        'gia_giam',
        'don_vi_tinh',
        'quy_cach',
        'dieu_kien_bao_quan',
        'han_su_dung_ngay',
        'mo_ta',
        'hinh_anh_chinh',
        'la_tuoi_song',
        'trang_thai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gia_ban' => 'decimal:2',
            'gia_giam' => 'decimal:2',
            'han_su_dung_ngay' => 'integer',
            'la_tuoi_song' => 'boolean',
            'trang_thai' => 'integer',
        ];
    }

    public function danhMuc(): BelongsTo
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_id');
    }

    public function hinhAnhs(): HasMany
    {
        return $this->hasMany(HinhAnhSanPham::class, 'san_pham_id')->orderBy('thu_tu');
    }

    public function giaTheoCuaHang(): HasMany
    {
        return $this->hasMany(GiaSanPhamCuaHang::class, 'san_pham_id');
    }

    public function loHangs(): HasMany
    {
        return $this->hasMany(LoHang::class, 'san_pham_id');
    }

    public function tonKhos(): HasMany
    {
        return $this->hasMany(TonKho::class, 'san_pham_id');
    }

    public function danhGias(): HasMany
    {
        return $this->hasMany(DanhGiaSanPham::class, 'san_pham_id');
    }

    public function chiTietDonHangs(): HasMany
    {
        return $this->hasMany(ChiTietDonHang::class, 'san_pham_id');
    }

    public function scopeDangKinhDoanh(Builder $query): Builder
    {
        return $query->where('trang_thai', 1);
    }

    public function scopeTuoiSong(Builder $query): Builder
    {
        return $query->where('la_tuoi_song', true);
    }
}
