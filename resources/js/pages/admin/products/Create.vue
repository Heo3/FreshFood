<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Boxes, Save } from 'lucide-vue-next';

const props = defineProps<{
    categories: Array<{ id: number; ten_danh_muc: string }>;
    stores: Array<{ id: number; ten_cua_hang: string }>;
}>();

const form = useForm({
    ten_san_pham: '',
    ma_va_vach: '',
    duong_dan_seo: '',
    danh_muc_id: props.categories[0]?.id || '',
    gia_ban: '',
    gia_giam: '',
    don_vi_tinh: 'Kg',
    quy_cach: 'Khay 500g',
    dieu_kien_bao_quan: 'Nhiệt độ 2 - 5°C trong tủ mát',
    han_su_dung_ngay: 3,
    hinh_anh_chinh: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=600&q=80',
    la_tuoi_song: true,
    trang_thai: 1,
    mo_ta: '',
});

function submit() {
    form.post(route('admin.products.store'));
}
</script>

<template>
    <AdminLayout
        title="Thêm Sản Phẩm Mới"
        :breadcrumbs="[
            { name: 'Admin', href: route('admin.dashboard') },
            { name: 'Sản phẩm', href: route('admin.products.index') },
            { name: 'Thêm mới' },
        ]"
    >
        <div class="mx-auto max-w-4xl space-y-4">
            <!-- BACK BUTTON -->
            <div>
                <Link
                    :href="route('admin.products.index')"
                    class="inline-flex items-center space-x-1.5 text-xs font-semibold text-gray-600 hover:text-[#007bff]"
                >
                    <ArrowLeft class="h-3.5 w-3.5" />
                    <span>Quay lại danh sách sản phẩm</span>
                </Link>
            </div>

            <!-- ADMINLTE CARD -->
            <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white">
                <!-- CARD HEADER -->
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h3 class="flex items-center space-x-2 text-sm font-bold text-[#343a40]">
                        <Boxes class="h-4 w-4 text-[#007bff]" />
                        <span>Thông Tin Sản Phẩm & Giá Niêm Yết</span>
                    </h3>
                </div>

                <!-- CARD BODY -->
                <form class="space-y-4 p-5 text-xs" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block font-bold text-gray-700"> Tên Sản Phẩm <span class="text-red-500">*</span> </label>
                            <input
                                v-model="form.ten_san_pham"
                                type="text"
                                required
                                placeholder="Ví dụ: Rau Cải Xanh Hữu Cơ Đà Lạt"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                            <p v-if="form.errors.ten_san_pham" class="mt-0.5 text-[11px] text-red-500">{{ form.errors.ten_san_pham }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Danh Mục Hàng Hóa <span class="text-red-500">*</span> </label>
                            <select
                                v-model="form.danh_muc_id"
                                required
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-white px-3 py-1.5 focus:border-[#007bff]"
                            >
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.ten_danh_muc }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Mã Sản Phẩm / Barcode </label>
                            <input
                                v-model="form.ma_va_vach"
                                type="text"
                                placeholder="Tự sinh nếu bỏ trống"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 font-mono text-[11px] focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Giá Bán Chuẩn (VNĐ) <span class="text-red-500">*</span> </label>
                            <input
                                v-model="form.gia_ban"
                                type="number"
                                required
                                min="0"
                                step="1000"
                                placeholder="35000"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 font-bold text-gray-900 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Giá Khuyến Mãi (Nếu có) </label>
                            <input
                                v-model="form.gia_giam"
                                type="number"
                                min="0"
                                step="1000"
                                placeholder="29000"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Đơn Vị Tính <span class="text-red-500">*</span> </label>
                            <input
                                v-model="form.don_vi_tinh"
                                type="text"
                                required
                                placeholder="Kg, Bó, Khay..."
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Quy Cách Đóng Gói </label>
                            <input
                                v-model="form.quy_cach"
                                type="text"
                                placeholder="Ví dụ: Khay 500g..."
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Hạn Sử Dụng Tiêu Chuẩn (Số Ngày) </label>
                            <input
                                v-model="form.han_su_dung_ngay"
                                type="number"
                                min="1"
                                placeholder="3"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Điều Kiện Bảo Quản </label>
                            <input
                                v-model="form.dieu_kien_bao_quan"
                                type="text"
                                placeholder="Nhiệt độ 2-5°C..."
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block font-bold text-gray-700"> Link Ảnh Sản Phẩm (URL) </label>
                            <input
                                v-model="form.hinh_anh_chinh"
                                type="text"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div class="flex items-center space-x-6 pt-1 sm:col-span-2">
                            <label class="flex cursor-pointer items-center space-x-2">
                                <input v-model="form.la_tuoi_song" type="checkbox" class="rounded text-[#007bff] focus:ring-[#007bff]" />
                                <span class="font-bold text-gray-800">Thực phẩm tươi sống (Ưu tiên xuất FEFO)</span>
                            </label>

                            <label class="flex cursor-pointer items-center space-x-2">
                                <input
                                    v-model="form.trang_thai"
                                    type="checkbox"
                                    :true-value="1"
                                    :false-value="0"
                                    class="rounded text-[#28a745] focus:ring-[#28a745]"
                                />
                                <span class="font-bold text-gray-800">Kích hoạt kinh doanh</span>
                            </label>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block font-bold text-gray-700"> Mô Tả Chi Tiết / Tiêu Chuẩn Nông Nghiệp </label>
                            <textarea
                                v-model="form.mo_ta"
                                rows="3"
                                placeholder="Nhập xuất xứ, chứng chỉ VietGAP/Organic..."
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>
                    </div>

                    <!-- CARD FOOTER AdminLTE -->
                    <div class="flex items-center justify-end space-x-2 border-t border-gray-200 pt-4">
                        <Link
                            :href="route('admin.products.index')"
                            class="rounded-sm bg-gray-200 px-3.5 py-1.5 font-semibold text-gray-700 transition-colors hover:bg-gray-300"
                        >
                            Hủy Bỏ
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="shadow-2xs flex items-center space-x-1 rounded-sm bg-[#007bff] px-4 py-1.5 font-semibold text-white transition-colors hover:bg-[#0069d9]"
                        >
                            <Save class="h-3.5 w-3.5" />
                            <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Sản Phẩm' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
