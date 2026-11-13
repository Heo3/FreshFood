<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    Plus,
    Search,
    TicketPercent,
    Edit2,
    Trash2,
    X,
    Save,
} from 'lucide-vue-next';

const props = defineProps<{
    coupons: {
        data: Array<{
            id: number;
            ma_khuyen_mai: string;
            ten_chuong_trinh: string;
            loai_giam_gia: string;
            gia_tri_giam: number;
            don_hang_toi_thieu: number | null;
            giam_toi_da: number | null;
            ngay_bat_dau: string;
            ngay_ket_thuc: string;
            so_luong: number;
            da_su_dung: number;
            trang_thai: number;
        }>;
        links: Array<any>;
    };
    filters: {
        search?: string;
        trang_thai?: string;
    };
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.trang_thai || '');
const showModal = ref(false);
const editingCoupon = ref<any | null>(null);

const form = useForm({
    ma_khuyen_mai: '',
    ten_chuong_trinh: '',
    loai_giam_gia: 'phan_tram',
    gia_tri_giam: '',
    don_hang_toi_thieu: '',
    giam_toi_da: '',
    ngay_bat_dau: new Date().toISOString().slice(0, 10),
    ngay_ket_thuc: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
    so_luong: 100,
    trang_thai: 1,
});

function openCreateModal() {
    editingCoupon.value = null;
    form.reset();
    form.clearErrors();
    form.ngay_bat_dau = new Date().toISOString().slice(0, 10);
    form.ngay_ket_thuc = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
    showModal.value = true;
}

function openEditModal(coupon: any) {
    editingCoupon.value = coupon;
    form.clearErrors();
    form.ma_khuyen_mai = coupon.ma_khuyen_mai;
    form.ten_chuong_trinh = coupon.ten_chuong_trinh;
    form.loai_giam_gia = coupon.loai_giam_gia;
    form.gia_tri_giam = coupon.gia_tri_giam;
    form.don_hang_toi_thieu = coupon.don_hang_toi_thieu || '';
    form.giam_toi_da = coupon.giam_toi_da || '';
    form.ngay_bat_dau = coupon.ngay_bat_dau ? coupon.ngay_bat_dau.slice(0, 10) : '';
    form.ngay_ket_thuc = coupon.ngay_ket_thuc ? coupon.ngay_ket_thuc.slice(0, 10) : '';
    form.so_luong = coupon.so_luong;
    form.trang_thai = coupon.trang_thai;
    showModal.value = true;
}

