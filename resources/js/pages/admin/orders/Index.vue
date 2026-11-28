<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { Eye, FileDown, Filter, Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    orders: {
        data: Array<{
            id: number;
            ma_don_hang: string;
            ten_nguoi_nhan: string;
            so_dien_thoai_nhan: string;
            tong_tien_hang: number;
            phi_giao_hang: number;
            tien_giam_gia: number;
            tong_thanhtoan: number;
            phuong_thuc_thanh_toan: string;
            trang_thai_thanh_toan: string;
            trang_thai_don_hang: string;
            created_at: string;
            cua_hang?: {
                id: number;
                ten_cua_hang: string;
            };
            nhan_vien_len_don?: {
                id: number;
                name: string;
                phone?: string;
                role?: string;
            };
            nguoi_giao_hang?: {
                id: number;
                name: string;
                phone?: string;
            };
            chi_tiet_don_hangs?: Array<any>;
        }>;
        links: Array<any>;
    };
    stores: Array<{ id: number; ten_cua_hang: string }>;
    filters: {
        search?: string;
        trang_thai_don_hang?: string;
        cua_hang_id?: string;
        trang_thai_thanh_toan?: string;
    };
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.trang_thai_don_hang || '');
const storeId = ref(props.filters.cua_hang_id || '');
const paymentStatus = ref(props.filters.trang_thai_thanh_toan || '');

function handleFilter() {
    router.get(
        route('admin.orders.index'),
        {
            search: search.value,
            trang_thai_don_hang: status.value,
            cua_hang_id: storeId.value,
            trang_thai_thanh_toan: paymentStatus.value,
        },
        { preserveState: true },
    );
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}

function getStatusBadge(st: string) {
    switch (st) {
        case 'hoan_thanh':
            return { label: 'Hoàn thành', class: 'bg-[#28a745] text-white' };
        case 'da_giao':
            return { label: 'Đã giao hàng', class: 'bg-[#17a2b8] text-white' };
        case 'dang_giao':
            return { label: 'Đang giao hàng', class: 'bg-[#007bff] text-white' };
        case 'dang_chuan_bi':
            return { label: 'Đang đóng gói', class: 'bg-[#ffc107] text-[#212529]' };
        case 'cho_xac_nhan':
            return { label: 'Chờ duyệt', class: 'bg-[#6f42c1] text-white' };
        case 'da_huy':
            return { label: 'Đã hủy', class: 'bg-[#dc3545] text-white' };
        default:
            return { label: st, class: 'bg-[#6c757d] text-white' };
    }
}
</script>

