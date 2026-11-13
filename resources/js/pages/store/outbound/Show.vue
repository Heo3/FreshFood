<script setup lang="ts">
import { ref } from 'vue';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    ArrowLeft,
    FileOutput,
    Calendar,
    User,
    CheckCircle2,
    Layers,
    Printer,
    Truck,
    Clock,
    PackageCheck,
} from 'lucide-vue-next';
import { Link, router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

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
        }
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
        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- TOP BAR -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('store.outbound.index')"
                    class="inline-flex items-center space-x-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 bg-white px-3.5 py-2 rounded-xl border border-slate-200 shadow-sm transition"
                >
                    <ArrowLeft class="w-4 h-4" />
                    <span>Quay lại danh sách phiếu xuất</span>
                </Link>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm transition cursor-pointer"
                    >
                        <Printer class="w-4 h-4 text-emerald-400" />
                        <span>In Phiếu Xuất Kho</span>
                    </button>
                    <h2 class="text-base font-black text-slate-900 hidden sm:block">#{{ phieuXuat.ma_phieu }}</h2>
                </div>
            </div>

            <!-- VOUCHER SUMMARY -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 block font-medium">Mã phiếu xuất:</span>
                    <strong class="text-slate-900 font-mono text-sm">{{ phieuXuat.ma_phieu }}</strong>
                </div>
                <div>
                    <span class="text-slate-400 block font-medium">Thời gian xuất:</span>
                    <strong class="text-slate-900">{{ formatDate(phieuXuat.xuat_luc) }}</strong>
                </div>
                <div>
                    <span class="text-slate-400 block font-medium">Người thực hiện:</span>
                    <strong class="text-slate-900">{{ phieuXuat.nguoi_tao?.name || 'Hệ thống' }}</strong>
                </div>
                <div v-if="phieuXuat.loai_xuat === 2">
                    <span class="text-slate-400 block font-medium">Điều chuyển sang:</span>
                    <strong class="text-sky-700 font-bold text-sm">{{ phieuXuat.cua_hang_nhan?.ten_cua_hang || 'Chi nhánh khác' }}</strong>
                </div>
                <div v-else>
                    <span class="text-slate-400 block font-medium">Đơn hàng liên quan:</span>
                    <strong class="text-emerald-700 font-mono text-sm">{{ phieuXuat.don_hang?.ma_don_hang || 'Không' }}</strong>
                </div>
            </div>

            <!-- 2-STEP TRANSFER NOTIFICATION / RECEIPT CONFIRMATION -->
            <div v-if="phieuXuat.loai_xuat === 2">
                <!-- Case 1: Already received at destination store -->
                <div
                    v-if="phieuXuat.trang_thai === 3"
                    class="p-5 rounded-3xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs"
                >
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold shrink-0 shadow-sm">
                            <CheckCircle2 class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-bold text-emerald-950 text-sm">Hàng đã được nhập vào kho đích thành công!</h4>
                            <p class="text-emerald-800 text-[11px] mt-0.5">
                                Chi nhánh {{ phieuXuat.cua_hang_nhan?.ten_cua_hang }} đã xác nhận nhập kho và số lượng tồn kho đã được ghi nhận.
                            </p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-900 font-black rounded-full text-[11px]">
                        Hoàn tất nhập chuyển
                    </span>
                </div>

                <!-- Case 2: In transit & viewing from destination store (Can confirm receipt) -->
                <div
                    v-else-if="Number(phieuXuat.cua_hang_nhan_id) === Number(currentStore?.id)"
                    class="p-6 rounded-3xl bg-gradient-to-r from-sky-50 via-blue-50 to-indigo-50 border-2 border-sky-300 shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-xs"
                >
                    <div class="flex items-start space-x-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-sky-600 text-white flex items-center justify-center font-bold shrink-0 shadow-md">
                            <Truck class="w-6 h-6 animate-pulse" />
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <h4 class="font-black text-sky-950 text-sm">Hàng đang vận chuyển tới chi nhánh của bạn</h4>
                                <span class="bg-amber-100 text-amber-900 font-bold px-2 py-0.5 rounded-full text-[10px]">
                                    Chờ xác nhận nhập
                                </span>
                            </div>
                            <p class="text-sky-800 text-[11px] leading-relaxed">
                                Hàng xuất từ <strong>{{ phieuXuat.cua_hang?.ten_cua_hang }}</strong>. Vui lòng kiểm đếm số lượng thực tế khi xe giao tới và bấm xác nhận để tự động tăng tồn kho chi nhánh.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="confirmTransferReceipt"
                        :disabled="isConfirmingReceipt"
                        class="shrink-0 bg-sky-600 hover:bg-sky-700 text-white font-black px-5 py-3 rounded-2xl shadow-lg shadow-sky-600/30 flex items-center space-x-2 text-xs transition hover:scale-102 active:scale-98 disabled:opacity-50 cursor-pointer"
                    >
                        <PackageCheck class="w-4 h-4" />
                        <span>{{ isConfirmingReceipt ? 'Đang ghi nhận...' : 'Xác Nhận Đã Nhận Hàng Vào Kho' }}</span>
                    </button>
                </div>

                <!-- Case 3: In transit & viewing from source store -->
                <div
                    v-else
                    class="p-5 rounded-3xl bg-amber-50/80 border border-amber-200 flex items-center justify-between text-xs"
                >
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold shrink-0 shadow-sm">
                            <Clock class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-950 text-sm">Hàng đang trên đường vận chuyển tới chi nhánh đích</h4>
                            <p class="text-amber-800 text-[11px] mt-0.5">
                                Đã trừ tồn kho tại chi nhánh xuất (FEFO). Đang chờ chi nhánh <strong>{{ phieuXuat.cua_hang_nhan?.ten_cua_hang }}</strong> nhận hàng và xác nhận nhập kho.
                            </p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-amber-100 text-amber-900 font-black rounded-full text-[11px]">
                        Đang vận chuyển
                    </span>
                </div>
            </div>

            <!-- ALLOCATION DETAILS (FEFO BREAKDOWN) -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-4">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center space-x-2">
                    <Layers class="w-4 h-4 text-emerald-600" />
                    <span>Chi Tiết Mặt Hàng & Phân Bổ Lô Hàng Xuất (FEFO)</span>
                </h3>

                <div class="space-y-4">
                    <div
                        v-for="chiTiet in phieuXuat.chi_tiets"
                        :key="chiTiet.id"
                        class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3"
                    >
                        <div class="flex justify-between items-center text-xs">
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm">{{ chiTiet.san_pham?.ten_san_pham }}</h4>
                                <p class="text-[11px] text-slate-400 font-mono">Barcode: {{ chiTiet.san_pham?.ma_va_vach }}</p>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-slate-900 text-sm">{{ chiTiet.so_luong }} {{ chiTiet.san_pham?.don_vi_tinh }}</span>
                                <p class="text-[11px] text-emerald-700 font-bold">{{ formatPrice(chiTiet.thanh_tien) }}</p>
                            </div>
                        </div>

                        <!-- LOT ALLOCATION TABLE -->
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <table class="w-full text-left text-[11px]">
                                <thead class="bg-slate-100/80 text-slate-500 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="py-2 px-3">Lô Hàng Trừ Kho</th>
                                        <th class="py-2 px-3">Hạn Sử Dụng (HSD)</th>
                                        <th class="py-2 px-3 text-right">Số Lượng Xuất</th>
                                        <th class="py-2 px-3 text-right">Đơn Giá Vốn</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="xk in chiTiet.chi_tiet_xuat_khos" :key="xk.id">
                                        <td class="py-2 px-3 font-mono font-bold text-slate-800">
                                            {{ xk.lo_hang?.ma_lo || 'Lô mặc định' }}
                                        </td>
                                        <td class="py-2 px-3 text-emerald-700 font-bold">
                                            {{ xk.lo_hang?.ngay_het_han ? new Date(xk.lo_hang.ngay_het_han).toLocaleDateString('vi-VN') : 'N/A' }}
                                        </td>
                                        <td class="py-2 px-3 text-right font-black text-slate-900">
                                            {{ xk.so_luong }} {{ chiTiet.san_pham?.don_vi_tinh }}
                                        </td>
                                        <td class="py-2 px-3 text-right text-slate-500">
                                            {{ formatPrice(xk.gia_xuat) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-between items-center text-xs">
                    <span class="font-bold text-slate-500">Tổng số lượng xuất: <strong class="text-slate-900">{{ phieuXuat.tong_so_luong }}</strong></span>
                    <span class="font-black text-base text-emerald-700">TỔNG GIÁ TRỊ XUẤT: {{ formatPrice(phieuXuat.tong_gia_tri) }}</span>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>

