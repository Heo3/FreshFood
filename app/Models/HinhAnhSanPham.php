<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HinhAnhSanPham extends Model
{
    use HasFactory;

    protected $table = 'hinh_anh_san_pham';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'san_pham_id',
        'duong_dan_anh',
        'thu_tu',
        'la_anh_chinh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'thu_tu' => 'integer',
            'la_anh_chinh' => 'boolean',
        ];
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }
}
