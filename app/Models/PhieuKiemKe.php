<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhieuKiemKe extends Model
{
    use HasFactory;

    protected $table = 'phieu_kiem_ke';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ma_phieu',
        'cua_hang_id',
        'nguoi_tao_id',
        'nguoi_duyet_id',
        'ngay_kiem_ke',
        'trang_thai',
        'ghi_chu',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay_kiem_ke' => 'datetime',
            'trang_thai' => 'integer',
        ];
    }

    public function cuaHang(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
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
        return $this->hasMany(ChiTietKiemKe::class, 'phieu_kiem_ke_id');
    }
}
