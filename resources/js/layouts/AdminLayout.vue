<script setup lang="ts">
import ToastContainer from '@/components/ui/ToastContainer.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Boxes,
    CheckCircle2,
    ChevronRight,
    ExternalLink,
    FolderTree,
    Headphones,
    Home,
    Image,
    LayoutDashboard,
    LogOut,
    Maximize,
    Menu,
    MessageSquare,
    Minimize,
    Search,
    ShoppingBag,
    Store,
    Store as StoreIcon,
    TicketPercent,
    Truck,
    Users,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    title?: string;
    breadcrumbs?: Array<{ name: string; href?: string }>;
}>();

const page = usePage();
const authUser = page.props.auth?.user as any;

// State for sidebar toggle
const isMobileSidebarOpen = ref(false);
const isDesktopSidebarCollapsed = ref(false);
const sidebarSearch = ref('');
const isFullscreen = ref(false);

function toggleDesktopSidebar() {
    isDesktopSidebarCollapsed.value = !isDesktopSidebarCollapsed.value;
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
        isFullscreen.value = true;
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(() => {});
        }
        isFullscreen.value = false;
    }
}

const mainNav = [
    { name: 'Dashboard', href: route('admin.dashboard'), icon: LayoutDashboard, pattern: 'admin.dashboard' },
    { name: 'Quản lý Sản phẩm', href: route('admin.products.index'), icon: Boxes, pattern: 'admin.products.*' },
    { name: 'Danh mục Hàng hóa', href: route('admin.categories.index'), icon: FolderTree, pattern: 'admin.categories.*' },
    { name: 'Quản lý Đơn hàng', href: route('admin.orders.index'), icon: ShoppingBag, pattern: 'admin.orders.*' },
    { name: 'CSKH & Khiếu nại', href: route('admin.support.index'), icon: Headphones, pattern: 'admin.support.*' },
    { name: 'Khuyến mãi & Voucher', href: route('admin.coupons.index'), icon: TicketPercent, pattern: 'admin.coupons.*' },
    { name: 'Nhà cung cấp', href: route('admin.suppliers.index'), icon: Truck, pattern: 'admin.suppliers.*' },
    { name: 'Đánh giá Khách hàng', href: route('admin.reviews.index'), icon: MessageSquare, pattern: 'admin.reviews.*' },
    { name: 'Banner & Quảng cáo', href: route('admin.banners.index'), icon: Image, pattern: 'admin.banners.*' },
    { name: 'Báo cáo & Thống kê', href: route('admin.reports.index'), icon: BarChart3, pattern: 'admin.reports.*' },
];

const adminOnlyNav = [
    { name: 'Chuỗi Cửa hàng', href: route('admin.stores.index'), icon: Store, pattern: 'admin.stores.*' },
    { name: 'Nhân sự & Phân quyền', href: route('admin.users.index'), icon: Users, pattern: 'admin.users.*' },
];

function isRouteActive(pattern: string): boolean {
    return route().current(pattern);
}

const filteredMainNav = computed(() => {
    if (!sidebarSearch.value.trim()) return mainNav;
    const q = sidebarSearch.value.toLowerCase();
    return mainNav.filter((item) => item.name.toLowerCase().includes(q));
});

