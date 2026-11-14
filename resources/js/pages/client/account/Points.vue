<script setup lang="ts">
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, ChevronRight, Coins, Sparkles } from 'lucide-vue-next';

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
    hoan_diem: { label: 'Hoàn điểm đơn hàng', isPositive: true },
    admin_dieu_chinh: { label: 'Thu hồi / Điều chỉnh điểm', isPositive: false },
};
</script>

<template>
    <StoreLayout title="Điểm Thưởng & Lịch Sử Tích Điểm">
        <!-- BREADCRUMBS -->
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-7xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <Link :href="route('account.index')" class="transition-colors hover:text-emerald-600">Tài khoản</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">Điểm thưởng thành viên</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <!-- SIDEBAR -->
                <div class="lg:col-span-1">
                    <AccountSidebar activeTab="points" />
                </div>

                <!-- MAIN POINTS CONTENT -->
                <div class="space-y-6 lg:col-span-3">
                    <!-- LOYALTY CARD BANNER -->
                    <div
                        class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-500 via-amber-600 to-amber-700 p-8 text-white shadow-xl"
                    >
                        <div class="pointer-events-none absolute -bottom-8 -right-8 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>

                        <div class="relative z-10 flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                            <div>
                                <span
                                    class="mb-2 inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-bold uppercase tracking-wider backdrop-blur-md"
                                >
                                    <Sparkles class="mr-1 h-3.5 w-3.5" /> Thẻ Thành Viên Fresh VIP
                                </span>
                                <h1 class="mt-1 text-3xl font-black sm:text-4xl">{{ points }} <span class="text-lg font-bold">Điểm</span></h1>
                                <p class="mt-1 text-xs text-amber-100">
                                    Tương đương giá trị thanh toán: <strong>{{ new Intl.NumberFormat('vi-VN').format(points * 1000) }}đ</strong>
                                </p>
                            </div>

                            <div class="max-w-xs space-y-1.5 rounded-2xl border border-white/20 bg-white/15 p-4 text-xs backdrop-blur-md">
                                <p class="font-bold">Quy tắc tích & tiêu điểm:</p>
                                <p class="text-amber-100">• 1 Điểm = 1.000đ khi thanh toán đơn hàng.</p>
                                <p class="text-amber-100">• Tích 1 điểm cho mỗi 10.000đ tiền thực trả.</p>
                                <p class="text-amber-100">• Trừ trực tiếp tại bước đặt hàng.</p>
                            </div>
                        </div>
                    </div>

                    <!-- TRANSACTION HISTORY -->
                    <div class="space-y-4 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h3 class="flex items-center border-b border-gray-100 pb-3 text-sm font-black text-gray-900">
                            <Coins class="mr-1.5 h-4 w-4 text-amber-500" /> Lịch Sử Biến Động Điểm
                        </h3>

                        <div v-if="history.data.length > 0" class="divide-y divide-gray-100 text-xs">
                            <div v-for="h in history.data" :key="h.id" class="flex items-center justify-between py-3.5">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold"
                                        :class="h.so_diem_thay_doi > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'"
                                    >
                                        <ArrowUpRight v-if="h.so_diem_thay_doi > 0" class="h-4 w-4" />
                                        <ArrowDownLeft v-else class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">
                                            {{ typeMap[h.loai_giao_dich]?.label || h.loai_giao_dich }}
                                        </p>
                                        <p class="text-[11px] text-gray-400">
                                            {{ h.ghi_chu || '' }} • {{ new Date(h.created_at).toLocaleString('vi-VN') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <span class="text-sm font-black" :class="h.so_diem_thay_doi > 0 ? 'text-emerald-600' : 'text-red-600'">
                                        {{ h.so_diem_thay_doi > 0 ? '+' : '' }}{{ h.so_diem_thay_doi }} điểm
                                    </span>
                                    <p class="text-[10px] text-gray-400">Số dư sau: {{ h.so_diem_sau_giao_dich }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-8 text-center text-xs text-gray-400">Chưa có giao dịch tích hoặc tiêu điểm nào.</div>
                    </div>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
