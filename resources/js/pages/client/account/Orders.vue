<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import { Package, ChevronRight, PackageX } from 'lucide-vue-next';

const props = defineProps<{
    orders: {
        data: Array<any>;
        links: Array<any>;
        total: number;
        last_page: number;
    };
    currentStatus: string;
}>();

const tabs = [
    { key: 'all', label: 'Tất cả' },
    { key: 'cho_xac_nhan', label: 'Chờ xác nhận' },
    { key: 'dang_chuan_bi', label: 'Đang chuẩn bị' },
    { key: 'dang_giao', label: 'Đang giao' },
    { key: 'da_giao', label: 'Đã hoàn thành' },
    { key: 'da_huy', label: 'Đã hủy' },
];

function setStatus(statusKey: string) {
    router.get(route('account.orders'), { status: statusKey }, { preserveState: true });
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

const statusMap: Record<string, { label: string; class: string }> = {
    cho_xac_nhan: { label: 'Chờ xác nhận', class: 'bg-amber-100 text-amber-800' },
    dang_chuan_bi: { label: 'Đang chuẩn bị', class: 'bg-blue-100 text-blue-800' },
    dang_giao: { label: 'Đang giao hàng', class: 'bg-indigo-100 text-indigo-800' },
    da_giao: { label: 'Đã hoàn thành', class: 'bg-emerald-100 text-emerald-800' },
    da_huy: { label: 'Đã hủy', class: 'bg-red-100 text-red-800' },
};
</script>

<template>
    <StoreLayout title="Lịch Sử Đơn Hàng">
        <!-- BREADCRUMBS -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <Link :href="route('account.index')" class="hover:text-emerald-600 transition-colors">Tài khoản</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Đơn hàng của tôi</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- SIDEBAR -->
                <div class="lg:col-span-1">
                    <AccountSidebar activeTab="orders" />
                </div>

                <!-- ORDERS LIST -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- STATUS TABS -->
                    <div class="bg-white p-2 rounded-2xl border border-gray-100 shadow-sm flex items-center space-x-1 overflow-x-auto text-xs font-bold">
                        <button
                            v-for="t in tabs"
                            :key="t.key"
                            @click="setStatus(t.key)"
                            class="px-4 py-2 rounded-xl transition-all whitespace-nowrap"
                            :class="currentStatus === t.key ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            {{ t.label }}
                        </button>
                    </div>

                    <!-- ORDER CARDS -->
                    <div v-if="orders.data.length > 0" class="space-y-4">
                        <div
                            v-for="order in orders.data"
                            :key="order.id"
                            class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4 text-xs"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-400">Mã đơn:</span>
                                    <strong class="font-mono text-gray-900 text-sm">{{ order.ma_don_hang }}</strong>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-gray-500">{{ new Date(order.created_at).toLocaleDateString('vi-VN') }}</span>
                                </div>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider w-fit"
                                    :class="statusMap[order.trang_thai_don_hang]?.class || 'bg-gray-100 text-gray-700'"
                                >
                                    {{ statusMap[order.trang_thai_don_hang]?.label || order.trang_thai_don_hang }}
                                </span>
                            </div>

                            <!-- Items preview -->
                            <div class="space-y-2">
                                <div
                                    v-for="item in order.chi_tiets"
                                    :key="item.id"
                                    class="flex items-center justify-between py-1"
                                >
                                    <div class="flex items-center space-x-3">
                                        <img
                                            :src="item.hinh_anh_snapshot || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=200'"
                                            :alt="item.ten_san_pham"
                                            class="w-10 h-10 rounded-xl object-cover bg-gray-50"
                                        />
                                        <div>
                                            <p class="font-bold text-gray-900">{{ item.ten_san_pham }}</p>
                                            <p class="text-gray-400 text-[11px]">{{ item.so_luong }} × {{ formatPrice(item.don_gia) }}</p>
                                        </div>
                                    </div>
                                    <span class="font-bold text-gray-800">{{ formatPrice(item.thanh_tien) }}</span>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-gray-100 flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                                <span class="text-gray-500">
                                    Chi nhánh: <strong class="text-gray-800">{{ order.cua_hang?.ten_cua_hang || 'Fresh Food' }}</strong>
                                </span>
                                <div class="flex items-center justify-between sm:justify-end space-x-4">
                                    <div class="text-right">
                                        <span class="text-gray-500 text-[11px]">Tổng đơn: </span>
                                        <span class="font-black text-sm text-emerald-700">{{ formatPrice(order.tong_thanhtoan) }}</span>
                                    </div>
                                    <Link
                                        :href="route('account.orders.show', { maDonHang: order.ma_don_hang })"
                                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition-colors shadow-sm"
                                    >
                                        Chi tiết đơn hàng
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EMPTY ORDERS -->
                    <div v-else class="bg-white rounded-3xl p-16 text-center border border-gray-100 shadow-sm">
                        <PackageX class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <h4 class="text-sm font-bold text-gray-800 mb-1">Không có đơn hàng nào</h4>
                        <p class="text-xs text-gray-400">Bạn chưa có đơn hàng nào ở trạng thái này.</p>
                    </div>

                    <!-- PAGINATION -->
                    <div v-if="orders.last_page > 1" class="flex justify-center space-x-1">
                        <Link
                            v-for="(link, i) in orders.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-colors"
                            :class="{
                                'bg-emerald-600 text-white shadow-sm': link.active,
                                'bg-white text-gray-700 hover:bg-gray-100 border border-gray-100': !link.active && link.url,
                                'text-gray-300 pointer-events-none': !link.url
                            }"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
