<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiaChiGiaoHang extends Model
{
    use HasFactory;

    protected $table = 'dia_chi_giao_hang';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'ten_nguoi_nhan',
        'so_dien_thoai_nhan',
        'dia_chi_chi_tiet',
        'phuong_xa',
        'quan_huyen',
        'tinh_thanh',
        'la_mac_dinh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'la_mac_dinh' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function donHangs(): HasMany
    {
        return $this->hasMany(DonHang::class, 'dia_chi_giao_hang_id');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function diaChiDayDu(): Attribute
    {
        return Attribute::make(
            get: fn (): string => implode(', ', array_filter([
                $this->dia_chi_chi_tiet,
                $this->phuong_xa,
                $this->quan_huyen,
                $this->tinh_thanh,
            ])),
        );
    }
}
