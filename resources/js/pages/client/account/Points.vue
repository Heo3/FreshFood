<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import {
    Coins,
    Award,
    Gift,
    Sparkles,
    ChevronRight,
    ArrowUpRight,
    ArrowDownLeft,
} from 'lucide-vue-next';

defineProps<{
    points: number;
    history: {
        data: Array<any>;
        links: Array<any>;
        total: number;
    };
}>();

const typeMap: Record<string, { label: string; isPositive: boolean }> = {
    tich_diem: { label: 'Tích điểm đơn hàng', isPositive: true },
    tieu_diem: { label: 'Tiêu điểm thanh toán', isPositive: false },
    hoan_diem: { label: 'Hoàn điểm trả hàng', isPositive: true },
    admin_dieu_chinh: { label: 'Điều chỉnh hệ thống', isPositive: true },
};
</script>

<template>
    <StoreLayout title="Điểm Thưởng & Lịch Sử Tích Điểm">
        <!-- BREADCRUMBS -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <Link :href="route('account.index')" class="hover:text-emerald-600 transition-colors">Tài khoản</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Điểm thưởng thành viên</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- SIDEBAR -->
                <div class="lg:col-span-1">
                    <AccountSidebar activeTab="points" />
                </div>

                <!-- MAIN POINTS CONTENT -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- LOYALTY CARD BANNER -->
                    <div class="rounded-3xl p-8 bg-gradient-to-br from-amber-500 via-amber-600 to-amber-700 text-white shadow-xl relative overflow-hidden">
                        <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

                        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                            <div>
                                <span class="inline-flex items-center text-xs font-bold bg-white/20 px-3 py-1 rounded-full uppercase tracking-wider backdrop-blur-md mb-2">
                                    <Sparkles class="w-3.5 h-3.5 mr-1" /> Thẻ Thành Viên Fresh VIP
                                </span>
                                <h1 class="text-3xl sm:text-4xl font-black mt-1">
                                    {{ points }} <span class="text-lg font-bold">Điểm</span>
                                </h1>
                                <p class="text-xs text-amber-100 mt-1">
                                    Tương đương giá trị thanh toán: <strong>{{ new Intl.NumberFormat('vi-VN').format(points * 1000) }}đ</strong>
                                </p>
                            </div>

                            <div class="bg-white/15 p-4 rounded-2xl border border-white/20 backdrop-blur-md text-xs space-y-1.5 max-w-xs">
                                <p class="font-bold">Quy tắc tích & tiêu điểm:</p>
                                <p class="text-amber-100">• 1 Điểm = 1.000đ khi thanh toán đơn hàng.</p>
                                <p class="text-amber-100">• Tích 5% giá trị mỗi đơn hàng hoàn thành.</p>
                                <p class="text-amber-100">• Trừ trực tiếp tại bước đặt hàng.</p>
                            </div>
                        </div>
                    </div>

                    <!-- TRANSACTION HISTORY -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                        <h3 class="font-black text-sm text-gray-900 pb-3 border-b border-gray-100 flex items-center">
                            <Coins class="w-4 h-4 mr-1.5 text-amber-500" /> Lịch Sử Biến Động Điểm
                        </h3>

                        <div v-if="history.data.length > 0" class="divide-y divide-gray-100 text-xs">
                            <div
                                v-for="h in history.data"
                                :key="h.id"
                                class="py-3.5 flex items-center justify-between"
                            >
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="w-9 h-9 rounded-xl flex items-center justify-center font-bold shrink-0"
                                        :class="h.so_diem_thay_doi > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'"
                                    >
                                        <ArrowUpRight v-if="h.so_diem_thay_doi > 0" class="w-4 h-4" />
                                        <ArrowDownLeft v-else class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">
                                            {{ typeMap[h.loai_giao_dich]?.label || h.loai_giao_dich }}
                                        </p>
                                        <p class="text-gray-400 text-[11px]">
                                            {{ h.ghi_chu || '' }} • {{ new Date(h.created_at).toLocaleString('vi-VN') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <span
                                        class="text-sm font-black"
                                        :class="h.so_diem_thay_doi > 0 ? 'text-emerald-600' : 'text-red-600'"
                                    >
                                        {{ h.so_diem_thay_doi > 0 ? '+' : '' }}{{ h.so_diem_thay_doi }} điểm
                                    </span>
                                    <p class="text-[10px] text-gray-400">Số dư sau: {{ h.so_diem_sau_giao_dich }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-xs text-gray-400">
                            Chưa có giao dịch tích hoặc tiêu điểm nào.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
