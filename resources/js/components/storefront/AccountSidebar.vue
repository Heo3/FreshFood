<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Coins, LayoutDashboard, LogOut, MapPin, Package, User } from 'lucide-vue-next';

defineProps<{
    activeTab?: string;
}>();

const page = usePage();
const user = page.props.auth?.user as any;
</script>

<template>
    <div class="space-y-6 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
        <!-- USER PROFILE BRIEF -->
        <div class="flex items-center space-x-3 border-b border-gray-100 pb-4">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-100 text-lg font-black text-emerald-700 shadow-sm"
            >
                {{ user?.name?.charAt(0) || 'U' }}
            </div>
            <div class="min-w-0">
                <h4 class="truncate text-sm font-bold text-gray-900">{{ user?.name }}</h4>
                <p class="mt-0.5 flex items-center text-xs font-bold text-amber-600">★ {{ user?.loyalty_points || 0 }} Điểm thưởng</p>
            </div>
        </div>

        <!-- NAV LINKS -->
        <nav class="space-y-1 text-xs font-semibold">
            <Link
                :href="route('account.index')"
                class="flex items-center space-x-2.5 rounded-xl px-3.5 py-2.5 transition-colors"
                :class="activeTab === 'index' ? 'bg-emerald-50 font-bold text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
                <LayoutDashboard class="h-4 w-4 text-emerald-600" />
                <span>Tổng quan tài khoản</span>
            </Link>

            <Link
                :href="route('account.orders')"
                class="flex items-center space-x-2.5 rounded-xl px-3.5 py-2.5 transition-colors"
                :class="activeTab === 'orders' ? 'bg-emerald-50 font-bold text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
                <Package class="h-4 w-4 text-emerald-600" />
                <span>Lịch sử đơn hàng</span>
            </Link>

            <Link
                :href="route('account.addresses')"
                class="flex items-center space-x-2.5 rounded-xl px-3.5 py-2.5 transition-colors"
                :class="activeTab === 'addresses' ? 'bg-emerald-50 font-bold text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
                <MapPin class="h-4 w-4 text-emerald-600" />
                <span>Sổ địa chỉ nhận hàng</span>
            </Link>

            <Link
                :href="route('account.points')"
                class="flex items-center space-x-2.5 rounded-xl px-3.5 py-2.5 transition-colors"
                :class="activeTab === 'points' ? 'bg-emerald-50 font-bold text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
                <Coins class="h-4 w-4 text-amber-500" />
                <span>Điểm thưởng & Lịch sử</span>
            </Link>

            <Link
                :href="route('profile.edit')"
                class="flex items-center space-x-2.5 rounded-xl px-3.5 py-2.5 transition-colors"
                :class="activeTab === 'profile' ? 'bg-emerald-50 font-bold text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
                <User class="h-4 w-4 text-emerald-600" />
                <span>Cài đặt thông tin</span>
            </Link>

            <div class="border-t border-gray-100 pt-3">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex w-full items-center space-x-2.5 rounded-xl px-3.5 py-2 text-left font-bold text-red-600 transition-colors hover:bg-red-50"
                >
                    <LogOut class="h-4 w-4" />
                    <span>Đăng xuất</span>
                </Link>
            </div>
        </nav>
    </div>
</template>
