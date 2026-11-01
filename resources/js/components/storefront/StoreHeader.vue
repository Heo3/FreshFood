<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useCart } from '@/composables/useCart';
import {
    ShoppingBag,
    Search,
    MapPin,
    PhoneCall,
    User as UserIcon,
    ChevronDown,
    ChevronRight,
    Truck,
    ShieldCheck,
    Clock,
    Sparkles,
    LogOut,
    Package,
    LayoutDashboard,
    X,
} from 'lucide-vue-next';

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
            router.get(route('products.index'), {}, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }
        return;
    }

    debounceTimer = setTimeout(async () => {
        // Tự động lọc danh sách sản phẩm trên trang /san-pham ngay khi gõ
        if (typeof window !== 'undefined' && window.location.pathname === '/san-pham') {
            router.get(route('products.index'), {
                q: trimmed,
            }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
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
            `Giỏ hàng của bạn đang có ${cartItems.value.length} sản phẩm từ chi nhánh hiện tại. Khi chuyển sang chi nhánh "${store.ten_cua_hang}", giỏ hàng sẽ được tự động xóa sạch để bạn chọn sản phẩm mới. Bạn có đồng ý chuyển không?`
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
            }
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
        }
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
    <header class="w-full bg-white shadow-sm sticky top-0 z-50 transition-all duration-200">
        <!-- TOP ANNOUNCEMENT BAR -->
        <div class="bg-gradient-to-r from-emerald-700 via-emerald-600 to-teal-700 text-white text-xs py-2 px-4">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-2">
                <div class="flex items-center space-x-4">
                    <span class="flex items-center font-medium">
                        <Truck class="w-3.5 h-3.5 mr-1 text-emerald-200 animate-pulse" />
                        Giao hàng siêu tốc trong 2 giờ
                    </span>
                    <span class="hidden sm:inline-block text-emerald-300">|</span>
                    <span class="hidden sm:flex items-center">
                        <ShieldCheck class="w-3.5 h-3.5 mr-1 text-emerald-200" />
                        100% Thực phẩm VietGAP & Organic tươi mới
                    </span>
                </div>

                <div class="flex items-center space-x-4">
                    <span class="flex items-center">
                        <PhoneCall class="w-3.5 h-3.5 mr-1 text-amber-300" />
                        Hotline đặt hàng: <strong class="ml-1 text-amber-300">1900 6868</strong>
                    </span>
                    <Link :href="route('orders.tracking')" class="hover:underline flex items-center text-emerald-100 hover:text-white">
                        <Clock class="w-3.5 h-3.5 mr-1" />
                        Tra cứu đơn hàng
                    </Link>
                </div>
            </div>
        </div>

        <!-- MAIN HEADER -->
        <div class="max-w-7xl mx-auto px-4 py-3.5 flex items-center justify-between gap-4">
            <!-- BRAND LOGO -->
            <Link :href="route('home')" class="flex items-center space-x-3 shrink-0 group">
                <img
                    src="/images/logo.png"
                    alt="Fresh Food"
                    class="h-11 w-auto object-contain rounded-xl shadow-sm group-hover:scale-105 transition-transform duration-300"
                />
                <div>
                    <span class="text-2xl font-black tracking-tight text-gray-900 group-hover:text-emerald-600 transition-colors">
                        Fresh<span class="text-emerald-600">Food</span>
                    </span>
                    <p class="text-[10px] uppercase font-bold tracking-widest text-emerald-700 -mt-1">
                        Tươi Sạch Mỗi Ngày
                    </p>
                </div>
            </Link>

            <!-- STORE LOCATION SELECTOR -->
            <div class="relative hidden lg:block">
                <button
                    @click="isStoreSelectOpen = !isStoreSelectOpen"
                    class="flex items-center space-x-2 bg-emerald-50/70 hover:bg-emerald-100/70 text-emerald-900 px-3.5 py-2 rounded-xl text-xs font-medium border border-emerald-200/60 transition-colors"
                >
                    <MapPin class="w-4 h-4 text-emerald-600 shrink-0" />
                    <div class="text-left max-w-[170px] truncate">
                        <p class="text-[10px] text-gray-500 font-normal leading-tight">Chi nhánh nhận hàng:</p>
                        <p class="font-semibold truncate">
                            {{ stores?.find(s => s.id === selectedStoreId)?.ten_cua_hang || 'Chọn chi nhánh gần bạn' }}
                        </p>
                    </div>
                    <ChevronDown class="w-3.5 h-3.5 text-emerald-600 ml-1" />
                </button>

                <!-- Store Dropdown Menu -->
                <div
                    v-if="isStoreSelectOpen"
                    class="absolute left-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 p-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                >
                    <p class="text-xs font-bold text-gray-700 px-3 py-2 border-b">Chọn cửa hàng phục vụ:</p>
                    <div class="max-h-60 overflow-y-auto mt-1 space-y-1">
                        <button
                            v-for="store in stores"
                            :key="store.id"
                            @click="handleStoreChange(store)"
                            class="w-full text-left p-2.5 rounded-xl hover:bg-emerald-50 text-xs transition-colors flex items-start space-x-2.5"
                            :class="{ 'bg-emerald-50/80 border border-emerald-200': store.id === selectedStoreId }"
                        >
                            <MapPin class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0" />
                            <div>
                                <p class="font-bold text-gray-800">{{ store.ten_cua_hang }}</p>
                                <p class="text-gray-500 text-[11px] leading-relaxed line-clamp-1">{{ store.dia_chi }}</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- SEARCH BAR WITH INSTANT LIVE RESULTS -->
            <div ref="searchContainerRef" class="flex-1 max-w-xl relative mx-2">
                <form @submit.prevent="handleSearch" class="relative">
                    <input
                        v-model="searchQuery"
                        @focus="suggestions.length > 0 ? showSuggestions = true : null"
                        type="text"
                        placeholder="Tìm kiếm rau củ tươi, cá hồi Nauy, thịt bò Úc, hoa quả nhập khẩu..."
                        class="w-full pl-11 pr-24 py-2.5 bg-gray-50/80 border border-gray-200 rounded-full text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-inner"
                    />
                    <Search class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />

                    <!-- Clear button -->
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="absolute right-20 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1"
                        title="Xóa tìm kiếm"
                    >
                        <X class="w-4 h-4" />
                    </button>

                    <button
                        type="submit"
                        class="absolute right-1.5 top-1/2 -translate-y-1/2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-sm transition-colors"
                    >
                        Tìm kiếm
                    </button>
                </form>

                <!-- LIVE SEARCH SUGGESTIONS DROPDOWN -->
                <div
                    v-if="showSuggestions && searchQuery.trim()"
                    class="absolute left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                >
                    <!-- Loading state -->
                    <div v-if="isSearching" class="p-4 text-center text-xs text-gray-500 flex items-center justify-center space-x-2">
                        <div class="w-4 h-4 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
                        <span>Đang tìm sản phẩm...</span>
                    </div>

                    <!-- Results list -->
                    <div v-else-if="suggestions.length > 0">
                        <div class="px-4 py-2 bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 flex justify-between items-center">
                            <span>Sản phẩm gợi ý</span>
                            <span class="text-emerald-600 font-semibold lowercase">{{ suggestions.length }} kết quả</span>
                        </div>
                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                            <div
                                v-for="item in suggestions"
                                :key="item.id"
                                @click="selectSuggestion(item)"
                                class="p-3 hover:bg-emerald-50/60 cursor-pointer flex items-center space-x-3 transition-colors group"
                            >
                                <img
                                    :src="item.hinh_anh || '/images/logo.png'"
                                    :alt="item.ten_san_pham"
                                    class="w-11 h-11 object-cover rounded-xl border border-gray-100 shrink-0 group-hover:scale-105 transition-transform"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 group-hover:text-emerald-700 truncate">
                                        {{ item.ten_san_pham }}
                                    </p>
                                    <p class="text-[10px] text-gray-500 truncate" v-if="item.ten_danh_muc">
                                        {{ item.ten_danh_muc }}
                                    </p>
                                    <div class="flex items-center space-x-2 mt-0.5">
                                        <span class="text-xs font-black text-emerald-700">
                                            {{ formatPrice(item.gia_giam || item.gia_ban) }}
                                        </span>
                                        <span v-if="item.gia_giam && item.gia_giam < item.gia_ban" class="text-[10px] text-gray-400 line-through">
                                            {{ formatPrice(item.gia_ban) }}
                                        </span>
                                        <span class="text-[10px] text-gray-400">/ {{ item.don_vi_tinh }}</span>
                                    </div>
                                </div>
                                <ChevronRight class="w-4 h-4 text-gray-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all" />
                            </div>
                        </div>

                        <!-- View all link -->
                        <button
                            @click="handleSearch"
                            class="w-full text-center py-2.5 bg-gray-50 hover:bg-emerald-50 text-xs font-bold text-emerald-700 border-t border-gray-100 transition-colors flex items-center justify-center space-x-1"
                        >
                            <span>Xem tất cả kết quả cho "{{ searchQuery }}"</span>
                            <ChevronRight class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="p-6 text-center text-xs text-gray-500">
                        <p class="font-medium text-gray-700">Không tìm thấy sản phẩm nào</p>
                        <p class="text-[11px] text-gray-400 mt-1">Thử tìm với từ khóa khác như "thịt bò", "rau cải", "táo",...</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT ACTIONS (ACCOUNT & CART) -->
            <div class="flex items-center space-x-3 shrink-0">
                <!-- User Account Dropdown -->
                <div class="relative">
                    <template v-if="authUser">
                        <button
                            @click="isUserMenuOpen = !isUserMenuOpen"
                            class="flex items-center space-x-2 p-1.5 rounded-xl hover:bg-gray-100 transition-colors text-left"
                        >
                            <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-sm shadow-sm border border-emerald-200">
                                {{ authUser.name?.charAt(0) || 'U' }}
                            </div>
                            <div class="hidden md:block">
                                <p class="text-xs font-bold text-gray-800 leading-tight">{{ authUser.name }}</p>
                                <p class="text-[10px] text-amber-600 font-bold flex items-center">
                                    ★ {{ authUser.loyalty_points || 0 }} điểm
                                </p>
                            </div>
                            <ChevronDown class="w-3.5 h-3.5 text-gray-500 hidden md:block" />
                        </button>

                        <div
                            v-if="isUserMenuOpen"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 p-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                        >
                            <div class="px-3 py-2 border-b border-gray-100">
                                <p class="text-xs font-bold text-gray-800 truncate">{{ authUser.name }}</p>
                                <p class="text-[11px] text-gray-500 truncate">{{ authUser.email }}</p>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                    {{ authUser.loyalty_points || 0 }} Điểm thưởng
                                </span>
                            </div>

                            <div class="py-1">
                                <Link
                                    :href="route('account.index')"
                                    class="flex items-center space-x-2.5 px-3 py-2 text-xs text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg transition-colors"
                                >
                                    <UserIcon class="w-4 h-4 text-emerald-600" />
                                    <span>Tài khoản của tôi</span>
                                </Link>
                                <Link
                                    :href="route('account.orders')"
                                    class="flex items-center space-x-2.5 px-3 py-2 text-xs text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg transition-colors"
                                >
                                    <Package class="w-4 h-4 text-emerald-600" />
                                    <span>Đơn hàng đã đặt</span>
                                </Link>

                                <!-- Admin link if privileged -->
                                <Link
                                    v-if="authUser.role === 'admin' || authUser.role === 'quan_ly'"
                                    :href="route('admin.dashboard')"
                                    class="flex items-center space-x-2.5 px-3 py-2 text-xs text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors font-semibold"
                                >
                                    <LayoutDashboard class="w-4 h-4 text-indigo-600" />
                                    <span>Trang Quản trị Hệ thống</span>
                                </Link>

                                <!-- Store POS link if staff -->
                                <Link
                                    v-if="authUser.role === 'nhan_vien' || authUser.role === 'quan_ly'"
                                    :href="route('store.pos.index')"
                                    class="flex items-center space-x-2.5 px-3 py-2 text-xs text-teal-700 hover:bg-teal-50 rounded-lg transition-colors font-semibold"
                                >
                                    <ShoppingBag class="w-4 h-4 text-teal-600" />
                                    <span>POS Bán hàng & Kho</span>
                                </Link>
                            </div>

                            <div class="pt-1 border-t border-gray-100">
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="w-full flex items-center space-x-2.5 px-3 py-2 text-xs text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                >
                                    <LogOut class="w-4 h-4" />
                                    <span>Đăng xuất</span>
                                </Link>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="flex items-center space-x-1.5 text-xs font-bold text-gray-700 hover:text-emerald-600 px-3 py-2 rounded-xl hover:bg-emerald-50/60 transition-colors"
                        >
                            <UserIcon class="w-4 h-4" />
                            <span>Đăng nhập</span>
                        </Link>
                    </template>
                </div>

                <!-- CART BUTTON WITH HOVER PREVIEW -->
                <div class="relative" @mouseleave="isCartPreviewOpen = false">
                    <Link
                        :href="'/gio-hang'"
                        @mouseenter="isCartPreviewOpen = true"
                        class="flex items-center space-x-2 bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 rounded-2xl shadow-md shadow-emerald-600/20 transition-all hover:scale-105 active:scale-95 cursor-pointer"
                    >
                        <div class="relative pointer-events-none">
                            <ShoppingBag class="w-5 h-5" />
                            <span
                                v-if="totalItems > 0"
                                class="absolute -top-2 -right-2 bg-amber-400 text-gray-900 font-extrabold text-[10px] w-4 h-4 rounded-full flex items-center justify-center ring-2 ring-white"
                            >
                                {{ totalItems > 99 ? '99+' : totalItems }}
                            </span>
                        </div>
                        <span class="hidden sm:inline font-bold text-xs pointer-events-none">
                            {{ totalItems > 0 ? formatPrice(subtotal) : 'Giỏ hàng' }}
                        </span>
                    </Link>

                    <!-- Cart Preview Dropdown -->
                    <div
                        v-if="isCartPreviewOpen && totalItems > 0"
                        class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 p-3 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                    >
                        <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                            <span class="text-xs font-bold text-gray-700">Giỏ hàng của bạn ({{ totalItems }})</span>
                            <Link :href="'/gio-hang'" class="text-[11px] font-semibold text-emerald-600 hover:underline">
                                Xem tất cả
                            </Link>
                        </div>

                        <div class="max-h-64 overflow-y-auto divide-y divide-gray-100 my-2">
                            <div
                                v-for="item in cartItems.slice(0, 4)"
                                :key="item.san_pham_id"
                                class="py-2 flex items-center space-x-2.5 text-xs"
                            >
                                <img
                                    :src="item.hinh_anh_chinh"
                                    :alt="item.ten_san_pham"
                                    class="w-10 h-10 rounded-lg object-cover bg-gray-100 shrink-0"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 truncate">{{ item.ten_san_pham }}</p>
                                    <p class="text-[11px] text-gray-500">
                                        {{ item.so_luong }} {{ item.don_vi_tinh }} ×
                                        <span class="font-bold text-emerald-700">{{ formatPrice(item.gia_giam || item.gia_ban) }}</span>
                                    </p>
                                </div>
                                <button
                                    @click="removeFromCart(item.san_pham_id)"
                                    class="text-gray-400 hover:text-red-500 p-1"
                                    title="Xóa"
                                >
                                    ×
                                </button>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-gray-100">
                            <div class="flex justify-between items-center mb-2.5">
                                <span class="text-xs text-gray-600">Tạm tính:</span>
                                <span class="text-sm font-extrabold text-emerald-700">{{ formatPrice(subtotal) }}</span>
                            </div>
                            <Link
                                :href="'/thanh-toan'"
                                class="w-full block text-center bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-xl text-xs transition-colors shadow-sm"
                            >
                                Tiến hành đặt hàng ngay
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK NAV CATEGORIES -->
        <nav class="border-t border-gray-100 bg-gray-50/60 hidden sm:block">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-6 text-xs font-semibold text-gray-700 py-2.5 overflow-x-auto no-scrollbar">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors shrink-0">
                    Trang chủ
                </Link>
                <Link :href="route('products.index')" class="hover:text-emerald-600 transition-colors shrink-0">
                    Tất cả sản phẩm
                </Link>
                <Link :href="route('categories.show', { slug: 'rau-cu-huu-co' })" class="hover:text-emerald-600 transition-colors shrink-0">
                    Rau củ hữu cơ
                </Link>
                <Link :href="route('categories.show', { slug: 'trai-cay-tuoi-ngon' })" class="hover:text-emerald-600 transition-colors shrink-0">
                    Trái cây tươi
                </Link>
                <Link :href="route('categories.show', { slug: 'thit-tuoi-trung-sach' })" class="hover:text-emerald-600 transition-colors shrink-0">
                    Thịt sạch & Trứng
                </Link>
                <Link :href="route('categories.show', { slug: 'thuy-hai-san-tuoi-song' })" class="hover:text-emerald-600 transition-colors shrink-0">
                    Cá hồi & Hải sản tươi
                </Link>
                <Link :href="route('categories.show', { slug: 'sua-bo-thuc-pham-che-bien' })" class="hover:text-emerald-600 transition-colors shrink-0">
                    Sữa bơ Dalatmilk
                </Link>
                <Link :href="route('products.index', { is_fresh: true })" class="text-emerald-700 font-bold hover:underline shrink-0 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-ping"></span>
                    Hàng tươi mới hôm nay
                </Link>
            </div>
        </nav>
    </header>
</template>
