<script setup lang="ts">
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronRight, Coins, Package, ShoppingBag } from 'lucide-vue-next';

defineProps<{
    recentOrders: Array<any>;
    stats: {
        totalOrders: number;
        totalSpent: number;
        points: number;
    };
}>();

const page = usePage();
const user = page.props.auth?.user as any;

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

const statusMap: Record<string, { label: string; class: string }> = {
    cho_xac_nhan: { label: 'Chờ xác nhận', class: 'bg-amber-100 text-amber-800' },
    dang_chuan_bi: { label: 'Đang chuẩn bị', class: 'bg-blue-100 text-blue-800' },
    dang_giao: { label: 'Đang giao', class: 'bg-indigo-100 text-indigo-800' },
    da_giao: { label: 'Đã giao', class: 'bg-emerald-100 text-emerald-800' },
    da_huy: { label: 'Đã hủy', class: 'bg-red-100 text-red-800' },
};
</script>

<template>
    <StoreLayout title="Tổng Quan Tài Khoản">
        <!-- BREADCRUMBS -->
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-7xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">Tài khoản cá nhân</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <!-- SIDEBAR -->
                <div class="lg:col-span-1">
                    <AccountSidebar activeTab="index" />
                </div>

                <!-- MAIN OVERVIEW CONTENT -->
                <div class="space-y-6 lg:col-span-3">
                    <!-- WELCOME BANNER -->
                    <div class="flex items-center justify-between rounded-3xl bg-gradient-to-r from-emerald-800 to-teal-800 p-6 text-white shadow-md">
                        <div>
                            <h2 class="text-xl font-black">Xin chào, {{ user?.name }}!</h2>
                            <p class="mt-1 text-xs text-emerald-100/90">
                                Cảm ơn bạn đã luôn tin chọn thực phẩm sạch, an toàn cho gia đình tại Fresh Food.
                            </p>
                        </div>
                    </div>

                    <!-- STATS CARDS -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="flex items-center space-x-4 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                <Package class="h-6 w-6" />
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-400">Tổng đơn hàng</span>
                                <p class="mt-0.5 text-xl font-black text-gray-900">{{ stats.totalOrders }}</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                                <Coins class="h-6 w-6" />
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-400">Điểm tích lũy</span>
                                <p class="mt-0.5 text-xl font-black text-amber-600">{{ stats.points }} điểm</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-600">
                                <ShoppingBag class="h-6 w-6" />
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-400">Tổng chi tiêu</span>
                                <p class="mt-0.5 text-lg font-black text-emerald-700">{{ formatPrice(stats.totalSpent) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- RECENT ORDERS -->
                    <div class="space-y-4 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <h3 class="text-sm font-black text-gray-900">Đơn Hàng Gần Đây</h3>
                            <Link :href="route('account.orders')" class="flex items-center text-xs font-bold text-emerald-600 hover:underline">
                                Xem tất cả <ArrowRight class="ml-1 h-3.5 w-3.5" />
                            </Link>
                        </div>

                        <div v-if="recentOrders.length > 0" class="divide-y divide-gray-100">
                            <div
                                v-for="order in recentOrders"
                                :key="order.id"
                                class="flex flex-col justify-between gap-3 py-4 text-xs sm:flex-row sm:items-center"
                            >
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <strong class="font-mono text-sm text-gray-900">{{ order.ma_don_hang }}</strong>
                                        <span
                                            class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                            :class="statusMap[order.trang_thai_don_hang]?.class || 'bg-gray-100 text-gray-700'"
                                        >
                                            {{ statusMap[order.trang_thai_don_hang]?.label || order.trang_thai_don_hang }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-[11px] text-gray-400">
                                        {{ order.chi_tiets?.length || 0 }} sản phẩm • Ngày đặt:
                                        {{ new Date(order.created_at).toLocaleDateString('vi-VN') }}
                                    </p>
                                </div>

                                <div class="flex items-center space-x-4">
                                    <span class="text-sm font-black text-emerald-700">{{ formatPrice(order.tong_thanhtoan) }}</span>
                                    <Link
                                        :href="route('account.orders.show', { maDonHang: order.ma_don_hang })"
                                        class="rounded-xl bg-gray-50 px-3 py-1.5 font-bold text-emerald-700 transition-colors hover:bg-emerald-50"
                                    >
                                        Chi tiết
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-8 text-center text-xs text-gray-400">Bạn chưa có đơn hàng nào gần đây.</div>
                    </div>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
