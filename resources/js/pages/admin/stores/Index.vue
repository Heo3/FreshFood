<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    Plus,
    Search,
    Store,
    Edit2,
    Trash2,
    Clock,
    Users,
    ShoppingBag,
    X,
    Save,
} from 'lucide-vue-next';

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
    <AdminLayout
        title="Chuỗi Cửa Hàng Siêu Thị"
        :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Chuỗi cửa hàng' }]"
    >
        <div class="space-y-4">
            <!-- ADMINLTE CARD -->
            <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs">
                <!-- CARD HEADER & TOOLS -->
                <div class="px-4 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h3 class="text-sm font-bold text-[#343a40] flex items-center space-x-2">
                        <Store class="w-4 h-4 text-[#007bff]" />
                        <span>Mạng Lưới Chi Nhánh FreshFood</span>
                    </h3>

                    <div class="flex items-center space-x-2">
                        <div class="relative w-48 sm:w-60">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Tên hoặc địa chỉ chi nhánh..."
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
                            <span>Mở Điểm Bán Mới</span>
                        </button>
                    </div>
                </div>

                <!-- CARD BODY TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase text-[10px]">
                                <th class="py-2.5 px-4">Tên Cửa Hàng</th>
                                <th class="py-2.5 px-4">Địa Chỉ</th>
                                <th class="py-2.5 px-4">Hotline</th>
                                <th class="py-2.5 px-4">Giờ Hoạt Động</th>
                                <th class="py-2.5 px-4 text-center">Nhân Sự</th>
                                <th class="py-2.5 px-4 text-center">Đơn Hàng</th>
                                <th class="py-2.5 px-4 text-center">Trạng Thái</th>
                                <th class="py-2.5 px-4 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="st in stores.data" :key="st.id" class="hover:bg-gray-50 transition-colors">
                                <td class="py-2.5 px-4">
                                    <div class="font-bold text-gray-900">{{ st.ten_cua_hang }}</div>
                                    <span class="text-gray-400 font-mono text-[10px]">ID: #{{ st.id }}</span>
                                </td>
                                <td class="py-2.5 px-4 text-gray-600 max-w-xs truncate">
                                    {{ st.dia_chi }}
                                </td>
                                <td class="py-2.5 px-4 font-mono font-bold text-gray-800">
                                    {{ st.so_dien_thoai }}
                                </td>
                                <td class="py-2.5 px-4 text-gray-500">
                                    <span class="inline-flex items-center space-x-1">
                                        <Clock class="w-3 h-3 text-gray-400" />
                                        <span>{{ st.gio_mo_cua }} - {{ st.gio_dong_cua }}</span>
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold bg-blue-100 text-blue-800">
                                        {{ st.users_count }} người
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold bg-green-100 text-green-800">
                                        {{ st.don_hangs_count }} đơn
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span
                                        :class="[
                                            'inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase shadow-2xs',
                                            st.trang_thai === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        ]"
                                    >
                                        {{ st.trang_thai === 1 ? 'Đang mở cửa' : 'Tạm dừng' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-right space-x-1">
                                    <button
                                        class="px-2 py-1 bg-gray-100 hover:bg-[#007bff] hover:text-white text-gray-700 rounded-sm font-semibold transition-colors"
                                        title="Chỉnh sửa"
                                        @click="openEditModal(st)"
                                    >
                                        <Edit2 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Sửa</span>
                                    </button>
                                    <button
                                        class="px-2 py-1 bg-red-50 hover:bg-[#dc3545] hover:text-white text-red-600 rounded-sm font-semibold transition-colors"
                                        title="Đóng cửa"
                                        @click="deleteStore(st)"
                                    >
                                        <Trash2 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Đóng</span>
                                    </button>
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
                            {{ editingStore ? 'Chỉnh Sửa Chi Nhánh' : 'Mở Điểm Bán Mới' }}
                        </h4>
                        <button class="text-white hover:text-gray-200 p-1" @click="showModal = false">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form class="p-4 space-y-3" @submit.prevent="submitForm">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Tên Siêu Thị / Chi Nhánh <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.ten_cua_hang"
                                type="text"
                                required
                                placeholder="Fresh Food - Quận 1"
                                class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Hotline / Số Điện Thoại <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.so_dien_thoai"
                                type="text"
                                required
                                placeholder="0901234567"
                                class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Địa Chỉ Chi Nhánh <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.dia_chi"
                                type="text"
                                required
                                placeholder="123 Lê Lợi, Bến Nghé, Quận 1, TP.HCM"
                                class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Giờ Mở Cửa</label>
                                <input
                                    v-model="form.gio_mo_cua"
                                    type="text"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Giờ Đóng Cửa</label>
                                <input
                                    v-model="form.gio_dong_cua"
                                    type="text"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Trạng Thái Hoạt Động</label>
                            <select
                                v-model="form.trang_thai"
                                class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm bg-white focus:outline-hidden focus:border-[#007bff]"
                            >
                                <option :value="1">Đang mở cửa kinh doanh</option>
                                <option :value="0">Tạm đóng cửa / Bảo trì</option>
                            </select>
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
                                <span>{{ form.processing ? 'Đang lưu...' : (editingStore ? 'Lưu Thay Đổi' : 'Thêm Chi Nhánh') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
