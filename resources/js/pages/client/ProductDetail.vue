<script setup lang="ts">
import ProductCard from '@/components/storefront/ProductCard.vue';
import { useCart } from '@/composables/useCart';
import { useToast } from '@/composables/useToast';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    CheckCircle,
    ChevronRight,
    Clock,
    MapPin,
    MessageSquare,
    RefreshCw,
    Send,
    ShieldCheck,
    ShoppingBag,
    Snowflake,
    Sparkles,
    Star,
    Truck,
    UserCheck,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    product: {
        id: number;
        ten_san_pham: string;
        duong_dan_seo: string;
        ma_va_vach?: string;
        gia_ban: number;
        gia_giam?: number | null;
        don_vi_tinh: string;
        quy_cach?: string;
        hinh_anh_chinh: string;
        dieu_kien_bao_quan?: string;
        han_su_dung_ngay?: number;
        mo_ta?: string;
        la_tuoi_song?: boolean;
        danh_muc?: any;
        hinh_anhs?: Array<any>;
        gia_theo_cua_hang?: Array<any>;
        ton_khos?: Array<any>;
        danh_gias?: Array<any>;
    };
    earliestLot?: {
        id: number;
        so_lo: string;
        ngay_san_xuat: string;
        ngay_het_han: string;
        so_luong_ton: number;
    } | null;
    relatedProducts?: Array<any>;
    stores?: Array<any>;
}>();

const page = usePage();
const toast = useToast();
const { addToCart, selectedStoreId, setStore, cartItems, saveCart } = useCart();

const quantity = ref(1);
const activeImage = ref(props.product.hinh_anh_chinh);
const reviewRating = ref(5);
const reviewContent = ref('');
const isSubmittingReview = ref(false);

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('vi-VN');
}

const activeStore = computed(() => {
    return props.stores?.find((s) => s.id === selectedStoreId.value) || props.stores?.[0];
});

function getStorePrice(storeId: number) {
    const storeGia = props.product.gia_theo_cua_hang?.find((g: any) => Number(g.cua_hang_id) === Number(storeId));
    if (storeGia) {
        return {
            gia_ban: Number(storeGia.gia_ban),
            gia_giam: storeGia.gia_giam !== null && storeGia.gia_giam !== undefined ? Number(storeGia.gia_giam) : null,
            co_gia_rieng: true,
        };
    }
    return {
        gia_ban: Number(props.product.gia_ban),
        gia_giam: props.product.gia_giam ? Number(props.product.gia_giam) : null,
        co_gia_rieng: false,
    };
}

const currentStorePricing = computed(() => getStorePrice(selectedStoreId.value));

const currentPrice = computed(() => {
    return currentStorePricing.value.gia_giam ?? currentStorePricing.value.gia_ban;
});

const discountPercent = computed(() => {
    if (!currentStorePricing.value.gia_giam) return 0;
    return Math.round(((currentStorePricing.value.gia_ban - currentStorePricing.value.gia_giam) / currentStorePricing.value.gia_ban) * 100);
});

