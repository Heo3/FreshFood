<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\GioHang;
use App\Models\MaGiamGia;
use App\Models\SanPham;
use App\Models\TonKho;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $stores = CuaHang::where('trang_thai', 1)->get();
        $storeId = CuaHang::layIdCuaHangHienTai($request);
        $user = $request->user();
        $sessionId = $request->session()->getId();

        $query = GioHang::with(['sanPham.danhMuc', 'cuaHang']);

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('session_id', $sessionId);
        }

        $cartItems = $query->get();

        // Chi nhánh của giỏ hàng là chi nhánh của các sản phẩm hiện có
        $cartStoreId = $cartItems->first()?->cua_hang_id ?? $storeId;

        foreach ($cartItems as $item) {
            if ($item->sanPham) {
                $item->sanPham->apDungGiaCuaHang($item->cua_hang_id ?? $cartStoreId);
            }
        }

        $availableCoupons = MaGiamGia::hieuLuc()->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'ma_khuyen_mai' => $c->ma_khuyen_mai,
                'ten_chuong_trinh' => $c->ten_chuong_trinh,
                'loai_giam_gia' => $c->loai_giam_gia,
                'gia_tri_giam' => (float) $c->gia_tri_giam,
                'don_hang_toi_thieu' => (float) $c->don_hang_toi_thieu,
                'giam_toi_da' => $c->giam_toi_da ? (float) $c->giam_toi_da : null,
                'ngay_ket_thuc' => $c->ngay_ket_thuc?->format('d/m/Y'),
            ];
        });

        // Tự động khôi phục mã giảm giá từ session nếu còn hiệu lực
        $sessionCouponCode = $request->session()->get('applied_coupon_code');
        $initialAppliedCoupon = null;
        if ($sessionCouponCode) {
            $couponModel = MaGiamGia::where('ma_khuyen_mai', $sessionCouponCode)->first();
            if ($couponModel && $couponModel->isAvailable()) {
                $subtotal = 0;
                foreach ($cartItems as $item) {
                    $itemPrice = $item->sanPham ? ($item->sanPham->gia_giam ?? $item->sanPham->gia_ban) : 0;
                    $subtotal += ($itemPrice * $item->so_luong);
                }
                if ($subtotal >= (float) $couponModel->don_hang_toi_thieu) {
                    $initialAppliedCoupon = [
                        'id' => $couponModel->id,
                        'code' => $couponModel->ma_khuyen_mai,
                        'program' => $couponModel->ten_chuong_trinh,
                        'type' => $couponModel->loai_giam_gia,
                        'value' => (float) $couponModel->gia_tri_giam,
                        'discount_amount' => $couponModel->tinhSoTienGiam($subtotal),
                    ];
                } else {
                    $request->session()->forget('applied_coupon_code');
                }
            } else {
                $request->session()->forget('applied_coupon_code');
            }
        }

        return Inertia::render('client/Cart', [
            'stores' => $stores,
            'selectedStoreId' => $cartStoreId,
            'initialCartItems' => $cartItems,
            'availableCoupons' => $availableCoupons,
            'appliedCoupon' => $initialAppliedCoupon,
        ]);
    }

    /**
     * API làm mới đơn giá các sản phẩm trong giỏ hàng theo chi nhánh được chọn
     */
    public function refreshPrices(Request $request): JsonResponse
    {
        $storeId = (int) ($request->input('store_id') ?? CuaHang::layIdCuaHangHienTai($request));
        $productIds = $request->input('product_ids', []);

        if (empty($productIds)) {
            return response()->json([
                'success' => true,
                'store_id' => $storeId,
                'prices' => (object) [],
            ]);
        }

        $products = SanPham::whereIn('id', $productIds)
            ->with(['giaTheoCuaHang' => function ($q) use ($storeId) {
                $q->where('cua_hang_id', $storeId);
            }])
            ->get();

        $prices = [];
        foreach ($products as $prod) {
            $storeGia = $prod->giaTheoCuaHang->first();
            $prices[$prod->id] = [
                'san_pham_id' => $prod->id,
                'gia_ban' => $storeGia ? (float) $storeGia->gia_ban : (float) $prod->gia_ban,
                'gia_giam' => $storeGia
                    ? ($storeGia->gia_giam !== null ? (float) $storeGia->gia_giam : null)
                    : ($prod->gia_giam !== null ? (float) $prod->gia_giam : null),
                'co_gia_chi_nhanh' => $storeGia !== null,
            ];
        }

        return response()->json([
            'success' => true,
            'store_id' => $storeId,
            'prices' => $prices,
        ]);
    }

    public function checkCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'total' => ['required', 'numeric', 'min:0'],
        ]);

        $code = trim(strtoupper($request->input('code')));
        $total = (float) $request->input('total');

        $coupon = MaGiamGia::where('ma_khuyen_mai', $code)->first();

        if (! $coupon) {
            $request->session()->forget('applied_coupon_code');

            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không tồn tại.',
            ], 422);
        }

        if (! $coupon->isAvailable()) {
            $request->session()->forget('applied_coupon_code');

            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá đã hết hạn hoặc hết lượt sử dụng.',
            ], 422);
        }

        if ($total < (float) $coupon->don_hang_toi_thieu) {
            $request->session()->forget('applied_coupon_code');

            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu '.number_format((float) $coupon->don_hang_toi_thieu).'đ để áp dụng mã này.',
            ], 422);
        }

        $discount = $coupon->tinhSoTienGiam($total);

        // Lưu vào session để tự động mang sang trang thanh toán (checkout)
        $request->session()->put('applied_coupon_code', $coupon->ma_khuyen_mai);

        return response()->json([
            'success' => true,
            'message' => 'Áp dụng mã giảm giá thành công!',
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->ma_khuyen_mai,
                'program' => $coupon->ten_chuong_trinh,
                'type' => $coupon->loai_giam_gia,
                'value' => (float) $coupon->gia_tri_giam,
                'discount_amount' => $discount,
            ],
        ]);
    }

    /**
     * Gỡ bỏ mã giảm giá khỏi session
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        $request->session()->forget('applied_coupon_code');

        return response()->json([
            'success' => true,
            'message' => 'Đã gỡ bỏ mã giảm giá.',
        ]);
    }

    /**
     * Thêm sản phẩm vào giỏ hàng trong database (table gio_hang)
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'san_pham_id' => ['required', 'exists:san_pham,id'],
            'so_luong' => ['required', 'numeric', 'min:0.1'],
            'store_id' => ['nullable', 'exists:cua_hang,id'],
            'reset_cart' => ['nullable', 'boolean'],
        ]);

        $storeId = (int) ($request->input('store_id') ?? CuaHang::layIdCuaHangHienTai($request));
        $user = $request->user();
        $sessionId = $request->session()->getId();

        $productId = (int) $request->input('san_pham_id');
        $quantity = (float) $request->input('so_luong');

        // Kiểm tra tồn kho tại chi nhánh được chọn
        $tonKho = TonKho::where('cua_hang_id', $storeId)
            ->where('san_pham_id', $productId)
            ->first();

        $hasStoreInventory = TonKho::where('cua_hang_id', $storeId)->exists();
        $hasProductInventory = TonKho::where('san_pham_id', $productId)->exists();

        $isOutOfStock = false;
        if ($tonKho) {
            $isOutOfStock = ($tonKho->so_luong_kha_dung <= 0);
        } elseif ($hasStoreInventory || $hasProductInventory) {
            $isOutOfStock = true;
        }

        if ($isOutOfStock) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm này tạm thời đã hết hàng trong kho tại chi nhánh đã chọn.',
            ], 422);
        }

        // Luôn đồng nhất toàn bộ sản phẩm trong giỏ hàng về cùng một chi nhánh
        $existingCartQuery = GioHang::query();
        if ($user) {
            $existingCartQuery->where(function ($q) use ($user, $sessionId) {
                $q->where('user_id', $user->id)
                    ->orWhere('session_id', $sessionId);
            });
        } else {
            $existingCartQuery->where('session_id', $sessionId);
        }

        if ($request->boolean('reset_cart')) {
            $existingCartQuery->delete();
        } else {
            $existingCartQuery->where('cua_hang_id', '!=', $storeId)->update(['cua_hang_id' => $storeId]);
        }

        $query = GioHang::where('cua_hang_id', $storeId)
            ->where('san_pham_id', $productId);

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('session_id', $sessionId);
        }

        $cartItem = $query->first();

        if ($cartItem) {
            $cartItem->so_luong += $quantity;
            $cartItem->save();
        } else {
            $cartItem = GioHang::create([
                'user_id' => $user ? $user->id : null,
                'session_id' => $sessionId,
                'cua_hang_id' => $storeId,
                'san_pham_id' => $productId,
                'so_luong' => $quantity,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu sản phẩm vào giỏ hàng trong CSDL!',
            'cart_item' => $cartItem,
        ]);
    }

    /**
     * Cập nhật số lượng sản phẩm trong database
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'san_pham_id' => ['required', 'exists:san_pham,id'],
            'so_luong' => ['required', 'numeric', 'min:0'],
            'store_id' => ['nullable', 'exists:cua_hang,id'],
        ]);

        $storeId = (int) ($request->input('store_id') ?? CuaHang::layIdCuaHangHienTai($request));
        $user = $request->user();
        $sessionId = $request->session()->getId();
        $productId = (int) $request->input('san_pham_id');
        $quantity = (float) $request->input('so_luong');

        $query = GioHang::where('cua_hang_id', $storeId)
            ->where('san_pham_id', $productId);

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('session_id', $sessionId);
        }

        if ($quantity <= 0) {
            $query->delete();
        } else {
            // Kiểm tra tồn kho tại chi nhánh khi tăng số lượng
            $tonKho = TonKho::where('cua_hang_id', $storeId)
                ->where('san_pham_id', $productId)
                ->first();

            $hasStoreInventory = TonKho::where('cua_hang_id', $storeId)->exists();
            $hasProductInventory = TonKho::where('san_pham_id', $productId)->exists();

            if ($tonKho && $tonKho->so_luong_kha_dung < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Kho chỉ còn {$tonKho->so_luong_kha_dung} sản phẩm khả dụng.",
                ], 422);
            } elseif (! $tonKho && ($hasStoreInventory || $hasProductInventory)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sản phẩm này tạm thời đã hết hàng trong kho tại chi nhánh đã chọn.',
                ], 422);
            }

            $cartItem = $query->first();
            if ($cartItem) {
                $cartItem->update(['so_luong' => $quantity]);
            } else {
                GioHang::create([
                    'user_id' => $user?->id,
                    'session_id' => $sessionId,
                    'cua_hang_id' => $storeId,
                    'san_pham_id' => $productId,
                    'so_luong' => $quantity,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Xóa 1 sản phẩm khỏi giỏ hàng trong database
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'san_pham_id' => ['required', 'exists:san_pham,id'],
            'store_id' => ['nullable', 'exists:cua_hang,id'],
        ]);

        $user = $request->user();
        $sessionId = $request->session()->getId();
        $productId = (int) $request->input('san_pham_id');
        $storeId = $request->input('store_id');

        $query = GioHang::where('san_pham_id', $productId);
        if ($storeId) {
            $query->where('cua_hang_id', (int) $storeId);
        }
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('session_id', $sessionId);
        }

        $query->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Xóa sạch toàn bộ giỏ hàng trong database
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();

        $query = GioHang::query();
        if ($user) {
            $query->where(function ($q) use ($user, $sessionId) {
                $q->where('user_id', $user->id)
                    ->orWhere('session_id', $sessionId);
            });
        } else {
            $query->where('session_id', $sessionId);
        }
        $query->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Đồng bộ toàn bộ giỏ hàng từ Frontend vào database
     */
    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();
        $items = $request->input('items', []);
        $storeId = (int) ($request->input('store_id') ?? CuaHang::layIdCuaHangHienTai($request));

        // Xóa giỏ hàng cũ của user hoặc session này tại chi nhánh
        $query = GioHang::where('cua_hang_id', $storeId);
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('session_id', $sessionId);
        }
        $query->delete();

        foreach ($items as $item) {
            if (isset($item['san_pham_id']) && isset($item['so_luong']) && (float) $item['so_luong'] > 0) {
                $product = SanPham::find($item['san_pham_id']);
                if ($product) {
                    GioHang::create([
                        'user_id' => $user?->id,
                        'session_id' => $sessionId,
                        'cua_hang_id' => $storeId,
                        'san_pham_id' => $product->id,
                        'so_luong' => (float) $item['so_luong'],
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }
}
