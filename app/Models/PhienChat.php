<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhienChat extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'phien_chat';

    public const LOAI_AI = 'ai';

    public const LOAI_NHAN_VIEN = 'nhan_vien';

    public const TRANG_THAI_CHO_XU_LY = 'cho_xu_ly';

    public const TRANG_THAI_DANG_XU_LY = 'dang_xu_ly';

    public const TRANG_THAI_DA_DONG = 'da_dong';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'cua_hang_id',
        'nhan_vien_id',
        'loai_chat',
        'chu_de',
        'ma_don_hang',
        'ten_khach_hang',
        'so_dien_thoai',
        'trang_thai',
        'danh_gia_sao',
        'tin_nhan_cuoi',
        'thoi_gian_tin_nhan_cuoi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'thoi_gian_tin_nhan_cuoi' => 'datetime',
            'danh_gia_sao' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cuaHang(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nhan_vien_id');
    }

    public function tinNhan(): HasMany
    {
        return $this->hasMany(TinNhanChat::class, 'phien_chat_id')->orderBy('created_at', 'asc');
    }
}
