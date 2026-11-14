<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Edit2, ExternalLink, Eye, EyeOff, Image as ImageIcon, Plus, Save, Search, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface BannerItem {
    id: number;
    tieu_de: string;
    phu_de?: string;
    hinh_anh: string;
    duong_dan?: string;
    nhan_nut?: string;
    vi_tri?: string;
    thu_tu: number;
    trang_thai: number;
    created_at: string;
}

const props = defineProps<{
    banners: {
        data: Array<BannerItem>;
        links: Array<any>;
    };
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingBanner = ref<BannerItem | null>(null);

const form = useForm({
    tieu_de: '',
    phu_de: '',
    hinh_anh: '',
    duong_dan: '/san-pham',
    nhan_nut: 'Mua Ngay',
    vi_tri: 'home_slider',
    thu_tu: 0,
    trang_thai: 1,
});

function handleSearch() {
    router.get(
        route('admin.banners.index'),
        {
            search: search.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function openCreateModal() {
    editingBanner.value = null;
    form.reset();
    form.clearErrors();
    form.duong_dan = '/san-pham';
    form.nhan_nut = 'Mua Ngay';
    form.vi_tri = 'home_slider';
    form.thu_tu = (props.banners.data.length || 0) + 1;
    form.trang_thai = 1;
    showModal.value = true;
}

function openEditModal(banner: BannerItem) {
    editingBanner.value = banner;
    form.clearErrors();
    form.tieu_de = banner.tieu_de;
    form.phu_de = banner.phu_de || '';
    form.hinh_anh = banner.hinh_anh;
    form.duong_dan = banner.duong_dan || '/san-pham';
    form.nhan_nut = banner.nhan_nut || 'Mua Ngay';
    form.vi_tri = banner.vi_tri || 'home_slider';
    form.thu_tu = banner.thu_tu;
    form.trang_thai = banner.trang_thai;
    showModal.value = true;
}

function submitForm() {
    if (editingBanner.value) {
        form.put(route('admin.banners.update', editingBanner.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('admin.banners.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
}

function toggleStatus(banner: BannerItem) {
    router.post(route('admin.banners.toggle', banner.id), {}, { preserveScroll: true });
}

function deleteBanner(banner: BannerItem) {
    if (confirm(`Bạn có chắc chắn muốn xóa banner "${banner.tieu_de}"?`)) {
        router.delete(route('admin.banners.destroy', banner.id), { preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout title="Quản lý Banner & Quảng Cáo">
        <Head title="Quản lý Banner & Quảng Cáo | FreshFood Admin" />

        <div class="space-y-6">
            <!-- HEADER -->
            <div
                class="shadow-xs flex flex-col justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-6 sm:flex-row sm:items-center"
            >
                <div>
                    <h2 class="flex items-center space-x-2 text-lg font-black text-slate-900">
                        <ImageIcon class="h-5 w-5 text-emerald-600" />
                        <span>Quản lý Banner Slider & Quảng Cáo</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Thiết lập các banner trượt trên trang chủ để thu hút khách hàng và quảng bá chiến dịch ưu đãi.
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    type="button"
                    class="inline-flex shrink-0 cursor-pointer items-center space-x-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700"
                >
                    <Plus class="h-4 w-4" />
                    <span>Thêm Banner Mới</span>
                </button>
            </div>

            <!-- SEARCH FILTER -->
            <div class="shadow-xs flex items-center justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white p-4">
                <div class="relative max-w-md flex-1">
                    <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text"
                        placeholder="Tìm theo tiêu đề, phụ đề hoặc đường dẫn..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs text-slate-900 outline-none focus:bg-white focus:ring-2 focus:ring-emerald-500"
                    />
                </div>
                <button
                    @click="handleSearch"
                    type="button"
                    class="cursor-pointer rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-800"
                >
                    Tìm kiếm
                </button>
            </div>

            <!-- TABLE -->
            <div class="shadow-xs overflow-hidden rounded-3xl border border-slate-200/80 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="w-16 px-4 py-3">Thứ tự</th>
                                <th class="w-44 px-4 py-3">Ảnh Banner</th>
                                <th class="px-4 py-3">Tiêu Đề & Phụ Đề</th>
                                <th class="px-4 py-3">Đích Đến (Link)</th>
                                <th class="px-4 py-3 text-center">Nút Bấm</th>
                                <th class="px-4 py-3 text-center">Trạng Thái</th>
                                <th class="w-28 px-4 py-3 text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="banners.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">Chưa có banner nào. Bấm "Thêm Banner Mới" để tạo ngay!</td>
                            </tr>
                            <tr v-for="b in banners.data" :key="b.id" class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-3 text-center font-black text-slate-700">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100">
                                        {{ b.thu_tu }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="shadow-xs h-20 w-36 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                        <img :src="b.hinh_anh" :alt="b.tieu_de" class="h-full w-full object-cover" />
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-extrabold text-slate-900">{{ b.tieu_de }}</p>
                                    <p class="mt-0.5 line-clamp-1 text-[11px] text-slate-500">{{ b.phu_de || '—' }}</p>
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] text-slate-600">
                                    <a
                                        :href="b.duong_dan || '#'"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-emerald-700 hover:underline"
                                    >
                                        <span>{{ b.duong_dan || '—' }}</span>
                                        <ExternalLink class="h-3 w-3" />
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-700">
                                        {{ b.nhan_nut || 'Mua Ngay' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        @click="toggleStatus(b)"
                                        type="button"
                                        class="inline-flex cursor-pointer items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-black transition"
                                        :class="
                                            b.trang_thai === 1
                                                ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                        "
                                    >
                                        <Eye v-if="b.trang_thai === 1" class="h-3 w-3 text-emerald-600" />
                                        <EyeOff v-else class="h-3 w-3 text-slate-400" />
                                        <span>{{ b.trang_thai === 1 ? 'Đang hiện' : 'Đang ẩn' }}</span>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-1">
                                        <button
                                            @click="openEditModal(b)"
                                            type="button"
                                            class="cursor-pointer rounded-lg p-1.5 text-slate-500 transition hover:bg-emerald-50 hover:text-emerald-600"
                                            title="Chỉnh sửa"
                                        >
                                            <Edit2 class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            @click="deleteBanner(b)"
                                            type="button"
                                            class="cursor-pointer rounded-lg p-1.5 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                                            title="Xóa"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div v-if="banners.links?.length > 3" class="flex justify-center gap-1 border-t border-slate-100 p-4">
                    <template v-for="(link, i) in banners.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold transition"
                            :class="link.active ? 'bg-emerald-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'"
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span v-else v-html="link.label" class="px-3 py-1.5 text-xs text-slate-400" />
                    </template>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT MODAL -->
        <div
            v-if="showModal"
            class="backdrop-blur-xs fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 duration-150 animate-in fade-in"
        >
            <div class="w-full max-w-lg space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl duration-150 animate-in zoom-in-95">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="flex items-center gap-2 text-base font-black text-slate-900">
                        <ImageIcon class="h-5 w-5 text-emerald-600" />
                        <span>{{ editingBanner ? 'Chỉnh Sửa Banner' : 'Thêm Banner Quảng Cáo Mới' }}</span>
                    </h3>
                    <button @click="showModal = false" class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-3.5 text-xs">
                    <div>
                        <label class="mb-1 block font-bold text-slate-700">Tiêu đề chính <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.tieu_de"
                            type="text"
                            required
                            placeholder="VD: Đại Tiệc Rau Củ Hữu Cơ Đà Lạt"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block font-bold text-slate-700">Phụ đề / Thông điệp phụ</label>
                        <input
                            v-model="form.phu_de"
                            type="text"
                            placeholder="VD: Giảm ngay 30% cho đơn từ 200k hôm nay"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block font-bold text-slate-700">URL Hình ảnh Banner <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.hinh_anh"
                            type="url"
                            required
                            placeholder="https://images.unsplash.com/..."
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                        <!-- Preview -->
                        <div v-if="form.hinh_anh" class="mt-2 h-24 w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                            <img :src="form.hinh_anh" class="h-full w-full object-cover" alt="Preview" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block font-bold text-slate-700">Đường dẫn khi click</label>
                            <input
                                v-model="form.duong_dan"
                                type="text"
                                placeholder="/san-pham"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-bold text-slate-700">Nhãn nút bấm</label>
                            <input
                                v-model="form.nhan_nut"
                                type="text"
                                placeholder="Mua Ngay"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block font-bold text-slate-700">Thứ tự hiển thị</label>
                            <input
                                v-model.number="form.thu_tu"
                                type="number"
                                min="0"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-bold text-slate-700">Trạng thái</label>
                            <select
                                v-model.number="form.trang_thai"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-bold outline-none focus:ring-2 focus:ring-emerald-500"
                            >
                                <option :value="1">Hiển thị (Bật)</option>
                                <option :value="0">Tạm ẩn (Tắt)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="cursor-pointer rounded-xl border border-slate-200 px-4 py-2 font-bold text-slate-600 transition hover:bg-slate-100"
                        >
                            Hủy bỏ
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 font-bold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <Save class="h-3.5 w-3.5" />
                            <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Banner' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
