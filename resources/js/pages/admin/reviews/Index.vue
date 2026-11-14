<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, EyeOff, MessageSquare, Search, Star, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface ReviewItem {
    id: number;
    user_id: number;
    san_pham_id: number;
    don_hang_id?: number;
    so_sao: number;
    noi_dung: string;
    trang_thai: number;
    created_at: string;
    user?: { id: number; name: string; email: string; avatar?: string };
    san_pham?: { id: number; ten_san_pham: string; duong_dan_seo: string; hinh_anh_chinh?: string };
}

const props = defineProps<{
    reviews: {
        data: Array<ReviewItem>;
        links: Array<any>;
    };
    filters: {
        search?: string;
        rating?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search || '');
const rating = ref(props.filters.rating || '');
const status = ref(props.filters.status || '');

function handleFilter() {
    router.get(
        route('admin.reviews.index'),
        {
            search: search.value || undefined,
            rating: rating.value || undefined,
            status: status.value !== '' ? status.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function toggleStatus(review: ReviewItem) {
    router.post(route('admin.reviews.toggle', review.id), {}, { preserveScroll: true });
}

function deleteReview(review: ReviewItem) {
    if (confirm(`Bạn có chắc muốn xóa đánh giá của khách hàng "${review.user?.name || 'Khách'}"?`)) {
        router.delete(route('admin.reviews.destroy', review.id), { preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout title="Quản lý Đánh Giá & Phản Hồi">
        <Head title="Quản lý Đánh Giá & Phản Hồi | FreshFood Admin" />

        <div class="space-y-6">
            <!-- HEADER -->
            <div
                class="shadow-xs flex flex-col justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-6 sm:flex-row sm:items-center"
            >
                <div>
                    <h2 class="flex items-center space-x-2 text-lg font-black text-slate-900">
                        <MessageSquare class="h-5 w-5 text-emerald-600" />
                        <span>Quản lý Đánh Giá & Nhận Xét Của Khách Hàng</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500">Kiểm duyệt phản hồi, chất lượng thực phẩm và mức độ hài lòng của khách mua hàng.</p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="shadow-xs grid grid-cols-1 gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 text-xs sm:grid-cols-4">
                <div class="relative sm:col-span-2">
                    <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        @keyup.enter="handleFilter"
                        type="text"
                        placeholder="Tìm theo tên khách, sản phẩm, nội dung..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-slate-900 outline-none focus:bg-white focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <div>
                    <select
                        v-model="rating"
                        @change="handleFilter"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-bold text-slate-700 outline-none focus:bg-white focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="">-- Tất cả số sao --</option>
                        <option value="5">⭐⭐⭐⭐⭐ (5 sao)</option>
                        <option value="4">⭐⭐⭐⭐ (4 sao)</option>
                        <option value="3">⭐⭐⭐ (3 sao)</option>
                        <option value="2">⭐⭐ (2 sao)</option>
                        <option value="1">⭐ (1 sao)</option>
                    </select>
                </div>

                <div>
                    <select
                        v-model="status"
                        @change="handleFilter"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-bold text-slate-700 outline-none focus:bg-white focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="1">Đang hiển thị</option>
                        <option value="0">Đã tạm ẩn</option>
                    </select>
                </div>
            </div>

            <!-- TABLE -->
            <div class="shadow-xs overflow-hidden rounded-3xl border border-slate-200/80 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="w-48 px-4 py-3">Khách Hàng</th>
                                <th class="w-52 px-4 py-3">Sản Phẩm</th>
                                <th class="w-28 px-4 py-3 text-center">Đánh Giá</th>
                                <th class="px-4 py-3">Nội Dung Nhận Xét</th>
                                <th class="w-28 px-4 py-3 text-center">Thời Gian</th>
                                <th class="w-28 px-4 py-3 text-center">Trạng Thái</th>
                                <th class="w-20 px-4 py-3 text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="reviews.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">Không có đánh giá nào phù hợp với bộ lọc.</td>
                            </tr>
                            <tr v-for="r in reviews.data" :key="r.id" class="transition hover:bg-slate-50/80">
                                <!-- User -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2.5">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-emerald-200 bg-emerald-100 font-bold text-emerald-800"
                                        >
                                            <img v-if="r.user?.avatar" :src="r.user.avatar" class="h-full w-full object-cover" />
                                            <span v-else>{{ r.user?.name ? r.user.name.charAt(0).toUpperCase() : 'K' }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-slate-900">{{ r.user?.name || 'Khách vãng lai' }}</p>
                                            <p class="truncate text-[10px] text-slate-400">{{ r.user?.email || '—' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Product -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <img
                                            :src="r.san_pham?.hinh_anh_chinh || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=100'"
                                            class="h-8 w-8 shrink-0 rounded-lg bg-slate-100 object-cover"
                                        />
                                        <div class="min-w-0">
                                            <a
                                                v-if="r.san_pham?.duong_dan_seo"
                                                :href="route('products.show', r.san_pham.duong_dan_seo)"
                                                target="_blank"
                                                class="line-clamp-1 font-bold text-slate-800 hover:text-emerald-600"
                                            >
                                                {{ r.san_pham?.ten_san_pham }}
                                            </a>
                                            <span v-else class="line-clamp-1 font-bold text-slate-700">{{ r.san_pham?.ten_san_pham }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Rating Stars -->
                                <td class="px-4 py-3 text-center">
                                    <div class="inline-flex items-center gap-0.5 text-amber-400">
                                        <Star
                                            v-for="s in 5"
                                            :key="s"
                                            class="h-3.5 w-3.5"
                                            :class="s <= r.so_sao ? 'fill-amber-400 text-amber-400' : 'text-slate-200'"
                                        />
                                    </div>
                                    <p class="mt-0.5 text-[10px] font-bold text-slate-400">{{ r.so_sao }}/5 sao</p>
                                </td>

                                <!-- Content -->
                                <td class="px-4 py-3">
                                    <p class="line-clamp-2 text-[11px] leading-relaxed text-slate-800">
                                        {{ r.noi_dung || '—' }}
                                    </p>
                                </td>

                                <!-- Date -->
                                <td class="px-4 py-3 text-center text-[11px] text-slate-400">
                                    {{ new Date(r.created_at).toLocaleDateString('vi-VN') }}
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3 text-center">
                                    <button
                                        @click="toggleStatus(r)"
                                        type="button"
                                        class="inline-flex cursor-pointer items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-black transition"
                                        :class="
                                            r.trang_thai === 1
                                                ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                                : 'bg-rose-100 text-rose-800 hover:bg-rose-200'
                                        "
                                    >
                                        <Eye v-if="r.trang_thai === 1" class="h-3 w-3 text-emerald-600" />
                                        <EyeOff v-else class="h-3 w-3 text-rose-600" />
                                        <span>{{ r.trang_thai === 1 ? 'Hiển thị' : 'Đã ẩn' }}</span>
                                    </button>
                                </td>

                                <!-- Action -->
                                <td class="px-4 py-3 text-center">
                                    <button
                                        @click="deleteReview(r)"
                                        type="button"
                                        class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                        title="Xóa đánh giá"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div v-if="reviews.links?.length > 3" class="flex justify-center gap-1 border-t border-slate-100 p-4">
                    <template v-for="(link, i) in reviews.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold transition"
                            :class="link.active ? 'bg-emerald-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        />
                        <span v-else v-html="link.label" class="px-3 py-1.5 text-xs text-slate-400" />
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
