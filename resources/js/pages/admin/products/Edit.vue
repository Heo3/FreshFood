<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { ArrowLeft, Save, Boxes, Store, Plus } from 'lucide-vue-next';

const props = defineProps<{
    product: {
        id: number;
        ten_san_pham: string;
        ma_va_vach: string;
        duong_dan_seo: string;
        danh_muc_id: number;
        gia_ban: number;
        gia_giam: number | null;
        don_vi_tinh: string;
        quy_cach: string | null;
        dieu_kien_bao_quan: string | null;
        han_su_dung_ngay: number | null;
        hinh_anh_chinh: string | null;
        la_tuoi_song: boolean;
        trang_thai: number;
        mo_ta: string | null;
        gia_theo_cua_hang?: Array<{
            id: number;
            cua_hang_id: number;
            gia_ban: number;
            gia_giam: number | null;
            cua_hang?: {
                id: number;
                ten_cua_hang: string;
            };
        }>;
    };
    categories: Array<{ id: number; ten_danh_muc: string }>;
    stores: Array<{ id: number; ten_cua_hang: string }>;
}>();

const form = useForm({
    ten_san_pham: props.product.ten_san_pham,
    ma_va_vach: props.product.ma_va_vach,
    duong_dan_seo: props.product.duong_dan_seo,
    danh_muc_id: props.product.danh_muc_id,
    gia_ban: props.product.gia_ban,
    gia_giam: props.product.gia_giam || '',
    don_vi_tinh: props.product.don_vi_tinh,
    quy_cach: props.product.quy_cach || '',
    dieu_kien_bao_quan: props.product.dieu_kien_bao_quan || '',
    han_su_dung_ngay: props.product.han_su_dung_ngay || '',
    hinh_anh_chinh: props.product.hinh_anh_chinh || '',
    la_tuoi_song: Boolean(props.product.la_tuoi_song),
    trang_thai: props.product.trang_thai,
    mo_ta: props.product.mo_ta || '',
});

const branchForm = useForm({
    cua_hang_id: props.stores[0]?.id || '',
    gia_ban: props.product.gia_ban,
    gia_giam: props.product.gia_giam || '',
});

function submit() {
    form.put(route('admin.products.update', props.product.id));
}

function submitBranchPrice() {
    branchForm.post(route('admin.products.branch-price', props.product.id), {
        preserveScroll: true,
    });
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}
</script>

<template>
    <AdminLayout
        :title="`Sửa Sản Phẩm: ${product.ten_san_pham}`"
        :breadcrumbs="[
            { name: 'Admin', href: route('admin.dashboard') },
            { name: 'Sản phẩm', href: route('admin.products.index') },
            { name: 'Chỉnh sửa' }
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

            <!-- ADMINLTE MAIN CARD -->
            <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs">
                <!-- CARD HEADER -->
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-[#343a40] flex items-center space-x-2">
                        <Boxes class="w-4 h-4 text-[#007bff]" />
                        <span>Chỉnh Sửa Thông Tin Sản Phẩm</span>
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
                                Mô Tả Chi Tiết
                            </label>
                            <textarea
                                v-model="form.mo_ta"
                                rows="3"
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
                            Quay Lại
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-1.5 bg-[#007bff] hover:bg-[#0069d9] text-white rounded-sm font-semibold shadow-2xs transition-colors flex items-center space-x-1"
                        >
                            <Save class="w-3.5 h-3.5" />
                            <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Thay Đổi' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- CARD BẢNG GIÁ CHI NHÁNH (AdminLTE card card-info card-outline) -->
            <div class="bg-white rounded-sm border-t-3 border-[#17a2b8] border-x border-b border-gray-200 shadow-xs">
                <!-- CARD HEADER -->
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-[#343a40] flex items-center space-x-2">
                        <Store class="w-4 h-4 text-[#17a2b8]" />
                        <span>Bảng Giá Riêng Từng Chi Nhánh</span>
                    </h3>
                </div>

                <div class="p-5 space-y-4 text-xs">
                    <!-- Danh sách giá chi nhánh đang có -->
                    <div v-if="product.gia_theo_cua_hang && product.gia_theo_cua_hang.length > 0" class="border border-gray-200 rounded-sm overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase text-[10px]">
                                    <th class="py-2 px-3">Chi Nhánh</th>
                                    <th class="py-2 px-3">Giá Bán Áp Dụng</th>
                                    <th class="py-2 px-3">Giá Khuyến Mãi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="gp in product.gia_theo_cua_hang" :key="gp.id" class="hover:bg-gray-50">
                                    <td class="py-2 px-3 font-bold text-gray-800">{{ gp.cua_hang?.ten_cua_hang }}</td>
                                    <td class="py-2 px-3 font-bold text-[#17a2b8]">{{ formatCurrency(gp.gia_ban) }}</td>
                                    <td class="py-2 px-3 text-red-600">{{ gp.gia_giam ? formatCurrency(gp.gia_giam) : '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-gray-500 italic p-2 bg-gray-50 border border-gray-200 rounded-sm">
                        Chưa có giá riêng chi nhánh. Hiện tại tất cả chi nhánh áp dụng giá chuẩn ({{ formatCurrency(product.gia_ban) }}).
                    </div>

                    <!-- Form thêm giá chi nhánh -->
                    <form class="p-3.5 bg-cyan-50/50 border border-cyan-200 rounded-sm space-y-3" @submit.prevent="submitBranchPrice">
                        <span class="font-bold text-[#17a2b8] block uppercase text-[11px]">Thiết Lập Giá Chi Nhánh Mới</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Chi nhánh</label>
                                <select
                                    v-model="branchForm.cua_hang_id"
                                    required
                                    class="w-full px-2.5 py-1.5 bg-white border border-gray-300 rounded-sm"
                                >
                                    <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Giá bán chi nhánh (VNĐ)</label>
                                <input
                                    v-model="branchForm.gia_ban"
                                    type="number"
                                    required
                                    min="0"
                                    step="1000"
                                    class="w-full px-2.5 py-1.5 bg-white border border-gray-300 rounded-sm font-bold"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Giá giảm (Nếu có)</label>
                                <input
                                    v-model="branchForm.gia_giam"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    placeholder="Không giảm"
                                    class="w-full px-2.5 py-1.5 bg-white border border-gray-300 rounded-sm"
                                />
                            </div>
                        </div>
                        <div class="text-right pt-1">
                            <button
                                type="submit"
                                :disabled="branchForm.processing"
                                class="px-3.5 py-1.5 bg-[#17a2b8] hover:bg-[#138496] text-white rounded-sm font-semibold shadow-2xs transition-colors"
                            >
                                {{ branchForm.processing ? 'Đang lưu...' : 'Lưu Giá Chi Nhánh' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
