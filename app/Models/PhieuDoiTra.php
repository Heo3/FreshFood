<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhieuDoiTra extends Model
{
    use HasFactory;

    protected $table = 'phieu_doi_tra';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ma_phieu',
        'don_hang_id',
        'cua_hang_id',
        'user_id',
        'nguoi_tao_id',
        'nguoi_duyet_id',
        'loai_doi_tra',
        'tong_tien_hoan',
        'trang_thai',
        'ly_do',
        'ghi_chu',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tong_tien_hoan' => 'decimal:2',
            'trang_thai' => 'integer',
        ];
    }

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public function cuaHang(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_tao_id');
    }

    public function nguoiDuyet(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_duyet_id');
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(ChiTietDoiTra::class, 'phieu_doi_tra_id');
    }
}
