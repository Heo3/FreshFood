<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    Plus,
    Search,
    Edit2,
    Trash2,
    FolderTree,
    Folder,
    X,
    Save,
} from 'lucide-vue-next';

const props = defineProps<{
    categories: {
        data: Array<{
            id: number;
            ten_danh_muc: string;
            duong_dan_seo: string;
            hinh_anh: string | null;
            danh_muc_cha_id: number | null;
            danh_muc_cha?: {
                id: number;
                ten_danh_muc: string;
            } | null;
            san_phams_count: number;
        }>;
        links: Array<any>;
    };
    parentCategories: Array<{
        id: number;
        ten_danh_muc: string;
    }>;
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingCategory = ref<any | null>(null);

const form = useForm({
    ten_danh_muc: '',
    duong_dan_seo: '',
    hinh_anh: '',
    danh_muc_cha_id: '' as any,
});

function openCreateModal() {
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEditModal(category: any) {
    editingCategory.value = category;
    form.clearErrors();
    form.ten_danh_muc = category.ten_danh_muc;
    form.duong_dan_seo = category.duong_dan_seo;
    form.hinh_anh = category.hinh_anh || '';
    form.danh_muc_cha_id = category.danh_muc_cha_id || '';
    showModal.value = true;
}

function submitForm() {
    if (editingCategory.value) {
        form.put(route('admin.categories.update', editingCategory.value.id), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('admin.categories.store'), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
}

function deleteCategory(category: any) {
    if (confirm(`Bạn có chắc chắn muốn xóa danh mục "${category.ten_danh_muc}"?`)) {
        router.delete(route('admin.categories.destroy', category.id));
    }
}

function handleSearch() {
    router.get(route('admin.categories.index'), { search: search.value }, { preserveState: true });
}
</script>

<template>
    <AdminLayout
        title="Danh Mục Hàng Hóa"
        :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Danh mục' }]"
    >
        <div class="space-y-4">
            <!-- ADMINLTE CARD -->
            <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs">
                <!-- CARD HEADER & TOOLS -->
                <div class="px-4 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h3 class="text-sm font-bold text-[#343a40] flex items-center space-x-2">
                        <FolderTree class="w-4 h-4 text-[#007bff]" />
                        <span>Danh Sách Danh Mục Sản Phẩm</span>
                    </h3>

                    <!-- CARD TOOLS -->
                    <div class="flex items-center space-x-2">
                        <div class="relative w-48 sm:w-60">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Tìm kiếm danh mục..."
                                class="w-full pl-3 pr-8 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                                @keyup.enter="handleSearch"
                            />
                            <Search
                                class="w-3.5 h-3.5 text-gray-400 absolute right-2.5 top-2.5 cursor-pointer"
                                @click="handleSearch"
                            />
                        </div>

                        <button
                            class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-[#007bff] hover:bg-[#0069d9] text-white rounded-sm text-xs font-semibold shadow-2xs transition-colors shrink-0"
                            @click="openCreateModal"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            <span>Thêm Mới</span>
                        </button>
                    </div>
                </div>

                <!-- CARD BODY - ADMINLTE TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase text-[10px]">
                                <th class="py-2.5 px-4 w-12 text-center">#</th>
                                <th class="py-2.5 px-4">Tên Danh Mục</th>
                                <th class="py-2.5 px-4">Đường Dẫn (Slug)</th>
                                <th class="py-2.5 px-4">Danh Mục Cha</th>
                                <th class="py-2.5 px-4 text-center">Số Sản Phẩm</th>
                                <th class="py-2.5 px-4 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(cat, idx) in categories.data" :key="cat.id" class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4 text-center text-gray-400 font-mono">{{ idx + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-[#343a40]">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-6 h-6 rounded bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                            <FolderTree class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ cat.ten_danh_muc }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-mono text-gray-500 text-[11px]">{{ cat.duong_dan_seo }}</td>
                                <td class="py-3 px-4 text-gray-600">
                                    <span v-if="cat.danh_muc_cha" class="inline-flex items-center px-2 py-0.5 rounded-sm bg-gray-100 text-gray-700 text-[11px]">
                                        <Folder class="w-3 h-3 mr-1 text-gray-400" />
                                        {{ cat.danh_muc_cha.ten_danh_muc }}
                                    </span>
                                    <span v-else class="text-gray-400 italic text-[11px]">-- Danh mục gốc --</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-sm text-[11px] font-bold bg-[#17a2b8]/10 text-[#17a2b8] border border-[#17a2b8]/20">
                                        {{ cat.san_phams_count }} sản phẩm
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right space-x-1">
                                    <button
                                        class="px-2 py-1 bg-gray-100 hover:bg-[#007bff] hover:text-white text-gray-700 rounded-sm font-semibold transition-colors"
                                        title="Chỉnh sửa"
                                        @click="openEditModal(cat)"
                                    >
                                        <Edit2 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Sửa</span>
                                    </button>
                                    <button
                                        class="px-2 py-1 bg-red-50 hover:bg-[#dc3545] hover:text-white text-red-600 rounded-sm font-semibold transition-colors"
                                        title="Xóa danh mục"
                                        @click="deleteCategory(cat)"
                                    >
                                        <Trash2 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Xóa</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="6" class="py-8 text-center text-gray-400 italic">
                                    Không có danh mục nào phù hợp.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ADMINLTE MODAL -->
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            >
                <div class="bg-white rounded-sm max-w-md w-full shadow-xl border border-gray-300 overflow-hidden">
                    <!-- Modal Header AdminLTE -->
                    <div class="px-4 py-3 bg-[#007bff] text-white flex items-center justify-between">
                        <h4 class="text-sm font-bold">
                            {{ editingCategory ? 'Chỉnh Sửa Danh Mục' : 'Thêm Danh Mục Mới' }}
                        </h4>
                        <button class="text-white hover:text-gray-200 p-1" @click="showModal = false">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form class="p-4 space-y-3 text-xs" @submit.prevent="submitForm">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Tên Danh Mục <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.ten_danh_muc"
                                type="text"
                                required
                                placeholder="Ví dụ: Rau Củ Tươi Sạch"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                            <p v-if="form.errors.ten_danh_muc" class="text-red-500 text-[11px] mt-0.5">{{ form.errors.ten_danh_muc }}</p>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Đường Dẫn SEO (Slug)
                            </label>
                            <input
                                v-model="form.duong_dan_seo"
                                type="text"
                                placeholder="Tự sinh nếu bỏ trống"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm font-mono text-[11px] focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Danh Mục Cha
                            </label>
                            <select
                                v-model="form.danh_muc_cha_id"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm bg-white focus:outline-hidden focus:border-[#007bff]"
                            >
                                <option value="">-- Là Danh mục gốc --</option>
                                <option
                                    v-for="p in parentCategories"
                                    :key="p.id"
                                    :value="p.id"
                                    :disabled="editingCategory?.id === p.id"
                                >
                                    {{ p.ten_danh_muc }}
                                </option>
                            </select>
                        </div>

                        <!-- Modal Footer AdminLTE -->
                        <div class="pt-3 border-t border-gray-200 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-sm font-semibold transition-colors"
                                @click="showModal = false"
                            >
                                Đóng
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-1.5 bg-[#007bff] hover:bg-[#0069d9] text-white rounded-sm font-semibold shadow-2xs transition-colors flex items-center space-x-1"
                            >
                                <Save class="w-3.5 h-3.5" />
                                <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Danh Mục' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
