<script setup lang="ts">
import { ref, computed } from 'vue';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    Search,
    Package,
    AlertTriangle,
    Layers,
    Calendar,
    ChevronRight,
    X,
} from 'lucide-vue-next';

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
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <Package class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Mặt Hàng Trong Kho</p>
                        <p class="text-xl font-black text-slate-900 mt-0.5">{{ totalItems }} SP</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Sắp Hết Hàng (Cảnh báo)</p>
                        <p class="text-xl font-black text-amber-600 mt-0.5">{{ lowStockCount }} SP</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <Layers class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Tổng Số Lô Đang Lưu</p>
                        <p class="text-xl font-black text-slate-900 mt-0.5">{{ totalLots }} Lô</p>
                    </div>
                </div>
            </div>

            <!-- CONTROLS & TABLE -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <!-- FILTER BAR -->
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row gap-3 justify-between items-center">
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm kiếm sản phẩm, barcode..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                        />
                    </div>

                    <div class="flex items-center space-x-2 text-xs w-full sm:w-auto">
                        <button
                            @click="filterStatus = 'all'"
                            :class="filterStatus === 'all' ? 'bg-slate-900 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3.5 py-2 rounded-xl transition"
                        >
                            Tất cả
                        </button>
                        <button
                            @click="filterStatus = 'low_stock'"
                            :class="filterStatus === 'low_stock' ? 'bg-amber-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-3.5 py-2 rounded-xl transition flex items-center space-x-1"
                        >
                            <AlertTriangle class="w-3.5 h-3.5" />
                            <span>Cảnh báo hết kho ({{ lowStockCount }})</span>
                        </button>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Sản Phẩm</th>
                                <th class="py-3 px-4">Ngành Hàng</th>
                                <th class="py-3 px-4 text-right">Tồn Khả Dụng</th>
                                <th class="py-3 px-4 text-center">Số Lô Hàng</th>
                                <th class="py-3 px-4 text-center">Trạng Thái</th>
                                <th class="py-3 px-4 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredInventory.length === 0">
                                <td colspan="6" class="text-center py-10 text-slate-400">
                                    Không tìm thấy dữ liệu tồn kho phù hợp.
                                </td>
                            </tr>
                            <tr v-for="item in filteredInventory" :key="item.id" class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900">{{ item.ten_san_pham }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ item.ma_va_vach }}</p>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ item.danh_muc || 'Mặc định' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-slate-900">
                                    <span :class="item.canh_bao ? 'text-amber-600' : 'text-emerald-700'">
                                        {{ item.so_luong_ton }}
                                    </span>
                                    <span class="text-slate-400 font-normal ml-1">{{ item.don_vi_tinh }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-slate-700">
                                    <span class="bg-slate-100 px-2 py-0.5 rounded-lg text-xs">{{ item.lots.length }} lô</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="item.so_luong_ton <= 0"
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-red-100 text-red-700"
                                    >
                                        Hết hàng
                                    </span>
                                    <span
                                        v-else-if="item.canh_bao"
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-700"
                                    >
                                        Sắp hết (≤ {{ item.nguong_canh_bao_thap }})
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700"
                                    >
                                        Đủ hàng
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button
                                        @click="viewLots(item)"
                                        class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-900 hover:text-white font-bold text-xs text-slate-700 transition"
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
        <div v-if="selectedItemLots" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-black text-slate-900 text-base">{{ selectedItemLots.ten_san_pham }}</h3>
                        <p class="text-xs text-slate-400 font-mono">Barcode: {{ selectedItemLots.ma_va_vach }} | Tổng tồn: {{ selectedItemLots.so_luong_ton }} {{ selectedItemLots.don_vi_tinh }}</p>
                    </div>
                    <button @click="selectedItemLots = null" class="text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- LOTS TABLE -->
                <div class="overflow-x-auto max-h-96">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5 px-3">Mã Lô</th>
                                <th class="py-2.5 px-3">Ngày Sản Xuất</th>
                                <th class="py-2.5 px-3">Hạn Sử Dụng (FEFO)</th>
                                <th class="py-2.5 px-3 text-right">Còn Lại</th>
                                <th class="py-2.5 px-3 text-right">Giá Vốn</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="selectedItemLots.lots.length === 0">
                                <td colspan="5" class="text-center py-6 text-slate-400">Chưa có lô hàng nào còn tồn.</td>
                            </tr>
                            <tr v-for="lot in selectedItemLots.lots" :key="lot.id" class="hover:bg-slate-50">
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-800">{{ lot.ma_lo }}</td>
                                <td class="py-2.5 px-3 text-slate-500">{{ formatDate(lot.ngay_san_xuat) }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">
                                        {{ formatDate(lot.ngay_het_han) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-black text-slate-900">
                                    {{ lot.so_luong_con_lai }} {{ selectedItemLots.don_vi_tinh }}
                                </td>
                                <td class="py-2.5 px-3 text-right text-slate-500 font-medium">
                                    {{ new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(lot.gia_nhap) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pt-2 flex justify-end">
                    <button
                        @click="selectedItemLots = null"
                        class="px-5 py-2 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition"
                    >
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>

