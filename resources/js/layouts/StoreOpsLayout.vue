<script setup lang="ts">
import ToastContainer from '@/components/ui/ToastContainer.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    ArrowLeftRight,
    CheckCircle2,
    ClipboardCheck,
    FileInput,
    FileOutput,
    LogOut,
    Package,
    RotateCcw,
    ShoppingCart,
    Store,
    Truck,
    XCircle,
} from 'lucide-vue-next';

const props = defineProps<{
    title?: string;
    stores?: Array<any>;
    currentStore?: any;
}>();

const page = usePage();
const authUser = page.props.auth?.user as any;

const navItems = [
    { name: 'Thu Ngân POS', href: route('store.pos.index'), icon: ShoppingCart, active: 'store.pos.*' },
    { name: 'Tồn Kho & Lô Hàng', href: route('store.inventory.index'), icon: Package, active: 'store.inventory.*' },
    { name: 'Cảnh Báo Cận Date', href: route('store.batches.expiry'), icon: AlertTriangle, active: 'store.batches.*' },
    { name: 'Phiếu Nhập Kho', href: route('store.inbound.index'), icon: FileInput, active: 'store.inbound.*' },
    { name: 'Phiếu Xuất Kho', href: route('store.outbound.index'), icon: FileOutput, active: 'store.outbound.*' },
    { name: 'Đội Xe & Giao Hàng', href: route('store.delivery.fleet'), icon: Truck, active: 'store.delivery.*' },
    { name: 'Điều Chuyển Chi Nhánh', href: route('store.transfer.create'), icon: ArrowLeftRight, active: 'store.transfer.*' },
    { name: 'Kiểm Kê Kho', href: route('store.audit.index'), icon: ClipboardCheck, active: 'store.audit.*' },
    { name: 'Xử Lý Đổi Trả', href: route('store.returns.index'), icon: RotateCcw, active: 'store.returns.*' },
];

function isRouteActive(pattern: string): boolean {
    return route().current(pattern);
}

function handleSwitchStore(event: Event) {
    const target = event.target as HTMLSelectElement;
    const storeId = target.value;
    router.get(window.location.pathname, { store_id: storeId }, { preserveState: false });
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-800 antialiased">
        <Head :title="title ? `${title} - Vận Hành Chi Nhánh Fresh Food` : 'Fresh Food Store Operations'" />

        <!-- TOP BAR -->
        <header class="sticky top-0 z-40 border-b border-slate-800 bg-slate-900 text-white shadow-md">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <!-- LOGO & BRAND -->
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 text-white shadow-lg shadow-emerald-900/30"
                    >
                        <Store class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-base font-black tracking-wide text-white">FRESH FOOD</span>
                            <span
                                class="rounded border border-emerald-500/30 bg-emerald-500/20 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-400"
                            >
                                Cửa Hàng & Kho
                            </span>
                        </div>
                        <p class="flex items-center gap-1 text-[11px] font-medium text-slate-400">
                            <span>Chi nhánh:</span>
                            <strong class="font-bold text-white">{{ currentStore?.ten_cua_hang ?? 'Chi nhánh mặc định' }}</strong>
                        </p>
                    </div>
                </div>

                <!-- STORE SELECTOR (FOR ADMIN ONLY) -->
                <div class="flex items-center space-x-3">
                    <div
                        v-if="stores && stores.length > 1 && authUser?.role === 'admin'"
                        class="flex items-center space-x-2 rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-1.5"
                    >
                        <Store class="h-3.5 w-3.5 text-emerald-400" />
                        <span class="text-xs font-medium text-slate-300">Đổi CN:</span>
                        <select
                            :value="currentStore?.id"
                            @change="handleSwitchStore"
                            class="cursor-pointer bg-transparent pr-4 text-xs font-bold text-white focus:outline-none"
                        >
                            <option v-for="s in stores" :key="s.id" :value="s.id" class="bg-slate-800 text-white">
                                {{ s.ten_cua_hang }}
                            </option>
                        </select>
                    </div>
                    <div
                        v-else-if="currentStore"
                        class="hidden items-center space-x-1.5 rounded-xl border border-slate-700/60 bg-slate-800/80 px-3 py-1.5 text-xs text-slate-300 sm:flex"
                    >
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        <span class="font-medium text-emerald-400">{{ currentStore.ten_cua_hang }}</span>
                    </div>

                    <!-- USER INFO & SHORTCUTS -->
                    <div class="flex items-center space-x-2 border-l border-slate-800 pl-3">
                        <div class="hidden text-right sm:block">
                            <p class="text-xs font-bold text-white">{{ authUser?.name ?? 'Nhân viên' }}</p>
                            <p class="text-[10px] font-medium capitalize text-slate-400">{{ authUser?.role?.replace('_', ' ') }}</p>
                        </div>
                        <Link
                            :href="route('home')"
                            class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                            title="Về Storefront Khách Hàng"
                        >
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-800 hover:text-red-400"
                            title="Đăng xuất"
                        >
                            <LogOut class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- SECONDARY NAVIGATION BAR -->
            <nav class="overflow-x-auto border-t border-slate-800/80 bg-slate-900/90 backdrop-blur">
                <div class="mx-auto flex max-w-7xl space-x-1 px-4 py-1.5 sm:space-x-2 sm:px-6 lg:px-8">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            isRouteActive(item.active)
                                ? 'bg-emerald-500 font-bold text-white shadow-md shadow-emerald-950'
                                : 'font-medium text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            'flex items-center space-x-2 whitespace-nowrap rounded-lg px-3.5 py-1.5 text-xs transition-all duration-150',
                        ]"
                    >
                        <component :is="item.icon" class="h-3.5 w-3.5" />
                        <span>{{ item.name }}</span>
                    </Link>
                </div>
            </nav>
        </header>

        <!-- FLASH SUCCESS / ERROR ALERTS -->
        <div v-if="$page.props.flash?.success" class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                class="flex items-center space-x-2.5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 shadow-sm"
            >
                <CheckCircle2 class="h-5 w-5 shrink-0 text-emerald-600" />
                <span>{{ $page.props.flash.success }}</span>
            </div>
        </div>

        <div v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-start space-x-2.5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800 shadow-sm">
                <XCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
                <div>
                    <p class="font-bold">Có lỗi xảy ra:</p>
                    <ul class="mt-1 list-inside list-disc space-y-0.5">
                        <li v-for="(err, key) in $page.props.errors" :key="key">{{ err }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT AREA -->
        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <slot />
        </main>

        <!-- GLOBAL TOAST NOTIFICATION CONTAINER -->
        <ToastContainer />
    </div>
</template>
