<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    ArrowLeft,
    CheckCircle2,
    Truck,
    Clock,
    ShoppingBag,
    User,
    MapPin,
    Phone,
    CreditCard,
    Save,
    AlertCircle,
    Store,
} from 'lucide-vue-next';

const props = defineProps<{
    order: {
        id: number;
        ma_don_hang: string;
        ten_nguoi_nhan: string;
        so_dien_thoai_nhan: string;
        dia_chi_giao: string;
        ghi_chu: string | null;
        tong_tien_hang: number;
        phi_giao_hang: number;
        tien_giam_gia: number;
        tong_thanhtoan: number;
        phuong_thuc_thanh_toan: string;
        trang_thai_thanh_toan: string;
        trang_thai_don_hang: string;
        created_at: string;
        user?: {
            id: number;
            name: string;
            email: string;
            phone: string;
        };
        cua_hang?: {
            id: number;
            ten_cua_hang: string;
            dia_chi: string;
        };
        ma_giam_gia?: {
            id: number;
            ma_khuyen_mai: string;
        };
        chi_tiet_don_hangs?: Array<{
            id: number;
            ten_san_pham: string;
            don_gia: number;
            so_luong: number;
            thanh_tien: number;
            san_pham?: {
                id: number;
                don_vi_tinh: string;
                hinh_anh_chinh: string;
            };
        }>;
    };
}>();

const form = useForm({
    trang_thai_don_hang: props.order.trang_thai_don_hang === 'da_giao' ? 'hoan_thanh' : props.order.trang_thai_don_hang,
    trang_thai_thanh_toan: props.order.trang_thai_thanh_toan,
    ghi_chu: props.order.ghi_chu || '',
});

function submitStatus() {
    form.post(route('admin.orders.update-status', props.order.id));
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}
</script>

