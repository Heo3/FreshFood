<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    DollarSign,
    ShoppingBag,
    AlertOctagon,
    Boxes,
    Store,
    TrendingUp,
    Clock,
    CheckCircle,
    XCircle,
    ArrowUpRight,
    ChevronRight,
    ArrowRight,
    RefreshCw,
    BarChart2,
    Calendar,
    Sparkles,
    Eye,
} from 'lucide-vue-next';

const props = defineProps<{
    metrics: {
        tong_doanh_thu: number;
        doanh_thu_hom_nay: number;
        doanh_thu_thang_nay: number;
        don_hang_hom_nay: number;
        don_cho_xac_nhan: number;
        don_hoan_thanh: number;
        don_da_huy: number;
        so_lo_het_han: number;
        so_lo_can_date: number;
        tong_tien_xuat_huy: number;
        tong_san_pham: number;
        tong_chi_nhanh: number;
    };
    chart_revenue: Array<{
        ngay: string;
        doanh_thu: number;
        so_don: number;
    }>;
    stores_revenue: Array<{
        id: number;
        ten_cua_hang: string;
        so_don: number;
        doanh_thu: number;
    }>;
    top_products: Array<{
        san_pham_id: number;
        ten_san_pham: string;
        tong_ban: number;
        tong_tien: number;
    }>;
    recent_orders: Array<{
        id: number;
        ma_don_hang: string;
        khach_hang: string;
        cua_hang: string;
        tong_thanhtoan: number;
        trang_thai_don_hang: string;
        created_at: string;
    }>;
    scope?: {
        is_manager: boolean;
        store_id: number | null;
        store_name?: string;
    };
}>();

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}

function getStatusBadge(status: string) {
    switch (status) {
        case 'hoan_thanh':
        case 'da_giao':
            return { label: 'Hoàn thành', class: 'bg-emerald-50 text-emerald-700 border border-emerald-200/80' };
        case 'dang_giao':
            return { label: 'Đang giao', class: 'bg-blue-50 text-blue-700 border border-blue-200/80' };
        case 'dang_chuan_bi':
            return { label: 'Đang đóng gói', class: 'bg-amber-50 text-amber-700 border border-amber-200/80' };
        case 'cho_xac_nhan':
            return { label: 'Chờ duyệt', class: 'bg-purple-50 text-purple-700 border border-purple-200/80' };
        case 'da_huy':
            return { label: 'Đã hủy', class: 'bg-rose-50 text-rose-700 border border-rose-200/80' };
        default:
            return { label: status, class: 'bg-slate-50 text-slate-700 border border-slate-200/80' };
    }
}

const maxRev = Math.max(...props.chart_revenue.map((i) => i.doanh_thu), 1);
const maxStoreRev = Math.max(...props.stores_revenue.map((s) => s.doanh_thu), 1);
</script>

