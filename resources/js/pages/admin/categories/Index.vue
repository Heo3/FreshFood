<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { Edit2, Folder, FolderTree, Plus, Save, Search, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

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
    <AdminLayout title="Danh Mục Hàng Hóa" :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Danh mục' }]">
        <div class="space-y-4">
            <!-- ADMINLTE CARD -->
            <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white">
                <!-- CARD HEADER & TOOLS -->
                <div class="flex flex-col justify-between gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center">
                    <h3 class="flex items-center space-x-2 text-sm font-bold text-[#343a40]">
                        <FolderTree class="h-4 w-4 text-[#007bff]" />
                        <span>Danh Sách Danh Mục Sản Phẩm</span>
                    </h3>

                    <!-- CARD TOOLS -->
                    <div class="flex items-center space-x-2">
                        <div class="relative w-48 sm:w-60">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Tìm kiếm danh mục..."
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-gray-50 py-1.5 pl-3 pr-8 text-xs focus:border-[#007bff]"
                                @keyup.enter="handleSearch"
                            />
                            <Search class="absolute right-2.5 top-2.5 h-3.5 w-3.5 cursor-pointer text-gray-400" @click="handleSearch" />
                        </div>

                        <button
                            class="shadow-2xs inline-flex shrink-0 items-center space-x-1.5 rounded-sm bg-[#007bff] px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#0069d9]"
                            @click="openCreateModal"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            <span>Thêm Mới</span>
                        </button>
                    </div>
                </div>

                <!-- CARD BODY - ADMINLTE TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase text-gray-600">
                                <th class="w-12 px-4 py-2.5 text-center">#</th>
                                <th class="px-4 py-2.5">Tên Danh Mục</th>
                                <th class="px-4 py-2.5">Đường Dẫn (Slug)</th>
                                <th class="px-4 py-2.5">Danh Mục Cha</th>
                                <th class="px-4 py-2.5 text-center">Số Sản Phẩm</th>
                                <th class="px-4 py-2.5 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(cat, idx) in categories.data" :key="cat.id" class="transition-colors hover:bg-gray-50">
                                <td class="px-4 py-3 text-center font-mono text-gray-400">{{ idx + 1 }}</td>
                                <td class="px-4 py-3 font-bold text-[#343a40]">
                                    <div class="flex items-center space-x-2">
                                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-emerald-50 text-emerald-600">
                                            <FolderTree class="h-3.5 w-3.5" />
                                        </div>
                                        <span>{{ cat.ten_danh_muc }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] text-gray-500">{{ cat.duong_dan_seo }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    <span
                                        v-if="cat.danh_muc_cha"
                                        class="inline-flex items-center rounded-sm bg-gray-100 px-2 py-0.5 text-[11px] text-gray-700"
                                    >
                                        <Folder class="mr-1 h-3 w-3 text-gray-400" />
                                        {{ cat.danh_muc_cha.ten_danh_muc }}
                                    </span>
                                    <span v-else class="text-[11px] italic text-gray-400">-- Danh mục gốc --</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-block rounded-sm border border-[#17a2b8]/20 bg-[#17a2b8]/10 px-2 py-0.5 text-[11px] font-bold text-[#17a2b8]"
                                    >
                                        {{ cat.san_phams_count }} sản phẩm
                                    </span>
                                </td>
                                <td class="space-x-1 px-4 py-3 text-right">
                                    <button
                                        class="rounded-sm bg-gray-100 px-2 py-1 font-semibold text-gray-700 transition-colors hover:bg-[#007bff] hover:text-white"
                                        title="Chỉnh sửa"
                                        @click="openEditModal(cat)"
                                    >
                                        <Edit2 class="mr-1 inline h-3.5 w-3.5" />
                                        <span>Sửa</span>
                                    </button>
                                    <button
                                        class="rounded-sm bg-red-50 px-2 py-1 font-semibold text-red-600 transition-colors hover:bg-[#dc3545] hover:text-white"
                                        title="Xóa danh mục"
                                        @click="deleteCategory(cat)"
                                    >
                                        <Trash2 class="mr-1 inline h-3.5 w-3.5" />
                                        <span>Xóa</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="6" class="py-8 text-center italic text-gray-400">Không có danh mục nào phù hợp.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ADMINLTE MODAL -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md overflow-hidden rounded-sm border border-gray-300 bg-white shadow-xl">
                    <!-- Modal Header AdminLTE -->
                    <div class="flex items-center justify-between bg-[#007bff] px-4 py-3 text-white">
                        <h4 class="text-sm font-bold">
                            {{ editingCategory ? 'Chỉnh Sửa Danh Mục' : 'Thêm Danh Mục Mới' }}
                        </h4>
                        <button class="p-1 text-white hover:text-gray-200" @click="showModal = false">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form class="space-y-3 p-4 text-xs" @submit.prevent="submitForm">
                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Tên Danh Mục <span class="text-red-500">*</span> </label>
                            <input
                                v-model="form.ten_danh_muc"
                                type="text"
                                required
                                placeholder="Ví dụ: Rau Củ Tươi Sạch"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                            <p v-if="form.errors.ten_danh_muc" class="mt-0.5 text-[11px] text-red-500">{{ form.errors.ten_danh_muc }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Đường Dẫn SEO (Slug) </label>
                            <input
                                v-model="form.duong_dan_seo"
                                type="text"
                                placeholder="Tự sinh nếu bỏ trống"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 font-mono text-[11px] focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Danh Mục Cha </label>
                            <select
                                v-model="form.danh_muc_cha_id"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-white px-3 py-1.5 focus:border-[#007bff]"
                            >
                                <option value="">-- Là Danh mục gốc --</option>
                                <option v-for="p in parentCategories" :key="p.id" :value="p.id" :disabled="editingCategory?.id === p.id">
                                    {{ p.ten_danh_muc }}
                                </option>
                            </select>
                        </div>

                        <!-- Modal Footer AdminLTE -->
                        <div class="flex items-center justify-end space-x-2 border-t border-gray-200 pt-3">
                            <button
                                type="button"
                                class="rounded-sm bg-gray-200 px-3 py-1.5 font-semibold text-gray-700 transition-colors hover:bg-gray-300"
                                @click="showModal = false"
                            >
                                Đóng
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="shadow-2xs flex items-center space-x-1 rounded-sm bg-[#007bff] px-4 py-1.5 font-semibold text-white transition-colors hover:bg-[#0069d9]"
                            >
                                <Save class="h-3.5 w-3.5" />
                                <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Danh Mục' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
