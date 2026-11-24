<script setup lang="ts">
import { useCart } from '@/composables/useCart';
import { Link, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Bot,
    Check,
    Copy,
    CornerDownLeft,
    ExternalLink,
    Headphones,
    MessageCircle,
    Package,
    Plus,
    RefreshCw,
    RotateCcw,
    Send,
    ShoppingCart,
    Sparkles,
    Trash2,
    User,
    X,
} from 'lucide-vue-next';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();
const authUser = (page.props as any).auth?.user;
const { addToCart } = useCart();

const isOpen = ref(false);
const activeTab = ref<'ai' | 'staff'>('ai');
const isConnecting = ref(false);
const isSending = ref(false);
const copiedId = ref<number | string | null>(null);
const addedToCartMap = ref<Record<number, boolean>>({});

interface ChatMessage {
    id: number | string;
    nguoi_gui_type: 'khach_hang' | 'ai' | 'nhan_vien' | 'he_thong';
    noi_dung: string;
    du_lieu_dinh_kem?: any;
    created_at?: string;
}

interface ChatSession {
    id: number;
    session_id: string;
    loai_chat: 'ai' | 'nhan_vien';
    chu_de: 'tu_van' | 'khieu_nai' | 'doi_tra' | 'khac';
    ma_don_hang?: string | null;
    ten_khach_hang?: string | null;
    so_dien_thoai?: string | null;
    trang_thai: 'cho_xu_ly' | 'dang_xu_ly' | 'da_dong';
}

const session = ref<ChatSession | null>(null);
const messages = ref<ChatMessage[]>([]);
const inputText = ref('');
const chatScrollContainer = ref<HTMLDivElement | null>(null);
const textareaRef = ref<HTMLTextAreaElement | null>(null);

// Form nhân viên / khiếu nại
const staffForm = ref({
    chu_de: 'khieu_nai' as 'tu_van' | 'khieu_nai' | 'doi_tra' | 'khac',
    ma_don_hang: '',
    ten_khach_hang: authUser?.name || '',
    so_dien_thoai: authUser?.phone || '',
});

let pollTimer: any = null;

// Lấy CSRF token cho fetch
function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (meta?.content) {
        return meta.content;
    }
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function scrollToBottom() {
    nextTick(() => {
        if (chatScrollContainer.value) {
            chatScrollContainer.value.scrollTop = chatScrollContainer.value.scrollHeight;
        }
    });
}

// Khởi tạo hoặc lấy lại phiên chat
async function initSession(forceNew: boolean = false) {
    isConnecting.value = true;
    try {
        const csrf = getCsrfToken();
        const storedSessionId = forceNew ? '' : (session.value?.session_id || localStorage.getItem('freshfood_chat_session_id') || '');

        if (forceNew) {
            localStorage.removeItem('freshfood_chat_session_id');
            session.value = null;
            messages.value = [];
        }

        const res = await fetch('/api/chat/session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-XSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                session_id: storedSessionId,
            }),
        });

        if (res.ok) {
            const data = await res.json();
            session.value = data.session;
            messages.value = data.messages || [];
            if (data.session?.session_id) {
                localStorage.setItem('freshfood_chat_session_id', data.session.session_id);
            }
            if (data.session?.loai_chat === 'nhan_vien') {
                activeTab.value = 'staff';
                staffForm.value.chu_de = data.session.chu_de;
                staffForm.value.ma_don_hang = data.session.ma_don_hang || '';
            }
            scrollToBottom();
        }
    } catch (err) {
        console.error('Lỗi khởi tạo chat:', err);
    } finally {
        isConnecting.value = false;
    }
}

