<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Boxes, Edit3, Filter, Plus, Save, Search, Store, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

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
        { preserveState: true },
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
    <AdminLayout title="Quản Lý Sản Phẩm" :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Sản phẩm' }]">
        <div class="space-y-4">
            <!-- ADMINLTE FILTER TOOLBAR CARD -->
            <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white p-3.5">
                <div class="flex flex-wrap items-center justify-between gap-2.5">
                    <!-- Filters -->
                    <div class="flex min-w-[280px] flex-1 flex-wrap items-center gap-2">
                        <div class="relative w-full sm:w-60">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Mã SP hoặc tên..."
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-gray-50 py-1.5 pl-3 pr-8 text-xs focus:border-[#007bff]"
                                @keyup.enter="handleFilter"
                            />
                            <Search class="absolute right-2.5 top-2.5 h-3.5 w-3.5 cursor-pointer text-gray-400" @click="handleFilter" />
                        </div>

                        <select
                            v-model="categoryId"
                            class="focus:outline-hidden rounded-sm border border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs focus:border-[#007bff]"
                            @change="handleFilter"
                        >
                            <option value="">-- Tất cả danh mục --</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.ten_danh_muc }}</option>
                        </select>

                        <select
                            v-model="isFresh"
                            class="focus:outline-hidden rounded-sm border border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs focus:border-[#007bff]"
                            @change="handleFilter"
                        >
                            <option value="">-- Loại sản phẩm --</option>
                            <option value="1">🌱 Hàng tươi sống (FEFO)</option>
                            <option value="0">📦 Hàng khô / Đóng gói</option>
                        </select>

                        <button
                            class="flex items-center space-x-1 rounded-sm bg-[#6c757d] px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#5a6268]"
                            @click="handleFilter"
                        >
                            <Filter class="h-3 w-3" />
                            <span>Lọc Dữ Liệu</span>
                        </button>
                    </div>

                    <!-- Add Button AdminLTE style (btn-success) -->
                    <Link
                        :href="route('admin.products.create')"
                        class="shadow-2xs inline-flex shrink-0 items-center space-x-1.5 rounded-sm bg-[#28a745] px-3.5 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#218838]"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        <span>Thêm Sản Phẩm Mới</span>
                    </Link>
                </div>
            </div>

            <!-- TABLE CARD (AdminLTE table-striped table-hover) -->
            <div class="shadow-xs overflow-hidden rounded-sm border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2.5">
                    <span class="text-xs font-bold text-[#343a40]">Danh Sách Sản Phẩm Hệ Thống</span>
                    <span class="text-[11px] text-gray-500">Hiển thị {{ products.data.length }} kết quả</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-100 text-[10px] font-bold uppercase text-gray-700">
                                <th class="px-3 py-2.5">Sản Phẩm</th>
                                <th class="px-3 py-2.5">Danh Mục</th>
                                <th class="px-3 py-2.5">Giá Niêm Yết</th>
                                <th class="px-3 py-2.5 text-center">Tồn Kho Toàn Chuỗi</th>
                                <th class="px-3 py-2.5 text-center">Bảng Giá Chi Nhánh</th>
                                <th class="px-3 py-2.5 text-center">Trạng Thái</th>
                                <th class="px-3 py-2.5 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="prod in products.data" :key="prod.id" class="transition-colors hover:bg-gray-50">
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center space-x-2.5">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-sm border border-gray-200 bg-gray-100"
                                        >
                                            <img
                                                v-if="prod.hinh_anh_chinh"
                                                :src="prod.hinh_anh_chinh"
                                                alt="Thumb"
                                                class="h-full w-full object-cover"
                                            />
                                            <Boxes v-else class="h-4 w-4 text-gray-400" />
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900">{{ prod.ten_san_pham }}</div>
                                            <div class="mt-0.5 flex items-center space-x-1.5">
                                                <span class="font-mono text-[10px] text-gray-400">{{ prod.ma_va_vach }}</span>
                                                <span
                                                    v-if="prod.la_tuoi_song"
                                                    class="py-0.2 rounded-2xs inline-block bg-green-100 px-1 text-[9px] font-bold text-green-800"
                                                >
                                                    Tươi sống
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 text-gray-600">
                                    {{ prod.danh_muc?.ten_danh_muc || 'N/A' }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="font-bold text-gray-900">
                                        {{ formatCurrency(prod.gia_ban) }}
                                        <span class="text-[10px] font-normal text-gray-500">/ {{ prod.don_vi_tinh }}</span>
                                    </div>
                                    <span v-if="prod.gia_giam" class="block text-[11px] font-semibold text-red-600">
                                        Giảm: {{ formatCurrency(prod.gia_giam) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <span
                                        :class="[
                                            'inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold',
                                            (prod.ton_khos_sum_so_luong_ton || 0) > 10
                                                ? 'bg-green-100 text-green-800'
                                                : (prod.ton_khos_sum_so_luong_ton || 0) > 0
                                                  ? 'bg-amber-100 text-amber-800'
                                                  : 'bg-red-100 text-red-800',
                                        ]"
                                    >
                                        {{ Number(prod.ton_khos_sum_so_luong_ton || 0).toFixed(1) }} {{ prod.don_vi_tinh }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <button
                                        class="inline-flex items-center space-x-1 rounded-sm border border-cyan-300 bg-cyan-50 px-2 py-0.5 text-[10px] font-semibold text-cyan-700 transition-colors hover:bg-cyan-100"
                                        @click="openBranchPriceModal(prod)"
                                    >
                                        <Store class="h-3 w-3" />
                                        <span>{{ prod.gia_theo_cua_hang?.length || 0 }} chi nhánh</span>
                                    </button>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <span
                                        :class="[
                                            'inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold',
                                            prod.trang_thai === 1 ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600',
                                        ]"
                                    >
                                        {{ prod.trang_thai === 1 ? 'Đang bán' : 'Tạm dừng' }}
                                    </span>
                                </td>
                                <td class="space-x-1 px-3 py-2.5 text-right">
                                    <Link
                                        :href="route('admin.products.edit', prod.id)"
                                        class="inline-block rounded-sm bg-gray-100 px-2 py-1 font-semibold text-gray-700 transition-colors hover:bg-[#007bff] hover:text-white"
                                        title="Chỉnh sửa chi tiết"
                                    >
                                        <Edit3 class="mr-1 inline h-3.5 w-3.5" />
                                        <span>Sửa</span>
                                    </Link>
                                    <button
                                        class="rounded-sm bg-red-50 px-2 py-1 font-semibold text-red-600 transition-colors hover:bg-[#dc3545] hover:text-white"
                                        title="Xóa sản phẩm"
                                        @click="deleteProduct(prod)"
                                    >
                                        <Trash2 class="mr-1 inline h-3.5 w-3.5" />
                                        <span>Xóa</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="7" class="py-8 text-center italic text-gray-400">Không có sản phẩm nào phù hợp bộ lọc.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL CẤU HÌNH GIÁ CHI NHÁNH (AdminLTE modal-dialog) -->
            <div v-if="showBranchPriceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md overflow-hidden rounded-sm border border-gray-300 bg-white text-xs shadow-xl">
                    <div class="flex items-center justify-between bg-[#17a2b8] px-4 py-3 text-white">
                        <div>
                            <h4 class="text-sm font-bold">Cấu Hình Giá Chi Nhánh</h4>
                            <p class="truncate text-[11px] text-cyan-100">{{ activeProductForPricing?.ten_san_pham }}</p>
                        </div>
                        <button class="p-1 text-white hover:text-gray-200" @click="showBranchPriceModal = false">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Bảng giá hiện hữu -->
                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-2.5">
                        <span class="mb-1 block font-bold text-gray-700">Giá Hệ Thống:</span>
                        <div class="flex justify-between text-[11px] text-gray-600">
                            <span>Giá Niêm Yết Mặc Định:</span>
                            <span class="font-bold text-gray-900">{{ formatCurrency(activeProductForPricing?.gia_ban) }}</span>
                        </div>
                        <div
                            v-for="gp in activeProductForPricing?.gia_theo_cua_hang"
                            :key="gp.id"
                            class="mt-1 flex justify-between text-[11px] text-[#007bff]"
                        >
                            <span>{{ gp.cua_hang?.ten_cua_hang }}:</span>
                            <span class="font-bold">{{ formatCurrency(gp.gia_ban) }}</span>
                        </div>
                    </div>

                    <form class="space-y-3 p-4" @submit.prevent="submitBranchPrice">
                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Chọn Chi Nhánh Cửa Hàng <span class="text-red-500">*</span> </label>
                            <select
                                v-model="priceForm.cua_hang_id"
                                required
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-white px-3 py-1.5 focus:border-[#17a2b8]"
                            >
                                <option v-for="st in stores" :key="st.id" :value="st.id">{{ st.ten_cua_hang }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700">
                                Giá Bán Tại Chi Nhánh (VNĐ) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="priceForm.gia_ban"
                                type="number"
                                required
                                min="0"
                                step="1000"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 font-bold text-gray-900 focus:border-[#17a2b8]"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-gray-700"> Giá Khuyến Mãi (Nếu có) </label>
                            <input
                                v-model="priceForm.gia_giam"
                                type="number"
                                min="0"
                                step="1000"
                                placeholder="Bỏ trống nếu không giảm giá"
                                class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-3 py-1.5 focus:border-[#17a2b8]"
                            />
                        </div>

                        <div class="flex items-center justify-end space-x-2 border-t border-gray-200 pt-3">
                            <button
                                type="button"
                                class="rounded-sm bg-gray-200 px-3 py-1.5 font-semibold text-gray-700 transition-colors hover:bg-gray-300"
                                @click="showBranchPriceModal = false"
                            >
                                Đóng
                            </button>
                            <button
                                type="submit"
                                :disabled="priceForm.processing"
                                class="shadow-2xs flex items-center space-x-1 rounded-sm bg-[#17a2b8] px-4 py-1.5 font-semibold text-white transition-colors hover:bg-[#138496]"
                            >
                                <Save class="h-3.5 w-3.5" />
                                <span>{{ priceForm.processing ? 'Đang lưu...' : 'Lưu Giá Chi Nhánh' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
