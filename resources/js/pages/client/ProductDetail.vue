<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import ProductCard from '@/components/storefront/ProductCard.vue';
import { useCart } from '@/composables/useCart';
import {
    ShoppingBag,
    Truck,
    ShieldCheck,
    RefreshCw,
    Star,
    Snowflake,
    Clock,
    MapPin,
    CheckCircle,
    ChevronRight,
    Sparkles,
} from 'lucide-vue-next';

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
    relatedProducts?: Array<any>;
    stores?: Array<any>;
}>();

const { addToCart, selectedStoreId, setStore, showToast, cartItems, clearCart, saveCart } = useCart();

const quantity = ref(1);
const activeImage = ref(props.product.hinh_anh_chinh);

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

const activeStore = computed(() => {
    return props.stores?.find((s) => s.id === selectedStoreId.value) || props.stores?.[0];
});

function getStorePrice(storeId: number) {
    const storeGia = props.product.gia_theo_cua_hang?.find(
        (g: any) => Number(g.cua_hang_id) === Number(storeId)
    );
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
    return Math.round(
        ((currentStorePricing.value.gia_ban - currentStorePricing.value.gia_giam) / currentStorePricing.value.gia_ban) * 100
    );
});

function switchToStore(store: any) {
    if (Number(selectedStoreId.value) === Number(store.id)) return;

    if (cartItems.value.length > 0) {
        const confirmed = window.confirm(
            `Giỏ hàng của bạn đang có ${cartItems.value.length} sản phẩm từ chi nhánh hiện tại. Khi chuyển sang chi nhánh "${store.ten_cua_hang}", giỏ hàng sẽ được tự động xóa sạch để bạn chọn sản phẩm mới. Bạn có đồng ý chuyển không?`
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
                    showToast(`Đã chuyển sang chi nhánh "${store.ten_cua_hang}" và làm mới giỏ hàng!`, 'success');
                },
            }
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
                showToast(`Đã chuyển sang chi nhánh "${store.ten_cua_hang}"! Bảng giá đã được cập nhật.`, 'success');
            },
        }
    );
}

