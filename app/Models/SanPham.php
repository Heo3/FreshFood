<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SanPham extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'san_pham';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ma_va_vach',
        'ten_san_pham',
        'duong_dan_seo',
        'danh_muc_id',
        'gia_ban',
        'gia_giam',
        'don_vi_tinh',
        'quy_cach',
        'dieu_kien_bao_quan',
        'han_su_dung_ngay',
        'mo_ta',
        'hinh_anh_chinh',
        'la_tuoi_song',
        'trang_thai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gia_ban' => 'decimal:2',
            'gia_giam' => 'decimal:2',
            'han_su_dung_ngay' => 'integer',
            'la_tuoi_song' => 'boolean',
            'trang_thai' => 'integer',
        ];
    }

    public function danhMuc(): BelongsTo
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_id');
    }

    public function hinhAnhs(): HasMany
    {
        return $this->hasMany(HinhAnhSanPham::class, 'san_pham_id')->orderBy('thu_tu');
    }

    public function giaTheoCuaHang(): HasMany
    {
        return $this->hasMany(GiaSanPhamCuaHang::class, 'san_pham_id');
    }

    public function loHangs(): HasMany
    {
        return $this->hasMany(LoHang::class, 'san_pham_id');
    }

    public function tonKhos(): HasMany
    {
        return $this->hasMany(TonKho::class, 'san_pham_id');
    }

    public function danhGias(): HasMany
    {
        return $this->hasMany(DanhGiaSanPham::class, 'san_pham_id');
    }

    public function chiTietDonHangs(): HasMany
    {
        return $this->hasMany(ChiTietDonHang::class, 'san_pham_id');
    }

    public function scopeDangKinhDoanh(Builder $query): Builder
    {
        return $query->where('trang_thai', 1);
    }

    public function scopeTuoiSong(Builder $query): Builder
    {
        return $query->where('la_tuoi_song', true);
    }

    /**
     * Scope ưu tiên chọn giá từ bảng gia_san_pham_cua_hang theo chi nhánh.
     * Nếu chi nhánh không có giá cấu hình riêng thì fallback về giá mặc định ở bảng san_pham.
     */
    public function scopeVoiGiaCuaHang(Builder $query, ?int $cuaHangId = null): Builder
    {
        if (! $cuaHangId) {
            return $query;
        }

        $joins = $query->getQuery()->joins ?? [];
        $alreadyJoined = false;
        foreach ($joins as $join) {
            if (str_contains($join->table, 'gia_san_pham_cua_hang')) {
                $alreadyJoined = true;
                break;
            }
        }

        if (! $alreadyJoined) {
            $query->leftJoin('gia_san_pham_cua_hang as gch', function ($join) use ($cuaHangId) {
                $join->on('san_pham.id', '=', 'gch.san_pham_id')
                    ->where('gch.cua_hang_id', '=', $cuaHangId);
            });
        }

        return $query->select([
            'san_pham.*',
            DB::raw('COALESCE(gch.gia_ban, san_pham.gia_ban) as gia_ban'),
            DB::raw('CASE WHEN gch.id IS NOT NULL THEN gch.gia_giam ELSE san_pham.gia_giam END as gia_giam'),
            DB::raw('CASE WHEN gch.id IS NOT NULL THEN 1 ELSE 0 END as co_gia_chi_nhanh'),
        ]);
    }

    /**
     * Scope lọc sản phẩm có giá giảm ưu tiên theo chi nhánh (Flash Sale / Sale)
     */
    public function scopeCoGiamGia(Builder $query, ?int $cuaHangId = null): Builder
    {
        if ($cuaHangId) {
            $this->scopeVoiGiaCuaHang($query, $cuaHangId);

            return $query->whereRaw('(CASE WHEN gch.id IS NOT NULL THEN gch.gia_giam ELSE san_pham.gia_giam END) IS NOT NULL');
        }

        return $query->whereNotNull('san_pham.gia_giam');
    }

    /**
     * Lấy giá bán và giá giảm theo chi nhánh: Ưu tiên table gia_san_pham_cua_hang, fallback về san_pham.
     *
     * @return array{gia_ban: float, gia_giam: ?float, co_gia_chi_nhanh: bool}
     */
    public function layGiaTheoCuaHang(?int $cuaHangId = null): array
    {
        if ($cuaHangId) {
            $giaCuaHang = $this->relationLoaded('giaTheoCuaHang')
                ? $this->giaTheoCuaHang->firstWhere('cua_hang_id', $cuaHangId)
                : $this->giaTheoCuaHang()->where('cua_hang_id', $cuaHangId)->first();

            if ($giaCuaHang) {
                return [
                    'gia_ban' => (float) $giaCuaHang->gia_ban,
                    'gia_giam' => $giaCuaHang->gia_giam !== null ? (float) $giaCuaHang->gia_giam : null,
                    'co_gia_chi_nhanh' => true,
                ];
            }
        }

        return [
            'gia_ban' => (float) $this->gia_ban,
            'gia_giam' => $this->gia_giam !== null ? (float) $this->gia_giam : null,
            'co_gia_chi_nhanh' => false,
        ];
    }

    /**
     * Gán đè giá của chi nhánh vào model hiện tại
     */
    public function apDungGiaCuaHang(?int $cuaHangId = null): self
    {
        if ($cuaHangId) {
            $gia = $this->layGiaTheoCuaHang($cuaHangId);
            $this->gia_ban = $gia['gia_ban'];
            $this->gia_giam = $gia['gia_giam'];
            $this->setAttribute('co_gia_chi_nhanh', $gia['co_gia_chi_nhanh']);
        }

        return $this;
    }
}
