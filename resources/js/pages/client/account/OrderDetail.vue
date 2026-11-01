<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import {
    ChevronRight,
    ArrowLeft,
    RotateCcw,
    MapPin,
    Calendar,
    CreditCard,
    CheckCircle2,
    Clock,
    X,
} from 'lucide-vue-next';

const props = defineProps<{
    order: {
        id: number;
        ma_don_hang: string;
        tong_tien_hang: number;
        phi_giao_hang: number;
        tien_giam_gia: number;
        tong_thanhtoan: number;
        phuong_thuc_thanh_toan: string;
        trang_thai_thanh_toan: string;
        trang_thai_don_hang: string;
        ten_nguoi_nhan: string;
        so_dien_thoai_nhan: string;
        dia_chi_giao: string;
        ghi_chu?: string;
        created_at: string;
        cua_hang?: any;
        chi_tiets?: Array<any>;
        phieu_doi_tras?: Array<any>;
    };
}>();

const isReturnModalOpen = ref(false);
const returnForm = ref({
    don_hang_id: props.order.id,
    loai_doi_tra: 'hoan_tien',
    ly_do: '',
    items: props.order.chi_tiets?.map((item) => ({
        san_pham_id: item.san_pham_id,
        ten_san_pham: item.ten_san_pham,
        so_luong: item.so_luong,
        don_gia: item.don_gia,
        selected: false,
    })) || [],
});

const isSubmittingReturn = ref(false);

function submitReturn() {
    const selectedItems = returnForm.value.items.filter((i) => i.selected);
    if (selectedItems.length === 0) {
        alert('Vui lòng chọn ít nhất 1 sản phẩm bị lỗi để đổi/trả.');
        return;
    }
    if (!returnForm.value.ly_do.trim()) {
        alert('Vui lòng nhập lý do yêu cầu đổi trả (VD: rau dập nát khi vận chuyển, cá có mùi hỏng...).');
        return;
    }

    isSubmittingReturn.value = true;
    router.post(route('account.return.store'), {
        don_hang_id: props.order.id,
        loai_doi_tra: returnForm.value.loai_doi_tra,
        ly_do: returnForm.value.ly_do,
        items: selectedItems.map((i) => ({
            san_pham_id: i.san_pham_id,
            so_luong: i.so_luong,
            don_gia: i.don_gia,
        })),
    }, {
        onSuccess: () => {
            isReturnModalOpen.value = false;
        },
        onFinish: () => {
            isSubmittingReturn.value = false;
        },
    });
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}

const statusMap: Record<string, { label: string; class: string }> = {
    cho_xac_nhan: { label: 'Chờ xác nhận', class: 'bg-amber-100 text-amber-800' },
    dang_chuan_bi: { label: 'Đang chuẩn bị', class: 'bg-blue-100 text-blue-800' },
    dang_giao: { label: 'Đang giao hàng', class: 'bg-indigo-100 text-indigo-800' },
    da_giao: { label: 'Đã giao thành công', class: 'bg-emerald-100 text-emerald-800' },
    da_huy: { label: 'Đã hủy', class: 'bg-red-100 text-red-800' },
};
</script>

