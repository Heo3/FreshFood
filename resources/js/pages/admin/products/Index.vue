<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    Plus,
    Search,
    Edit3,
    Trash2,
    Boxes,
    BadgePercent,
    SlidersHorizontal,
    X,
    Store,
    Save,
    Filter,
} from 'lucide-vue-next';

const props = defineProps<{
    products: {
        data: Array<{
            id: number;
            ma_va_vach: string;
            ten_san_pham: string;
            gia_ban: number;
            gia_giam: number | null;
            don_vi_tinh: string;
            hinh_anh_chinh: string | null;
            la_tuoi_song: boolean;
            trang_thai: number;
            danh_muc?: {
                id: number;
                ten_danh_muc: string;
            } | null;
            gia_theo_cua_hang?: Array<{
                id: number;
                cua_hang_id: number;
                gia_ban: number;
                gia_giam: number | null;
                cua_hang?: {
                    ten_cua_hang: string;
                };
            }>;
            ton_khos_sum_so_luong_ton: number | null;
        }>;
        links: Array<any>;
    };
    categories: Array<{ id: number; ten_danh_muc: string }>;
    stores: Array<{ id: number; ten_cua_hang: string }>;
    filters: {
        search?: string;
        danh_muc_id?: string;
        la_tuoi_song?: string;
        trang_thai?: string;
    };
}>();

const search = ref(props.filters.search || '');
const categoryId = ref(props.filters.danh_muc_id || '');
const isFresh = ref(props.filters.la_tuoi_song || '');
const status = ref(props.filters.trang_thai || '');

// Modal Cấu hình Giá Chi Nhánh
const showBranchPriceModal = ref(false);
const activeProductForPricing = ref<any | null>(null);

const priceForm = useForm({
    cua_hang_id: '',
    gia_ban: '',
    gia_giam: '',
});

function openBranchPriceModal(prod: any) {
    activeProductForPricing.value = prod;
    priceForm.cua_hang_id = props.stores[0]?.id?.toString() || '';
    priceForm.gia_ban = prod.gia_ban;
    priceForm.gia_giam = prod.gia_giam || '';
    showBranchPriceModal.value = true;
}

function submitBranchPrice() {
    if (!activeProductForPricing.value) return;
    priceForm.post(route('admin.products.branch-price', activeProductForPricing.value.id), {
        onSuccess: () => {
            showBranchPriceModal.value = false;
        },
    });
}

function handleFilter() {
    router.get(
        route('admin.products.index'),
        {
            search: search.value,
            danh_muc_id: categoryId.value,
            la_tuoi_song: isFresh.value,
            trang_thai: status.value,
        },
        { preserveState: true }
    );
}

function deleteProduct(prod: any) {
    if (confirm(`Bạn có chắc chắn muốn xóa sản phẩm "${prod.ten_san_pham}"?`)) {
        router.delete(route('admin.products.destroy', prod.id));
    }
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}
</script>

