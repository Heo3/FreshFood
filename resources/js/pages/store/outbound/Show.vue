<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, Clock, Layers, PackageCheck, Printer, Truck } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    stores: Array<any>;
    currentStore: any;
    phieuXuat: any;
}>();

const toast = useToast();
const isConfirmingReceipt = ref(false);

function confirmTransferReceipt() {
    if (!window.confirm('Bạn có chắc chắn đã kiểm đếm đầy đủ số lượng và muốn nhập số hàng này vào kho của chi nhánh?')) {
        return;
    }

    isConfirmingReceipt.value = true;
    router.post(
        route('store.transfer.confirm', props.phieuXuat.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Đã xác nhận nhập kho đích thành công! Tồn kho chi nhánh đã được cập nhật.');
            },
            onError: (err: any) => {
                toast.error(err?.message || 'Có lỗi xảy ra khi xác nhận nhập kho.');
            },
            onFinish: () => {
                isConfirmingReceipt.value = false;
            },
        },
    );
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleString('vi-VN');
}
</script>

<template>
    <StoreOpsLayout :title="`Chi Tiết Phiếu Xuất #${phieuXuat.ma_phieu}`" :stores="stores" :currentStore="currentStore">
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- TOP BAR -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('store.outbound.index')"
                    class="inline-flex items-center space-x-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 shadow-sm transition hover:text-slate-900"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Quay lại danh sách phiếu xuất</span>
                </Link>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800"
                    >
                        <Printer class="h-4 w-4 text-emerald-400" />
                        <span>In Phiếu Xuất Kho</span>
                    </button>
                    <h2 class="hidden text-base font-black text-slate-900 sm:block">#{{ phieuXuat.ma_phieu }}</h2>
                </div>
            </div>

            <!-- VOUCHER SUMMARY -->
            <div class="grid grid-cols-2 gap-4 rounded-3xl border border-slate-200/80 bg-white p-6 text-xs shadow-sm sm:grid-cols-4">
                <div>
                    <span class="block font-medium text-slate-400">Mã phiếu xuất:</span>
                    <strong class="font-mono text-sm text-slate-900">{{ phieuXuat.ma_phieu }}</strong>
                </div>
                <div>
                    <span class="block font-medium text-slate-400">Thời gian xuất:</span>
                    <strong class="text-slate-900">{{ formatDate(phieuXuat.xuat_luc) }}</strong>
                </div>
                <div>
                    <span class="block font-medium text-slate-400">Người thực hiện:</span>
                    <strong class="text-slate-900">{{ phieuXuat.nguoi_tao?.name || 'Hệ thống' }}</strong>
                </div>
                <div v-if="phieuXuat.loai_xuat === 2">
                    <span class="block font-medium text-slate-400">Điều chuyển sang:</span>
                    <strong class="text-sm font-bold text-sky-700">{{ phieuXuat.cua_hang_nhan?.ten_cua_hang || 'Chi nhánh khác' }}</strong>
                </div>
                <div v-else>
                    <span class="block font-medium text-slate-400">Đơn hàng liên quan:</span>
                    <strong class="font-mono text-sm text-emerald-700">{{ phieuXuat.don_hang?.ma_don_hang || 'Không' }}</strong>
                </div>
            </div>

            <!-- 2-STEP TRANSFER NOTIFICATION / RECEIPT CONFIRMATION -->
            <div v-if="phieuXuat.loai_xuat === 2">
                <!-- Case 1: Already received at destination store -->
                <div
                    v-if="phieuXuat.trang_thai === 3"
                    class="flex items-center justify-between rounded-3xl border border-emerald-200 bg-emerald-50 p-5 text-xs"
                >
                    <div class="flex items-center space-x-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 font-bold text-white shadow-sm">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-emerald-950">Hàng đã được nhập vào kho đích thành công!</h4>
                            <p class="mt-0.5 text-[11px] text-emerald-800">
                                Chi nhánh {{ phieuXuat.cua_hang_nhan?.ten_cua_hang }} đã xác nhận nhập kho và số lượng tồn kho đã được ghi nhận.
                            </p>
                        </div>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-black text-emerald-900"> Hoàn tất nhập chuyển </span>
                </div>

                <!-- Case 2: In transit & viewing from destination store (Can confirm receipt) -->
                <div
                    v-else-if="Number(phieuXuat.cua_hang_nhan_id) === Number(currentStore?.id)"
                    class="flex flex-col items-start justify-between gap-4 rounded-3xl border-2 border-sky-300 bg-gradient-to-r from-sky-50 via-blue-50 to-indigo-50 p-6 text-xs shadow-md sm:flex-row sm:items-center"
                >
                    <div class="flex items-start space-x-3.5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-600 font-bold text-white shadow-md">
                            <Truck class="h-6 w-6 animate-pulse" />
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-sm font-black text-sky-950">Hàng đang vận chuyển tới chi nhánh của bạn</h4>
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-900"> Chờ xác nhận nhập </span>
                            </div>
                            <p class="text-[11px] leading-relaxed text-sky-800">
                                Hàng xuất từ <strong>{{ phieuXuat.cua_hang?.ten_cua_hang }}</strong
                                >. Vui lòng kiểm đếm số lượng thực tế khi xe giao tới và bấm xác nhận để tự động tăng tồn kho chi nhánh.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="confirmTransferReceipt"
                        :disabled="isConfirmingReceipt"
                        class="hover:scale-102 active:scale-98 flex shrink-0 cursor-pointer items-center space-x-2 rounded-2xl bg-sky-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-sky-600/30 transition hover:bg-sky-700 disabled:opacity-50"
                    >
                        <PackageCheck class="h-4 w-4" />
                        <span>{{ isConfirmingReceipt ? 'Đang ghi nhận...' : 'Xác Nhận Đã Nhận Hàng Vào Kho' }}</span>
                    </button>
                </div>

                <!-- Case 3: In transit & viewing from source store -->
                <div v-else class="flex items-center justify-between rounded-3xl border border-amber-200 bg-amber-50/80 p-5 text-xs">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-500 font-bold text-white shadow-sm">
                            <Clock class="h-5 w-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-amber-950">Hàng đang trên đường vận chuyển tới chi nhánh đích</h4>
                            <p class="mt-0.5 text-[11px] text-amber-800">
                                Đã trừ tồn kho tại chi nhánh xuất (FEFO). Đang chờ chi nhánh
                                <strong>{{ phieuXuat.cua_hang_nhan?.ten_cua_hang }}</strong> nhận hàng và xác nhận nhập kho.
                            </p>
                        </div>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-black text-amber-900"> Đang vận chuyển </span>
                </div>
            </div>

            <!-- ALLOCATION DETAILS (FEFO BREAKDOWN) -->
            <div class="space-y-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <h3 class="flex items-center space-x-2 text-xs font-black uppercase tracking-wider text-slate-800">
                    <Layers class="h-4 w-4 text-emerald-600" />
                    <span>Chi Tiết Mặt Hàng & Phân Bổ Lô Hàng Xuất (FEFO)</span>
                </h3>

                <div class="space-y-4">
                    <div
                        v-for="chiTiet in phieuXuat.chi_tiets"
                        :key="chiTiet.id"
                        class="space-y-3 rounded-2xl border border-slate-200/80 bg-slate-50 p-4"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">{{ chiTiet.san_pham?.ten_san_pham }}</h4>
                                <p class="font-mono text-[11px] text-slate-400">Barcode: {{ chiTiet.san_pham?.ma_va_vach }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-black text-slate-900">{{ chiTiet.so_luong }} {{ chiTiet.san_pham?.don_vi_tinh }}</span>
                                <p class="text-[11px] font-bold text-emerald-700">{{ formatPrice(chiTiet.thanh_tien) }}</p>
                            </div>
                        </div>

                        <!-- LOT ALLOCATION TABLE -->
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <table class="w-full text-left text-[11px]">
                                <thead class="bg-slate-100/80 font-bold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2">Lô Hàng Trừ Kho</th>
                                        <th class="px-3 py-2">Hạn Sử Dụng (HSD)</th>
                                        <th class="px-3 py-2 text-right">Số Lượng Xuất</th>
                                        <th class="px-3 py-2 text-right">Đơn Giá Vốn</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="xk in chiTiet.chi_tiet_xuat_khos" :key="xk.id">
                                        <td class="px-3 py-2 font-mono font-bold text-slate-800">
                                            {{ xk.lo_hang?.ma_lo || 'Lô mặc định' }}
                                        </td>
                                        <td class="px-3 py-2 font-bold text-emerald-700">
                                            {{ xk.lo_hang?.ngay_het_han ? new Date(xk.lo_hang.ngay_het_han).toLocaleDateString('vi-VN') : 'N/A' }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-black text-slate-900">
                                            {{ xk.so_luong }} {{ chiTiet.san_pham?.don_vi_tinh }}
                                        </td>
                                        <td class="px-3 py-2 text-right text-slate-500">
                                            {{ formatPrice(xk.gia_xuat) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
                    <span class="font-bold text-slate-500"
                        >Tổng số lượng xuất: <strong class="text-slate-900">{{ phieuXuat.tong_so_luong }}</strong></span
                    >
                    <span class="text-base font-black text-emerald-700">TỔNG GIÁ TRỊ XUẤT: {{ formatPrice(phieuXuat.tong_gia_tri) }}</span>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>