function switchToStore(store: any) {
    if (Number(selectedStoreId.value) === Number(store.id)) return;

    if (cartItems.value.length > 0) {
        const confirmed = window.confirm(
            `Giỏ hàng của bạn đang có ${cartItems.value.length} sản phẩm từ chi nhánh hiện tại. Khi chuyển sang chi nhánh "${store.ten_cua_hang}", giỏ hàng sẽ được tự động xóa sạch để bạn chọn sản phẩm mới. Bạn có đồng ý chuyển không?`,
        );
        if (!confirmed) return;

        // Bấm OK -> Xóa sạch giỏ hàng trên client ngay lập tức
        cartItems.value = [];
        selectedStoreId.value = store.id;
        saveCart();

        // Gửi xóa giỏ hàng trên server kèm đổi chi nhánh
        router.post(
            route('store.select'),
            { store_id: store.id, clear_cart: true },
            {
                preserveScroll: true,
                onSuccess: () => {
                    cartItems.value = [];
                    saveCart();
                    toast.success(`Đã chuyển sang chi nhánh "${store.ten_cua_hang}" và làm mới giỏ hàng!`);
                },
            },
        );
        return;
    }

    setStore(store.id);
    router.post(
        route('store.select'),
        { store_id: store.id },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Đã chuyển sang chi nhánh "${store.ten_cua_hang}"! Bảng giá đã được cập nhật.`);
            },
        },
    );
}

function handleAddToCart() {
    addToCart(props.product, quantity.value);
    toast.success(`Đã thêm ${quantity.value} ${props.product.don_vi_tinh} vào giỏ hàng!`);
}

function handleBuyNow() {
    addToCart(props.product, quantity.value);
    router.visit(route('checkout.index'));
}

// Average rating
const averageRating = computed(() => {
    if (!props.product.danh_gias || props.product.danh_gias.length === 0) return 5.0;
    const total = props.product.danh_gias.reduce((sum, r) => sum + r.so_sao, 0);
    return (total / props.product.danh_gias.length).toFixed(1);
});

function submitReview() {
    if (!page.props.auth?.user) {
        toast.warning('Vui lòng đăng nhập để gửi đánh giá sản phẩm.');
        router.visit(route('login'));
        return;
    }

    if (!reviewContent.value.trim()) {
        toast.warning('Vui lòng nhập lời nhận xét hoặc cảm nhận của bạn.');
        return;
    }

    isSubmittingReview.value = true;
    router.post(
        route('account.review.store'),
        {
            san_pham_id: props.product.id,
            so_sao: reviewRating.value,
            noi_dung: reviewContent.value.trim(),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Cảm ơn bạn đã gửi đánh giá cho sản phẩm!');
                reviewContent.value = '';
                reviewRating.value = 5;
            },
            onError: () => {
                toast.error('Có lỗi xảy ra khi gửi đánh giá.');
            },
            onFinish: () => {
                isSubmittingReview.value = false;
            },
        },
    );
}
</script>

<template>
    <StoreLayout :title="product.ten_san_pham" :stores="stores">
        <!-- BREADCRUMBS -->
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-7xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <Link :href="route('products.index')" class="transition-colors hover:text-emerald-600">Sản phẩm</Link>
                <template v-if="product.danh_muc">
                    <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                    <Link :href="'/danh-muc/' + product.danh_muc.duong_dan_seo" class="transition-colors hover:text-emerald-600">
                        {{ product.danh_muc.ten_danh_muc }}
                    </Link>
                </template>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="truncate font-semibold text-gray-900">{{ product.ten_san_pham }}</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8">
            <!-- MAIN PRODUCT OVERVIEW -->
            <div class="mb-12 grid grid-cols-1 gap-8 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-10 md:grid-cols-2 lg:gap-12">
                <!-- LEFT: IMAGE GALLERY -->
                <div>
                    <!-- Main image view -->
                    <div class="relative mb-4 aspect-square overflow-hidden rounded-2xl border border-gray-100 bg-gray-50">
                        <img :src="activeImage || product.hinh_anh_chinh" :alt="product.ten_san_pham" class="h-full w-full object-cover" />
                        <span
                            v-if="discountPercent > 0"
                            class="absolute left-4 top-4 rounded-full bg-red-600 px-3 py-1 text-xs font-black text-white shadow-lg"
                        >
                            -{{ discountPercent }}%
                        </span>
                        <span
                            v-if="product.la_tuoi_song"
                            class="absolute right-4 top-4 flex items-center rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white shadow-md"
                        >
                            <Sparkles class="mr-1 h-3.5 w-3.5" /> Tươi mới hôm nay
                        </span>
                    </div>

                    <!-- Thumbnails list -->
                    <div class="flex items-center space-x-3 overflow-x-auto pb-2">
                        <button
                            @click="activeImage = product.hinh_anh_chinh"
                            class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 transition-all"
                            :class="
                                activeImage === product.hinh_anh_chinh
                                    ? 'border-emerald-600 ring-2 ring-emerald-500/20'
                                    : 'border-gray-200 opacity-70 hover:opacity-100'
                            "
                        >
                            <img :src="product.hinh_anh_chinh" :alt="product.ten_san_pham" class="h-full w-full object-cover" />
                        </button>
                        <button
                            v-for="img in product.hinh_anhs"
                            :key="img.id"
                            @click="activeImage = img.duong_dan_anh"
                            class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 transition-all"
                            :class="
                                activeImage === img.duong_dan_anh
                                    ? 'border-emerald-600 ring-2 ring-emerald-500/20'
                                    : 'border-gray-200 opacity-70 hover:opacity-100'
                            "
                        >
                            <img :src="img.duong_dan_anh" :alt="product.ten_san_pham" class="h-full w-full object-cover" />
                        </button>
                    </div>
                </div>

                <!-- RIGHT: DETAILS & ACTIONS -->
                <div class="flex flex-col justify-between">
                    <div>
                        <!-- Category & Barcode -->
                        <div class="mb-2 flex items-center justify-between text-xs text-gray-500">
                            <span v-if="product.danh_muc" class="font-bold uppercase tracking-wider text-emerald-700">
                                {{ product.danh_muc.ten_danh_muc }}
                            </span>
                            <span v-if="product.ma_va_vach" class="rounded bg-gray-100 px-2 py-0.5 font-mono">
                                Barcode: {{ product.ma_va_vach }}
                            </span>
                        </div>

                        <!-- Product Title -->
                        <h1 class="mb-3 text-2xl font-black leading-snug text-gray-900 sm:text-3xl">
                            {{ product.ten_san_pham }}
                        </h1>

                        <!-- Ratings -->
                        <div class="mb-4 flex items-center space-x-2 text-xs text-gray-500">
                            <div class="flex items-center text-amber-500">
                                <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
                                <span class="ml-1 font-bold text-gray-900">{{ averageRating }}</span>
                            </div>
                            <span>•</span>
                            <span>{{ product.danh_gias?.length || 0 }} Đánh giá của khách hàng</span>
                        </div>

                        <!-- PRICE STRIP -->
                        <div class="mb-6 rounded-2xl border border-gray-100 bg-gray-50 p-4">
                            <div class="flex items-baseline space-x-3">
                                <span class="text-3xl font-black tracking-tight text-emerald-700">
                                    {{ formatPrice(currentPrice) }}
                                </span>
                                <span class="text-sm font-semibold text-gray-500">/ {{ product.don_vi_tinh }}</span>
                                <span v-if="currentStorePricing.gia_giam" class="text-base text-gray-400 line-through">
                                    {{ formatPrice(currentStorePricing.gia_ban) }}
                                </span>
                            </div>
                            <div
                                v-if="currentStorePricing.co_gia_rieng"
                                class="mt-2 flex w-fit items-center rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"
                            >
                                <Sparkles class="mr-1 h-3.5 w-3.5 text-emerald-600" />
                                Bảng giá riêng tại chi nhánh: {{ activeStore?.ten_cua_hang }}
                            </div>
                        </div>

                        <!-- SPECIFICATIONS & PRESERVATION -->
                        <div class="mb-6 grid grid-cols-2 gap-3 text-xs">
                            <!-- LIVE EXPIRY CAM KẾT DATE TƯƠI MỚI (FEFO) -->
                            <div
                                v-if="earliestLot"
                                class="shadow-2xs col-span-2 flex items-center justify-between rounded-2xl border border-emerald-200/90 bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50/70 p-3"
                            >
                                <div class="flex items-center space-x-2.5">
                                    <div class="shadow-xs flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                        <Sparkles class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="flex items-center space-x-1.5">
                                            <span class="text-[11px] font-black uppercase tracking-wider text-emerald-900"
                                                >Cam kết Date tươi mới</span
                                            >
                                            <span class="rounded-full bg-emerald-600 px-1.5 py-0.5 text-[9px] font-black text-white">FEFO</span>
                                        </div>
                                        <p class="text-xs font-medium text-emerald-800">
                                            Lô xuất hiện tại (HSD):
                                            <strong class="font-black text-emerald-900">{{ formatDate(earliestLot.ngay_het_han) }}</strong>
                                            <span class="ml-1 text-[10px] text-emerald-700/70">(Lô #{{ earliestLot.so_lo }})</span>
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="shadow-2xs shrink-0 rounded-xl border border-emerald-200 bg-white px-2.5 py-1 text-[11px] font-bold text-emerald-800"
                                >
                                    Còn {{ earliestLot.so_luong_ton }} {{ product.don_vi_tinh }}
                                </span>
                            </div>

                            <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                                <span class="mb-0.5 block text-gray-400">Quy cách đóng gói:</span>
                                <strong class="text-gray-800">{{ product.quy_cach || 'Từng ' + product.don_vi_tinh }}</strong>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                                <span class="mb-0.5 block text-gray-400">Bảo quản khuyến nghị:</span>
                                <strong class="flex items-center text-gray-800">
                                    <Snowflake class="mr-1 h-3.5 w-3.5 text-sky-600" />
                                    {{
                                        product.dieu_kien_bao_quan === 'mat'
                                            ? 'Ngăn mát 0 - 4°C'
                                            : product.dieu_kien_bao_quan === 'dong_lanh'
                                              ? 'Đông lạnh -18°C'
                                              : 'Nhiệt độ thường'
                                    }}
                                </strong>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                                <span class="mb-0.5 block text-gray-400">Hạn sử dụng khuyên dùng:</span>
                                <strong class="flex items-center text-gray-800">
                                    <Clock class="mr-1 h-3.5 w-3.5 text-emerald-600" />
                                    {{ product.han_su_dung_ngay ? product.han_su_dung_ngay + ' ngày' : 'Sử dụng tốt nhất trong ngày' }}
                                </strong>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                                <span class="mb-0.5 block text-gray-400">Tiêu chuẩn canh tác:</span>
                                <strong class="flex items-center text-emerald-700">
                                    <CheckCircle class="mr-1 h-3.5 w-3.5 text-emerald-600" />
                                    VietGAP / Organic
                                </strong>
                            </div>
                        </div>

                        <!-- QUANTITY PICKER & ACTION BUTTONS -->
                        <div class="space-y-4 border-t border-gray-100 pt-4">
                            <div class="flex items-center space-x-4">
                                <span class="text-xs font-bold text-gray-700">Số lượng:</span>
                                <div class="flex items-center rounded-xl border border-gray-200 bg-gray-50 p-1">
                                    <button
                                        @click="quantity = Math.max(1, quantity - 1)"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-white font-bold text-gray-700 shadow-sm transition-colors hover:bg-gray-100"
                                    >
                                        -
                                    </button>
                                    <input
                                        type="number"
                                        v-model="quantity"
                                        min="1"
                                        class="w-12 border-none bg-transparent text-center text-xs font-black focus:outline-none"
                                    />
                                    <button
                                        @click="quantity++"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-white font-bold text-gray-700 shadow-sm transition-colors hover:bg-gray-100"
                                    >
                                        +
                                    </button>
                                </div>
                                <span class="text-xs font-medium text-gray-500">
                                    Thành tiền:
                                    <strong class="text-sm font-extrabold text-emerald-700">{{ formatPrice(currentPrice * quantity) }}</strong>
                                </span>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button
                                    @click="handleAddToCart"
                                    class="hover:scale-102 active:scale-98 flex flex-1 items-center justify-center space-x-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-3.5 text-sm font-bold text-emerald-800 transition-all hover:bg-emerald-100"
                                    id="detail-add-btn"
                                >
                                    <ShoppingBag class="h-4 w-4 text-emerald-600" />
                                    <span>Thêm vào giỏ hàng</span>
                                </button>
                                <button
                                    @click="handleBuyNow"
                                    class="hover:scale-102 active:scale-98 flex-1 rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-emerald-600/30 transition-all hover:bg-emerald-700"
                                    id="detail-buynow-btn"
                                >
                                    Mua ngay giao trong 2H
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- COMMITMENT STRIP -->
                    <div class="mt-8 grid grid-cols-3 gap-2 border-t border-gray-100 pt-6 text-center text-[11px] text-gray-500">
                        <div class="flex flex-col items-center">
                            <Truck class="mb-1 h-5 w-5 text-emerald-600" />
                            <span>Giao siêu tốc 2h</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <RefreshCw class="mb-1 h-5 w-5 text-emerald-600" />
                            <span>Đổi trả trong 24h</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <ShieldCheck class="mb-1 h-5 w-5 text-emerald-600" />
                            <span>100% Chính gốc</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRODUCT DESCRIPTION & STORE AVAILABILITY TABS -->
            <div class="mb-12 grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Description -->
                <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8 lg:col-span-2">
                    <h3 class="mb-4 border-b border-gray-100 pb-3 text-lg font-black text-gray-900">Mô Tả & Thông Tin Chi Tiết</h3>
                    <div class="prose prose-emerald space-y-4 text-xs leading-relaxed text-gray-600 sm:text-sm">
                        <p>{{ product.mo_ta }}</p>
                        <h4 class="mt-4 text-sm font-bold text-gray-800">Hướng dẫn bảo quản & chế biến:</h4>
                        <ul class="list-disc space-y-1 pl-5 text-xs text-gray-600">
                            <li>Rửa sạch nhẹ nhàng dưới vòi nước chảy trước khi chế biến.</li>
                            <li>Nếu chưa dùng ngay, bọc kín trong túi zip hoặc hộp kín và để trong ngăn mát 0-4°C.</li>
                            <li>Sử dụng trong vòng thời gian khuyến nghị để giữ trọn vẹn hương vị và vi chất dinh dưỡng.</li>
                        </ul>
                    </div>
                </div>

                <!-- Store Availability -->
                <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="mb-3 flex items-center text-sm font-black text-gray-900">
                        <MapPin class="mr-1.5 h-4 w-4 text-emerald-600" /> Tình Trạng & Giá Theo Chi Nhánh
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div
                            v-for="store in stores"
                            :key="store.id"
                            class="rounded-2xl border p-3.5 transition-all"
                            :class="
                                store.id === selectedStoreId
                                    ? 'border-emerald-300 bg-emerald-50/70 ring-2 ring-emerald-500/10'
                                    : 'border-gray-100 bg-gray-50'
                            "
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="flex items-center font-bold text-gray-800">
                                        {{ store.ten_cua_hang }}
                                        <span
                                            v-if="store.id === selectedStoreId"
                                            class="ml-2 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-black text-white"
                                        >
                                            Đang chọn
                                        </span>
                                    </p>
                                    <p class="line-clamp-1 text-[11px] text-gray-500">{{ store.dia_chi }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-xs font-black text-emerald-700">
                                        {{ formatPrice(getStorePrice(store.id).gia_giam ?? getStorePrice(store.id).gia_ban) }}
                                    </p>
                                    <p v-if="getStorePrice(store.id).gia_giam" class="text-[10px] text-gray-400 line-through">
                                        {{ formatPrice(getStorePrice(store.id).gia_ban) }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2.5 flex items-center justify-between border-t border-gray-200/60 pt-2">
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                    <CheckCircle class="mr-1 h-3 w-3 text-emerald-600" /> Sẵn sàng giao 2h
                                </span>
                                <button
                                    v-if="store.id !== selectedStoreId"
                                    @click="switchToStore(store)"
                                    type="button"
                                    class="cursor-pointer text-[11px] font-bold text-emerald-700 hover:text-emerald-800 hover:underline"
                                >
                                    Chọn chi nhánh này →
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CUSTOMER REVIEWS & FEEDBACK -->
            <div class="mb-12 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col justify-between gap-4 border-b border-gray-100 pb-6 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="flex items-center text-xl font-black text-gray-900">
                            <Star class="mr-2 h-5 w-5 fill-amber-400 text-amber-400" />
                            Đánh Giá Từ Khách Hàng
                        </h3>
                        <p class="mt-1 text-xs text-gray-500">Nhận xét thực tế từ những khách hàng đã mua và trải nghiệm sản phẩm này.</p>
                    </div>

                    <div class="flex w-fit items-center space-x-4 rounded-2xl border border-amber-100 bg-amber-50/60 px-4 py-2.5">
                        <div class="font-mono text-3xl font-black text-amber-600">{{ averageRating }}</div>
                        <div>
                            <div class="flex items-center text-amber-400">
                                <Star
                                    v-for="s in 5"
                                    :key="s"
                                    class="h-4 w-4"
                                    :class="s <= Math.round(Number(averageRating)) ? 'fill-amber-400 text-amber-400' : 'text-gray-300'"
                                />
                            </div>
                            <span class="mt-0.5 block text-[11px] font-bold text-gray-600"> {{ product.danh_gias?.length || 0 }} lượt đánh giá </span>
                        </div>
                    </div>
                </div>

                <!-- WRITE A REVIEW FORM -->
                <div class="my-8 rounded-2xl border border-gray-200/80 bg-gray-50 p-5">
                    <h4 class="mb-3 flex items-center text-sm font-black text-gray-900">
                        <MessageSquare class="mr-2 h-4 w-4 text-emerald-600" />
                        Gửi Đánh Giá Của Bạn
                    </h4>

                    <div v-if="page.props.auth?.user" class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <span class="text-xs font-bold text-gray-700">Mức độ hài lòng:</span>
                            <div class="flex items-center space-x-1">
                                <button
                                    v-for="star in 5"
                                    :key="star"
                                    type="button"
                                    @click="reviewRating = star"
                                    class="cursor-pointer p-1 transition-transform hover:scale-110"
                                >
                                    <Star
                                        class="h-6 w-6 transition-colors"
                                        :class="star <= reviewRating ? 'fill-amber-400 text-amber-400' : 'text-gray-300 hover:text-amber-300'"
                                    />
                                </button>
                            </div>
                            <span class="text-xs font-bold text-amber-600">
                                {{
                                    reviewRating === 5
                                        ? 'Tuyệt vời, rất tươi ngon!'
                                        : reviewRating === 4
                                          ? 'Hài lòng'
                                          : reviewRating === 3
                                            ? 'Bình thường'
                                            : 'Chưa hài lòng'
                                }}
                            </span>
                        </div>

                        <div>
                            <textarea
                                v-model="reviewContent"
                                rows="3"
                                placeholder="Chia sẻ cảm nhận chi tiết về độ tươi, đóng gói hoặc giao hàng của sản phẩm..."
                                class="shadow-xs w-full rounded-xl border-gray-300 bg-white p-3 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                            ></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button
                                @click="submitReview"
                                :disabled="isSubmittingReview"
                                class="inline-flex cursor-pointer items-center space-x-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-md transition hover:bg-emerald-700 disabled:opacity-50"
                            >
                                <Send class="h-3.5 w-3.5" />
                                <span>{{ isSubmittingReview ? 'Đang gửi...' : 'Gửi Đánh Giá Ngay' }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-else class="space-y-2 py-4 text-center">
                        <p class="text-xs text-gray-600">Bạn cần đăng nhập tài khoản để có thể đánh giá và chia sẻ trải nghiệm về sản phẩm này.</p>
                        <Link
                            :href="route('login')"
                            class="inline-block rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700"
                        >
                            Đăng nhập để đánh giá
                        </Link>
                    </div>
                </div>

                <!-- REVIEWS LIST -->
                <div v-if="product.danh_gias && product.danh_gias.length > 0" class="divide-y divide-gray-100">
                    <div v-for="rev in product.danh_gias" :key="rev.id" class="space-y-2 py-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-emerald-200 bg-emerald-100 text-xs font-black text-emerald-800"
                                >
                                    <img v-if="rev.user?.avatar" :src="rev.user.avatar" class="h-full w-full object-cover" />
                                    <span v-else>{{ rev.user?.name ? rev.user.name.charAt(0).toUpperCase() : 'K' }}</span>
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h5 class="text-xs font-bold text-gray-900">{{ rev.user?.name || 'Khách hàng FreshFood' }}</h5>
                                        <span
                                            class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700"
                                        >
                                            <UserCheck class="mr-1 h-3 w-3 text-emerald-600" /> Đã mua hàng
                                        </span>
                                    </div>
                                    <div class="mt-0.5 flex items-center space-x-2">
                                        <div class="flex items-center text-amber-400">
                                            <Star
                                                v-for="s in 5"
                                                :key="s"
                                                class="h-3 w-3"
                                                :class="s <= rev.so_sao ? 'fill-amber-400 text-amber-400' : 'text-gray-300'"
                                            />
                                        </div>
                                        <span class="text-[10px] text-gray-400">
                                            {{ new Date(rev.created_at).toLocaleDateString('vi-VN') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="pl-12 text-xs leading-relaxed text-gray-700">
                            {{ rev.noi_dung || 'Khách hàng không để lại nhận xét bằng lời.' }}
                        </p>
                    </div>
                </div>

                <div v-else class="space-y-2 py-8 text-center text-gray-400">
                    <MessageSquare class="mx-auto h-8 w-8 text-gray-300" />
                    <p class="text-xs">Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên trải nghiệm và chia sẻ!</p>
                </div>
            </div>

            <!-- RELATED PRODUCTS -->
            <div v-if="relatedProducts?.length > 0" class="mb-12">
                <h3 class="mb-6 text-xl font-black text-gray-900">Sản Phẩm Thường Được Mua Kèm</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <ProductCard v-for="rel in relatedProducts" :key="rel.id" :product="rel" />
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
