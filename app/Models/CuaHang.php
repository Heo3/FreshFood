<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

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

    /**
     * Xác định ID chi nhánh hiện tại đang được chọn từ Request / Session / Cookie
     */
    public static function layIdCuaHangHienTai(?Request $request = null): int
    {
        $req = $request ?? request();

        $storeId = $req?->input('store_id')
            ?? $req?->cookie('freshfood_store_id')
            ?? ($req?->hasSession() ? $req->session()->get('selected_store_id') : null)
            ?? $req?->user()?->store_id;

        if ($storeId && self::where('id', (int) $storeId)->where('trang_thai', 1)->exists()) {
            return (int) $storeId;
        }

        return self::where('trang_thai', 1)->value('id') ?? 1;
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
