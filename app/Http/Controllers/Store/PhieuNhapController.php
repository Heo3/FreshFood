<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ChiTietPhieuNhap;
use App\Models\CuaHang;
use App\Models\LoHang;
use App\Models\NhaCungCap;
use App\Models\PhieuNhap;
use App\Models\SanPham;
use App\Models\TheKho;
use App\Models\TonKho;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PhieuNhapController extends Controller
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

        $phieuNhaps = PhieuNhap::query()
            ->where('cua_hang_id', $storeId)
            ->with(['nhaCungCap', 'nguoiTao', 'chiTiets.sanPham'])
            ->latest('id')
            ->get();

        return Inertia::render('store/inbound/Index', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'phieuNhaps' => $phieuNhaps,
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

        $suppliers = NhaCungCap::where('trang_thai', 1)->get();
        $products = SanPham::dangKinhDoanh()->get(['id', 'ten_san_pham', 'ma_va_vach', 'don_vi_tinh', 'gia_ban']);

        return Inertia::render('store/inbound/Create', [
            'stores' => $stores,
            'currentStore' => $currentStore,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nha_cung_cap_id' => ['required', 'exists:nha_cung_cap,id'],
            'store_id' => ['nullable', 'exists:cua_hang,id'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.san_pham_id' => ['required', 'exists:san_pham,id'],
            'items.*.so_luong' => ['required', 'numeric', 'min:0.1'],
            'items.*.don_gia_nhap' => ['required', 'numeric', 'min:0'],
            'items.*.ma_lo' => ['nullable', 'string', 'max:50'],
            'items.*.ngay_san_xuat' => ['required', 'date'],
            'items.*.ngay_het_han' => ['required', 'date', 'after:items.*.ngay_san_xuat'],
        ]);

        $storeId = $this->layStoreId($request);
        $user = $request->user();

        DB::transaction(function () use ($validated, $storeId, $user) {
            $maPhieu = 'PN-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
            $tongTien = 0.0;

            foreach ($validated['items'] as $item) {
                $tongTien += ($item['so_luong'] * $item['don_gia_nhap']);
            }

            $phieuNhap = PhieuNhap::create([
                'ma_phieu' => $maPhieu,
                'cua_hang_id' => $storeId,
                'nha_cung_cap_id' => $validated['nha_cung_cap_id'],
                'nguoi_tao_id' => $user->id,
                'nguoi_duyet_id' => $user->id,
                'tong_tien' => $tongTien,
                'trang_thai' => 1, // Đã nhập kho
                'ngay_nhap' => now(),
                'ghi_chu' => $validated['ghi_chu'] ?? 'Nhập kho thực phẩm tươi',
            ]);

            foreach ($validated['items'] as $item) {
                $sanPhamId = (int) $item['san_pham_id'];
                $soLuong = (float) $item['so_luong'];
                $donGiaNhap = (float) $item['don_gia_nhap'];
                $thanhTien = $soLuong * $donGiaNhap;
                $maLo = ! empty($item['ma_lo'])
                    ? $item['ma_lo']
                    : sprintf('LO-%d-%s-%s', $sanPhamId, now()->format('ymd'), strtoupper(Str::random(3)));

                // Tạo Lô Hàng
                $loHang = LoHang::create([
                    'ma_lo' => $maLo,
                    'san_pham_id' => $sanPhamId,
                    'cua_hang_id' => $storeId,
                    'nha_cung_cap_id' => $validated['nha_cung_cap_id'],
                    'phieu_nhap_id' => $phieuNhap->id,
                    'ngay_san_xuat' => $item['ngay_san_xuat'],
                    'ngay_het_han' => $item['ngay_het_han'],
                    'so_luong_nhap' => $soLuong,
                    'so_luong_con_lai' => $soLuong,
                    'gia_nhap' => $donGiaNhap,
                    'trang_thai' => 1,
                ]);

                // Tạo Chi Tiết Phiếu Nhập
                ChiTietPhieuNhap::create([
                    'phieu_nhap_id' => $phieuNhap->id,
                    'san_pham_id' => $sanPhamId,
                    'lo_hang_id' => $loHang->id,
                    'ma_lo' => $maLo,
                    'so_luong' => $soLuong,
                    'don_gia_nhap' => $donGiaNhap,
                    'thanh_tien' => $thanhTien,
                    'ngay_san_xuat' => $item['ngay_san_xuat'],
                    'ngay_het_han' => $item['ngay_het_han'],
                ]);

                // Cập nhật tồn kho TonKho
                /** @var TonKho $tonKho */
                $tonKho = TonKho::firstOrCreate(
                    [
                        'cua_hang_id' => $storeId,
                        'san_pham_id' => $sanPhamId,
                    ],
                    [
                        'so_luong_ton' => 0,
                        'so_luong_tam_giu' => 0,
                        'nguong_canh_bao_thap' => 10,
                    ]
                );

                $tonKho->so_luong_ton = round(((float) $tonKho->so_luong_ton) + $soLuong, 2);
                $tonKho->save();

                // Ghi nhận Thẻ Kho
                TheKho::create([
                    'cua_hang_id' => $storeId,
                    'san_pham_id' => $sanPhamId,
                    'lo_hang_id' => $loHang->id,
                    'loai_thao_tac' => 'nhap_kho',
                    'so_luong_thay_doi' => $soLuong,
                    'ton_sau_thay_doi' => (float) $tonKho->so_luong_ton,
                    'ma_chung_tu' => $maPhieu,
                    'nguoi_thuc_hien_id' => $user->id,
                    'ghi_chu' => "Nhập kho theo phiếu #{$maPhieu}",
                ]);
            }
        });

        return redirect()->route('store.inbound.index')->with('success', 'Tạo phiếu nhập và cập nhật tồn kho lô hàng thành công!');
    }
}
