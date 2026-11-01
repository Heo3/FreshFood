<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\GioHang;
use App\Models\MaGiamGia;
use App\Models\SanPham;
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

        return Inertia::render('client/Cart', [
            'stores' => $stores,
            'selectedStoreId' => $cartStoreId,
            'initialCartItems' => $cartItems,
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
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không tồn tại.',
            ], 422);
        }

        if (! $coupon->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá đã hết hạn hoặc hết lượt sử dụng.',
            ], 422);
        }

        if ($total < (float) $coupon->don_hang_toi_thieu) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu '.number_format((float) $coupon->don_hang_toi_thieu).'đ để áp dụng mã này.',
            ], 422);
        }

        $discount = $coupon->tinhSoTienGiam($total);

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
