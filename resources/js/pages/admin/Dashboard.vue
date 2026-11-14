<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import { AlertOctagon, ArrowRight, BarChart2, Boxes, ChevronRight, DollarSign, Eye, ShoppingBag, Store, TrendingUp } from 'lucide-vue-next';

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
                class="shadow-xs flex flex-col justify-between gap-3 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50/80 to-teal-50/50 p-4 sm:flex-row sm:items-center sm:p-5"
            >
                <div class="flex items-center space-x-3.5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100/80 text-emerald-700">
                        <Store class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800">
                            Phân quyền Quản lý Chi nhánh:
                            <span class="font-extrabold text-emerald-700">{{ scope.store_name || `Chi nhánh #${scope.store_id}` }}</span>
                        </p>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                            Các số liệu doanh thu, đơn hàng, cảnh báo cận date và xuất hủy bên dưới được tự động giới hạn riêng cho chi nhánh của bạn.
                        </p>
                    </div>
                </div>
                <span
                    class="self-start rounded-full border border-emerald-300/80 bg-emerald-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-emerald-800 sm:self-auto"
                >
                    Chi nhánh Scoped
                </span>
            </div>

            <!-- ============================================================== -->
            <!-- 1. MODERN METRIC STAT CARDS (4 Sleek Responsive Cards)         -->
            <!-- ============================================================== -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4">
                <!-- CARD 1: DOANH THU THÁNG (Emerald) -->
                <div
                    class="shadow-xs group flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-5 transition-all duration-200 hover:border-emerald-300 hover:shadow-md"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Doanh Thu Tháng Này</span>
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-transform duration-200 group-hover:scale-105"
                            >
                                <DollarSign class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">
                            {{ formatCurrency(metrics.doanh_thu_thang_nay) }}
                        </div>
                        <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                            <span class="font-medium text-emerald-600">Hôm nay:</span>
                            <span class="font-semibold text-slate-700">{{ formatCurrency(metrics.doanh_thu_hom_nay) }}</span>
                        </div>
                    </div>
                    <div class="mt-3 border-t border-slate-100 pt-4">
                        <Link
                            :href="route('admin.reports.index')"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 transition-colors hover:text-emerald-700"
                        >
                            <span>Xem báo cáo chi tiết</span>
                            <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                        </Link>
                    </div>
                </div>

                <!-- CARD 2: ĐƠN HÀNG HÔM NAY (Blue) -->
                <div
                    class="shadow-xs group flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-5 transition-all duration-200 hover:border-blue-300 hover:shadow-md"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Đơn Hàng Hôm Nay</span>
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-transform duration-200 group-hover:scale-105"
                            >
                                <ShoppingBag class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="mt-2 flex items-baseline gap-1.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            <span>{{ metrics.don_hang_hom_nay }}</span>
                            <span class="text-sm font-normal text-slate-500">đơn</span>
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            <span>Chờ duyệt: </span>
                            <b class="font-semibold text-purple-600">{{ metrics.don_cho_xac_nhan }}</b>
                            <span class="mx-1 text-slate-300">&bull;</span>
                            <span>Hoàn thành: </span>
                            <b class="font-semibold text-emerald-600">{{ metrics.don_hoan_thanh }}</b>
                        </div>
                    </div>
                    <div class="mt-3 border-t border-slate-100 pt-4">
                        <Link
                            :href="route('admin.orders.index')"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 transition-colors hover:text-blue-700"
                        >
                            <span>Quản lý đơn hàng</span>
                            <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                        </Link>
                    </div>
                </div>

                <!-- CARD 3: CẢNH BÁO DATE (Amber) -->
                <div
                    class="shadow-xs group flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-5 transition-all duration-200 hover:border-amber-300 hover:shadow-md"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Lô Cận Hạn Sử Dụng</span>
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition-transform duration-200 group-hover:scale-105"
                            >
                                <AlertOctagon class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="mt-2 flex items-baseline gap-1.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            <span>{{ metrics.so_lo_can_date }}</span>
                            <span class="text-sm font-normal text-slate-500">lô</span>
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            <span>Đã hết HSD: </span>
                            <b class="font-semibold text-rose-600">{{ metrics.so_lo_het_han }} lô</b>
                            <span class="text-slate-400"> cần xuất hủy</span>
                        </div>
                    </div>
                    <div class="mt-3 border-t border-slate-100 pt-4">
                        <Link
                            :href="route('store.batches.expiry')"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 transition-colors hover:text-amber-700"
                        >
                            <span>Xử lý xả hàng / Hủy lô</span>
                            <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                        </Link>
                    </div>
                </div>

                <!-- CARD 4: HAO HỤT HÀNG HỦY (Rose) -->
                <div
                    class="shadow-xs group flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-5 transition-all duration-200 hover:border-rose-300 hover:shadow-md"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Chi Phí Hủy Hàng / Date</span>
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 transition-transform duration-200 group-hover:scale-105"
                            >
                                <Store class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">
                            {{ formatCurrency(metrics.tong_tien_xuat_huy) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            <span>Hệ thống: </span>
                            <b class="text-slate-700">{{ metrics.tong_chi_nhanh }} siêu thị</b>
                            <span class="mx-1 text-slate-300">&bull;</span>
                            <span>{{ metrics.tong_san_pham }} SP</span>
                        </div>
                    </div>
                    <div class="mt-3 border-t border-slate-100 pt-4">
                        <Link
                            :href="route('admin.stores.index')"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 transition-colors hover:text-rose-700"
                        >
                            <span>Quản lý chuỗi chi nhánh</span>
                            <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- 2. CHARTS & STORE REVENUES ROW                                 -->
            <!-- ============================================================== -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- CHART CARD (7 Days Revenue) -->
                <div class="shadow-xs flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 lg:col-span-2">
                    <div>
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900">
                                    <BarChart2 class="h-4 w-4 text-emerald-600" />
                                    <span>Biểu Đồ Doanh Thu 7 Ngày Gần Nhất</span>
                                </h3>
                                <p class="mt-0.5 text-xs text-slate-400">Thống kê doanh số chuỗi cửa hàng theo thời gian thực</p>
                            </div>
                            <span
                                class="inline-flex items-center rounded-full border border-emerald-200/60 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"
                            >
                                <TrendingUp class="mr-1 h-3 w-3 text-emerald-600" />
                                Real-time
                            </span>
                        </div>

                        <!-- Bar Chart Container -->
                        <div class="flex h-64 items-end justify-between gap-3 border-b border-slate-100 pb-2 pt-8">
                            <div
                                v-for="item in chart_revenue"
                                :key="item.ngay"
                                class="group relative flex h-full flex-1 flex-col items-center justify-end"
                            >
                                <!-- Floating Tooltip on Hover -->
                                <div
                                    class="pointer-events-none absolute -top-8 z-20 mb-2 whitespace-nowrap rounded-xl bg-slate-900 px-2.5 py-1.5 text-center text-[11px] text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100"
                                >
                                    <div class="font-bold text-emerald-400">{{ formatCurrency(item.doanh_thu) }}</div>
                                    <div class="text-[10px] text-slate-300">{{ item.so_don }} đơn hàng</div>
                                </div>

                                <!-- Sleek Bar with Rounded Top & Gradient -->
                                <div
                                    class="shadow-xs relative min-h-[10px] w-full max-w-[42px] cursor-pointer rounded-t-xl bg-gradient-to-t from-emerald-600 to-teal-400 transition-all duration-300 hover:from-emerald-500 hover:to-teal-300"
                                    :style="{ height: `${Math.round((item.doanh_thu / maxRev) * 100)}%` }"
                                />

                                <!-- X-Axis Date Label -->
                                <span class="mt-2.5 text-xs font-medium text-slate-500">{{ item.ngay }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Footer Info -->
                    <div class="flex items-center justify-between pt-3 text-xs text-slate-500">
                        <span>Biểu đồ tự động cập nhật theo đơn hàng</span>
                        <span class="font-semibold text-slate-800"
                            >Doanh số cao nhất: <span class="font-bold text-emerald-600">{{ formatCurrency(maxRev) }}</span></span
                        >
                    </div>
                </div>

                <!-- STORE REVENUE CARD -->
                <div class="shadow-xs flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6">
                    <div>
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900">
                                    <Store class="h-4 w-4 text-emerald-600" />
                                    <span>Doanh Thu Theo Chi Nhánh</span>
                                </h3>
                                <p class="mt-0.5 text-xs text-slate-400">Xếp hạng theo doanh số bán lẻ</p>
                            </div>
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                                {{ stores_revenue.length }} điểm bán
                            </span>
                        </div>

                        <!-- Store List -->
                        <div class="space-y-3.5 py-4">
                            <div v-for="store in stores_revenue" :key="store.id" class="group">
                                <div class="mb-1.5 flex items-center justify-between text-xs">
                                    <span class="max-w-[180px] truncate font-semibold text-slate-800">{{ store.ten_cua_hang }}</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(store.doanh_thu) }}</span>
                                </div>
                                <!-- Micro Progress Bar -->
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-1.5 rounded-full bg-emerald-500 transition-all duration-500"
                                        :style="{ width: `${Math.round((store.doanh_thu / maxStoreRev) * 100)}%` }"
                                    />
                                </div>
                                <div class="mt-1 text-[10px] text-slate-400">{{ store.so_don }} đơn hàng thành công</div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Link -->
                    <div class="border-t border-slate-100 pt-3">
                        <Link
                            :href="route('admin.reports.index')"
                            class="inline-flex w-full items-center justify-center gap-1 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold text-slate-700 transition-all hover:border-emerald-300 hover:bg-emerald-50/50 hover:text-emerald-600"
                        >
                            <span>Xem Báo Cáo Doanh Số Chi Nhánh</span>
                            <ChevronRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- 3. TOP PRODUCTS & RECENT ORDERS ROW                            -->
            <!-- ============================================================== -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- TOP SELLING PRODUCTS -->
                <div class="shadow-xs flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6">
                    <div>
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900">
                                    <Boxes class="h-4 w-4 text-amber-500" />
                                    <span>Top Sản Phẩm Bán Chạy</span>
                                </h3>
                                <p class="mt-0.5 text-xs text-slate-400">Sản phẩm thực phẩm tươi có lượng tiêu thụ lớn</p>
                            </div>
                        </div>

                        <!-- Product List -->
                        <div class="divide-y divide-slate-100 py-2">
                            <div
                                v-for="(prod, idx) in top_products"
                                :key="prod.san_pham_id"
                                class="-mx-2 flex items-center justify-between rounded-xl px-2 py-3 transition-colors hover:bg-slate-50/80"
                            >
                                <div class="flex min-w-0 items-center space-x-3">
                                    <span
                                        :class="[
                                            'flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-[11px] font-bold',
                                            idx === 0
                                                ? 'bg-amber-100 text-amber-800'
                                                : idx === 1
                                                  ? 'bg-slate-200 text-slate-700'
                                                  : idx === 2
                                                    ? 'border border-amber-300 bg-amber-50 text-amber-900'
                                                    : 'bg-slate-100 text-slate-500',
                                        ]"
                                    >
                                        {{ idx + 1 }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="max-w-[150px] truncate text-xs font-semibold text-slate-800">{{ prod.ten_san_pham }}</div>
                                        <span class="text-[11px] font-medium text-emerald-600">Đã bán: {{ Number(prod.tong_ban).toFixed(1) }}</span>
                                    </div>
                                </div>
                                <span class="whitespace-nowrap pl-2 text-xs font-bold text-slate-900">
                                    {{ formatCurrency(prod.tong_tien) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Link -->
                    <div class="border-t border-slate-100 pt-3">
                        <Link
                            :href="route('admin.products.index')"
                            class="inline-flex w-full items-center justify-center gap-1 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold text-slate-700 transition-all hover:border-emerald-300 hover:bg-emerald-50/50 hover:text-emerald-600"
                        >
                            <span>Xem Kho Hàng & Sản Phẩm</span>
                            <ChevronRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- RECENT ORDERS TABLE -->
                <div class="shadow-xs flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-white lg:col-span-2">
                    <div>
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-slate-100 p-5 sm:px-6 sm:py-5">
                            <div>
                                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900">
                                    <ShoppingBag class="h-4 w-4 text-emerald-600" />
                                    <span>Đơn Hàng Mới Nhất Chuỗi</span>
                                </h3>
                                <p class="mt-0.5 text-xs text-slate-400">Theo dõi luồng đơn hàng trực tuyến và POS</p>
                            </div>
                            <Link
                                :href="route('admin.orders.index')"
                                class="flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline"
                            >
                                <span>Xem tất cả</span>
                                <ChevronRight class="h-3.5 w-3.5" />
                            </Link>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-left text-xs">
                                <thead>
                                    <tr
                                        class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                                    >
                                        <th class="px-4 py-3 sm:px-6">Mã Đơn</th>
                                        <th class="px-4 py-3">Khách Hàng</th>
                                        <th class="px-4 py-3">Chi Nhánh</th>
                                        <th class="px-4 py-3">Tổng Tiền</th>
                                        <th class="px-4 py-3 text-center">Trạng Thái</th>
                                        <th class="px-4 py-3 text-right sm:px-6">Chi Tiết</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="order in recent_orders" :key="order.id" class="transition-colors hover:bg-slate-50/60">
                                        <td class="px-4 py-3 font-semibold text-emerald-600 sm:px-6">
                                            <Link :href="route('admin.orders.show', order.id)" class="hover:underline">
                                                {{ order.ma_don_hang }}
                                            </Link>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ order.khach_hang }}</td>
                                        <td class="px-4 py-3 text-slate-500">{{ order.cua_hang }}</td>
                                        <td class="px-4 py-3 font-bold text-slate-900">{{ formatCurrency(order.tong_thanhtoan) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                :class="[
                                                    'inline-block rounded-full px-2.5 py-1 text-[10px] font-semibold tracking-wide',
                                                    getStatusBadge(order.trang_thai_don_hang).class,
                                                ]"
                                            >
                                                {{ getStatusBadge(order.trang_thai_don_hang).label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right sm:px-6">
                                            <Link
                                                :href="route('admin.orders.show', order.id)"
                                                class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 font-medium text-slate-700 transition-colors hover:bg-emerald-600 hover:text-white"
                                            >
                                                <Eye class="h-3 w-3" />
                                                <span>Xem</span>
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Table Count -->
                    <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/40 px-6 py-3 text-[11px] text-slate-500">
                        <span>Hiển thị {{ recent_orders.length }} đơn hàng phát sinh gần nhất</span>
                        <Link :href="route('admin.orders.index')" class="font-medium text-emerald-600 hover:underline"
                            >Quản lý toàn bộ đơn &rarr;</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
