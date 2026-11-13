<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    Search,
    ShoppingBag,
    Eye,
    Filter,
} from 'lucide-vue-next';

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
        { preserveState: true }
    );
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}

function getStatusBadge(st: string) {
    switch (st) {
        case 'hoan_thanh':
            return { label: 'Hoàn thành', class: 'bg-[#28a745] text-white' };
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
    <AdminLayout
        title="Quản Lý Đơn Hàng"
        :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Đơn hàng' }]"
    >
        <div class="space-y-4">
            <!-- ADMINLTE FILTER CARD -->
            <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs p-3.5">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative w-full sm:w-60">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Mã đơn, người nhận, SĐT..."
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
                        <option value="">-- Tất cả trạng thái đơn --</option>
                        <option value="cho_xac_nhan">Chờ duyệt</option>
                        <option value="dang_chuan_bi">Đang đóng gói</option>
                        <option value="dang_giao">Đang giao hàng</option>
                        <option value="hoan_thanh">Đã hoàn thành</option>
                        <option value="da_huy">Đã hủy</option>
                    </select>

                    <select
                        v-model="storeId"
                        class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                        @change="handleFilter"
                    >
                        <option value="">-- Tất cả chi nhánh --</option>
                        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }}</option>
                    </select>

                    <select
                        v-model="paymentStatus"
                        class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                        @change="handleFilter"
                    >
                        <option value="">-- Trạng thái thanh toán --</option>
                        <option value="da_thanh_toan">Đã thanh toán</option>
                        <option value="chua_thanh_toan">Chưa thanh toán</option>
                    </select>

                    <button
                        class="px-3.5 py-1.5 bg-[#007bff] hover:bg-[#0069d9] text-white rounded-sm text-xs font-semibold transition-colors flex items-center space-x-1"
                        @click="handleFilter"
                    >
                        <Filter class="w-3 h-3" />
                        <span>Lọc Đơn Hàng</span>
                    </button>
                </div>
            </div>

            <!-- ORDERS TABLE CARD -->
            <div class="bg-white rounded-sm border border-gray-200 shadow-xs overflow-hidden">
                <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-xs font-bold text-[#343a40]">Danh Sách Đơn Hàng Chuỗi</span>
                    <span class="text-[11px] text-gray-500">{{ orders.data.length }} đơn hàng</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-200 text-gray-700 font-bold uppercase text-[10px]">
                                <th class="py-2.5 px-3">Mã Đơn / Thời Gian</th>
                                <th class="py-2.5 px-3">Khách Hàng / SĐT</th>
                                <th class="py-2.5 px-3">Chi Nhánh Xử Lý</th>
                                <th class="py-2.5 px-3">Tổng Thanh Toán</th>
                                <th class="py-2.5 px-3 text-center">Thanh Toán</th>
                                <th class="py-2.5 px-3 text-center">Trạng Thái Đơn</th>
                                <th class="py-2.5 px-3 text-right">Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 transition-colors">
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-[#007bff]">
                                        <Link :href="route('admin.orders.show', order.id)" class="hover:underline">
                                            {{ order.ma_don_hang }}
                                        </Link>
                                    </div>
                                    <div class="text-[10px] text-gray-400">{{ order.created_at }}</div>
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-gray-900">{{ order.ten_nguoi_nhan }}</div>
                                    <div class="text-[11px] text-gray-500">{{ order.so_dien_thoai_nhan }}</div>
                                </td>
                                <td class="py-2.5 px-3 text-gray-600">
                                    {{ order.cua_hang?.ten_cua_hang || 'N/A' }}
                                </td>
                                <td class="py-2.5 px-3 font-bold text-gray-900">
                                    {{ formatCurrency(order.tong_thanhtoan) }}
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span
                                        :class="[
                                            'inline-block px-1.5 py-0.2 rounded-2xs text-[10px] font-bold uppercase',
                                            order.trang_thai_thanh_toan === 'da_thanh_toan'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-amber-100 text-amber-800'
                                        ]"
                                    >
                                        {{ order.trang_thai_thanh_toan === 'da_thanh_toan' ? 'Đã thu tiền' : 'Chưa thu' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span
                                        :class="[
                                            'inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase shadow-2xs',
                                            getStatusBadge(order.trang_thai_don_hang).class
                                        ]"
                                    >
                                        {{ getStatusBadge(order.trang_thai_don_hang).label }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <Link
                                        :href="route('admin.orders.show', order.id)"
                                        class="inline-flex items-center space-x-1 px-2 py-1 rounded-sm bg-gray-100 hover:bg-[#007bff] hover:text-white text-gray-700 font-semibold transition-colors"
                                    >
                                        <Eye class="w-3 h-3" />
                                        <span>Xem Đơn</span>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="orders.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-gray-400 italic">
                                    Không tìm thấy đơn hàng nào.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
