<?php

namespace App\Http\Middleware;

use App\Models\CuaHang;
use App\Models\GioHang;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $selectedStoreId = CuaHang::layIdCuaHangHienTai($request);
        $user = $request->user();
        $sessionId = $request->session()->getId();

        $cartQuery = GioHang::with(['sanPham']);
        if ($user) {
            $cartQuery->where('user_id', $user->id);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }

        $serverCart = $cartQuery->get()->map(function ($item) use ($selectedStoreId) {
            if ($item->sanPham) {
                $item->sanPham->apDungGiaCuaHang($selectedStoreId);
            }

            return [
                'san_pham_id' => $item->san_pham_id,
                'ten_san_pham' => $item->sanPham?->ten_san_pham ?? '',
                'duong_dan_seo' => $item->sanPham?->duong_dan_seo ?? '',
                'hinh_anh_chinh' => $item->sanPham?->hinh_anh_chinh ?? '',
                'don_vi_tinh' => $item->sanPham?->don_vi_tinh ?? 'kg',
                'quy_cach' => $item->sanPham?->quy_cach,
                'gia_ban' => (float) ($item->sanPham?->gia_ban ?? 0),
                'gia_giam' => $item->sanPham?->gia_giam !== null ? (float) $item->sanPham->gia_giam : null,
                'so_luong' => (float) $item->so_luong,
            ];
        })->values()->all();

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'csrf_token' => csrf_token(),
            'serverCart' => $serverCart,
            'selectedStoreId' => $selectedStoreId,
            'stores' => CuaHang::where('trang_thai', 1)->get([
                'id',
                'ten_cua_hang',
                'dia_chi',
                'so_dien_thoai',
            ]),
        ]);
    }
}
