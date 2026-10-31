<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiTietPhieuNhap extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_phieu_nhap';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'phieu_nhap_id',
        'san_pham_id',
        'lo_hang_id',
        'ma_lo',
        'so_luong',
        'don_gia_nhap',
        'thanh_tien',
        'ngay_san_xuat',
        'ngay_het_han',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_luong' => 'decimal:2',
            'don_gia_nhap' => 'decimal:2',
            'thanh_tien' => 'decimal:2',
            'ngay_san_xuat' => 'date',
            'ngay_het_han' => 'date',
        ];
    }

    public function phieuNhap(): BelongsTo
    {
        return $this->belongsTo(PhieuNhap::class, 'phieu_nhap_id');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function loHang(): BelongsTo
    {
        return $this->belongsTo(LoHang::class, 'lo_hang_id');
    }
}
