<script setup lang="ts">
import { ref, computed } from 'vue';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    Search,
    FileOutput,
    Eye,
    Calendar,
    User,
    ArrowLeftRight,
    ShoppingBag,
    PackageCheck,
    Clock,
    CheckCircle2,
    Truck,
    Bike,
    MapPin,
    AlertCircle,
    Send,
    X,
} from 'lucide-vue-next';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

interface PhieuXuatItem {
    id: number;
    ma_phieu: string;
    loai_xuat: number;
    trang_thai: number;
    cua_hang_id: number;
    cua_hang_nhan_id?: number;
    cua_hang?: { id: number; ten_cua_hang: string };
    don_hang?: { id: number; ma_don_hang: string };
    cua_hang_nhan?: { id: number; ten_cua_hang: string };
    nguoi_giao_hang?: { id: number; name: string; loai_phuong_tien?: string; bien_so_xe?: string };
    nguoi_tao?: { id: number; name: string };
    tong_so_luong: number;
    tong_gia_tri: number;
    ly_do?: string;
    xuat_luc: string;
    chi_tiets: Array<any>;
}

interface PendingOrderItem {
    id: number;
    ma_don_hang: string;
    ten_nguoi_nhan: string;
    so_dien_thoai_nhan: string;
    dia_chi_giao: string;
    tong_thanhtoan: number;
    trang_thai_don_hang: string;
    created_at: string;
    user?: { id: number; name: string; phone?: string };
    nguoi_giao_hang?: { id: number; name: string; loai_phuong_tien?: string; bien_so_xe?: string };
    loai_phuong_tien_giao?: string;
    bien_so_xe_giao?: string;
    chi_tiet_don_hangs?: Array<{
        id: number;
        san_pham_id: number;
        so_luong: number;
        don_gia: number;
        thanh_tien: number;
        san_pham?: { id: number; ten_san_pham: string; don_vi_tinh: string };
    }>;
}

const props = withDefaults(
    defineProps<{
        stores: Array<any>;
        currentStore: any;
        phieuXuats: Array<PhieuXuatItem>;
        pendingOrders?: Array<PendingOrderItem>;
        readyOrders?: Array<any>;
        drivers?: Array<any>;
    }>(),
    {
        pendingOrders: () => [],
        readyOrders: () => [],
        drivers: () => [],
    }
);

const toast = useToast();
const activeTab = ref<'pending' | 'dispatch' | 'history'>('pending');
const searchQuery = ref('');
const isFulfilling = ref<number | null>(null);

// Dispatch modal state
const isDispatchModalOpen = ref(false);
const selectedOrderForDispatch = ref<any>(null);
const dispatchForm = ref({
    nguoi_giao_hang_id: '' as number | '',
    loai_phuong_tien_giao: 'xe_may' as 'xe_may' | 'xe_tai',
    bien_so_xe_giao: '',
    ghi_chu_giao_hang: '',
});
const isSubmittingDispatch = ref(false);

function openDispatchModal(order: any) {
    selectedOrderForDispatch.value = order;

    // Check bulk order
    const totalQty = order.chi_tiet_don_hangs?.reduce((sum: number, it: any) => sum + Number(it.so_luong || 0), 0) || 0;
    const isBulk = totalQty >= 15 || Number(order.tong_thanhtoan) >= 2000000;

    const preferredType = isBulk ? 'xe_tai' : 'xe_may';
    const suggestedDriver = props.drivers.find(d => d.loai_phuong_tien === preferredType && d.trang_thai_giao === 'san_sang')
        || props.drivers.find(d => d.loai_phuong_tien === preferredType)
        || props.drivers[0];

    dispatchForm.value = {
        nguoi_giao_hang_id: suggestedDriver?.id || '',
        loai_phuong_tien_giao: suggestedDriver?.loai_phuong_tien || preferredType,
        bien_so_xe_giao: suggestedDriver?.bien_so_xe || '',
        ghi_chu_giao_hang: isBulk ? 'Đơn hàng số lượng lớn - Đã điều phối Xe Tải giao tận nơi' : '',
    };

    isDispatchModalOpen.value = true;
}

function onDriverSelected() {
    const driver = props.drivers.find(d => d.id === Number(dispatchForm.value.nguoi_giao_hang_id));
    if (driver) {
        dispatchForm.value.loai_phuong_tien_giao = driver.loai_phuong_tien;
        dispatchForm.value.bien_so_xe_giao = driver.bien_so_xe || '';
    }
}

