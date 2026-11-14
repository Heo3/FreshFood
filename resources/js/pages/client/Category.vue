<script setup lang="ts">
import ProductCard from '@/components/storefront/ProductCard.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link } from '@inertiajs/vue3';
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
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-7xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <Link :href="route('products.index')" class="transition-colors hover:text-emerald-600">Sản phẩm</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">{{ currentCategory.ten_danh_muc }}</span>
            </div>
        </div>

        <!-- CATEGORY HERO HEADER -->
        <section class="mx-auto max-w-7xl px-4 py-6">
            <div
                class="relative flex items-center justify-between overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 to-teal-800 p-6 text-white shadow-lg sm:p-10"
            >
                <div>
                    <span class="mb-1 block text-xs font-bold uppercase tracking-widest text-emerald-300"> Danh Mục Thực Phẩm </span>
                    <h1 class="text-2xl font-black sm:text-4xl">{{ currentCategory.ten_danh_muc }}</h1>
                    <p class="mt-2 max-w-xl text-xs text-emerald-100/90 sm:text-sm">
                        Tuyển chọn các mặt hàng đạt tiêu chuẩn cao nhất từ các nông trại sạch và nhà phân phối uy tín.
                    </p>
                </div>
                <div
                    v-if="currentCategory.hinh_anh"
                    class="hidden h-32 w-32 shrink-0 overflow-hidden rounded-2xl border border-white/20 shadow-md sm:block"
                >
                    <img :src="currentCategory.hinh_anh" :alt="currentCategory.ten_danh_muc" class="h-full w-full object-cover" />
                </div>
            </div>

            <!-- SUBCATEGORIES CHIPS -->
            <div v-if="currentCategory.danh_muc_con?.length > 0" class="mt-4 flex items-center space-x-2 overflow-x-auto pb-2">
                <span class="shrink-0 text-xs font-bold text-gray-400">Danh mục con:</span>
                <Link
                    v-for="sub in currentCategory.danh_muc_con"
                    :key="sub.id"
                    :href="'/danh-muc/' + sub.duong_dan_seo"
                    class="shrink-0 rounded-full border border-gray-200 bg-white px-3.5 py-1.5 text-xs font-bold text-gray-700 shadow-sm transition-colors hover:bg-emerald-50 hover:text-emerald-700"
                >
                    {{ sub.ten_danh_muc }}
                </Link>
            </div>
        </section>

        <!-- PRODUCT GRID -->
        <section class="mx-auto max-w-7xl px-4 pb-12">
            <div class="mb-6 flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">
                    Hiển thị <strong class="text-gray-900">{{ products.total }}</strong> sản phẩm
                </span>
            </div>

            <div v-if="products.data.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <ProductCard v-for="p in products.data" :key="p.id" :product="p" />
            </div>

            <div v-else class="rounded-3xl border border-gray-100 bg-white p-12 text-center shadow-sm">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                    <PackageX class="h-8 w-8" />
                </div>
                <h3 class="mb-1 text-base font-bold text-gray-900">Chưa có sản phẩm trong danh mục này</h3>
                <p class="mx-auto mb-4 max-w-sm text-xs text-gray-500">Vui lòng quay lại sau hoặc duyệt các danh mục thực phẩm tươi sống khác.</p>
                <Link
                    :href="route('products.index')"
                    class="inline-block rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700"
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
                    class="rounded-xl px-3.5 py-2 text-xs font-bold transition-colors"
                    :class="{
                        'bg-emerald-600 text-white shadow-sm': link.active,
                        'border border-gray-100 bg-white text-gray-700 hover:bg-gray-100': !link.active && link.url,
                        'pointer-events-none text-gray-300': !link.url,
                    }"
                    v-html="link.label"
                />
            </div>
        </section>
    </StoreLayout>
</template>
