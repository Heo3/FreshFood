<script setup lang="ts">
import { ref, computed } from 'vue';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    RotateCcw,
    Search,
    CheckCircle2,
    XCircle,
    User,
    Calendar,
    X,
} from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';

interface ReturnRequestItem {
    id: number;
    ma_phieu: string;
    don_hang?: { id: number; ma_don_hang: string };
    user?: { id: number; name: string; phone?: string };
    nguoi_duyet?: { id: number; name: string };
    loai_doi_tra: number;
    tong_tien_hoan: number;
    trang_thai: number;
    ly_do: string;
    ghi_chu?: string;
    created_at: string;
    chi_tiets: Array<any>;
}

const props = defineProps<{
    stores: Array<any>;
    currentStore: any;
    phieuDoiTras: Array<ReturnRequestItem>;
}>();

const searchQuery = ref('');
const selectedRejectTicket = ref<ReturnRequestItem | null>(null);
const rejectReason = ref('');

const page = usePage();
const isAuthorizedManager = computed(() => {
    const role = (page.props.auth?.user as any)?.role;
    return role === 'admin' || role === 'quan_ly';
});

const filteredTickets = computed(() => {
    return props.phieuDoiTras.filter((t) => {
        const q = searchQuery.value.toLowerCase().trim();
        return !q || t.ma_phieu.toLowerCase().includes(q) || t.don_hang?.ma_don_hang?.toLowerCase().includes(q) || t.user?.name?.toLowerCase().includes(q);
    });
});

function handleApprove(ticket: ReturnRequestItem) {
    if (confirm(`Bạn có chắc chắn muốn duyệt yêu cầu đổi trả #${ticket.ma_phieu}?`)) {
        router.post(route('store.returns.approve', ticket.id), {}, {
            preserveScroll: true,
        });
    }
}

function openRejectModal(ticket: ReturnRequestItem) {
    selectedRejectTicket.value = ticket;
    rejectReason.value = '';
}

function handleRejectSubmit() {
    if (!selectedRejectTicket.value || !rejectReason.value.trim()) {
        alert('Vui lòng nhập lý do từ chối!');
        return;
    }

    router.post(route('store.returns.reject', selectedRejectTicket.value.id), {
        ly_do_tu_choi: rejectReason.value.trim(),
    }, {
        onSuccess: () => {
            selectedRejectTicket.value = null;
        },
    });
}

function getStatusBadge(status: number): { text: string; class: string } {
    switch (status) {
        case 0:
            return { text: 'Chờ xử lý', class: 'bg-amber-100 text-amber-800' };
        case 1:
            return { text: 'Đã chấp thuận', class: 'bg-emerald-100 text-emerald-800' };
        case 2:
            return { text: 'Đã từ chối', class: 'bg-red-100 text-red-800' };
        default:
            return { text: 'Khác', class: 'bg-slate-100 text-slate-800' };
    }
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleString('vi-VN');
}
</script>

<template>
    <StoreOpsLayout title="Xử Lý Yêu Cầu Đổi Trả Thực Phẩm 24h" :stores="stores" :currentStore="currentStore">
        <div class="space-y-6">
            <!-- TOP BAR -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-base font-black text-slate-900 flex items-center space-x-2">
                        <RotateCcw class="w-5 h-5 text-emerald-600" />
                        <span>Danh Sách Yêu Cầu Đổi Trả Thực Phẩm Dập Nát (Chính Sách 24h)</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Xử lý hoàn tiền hoặc xuất bù thực phẩm tươi mới cho khách hàng.</p>
                </div>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm kiếm mã phiếu, đơn hàng, khách hàng..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                        />
                    </div>
                    <span class="text-xs text-slate-400 font-medium">Tổng: {{ filteredTickets.length }} yêu cầu</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Mã Phiếu</th>
                                <th class="py-3 px-4">Đơn Hàng Gốc</th>
                                <th class="py-3 px-4">Khách Hàng</th>
                                <th class="py-3 px-4">Lý Do Đổi Trả</th>
                                <th class="py-3 px-4 text-right">Số Tiền Hoàn</th>
                                <th class="py-3 px-4 text-center">Trạng Thái</th>
                                <th class="py-3 px-4 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredTickets.length === 0">
                                <td colspan="7" class="text-center py-10 text-slate-400">
                                    Không có yêu cầu đổi trả nào.
                                </td>
                            </tr>
                            <tr v-for="ticket in filteredTickets" :key="ticket.id" class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    {{ ticket.ma_phieu }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                                    {{ ticket.don_hang?.ma_don_hang || '—' }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800">
                                    {{ ticket.user?.name || 'Khách vãng lai' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 max-w-xs truncate">
                                    {{ ticket.ly_do }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-emerald-700">
                                    {{ formatPrice(ticket.tong_tien_hoan) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span :class="getStatusBadge(ticket.trang_thai).class" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
                                        {{ getStatusBadge(ticket.trang_thai).text }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                    <template v-if="ticket.trang_thai === 0">
                                        <div v-if="isAuthorizedManager" class="inline-flex space-x-1.5">
                                            <button
                                                @click="handleApprove(ticket)"
                                                class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs inline-flex items-center space-x-1 shadow-sm transition"
                                            >
                                                <CheckCircle2 class="w-3.5 h-3.5" />
                                                <span>Duyệt</span>
                                            </button>
                                            <button
                                                @click="openRejectModal(ticket)"
                                                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-red-50 hover:text-red-700 text-slate-600 font-bold text-xs inline-flex items-center space-x-1 transition"
                                            >
                                                <XCircle class="w-3.5 h-3.5" />
                                                <span>Từ Chối</span>
                                            </button>
                                        </div>
                                        <span v-else class="text-[11px] text-amber-600 font-medium italic">
                                            Chờ Quản lý phê duyệt
                                        </span>
                                    </template>
                                    <span v-else class="text-[11px] text-slate-400 italic">
                                        Đã xử lý bởi {{ ticket.nguoi_duyet?.name || 'Quản lý' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- REJECT MODAL -->
        <div v-if="selectedRejectTicket" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-black text-slate-900 text-base">Từ Chối Đổi Trả #{{ selectedRejectTicket.ma_phieu }}</h3>
                    <button @click="selectedRejectTicket = null" class="text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Lý do từ chối:</label>
                    <textarea
                        v-model="rejectReason"
                        rows="3"
                        placeholder="Ví dụ: Sản phẩm đã quá thời hạn 24h hoặc không có bằng chứng dập nát..."
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-emerald-500 outline-none"
                    ></textarea>
                </div>

                <div class="pt-2 flex space-x-2">
                    <button
                        @click="selectedRejectTicket = null"
                        class="flex-1 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50"
                    >
                        Hủy
                    </button>
                    <button
                        @click="handleRejectSubmit"
                        class="flex-1 py-2 rounded-xl bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition"
                    >
                        Xác Nhận Từ Chối
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>

