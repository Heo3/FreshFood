<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DanhMuc extends Model
{
    use HasFactory;

    protected $table = 'danh_muc';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ten_danh_muc',
        'duong_dan_seo',
        'hinh_anh',
        'danh_muc_cha_id',
    ];

    public function danhMucCha(): BelongsTo
    {
        return $this->belongsTo(self::class, 'danh_muc_cha_id');
    }

    public function danhMucCon(): HasMany
    {
        return $this->hasMany(self::class, 'danh_muc_cha_id');
    }

    public function sanPhams(): HasMany
    {
        return $this->hasMany(SanPham::class, 'danh_muc_id');
    }
}
