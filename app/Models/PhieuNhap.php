<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhieuNhap extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'phieu_nhap';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ma_phieu',
        'cua_hang_id',
        'nha_cung_cap_id',
        'nguoi_tao_id',
        'nguoi_duyet_id',
        'tong_tien',
        'trang_thai',
        'ngay_nhap',
        'ghi_chu',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tong_tien' => 'decimal:2',
            'trang_thai' => 'integer',
            'ngay_nhap' => 'datetime',
        ];
    }

    public function cuaHang(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
    }

    public function nhaCungCap(): BelongsTo
    {
        return $this->belongsTo(NhaCungCap::class, 'nha_cung_cap_id');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_tao_id');
    }

    public function nguoiDuyet(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_duyet_id');
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(ChiTietPhieuNhap::class, 'phieu_nhap_id');
    }

    public function loHangs(): HasMany
    {
        return $this->hasMany(LoHang::class, 'phieu_nhap_id');
    }
}
