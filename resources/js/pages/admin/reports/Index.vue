<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { AlertOctagon, Boxes, DollarSign, FileDown, Filter, Store } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    stores: Array<{ id: number; ten_cua_hang: string }>;
    categories: Array<{ id: number; ten_danh_muc: string; san_phams_count: number }>;
    metrics: {
        tong_doanh_thu: number;
        tong_don_hang: number;
        tong_tien_xuat_huy: number;
    };
    inventory_by_store: Array<{
        id: number;
        ten_cua_hang: string;
        tong_ton: number;
    }>;
    near_expiry_lots: Array<{
        id: number;
        so_lo: string;
        ten_san_pham: string;
        ten_cua_hang: string;
        so_luong_ton: number;
        ngay_het_han: string;
        da_het_han: boolean;
    }>;
    filters: {
        cua_hang_id?: string;
        from_date?: string;
        to_date?: string;
    };
}>();

const storeId = ref(props.filters.cua_hang_id || '');
const fromDate = ref(props.filters.from_date || '');
const toDate = ref(props.filters.to_date || '');

function handleFilter() {
    router.get(
        route('admin.reports.index'),
        {
            cua_hang_id: storeId.value,
            from_date: fromDate.value,
            to_date: toDate.value,
        },
        { preserveState: true },
    );
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}
</script>

