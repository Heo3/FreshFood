<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    Plus,
    Search,
    Truck,
    Edit2,
    Trash2,
    Phone,
    Mail,
    X,
    Save,
} from 'lucide-vue-next';

const props = defineProps<{
    suppliers: {
        data: Array<{
            id: number;
            ma_ncc: string;
            ten_nha_cung_cap: string;
            nguoi_lien_he: string | null;
            so_dien_thoai: string;
            email: string | null;
            dia_chi: string | null;
            ma_so_thue: string | null;
            ghi_chu: string | null;
            trang_thai: number;
            phieu_nhaps_count: number;
        }>;
        links: Array<any>;
    };
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingSupplier = ref<any | null>(null);

const form = useForm({
    ma_ncc: '',
    ten_nha_cung_cap: '',
    nguoi_lien_he: '',
    so_dien_thoai: '',
    email: '',
    dia_chi: '',
    ma_so_thue: '',
    ghi_chu: '',
    trang_thai: 1,
});

function openCreateModal() {
    editingSupplier.value = null;
    form.reset();
    form.clearErrors();
    form.ma_ncc = 'NCC-' + Math.floor(100 + Math.random() * 900);
    showModal.value = true;
}

function openEditModal(supplier: any) {
    editingSupplier.value = supplier;
    form.clearErrors();
    form.ma_ncc = supplier.ma_ncc;
    form.ten_nha_cung_cap = supplier.ten_nha_cung_cap;
    form.nguoi_lien_he = supplier.nguoi_lien_he || '';
    form.so_dien_thoai = supplier.so_dien_thoai;
    form.email = supplier.email || '';
    form.dia_chi = supplier.dia_chi || '';
    form.ma_so_thue = supplier.ma_so_thue || '';
    form.ghi_chu = supplier.ghi_chu || '';
    form.trang_thai = supplier.trang_thai;
    showModal.value = true;
}

function submitForm() {
    if (editingSupplier.value) {
        form.put(route('admin.suppliers.update', editingSupplier.value.id), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('admin.suppliers.store'), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
}

function deleteSupplier(supplier: any) {
    if (confirm(`Bạn có chắc muốn xóa nhà cung cấp "${supplier.ten_nha_cung_cap}"?`)) {
        router.delete(route('admin.suppliers.destroy', supplier.id));
    }
}

function handleSearch() {
    router.get(route('admin.suppliers.index'), { search: search.value }, { preserveState: true });
}
</script>

<template>
    <AdminLayout
        title="Nhà Cung Cấp"
        :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Nhà cung cấp' }]"
    >
        <div class="space-y-4">
            <!-- ADMINLTE CARD -->
            <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs">
                <!-- CARD HEADER & TOOLS -->
                <div class="px-4 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h3 class="text-sm font-bold text-[#343a40] flex items-center space-x-2">
                        <Truck class="w-4 h-4 text-[#007bff]" />
                        <span>Danh Sách Đối Tác Cung Ứng</span>
                    </h3>

                    <div class="flex items-center space-x-2">
                        <div class="relative w-48 sm:w-60">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Tên, mã NCC hoặc SĐT..."
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
                            <span>Thêm NCC</span>
                        </button>
                    </div>
                </div>

                <!-- CARD BODY TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase text-[10px]">
                                <th class="py-2.5 px-4">Mã / Nhà Cung Cấp</th>
                                <th class="py-2.5 px-4">Đại Diện Liên Hệ</th>
                                <th class="py-2.5 px-4">Thông Tin Liên Lạc</th>
                                <th class="py-2.5 px-4">Địa Chỉ Trụ Sở</th>
                                <th class="py-2.5 px-4 text-center">Đợt Nhập Hàng</th>
                                <th class="py-2.5 px-4 text-center">Trạng Thái</th>
                                <th class="py-2.5 px-4 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="sup in suppliers.data" :key="sup.id" class="hover:bg-gray-50 transition-colors">
                                <td class="py-2.5 px-4">
                                    <div class="font-bold text-gray-900">{{ sup.ten_nha_cung_cap }}</div>
                                    <span class="font-mono text-[#007bff] font-semibold text-[10px]">{{ sup.ma_ncc }}</span>
                                </td>
                                <td class="py-2.5 px-4 text-gray-700 font-medium">
                                    {{ sup.nguoi_lien_he || 'Chưa cập nhật' }}
                                </td>
                                <td class="py-2.5 px-4 text-[11px] space-y-0.5">
                                    <div class="flex items-center space-x-1 text-gray-800 font-mono">
                                        <Phone class="w-3 h-3 text-gray-400" />
                                        <span>{{ sup.so_dien_thoai }}</span>
                                    </div>
                                    <div v-if="sup.email" class="flex items-center space-x-1 text-gray-500">
                                        <Mail class="w-3 h-3 text-gray-400" />
                                        <span>{{ sup.email }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-4 text-gray-500 max-w-xs truncate">
                                    {{ sup.dia_chi || 'N/A' }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold bg-gray-100 text-gray-700">
                                        {{ sup.phieu_nhaps_count }} phiếu
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span
                                        :class="[
                                            'inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase shadow-2xs',
                                            sup.trang_thai === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        ]"
                                    >
                                        {{ sup.trang_thai === 1 ? 'Đang hợp tác' : 'Tạm dừng' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-right space-x-1">
                                    <button
                                        class="px-2 py-1 bg-gray-100 hover:bg-[#007bff] hover:text-white text-gray-700 rounded-sm font-semibold transition-colors"
                                        title="Chỉnh sửa"
                                        @click="openEditModal(sup)"
                                    >
                                        <Edit2 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Sửa</span>
                                    </button>
                                    <button
                                        class="px-2 py-1 bg-red-50 hover:bg-[#dc3545] hover:text-white text-red-600 rounded-sm font-semibold transition-colors"
                                        title="Xóa nhà cung cấp"
                                        @click="deleteSupplier(sup)"
                                    >
                                        <Trash2 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Xóa</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="suppliers.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-gray-400 italic">
                                    Không có nhà cung cấp nào.
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
                <div class="bg-white rounded-sm max-w-lg w-full shadow-xl border border-gray-300 overflow-hidden text-xs">
                    <div class="px-4 py-3 bg-[#007bff] text-white flex items-center justify-between">
                        <h4 class="font-bold text-sm">
                            {{ editingSupplier ? 'Chỉnh Sửa Nhà Cung Cấp' : 'Thêm Nhà Cung Cấp Mới' }}
                        </h4>
                        <button class="text-white hover:text-gray-200 p-1" @click="showModal = false">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form class="p-4 space-y-3" @submit.prevent="submitForm">
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">
                                    Mã NCC <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.ma_ncc"
                                    type="text"
                                    required
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm font-mono uppercase focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>

                            <div class="col-span-2">
                                <label class="block font-bold text-gray-700 mb-1">
                                    Tên Nhà Cung Cấp <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.ten_nha_cung_cap"
                                    type="text"
                                    required
                                    placeholder="HTX Rau Sạch Đà Lạt"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Đại Diện Liên Hệ</label>
                                <input
                                    v-model="form.nguoi_lien_he"
                                    type="text"
                                    placeholder="Nguyễn Văn A"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">
                                    Số Điện Thoại <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.so_dien_thoai"
                                    type="text"
                                    required
                                    placeholder="0987654321"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Email</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="ncc@domain.com"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Mã Số Thuế</label>
                                <input
                                    v-model="form.ma_so_thue"
                                    type="text"
                                    placeholder="0312345678"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Địa Chỉ Kho Hàng / Trụ Sở</label>
                            <input
                                v-model="form.dia_chi"
                                type="text"
                                placeholder="123 Đinh Tiên Hoàng, Đà Lạt..."
                                class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div class="pt-3 border-t border-gray-200 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-sm font-semibold transition-colors"
                                @click="showModal = false"
                            >
                                Hủy Bỏ
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-1.5 bg-[#007bff] hover:bg-[#0069d9] text-white rounded-sm font-semibold shadow-2xs transition-colors flex items-center space-x-1"
                            >
                                <Save class="w-3.5 h-3.5" />
                                <span>{{ form.processing ? 'Đang lưu...' : (editingSupplier ? 'Lưu Thay Đổi' : 'Tạo NCC') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
