<script setup lang="ts">
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

interface AuditItem {
    san_pham_id: number;
    ten_san_pham: string;
    ma_va_vach: string;
    don_vi_tinh: string;
    danh_muc: string;
    so_luong_he_thong: number;
    lots: Array<any>;
}

const props = defineProps<{
    stores: Array<any>;
    currentStore: any;
    items: Array<AuditItem>;
}>();

const note = ref('Kiểm kê kho thực tế cuối ngày');
const isSubmitting = ref(false);

interface AuditFormRow {
    san_pham_id: number;
    ten_san_pham: string;
    don_vi_tinh: string;
    so_luong_he_thong: number;
    so_luong_thuc_te: number;
    lo_hang_id?: number | null;
    ly_do_lech: string;
}

const formRows = ref<AuditFormRow[]>(
    props.items.map((i) => ({
        san_pham_id: i.san_pham_id,
        ten_san_pham: i.ten_san_pham,
        don_vi_tinh: i.don_vi_tinh,
        so_luong_he_thong: i.so_luong_he_thong,
        so_luong_thuc_te: i.so_luong_he_thong, // Default equals system
        lo_hang_id: i.lots[0]?.id || null,
        ly_do_lech: '',
    })),
);

function getDifference(row: AuditFormRow): number {
    return Number(((row.so_luong_thuc_te || 0) - (row.so_luong_he_thong || 0)).toFixed(2));
}

function handleSubmit() {
    isSubmitting.value = true;
    router.post(
        route('store.audit.store'),
        {
            ghi_chu: note.value,
            store_id: props.currentStore?.id,
            items: formRows.value.map((r) => ({
                san_pham_id: r.san_pham_id,
                lo_hang_id: r.lo_hang_id,
                so_luong_he_thong: r.so_luong_he_thong,
                so_luong_thuc_te: r.so_luong_thuc_te,
                ly_do_lech: r.ly_do_lech || (getDifference(r) !== 0 ? 'Chênh lệch kiểm kê thực tế' : null),
            })),
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
    <StoreOpsLayout title="Lập Biên Bản Kiểm Kê Kho" :stores="stores" :currentStore="currentStore">
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- TOP BAR -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('store.audit.index')"
                    class="inline-flex items-center space-x-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 shadow-sm transition hover:text-slate-900"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Quay lại</span>
                </Link>

                <h2 class="text-base font-black text-slate-900">KIỂM KÊ KHO THỰC PHẨM & CÂN BẰNG TỒN</h2>
            </div>

            <!-- AUDIT FORM -->
            <div class="space-y-6 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Ghi chú đợt kiểm kê:</label>
                    <input
                        v-model="note"
                        type="text"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-xs text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-3 py-3">Sản Phẩm</th>
                                <th class="px-3 py-3 text-right">Tồn Sổ Sách</th>
                                <th class="w-36 px-3 py-3 text-right">Thực Tế Đếm Được</th>
                                <th class="px-3 py-3 text-center">Chênh Lệch</th>
                                <th class="px-3 py-3">Nguyên Nhân Hao Hụt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in formRows" :key="row.san_pham_id" class="hover:bg-slate-50">
                                <td class="px-3 py-3">
                                    <p class="font-bold text-slate-900">{{ row.ten_san_pham }}</p>
                                    <span class="text-[10px] text-slate-400">Đơn vị: {{ row.don_vi_tinh }}</span>
                                </td>
                                <td class="px-3 py-3 text-right font-black text-slate-800">{{ row.so_luong_he_thong }} {{ row.don_vi_tinh }}</td>
                                <td class="px-3 py-3 text-right">
                                    <input
                                        v-model.number="row.so_luong_thuc_te"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-right text-xs font-black text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500"
                                    />
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        v-if="getDifference(row) === 0"
                                        class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600"
                                    >
                                        Khớp
                                    </span>
                                    <span
                                        v-else-if="getDifference(row) < 0"
                                        class="rounded bg-red-100 px-2 py-0.5 text-[11px] font-bold text-red-700"
                                    >
                                        Thiếu {{ getDifference(row) }} {{ row.don_vi_tinh }}
                                    </span>
                                    <span v-else class="rounded bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                        Thừa +{{ getDifference(row) }} {{ row.don_vi_tinh }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <input
                                        v-model="row.ly_do_lech"
                                        type="text"
                                        :placeholder="getDifference(row) < 0 ? 'Dập nát / Hao hụt bốc hơi...' : 'Ghi chú...'"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        @click="handleSubmit"
                        :disabled="isSubmitting"
                        class="rounded-2xl bg-emerald-600 px-6 py-3 text-xs font-black text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-700 active:scale-[0.99]"
                    >
                        {{ isSubmitting ? 'Đang cân bằng kho...' : 'LƯU BIÊN BẢN & CÂN BẰNG TỒN KHO' }}
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>
