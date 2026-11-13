<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    ArrowLeftRight,
    Store,
    Package,
    Plus,
    Trash2,
    AlertCircle,
    CheckCircle2,
    ArrowRight,
    Search,
    Info,
    Calendar,
    Truck,
    Bike,
} from 'lucide-vue-next';

interface ProductItem {
    id: number;
    ten_san_pham: string;
    ma_va_vach: string;
    don_vi_tinh: string;
    danh_muc?: string;
    so_luong_ton: number;
}

interface TransferItem {
    san_pham_id: number;
    ten_san_pham: string;
    don_vi_tinh: string;
    ton_kho_nguon: number;
    so_luong: number;
}

const props = withDefaults(
    defineProps<{
        stores: Array<{ id: number; ten_cua_hang: string; dia_chi: string }>;
        currentStore: { id: number; ten_cua_hang: string };
        products: Array<ProductItem>;
        drivers?: Array<any>;
    }>(),
    {
        drivers: () => [],
    }
);

const sourceStoreId = ref<number>(props.currentStore?.id || props.stores[0]?.id || 1);
const targetStoreId = ref<number>(
    props.stores.find((s) => s.id !== sourceStoreId.value)?.id || 1
);

// Switch source store dynamically
function onSourceStoreChange() {
    if (sourceStoreId.value === targetStoreId.value) {
        const nextStore = props.stores.find((s) => s.id !== sourceStoreId.value);
        if (nextStore) {
            targetStoreId.value = nextStore.id;
        }
    }
    // Reload products for new source store
    router.get(
        route('store.transfer.create'),
        { store_id: sourceStoreId.value },
        { preserveState: true, replace: true }
    );
}

// Product picker state
const selectedProductId = ref<number | ''>('');
const quantityInput = ref<number>(20);
const searchKeyword = ref<string>('');

const selectedProduct = computed(() => {
    if (!selectedProductId.value) return null;
    return props.products.find((p) => p.id === Number(selectedProductId.value)) || null;
});

const filteredProductOptions = computed(() => {
    if (!searchKeyword.value.trim()) return props.products;
    const q = searchKeyword.value.toLowerCase().trim();
    return props.products.filter(
        (p) =>
            p.ten_san_pham.toLowerCase().includes(q) ||
            p.ma_va_vach?.toLowerCase().includes(q) ||
            p.danh_muc?.toLowerCase().includes(q)
    );
});

// Transfer items list
const items = ref<Array<TransferItem>>([]);
const noteInput = ref<string>('Điều chuyển hàng hóa bổ sung tồn kho chi nhánh');

function addItem() {
    if (!selectedProduct.value) return;

    const prod = selectedProduct.value;
    const qty = Number(quantityInput.value);

    if (qty <= 0) {
        alert('Số lượng điều chuyển phải lớn hơn 0.');
        return;
    }

    if (qty > prod.so_luong_ton) {
        alert(
            `Chi nhánh xuất chỉ còn ${prod.so_luong_ton} ${prod.don_vi_tinh}. Không thể điều chuyển vượt quá tồn khả dụng.`
        );
        return;
    }

    // Check if item already in list
    const existingIndex = items.value.findIndex((i) => i.san_pham_id === prod.id);
    if (existingIndex >= 0) {
        const newTotal = items.value[existingIndex].so_luong + qty;
        if (newTotal > prod.so_luong_ton) {
            alert(`Tổng số lượng chuyển (${newTotal}) vượt quá tồn chi nhánh (${prod.so_luong_ton}).`);
            return;
        }
        items.value[existingIndex].so_luong = newTotal;
    } else {
        items.value.push({
            san_pham_id: prod.id,
            ten_san_pham: prod.ten_san_pham,
            don_vi_tinh: prod.don_vi_tinh,
            ton_kho_nguon: prod.so_luong_ton,
            so_luong: qty,
        });
    }

    selectedProductId.value = '';
    quantityInput.value = 1;
}

function removeItem(index: number) {
    items.value.splice(index, 1);
}

