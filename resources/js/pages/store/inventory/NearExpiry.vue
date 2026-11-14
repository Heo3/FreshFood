<script setup lang="ts">
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, Clock, Flame, Search, Tag, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

    router.post(
        route('store.batches.discount'),
        {
            san_pham_id: selectedLotForDiscount.value.san_pham_id,
            gia_giam: discountPriceInput.value,
            store_id: props.currentStore?.id,
        },
        {
            onSuccess: () => {
                selectedLotForDiscount.value = null;
            },
        },
    );
}

function openDiscardModal(lot: NearExpiryLot) {
    selectedLotForDiscard.value = lot;
    discardQuantityInput.value = lot.so_luong_con_lai;
}

function submitDiscard() {
    if (!selectedLotForDiscard.value || !discardQuantityInput.value) return;

    router.post(
        route('store.batches.discard'),
        {
            lo_hang_id: selectedLotForDiscard.value.id,
            so_luong: discardQuantityInput.value,
            ly_do: discardReasonInput.value,
            store_id: props.currentStore?.id,
        },
        {
            onSuccess: () => {
                selectedLotForDiscard.value = null;
            },
        },
    );
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
}
</script>

<template>
    <StoreOpsLayout title="Cảnh Báo Cận Date & Xả Hàng" :stores="stores" :currentStore="currentStore">
        <div class="space-y-6">
            <!-- HEADER INFO BANNER -->
            <div
                class="flex flex-col items-start justify-between gap-4 rounded-3xl bg-gradient-to-r from-red-600 via-amber-600 to-amber-700 p-6 text-white shadow-lg sm:flex-row sm:items-center"
            >
                <div>
                    <div class="flex items-center space-x-2">
                        <Flame class="h-6 w-6 text-amber-300" />
                        <h2 class="text-xl font-black tracking-wide">TRUNG TÂM KIỂM SOÁT HẠN SỬ DỤNG (FEFO)</h2>
                    </div>
                    <p class="mt-1 max-w-2xl text-xs text-amber-100">
                        Tự động rà soát hạn dùng của từng lô thực phẩm tươi sống tại chi nhánh. Cho phép xả hàng giảm giá 50-70% trong ngày hoặc xuất
                        hủy ngay trước khi hỏng.
                    </p>
                </div>
            </div>

            <!-- LEVEL COUNTERS -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div
                    @click="filterLevel = 'critical'"
                    :class="filterLevel === 'critical' ? 'bg-red-50/80 ring-2 ring-red-500' : 'bg-white'"
                    class="flex cursor-pointer items-center space-x-4 rounded-3xl border border-red-200/80 p-5 shadow-sm transition"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-100 text-red-600">
                        <AlertTriangle class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-red-700">Báo Động Đỏ (≤ 1 Ngày)</p>
                        <p class="mt-0.5 text-2xl font-black text-red-700">{{ criticalCount }} Lô hàng</p>
                    </div>
                </div>

                <div
                    @click="filterLevel = 'warning'"
                    :class="filterLevel === 'warning' ? 'bg-amber-50/80 ring-2 ring-amber-500' : 'bg-white'"
                    class="flex cursor-pointer items-center space-x-4 rounded-3xl border border-amber-200/80 p-5 shadow-sm transition"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                        <Clock class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Cảnh Báo Vàng (2 - 3 Ngày)</p>
                        <p class="mt-0.5 text-2xl font-black text-amber-700">{{ warningCount }} Lô hàng</p>
                    </div>
                </div>

                <div
                    @click="filterLevel = 'expired'"
                    :class="filterLevel === 'expired' ? 'bg-slate-100 ring-2 ring-slate-800' : 'bg-white'"
                    class="flex cursor-pointer items-center space-x-4 rounded-3xl border border-slate-200/80 p-5 shadow-sm transition"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200 text-slate-700">
                        <Trash2 class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-700">Đã Quá Hạn Sử Dụng</p>
                        <p class="mt-0.5 text-2xl font-black text-slate-900">{{ expiredCount }} Lô hàng</p>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <!-- CONTROLS -->
                <div class="flex flex-col items-center justify-between gap-3 border-b border-slate-100 p-4 sm:flex-row sm:p-5">
                    <div class="relative w-full sm:w-80">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm kiếm theo mã lô, tên sản phẩm..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs outline-none focus:ring-2 focus:ring-amber-500"
                        />
                    </div>

                    <div class="flex items-center space-x-2 text-xs">
                        <button
                            @click="filterLevel = 'all'"
                            :class="filterLevel === 'all' ? 'bg-slate-900 font-bold text-white' : 'bg-slate-100 text-slate-600'"
                            class="rounded-xl px-3.5 py-1.5 transition"
                        >
                            Xem Tất Cả ({{ nearExpiryLots.length }})
                        </button>
                    </div>
                </div>

                <!-- TABLE CONTENT -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Lô Hàng / Sản Phẩm</th>
                                <th class="px-4 py-3">Hạn Sử Dụng</th>
                                <th class="px-4 py-3 text-center">Tình Trạng HSD</th>
                                <th class="px-4 py-3 text-right">Tồn Còn Lại</th>
                                <th class="px-4 py-3 text-right">Giá Gốc</th>
                                <th class="px-4 py-3 text-right">Hành Động Xử Lý</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredLots.length === 0">
                                <td colspan="6" class="py-10 text-center text-slate-400">Không có lô hàng nào trong danh mục cảnh báo này.</td>
                            </tr>
                            <tr v-for="lot in filteredLots" :key="lot.id" class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5">
                                    <p class="font-bold text-slate-900">{{ lot.ten_san_pham }}</p>
                                    <p class="font-mono text-[10px] text-slate-400">Mã lô: {{ lot.ma_lo }} | {{ lot.danh_muc }}</p>
                                </td>
                                <td class="px-4 py-3.5 font-medium text-slate-600">
                                    {{ lot.ngay_het_han }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        v-if="lot.muc_do_canh_bao === 'expired'"
                                        class="rounded-full bg-slate-900 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white"
                                    >
                                        Đã hết hạn
                                    </span>
                                    <span
                                        v-else-if="lot.muc_do_canh_bao === 'critical'"
                                        class="animate-pulse rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-red-700"
                                    >
                                        Còn {{ lot.so_ngay_con_lai }} ngày (Đỏ)
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-amber-800"
                                    >
                                        Còn {{ lot.so_ngay_con_lai }} ngày (Vàng)
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right font-black text-slate-900">{{ lot.so_luong_con_lai }} {{ lot.don_vi_tinh }}</td>
                                <td class="px-4 py-3.5 text-right font-bold text-slate-600">
                                    {{ formatPrice(lot.gia_ban_goc) }}
                                </td>
                                <td class="space-x-1.5 whitespace-nowrap px-4 py-3.5 text-right">
                                    <!-- BUTTON: ÁP GIÁ XẢ HÀNG -->
                                    <button
                                        v-if="lot.muc_do_canh_bao !== 'expired'"
                                        @click="openDiscountModal(lot)"
                                        class="inline-flex items-center space-x-1 rounded-xl bg-amber-500 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-amber-600"
                                    >
                                        <Tag class="h-3.5 w-3.5" />
                                        <span>Xả Hàng</span>
                                    </button>

                                    <!-- BUTTON: XUẤT HỦY (QUẢN LÝ / ADMIN) -->
                                    <button
                                        v-if="isAuthorizedManager"
                                        @click="openDiscardModal(lot)"
                                        class="inline-flex items-center space-x-1 rounded-xl bg-red-100 px-3 py-1.5 text-xs font-bold text-red-700 transition hover:bg-red-600 hover:text-white"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                        <span>Xuất Hủy</span>
                                    </button>
                                    <span v-else class="text-[11px] italic text-slate-400"> (Cần Quản lý duyệt hủy) </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DISCOUNT MODAL -->
        <div v-if="selectedLotForDiscount" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md space-y-4 rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <Tag class="h-5 w-5 text-amber-500" />
                        <h3 class="text-base font-black text-slate-900">Áp Dụng Giá Xả Hàng Cận Date</h3>
                    </div>
                    <button @click="selectedLotForDiscount = null" class="text-slate-400 hover:text-slate-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-2 rounded-2xl bg-slate-50 p-3 text-xs">
                    <p class="font-bold text-slate-900">{{ selectedLotForDiscount.ten_san_pham }}</p>
                    <p class="text-slate-500">
                        Mã lô: <strong class="text-slate-700">{{ selectedLotForDiscount.ma_lo }}</strong> | HSD:
                        {{ selectedLotForDiscount.ngay_het_han }}
                    </p>
                    <p class="text-slate-500">
                        Giá bán gốc: <strong class="text-slate-700">{{ formatPrice(selectedLotForDiscount.gia_ban_goc) }}</strong>
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Giá khuyến mãi xả hàng mới (VNĐ):</label>
                    <input
                        v-model.number="discountPriceInput"
                        type="number"
                        placeholder="Nhập giá giảm..."
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm font-black text-emerald-700 outline-none focus:ring-2 focus:ring-amber-500"
                    />
                    <p class="text-[11px] italic text-slate-400">
                        Giá giảm này sẽ hiển thị nổi bật trên Storefront của chi nhánh để khách mua nhanh.
                    </p>
                </div>

                <div class="flex space-x-2 pt-2">
                    <button
                        @click="selectedLotForDiscount = null"
                        class="flex-1 rounded-xl border border-slate-200 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
                    >
                        Hủy
                    </button>
                    <button
                        @click="submitDiscount"
                        class="flex-1 rounded-xl bg-amber-500 py-2 text-xs font-bold text-white transition hover:bg-amber-600"
                    >
                        Xác Nhận Giảm Giá
                    </button>
                </div>
            </div>
        </div>

        <!-- DISCARD MODAL -->
        <div v-if="selectedLotForDiscard" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md space-y-4 rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <Trash2 class="h-5 w-5 text-red-600" />
                        <h3 class="text-base font-black text-slate-900">Xuất Hủy Hàng Quá Date / Hỏng</h3>
                    </div>
                    <button @click="selectedLotForDiscard = null" class="text-slate-400 hover:text-slate-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-2 rounded-2xl border border-red-100 bg-red-50/50 p-3 text-xs">
                    <p class="font-bold text-slate-900">{{ selectedLotForDiscard.ten_san_pham }}</p>
                    <p class="text-slate-500">
                        Mã lô: <strong class="text-slate-700">{{ selectedLotForDiscard.ma_lo }}</strong>
                    </p>
                    <p class="text-slate-500">
                        Tồn khả dụng trong lô:
                        <strong class="text-red-700">{{ selectedLotForDiscard.so_luong_con_lai }} {{ selectedLotForDiscard.don_vi_tinh }}</strong>
                    </p>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="font-bold text-slate-700">Số lượng xuất hủy:</label>
                        <input
                            v-model.number="discardQuantityInput"
                            type="number"
                            step="0.1"
                            :max="selectedLotForDiscard.so_luong_con_lai"
                            class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2 font-black text-slate-900 outline-none focus:ring-2 focus:ring-red-500"
                        />
                    </div>

                    <div>
                        <label class="font-bold text-slate-700">Lý do xuất hủy:</label>
                        <input
                            v-model="discardReasonInput"
                            type="text"
                            class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2 text-slate-800 outline-none focus:ring-2 focus:ring-red-500"
                        />
                    </div>
                </div>

                <div class="flex space-x-2 pt-2">
                    <button
                        @click="selectedLotForDiscard = null"
                        class="flex-1 rounded-xl border border-slate-200 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
                    >
                        Hủy
                    </button>
                    <button @click="submitDiscard" class="flex-1 rounded-xl bg-red-600 py-2 text-xs font-bold text-white transition hover:bg-red-700">
                        Xác Nhận Xuất Hủy
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>
