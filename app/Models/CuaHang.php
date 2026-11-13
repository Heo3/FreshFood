<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class CuaHang extends Model
{
    use HasFactory;

    protected $table = 'cua_hang';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ten_cua_hang',
        'so_dien_thoai',
        'dia_chi',
        'kinh_do',
        'vi_do',
        'gio_mo_cua',
        'gio_dong_cua',
        'trang_thai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kinh_do' => 'decimal:7',
            'vi_do' => 'decimal:7',
            'trang_thai' => 'integer',
        ];
    }

    /**
     * Xác định ID chi nhánh hiện tại đang được chọn từ Request / Session / Cookie
     */
    public static function layIdCuaHangHienTai(?Request $request = null): int
    {
        $req = $request ?? request();

        $storeId = $req?->input('store_id')
            ?? $req?->cookie('freshfood_store_id')
            ?? ($req?->hasSession() ? $req->session()->get('selected_store_id') : null)
            ?? $req?->user()?->store_id;

        if ($storeId && self::where('id', (int) $storeId)->where('trang_thai', 1)->exists()) {
            return (int) $storeId;
        }

        return self::where('trang_thai', 1)->value('id') ?? 1;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'store_id');
    }

    public function tonKhos(): HasMany
    {
        return $this->hasMany(TonKho::class, 'cua_hang_id');
    }

    public function giaSanPham(): HasMany
    {
        return $this->hasMany(GiaSanPhamCuaHang::class, 'cua_hang_id');
    }

    public function donHangs(): HasMany
    {
        return $this->hasMany(DonHang::class, 'cua_hang_id');
    }

    public function phieuNhaps(): HasMany
    {
        return $this->hasMany(PhieuNhap::class, 'cua_hang_id');
    }

    public function phieuXuats(): HasMany
    {
        return $this->hasMany(PhieuXuat::class, 'cua_hang_id');
    }

    public function loHangs(): HasMany
    {
        return $this->hasMany(LoHang::class, 'cua_hang_id');
    }

    public function gioHangs(): HasMany
    {
        return $this->hasMany(GioHang::class, 'cua_hang_id');
    }

    public function phieuKiemKes(): HasMany
    {
        return $this->hasMany(PhieuKiemKe::class, 'cua_hang_id');
    }

    public function theKhos(): HasMany
    {
        return $this->hasMany(TheKho::class, 'cua_hang_id');
    }

    public function phieuDoiTras(): HasMany
    {
        return $this->hasMany(PhieuDoiTra::class, 'cua_hang_id');
    }

    /**
     * Tính khoảng cách giữa 2 tọa độ GPS (km) theo công thức Haversine
     */
    public static function tinhKhoangCachKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Bán kính trái đất (km)
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Tìm chi nhánh gần nhất đang hoạt động dựa vào tọa độ GPS (lat, lon)
     */
    public static function timChiNhanhGanNhatTheoToaDo(float $lat, float $lon): ?self
    {
        $stores = self::where('trang_thai', 1)->whereNotNull('vi_do')->whereNotNull('kinh_do')->get();
        if ($stores->isEmpty()) {
            return self::where('trang_thai', 1)->first();
        }

        $minDistance = null;
        $nearest = null;

        foreach ($stores as $store) {
            $dist = self::tinhKhoangCachKm($lat, $lon, (float) $store->vi_do, (float) $store->kinh_do);
            if ($minDistance === null || $dist < $minDistance) {
                $minDistance = $dist;
                $nearest = $store;
            }
        }

        return $nearest;
    }

    /**
     * Phỏng đoán và tìm chi nhánh phù hợp nhất dựa trên từ khóa địa chỉ giao hàng
     */
    public static function timChiNhanhPhuHopTheoDiaChi(string $diaChi): ?self
    {
        $diaChiLower = mb_strtolower($diaChi, 'UTF-8');
        $stores = self::where('trang_thai', 1)->get();

        if ($stores->isEmpty()) {
            return null;
        }

        // 1. Kiểm tra theo tỉnh/thành phố lớn
        $isHanoi = str_contains($diaChiLower, 'hà nội') || str_contains($diaChiLower, 'ha noi') || str_contains($diaChiLower, 'hn');
        $isHcm = str_contains($diaChiLower, 'hồ chí minh') || str_contains($diaChiLower, 'ho chi minh') || str_contains($diaChiLower, 'hcm') || str_contains($diaChiLower, 'sài gòn') || str_contains($diaChiLower, 'sai gon') || str_contains($diaChiLower, 'tphcm');
        $isDanang = str_contains($diaChiLower, 'đà nẵng') || str_contains($diaChiLower, 'da nang');

        // Lọc danh sách chi nhánh theo vùng
        $matchedStores = $stores;
        if ($isHanoi) {
            $matchedStores = $stores->filter(fn ($s) => str_contains(mb_strtolower($s->dia_chi, 'UTF-8'), 'hà nội') || str_contains(mb_strtolower($s->ten_cua_hang, 'UTF-8'), 'hà nội'));
        } elseif ($isHcm) {
            $matchedStores = $stores->filter(fn ($s) => str_contains(mb_strtolower($s->dia_chi, 'UTF-8'), 'hồ chí minh') || str_contains(mb_strtolower($s->dia_chi, 'UTF-8'), 'tp.hcm') || str_contains(mb_strtolower($s->dia_chi, 'UTF-8'), 'tphcm'));
        } elseif ($isDanang) {
            $matchedStores = $stores->filter(fn ($s) => str_contains(mb_strtolower($s->dia_chi, 'UTF-8'), 'đà nẵng'));
        }

        if ($matchedStores->isEmpty()) {
            $matchedStores = $stores;
        }

        // 2. Tiếp tục tìm chi nhánh trùng tên Quận/Huyện trong danh sách đã thu hẹp
        foreach ($matchedStores as $store) {
            $storeNameLower = mb_strtolower($store->ten_cua_hang, 'UTF-8');
            $storeAddressLower = mb_strtolower($store->dia_chi, 'UTF-8');

            $keywords = [
                'quận 1' => ['quận 1', 'quan 1', 'q1', 'q.1', 'bến nghé', 'đa kao'],
                'quận 7' => ['quận 7', 'quan 7', 'q7', 'q.7', 'tân phong', 'phú mỹ hưng'],
                'bình thạnh' => ['bình thạnh', 'binh thanh'],
                'cầu giấy' => ['cầu giấy', 'cau giay', 'dịch vọng'],
                'đống đa' => ['đống đa', 'dong da'],
                'thanh xuân' => ['thanh xuân', 'thanh xuan'],
                'ba đình' => ['ba đình', 'ba dinh'],
                'hải châu' => ['hải châu', 'hai chau'],
                'thủ đức' => ['thủ đức', 'thu duc', 'quận 2', 'quận 9'],
            ];

            foreach ($keywords as $district => $aliases) {
                $storeMatchesDistrict = str_contains($storeNameLower, $district) || str_contains($storeAddressLower, $district);
                if ($storeMatchesDistrict) {
                    foreach ($aliases as $alias) {
                        if (str_contains($diaChiLower, $alias)) {
                            return $store;
                        }
                    }
                }
            }
        }

        return $matchedStores->first();
    }

    /**
     * Kiểm tra xem địa chỉ nhận hàng có bị lệch vùng địa lý nghiêm trọng so với chi nhánh hay không (ví dụ giao HN nhưng chọn store HCM)
     */
    public function laLechVungDiaLy(string $diaChi): bool
    {
        $diaChiLower = mb_strtolower($diaChi, 'UTF-8');
        $storeDiaChiLower = mb_strtolower($this->dia_chi, 'UTF-8');
        $storeTenLower = mb_strtolower($this->ten_cua_hang, 'UTF-8');

        $storeIsHn = str_contains($storeDiaChiLower, 'hà nội') || str_contains($storeTenLower, 'hà nội') || str_contains($storeTenLower, 'cầu giấy');
        $storeIsHcm = str_contains($storeDiaChiLower, 'hồ chí minh') || str_contains($storeDiaChiLower, 'tp.hcm') || str_contains($storeDiaChiLower, 'hcm') || str_contains($storeTenLower, 'quận 1') || str_contains($storeTenLower, 'quận 7') || str_contains($storeTenLower, 'bình thạnh');
        $storeIsDanang = str_contains($storeDiaChiLower, 'đà nẵng') || str_contains($storeTenLower, 'đà nẵng');

        $orderIsHn = str_contains($diaChiLower, 'hà nội') || str_contains($diaChiLower, 'ha noi') || str_contains($diaChiLower, 'cầu giấy') || str_contains($diaChiLower, 'đống đa') || str_contains($diaChiLower, 'ba đình');
        $orderIsHcm = str_contains($diaChiLower, 'hồ chí minh') || str_contains($diaChiLower, 'ho chi minh') || str_contains($diaChiLower, 'tphcm') || str_contains($diaChiLower, 'sài gòn') || str_contains($diaChiLower, 'quận 1') || str_contains($diaChiLower, 'quận 7') || str_contains($diaChiLower, 'bình thạnh') || str_contains($diaChiLower, 'thủ đức');
        $orderIsDanang = str_contains($diaChiLower, 'đà nẵng') || str_contains($diaChiLower, 'da nang');

        if ($storeIsHn && ($orderIsHcm || $orderIsDanang)) {
            return true;
        }

        if ($storeIsHcm && ($orderIsHn || $orderIsDanang)) {
            return true;
        }

        if ($storeIsDanang && ($orderIsHn || $orderIsHcm)) {
            return true;
        }

        return false;
    }
}
