<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiaSanPhamCuaHang extends Model
{
    use HasFactory;

    protected $table = 'gia_san_pham_cua_hang';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cua_hang_id',
        'san_pham_id',
        'gia_ban',
        'gia_giam',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gia_ban' => 'decimal:2',
            'gia_giam' => 'decimal:2',
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
}