function submitDispatch() {
    if (!selectedOrderForDispatch.value || !dispatchForm.value.nguoi_giao_hang_id) {
        toast.warning('Vui lòng chọn nhân viên giao hàng.');
        return;
    }

    isSubmittingDispatch.value = true;
    router.post(
        route('store.delivery.assign', selectedOrderForDispatch.value.id),
        dispatchForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isDispatchModalOpen.value = false;
                toast.success('Đã điều phối nhân viên giao hàng thành công!');
            },
            onError: () => {
                toast.error('Có lỗi xảy ra khi điều phối.');
            },
            onFinish: () => {
                isSubmittingDispatch.value = false;
            },
        }
    );
}

const filteredPhieuXuats = computed(() => {
    return props.phieuXuats.filter((p) => {
        const q = searchQuery.value.toLowerCase().trim();
        return (
            !q ||
            p.ma_phieu.toLowerCase().includes(q) ||
            p.don_hang?.ma_don_hang?.toLowerCase().includes(q)
        );
    });
});

const filteredPendingOrders = computed(() => {
    return props.pendingOrders.filter((o) => {
        const q = searchQuery.value.toLowerCase().trim();
        return (
            !q ||
            o.ma_don_hang.toLowerCase().includes(q) ||
            o.ten_nguoi_nhan.toLowerCase().includes(q) ||
            o.so_dien_thoai_nhan.includes(q)
        );
    });
});

const filteredReadyOrders = computed(() => {
    return props.readyOrders.filter((o: any) => {
        const q = searchQuery.value.toLowerCase().trim();
        return (
            !q ||
            o.ma_don_hang.toLowerCase().includes(q) ||
            o.ten_nguoi_nhan.toLowerCase().includes(q) ||
            o.so_dien_thoai_nhan.includes(q)
        );
    });
});

function getLoaiXuatBadge(type: number): { text: string; class: string } {
    switch (type) {
        case 1:
            return { text: 'Xuất Bán Hàng', class: 'bg-emerald-100 text-emerald-800' };
        case 2:
            return { text: 'Xuất Chuyển Kho', class: 'bg-sky-100 text-sky-800' };
        case 3:
            return { text: 'Xuất Hủy Hàng Hỏng', class: 'bg-red-100 text-red-800' };
        default:
            return { text: 'Khác', class: 'bg-slate-100 text-slate-800' };
    }
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}

function formatDate(dateStr: string): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('vi-VN');
}

function fulfillOrder(orderId: number, maDon: string) {
    if (!confirm(`Xác nhận nhặt hàng & xuất kho FEFO cho đơn #${maDon} để chuẩn bị giao shipper?`)) {
        return;
    }

    isFulfilling.value = orderId;
    router.post(
        route('store.outbound.fulfill', orderId),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isFulfilling.value = null;
            },
        }
    );
}
</script>