function handleAddToCart() {
    addToCart(props.product, quantity.value);
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
</script>

<template>
    <StoreLayout :title="product.ten_san_pham" :stores="stores">
        <!-- BREADCRUMBS -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <Link :href="route('products.index')" class="hover:text-emerald-600 transition-colors">Sản phẩm</Link>
                <template v-if="product.danh_muc">
                    <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                    <Link
                        :href="'/danh-muc/' + product.danh_muc.duong_dan_seo"
                        class="hover:text-emerald-600 transition-colors"
                    >
                        {{ product.danh_muc.ten_danh_muc }}
                    </Link>
                </template>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold truncate">{{ product.ten_san_pham }}</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- MAIN PRODUCT OVERVIEW -->
            <div class="bg-white rounded-3xl p-6 sm:p-10 border border-gray-100 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 mb-12">
                <!-- LEFT: IMAGE GALLERY -->
                <div>
                    <!-- Main image view -->
                    <div class="aspect-square rounded-2xl overflow-hidden bg-gray-50 border border-gray-100 relative mb-4">
                        <img
                            :src="activeImage || product.hinh_anh_chinh"
                            :alt="product.ten_san_pham"
                            class="w-full h-full object-cover"
                        />
                        <span
                            v-if="discountPercent > 0"
                            class="absolute top-4 left-4 bg-red-600 text-white font-black text-xs px-3 py-1 rounded-full shadow-lg"
                        >
                            -{{ discountPercent }}%
                        </span>
                        <span
                            v-if="product.la_tuoi_song"
                            class="absolute top-4 right-4 bg-emerald-600 text-white font-bold text-xs px-3 py-1 rounded-full shadow-md flex items-center"
                        >
                            <Sparkles class="w-3.5 h-3.5 mr-1" /> Tươi mới hôm nay
                        </span>
                    </div>

                    <!-- Thumbnails list -->
                    <div class="flex items-center space-x-3 overflow-x-auto pb-2">
                        <button
                            @click="activeImage = product.hinh_anh_chinh"
                            class="w-16 h-16 rounded-xl overflow-hidden border-2 transition-all shrink-0"
                            :class="activeImage === product.hinh_anh_chinh ? 'border-emerald-600 ring-2 ring-emerald-500/20' : 'border-gray-200 opacity-70 hover:opacity-100'"
                        >
                            <img :src="product.hinh_anh_chinh" :alt="product.ten_san_pham" class="w-full h-full object-cover" />
                        </button>
                        <button
                            v-for="img in product.hinh_anhs"
                            :key="img.id"
                            @click="activeImage = img.duong_dan_anh"
                            class="w-16 h-16 rounded-xl overflow-hidden border-2 transition-all shrink-0"
                            :class="activeImage === img.duong_dan_anh ? 'border-emerald-600 ring-2 ring-emerald-500/20' : 'border-gray-200 opacity-70 hover:opacity-100'"
                        >
                            <img :src="img.duong_dan_anh" :alt="product.ten_san_pham" class="w-full h-full object-cover" />
                        </button>
                    </div>
                </div>

                <!-- RIGHT: DETAILS & ACTIONS -->
                <div class="flex flex-col justify-between">
                    <div>
                        <!-- Category & Barcode -->
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                            <span v-if="product.danh_muc" class="text-emerald-700 font-bold uppercase tracking-wider">
                                {{ product.danh_muc.ten_danh_muc }}
                            </span>
                            <span v-if="product.ma_va_vach" class="font-mono bg-gray-100 px-2 py-0.5 rounded">
                                Barcode: {{ product.ma_va_vach }}
                            </span>
                        </div>

                        <!-- Product Title -->
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 leading-snug mb-3">
                            {{ product.ten_san_pham }}
                        </h1>

                        <!-- Ratings -->
                        <div class="flex items-center space-x-2 text-xs text-gray-500 mb-4">
                            <div class="flex items-center text-amber-500">
                                <Star class="w-4 h-4 fill-amber-400 text-amber-400" />
                                <span class="font-bold ml-1 text-gray-900">{{ averageRating }}</span>
                            </div>
                            <span>•</span>
                            <span>{{ product.danh_gias?.length || 0 }} Đánh giá của khách hàng</span>
                        </div>

                        <!-- PRICE STRIP -->
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 mb-6">
                            <div class="flex items-baseline space-x-3">
                                <span class="text-3xl font-black text-emerald-700 tracking-tight">
                                    {{ formatPrice(currentPrice) }}
                                </span>
                                <span class="text-sm font-semibold text-gray-500">/ {{ product.don_vi_tinh }}</span>
                                <span
                                    v-if="currentStorePricing.gia_giam"
                                    class="text-base text-gray-400 line-through"
                                >
                                    {{ formatPrice(currentStorePricing.gia_ban) }}
                                </span>
                            </div>
                            <div v-if="currentStorePricing.co_gia_rieng" class="mt-2 flex items-center text-xs text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-lg w-fit">
                                <Sparkles class="w-3.5 h-3.5 mr-1 text-emerald-600" />
                                Bảng giá riêng tại chi nhánh: {{ activeStore?.ten_cua_hang }}
                            </div>
                        </div>

                        <!-- SPECIFICATIONS & PRESERVATION -->
                        <div class="grid grid-cols-2 gap-3 text-xs mb-6">
                            <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                                <span class="text-gray-400 block mb-0.5">Quy cách đóng gói:</span>
                                <strong class="text-gray-800">{{ product.quy_cach || ('Từng ' + product.don_vi_tinh) }}</strong>
                            </div>
                            <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                                <span class="text-gray-400 block mb-0.5">Bảo quản khuyến nghị:</span>
                                <strong class="text-gray-800 flex items-center">
                                    <Snowflake class="w-3.5 h-3.5 mr-1 text-sky-600" />
                                    {{ product.dieu_kien_bao_quan === 'mat' ? 'Ngăn mát 0 - 4°C' : (product.dieu_kien_bao_quan === 'dong_lanh' ? 'Đông lạnh -18°C' : 'Nhiệt độ thường') }}
                                </strong>
                            </div>
                            <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                                <span class="text-gray-400 block mb-0.5">Hạn sử dụng khuyên dùng:</span>
                                <strong class="text-gray-800 flex items-center">
                                    <Clock class="w-3.5 h-3.5 mr-1 text-emerald-600" />
                                    {{ product.han_su_dung_ngay ? product.han_su_dung_ngay + ' ngày' : 'Sử dụng tốt nhất trong ngày' }}
                                </strong>
                            </div>
                            <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                                <span class="text-gray-400 block mb-0.5">Tiêu chuẩn canh tác:</span>
                                <strong class="text-emerald-700 flex items-center">
                                    <CheckCircle class="w-3.5 h-3.5 mr-1 text-emerald-600" />
                                    VietGAP / Organic
                                </strong>
                            </div>
                        </div>

                        <!-- QUANTITY PICKER & ACTION BUTTONS -->
                        <div class="space-y-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center space-x-4">
                                <span class="text-xs font-bold text-gray-700">Số lượng:</span>
                                <div class="flex items-center border border-gray-200 rounded-xl bg-gray-50 p-1">
                                    <button
                                        @click="quantity = Math.max(1, quantity - 1)"
                                        class="w-8 h-8 rounded-lg bg-white shadow-sm font-bold text-gray-700 hover:bg-gray-100 transition-colors flex items-center justify-center"
                                    >
                                        -
                                    </button>
                                    <input
                                        type="number"
                                        v-model="quantity"
                                        min="1"
                                        class="w-12 text-center text-xs font-black bg-transparent border-none focus:outline-none"
                                    />
                                    <button
                                        @click="quantity++"
                                        class="w-8 h-8 rounded-lg bg-white shadow-sm font-bold text-gray-700 hover:bg-gray-100 transition-colors flex items-center justify-center"
                                    >
                                        +
                                    </button>
                                </div>
                                <span class="text-xs text-gray-500 font-medium">
                                    Thành tiền: <strong class="text-emerald-700 text-sm font-extrabold">{{ formatPrice(currentPrice * quantity) }}</strong>
                                </span>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <button
                                    @click="handleAddToCart"
                                    class="flex-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold py-3.5 px-6 rounded-2xl flex items-center justify-center space-x-2 border border-emerald-200 transition-all hover:scale-102 active:scale-98 text-sm"
                                    id="detail-add-btn"
                                >
                                    <ShoppingBag class="w-4 h-4 text-emerald-600" />
                                    <span>Thêm vào giỏ hàng</span>
                                </button>
                                <button
                                    @click="handleBuyNow"
                                    class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3.5 px-6 rounded-2xl shadow-lg shadow-emerald-600/30 transition-all hover:scale-102 active:scale-98 text-sm"
                                    id="detail-buynow-btn"
                                >
                                    Mua ngay giao trong 2H
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- COMMITMENT STRIP -->
                    <div class="mt-8 pt-6 border-t border-gray-100 grid grid-cols-3 gap-2 text-center text-[11px] text-gray-500">
                        <div class="flex flex-col items-center">
                            <Truck class="w-5 h-5 text-emerald-600 mb-1" />
                            <span>Giao siêu tốc 2h</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <RefreshCw class="w-5 h-5 text-emerald-600 mb-1" />
                            <span>Đổi trả trong 24h</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <ShieldCheck class="w-5 h-5 text-emerald-600 mb-1" />
                            <span>100% Chính gốc</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRODUCT DESCRIPTION & STORE AVAILABILITY TABS -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
                <!-- Description -->
                <div class="lg:col-span-2 bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-black text-gray-900 mb-4 pb-3 border-b border-gray-100">
                        Mô Tả & Thông Tin Chi Tiết
                    </h3>
                    <div class="prose prose-emerald text-xs sm:text-sm text-gray-600 leading-relaxed space-y-4">
                        <p>{{ product.mo_ta }}</p>
                        <h4 class="font-bold text-gray-800 text-sm mt-4">Hướng dẫn bảo quản & chế biến:</h4>
                        <ul class="list-disc pl-5 space-y-1 text-xs text-gray-600">
                            <li>Rửa sạch nhẹ nhàng dưới vòi nước chảy trước khi chế biến.</li>
                            <li>Nếu chưa dùng ngay, bọc kín trong túi zip hoặc hộp kín và để trong ngăn mát 0-4°C.</li>
                            <li>Sử dụng trong vòng thời gian khuyến nghị để giữ trọn vẹn hương vị và vi chất dinh dưỡng.</li>
                        </ul>
                    </div>
                </div>

                <!-- Store Availability -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-black text-gray-900 mb-3 flex items-center">
                        <MapPin class="w-4 h-4 mr-1.5 text-emerald-600" /> Tình Trạng & Giá Theo Chi Nhánh
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div
                            v-for="store in stores"
                            :key="store.id"
                            class="p-3.5 rounded-2xl border transition-all"
                            :class="store.id === selectedStoreId ? 'bg-emerald-50/70 border-emerald-300 ring-2 ring-emerald-500/10' : 'bg-gray-50 border-gray-100'"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-bold text-gray-800 flex items-center">
                                        {{ store.ten_cua_hang }}
                                        <span v-if="store.id === selectedStoreId" class="ml-2 text-[10px] font-black bg-emerald-600 text-white px-2 py-0.5 rounded-full">
                                            Đang chọn
                                        </span>
                                    </p>
                                    <p class="text-[11px] text-gray-500 line-clamp-1">{{ store.dia_chi }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs font-black text-emerald-700">
                                        {{ formatPrice(getStorePrice(store.id).gia_giam ?? getStorePrice(store.id).gia_ban) }}
                                    </p>
                                    <p v-if="getStorePrice(store.id).gia_giam" class="text-[10px] text-gray-400 line-through">
                                        {{ formatPrice(getStorePrice(store.id).gia_ban) }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2.5 pt-2 border-t border-gray-200/60 flex items-center justify-between">
                                <span class="inline-flex items-center text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                    <CheckCircle class="w-3 h-3 mr-1 text-emerald-600" /> Sẵn sàng giao 2h
                                </span>
                                <button
                                    v-if="store.id !== selectedStoreId"
                                    @click="switchToStore(store)"
                                    type="button"
                                    class="text-[11px] font-bold text-emerald-700 hover:text-emerald-800 hover:underline cursor-pointer"
                                >
                                    Chọn chi nhánh này →
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RELATED PRODUCTS -->
            <div v-if="relatedProducts?.length > 0" class="mb-12">
                <h3 class="text-xl font-black text-gray-900 mb-6">Sản Phẩm Thường Được Mua Kèm</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <ProductCard
                        v-for="rel in relatedProducts"
                        :key="rel.id"
                        :product="rel"
                    />
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
