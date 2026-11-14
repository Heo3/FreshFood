<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Boxes, Save, Store } from 'lucide-vue-next';

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
            { name: 'Chỉnh sửa' },
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

            <!-- ADMINLTE MAIN CARD -->
            <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white">
                <!-- CARD HEADER -->
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h3 class="flex items-center space-x-2 text-sm font-bold text-[#343a40]">
                        <Boxes class="h-4 w-4 text-[#007bff]" />
                        <span>Chỉnh Sửa Thông Tin Sản Phẩm</span>
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
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Đơn Vị Tính <span class="text-red-500">*</span> </label>
                            <input
                                v-model="form.don_vi_tinh"
                                type="text"
                                required
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Quy Cách Đóng Gói </label>
                            <input
                                v-model="form.quy_cach"
                                type="text"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Hạn Sử Dụng Tiêu Chuẩn (Số Ngày) </label>
                            <input
                                v-model="form.han_su_dung_ngay"
                                type="number"
                                min="1"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#007bff]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Điều Kiện Bảo Quản </label>
                            <input
                                v-model="form.dieu_kien_bao_quan"
                                type="text"
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
                            <label class="mb-1 block font-bold text-gray-700"> Mô Tả Chi Tiết </label>
                            <textarea
                                v-model="form.mo_ta"
                                rows="3"
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
                            Quay Lại
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="shadow-2xs flex items-center space-x-1 rounded-sm bg-[#007bff] px-4 py-1.5 font-semibold text-white transition-colors hover:bg-[#0069d9]"
                        >
                            <Save class="h-3.5 w-3.5" />
                            <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Thay Đổi' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- CARD BẢNG GIÁ CHI NHÁNH (AdminLTE card card-info card-outline) -->
            <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#17a2b8] border-gray-200 bg-white">
                <!-- CARD HEADER -->
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h3 class="flex items-center space-x-2 text-sm font-bold text-[#343a40]">
                        <Store class="h-4 w-4 text-[#17a2b8]" />
                        <span>Bảng Giá Riêng Từng Chi Nhánh</span>
                    </h3>
                </div>

                <div class="space-y-4 p-5 text-xs">
                    <!-- Danh sách giá chi nhánh đang có -->
                    <div
                        v-if="product.gia_theo_cua_hang && product.gia_theo_cua_hang.length > 0"
                        class="overflow-hidden rounded-sm border border-gray-200"
                    >
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase text-gray-600">
                                    <th class="px-3 py-2">Chi Nhánh</th>
                                    <th class="px-3 py-2">Giá Bán Áp Dụng</th>
                                    <th class="px-3 py-2">Giá Khuyến Mãi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="gp in product.gia_theo_cua_hang" :key="gp.id" class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-bold text-gray-800">{{ gp.cua_hang?.ten_cua_hang }}</td>
                                    <td class="px-3 py-2 font-bold text-[#17a2b8]">{{ formatCurrency(gp.gia_ban) }}</td>
                                    <td class="px-3 py-2 text-red-600">{{ gp.gia_giam ? formatCurrency(gp.gia_giam) : '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="rounded-sm border border-gray-200 bg-gray-50 p-2 italic text-gray-500">
                        Chưa có giá riêng chi nhánh. Hiện tại tất cả chi nhánh áp dụng giá chuẩn ({{ formatCurrency(product.gia_ban) }}).
                    </div>

                    <!-- Form thêm giá chi nhánh -->
                    <form class="space-y-3 rounded-sm border border-cyan-200 bg-cyan-50/50 p-3.5" @submit.prevent="submitBranchPrice">
                        <span class="block text-[11px] font-bold uppercase text-[#17a2b8]">Thiết Lập Giá Chi Nhánh Mới</span>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block font-semibold text-gray-700">Chi nhánh</label>
                                <select
                                    v-model="branchForm.cua_hang_id"
                                    required
                                    class="w-full rounded-sm border border-gray-300 bg-white px-2.5 py-1.5"
                                >
                                    <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold text-gray-700">Giá bán chi nhánh (VNĐ)</label>
                                <input
                                    v-model="branchForm.gia_ban"
                                    type="number"
                                    required
                                    min="0"
                                    step="1000"
                                    class="w-full rounded-sm border border-gray-300 bg-white px-2.5 py-1.5 font-bold"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold text-gray-700">Giá giảm (Nếu có)</label>
                                <input
                                    v-model="branchForm.gia_giam"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    placeholder="Không giảm"
                                    class="w-full rounded-sm border border-gray-300 bg-white px-2.5 py-1.5"
                                />
                            </div>
                        </div>
                        <div class="pt-1 text-right">
                            <button
                                type="submit"
                                :disabled="branchForm.processing"
                                class="shadow-2xs rounded-sm bg-[#17a2b8] px-3.5 py-1.5 font-semibold text-white transition-colors hover:bg-[#138496]"
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
