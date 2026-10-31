<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiTietXuatKho extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_xuat_kho';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'chi_tiet_phieu_xuat_id',
        'lo_hang_id',
        'so_luong',
        'gia_xuat',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_luong' => 'decimal:2',
            'gia_xuat' => 'decimal:2',
        ];
    }

    public function chiTietPhieuXuat(): BelongsTo
    {
        return $this->belongsTo(ChiTietPhieuXuat::class, 'chi_tiet_phieu_xuat_id');
    }

    public function loHang(): BelongsTo
    {
        return $this->belongsTo(LoHang::class, 'lo_hang_id');
    }
}