<template>
    <AdminLayout title="Bảng Điều Khiển Tổng Quan">
        <div class="space-y-6">
            <!-- SCOPE NOTICE BANNER -->
            <div
                v-if="scope?.is_manager"
                class="bg-gradient-to-r from-emerald-50/80 to-teal-50/50 border border-emerald-200/80 p-4 sm:p-5 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100/80 text-emerald-700 flex items-center justify-center shrink-0">
                        <Store class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800">
                            Phân quyền Quản lý Chi nhánh:
                            <span class="text-emerald-700 font-extrabold">{{ scope.store_name || `Chi nhánh #${scope.store_id}` }}</span>
                        </p>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Các số liệu doanh thu, đơn hàng, cảnh báo cận date và xuất hủy bên dưới được tự động giới hạn riêng cho chi nhánh của bạn.
                        </p>
                    </div>
                </div>
                <span class="self-start sm:self-auto text-[11px] uppercase font-bold tracking-wider px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300/80">
                    Chi nhánh Scoped
                </span>
            </div>

            <!-- ============================================================== -->
            <!-- 1. MODERN METRIC STAT CARDS (4 Sleek Responsive Cards)         -->
            <!-- ============================================================== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <!-- CARD 1: DOANH THU THÁNG (Emerald) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md hover:border-emerald-300 transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Doanh Thu Tháng Này</span>
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                                <DollarSign class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2">
                            {{ formatCurrency(metrics.doanh_thu_thang_nay) }}
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
                            <span class="font-medium text-emerald-600">Hôm nay:</span>
                            <span class="font-semibold text-slate-700">{{ formatCurrency(metrics.doanh_thu_hom_nay) }}</span>
                        </div>
                    </div>
                    <div class="pt-4 mt-3 border-t border-slate-100">
                        <Link
                            :href="route('admin.reports.index')"
                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1 transition-colors"
                        >
                            <span>Xem báo cáo chi tiết</span>
                            <ArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
                        </Link>
                    </div>
                </div>

                <!-- CARD 2: ĐƠN HÀNG HÔM NAY (Blue) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md hover:border-blue-300 transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Đơn Hàng Hôm Nay</span>
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                                <ShoppingBag class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 flex items-baseline gap-1.5">
                            <span>{{ metrics.don_hang_hom_nay }}</span>
                            <span class="text-sm font-normal text-slate-500">đơn</span>
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            <span>Chờ duyệt: </span>
                            <b class="text-purple-600 font-semibold">{{ metrics.don_cho_xac_nhan }}</b>
                            <span class="mx-1 text-slate-300">&bull;</span>
                            <span>Hoàn thành: </span>
                            <b class="text-emerald-600 font-semibold">{{ metrics.don_hoan_thanh }}</b>
                        </div>
                    </div>
                    <div class="pt-4 mt-3 border-t border-slate-100">
                        <Link
                            :href="route('admin.orders.index')"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1 transition-colors"
                        >
                            <span>Quản lý đơn hàng</span>
                            <ArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
                        </Link>
                    </div>
                </div>

                <!-- CARD 3: CẢNH BÁO DATE (Amber) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md hover:border-amber-300 transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Lô Cận Hạn Sử Dụng</span>
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                                <AlertOctagon class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 flex items-baseline gap-1.5">
                            <span>{{ metrics.so_lo_can_date }}</span>
                            <span class="text-sm font-normal text-slate-500">lô</span>
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            <span>Đã hết HSD: </span>
                            <b class="text-rose-600 font-semibold">{{ metrics.so_lo_het_han }} lô</b>
                            <span class="text-slate-400"> cần xuất hủy</span>
                        </div>
                    </div>
                    <div class="pt-4 mt-3 border-t border-slate-100">
                        <Link
                            :href="route('store.batches.expiry')"
                            class="text-xs font-semibold text-amber-600 hover:text-amber-700 inline-flex items-center gap-1 transition-colors"
                        >
                            <span>Xử lý xả hàng / Hủy lô</span>
                            <ArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
                        </Link>
                    </div>
                </div>

                <!-- CARD 4: HAO HỤT HÀNG HỦY (Rose) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:shadow-md hover:border-rose-300 transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Chi Phí Hủy Hàng / Date</span>
                            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                                <Store class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2">
                            {{ formatCurrency(metrics.tong_tien_xuat_huy) }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            <span>Hệ thống: </span>
                            <b class="text-slate-700">{{ metrics.tong_chi_nhanh }} siêu thị</b>
                            <span class="mx-1 text-slate-300">&bull;</span>
                            <span>{{ metrics.tong_san_pham }} SP</span>
                        </div>
                    </div>
                    <div class="pt-4 mt-3 border-t border-slate-100">
                        <Link
                            :href="route('admin.stores.index')"
                            class="text-xs font-semibold text-rose-600 hover:text-rose-700 inline-flex items-center gap-1 transition-colors"
                        >
                            <span>Quản lý chuỗi chi nhánh</span>
                            <ArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- 2. CHARTS & STORE REVENUES ROW                                 -->
            <!-- ============================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- CHART CARD (7 Days Revenue) -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <!-- Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <BarChart2 class="w-4 h-4 text-emerald-600" />
                                    <span>Biểu Đồ Doanh Thu 7 Ngày Gần Nhất</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Thống kê doanh số chuỗi cửa hàng theo thời gian thực</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                <TrendingUp class="w-3 h-3 mr-1 text-emerald-600" />
                                Real-time
                            </span>
                        </div>

                        <!-- Bar Chart Container -->
                        <div class="h-64 flex items-end justify-between gap-3 pt-8 pb-2 border-b border-slate-100">
                            <div
                                v-for="item in chart_revenue"
                                :key="item.ngay"
                                class="flex-1 flex flex-col items-center h-full justify-end group relative"
                            >
                                <!-- Floating Tooltip on Hover -->
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900 text-white text-[11px] rounded-xl px-2.5 py-1.5 mb-2 pointer-events-none text-center whitespace-nowrap shadow-lg absolute -top-8 z-20">
                                    <div class="font-bold text-emerald-400">{{ formatCurrency(item.doanh_thu) }}</div>
                                    <div class="text-slate-300 text-[10px]">{{ item.so_don }} đơn hàng</div>
                                </div>

                                <!-- Sleek Bar with Rounded Top & Gradient -->
                                <div
                                    class="w-full max-w-[42px] bg-gradient-to-t from-emerald-600 to-teal-400 hover:from-emerald-500 hover:to-teal-300 rounded-t-xl transition-all duration-300 relative min-h-[10px] shadow-xs cursor-pointer"
                                    :style="{ height: `${Math.round((item.doanh_thu / maxRev) * 100)}%` }"
                                />

                                <!-- X-Axis Date Label -->
                                <span class="text-xs text-slate-500 font-medium mt-2.5">{{ item.ngay }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Footer Info -->
                    <div class="flex items-center justify-between pt-3 text-xs text-slate-500">
                        <span>Biểu đồ tự động cập nhật theo đơn hàng</span>
                        <span class="font-semibold text-slate-800">Doanh số cao nhất: <span class="text-emerald-600 font-bold">{{ formatCurrency(maxRev) }}</span></span>
                    </div>
                </div>

                <!-- STORE REVENUE CARD -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <!-- Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <Store class="w-4 h-4 text-emerald-600" />
                                    <span>Doanh Thu Theo Chi Nhánh</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Xếp hạng theo doanh số bán lẻ</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">
                                {{ stores_revenue.length }} điểm bán
                            </span>
                        </div>

                        <!-- Store List -->
                        <div class="py-4 space-y-3.5">
                            <div
                                v-for="store in stores_revenue"
                                :key="store.id"
                                class="group"
                            >
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="font-semibold text-slate-800 truncate max-w-[180px]">{{ store.ten_cua_hang }}</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(store.doanh_thu) }}</span>
                                </div>
                                <!-- Micro Progress Bar -->
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div
                                        class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500"
                                        :style="{ width: `${Math.round((store.doanh_thu / maxStoreRev) * 100)}%` }"
                                    />
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    {{ store.so_don }} đơn hàng thành công
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Link -->
                    <div class="pt-3 border-t border-slate-100">
                        <Link
                            :href="route('admin.reports.index')"
                            class="w-full inline-flex items-center justify-center text-xs font-semibold text-slate-700 hover:text-emerald-600 py-2.5 px-3 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition-all gap-1"
                        >
                            <span>Xem Báo Cáo Doanh Số Chi Nhánh</span>
                            <ChevronRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- 3. TOP PRODUCTS & RECENT ORDERS ROW                            -->
            <!-- ============================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- TOP SELLING PRODUCTS -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <!-- Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <Boxes class="w-4 h-4 text-amber-500" />
                                    <span>Top Sản Phẩm Bán Chạy</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Sản phẩm thực phẩm tươi có lượng tiêu thụ lớn</p>
                            </div>
                        </div>

                        <!-- Product List -->
                        <div class="py-2 divide-y divide-slate-100">
                            <div
                                v-for="(prod, idx) in top_products"
                                :key="prod.san_pham_id"
                                class="py-3 flex items-center justify-between hover:bg-slate-50/80 px-2 -mx-2 rounded-xl transition-colors"
                            >
                                <div class="flex items-center space-x-3 min-w-0">
                                    <span
                                        :class="[
                                            'w-6 h-6 rounded-lg font-bold flex items-center justify-center text-[11px] shrink-0',
                                            idx === 0 ? 'bg-amber-100 text-amber-800' :
                                            idx === 1 ? 'bg-slate-200 text-slate-700' :
                                            idx === 2 ? 'bg-amber-50 text-amber-900 border border-amber-300' : 'bg-slate-100 text-slate-500'
                                        ]"
                                    >
                                        {{ idx + 1 }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-xs text-slate-800 truncate max-w-[150px]">{{ prod.ten_san_pham }}</div>
                                        <span class="text-[11px] text-emerald-600 font-medium">Đã bán: {{ Number(prod.tong_ban).toFixed(1) }}</span>
                                    </div>
                                </div>
                                <span class="font-bold text-xs text-slate-900 whitespace-nowrap pl-2">
                                    {{ formatCurrency(prod.tong_tien) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Link -->
                    <div class="pt-3 border-t border-slate-100">
                        <Link
                            :href="route('admin.products.index')"
                            class="w-full inline-flex items-center justify-center text-xs font-semibold text-slate-700 hover:text-emerald-600 py-2.5 px-3 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition-all gap-1"
                        >
                            <span>Xem Kho Hàng & Sản Phẩm</span>
                            <ChevronRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- RECENT ORDERS TABLE -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col justify-between">
                    <div>
                        <!-- Header -->
                        <div class="p-5 sm:px-6 sm:py-5 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <ShoppingBag class="w-4 h-4 text-emerald-600" />
                                    <span>Đơn Hàng Mới Nhất Chuỗi</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Theo dõi luồng đơn hàng trực tuyến và POS</p>
                            </div>
                            <Link
                                :href="route('admin.orders.index')"
                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 hover:underline"
                            >
                                <span>Xem tất cả</span>
                                <ChevronRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                                        <th class="py-3 px-4 sm:px-6">Mã Đơn</th>
                                        <th class="py-3 px-4">Khách Hàng</th>
                                        <th class="py-3 px-4">Chi Nhánh</th>
                                        <th class="py-3 px-4">Tổng Tiền</th>
                                        <th class="py-3 px-4 text-center">Trạng Thái</th>
                                        <th class="py-3 px-4 sm:px-6 text-right">Chi Tiết</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="order in recent_orders" :key="order.id" class="hover:bg-slate-50/60 transition-colors">
                                        <td class="py-3 px-4 sm:px-6 font-semibold text-emerald-600">
                                            <Link :href="route('admin.orders.show', order.id)" class="hover:underline">
                                                {{ order.ma_don_hang }}
                                            </Link>
                                        </td>
                                        <td class="py-3 px-4 font-medium text-slate-800">{{ order.khach_hang }}</td>
                                        <td class="py-3 px-4 text-slate-500">{{ order.cua_hang }}</td>
                                        <td class="py-3 px-4 font-bold text-slate-900">{{ formatCurrency(order.tong_thanhtoan) }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <span
                                                :class="[
                                                    'inline-block px-2.5 py-1 rounded-full text-[10px] font-semibold tracking-wide',
                                                    getStatusBadge(order.trang_thai_don_hang).class
                                                ]"
                                            >
                                                {{ getStatusBadge(order.trang_thai_don_hang).label }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 sm:px-6 text-right">
                                            <Link
                                                :href="route('admin.orders.show', order.id)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 font-medium transition-colors"
                                            >
                                                <Eye class="w-3 h-3" />
                                                <span>Xem</span>
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Table Count -->
                    <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/40 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Hiển thị {{ recent_orders.length }} đơn hàng phát sinh gần nhất</span>
                        <Link :href="route('admin.orders.index')" class="text-emerald-600 hover:underline font-medium">Quản lý toàn bộ đơn &rarr;</Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>