<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import ProductCard from '@/components/storefront/ProductCard.vue';
import { ChevronRight, PackageX } from 'lucide-vue-next';

defineProps<{
    currentCategory: {
        id: number;
        ten_danh_muc: string;
        duong_dan_seo: string;
        hinh_anh?: string;
        danh_muc_cha?: any;
        danh_muc_con?: Array<any>;
    };
    products: {
        data: Array<any>;
        links: Array<any>;
        total: number;
        last_page: number;
    };
    categories: Array<any>;
    stores: Array<any>;
}>();
</script>

<template>
    <StoreLayout :title="currentCategory.ten_danh_muc" :stores="stores">
        <!-- BREADCRUMBS -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <Link :href="route('products.index')" class="hover:text-emerald-600 transition-colors">Sản phẩm</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">{{ currentCategory.ten_danh_muc }}</span>
            </div>
        </div>

        <!-- CATEGORY HERO HEADER -->
        <section class="max-w-7xl mx-auto px-4 py-6">
            <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-emerald-800 to-teal-800 text-white p-6 sm:p-10 shadow-lg flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-emerald-300 uppercase tracking-widest mb-1 block">
                        Danh Mục Thực Phẩm
                    </span>
                    <h1 class="text-2xl sm:text-4xl font-black">{{ currentCategory.ten_danh_muc }}</h1>
                    <p class="text-xs sm:text-sm text-emerald-100/90 mt-2 max-w-xl">
                        Tuyển chọn các mặt hàng đạt tiêu chuẩn cao nhất từ các nông trại sạch và nhà phân phối uy tín.
                    </p>
                </div>
                <div v-if="currentCategory.hinh_anh" class="hidden sm:block w-32 h-32 rounded-2xl overflow-hidden shadow-md shrink-0 border border-white/20">
                    <img :src="currentCategory.hinh_anh" :alt="currentCategory.ten_danh_muc" class="w-full h-full object-cover" />
                </div>
            </div>

            <!-- SUBCATEGORIES CHIPS -->
            <div v-if="currentCategory.danh_muc_con?.length > 0" class="mt-4 flex items-center space-x-2 overflow-x-auto pb-2">
                <span class="text-xs font-bold text-gray-400 shrink-0">Danh mục con:</span>
                <Link
                    v-for="sub in currentCategory.danh_muc_con"
                    :key="sub.id"
                    :href="'/danh-muc/' + sub.duong_dan_seo"
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-white text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 border border-gray-200 transition-colors shrink-0 shadow-sm"
                >
                    {{ sub.ten_danh_muc }}
                </Link>
            </div>
        </section>

        <!-- PRODUCT GRID -->
        <section class="max-w-7xl mx-auto px-4 pb-12">
            <div class="flex items-center justify-between mb-6">
                <span class="text-xs text-gray-500 font-medium">
                    Hiển thị <strong class="text-gray-900">{{ products.total }}</strong> sản phẩm
                </span>
            </div>

            <div v-if="products.data.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <ProductCard
                    v-for="p in products.data"
                    :key="p.id"
                    :product="p"
                />
            </div>

            <div v-else class="bg-white rounded-3xl p-12 text-center border border-gray-100 shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-4">
                    <PackageX class="w-8 h-8" />
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Chưa có sản phẩm trong danh mục này</h3>
                <p class="text-xs text-gray-500 max-w-sm mx-auto mb-4">
                    Vui lòng quay lại sau hoặc duyệt các danh mục thực phẩm tươi sống khác.
                </p>
                <Link
                    :href="route('products.index')"
                    class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition-colors"
                >
                    Xem tất cả sản phẩm
                </Link>
            </div>

            <!-- PAGINATION -->
            <div v-if="products.last_page > 1" class="mt-8 flex justify-center space-x-1">
                <Link
                    v-for="(link, i) in products.links"
                    :key="i"
                    :href="link.url || '#'"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-colors"
                    :class="{
                        'bg-emerald-600 text-white shadow-sm': link.active,
                        'bg-white text-gray-700 hover:bg-gray-100 border border-gray-100': !link.active && link.url,
                        'text-gray-300 pointer-events-none': !link.url
                    }"
                    v-html="link.label"
                />
            </div>
        </section>
    </StoreLayout>
</template>
