<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { Clock, Edit2, Plus, Save, Search, Store, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    stores: {
        data: Array<{
            id: number;
            ten_cua_hang: string;
            so_dien_thoai: string;
            dia_chi: string;
            gio_mo_cua: string | null;
            gio_dong_cua: string | null;
            kinh_do: number | null;
            vi_do: number | null;
            trang_thai: number;
            don_hangs_count: number;
            users_count: number;
        }>;
        links: Array<any>;
    };
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingStore = ref<any | null>(null);

const form = useForm({
    ten_cua_hang: '',
    so_dien_thoai: '',
    dia_chi: '',
    gio_mo_cua: '06:00',
    gio_dong_cua: '21:30',
    kinh_do: '',
    vi_do: '',
    trang_thai: 1,
});

function openCreateModal() {
    editingStore.value = null;
    form.reset();
    form.clearErrors();
    form.gio_mo_cua = '06:00';
    form.gio_dong_cua = '21:30';
    showModal.value = true;
}

function openEditModal(store: any) {
    editingStore.value = store;
    form.clearErrors();
    form.ten_cua_hang = store.ten_cua_hang;
    form.so_dien_thoai = store.so_dien_thoai;
    form.dia_chi = store.dia_chi;
    form.gio_mo_cua = store.gio_mo_cua || '06:00';
    form.gio_dong_cua = store.gio_dong_cua || '21:30';
    form.kinh_do = store.kinh_do || '';
    form.vi_do = store.vi_do || '';
    form.trang_thai = store.trang_thai;
    showModal.value = true;
}

function submitForm() {
    if (editingStore.value) {
        form.put(route('admin.stores.update', editingStore.value.id), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('admin.stores.store'), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
}

function deleteStore(store: any) {
    if (confirm(`Bạn có chắc muốn đóng cửa/xóa chi nhánh "${store.ten_cua_hang}"?`)) {
        router.delete(route('admin.stores.destroy', store.id));
    }
}

function handleSearch() {
    router.get(route('admin.stores.index'), { search: search.value }, { preserveState: true });
}
</script>

<template>
    <AdminLayout title="Chuỗi Cửa Hàng Siêu Thị" :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Chuỗi cửa hàng' }]">
        <div class="space-y-4">
            <!-- ADMINLTE CARD -->
            <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white">
                <!-- CARD HEADER & TOOLS -->
                <div class="flex flex-col justify-between gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center">
                    <h3 class="flex items-center space-x-2 text-sm font-bold text-[#343a40]">
                        <Store class="h-4 w-4 text-[#007bff]" />
                        <span>Mạng Lưới Chi Nhánh FreshFood</span>
                    </h3>

                    <div class="flex items-center space-x-2">
                        <div class="relative w-48 sm:w-60">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Tên hoặc địa chỉ chi nhánh..."
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
                            <span>Mở Điểm Bán Mới</span>
                        </button>
                    </div>
                </div>

                <!-- CARD BODY TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase text-gray-600">
                                <th class="px-4 py-2.5">Tên Cửa Hàng</th>
                                <th class="px-4 py-2.5">Địa Chỉ</th>
                                <th class="px-4 py-2.5">Hotline</th>
                                <th class="px-4 py-2.5">Giờ Hoạt Động</th>
                                <th class="px-4 py-2.5 text-center">Nhân Sự</th>
                                <th class="px-4 py-2.5 text-center">Đơn Hàng</th>
                                <th class="px-4 py-2.5 text-center">Trạng Thái</th>
                                <th class="px-4 py-2.5 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="st in stores.data" :key="st.id" class="transition-colors hover:bg-gray-50">
                                <td class="px-4 py-2.5">
                                    <div class="font-bold text-gray-900">{{ st.ten_cua_hang }}</div>
                                    <span class="font-mono text-[10px] text-gray-400">ID: #{{ st.id }}</span>
                                </td>
                                <td class="max-w-xs truncate px-4 py-2.5 text-gray-600">
                                    {{ st.dia_chi }}
                                </td>
                                <td class="px-4 py-2.5 font-mono font-bold text-gray-800">
                                    {{ st.so_dien_thoai }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-500">
                                    <span class="inline-flex items-center space-x-1">
                                        <Clock class="h-3 w-3 text-gray-400" />
                                        <span>{{ st.gio_mo_cua }} - {{ st.gio_dong_cua }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-block rounded-sm bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-800">
                                        {{ st.users_count }} người
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-block rounded-sm bg-green-100 px-2 py-0.5 text-[10px] font-bold text-green-800">
                                        {{ st.don_hangs_count }} đơn
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span
                                        :class="[
                                            'shadow-2xs inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase',
                                            st.trang_thai === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                                        ]"
                                    >
                                        {{ st.trang_thai === 1 ? 'Đang mở cửa' : 'Tạm dừng' }}
                                    </span>
                                </td>
                                <td class="space-x-1 px-4 py-2.5 text-right">
                                    <button
                                        class="rounded-sm bg-gray-100 px-2 py-1 font-semibold text-gray-700 transition-colors hover:bg-[#007bff] hover:text-white"
                                        title="Chỉnh sửa"
                                        @click="openEditModal(st)"
                                    >
                                        <Edit2 class="mr-1 inline h-3.5 w-3.5" />
                                        <span>Sửa</span>
                                    </button>
                                    <button
                                        class="rounded-sm bg-red-50 px-2 py-1 font-semibold text-red-600 transition-colors hover:bg-[#dc3545] hover:text-white"
                                        title="Đóng cửa"
                                        @click="deleteStore(st)"
                                    >
                                        <Trash2 class="mr-1 inline h-3.5 w-3.5" />
                                        <span>Đóng</span>
                                    </button>
                                </td>
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
                            {{ editingStore ? 'Chỉnh Sửa Chi Nhánh' : 'Mở Điểm Bán Mới' }}
                        </h4>
                        <button class="p-1 text-white hover:text-gray-200" @click="showModal = false">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form class="space-y-3 p-4" @submit.prevent="submitForm">
                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Tên Siêu Thị / Chi Nhánh <span class="text-red-500">*</span> </label>
                            <input
                                v-model="form.ten_cua_hang"
                                type="text"
                                required
                                placeholder="Fresh Food - Quận 1"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Hotline / Số Điện Thoại <span class="text-red-500">*</span> </label>
                            <input
                                v-model="form.so_dien_thoai"
                                type="text"
                                required
                                placeholder="0901234567"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Địa Chỉ Chi Nhánh <span class="text-red-500">*</span> </label>
                            <input
                                v-model="form.dia_chi"
                                type="text"
                                required
                                placeholder="123 Lê Lợi, Bến Nghé, Quận 1, TP.HCM"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Giờ Mở Cửa</label>
                                <input
                                    v-model="form.gio_mo_cua"
                                    type="text"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Giờ Đóng Cửa</label>
                                <input
                                    v-model="form.gio_dong_cua"
                                    type="text"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700">Trạng Thái Hoạt Động</label>
                            <select
                                v-model="form.trang_thai"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-white px-2.5 py-1.5 focus:border-[#007bff]"
                            >
                                <option :value="1">Đang mở cửa kinh doanh</option>
                                <option :value="0">Tạm đóng cửa / Bảo trì</option>
                            </select>
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
                                <span>{{ form.processing ? 'Đang lưu...' : editingStore ? 'Lưu Thay Đổi' : 'Thêm Chi Nhánh' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
