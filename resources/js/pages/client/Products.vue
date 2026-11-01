<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import ProductCard from '@/components/storefront/ProductCard.vue';
import {
    Filter,
    LayoutGrid,
    List,
    Search,
    X,
    Sparkles,
    ChevronRight,
    PackageX,
} from 'lucide-vue-next';

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
watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        localFilters.q = newFilters.q || '';
        localFilters.category = newFilters.category || '';
        localFilters.min_price = newFilters.min_price || '';
        localFilters.max_price = newFilters.max_price || '';
        localFilters.is_fresh = newFilters.is_fresh || false;
        localFilters.preservation = newFilters.preservation || '';
        localFilters.sort = newFilters.sort || '';
    }
}, { deep: true });

// Debounce for search input: automatically filters when typing without pressing Enter
let searchDebounce: ReturnType<typeof setTimeout> | null = null;
watch(() => localFilters.q, (newVal, oldVal) => {
    if (newVal === oldVal) return;
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        applyFilters();
    }, 350);
});

function applyFilters() {
    router.get(route('products.index'), {
        ...localFilters,
        page: 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
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
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Tất cả sản phẩm</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- 1. FILTER SIDEBAR (DESKTOP) -->
                <aside class="w-full lg:w-72 shrink-0">
                    <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm sticky top-28 space-y-6">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <span class="font-black text-sm text-gray-900 flex items-center">
                                <Filter class="w-4 h-4 mr-1.5 text-emerald-600" /> Bộ Lọc Tìm Kiếm
                            </span>
                            <button
                                @click="resetFilters"
                                class="text-[11px] font-bold text-gray-400 hover:text-red-500 transition-colors"
                            >
                                Xóa tất cả
                            </button>
                        </div>

                        <!-- Tìm kiếm từ khóa trực tiếp -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">Từ khóa tìm kiếm</h4>
                            <div class="relative">
                                <input
                                    v-model="localFilters.q"
                                    type="text"
                                    placeholder="Nhập tên sản phẩm để lọc..."
                                    class="w-full pl-8 pr-7 py-2 text-xs border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none transition-colors"
                                />
                                <Search class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                                <button
                                    v-if="localFilters.q"
                                    @click="localFilters.q = ''"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <X class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Lọc theo danh mục -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">Danh mục</h4>
                            <div class="space-y-1.5 max-h-52 overflow-y-auto pr-1">
                                <button
                                    @click="localFilters.category = ''; applyFilters()"
                                    class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs font-medium transition-colors flex justify-between items-center"
                                    :class="!localFilters.category ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-gray-600 hover:bg-gray-50'"
                                >
                                    <span>Tất cả danh mục</span>
                                </button>
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    @click="localFilters.category = cat.duong_dan_seo; applyFilters()"
                                    class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs font-medium transition-colors flex justify-between items-center"
                                    :class="localFilters.category === cat.duong_dan_seo ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-gray-600 hover:bg-gray-50'"
                                >
                                    <span class="truncate">{{ cat.ten_danh_muc }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Lọc theo khoảng giá -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">Mức giá</h4>
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <input
                                    v-model="localFilters.min_price"
                                    type="number"
                                    placeholder="Từ (đ)"
                                    @change="applyFilters"
                                    class="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                                />
                                <input
                                    v-model="localFilters.max_price"
                                    type="number"
                                    placeholder="Đến (đ)"
                                    @change="applyFilters"
                                    class="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                                />
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    @click="setPricePreset(0, 50000)"
                                    class="px-2 py-1 rounded-lg text-[10px] bg-gray-50 hover:bg-emerald-50 text-gray-600 hover:text-emerald-700 border border-gray-100"
                                >
                                    Dưới 50K
                                </button>
                                <button
                                    @click="setPricePreset(50000, 150000)"
                                    class="px-2 py-1 rounded-lg text-[10px] bg-gray-50 hover:bg-emerald-50 text-gray-600 hover:text-emerald-700 border border-gray-100"
                                >
                                    50K - 150K
                                </button>
                                <button
                                    @click="setPricePreset(150000, 300000)"
                                    class="px-2 py-1 rounded-lg text-[10px] bg-gray-50 hover:bg-emerald-50 text-gray-600 hover:text-emerald-700 border border-gray-100"
                                >
                                    150K - 300K
                                </button>
                                <button
                                    @click="setPricePreset(300000, '')"
                                    class="px-2 py-1 rounded-lg text-[10px] bg-gray-50 hover:bg-emerald-50 text-gray-600 hover:text-emerald-700 border border-gray-100"
                                >
                                    Trên 300K
                                </button>
                            </div>
                        </div>

                        <!-- Đặc tính tươi sống -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Đặc tính sản phẩm</h4>
                            <label class="flex items-center space-x-2 text-xs text-gray-700 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="localFilters.is_fresh"
                                    @change="applyFilters"
                                    class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span class="flex items-center">
                                    <Sparkles class="w-3.5 h-3.5 mr-1 text-emerald-600" />
                                    Hàng tươi sống trong ngày
                                </span>
                            </label>
                        </div>
                    </div>
                </aside>

                <!-- 2. PRODUCT LISTING AREA -->
                <div class="flex-1 min-w-0">
                    <!-- Top control bar: count, active chips, sorting, grid/list view -->
                    <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500 font-medium">Tìm thấy:</span>
                            <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full">
                                {{ products.total }} sản phẩm
                            </span>
                        </div>

                        <div class="flex items-center space-x-3 ml-auto">
                            <!-- Sort selector -->
                            <select
                                v-model="localFilters.sort"
                                @change="applyFilters"
                                class="text-xs font-semibold bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-gray-700 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="">Sắp xếp: Mới nhất</option>
                                <option value="price_asc">Giá: Thấp đến Cao</option>
                                <option value="price_desc">Giá: Cao đến Thấp</option>
                                <option value="name_asc">Tên: A - Z</option>
                            </select>

                            <!-- Grid / List view toggle -->
                            <div class="hidden sm:flex items-center space-x-1 bg-gray-100 p-1 rounded-xl">
                                <button
                                    @click="isGridView = true"
                                    class="p-1.5 rounded-lg transition-colors"
                                    :class="isGridView ? 'bg-white shadow-sm text-emerald-700' : 'text-gray-400 hover:text-gray-700'"
                                    title="Dạng lưới"
                                >
                                    <LayoutGrid class="w-4 h-4" />
                                </button>
                                <button
                                    @click="isGridView = false"
                                    class="p-1.5 rounded-lg transition-colors"
                                    :class="!isGridView ? 'bg-white shadow-sm text-emerald-700' : 'text-gray-400 hover:text-gray-700'"
                                    title="Dạng danh sách"
                                >
                                    <List class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PRODUCTS GRID -->
                    <div
                        v-if="products.data.length > 0"
                        :class="isGridView ? 'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-4' : 'space-y-4'"
                    >
                        <ProductCard
                            v-for="p in products.data"
                            :key="p.id"
                            :product="p"
                        />
                    </div>

                    <!-- EMPTY STATE -->
                    <div
                        v-else
                        class="bg-white rounded-3xl p-12 text-center border border-gray-100 shadow-sm"
                    >
                        <div class="w-16 h-16 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-4">
                            <PackageX class="w-8 h-8" />
                        </div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Không tìm thấy sản phẩm phù hợp</h3>
                        <p class="text-xs text-gray-500 max-w-sm mx-auto mb-4">
                            Vui lòng thử tìm kiếm với từ khóa khác hoặc bỏ bớt các tiêu chí lọc giá và danh mục.
                        </p>
                        <button
                            @click="resetFilters"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition-colors"
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
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-colors"
                            :class="{
                                'bg-emerald-600 text-white shadow-sm': link.active,
                                'bg-white text-gray-700 hover:bg-gray-100 border border-gray-100': !link.active && link.url,
                                'text-gray-300 pointer-events-none': !link.url
                            }"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
