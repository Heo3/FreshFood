<script setup lang="ts">
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { CheckCircle2, RotateCcw, Search, X, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
        return (
            !q ||
            t.ma_phieu.toLowerCase().includes(q) ||
            t.don_hang?.ma_don_hang?.toLowerCase().includes(q) ||
            t.user?.name?.toLowerCase().includes(q)
        );
    });
});

function handleApprove(ticket: ReturnRequestItem) {
    if (confirm(`Bạn có chắc chắn muốn duyệt yêu cầu đổi trả #${ticket.ma_phieu}?`)) {
        router.post(
            route('store.returns.approve', ticket.id),
            {},
            {
                preserveScroll: true,
            },
        );
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

    router.post(
        route('store.returns.reject', selectedRejectTicket.value.id),
        {
            ly_do_tu_choi: rejectReason.value.trim(),
        },
        {
            onSuccess: () => {
                selectedRejectTicket.value = null;
            },
        },
    );
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
            <div
                class="flex flex-col items-start justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:flex-row sm:items-center"
            >
                <div>
                    <h2 class="flex items-center space-x-2 text-base font-black text-slate-900">
                        <RotateCcw class="h-5 w-5 text-emerald-600" />
                        <span>Danh Sách Yêu Cầu Đổi Trả Thực Phẩm Dập Nát (Chính Sách 24h)</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-400">Xử lý hoàn tiền hoặc xuất bù thực phẩm tươi mới cho khách hàng.</p>
                </div>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 p-4">
                    <div class="relative w-full sm:w-80">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm kiếm mã phiếu, đơn hàng, khách hàng..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                    <span class="text-xs font-medium text-slate-400">Tổng: {{ filteredTickets.length }} yêu cầu</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Mã Phiếu</th>
                                <th class="px-4 py-3">Đơn Hàng Gốc</th>
                                <th class="px-4 py-3">Khách Hàng</th>
                                <th class="px-4 py-3">Lý Do Đổi Trả</th>
                                <th class="px-4 py-3 text-right">Số Tiền Hoàn</th>
                                <th class="px-4 py-3 text-center">Trạng Thái</th>
                                <th class="px-4 py-3 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredTickets.length === 0">
                                <td colspan="7" class="py-10 text-center text-slate-400">Không có yêu cầu đổi trả nào.</td>
                            </tr>
                            <tr v-for="ticket in filteredTickets" :key="ticket.id" class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5 font-mono font-bold text-slate-900">
                                    {{ ticket.ma_phieu }}
                                </td>
                                <td class="px-4 py-3.5 font-mono font-bold text-slate-700">
                                    {{ ticket.don_hang?.ma_don_hang || '—' }}
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-800">
                                    {{ ticket.user?.name || 'Khách vãng lai' }}
                                </td>
                                <td class="max-w-xs truncate px-4 py-3.5 text-slate-600">
                                    {{ ticket.ly_do }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-black text-emerald-700">
                                    {{ formatPrice(ticket.tong_tien_hoan) }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        :class="getStatusBadge(ticket.trang_thai).class"
                                        class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider"
                                    >
                                        {{ getStatusBadge(ticket.trang_thai).text }}
                                    </span>
                                </td>
                                <td class="space-x-1.5 whitespace-nowrap px-4 py-3.5 text-right">
                                    <template v-if="ticket.trang_thai === 0">
                                        <div v-if="isAuthorizedManager" class="inline-flex space-x-1.5">
                                            <button
                                                @click="handleApprove(ticket)"
                                                class="inline-flex items-center space-x-1 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700"
                                            >
                                                <CheckCircle2 class="h-3.5 w-3.5" />
                                                <span>Duyệt</span>
                                            </button>
                                            <button
                                                @click="openRejectModal(ticket)"
                                                class="inline-flex items-center space-x-1 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-red-50 hover:text-red-700"
                                            >
                                                <XCircle class="h-3.5 w-3.5" />
                                                <span>Từ Chối</span>
                                            </button>
                                        </div>
                                        <span v-else class="text-[11px] font-medium italic text-amber-600"> Chờ Quản lý phê duyệt </span>
                                    </template>
                                    <span v-else class="text-[11px] italic text-slate-400">
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
        <div v-if="selectedRejectTicket" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md space-y-4 rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-black text-slate-900">Từ Chối Đổi Trả #{{ selectedRejectTicket.ma_phieu }}</h3>
                    <button @click="selectedRejectTicket = null" class="text-slate-400 hover:text-slate-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Lý do từ chối:</label>
                    <textarea
                        v-model="rejectReason"
                        rows="3"
                        placeholder="Ví dụ: Sản phẩm đã quá thời hạn 24h hoặc không có bằng chứng dập nát..."
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500"
                    ></textarea>
                </div>

                <div class="flex space-x-2 pt-2">
                    <button
                        @click="selectedRejectTicket = null"
                        class="flex-1 rounded-xl border border-slate-200 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
                    >
                        Hủy
                    </button>
                    <button
                        @click="handleRejectSubmit"
                        class="flex-1 rounded-xl bg-red-600 py-2 text-xs font-bold text-white transition hover:bg-red-700"
                    >
                        Xác Nhận Từ Chối
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>
