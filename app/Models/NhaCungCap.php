<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NhaCungCap extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'nha_cung_cap';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ma_ncc',
        'ten_nha_cung_cap',
        'nguoi_lien_he',
        'so_dien_thoai',
        'email',
        'dia_chi',
        'ma_so_thue',
        'ghi_chu',
        'trang_thai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trang_thai' => 'integer',
        ];
    }

    public function phieuNhaps(): HasMany
    {
        return $this->hasMany(PhieuNhap::class, 'nha_cung_cap_id');
    }

    public function loHangs(): HasMany
    {
        return $this->hasMany(LoHang::class, 'nha_cung_cap_id');
    }

    public function phieuXuats(): HasMany
    {
        return $this->hasMany(PhieuXuat::class, 'nha_cung_cap_id');
    }
}
