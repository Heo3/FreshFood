<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    BarChart3,
    Calendar,
    DollarSign,
    ShoppingBag,
    AlertOctagon,
    Boxes,
    Store,
    Filter,
    ArrowRight,
} from 'lucide-vue-next';

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
        { preserveState: true }
    );
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}
</script>

<template>
    <AdminLayout
        title="Báo Cáo & Thống Kê"
        :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Báo cáo' }]"
    >
        <div class="space-y-4">
            <!-- ADMINLTE FILTER TOOLBAR CARD -->
            <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs p-3.5">
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <div class="flex items-center space-x-1.5">
                        <span class="font-bold text-gray-700">Từ ngày:</span>
                        <input
                            v-model="fromDate"
                            type="date"
                            class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                        />
                    </div>

                    <div class="flex items-center space-x-1.5">
                        <span class="font-bold text-gray-700">Đến ngày:</span>
                        <input
                            v-model="toDate"
                            type="date"
                            class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                        />
                    </div>

                    <div class="flex items-center space-x-1.5">
                        <span class="font-bold text-gray-700">Chi nhánh:</span>
                        <select
                            v-model="storeId"
                            class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                        >
                            <option value="">-- Toàn bộ chuỗi siêu thị --</option>
                            <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }}</option>
                        </select>
                    </div>

                    <button
                        class="px-4 py-1.5 bg-[#007bff] hover:bg-[#0069d9] text-white rounded-sm font-semibold transition-colors flex items-center space-x-1"
                        @click="handleFilter"
                    >
                        <Filter class="w-3.5 h-3.5" />
                        <span>Xem Báo Cáo</span>
                    </button>
                </div>
            </div>

            <!-- ADMINLTE SMALL-BOXES -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- DOANH THU KỲ -->
                <div class="relative bg-[#17a2b8] text-white rounded-sm shadow-sm overflow-hidden p-4">
                    <div class="text-2xl font-extrabold tracking-tight">
                        {{ formatCurrency(metrics.tong_doanh_thu) }}
                    </div>
                    <p class="text-xs uppercase font-medium text-cyan-100 mt-1">
                        Doanh Thu Kỳ Này
                    </p>
                    <span class="text-[11px] text-cyan-100 block mt-0.5">
                        {{ metrics.tong_don_hang }} đơn hàng giao thành công
                    </span>
                    <DollarSign class="w-16 h-16 text-black/15 absolute right-2 top-2 pointer-events-none" />
                </div>

                <!-- HAO HỤT HÀNG HỦY -->
                <div class="relative bg-[#dc3545] text-white rounded-sm shadow-sm overflow-hidden p-4">
                    <div class="text-2xl font-extrabold tracking-tight">
                        {{ formatCurrency(metrics.tong_tien_xuat_huy) }}
                    </div>
                    <p class="text-xs uppercase font-medium text-red-100 mt-1">
                        Hao Hụt & Xuất Hủy Date
                    </p>
                    <span class="text-[11px] text-red-100 block mt-0.5">
                        Tổn thất thực phẩm hỏng / cận hạn
                    </span>
                    <AlertOctagon class="w-16 h-16 text-black/15 absolute right-2 top-2 pointer-events-none" />
                </div>

                <!-- TỒN KHO TOÀN CHUỖI -->
                <div class="relative bg-[#28a745] text-white rounded-sm shadow-sm overflow-hidden p-4">
                    <div class="text-2xl font-extrabold tracking-tight">
                        {{ Number(inventory_by_store.reduce((acc, i) => acc + i.tong_ton, 0)).toFixed(1) }} <span class="text-sm font-normal">đơn vị</span>
                    </div>
                    <p class="text-xs uppercase font-medium text-green-100 mt-1">
                        Tổng Tồn Kho Chuỗi Hiện Tại
                    </p>
                    <span class="text-[11px] text-green-100 block mt-0.5">
                        Phân bổ trên {{ inventory_by_store.length }} chi nhánh
                    </span>
                    <Boxes class="w-16 h-16 text-black/15 absolute right-2 top-2 pointer-events-none" />
                </div>
            </div>

            <!-- DETAILED REPORT CARDS -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- TỒN KHO CHI NHÁNH CARD (card card-primary card-outline) -->
                <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs">
                    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-[#343a40] flex items-center space-x-2">
                            <Store class="w-4 h-4 text-[#007bff]" />
                            <span>Tồn Kho Theo Chi Nhánh</span>
                        </h4>
                    </div>

                    <div class="p-4 space-y-2.5">
                        <div
                            v-for="inv in inventory_by_store"
                            :key="inv.id"
                            class="p-2.5 bg-gray-50 border border-gray-200 rounded-sm flex items-center justify-between text-xs"
                        >
                            <span class="font-bold text-gray-800">{{ inv.ten_cua_hang }}</span>
                            <span class="font-bold text-[#007bff] bg-white px-2.5 py-1 rounded-sm border border-gray-200">
                                {{ Number(inv.tong_ton).toFixed(1) }} SP
                            </span>
                        </div>
                    </div>
                </div>

                <!-- LÔ HÀNG CẬN DATE CARD (card card-danger card-outline) -->
                <div class="bg-white rounded-sm border-t-3 border-[#dc3545] border-x border-b border-gray-200 shadow-xs">
                    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-[#343a40] flex items-center space-x-2">
                            <AlertOctagon class="w-4 h-4 text-[#dc3545]" />
                            <span>Lô Hàng Cận Date Trong Kho Cần Xử Lý</span>
                        </h4>
                    </div>

                    <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto text-xs">
                        <div
                            v-for="lot in near_expiry_lots"
                            :key="lot.id"
                            class="p-3 flex items-center justify-between hover:bg-gray-50"
                        >
                            <div class="min-w-0 pr-2">
                                <div class="font-bold text-gray-900 truncate">{{ lot.ten_san_pham }}</div>
                                <span class="text-gray-400 font-mono text-[10px]">Lô: {{ lot.so_lo }} ({{ lot.ten_cua_hang }})</span>
                            </div>
                            <div class="text-right shrink-0">
                                <span
                                    :class="[
                                        'inline-block px-1.5 py-0.2 rounded-2xs text-[10px] font-bold uppercase',
                                        lot.da_het_han ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'
                                    ]"
                                >
                                    {{ lot.da_het_han ? 'Đã hết HSD' : `HSD: ${lot.ngay_het_han}` }}
                                </span>
                                <div class="font-bold text-gray-800 text-[11px] mt-0.5">Tồn: {{ lot.so_luong_ton }}</div>
                            </div>
                        </div>
                        <div v-if="near_expiry_lots.length === 0" class="p-6 text-center text-gray-400 italic">
                            Không có lô hàng cận hạn sử dụng nào.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
