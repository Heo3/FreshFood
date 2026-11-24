<script setup lang="ts">
import { useCart } from '@/composables/useCart';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronRight,
    Clock,
    LayoutDashboard,
    LocateFixed,
    LogOut,
    MapPin,
    Package,
    PhoneCall,
    Search,
    ShieldCheck,
    ShoppingBag,
    Truck,
    User as UserIcon,
    X,
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';

defineProps<{
    stores?: Array<{
        id: number;
        ten_cua_hang: string;
        dia_chi: string;
        so_dien_thoai?: string;
    }>;
}>();

const page = usePage();
const authUser = ref(page.props.auth?.user as any);

const { totalItems, subtotal, cartItems, selectedStoreId, setStore, removeFromCart, showToast, clearCart, saveCart } = useCart();

if (page.props.selectedStoreId && page.props.selectedStoreId !== selectedStoreId.value) {
    selectedStoreId.value = Number(page.props.selectedStoreId);
}

const searchQuery = ref('');
const isCartPreviewOpen = ref(false);
const isUserMenuOpen = ref(false);
const isStoreSelectOpen = ref(false);

const suggestions = ref<any[]>([]);
const isSearching = ref(false);
const showSuggestions = ref(false);
const searchContainerRef = ref<HTMLElement | null>(null);

onMounted(() => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('q')) {
            searchQuery.value = urlParams.get('q') || '';
        }

        const handleClickOutside = (e: MouseEvent) => {
            if (searchContainerRef.value && !searchContainerRef.value.contains(e.target as Node)) {
                showSuggestions.value = false;
            }
        };
        document.addEventListener('click', handleClickOutside);
        onUnmounted(() => {
            document.removeEventListener('click', handleClickOutside);
        });
    }
});

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, (newVal) => {
    if (debounceTimer) clearTimeout(debounceTimer);

    const trimmed = newVal.trim();
    if (!trimmed) {
        suggestions.value = [];
        showSuggestions.value = false;
        if (typeof window !== 'undefined' && window.location.pathname === '/san-pham') {
            router.get(
                route('products.index'),
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }
        return;
    }

    debounceTimer = setTimeout(async () => {
        // Tự động lọc danh sách sản phẩm trên trang /san-pham ngay khi gõ
        if (typeof window !== 'undefined' && window.location.pathname === '/san-pham') {
            router.get(
                route('products.index'),
                {
                    q: trimmed,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }

        // Gọi API lấy kết quả gợi ý dropdown
        isSearching.value = true;
        try {
            const res = await fetch(`/api/products/search-suggestions?q=${encodeURIComponent(trimmed)}`);
            if (res.ok) {
                const data = await res.json();
                suggestions.value = data.products || [];
                showSuggestions.value = true;
            }
        } catch (err) {
            console.error('Lỗi tìm kiếm gợi ý:', err);
        } finally {
            isSearching.value = false;
        }
    }, 300);
});

function handleStoreChange(store: any) {
    if (Number(selectedStoreId.value) === Number(store.id)) {
        isStoreSelectOpen.value = false;
        return;
    }

    if (cartItems.value.length > 0) {
        const confirmed = window.confirm(
            `Giỏ hàng của bạn đang có ${cartItems.value.length} sản phẩm từ chi nhánh hiện tại. Khi chuyển sang chi nhánh "${store.ten_cua_hang}", giỏ hàng sẽ được tự động xóa sạch để bạn chọn sản phẩm mới. Bạn có đồng ý chuyển không?`,
        );
        if (!confirmed) {
            isStoreSelectOpen.value = false;
            return;
        }

        // Bấm OK -> Xóa sạch giỏ hàng trên client ngay lập tức
        cartItems.value = [];
        selectedStoreId.value = store.id;
        saveCart();
        isStoreSelectOpen.value = false;

        // Gửi xóa giỏ hàng trên server kèm đổi chi nhánh
        router.post(
            route('store.select'),
            { store_id: store.id, clear_cart: true },
            {
                preserveScroll: true,
                onSuccess: () => {
                    cartItems.value = [];
                    saveCart();
                    showToast(`Đã chuyển sang chi nhánh "${store.ten_cua_hang}" và làm mới giỏ hàng!`, 'success');
                },
            },
        );
        return;
    }

    setStore(store.id);
    isStoreSelectOpen.value = false;
    router.post(
        route('store.select'),
        { store_id: store.id },
        {
            preserveScroll: true,
            onSuccess: () => {
                showToast(`Đã chuyển sang chi nhánh ${store.ten_cua_hang}!`, 'success');
            },
        },
    );
}

const isHeaderLocating = ref(false);

function autoLocateHeader() {
    if (typeof window === 'undefined' || !navigator.geolocation) {
        showToast('Trình duyệt không hỗ trợ GPS.', 'error');
        return;
    }
    isHeaderLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            try {
                const res = await fetch(route('store.find-nearest'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                    },
                    body: JSON.stringify({
                        lat: pos.coords.latitude,
                        lon: pos.coords.longitude,
                    }),
                });
                const data = await res.json();
                if (data.success && data.store) {
                    handleStoreChange(data.store);
                    const distMsg = data.distance_km ? ` (Cách ~${data.distance_km} km)` : '';
                    showToast(`Đã chọn: ${data.store.ten_cua_hang}${distMsg}`, 'success');
                }
            } catch (e) {
                showToast('Lỗi khi định vị chi nhánh.', 'error');
            } finally {
                isHeaderLocating.value = false;
            }
        },
        () => {
            isHeaderLocating.value = false;
            showToast('Không thể lấy vị trí hiện tại của bạn.', 'error');
        },
        { timeout: 10000 },
    );
}

