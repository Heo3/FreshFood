<script setup lang="ts">
import { useCart } from '@/composables/useCart';
import { Link, usePage } from '@inertiajs/vue3';
import { Clock, Home, LayoutGrid, ShoppingBag, User } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const authUser = computed(() => (page.props.auth as any)?.user);
const { totalItems } = useCart();

const navItems = computed(() => [
    {
        name: 'Trang chủ',
        href: route('home'),
        icon: Home,
        active: route().current('home'),
    },
    {
        name: 'Sản phẩm',
        href: route('products.index'),
        icon: LayoutGrid,
        active: route().current('products.*') || route().current('categories.*'),
    },
    {
        name: 'Giỏ hàng',
        href: route('cart.index'),
        icon: ShoppingBag,
        active: route().current('cart.*'),
        badge: totalItems.value > 0 ? (totalItems.value > 99 ? '99+' : totalItems.value) : null,
    },
    {
        name: 'Tra cứu',
        href: route('orders.tracking'),
        icon: Clock,
        active: route().current('orders.tracking'),
    },
    {
        name: authUser.value ? 'Tài khoản' : 'Đăng nhập',
        href: authUser.value ? route('account.index') : route('login'),
        icon: User,
        active:
            route().current('account.*') ||
            route().current('login') ||
            route().current('register'),
    },
]);
</script>

<template>
    <nav
        aria-label="Mobile Navigation"
        class="fixed bottom-0 left-0 right-0 z-40 border-t border-slate-200/90 bg-white/95 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] backdrop-blur-md transition-transform duration-300 lg:hidden"
    >
        <div class="mx-auto flex max-w-md items-center justify-around px-2 py-1.5 pb-safe">
            <Link
                v-for="item in navItems"
                :key="item.name"
                :href="item.href"
                :class="[
                    'relative flex flex-col items-center justify-center rounded-xl px-3 py-1 text-center transition-colors',
                    item.active
                        ? 'font-bold text-emerald-700'
                        : 'font-medium text-slate-500 hover:text-slate-800',
                ]"
            >
                <div class="relative">
                    <component
                        :is="item.icon"
                        :class="[
                            'h-5 w-5 transition-transform',
                            item.active ? 'scale-110 text-emerald-600' : 'text-slate-400',
                        ]"
                    />
                    <span
                        v-if="item.badge"
                        class="absolute -right-2.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[9px] font-black text-white ring-2 ring-white"
                    >
                        {{ item.badge }}
                    </span>
                </div>
                <span class="mt-0.5 text-[10px] leading-tight">
                    {{ item.name }}
                </span>
                <span
                    v-if="item.active"
                    class="absolute -bottom-1 h-0.5 w-4 rounded-full bg-emerald-600"
                />
            </Link>
        </div>
    </nav>
</template>

<style scoped>
.pb-safe {
    padding-bottom: max(0.375rem, env(safe-area-inset-bottom, 0.375rem));
}
</style>
