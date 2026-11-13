<script setup lang="ts">
import { ref, computed } from 'vue';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    AlertTriangle,
    Clock,
    Tag,
    Trash2,
    Calendar,
    CheckCircle2,
    X,
    Flame,
    Search,
} from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';

interface NearExpiryLot {
    id: number;
    ma_lo: string;
    san_pham_id: number;
    ten_san_pham: string;
    danh_muc: string;
    don_vi_tinh: string;
    gia_ban_goc: number;
    so_luong_con_lai: number;
    ngay_san_xuat: string | null;
    ngay_het_han: string | null;
    so_ngay_con_lai: number;
    muc_do_canh_bao: 'expired' | 'critical' | 'warning' | 'safe';
}

const props = defineProps<{
    stores: Array<any>;
    currentStore: any;
    nearExpiryLots: Array<NearExpiryLot>;
}>();

const filterLevel = ref<'all' | 'critical' | 'warning' | 'expired'>('all');
const searchQuery = ref('');

const page = usePage();
const isAuthorizedManager = computed(() => {
    const role = (page.props.auth?.user as any)?.role;
    return role === 'admin' || role === 'quan_ly';
});

const selectedLotForDiscount = ref<NearExpiryLot | null>(null);
const discountPriceInput = ref<number | null>(null);

const selectedLotForDiscard = ref<NearExpiryLot | null>(null);
const discardQuantityInput = ref<number | null>(null);
const discardReasonInput = ref('Quá hạn sử dụng / Hư hỏng dập nát');

const filteredLots = computed(() => {
    return props.nearExpiryLots.filter((lot) => {
        const matchesLevel = filterLevel.value === 'all' || lot.muc_do_canh_bao === filterLevel.value;
        const q = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !q || lot.ten_san_pham.toLowerCase().includes(q) || lot.ma_lo.toLowerCase().includes(q);
        return matchesLevel && matchesSearch;
    });
});

const criticalCount = computed(() => props.nearExpiryLots.filter((l) => l.muc_do_canh_bao === 'critical').length);
const warningCount = computed(() => props.nearExpiryLots.filter((l) => l.muc_do_canh_bao === 'warning').length);
const expiredCount = computed(() => props.nearExpiryLots.filter((l) => l.muc_do_canh_bao === 'expired').length);

function openDiscountModal(lot: NearExpiryLot) {
    selectedLotForDiscount.value = lot;
    // Gợi ý giảm 50%
    discountPriceInput.value = Math.round(lot.gia_ban_goc * 0.5);
}

function submitDiscount() {
    if (!selectedLotForDiscount.value || !discountPriceInput.value) return;

    router.post(route('store.batches.discount'), {
        san_pham_id: selectedLotForDiscount.value.san_pham_id,
        gia_giam: discountPriceInput.value,
        store_id: props.currentStore?.id,
    }, {
        onSuccess: () => {
            selectedLotForDiscount.value = null;
        },
    });
}

function openDiscardModal(lot: NearExpiryLot) {
    selectedLotForDiscard.value = lot;
    discardQuantityInput.value = lot.so_luong_con_lai;
}

function submitDiscard() {
    if (!selectedLotForDiscard.value || !discardQuantityInput.value) return;

    router.post(route('store.batches.discard'), {
        lo_hang_id: selectedLotForDiscard.value.id,
        so_luong: discardQuantityInput.value,
        ly_do: discardReasonInput.value,
        store_id: props.currentStore?.id,
    }, {
        onSuccess: () => {
            selectedLotForDiscard.value = null;
        },
    });
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
}
</script>