const totalTransferQty = computed(() => {
    return items.value.reduce((sum, it) => sum + Number(it.so_luong || 0), 0);
});

const isBulkTransfer = computed(() => totalTransferQty.value >= 20);

// Driver & Vehicle state
const selectedDriverId = ref<number | ''>('');
const vehicleType = ref<'xe_tai' | 'xe_may'>('xe_tai');
const licensePlate = ref<string>('');

function onDriverSelected() {
    const d = props.drivers.find((drv) => drv.id === Number(selectedDriverId.value));
    if (d) {
        vehicleType.value = d.loai_phuong_tien || (isBulkTransfer.value ? 'xe_tai' : 'xe_may');
        licensePlate.value = d.bien_so_xe || '';
    }
}

const form = useForm({
    cua_hang_xuat_id: sourceStoreId.value,
    cua_hang_nhan_id: targetStoreId.value,
    items: [] as Array<{ san_pham_id: number; so_luong: number }>,
    ghi_chu: '',
    nguoi_giao_hang_id: null as number | null,
    loai_phuong_tien: 'xe_tai' as 'xe_tai' | 'xe_may',
    bien_so_xe: '',
});

function submitTransfer() {
    if (items.value.length === 0) {
        alert('Vui lòng thêm ít nhất một sản phẩm cần điều chuyển.');
        return;
    }

    if (sourceStoreId.value === targetStoreId.value) {
        alert('Chi nhánh xuất và chi nhánh nhận không được trùng nhau.');
        return;
    }

    if (isBulkTransfer.value && vehicleType.value === 'xe_may') {
        alert('Tổng số lượng điều chuyển lớn (>= 20 SP). Bắt buộc sử dụng Xe Tải để vận chuyển!');
        return;
    }

    form.cua_hang_xuat_id = sourceStoreId.value;
    form.cua_hang_nhan_id = targetStoreId.value;
    form.ghi_chu = noteInput.value;
    form.nguoi_giao_hang_id = selectedDriverId.value ? Number(selectedDriverId.value) : null;
    form.loai_phuong_tien = vehicleType.value;
    form.bien_so_xe = licensePlate.value;
    form.items = items.value.map((i) => ({
        san_pham_id: i.san_pham_id,
        so_luong: i.so_luong,
    }));

    form.post(route('store.transfer.store'), {
        preserveScroll: true,
        onError: () => {
            // Form errors will display automatically
        },
    });
}
</script>

