<script setup lang="ts">
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { AlertCircle, ChevronRight, Clock, PhoneCall, Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    searchedCode?: string;
    searchedPhone?: string;
    order?: any;
}>();

const orderCode = ref(props.searchedCode || '');
const phoneNumber = ref(props.searchedPhone || '');

function handleSearch() {
    router.get(
        route('orders.tracking'),
        {
            code: orderCode.value.trim(),
            phone: phoneNumber.value.trim(),
        },
        {
            preserveState: true,
        },
    );
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

const statusMap: Record<string, { label: string; class: string }> = {
    cho_xac_nhan: { label: 'Chờ xác nhận', class: 'bg-amber-100 text-amber-800' },
    dang_chuan_bi: { label: 'Đã duyệt & Đang chuẩn bị (Không thể hủy)', class: 'bg-blue-100 text-blue-800' },
    dang_giao: { label: 'Đang giao hàng', class: 'bg-indigo-100 text-indigo-800' },
    da_giao: { label: 'Giao hàng thành công', class: 'bg-emerald-100 text-emerald-800' },
    hoan_thanh: { label: 'Giao hàng thành công', class: 'bg-emerald-100 text-emerald-800' },
    da_huy: { label: 'Đã hủy', class: 'bg-red-100 text-red-800' },
};
</script>

<template>
    <StoreLayout title="Tra Cứu Đơn Hàng">
        <!-- BREADCRUMBS -->
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-7xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">Tra cứu đơn hàng</span>
            </div>
        </div>

        <div class="mx-auto max-w-4xl px-4 py-10">
            <!-- HEADER -->
            <div class="mx-auto mb-8 max-w-xl text-center">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <Clock class="h-7 w-7" />
                </div>
                <h1 class="mb-2 text-2xl font-black tracking-tight text-gray-900 sm:text-3xl">Tra Cứu Tiến Độ Đơn Hàng</h1>
                <p class="text-xs text-gray-500">
                    Nhập mã đơn hàng hoặc số điện thoại để kiểm tra trạng thái đóng gói và hành trình giao thực phẩm tươi sống.
                </p>
            </div>

            <!-- SEARCH FORM -->
            <form
                @submit.prevent="handleSearch"
                class="mb-8 grid grid-cols-1 gap-3 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:grid-cols-3"
            >
                <div class="sm:col-span-1">
                    <label class="mb-1 block text-xs font-bold text-gray-700">Mã đơn hàng *</label>
                    <input
                        v-model="orderCode"
                        type="text"
                        placeholder="VD: FF261031..."
                        required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 font-mono text-xs uppercase focus:border-emerald-500 focus:outline-none"
                    />
                </div>
                <div class="sm:col-span-1">
                    <label class="mb-1 block text-xs font-bold text-gray-700">Số điện thoại (tùy chọn)</label>
                    <input
                        v-model="phoneNumber"
                        type="tel"
                        placeholder="0912 345 678"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs focus:border-emerald-500 focus:outline-none"
                    />
                </div>
                <div class="flex items-end">
                    <button
                        type="submit"
                        class="flex w-full items-center justify-center space-x-1.5 rounded-xl bg-emerald-600 px-4 py-3.5 text-xs font-bold text-white shadow-md shadow-emerald-600/20 transition-all hover:bg-emerald-700"
                    >
                        <Search class="h-4 w-4" />
                        <span>Tra cứu ngay</span>
                    </button>
                </div>
            </form>

            <!-- RESULT DETAILS IF FOUND -->
            <div v-if="order" class="space-y-6 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                <!-- Status header -->
                <div class="flex flex-col justify-between gap-3 border-b border-gray-100 pb-6 sm:flex-row sm:items-center">
                    <div>
                        <span class="mb-0.5 block text-xs text-gray-400">Mã đơn hàng</span>
                        <strong class="font-mono text-xl font-black text-gray-900">{{ order.ma_don_hang }}</strong>
                    </div>

                    <div class="flex items-center space-x-3">
                        <span
                            class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-wider"
                            :class="statusMap[order.trang_thai_don_hang]?.class || 'bg-gray-100 text-gray-800'"
                        >
                            {{ statusMap[order.trang_thai_don_hang]?.label || order.trang_thai_don_hang }}
                        </span>
                    </div>
                </div>

                <!-- PACKING NOTICE BANNER -->
                <div
                    v-if="order.trang_thai_don_hang === 'dang_chuan_bi'"
                    class="flex items-center justify-between gap-3 rounded-2xl border border-blue-200 bg-blue-50/80 p-3.5 text-xs"
                >
                    <div class="flex items-center space-x-2 font-medium text-blue-900">
                        <Clock class="h-4 w-4 shrink-0 text-blue-600" />
                        <span
                            >Đơn hàng đã được duyệt và đang chuẩn bị đóng gói tại chi nhánh.
                            <strong>Khách hàng không thể hủy đơn lúc này.</strong></span
                        >
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-3">
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <span class="mb-1 block text-gray-400">Người nhận hàng:</span>
                        <strong class="block text-sm text-gray-900">{{ order.ten_nguoi_nhan }}</strong>
                        <span class="text-gray-600">{{ order.so_dien_thoai_nhan }}</span>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4">
                        <span class="mb-1 block text-gray-400">Địa chỉ giao:</span>
                        <p class="line-clamp-2 font-medium leading-relaxed text-gray-800">{{ order.dia_chi_giao }}</p>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4">
                        <span class="mb-1 block text-gray-400">Chi nhánh xuất kho:</span>
                        <strong class="block text-emerald-800">{{ order.cua_hang?.ten_cua_hang || 'Chi nhánh Fresh Food' }}</strong>
                        <span class="text-[11px] text-gray-500">Giao siêu tốc trong 2 giờ</span>
                    </div>
                </div>

                <!-- Items list -->
                <div>
                    <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400">Sản phẩm trong đơn:</h4>
                    <div class="divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-100">
                        <div
                            v-for="item in order.chi_tiets"
                            :key="item.id"
                            class="flex items-center justify-between p-3.5 text-xs transition-colors hover:bg-gray-50"
                        >
                            <div class="flex items-center space-x-3">
                                <img
                                    :src="item.hinh_anh_snapshot || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=200'"
                                    :alt="item.ten_san_pham"
                                    class="h-10 w-10 rounded-xl bg-gray-100 object-cover"
                                />
                                <div>
                                    <p class="font-bold text-gray-900">{{ item.ten_san_pham }}</p>
                                    <p class="text-[11px] text-gray-500">{{ item.so_luong }} × {{ formatPrice(item.don_gia) }}</p>
                                </div>
                            </div>
                            <span class="font-black text-gray-900">{{ formatPrice(item.thanh_tien) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total breakdown -->
                <div class="flex flex-col items-start justify-between gap-3 border-t border-gray-100 pt-4 text-xs sm:flex-row sm:items-center">
                    <div class="flex items-center space-x-2 text-gray-500">
                        <PhoneCall class="h-4 w-4 text-emerald-600" />
                        <span>Cần hỗ trợ đơn hàng? Gọi hotline: <strong>1900 6868</strong></span>
                    </div>
                    <div class="w-full text-right sm:w-auto">
                        <span class="mr-2 text-gray-500">Tổng thanh toán:</span>
                        <span class="text-xl font-black text-emerald-700">{{ formatPrice(order.tong_thanhtoan) }}</span>
                    </div>
                </div>
            </div>

            <!-- NOT FOUND STATE -->
            <div v-else-if="searchedCode" class="rounded-3xl border border-gray-100 bg-white p-12 text-center shadow-sm">
                <AlertCircle class="mx-auto mb-3 h-12 w-12 text-amber-500" />
                <h3 class="mb-1 text-base font-bold text-gray-900">Không tìm thấy đơn hàng</h3>
                <p class="mx-auto max-w-sm text-xs text-gray-500">
                    Vui lòng kiểm tra lại mã đơn hàng <strong class="font-mono text-gray-800">{{ searchedCode }}</strong> đã nhập chính xác chưa.
                </p>
            </div>
        </div>
    </StoreLayout>
</template>
