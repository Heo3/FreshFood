<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    Plus,
    Search,
    Image as ImageIcon,
    Edit2,
    Trash2,
    X,
    Save,
    ExternalLink,
    Eye,
    EyeOff,
} from 'lucide-vue-next';

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
    router.get(route('admin.banners.index'), {
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
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
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
                <div>
                    <h2 class="text-lg font-black text-slate-900 flex items-center space-x-2">
                        <ImageIcon class="w-5 h-5 text-emerald-600" />
                        <span>Quản lý Banner Slider & Quảng Cáo</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Thiết lập các banner trượt trên trang chủ để thu hút khách hàng và quảng bá chiến dịch ưu đãi.
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    type="button"
                    class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition cursor-pointer shrink-0"
                >
                    <Plus class="w-4 h-4" />
                    <span>Thêm Banner Mới</span>
                </button>
            </div>

            <!-- SEARCH FILTER -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text"
                        placeholder="Tìm theo tiêu đề, phụ đề hoặc đường dẫn..."
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                    />
                </div>
                <button
                    @click="handleSearch"
                    type="button"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition cursor-pointer"
                >
                    Tìm kiếm
                </button>
            </div>

            <!-- TABLE -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4 w-16">Thứ tự</th>
                                <th class="py-3 px-4 w-44">Ảnh Banner</th>
                                <th class="py-3 px-4">Tiêu Đề & Phụ Đề</th>
                                <th class="py-3 px-4">Đích Đến (Link)</th>
                                <th class="py-3 px-4 text-center">Nút Bấm</th>
                                <th class="py-3 px-4 text-center">Trạng Thái</th>
                                <th class="py-3 px-4 text-center w-28">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="banners.data.length === 0">
                                <td colspan="7" class="text-center py-12 text-slate-400">
                                    Chưa có banner nào. Bấm "Thêm Banner Mới" để tạo ngay!
                                </td>
                            </tr>
                            <tr v-for="b in banners.data" :key="b.id" class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-black text-slate-700 text-center">
                                    <span class="w-6 h-6 rounded-lg bg-slate-100 inline-flex items-center justify-center">
                                        {{ b.thu_tu }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="w-36 h-20 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shadow-xs">
                                        <img :src="b.hinh_anh" :alt="b.tieu_de" class="w-full h-full object-cover" />
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-extrabold text-slate-900 text-sm">{{ b.tieu_de }}</p>
                                    <p class="text-slate-500 text-[11px] mt-0.5 line-clamp-1">{{ b.phu_de || '—' }}</p>
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    <a :href="b.duong_dan || '#'" target="_blank" class="text-emerald-700 hover:underline inline-flex items-center gap-1">
                                        <span>{{ b.duong_dan || '—' }}</span>
                                        <ExternalLink class="w-3 h-3" />
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold rounded-lg text-[10px]">
                                        {{ b.nhan_nut || 'Mua Ngay' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button
                                        @click="toggleStatus(b)"
                                        type="button"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black transition cursor-pointer"
                                        :class="b.trang_thai === 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    >
                                        <Eye v-if="b.trang_thai === 1" class="w-3 h-3 text-emerald-600" />
                                        <EyeOff v-else class="w-3 h-3 text-slate-400" />
                                        <span>{{ b.trang_thai === 1 ? 'Đang hiện' : 'Đang ẩn' }}</span>
                                    </button>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1">
                                        <button
                                            @click="openEditModal(b)"
                                            type="button"
                                            class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition cursor-pointer"
                                            title="Chỉnh sửa"
                                        >
                                            <Edit2 class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            @click="deleteBanner(b)"
                                            type="button"
                                            class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer"
                                            title="Xóa"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div v-if="banners.links?.length > 3" class="p-4 border-t border-slate-100 flex justify-center gap-1">
                    <template v-for="(link, i) in banners.links" :key="i">
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

        <!-- CREATE / EDIT MODAL -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-150"
        >
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 space-y-4 animate-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <ImageIcon class="w-5 h-5 text-emerald-600" />
                        <span>{{ editingBanner ? 'Chỉnh Sửa Banner' : 'Thêm Banner Quảng Cáo Mới' }}</span>
                    </h3>
                    <button @click="showModal = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tiêu đề chính <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.tieu_de"
                            type="text"
                            required
                            placeholder="VD: Đại Tiệc Rau Củ Hữu Cơ Đà Lạt"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Phụ đề / Thông điệp phụ</label>
                        <input
                            v-model="form.phu_de"
                            type="text"
                            placeholder="VD: Giảm ngay 30% cho đơn từ 200k hôm nay"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">URL Hình ảnh Banner <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.hinh_anh"
                            type="url"
                            required
                            placeholder="https://images.unsplash.com/..."
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none"
                        />
                        <!-- Preview -->
                        <div v-if="form.hinh_anh" class="mt-2 w-full h-24 rounded-xl overflow-hidden border border-slate-200 bg-slate-100">
                            <img :src="form.hinh_anh" class="w-full h-full object-cover" alt="Preview" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Đường dẫn khi click</label>
                            <input
                                v-model="form.duong_dan"
                                type="text"
                                placeholder="/san-pham"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nhãn nút bấm</label>
                            <input
                                v-model="form.nhan_nut"
                                type="text"
                                placeholder="Mua Ngay"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Thứ tự hiển thị</label>
                            <input
                                v-model.number="form.thu_tu"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Trạng thái</label>
                            <select
                                v-model.number="form.trang_thai"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none bg-white font-bold"
                            >
                                <option :value="1">Hiển thị (Bật)</option>
                                <option :value="0">Tạm ẩn (Tắt)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-100 transition cursor-pointer"
                        >
                            Hủy bỏ
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold shadow-md shadow-emerald-600/20 transition cursor-pointer"
                        >
                            <Save class="w-3.5 h-3.5" />
                            <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Banner' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

