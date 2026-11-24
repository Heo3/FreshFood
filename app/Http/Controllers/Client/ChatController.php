<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\PhienChat;
use App\Models\TinNhanChat;
use App\Services\ChatAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        protected ChatAiService $aiService
    ) {}

    /**
     * Lấy hoặc tạo phiên chat hiện tại của khách hàng.
     */
    public function getOrCreateSession(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id') ?: $request->cookie('freshfood_chat_session_id');

        if (! $sessionId) {
            $sessionId = (string) Str::uuid();
        }

        $userId = Auth::id();
        $user = Auth::user();

        // Tìm phiên chat gần nhất (ưu tiên theo user_id nếu đã đăng nhập, hoặc session_id)
        $query = PhienChat::query();
        if ($userId) {
            $query->where(function ($q) use ($userId, $sessionId) {
                $q->where('user_id', $userId)->orWhere('session_id', $sessionId);
            });
        } else {
            $query->where('session_id', $sessionId);
        }

        $session = $query->latest()->first();

        if (! $session) {
            $session = PhienChat::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'ten_khach_hang' => $user?->name,
                'so_dien_thoai' => $user?->phone,
                'loai_chat' => PhienChat::LOAI_AI,
                'chu_de' => 'tu_van',
                'trang_thai' => PhienChat::TRANG_THAI_CHO_XU_LY,
            ]);

            // Thêm tin nhắn chào mừng ban đầu từ AI
            $welcome = $this->aiService->generateReply('chào', [], $user);
            TinNhanChat::create([
                'phien_chat_id' => $session->id,
                'nguoi_gui_type' => TinNhanChat::NGUOI_GUI_AI,
                'noi_dung' => $welcome['content'],
                'du_lieu_dinh_kem' => $welcome['cards'],
            ]);
        } elseif ($userId && ! $session->user_id) {
            $session->update([
                'user_id' => $userId,
                'ten_khach_hang' => $session->ten_khach_hang ?: $user?->name,
                'so_dien_thoai' => $session->so_dien_thoai ?: $user?->phone,
            ]);
        }

        $messages = $session->tinNhan()->get();

        return response()->json([
            'session' => $session,
            'messages' => $messages,
        ])->cookie('freshfood_chat_session_id', $sessionId, 60 * 24 * 30);
    }

    /**
     * Gửi tin nhắn từ khách hàng (hỗ trợ cả Chat AI và Chat Nhân viên).
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string'],
            'noi_dung' => ['required', 'string', 'max:2000'],
            'loai_chat' => ['nullable', 'string', 'in:ai,nhan_vien'],
            'chu_de' => ['nullable', 'string', 'in:tu_van,khieu_nai,doi_tra,khac'],
            'ma_don_hang' => ['nullable', 'string', 'max:50'],
            'ten_khach_hang' => ['nullable', 'string', 'max:100'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
        ]);

        $user = Auth::user();
        $sessionId = $validated['session_id'];

        $session = PhienChat::where('session_id', $sessionId)
            ->when($user, fn ($q) => $q->orWhere('user_id', $user->id))
            ->latest()
            ->first();

        if (! $session) {
            $session = PhienChat::create([
                'session_id' => $sessionId,
                'user_id' => $user?->id,
                'ten_khach_hang' => $validated['ten_khach_hang'] ?? $user?->name,
                'so_dien_thoai' => $validated['so_dien_thoai'] ?? $user?->phone,
                'loai_chat' => $validated['loai_chat'] ?? PhienChat::LOAI_AI,
                'chu_de' => $validated['chu_de'] ?? 'tu_van',
                'ma_don_hang' => $validated['ma_don_hang'] ?? null,
                'trang_thai' => PhienChat::TRANG_THAI_CHO_XU_LY,
            ]);
        } else {
            $updates = [
                'tin_nhan_cuoi' => $validated['noi_dung'],
                'thoi_gian_tin_nhan_cuoi' => now(),
            ];
            if (! empty($validated['loai_chat'])) {
                $updates['loai_chat'] = $validated['loai_chat'];
            }
            if (! empty($validated['chu_de'])) {
                $updates['chu_de'] = $validated['chu_de'];
            }
            if (! empty($validated['ma_don_hang'])) {
                $updates['ma_don_hang'] = $validated['ma_don_hang'];
            }
            if (! empty($validated['ten_khach_hang']) && ! $session->ten_khach_hang) {
                $updates['ten_khach_hang'] = $validated['ten_khach_hang'];
            }
            if (! empty($validated['so_dien_thoai']) && ! $session->so_dien_thoai) {
                $updates['so_dien_thoai'] = $validated['so_dien_thoai'];
            }
            if ($user && ! $session->user_id) {
                $updates['user_id'] = $user->id;
            }
            $session->update($updates);
        }

        // 1. Lưu tin nhắn người dùng
        $userMsg = TinNhanChat::create([
            'phien_chat_id' => $session->id,
            'nguoi_gui_type' => TinNhanChat::NGUOI_GUI_KHACH_HANG,
            'nguoi_gui_id' => $user?->id,
            'noi_dung' => $validated['noi_dung'],
        ]);

        $replies = [];

        // 2. Xử lý phản hồi theo loại chat
        if ($session->loai_chat === PhienChat::LOAI_AI) {
            // Lấy lịch sử 6 tin gần nhất
            $history = $session->tinNhan()
                ->where('id', '!=', $userMsg->id)
                ->latest()
                ->limit(6)
                ->get()
                ->reverse()
                ->map(fn ($m) => [
                    'role' => $m->nguoi_gui_type,
                    'content' => $m->noi_dung,
                ])
                ->all();

            $aiReply = $this->aiService->generateReply($validated['noi_dung'], $history, $user);

            $aiMsg = TinNhanChat::create([
                'phien_chat_id' => $session->id,
                'nguoi_gui_type' => TinNhanChat::NGUOI_GUI_AI,
                'noi_dung' => $aiReply['content'],
                'du_lieu_dinh_kem' => $aiReply['cards'],
            ]);

            $session->update([
                'tin_nhan_cuoi' => $aiReply['content'],
                'thoi_gian_tin_nhan_cuoi' => now(),
            ]);

            $replies[] = $aiMsg;
        } else {
            // Chế độ Nhân viên: Nếu là tin nhắn đầu tiên chuyển sang nhân viên, gửi tin hệ thống tự động
            $session->update(['trang_thai' => PhienChat::TRANG_THAI_CHO_XU_LY]);
            $staffMsgCount = $session->tinNhan()->whereIn('nguoi_gui_type', [TinNhanChat::NGUOI_GUI_NHAN_VIEN, TinNhanChat::NGUOI_GUI_HE_THONG])->count();
            if ($staffMsgCount === 0) {
                $topicText = match ($session->chu_de) {
                    'khieu_nai' => 'khiếu nại sản phẩm/đơn hàng',
                    'doi_tra' => 'yêu cầu đổi trả hàng',
                    default => 'tư vấn dịch vụ',
                };
                $orderNotice = $session->ma_don_hang ? " (Đơn hàng: #{$session->ma_don_hang})" : '';

                $sysMsg = TinNhanChat::create([
                    'phien_chat_id' => $session->id,
                    'nguoi_gui_type' => TinNhanChat::NGUOI_GUI_HE_THONG,
                    'noi_dung' => "Yêu cầu hỗ trợ {$topicText}{$orderNotice} của bạn đã được chuyển đến bộ phận CSKH Fresh Food. Chuyên viên sẽ trả lời trực tiếp trong ít phút, bạn vui lòng giữ kết nối nhé! 🌿",
                ]);
                $replies[] = $sysMsg;
            }
        }

        return response()->json([
            'user_message' => $userMsg,
            'replies' => $replies,
            'session' => $session->fresh(),
        ]);
    }

    /**
     * Chuyển đổi giữa chế độ AI và Nhân viên tư vấn / Khiếu nại.
     */
    public function switchMode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string'],
            'loai_chat' => ['required', 'string', 'in:ai,nhan_vien'],
            'chu_de' => ['nullable', 'string', 'in:tu_van,khieu_nai,doi_tra,khac'],
            'ma_don_hang' => ['nullable', 'string', 'max:50'],
            'ten_khach_hang' => ['nullable', 'string', 'max:100'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
        ]);

        $user = Auth::user();
        $session = PhienChat::where('session_id', $validated['session_id'])
            ->when($user, fn ($q) => $q->orWhere('user_id', $user->id))
            ->latest()
            ->firstOrFail();

        $session->update([
            'loai_chat' => $validated['loai_chat'],
            'chu_de' => $validated['chu_de'] ?? $session->chu_de,
            'ma_don_hang' => $validated['ma_don_hang'] ?? $session->ma_don_hang,
            'ten_khach_hang' => $validated['ten_khach_hang'] ?? $session->ten_khach_hang,
            'so_dien_thoai' => $validated['so_dien_thoai'] ?? $session->so_dien_thoai,
            'trang_thai' => ($validated['loai_chat'] === PhienChat::LOAI_NHAN_VIEN) ? PhienChat::TRANG_THAI_CHO_XU_LY : $session->trang_thai,
        ]);

        // Tạo thông báo hệ thống khi chuyển chế độ
        $noiDung = ($validated['loai_chat'] === PhienChat::LOAI_NHAN_VIEN)
            ? 'Bạn đã chuyển sang kết nối trực tiếp với Chuyên viên tư vấn & Khiếu nại. Hãy gửi câu hỏi hoặc phản hồi của bạn dưới đây.'
            : 'Bạn đã quay trở lại trò chuyện cùng Trợ lý AI Fresh Food 24/7.';

        $sysMsg = TinNhanChat::create([
            'phien_chat_id' => $session->id,
            'nguoi_gui_type' => TinNhanChat::NGUOI_GUI_HE_THONG,
            'noi_dung' => $noiDung,
        ]);

        return response()->json([
            'session' => $session->fresh(),
            'system_message' => $sysMsg,
            'messages' => $session->tinNhan()->get(),
        ]);
    }

    /**
     * Lấy danh sách tin nhắn mới nhất (phục vụ polling tin nhắn mới từ nhân viên).
     */
    public function getMessages(Request $request, int $phienChatId): JsonResponse
    {
        $session = PhienChat::findOrFail($phienChatId);
        $messages = $session->tinNhan()->get();

        return response()->json([
            'session' => $session,
            'messages' => $messages,
        ]);
    }
}
