<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Boxes,
    FolderTree,
    ShoppingBag,
    TicketPercent,
    Truck,
    BarChart3,
    Store,
    Users,
    LogOut,
    ExternalLink,
    Store as StoreIcon,
    CheckCircle2,
    XCircle,
    Menu,
    X,
    ChevronRight,
    Shield,
    Maximize,
    Minimize,
    Search,
    Home,
    Bell,
    Sparkles,
    Image,
    MessageSquare,
} from 'lucide-vue-next';
import ToastContainer from '@/components/ui/ToastContainer.vue';

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
    return mainNav.filter(item => item.name.toLowerCase().includes(q));
});

const filteredAdminNav = computed(() => {
    if (!sidebarSearch.value.trim()) return adminOnlyNav;
    const q = sidebarSearch.value.toLowerCase();
    return adminOnlyNav.filter(item => item.name.toLowerCase().includes(q));
});
</script>

<template>
    <div class="min-h-screen bg-slate-50/70 flex flex-col font-sans text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">
        <Head :title="title ? `${title} | FreshFood Admin` : 'FreshFood Quản Trị Hệ Thống'" />

        <!-- MOBILE BACKDROP -->
        <div
            v-if="isMobileSidebarOpen"
            class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs transition-opacity lg:hidden"
            @click="isMobileSidebarOpen = false"
        />

        <!-- ============================================== -->
        <!-- 1. MODERN TOP NAVBAR                           -->
        <!-- ============================================== -->
        <header
            :class="[
                'bg-white/90 backdrop-blur-md border-b border-slate-200/80 h-16 flex items-center justify-between px-4 sm:px-6 z-30 sticky top-0 transition-all duration-300',
                isDesktopSidebarCollapsed ? 'lg:pl-[84px]' : 'lg:pl-[276px]'
            ]"
        >
            <!-- LEFT NAVBAR LINKS -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Push Menu / Hamburger toggle -->
                <button
                    class="p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors focus:outline-hidden"
                    title="Thu gọn menu"
                    @click="isMobileSidebarOpen ? isMobileSidebarOpen = false : (window.innerWidth < 1024 ? isMobileSidebarOpen = true : toggleDesktopSidebar())"
                >
                    <Menu class="w-5 h-5" />
                </button>

                <!-- BREADCRUMBS -->
                <nav class="hidden md:flex items-center space-x-1.5 text-xs text-slate-500 pl-1">
                    <Link :href="route('admin.dashboard')" class="text-slate-600 hover:text-emerald-600 transition-colors flex items-center gap-1 font-medium">
                        <Home class="w-3.5 h-3.5" />
                        <span>Trang chủ</span>
                    </Link>
                    <ChevronRight class="w-3.5 h-3.5 text-slate-300" />
                    <template v-if="breadcrumbs && breadcrumbs.length > 0">
                        <template v-for="(bc, index) in breadcrumbs" :key="bc.name">
                            <Link v-if="bc.href" :href="bc.href" class="text-slate-600 hover:text-emerald-600 font-medium transition-colors">
                                {{ bc.name }}
                            </Link>
                            <span v-else class="text-slate-900 font-semibold">{{ bc.name }}</span>
                            <ChevronRight v-if="index < breadcrumbs.length - 1" class="w-3.5 h-3.5 text-slate-300" />
                        </template>
                    </template>
                    <span v-else class="text-slate-900 font-semibold truncate max-w-[200px]">{{ title || 'Dashboard' }}</span>
                </nav>
            </div>

            <!-- RIGHT NAVBAR CONTROLS & SHORTCUTS -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- SHORTCUT: POS RETAIL -->
                <Link
                    :href="route('store.pos.index')"
                    class="hidden sm:inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 hover:bg-emerald-100 hover:border-emerald-300 transition-all shadow-2xs"
                >
                    <StoreIcon class="w-3.5 h-3.5 mr-1.5 text-emerald-600" />
                    <span>Thu Ngân POS</span>
                </Link>

                <!-- SHORTCUT: STOREFRONT -->
                <a
                    href="/"
                    target="_blank"
                    class="hidden lg:inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-xl bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 hover:border-slate-300 transition-all shadow-2xs"
                >
                    <ExternalLink class="w-3.5 h-3.5 mr-1.5 text-slate-500" />
                    <span>Xem Website</span>
                </a>

                <!-- FULLSCREEN TOGGLE -->
                <button
                    class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors"
                    title="Toàn màn hình"
                    @click="toggleFullscreen"
                >
                    <Maximize v-if="!isFullscreen" class="w-4 h-4" />
                    <Minimize v-else class="w-4 h-4" />
                </button>

                <!-- USER PILL & PROFILE -->
                <div class="flex items-center space-x-2.5 pl-2 sm:pl-3 border-l border-slate-200">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                        {{ authUser?.name?.charAt(0) || 'A' }}
                    </div>
                    <div class="hidden sm:block text-left text-xs leading-tight">
                        <div class="font-bold text-slate-800 truncate max-w-[140px]">{{ authUser?.name }}</div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded-md">
                                {{ authUser?.role === 'admin' ? 'Quản trị viên' : 'Quản lý' }}
                            </span>
                            <span v-if="authUser?.cua_hang?.ten_cua_hang" class="text-[10px] bg-slate-100 text-slate-600 font-medium px-1.5 py-0.2 rounded-md truncate max-w-[110px]" :title="authUser?.cua_hang?.ten_cua_hang">
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
                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors ml-1"
                    title="Đăng xuất khỏi hệ thống"
                >
                    <LogOut class="w-4 h-4" />
                </Link>
            </div>
        </header>

        <!-- ============================================== -->
        <!-- 2. MODERN SLEEK SIDEBAR                        -->
        <!-- ============================================== -->
        <aside
            :class="[
                'fixed top-0 bottom-0 left-0 z-50 bg-slate-900 text-slate-300 flex flex-col transition-all duration-300 ease-in-out border-r border-slate-800/80 shadow-xl select-none',
                // Mobile
                isMobileSidebarOpen ? 'translate-x-0 w-[260px]' : '-translate-x-full lg:translate-x-0',
                // Desktop collapse
                isDesktopSidebarCollapsed ? 'lg:w-[68px]' : 'lg:w-[260px]'
            ]"
        >
            <!-- BRAND LOGO -->
            <div class="h-16 border-b border-slate-800/80 flex items-center justify-between px-4">
                <Link :href="route('admin.dashboard')" class="flex items-center space-x-3 overflow-hidden">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white font-extrabold text-sm shadow-md shadow-emerald-500/20 shrink-0">
                        FF
                    </div>
                    <div v-show="!isDesktopSidebarCollapsed" class="whitespace-nowrap transition-opacity duration-200">
                        <span class="text-white font-bold text-base tracking-tight">FreshFood</span>
                        <span class="text-emerald-400 font-medium text-xs ml-1.5 px-1.5 py-0.5 rounded-md bg-emerald-500/10 border border-emerald-500/20">Admin</span>
                    </div>
                </Link>

                <button
                    class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    @click="isMobileSidebarOpen = false"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- USER STATUS CARD -->
            <div v-show="!isDesktopSidebarCollapsed" class="p-3 border-b border-slate-800/80">
                <div class="bg-slate-800/50 rounded-xl p-2.5 border border-slate-700/40 flex items-center space-x-3">
                    <div class="relative shrink-0">
                        <div class="w-8 h-8 rounded-lg bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 flex items-center justify-center font-bold text-xs">
                            {{ authUser?.name?.charAt(0) || 'A' }}
                        </div>
                        <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full border-2 border-slate-900 absolute -bottom-0.5 -right-0.5"></span>
                    </div>
                    <div class="overflow-hidden min-w-0 flex-1">
                        <div class="text-xs font-semibold text-white truncate">{{ authUser?.name }}</div>
                        <div class="text-[10px] text-emerald-400 font-medium flex items-center space-x-1 mt-0.5">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
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
                        class="w-full bg-slate-800/80 border border-slate-700/60 text-xs text-slate-200 placeholder-slate-400 rounded-xl pl-8 pr-3 py-2 focus:outline-hidden focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all"
                    />
                    <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" />
                </div>
            </div>

            <!-- SIDEBAR NAVIGATION MENU -->
            <div class="flex-1 overflow-y-auto px-2.5 py-3 space-y-4 scrollbar-thin scrollbar-thumb-slate-700">
                <!-- SECTION 1: VẬN HÀNH & KINH DOANH -->
                <div>
                    <div
                        v-show="!isDesktopSidebarCollapsed"
                        class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400"
                    >
                        DANH MỤC QUẢN TRỊ
                    </div>
                    <nav class="space-y-1 mt-1">
                        <Link
                            v-for="item in filteredMainNav"
                            :key="item.name"
                            :href="item.href"
                            :title="isDesktopSidebarCollapsed ? item.name : undefined"
                            :class="[
                                'flex items-center px-3 py-2.5 rounded-xl text-xs font-medium transition-all group duration-150',
                                isRouteActive(item.pattern)
                                    ? 'bg-emerald-600 text-white font-semibold shadow-sm shadow-emerald-600/30'
                                    : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    'w-4 h-4 shrink-0 transition-colors',
                                    isRouteActive(item.pattern) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400',
                                    isDesktopSidebarCollapsed ? 'mx-auto' : 'mr-3'
                                ]"
                            />
                            <span v-show="!isDesktopSidebarCollapsed" class="truncate flex-1">{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>

                <!-- SECTION 2: HỆ THỐNG CHUỖI (ADMIN ONLY) -->
                <div v-if="authUser?.role === 'admin' && filteredAdminNav.length > 0">
                    <div
                        v-show="!isDesktopSidebarCollapsed"
                        class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-teal-400"
                    >
                        HỆ THỐNG CHUỖI
                    </div>
                    <nav class="space-y-1 mt-1">
                        <Link
                            v-for="item in filteredAdminNav"
                            :key="item.name"
                            :href="item.href"
                            :title="isDesktopSidebarCollapsed ? item.name : undefined"
                            :class="[
                                'flex items-center px-3 py-2.5 rounded-xl text-xs font-medium transition-all group duration-150',
                                isRouteActive(item.pattern)
                                    ? 'bg-emerald-600 text-white font-semibold shadow-sm shadow-emerald-600/30'
                                    : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    'w-4 h-4 shrink-0 transition-colors',
                                    isRouteActive(item.pattern) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400',
                                    isDesktopSidebarCollapsed ? 'mx-auto' : 'mr-3'
                                ]"
                            />
                            <span v-show="!isDesktopSidebarCollapsed" class="truncate flex-1">{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>

                <!-- SECTION 3: LIÊN KẾT NGOÀI -->
                <div>
                    <div
                        v-show="!isDesktopSidebarCollapsed"
                        class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400"
                    >
                        TRUY CẬP NHANH
                    </div>
                    <nav class="space-y-1 mt-1">
                        <Link
                            :href="route('store.pos.index')"
                            :title="isDesktopSidebarCollapsed ? 'Bán Hàng POS' : undefined"
                            class="flex items-center px-3 py-2 rounded-xl text-xs text-slate-300 hover:bg-slate-800/80 hover:text-white transition-colors group"
                        >
                            <StoreIcon :class="['w-4 h-4 text-emerald-400 shrink-0', isDesktopSidebarCollapsed ? 'mx-auto' : 'mr-3']" />
                            <span v-show="!isDesktopSidebarCollapsed" class="truncate">Mở Quầy POS Bán Lẻ</span>
                        </Link>
                        <a
                            href="/"
                            target="_blank"
                            :title="isDesktopSidebarCollapsed ? 'Website FreshFood' : undefined"
                            class="flex items-center px-3 py-2 rounded-xl text-xs text-slate-300 hover:bg-slate-800/80 hover:text-white transition-colors group"
                        >
                            <ExternalLink :class="['w-4 h-4 text-sky-400 shrink-0', isDesktopSidebarCollapsed ? 'mx-auto' : 'mr-3']" />
                            <span v-show="!isDesktopSidebarCollapsed" class="truncate">Cửa Hàng Trực Tuyến</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- SIDEBAR FOOTER -->
            <div class="p-3 border-t border-slate-800/80 text-[11px] text-slate-400 text-center bg-slate-950/60">
                <span v-show="!isDesktopSidebarCollapsed">FreshFood Platform &bull; v2.4</span>
                <span v-show="isDesktopSidebarCollapsed">FF</span>
            </div>
        </aside>

        <!-- ============================================== -->
        <!-- 3. MAIN CONTENT WRAPPER                        -->
        <!-- ============================================== -->
        <div
            :class="[
                'flex-1 flex flex-col min-w-0 transition-all duration-300',
                isDesktopSidebarCollapsed ? 'lg:pl-[68px]' : 'lg:pl-[260px]'
            ]"
        >
            <!-- CONTENT HEADER -->
            <div class="px-4 sm:px-6 pt-5 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>{{ title || 'Dashboard' }}</span>
                    </h1>
                </div>

                <!-- MOBILE BREADCRUMB -->
                <nav class="flex md:hidden items-center space-x-1.5 text-xs text-slate-500">
                    <Link :href="route('admin.dashboard')" class="text-emerald-600 hover:underline flex items-center">
                        <Home class="w-3.5 h-3.5 mr-1" />
                        <span>Trang chủ</span>
                    </Link>
                    <span>/</span>
                    <span class="text-slate-800 font-medium truncate max-w-[150px]">{{ title || 'Dashboard' }}</span>
                </nav>
            </div>

            <!-- FLASH ALERTS -->
            <div class="px-4 sm:px-6">
                <div
                    v-if="page.props.flash?.success"
                    class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-xs font-medium flex items-center justify-between shadow-xs mb-4"
                >
                    <div class="flex items-center space-x-2.5">
                        <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span>{{ page.props.flash.success }}</span>
                    </div>
                </div>

                <div
                    v-if="page.props.flash?.error"
                    class="p-3.5 rounded-xl bg-rose-50 border border-rose-200/80 text-rose-800 text-xs font-medium flex items-center justify-between shadow-xs mb-4"
                >
                    <div class="flex items-center space-x-2.5">
                        <XCircle class="w-4 h-4 text-rose-600 shrink-0" />
                        <span>{{ page.props.flash.error }}</span>
                    </div>
                </div>
            </div>

            <!-- MAIN PAGE CONTENT BODY -->
            <main class="flex-1 px-4 sm:px-6 pb-8">
                <slot />
            </main>

            <!-- ============================================== -->
            <!-- 4. MODERN FOOTER                               -->
            <!-- ============================================== -->
            <footer class="bg-white/80 backdrop-blur-xs border-t border-slate-200/80 py-3.5 px-6 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-1.5">
                <div>
                    <strong>Bản quyền &copy; 2026 <a href="/" class="text-emerald-600 hover:underline font-semibold">FreshFood Supermarket</a>.</strong>
                    <span> Chuỗi Siêu Thị Thực Phẩm Tươi Sạch.</span>
                </div>
                <div class="text-slate-400 font-medium">
                    Hệ Thống Quản Lý Đa Chi Nhánh
                </div>
            </footer>

            <!-- GLOBAL TOAST NOTIFICATIONS -->
            <ToastContainer />
        </div>
    </div>
</template>