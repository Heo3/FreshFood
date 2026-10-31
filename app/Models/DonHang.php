<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonHang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'don_hang';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ma_don_hang',
        'user_id',
        'cua_hang_id',
        'dia_chi_giao_hang_id',
        'ma_giam_gia_id',
        'tong_tien_hang',
        'phi_giao_hang',
        'tien_giam_gia',
        'tong_thanhtoan',
        'phuong_thuc_thanh_toan',
        'ma_giao_dich',
        'trang_thai_thanh_toan',
        'trang_thai_don_hang',
        'ten_nguoi_nhan',
        'so_dien_thoai_nhan',
        'dia_chi_giao',
        'ghi_chu',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tong_tien_hang' => 'decimal:2',
            'phi_giao_hang' => 'decimal:2',
            'tien_giam_gia' => 'decimal:2',
            'tong_thanhtoan' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cuaHang(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
    }

    public function diaChiGiaoHang(): BelongsTo
    {
        return $this->belongsTo(DiaChiGiaoHang::class, 'dia_chi_giao_hang_id');
    }

    public function maGiamGia(): BelongsTo
    {
        return $this->belongsTo(MaGiamGia::class, 'ma_giam_gia_id');
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(ChiTietDonHang::class, 'don_hang_id');
    }

    public function phieuXuats(): HasMany
    {
        return $this->hasMany(PhieuXuat::class, 'don_hang_id');
    }

    public function phieuDoiTras(): HasMany
    {
        return $this->hasMany(PhieuDoiTra::class, 'don_hang_id');
    }

    public function lichSuTichDiems(): HasMany
    {
        return $this->hasMany(LichSuTichDiem::class, 'don_hang_id');
    }

    public function danhGias(): HasMany
    {
        return $this->hasMany(DanhGiaSanPham::class, 'don_hang_id');
    }
}