<template>
    <AdminLayout title="Quản Lý Đơn Hàng" :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Đơn hàng' }]">
        <div class="space-y-4">
            <!-- ADMINLTE FILTER CARD -->
            <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white p-3.5">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative w-full sm:w-60">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Mã đơn, người nhận, SĐT..."
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
                        <option value="">-- Tất cả trạng thái đơn --</option>
                        <option value="cho_xac_nhan">Chờ duyệt</option>
                        <option value="dang_chuan_bi">Đang đóng gói</option>
                        <option value="dang_giao">Đang giao hàng</option>
                        <option value="da_giao">Đã giao hàng</option>
                        <option value="hoan_thanh">Đã hoàn thành</option>
                        <option value="da_huy">Đã hủy</option>
                    </select>

                    <select
                        v-model="storeId"
                        class="focus:outline-hidden rounded-sm border border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs focus:border-[#007bff]"
                        @change="handleFilter"
                    >
                        <option value="">-- Tất cả chi nhánh --</option>
                        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }}</option>
                    </select>

                    <select
                        v-model="paymentStatus"
                        class="focus:outline-hidden rounded-sm border border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs focus:border-[#007bff]"
                        @change="handleFilter"
                    >
                        <option value="">-- Trạng thái thanh toán --</option>
                        <option value="da_thanh_toan">Đã thanh toán</option>
                        <option value="chua_thanh_toan">Chưa thanh toán</option>
                    </select>

                    <button
                        class="flex items-center space-x-1 rounded-sm bg-[#007bff] px-3.5 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#0069d9]"
                        @click="handleFilter"
                    >
                        <Filter class="h-3 w-3" />
                        <span>Lọc Đơn Hàng</span>
                    </button>

                    <a
                        :href="route('admin.orders.export', { search, trang_thai_don_hang: status, cua_hang_id: storeId, trang_thai_thanh_toan: paymentStatus })"
                        class="flex items-center space-x-1 rounded-sm bg-[#28a745] px-3.5 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#218838]"
                        title="Xuất danh sách đơn hàng ra file Excel / CSV"
                    >
                        <FileDown class="h-3 w-3" />
                        <span>Xuất Excel / CSV</span>
                    </a>
                </div>
            </div>

            <!-- ORDERS TABLE CARD -->
            <div class="shadow-xs overflow-hidden rounded-sm border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2.5">
                    <span class="text-xs font-bold text-[#343a40]">Danh Sách Đơn Hàng Chuỗi</span>
                    <span class="text-[11px] text-gray-500">{{ orders.data.length }} đơn hàng</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-100 text-[10px] font-bold uppercase text-gray-700">
                                <th class="px-3 py-2.5">Mã Đơn / Thời Gian</th>
                                <th class="px-3 py-2.5">Khách Hàng / SĐT</th>
                                <th class="px-3 py-2.5">Chi Nhánh Xử Lý</th>
                                <th class="px-3 py-2.5">Người Chuẩn Bị & Giao</th>
                                <th class="px-3 py-2.5">Tổng Thanh Toán</th>
                                <th class="px-3 py-2.5 text-center">Thanh Toán</th>
                                <th class="px-3 py-2.5 text-center">Trạng Thái Đơn</th>
                                <th class="px-3 py-2.5 text-right">Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="order in orders.data" :key="order.id" class="transition-colors hover:bg-gray-50">
                                <td class="px-3 py-2.5">
                                    <div class="font-bold text-[#007bff]">
                                        <Link :href="route('admin.orders.show', order.id)" class="hover:underline">
                                            {{ order.ma_don_hang }}
                                        </Link>
                                    </div>
                                    <div class="text-[10px] text-gray-400">{{ order.created_at }}</div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="font-bold text-gray-900">{{ order.ten_nguoi_nhan }}</div>
                                    <div class="text-[11px] text-gray-500">{{ order.so_dien_thoai_nhan }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-gray-600">
                                    {{ order.cua_hang?.ten_cua_hang || 'N/A' }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="text-[11px] leading-tight">
                                        <span class="text-gray-400">Chuẩn bị: </span>
                                        <span :class="order.nhan_vien_len_don ? 'font-semibold text-blue-700' : 'text-amber-600 italic'">
                                            {{ order.nhan_vien_len_don?.name || 'Chờ chuẩn bị' }}
                                        </span>
                                    </div>
                                    <div class="mt-0.5 text-[11px] leading-tight">
                                        <span class="text-gray-400">Giao hàng: </span>
                                        <span :class="order.nguoi_giao_hang ? 'font-semibold text-emerald-700' : 'text-gray-400 italic'">
                                            {{ order.nguoi_giao_hang?.name ? (order.nguoi_giao_hang.name + (order.nguoi_giao_hang.phone ? ` (${order.nguoi_giao_hang.phone})` : '')) : 'Chưa phân công' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 font-bold text-gray-900">
                                    {{ formatCurrency(order.tong_thanhtoan) }}
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <span
                                        :class="[
                                            'py-0.2 rounded-2xs inline-block px-1.5 text-[10px] font-bold uppercase',
                                            order.trang_thai_thanh_toan === 'da_thanh_toan'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-amber-100 text-amber-800',
                                        ]"
                                    >
                                        {{ order.trang_thai_thanh_toan === 'da_thanh_toan' ? 'Đã thu tiền' : 'Chưa thu' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <span
                                        :class="[
                                            'shadow-2xs inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase',
                                            getStatusBadge(order.trang_thai_don_hang).class,
                                        ]"
                                    >
                                        {{ getStatusBadge(order.trang_thai_don_hang).label }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <Link
                                        :href="route('admin.orders.show', order.id)"
                                        class="inline-flex items-center space-x-1 rounded-sm bg-gray-100 px-2 py-1 font-semibold text-gray-700 transition-colors hover:bg-[#007bff] hover:text-white"
                                    >
                                        <Eye class="h-3 w-3" />
                                        <span>Xem Đơn</span>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="orders.data.length === 0">
                                <td colspan="8" class="py-8 text-center italic text-gray-400">Không tìm thấy đơn hàng nào.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
