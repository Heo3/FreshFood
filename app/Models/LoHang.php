<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoHang extends Model
{
    use HasFactory;

    protected $table = 'lo_hang';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ma_lo',
        'san_pham_id',
        'cua_hang_id',
        'nha_cung_cap_id',
        'phieu_nhap_id',
        'ngay_san_xuat',
        'ngay_het_han',
        'so_luong_nhap',
        'so_luong_con_lai',
        'gia_nhap',
        'trang_thai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay_san_xuat' => 'date',
            'ngay_het_han' => 'date',
            'so_luong_nhap' => 'decimal:2',
            'so_luong_con_lai' => 'decimal:2',
            'gia_nhap' => 'decimal:2',
            'trang_thai' => 'integer',
        ];
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function cuaHang(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
    }

    public function nhaCungCap(): BelongsTo
    {
        return $this->belongsTo(NhaCungCap::class, 'nha_cung_cap_id');
    }

    public function phieuNhap(): BelongsTo
    {
        return $this->belongsTo(PhieuNhap::class, 'phieu_nhap_id');
    }

    public function chiTietPhieuNhaps(): HasMany
    {
        return $this->hasMany(ChiTietPhieuNhap::class, 'lo_hang_id');
    }

    public function chiTietXuatKhos(): HasMany
    {
        return $this->hasMany(ChiTietXuatKho::class, 'lo_hang_id');
    }

    public function scopeConHan(Builder $query): Builder
    {
        return $query->where('ngay_het_han', '>=', now()->toDateString())
            ->where('so_luong_con_lai', '>', 0)
            ->where('trang_thai', 1);
    }

    public function scopeSapHetHan(Builder $query, int $days = 3): Builder
    {
        return $query->where('ngay_het_han', '>=', now()->toDateString())
            ->where('ngay_het_han', '<=', now()->addDays($days)->toDateString())
            ->where('so_luong_con_lai', '>', 0)
            ->where('trang_thai', 1);
    }

    public function scopeFefo(Builder $query): Builder
    {
        return $query->orderBy('ngay_het_han', 'asc');
    }
}
