<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { ArrowLeft, Save, Boxes, Sparkles } from 'lucide-vue-next';

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
            { name: 'Thêm mới' }
        ]"
    >
        <div class="max-w-4xl mx-auto space-y-4">
            <!-- BACK BUTTON -->
            <div>
                <Link
                    :href="route('admin.products.index')"
                    class="inline-flex items-center space-x-1.5 text-xs font-semibold text-gray-600 hover:text-[#007bff]"
                >
                    <ArrowLeft class="w-3.5 h-3.5" />
                    <span>Quay lại danh sách sản phẩm</span>
                </Link>
            </div>

            <!-- ADMINLTE CARD -->
            <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs">
                <!-- CARD HEADER -->
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-[#343a40] flex items-center space-x-2">
                        <Boxes class="w-4 h-4 text-[#007bff]" />
                        <span>Thông Tin Sản Phẩm & Giá Niêm Yết</span>
                    </h3>
                </div>

                <!-- CARD BODY -->
                <form class="p-5 space-y-4 text-xs" @submit.prevent="submit">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-gray-700 mb-1">
                                Tên Sản Phẩm <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.ten_san_pham"
                                type="text"
                                required
                                placeholder="Ví dụ: Rau Cải Xanh Hữu Cơ Đà Lạt"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                            <p v-if="form.errors.ten_san_pham" class="text-red-500 text-[11px] mt-0.5">{{ form.errors.ten_san_pham }}</p>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Danh Mục Hàng Hóa <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.danh_muc_id"
                                required
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm bg-white focus:outline-hidden focus:border-[#007bff]"
                            >
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.ten_danh_muc }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Mã Sản Phẩm / Barcode
                            </label>
                            <input
                                v-model="form.ma_va_vach"
                                type="text"
                                placeholder="Tự sinh nếu bỏ trống"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm font-mono text-[11px] focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Giá Bán Chuẩn (VNĐ) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.gia_ban"
                                type="number"
                                required
                                min="0"
                                step="1000"
                                placeholder="35000"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm font-bold text-gray-900 focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Giá Khuyến Mãi (Nếu có)
                            </label>
                            <input
                                v-model="form.gia_giam"
                                type="number"
                                min="0"
                                step="1000"
                                placeholder="29000"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Đơn Vị Tính <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.don_vi_tinh"
                                type="text"
                                required
                                placeholder="Kg, Bó, Khay..."
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Quy Cách Đóng Gói
                            </label>
                            <input
                                v-model="form.quy_cach"
                                type="text"
                                placeholder="Ví dụ: Khay 500g..."
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Hạn Sử Dụng Tiêu Chuẩn (Số Ngày)
                            </label>
                            <input
                                v-model="form.han_su_dung_ngay"
                                type="number"
                                min="1"
                                placeholder="3"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Điều Kiện Bảo Quản
                            </label>
                            <input
                                v-model="form.dieu_kien_bao_quan"
                                type="text"
                                placeholder="Nhiệt độ 2-5°C..."
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-gray-700 mb-1">
                                Link Ảnh Sản Phẩm (URL)
                            </label>
                            <input
                                v-model="form.hinh_anh_chinh"
                                type="text"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>

                        <div class="sm:col-span-2 flex items-center space-x-6 pt-1">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    v-model="form.la_tuoi_song"
                                    type="checkbox"
                                    class="rounded text-[#007bff] focus:ring-[#007bff]"
                                />
                                <span class="font-bold text-gray-800">Thực phẩm tươi sống (Ưu tiên xuất FEFO)</span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer">
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
                            <label class="block font-bold text-gray-700 mb-1">
                                Mô Tả Chi Tiết / Tiêu Chuẩn Nông Nghiệp
                            </label>
                            <textarea
                                v-model="form.mo_ta"
                                rows="3"
                                placeholder="Nhập xuất xứ, chứng chỉ VietGAP/Organic..."
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#007bff]"
                            />
                        </div>
                    </div>

                    <!-- CARD FOOTER AdminLTE -->
                    <div class="pt-4 border-t border-gray-200 flex items-center justify-end space-x-2">
                        <Link
                            :href="route('admin.products.index')"
                            class="px-3.5 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-sm font-semibold transition-colors"
                        >
                            Hủy Bỏ
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-1.5 bg-[#007bff] hover:bg-[#0069d9] text-white rounded-sm font-semibold shadow-2xs transition-colors flex items-center space-x-1"
                        >
                            <Save class="w-3.5 h-3.5" />
                            <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Sản Phẩm' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
