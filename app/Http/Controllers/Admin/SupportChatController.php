<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\PhienChat;
use App\Models\TinNhanChat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SupportChatController extends Controller
{
    /**
     * Danh sách và chi tiết các phiên hỗ trợ & khiếu nại khách hàng.
     */
    public function index(Request $request): Response
    {
        $status = $request->input('status');
        $topic = $request->input('topic');
        $search = $request->input('search');
        $selectedId = $request->input('session_id');

        $query = PhienChat::with([
            'user:id,name,email,avatar,phone',
            'nhanVien:id,name,email',
        ])
            ->where('loai_chat', PhienChat::LOAI_NHAN_VIEN);

        if ($status) {
            $query->where('trang_thai', $status);
        }

        if ($topic) {
            $query->where('chu_de', $topic);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ten_khach_hang', 'like', "%{$search}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$search}%")
                    ->orWhere('ma_don_hang', 'like', "%{$search}%")
                    ->orWhere('tin_nhan_cuoi', 'like', "%{$search}%");
            });
        }

        $sessions = $query->orderByRaw("CASE WHEN trang_thai = 'cho_xu_ly' THEN 1 WHEN trang_thai = 'dang_xu_ly' THEN 2 ELSE 3 END")
            ->latest('thoi_gian_tin_nhan_cuoi')
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        // Xác định session đang được chọn để hiển thị chi tiết
        $currentSession = null;
        $messages = [];
        $relatedOrder = null;

        $targetId = $selectedId ?: $sessions->first()?->id;

        if ($targetId) {
            $currentSession = PhienChat::with(['user', 'nhanVien'])->find($targetId);

            if ($currentSession) {
                // Đánh dấu các tin nhắn của khách là đã xem
                $currentSession->tinNhan()
                    ->where('nguoi_gui_type', TinNhanChat::NGUOI_GUI_KHACH_HANG)
                    ->where('da_xem', false)
                    ->update(['da_xem' => true]);

                $messages = $currentSession->tinNhan()->get();

                if ($currentSession->ma_don_hang) {
                    $relatedOrder = DonHang::with(['chiTietDonHangs.sanPham'])
                        ->where('ma_don_hang', $currentSession->ma_don_hang)
                        ->first();
                }
            }
        }

        // Thống kê số lượng theo trạng thái
        $counts = [
            'cho_xu_ly' => PhienChat::where('loai_chat', PhienChat::LOAI_NHAN_VIEN)->where('trang_thai', PhienChat::TRANG_THAI_CHO_XU_LY)->count(),
            'dang_xu_ly' => PhienChat::where('loai_chat', PhienChat::LOAI_NHAN_VIEN)->where('trang_thai', PhienChat::TRANG_THAI_DANG_XU_LY)->count(),
            'da_dong' => PhienChat::where('loai_chat', PhienChat::LOAI_NHAN_VIEN)->where('trang_thai', PhienChat::TRANG_THAI_DA_DONG)->count(),
            'khieu_nai' => PhienChat::where('loai_chat', PhienChat::LOAI_NHAN_VIEN)->where('chu_de', 'khieu_nai')->where('trang_thai', '!=', PhienChat::TRANG_THAI_DA_DONG)->count(),
        ];

        return Inertia::render('admin/support/Index', [
            'sessions' => $sessions,
            'currentSession' => $currentSession,
            'messages' => $messages,
            'relatedOrder' => $relatedOrder,
            'counts' => $counts,
            'filters' => [
                'status' => $status,
                'topic' => $topic,
                'search' => $search,
                'session_id' => $targetId,
            ],
        ]);
    }

    /**
     * Nhân viên trả lời tin nhắn cho khách hàng.
     */
    public function reply(Request $request, PhienChat $session): RedirectResponse
    {
        $validated = $request->validate([
            'noi_dung' => ['required', 'string', 'max:2000'],
        ]);

        $adminUser = Auth::user();

        TinNhanChat::create([
            'phien_chat_id' => $session->id,
            'nguoi_gui_type' => TinNhanChat::NGUOI_GUI_NHAN_VIEN,
            'nguoi_gui_id' => $adminUser->id,
            'noi_dung' => $validated['noi_dung'],
        ]);

        $session->update([
            'nhan_vien_id' => $adminUser->id,
            'trang_thai' => PhienChat::TRANG_THAI_DANG_XU_LY,
            'tin_nhan_cuoi' => $validated['noi_dung'],
            'thoi_gian_tin_nhan_cuoi' => now(),
        ]);

        return back()->with('success', 'Đã gửi phản hồi thành công đến khách hàng.');
    }

    /**
     * Cập nhật trạng thái phiên hỗ trợ (Đang xử lý, Đã giải quyết / Đã đóng).
     */
    public function updateStatus(Request $request, PhienChat $session): RedirectResponse
    {
        $validated = $request->validate([
            'trang_thai' => ['required', 'string', 'in:cho_xu_ly,dang_xu_ly,da_dong'],
        ]);

        $session->update([
            'trang_thai' => $validated['trang_thai'],
        ]);

        $statusText = match ($validated['trang_thai']) {
            'dang_xu_ly' => 'đang tiếp nhận xử lý',
            'da_dong' => 'đã giải quyết và đóng phiên',
            default => 'chờ xử lý',
        };

        // Ghi nhận một tin nhắn hệ thống nếu đóng phiên
        if ($validated['trang_thai'] === PhienChat::TRANG_THAI_DA_DONG) {
            TinNhanChat::create([
                'phien_chat_id' => $session->id,
                'nguoi_gui_type' => TinNhanChat::NGUOI_GUI_HE_THONG,
                'noi_dung' => 'Phiên hỗ trợ này đã được nhân viên đánh dấu hoàn tất. Nếu cần hỗ trợ thêm, bạn có thể nhắn tin lại bất cứ lúc nào.',
            ]);
        }

        return back()->with('success', "Phiên hỗ trợ #{$session->id} đã được chuyển sang trạng thái: {$statusText}.");
    }
}
