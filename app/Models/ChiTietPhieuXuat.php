<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChiTietPhieuXuat extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_phieu_xuat';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'phieu_xuat_id',
        'san_pham_id',
        'so_luong',
        'gia_xuat',
        'thanh_tien',
        'ghi_chu',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_luong' => 'decimal:2',
            'gia_xuat' => 'decimal:2',
            'thanh_tien' => 'decimal:2',
        ];
    }

    public function phieuXuat(): BelongsTo
    {
        return $this->belongsTo(PhieuXuat::class, 'phieu_xuat_id');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function chiTietXuatKhos(): HasMany
    {
        return $this->hasMany(ChiTietXuatKho::class, 'chi_tiet_phieu_xuat_id');
    }
}
