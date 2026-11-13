<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    Store,
    ShoppingCart,
    Package,
    AlertTriangle,
    FileInput,
    FileOutput,
    ClipboardCheck,
    RotateCcw,
    LogOut,
    ArrowLeft,
    ArrowLeftRight,
    Truck,
    CheckCircle2,
    XCircle,
    Info,
    ChevronDown,
} from 'lucide-vue-next';
import ToastContainer from '@/components/ui/ToastContainer.vue';

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
    <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800 antialiased">
        <Head :title="title ? `${title} - Vận Hành Chi Nhánh Fresh Food` : 'Fresh Food Store Operations'" />

        <!-- TOP BAR -->
        <header class="bg-slate-900 text-white sticky top-0 z-40 border-b border-slate-800 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- LOGO & BRAND -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white shadow-lg shadow-emerald-900/30">
                        <Store class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-black text-base tracking-wide text-white">FRESH FOOD</span>
                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                Cửa Hàng & Kho
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 flex items-center gap-1 font-medium">
                            <span>Chi nhánh:</span>
                            <strong class="text-white font-bold">{{ currentStore?.ten_cua_hang ?? 'Chi nhánh mặc định' }}</strong>
                        </p>
                    </div>
                </div>

                <!-- STORE SELECTOR (FOR ADMIN ONLY) -->
                <div class="flex items-center space-x-3">
                    <div v-if="stores && stores.length > 1 && authUser?.role === 'admin'" class="flex items-center space-x-2 bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-700">
                        <Store class="w-3.5 h-3.5 text-emerald-400" />
                        <span class="text-xs text-slate-300 font-medium">Đổi CN:</span>
                        <select
                            :value="currentStore?.id"
                            @change="handleSwitchStore"
                            class="bg-transparent text-xs text-white font-bold focus:outline-none cursor-pointer pr-4"
                        >
                            <option v-for="s in stores" :key="s.id" :value="s.id" class="bg-slate-800 text-white">
                                {{ s.ten_cua_hang }}
                            </option>
                        </select>
                    </div>
                    <div v-else-if="currentStore" class="hidden sm:flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/60 text-xs text-slate-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="font-medium text-emerald-400">{{ currentStore.ten_cua_hang }}</span>
                    </div>

                    <!-- USER INFO & SHORTCUTS -->
                    <div class="flex items-center space-x-2 border-l border-slate-800 pl-3">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold text-white">{{ authUser?.name ?? 'Nhân viên' }}</p>
                            <p class="text-[10px] text-slate-400 capitalize font-medium">{{ authUser?.role?.replace('_', ' ') }}</p>
                        </div>
                        <Link
                            :href="route('home')"
                            class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition"
                            title="Về Storefront Khách Hàng"
                        >
                            <ArrowLeft class="w-4 h-4" />
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="p-2 rounded-xl text-slate-400 hover:text-red-400 hover:bg-slate-800 transition"
                            title="Đăng xuất"
                        >
                            <LogOut class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- SECONDARY NAVIGATION BAR -->
            <nav class="bg-slate-900/90 backdrop-blur border-t border-slate-800/80 overflow-x-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex space-x-1 sm:space-x-2 py-1.5">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            isRouteActive(item.active)
                                ? 'bg-emerald-500 text-white shadow-md shadow-emerald-950 font-bold'
                                : 'text-slate-300 hover:text-white hover:bg-slate-800/80 font-medium',
                            'px-3.5 py-1.5 rounded-lg text-xs flex items-center space-x-2 whitespace-nowrap transition-all duration-150'
                        ]"
                    >
                        <component :is="item.icon" class="w-3.5 h-3.5" />
                        <span>{{ item.name }}</span>
                    </Link>
                </div>
            </nav>
        </header>

        <!-- FLASH SUCCESS / ERROR ALERTS -->
        <div v-if="$page.props.flash?.success" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center space-x-2.5 shadow-sm text-xs font-semibold">
                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                <span>{{ $page.props.flash.success }}</span>
            </div>
        </div>

        <div v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl flex items-start space-x-2.5 shadow-sm text-xs">
                <XCircle class="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
                <div>
                    <p class="font-bold">Có lỗi xảy ra:</p>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        <li v-for="(err, key) in $page.props.errors" :key="key">{{ err }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <slot />
        </main>

        <!-- GLOBAL TOAST NOTIFICATION CONTAINER -->
        <ToastContainer />
    </div>
</template>

