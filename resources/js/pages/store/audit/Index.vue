<script setup lang="ts">
import { ref, computed } from 'vue';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    Plus,
    Search,
    ClipboardCheck,
    Calendar,
    User,
    CheckCircle2,
} from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

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
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-base font-black text-slate-900 flex items-center space-x-2">
                        <ClipboardCheck class="w-5 h-5 text-emerald-600" />
                        <span>Biên Bản Kiểm Kê Kho & Ghi Nhận Hao Hụt Thực Phẩm</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">So sánh số lượng sổ sách vs kiểm đếm thực tế, ghi nhận hao hụt dập nát và cân bằng kho.</p>
                </div>

                <Link
                    :href="route('store.audit.create')"
                    class="px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center space-x-1.5 shadow-md shadow-emerald-700/20 transition"
                >
                    <Plus class="w-4 h-4" />
                    <span>Tạo Biên Bản Kiểm Kê Mới</span>
                </Link>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm kiếm mã phiếu kiểm kê..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                        />
                    </div>
                    <span class="text-xs text-slate-400 font-medium">Tổng: {{ filteredPhieuKiemKes.length }} biên bản</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Mã Phiếu</th>
                                <th class="py-3 px-4">Ngày Kiểm Kê</th>
                                <th class="py-3 px-4">Người Kiểm Kê</th>
                                <th class="py-3 px-4 text-center">Số Mặt Hàng</th>
                                <th class="py-3 px-4">Ghi Chú</th>
                                <th class="py-3 px-4 text-center">Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredPhieuKiemKes.length === 0">
                                <td colspan="6" class="text-center py-10 text-slate-400">
                                    Chưa có biên bản kiểm kê nào.
                                </td>
                            </tr>
                            <tr v-for="pk in filteredPhieuKiemKes" :key="pk.id" class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    {{ pk.ma_phieu }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ formatDate(pk.ngay_kiem_ke) }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 font-medium">
                                    {{ pk.nguoi_tao?.name }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-slate-700">
                                    <span class="bg-slate-100 px-2 py-0.5 rounded-lg">{{ pk.chi_tiets?.length ?? 0 }} SP</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 line-clamp-1">
                                    {{ pk.ghi_chu || 'Kiểm kê định kỳ' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800">
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

