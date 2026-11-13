<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import {
    Truck,
    Bike,
    Phone,
    MapPin,
    CheckCircle2,
    Clock,
    AlertCircle,
    Package,
    Navigation,
    LogOut,
    Check,
    Layers,
    ArrowLeftRight,
} from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import ToastContainer from '@/components/ui/ToastContainer.vue';

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
        }
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
        }
    );
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-slate-900 pb-12">
        <Head title="Cổng Giao Hàng & Điều Phối Xe - FreshFood" />
        <ToastContainer />

        <!-- TOP APP BAR -->
        <header class="bg-slate-900 text-white px-4 py-3 sticky top-0 z-40 shadow-md">
            <div class="max-w-3xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div
                        class="w-10 h-10 rounded-2xl flex items-center justify-center font-black shadow-inner"
                        :class="driverInfo.loai_phuong_tien === 'xe_tai' ? 'bg-sky-500 text-white' : 'bg-emerald-500 text-white'"
                    >
                        <Truck v-if="driverInfo.loai_phuong_tien === 'xe_tai'" class="w-5 h-5" />
                        <Bike v-else class="w-5 h-5" />
                    </div>
                    <div>
                        <h1 class="text-sm font-bold flex items-center gap-1.5">
                            <span>{{ driverInfo.name }}</span>
                            <span class="text-[10px] bg-slate-800 px-2 py-0.5 rounded-full font-mono text-emerald-400">
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
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition flex items-center gap-1"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">Về web</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="max-w-3xl mx-auto w-full px-4 pt-4 space-y-4">
            <!-- TABS -->
            <div class="grid grid-cols-3 gap-2 bg-slate-200/80 p-1 rounded-2xl text-xs font-bold">
                <button
                    type="button"
                    @click="activeTab = 'active'"
                    class="py-2.5 rounded-xl transition flex items-center justify-center space-x-1.5"
                    :class="activeTab === 'active' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                >
                    <Package class="w-4 h-4 text-emerald-600" />
                    <span>Đơn Cần Giao ({{ activeDeliveries.length }})</span>
                </button>

                <button
                    v-if="driverInfo.loai_phuong_tien === 'xe_tai' || driverInfo.role === 'admin'"
                    type="button"
                    @click="activeTab = 'transfers'"
                    class="py-2.5 rounded-xl transition flex items-center justify-center space-x-1.5"
                    :class="activeTab === 'transfers' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                >
                    <ArrowLeftRight class="w-4 h-4 text-sky-600" />
                    <span>Chuyến Xe Tải ({{ transferTrips?.length || 0 }})</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'history'"
                    class="py-2.5 rounded-xl transition flex items-center justify-center space-x-1.5"
                    :class="activeTab === 'history' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                >
                    <Clock class="w-4 h-4 text-slate-500" />
                    <span>Lịch Sử ({{ historyDeliveries.length }})</span>
                </button>
            </div>

            <!-- TAB 1: ACTIVE DELIVERIES -->
            <div v-if="activeTab === 'active'" class="space-y-4">
                <div v-if="activeDeliveries.length === 0" class="bg-white rounded-3xl p-8 text-center border border-slate-200 text-slate-400 space-y-2">
                    <CheckCircle2 class="w-10 h-10 mx-auto text-emerald-500" />
                    <h3 class="font-bold text-slate-800 text-sm">Hiện không có đơn hàng nào cần giao!</h3>
                    <p class="text-xs">Bạn đang ở trạng thái sẵn sàng. Khi cửa hàng điều phối đơn mới, đơn sẽ hiển thị tại đây.</p>
                </div>

                <div
                    v-for="order in activeDeliveries"
                    :key="order.id"
                    class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-5 space-y-4"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-mono font-black text-slate-900 text-sm">#{{ order.ma_don_hang }}</span>
                                <span
                                    v-if="order.loai_phuong_tien_giao === 'xe_tai'"
                                    class="bg-sky-100 text-sky-800 text-[10px] font-black px-2 py-0.5 rounded-full flex items-center gap-1"
                                >
                                    <Truck class="w-3 h-3" /> Đơn Xe Tải
                                </span>
                                <span
                                    v-else
                                    class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded-full flex items-center gap-1"
                                >
                                    <Bike class="w-3 h-3" /> Đơn Xe Máy
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Xuất từ chi nhánh: <strong>{{ order.cua_hang?.ten_cua_hang }}</strong>
                            </p>
                        </div>

                        <span class="px-2.5 py-1 bg-amber-100 text-amber-900 font-bold text-xs rounded-full animate-pulse">
                            Đang đi giao
                        </span>
                    </div>

                    <!-- CUSTOMER INFO CARD -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 text-sm">{{ order.ten_nguoi_nhan }}</span>
                            <a
                                :href="`tel:${order.so_dien_thoai_nhan}`"
                                class="inline-flex items-center space-x-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-xl shadow-xs transition"
                            >
                                <Phone class="w-3.5 h-3.5" />
                                <span>Gọi: {{ order.so_dien_thoai_nhan }}</span>
                            </a>
                        </div>

                        <div class="flex items-start space-x-1.5 text-slate-700">
                            <MapPin class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                            <span class="font-medium leading-relaxed">{{ order.dia_chi_giao }}</span>
                        </div>

                        <div v-if="order.ghi_chu_giao_hang" class="p-2 bg-amber-50 rounded-xl text-amber-900 text-[11px] border border-amber-200">
                            <strong>Ghi chú giao:</strong> {{ order.ghi_chu_giao_hang }}
                        </div>
                    </div>

                    <!-- PAYMENT STRIP -->
                    <div class="p-3.5 rounded-2xl border flex items-center justify-between text-xs" :class="order.phuong_thuc_thanh_toan === 'cod' ? 'bg-amber-50/70 border-amber-200' : 'bg-emerald-50/70 border-emerald-200'">
                        <div>
                            <span class="text-slate-500 block text-[11px]">Hình thức thanh toán:</span>
                            <strong class="font-bold" :class="order.phuong_thuc_thanh_toan === 'cod' ? 'text-amber-900' : 'text-emerald-900'">
                                {{ order.phuong_thuc_thanh_toan === 'cod' ? '💵 Thu tiền mặt khi giao (COD)' : '💳 Đã thanh toán online trước' }}
                            </strong>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] text-slate-500 block">Số tiền cần thu:</span>
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
                            class="py-3 px-4 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition"
                        >
                            Chưa giao được / Hẹn lại
                        </button>
                        <button
                            type="button"
                            @click="completeDelivery(order.id, order.ma_don_hang)"
                            :disabled="isSubmitting === order.id"
                            class="py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-md shadow-emerald-600/30 transition flex items-center justify-center space-x-1.5 cursor-pointer disabled:opacity-50"
                        >
                            <Check class="w-4 h-4" />
                            <span>{{ isSubmitting === order.id ? 'Đang cập nhật...' : 'Đã Giao Thành Công' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 2: INTER-STORE TRANSFERS FOR TRUCKS -->
            <div v-if="activeTab === 'transfers'" class="space-y-4">
                <div v-if="!transferTrips || transferTrips.length === 0" class="bg-white rounded-3xl p-8 text-center border border-slate-200 text-slate-400">
                    <Truck class="w-10 h-10 mx-auto text-sky-500 mb-2" />
                    <p class="text-xs">Chưa có chuyến xe tải điều chuyển liên chi nhánh nào được gán cho bạn.</p>
                </div>

                <div
                    v-for="trip in transferTrips"
                    :key="trip.id"
                    class="bg-white rounded-3xl border border-sky-200 p-5 space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-mono font-black text-sky-900 text-sm">Phiếu chuyển: #{{ trip.ma_phieu }}</span>
                        <span
                            class="text-[10px] font-black px-2.5 py-1 rounded-full uppercase"
                            :class="trip.trang_thai === 3 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800 animate-pulse'"
                        >
                            {{ trip.trang_thai === 3 ? 'Đã giao tới kho đích' : 'Đang chở xe tải' }}
                        </span>
                    </div>

                    <div class="p-3 bg-sky-50 rounded-2xl text-xs space-y-1.5 text-sky-950">
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
                <div v-if="historyDeliveries.length === 0" class="bg-white rounded-3xl p-8 text-center border border-slate-200 text-slate-400">
                    <Clock class="w-8 h-8 mx-auto text-slate-300 mb-1" />
                    <p class="text-xs">Chưa có lịch sử đơn hàng nào.</p>
                </div>

                <div
                    v-for="h in historyDeliveries"
                    :key="h.id"
                    class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center justify-between text-xs"
                >
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-mono font-bold text-slate-900">#{{ h.ma_don_hang }}</span>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-black px-2 py-0.5 rounded-full">
                                Đã giao
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ h.ten_nguoi_nhan }} • {{ h.dia_chi_giao }}</p>
                    </div>
                    <div class="text-right">
                        <span class="font-black text-slate-900 block">{{ formatPrice(h.tong_thanhtoan) }}</span>
                        <span class="text-[10px] text-slate-400">{{ new Date(h.thoi_gian_giao_thanh_cong || h.updated_at).toLocaleDateString('vi-VN') }}</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

