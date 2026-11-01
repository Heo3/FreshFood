<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import {
    ShoppingBag,
    Coins,
    Package,
    ArrowRight,
    ChevronRight,
} from 'lucide-vue-next';

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
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Tài khoản cá nhân</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- SIDEBAR -->
                <div class="lg:col-span-1">
                    <AccountSidebar activeTab="index" />
                </div>

                <!-- MAIN OVERVIEW CONTENT -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- WELCOME BANNER -->
                    <div class="bg-gradient-to-r from-emerald-800 to-teal-800 text-white p-6 rounded-3xl shadow-md flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-black">Xin chào, {{ user?.name }}!</h2>
                            <p class="text-xs text-emerald-100/90 mt-1">
                                Cảm ơn bạn đã luôn tin chọn thực phẩm sạch, an toàn cho gia đình tại Fresh Food.
                            </p>
                        </div>
                    </div>

                    <!-- STATS CARDS -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <Package class="w-6 h-6" />
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 font-medium">Tổng đơn hàng</span>
                                <p class="text-xl font-black text-gray-900 mt-0.5">{{ stats.totalOrders }}</p>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                <Coins class="w-6 h-6" />
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 font-medium">Điểm tích lũy</span>
                                <p class="text-xl font-black text-amber-600 mt-0.5">{{ stats.points }} điểm</p>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center">
                                <ShoppingBag class="w-6 h-6" />
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 font-medium">Tổng chi tiêu</span>
                                <p class="text-lg font-black text-emerald-700 mt-0.5">{{ formatPrice(stats.totalSpent) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- RECENT ORDERS -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <h3 class="font-black text-sm text-gray-900">Đơn Hàng Gần Đây</h3>
                            <Link :href="route('account.orders')" class="text-xs font-bold text-emerald-600 hover:underline flex items-center">
                                Xem tất cả <ArrowRight class="w-3.5 h-3.5 ml-1" />
                            </Link>
                        </div>

                        <div v-if="recentOrders.length > 0" class="divide-y divide-gray-100">
                            <div
                                v-for="order in recentOrders"
                                :key="order.id"
                                class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                            >
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <strong class="font-mono text-sm text-gray-900">{{ order.ma_don_hang }}</strong>
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                            :class="statusMap[order.trang_thai_don_hang]?.class || 'bg-gray-100 text-gray-700'"
                                        >
                                            {{ statusMap[order.trang_thai_don_hang]?.label || order.trang_thai_don_hang }}
                                        </span>
                                    </div>
                                    <p class="text-gray-400 text-[11px] mt-1">
                                        {{ order.chi_tiets?.length || 0 }} sản phẩm • Ngày đặt: {{ new Date(order.created_at).toLocaleDateString('vi-VN') }}
                                    </p>
                                </div>

                                <div class="flex items-center space-x-4">
                                    <span class="font-black text-sm text-emerald-700">{{ formatPrice(order.tong_thanhtoan) }}</span>
                                    <Link
                                        :href="route('account.orders.show', { maDonHang: order.ma_don_hang })"
                                        class="px-3 py-1.5 rounded-xl bg-gray-50 hover:bg-emerald-50 text-emerald-700 font-bold transition-colors"
                                    >
                                        Chi tiết
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-xs text-gray-400">
                            Bạn chưa có đơn hàng nào gần đây.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