function handleSearch() {
    showSuggestions.value = false;
    if (searchQuery.value.trim()) {
        router.visit(route('products.index', { q: searchQuery.value.trim() }));
    }
}

function selectSuggestion(item: any) {
    showSuggestions.value = false;
    router.visit(route('products.show', { slug: item.duong_dan_seo }));
}

function formatPrice(value: number): string {
    return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
}
</script>

<template>
    <header class="sticky top-0 z-50 w-full bg-white shadow-sm transition-all duration-200">
        <!-- TOP ANNOUNCEMENT BAR -->
        <div class="bg-gradient-to-r from-emerald-700 via-emerald-600 to-teal-700 px-4 py-2 text-xs text-white">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 md:flex-row">
                <div class="flex items-center space-x-4">
                    <span class="flex items-center font-medium">
                        <Truck class="mr-1 h-3.5 w-3.5 animate-pulse text-emerald-200" />
                        Giao hàng siêu tốc trong 2 giờ
                    </span>
                    <span class="hidden text-emerald-300 sm:inline-block">|</span>
                    <span class="hidden items-center sm:flex">
                        <ShieldCheck class="mr-1 h-3.5 w-3.5 text-emerald-200" />
                        100% Thực phẩm VietGAP & Organic tươi mới
                    </span>
                </div>

                <div class="flex items-center space-x-4">
                    <span class="flex items-center">
                        <PhoneCall class="mr-1 h-3.5 w-3.5 text-amber-300" />
                        Hotline đặt hàng: <strong class="ml-1 text-amber-300">1900 6868</strong>
                    </span>
                    <Link :href="route('orders.tracking')" class="flex items-center text-emerald-100 hover:text-white hover:underline">
                        <Clock class="mr-1 h-3.5 w-3.5" />
                        Tra cứu đơn hàng
                    </Link>
                </div>
            </div>
        </div>

        <!-- MAIN HEADER -->
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3.5">
            <!-- BRAND LOGO -->
            <Link :href="route('home')" class="group flex shrink-0 items-center space-x-3">
                <img
                    src="/images/logo.png"
                    alt="Fresh Food"
                    class="h-11 w-auto rounded-xl object-contain shadow-sm transition-transform duration-300 group-hover:scale-105"
                />
                <div>
                    <span class="text-2xl font-black tracking-tight text-gray-900 transition-colors group-hover:text-emerald-600">
                        Fresh<span class="text-emerald-600">Food</span>
                    </span>
                    <p class="-mt-1 text-[10px] font-bold uppercase tracking-widest text-emerald-700">Tươi Sạch Mỗi Ngày</p>
                </div>
            </Link>

            <!-- STORE LOCATION SELECTOR -->
            <div class="relative hidden lg:block">
                <button
                    @click="isStoreSelectOpen = !isStoreSelectOpen"
                    class="flex items-center space-x-2 rounded-xl border border-emerald-200/60 bg-emerald-50/70 px-3.5 py-2 text-xs font-medium text-emerald-900 transition-colors hover:bg-emerald-100/70"
                >
                    <MapPin class="h-4 w-4 shrink-0 text-emerald-600" />
                    <div class="max-w-[170px] truncate text-left">
                        <p class="text-[10px] font-normal leading-tight text-gray-500">Chi nhánh nhận hàng:</p>
                        <p class="truncate font-semibold">
                            {{ stores?.find((s) => s.id === selectedStoreId)?.ten_cua_hang || 'Chọn chi nhánh gần bạn' }}
                        </p>
                    </div>
                    <ChevronDown class="ml-1 h-3.5 w-3.5 text-emerald-600" />
                </button>

                <!-- Store Dropdown Menu -->
                <div
                    v-if="isStoreSelectOpen"
                    class="absolute left-0 z-50 mt-2 w-80 rounded-2xl border border-gray-100 bg-white p-2 shadow-xl duration-150 animate-in fade-in slide-in-from-top-2"
                >
                    <div class="flex items-center justify-between border-b px-3 py-2">
                        <p class="text-xs font-bold text-gray-700">Chọn cửa hàng phục vụ:</p>
                    </div>

                    <!-- Nút định vị GPS tự chọn chi nhánh gần nhất -->
                    <button
                        type="button"
                        @click="autoLocateHeader"
                        :disabled="isHeaderLocating"
                        class="my-1.5 flex w-full items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-2.5 text-left text-xs font-bold text-emerald-800 transition-colors hover:bg-emerald-100"
                    >
                        <div class="flex items-center space-x-2">
                            <LocateFixed class="h-4 w-4 text-emerald-600" :class="{ 'animate-spin': isHeaderLocating }" />
                            <span>{{ isHeaderLocating ? 'Đang xác định vị trí...' : '📍 Tự động chọn chi nhánh gần nhất' }}</span>
                        </div>
                    </button>

                    <div class="mt-1 max-h-60 space-y-1 overflow-y-auto">
                        <button
                            v-for="store in stores"
                            :key="store.id"
                            @click="handleStoreChange(store)"
                            class="flex w-full items-start space-x-2.5 rounded-xl p-2.5 text-left text-xs transition-colors hover:bg-emerald-50"
                            :class="{ 'border border-emerald-200 bg-emerald-50/80': store.id === selectedStoreId }"
                        >
                            <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                            <div>
                                <p class="font-bold text-gray-800">{{ store.ten_cua_hang }}</p>
                                <p class="line-clamp-1 text-[11px] leading-relaxed text-gray-500">{{ store.dia_chi }}</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- SEARCH BAR WITH INSTANT LIVE RESULTS -->
            <div ref="searchContainerRef" class="relative mx-2 max-w-xl flex-1">
                <form @submit.prevent="handleSearch" class="relative">
                    <input
                        v-model="searchQuery"
                        @focus="suggestions.length > 0 ? (showSuggestions = true) : null"
                        type="text"
                        placeholder="Tìm kiếm rau củ tươi, cá hồi Nauy, thịt bò Úc, hoa quả nhập khẩu..."
                        class="w-full rounded-full border border-gray-200 bg-gray-50/80 py-2.5 pl-11 pr-24 text-sm text-gray-800 placeholder-gray-400 shadow-inner transition-all focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    />
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />

                    <!-- Clear button -->
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="absolute right-20 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600"
                        title="Xóa tìm kiếm"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <button
                        type="submit"
                        class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
                    >
                        Tìm kiếm
                    </button>
                </form>

                <!-- LIVE SEARCH SUGGESTIONS DROPDOWN -->
                <div
                    v-if="showSuggestions && searchQuery.trim()"
                    class="absolute left-0 right-0 z-50 mt-2 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl duration-150 animate-in fade-in slide-in-from-top-2"
                >
                    <!-- Loading state -->
                    <div v-if="isSearching" class="flex items-center justify-center space-x-2 p-4 text-center text-xs text-gray-500">
                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-600 border-t-transparent"></div>
                        <span>Đang tìm sản phẩm...</span>
                    </div>

                    <!-- Results list -->
                    <div v-else-if="suggestions.length > 0">
                        <div
                            class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-gray-500"
                        >
                            <span>Sản phẩm gợi ý</span>
                            <span class="font-semibold lowercase text-emerald-600">{{ suggestions.length }} kết quả</span>
                        </div>
                        <div class="max-h-80 divide-y divide-gray-50 overflow-y-auto">
                            <div
                                v-for="item in suggestions"
                                :key="item.id"
                                @click="selectSuggestion(item)"
                                class="group flex cursor-pointer items-center space-x-3 p-3 transition-colors hover:bg-emerald-50/60"
                            >
                                <img
                                    :src="item.hinh_anh || '/images/logo.png'"
                                    :alt="item.ten_san_pham"
                                    class="h-11 w-11 shrink-0 rounded-xl border border-gray-100 object-cover transition-transform group-hover:scale-105"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-bold text-gray-900 group-hover:text-emerald-700">
                                        {{ item.ten_san_pham }}
                                    </p>
                                    <p class="truncate text-[10px] text-gray-500" v-if="item.ten_danh_muc">
                                        {{ item.ten_danh_muc }}
                                    </p>
                                    <div class="mt-0.5 flex items-center space-x-2">
                                        <span class="text-xs font-black text-emerald-700">
                                            {{ formatPrice(item.gia_giam || item.gia_ban) }}
                                        </span>
                                        <span v-if="item.gia_giam && item.gia_giam < item.gia_ban" class="text-[10px] text-gray-400 line-through">
                                            {{ formatPrice(item.gia_ban) }}
                                        </span>
                                        <span class="text-[10px] text-gray-400">/ {{ item.don_vi_tinh }}</span>
                                    </div>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400 transition-all group-hover:translate-x-0.5 group-hover:text-emerald-600" />
                            </div>
                        </div>

                        <!-- View all link -->
                        <button
                            @click="handleSearch"
                            class="flex w-full items-center justify-center space-x-1 border-t border-gray-100 bg-gray-50 py-2.5 text-center text-xs font-bold text-emerald-700 transition-colors hover:bg-emerald-50"
                        >
                            <span>Xem tất cả kết quả cho "{{ searchQuery }}"</span>
                            <ChevronRight class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="p-6 text-center text-xs text-gray-500">
                        <p class="font-medium text-gray-700">Không tìm thấy sản phẩm nào</p>
                        <p class="mt-1 text-[11px] text-gray-400">Thử tìm với từ khóa khác như "thịt bò", "rau cải", "táo",...</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT ACTIONS (ACCOUNT & CART) -->
            <div class="flex shrink-0 items-center space-x-3">
                <!-- User Account Dropdown -->
                <div class="relative">
                    <template v-if="authUser">
                        <button
                            @click="isUserMenuOpen = !isUserMenuOpen"
                            class="flex items-center space-x-2 rounded-xl p-1.5 text-left transition-colors hover:bg-gray-100"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-full border border-emerald-200 bg-emerald-100 text-sm font-bold text-emerald-700 shadow-sm"
                            >
                                {{ authUser.name?.charAt(0) || 'U' }}
                            </div>
                            <div class="hidden md:block">
                                <p class="text-xs font-bold leading-tight text-gray-800">{{ authUser.name }}</p>
                                <p class="flex items-center text-[10px] font-bold text-amber-600">★ {{ authUser.loyalty_points || 0 }} điểm</p>
                            </div>
                            <ChevronDown class="hidden h-3.5 w-3.5 text-gray-500 md:block" />
                        </button>

                        <div
                            v-if="isUserMenuOpen"
                            class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-gray-100 bg-white p-2 shadow-xl duration-150 animate-in fade-in slide-in-from-top-2"
                        >
                            <div class="border-b border-gray-100 px-3 py-2">
                                <p class="truncate text-xs font-bold text-gray-800">{{ authUser.name }}</p>
                                <p class="truncate text-[11px] text-gray-500">{{ authUser.email }}</p>
                                <span class="mt-1 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">
                                    {{ authUser.loyalty_points || 0 }} Điểm thưởng
                                </span>
                            </div>

                            <div class="py-1">
                                <Link
                                    :href="route('account.index')"
                                    class="flex items-center space-x-2.5 rounded-lg px-3 py-2 text-xs text-gray-700 transition-colors hover:bg-emerald-50 hover:text-emerald-700"
                                >
                                    <UserIcon class="h-4 w-4 text-emerald-600" />
                                    <span>Tài khoản của tôi</span>
                                </Link>
                                <Link
                                    :href="route('account.orders')"
                                    class="flex items-center space-x-2.5 rounded-lg px-3 py-2 text-xs text-gray-700 transition-colors hover:bg-emerald-50 hover:text-emerald-700"
                                >
                                    <Package class="h-4 w-4 text-emerald-600" />
                                    <span>Đơn hàng đã đặt</span>
                                </Link>

                                <!-- Admin link if privileged -->
                                <Link
                                    v-if="authUser.role === 'admin' || authUser.role === 'quan_ly'"
                                    :href="route('admin.dashboard')"
                                    class="flex items-center space-x-2.5 rounded-lg px-3 py-2 text-xs font-semibold text-indigo-700 transition-colors hover:bg-indigo-50"
                                >
                                    <LayoutDashboard class="h-4 w-4 text-indigo-600" />
                                    <span>Trang Quản trị Hệ thống</span>
                                </Link>

                                <!-- Store POS link if staff -->
                                <Link
                                    v-if="authUser.role === 'nhan_vien' || authUser.role === 'quan_ly'"
                                    :href="route('store.pos.index')"
                                    class="flex items-center space-x-2.5 rounded-lg px-3 py-2 text-xs font-semibold text-teal-700 transition-colors hover:bg-teal-50"
                                >
                                    <ShoppingBag class="h-4 w-4 text-teal-600" />
                                    <span>POS Bán hàng & Kho</span>
                                </Link>

                                <!-- Delivery / Shipper portal link -->
                                <Link
                                    v-if="authUser.role === 'giao_hang'"
                                    :href="route('delivery.index')"
                                    class="flex items-center space-x-2.5 rounded-lg px-3 py-2 text-xs font-semibold text-sky-700 transition-colors hover:bg-sky-50"
                                >
                                    <Truck class="h-4 w-4 text-sky-600" />
                                    <span>Đơn Giao Của Tôi (Shipper)</span>
                                </Link>
                            </div>

                            <div class="border-t border-gray-100 pt-1">
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="flex w-full items-center space-x-2.5 rounded-lg px-3 py-2 text-xs text-red-600 transition-colors hover:bg-red-50"
                                >
                                    <LogOut class="h-4 w-4" />
                                    <span>Đăng xuất</span>
                                </Link>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="flex items-center space-x-1.5 rounded-xl px-3 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-emerald-50/60 hover:text-emerald-600"
                        >
                            <UserIcon class="h-4 w-4" />
                            <span>Đăng nhập</span>
                        </Link>
                    </template>
                </div>

                <!-- CART BUTTON WITH HOVER PREVIEW -->
                <div class="relative" @mouseleave="isCartPreviewOpen = false">
                    <Link
                        :href="'/gio-hang'"
                        @mouseenter="isCartPreviewOpen = true"
                        class="flex cursor-pointer items-center space-x-2 rounded-2xl bg-emerald-600 px-3.5 py-2 text-white shadow-md shadow-emerald-600/20 transition-all hover:scale-105 hover:bg-emerald-700 active:scale-95"
                    >
                        <div class="pointer-events-none relative">
                            <ShoppingBag class="h-5 w-5" />
                            <span
                                v-if="totalItems > 0"
                                class="absolute -right-2 -top-2 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-[10px] font-extrabold text-gray-900 ring-2 ring-white"
                            >
                                {{ totalItems > 99 ? '99+' : totalItems }}
                            </span>
                        </div>
                        <span class="pointer-events-none hidden text-xs font-bold sm:inline">
                            {{ totalItems > 0 ? formatPrice(subtotal) : 'Giỏ hàng' }}
                        </span>
                    </Link>

                    <!-- Cart Preview Dropdown -->
                    <div
                        v-if="isCartPreviewOpen && totalItems > 0"
                        class="absolute right-0 z-50 mt-2 w-80 rounded-2xl border border-gray-100 bg-white p-3 shadow-2xl duration-150 animate-in fade-in slide-in-from-top-2"
                    >
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <span class="text-xs font-bold text-gray-700">Giỏ hàng của bạn ({{ totalItems }})</span>
                            <Link :href="'/gio-hang'" class="text-[11px] font-semibold text-emerald-600 hover:underline"> Xem tất cả </Link>
                        </div>

                        <div class="my-2 max-h-64 divide-y divide-gray-100 overflow-y-auto">
                            <div v-for="item in cartItems.slice(0, 4)" :key="item.san_pham_id" class="flex items-center space-x-2.5 py-2 text-xs">
                                <img
                                    :src="item.hinh_anh_chinh"
                                    :alt="item.ten_san_pham"
                                    class="h-10 w-10 shrink-0 rounded-lg bg-gray-100 object-cover"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-gray-800">{{ item.ten_san_pham }}</p>
                                    <p class="text-[11px] text-gray-500">
                                        {{ item.so_luong }} {{ item.don_vi_tinh }} ×
                                        <span class="font-bold text-emerald-700">{{ formatPrice(item.gia_giam || item.gia_ban) }}</span>
                                    </p>
                                </div>
                                <button @click="removeFromCart(item.san_pham_id)" class="p-1 text-gray-400 hover:text-red-500" title="Xóa">×</button>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-2">
                            <div class="mb-2.5 flex items-center justify-between">
                                <span class="text-xs text-gray-600">Tạm tính:</span>
                                <span class="text-sm font-extrabold text-emerald-700">{{ formatPrice(subtotal) }}</span>
                            </div>
                            <Link
                                :href="'/thanh-toan'"
                                class="block w-full rounded-xl bg-emerald-600 py-2 text-center text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
                            >
                                Tiến hành đặt hàng ngay
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MOBILE STORE SELECTOR BAR -->
        <div class="border-t border-emerald-100 bg-emerald-50/70 px-4 py-2 lg:hidden">
            <button
                @click="isStoreSelectOpen = !isStoreSelectOpen"
                class="flex w-full items-center justify-between text-xs font-medium text-emerald-950"
            >
                <div class="flex items-center space-x-2 truncate">
                    <MapPin class="h-4 w-4 shrink-0 text-emerald-600" />
                    <span class="text-[11px] text-gray-500 shrink-0">Chi nhánh:</span>
                    <span class="truncate font-bold text-emerald-900">
                        {{ stores?.find((s) => s.id === selectedStoreId)?.ten_cua_hang || 'Chọn chi nhánh gần bạn' }}
                    </span>
                </div>
                <div class="flex items-center space-x-1 shrink-0 text-[11px] font-bold text-emerald-700">
                    <span>Đổi</span>
                    <ChevronDown class="h-3.5 w-3.5" />
                </div>
            </button>
        </div>

        <!-- MOBILE STORE SELECT MODAL -->
        <div
            v-if="isStoreSelectOpen"
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 p-0 sm:p-4 backdrop-blur-xs lg:hidden"
            @click.self="isStoreSelectOpen = false"
        >
            <div class="w-full max-w-md rounded-t-3xl sm:rounded-3xl bg-white p-4 shadow-2xl duration-200 animate-in slide-in-from-bottom">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <MapPin class="h-5 w-5 text-emerald-600" />
                        <h3 class="text-sm font-bold text-gray-900">Chọn Chi Nhánh Gần Bạn</h3>
                    </div>
                    <button @click="isStoreSelectOpen = false" class="rounded-full p-1 text-gray-400 hover:bg-gray-100">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <button
                    type="button"
                    @click="autoLocateHeader(); isStoreSelectOpen = false"
                    :disabled="isHeaderLocating"
                    class="my-3 flex w-full items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-left text-xs font-bold text-emerald-800 transition-colors hover:bg-emerald-100"
                >
                    <div class="flex items-center space-x-2">
                        <LocateFixed class="h-4 w-4 text-emerald-600" :class="{ 'animate-spin': isHeaderLocating }" />
                        <span>{{ isHeaderLocating ? 'Đang xác định vị trí...' : '📍 Tự động chọn chi nhánh gần nhất' }}</span>
                    </div>
                </button>

                <div class="max-h-72 space-y-1.5 overflow-y-auto pr-1">
                    <button
                        v-for="store in stores"
                        :key="store.id"
                        @click="handleStoreChange(store); isStoreSelectOpen = false"
                        class="flex w-full items-start space-x-3 rounded-2xl p-3 text-left text-xs transition-colors hover:bg-emerald-50 border border-transparent"
                        :class="{ 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20': store.id === selectedStoreId }"
                    >
                        <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-gray-800">{{ store.ten_cua_hang }}</p>
                            <p class="mt-0.5 line-clamp-2 text-[11px] leading-relaxed text-gray-500">{{ store.dia_chi }}</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- QUICK NAV CATEGORIES -->
        <nav class="border-t border-gray-100 bg-gray-50/70">
            <div class="no-scrollbar mx-auto flex max-w-7xl items-center space-x-5 overflow-x-auto px-4 py-2 text-xs font-semibold text-gray-700 whitespace-nowrap">
                <Link :href="route('home')" class="shrink-0 transition-colors hover:text-emerald-600"> Trang chủ </Link>
                <Link :href="route('products.index')" class="shrink-0 transition-colors hover:text-emerald-600"> Tất cả sản phẩm </Link>
                <Link :href="route('categories.show', { slug: 'rau-cu-huu-co' })" class="shrink-0 transition-colors hover:text-emerald-600">
                    Rau củ hữu cơ
                </Link>
                <Link :href="route('categories.show', { slug: 'trai-cay-tuoi-ngon' })" class="shrink-0 transition-colors hover:text-emerald-600">
                    Trái cây tươi
                </Link>
                <Link :href="route('categories.show', { slug: 'thit-tuoi-trung-sach' })" class="shrink-0 transition-colors hover:text-emerald-600">
                    Thịt sạch & Trứng
                </Link>
                <Link :href="route('categories.show', { slug: 'thuy-hai-san-tuoi-song' })" class="shrink-0 transition-colors hover:text-emerald-600">
                    Cá hồi & Hải sản tươi
                </Link>
                <Link
                    :href="route('categories.show', { slug: 'sua-bo-thuc-pham-che-bien' })"
                    class="shrink-0 transition-colors hover:text-emerald-600"
                >
                    Sữa bơ Dalatmilk
                </Link>
                <Link
                    :href="route('products.index', { is_fresh: true })"
                    class="flex shrink-0 items-center font-bold text-emerald-700 hover:underline"
                >
                    <span class="mr-1.5 h-2 w-2 animate-ping rounded-full bg-emerald-500"></span>
                    Hàng tươi mới hôm nay
                </Link>
            </div>
        </nav>
    </header>
</template>