<template>
    <AdminLayout
        :title="`Chi Tiết Đơn Hàng: ${order.ma_don_hang}`"
        :breadcrumbs="[
            { name: 'Admin', href: route('admin.dashboard') },
            { name: 'Đơn hàng', href: route('admin.orders.index') },
            { name: order.ma_don_hang }
        ]"
    >
        <div class="max-w-5xl mx-auto space-y-4">
            <!-- BACK BUTTON -->
            <div>
                <Link
                    :href="route('admin.orders.index')"
                    class="inline-flex items-center space-x-1.5 text-xs font-semibold text-gray-600 hover:text-[#007bff]"
                >
                    <ArrowLeft class="w-3.5 h-3.5" />
                    <span>Quay lại danh sách đơn hàng</span>
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- LEFT COLUMN: ITEMS & FINANCIALS -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- ITEMS TABLE CARD -->
                    <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs">
                        <!-- Card Header -->
                        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-[#343a40] flex items-center space-x-2">
                                <ShoppingBag class="w-4 h-4 text-[#007bff]" />
                                <span>Danh Sách Mặt Hàng Đặt Mua</span>
                            </h3>
                            <span class="text-xs font-mono text-gray-400">#{{ order.ma_don_hang }}</span>
                        </div>

                        <!-- Card Body -->
                        <div class="p-0">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase text-[10px]">
                                        <th class="py-2 px-4">Sản Phẩm</th>
                                        <th class="py-2 px-3 text-center">Đơn Giá</th>
                                        <th class="py-2 px-3 text-center">Số Lượng</th>
                                        <th class="py-2 px-4 text-right">Thành Tiền</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="item in order.chi_tiet_don_hangs" :key="item.id" class="hover:bg-gray-50">
                                        <td class="py-2.5 px-4 font-bold text-gray-800">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-8 h-8 rounded-sm bg-gray-100 border border-gray-200 overflow-hidden shrink-0">
                                                    <img
                                                        v-if="item.san_pham?.hinh_anh_chinh"
                                                        :src="item.san_pham.hinh_anh_chinh"
                                                        alt="Thumb"
                                                        class="w-full h-full object-cover"
                                                    />
                                                </div>
                                                <span class="truncate">{{ item.ten_san_pham }}</span>
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-3 text-center text-gray-600">
                                            {{ formatCurrency(item.don_gia) }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-bold text-gray-800">
                                            {{ Number(item.so_luong).toFixed(1) }} {{ item.san_pham?.don_vi_tinh || 'Kg' }}
                                        </td>
                                        <td class="py-2.5 px-4 text-right font-extrabold text-gray-900">
                                            {{ formatCurrency(item.thanh_tien) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- FINANCIAL BREAKDOWN -->
                            <div class="p-4 bg-gray-50 border-t border-gray-200 space-y-1.5 text-xs text-gray-600">
                                <div class="flex justify-between">
                                    <span>Tổng tiền hàng:</span>
                                    <span class="font-bold text-gray-800">{{ formatCurrency(order.tong_tien_hang) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Phí giao hàng:</span>
                                    <span>{{ formatCurrency(order.phi_giao_hang) }}</span>
                                </div>
                                <div v-if="order.tien_giam_gia > 0" class="flex justify-between text-red-600">
                                    <span>Giảm giá voucher ({{ order.ma_giam_gia?.ma_khuyen_mai }}):</span>
                                    <span>- {{ formatCurrency(order.tien_giam_gia) }}</span>
                                </div>
                                <div class="flex justify-between text-sm font-extrabold text-[#28a745] pt-2 border-t border-gray-200">
                                    <span>Tổng cộng thanh toán:</span>
                                    <span>{{ formatCurrency(order.tong_thanhtoan) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CUSTOMER & DELIVERY INFO CARD -->
                    <div class="bg-white rounded-sm border border-gray-200 shadow-xs p-4 text-xs space-y-3">
                        <span class="font-bold text-sm text-[#343a40] block border-b border-gray-100 pb-2 flex items-center space-x-2">
                            <User class="w-4 h-4 text-[#007bff]" />
                            <span>Thông Tin Người Nhận & Giao Hàng</span>
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <span class="text-gray-400 block text-[11px]">Người nhận hàng:</span>
                                <b class="text-gray-900">{{ order.ten_nguoi_nhan }}</b>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[11px]">Số điện thoại:</span>
                                <b class="text-gray-900 font-mono">{{ order.so_dien_thoai_nhan }}</b>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="text-gray-400 block text-[11px]">Địa chỉ giao:</span>
                                <span class="text-gray-800">{{ order.dia_chi_giao }}</span>
                            </div>
                            <div v-if="order.ghi_chu" class="sm:col-span-2 p-2 bg-amber-50 border border-amber-200 text-amber-900 rounded-sm">
                                <b>Ghi chú:</b> {{ order.ghi_chu }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: STATUS UPDATE & STORE -->
                <div class="space-y-4">
                    <!-- UPDATE FORM CARD -->
                    <div class="bg-white rounded-sm border-t-3 border-[#28a745] border-x border-b border-gray-200 shadow-xs p-4 text-xs space-y-3">
                        <h4 class="font-bold text-sm text-[#343a40] border-b border-gray-100 pb-2">
                            Cập Nhật Trạng Thái
                        </h4>

                        <form class="space-y-3" @submit.prevent="submitStatus">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Trạng Thái Đơn</label>
                                <select
                                    v-model="form.trang_thai_don_hang"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm bg-white font-semibold focus:outline-hidden focus:border-[#28a745]"
                                >
                                    <option value="cho_xac_nhan">Chờ Duyệt</option>
                                    <option value="dang_chuan_bi">Đang Đóng Gói (Xuất FEFO)</option>
                                    <option value="dang_giao">Đang Giao Hàng</option>
                                    <option value="hoan_thanh">Đã Hoàn Thành</option>
                                    <option value="da_huy">Đã Hủy Đơn</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Trạng Thái Thanh Toán</label>
                                <select
                                    v-model="form.trang_thai_thanh_toan"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm bg-white focus:outline-hidden focus:border-[#28a745]"
                                >
                                    <option value="chua_thanh_toan">Chưa thanh toán</option>
                                    <option value="da_thanh_toan">Đã thanh toán</option>
                                    <option value="hoan_tien">Đã hoàn tiền</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Ghi Chú Điều Phối</label>
                                <textarea
                                    v-model="form.ghi_chu"
                                    rows="2"
                                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm focus:outline-hidden focus:border-[#28a745]"
                                    placeholder="Lý do hoặc chỉ đạo..."
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-1.5 bg-[#28a745] hover:bg-[#218838] text-white rounded-sm font-semibold shadow-2xs transition-colors flex items-center justify-center space-x-1"
                            >
                                <Save class="w-3.5 h-3.5" />
                                <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Trạng Thái' }}</span>
                            </button>
                        </form>
                    </div>

                    <!-- STORE ASSIGNMENT -->
                    <div class="bg-white rounded-sm border border-gray-200 shadow-xs p-4 text-xs space-y-1">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Chi Nhánh Xuất Hàng</span>
                        <div class="font-bold text-gray-900 text-sm">{{ order.cua_hang?.ten_cua_hang }}</div>
                        <div class="text-gray-500">{{ order.cua_hang?.dia_chi }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
