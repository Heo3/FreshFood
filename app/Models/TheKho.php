<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TheKho extends Model
{
    use HasFactory;

    protected $table = 'the_kho';

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cua_hang_id',
        'san_pham_id',
        'lo_hang_id',
        'loai_thao_tac',
        'so_luong_thay_doi',
        'ton_sau_thay_doi',
        'ma_chung_tu',
        'nguoi_thuc_hien_id',
        'ghi_chu',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'so_luong_thay_doi' => 'decimal:2',
            'ton_sau_thay_doi' => 'decimal:2',
            'created_at' => 'datetime',
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

    public function loHang(): BelongsTo
    {
        return $this->belongsTo(LoHang::class, 'lo_hang_id');
    }

    public function nguoiThucHien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_thuc_hien_id');
    }
}
