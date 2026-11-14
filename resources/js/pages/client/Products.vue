<script setup lang="ts">
import ProductCard from '@/components/storefront/ProductCard.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ChevronRight, Filter, LayoutGrid, List, PackageX, Search, Sparkles, X } from 'lucide-vue-next';
import { reactive, ref, watch } from 'vue';

const props = defineProps<{
    products: {
        data: Array<any>;
        links: Array<any>;
        total: number;
        current_page: number;
        last_page: number;
    };
    categories: Array<any>;
    stores: Array<any>;
    filters: {
        q?: string;
        category?: string;
        min_price?: number;
        max_price?: number;
        is_fresh?: boolean;
        preservation?: string;
        sort?: string;
    };
}>();

const isGridView = ref(true);
const isMobileFilterOpen = ref(false);

const localFilters = reactive({
    q: props.filters?.q || '',
    category: props.filters?.category || '',
    min_price: props.filters?.min_price || '',
    max_price: props.filters?.max_price || '',
    is_fresh: props.filters?.is_fresh || false,
    preservation: props.filters?.preservation || '',
    sort: props.filters?.sort || '',
});

// Sync when props.filters updates (e.g. from header search or URL change)
watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters) {
            localFilters.q = newFilters.q || '';
            localFilters.category = newFilters.category || '';
            localFilters.min_price = newFilters.min_price || '';
            localFilters.max_price = newFilters.max_price || '';
            localFilters.is_fresh = newFilters.is_fresh || false;
            localFilters.preservation = newFilters.preservation || '';
            localFilters.sort = newFilters.sort || '';
        }
    },
    { deep: true },
);

// Debounce for search input: automatically filters when typing without pressing Enter
let searchDebounce: ReturnType<typeof setTimeout> | null = null;
watch(
    () => localFilters.q,
    (newVal, oldVal) => {
        if (newVal === oldVal) return;
        if (searchDebounce) clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            applyFilters();
        }, 350);
    },
);

