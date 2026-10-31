<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiTietKiemKe extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_kiem_ke';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'phieu_kiem_ke_id',
        'san_pham_id',
        'lo_hang_id',
        'so_luong_he_thong',
        'so_luong_thuc_te',
        'ly_do_lech',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_luong_he_thong' => 'decimal:2',
            'so_luong_thuc_te' => 'decimal:2',
            'so_luong_lech' => 'decimal:2',
        ];
    }

    public function phieuKiemKe(): BelongsTo
    {
        return $this->belongsTo(PhieuKiemKe::class, 'phieu_kiem_ke_id');
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
