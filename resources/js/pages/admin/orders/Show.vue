<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, ShoppingBag, User } from 'lucide-vue-next';

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
        nhan_vien_len_don_id?: number | null;
        nguoi_giao_hang_id?: number | null;
        loai_phuong_tien_giao?: string | null;
        bien_so_xe_giao?: string | null;
        thoi_gian_bat_dau_giao?: string | null;
        thoi_gian_giao_thanh_cong?: string | null;
        created_at: string;
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
            bien_so_xe?: string;
            loai_phuong_tien?: string;
        };
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
    staffList?: Array<{
        id: number;
        name: string;
        phone?: string;
        role?: string;
    }>;
    driverList?: Array<{
        id: number;
        name: string;
        phone?: string;
        loai_phuong_tien?: string;
        bien_so_xe?: string;
        trang_thai_giao?: string;
    }>;
}>();

const form = useForm({
    trang_thai_don_hang: props.order.trang_thai_don_hang,
    trang_thai_thanh_toan: props.order.trang_thai_thanh_toan,
    nhan_vien_len_don_id: props.order.nhan_vien_len_don_id ?? null,
    nguoi_giao_hang_id: props.order.nguoi_giao_hang_id ?? null,
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
            { name: order.ma_don_hang },
        ]"
    >
        <div class="mx-auto max-w-5xl space-y-4">
            <!-- BACK BUTTON -->
            <div>
                <Link
                    :href="route('admin.orders.index')"
                    class="inline-flex items-center space-x-1.5 text-xs font-semibold text-gray-600 hover:text-[#007bff]"
                >
                    <ArrowLeft class="h-3.5 w-3.5" />
                    <span>Quay lại danh sách đơn hàng</span>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- LEFT COLUMN: ITEMS & FINANCIALS -->
                <div class="space-y-4 lg:col-span-2">
                    <!-- ITEMS TABLE CARD -->
                    <div class="border-t-3 shadow-xs rounded-sm border-x border-b border-[#007bff] border-gray-200 bg-white">
                        <!-- Card Header -->
                        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                            <h3 class="flex items-center space-x-2 text-sm font-bold text-[#343a40]">
                                <ShoppingBag class="h-4 w-4 text-[#007bff]" />
                                <span>Danh Sách Mặt Hàng Đặt Mua</span>
                            </h3>
                            <span class="font-mono text-xs text-gray-400">#{{ order.ma_don_hang }}</span>
                        </div>

                        <!-- Card Body -->
                        <div class="p-0">
                            <table class="w-full border-collapse text-left text-xs">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase text-gray-600">
                                        <th class="px-4 py-2">Sản Phẩm</th>
                                        <th class="px-3 py-2 text-center">Đơn Giá</th>
                                        <th class="px-3 py-2 text-center">Số Lượng</th>
                                        <th class="px-4 py-2 text-right">Thành Tiền</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="item in order.chi_tiet_don_hangs" :key="item.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5 font-bold text-gray-800">
                                            <div class="flex items-center space-x-2">
                                                <div class="h-8 w-8 shrink-0 overflow-hidden rounded-sm border border-gray-200 bg-gray-100">
                                                    <img
                                                        v-if="item.san_pham?.hinh_anh_chinh"
                                                        :src="item.san_pham.hinh_anh_chinh"
                                                        alt="Thumb"
                                                        class="h-full w-full object-cover"
                                                    />
                                                </div>
                                                <span class="truncate">{{ item.ten_san_pham }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5 text-center text-gray-600">
                                            {{ formatCurrency(item.don_gia) }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center font-bold text-gray-800">
                                            {{ Number(item.so_luong).toFixed(1) }} {{ item.san_pham?.don_vi_tinh || 'Kg' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-extrabold text-gray-900">
                                            {{ formatCurrency(item.thanh_tien) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- FINANCIAL BREAKDOWN -->
                            <div class="space-y-1.5 border-t border-gray-200 bg-gray-50 p-4 text-xs text-gray-600">
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
                                <div class="flex justify-between border-t border-gray-200 pt-2 text-sm font-extrabold text-[#28a745]">
                                    <span>Tổng cộng thanh toán:</span>
                                    <span>{{ formatCurrency(order.tong_thanhtoan) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CUSTOMER & DELIVERY INFO CARD -->
                    <div class="shadow-xs space-y-3 rounded-sm border border-gray-200 bg-white p-4 text-xs">
                        <span class="block flex items-center space-x-2 border-b border-gray-100 pb-2 text-sm font-bold text-[#343a40]">
                            <User class="h-4 w-4 text-[#007bff]" />
                            <span>Thông Tin Người Nhận & Giao Hàng</span>
                        </span>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <span class="block text-[11px] text-gray-400">Người nhận hàng:</span>
                                <b class="text-gray-900">{{ order.ten_nguoi_nhan }}</b>
                            </div>
                            <div>
                                <span class="block text-[11px] text-gray-400">Số điện thoại:</span>
                                <b class="font-mono text-gray-900">{{ order.so_dien_thoai_nhan }}</b>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="block text-[11px] text-gray-400">Địa chỉ giao:</span>
                                <span class="text-gray-800">{{ order.dia_chi_giao }}</span>
                            </div>

                            <div class="border-t border-gray-100 pt-2 sm:col-span-2">
                                <span class="block text-[11px] font-semibold text-gray-500">Nhân viên chuẩn bị đơn / Soạn hàng:</span>
                                <div v-if="order.nhan_vien_len_don" class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                    <span class="rounded bg-blue-50 px-2 py-0.5 font-bold text-blue-700">
                                        {{ order.nhan_vien_len_don.name }}
                                    </span>
                                    <span v-if="order.nhan_vien_len_don.phone" class="font-mono text-gray-600">
                                        ({{ order.nhan_vien_len_don.phone }})
                                    </span>
                                    <span v-if="order.nhan_vien_len_don.role" class="text-[10px] uppercase text-gray-400">
                                        [{{ order.nhan_vien_len_don.role }}]
                                    </span>
                                </div>
                                <div v-else class="mt-0.5 font-medium text-amber-700">
                                    <span>Khách tự đặt online - </span>
                                    <span class="italic text-gray-500">Chưa có nhân viên tiếp nhận chuẩn bị</span>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-2 sm:col-span-2">
                                <span class="block text-[11px] font-semibold text-gray-500">Nhân viên giao hàng (Shipper):</span>
                                <div v-if="order.nguoi_giao_hang" class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                    <span class="rounded bg-emerald-50 px-2 py-0.5 font-bold text-emerald-700">
                                        {{ order.nguoi_giao_hang.name }}
                                    </span>
                                    <span v-if="order.bien_so_xe_giao || order.nguoi_giao_hang.bien_so_xe" class="font-mono font-semibold text-gray-700">
                                        - Biển số: {{ order.bien_so_xe_giao || order.nguoi_giao_hang.bien_so_xe }}
                                    </span>
                                    <span v-if="order.nguoi_giao_hang.phone" class="font-mono text-gray-600">
                                        ({{ order.nguoi_giao_hang.phone }})
                                    </span>
                                </div>
                                <span v-else class="text-gray-400 italic">Chưa phân công nhân viên giao hàng</span>
                            </div>

                            <div v-if="order.ghi_chu" class="rounded-sm border border-amber-200 bg-amber-50 p-2 text-amber-900 sm:col-span-2">
                                <b>Ghi chú:</b> {{ order.ghi_chu }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: STATUS UPDATE & STORE -->
                <div class="space-y-4">
                    <!-- UPDATE FORM CARD -->
                    <div class="border-t-3 shadow-xs space-y-3 rounded-sm border-x border-b border-[#28a745] border-gray-200 bg-white p-4 text-xs">
                        <h4 class="border-b border-gray-100 pb-2 text-sm font-bold text-[#343a40]">Cập Nhật Trạng Thái</h4>

                        <form class="space-y-3" @submit.prevent="submitStatus">
                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Trạng Thái Đơn</label>
                                <select
                                    v-model="form.trang_thai_don_hang"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-white px-2.5 py-1.5 font-semibold focus:border-[#28a745]"
                                >
                                    <option value="cho_xac_nhan">Chờ Duyệt (Chờ Xác Nhận)</option>
                                    <option value="dang_chuan_bi">Đang Đóng Gói (Xuất FEFO)</option>
                                    <option value="dang_giao">Đang Giao Hàng</option>
                                    <option value="da_giao">Đã Giao Hàng</option>
                                    <option value="hoan_thanh">Đã Hoàn Thành</option>
                                    <option value="da_huy">Đã Hủy Đơn</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Trạng Thái Thanh Toán</label>
                                <select
                                    v-model="form.trang_thai_thanh_toan"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-white px-2.5 py-1.5 focus:border-[#28a745]"
                                >
                                    <option value="chua_thanh_toan">Chưa thanh toán</option>
                                    <option value="da_thanh_toan">Đã thanh toán</option>
                                    <option value="hoan_tien">Đã hoàn tiền</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Nhân Viên Chuẩn Bị Đơn / Soạn Hàng</label>
                                <select
                                    v-model="form.nhan_vien_len_don_id"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-white px-2.5 py-1.5 focus:border-[#28a745]"
                                >
                                    <option :value="null">-- Chưa phân công nhân viên chuẩn bị --</option>
                                    <option v-for="staff in staffList" :key="staff.id" :value="staff.id">
                                        {{ staff.name }} ({{ staff.role === 'admin' ? 'Quản trị viên' : (staff.role === 'quan_ly' ? 'Quản lý' : 'Nhân viên') }})
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Nhân Viên Giao Hàng (Shipper)</label>
                                <select
                                    v-model="form.nguoi_giao_hang_id"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 bg-white px-2.5 py-1.5 focus:border-[#28a745]"
                                >
                                    <option :value="null">-- Chưa phân công shipper --</option>
                                    <option v-for="driver in driverList" :key="driver.id" :value="driver.id">
                                        {{ driver.name }} - {{ driver.bien_so_xe || driver.loai_phuong_tien || 'Xe máy' }} ({{ driver.phone || 'N/A' }})
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-gray-700">Ghi Chú Điều Phối</label>
                                <textarea
                                    v-model="form.ghi_chu"
                                    rows="2"
                                    class="focus:outline-hidden w-full rounded-sm border border-gray-300 px-2.5 py-1.5 focus:border-[#28a745]"
                                    placeholder="Lý do hoặc chỉ đạo..."
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="shadow-2xs flex w-full items-center justify-center space-x-1 rounded-sm bg-[#28a745] py-1.5 font-semibold text-white transition-colors hover:bg-[#218838]"
                            >
                                <Save class="h-3.5 w-3.5" />
                                <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Trạng Thái' }}</span>
                            </button>
                        </form>
                    </div>

                    <!-- STORE ASSIGNMENT -->
                    <div class="shadow-xs space-y-1 rounded-sm border border-gray-200 bg-white p-4 text-xs">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400">Chi Nhánh Xuất Hàng</span>
                        <div class="text-sm font-bold text-gray-900">{{ order.cua_hang?.ten_cua_hang }}</div>
                        <div class="text-gray-500">{{ order.cua_hang?.dia_chi }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