function applyFilters() {
    router.get(
        route('products.index'),
        {
            ...localFilters,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function resetFilters() {
    localFilters.q = '';
    localFilters.category = '';
    localFilters.min_price = '';
    localFilters.max_price = '';
    localFilters.is_fresh = false;
    localFilters.preservation = '';
    localFilters.sort = '';
    applyFilters();
}

function setPricePreset(min: number | '', max: number | '') {
    localFilters.min_price = min;
    localFilters.max_price = max;
    applyFilters();
}
</script>

<template>
    <StoreLayout title="Tất Cả Sản Phẩm" :stores="stores">
        <!-- BREADCRUMBS -->
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-7xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">Tất cả sản phẩm</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8">
            <div class="flex flex-col gap-8 lg:flex-row">
                <!-- 1. FILTER SIDEBAR (DESKTOP) -->
                <aside class="w-full shrink-0 lg:w-72">
                    <div class="sticky top-28 space-y-6 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <span class="flex items-center text-sm font-black text-gray-900">
                                <Filter class="mr-1.5 h-4 w-4 text-emerald-600" /> Bộ Lọc Tìm Kiếm
                            </span>
                            <button @click="resetFilters" class="text-[11px] font-bold text-gray-400 transition-colors hover:text-red-500">
                                Xóa tất cả
                            </button>
                        </div>

                        <!-- Tìm kiếm từ khóa trực tiếp -->
                        <div>
                            <h4 class="mb-2.5 text-xs font-bold uppercase tracking-wider text-gray-700">Từ khóa tìm kiếm</h4>
                            <div class="relative">
                                <input
                                    v-model="localFilters.q"
                                    type="text"
                                    placeholder="Nhập tên sản phẩm để lọc..."
                                    class="w-full rounded-xl border border-gray-200 py-2 pl-8 pr-7 text-xs transition-colors focus:border-emerald-500 focus:outline-none"
                                />
                                <Search class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                                <button
                                    v-if="localFilters.q"
                                    @click="localFilters.q = ''"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Lọc theo danh mục -->
                        <div>
                            <h4 class="mb-2.5 text-xs font-bold uppercase tracking-wider text-gray-700">Danh mục</h4>
                            <div class="max-h-52 space-y-1.5 overflow-y-auto pr-1">
                                <button
                                    @click="
                                        localFilters.category = '';
                                        applyFilters();
                                    "
                                    class="flex w-full items-center justify-between rounded-xl px-2.5 py-1.5 text-left text-xs font-medium transition-colors"
                                    :class="!localFilters.category ? 'bg-emerald-50 font-bold text-emerald-700' : 'text-gray-600 hover:bg-gray-50'"
                                >
                                    <span>Tất cả danh mục</span>
                                </button>
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    @click="
                                        localFilters.category = cat.duong_dan_seo;
                                        applyFilters();
                                    "
                                    class="flex w-full items-center justify-between rounded-xl px-2.5 py-1.5 text-left text-xs font-medium transition-colors"
                                    :class="
                                        localFilters.category === cat.duong_dan_seo
                                            ? 'bg-emerald-50 font-bold text-emerald-700'
                                            : 'text-gray-600 hover:bg-gray-50'
                                    "
                                >
                                    <span class="truncate">{{ cat.ten_danh_muc }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Lọc theo khoảng giá -->
                        <div>
                            <h4 class="mb-2.5 text-xs font-bold uppercase tracking-wider text-gray-700">Mức giá</h4>
                            <div class="mb-2 grid grid-cols-2 gap-2">
                                <input
                                    v-model="localFilters.min_price"
                                    type="number"
                                    placeholder="Từ (đ)"
                                    @change="applyFilters"
                                    class="w-full rounded-xl border border-gray-200 px-2.5 py-1.5 text-xs focus:border-emerald-500 focus:outline-none"
                                />
                                <input
                                    v-model="localFilters.max_price"
                                    type="number"
                                    placeholder="Đến (đ)"
                                    @change="applyFilters"
                                    class="w-full rounded-xl border border-gray-200 px-2.5 py-1.5 text-xs focus:border-emerald-500 focus:outline-none"
                                />
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    @click="setPricePreset(0, 50000)"
                                    class="rounded-lg border border-gray-100 bg-gray-50 px-2 py-1 text-[10px] text-gray-600 hover:bg-emerald-50 hover:text-emerald-700"
                                >
                                    Dưới 50K
                                </button>
                                <button
                                    @click="setPricePreset(50000, 150000)"
                                    class="rounded-lg border border-gray-100 bg-gray-50 px-2 py-1 text-[10px] text-gray-600 hover:bg-emerald-50 hover:text-emerald-700"
                                >
                                    50K - 150K
                                </button>
                                <button
                                    @click="setPricePreset(150000, 300000)"
                                    class="rounded-lg border border-gray-100 bg-gray-50 px-2 py-1 text-[10px] text-gray-600 hover:bg-emerald-50 hover:text-emerald-700"
                                >
                                    150K - 300K
                                </button>
                                <button
                                    @click="setPricePreset(300000, '')"
                                    class="rounded-lg border border-gray-100 bg-gray-50 px-2 py-1 text-[10px] text-gray-600 hover:bg-emerald-50 hover:text-emerald-700"
                                >
                                    Trên 300K
                                </button>
                            </div>
                        </div>

                        <!-- Đặc tính tươi sống -->
                        <div>
                            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-700">Đặc tính sản phẩm</h4>
                            <label class="flex cursor-pointer items-center space-x-2 text-xs text-gray-700">
                                <input
                                    type="checkbox"
                                    v-model="localFilters.is_fresh"
                                    @change="applyFilters"
                                    class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span class="flex items-center">
                                    <Sparkles class="mr-1 h-3.5 w-3.5 text-emerald-600" />
                                    Hàng tươi sống trong ngày
                                </span>
                            </label>
                        </div>
                    </div>
                </aside>

                <!-- 2. PRODUCT LISTING AREA -->
                <div class="min-w-0 flex-1">
                    <!-- Top control bar: count, active chips, sorting, grid/list view -->
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-gray-100 bg-white p-4 shadow-sm">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-medium text-gray-500">Tìm thấy:</span>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700">
                                {{ products.total }} sản phẩm
                            </span>
                        </div>

                        <div class="ml-auto flex items-center space-x-3">
                            <!-- Sort selector -->
                            <select
                                v-model="localFilters.sort"
                                @change="applyFilters"
                                class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="">Sắp xếp: Mới nhất</option>
                                <option value="price_asc">Giá: Thấp đến Cao</option>
                                <option value="price_desc">Giá: Cao đến Thấp</option>
                                <option value="name_asc">Tên: A - Z</option>
                            </select>

                            <!-- Grid / List view toggle -->
                            <div class="hidden items-center space-x-1 rounded-xl bg-gray-100 p-1 sm:flex">
                                <button
                                    @click="isGridView = true"
                                    class="rounded-lg p-1.5 transition-colors"
                                    :class="isGridView ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-400 hover:text-gray-700'"
                                    title="Dạng lưới"
                                >
                                    <LayoutGrid class="h-4 w-4" />
                                </button>
                                <button
                                    @click="isGridView = false"
                                    class="rounded-lg p-1.5 transition-colors"
                                    :class="!isGridView ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-400 hover:text-gray-700'"
                                    title="Dạng danh sách"
                                >
                                    <List class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PRODUCTS GRID -->
                    <div v-if="products.data.length > 0" :class="isGridView ? 'grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-3' : 'space-y-4'">
                        <ProductCard v-for="p in products.data" :key="p.id" :product="p" />
                    </div>

                    <!-- EMPTY STATE -->
                    <div v-else class="rounded-3xl border border-gray-100 bg-white p-12 text-center shadow-sm">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                            <PackageX class="h-8 w-8" />
                        </div>
                        <h3 class="mb-1 text-base font-bold text-gray-900">Không tìm thấy sản phẩm phù hợp</h3>
                        <p class="mx-auto mb-4 max-w-sm text-xs text-gray-500">
                            Vui lòng thử tìm kiếm với từ khóa khác hoặc bỏ bớt các tiêu chí lọc giá và danh mục.
                        </p>
                        <button
                            @click="resetFilters"
                            class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700"
                        >
                            Xóa bộ lọc
                        </button>
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
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
