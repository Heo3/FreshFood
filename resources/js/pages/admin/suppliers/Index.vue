<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { Edit2, Mail, Phone, Plus, Save, Search, Trash2, Truck, X } from 'lucide-vue-next';
import { ref } from 'vue';

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
    <AdminLayout title="Nhà Cung Cấp" :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Nhà cung cấp' }]">
        <div class="space-y-4">
            <!-- ADMINLTE CARD -->
            <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white">
                <!-- CARD HEADER & TOOLS -->
                <div class="flex flex-col justify-between gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center">
                    <h3 class="flex items-center space-x-2 text-sm font-bold text-[#343a40]">
                        <Truck class="h-4 w-4 text-[#007bff]" />
                        <span>Danh Sách Đối Tác Cung Ứng</span>
                    </h3>

                    <div class="flex items-center space-x-2">
                        <div class="relative w-48 sm:w-60">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Tên, mã NCC hoặc SĐT..."
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
                            <span>Thêm NCC</span>
                        </button>
                    </div>
                </div>

                <!-- CARD BODY TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase text-gray-600">
                                <th class="px-4 py-2.5">Mã / Nhà Cung Cấp</th>
                                <th class="px-4 py-2.5">Đại Diện Liên Hệ</th>
                                <th class="px-4 py-2.5">Thông Tin Liên Lạc</th>
                                <th class="px-4 py-2.5">Địa Chỉ Trụ Sở</th>
                                <th class="px-4 py-2.5 text-center">Đợt Nhập Hàng</th>
                                <th class="px-4 py-2.5 text-center">Trạng Thái</th>
                                <th class="px-4 py-2.5 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="sup in suppliers.data" :key="sup.id" class="transition-colors hover:bg-gray-50">
                                <td class="px-4 py-2.5">
                                    <div class="font-bold text-gray-900">{{ sup.ten_nha_cung_cap }}</div>
                                    <span class="font-mono text-[10px] font-semibold text-[#007bff]">{{ sup.ma_ncc }}</span>
                                </td>
                                <td class="px-4 py-2.5 font-medium text-gray-700">
                                    {{ sup.nguoi_lien_he || 'Chưa cập nhật' }}
                                </td>
                                <td class="space-y-0.5 px-4 py-2.5 text-[11px]">
                                    <div class="flex items-center space-x-1 font-mono text-gray-800">
                                        <Phone class="h-3 w-3 text-gray-400" />
                                        <span>{{ sup.so_dien_thoai }}</span>
                                    </div>
                                    <div v-if="sup.email" class="flex items-center space-x-1 text-gray-500">
                                        <Mail class="h-3 w-3 text-gray-400" />
                                        <span>{{ sup.email }}</span>
                                    </div>
                                </td>
                                <td class="max-w-xs truncate px-4 py-2.5 text-gray-500">
                                    {{ sup.dia_chi || 'N/A' }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-block rounded-sm bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-700">
                                        {{ sup.phieu_nhaps_count }} phiếu
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span
                                        :class="[
                                            'shadow-2xs inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase',
                                            sup.trang_thai === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                                        ]"
                                    >
                                        {{ sup.trang_thai === 1 ? 'Đang hợp tác' : 'Tạm dừng' }}
                                    </span>
                                </td>
                                <td class="space-x-1 px-4 py-2.5 text-right">
                                    <button
                                        class="rounded-sm bg-gray-100 px-2 py-1 font-semibold text-gray-700 transition-colors hover:bg-[#007bff] hover:text-white"
                                        title="Chỉnh sửa"
                                        @click="openEditModal(sup)"
                                    >
                                        <Edit2 class="mr-1 inline h-3.5 w-3.5" />
                                        <span>Sửa</span>
                                    </button>
                                    <button
                                        class="rounded-sm bg-red-50 px-2 py-1 font-semibold text-red-600 transition-colors hover:bg-[#dc3545] hover:text-white"
                                        title="Xóa nhà cung cấp"
                                        @click="deleteSupplier(sup)"
                                    >
                                        <Trash2 class="mr-1 inline h-3.5 w-3.5" />
                                        <span>Xóa</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="suppliers.data.length === 0">
                                <td colspan="7" class="py-8 text-center italic text-gray-400">Không có nhà cung cấp nào.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ADMINLTE MODAL -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-lg overflow-hidden rounded-sm border border-gray-300 bg-white text-xs shadow-xl">
                    <div class="flex items-center justify-between bg-[#007bff] px-4 py-3 text-white">
                        <h4 class="text-sm font-bold">
                            {{ editingSupplier ? 'Chỉnh Sửa Nhà Cung Cấp' : 'Thêm Nhà Cung Cấp Mới' }}
                        </h4>
                        <button class="p-1 text-white hover:text-gray-200" @click="showModal = false">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form class="space-y-3 p-4" @submit.prevent="submitForm">
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="mb-1 block font-bold text-gray-700"> Mã NCC <span class="text-red-500">*</span> </label>
                                <input
                                    v-model="form.ma_ncc"
                                    type="text"
                                    required
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 font-mono uppercase focus:border-[#007bff]"
                                />
                            </div>

                            <div class="col-span-2">
                                <label class="mb-1 block font-bold text-gray-700"> Tên Nhà Cung Cấp <span class="text-red-500">*</span> </label>
                                <input
                                    v-model="form.ten_nha_cung_cap"
                                    type="text"
                                    required
                                    placeholder="HTX Rau Sạch Đà Lạt"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Đại Diện Liên Hệ</label>
                                <input
                                    v-model="form.nguoi_lien_he"
                                    type="text"
                                    placeholder="Nguyễn Văn A"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700"> Số Điện Thoại <span class="text-red-500">*</span> </label>
                                <input
                                    v-model="form.so_dien_thoai"
                                    type="text"
                                    required
                                    placeholder="0987654321"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Email</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="ncc@domain.com"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Mã Số Thuế</label>
                                <input
                                    v-model="form.ma_so_thue"
                                    type="text"
                                    placeholder="0312345678"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700">Địa Chỉ Kho Hàng / Trụ Sở</label>
                            <input
                                v-model="form.dia_chi"
                                type="text"
                                placeholder="123 Đinh Tiên Hoàng, Đà Lạt..."
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div class="flex items-center justify-end space-x-2 border-t border-gray-200 pt-3">
                            <button
                                type="button"
                                class="rounded-sm bg-gray-200 px-3 py-1.5 font-semibold text-gray-700 transition-colors hover:bg-gray-300"
                                @click="showModal = false"
                            >
                                Hủy Bỏ
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="shadow-2xs flex items-center space-x-1 rounded-sm bg-[#007bff] px-4 py-1.5 font-semibold text-white transition-colors hover:bg-[#0069d9]"
                            >
                                <Save class="h-3.5 w-3.5" />
                                <span>{{ form.processing ? 'Đang lưu...' : editingSupplier ? 'Lưu Thay Đổi' : 'Tạo NCC' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
