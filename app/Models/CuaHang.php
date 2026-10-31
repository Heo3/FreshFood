<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuaHang extends Model
{
    use HasFactory;

    protected $table = 'cua_hang';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ten_cua_hang',
        'so_dien_thoai',
        'dia_chi',
        'kinh_do',
        'vi_do',
        'gio_mo_cua',
        'gio_dong_cua',
        'trang_thai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kinh_do' => 'decimal:7',
            'vi_do' => 'decimal:7',
            'trang_thai' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'store_id');
    }

    public function tonKhos(): HasMany
    {
        return $this->hasMany(TonKho::class, 'cua_hang_id');
    }

    public function giaSanPham(): HasMany
    {
        return $this->hasMany(GiaSanPhamCuaHang::class, 'cua_hang_id');
    }

    public function donHangs(): HasMany
    {
        return $this->hasMany(DonHang::class, 'cua_hang_id');
    }

    public function phieuNhaps(): HasMany
    {
        return $this->hasMany(PhieuNhap::class, 'cua_hang_id');
    }

    public function phieuXuats(): HasMany
    {
        return $this->hasMany(PhieuXuat::class, 'cua_hang_id');
    }

    public function loHangs(): HasMany
    {
        return $this->hasMany(LoHang::class, 'cua_hang_id');
    }

    public function gioHangs(): HasMany
    {
        return $this->hasMany(GioHang::class, 'cua_hang_id');
    }

    public function phieuKiemKes(): HasMany
    {
        return $this->hasMany(PhieuKiemKe::class, 'cua_hang_id');
    }

    public function theKhos(): HasMany
    {
        return $this->hasMany(TheKho::class, 'cua_hang_id');
    }

    public function phieuDoiTras(): HasMany
    {
        return $this->hasMany(PhieuDoiTra::class, 'cua_hang_id');
    }
}
