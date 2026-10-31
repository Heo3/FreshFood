<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanhGiaSanPham extends Model
{
    use HasFactory;

    protected $table = 'danh_gia_san_pham';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'san_pham_id',
        'don_hang_id',
        'so_sao',
        'noi_dung',
        'hinh_anh',
        'trang_thai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_sao' => 'integer',
            'hinh_anh' => 'array',
            'trang_thai' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public function scopeDaDuyet(Builder $query): Builder
    {
        return $query->where('trang_thai', 1);
    }
}
