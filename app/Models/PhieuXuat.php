<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhieuXuat extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'phieu_xuat';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ma_phieu',
        'cua_hang_id',
        'loai_xuat',
        'don_hang_id',
        'cua_hang_nhan_id',
        'nha_cung_cap_id',
        'nguoi_tao_id',
        'nguoi_duyet_id',
        'trang_thai',
        'tong_so_luong',
        'tong_gia_tri',
        'ly_do',
        'ghi_chu',
        'xuat_luc',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loai_xuat' => 'integer',
            'trang_thai' => 'integer',
            'tong_so_luong' => 'decimal:2',
            'tong_gia_tri' => 'decimal:2',
            'xuat_luc' => 'datetime',
        ];
    }

    public function cuaHang(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_id');
    }

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public function cuaHangNhan(): BelongsTo
    {
        return $this->belongsTo(CuaHang::class, 'cua_hang_nhan_id');
    }

    public function nhaCungCap(): BelongsTo
    {
        return $this->belongsTo(NhaCungCap::class, 'nha_cung_cap_id');
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
        return $this->hasMany(ChiTietPhieuXuat::class, 'phieu_xuat_id');
    }
}
