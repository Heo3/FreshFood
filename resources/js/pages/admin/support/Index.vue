<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    Check,
    CheckCircle2,
    Clock,
    ExternalLink,
    Filter,
    Headphones,
    MessageSquare,
    Package,
    Phone,
    RefreshCw,
    Search,
    Send,
    ShieldAlert,
    User,
    X,
} from 'lucide-vue-next';
import { nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    sessions: {
        data: Array<any>;
        links: Array<any>;
        current_page: number;
        last_page: number;
        total: number;
    };
    currentSession?: any;
    messages: Array<any>;
    relatedOrder?: any;
    counts: {
        cho_xu_ly: number;
        dang_xu_ly: number;
        da_dong: number;
        khieu_nai: number;
    };
    filters: {
        status?: string;
        topic?: string;
        search?: string;
        session_id?: number | string;
    };
}>();

const search = ref(props.filters.search || '');
const currentFilterStatus = ref(props.filters.status || '');
const currentFilterTopic = ref(props.filters.topic || '');
const messagesContainer = ref<HTMLDivElement | null>(null);

const replyForm = useForm({
    noi_dung: '',
});

const statusForm = useForm({
    trang_thai: '',
});

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

onMounted(() => {
    scrollToBottom();
});

watch(
    () => props.messages,
    () => {
        scrollToBottom();
    },
    { deep: true },
);