<template>
    <StoreOpsLayout title="Lập Phiếu Điều Chuyển Chi Nhánh" :stores="stores" :currentStore="currentStore">
        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- HEADER CARD -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900 flex items-center space-x-2">
                        <ArrowLeftRight class="w-6 h-6 text-emerald-600" />
                        <span>Điều Chuyển Hàng Hóa Giữa Các Chi Nhánh</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Hệ thống tự động trừ lô hàng xuất theo chuẩn **FEFO** tại chi nhánh xuất và đồng bộ hạn sử dụng về chi nhánh nhận.
                    </p>
                </div>
            </div>

            <!-- STEP 1: SELECT STORES -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <Store class="w-4 h-4 text-emerald-600" />
                    <span>Bước 1: Chọn Chi Nhánh Xuất & Nhận</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                    <!-- SOURCE STORE -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                        <label class="block text-xs font-bold text-slate-700">Chi Nhánh Xuất Hàng (Chuyển đi):</label>
                        <select
                            v-model="sourceStoreId"
                            @change="onSourceStoreChange"
                            class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
                        >
                            <option v-for="s in stores" :key="s.id" :value="s.id">
                                {{ s.ten_cua_hang }} ({{ s.dia_chi }})
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-400">
                            Tồn kho và lô hàng sẽ được trừ tại chi nhánh này theo FEFO.
                        </p>
                    </div>

                    <!-- DESTINATION STORE -->
                    <div class="p-4 rounded-2xl bg-emerald-50/50 border border-emerald-200 space-y-2">
                        <label class="block text-xs font-bold text-emerald-900">Chi Nhánh Nhận Hàng (Nhập về):</label>
                        <select
                            v-model="targetStoreId"
                            class="w-full bg-white border border-emerald-300 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
                        >
                            <option
                                v-for="s in stores"
                                :key="s.id"
                                :value="s.id"
                                :disabled="s.id === sourceStoreId"
                            >
                                {{ s.ten_cua_hang }} {{ s.id === sourceStoreId ? '(Đang là bên xuất)' : '' }}
                            </option>
                        </select>
                        <p class="text-[11px] text-emerald-700">
                            Hàng hóa sẽ được cộng tồn kho và tạo lô hàng bảo toàn HSD tại đây.
                        </p>
                    </div>
                </div>
            </div>

            <!-- STEP 2: SELECT PRODUCT & QUANTITY -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <Package class="w-4 h-4 text-emerald-600" />
                    <span>Bước 2: Chọn Sản Phẩm & Số Lượng Điều Chuyển</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <!-- SEARCH & SELECT PRODUCT -->
                    <div class="md:col-span-7 space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Sản phẩm thực phẩm tươi:</label>
                        <div class="flex gap-2">
                            <input
                                v-model="searchKeyword"
                                type="text"
                                placeholder="Gõ tìm tên sản phẩm (vd: Cải Bó Xôi)..."
                                class="w-1/2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                            />
                            <select
                                v-model="selectedProductId"
                                class="w-1/2 bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
                            >
                                <option value="">-- Chọn sản phẩm --</option>
                                <option
                                    v-for="p in filteredProductOptions"
                                    :key="p.id"
                                    :value="p.id"
                                    :disabled="p.so_luong_ton <= 0"
                                >
                                    {{ p.ten_san_pham }} (Tồn: {{ p.so_luong_ton }} {{ p.don_vi_tinh }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- QUANTITY INPUT -->
                    <div class="md:col-span-3 space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Số lượng chuyển:</label>
                        <div class="flex items-center">
                            <input
                                v-model="quantityInput"
                                type="number"
                                min="0.1"
                                step="any"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500 outline-none"
                            />
                            <span class="ml-2 text-xs font-bold text-slate-500 whitespace-nowrap">
                                {{ selectedProduct?.don_vi_tinh || 'Đơn vị' }}
                            </span>
                        </div>
                    </div>

                    <!-- ADD BUTTON -->
                    <div class="md:col-span-2">
                        <button
                            type="button"
                            @click="addItem"
                            :disabled="!selectedProduct"
                            class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-200 disabled:text-slate-400 text-white font-bold text-xs transition shadow-sm"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Thêm</span>
                        </button>
                    </div>
                </div>

                <!-- STOCK AVAILABILITY BADGE -->
                <div v-if="selectedProduct" class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
                    <div>
                        <span class="font-bold text-slate-800">{{ selectedProduct.ten_san_pham }}</span>
                        <span class="text-slate-500 ml-2">Mã vạch: {{ selectedProduct.ma_va_vach || 'N/A' }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-slate-500">Tồn khả dụng chi nhánh xuất:</span>
                        <b class="text-emerald-700 font-extrabold text-sm">{{ selectedProduct.so_luong_ton }} {{ selectedProduct.don_vi_tinh }}</b>
                    </div>
                </div>
            </div>

            <!-- STEP 3: ITEMS LIST & CONFIRMATION -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                        <span>Bước 3: Danh Sách Hàng Hóa Điều Chuyển ({{ items.length }} mặt hàng)</span>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">STT</th>
                                <th class="py-3 px-4">Tên Sản Phẩm</th>
                                <th class="py-3 px-4 text-center">ĐVT</th>
                                <th class="py-3 px-4 text-right">Tồn Chi Nhánh Xuất</th>
                                <th class="py-3 px-4 text-right">Số Lượng Chuyển</th>
                                <th class="py-3 px-4 text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="items.length === 0">
                                <td colspan="6" class="text-center py-8 text-slate-400">
                                    Chưa có mặt hàng nào. Vui lòng chọn sản phẩm và bấm "Thêm".
                                </td>
                            </tr>
                            <tr v-for="(it, idx) in items" :key="it.san_pham_id" class="hover:bg-slate-50">
                                <td class="py-3 px-4 text-slate-400 font-bold">{{ idx + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800">{{ it.ten_san_pham }}</td>
                                <td class="py-3 px-4 text-center text-slate-500">{{ it.don_vi_tinh }}</td>
                                <td class="py-3 px-4 text-right text-slate-500">{{ it.ton_kho_nguon }}</td>
                                <td class="py-3 px-4 text-right">
                                    <span class="font-extrabold text-emerald-600 text-sm px-2.5 py-1 bg-emerald-50 rounded-lg">
                                        {{ it.so_luong }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button
                                        type="button"
                                        @click="removeItem(idx)"
                                        class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- VEHICLE & DRIVER DISPATCH -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <label class="block text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <Truck class="w-4 h-4 text-sky-600" />
                            <span>Phương Tiện & Tài Xế Vận Chuyển:</span>
                        </label>
                        <span
                            v-if="isBulkTransfer"
                            class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 flex items-center gap-1 self-start sm:self-auto"
                        >
                            <Truck class="w-3 h-3" />
                            <span>Chuyển số lượng lớn ({{ totalTransferQty }} SP): Bắt buộc dùng Xe Tải</span>
                        </span>
                        <span
                            v-else
                            class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 self-start sm:self-auto"
                        >
                            Tổng hàng: {{ totalTransferQty }} SP
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Tài xế điều chuyển:</label>
                            <select
                                v-model="selectedDriverId"
                                @change="onDriverSelected"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-sky-500 outline-none"
                            >
                                <option value="">-- Chọn tài xế (Tùy chọn) --</option>
                                <option v-for="d in drivers" :key="d.id" :value="d.id">
                                    {{ d.loai_phuong_tien === 'xe_tai' ? '🚚 Xe tải' : '🏍️ Xe máy' }} - {{ d.name }} ({{ d.bien_so_xe || 'Chưa BKS' }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Loại phương tiện:</label>
                            <select
                                v-model="vehicleType"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-sky-500 outline-none"
                            >
                                <option value="xe_tai">🚚 Xe Tải (Khuyên dùng cho liên chi nhánh)</option>
                                <option value="xe_may" :disabled="isBulkTransfer">🏍️ Xe Máy {{ isBulkTransfer ? '(Vượt tải trọng >=20 SP)' : '' }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Biển số xe vận chuyển:</label>
                            <input
                                v-model="licensePlate"
                                type="text"
                                placeholder="VD: 51C-123.45"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-mono font-bold text-slate-800 focus:ring-2 focus:ring-sky-500 outline-none"
                            />
                        </div>
                    </div>
                </div>

                <!-- NOTE / REASON -->
                <div class="space-y-1.5 pt-2">
                    <label class="block text-xs font-bold text-slate-700">Ghi chú điều chuyển:</label>
                    <textarea
                        v-model="noteInput"
                        rows="2"
                        placeholder="Nhập lý do hoặc mã chứng từ điều chuyển nội bộ..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                    ></textarea>
                </div>

                <!-- ERROR ALERT -->
                <div
                    v-if="form.errors.error"
                    class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center space-x-2"
                >
                    <AlertCircle class="w-4 h-4 shrink-0" />
                    <span>{{ form.errors.error }}</span>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="pt-4 flex justify-end">
                    <button
                        type="button"
                        @click="submitTransfer"
                        :disabled="items.length === 0 || form.processing"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-200 disabled:text-slate-400 text-white font-extrabold text-sm shadow-lg shadow-emerald-600/30 transition-all cursor-pointer"
                    >
                        <ArrowLeftRight class="w-4 h-4" />
                        <span>{{ form.processing ? 'Đang Xử Lý FEFO...' : 'Xác Nhận Xuất & Điều Chuyển Kho' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>

