<script setup lang="ts">
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import { AlertTriangle, Layers, Package, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface InventoryItem {
    id: number;
    san_pham_id: number;
    ten_san_pham: string;
    ma_va_vach: string;
    danh_muc: string;
    don_vi_tinh: string;
    so_luong_ton: number;
    so_luong_tam_giu: number;
    nguong_canh_bao_thap: number;
    canh_bao: boolean;
    lots: Array<any>;
}

const props = defineProps<{
    stores: Array<any>;
    currentStore: any;
    inventory: Array<InventoryItem>;
}>();

const searchQuery = ref('');
const filterStatus = ref<'all' | 'low_stock'>('all');
const selectedItemLots = ref<InventoryItem | null>(null);

const filteredInventory = computed(() => {
    return props.inventory.filter((item) => {
        const matchesStatus = filterStatus.value === 'all' || (filterStatus.value === 'low_stock' && item.canh_bao);
        const q = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !q || item.ten_san_pham.toLowerCase().includes(q) || item.ma_va_vach.includes(q);
        return matchesStatus && matchesSearch;
    });
});

const totalItems = computed(() => props.inventory.length);
const lowStockCount = computed(() => props.inventory.filter((i) => i.canh_bao).length);
const totalLots = computed(() => props.inventory.reduce((sum, i) => sum + i.lots.length, 0));

function viewLots(item: InventoryItem) {
    selectedItemLots.value = item;
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('vi-VN');
}
</script>

<template>
    <StoreOpsLayout title="Quản Lý Tồn Kho & Lô Hàng" :stores="stores" :currentStore="currentStore">
        <div class="space-y-6">
            <!-- STATS ROW -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="flex items-center space-x-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <Package class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Mặt Hàng Trong Kho</p>
                        <p class="mt-0.5 text-xl font-black text-slate-900">{{ totalItems }} SP</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                        <AlertTriangle class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Sắp Hết Hàng (Cảnh báo)</p>
                        <p class="mt-0.5 text-xl font-black text-amber-600">{{ lowStockCount }} SP</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                        <Layers class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Tổng Số Lô Đang Lưu</p>
                        <p class="mt-0.5 text-xl font-black text-slate-900">{{ totalLots }} Lô</p>
                    </div>
                </div>
            </div>

            <!-- CONTROLS & TABLE -->
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <!-- FILTER BAR -->
                <div class="flex flex-col items-center justify-between gap-3 border-b border-slate-100 p-4 sm:flex-row sm:p-5">
                    <div class="relative w-full sm:w-80">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm kiếm sản phẩm, barcode..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="flex w-full items-center space-x-2 text-xs sm:w-auto">
                        <button
                            @click="filterStatus = 'all'"
                            :class="filterStatus === 'all' ? 'bg-slate-900 font-bold text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="rounded-xl px-3.5 py-2 transition"
                        >
                            Tất cả
                        </button>
                        <button
                            @click="filterStatus = 'low_stock'"
                            :class="
                                filterStatus === 'low_stock' ? 'bg-amber-600 font-bold text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            "
                            class="flex items-center space-x-1 rounded-xl px-3.5 py-2 transition"
                        >
                            <AlertTriangle class="h-3.5 w-3.5" />
                            <span>Cảnh báo hết kho ({{ lowStockCount }})</span>
                        </button>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 bg-slate-50/80 font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Sản Phẩm</th>
                                <th class="px-4 py-3">Ngành Hàng</th>
                                <th class="px-4 py-3 text-right">Tồn Khả Dụng</th>
                                <th class="px-4 py-3 text-center">Số Lô Hàng</th>
                                <th class="px-4 py-3 text-center">Trạng Thái</th>
                                <th class="px-4 py-3 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredInventory.length === 0">
                                <td colspan="6" class="py-10 text-center text-slate-400">Không tìm thấy dữ liệu tồn kho phù hợp.</td>
                            </tr>
                            <tr v-for="item in filteredInventory" :key="item.id" class="transition hover:bg-slate-50/60">
                                <td class="px-4 py-3.5">
                                    <p class="font-bold text-slate-900">{{ item.ten_san_pham }}</p>
                                    <p class="font-mono text-[10px] text-slate-400">{{ item.ma_va_vach }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600">
                                    {{ item.danh_muc || 'Mặc định' }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-black text-slate-900">
                                    <span :class="item.canh_bao ? 'text-amber-600' : 'text-emerald-700'">
                                        {{ item.so_luong_ton }}
                                    </span>
                                    <span class="ml-1 font-normal text-slate-400">{{ item.don_vi_tinh }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold text-slate-700">
                                    <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs">{{ item.lots.length }} lô</span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        v-if="item.so_luong_ton <= 0"
                                        class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-red-700"
                                    >
                                        Hết hàng
                                    </span>
                                    <span
                                        v-else-if="item.canh_bao"
                                        class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-amber-700"
                                    >
                                        Sắp hết (≤ {{ item.nguong_canh_bao_thap }})
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700"
                                    >
                                        Đủ hàng
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <button
                                        @click="viewLots(item)"
                                        class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-900 hover:text-white"
                                    >
                                        Chi tiết Lô
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- LOT DETAILS MODAL -->
        <div v-if="selectedItemLots" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-2xl space-y-4 rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900">{{ selectedItemLots.ten_san_pham }}</h3>
                        <p class="font-mono text-xs text-slate-400">
                            Barcode: {{ selectedItemLots.ma_va_vach }} | Tổng tồn: {{ selectedItemLots.so_luong_ton }}
                            {{ selectedItemLots.don_vi_tinh }}
                        </p>
                    </div>
                    <button @click="selectedItemLots = null" class="text-slate-400 hover:text-slate-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- LOTS TABLE -->
                <div class="max-h-96 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-3 py-2.5">Mã Lô</th>
                                <th class="px-3 py-2.5">Ngày Sản Xuất</th>
                                <th class="px-3 py-2.5">Hạn Sử Dụng (FEFO)</th>
                                <th class="px-3 py-2.5 text-right">Còn Lại</th>
                                <th class="px-3 py-2.5 text-right">Giá Vốn</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="selectedItemLots.lots.length === 0">
                                <td colspan="5" class="py-6 text-center text-slate-400">Chưa có lô hàng nào còn tồn.</td>
                            </tr>
                            <tr v-for="lot in selectedItemLots.lots" :key="lot.id" class="hover:bg-slate-50">
                                <td class="px-3 py-2.5 font-mono font-bold text-slate-800">{{ lot.ma_lo }}</td>
                                <td class="px-3 py-2.5 text-slate-500">{{ formatDate(lot.ngay_san_xuat) }}</td>
                                <td class="px-3 py-2.5">
                                    <span class="rounded bg-emerald-50 px-2 py-0.5 font-bold text-emerald-700">
                                        {{ formatDate(lot.ngay_het_han) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-right font-black text-slate-900">
                                    {{ lot.so_luong_con_lai }} {{ selectedItemLots.don_vi_tinh }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-medium text-slate-500">
                                    {{ new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(lot.gia_nhap) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        @click="selectedItemLots = null"
                        class="rounded-xl bg-slate-900 px-5 py-2 text-xs font-bold text-white transition hover:bg-slate-800"
                    >
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>
