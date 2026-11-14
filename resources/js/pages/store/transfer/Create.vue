<script setup lang="ts">
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeftRight, CheckCircle2, Package, Plus, Store, Trash2, Truck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
    },
);

const sourceStoreId = ref<number>(props.currentStore?.id || props.stores[0]?.id || 1);
const targetStoreId = ref<number>(props.stores.find((s) => s.id !== sourceStoreId.value)?.id || 1);

// Switch source store dynamically
function onSourceStoreChange() {
    if (sourceStoreId.value === targetStoreId.value) {
        const nextStore = props.stores.find((s) => s.id !== sourceStoreId.value);
        if (nextStore) {
            targetStoreId.value = nextStore.id;
        }
    }
    // Reload products for new source store
    router.get(route('store.transfer.create'), { store_id: sourceStoreId.value }, { preserveState: true, replace: true });
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
        (p) => p.ten_san_pham.toLowerCase().includes(q) || p.ma_va_vach?.toLowerCase().includes(q) || p.danh_muc?.toLowerCase().includes(q),
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
        alert(`Chi nhánh xuất chỉ còn ${prod.so_luong_ton} ${prod.don_vi_tinh}. Không thể điều chuyển vượt quá tồn khả dụng.`);
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
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- HEADER CARD -->
            <div
                class="flex flex-col items-start justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:flex-row sm:items-center"
            >
                <div>
                    <h2 class="flex items-center space-x-2 text-lg font-black text-slate-900">
                        <ArrowLeftRight class="h-6 w-6 text-emerald-600" />
                        <span>Điều Chuyển Hàng Hóa Giữa Các Chi Nhánh</span>
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Hệ thống tự động trừ lô hàng xuất theo chuẩn **FEFO** tại chi nhánh xuất và đồng bộ hạn sử dụng về chi nhánh nhận.
                    </p>
                </div>
            </div>

            <!-- STEP 1: SELECT STORES -->
            <div class="space-y-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <Store class="h-4 w-4 text-emerald-600" />
                    <span>Bước 1: Chọn Chi Nhánh Xuất & Nhận</span>
                </div>

                <div class="grid grid-cols-1 items-center gap-6 md:grid-cols-2">
                    <!-- SOURCE STORE -->
                    <div class="space-y-2 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <label class="block text-xs font-bold text-slate-700">Chi Nhánh Xuất Hàng (Chuyển đi):</label>
                        <select
                            v-model="sourceStoreId"
                            @change="onSourceStoreChange"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                            <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }} ({{ s.dia_chi }})</option>
                        </select>
                        <p class="text-[11px] text-slate-400">Tồn kho và lô hàng sẽ được trừ tại chi nhánh này theo FEFO.</p>
                    </div>

                    <!-- DESTINATION STORE -->
                    <div class="space-y-2 rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4">
                        <label class="block text-xs font-bold text-emerald-900">Chi Nhánh Nhận Hàng (Nhập về):</label>
                        <select
                            v-model="targetStoreId"
                            class="w-full rounded-xl border border-emerald-300 bg-white px-3 py-2.5 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                            <option v-for="s in stores" :key="s.id" :value="s.id" :disabled="s.id === sourceStoreId">
                                {{ s.ten_cua_hang }} {{ s.id === sourceStoreId ? '(Đang là bên xuất)' : '' }}
                            </option>
                        </select>
                        <p class="text-[11px] text-emerald-700">Hàng hóa sẽ được cộng tồn kho và tạo lô hàng bảo toàn HSD tại đây.</p>
                    </div>
                </div>
            </div>

            <!-- STEP 2: SELECT PRODUCT & QUANTITY -->
            <div class="space-y-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <Package class="h-4 w-4 text-emerald-600" />
                    <span>Bước 2: Chọn Sản Phẩm & Số Lượng Điều Chuyển</span>
                </div>

                <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-12">
                    <!-- SEARCH & SELECT PRODUCT -->
                    <div class="space-y-1.5 md:col-span-7">
                        <label class="block text-xs font-bold text-slate-700">Sản phẩm thực phẩm tươi:</label>
                        <div class="flex gap-2">
                            <input
                                v-model="searchKeyword"
                                type="text"
                                placeholder="Gõ tìm tên sản phẩm (vd: Cải Bó Xôi)..."
                                class="w-1/2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                            <select
                                v-model="selectedProductId"
                                class="w-1/2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 outline-none focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="">-- Chọn sản phẩm --</option>
                                <option v-for="p in filteredProductOptions" :key="p.id" :value="p.id" :disabled="p.so_luong_ton <= 0">
                                    {{ p.ten_san_pham }} (Tồn: {{ p.so_luong_ton }} {{ p.don_vi_tinh }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- QUANTITY INPUT -->
                    <div class="space-y-1.5 md:col-span-3">
                        <label class="block text-xs font-bold text-slate-700">Số lượng chuyển:</label>
                        <div class="flex items-center">
                            <input
                                v-model="quantityInput"
                                type="number"
                                min="0.1"
                                step="any"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                            <span class="ml-2 whitespace-nowrap text-xs font-bold text-slate-500">
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
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:bg-slate-200 disabled:text-slate-400"
                        >
                            <Plus class="h-4 w-4" />
                            <span>Thêm</span>
                        </button>
                    </div>
                </div>

                <!-- STOCK AVAILABILITY BADGE -->
                <div v-if="selectedProduct" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs">
                    <div>
                        <span class="font-bold text-slate-800">{{ selectedProduct.ten_san_pham }}</span>
                        <span class="ml-2 text-slate-500">Mã vạch: {{ selectedProduct.ma_va_vach || 'N/A' }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-slate-500">Tồn khả dụng chi nhánh xuất:</span>
                        <b class="text-sm font-extrabold text-emerald-700">{{ selectedProduct.so_luong_ton }} {{ selectedProduct.don_vi_tinh }}</b>
                    </div>
                </div>
            </div>

            <!-- STEP 3: ITEMS LIST & CONFIRMATION -->
            <div class="space-y-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                        <span>Bước 3: Danh Sách Hàng Hóa Điều Chuyển ({{ items.length }} mặt hàng)</span>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-200 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">STT</th>
                                <th class="px-4 py-3">Tên Sản Phẩm</th>
                                <th class="px-4 py-3 text-center">ĐVT</th>
                                <th class="px-4 py-3 text-right">Tồn Chi Nhánh Xuất</th>
                                <th class="px-4 py-3 text-right">Số Lượng Chuyển</th>
                                <th class="px-4 py-3 text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="items.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    Chưa có mặt hàng nào. Vui lòng chọn sản phẩm và bấm "Thêm".
                                </td>
                            </tr>
                            <tr v-for="(it, idx) in items" :key="it.san_pham_id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-400">{{ idx + 1 }}</td>
                                <td class="px-4 py-3 font-bold text-slate-800">{{ it.ten_san_pham }}</td>
                                <td class="px-4 py-3 text-center text-slate-500">{{ it.don_vi_tinh }}</td>
                                <td class="px-4 py-3 text-right text-slate-500">{{ it.ton_kho_nguon }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-sm font-extrabold text-emerald-600">
                                        {{ it.so_luong }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="removeItem(idx)"
                                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- VEHICLE & DRIVER DISPATCH -->
                <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <label class="block flex items-center gap-1.5 text-xs font-bold text-slate-700">
                            <Truck class="h-4 w-4 text-sky-600" />
                            <span>Phương Tiện & Tài Xế Vận Chuyển:</span>
                        </label>
                        <span
                            v-if="isBulkTransfer"
                            class="flex items-center gap-1 self-start rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-800 sm:self-auto"
                        >
                            <Truck class="h-3 w-3" />
                            <span>Chuyển số lượng lớn ({{ totalTransferQty }} SP): Bắt buộc dùng Xe Tải</span>
                        </span>
                        <span v-else class="self-start rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-bold text-slate-700 sm:self-auto">
                            Tổng hàng: {{ totalTransferQty }} SP
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Tài xế điều chuyển:</label>
                            <select
                                v-model="selectedDriverId"
                                @change="onDriverSelected"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-sky-500"
                            >
                                <option value="">-- Chọn tài xế (Tùy chọn) --</option>
                                <option v-for="d in drivers" :key="d.id" :value="d.id">
                                    {{ d.loai_phuong_tien === 'xe_tai' ? '🚚 Xe tải' : '🏍️ Xe máy' }} - {{ d.name }} ({{
                                        d.bien_so_xe || 'Chưa BKS'
                                    }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Loại phương tiện:</label>
                            <select
                                v-model="vehicleType"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-sky-500"
                            >
                                <option value="xe_tai">🚚 Xe Tải (Khuyên dùng cho liên chi nhánh)</option>
                                <option value="xe_may" :disabled="isBulkTransfer">
                                    🏍️ Xe Máy {{ isBulkTransfer ? '(Vượt tải trọng >=20 SP)' : '' }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-bold text-slate-500">Biển số xe vận chuyển:</label>
                            <input
                                v-model="licensePlate"
                                type="text"
                                placeholder="VD: 51C-123.45"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-sky-500"
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
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs outline-none focus:ring-2 focus:ring-emerald-500"
                    ></textarea>
                </div>

                <!-- ERROR ALERT -->
                <div
                    v-if="form.errors.error"
                    class="flex items-center space-x-2 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-medium text-rose-800"
                >
                    <AlertCircle class="h-4 w-4 shrink-0" />
                    <span>{{ form.errors.error }}</span>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="flex justify-end pt-4">
                    <button
                        type="button"
                        @click="submitTransfer"
                        :disabled="items.length === 0 || form.processing"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-emerald-600/30 transition-all hover:bg-emerald-700 disabled:bg-slate-200 disabled:text-slate-400"
                    >
                        <ArrowLeftRight class="h-4 w-4" />
                        <span>{{ form.processing ? 'Đang Xử Lý FEFO...' : 'Xác Nhận Xuất & Điều Chuyển Kho' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>
