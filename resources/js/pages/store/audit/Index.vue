<script setup lang="ts">
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ClipboardCheck, Plus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface PhieuKiemKeItem {
    id: number;
    ma_phieu: string;
    nguoi_tao: { id: number; name: string };
    nguoi_duyet?: { id: number; name: string };
    ngay_kiem_ke: string;
    trang_thai: number;
    ghi_chu?: string;
    chi_tiets: Array<any>;
}

const props = defineProps<{
    stores: Array<any>;
    currentStore: any;
    phieuKiemKes: Array<PhieuKiemKeItem>;
}>();

const searchQuery = ref('');

const filteredPhieuKiemKes = computed(() => {
    return props.phieuKiemKes.filter((p) => {
        const q = searchQuery.value.toLowerCase().trim();
        return !q || p.ma_phieu.toLowerCase().includes(q) || p.ghi_chu?.toLowerCase().includes(q);
    });
});

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleString('vi-VN');
}
</script>

<template>
    <StoreOpsLayout title="Kiểm Kê Kho & Cân Bằng Tồn Kho" :stores="stores" :currentStore="currentStore">
        <div class="space-y-6">
            <!-- TOP BAR -->
            <div
                class="flex flex-col items-start justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:flex-row sm:items-center"
            >
                <div>
                    <h2 class="flex items-center space-x-2 text-base font-black text-slate-900">
                        <ClipboardCheck class="h-5 w-5 text-emerald-600" />
                        <span>Biên Bản Kiểm Kê Kho & Ghi Nhận Hao Hụt Thực Phẩm</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-400">
                        So sánh số lượng sổ sách vs kiểm đếm thực tế, ghi nhận hao hụt dập nát và cân bằng kho.
                    </p>
                </div>

                <Link
                    :href="route('store.audit.create')"
                    class="flex items-center space-x-1.5 rounded-2xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-700/20 transition hover:bg-emerald-700"
                >
                    <Plus class="h-4 w-4" />
                    <span>Tạo Biên Bản Kiểm Kê Mới</span>
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
                            placeholder="Tìm kiếm mã phiếu kiểm kê..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                    <span class="text-xs font-medium text-slate-400">Tổng: {{ filteredPhieuKiemKes.length }} biên bản</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Mã Phiếu</th>
                                <th class="px-4 py-3">Ngày Kiểm Kê</th>
                                <th class="px-4 py-3">Người Kiểm Kê</th>
                                <th class="px-4 py-3 text-center">Số Mặt Hàng</th>
                                <th class="px-4 py-3">Ghi Chú</th>
                                <th class="px-4 py-3 text-center">Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredPhieuKiemKes.length === 0">
                                <td colspan="6" class="py-10 text-center text-slate-400">Chưa có biên bản kiểm kê nào.</td>
                            </tr>
                            <tr v-for="pk in filteredPhieuKiemKes" :key="pk.id" class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5 font-mono font-bold text-slate-900">
                                    {{ pk.ma_phieu }}
                                </td>
                                <td class="px-4 py-3.5 text-slate-600">
                                    {{ formatDate(pk.ngay_kiem_ke) }}
                                </td>
                                <td class="px-4 py-3.5 font-medium text-slate-600">
                                    {{ pk.nguoi_tao?.name }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold text-slate-700">
                                    <span class="rounded-lg bg-slate-100 px-2 py-0.5">{{ pk.chi_tiets?.length ?? 0 }} SP</span>
                                </td>
                                <td class="line-clamp-1 px-4 py-3.5 text-slate-500">
                                    {{ pk.ghi_chu || 'Kiểm kê định kỳ' }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-800"
                                    >
                                        Đã cân bằng tồn
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