function selectSession(sessionId: number) {
    router.get(
        route('admin.support.index'),
        {
            status: currentFilterStatus.value || undefined,
            topic: currentFilterTopic.value || undefined,
            search: search.value || undefined,
            session_id: sessionId,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function handleFilter() {
    router.get(
        route('admin.support.index'),
        {
            status: currentFilterStatus.value || undefined,
            topic: currentFilterTopic.value || undefined,
            search: search.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function handleReply() {
    if (!props.currentSession || !replyForm.noi_dung.trim()) {
        return;
    }

    replyForm.post(route('admin.support.reply', { session: props.currentSession.id }), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
            scrollToBottom();
        },
    });
}

function updateStatus(newStatus: 'cho_xu_ly' | 'dang_xu_ly' | 'da_dong') {
    if (!props.currentSession) {
        return;
    }

    statusForm.trang_thai = newStatus;
    statusForm.post(route('admin.support.update-status', { session: props.currentSession.id }), {
        preserveScroll: true,
    });
}

// Chèn nhanh câu trả lời mẫu
function insertCannedReply(text: string) {
    replyForm.noi_dung = text;
}

const cannedReplies = [
    'Dạ Fresh Food xin thành thật xin lỗi bạn về sự cố này ạ! Shop xin phép kiểm tra và hỗ trợ giải quyết ngay cho bạn.',
    'Dạ bạn vui lòng gửi giúp shop hình ảnh thực tế của sản phẩm để bên em đối soát và tiến hành đổi trả/hoàn tiền ngay trong hôm nay nhé!',
    'Dạ đơn hàng của bạn hiện đang được đóng gói cẩn thận và tài xế đang giao hỏa tốc đến địa chỉ của bạn ạ.',
    'Dạ yêu cầu khiếu nại của bạn đã được giải quyết thành công. Rất cảm ơn bạn đã tin chọn Fresh Food!',
];
</script>

<template>
    <AdminLayout
        title="CSKH & Xử Lý Khiếu Nại"
        :breadcrumbs="[{ name: 'Dashboard', href: route('admin.dashboard') }, { name: 'CSKH & Khiếu Nại' }]"
    >
        <Head title="CSKH & Xử lý Khiếu Nại - Quản Trị Fresh Food" />

        <div class="h-[calc(100vh-140px)] flex flex-col space-y-3">
            <!-- THANH THỐNG KÊ NHANH -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0">
                <button
                    type="button"
                    @click="currentFilterStatus = 'cho_xu_ly'; handleFilter()"
                    :class="[
                        'flex items-center space-x-3 rounded-2xl p-3 border text-left transition-all',
                        currentFilterStatus === 'cho_xu_ly'
                            ? 'bg-red-50 border-red-300 ring-2 ring-red-400/20'
                            : 'bg-white border-gray-200 hover:border-gray-300',
                    ]"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 shrink-0">
                        <AlertCircle class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Chờ Xử Lý</p>
                        <p class="text-lg font-black text-red-600">{{ counts.cho_xu_ly }}</p>
                    </div>
                </button>

                <button
                    type="button"
                    @click="currentFilterStatus = 'dang_xu_ly'; handleFilter()"
                    :class="[
                        'flex items-center space-x-3 rounded-2xl p-3 border text-left transition-all',
                        currentFilterStatus === 'dang_xu_ly'
                            ? 'bg-amber-50 border-amber-300 ring-2 ring-amber-400/20'
                            : 'bg-white border-gray-200 hover:border-gray-300',
                    ]"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 shrink-0">
                        <Clock class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Đang Hỗ Trợ</p>
                        <p class="text-lg font-black text-amber-600">{{ counts.dang_xu_ly }}</p>
                    </div>
                </button>

                <button
                    type="button"
                    @click="currentFilterTopic = 'khieu_nai'; handleFilter()"
                    :class="[
                        'flex items-center space-x-3 rounded-2xl p-3 border text-left transition-all',
                        currentFilterTopic === 'khieu_nai'
                            ? 'bg-rose-50 border-rose-300 ring-2 ring-rose-400/20'
                            : 'bg-white border-gray-200 hover:border-gray-300',
                    ]"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600 shrink-0">
                        <ShieldAlert class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Khiếu Nại Cần Giải Quyết</p>
                        <p class="text-lg font-black text-rose-600">{{ counts.khieu_nai }}</p>
                    </div>
                </button>

                <button
                    type="button"
                    @click="currentFilterStatus = 'da_dong'; handleFilter()"
                    :class="[
                        'flex items-center space-x-3 rounded-2xl p-3 border text-left transition-all',
                        currentFilterStatus === 'da_dong'
                            ? 'bg-emerald-50 border-emerald-300 ring-2 ring-emerald-400/20'
                            : 'bg-white border-gray-200 hover:border-gray-300',
                    ]"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 shrink-0">
                        <CheckCircle2 class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Đã Giải Quyết Xong</p>
                        <p class="text-lg font-black text-emerald-600">{{ counts.da_dong }}</p>
                    </div>
                </button>
            </div>

            <!-- GIAO DIỆN CHÍNH DUAL-PANE -->
            <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-3 min-h-0 bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- CỘT TRÁI (4 CỘT): DANH SÁCH CUỘC HỘI THOẠI -->
                <div class="lg:col-span-4 border-r border-gray-200 flex flex-col min-h-0 bg-gray-50/50">
                    <!-- TÌM KIẾM & BỘ LỌC -->
                    <div class="p-3 border-b border-gray-200 space-y-2 bg-white">
                        <div class="relative">
                            <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                            <input
                                v-model="search"
                                @keydown.enter="handleFilter"
                                type="text"
                                placeholder="Tìm theo tên, SĐT, mã đơn..."
                                class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-emerald-500 focus:outline-hidden"
                            />
                        </div>

                        <!-- Lọc theo chủ đề -->
                        <div class="flex items-center space-x-1 text-xs">
                            <button
                                type="button"
                                @click="currentFilterTopic = ''; handleFilter()"
                                :class="[
                                    'px-2.5 py-1 rounded-lg font-semibold transition-colors',
                                    !currentFilterTopic ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100',
                                ]"
                            >
                                Tất cả
                            </button>
                            <button
                                type="button"
                                @click="currentFilterTopic = 'khieu_nai'; handleFilter()"
                                :class="[
                                    'px-2 py-1 rounded-lg font-semibold transition-colors',
                                    currentFilterTopic === 'khieu_nai' ? 'bg-red-600 text-white' : 'text-red-700 hover:bg-red-50',
                                ]"
                            >
                                Khiếu nại
                            </button>
                            <button
                                type="button"
                                @click="currentFilterTopic = 'doi_tra'; handleFilter()"
                                :class="[
                                    'px-2 py-1 rounded-lg font-semibold transition-colors',
                                    currentFilterTopic === 'doi_tra' ? 'bg-amber-600 text-white' : 'text-amber-700 hover:bg-amber-50',
                                ]"
                            >
                                Đổi trả
                            </button>
                            <button
                                type="button"
                                @click="currentFilterTopic = 'tu_van'; handleFilter()"
                                :class="[
                                    'px-2 py-1 rounded-lg font-semibold transition-colors',
                                    currentFilterTopic === 'tu_van' ? 'bg-emerald-600 text-white' : 'text-emerald-700 hover:bg-emerald-50',
                                ]"
                            >
                                Tư vấn
                            </button>
                        </div>
                    </div>

                    <!-- DANH SÁCH HỘI THOẠI SCROLLABLE -->
                    <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                        <div v-if="sessions.data.length === 0" class="p-8 text-center text-gray-400">
                            <Headphones class="h-8 w-8 mx-auto mb-2 opacity-50" />
                            <p class="text-xs">Không có phiên hỗ trợ nào phù hợp.</p>
                        </div>

                        <button
                            v-for="s in sessions.data"
                            :key="s.id"
                            type="button"
                            @click="selectSession(s.id)"
                            :class="[
                                'w-full text-left p-3.5 transition-colors flex items-start space-x-3 border-l-4',
                                currentSession?.id === s.id
                                    ? 'bg-emerald-50/80 border-emerald-600'
                                    : 'border-transparent hover:bg-white',
                            ]"
                        >
                            <!-- Avatar khách hàng -->
                            <div class="relative shrink-0">
                                <img
                                    v-if="s.user?.avatar"
                                    :src="s.user.avatar"
                                    :alt="s.ten_khach_hang"
                                    class="h-10 w-10 rounded-full object-cover border border-gray-200"
                                />
                                <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs">
                                    {{ (s.ten_khach_hang || 'KH').substring(0, 2).toUpperCase() }}
                                </div>

                                <!-- Dot trạng thái -->
                                <span
                                    v-if="s.trang_thai === 'cho_xu_ly'"
                                    class="absolute -top-0.5 -right-0.5 h-3 w-3 rounded-full bg-red-500 border-2 border-white ring-1 ring-red-400 animate-pulse"
                                    title="Chờ xử lý"
                                ></span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <h4 class="text-xs font-bold text-gray-900 truncate">
                                        {{ s.ten_khach_hang || s.user?.name || 'Khách vãng lai' }}
                                    </h4>
                                    <span class="text-[10px] text-gray-400 shrink-0">
                                        {{ s.thoi_gian_tin_nhan_cuoi ? new Date(s.thoi_gian_tin_nhan_cuoi).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) : '' }}
                                    </span>
                                </div>

                                <div class="flex items-center space-x-1.5 mb-1">
                                    <!-- Tag chủ đề -->
                                    <span
                                        v-if="s.chu_de === 'khieu_nai'"
                                        class="rounded-md bg-red-100 px-1.5 py-0.5 text-[9px] font-bold text-red-700"
                                    >
                                        Khiếu nại
                                    </span>
                                    <span
                                        v-else-if="s.chu_de === 'doi_tra'"
                                        class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold text-amber-700"
                                    >
                                        Đổi trả
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-md bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700"
                                    >
                                        Tư vấn
                                    </span>

                                    <!-- Tag đơn hàng -->
                                    <span
                                        v-if="s.ma_don_hang"
                                        class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[9px] font-medium text-gray-600 truncate"
                                    >
                                        #{{ s.ma_don_hang }}
                                    </span>
                                </div>

                                <p class="text-[11px] text-gray-500 truncate leading-relaxed">
                                    {{ s.tin_nhan_cuoi || 'Chưa có nội dung...' }}
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- CỘT PHẢI (8 CỘT): CHI TIẾT HỘI THOẠI & PHẢN HỒI -->
                <div v-if="currentSession" class="lg:col-span-8 flex flex-col min-h-0 bg-white">
                    <!-- HEADER KHUNG HỘI THOẠI -->
                    <div class="p-3.5 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3 bg-gray-50/70">
                        <div class="flex items-center space-x-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white font-bold text-sm shrink-0">
                                {{ (currentSession.ten_khach_hang || 'KH').substring(0, 2).toUpperCase() }}
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-sm font-bold text-gray-900">
                                        {{ currentSession.ten_khach_hang || currentSession.user?.name || 'Khách vãng lai' }}
                                    </h3>
                                    <span
                                        v-if="currentSession.trang_thai === 'cho_xu_ly'"
                                        class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700"
                                    >
                                        Chờ tiếp nhận
                                    </span>
                                    <span
                                        v-else-if="currentSession.trang_thai === 'dang_xu_ly'"
                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700"
                                    >
                                        Đang hỗ trợ
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600"
                                    >
                                        Đã giải quyết
                                    </span>
                                </div>
                                <div class="flex items-center space-x-3 text-xs text-gray-500 mt-0.5">
                                    <span v-if="currentSession.so_dien_thoai || currentSession.user?.phone" class="flex items-center space-x-1">
                                        <Phone class="h-3 w-3 text-gray-400" />
                                        <span>{{ currentSession.so_dien_thoai || currentSession.user?.phone }}</span>
                                    </span>
                                    <span v-if="currentSession.nhan_vien" class="text-gray-400">
                                        NV phụ trách: <strong>{{ currentSession.nhan_vien.name }}</strong>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- CÁC NÚT THAO TÁC TRẠNG THÁI -->
                        <div class="flex items-center space-x-2">
                            <button
                                v-if="currentSession.trang_thai === 'cho_xu_ly'"
                                type="button"
                                @click="updateStatus('dang_xu_ly')"
                                class="rounded-xl bg-amber-500 hover:bg-amber-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition-colors flex items-center space-x-1"
                            >
                                <Check class="h-3.5 w-3.5" />
                                <span>Tiếp Nhận Xử Lý</span>
                            </button>

                            <button
                                v-if="currentSession.trang_thai !== 'da_dong'"
                                type="button"
                                @click="updateStatus('da_dong')"
                                class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition-colors flex items-center space-x-1"
                            >
                                <CheckCircle2 class="h-3.5 w-3.5" />
                                <span>Giải Quyết & Đóng Phiên</span>
                            </button>

                            <button
                                v-else
                                type="button"
                                @click="updateStatus('dang_xu_ly')"
                                class="rounded-xl border border-gray-300 hover:bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors"
                            >
                                Mở lại phiên này
                            </button>
                        </div>
                    </div>

                    <!-- THÔNG TIN ĐƠN HÀNG LIÊN QUAN (NẾU CÓ) -->
                    <div
                        v-if="relatedOrder"
                        class="bg-blue-50/80 border-b border-blue-200/90 px-4 py-2.5 flex items-center justify-between text-xs text-blue-900"
                    >
                        <div class="flex items-center space-x-2.5">
                            <Package class="h-4 w-4 text-blue-600 shrink-0" />
                            <div>
                                <span class="font-bold">Đơn hàng khiếu nại #{{ relatedOrder.ma_don_hang }}:</span>
                                <span class="ml-1 text-blue-800">
                                    {{ Number(relatedOrder.tong_thanhtoan).toLocaleString('vi-VN') }}đ •
                                    {{ relatedOrder.trang_thai_don_hang }} •
                                    {{ relatedOrder.chi_tiet_don_hangs?.length || 0 }} sản phẩm
                                </span>
                            </div>
                        </div>
                        <Link
                            :href="route('admin.orders.show', { order: relatedOrder.id })"
                            class="inline-flex items-center space-x-1 font-bold text-blue-700 hover:underline shrink-0"
                            target="_blank"
                        >
                            <span>Xem chi tiết đơn</span>
                            <ExternalLink class="h-3 w-3" />
                        </Link>
                    </div>

                    <!-- DANH SÁCH TIN NHẮN (MESSAGE HISTORY) -->
                    <div
                        ref="messagesContainer"
                        class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-neutral-50/60"
                    >
                        <div
                            v-for="msg in messages"
                            :key="msg.id"
                            :class="[
                                'flex flex-col max-w-[80%]',
                                msg.nguoi_gui_type === 'nhan_vien'
                                    ? 'ml-auto items-end'
                                    : msg.nguoi_gui_type === 'he_thong'
                                      ? 'mx-auto items-center max-w-[90%]'
                                      : 'mr-auto items-start',
                            ]"
                        >
                            <!-- Thông báo hệ thống -->
                            <div
                                v-if="msg.nguoi_gui_type === 'he_thong'"
                                class="my-1 rounded-2xl bg-amber-100 border border-amber-200 px-3 py-1 text-center text-xs font-medium text-amber-900"
                            >
                                {{ msg.noi_dung }}
                            </div>

                            <!-- Tin nhắn người gửi -->
                            <div v-else class="space-y-1 w-full">
                                <div class="flex items-center space-x-1.5 px-1 text-[10px] text-gray-400">
                                    <span v-if="msg.nguoi_gui_type === 'nhan_vien'" class="font-semibold text-emerald-700">
                                        {{ msg.nguoi_gui?.name || 'Nhân viên CSKH' }}
                                    </span>
                                    <span v-else-if="msg.nguoi_gui_type === 'ai'" class="font-semibold text-purple-600">
                                        Trợ lý AI
                                    </span>
                                    <span v-else class="font-semibold text-gray-600">
                                        {{ currentSession.ten_khach_hang || 'Khách hàng' }}
                                    </span>
                                    <span>• {{ new Date(msg.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                </div>

                                <div
                                    :class="[
                                        'rounded-2xl px-4 py-2.5 text-xs leading-relaxed break-words shadow-2xs',
                                        msg.nguoi_gui_type === 'nhan_vien'
                                            ? 'bg-emerald-600 text-white rounded-tr-none'
                                            : msg.nguoi_gui_type === 'ai'
                                              ? 'bg-purple-50 border border-purple-200 text-purple-950 rounded-tl-none'
                                              : 'bg-white border border-gray-200 text-gray-800 rounded-tl-none',
                                    ]"
                                    style="white-space: pre-wrap;"
                                >
                                    {{ msg.noi_dung }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KHU VỰC CÂU TRẢ LỜI MẪU (CANNED REPLIES) -->
                    <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 flex items-center space-x-1.5 overflow-x-auto text-[11px] no-scrollbar">
                        <span class="text-gray-400 shrink-0 font-medium">Trả lời nhanh:</span>
                        <button
                            v-for="(canned, cIdx) in cannedReplies"
                            :key="cIdx"
                            type="button"
                            @click="insertCannedReply(canned)"
                            class="shrink-0 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-gray-700 hover:border-emerald-500 hover:text-emerald-700 transition-colors"
                        >
                            {{ canned.substring(0, 32) }}...
                        </button>
                    </div>

                    <!-- FORM GỬI PHẢN HỒI -->
                    <form @submit.prevent="handleReply" class="p-3 border-t border-gray-200 bg-white">
                        <div class="flex items-end space-x-2">
                            <textarea
                                v-model="replyForm.noi_dung"
                                rows="2"
                                placeholder="Nhập câu trả lời cho khách hàng... (Nhấn Enter để gửi)"
                                @keydown.enter.exact.prevent="handleReply"
                                class="flex-1 rounded-2xl border border-gray-300 bg-gray-50/70 p-3 text-xs text-gray-800 placeholder:text-gray-400 focus:border-emerald-500 focus:bg-white focus:outline-hidden resize-none"
                            ></textarea>

                            <button
                                type="submit"
                                :disabled="!replyForm.noi_dung.trim() || replyForm.processing"
                                class="flex h-12 px-4 shrink-0 items-center justify-center space-x-1.5 rounded-2xl bg-emerald-600 text-white font-bold text-xs shadow-md hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                            >
                                <Send class="h-4 w-4" />
                                <span>Gửi</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- CHƯA CHỌN PHIÊN NÀO -->
                <div v-else class="lg:col-span-8 flex flex-col items-center justify-center p-12 text-center text-gray-400">
                    <MessageSquare class="h-12 w-12 text-gray-300 mb-3" />
                    <h3 class="text-sm font-bold text-gray-700">Chọn một phiên hỗ trợ bên trái</h3>
                    <p class="text-xs text-gray-400 mt-1 max-w-sm">
                        Nhân viên có thể trực tiếp chat, tư vấn và giải quyết khiếu nại đổi trả cho khách hàng tại đây.
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
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
