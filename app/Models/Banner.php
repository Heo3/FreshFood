<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tieu_de',
        'phu_de',
        'hinh_anh',
        'duong_dan',
        'nhan_nut',
        'vi_tri',
        'thu_tu',
        'trang_thai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'thu_tu' => 'integer',
            'trang_thai' => 'integer',
        ];
    }

    public function scopeDangHienThi(Builder $query): Builder
    {
        return $query->where('trang_thai', 1)->orderBy('thu_tu', 'asc')->latest('id');
    }
}