<template>
    <AdminLayout
        title="Quản Lý Sản Phẩm"
        :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Sản phẩm' }]"
    >
        <div class="space-y-4">
            <!-- ADMINLTE FILTER TOOLBAR CARD -->
            <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs p-3.5">
                <div class="flex flex-wrap items-center justify-between gap-2.5">
                    <!-- Filters -->
                    <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[280px]">
                        <div class="relative w-full sm:w-60">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Mã SP hoặc tên..."
                                class="w-full pl-3 pr-8 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                                @keyup.enter="handleFilter"
                            />
                            <Search
                                class="w-3.5 h-3.5 text-gray-400 absolute right-2.5 top-2.5 cursor-pointer"
                                @click="handleFilter"
                            />
                        </div>

                        <select
                            v-model="categoryId"
                            class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                            @change="handleFilter"
                        >
                            <option value="">-- Tất cả danh mục --</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.ten_danh_muc }}</option>
                        </select>

                        <select
                            v-model="isFresh"
                            class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                            @change="handleFilter"
                        >
                            <option value="">-- Loại sản phẩm --</option>
                            <option value="1">🌱 Hàng tươi sống (FEFO)</option>
                            <option value="0">📦 Hàng khô / Đóng gói</option>
                        </select>

                        <button
                            class="px-3 py-1.5 bg-[#6c757d] hover:bg-[#5a6268] text-white rounded-sm text-xs font-semibold transition-colors flex items-center space-x-1"
                            @click="handleFilter"
                        >
                            <Filter class="w-3 h-3" />
                            <span>Lọc Dữ Liệu</span>
                        </button>
                    </div>

                    <!-- Add Button AdminLTE style (btn-success) -->
                    <Link
                        :href="route('admin.products.create')"
                        class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 bg-[#28a745] hover:bg-[#218838] text-white rounded-sm text-xs font-semibold shadow-2xs transition-colors shrink-0"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>Thêm Sản Phẩm Mới</span>
                    </Link>
                </div>
            </div>

            <!-- TABLE CARD (AdminLTE table-striped table-hover) -->
            <div class="bg-white rounded-sm border border-gray-200 shadow-xs overflow-hidden">
                <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-xs font-bold text-[#343a40]">Danh Sách Sản Phẩm Hệ Thống</span>
                    <span class="text-[11px] text-gray-500">Hiển thị {{ products.data.length }} kết quả</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-200 text-gray-700 font-bold uppercase text-[10px]">
                                <th class="py-2.5 px-3">Sản Phẩm</th>
                                <th class="py-2.5 px-3">Danh Mục</th>
                                <th class="py-2.5 px-3">Giá Niêm Yết</th>
                                <th class="py-2.5 px-3 text-center">Tồn Kho Toàn Chuỗi</th>
                                <th class="py-2.5 px-3 text-center">Bảng Giá Chi Nhánh</th>
                                <th class="py-2.5 px-3 text-center">Trạng Thái</th>
                                <th class="py-2.5 px-3 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="prod in products.data" :key="prod.id" class="hover:bg-gray-50 transition-colors">
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-10 h-10 rounded-sm bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0 overflow-hidden">
                                            <img
                                                v-if="prod.hinh_anh_chinh"
                                                :src="prod.hinh_anh_chinh"
                                                alt="Thumb"
                                                class="w-full h-full object-cover"
                                            />
                                            <Boxes v-else class="w-4 h-4 text-gray-400" />
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900">{{ prod.ten_san_pham }}</div>
                                            <div class="flex items-center space-x-1.5 mt-0.5">
                                                <span class="font-mono text-[10px] text-gray-400">{{ prod.ma_va_vach }}</span>
                                                <span
                                                    v-if="prod.la_tuoi_song"
                                                    class="inline-block px-1 py-0.2 rounded-2xs text-[9px] font-bold bg-green-100 text-green-800"
                                                >
                                                    Tươi sống
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-gray-600">
                                    {{ prod.danh_muc?.ten_danh_muc || 'N/A' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-gray-900">
                                        {{ formatCurrency(prod.gia_ban) }}
                                        <span class="text-[10px] font-normal text-gray-500">/ {{ prod.don_vi_tinh }}</span>
                                    </div>
                                    <span v-if="prod.gia_giam" class="text-[11px] text-red-600 font-semibold block">
                                        Giảm: {{ formatCurrency(prod.gia_giam) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span
                                        :class="[
                                            'inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold',
                                            (prod.ton_khos_sum_so_luong_ton || 0) > 10
                                                ? 'bg-green-100 text-green-800'
                                                : (prod.ton_khos_sum_so_luong_ton || 0) > 0
                                                    ? 'bg-amber-100 text-amber-800'
                                                    : 'bg-red-100 text-red-800'
                                        ]"
                                    >
                                        {{ Number(prod.ton_khos_sum_so_luong_ton || 0).toFixed(1) }} {{ prod.don_vi_tinh }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <button
                                        class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-sm text-[10px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-300 hover:bg-cyan-100 transition-colors"
                                        @click="openBranchPriceModal(prod)"
                                    >
                                        <Store class="w-3 h-3" />
                                        <span>{{ prod.gia_theo_cua_hang?.length || 0 }} chi nhánh</span>
                                    </button>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span
                                        :class="[
                                            'inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold',
                                            prod.trang_thai === 1 ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600'
                                        ]"
                                    >
                                        {{ prod.trang_thai === 1 ? 'Đang bán' : 'Tạm dừng' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right space-x-1">
                                    <Link
                                        :href="route('admin.products.edit', prod.id)"
                                        class="px-2 py-1 bg-gray-100 hover:bg-[#007bff] hover:text-white text-gray-700 rounded-sm font-semibold transition-colors inline-block"
                                        title="Chỉnh sửa chi tiết"
                                    >
                                        <Edit3 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Sửa</span>
                                    </Link>
                                    <button
                                        class="px-2 py-1 bg-red-50 hover:bg-[#dc3545] hover:text-white text-red-600 rounded-sm font-semibold transition-colors"
                                        title="Xóa sản phẩm"
                                        @click="deleteProduct(prod)"
                                    >
                                        <Trash2 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Xóa</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-gray-400 italic">
                                    Không có sản phẩm nào phù hợp bộ lọc.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL CẤU HÌNH GIÁ CHI NHÁNH (AdminLTE modal-dialog) -->
            <div
                v-if="showBranchPriceModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            >
                <div class="bg-white rounded-sm max-w-md w-full shadow-xl border border-gray-300 overflow-hidden text-xs">
                    <div class="px-4 py-3 bg-[#17a2b8] text-white flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-sm">Cấu Hình Giá Chi Nhánh</h4>
                            <p class="text-[11px] text-cyan-100 truncate">{{ activeProductForPricing?.ten_san_pham }}</p>
                        </div>
                        <button class="text-white hover:text-gray-200 p-1" @click="showBranchPriceModal = false">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Bảng giá hiện hữu -->
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="font-bold text-gray-700 block mb-1">Giá Hệ Thống:</span>
                        <div class="flex justify-between text-gray-600 text-[11px]">
                            <span>Giá Niêm Yết Mặc Định:</span>
                            <span class="font-bold text-gray-900">{{ formatCurrency(activeProductForPricing?.gia_ban) }}</span>
                        </div>
                        <div
                            v-for="gp in activeProductForPricing?.gia_theo_cua_hang"
                            :key="gp.id"
                            class="flex justify-between text-[#007bff] text-[11px] mt-1"
                        >
                            <span>{{ gp.cua_hang?.ten_cua_hang }}:</span>
                            <span class="font-bold">{{ formatCurrency(gp.gia_ban) }}</span>
                        </div>
                    </div>

                    <form class="p-4 space-y-3" @submit.prevent="submitBranchPrice">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Chọn Chi Nhánh Cửa Hàng <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="priceForm.cua_hang_id"
                                required
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm bg-white focus:outline-hidden focus:border-[#17a2b8]"
                            >
                                <option v-for="st in stores" :key="st.id" :value="st.id">{{ st.ten_cua_hang }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Giá Bán Tại Chi Nhánh (VNĐ) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="priceForm.gia_ban"
                                type="number"
                                required
                                min="0"
                                step="1000"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm font-bold text-gray-900 focus:outline-hidden focus:border-[#17a2b8]"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Giá Khuyến Mãi (Nếu có)
                            </label>
                            <input
                                v-model="priceForm.gia_giam"
                                type="number"
                                min="0"
                                step="1000"
                                placeholder="Bỏ trống nếu không giảm giá"
                                class="w-full px-3 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#17a2b8]"
                            />
                        </div>

                        <div class="pt-3 border-t border-gray-200 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-sm font-semibold transition-colors"
                                @click="showBranchPriceModal = false"
                            >
                                Đóng
                            </button>
                            <button
                                type="submit"
                                :disabled="priceForm.processing"
                                class="px-4 py-1.5 bg-[#17a2b8] hover:bg-[#138496] text-white rounded-sm font-semibold shadow-2xs transition-colors flex items-center space-x-1"
                            >
                                <Save class="w-3.5 h-3.5" />
                                <span>{{ priceForm.processing ? 'Đang lưu...' : 'Lưu Giá Chi Nhánh' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
