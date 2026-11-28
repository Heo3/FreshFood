<script setup lang="ts">
import { useCart } from '@/composables/useCart';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ShoppingBag, Snowflake, Sparkles } from 'lucide-vue-next';

const props = defineProps<{
    product: {
        id: number;
        ten_san_pham: string;
        duong_dan_seo: string;
        gia_ban: number;
        gia_giam?: number | null;
        don_vi_tinh: string;
        quy_cach?: string;
        hinh_anh_chinh: string;
        la_tuoi_song?: boolean;
        co_gia_chi_nhanh?: boolean;
        dieu_kien_bao_quan?: string;
        gia_theo_cua_hang?: Array<any>;
        da_het_hang?: boolean;
        danh_muc?: {
            id: number;
            ten_danh_muc: string;
            duong_dan_seo: string;
        };
    };
}>();

const page = usePage();
const { addToCart } = useCart();

function handleAddToCart(e: Event) {
    e.preventDefault();
    e.stopPropagation();
    if (!page.props.auth?.user) {
        router.visit(route('login'));
        return;
    }
    addToCart(props.product);
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

const discountPercent = props.product.gia_giam ? Math.round(((props.product.gia_ban - props.product.gia_giam) / props.product.gia_ban) * 100) : 0;
</script>

<template>
    <div
        class="group relative flex flex-col justify-between overflow-hidden rounded-2xl sm:rounded-3xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:border-emerald-200 hover:shadow-xl"
    >
        <!-- BADGES -->
        <div class="pointer-events-none absolute left-2 top-2 sm:left-3 sm:top-3 z-10 flex flex-col gap-1 sm:gap-1.5">
            <!-- Out of Stock Badge -->
            <span
                v-if="product.da_het_hang"
                class="rounded-full bg-red-600 px-2 py-0.5 text-[9px] sm:text-[10px] font-black uppercase tracking-wider text-white shadow-md"
            >
                Hết hàng
            </span>

            <!-- Discount Badge -->
            <span
                v-if="discountPercent > 0"
                class="rounded-full bg-gradient-to-r from-red-600 to-rose-500 px-2 py-0.5 text-[9px] sm:text-[10px] font-black uppercase tracking-wider text-white shadow-md"
            >
                -{{ discountPercent }}%
            </span>

            <!-- Freshness Badge -->
            <span
                v-if="product.la_tuoi_song"
                class="flex items-center rounded-full bg-emerald-600/90 px-1.5 sm:px-2 py-0.5 text-[9px] sm:text-[10px] font-bold text-white shadow-sm backdrop-blur-sm"
            >
                <Sparkles class="mr-0.5 h-2.5 w-2.5 sm:h-3 sm:w-3" />
                Tươi mới
            </span>

            <!-- Branch Price Badge -->
            <span
                v-if="product.co_gia_chi_nhanh"
                class="flex items-center rounded-full bg-teal-700/90 px-1.5 sm:px-2 py-0.5 text-[8px] sm:text-[9px] font-bold text-white shadow-sm backdrop-blur-sm"
            >
                Giá chi nhánh
            </span>

            <!-- Cool preservation Badge -->
            <span
                v-if="product.dieu_kien_bao_quan === 'mat' || product.dieu_kien_bao_quan === 'dong_lanh'"
                class="flex items-center rounded-full bg-sky-600/80 px-1.5 py-0.5 text-[8px] sm:text-[9px] font-medium text-white backdrop-blur-sm"
            >
                <Snowflake class="mr-0.5 h-2.5 w-2.5" />
                {{ product.dieu_kien_bao_quan === 'mat' ? 'Lạnh 0-4°C' : 'Đông -18°C' }}
            </span>
        </div>

        <!-- PRODUCT IMAGE WITH ZOOM EFFECT -->
        <Link :href="'/san-pham/' + product.duong_dan_seo" class="relative block aspect-square cursor-pointer overflow-hidden bg-gray-50">
            <img
                :src="product.hinh_anh_chinh || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=600'"
                :alt="product.ten_san_pham"
                class="group-hover:scale-108 h-full w-full object-cover transition-transform duration-500 ease-out"
                :class="{ 'opacity-70 grayscale-[20%]': product.da_het_hang }"
                loading="lazy"
            />
            <div class="pointer-events-none absolute inset-0 bg-black/5 opacity-0 transition-opacity group-hover:opacity-100"></div>

            <!-- Out of stock overlay banner -->
            <div
                v-if="product.da_het_hang"
                class="absolute inset-0 flex items-center justify-center bg-black/25 backdrop-blur-[0.5px]"
            >
                <span class="rounded-xl bg-red-600/95 px-2.5 py-1 sm:px-3 sm:py-1.5 text-[11px] sm:text-xs font-black uppercase tracking-wider text-white shadow-lg">
                    Tạm hết hàng
                </span>
            </div>
        </Link>

        <!-- PRODUCT CONTENT -->
        <div class="flex flex-1 flex-col justify-between p-3 sm:p-4">
            <div>
                <!-- Category name -->
                <p v-if="product.danh_muc" class="mb-0.5 sm:mb-1 line-clamp-1 text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-emerald-600">
                    {{ product.danh_muc.ten_danh_muc }}
                </p>

                <!-- Product Title -->
                <Link
                    :href="'/san-pham/' + product.duong_dan_seo"
                    class="mb-1 sm:mb-1.5 line-clamp-2 block cursor-pointer text-xs sm:text-sm font-bold leading-snug text-gray-900 transition-colors hover:text-emerald-600"
                    :title="product.ten_san_pham"
                >
                    {{ product.ten_san_pham }}
                </Link>

                <!-- Specification / Packing -->
                <p class="mb-1.5 sm:mb-2 line-clamp-1 text-[10px] sm:text-[11px] text-gray-400">
                    {{ product.quy_cach || 'Đơn vị: ' + product.don_vi_tinh }}
                </p>
            </div>

            <!-- PRICE & ADD TO CART ACTION -->
            <div class="mt-auto flex items-end justify-between gap-1.5 sm:gap-2 border-t border-gray-50 pt-2">
                <div class="min-w-0">
                    <div class="flex items-baseline space-x-1 sm:space-x-1.5">
                        <span class="text-sm sm:text-base font-black tracking-tight text-emerald-700">
                            {{ formatPrice(product.gia_giam ?? product.gia_ban) }}
                        </span>
                        <span class="text-[9px] sm:text-[10px] font-medium text-gray-500">/{{ product.don_vi_tinh }}</span>
                    </div>
                    <p v-if="product.gia_giam" class="-mt-0.5 text-[10px] sm:text-xs text-gray-400 line-through">
                        {{ formatPrice(product.gia_ban) }}
                    </p>
                </div>

                <!-- ADD TO CART BUTTON -->
                <button
                    v-if="!product.da_het_hang"
                    @click="handleAddToCart($event)"
                    class="group/btn flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-lg sm:rounded-xl bg-emerald-50 text-emerald-700 shadow-sm transition-all duration-200 hover:bg-emerald-600 hover:text-white active:scale-90"
                    title="Thêm vào giỏ hàng"
                    :id="'add-btn-' + product.id"
                >
                    <ShoppingBag class="h-3.5 w-3.5 sm:h-4 sm:w-4 transition-transform group-hover/btn:scale-110" />
                </button>
                <div
                    v-else
                    class="flex h-8 sm:h-9 items-center justify-center rounded-lg sm:rounded-xl border border-gray-200 bg-gray-100 px-2 sm:px-2.5 text-[10px] sm:text-[11px] font-bold text-gray-400 cursor-not-allowed select-none"
                    title="Sản phẩm tạm thời hết hàng trong kho"
                >
                    <span>Hết hàng</span>
                </div>
            </div>
        </div>
    </div>
</template>