function submitForm() {
    if (editingCoupon.value) {
        form.put(route('admin.coupons.update', editingCoupon.value.id), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('admin.coupons.store'), {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
}

function deleteCoupon(coupon: any) {
    if (confirm(`Bạn có chắc muốn xóa mã giảm giá "${coupon.ma_khuyen_mai}"?`)) {
        router.delete(route('admin.coupons.destroy', coupon.id));
    }
}

function handleFilter() {
    router.get(
        route('admin.coupons.index'),
        { search: search.value, trang_thai: status.value },
        { preserveState: true }
    );
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}
</script>

<template>
    <AdminLayout
        title="Khuyến Mãi & Voucher"
        :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Khuyến mãi' }]"
    >
        <div class="space-y-4">
            <!-- ADMINLTE CARD -->
            <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs">
                <!-- CARD HEADER & TOOLS -->
                <div class="px-4 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h3 class="text-sm font-bold text-[#343a40] flex items-center space-x-2">
                        <TicketPercent class="w-4 h-4 text-[#007bff]" />
                        <span>Danh Sách Mã Giảm Giá & Voucher</span>
                    </h3>

                    <!-- TOOLS -->
                    <div class="flex items-center space-x-2">
                        <div class="relative w-44 sm:w-56">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Mã code hoặc tên..."
                                class="w-full pl-3 pr-8 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                                @keyup.enter="handleFilter"
                            />
                            <Search
                                class="w-3.5 h-3.5 text-gray-400 absolute right-2.5 top-2.5 cursor-pointer"
                                @click="handleFilter"
                            />
                        </div>

                        <select
                            v-model="status"
                            class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                            @change="handleFilter"
                        >
                            <option value="">-- Trạng thái --</option>
                            <option value="1">Đang chạy</option>
                            <option value="0">Tạm dừng</option>
                        </select>

                        <button
                            class="inline-flex items-center space-x-1 px-3 py-1.5 bg-[#007bff] hover:bg-[#0069d9] text-white rounded-sm text-xs font-semibold shadow-2xs transition-colors shrink-0"
                            @click="openCreateModal"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            <span>Tạo Voucher</span>
                        </button>
                    </div>
                </div>

                <!-- CARD BODY TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase text-[10px]">
                                <th class="py-2.5 px-4">Mã Code</th>
                                <th class="py-2.5 px-4">Tên Chương Trình</th>
                                <th class="py-2.5 px-4">Mức Giảm</th>
                                <th class="py-2.5 px-4">Đơn Tối Thiểu</th>
                                <th class="py-2.5 px-4 text-center">Lượt Dùng</th>
                                <th class="py-2.5 px-4">Thời Hạn</th>
                                <th class="py-2.5 px-4 text-center">Trạng Thái</th>
                                <th class="py-2.5 px-4 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="cp in coupons.data" :key="cp.id" class="hover:bg-gray-50 transition-colors">
                                <td class="py-2.5 px-4 font-mono font-bold text-[#007bff]">
                                    {{ cp.ma_khuyen_mai }}
                                </td>
                                <td class="py-2.5 px-4 font-bold text-gray-800">{{ cp.ten_chuong_trinh }}</td>
                                <td class="py-2.5 px-4 font-bold text-gray-900">
                                    <span v-if="cp.loai_giam_gia === 'phan_tram'">{{ cp.gia_tri_giam }}%</span>
                                    <span v-else>{{ formatCurrency(cp.gia_tri_giam) }}</span>
                                    <span v-if="cp.giam_toi_da" class="text-[10px] text-gray-400 block font-normal">
                                        Tối đa {{ formatCurrency(cp.giam_toi_da) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-gray-600">
                                    {{ cp.don_hang_toi_thieu ? formatCurrency(cp.don_hang_toi_thieu) : 'Không giới hạn' }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold bg-gray-100 text-gray-700">
                                        {{ cp.da_su_dung }} / {{ cp.so_luong }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-gray-500 text-[11px]">
                                    <div>Từ: {{ cp.ngay_bat_dau?.slice(0, 10) }}</div>
                                    <div>Đến: {{ cp.ngay_ket_thuc?.slice(0, 10) }}</div>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span
                                        :class="[
                                            'inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase shadow-2xs',
                                            cp.trang_thai === 1 ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600'
                                        ]"
                                    >
                                        {{ cp.trang_thai === 1 ? 'Đang chạy' : 'Tạm dừng' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-right space-x-1">
                                    <button
                                        class="px-2 py-1 bg-gray-100 hover:bg-[#007bff] hover:text-white text-gray-700 rounded-sm font-semibold transition-colors"
                                        title="Chỉnh sửa"
                                        @click="openEditModal(cp)"
                                    >
                                        <Edit2 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Sửa</span>
                                    </button>
                                    <button
                                        class="px-2 py-1 bg-red-50 hover:bg-[#dc3545] hover:text-white text-red-600 rounded-sm font-semibold transition-colors"
                                        title="Xóa voucher"
                                        @click="deleteCoupon(cp)"
                                    >
                                        <Trash2 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Xóa</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="coupons.data.length === 0">
                                <td colspan="8" class="py-8 text-center text-gray-400 italic">
                                    Không có chương trình voucher nào.
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
                            {{ editingCoupon ? 'Chỉnh Sửa Mã Khuyến Mãi' : 'Tạo Voucher Mới' }}
                        </h4>
                        <button class="text-white hover:text-gray-200 p-1" @click="showModal = false">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form class="p-4 space-y-3" @submit.prevent="submitForm">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">
                                    Mã Voucher <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.ma_khuyen_mai"
                                    type="text"
                                    required
                                    placeholder="FRESH20"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm font-mono uppercase focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">
                                    Loại Giảm Giá
                                </label>
                                <select
                                    v-model="form.loai_giam_gia"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm bg-white focus:outline-hidden focus:border-[#007bff]"
                                >
                                    <option value="phan_tram">Phần trăm (%)</option>
                                    <option value="so_tien">Số tiền trực tiếp (VNĐ)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Tên Chương Trình <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.ten_chuong_trinh"
                                type="text"
                                required
                                placeholder="Ưu đãi mừng khách hàng..."
                                class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">
                                    Mức Giảm <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.gia_tri_giam"
                                    type="number"
                                    required
                                    min="0"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm font-bold focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Đơn Tối Thiểu</label>
                                <input
                                    v-model="form.don_hang_toi_thieu"
                                    type="number"
                                    min="0"
                                    placeholder="150000"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Giảm Tối Đa</label>
                                <input
                                    v-model="form.giam_toi_da"
                                    type="number"
                                    min="0"
                                    placeholder="50000"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Ngày Bắt Đầu</label>
                                <input
                                    v-model="form.ngay_bat_dau"
                                    type="date"
                                    required
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Ngày Hết Hạn</label>
                                <input
                                    v-model="form.ngay_ket_thuc"
                                    type="date"
                                    required
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Số Lượng Lượt Dùng</label>
                                <input
                                    v-model="form.so_luong"
                                    type="number"
                                    required
                                    min="1"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Trạng Thái</label>
                                <select
                                    v-model="form.trang_thai"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm bg-white focus:outline-hidden focus:border-[#007bff]"
                                >
                                    <option :value="1">Kích hoạt ngay</option>
                                    <option :value="0">Tạm dừng</option>
                                </select>
                            </div>
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
                                <span>{{ form.processing ? 'Đang lưu...' : (editingCoupon ? 'Lưu Thay Đổi' : 'Tạo Voucher') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
