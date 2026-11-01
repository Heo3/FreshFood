<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useCart } from '@/composables/useCart';
import { ShoppingBag, Sparkles, Snowflake } from 'lucide-vue-next';

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
        danh_muc?: {
            id: number;
            ten_danh_muc: string;
            duong_dan_seo: string;
        };
    };
}>();

const { addToCart } = useCart();

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

const discountPercent = props.product.gia_giam
    ? Math.round(((props.product.gia_ban - props.product.gia_giam) / props.product.gia_ban) * 100)
    : 0;
</script>

<template>
    <div class="group bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl hover:border-emerald-200 transition-all duration-300 flex flex-col justify-between relative">
        <!-- BADGES -->
        <div class="absolute top-3 left-3 z-10 flex flex-col gap-1.5 pointer-events-none">
            <!-- Discount Badge -->
            <span
                v-if="discountPercent > 0"
                class="bg-gradient-to-r from-red-600 to-rose-500 text-white font-black text-[10px] px-2 py-0.5 rounded-full shadow-md uppercase tracking-wider"
            >
                -{{ discountPercent }}%
            </span>

            <!-- Freshness Badge -->
            <span
                v-if="product.la_tuoi_song"
                class="bg-emerald-600/90 backdrop-blur-sm text-white font-bold text-[10px] px-2 py-0.5 rounded-full shadow-sm flex items-center"
            >
                <Sparkles class="w-3 h-3 mr-0.5" />
                Tươi mới
            </span>

            <!-- Branch Price Badge -->
            <span
                v-if="product.co_gia_chi_nhanh"
                class="bg-teal-700/90 backdrop-blur-sm text-white font-bold text-[9px] px-2 py-0.5 rounded-full shadow-sm flex items-center"
            >
                Giá chi nhánh
            </span>

            <!-- Cool preservation Badge -->
            <span
                v-if="product.dieu_kien_bao_quan === 'mat' || product.dieu_kien_bao_quan === 'dong_lanh'"
                class="bg-sky-600/80 backdrop-blur-sm text-white font-medium text-[9px] px-1.5 py-0.5 rounded-full flex items-center"
            >
                <Snowflake class="w-2.5 h-2.5 mr-0.5" />
                {{ product.dieu_kien_bao_quan === 'mat' ? 'Lạnh 0-4°C' : 'Đông -18°C' }}
            </span>
        </div>

        <!-- PRODUCT IMAGE WITH ZOOM EFFECT -->
        <Link
            :href="'/san-pham/' + product.duong_dan_seo"
            class="block overflow-hidden relative aspect-square bg-gray-50 cursor-pointer"
        >
            <img
                :src="product.hinh_anh_chinh || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=600'"
                :alt="product.ten_san_pham"
                class="w-full h-full object-cover group-hover:scale-108 transition-transform duration-500 ease-out"
                loading="lazy"
            />
            <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
        </Link>

        <!-- PRODUCT CONTENT -->
        <div class="p-4 flex-1 flex flex-col justify-between">
            <div>
                <!-- Category name -->
                <p v-if="product.danh_muc" class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider mb-1 line-clamp-1">
                    {{ product.danh_muc.ten_danh_muc }}
                </p>

                <!-- Product Title -->
                <Link
                    :href="'/san-pham/' + product.duong_dan_seo"
                    class="font-bold text-gray-900 text-sm hover:text-emerald-600 transition-colors line-clamp-2 leading-snug mb-1.5 cursor-pointer block"
                    :title="product.ten_san_pham"
                >
                    {{ product.ten_san_pham }}
                </Link>

                <!-- Specification / Packing -->
                <p class="text-[11px] text-gray-400 line-clamp-1 mb-2">
                    {{ product.quy_cach || ('Đơn vị: ' + product.don_vi_tinh) }}
                </p>
            </div>

            <!-- PRICE & ADD TO CART ACTION -->
            <div class="pt-2 border-t border-gray-50 flex items-end justify-between gap-2 mt-auto">
                <div class="min-w-0">
                    <div class="flex items-baseline space-x-1.5">
                        <span class="text-base font-black text-emerald-700 tracking-tight">
                            {{ formatPrice(product.gia_giam ?? product.gia_ban) }}
                        </span>
                        <span class="text-[10px] text-gray-500 font-medium">/{{ product.don_vi_tinh }}</span>
                    </div>
                    <p
                        v-if="product.gia_giam"
                        class="text-xs text-gray-400 line-through -mt-0.5"
                    >
                        {{ formatPrice(product.gia_ban) }}
                    </p>
                </div>

                <!-- ADD TO CART BUTTON -->
                <button
                    @click="addToCart(product)"
                    class="w-9 h-9 rounded-xl bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white flex items-center justify-center transition-all duration-200 active:scale-90 shadow-sm shrink-0 group/btn"
                    title="Thêm vào giỏ hàng"
                    :id="'add-btn-' + product.id"
                >
                    <ShoppingBag class="w-4 h-4 group-hover/btn:scale-110 transition-transform" />
                </button>
            </div>
        </div>
    </div>
</template>