const filteredAdminNav = computed(() => {
    if (!sidebarSearch.value.trim()) return adminOnlyNav;
    const q = sidebarSearch.value.toLowerCase();
    return adminOnlyNav.filter((item) => item.name.toLowerCase().includes(q));
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50/70 font-sans text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">
        <Head :title="title ? `${title} | FreshFood Admin` : 'FreshFood Quản Trị Hệ Thống'" />

        <!-- MOBILE BACKDROP -->
        <div
            v-if="isMobileSidebarOpen"
            class="backdrop-blur-xs fixed inset-0 z-40 bg-slate-900/60 transition-opacity lg:hidden"
            @click="isMobileSidebarOpen = false"
        />

        <!-- ============================================== -->
        <!-- 1. MODERN TOP NAVBAR                           -->
        <!-- ============================================== -->
        <header
            :class="[
                'sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur-md transition-all duration-300 sm:px-6',
                isDesktopSidebarCollapsed ? 'lg:pl-[84px]' : 'lg:pl-[276px]',
            ]"
        >
            <!-- LEFT NAVBAR LINKS -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Push Menu / Hamburger toggle -->
                <button
                    class="focus:outline-hidden rounded-xl p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
                    title="Thu gọn menu"
                    @click="
                        isMobileSidebarOpen
                            ? (isMobileSidebarOpen = false)
                            : window.innerWidth < 1024
                              ? (isMobileSidebarOpen = true)
                              : toggleDesktopSidebar()
                    "
                >
                    <Menu class="h-5 w-5" />
                </button>

                <!-- BREADCRUMBS -->
                <nav class="hidden items-center space-x-1.5 pl-1 text-xs text-slate-500 md:flex">
                    <Link
                        :href="route('admin.dashboard')"
                        class="flex items-center gap-1 font-medium text-slate-600 transition-colors hover:text-emerald-600"
                    >
                        <Home class="h-3.5 w-3.5" />
                        <span>Trang chủ</span>
                    </Link>
                    <ChevronRight class="h-3.5 w-3.5 text-slate-300" />
                    <template v-if="breadcrumbs && breadcrumbs.length > 0">
                        <template v-for="(bc, index) in breadcrumbs" :key="bc.name">
                            <Link v-if="bc.href" :href="bc.href" class="font-medium text-slate-600 transition-colors hover:text-emerald-600">
                                {{ bc.name }}
                            </Link>
                            <span v-else class="font-semibold text-slate-900">{{ bc.name }}</span>
                            <ChevronRight v-if="index < breadcrumbs.length - 1" class="h-3.5 w-3.5 text-slate-300" />
                        </template>
                    </template>
                    <span v-else class="max-w-[200px] truncate font-semibold text-slate-900">{{ title || 'Dashboard' }}</span>
                </nav>
            </div>

            <!-- RIGHT NAVBAR CONTROLS & SHORTCUTS -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- SHORTCUT: POS RETAIL -->
                <Link
                    :href="route('store.pos.index')"
                    class="shadow-2xs hidden items-center rounded-xl border border-emerald-200/80 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition-all hover:border-emerald-300 hover:bg-emerald-100 sm:inline-flex"
                >
                    <StoreIcon class="mr-1.5 h-3.5 w-3.5 text-emerald-600" />
                    <span>Thu Ngân POS</span>
                </Link>

                <!-- SHORTCUT: STOREFRONT -->
                <a
                    href="/"
                    target="_blank"
                    class="shadow-2xs hidden items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-all hover:border-slate-300 hover:bg-slate-100 lg:inline-flex"
                >
                    <ExternalLink class="mr-1.5 h-3.5 w-3.5 text-slate-500" />
                    <span>Xem Website</span>
                </a>

                <!-- FULLSCREEN TOGGLE -->
                <button
                    class="rounded-xl p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800"
                    title="Toàn màn hình"
                    @click="toggleFullscreen"
                >
                    <Maximize v-if="!isFullscreen" class="h-4 w-4" />
                    <Minimize v-else class="h-4 w-4" />
                </button>

                <!-- USER PILL & PROFILE -->
                <div class="flex items-center space-x-2.5 border-l border-slate-200 pl-2 sm:pl-3">
                    <div
                        class="shadow-xs flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-xs font-bold text-white"
                    >
                        {{ authUser?.name?.charAt(0) || 'A' }}
                    </div>
                    <div class="hidden text-left text-xs leading-tight sm:block">
                        <div class="max-w-[140px] truncate font-bold text-slate-800">{{ authUser?.name }}</div>
                        <div class="mt-0.5 flex items-center gap-1.5">
                            <span class="py-0.2 rounded-md bg-emerald-50 px-1.5 text-[10px] font-semibold text-emerald-700">
                                {{ authUser?.role === 'admin' ? 'Quản trị viên' : 'Quản lý' }}
                            </span>
                            <span
                                v-if="authUser?.cua_hang?.ten_cua_hang"
                                class="py-0.2 max-w-[110px] truncate rounded-md bg-slate-100 px-1.5 text-[10px] font-medium text-slate-600"
                                :title="authUser?.cua_hang?.ten_cua_hang"
                            >
                                {{ authUser?.cua_hang?.ten_cua_hang }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- LOGOUT BUTTON -->
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="ml-1 rounded-xl p-2 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                    title="Đăng xuất khỏi hệ thống"
                >
                    <LogOut class="h-4 w-4" />
                </Link>
            </div>
        </header>

        <!-- ============================================== -->
        <!-- 2. MODERN SLEEK SIDEBAR                        -->
        <!-- ============================================== -->
        <aside
            :class="[
                'fixed bottom-0 left-0 top-0 z-50 flex select-none flex-col border-r border-slate-800/80 bg-slate-900 text-slate-300 shadow-xl transition-all duration-300 ease-in-out',
                // Mobile
                isMobileSidebarOpen ? 'w-[260px] translate-x-0' : '-translate-x-full lg:translate-x-0',
                // Desktop collapse
                isDesktopSidebarCollapsed ? 'lg:w-[68px]' : 'lg:w-[260px]',
            ]"
        >
            <!-- BRAND LOGO -->
            <div class="flex h-16 items-center justify-between border-b border-slate-800/80 px-4">
                <Link :href="route('admin.dashboard')" class="flex items-center space-x-3 overflow-hidden">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-sm font-extrabold text-white shadow-md shadow-emerald-500/20"
                    >
                        FF
                    </div>
                    <div v-show="!isDesktopSidebarCollapsed" class="whitespace-nowrap transition-opacity duration-200">
                        <span class="text-base font-bold tracking-tight text-white">FreshFood</span>
                        <span
                            class="ml-1.5 rounded-md border border-emerald-500/20 bg-emerald-500/10 px-1.5 py-0.5 text-xs font-medium text-emerald-400"
                            >Admin</span
                        >
                    </div>
                </Link>

                <button
                    class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-800 hover:text-white lg:hidden"
                    @click="isMobileSidebarOpen = false"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- USER STATUS CARD -->
            <div v-show="!isDesktopSidebarCollapsed" class="border-b border-slate-800/80 p-3">
                <div class="flex items-center space-x-3 rounded-xl border border-slate-700/40 bg-slate-800/50 p-2.5">
                    <div class="relative shrink-0">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-500/40 bg-emerald-950/80 text-xs font-bold text-emerald-300"
                        >
                            {{ authUser?.name?.charAt(0) || 'A' }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-slate-900 bg-emerald-500"></span>
                    </div>
                    <div class="min-w-0 flex-1 overflow-hidden">
                        <div class="truncate text-xs font-semibold text-white">{{ authUser?.name }}</div>
                        <div class="mt-0.5 flex items-center space-x-1 text-[10px] font-medium text-emerald-400">
                            <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                            <span>Trực tuyến</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR SEARCH -->
            <div v-show="!isDesktopSidebarCollapsed" class="px-3 pt-3">
                <div class="relative">
                    <input
                        v-model="sidebarSearch"
                        type="text"
                        placeholder="Tìm chức năng..."
                        class="focus:outline-hidden w-full rounded-xl border border-slate-700/60 bg-slate-800/80 py-2 pl-8 pr-3 text-xs text-slate-200 placeholder-slate-400 transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                    />
                    <Search class="pointer-events-none absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
                </div>
            </div>

            <!-- SIDEBAR NAVIGATION MENU -->
            <div class="scrollbar-thin scrollbar-thumb-slate-700 flex-1 space-y-4 overflow-y-auto px-2.5 py-3">
                <!-- SECTION 1: VẬN HÀNH & KINH DOANH -->
                <div>
                    <div v-show="!isDesktopSidebarCollapsed" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        DANH MỤC QUẢN TRỊ
                    </div>
                    <nav class="mt-1 space-y-1">
                        <Link
                            v-for="item in filteredMainNav"
                            :key="item.name"
                            :href="item.href"
                            :title="isDesktopSidebarCollapsed ? item.name : undefined"
                            :class="[
                                'group flex items-center rounded-xl px-3 py-2.5 text-xs font-medium transition-all duration-150',
                                isRouteActive(item.pattern)
                                    ? 'bg-emerald-600 font-semibold text-white shadow-sm shadow-emerald-600/30'
                                    : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    'h-4 w-4 shrink-0 transition-colors',
                                    isRouteActive(item.pattern) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400',
                                    isDesktopSidebarCollapsed ? 'mx-auto' : 'mr-3',
                                ]"
                            />
                            <span v-show="!isDesktopSidebarCollapsed" class="flex-1 truncate">{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>

                <!-- SECTION 2: HỆ THỐNG CHUỖI (ADMIN ONLY) -->
                <div v-if="authUser?.role === 'admin' && filteredAdminNav.length > 0">
                    <div v-show="!isDesktopSidebarCollapsed" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-teal-400">
                        HỆ THỐNG CHUỖI
                    </div>
                    <nav class="mt-1 space-y-1">
                        <Link
                            v-for="item in filteredAdminNav"
                            :key="item.name"
                            :href="item.href"
                            :title="isDesktopSidebarCollapsed ? item.name : undefined"
                            :class="[
                                'group flex items-center rounded-xl px-3 py-2.5 text-xs font-medium transition-all duration-150',
                                isRouteActive(item.pattern)
                                    ? 'bg-emerald-600 font-semibold text-white shadow-sm shadow-emerald-600/30'
                                    : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    'h-4 w-4 shrink-0 transition-colors',
                                    isRouteActive(item.pattern) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400',
                                    isDesktopSidebarCollapsed ? 'mx-auto' : 'mr-3',
                                ]"
                            />
                            <span v-show="!isDesktopSidebarCollapsed" class="flex-1 truncate">{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>

                <!-- SECTION 3: LIÊN KẾT NGOÀI -->
                <div>
                    <div v-show="!isDesktopSidebarCollapsed" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        TRUY CẬP NHANH
                    </div>
                    <nav class="mt-1 space-y-1">
                        <Link
                            :href="route('store.pos.index')"
                            :title="isDesktopSidebarCollapsed ? 'Bán Hàng POS' : undefined"
                            class="group flex items-center rounded-xl px-3 py-2 text-xs text-slate-300 transition-colors hover:bg-slate-800/80 hover:text-white"
                        >
                            <StoreIcon :class="['h-4 w-4 shrink-0 text-emerald-400', isDesktopSidebarCollapsed ? 'mx-auto' : 'mr-3']" />
                            <span v-show="!isDesktopSidebarCollapsed" class="truncate">Mở Quầy POS Bán Lẻ</span>
                        </Link>
                        <a
                            href="/"
                            target="_blank"
                            :title="isDesktopSidebarCollapsed ? 'Website FreshFood' : undefined"
                            class="group flex items-center rounded-xl px-3 py-2 text-xs text-slate-300 transition-colors hover:bg-slate-800/80 hover:text-white"
                        >
                            <ExternalLink :class="['h-4 w-4 shrink-0 text-sky-400', isDesktopSidebarCollapsed ? 'mx-auto' : 'mr-3']" />
                            <span v-show="!isDesktopSidebarCollapsed" class="truncate">Cửa Hàng Trực Tuyến</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- SIDEBAR FOOTER -->
            <div class="border-t border-slate-800/80 bg-slate-950/60 p-3 text-center text-[11px] text-slate-400">
                <span v-show="!isDesktopSidebarCollapsed">FreshFood Platform &bull; v2.4</span>
                <span v-show="isDesktopSidebarCollapsed">FF</span>
            </div>
        </aside>

        <!-- ============================================== -->
        <!-- 3. MAIN CONTENT WRAPPER                        -->
        <!-- ============================================== -->
        <div :class="['flex min-w-0 flex-1 flex-col transition-all duration-300', isDesktopSidebarCollapsed ? 'lg:pl-[68px]' : 'lg:pl-[260px]']">
            <!-- CONTENT HEADER -->
            <div class="flex flex-col gap-2 px-4 pb-3 pt-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h1 class="flex items-center gap-2 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                        <span>{{ title || 'Dashboard' }}</span>
                    </h1>
                </div>

                <!-- MOBILE BREADCRUMB -->
                <nav class="flex items-center space-x-1.5 text-xs text-slate-500 md:hidden">
                    <Link :href="route('admin.dashboard')" class="flex items-center text-emerald-600 hover:underline">
                        <Home class="mr-1 h-3.5 w-3.5" />
                        <span>Trang chủ</span>
                    </Link>
                    <span>/</span>
                    <span class="max-w-[150px] truncate font-medium text-slate-800">{{ title || 'Dashboard' }}</span>
                </nav>
            </div>

            <!-- FLASH ALERTS -->
            <div class="px-4 sm:px-6">
                <div
                    v-if="page.props.flash?.success"
                    class="shadow-xs mb-4 flex items-center justify-between rounded-xl border border-emerald-200/80 bg-emerald-50 p-3.5 text-xs font-medium text-emerald-800"
                >
                    <div class="flex items-center space-x-2.5">
                        <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-600" />
                        <span>{{ page.props.flash.success }}</span>
                    </div>
                </div>

                <div
                    v-if="page.props.flash?.error || (page.props.errors as any)?.error"
                    class="shadow-xs mb-4 flex items-center justify-between rounded-xl border border-rose-200/80 bg-rose-50 p-3.5 text-xs font-medium text-rose-800"
                >
                    <div class="flex items-center space-x-2.5">
                        <XCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ page.props.flash?.error || (page.props.errors as any)?.error }}</span>
                    </div>
                </div>
            </div>

            <!-- MAIN PAGE CONTENT BODY -->
            <main class="flex-1 px-4 pb-8 sm:px-6">
                <slot />
            </main>

            <!-- ============================================== -->
            <!-- 4. MODERN FOOTER                               -->
            <!-- ============================================== -->
            <footer
                class="backdrop-blur-xs flex flex-col items-center justify-between gap-1.5 border-t border-slate-200/80 bg-white/80 px-6 py-3.5 text-xs text-slate-500 sm:flex-row"
            >
                <div>
                    <strong
                        >Bản quyền &copy; 2026 <a href="/" class="font-semibold text-emerald-600 hover:underline">FreshFood Supermarket</a>.</strong
                    >
                    <span> Chuỗi Siêu Thị Thực Phẩm Tươi Sạch.</span>
                </div>
                <div class="font-medium text-slate-400">Hệ Thống Quản Lý Đa Chi Nhánh</div>
            </footer>

            <!-- GLOBAL TOAST NOTIFICATIONS -->
            <ToastContainer />
        </div>
    </div>
</template>
