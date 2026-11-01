<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import {
    Search,
    Package,
    Truck,
    CheckCircle2,
    Clock,
    AlertCircle,
    ChevronRight,
    PhoneCall,
} from 'lucide-vue-next';

const props = defineProps<{
    searchedCode?: string;
    searchedPhone?: string;
    order?: any;
}>();

const orderCode = ref(props.searchedCode || '');
const phoneNumber = ref(props.searchedPhone || '');

function handleSearch() {
    router.get(route('orders.tracking'), {
        code: orderCode.value.trim(),
        phone: phoneNumber.value.trim(),
    }, {
        preserveState: true,
    });
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

const statusMap: Record<string, { label: string; class: string }> = {
    cho_xac_nhan: { label: 'Chờ xác nhận', class: 'bg-amber-100 text-amber-800' },
    dang_chuan_bi: { label: 'Đang chuẩn bị hàng', class: 'bg-blue-100 text-blue-800' },
    dang_giao: { label: 'Đang giao hàng', class: 'bg-indigo-100 text-indigo-800' },
    da_giao: { label: 'Giao hàng thành công', class: 'bg-emerald-100 text-emerald-800' },
    da_huy: { label: 'Đã hủy', class: 'bg-red-100 text-red-800' },
};
</script>

<template>
    <StoreLayout title="Tra Cứu Đơn Hàng">
        <!-- BREADCRUMBS -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Tra cứu đơn hàng</span>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 py-10">
            <!-- HEADER -->
            <div class="text-center max-w-xl mx-auto mb-8">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                    <Clock class="w-7 h-7" />
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mb-2">
                    Tra Cứu Tiến Độ Đơn Hàng
                </h1>
                <p class="text-xs text-gray-500">
                    Nhập mã đơn hàng hoặc số điện thoại để kiểm tra trạng thái đóng gói và hành trình giao thực phẩm tươi sống.
                </p>
            </div>

            <!-- SEARCH FORM -->
            <form @submit.prevent="handleSearch" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm mb-8 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-1">
                    <label class="text-xs font-bold text-gray-700 block mb-1">Mã đơn hàng *</label>
                    <input
                        v-model="orderCode"
                        type="text"
                        placeholder="VD: FF261031..."
                        required
                        class="w-full text-xs p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none uppercase font-mono"
                    />
                </div>
                <div class="sm:col-span-1">
                    <label class="text-xs font-bold text-gray-700 block mb-1">Số điện thoại (tùy chọn)</label>
                    <input
                        v-model="phoneNumber"
                        type="tel"
                        placeholder="0912 345 678"
                        class="w-full text-xs p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                    />
                </div>
                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-3.5 px-4 rounded-xl flex items-center justify-center space-x-1.5 shadow-md shadow-emerald-600/20 transition-all"
                    >
                        <Search class="w-4 h-4" />
                        <span>Tra cứu ngay</span>
                    </button>
                </div>
            </form>

            <!-- RESULT DETAILS IF FOUND -->
            <div v-if="order" class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-6">
                <!-- Status header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-gray-100 gap-3">
                    <div>
                        <span class="text-xs text-gray-400 block mb-0.5">Mã đơn hàng</span>
                        <strong class="font-mono text-xl font-black text-gray-900">{{ order.ma_don_hang }}</strong>
                    </div>

                    <div class="flex items-center space-x-3">
                        <span
                            class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider"
                            :class="statusMap[order.trang_thai_don_hang]?.class || 'bg-gray-100 text-gray-800'"
                        >
                            {{ statusMap[order.trang_thai_don_hang]?.label || order.trang_thai_don_hang }}
                        </span>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 bg-gray-50 rounded-2xl">
                        <span class="text-gray-400 block mb-1">Người nhận hàng:</span>
                        <strong class="text-gray-900 block text-sm">{{ order.ten_nguoi_nhan }}</strong>
                        <span class="text-gray-600">{{ order.so_dien_thoai_nhan }}</span>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-2xl">
                        <span class="text-gray-400 block mb-1">Địa chỉ giao:</span>
                        <p class="text-gray-800 line-clamp-2 leading-relaxed font-medium">{{ order.dia_chi_giao }}</p>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-2xl">
                        <span class="text-gray-400 block mb-1">Chi nhánh xuất kho:</span>
                        <strong class="text-emerald-800 block">{{ order.cua_hang?.ten_cua_hang || 'Chi nhánh Fresh Food' }}</strong>
                        <span class="text-[11px] text-gray-500">Giao siêu tốc trong 2 giờ</span>
                    </div>
                </div>

                <!-- Items list -->
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Sản phẩm trong đơn:</h4>
                    <div class="divide-y divide-gray-100 border border-gray-100 rounded-2xl overflow-hidden">
                        <div
                            v-for="item in order.chi_tiets"
                            :key="item.id"
                            class="p-3.5 flex items-center justify-between text-xs hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-center space-x-3">
                                <img
                                    :src="item.hinh_anh_snapshot || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=200'"
                                    :alt="item.ten_san_pham"
                                    class="w-10 h-10 rounded-xl object-cover bg-gray-100"
                                />
                                <div>
                                    <p class="font-bold text-gray-900">{{ item.ten_san_pham }}</p>
                                    <p class="text-gray-500 text-[11px]">{{ item.so_luong }} × {{ formatPrice(item.don_gia) }}</p>
                                </div>
                            </div>
                            <span class="font-black text-gray-900">{{ formatPrice(item.thanh_tien) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total breakdown -->
                <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center text-xs gap-3">
                    <div class="flex items-center space-x-2 text-gray-500">
                        <PhoneCall class="w-4 h-4 text-emerald-600" />
                        <span>Cần hỗ trợ đơn hàng? Gọi hotline: <strong>1900 6868</strong></span>
                    </div>
                    <div class="text-right w-full sm:w-auto">
                        <span class="text-gray-500 mr-2">Tổng thanh toán:</span>
                        <span class="text-xl font-black text-emerald-700">{{ formatPrice(order.tong_thanhtoan) }}</span>
                    </div>
                </div>
            </div>

            <!-- NOT FOUND STATE -->
            <div v-else-if="searchedCode" class="bg-white rounded-3xl p-12 text-center border border-gray-100 shadow-sm">
                <AlertCircle class="w-12 h-12 text-amber-500 mx-auto mb-3" />
                <h3 class="text-base font-bold text-gray-900 mb-1">Không tìm thấy đơn hàng</h3>
                <p class="text-xs text-gray-500 max-w-sm mx-auto">
                    Vui lòng kiểm tra lại mã đơn hàng <strong class="font-mono text-gray-800">{{ searchedCode }}</strong> đã nhập chính xác chưa.
                </p>
            </div>
        </div>
    </StoreLayout>
</template>
