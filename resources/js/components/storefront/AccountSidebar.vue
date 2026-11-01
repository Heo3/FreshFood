<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Package,
    MapPin,
    Coins,
    User,
    LogOut,
} from 'lucide-vue-next';

defineProps<{
    activeTab?: string;
}>();

const page = usePage();
const user = page.props.auth?.user as any;
</script>

<template>
    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-6">
        <!-- USER PROFILE BRIEF -->
        <div class="flex items-center space-x-3 pb-4 border-b border-gray-100">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 font-black flex items-center justify-center text-lg shadow-sm border border-emerald-200">
                {{ user?.name?.charAt(0) || 'U' }}
            </div>
            <div class="min-w-0">
                <h4 class="font-bold text-sm text-gray-900 truncate">{{ user?.name }}</h4>
                <p class="text-xs text-amber-600 font-bold flex items-center mt-0.5">
                    ★ {{ user?.loyalty_points || 0 }} Điểm thưởng
                </p>
            </div>
        </div>

        <!-- NAV LINKS -->
        <nav class="space-y-1 text-xs font-semibold">
            <Link
                :href="route('account.index')"
                class="flex items-center space-x-2.5 px-3.5 py-2.5 rounded-xl transition-colors"
                :class="activeTab === 'index' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
                <LayoutDashboard class="w-4 h-4 text-emerald-600" />
                <span>Tổng quan tài khoản</span>
            </Link>

            <Link
                :href="route('account.orders')"
                class="flex items-center space-x-2.5 px-3.5 py-2.5 rounded-xl transition-colors"
                :class="activeTab === 'orders' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
                <Package class="w-4 h-4 text-emerald-600" />
                <span>Lịch sử đơn hàng</span>
            </Link>

            <Link
                :href="route('account.addresses')"
                class="flex items-center space-x-2.5 px-3.5 py-2.5 rounded-xl transition-colors"
                :class="activeTab === 'addresses' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
                <MapPin class="w-4 h-4 text-emerald-600" />
                <span>Sổ địa chỉ nhận hàng</span>
            </Link>

            <Link
                :href="route('account.points')"
                class="flex items-center space-x-2.5 px-3.5 py-2.5 rounded-xl transition-colors"
                :class="activeTab === 'points' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
                <Coins class="w-4 h-4 text-amber-500" />
                <span>Điểm thưởng & Lịch sử</span>
            </Link>

            <Link
                :href="route('profile.edit')"
                class="flex items-center space-x-2.5 px-3.5 py-2.5 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors"
            >
                <User class="w-4 h-4 text-gray-400" />
                <span>Cài đặt thông tin</span>
            </Link>

            <div class="pt-3 border-t border-gray-100">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center space-x-2.5 px-3.5 py-2 rounded-xl text-red-600 hover:bg-red-50 transition-colors text-left font-bold"
                >
                    <LogOut class="w-4 h-4" />
                    <span>Đăng xuất</span>
                </Link>
            </div>
        </nav>
    </div>
</template>
