<?php

namespace Database\Seeders;

use App\Models\CuaHang;
use App\Models\LoHang;
use App\Models\NhaCungCap;
use App\Models\SanPham;
use App\Models\TonKho;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LoHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = CuaHang::all();
        $products = SanPham::all();
        $suppliers = NhaCungCap::all();

        $today = Carbon::today();

        foreach ($stores as $storeIndex => $store) {
            foreach ($products as $productIndex => $product) {
                $supplier = $suppliers[$productIndex % $suppliers->count()] ?? null;
                $costPrice = round($product->gia_ban * 0.7, -2);

                $totalStockForProduct = 0;

                // Tạo 3 lô hàng cho mỗi sản phẩm tại mỗi cửa hàng:
                // Lô 1: Lô cận date (HSD 1-2 ngày nữa) - Ưu tiên FEFO số 1
                $expDays1 = ($product->la_tuoi_song) ? 1 : 2;
                $batchCode1 = sprintf('LH%02d%02d-01-EXP%s', $store->id, $product->id, $today->copy()->addDays($expDays1)->format('md'));
                $qty1 = ($product->don_vi_tinh === 'Kg') ? 15.0 : 20.0;

                LoHang::updateOrCreate(
                    ['ma_lo' => $batchCode1],
                    [
                        'san_pham_id' => $product->id,
                        'cua_hang_id' => $store->id,
                        'nha_cung_cap_id' => $supplier?->id,
                        'ngay_san_xuat' => $today->copy()->subDays(3)->toDateString(),
                        'ngay_het_han' => $today->copy()->addDays($expDays1)->toDateString(),
                        'so_luong_nhap' => $qty1 + 10,
                        'so_luong_con_lai' => $qty1,
                        'gia_nhap' => $costPrice,
                        'trang_thai' => 1,
                    ]
                );
                $totalStockForProduct += $qty1;

                // Lô 2: Lô hạn vừa (HSD 5-8 ngày nữa)
                $expDays2 = ($product->la_tuoi_song) ? 5 : 10;
                $batchCode2 = sprintf('LH%02d%02d-02-EXP%s', $store->id, $product->id, $today->copy()->addDays($expDays2)->format('md'));
                $qty2 = ($product->don_vi_tinh === 'Kg') ? 35.0 : 40.0;

                LoHang::updateOrCreate(
                    ['ma_lo' => $batchCode2],
                    [
                        'san_pham_id' => $product->id,
                        'cua_hang_id' => $store->id,
                        'nha_cung_cap_id' => $supplier?->id,
                        'ngay_san_xuat' => $today->copy()->subDays(1)->toDateString(),
                        'ngay_het_han' => $today->copy()->addDays($expDays2)->toDateString(),
                        'so_luong_nhap' => $qty2,
                        'so_luong_con_lai' => $qty2,
                        'gia_nhap' => $costPrice,
                        'trang_thai' => 1,
                    ]
                );
                $totalStockForProduct += $qty2;

                // Lô 3: Lô mới nhập hôm nay (HSD 15 - 45 ngày)
                $expDays3 = ($product->han_su_dung_ngay ?? 20) + 5;
                $batchCode3 = sprintf('LH%02d%02d-03-EXP%s', $store->id, $product->id, $today->copy()->addDays($expDays3)->format('md'));
                $qty3 = ($product->don_vi_tinh === 'Kg') ? 50.0 : 60.0;

                LoHang::updateOrCreate(
                    ['ma_lo' => $batchCode3],
                    [
                        'san_pham_id' => $product->id,
                        'cua_hang_id' => $store->id,
                        'nha_cung_cap_id' => $supplier?->id,
                        'ngay_san_xuat' => $today->toDateString(),
                        'ngay_het_han' => $today->copy()->addDays($expDays3)->toDateString(),
                        'so_luong_nhap' => $qty3,
                        'so_luong_con_lai' => $qty3,
                        'gia_nhap' => $costPrice,
                        'trang_thai' => 1,
                    ]
                );
                $totalStockForProduct += $qty3;

                // Cập nhật tồn kho tổng hợp tại cửa hàng (TonKho)
                TonKho::updateOrCreate(
                    [
                        'cua_hang_id' => $store->id,
                        'san_pham_id' => $product->id,
                    ],
                    [
                        'so_luong_ton' => $totalStockForProduct,
                        'so_luong_tam_giu' => 0,
                        'nguong_canh_bao_thap' => 15.0,
                    ]
                );
            }
        }
    }
}
