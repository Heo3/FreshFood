<script setup lang="ts">
import { ref, computed } from 'vue';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    Plus,
    Search,
    FileInput,
    Calendar,
    Building2,
    User,
    CheckCircle2,
} from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

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
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
                <div>
                    <h2 class="text-base font-black text-slate-900 flex items-center space-x-2">
                        <FileInput class="w-5 h-5 text-emerald-600" />
                        <span>Danh Sách Phiếu Nhập Kho Từ Nhà Cung Cấp</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Theo dõi các đợt nhập hàng, số lô và hạn sử dụng của thực phẩm.</p>
                </div>

                <Link
                    :href="route('store.inbound.create')"
                    class="px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center space-x-1.5 shadow-md shadow-emerald-700/20 transition"
                >
                    <Plus class="w-4 h-4" />
                    <span>Lập Phiếu Nhập Mới</span>
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
                            placeholder="Tìm kiếm mã phiếu, nhà cung cấp..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                        />
                    </div>
                    <span class="text-xs text-slate-400 font-medium">Tổng: {{ filteredPhieuNhaps.length }} phiếu</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Mã Phiếu</th>
                                <th class="py-3 px-4">Nhà Cung Cấp</th>
                                <th class="py-3 px-4">Thời Gian Nhập</th>
                                <th class="py-3 px-4">Người Nhập</th>
                                <th class="py-3 px-4 text-center">Số Mặt Hàng</th>
                                <th class="py-3 px-4 text-right">Tổng Tiền</th>
                                <th class="py-3 px-4 text-center">Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredPhieuNhaps.length === 0">
                                <td colspan="7" class="text-center py-10 text-slate-400">
                                    Chưa có phiếu nhập kho nào.
                                </td>
                            </tr>
                            <tr v-for="pn in filteredPhieuNhaps" :key="pn.id" class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    {{ pn.ma_phieu }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-700">
                                    {{ pn.nha_cung_cap?.ten_nha_cung_cap }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-500">
                                    {{ formatDate(pn.ngay_nhap) }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ pn.nguoi_tao?.name }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-slate-700">
                                    <span class="bg-slate-100 px-2 py-0.5 rounded-lg">{{ pn.chi_tiets?.length ?? 0 }} SP</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-emerald-700">
                                    {{ formatPrice(pn.tong_tien) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800">
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

