<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiTietDoiTra extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_doi_tra';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'phieu_doi_tra_id',
        'san_pham_id',
        'lo_hang_id',
        'so_luong',
        'don_gia',
        'thanh_tien',
        'tinh_trang_hang',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_luong' => 'decimal:2',
            'don_gia' => 'decimal:2',
            'thanh_tien' => 'decimal:2',
        ];
    }

    public function phieuDoiTra(): BelongsTo
    {
        return $this->belongsTo(PhieuDoiTra::class, 'phieu_doi_tra_id');
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
