<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ChiTietKiemKe;
use App\Models\CuaHang;
use App\Models\LoHang;
use App\Models\PhieuKiemKe;
use App\Models\TheKho;
use App\Models\TonKho;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PhieuKiemKeController extends Controller
{
    protected function layStoreId(Request $request): int
    {
        $user = $request->user();
        if ($user && ! $user->isAdmin() && $user->store_id) {
            return (int) $user->store_id;
        }

        if ($request->filled('store_id') && $user?->isAdmin()) {
            return (int) $request->input('store_id');
        }

        return (int) ($user?->store_id ?? CuaHang::layIdCuaHangHienTai($request));
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $storeId = $this->layStoreId($request);
        $stores = ($user && ! $user->isAdmin() && $user->store_id)
            ? CuaHang::where('id', $user->store_id)->get()
            : CuaHang::where('trang_thai', 1)->get();
        $currentStore = CuaHang::find($storeId) ?? $stores->first();

        $phieuKiemKes = PhieuKiemKe::query()
            ->where('cua_hang_id', $storeId)
            ->with(['nguoiTao', 'nguoiDuyet', 'chiTiets.sanPham', 'chiTiets.loHang'])
            ->latest('id')
            ->get();

        return Inertia::render('store/audit/Index', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'phieuKiemKes' => $phieuKiemKes,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $storeId = $this->layStoreId($request);
        $stores = ($user && ! $user->isAdmin() && $user->store_id)
            ? CuaHang::where('id', $user->store_id)->get()
            : CuaHang::where('trang_thai', 1)->get();
        $currentStore = CuaHang::find($storeId) ?? $stores->first();

        $items = TonKho::query()
            ->where('cua_hang_id', $storeId)
            ->with(['sanPham.danhMuc'])
            ->get()
            ->map(function ($item) use ($storeId) {
                $lots = LoHang::query()
                    ->where('cua_hang_id', $storeId)
                    ->where('san_pham_id', $item->san_pham_id)
                    ->where('so_luong_con_lai', '>', 0)
                    ->orderBy('ngay_het_han', 'asc')
                    ->get();

                return [
                    'san_pham_id' => $item->san_pham_id,
                    'ten_san_pham' => $item->sanPham?->ten_san_pham,
                    'ma_va_vach' => $item->sanPham?->ma_va_vach,
                    'don_vi_tinh' => $item->sanPham?->don_vi_tinh,
                    'danh_muc' => $item->sanPham?->danhMuc?->ten_danh_muc,
                    'so_luong_he_thong' => (float) $item->so_luong_ton,
                    'lots' => $lots,
                ];
            });

        return Inertia::render('store/audit/Create', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'items' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ghi_chu' => ['nullable', 'string', 'max:500'],
            'store_id' => ['nullable', 'exists:cua_hang,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.san_pham_id' => ['required', 'exists:san_pham,id'],
            'items.*.lo_hang_id' => ['nullable', 'exists:lo_hang,id'],
            'items.*.so_luong_he_thong' => ['required', 'numeric', 'min:0'],
            'items.*.so_luong_thuc_te' => ['required', 'numeric', 'min:0'],
            'items.*.ly_do_lech' => ['nullable', 'string', 'max:255'],
        ]);

        $storeId = $this->layStoreId($request);
        $user = $request->user();

        DB::transaction(function () use ($validated, $storeId, $user) {
            $maPhieu = 'PKK-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));

            $phieuKiemKe = PhieuKiemKe::create([
                'ma_phieu' => $maPhieu,
                'cua_hang_id' => $storeId,
                'nguoi_tao_id' => $user->id,
                'nguoi_duyet_id' => $user->id,
                'ngay_kiem_ke' => now(),
                'trang_thai' => 1, // Đã cân bằng kho
                'ghi_chu' => $validated['ghi_chu'] ?? 'Kiểm kê định kỳ tại chi nhánh',
            ]);

            foreach ($validated['items'] as $item) {
                $sanPhamId = (int) $item['san_pham_id'];
                $soLuongHeThong = (float) $item['so_luong_he_thong'];
                $soLuongThucTe = (float) $item['so_luong_thuc_te'];
                $chenhLech = round($soLuongThucTe - $soLuongHeThong, 2);
                $loHangId = ! empty($item['lo_hang_id']) ? (int) $item['lo_hang_id'] : null;

                ChiTietKiemKe::create([
                    'phieu_kiem_ke_id' => $phieuKiemKe->id,
                    'san_pham_id' => $sanPhamId,
                    'lo_hang_id' => $loHangId,
                    'so_luong_he_thong' => $soLuongHeThong,
                    'so_luong_thuc_te' => $soLuongThucTe,
                    'ly_do_lech' => $item['ly_do_lech'] ?? ($chenhLech !== 0.0 ? 'Hao hụt/Dập nát thực tế' : null),
                ]);

                // Nếu có chênh lệch tồn kho -> Cân bằng tồn kho
                if (abs($chenhLech) > 0.0001) {
                    /** @var TonKho $tonKho */
                    $tonKho = TonKho::firstOrCreate(
                        [
                            'cua_hang_id' => $storeId,
                            'san_pham_id' => $sanPhamId,
                        ],
                        [
                            'so_luong_ton' => $soLuongHeThong,
                            'so_luong_tam_giu' => 0,
                            'nguong_canh_bao_thap' => 10,
                        ]
                    );

                    $tonKho->so_luong_ton = $soLuongThucTe;
                    $tonKho->save();

                    if ($loHangId) {
                        $loHang = LoHang::find($loHangId);
                        if ($loHang) {
                            $loHang->so_luong_con_lai = max(0.0, round(((float) $loHang->so_luong_con_lai) + $chenhLech, 2));
                            $loHang->save();
                        }
                    }

                    // Ghi nhận Thẻ Kho
                    TheKho::create([
                        'cua_hang_id' => $storeId,
                        'san_pham_id' => $sanPhamId,
                        'lo_hang_id' => $loHangId,
                        'loai_thao_tac' => 'can_bang_kiem_ke',
                        'so_luong_thay_doi' => $chenhLech,
                        'ton_sau_thay_doi' => $soLuongThucTe,
                        'ma_chung_tu' => $maPhieu,
                        'nguoi_thuc_hien_id' => $user->id,
                        'ghi_chu' => 'Cân bằng kiểm kê: '.($item['ly_do_lech'] ?? ($chenhLech < 0 ? 'Thất thoát/Hư hao' : 'Thặng dư')),
                    ]);
                }
            }
        });

        return redirect()->route('store.audit.index')->with('success', 'Biên bản kiểm kê đã được lưu và cân bằng tồn kho thành công!');
    }
}
