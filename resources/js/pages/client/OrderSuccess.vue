<script setup lang="ts">
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Banknote, CheckCircle2, Download } from 'lucide-vue-next';
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

                <!-- VNPAY-QR PAYMENT BOX -->
                <div
                    v-if="order.phuong_thuc_thanh_toan === 'vnpay' || order.phuong_thuc_thanh_toan === 'chuyen_khoan'"
                    class="mx-auto mb-8 max-w-lg space-y-4 rounded-3xl border-2 border-blue-200/80 bg-gradient-to-b from-blue-50/70 via-white to-blue-50/40 p-6 text-left shadow-lg shadow-blue-900/5"
                >
                    <div class="flex items-center justify-between border-b border-blue-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-xl bg-blue-600 text-xs font-black text-white">QR</div>
                            <div>
                                <h3 class="text-sm font-black text-blue-950">MÃ THANH TOÁN VNPAY-QR</h3>
                                <p class="text-[11px] text-blue-700">Quét mã bằng App Ngân hàng hoặc Ví VNPAY</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-blue-800">
                            VNPAY-QR
                        </span>
                    </div>

                    <div class="flex flex-col items-center gap-5 pt-1 sm:flex-row">
                        <!-- QR IMAGE -->
                        <div class="group relative shrink-0 rounded-2xl border-2 border-blue-300 bg-white p-2 shadow-sm">
                            <img :src="vnpayQrUrl" alt="Mã VNPAY QR Thanh Toán" class="h-44 w-44 rounded-xl object-contain" />
                            <a
                                :href="vnpayQrUrl"
                                download="VNPAY-QR-Order.png"
                                target="_blank"
                                class="mt-1.5 flex w-full items-center justify-center space-x-1 rounded-lg bg-blue-50 py-1 text-[10px] font-bold text-blue-700 transition hover:bg-blue-100"
                            >
                                <Download class="h-3 w-3" />
                                <span>Tải mã QR</span>
                            </a>
                        </div>

                        <!-- TRANSFER DETAILS -->
                        <div class="flex-1 space-y-2 text-xs text-slate-700">
                            <div class="rounded-xl border border-blue-100/80 bg-blue-50/60 p-2.5">
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-blue-600">Số tiền thanh toán:</span>
                                <strong class="text-base font-black text-blue-700">{{ formatPrice(order.tong_thanhtoan) }}</strong>
                            </div>

                            <div class="space-y-1 text-[11px]">
                                <p>Cổng thanh toán: <strong class="text-slate-900">VNPAY-QR / NAPAS247</strong></p>
                                <p>Đơn vị thụ hưởng: <strong class="uppercase text-slate-900">PHAN VAN THIEN DUY</strong></p>
                                <p>Ngân hàng: <strong class="text-slate-900">VPBank (Việt Nam Thịnh Vượng)</strong></p>
                                <p>Số tài khoản: <strong class="font-mono text-xs font-bold text-blue-700">0329643444</strong></p>
                                <p>
                                    Nội dung chuyển khoản: <strong class="font-mono font-bold text-blue-700">VNPAY {{ order.ma_don_hang }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 3 EASY STEPS -->
                    <div class="grid grid-cols-3 gap-2 border-t border-blue-100 pt-3 text-center text-[10px] text-slate-600">
                        <div class="rounded-xl border border-blue-100 bg-white p-1.5">
                            <span class="block font-bold text-blue-600">Bước 1</span>
                            <span>Mở App Ngân hàng hoặc Ví VNPAY</span>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-white p-1.5">
                            <span class="block font-bold text-blue-600">Bước 2</span>
                            <span>Chọn biểu tượng "Quét QR"</span>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-white p-1.5">
                            <span class="block font-bold text-blue-600">Bước 3</span>
                            <span>Xác nhận & Hoàn tất thanh toán</span>
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
