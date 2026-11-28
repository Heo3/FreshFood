<script setup lang="ts">
import ToastContainer from '@/components/ui/ToastContainer.vue';
import { useToast } from '@/composables/useToast';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeftRight, Bike, Check, CheckCircle2, Clock, LogOut, MapPin, Navigation, Package, Phone, Truck } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    activeDeliveries: Array<any>;
    historyDeliveries: Array<any>;
    transferTrips?: Array<any>;
    driverInfo: any;
}>();

const toast = useToast();
const activeTab = ref<'active' | 'transfers' | 'history'>('active');
const isSubmitting = ref<number | null>(null);

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

function completeDelivery(orderId: number, orderCode: string) {
    if (!confirm(`Bạn xác nhận đã giao thành công và thu tiền (nếu COD) cho đơn hàng #${orderCode}?`)) {
        return;
    }

    isSubmitting.value = orderId;
    router.post(
        route('delivery.complete', orderId),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Đã hoàn thành giao đơn #${orderCode}!`);
            },
            onError: () => {
                toast.error('Có lỗi xảy ra khi xác nhận giao hàng.');
            },
            onFinish: () => {
                isSubmitting.value = null;
            },
        },
    );
}

function reportIssue(orderId: number, orderCode: string) {
    const reason = prompt(`Nhập lý do chưa giao được đơn #${orderCode} (VD: Khách không nghe máy, khách xin dời sang chiều...):`);
    if (!reason || !reason.trim()) return;

    router.post(
        route('delivery.fail', orderId),
        { ly_do: reason.trim() },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.info(`Đã lưu ghi chú sự cố cho đơn #${orderCode}.`);
            },
        },
    );
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-100 pb-12 font-sans text-slate-900">
        <Head title="Cổng Giao Hàng & Điều Phối Xe - FreshFood" />
        <ToastContainer />

        <!-- TOP APP BAR -->
        <header class="sticky top-0 z-40 bg-slate-900 px-4 py-3 text-white shadow-md">
            <div class="mx-auto flex max-w-3xl items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl font-black shadow-inner"
                        :class="driverInfo.loai_phuong_tien === 'xe_tai' ? 'bg-sky-500 text-white' : 'bg-emerald-500 text-white'"
                    >
                        <Truck v-if="driverInfo.loai_phuong_tien === 'xe_tai'" class="h-5 w-5" />
                        <Bike v-else class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="flex items-center gap-1.5 text-sm font-bold">
                            <span>{{ driverInfo.name }}</span>
                            <span class="rounded-full bg-slate-800 px-2 py-0.5 font-mono text-[10px] text-emerald-400">
                                {{ driverInfo.bien_so_xe || 'BKS: —' }}
                            </span>
                        </h1>
                        <p class="text-[11px] text-slate-400">
                            {{ driverInfo.loai_phuong_tien === 'xe_tai' ? 'Tài xế Xe Tải Vận Chuyển 🚚' : 'Shipper Xe Máy Giao Hàng 2H 🏍️' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <Link
                        :href="route('home')"
                        class="flex items-center gap-1 rounded-xl bg-slate-800 p-2 text-xs font-bold text-slate-300 transition hover:bg-slate-700"
                    >
                        <LogOut class="h-3.5 w-3.5" />
                        <span class="hidden sm:inline">Về web</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="mx-auto w-full max-w-3xl space-y-4 px-4 pt-4">
            <!-- TABS -->
            <div class="grid grid-cols-3 gap-2 rounded-2xl bg-slate-200/80 p-1 text-xs font-bold">
                <button
                    type="button"
                    @click="activeTab = 'active'"
                    class="flex items-center justify-center space-x-1.5 rounded-xl py-2.5 transition"
                    :class="activeTab === 'active' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                >
                    <Package class="h-4 w-4 text-emerald-600" />
                    <span>Đơn Cần Giao ({{ activeDeliveries.length }})</span>
                </button>

                <button
                    v-if="driverInfo.loai_phuong_tien === 'xe_tai' || driverInfo.role === 'admin'"
                    type="button"
                    @click="activeTab = 'transfers'"
                    class="flex items-center justify-center space-x-1.5 rounded-xl py-2.5 transition"
                    :class="activeTab === 'transfers' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                >
                    <ArrowLeftRight class="h-4 w-4 text-sky-600" />
                    <span>Chuyến Xe Tải ({{ transferTrips?.length || 0 }})</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'history'"
                    class="flex items-center justify-center space-x-1.5 rounded-xl py-2.5 transition"
                    :class="activeTab === 'history' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                >
                    <Clock class="h-4 w-4 text-slate-500" />
                    <span>Lịch Sử ({{ historyDeliveries.length }})</span>
                </button>
            </div>

            <!-- TAB 1: ACTIVE DELIVERIES -->
            <div v-if="activeTab === 'active'" class="space-y-4">
                <div
                    v-if="activeDeliveries.length === 0"
                    class="space-y-2 rounded-3xl border border-slate-200 bg-white p-8 text-center text-slate-400"
                >
                    <CheckCircle2 class="mx-auto h-10 w-10 text-emerald-500" />
                    <h3 class="text-sm font-bold text-slate-800">Hiện không có đơn hàng nào cần giao!</h3>
                    <p class="text-xs">Bạn đang ở trạng thái sẵn sàng. Khi cửa hàng điều phối đơn mới, đơn sẽ hiển thị tại đây.</p>
                </div>

                <div
                    v-for="order in activeDeliveries"
                    :key="order.id"
                    class="space-y-4 rounded-3xl border border-slate-200/90 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-mono text-sm font-black text-slate-900">#{{ order.ma_don_hang }}</span>
                                <span
                                    v-if="order.loai_phuong_tien_giao === 'xe_tai'"
                                    class="flex items-center gap-1 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-black text-sky-800"
                                >
                                    <Truck class="h-3 w-3" /> Đơn Xe Tải
                                </span>
                                <span
                                    v-else
                                    class="flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-emerald-800"
                                >
                                    <Bike class="h-3 w-3" /> Đơn Xe Máy
                                </span>
                            </div>
                            <p class="mt-0.5 text-[11px] text-slate-400">
                                Xuất từ chi nhánh: <strong>{{ order.cua_hang?.ten_cua_hang }}</strong>
                            </p>
                        </div>

                        <span class="animate-pulse rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-900"> Đang đi giao </span>
                    </div>

                    <!-- CUSTOMER INFO CARD -->
                    <div class="space-y-2 rounded-2xl border border-slate-100 bg-slate-50 p-3.5 text-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-bold text-slate-900">{{ order.ten_nguoi_nhan }}</span>
                            <div class="flex items-center gap-1.5">
                                <a
                                    :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(order.dia_chi_giao)}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="shadow-xs inline-flex items-center space-x-1 rounded-xl border border-sky-300 bg-sky-50 px-2.5 py-1.5 font-bold text-sky-800 transition hover:bg-sky-100"
                                >
                                    <Navigation class="h-3.5 w-3.5 text-sky-600" />
                                    <span>Chỉ đường</span>
                                </a>
                                <a
                                    :href="`tel:${order.so_dien_thoai_nhan}`"
                                    class="shadow-xs inline-flex items-center space-x-1 rounded-xl bg-emerald-600 px-3 py-1.5 font-bold text-white transition hover:bg-emerald-700"
                                >
                                    <Phone class="h-3.5 w-3.5" />
                                    <span>Gọi: {{ order.so_dien_thoai_nhan }}</span>
                                </a>
                            </div>
                        </div>

                        <div v-if="order.khung_gio_giao" class="flex items-center space-x-1.5 rounded-xl border border-indigo-200 bg-indigo-50 p-2 text-[11px] font-bold text-indigo-900">
                            <Clock class="h-3.5 w-3.5 text-indigo-600" />
                            <span>Khung giờ hẹn: {{ order.khung_gio_giao }}</span>
                        </div>

                        <div class="flex items-start space-x-1.5 text-slate-700">
                            <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
                            <span class="font-medium leading-relaxed">{{ order.dia_chi_giao }}</span>
                        </div>

                        <div v-if="order.ghi_chu_giao_hang" class="rounded-xl border border-amber-200 bg-amber-50 p-2 text-[11px] text-amber-900">
                            <strong>Ghi chú giao:</strong> {{ order.ghi_chu_giao_hang }}
                        </div>
                    </div>

                    <!-- PAYMENT STRIP -->
                    <div
                        class="flex items-center justify-between rounded-2xl border p-3.5 text-xs"
                        :class="order.phuong_thuc_thanh_toan === 'cod' ? 'border-amber-200 bg-amber-50/70' : 'border-emerald-200 bg-emerald-50/70'"
                    >
                        <div>
                            <span class="block text-[11px] text-slate-500">Hình thức thanh toán:</span>
                            <strong class="font-bold" :class="order.phuong_thuc_thanh_toan === 'cod' ? 'text-amber-900' : 'text-emerald-900'">
                                {{ order.phuong_thuc_thanh_toan === 'cod' ? '💵 Thu tiền mặt khi giao (COD)' : '💳 Đã thanh toán online trước' }}
                            </strong>
                        </div>
                        <div class="text-right">
                            <span class="block text-[11px] text-slate-500">Số tiền cần thu:</span>
                            <span class="text-base font-black" :class="order.phuong_thuc_thanh_toan === 'cod' ? 'text-red-600' : 'text-emerald-700'">
                                {{ order.phuong_thuc_thanh_toan === 'cod' ? formatPrice(order.tong_thanhtoan) : '0đ (Đã trả)' }}
                            </span>
                        </div>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button
                            type="button"
                            @click="reportIssue(order.id, order.ma_don_hang)"
                            class="rounded-2xl bg-slate-100 px-4 py-3 text-xs font-bold text-slate-700 transition hover:bg-slate-200"
                        >
                            Chưa giao được / Hẹn lại
                        </button>
                        <button
                            type="button"
                            @click="completeDelivery(order.id, order.ma_don_hang)"
                            :disabled="isSubmitting === order.id"
                            class="flex cursor-pointer items-center justify-center space-x-1.5 rounded-2xl bg-emerald-600 px-4 py-3 text-xs font-black text-white shadow-md shadow-emerald-600/30 transition hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <Check class="h-4 w-4" />
                            <span>{{ isSubmitting === order.id ? 'Đang cập nhật...' : 'Đã Giao Thành Công' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 2: INTER-STORE TRANSFERS FOR TRUCKS -->
            <div v-if="activeTab === 'transfers'" class="space-y-4">
                <div
                    v-if="!transferTrips || transferTrips.length === 0"
                    class="rounded-3xl border border-slate-200 bg-white p-8 text-center text-slate-400"
                >
                    <Truck class="mx-auto mb-2 h-10 w-10 text-sky-500" />
                    <p class="text-xs">Chưa có chuyến xe tải điều chuyển liên chi nhánh nào được gán cho bạn.</p>
                </div>

                <div v-for="trip in transferTrips" :key="trip.id" class="space-y-3 rounded-3xl border border-sky-200 bg-white p-5">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-sm font-black text-sky-900">Phiếu chuyển: #{{ trip.ma_phieu }}</span>
                        <span
                            class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase"
                            :class="trip.trang_thai === 3 ? 'bg-emerald-100 text-emerald-800' : 'animate-pulse bg-amber-100 text-amber-800'"
                        >
                            {{ trip.trang_thai === 3 ? 'Đã giao tới kho đích' : 'Đang chở xe tải' }}
                        </span>
                    </div>

                    <div class="space-y-1.5 rounded-2xl bg-sky-50 p-3 text-xs text-sky-950">
                        <div class="flex justify-between">
                            <span>Từ kho xuất:</span>
                            <strong class="font-bold">{{ trip.cua_hang?.ten_cua_hang }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span>Đến kho đích:</span>
                            <strong class="font-bold text-emerald-700">{{ trip.cua_hang_nhan?.ten_cua_hang }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span>Tổng lượng hàng chở:</span>
                            <strong class="font-black">{{ trip.tong_so_luong }} phần</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: HISTORY -->
            <div v-if="activeTab === 'history'" class="space-y-3">
                <div v-if="historyDeliveries.length === 0" class="rounded-3xl border border-slate-200 bg-white p-8 text-center text-slate-400">
                    <Clock class="mx-auto mb-1 h-8 w-8 text-slate-300" />
                    <p class="text-xs">Chưa có lịch sử đơn hàng nào.</p>
                </div>

                <div
                    v-for="h in historyDeliveries"
                    :key="h.id"
                    class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 text-xs"
                >
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-mono font-bold text-slate-900">#{{ h.ma_don_hang }}</span>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-emerald-800"> Đã giao </span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-slate-500">{{ h.ten_nguoi_nhan }} • {{ h.dia_chi_giao }}</p>
                    </div>
                    <div class="text-right">
                        <span class="block font-black text-slate-900">{{ formatPrice(h.tong_thanhtoan) }}</span>
                        <span class="text-[10px] text-slate-400">{{
                            new Date(h.thoi_gian_giao_thanh_cong || h.updated_at).toLocaleDateString('vi-VN')
                        }}</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
