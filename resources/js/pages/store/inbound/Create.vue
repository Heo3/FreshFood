<script setup lang="ts">
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ProductOption {
    id: number;
    ten_san_pham: string;
    ma_va_vach: string;
    don_vi_tinh: string;
    gia_ban: number;
}

interface SupplierOption {
    id: number;
    ten_nha_cung_cap: string;
    so_dien_thoai: string;
}

const props = defineProps<{
    stores: Array<any>;
    currentStore: any;
    suppliers: Array<SupplierOption>;
    products: Array<ProductOption>;
}>();

const supplierId = ref<number | ''>(props.suppliers[0]?.id || '');
const note = ref('');
const isSubmitting = ref(false);

const todayStr = new Date().toISOString().split('T')[0];
const next7DaysStr = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

interface RowItem {
    san_pham_id: number;
    ma_lo: string;
    ngay_san_xuat: string;
    ngay_het_han: string;
    so_luong: number;
    don_gia_nhap: number;
}

const items = ref<RowItem[]>([
    {
        san_pham_id: props.products[0]?.id || 1,
        ma_lo: '',
        ngay_san_xuat: todayStr,
        ngay_het_han: next7DaysStr,
        so_luong: 10,
        don_gia_nhap: 20000,
    },
]);

function addRow() {
    items.value.push({
        san_pham_id: props.products[0]?.id || 1,
        ma_lo: '',
        ngay_san_xuat: todayStr,
        ngay_het_han: next7DaysStr,
        so_luong: 10,
        don_gia_nhap: 20000,
    });
}

function removeRow(index: number) {
    if (items.value.length > 1) {
        items.value.splice(index, 1);
    }
}

const totalQuantity = computed(() => {
    return items.value.reduce((sum, i) => sum + (Number(i.so_luong) || 0), 0);
});

const totalAmount = computed(() => {
    return items.value.reduce((sum, i) => sum + (Number(i.so_luong) || 0) * (Number(i.don_gia_nhap) || 0), 0);
});

function getProductUnit(id: number): string {
    return props.products.find((p) => p.id === id)?.don_vi_tinh || 'kg';
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
}

function handleSubmit() {
    if (!supplierId.value) {
        alert('Vui lòng chọn nhà cung cấp!');
        return;
    }

    if (items.value.length === 0) {
        alert('Vui lòng thêm ít nhất 1 mặt hàng nhập kho!');
        return;
    }

    isSubmitting.value = true;
    router.post(
        route('store.inbound.store'),
        {
            nha_cung_cap_id: supplierId.value,
            store_id: props.currentStore?.id,
            ghi_chu: note.value,
            items: items.value,
        },
        {
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}
</script>

<template>
    <StoreOpsLayout title="Lập Phiếu Nhập Kho Mới" :stores="stores" :currentStore="currentStore">
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- TOP BAR -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('store.inbound.index')"
                    class="inline-flex items-center space-x-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 shadow-sm transition hover:text-slate-900"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Quay lại danh sách</span>
                </Link>

                <h2 class="text-base font-black text-slate-900">LẬP PHIẾU NHẬP KHO THỰC PHẨM THEO LÔ</h2>
            </div>

            <!-- FORM CARD -->
            <div class="space-y-6 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <!-- GENERAL INFO -->
                <div class="grid grid-cols-1 gap-4 border-b border-slate-100 pb-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Nhà Cung Cấp:</label>
                        <select
                            v-model="supplierId"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs font-bold text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                            <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">
                                {{ sup.ten_nha_cung_cap }} ({{ sup.so_dien_thoai }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Ghi chú phiếu nhập:</label>
                        <input
                            v-model="note"
                            type="text"
                            placeholder="Ví dụ: Đợt nhập rau củ sáng sớm Đà Lạt..."
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-800 outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <!-- ITEMS TABLE -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">Danh Sách Mặt Hàng & Thông Tin Lô Hàng HSD</h4>
                        <button
                            type="button"
                            @click="addRow"
                            class="flex items-center space-x-1 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-600 hover:text-white"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            <span>Thêm dòng sản phẩm</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in items"
                            :key="index"
                            class="relative grid grid-cols-1 items-end gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 p-4 sm:grid-cols-12"
                        >
                            <!-- SAN PHAM -->
                            <div class="sm:col-span-3">
                                <label class="mb-1 block text-[11px] font-bold text-slate-600">Sản phẩm:</label>
                                <select
                                    v-model="item.san_pham_id"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500"
                                >
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.ten_san_pham }} ({{ p.don_vi_tinh }})</option>
                                </select>
                            </div>

                            <!-- MA LO -->
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[11px] font-bold text-slate-600">Mã Lô (Tự sinh nếu trống):</label>
                                <input
                                    v-model="item.ma_lo"
                                    type="text"
                                    placeholder="Tự sinh..."
                                    class="w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 font-mono text-xs text-slate-800 outline-none focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>

                            <!-- NGAY SAN XUAT -->
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[11px] font-bold text-slate-600">Ngày SX:</label>
                                <input
                                    v-model="item.ngay_san_xuat"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs text-slate-800 outline-none focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>

                            <!-- NGAY HET HAN -->
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[11px] font-bold text-slate-600">Hạn Dùng (HSD):</label>
                                <input
                                    v-model="item.ngay_het_han"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold text-emerald-800 outline-none focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>

                            <!-- SO LUONG -->
                            <div class="sm:col-span-1">
                                <label class="mb-1 block text-[11px] font-bold text-slate-600">Số lượng:</label>
                                <input
                                    v-model.number="item.so_luong"
                                    type="number"
                                    step="0.1"
                                    min="0.1"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-2 py-2 text-xs font-black text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>

                            <!-- DON GIA NHAP -->
                            <div class="sm:col-span-1">
                                <label class="mb-1 block text-[11px] font-bold text-slate-600">Đơn giá:</label>
                                <input
                                    v-model.number="item.don_gia_nhap"
                                    type="number"
                                    step="1000"
                                    min="0"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-2 py-2 text-xs font-bold text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>

                            <!-- REMOVE -->
                            <div class="flex justify-center pb-1 sm:col-span-1">
                                <button
                                    type="button"
                                    @click="removeRow(index)"
                                    class="p-2 text-slate-400 transition hover:text-red-600"
                                    title="Xóa dòng"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TOTALS & SUBMIT -->
                <div class="flex flex-col items-center justify-between gap-4 border-t border-slate-100 pt-4 sm:flex-row">
                    <div class="flex items-center space-x-4 text-xs font-bold">
                        <span class="text-slate-500"
                            >Tổng mặt hàng: <strong class="text-slate-900">{{ items.length }}</strong></span
                        >
                        <span class="text-slate-500"
                            >Tổng số lượng: <strong class="text-slate-900">{{ totalQuantity.toFixed(1) }}</strong></span
                        >
                        <span class="text-slate-500"
                            >Tổng giá trị nhập: <strong class="text-sm text-emerald-700">{{ formatPrice(totalAmount) }}</strong></span
                        >
                    </div>

                    <button
                        type="button"
                        @click="handleSubmit"
                        :disabled="isSubmitting"
                        class="rounded-2xl bg-emerald-600 px-6 py-3 text-xs font-black text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-700 active:scale-[0.99]"
                    >
                        {{ isSubmitting ? 'Đang tạo lô & lưu kho...' : 'HOÀN THÀNH NHẬP KHO & CẬP NHẬT TỒN' }}
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>
