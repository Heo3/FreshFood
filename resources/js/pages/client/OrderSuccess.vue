<script setup lang="ts">
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, Banknote, CheckCircle2, CreditCard, Download, ExternalLink, RefreshCw, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    order: {
        id: number;
        ma_don_hang: string;
        tong_thanhtoan: number;
        tong_tien_hang: number;
        phi_giao_hang: number;
        tien_giam_gia: number;
        phuong_thuc_thanh_toan: string;
        trang_thai_thanh_toan: string;
        trang_thai_don_hang: string;
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

function payWithVNPay() {
    router.post(route('checkout.vnpay-payment', props.order.ma_don_hang));
}

// Link tạo mã QR VNPAY / VietQR chuẩn với thông tin tài khoản VPBank
const vnpayQrUrl = computed(() => {
    return `https://img.vietqr.io/image/VPB-0329643444-compact2.png?amount=${props.order.tong_thanhtoan}&addInfo=VNPAY%20${props.order.ma_don_hang}&accountName=PHAN%20VAN%20THIEN%20DUY`;
});
</script>

<template>
    <StoreLayout title="Đặt Hàng Thành Công">
        <div class="mx-auto max-w-3xl px-4 py-12">
            <div class="mb-8 rounded-3xl border border-gray-100 bg-white p-6 text-center shadow-xl sm:p-12">
                <!-- SUCCESS ICON -->
                <div
                    class="mx-auto mb-5 flex h-20 w-20 animate-bounce items-center justify-center rounded-full bg-emerald-100 text-emerald-600 shadow-inner ring-8 ring-emerald-50"
                >
                    <CheckCircle2 class="h-12 w-12" />
                </div>

                <span class="mb-2 inline-block rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-wider text-emerald-700">
                    Đặt hàng thành công
                </span>
                <h1 class="mb-2 text-2xl font-black text-gray-900 sm:text-3xl">Cảm Ơn Bạn Đã Đặt Hàng Tại Fresh Food!</h1>
                <p class="mx-auto mb-6 max-w-lg text-xs text-gray-500 sm:text-sm">
                    Mã đơn hàng: <strong class="font-mono text-base text-emerald-700">{{ order.ma_don_hang }}</strong
                    >. Nhân viên siêu thị đang tiến hành chọn những phần thực phẩm tươi sạch nhất để giao đến bạn.
                </p>

                <!-- VNPAY PAYMENT BOX -->
                <div v-if="order.phuong_thuc_thanh_toan === 'vnpay' || order.phuong_thuc_thanh_toan === 'chuyen_khoan'">
                    <!-- ĐÃ THANH TOÁN THÀNH CÔNG -->
                    <div
                        v-if="order.trang_thai_thanh_toan === 'da_thanh_toan'"
                        class="mx-auto mb-8 max-w-lg rounded-3xl border-2 border-emerald-300 bg-emerald-50/80 p-6 text-left shadow-lg shadow-emerald-900/5"
                    >
                        <div class="flex items-center space-x-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white">
                                <CheckCircle2 class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-emerald-950">ĐÃ THANH TOÁN VNPAY THÀNH CÔNG</h3>
                                <p class="text-xs text-emerald-700 font-medium">Hệ thống đã nhận được số tiền {{ formatPrice(order.tong_thanhtoan) }}. Đơn hàng đang được chuẩn bị!</p>
                            </div>
                        </div>
                    </div>

                    <!-- CHƯA THANH TOÁN - CỔNG VNPAY SANDBOX -->
                    <div
                        v-else
                        class="mx-auto mb-8 max-w-lg space-y-4 rounded-3xl border-2 border-blue-200/90 bg-gradient-to-b from-blue-50/70 via-white to-blue-50/40 p-6 text-left shadow-lg shadow-blue-900/5"
                    >
                        <div class="flex items-center justify-between border-b border-blue-100 pb-3">
                            <div class="flex items-center space-x-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-600 text-xs font-black text-white">
                                    <CreditCard class="h-4 w-4" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-blue-950">CỔNG THANH TOÁN VNPAY SANDBOX</h3>
                                    <p class="text-[11px] text-blue-700">Đơn hàng đang chờ bạn thanh toán trực tuyến</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-800">
                                Chờ Thanh Toán
                            </span>
                        </div>

                        <!-- NÚT THANH TOÁN NGAY QUA VNPAY -->
                        <div class="rounded-2xl border border-blue-100 bg-blue-50/80 p-4 text-center">
                            <p class="text-xs text-slate-600 mb-1">Số tiền cần thanh toán:</p>
                            <p class="text-2xl font-black text-blue-700 mb-3">{{ formatPrice(order.tong_thanhtoan) }}</p>
                            <button
                                @click="payWithVNPay"
                                type="button"
                                class="inline-flex w-full items-center justify-center space-x-2 rounded-xl bg-blue-600 py-3 text-xs font-black uppercase tracking-wider text-white shadow-md transition-all hover:bg-blue-700 cursor-pointer"
                            >
                                <ExternalLink class="h-4 w-4" />
                                <span>Thanh Toán Ngay Qua VNPAY Sandbox</span>
                            </button>
                        </div>

                        <!-- THÔNG TIN THẺ TEST -->
                        <div class="rounded-2xl border border-blue-200 bg-white p-3 text-[11px] text-slate-700 space-y-1.5">
                            <p class="font-bold text-blue-900 flex items-center">
                                <ShieldCheck class="mr-1 h-3.5 w-3.5 text-blue-600" />
                                Thông tin thẻ TEST (Môi trường Sandbox):
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-2 gap-y-1 text-[11px]">
                                <div>Ngân hàng: <strong class="font-bold text-slate-900">NCB</strong></div>
                                <div>Số thẻ: <strong class="font-mono font-bold text-blue-700">9704198526191432198</strong></div>
                                <div>Tên: <strong class="font-bold text-slate-900">NGUYEN VAN A</strong></div>
                                <div>Ngày: <strong class="font-bold text-slate-900">07/15</strong> • OTP: <strong class="font-bold text-slate-900">123456</strong></div>
                            </div>
                        </div>

                        <!-- QR DỰ PHÒNG HOẶC QUÉT MÃ -->
                        <div class="border-t border-blue-100 pt-3">
                            <details class="text-xs text-slate-600">
                                <summary class="font-bold text-blue-700 cursor-pointer hover:underline">
                                    Hoặc quét mã VietQR / VNPAY-QR chuyển khoản
                                </summary>
                                <div class="mt-3 flex flex-col items-center sm:flex-row gap-4 p-2 bg-slate-50 rounded-xl">
                                    <img :src="vnpayQrUrl" alt="Mã VNPAY QR Thanh Toán" class="h-32 w-32 rounded-lg object-contain bg-white p-1 border" />
                                    <div class="text-[11px] space-y-1">
                                        <p>STK: <strong class="font-mono text-blue-700">0329643444</strong> (VPBank)</p>
                                        <p>Chủ TK: <strong class="uppercase text-slate-900">PHAN VAN THIEN DUY</strong></p>
                                        <p>Nội dung: <strong class="font-mono text-blue-700">VNPAY {{ order.ma_don_hang }}</strong></p>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <!-- COD NOTICE BOX -->
                <div
                    v-else
                    class="mx-auto mb-8 flex max-w-lg items-start space-x-3.5 rounded-3xl border border-emerald-200 bg-emerald-50/60 p-5 text-left"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                        <Banknote class="h-5 w-5" />
                    </div>
                    <div class="space-y-1 text-xs text-emerald-950">
                        <h4 class="text-sm font-black">Thanh toán khi nhận hàng (COD)</h4>
                        <p class="text-[11px] leading-relaxed text-emerald-800">
                            Đơn hàng của bạn sẽ được giao nhanh chóng trong vòng 2h. Quý khách vui lòng chuẩn bị số tiền
                            <strong class="font-black text-emerald-700">{{ formatPrice(order.tong_thanhtoan) }}</strong>
                            để thanh toán cho nhân viên giao hàng sau khi kiểm tra thực phẩm.
                        </p>
                    </div>
                </div>

                <!-- DELIVERY ESTIMATION TIMELINE -->
                <div class="mb-8 rounded-3xl border border-gray-100 bg-gray-50 p-6 text-left">
                    <h3 class="mb-4 text-xs font-bold uppercase tracking-wider text-gray-400">Tiến độ đơn hàng</h3>
                    <div class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-4">
                        <div class="flex items-center space-x-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">✓</div>
                            <div>
                                <strong class="block text-gray-900">Đã nhận đơn</strong>
                                <span class="text-[10px] text-gray-400">Vừa xong</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 opacity-50">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-bold text-gray-600">2</div>
                            <div>
                                <strong class="block text-gray-900">Đang soạn hàng</strong>
                                <span class="text-[10px] text-gray-400">Kho chi nhánh</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 opacity-50">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-bold text-gray-600">3</div>
                            <div>
                                <strong class="block text-gray-900">Đang giao hàng</strong>
                                <span class="text-[10px] text-gray-400">Giao siêu tốc 2h</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 opacity-50">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-bold text-gray-600">4</div>
                            <div>
                                <strong class="block text-gray-900">Hoàn thành</strong>
                                <span class="text-[10px] text-gray-400">Tích điểm nhận hàng</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ORDER SUMMARY INFO -->
                <div class="mb-8 space-y-2 rounded-3xl border border-gray-100 bg-gray-50 p-6 text-left text-xs">
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500">Người nhận:</span>
                        <strong class="text-gray-900">{{ order.ten_nguoi_nhan }} - {{ order.so_dien_thoai_nhan }}</strong>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500">Địa chỉ giao:</span>
                        <strong class="max-w-sm truncate text-right text-gray-900">{{ order.dia_chi_giao }}</strong>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500">Tổng thanh toán:</span>
                        <strong class="text-sm font-black text-emerald-700">{{ formatPrice(order.tong_thanhtoan) }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Phương thức thanh toán:</span>
                        <span class="font-bold uppercase text-slate-800">
                            {{
                                order.phuong_thuc_thanh_toan === 'vnpay'
                                    ? 'Quét mã VNPAY-QR'
                                    : order.phuong_thuc_thanh_toan === 'cod'
                                      ? 'Thanh toán khi nhận hàng (COD)'
                                      : order.phuong_thuc_thanh_toan
                            }}
                        </span>
                    </div>
                </div>

                <!-- BUTTONS -->
                <div class="flex flex-col justify-center gap-3 sm:flex-row">
                    <Link
                        :href="route('orders.tracking', { code: order.ma_don_hang })"
                        class="flex items-center justify-center rounded-2xl bg-emerald-600 px-6 py-3.5 text-xs font-bold text-white shadow-md transition-all hover:bg-emerald-700"
                    >
                        <span>Tra cứu & Theo dõi đơn hàng</span>
                        <ArrowRight class="ml-1.5 h-4 w-4" />
                    </Link>
                    <Link
                        :href="route('home')"
                        class="rounded-2xl bg-gray-100 px-6 py-3.5 text-xs font-bold text-gray-700 transition-all hover:bg-gray-200"
                    >
                        Quay lại trang chủ
                    </Link>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