<template>
    <StoreOpsLayout title="Quản Lý Phiếu Xuất & Đóng Gói Đơn Hàng" :stores="stores" :currentStore="currentStore">
        <div class="space-y-6">
            <!-- TOP BAR -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-base font-black text-slate-900 flex items-center space-x-2">
                        <FileOutput class="w-5 h-5 text-emerald-600" />
                        <span>Quản Lý Xuất Kho & Đóng Gói Đơn Hàng</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Chuẩn bị hàng giao shipper (FEFO), xuất bán lẻ, xuất hủy và xuất điều chuyển liên chi nhánh.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('store.transfer.create')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition-all shrink-0"
                    >
                        <ArrowLeftRight class="w-4 h-4 text-emerald-400" />
                        <span>Lập Phiếu Điều Chuyển</span>
                    </Link>
                </div>
            </div>

            <!-- TABS CONTROLLER -->
            <div class="flex items-center space-x-2 border-b border-slate-200 pb-1">
                <button
                    type="button"
                    @click="activeTab = 'pending'"
                    :class="[
                        'px-4 py-2.5 rounded-2xl font-bold text-xs flex items-center gap-2 transition-all cursor-pointer',
                        activeTab === 'pending'
                            ? 'bg-emerald-600 text-white shadow-sm'
                            : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'
                    ]"
                >
                    <ShoppingBag class="w-4 h-4" />
                    <span>Đơn Hàng Cần Đóng Gói (Chờ Xuất)</span>
                    <span
                        :class="[
                            'px-2 py-0.5 rounded-full text-[10px] font-black',
                            activeTab === 'pending' ? 'bg-white text-emerald-700' : 'bg-emerald-100 text-emerald-800'
                        ]"
                    >
                        {{ pendingOrders.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'dispatch'"
                    :class="[
                        'px-4 py-2.5 rounded-2xl font-bold text-xs flex items-center gap-2 transition-all cursor-pointer',
                        activeTab === 'dispatch'
                            ? 'bg-sky-600 text-white shadow-sm'
                            : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'
                    ]"
                >
                    <Truck class="w-4 h-4" />
                    <span>Đơn Sẵn Sàng Giao & Điều Phối Xe</span>
                    <span
                        :class="[
                            'px-2 py-0.5 rounded-full text-[10px] font-black',
                            activeTab === 'dispatch' ? 'bg-white text-sky-800' : 'bg-sky-100 text-sky-800'
                        ]"
                    >
                        {{ readyOrders.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'history'"
                    :class="[
                        'px-4 py-2.5 rounded-2xl font-bold text-xs flex items-center gap-2 transition-all cursor-pointer',
                        activeTab === 'history'
                            ? 'bg-emerald-600 text-white shadow-sm'
                            : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'
                    ]"
                >
                    <FileOutput class="w-4 h-4" />
                    <span>Lịch Sử Phiếu Xuất Kho (Đã Xuất)</span>
                    <span
                        :class="[
                            'px-2 py-0.5 rounded-full text-[10px] font-black',
                            activeTab === 'history' ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-600'
                        ]"
                    >
                        {{ phieuXuats.length }}
                    </span>
                </button>
            </div>

            <!-- SEARCH FILTER -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="activeTab === 'pending' ? 'Tìm mã đơn, tên khách, SĐT...' : 'Tìm mã phiếu xuất, mã đơn...'"
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                        />
                    </div>
                    <span class="text-xs text-slate-400 font-medium">
                        {{ activeTab === 'pending' ? `Chờ xuất: ${filteredPendingOrders.length} đơn` : `Tổng: ${filteredPhieuXuats.length} phiếu` }}
                    </span>
                </div>

                <!-- ============================================== -->
                <!-- TAB 1: PENDING ORDERS TABLE                   -->
                <!-- ============================================== -->
                <div v-if="activeTab === 'pending'" class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Mã Đơn Hàng</th>
                                <th class="py-3 px-4">Khách Hàng & Nơi Giao</th>
                                <th class="py-3 px-4">Mặt Hàng Cần Soạn</th>
                                <th class="py-3 px-4">Thời Gian Đặt</th>
                                <th class="py-3 px-4 text-right">Tổng Tiền</th>
                                <th class="py-3 px-4 text-center">Thao Tác Soạn Hàng</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredPendingOrders.length === 0">
                                <td colspan="6" class="text-center py-12 text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <CheckCircle2 class="w-8 h-8 text-emerald-500/50" />
                                        <p class="font-medium text-slate-600">Hiện tại không có đơn hàng nào chờ soạn xuất kho tại chi nhánh này.</p>
                                        <p class="text-[11px] text-slate-400">Tất cả đơn hàng mới phát sinh của chi nhánh sẽ tự động hiển thị tại đây.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="order in filteredPendingOrders" :key="order.id" class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-emerald-700">
                                    <div>#{{ order.ma_don_hang }}</div>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-50 text-purple-700 border border-purple-200">
                                        {{ order.trang_thai_don_hang === 'cho_xac_nhan' ? 'Chờ xác nhận' : 'Đang chuẩn bị' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ order.ten_nguoi_nhan }}</div>
                                    <div class="text-slate-500 font-medium text-[11px]">{{ order.so_dien_thoai_nhan }}</div>
                                    <div class="text-slate-400 text-[11px] truncate max-w-xs flex items-center gap-1 mt-0.5" :title="order.dia_chi_giao">
                                        <MapPin class="w-3 h-3 text-slate-400 shrink-0" />
                                        <span>{{ order.dia_chi_giao }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div
                                            v-for="ct in (order.chi_tiet_don_hangs || [])"
                                            :key="ct.id"
                                            class="flex items-center gap-1 text-[11px] text-slate-700"
                                        >
                                            <span class="font-medium">&bull; {{ ct.san_pham?.ten_san_pham || 'Sản phẩm' }}:</span>
                                            <b class="text-slate-900">{{ ct.so_luong }} {{ ct.san_pham?.don_vi_tinh || 'phần' }}</b>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500">
                                    <div class="flex items-center gap-1">
                                        <Clock class="w-3 h-3 text-slate-400" />
                                        <span>{{ formatDate(order.created_at) }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-extrabold text-slate-900 text-sm">
                                    {{ formatPrice(order.tong_thanhtoan) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        type="button"
                                        @click="fulfillOrder(order.id, order.ma_don_hang)"
                                        :disabled="isFulfilling === order.id"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all cursor-pointer disabled:bg-slate-300"
                                    >
                                        <PackageCheck class="w-4 h-4" />
                                        <span>{{ isFulfilling === order.id ? 'Đang xuất FEFO...' : 'Soạn Hàng & Xuất Kho' }}</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================================== -->
                <!-- TAB 2: READY ORDERS & DELIVERY DISPATCH        -->
                <!-- ============================================== -->
                <div v-else-if="activeTab === 'dispatch'" class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Mã Đơn & Gợi Ý Xe</th>
                                <th class="py-3 px-4">Khách Hàng & Nơi Giao</th>
                                <th class="py-3 px-4">Sản Phẩm Trong Đơn</th>
                                <th class="py-3 px-4">Phương Tiện & Tài Xế</th>
                                <th class="py-3 px-4 text-right">Tổng Tiền</th>
                                <th class="py-3 px-4 text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredReadyOrders.length === 0">
                                <td colspan="6" class="text-center py-12 text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <Truck class="w-8 h-8 text-sky-500/50" />
                                        <p class="font-medium text-slate-600">Không có đơn hàng nào đang chờ điều phối giao hàng.</p>
                                        <p class="text-[11px] text-slate-400">Sau khi soạn hàng FEFO xong, đơn hàng sẽ chuyển sang tab này để gán xe tải hoặc xe máy.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="order in filteredReadyOrders" :key="order.id" class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    <div class="text-emerald-700">#{{ order.ma_don_hang }}</div>
                                    <div class="mt-1">
                                        <span
                                            v-if="(order.chi_tiet_don_hangs?.reduce((s: number, i: any) => s + Number(i.so_luong || 0), 0) || 0) >= 15 || Number(order.tong_thanhtoan) >= 2000000"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300"
                                        >
                                            <Truck class="w-3 h-3 text-amber-700" />
                                            <span>Khách đặt lớn: Xe Tải 🚚</span>
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300"
                                        >
                                            <Bike class="w-3 h-3 text-emerald-700" />
                                            <span>Đơn lẻ: Xe Máy 🏍️</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ order.ten_nguoi_nhan }}</div>
                                    <div class="text-slate-500 text-[11px]">{{ order.so_dien_thoai_nhan }}</div>
                                    <div class="text-slate-400 text-[11px] truncate max-w-xs flex items-center gap-1 mt-0.5" :title="order.dia_chi_giao">
                                        <MapPin class="w-3 h-3 text-slate-400 shrink-0" />
                                        <span>{{ order.dia_chi_giao }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-[11px] text-slate-700 font-bold">
                                        Tổng: {{ order.chi_tiet_don_hangs?.reduce((s: number, i: any) => s + Number(i.so_luong || 0), 0) || 0 }} phần
                                    </div>
                                    <div class="text-[10px] text-slate-400 line-clamp-1">
                                        {{ order.chi_tiet_don_hangs?.map((c: any) => `${c.san_pham?.ten_san_pham} (x${c.so_luong})`).join(', ') }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div v-if="order.nguoi_giao_hang" class="space-y-0.5">
                                        <div class="flex items-center space-x-1 text-slate-900 font-bold text-xs">
                                            <Truck v-if="order.loai_phuong_tien_giao === 'xe_tai'" class="w-3.5 h-3.5 text-sky-600" />
                                            <Bike v-else class="w-3.5 h-3.5 text-emerald-600" />
                                            <span>{{ order.nguoi_giao_hang.name }}</span>
                                        </div>
                                        <div class="text-[10px] font-mono font-bold text-slate-500">
                                            BKS: {{ order.bien_so_xe_giao || order.nguoi_giao_hang.bien_so_xe || '—' }}
                                        </div>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider" :class="order.trang_thai_don_hang === 'dang_giao' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600'">
                                            {{ order.trang_thai_don_hang === 'dang_giao' ? 'Đang đi giao' : 'Đã chuẩn bị' }}
                                        </span>
                                    </div>
                                    <span v-else class="text-[11px] text-amber-700 font-bold bg-amber-50 px-2 py-1 rounded-lg border border-amber-200">
                                        Chưa phân công xe
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-extrabold text-slate-900 text-sm">
                                    {{ formatPrice(order.tong_thanhtoan) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        type="button"
                                        @click="openDispatchModal(order)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-xs shadow-xs transition cursor-pointer"
                                        :class="order.nguoi_giao_hang ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-sky-600 hover:bg-sky-700 text-white shadow-sky-600/20'"
                                    >
                                        <Truck class="w-3.5 h-3.5" />
                                        <span>{{ order.nguoi_giao_hang ? 'Đổi Tài Xế' : 'Phân Công Xe' }}</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================================== -->
                <!-- TAB 3: ISSUED OUTBOUND TICKETS (HISTORY)       -->
                <!-- ============================================== -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Mã Phiếu</th>
                                <th class="py-3 px-4">Loại Xuất Kho</th>
                                <th class="py-3 px-4">Đơn Hàng Gốc</th>
                                <th class="py-3 px-4">Thời Gian Xuất</th>
                                <th class="py-3 px-4">Người Thực Hiện</th>
                                <th class="py-3 px-4 text-right">Tổng Lượng</th>
                                <th class="py-3 px-4 text-right">Tổng Giá Trị</th>
                                <th class="py-3 px-4 text-center">Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredPhieuXuats.length === 0">
                                <td colspan="8" class="text-center py-10 text-slate-400">
                                    Chưa có phiếu xuất kho nào.
                                </td>
                            </tr>
                            <tr v-for="px in filteredPhieuXuats" :key="px.id" class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    {{ px.ma_phieu }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center space-x-1">
                                        <span :class="getLoaiXuatBadge(px.loai_xuat).class" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
                                            {{ getLoaiXuatBadge(px.loai_xuat).text }}
                                        </span>
                                        <template v-if="px.loai_xuat === 2">
                                            <span v-if="px.trang_thai === 3" class="text-[9px] bg-emerald-100 text-emerald-800 font-bold px-1.5 py-0.5 rounded">
                                                Đã nhận
                                            </span>
                                            <span v-else-if="Number(px.cua_hang_nhan_id) === Number(currentStore?.id)" class="text-[9px] bg-sky-100 text-sky-800 font-black px-1.5 py-0.5 rounded animate-pulse">
                                                Cần nhận kho
                                            </span>
                                            <span v-else class="text-[9px] bg-amber-100 text-amber-800 font-bold px-1.5 py-0.5 rounded">
                                                Đang chuyển
                                            </span>
                                        </template>
                                    </div>
                                    <div v-if="px.loai_xuat === 2" class="text-[11px] text-sky-700 font-bold mt-1">
                                        <span v-if="Number(px.cua_hang_nhan_id) === Number(currentStore?.id)">
                                            &larr; Từ: {{ px.cua_hang?.ten_cua_hang || 'Chi nhánh khác' }}
                                        </span>
                                        <span v-else>
                                            &rarr; Sang: {{ px.cua_hang_nhan?.ten_cua_hang || 'Chi nhánh khác' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-600 font-bold">
                                    {{ px.don_hang?.ma_don_hang || '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-500">
                                    {{ formatDate(px.xuat_luc) }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ px.nguoi_tao?.name || 'Hệ thống' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-slate-800">
                                    {{ px.tong_so_luong }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-emerald-700">
                                    {{ formatPrice(px.tong_gia_tri) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <Link
                                        :href="route('store.outbound.show', px.id)"
                                        class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 font-bold transition"
                                    >
                                        <Eye class="w-3.5 h-3.5" />
                                        <span>Xem & In</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DISPATCH MODAL -->
        <div
            v-if="isDispatchModalOpen && selectedOrderForDispatch"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-150"
        >
            <div
                class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 space-y-5 animate-in zoom-in-95 duration-150"
            >
                <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-sky-50 text-sky-600">
                                <Truck class="w-5 h-5" />
                            </span>
                            <div>
                                <h3 class="text-base font-black text-slate-900">
                                    Điều Phối Xe & Giao Hàng
                                </h3>
                                <p class="text-xs text-slate-500 font-mono">
                                    Đơn #{{ selectedOrderForDispatch.ma_don_hang }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isDispatchModalOpen = false"
                        class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition cursor-pointer"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- ORDER INFO & RECOMMENDATION -->
                <div class="bg-slate-50 rounded-2xl p-4 text-xs space-y-2 border border-slate-100">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Khách nhận:</span>
                        <span class="font-bold text-slate-800">{{ selectedOrderForDispatch.ten_nguoi_nhan }} ({{ selectedOrderForDispatch.so_dien_thoai_nhan }})</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Địa chỉ:</span>
                        <span class="font-bold text-slate-800 text-right max-w-[260px] truncate" :title="selectedOrderForDispatch.dia_chi_giao">{{ selectedOrderForDispatch.dia_chi_giao }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tổng tiền:</span>
                        <span class="font-black text-emerald-700">{{ formatPrice(selectedOrderForDispatch.tong_thanhtoan) }}</span>
                    </div>

                    <!-- Recommendation Callout -->
                    <div
                        v-if="(selectedOrderForDispatch.chi_tiet_don_hangs?.reduce((s: number, i: any) => s + Number(i.so_luong || 0), 0) || 0) >= 15 || Number(selectedOrderForDispatch.tong_thanhtoan) >= 2000000"
                        class="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-start gap-2"
                    >
                        <Truck class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                        <div>
                            <div class="font-black text-[11px] uppercase tracking-wide">Khuyến nghị: Xe Tải 🚚</div>
                            <div class="text-[11px] text-amber-700 mt-0.5 leading-relaxed">
                                Đơn hàng giá trị hoặc số lượng lớn. Đề xuất điều phối xe tải để đảm bảo bảo quản và giao nhanh.
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="mt-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-2"
                    >
                        <Bike class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                        <div>
                            <div class="font-black text-[11px] uppercase tracking-wide">Khuyến nghị: Xe Máy 🏍️</div>
                            <div class="text-[11px] text-emerald-700 mt-0.5 leading-relaxed">
                                Đơn hàng tiêu chuẩn, ưu tiên phân công shipper xe máy linh hoạt.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DISPATCH FORM -->
                <form @submit.prevent="submitDispatch" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">
                            Chọn Tài Xế / Shipper <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="dispatchForm.nguoi_giao_hang_id"
                            @change="onDriverSelected"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium bg-white"
                            required
                        >
                            <option value="" disabled>-- Chọn tài xế điều phối --</option>
                            <option
                                v-for="d in drivers"
                                :key="d.id"
                                :value="d.id"
                            >
                                {{ d.loai_phuong_tien === 'xe_tai' ? '🚚 Xe tải' : '🏍️ Xe máy' }} - {{ d.name }} ({{ d.so_dien_thoai || 'Không SĐT' }}) - {{ d.bien_so_xe || 'Chưa BKS' }} [{{ d.trang_thai_giao === 'san_sang' ? 'Sẵn sàng' : 'Đang giao' }}]
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Loại Phương Tiện</label>
                            <select
                                v-model="dispatchForm.loai_phuong_tien_giao"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium bg-white"
                            >
                                <option value="xe_may">🏍️ Xe Máy</option>
                                <option value="xe_tai">🚚 Xe Tải</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1.5">Biển Số Xe</label>
                            <input
                                v-model="dispatchForm.bien_so_xe_giao"
                                type="text"
                                placeholder="VD: 51C-889.99"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium font-mono"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">Ghi Chú Giao Hàng</label>
                        <textarea
                            v-model="dispatchForm.ghi_chu_giao_hang"
                            rows="2"
                            placeholder="Ghi chú cho tài xế (dễ vỡ, thùng giữ nhiệt, giao giờ hành chính...)"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isDispatchModalOpen = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-100 transition cursor-pointer"
                        >
                            Hủy Bỏ
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmittingDispatch"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 disabled:opacity-50 text-white font-black shadow-md shadow-sky-600/20 transition cursor-pointer"
                        >
                            <Send class="w-4 h-4" />
                            <span>{{ isSubmittingDispatch ? 'Đang Xử Lý...' : 'Xác Nhận Xuất Xe Giao Hàng' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </StoreOpsLayout>
</template>