// Gửi tin nhắn
async function handleSend() {
    const text = inputText.value.trim();
    if (!text || isSending.value) {
        return;
    }

    if (!session.value) {
        await initSession();
    }

    const currentSessionId = session.value?.session_id || localStorage.getItem('freshfood_chat_session_id') || '';

    // Tin nhắn tạm thời hiển thị trên UI ngay
    const tempUserMsg: ChatMessage = {
        id: 'temp-' + Date.now(),
        nguoi_gui_type: 'khach_hang',
        noi_dung: text,
        created_at: new Date().toISOString(),
    };
    messages.value.push(tempUserMsg);
    inputText.value = '';
    scrollToBottom();

    // Reset textarea height
    if (textareaRef.value) {
        textareaRef.value.style.height = 'auto';
    }

    isSending.value = true;
    try {
        const csrf = getCsrfToken();
        const res = await fetch('/api/chat/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-XSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                session_id: currentSessionId,
                noi_dung: text,
                loai_chat: activeTab.value === 'ai' ? 'ai' : 'nhan_vien',
                chu_de: activeTab.value === 'staff' ? staffForm.value.chu_de : undefined,
                ma_don_hang: activeTab.value === 'staff' ? staffForm.value.ma_don_hang : undefined,
                ten_khach_hang: staffForm.value.ten_khach_hang,
                so_dien_thoai: staffForm.value.so_dien_thoai,
            }),
        });

        if (res.ok) {
            const data = await res.json();
            const idx = messages.value.findIndex((m) => m.id === tempUserMsg.id);
            if (idx !== -1 && data.user_message) {
                messages.value[idx] = data.user_message;
            }
            if (data.replies && data.replies.length > 0) {
                messages.value.push(...data.replies);
            }
            if (data.session) {
                session.value = data.session;
            }
            scrollToBottom();
        }
    } catch (err) {
        console.error('Lỗi khi gửi tin nhắn:', err);
    } finally {
        isSending.value = false;
    }
}

// Chuyển sang chế độ Nhân viên / Khiếu nại
async function switchMode(mode: 'ai' | 'staff') {
    activeTab.value = mode;
    if (!session.value) {
        await initSession();
        return;
    }

    try {
        const csrf = getCsrfToken();
        const res = await fetch('/api/chat/switch-mode', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-XSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                session_id: session.value.session_id,
                loai_chat: mode === 'ai' ? 'ai' : 'nhan_vien',
                chu_de: staffForm.value.chu_de,
                ma_don_hang: staffForm.value.ma_don_hang,
                ten_khach_hang: staffForm.value.ten_khach_hang,
                so_dien_thoai: staffForm.value.so_dien_thoai,
            }),
        });

        if (res.ok) {
            const data = await res.json();
            session.value = data.session;
            if (data.system_message) {
                messages.value.push(data.system_message);
            }
            scrollToBottom();
        }
    } catch (err) {
        console.error('Lỗi chuyển chế độ chat:', err);
    }
}

// Bấm nhanh câu hỏi mẫu
function sendQuickPrompt(promptText: string) {
    inputText.value = promptText;
    handleSend();
}

// Copy tin nhắn
async function copyToClipboard(text: string, id: number | string) {
    try {
        await navigator.clipboard.writeText(text);
        copiedId.value = id;
        setTimeout(() => {
            if (copiedId.value === id) {
                copiedId.value = null;
            }
        }, 2000);
    } catch {
        // bỏ qua
    }
}

// Thêm nhanh sản phẩm vào giỏ hàng ngay từ card của AI
async function handleQuickAddToCart(product: any) {
    addedToCartMap.value[product.id] = true;
    await addToCart(product, 1);
    setTimeout(() => {
        addedToCartMap.value[product.id] = false;
    }, 2500);
}

// Tự động giãn chiều cao textarea khi gõ nhiều dòng
function handleTextareaInput(e: Event) {
    const el = e.target as HTMLTextAreaElement;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
    }
}

// Polling tin nhắn từ nhân viên khi mở widget
async function fetchLatestMessages() {
    if (!isOpen.value || !session.value) {
        return;
    }
    try {
        const res = await fetch(`/api/chat/messages/${session.value.id}`);
        if (res.ok) {
            const data = await res.json();
            if (data.messages && data.messages.length > messages.value.length) {
                messages.value = data.messages;
                scrollToBottom();
            }
            if (data.session) {
                session.value = data.session;
            }
        }
    } catch {
        // bỏ qua
    }
}

