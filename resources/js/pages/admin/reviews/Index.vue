<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    Search,
    MessageSquare,
    Star,
    Trash2,
    Eye,
    EyeOff,
    User,
    Package,
    Calendar,
    Filter,
} from 'lucide-vue-next';

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
    router.get(route('admin.reviews.index'), {
        search: search.value || undefined,
        rating: rating.value || undefined,
        status: status.value !== '' ? status.value : undefined,
    }, { preserveState: true, replace: true });
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
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900 flex items-center space-x-2">
                        <MessageSquare class="w-5 h-5 text-emerald-600" />
                        <span>Quản lý Đánh Giá & Nhận Xét Của Khách Hàng</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kiểm duyệt phản hồi, chất lượng thực phẩm và mức độ hài lòng của khách mua hàng.
                    </p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <div class="relative sm:col-span-2">
                    <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        @keyup.enter="handleFilter"
                        type="text"
                        placeholder="Tìm theo tên khách, sản phẩm, nội dung..."
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                    />
                </div>

                <div>
                    <select
                        v-model="rating"
                        @change="handleFilter"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
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
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                    >
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="1">Đang hiển thị</option>
                        <option value="0">Đã tạm ẩn</option>
                    </select>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4 w-48">Khách Hàng</th>
                                <th class="py-3 px-4 w-52">Sản Phẩm</th>
                                <th class="py-3 px-4 w-28 text-center">Đánh Giá</th>
                                <th class="py-3 px-4">Nội Dung Nhận Xét</th>
                                <th class="py-3 px-4 w-28 text-center">Thời Gian</th>
                                <th class="py-3 px-4 w-28 text-center">Trạng Thái</th>
                                <th class="py-3 px-4 w-20 text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="reviews.data.length === 0">
                                <td colspan="7" class="text-center py-12 text-slate-400">
                                    Không có đánh giá nào phù hợp với bộ lọc.
                                </td>
                            </tr>
                            <tr v-for="r in reviews.data" :key="r.id" class="hover:bg-slate-50/80 transition">
                                <!-- User -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0 border border-emerald-200 overflow-hidden">
                                            <img v-if="r.user?.avatar" :src="r.user.avatar" class="w-full h-full object-cover" />
                                            <span v-else>{{ r.user?.name ? r.user.name.charAt(0).toUpperCase() : 'K' }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate">{{ r.user?.name || 'Khách vãng lai' }}</p>
                                            <p class="text-[10px] text-slate-400 truncate">{{ r.user?.email || '—' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Product -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2">
                                        <img
                                            :src="r.san_pham?.hinh_anh_chinh || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=100'"
                                            class="w-8 h-8 rounded-lg object-cover bg-slate-100 shrink-0"
                                        />
                                        <div class="min-w-0">
                                            <a
                                                v-if="r.san_pham?.duong_dan_seo"
                                                :href="route('products.show', r.san_pham.duong_dan_seo)"
                                                target="_blank"
                                                class="font-bold text-slate-800 hover:text-emerald-600 line-clamp-1"
                                            >
                                                {{ r.san_pham?.ten_san_pham }}
                                            </a>
                                            <span v-else class="text-slate-700 font-bold line-clamp-1">{{ r.san_pham?.ten_san_pham }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Rating Stars -->
                                <td class="py-3 px-4 text-center">
                                    <div class="inline-flex items-center gap-0.5 text-amber-400">
                                        <Star
                                            v-for="s in 5"
                                            :key="s"
                                            class="w-3.5 h-3.5"
                                            :class="s <= r.so_sao ? 'fill-amber-400 text-amber-400' : 'text-slate-200'"
                                        />
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-bold mt-0.5">{{ r.so_sao }}/5 sao</p>
                                </td>

                                <!-- Content -->
                                <td class="py-3 px-4">
                                    <p class="text-slate-800 leading-relaxed text-[11px] line-clamp-2">
                                        {{ r.noi_dung || '—' }}
                                    </p>
                                </td>

                                <!-- Date -->
                                <td class="py-3 px-4 text-center text-slate-400 text-[11px]">
                                    {{ new Date(r.created_at).toLocaleDateString('vi-VN') }}
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4 text-center">
                                    <button
                                        @click="toggleStatus(r)"
                                        type="button"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black transition cursor-pointer"
                                        :class="r.trang_thai === 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-rose-100 text-rose-800 hover:bg-rose-200'"
                                    >
                                        <Eye v-if="r.trang_thai === 1" class="w-3 h-3 text-emerald-600" />
                                        <EyeOff v-else class="w-3 h-3 text-rose-600" />
                                        <span>{{ r.trang_thai === 1 ? 'Hiển thị' : 'Đã ẩn' }}</span>
                                    </button>
                                </td>

                                <!-- Action -->
                                <td class="py-3 px-4 text-center">
                                    <button
                                        @click="deleteReview(r)"
                                        type="button"
                                        class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer"
                                        title="Xóa đánh giá"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div v-if="reviews.links?.length > 3" class="p-4 border-t border-slate-100 flex justify-center gap-1">
                    <template v-for="(link, i) in reviews.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="link.active ? 'bg-emerald-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        />
                        <span v-else v-html="link.label" class="px-3 py-1.5 text-xs text-slate-400" />
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

