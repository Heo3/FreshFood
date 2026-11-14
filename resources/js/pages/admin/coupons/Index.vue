<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { Edit2, Plus, Save, Search, TicketPercent, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

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
    router.get(route('admin.coupons.index'), { search: search.value, trang_thai: status.value }, { preserveState: true });
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}
</script>

<template>
    <AdminLayout title="Khuyến Mãi & Voucher" :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Khuyến mãi' }]">
        <div class="space-y-4">
            <!-- ADMINLTE CARD -->
            <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white">
                <!-- CARD HEADER & TOOLS -->
                <div class="flex flex-col justify-between gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center">
                    <h3 class="flex items-center space-x-2 text-sm font-bold text-[#343a40]">
                        <TicketPercent class="h-4 w-4 text-[#007bff]" />
                        <span>Danh Sách Mã Giảm Giá & Voucher</span>
                    </h3>

                    <!-- TOOLS -->
                    <div class="flex items-center space-x-2">
                        <div class="relative w-44 sm:w-56">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Mã code hoặc tên..."
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-gray-50 py-1.5 pl-3 pr-8 text-xs focus:border-[#007bff]"
                                @keyup.enter="handleFilter"
                            />
                            <Search class="absolute right-2.5 top-2.5 h-3.5 w-3.5 cursor-pointer text-gray-400" @click="handleFilter" />
                        </div>

                        <select
                            v-model="status"
                            class="focus:outline-hidden rounded-sm border border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs focus:border-[#007bff]"
                            @change="handleFilter"
                        >
                            <option value="">-- Trạng thái --</option>
                            <option value="1">Đang chạy</option>
                            <option value="0">Tạm dừng</option>
                        </select>

                        <button
                            class="shadow-2xs inline-flex shrink-0 items-center space-x-1 rounded-sm bg-[#007bff] px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#0069d9]"
                            @click="openCreateModal"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            <span>Tạo Voucher</span>
                        </button>
                    </div>
                </div>

                <!-- CARD BODY TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase text-gray-600">
                                <th class="px-4 py-2.5">Mã Code</th>
                                <th class="px-4 py-2.5">Tên Chương Trình</th>
                                <th class="px-4 py-2.5">Mức Giảm</th>
                                <th class="px-4 py-2.5">Đơn Tối Thiểu</th>
                                <th class="px-4 py-2.5 text-center">Lượt Dùng</th>
                                <th class="px-4 py-2.5">Thời Hạn</th>
                                <th class="px-4 py-2.5 text-center">Trạng Thái</th>
                                <th class="px-4 py-2.5 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="cp in coupons.data" :key="cp.id" class="transition-colors hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-mono font-bold text-[#007bff]">
                                    {{ cp.ma_khuyen_mai }}
                                </td>
                                <td class="px-4 py-2.5 font-bold text-gray-800">{{ cp.ten_chuong_trinh }}</td>
                                <td class="px-4 py-2.5 font-bold text-gray-900">
                                    <span v-if="cp.loai_giam_gia === 'phan_tram'">{{ cp.gia_tri_giam }}%</span>
                                    <span v-else>{{ formatCurrency(cp.gia_tri_giam) }}</span>
                                    <span v-if="cp.giam_toi_da" class="block text-[10px] font-normal text-gray-400">
                                        Tối đa {{ formatCurrency(cp.giam_toi_da) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-gray-600">
                                    {{ cp.don_hang_toi_thieu ? formatCurrency(cp.don_hang_toi_thieu) : 'Không giới hạn' }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-block rounded-sm bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-700">
                                        {{ cp.da_su_dung }} / {{ cp.so_luong }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-[11px] text-gray-500">
                                    <div>Từ: {{ cp.ngay_bat_dau?.slice(0, 10) }}</div>
                                    <div>Đến: {{ cp.ngay_ket_thuc?.slice(0, 10) }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span
                                        :class="[
                                            'shadow-2xs inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase',
                                            cp.trang_thai === 1 ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600',
                                        ]"
                                    >
                                        {{ cp.trang_thai === 1 ? 'Đang chạy' : 'Tạm dừng' }}
                                    </span>
                                </td>
                                <td class="space-x-1 px-4 py-2.5 text-right">
                                    <button
                                        class="rounded-sm bg-gray-100 px-2 py-1 font-semibold text-gray-700 transition-colors hover:bg-[#007bff] hover:text-white"
                                        title="Chỉnh sửa"
                                        @click="openEditModal(cp)"
                                    >
                                        <Edit2 class="mr-1 inline h-3.5 w-3.5" />
                                        <span>Sửa</span>
                                    </button>
                                    <button
                                        class="rounded-sm bg-red-50 px-2 py-1 font-semibold text-red-600 transition-colors hover:bg-[#dc3545] hover:text-white"
                                        title="Xóa voucher"
                                        @click="deleteCoupon(cp)"
                                    >
                                        <Trash2 class="mr-1 inline h-3.5 w-3.5" />
                                        <span>Xóa</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="coupons.data.length === 0">
                                <td colspan="8" class="py-8 text-center italic text-gray-400">Không có chương trình voucher nào.</td>
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
                            {{ editingCoupon ? 'Chỉnh Sửa Mã Khuyến Mãi' : 'Tạo Voucher Mới' }}
                        </h4>
                        <button class="p-1 text-white hover:text-gray-200" @click="showModal = false">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form class="space-y-3 p-4" @submit.prevent="submitForm">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-bold text-gray-700"> Mã Voucher <span class="text-red-500">*</span> </label>
                                <input
                                    v-model="form.ma_khuyen_mai"
                                    type="text"
                                    required
                                    placeholder="FRESH20"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 font-mono uppercase focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700"> Loại Giảm Giá </label>
                                <select
                                    v-model="form.loai_giam_gia"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-white px-2.5 py-1.5 focus:border-[#007bff]"
                                >
                                    <option value="phan_tram">Phần trăm (%)</option>
                                    <option value="so_tien">Số tiền trực tiếp (VNĐ)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Tên Chương Trình <span class="text-red-500">*</span> </label>
                            <input
                                v-model="form.ten_chuong_trinh"
                                type="text"
                                required
                                placeholder="Ưu đãi mừng khách hàng..."
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="mb-1 block font-bold text-gray-700"> Mức Giảm <span class="text-red-500">*</span> </label>
                                <input
                                    v-model="form.gia_tri_giam"
                                    type="number"
                                    required
                                    min="0"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 font-bold focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Đơn Tối Thiểu</label>
                                <input
                                    v-model="form.don_hang_toi_thieu"
                                    type="number"
                                    min="0"
                                    placeholder="150000"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Giảm Tối Đa</label>
                                <input
                                    v-model="form.giam_toi_da"
                                    type="number"
                                    min="0"
                                    placeholder="50000"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Ngày Bắt Đầu</label>
                                <input
                                    v-model="form.ngay_bat_dau"
                                    type="date"
                                    required
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Ngày Hết Hạn</label>
                                <input
                                    v-model="form.ngay_ket_thuc"
                                    type="date"
                                    required
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Số Lượng Lượt Dùng</label>
                                <input
                                    v-model="form.so_luong"
                                    type="number"
                                    required
                                    min="1"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#007bff]"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Trạng Thái</label>
                                <select
                                    v-model="form.trang_thai"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-white px-2.5 py-1.5 focus:border-[#007bff]"
                                >
                                    <option :value="1">Kích hoạt ngay</option>
                                    <option :value="0">Tạm dừng</option>
                                </select>
                            </div>
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
                                <span>{{ form.processing ? 'Đang lưu...' : editingCoupon ? 'Lưu Thay Đổi' : 'Tạo Voucher' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
