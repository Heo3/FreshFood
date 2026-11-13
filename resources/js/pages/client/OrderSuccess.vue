<script setup lang="ts">
import { computed } from 'vue';
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
    Sparkles,
    Download,
    CreditCard,
    Banknote,
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

// Link tạo mã QR VNPAY / VietQR chuẩn với thông tin tài khoản VPBank
const vnpayQrUrl = computed(() => {
    return `https://img.vietqr.io/image/VPB-0329643444-compact2.png?amount=${props.order.tong_thanhtoan}&addInfo=VNPAY%20${props.order.ma_don_hang}&accountName=PHAN%20VAN%20THIEN%20DUY`;
});
</script>

<template>
    <StoreLayout title="Đặt Hàng Thành Công">
        <div class="max-w-3xl mx-auto px-4 py-12">
            <div class="bg-white rounded-3xl p-6 sm:p-12 border border-gray-100 shadow-xl text-center mb-8">
                <!-- SUCCESS ICON -->
                <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-5 shadow-inner ring-8 ring-emerald-50 animate-bounce">
                    <CheckCircle2 class="w-12 h-12" />
                </div>

                <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-black rounded-full uppercase tracking-wider mb-2">
                    Đặt hàng thành công
                </span>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 mb-2">
                    Cảm Ơn Bạn Đã Đặt Hàng Tại Fresh Food!
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 max-w-lg mx-auto mb-6">
                    Mã đơn hàng: <strong class="text-emerald-700 font-mono text-base">{{ order.ma_don_hang }}</strong>.
                    Nhân viên siêu thị đang tiến hành chọn những phần thực phẩm tươi sạch nhất để giao đến bạn.
                </p>

                <!-- VNPAY-QR PAYMENT BOX -->
                <div
                    v-if="order.phuong_thuc_thanh_toan === 'vnpay' || order.phuong_thuc_thanh_toan === 'chuyen_khoan'"
                    class="p-6 bg-gradient-to-b from-blue-50/70 via-white to-blue-50/40 rounded-3xl border-2 border-blue-200/80 mb-8 max-w-lg mx-auto text-left shadow-lg shadow-blue-900/5 space-y-4"
                >
                    <div class="flex items-center justify-between pb-3 border-b border-blue-100">
                        <div class="flex items-center space-x-2">
                            <div class="w-7 h-7 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-xs">
                                QR
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-blue-950">MÃ THANH TOÁN VNPAY-QR</h3>
                                <p class="text-[11px] text-blue-700">Quét mã bằng App Ngân hàng hoặc Ví VNPAY</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-800">
                            VNPAY-QR
                        </span>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-5 pt-1">
                        <!-- QR IMAGE -->
                        <div class="relative p-2 rounded-2xl border-2 border-blue-300 bg-white shadow-sm shrink-0 group">
                            <img
                                :src="vnpayQrUrl"
                                alt="Mã VNPAY QR Thanh Toán"
                                class="w-44 h-44 object-contain rounded-xl"
                            />
                            <a
                                :href="vnpayQrUrl"
                                download="VNPAY-QR-Order.png"
                                target="_blank"
                                class="mt-1.5 w-full py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[10px] rounded-lg transition flex items-center justify-center space-x-1"
                            >
                                <Download class="w-3 h-3" />
                                <span>Tải mã QR</span>
                            </a>
                        </div>

                        <!-- TRANSFER DETAILS -->
                        <div class="text-xs space-y-2 text-slate-700 flex-1">
                            <div class="p-2.5 rounded-xl bg-blue-50/60 border border-blue-100/80">
                                <span class="text-[10px] text-blue-600 font-bold block uppercase tracking-wider">Số tiền thanh toán:</span>
                                <strong class="text-base text-blue-700 font-black">{{ formatPrice(order.tong_thanhtoan) }}</strong>
                            </div>

                            <div class="space-y-1 text-[11px]">
                                <p>Cổng thanh toán: <strong class="text-slate-900">VNPAY-QR / NAPAS247</strong></p>
                                <p>Đơn vị thụ hưởng: <strong class="text-slate-900 uppercase">PHAN VAN THIEN DUY</strong></p>
                                <p>Ngân hàng: <strong class="text-slate-900">VPBank (Việt Nam Thịnh Vượng)</strong></p>
                                <p>Số tài khoản: <strong class="font-mono text-blue-700 font-bold text-xs">0329643444</strong></p>
                                <p>Nội dung chuyển khoản: <strong class="font-mono text-blue-700 font-bold">VNPAY {{ order.ma_don_hang }}</strong></p>
                            </div>
                        </div>
                    </div>

                    <!-- 3 EASY STEPS -->
                    <div class="pt-3 border-t border-blue-100 grid grid-cols-3 gap-2 text-center text-[10px] text-slate-600">
                        <div class="p-1.5 rounded-xl bg-white border border-blue-100">
                            <span class="font-bold text-blue-600 block">Bước 1</span>
                            <span>Mở App Ngân hàng hoặc Ví VNPAY</span>
                        </div>
                        <div class="p-1.5 rounded-xl bg-white border border-blue-100">
                            <span class="font-bold text-blue-600 block">Bước 2</span>
                            <span>Chọn biểu tượng "Quét QR"</span>
                        </div>
                        <div class="p-1.5 rounded-xl bg-white border border-blue-100">
                            <span class="font-bold text-blue-600 block">Bước 3</span>
                            <span>Xác nhận & Hoàn tất thanh toán</span>
                        </div>
                    </div>
                </div>

                <!-- COD NOTICE BOX -->
                <div
                    v-else
                    class="p-5 bg-emerald-50/60 rounded-3xl border border-emerald-200 mb-8 max-w-lg mx-auto text-left flex items-start space-x-3.5"
                >
                    <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <Banknote class="w-5 h-5" />
                    </div>
                    <div class="text-xs text-emerald-950 space-y-1">
                        <h4 class="font-black text-sm">Thanh toán khi nhận hàng (COD)</h4>
                        <p class="text-emerald-800 leading-relaxed text-[11px]">
                            Đơn hàng của bạn sẽ được giao nhanh chóng trong vòng 2h. Quý khách vui lòng chuẩn bị số tiền 
                            <strong class="font-black text-emerald-700">{{ formatPrice(order.tong_thanhtoan) }}</strong> 
                            để thanh toán cho nhân viên giao hàng sau khi kiểm tra thực phẩm.
                        </p>
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
                        <span class="text-gray-500">Phương thức thanh toán:</span>
                        <span class="font-bold text-slate-800 uppercase">
                            {{ order.phuong_thuc_thanh_toan === 'vnpay' ? 'Quét mã VNPAY-QR' : (order.phuong_thuc_thanh_toan === 'cod' ? 'Thanh toán khi nhận hàng (COD)' : order.phuong_thuc_thanh_toan) }}
                        </span>
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
