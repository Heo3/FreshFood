<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaGiamGia extends Model
{
    use HasFactory;

    protected $table = 'ma_giam_gia';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ma_khuyen_mai',
        'ten_chuong_trinh',
        'loai_giam_gia',
        'gia_tri_giam',
        'don_hang_toi_thieu',
        'giam_toi_da',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'so_luong',
        'da_su_dung',
        'trang_thai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gia_tri_giam' => 'decimal:2',
            'don_hang_toi_thieu' => 'decimal:2',
            'giam_toi_da' => 'decimal:2',
            'ngay_bat_dau' => 'datetime',
            'ngay_ket_thuc' => 'datetime',
            'so_luong' => 'integer',
            'da_su_dung' => 'integer',
            'trang_thai' => 'integer',
        ];
    }

    public function donHangs(): HasMany
    {
        return $this->hasMany(DonHang::class, 'ma_giam_gia_id');
    }

    public function scopeHieuLuc(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('trang_thai', 1)
            ->where('ngay_bat_dau', '<=', $now)
            ->where('ngay_ket_thuc', '>=', $now)
            ->whereColumn('da_su_dung', '<', 'so_luong');
    }

    public function isAvailable(): bool
    {
        $now = Carbon::now();

        return $this->trang_thai === 1
            && $this->da_su_dung < $this->so_luong
            && $this->ngay_bat_dau <= $now
            && $this->ngay_ket_thuc >= $now;
    }

    public function tinhSoTienGiam(float $tongTienHang): float
    {
        if ($tongTienHang < (float) $this->don_hang_toi_thieu) {
            return 0.0;
        }

        if ($this->loai_giam_gia === 'phan_tram') {
            $giam = ($tongTienHang * ((float) $this->gia_tri_giam)) / 100;
            if ($this->giam_toi_da !== null && $giam > (float) $this->giam_toi_da) {
                return (float) $this->giam_toi_da;
            }

            return $giam;
        }

        return min((float) $this->gia_tri_giam, $tongTienHang);
    }
}