<template>
    <StoreOpsLayout title="Cảnh Báo Cận Date & Xả Hàng" :stores="stores" :currentStore="currentStore">
        <div class="space-y-6">
            <!-- HEADER INFO BANNER -->
            <div class="bg-gradient-to-r from-red-600 via-amber-600 to-amber-700 rounded-3xl p-6 text-white shadow-lg flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <div class="flex items-center space-x-2">
                        <Flame class="w-6 h-6 text-amber-300" />
                        <h2 class="text-xl font-black tracking-wide">TRUNG TÂM KIỂM SOÁT HẠN SỬ DỤNG (FEFO)</h2>
                    </div>
                    <p class="text-xs text-amber-100 mt-1 max-w-2xl">
                        Tự động rà soát hạn dùng của từng lô thực phẩm tươi sống tại chi nhánh. Cho phép xả hàng giảm giá 50-70% trong ngày hoặc xuất hủy ngay trước khi hỏng.
                    </p>
                </div>
            </div>

            <!-- LEVEL COUNTERS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div
                    @click="filterLevel = 'critical'"
                    :class="filterLevel === 'critical' ? 'ring-2 ring-red-500 bg-red-50/80' : 'bg-white'"
                    class="p-5 rounded-3xl border border-red-200/80 shadow-sm cursor-pointer transition flex items-center space-x-4"
                >
                    <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-red-700 uppercase tracking-wider">Báo Động Đỏ (≤ 1 Ngày)</p>
                        <p class="text-2xl font-black text-red-700 mt-0.5">{{ criticalCount }} Lô hàng</p>
                    </div>
                </div>

                <div
                    @click="filterLevel = 'warning'"
                    :class="filterLevel === 'warning' ? 'ring-2 ring-amber-500 bg-amber-50/80' : 'bg-white'"
                    class="p-5 rounded-3xl border border-amber-200/80 shadow-sm cursor-pointer transition flex items-center space-x-4"
                >
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center">
                        <Clock class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">Cảnh Báo Vàng (2 - 3 Ngày)</p>
                        <p class="text-2xl font-black text-amber-700 mt-0.5">{{ warningCount }} Lô hàng</p>
                    </div>
                </div>

                <div
                    @click="filterLevel = 'expired'"
                    :class="filterLevel === 'expired' ? 'ring-2 ring-slate-800 bg-slate-100' : 'bg-white'"
                    class="p-5 rounded-3xl border border-slate-200/80 shadow-sm cursor-pointer transition flex items-center space-x-4"
                >
                    <div class="w-12 h-12 rounded-2xl bg-slate-200 text-slate-700 flex items-center justify-center">
                        <Trash2 class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">Đã Quá Hạn Sử Dụng</p>
                        <p class="text-2xl font-black text-slate-900 mt-0.5">{{ expiredCount }} Lô hàng</p>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <!-- CONTROLS -->
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row gap-3 justify-between items-center">
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm kiếm theo mã lô, tên sản phẩm..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 outline-none"
                        />
                    </div>

                    <div class="flex items-center space-x-2 text-xs">
                        <button
                            @click="filterLevel = 'all'"
                            :class="filterLevel === 'all' ? 'bg-slate-900 text-white font-bold' : 'bg-slate-100 text-slate-600'"
                            class="px-3.5 py-1.5 rounded-xl transition"
                        >
                            Xem Tất Cả ({{ nearExpiryLots.length }})
                        </button>
                    </div>
                </div>

                <!-- TABLE CONTENT -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Lô Hàng / Sản Phẩm</th>
                                <th class="py-3 px-4">Hạn Sử Dụng</th>
                                <th class="py-3 px-4 text-center">Tình Trạng HSD</th>
                                <th class="py-3 px-4 text-right">Tồn Còn Lại</th>
                                <th class="py-3 px-4 text-right">Giá Gốc</th>
                                <th class="py-3 px-4 text-right">Hành Động Xử Lý</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredLots.length === 0">
                                <td colspan="6" class="text-center py-10 text-slate-400">
                                    Không có lô hàng nào trong danh mục cảnh báo này.
                                </td>
                            </tr>
                            <tr v-for="lot in filteredLots" :key="lot.id" class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900">{{ lot.ten_san_pham }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">Mã lô: {{ lot.ma_lo }} | {{ lot.danh_muc }}</p>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 font-medium">
                                    {{ lot.ngay_het_han }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="lot.muc_do_canh_bao === 'expired'"
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-900 text-white"
                                    >
                                        Đã hết hạn
                                    </span>
                                    <span
                                        v-else-if="lot.muc_do_canh_bao === 'critical'"
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-red-100 text-red-700 animate-pulse"
                                    >
                                        Còn {{ lot.so_ngay_con_lai }} ngày (Đỏ)
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800"
                                    >
                                        Còn {{ lot.so_ngay_con_lai }} ngày (Vàng)
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-slate-900">
                                    {{ lot.so_luong_con_lai }} {{ lot.don_vi_tinh }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-600 font-bold">
                                    {{ formatPrice(lot.gia_ban_goc) }}
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                    <!-- BUTTON: ÁP GIÁ XẢ HÀNG -->
                                    <button
                                        v-if="lot.muc_do_canh_bao !== 'expired'"
                                        @click="openDiscountModal(lot)"
                                        class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs inline-flex items-center space-x-1 shadow-sm transition"
                                    >
                                        <Tag class="w-3.5 h-3.5" />
                                        <span>Xả Hàng</span>
                                    </button>

                                    <!-- BUTTON: XUẤT HỦY (QUẢN LÝ / ADMIN) -->
                                    <button
                                        v-if="isAuthorizedManager"
                                        @click="openDiscardModal(lot)"
                                        class="px-3 py-1.5 rounded-xl bg-red-100 hover:bg-red-600 hover:text-white text-red-700 font-bold text-xs inline-flex items-center space-x-1 transition"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                        <span>Xuất Hủy</span>
                                    </button>
                                    <span v-else class="text-[11px] text-slate-400 italic">
                                        (Cần Quản lý duyệt hủy)
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DISCOUNT MODAL -->
        <div v-if="selectedLotForDiscount" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center space-x-2">
                        <Tag class="w-5 h-5 text-amber-500" />
                        <h3 class="font-black text-slate-900 text-base">Áp Dụng Giá Xả Hàng Cận Date</h3>
                    </div>
                    <button @click="selectedLotForDiscount = null" class="text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="text-xs space-y-2 bg-slate-50 p-3 rounded-2xl">
                    <p class="font-bold text-slate-900">{{ selectedLotForDiscount.ten_san_pham }}</p>
                    <p class="text-slate-500">Mã lô: <strong class="text-slate-700">{{ selectedLotForDiscount.ma_lo }}</strong> | HSD: {{ selectedLotForDiscount.ngay_het_han }}</p>
                    <p class="text-slate-500">Giá bán gốc: <strong class="text-slate-700">{{ formatPrice(selectedLotForDiscount.gia_ban_goc) }}</strong></p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Giá khuyến mãi xả hàng mới (VNĐ):</label>
                    <input
                        v-model.number="discountPriceInput"
                        type="number"
                        placeholder="Nhập giá giảm..."
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-black text-emerald-700 focus:ring-2 focus:ring-amber-500 outline-none"
                    />
                    <p class="text-[11px] text-slate-400 italic">Giá giảm này sẽ hiển thị nổi bật trên Storefront của chi nhánh để khách mua nhanh.</p>
                </div>

                <div class="pt-2 flex space-x-2">
                    <button
                        @click="selectedLotForDiscount = null"
                        class="flex-1 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50"
                    >
                        Hủy
                    </button>
                    <button
                        @click="submitDiscount"
                        class="flex-1 py-2 rounded-xl bg-amber-500 text-white text-xs font-bold hover:bg-amber-600 transition"
                    >
                        Xác Nhận Giảm Giá
                    </button>
                </div>
            </div>
        </div>

        <!-- DISCARD MODAL -->
        <div v-if="selectedLotForDiscard" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center space-x-2">
                        <Trash2 class="w-5 h-5 text-red-600" />
                        <h3 class="font-black text-slate-900 text-base">Xuất Hủy Hàng Quá Date / Hỏng</h3>
                    </div>
                    <button @click="selectedLotForDiscard = null" class="text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="text-xs space-y-2 bg-red-50/50 p-3 rounded-2xl border border-red-100">
                    <p class="font-bold text-slate-900">{{ selectedLotForDiscard.ten_san_pham }}</p>
                    <p class="text-slate-500">Mã lô: <strong class="text-slate-700">{{ selectedLotForDiscard.ma_lo }}</strong></p>
                    <p class="text-slate-500">Tồn khả dụng trong lô: <strong class="text-red-700">{{ selectedLotForDiscard.so_luong_con_lai }} {{ selectedLotForDiscard.don_vi_tinh }}</strong></p>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="font-bold text-slate-700">Số lượng xuất hủy:</label>
                        <input
                            v-model.number="discardQuantityInput"
                            type="number"
                            step="0.1"
                            :max="selectedLotForDiscard.so_luong_con_lai"
                            class="w-full mt-1 px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl font-black text-slate-900 focus:ring-2 focus:ring-red-500 outline-none"
                        />
                    </div>

                    <div>
                        <label class="font-bold text-slate-700">Lý do xuất hủy:</label>
                        <input
                            v-model="discardReasonInput"
                            type="text"
                            class="w-full mt-1 px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-800 focus:ring-2 focus:ring-red-500 outline-none"
                        />
                    </div>
                </div>

                <div class="pt-2 flex space-x-2">
                    <button
                        @click="selectedLotForDiscard = null"
                        class="flex-1 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50"
                    >
                        Hủy
                    </button>
                    <button
                        @click="submitDiscard"
                        class="flex-1 py-2 rounded-xl bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition"
                    >
                        Xác Nhận Xuất Hủy
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>