<template>
    <StoreLayout :title="`Chi Tiết Đơn Hàng ${order.ma_don_hang}`">
        <!-- BREADCRUMBS -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <Link :href="route('account.orders')" class="hover:text-emerald-600 transition-colors">Đơn hàng</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">{{ order.ma_don_hang }}</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- SIDEBAR -->
                <div class="lg:col-span-1">
                    <AccountSidebar activeTab="orders" />
                </div>

                <!-- ORDER DETAIL MAIN -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- TOP BAR -->
                    <div class="flex items-center justify-between">
                        <Link :href="route('account.orders')" class="text-xs font-bold text-gray-500 hover:text-emerald-600 flex items-center">
                            <ArrowLeft class="w-4 h-4 mr-1" /> Quay lại danh sách đơn hàng
                        </Link>

                        <!-- ĐỔI TRẢ BUTTON NẾU ĐÃ GIAO -->
                        <button
                            v-if="order.trang_thai_don_hang === 'da_giao' || order.trang_thai_don_hang === 'dang_giao'"
                            @click="isReturnModalOpen = true"
                            class="px-4 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 text-xs font-bold flex items-center space-x-1.5 transition-colors shadow-sm"
                        >
                            <RotateCcw class="w-3.5 h-3.5 text-amber-700" />
                            <span>Yêu cầu Đổi / Trả hàng dập nát (24h)</span>
                        </button>
                    </div>

                    <!-- ORDER SUMMARY CARD -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-6">
                        <div class="flex flex-col sm:flex-row justify-between sm:items-center pb-6 border-b border-gray-100 gap-3">
                            <div>
                                <span class="text-xs text-gray-400 block mb-0.5">Chi tiết đơn hàng</span>
                                <h1 class="text-xl sm:text-2xl font-black text-gray-900 font-mono">{{ order.ma_don_hang }}</h1>
                                <p class="text-xs text-gray-500 mt-1 flex items-center">
                                    <Calendar class="w-3.5 h-3.5 mr-1 text-gray-400" />
                                    Ngày đặt: {{ new Date(order.created_at).toLocaleString('vi-VN') }}
                                </p>
                            </div>

                            <span
                                class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider w-fit"
                                :class="statusMap[order.trang_thai_don_hang]?.class || 'bg-gray-100 text-gray-800'"
                            >
                                {{ statusMap[order.trang_thai_don_hang]?.label || order.trang_thai_don_hang }}
                            </span>
                        </div>

                        <!-- ADDRESS & BRANCH INFO -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div class="p-4 bg-gray-50 rounded-2xl">
                                <span class="text-gray-400 font-bold block mb-1">Địa chỉ nhận hàng:</span>
                                <strong class="text-gray-900 block text-sm">{{ order.ten_nguoi_nhan }}</strong>
                                <span class="text-gray-600 block mb-1">{{ order.so_dien_thoai_nhan }}</span>
                                <p class="text-gray-700 leading-relaxed font-medium">{{ order.dia_chi_giao }}</p>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-2xl">
                                <span class="text-gray-400 font-bold block mb-1">Chi nhánh & Thanh toán:</span>
                                <strong class="text-emerald-800 block text-sm">{{ order.cua_hang?.ten_cua_hang }}</strong>
                                <p class="text-gray-500 text-[11px] mb-2">{{ order.cua_hang?.dia_chi }}</p>
                                <p class="text-gray-700">
                                    Phương thức: <strong class="uppercase text-gray-900">{{ order.phuong_thuc_thanh_toan }}</strong>
                                </p>
                            </div>
                        </div>

                        <!-- ITEMS TABLE -->
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Sản phẩm trong đơn:</h3>
                            <div class="divide-y divide-gray-100 border border-gray-100 rounded-2xl overflow-hidden text-xs">
                                <div
                                    v-for="item in order.chi_tiets"
                                    :key="item.id"
                                    class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
                                >
                                    <div class="flex items-center space-x-3.5">
                                        <img
                                            :src="item.hinh_anh_snapshot || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=200'"
                                            :alt="item.ten_san_pham"
                                            class="w-12 h-12 rounded-xl object-cover bg-gray-100 shrink-0"
                                        />
                                        <div>
                                            <p class="font-bold text-gray-900">{{ item.ten_san_pham }}</p>
                                            <p class="text-gray-400 text-[11px]">{{ item.so_luong }} × {{ formatPrice(item.don_gia) }}</p>
                                        </div>
                                    </div>
                                    <span class="font-black text-gray-900">{{ formatPrice(item.thanh_tien) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- PRICE BREAKDOWN -->
                        <div class="space-y-2 text-xs text-gray-600 max-w-xs ml-auto pt-4 border-t border-gray-100">
                            <div class="flex justify-between">
                                <span>Tiền hàng:</span>
                                <strong class="text-gray-900">{{ formatPrice(order.tong_tien_hang) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Phí vận chuyển:</span>
                                <strong class="text-gray-900">{{ formatPrice(order.phi_giao_hang) }}</strong>
                            </div>
                            <div v-if="order.tien_giam_gia > 0" class="flex justify-between text-emerald-700 font-bold">
                                <span>Giảm giá / Voucher:</span>
                                <span>-{{ formatPrice(order.tien_giam_gia) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-black text-gray-900 pt-2 border-t border-gray-200">
                                <span>Tổng thanh toán:</span>
                                <span class="text-emerald-700 text-lg font-black">{{ formatPrice(order.tong_thanhtoan) }}</span>
                            </div>
                        </div>

                        <!-- RETURN TICKETS HISTORY IF ANY -->
                        <div v-if="order.phieu_doi_tras?.length > 0" class="mt-6 p-4 rounded-2xl bg-amber-50/70 border border-amber-200 text-xs">
                            <h4 class="font-bold text-amber-950 mb-2 flex items-center">
                                <RotateCcw class="w-4 h-4 mr-1 text-amber-700" /> Lịch sử yêu cầu đổi trả của đơn:
                            </h4>
                            <div v-for="p in order.phieu_doi_tras" :key="p.id" class="py-2 border-t border-amber-200/60">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-mono font-bold">{{ p.ma_phieu }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                        :class="p.trang_thai === 1 ? 'bg-emerald-100 text-emerald-800' : (p.trang_thai === 2 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800')">
                                        {{ p.trang_thai === 1 ? 'Đã duyệt' : (p.trang_thai === 2 ? 'Từ chối' : 'Đang xử lý') }}
                                    </span>
                                </div>
                                <p class="text-gray-700">Lý do: {{ p.ly_do }}</p>
                                <p class="text-gray-500 text-[11px]">Số tiền hoàn/đổi: {{ formatPrice(p.tong_tien_hoan) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RETURN/EXCHANGE MODAL -->
        <div v-if="isReturnModalOpen" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl animate-in fade-in duration-200">
                <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                    <h3 class="font-black text-base text-gray-900 flex items-center">
                        <RotateCcw class="w-5 h-5 mr-2 text-amber-600" /> Yêu Cầu Đổi / Trả Thực Phẩm
                    </h3>
                    <button @click="isReturnModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <p class="text-xs text-gray-500 leading-relaxed">
                    Fresh Food cam kết đổi mới hoặc hoàn tiền 100% nếu thực phẩm bị dập nát, ôi thiu trong vòng 24h kể từ khi nhận hàng.
                </p>

                <!-- Select products to return -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-gray-700 block">Chọn sản phẩm cần đổi/trả:</label>
                    <div class="max-h-40 overflow-y-auto space-y-1.5 border border-gray-100 p-2 rounded-xl text-xs">
                        <label
                            v-for="item in returnForm.items"
                            :key="item.san_pham_id"
                            class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-50 cursor-pointer"
                        >
                            <input type="checkbox" v-model="item.selected" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                            <span class="flex-1 text-gray-800 font-medium truncate">{{ item.ten_san_pham }}</span>
                            <span class="text-gray-400 shrink-0">{{ item.so_luong }} × {{ formatPrice(item.don_gia) }}</span>
                        </label>
                    </div>
                </div>

                <!-- Return method -->
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <label class="p-3 rounded-xl border flex items-center space-x-2 cursor-pointer"
                        :class="returnForm.loai_doi_tra === 'hoan_tien' ? 'border-emerald-600 bg-emerald-50 text-emerald-900 font-bold' : 'border-gray-200'">
                        <input type="radio" value="hoan_tien" v-model="returnForm.loai_doi_tra" class="text-emerald-600" />
                        <span>Hoàn tiền đơn</span>
                    </label>
                    <label class="p-3 rounded-xl border flex items-center space-x-2 cursor-pointer"
                        :class="returnForm.loai_doi_tra === 'doi_hang' ? 'border-emerald-600 bg-emerald-50 text-emerald-900 font-bold' : 'border-gray-200'">
                        <input type="radio" value="doi_hang" v-model="returnForm.loai_doi_tra" class="text-emerald-600" />
                        <span>Đổi sản phẩm mới</span>
                    </label>
                </div>

                <!-- Reason input -->
                <div>
                    <label class="text-xs font-bold text-gray-700 block mb-1">Mô tả tình trạng hư hỏng / dập nát *</label>
                    <textarea
                        v-model="returnForm.ly_do"
                        rows="3"
                        placeholder="Vui lòng mô tả chi tiết: rau bị dập úng, cá có mùi lạ, hộp trái cây bị vỡ..."
                        class="w-full text-xs p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                    ></textarea>
                </div>

                <!-- Actions -->
                <div class="flex space-x-3 pt-2">
                    <button
                        type="button"
                        @click="isReturnModalOpen = false"
                        class="flex-1 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition-colors"
                    >
                        Hủy bỏ
                    </button>
                    <button
                        type="button"
                        @click="submitReturn"
                        :disabled="isSubmittingReturn"
                        class="flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors disabled:opacity-50"
                    >
                        {{ isSubmittingReturn ? 'Đang gửi...' : 'Gửi yêu cầu' }}
                    </button>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