<template>
    <AdminLayout title="Báo Cáo & Thống Kê" :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Báo cáo' }]">
        <div class="space-y-4">
            <!-- ADMINLTE FILTER TOOLBAR CARD -->
            <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white p-3.5">
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <div class="flex items-center space-x-1.5">
                        <span class="font-bold text-gray-700">Từ ngày:</span>
                        <input
                            v-model="fromDate"
                            type="date"
                            class="focus:outline-hidden rounded-sm border border-gray-300 bg-gray-50 px-2.5 py-1.5 focus:border-[#007bff]"
                        />
                    </div>

                    <div class="flex items-center space-x-1.5">
                        <span class="font-bold text-gray-700">Đến ngày:</span>
                        <input
                            v-model="toDate"
                            type="date"
                            class="focus:outline-hidden rounded-sm border border-gray-300 bg-gray-50 px-2.5 py-1.5 focus:border-[#007bff]"
                        />
                    </div>

                    <div class="flex items-center space-x-1.5">
                        <span class="font-bold text-gray-700">Chi nhánh:</span>
                        <select
                            v-model="storeId"
                            class="focus:outline-hidden rounded-sm border border-gray-300 bg-gray-50 px-2.5 py-1.5 focus:border-[#007bff]"
                        >
                            <option value="">-- Toàn bộ chuỗi siêu thị --</option>
                            <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }}</option>
                        </select>
                    </div>

                    <button
                        class="flex items-center space-x-1 rounded-sm bg-[#007bff] px-4 py-1.5 font-semibold text-white transition-colors hover:bg-[#0069d9]"
                        @click="handleFilter"
                    >
                        <Filter class="h-3.5 w-3.5" />
                        <span>Xem Báo Cáo</span>
                    </button>

                    <a
                        :href="route('admin.reports.export', { cua_hang_id: storeId, from_date: fromDate, to_date: toDate })"
                        class="flex items-center space-x-1 rounded-sm bg-[#28a745] px-4 py-1.5 font-semibold text-white transition-colors hover:bg-[#218838]"
                        title="Xuất báo cáo doanh thu ra file Excel / CSV"
                    >
                        <FileDown class="h-3.5 w-3.5" />
                        <span>Xuất Báo Cáo CSV</span>
                    </a>
                </div>
            </div>

            <!-- ADMINLTE SMALL-BOXES -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <!-- DOANH THU KỲ -->
                <div class="relative overflow-hidden rounded-sm bg-[#17a2b8] p-4 text-white shadow-sm">
                    <div class="text-2xl font-extrabold tracking-tight">
                        {{ formatCurrency(metrics.tong_doanh_thu) }}
                    </div>
                    <p class="mt-1 text-xs font-medium uppercase text-cyan-100">Doanh Thu Kỳ Này</p>
                    <span class="mt-0.5 block text-[11px] text-cyan-100"> {{ metrics.tong_don_hang }} đơn hàng giao thành công </span>
                    <DollarSign class="pointer-events-none absolute right-2 top-2 h-16 w-16 text-black/15" />
                </div>

                <!-- HAO HỤT HÀNG HỦY -->
                <div class="relative overflow-hidden rounded-sm bg-[#dc3545] p-4 text-white shadow-sm">
                    <div class="text-2xl font-extrabold tracking-tight">
                        {{ formatCurrency(metrics.tong_tien_xuat_huy) }}
                    </div>
                    <p class="mt-1 text-xs font-medium uppercase text-red-100">Hao Hụt & Xuất Hủy Date</p>
                    <span class="mt-0.5 block text-[11px] text-red-100"> Tổn thất thực phẩm hỏng / cận hạn </span>
                    <AlertOctagon class="pointer-events-none absolute right-2 top-2 h-16 w-16 text-black/15" />
                </div>

                <!-- TỒN KHO TOÀN CHUỖI -->
                <div class="relative overflow-hidden rounded-sm bg-[#28a745] p-4 text-white shadow-sm">
                    <div class="text-2xl font-extrabold tracking-tight">
                        {{ Number(inventory_by_store.reduce((acc, i) => acc + i.tong_ton, 0)).toFixed(1) }}
                        <span class="text-sm font-normal">đơn vị</span>
                    </div>
                    <p class="mt-1 text-xs font-medium uppercase text-green-100">Tổng Tồn Kho Chuỗi Hiện Tại</p>
                    <span class="mt-0.5 block text-[11px] text-green-100"> Phân bổ trên {{ inventory_by_store.length }} chi nhánh </span>
                    <Boxes class="pointer-events-none absolute right-2 top-2 h-16 w-16 text-black/15" />
                </div>
            </div>

            <!-- DETAILED REPORT CARDS -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- TỒN KHO CHI NHÁNH CARD (card card-primary card-outline) -->
                <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                        <h4 class="flex items-center space-x-2 text-sm font-bold text-[#343a40]">
                            <Store class="h-4 w-4 text-[#007bff]" />
                            <span>Tồn Kho Theo Chi Nhánh</span>
                        </h4>
                    </div>

                    <div class="space-y-2.5 p-4">
                        <div
                            v-for="inv in inventory_by_store"
                            :key="inv.id"
                            class="flex items-center justify-between rounded-sm border border-gray-200 bg-gray-50 p-2.5 text-xs"
                        >
                            <span class="font-bold text-gray-800">{{ inv.ten_cua_hang }}</span>
                            <span class="rounded-sm border border-gray-200 bg-white px-2.5 py-1 font-bold text-[#007bff]">
                                {{ Number(inv.tong_ton).toFixed(1) }} SP
                            </span>
                        </div>
                    </div>
                </div>

                <!-- LÔ HÀNG CẬN DATE CARD (card card-danger card-outline) -->
                <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#dc3545] border-gray-200 bg-white">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                        <h4 class="flex items-center space-x-2 text-sm font-bold text-[#343a40]">
                            <AlertOctagon class="h-4 w-4 text-[#dc3545]" />
                            <span>Lô Hàng Cận Date Trong Kho Cần Xử Lý</span>
                        </h4>
                    </div>

                    <div class="max-h-80 divide-y divide-gray-100 overflow-y-auto text-xs">
                        <div v-for="lot in near_expiry_lots" :key="lot.id" class="flex items-center justify-between p-3 hover:bg-gray-50">
                            <div class="min-w-0 pr-2">
                                <div class="truncate font-bold text-gray-900">{{ lot.ten_san_pham }}</div>
                                <span class="font-mono text-[10px] text-gray-400">Lô: {{ lot.so_lo }} ({{ lot.ten_cua_hang }})</span>
                            </div>
                            <div class="shrink-0 text-right">
                                <span
                                    :class="[
                                        'py-0.2 rounded-2xs inline-block px-1.5 text-[10px] font-bold uppercase',
                                        lot.da_het_han ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800',
                                    ]"
                                >
                                    {{ lot.da_het_han ? 'Đã hết HSD' : `HSD: ${lot.ngay_het_han}` }}
                                </span>
                                <div class="mt-0.5 text-[11px] font-bold text-gray-800">Tồn: {{ lot.so_luong_ton }}</div>
                            </div>
                        </div>
                        <div v-if="near_expiry_lots.length === 0" class="p-6 text-center italic text-gray-400">
                            Không có lô hàng cận hạn sử dụng nào.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
