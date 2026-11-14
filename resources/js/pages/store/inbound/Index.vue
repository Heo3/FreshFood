<script setup lang="ts">
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import { Link } from '@inertiajs/vue3';
import { FileInput, Plus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface PhieuNhapItem {
    id: number;
    ma_phieu: string;
    cua_hang_id: number;
    nha_cung_cap: { id: number; ten_nha_cung_cap: string };
    nguoi_tao: { id: number; name: string };
    tong_tien: number;
    trang_thai: number;
    ngay_nhap: string;
    ghi_chu?: string;
    chi_tiets: Array<any>;
}

const props = defineProps<{
    stores: Array<any>;
    currentStore: any;
    phieuNhaps: Array<PhieuNhapItem>;
}>();

const searchQuery = ref('');

const filteredPhieuNhaps = computed(() => {
    return props.phieuNhaps.filter((p) => {
        const q = searchQuery.value.toLowerCase().trim();
        return !q || p.ma_phieu.toLowerCase().includes(q) || p.nha_cung_cap?.ten_nha_cung_cap?.toLowerCase().includes(q);
    });
});

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleString('vi-VN');
}
</script>

<template>
    <StoreOpsLayout title="Quản Lý Nhập Kho" :stores="stores" :currentStore="currentStore">
        <div class="space-y-6">
            <!-- TOP BAR -->
            <div
                class="flex flex-col items-start justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:flex-row sm:items-center"
            >
                <div>
                    <h2 class="flex items-center space-x-2 text-base font-black text-slate-900">
                        <FileInput class="h-5 w-5 text-emerald-600" />
                        <span>Danh Sách Phiếu Nhập Kho Từ Nhà Cung Cấp</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-400">Theo dõi các đợt nhập hàng, số lô và hạn sử dụng của thực phẩm.</p>
                </div>

                <Link
                    :href="route('store.inbound.create')"
                    class="flex items-center space-x-1.5 rounded-2xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-700/20 transition hover:bg-emerald-700"
                >
                    <Plus class="h-4 w-4" />
                    <span>Lập Phiếu Nhập Mới</span>
                </Link>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 p-4">
                    <div class="relative w-full sm:w-80">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm kiếm mã phiếu, nhà cung cấp..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                    <span class="text-xs font-medium text-slate-400">Tổng: {{ filteredPhieuNhaps.length }} phiếu</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Mã Phiếu</th>
                                <th class="px-4 py-3">Nhà Cung Cấp</th>
                                <th class="px-4 py-3">Thời Gian Nhập</th>
                                <th class="px-4 py-3">Người Nhập</th>
                                <th class="px-4 py-3 text-center">Số Mặt Hàng</th>
                                <th class="px-4 py-3 text-right">Tổng Tiền</th>
                                <th class="px-4 py-3 text-center">Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredPhieuNhaps.length === 0">
                                <td colspan="7" class="py-10 text-center text-slate-400">Chưa có phiếu nhập kho nào.</td>
                            </tr>
                            <tr v-for="pn in filteredPhieuNhaps" :key="pn.id" class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5 font-mono font-bold text-slate-900">
                                    {{ pn.ma_phieu }}
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-700">
                                    {{ pn.nha_cung_cap?.ten_nha_cung_cap }}
                                </td>
                                <td class="px-4 py-3.5 text-slate-500">
                                    {{ formatDate(pn.ngay_nhap) }}
                                </td>
                                <td class="px-4 py-3.5 text-slate-600">
                                    {{ pn.nguoi_tao?.name }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold text-slate-700">
                                    <span class="rounded-lg bg-slate-100 px-2 py-0.5">{{ pn.chi_tiets?.length ?? 0 }} SP</span>
                                </td>
                                <td class="px-4 py-3.5 text-right font-black text-emerald-700">
                                    {{ formatPrice(pn.tong_tien) }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-800"
                                    >
                                        Đã nhập kho
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>