// Trợ lý định dạng Markdown nhẹ nhàng, an toàn
function formatMessageContent(raw: string): string {
    if (!raw) return '';
    let text = raw
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    // In đậm **text**
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-gray-950">$1</strong>');

    // In nghiêng *text*
    text = text.replace(/(?:^|[^*])\*([^*]+)\*(?:[^*]|$)/g, ' <em class="italic text-gray-700">$1</em> ');

    // Code inline `code`
    text = text.replace(/`([^`]+)`/g, '<code class="rounded-md bg-emerald-50 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-emerald-800 border border-emerald-200/60">$1</code>');

    // Gạch đầu dòng
    text = text.replace(/(?:^|\n)[*-]\s+(.+)/g, '<div class="flex items-start space-x-1.5 my-0.5"><span class="text-emerald-500 font-bold leading-normal">•</span><span>$1</span></div>');

    // Danh sách đánh số 1. 2. 3.
    text = text.replace(/(?:^|\n)(\d+)\.\s+(.+)/g, '<div class="flex items-start space-x-1.5 my-0.5"><span class="font-bold text-emerald-700 shrink-0">$1.</span><span>$2</span></div>');

    // Xuống dòng
    text = text.replace(/\n/g, '<br>');

    return text;
}

watch(isOpen, (newVal) => {
    if (newVal) {
        if (!session.value) {
            initSession();
        }
        scrollToBottom();
        if (!pollTimer) {
            pollTimer = setInterval(fetchLatestMessages, 5000);
        }
    } else {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }
});

onMounted(() => {
    const saved = localStorage.getItem('freshfood_chat_session_id');
    if (saved) {
        initSession();
    }
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});
</script>

<template>
    <div class="fixed bottom-20 right-4 sm:bottom-6 sm:right-6 z-50 select-none">
        <!-- NÚT BẤM MỞ CHAT WIDGET (BONG BÓNG NỔI) -->
        <button
            v-if="!isOpen"
            type="button"
            @click="isOpen = true"
            aria-label="Mở khung chat hỗ trợ"
            class="group relative flex items-center space-x-2.5 rounded-full bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 p-3.5 sm:px-5 sm:py-3.5 text-white shadow-xl shadow-emerald-700/30 ring-4 ring-emerald-500/20 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-emerald-700/40 active:scale-95"
        >
            <!-- Hiệu ứng Ping phát sáng -->
            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-4 w-4 rounded-full bg-emerald-400 border-2 border-white"></span>
            </span>

            <div class="relative flex items-center justify-center">
                <Bot class="h-6 w-6 transition-transform group-hover:rotate-12" />
            </div>

            <div class="hidden sm:flex flex-col text-left leading-tight">
                <div class="flex items-center space-x-1">
                    <span class="text-xs font-bold tracking-wide">Trợ Lý AI & CSKH</span>
                    <span class="rounded-full bg-emerald-400/30 px-1.5 py-0.2 text-[9px] font-extrabold uppercase tracking-wider text-emerald-100">2.0</span>
                </div>
                <span class="text-[10px] text-emerald-100 font-medium">Tư vấn món ngon & Khiếu nại 24/7</span>
            </div>
        </button>

        <!-- CỬA SỔ CHAT HOÀN CHỈNH KHI MỞ -->
        <div
            v-else
            class="flex flex-col w-[94vw] sm:w-[440px] h-[600px] max-h-[85vh] rounded-3xl bg-white shadow-2xl border border-gray-100 ring-1 ring-black/5 overflow-hidden duration-200 animate-in fade-in zoom-in-95"
        >
            <!-- 1. HEADER KHUNG CHAT -->
            <div class="bg-gradient-to-r from-emerald-600 via-emerald-700 to-teal-700 px-4 py-3.5 text-white shadow-md">
                <div class="flex items-center justify-between mb-2.5">
                    <div class="flex items-center space-x-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-xs ring-1 ring-white/20">
                            <Bot v-if="activeTab === 'ai'" class="h-5 w-5 text-emerald-200" />
                            <Headphones v-else class="h-5 w-5 text-emerald-200" />
                        </div>
                        <div>
                            <div class="flex items-center space-x-1.5">
                                <h3 class="text-sm font-bold tracking-wide">
                                    {{ activeTab === 'ai' ? 'Trợ Lý AI Fresh Food' : 'Chuyên Viên CSKH & Khiếu Nại' }}
                                </h3>
                                <span class="h-2 w-2 rounded-full bg-emerald-300 animate-pulse"></span>
                            </div>
                            <p class="text-[11px] text-emerald-100/90 font-medium">
                                {{ activeTab === 'ai' ? 'Tư vấn món ngon, dinh dưỡng & tra cứu đơn' : 'Hỗ trợ trực tiếp & giải quyết đổi trả 24h' }}
                            </p>
                        </div>
                    </div>

                    <!-- Nút thao tác Header: Làm mới chat + Thu nhỏ -->
                    <div class="flex items-center space-x-1">
                        <button
                            type="button"
                            @click="initSession(true)"
                            title="Làm mới cuộc trò chuyện"
                            class="rounded-full p-1.5 text-emerald-100 hover:bg-white/20 hover:text-white transition-colors"
                        >
                            <RotateCcw class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            @click="isOpen = false"
                            title="Thu nhỏ khung chat"
                            class="rounded-full p-1.5 text-emerald-100 hover:bg-white/20 hover:text-white transition-colors"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- 2. TAB SELECTOR (AI vs NHÂN VIÊN) -->
                <div class="grid grid-cols-2 p-1 bg-emerald-800/40 backdrop-blur-xs rounded-xl text-xs font-semibold">
                    <button
                        type="button"
                        @click="switchMode('ai')"
                        :class="[
                            'flex items-center justify-center space-x-1.5 py-1.5 rounded-lg transition-all',
                            activeTab === 'ai' ? 'bg-white text-emerald-900 shadow-sm font-bold' : 'text-emerald-100 hover:text-white',
                        ]"
                    >
                        <Sparkles class="h-3.5 w-3.5" />
                        <span>Chat Với AI (24/7)</span>
                    </button>

                    <button
                        type="button"
                        @click="switchMode('staff')"
                        :class="[
                            'flex items-center justify-center space-x-1.5 py-1.5 rounded-lg transition-all',
                            activeTab === 'staff' ? 'bg-white text-emerald-900 shadow-sm font-bold' : 'text-emerald-100 hover:text-white',
                        ]"
                    >
                        <Headphones class="h-3.5 w-3.5" />
                        <span>Gặp Nhân Viên</span>
                    </button>
                </div>
            </div>

            <!-- NẾU ĐANG CHỌN TAB GẶP NHÂN VIÊN: CẤU HÌNH NHANH CHỦ ĐỀ / MÃ ĐƠN HÀNG -->
            <div v-if="activeTab === 'staff'" class="bg-amber-50/90 border-b border-amber-200/80 px-3.5 py-2.5 text-xs">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-amber-900 flex items-center space-x-1">
                        <AlertCircle class="h-3.5 w-3.5 text-amber-600" />
                        <span>Chủ đề cần hỗ trợ:</span>
                    </span>
                    <span
                        v-if="session?.trang_thai === 'cho_xu_ly'"
                        class="rounded-full bg-amber-200/70 px-2 py-0.5 text-[10px] font-bold text-amber-800"
                    >
                        Đang chờ chuyên viên
                    </span>
                    <span
                        v-else-if="session?.trang_thai === 'dang_xu_ly'"
                        class="rounded-full bg-emerald-200/70 px-2 py-0.5 text-[10px] font-bold text-emerald-800"
                    >
                        Đang được hỗ trợ
                    </span>
                    <span
                        v-else-if="session?.trang_thai === 'da_dong'"
                        class="rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-bold text-gray-700"
                    >
                        Đã đóng phiên
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-1.5 mb-2">
                    <button
                        type="button"
                        @click="staffForm.chu_de = 'khieu_nai'"
                        :class="[
                            'rounded-xl py-1.5 px-2 text-center font-bold text-[11px] transition-colors border',
                            staffForm.chu_de === 'khieu_nai'
                                ? 'bg-red-600 text-white border-red-600 shadow-xs'
                                : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50',
                        ]"
                    >
                        🚨 Khiếu nại
                    </button>
                    <button
                        type="button"
                        @click="staffForm.chu_de = 'doi_tra'"
                        :class="[
                            'rounded-xl py-1.5 px-2 text-center font-bold text-[11px] transition-colors border',
                            staffForm.chu_de === 'doi_tra'
                                ? 'bg-amber-600 text-white border-amber-600 shadow-xs'
                                : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50',
                        ]"
                    >
                        🔄 Đổi trả hàng
                    </button>
                    <button
                        type="button"
                        @click="staffForm.chu_de = 'tu_van'"
                        :class="[
                            'rounded-xl py-1.5 px-2 text-center font-bold text-[11px] transition-colors border',
                            staffForm.chu_de === 'tu_van'
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50',
                        ]"
                    >
                        💬 Tư vấn đặt
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="flex items-center space-x-1.5 rounded-lg border border-gray-200 bg-white px-2 py-1">
                        <Package class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                        <input
                            v-model="staffForm.ma_don_hang"
                            type="text"
                            placeholder="Mã đơn (VD: FF...)"
                            class="w-full text-[11px] text-gray-800 placeholder:text-gray-400 focus:outline-hidden"
                        />
                    </div>
                    <div class="flex items-center space-x-1.5 rounded-lg border border-gray-200 bg-white px-2 py-1">
                        <User class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                        <input
                            v-model="staffForm.ten_khach_hang"
                            type="text"
                            placeholder="Tên của bạn"
                            class="w-full text-[11px] text-gray-800 placeholder:text-gray-400 focus:outline-hidden"
                        />
                    </div>
                </div>
            </div>

            <!-- 3. DANH SÁCH TIN NHẮN (MESSAGE STREAM) -->
            <div
                ref="chatScrollContainer"
                class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-neutral-50/60"
            >
                <div v-if="messages.length === 0 && isConnecting" class="flex flex-col items-center justify-center h-full text-gray-400 space-y-2">
                    <RefreshCw class="h-6 w-6 animate-spin text-emerald-600" />
                    <span class="text-xs">Đang kết nối hệ thống tư vấn...</span>
                </div>

                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    :class="[
                        'flex flex-col max-w-[88%]',
                        msg.nguoi_gui_type === 'khach_hang'
                            ? 'ml-auto items-end'
                            : msg.nguoi_gui_type === 'he_thong'
                              ? 'mx-auto items-center max-w-[95%]'
                              : 'mr-auto items-start',
                    ]"
                >
                    <!-- THÔNG BÁO HỆ THỐNG -->
                    <div
                        v-if="msg.nguoi_gui_type === 'he_thong'"
                        class="my-1 rounded-2xl bg-amber-100/80 border border-amber-200/90 px-3.5 py-1.5 text-center text-[11px] font-medium text-amber-900 shadow-2xs"
                    >
                        {{ msg.noi_dung }}
                    </div>

                    <!-- TIN NHẮN CỦA KHÁCH HÀNG / AI / NHÂN VIÊN -->
                    <div v-else class="space-y-1 w-full group">
                        <div class="flex items-center justify-between px-1 text-[10px] text-gray-400">
                            <div class="flex items-center space-x-1.5">
                                <span v-if="msg.nguoi_gui_type === 'khach_hang'" class="font-semibold text-gray-600">Bạn</span>
                                <span v-else-if="msg.nguoi_gui_type === 'ai'" class="font-bold text-emerald-600 flex items-center space-x-1">
                                    <Sparkles class="h-3 w-3" />
                                    <span>Trợ lý AI</span>
                                </span>
                                <span v-else class="font-bold text-teal-700 flex items-center space-x-1">
                                    <Headphones class="h-3 w-3" />
                                    <span>Chuyên viên Fresh Food</span>
                                </span>
                            </div>

                            <!-- Nút copy nội dung tin nhắn -->
                            <button
                                type="button"
                                @click="copyToClipboard(msg.noi_dung, msg.id)"
                                title="Sao chép câu trả lời"
                                class="opacity-0 group-hover:opacity-100 hover:text-gray-700 transition-opacity"
                            >
                                <Check v-if="copiedId === msg.id" class="h-3 w-3 text-emerald-600" />
                                <Copy v-else class="h-3 w-3" />
                            </button>
                        </div>

                        <!-- BONG BÓNG TIN NHẮN VỚI ĐỊNH DẠNG MARKDOWN -->
                        <div
                            :class="[
                                'rounded-2xl px-4 py-2.5 text-xs leading-relaxed break-words shadow-2xs',
                                msg.nguoi_gui_type === 'khach_hang'
                                    ? 'bg-emerald-600 text-white rounded-tr-none'
                                    : msg.nguoi_gui_type === 'ai'
                                      ? 'bg-white border border-gray-200 text-gray-800 rounded-tl-none'
                                      : 'bg-teal-50 border border-teal-200 text-teal-950 rounded-tl-none font-medium',
                            ]"
                            v-html="formatMessageContent(msg.noi_dung)"
                        ></div>

                        <!-- NẾU CÓ DỮ LIỆU ĐÍNH KÈM (CARDS) TỪ AI -->
                        <div v-if="msg.du_lieu_dinh_kem" class="mt-2 w-full space-y-2">
                            <!-- THẺ SẢN PHẨM GỢI Ý KÈM NÚT "THÊM VÀO GIỎ" -->
                            <div
                                v-if="msg.du_lieu_dinh_kem.type === 'products' && msg.du_lieu_dinh_kem.items?.length"
                                class="rounded-2xl border border-emerald-200 bg-white p-3 shadow-xs"
                            >
                                <p class="text-[11px] font-bold text-emerald-800 mb-2 flex items-center space-x-1">
                                    <Sparkles class="h-3 w-3 text-emerald-600" />
                                    <span>{{ msg.du_lieu_dinh_kem.title || 'Sản phẩm tươi sạch gợi ý' }}</span>
                                </p>
                                <div class="grid grid-cols-1 gap-2">
                                    <div
                                        v-for="p in msg.du_lieu_dinh_kem.items"
                                        :key="p.id"
                                        class="flex items-center justify-between space-x-2 rounded-xl p-2 bg-emerald-50/40 border border-emerald-100 hover:bg-emerald-50 transition-colors"
                                    >
                                        <Link
                                            :href="route('products.show', { slug: p.duong_dan_seo })"
                                            class="flex items-center space-x-2 min-w-0 flex-1"
                                        >
                                            <img
                                                :src="p.hinh_anh_chinh || '/placeholder.png'"
                                                :alt="p.ten_san_pham"
                                                class="h-10 w-10 shrink-0 rounded-lg object-cover border border-gray-200"
                                            />
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-xs font-bold text-gray-900 hover:text-emerald-700">{{ p.ten_san_pham }}</p>
                                                <p class="text-[11px] font-bold text-emerald-700">
                                                    {{ (p.gia_giam || p.gia_ban).toLocaleString('vi-VN') }}đ
                                                    <span class="text-[10px] font-normal text-gray-500">/ {{ p.don_vi_tinh }}</span>
                                                </p>
                                            </div>
                                        </Link>

                                        <!-- Nút Thêm vào giỏ hàng ngay -->
                                        <button
                                            type="button"
                                            @click="handleQuickAddToCart(p)"
                                            :title="'Thêm ' + p.ten_san_pham + ' vào giỏ hàng'"
                                            class="flex h-8 items-center space-x-1 rounded-lg px-2.5 text-[11px] font-bold transition-all shrink-0 shadow-2xs"
                                            :class="[
                                                addedToCartMap[p.id]
                                                    ? 'bg-emerald-700 text-white'
                                                    : 'bg-emerald-600 hover:bg-emerald-700 text-white active:scale-95'
                                            ]"
                                        >
                                            <Check v-if="addedToCartMap[p.id]" class="h-3.5 w-3.5" />
                                            <ShoppingCart v-else class="h-3.5 w-3.5" />
                                            <span>{{ addedToCartMap[p.id] ? 'Đã thêm' : 'Mua ngay' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- DANH SÁCH GIA VỊ / NGUYÊN LIỆU CẦN TỰ CHUẨN BỊ THÊM Ở NHÀ -->
                            <div
                                v-if="msg.du_lieu_dinh_kem.missing_ingredients && msg.du_lieu_dinh_kem.missing_ingredients.length"
                                class="rounded-2xl border border-amber-200 bg-amber-50/90 p-3 shadow-2xs text-xs text-amber-950"
                            >
                                <p class="text-[11px] font-bold text-amber-900 mb-1.5 flex items-center space-x-1.5">
                                    <AlertCircle class="h-3.5 w-3.5 text-amber-600 shrink-0" />
                                    <span>Gia vị & nguyên liệu bạn cần tự chuẩn bị thêm:</span>
                                </p>
                                <ul class="space-y-1 pl-1">
                                    <li
                                        v-for="(item, idx) in msg.du_lieu_dinh_kem.missing_ingredients"
                                        :key="idx"
                                        class="flex items-start space-x-1.5 text-[11px] text-amber-900 leading-tight"
                                    >
                                        <span class="text-amber-500 font-bold shrink-0">•</span>
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- THẺ THÔNG TIN ĐƠN HÀNG TRA CỨU -->
                            <div
                                v-else-if="msg.du_lieu_dinh_kem.type === 'order'"
                                class="rounded-2xl border border-blue-200 bg-blue-50/90 p-3 shadow-xs text-xs text-blue-900"
                            >
                                <div class="flex items-center justify-between font-bold mb-1">
                                    <span>Mã đơn: #{{ msg.du_lieu_dinh_kem.order_code }}</span>
                                    <span class="rounded-md bg-blue-200/80 px-2 py-0.5 text-[10px] text-blue-950 font-bold">
                                        {{ msg.du_lieu_dinh_kem.status_label }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-blue-800">
                                    Tổng tiền: <strong>{{ msg.du_lieu_dinh_kem.total_formatted }}</strong> • {{ msg.du_lieu_dinh_kem.payment_status }}
                                </p>
                                <Link
                                    :href="route('orders.tracking', { code: msg.du_lieu_dinh_kem.order_code })"
                                    class="mt-2 inline-flex items-center space-x-1 font-bold text-blue-700 hover:underline text-[11px]"
                                >
                                    <span>Chi tiết hành trình giao hàng</span>
                                    <ExternalLink class="h-3 w-3" />
                                </Link>
                            </div>

                            <!-- NÚT HÀNH ĐỘNG CHUYỂN SANG GẶP NHÂN VIÊN -->
                            <div
                                v-else-if="msg.du_lieu_dinh_kem.type === 'action' && msg.du_lieu_dinh_kem.action === 'switch_to_staff'"
                                class="pt-1"
                            >
                                <button
                                    type="button"
                                    @click="switchMode('staff')"
                                    class="flex w-full items-center justify-center space-x-2 rounded-xl bg-red-600 px-3.5 py-2.5 text-xs font-bold text-white shadow-md hover:bg-red-700 transition-colors active:scale-98"
                                >
                                    <Headphones class="h-4 w-4" />
                                    <span>{{ msg.du_lieu_dinh_kem.button_text || 'Chuyển sang Chat với Nhân viên' }}</span>
                                </button>
                            </div>

                            <!-- GỢI Ý CÂU HỎI NHANH TRONG TIN NHẮN -->
                            <div
                                v-else-if="msg.du_lieu_dinh_kem.type === 'suggestions' && msg.du_lieu_dinh_kem.items?.length"
                                class="flex flex-wrap gap-1.5 pt-1"
                            >
                                <button
                                    v-for="(item, sIdx) in msg.du_lieu_dinh_kem.items"
                                    :key="sIdx"
                                    type="button"
                                    @click="sendQuickPrompt(item)"
                                    class="rounded-full border border-emerald-300 bg-white px-3 py-1 text-[11px] font-semibold text-emerald-800 shadow-2xs hover:bg-emerald-50 transition-colors"
                                >
                                    {{ item }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ĐANG CHỜ PHẢN HỒI (TYPING INDICATOR) -->
                <div v-if="isSending" class="flex items-center space-x-1.5 text-gray-400 text-xs px-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-bounce"></span>
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-bounce [animation-delay:0.2s]"></span>
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-bounce [animation-delay:0.4s]"></span>
                    <span class="text-[11px] text-gray-500 ml-1">Đang suy nghĩ câu trả lời...</span>
                </div>
            </div>

            <!-- 4. THANH PHÍM TẮT GỢI Ý NHANH (QUICK PROMPT CHIPS) -->
            <div
                v-if="activeTab === 'ai'"
                class="flex items-center space-x-1.5 overflow-x-auto px-3.5 py-2 bg-gray-50/90 border-t border-gray-100 text-[11px] no-scrollbar shrink-0"
            >
                <button
                    type="button"
                    @click="sendQuickPrompt('Hôm nay ăn gì, gợi ý thực đơn gia đình')"
                    class="shrink-0 flex items-center space-x-1 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-gray-700 hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50/50 transition-colors shadow-2xs"
                >
                    <span>🍲</span>
                    <span>Hôm nay ăn gì?</span>
                </button>
                <button
                    type="button"
                    @click="sendQuickPrompt('Gợi ý các loại thịt tươi và hải sản ngon hôm nay')"
                    class="shrink-0 flex items-center space-x-1 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-gray-700 hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50/50 transition-colors shadow-2xs"
                >
                    <span>🥩</span>
                    <span>Thịt & Hải sản tươi</span>
                </button>
                <button
                    type="button"
                    @click="sendQuickPrompt('Cách bảo quản rau củ và thịt tươi trong tủ lạnh')"
                    class="shrink-0 flex items-center space-x-1 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-gray-700 hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50/50 transition-colors shadow-2xs"
                >
                    <span>❄️</span>
                    <span>Mẹo bảo quản</span>
                </button>
                <button
                    type="button"
                    @click="sendQuickPrompt('Chính sách giao hàng siêu tốc và đổi trả 24h')"
                    class="shrink-0 flex items-center space-x-1 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-gray-700 hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50/50 transition-colors shadow-2xs"
                >
                    <span>🚚</span>
                    <span>Giao 2h & Đổi trả</span>
                </button>
            </div>

            <!-- 5. KHUNG NHẬP LIỆU THÔNG MINH (SMART INPUT BAR) -->
            <form
                @submit.prevent="handleSend"
                class="border-t border-gray-200 bg-white p-3 shrink-0"
            >
                <div class="relative flex items-end rounded-2xl border border-gray-300 bg-gray-50/70 focus-within:border-emerald-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-500/20 transition-all p-1.5">
                    <textarea
                        ref="textareaRef"
                        v-model="inputText"
                        rows="1"
                        @input="handleTextareaInput"
                        @keydown="handleKeydown"
                        :placeholder="
                            activeTab === 'ai'
                                ? 'Hỏi AI: Món ăn hôm nay, mẹo bảo quản, mã đơn FF...'
                                : 'Nhập nội dung cần hỗ trợ hoặc khiếu nại...'
                        "
                        :disabled="isSending"
                        class="w-full resize-none bg-transparent px-2.5 py-1 text-xs text-gray-800 placeholder:text-gray-400 focus:outline-hidden max-h-28"
                    ></textarea>

                    <!-- Nút xóa nhanh text khi có chữ -->
                    <button
                        v-if="inputText.trim()"
                        type="button"
                        @click="inputText = ''; if (textareaRef) textareaRef.style.height = 'auto';"
                        class="p-1 text-gray-400 hover:text-gray-600 transition-colors shrink-0 mb-0.5"
                        title="Xóa văn bản"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <!-- Nút Gửi -->
                    <button
                        type="submit"
                        :disabled="!inputText.trim() || isSending"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all active:scale-95 ml-1 mb-0.5"
                        title="Gửi (Enter)"
                    >
                        <Send class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex items-center justify-between mt-1.5 px-1 text-[10px] text-gray-400">
                    <span>Nhấn <strong>Enter</strong> để gửi, <strong>Shift + Enter</strong> xuống dòng</span>
                    <span v-if="activeTab === 'ai'" class="text-emerald-600 font-semibold flex items-center space-x-1">
                        <Sparkles class="h-2.5 w-2.5" />
                        <span>AI Hybrid 2.0</span>
                    </span>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
