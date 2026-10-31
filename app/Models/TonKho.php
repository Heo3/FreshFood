<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TonKho extends Model
{
    use HasFactory;

    protected $table = 'ton_kho';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cua_hang_id',
        'san_pham_id',
        'so_luong_ton',
        'so_luong_tam_giu',
        'nguong_canh_bao_thap',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_luong_ton' => 'decimal:2',
            'so_luong_tam_giu' => 'decimal:2',
            'nguong_canh_bao_thap' => 'decimal:2',
        ];
    }

    public function cuaHang(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    /**
     * @return Attribute<float, never>
     */
    protected function soLuongKhaDung(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) max(0, ((float) $this->so_luong_ton) - ((float) $this->so_luong_tam_giu)),
        );
    }

    public function scopeCanhBaoHetHang(Builder $query): Builder
    {
        return $query->whereColumn('so_luong_ton', '<=', 'nguong_canh_bao_thap');
    }
}
