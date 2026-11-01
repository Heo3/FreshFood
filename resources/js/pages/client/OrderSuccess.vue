<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import {
    CheckCircle2,
    Truck,
    Clock,
    PhoneCall,
    MapPin,
    ArrowRight,
    QrCode,
} from 'lucide-vue-next';

const props = defineProps<{
    order: {
        id: number;
        ma_don_hang: string;
        tong_thanhtoan: number;
        tong_tien_hang: number;
        phi_giao_hang: number;
        tien_giam_gia: number;
        phuong_thuc_thanh_toan: string;
        ten_nguoi_nhan: string;
        so_dien_thoai_nhan: string;
        dia_chi_giao: string;
        created_at: string;
        cua_hang?: any;
        chi_tiets?: Array<any>;
    };
}>();

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

// Giả lập link VietQR chuẩn
const vietQrUrl = `https://img.vietqr.io/image/MB-0901888999-compact2.png?amount=${props.order.tong_thanhtoan}&addInfo=${props.order.ma_don_hang}&accountName=FRESH%20FOOD%20VIET%20NAM`;
</script>

<template>
    <StoreLayout title="Đặt Hàng Thành Công">
        <div class="max-w-3xl mx-auto px-4 py-12">
            <div class="bg-white rounded-3xl p-8 sm:p-12 border border-gray-100 shadow-xl text-center mb-8">
                <!-- SUCCESS ICON -->
                <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-6 shadow-inner ring-8 ring-emerald-50 animate-bounce">
                    <CheckCircle2 class="w-12 h-12" />
                </div>

                <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-black rounded-full uppercase tracking-wider mb-2">
                    Đặt hàng thành công
                </span>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 mb-2">
                    Cảm Ơn Bạn Đã Đặt Hàng Tại Fresh Food!
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 max-w-lg mx-auto mb-6">
                    Mã đơn hàng của bạn là: <strong class="text-emerald-700 font-mono text-base">{{ order.ma_don_hang }}</strong>.
                    Nhân viên cửa hàng đang tiến hành chọn những phần thực phẩm tươi ngon nhất để đóng gói.
                </p>

                <!-- VIETQR BOX IF TRANSFER -->
                <div
                    v-if="order.phuong_thuc_thanh_toan === 'chuyen_khoan'"
                    class="p-6 bg-emerald-50/60 rounded-3xl border border-emerald-200 mb-8 max-w-md mx-auto text-left"
                >
                    <div class="flex items-center space-x-2 mb-3">
                        <QrCode class="w-5 h-5 text-emerald-700" />
                        <h3 class="text-sm font-black text-emerald-950">Quét mã VietQR chuyển khoản</h3>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <img
                            :src="vietQrUrl"
                            alt="VietQR Transfer"
                            class="w-36 h-36 rounded-2xl border border-gray-200 bg-white p-1 shrink-0 shadow-sm"
                        />
                        <div class="text-xs space-y-1 text-gray-700">
                            <p>Ngân hàng: <strong>MB Bank (Quân Đội)</strong></p>
                            <p>Số tài khoản: <strong class="font-mono text-sm text-emerald-700">0901888999</strong></p>
                            <p>Chủ TK: <strong>CTY FRESH FOOD VN</strong></p>
                            <p>Số tiền: <strong class="text-emerald-700 font-bold">{{ formatPrice(order.tong_thanhtoan) }}</strong></p>
                            <p>Nội dung CK: <strong class="font-mono text-emerald-700">{{ order.ma_don_hang }}</strong></p>
                        </div>
                    </div>
                </div>

                <!-- DELIVERY ESTIMATION TIMELINE -->
                <div class="bg-gray-50 p-6 rounded-3xl border border-gray-100 text-left mb-8">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Tiến độ đơn hàng</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                        <div class="flex items-center space-x-2">
                            <div class="w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">✓</div>
                            <div>
                                <strong class="text-gray-900 block">Đã nhận đơn</strong>
                                <span class="text-[10px] text-gray-400">Vừa xong</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 opacity-50">
                            <div class="w-7 h-7 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-xs">2</div>
                            <div>
                                <strong class="text-gray-900 block">Đang soạn hàng</strong>
                                <span class="text-[10px] text-gray-400">Kho chi nhánh</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 opacity-50">
                            <div class="w-7 h-7 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-xs">3</div>
                            <div>
                                <strong class="text-gray-900 block">Đang giao hàng</strong>
                                <span class="text-[10px] text-gray-400">Giao siêu tốc 2h</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 opacity-50">
                            <div class="w-7 h-7 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-xs">4</div>
                            <div>
                                <strong class="text-gray-900 block">Hoàn thành</strong>
                                <span class="text-[10px] text-gray-400">Tích điểm nhận hàng</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ORDER SUMMARY INFO -->
                <div class="bg-gray-50 p-6 rounded-3xl border border-gray-100 text-left text-xs space-y-2 mb-8">
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500">Người nhận:</span>
                        <strong class="text-gray-900">{{ order.ten_nguoi_nhan }} - {{ order.so_dien_thoai_nhan }}</strong>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500">Địa chỉ giao:</span>
                        <strong class="text-gray-900 max-w-sm text-right truncate">{{ order.dia_chi_giao }}</strong>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500">Tổng thanh toán:</span>
                        <strong class="text-emerald-700 text-sm font-black">{{ formatPrice(order.tong_thanhtoan) }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Phương thức:</span>
                        <span class="uppercase font-bold text-gray-800">{{ order.phuong_thuc_thanh_toan }}</span>
                    </div>
                </div>

                <!-- BUTTONS -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <Link
                        :href="route('orders.tracking', { code: order.ma_don_hang })"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-6 py-3.5 rounded-2xl shadow-md transition-all flex items-center justify-center"
                    >
                        <span>Tra cứu & Theo dõi đơn hàng</span>
                        <ArrowRight class="w-4 h-4 ml-1.5" />
                    </Link>
                    <Link
                        :href="route('home')"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-6 py-3.5 rounded-2xl transition-all"
                    >
                        Quay lại trang chủ
                    </Link>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
