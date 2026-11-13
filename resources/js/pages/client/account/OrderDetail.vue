<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import { useToast } from '@/composables/useToast';
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
    Ban,
    Star,
    MessageSquare,
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

const isCancelModalOpen = ref(false);
const cancelReasonSelect = ref('Đổi ý không muốn mua nữa');
const cancelReasonCustom = ref('');
const isSubmittingCancel = ref(false);

const toast = useToast();
const isReviewModalOpen = ref(false);
const selectedItemForReview = ref<any>(null);
const reviewStar = ref(5);
const reviewText = ref('');
const isSubmittingReview = ref(false);

function openReviewModal(item: any) {
    selectedItemForReview.value = item;
    reviewStar.value = 5;
    reviewText.value = '';
    isReviewModalOpen.value = true;
}

function submitReview() {
    if (!selectedItemForReview.value) return;
    if (!reviewText.value.trim()) {
        toast.warning('Vui lòng nhập lời nhận xét hoặc chia sẻ trải nghiệm của bạn.');
        return;
    }

    isSubmittingReview.value = true;
    router.post(
        route('account.review.store'),
        {
            san_pham_id: selectedItemForReview.value.san_pham_id,
            don_hang_id: props.order.id,
            so_sao: reviewStar.value,
            noi_dung: reviewText.value.trim(),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isReviewModalOpen.value = false;
                toast.success(`Cảm ơn bạn đã đánh giá sản phẩm "${selectedItemForReview.value.ten_san_pham}"!`);
            },
            onError: () => {
                toast.error('Có lỗi xảy ra khi gửi đánh giá.');
            },
            onFinish: () => {
                isSubmittingReview.value = false;
            },
        }
    );
}

function submitCancel() {
    const finalReason = cancelReasonSelect.value === 'Khác'
        ? cancelReasonCustom.value.trim()
        : (cancelReasonCustom.value.trim() ? `${cancelReasonSelect.value} - ${cancelReasonCustom.value.trim()}` : cancelReasonSelect.value);

    isSubmittingCancel.value = true;
    router.post(route('account.orders.cancel', props.order.id), {
        ly_do: finalReason || 'Khách hàng yêu cầu hủy đơn',
    }, {
        onSuccess: () => {
            isCancelModalOpen.value = false;
        },
        onFinish: () => {
            isSubmittingCancel.value = false;
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
    hoan_thanh: { label: 'Đã hoàn thành', class: 'bg-emerald-100 text-emerald-800' },
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
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <Link :href="route('account.orders')" class="text-xs font-bold text-gray-500 hover:text-emerald-600 flex items-center">
                            <ArrowLeft class="w-4 h-4 mr-1" /> Quay lại danh sách đơn hàng
                        </Link>

                        <div class="flex items-center space-x-2">
                            <!-- NÚT HỦY ĐƠN HÀNG KHI CHỜ XÁC NHẬN -->
                            <button
                                v-if="order.trang_thai_don_hang === 'cho_xac_nhan'"
                                @click="isCancelModalOpen = true"
                                class="px-4 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-xs font-bold flex items-center space-x-1.5 transition-colors shadow-xs"
                            >
                                <Ban class="w-3.5 h-3.5 text-red-600" />
                                <span>Hủy đơn hàng</span>
                            </button>

                            <!-- THÔNG BÁO KHÔNG THỂ HỦY KHI ĐÃ DUYỆT VÀ ĐANG CHUẨN BỊ -->
                            <div
                                v-else-if="order.trang_thai_don_hang === 'dang_chuan_bi'"
                                class="px-3.5 py-1.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs font-bold flex items-center space-x-1.5"
                                title="Đơn hàng đã được duyệt và đang chuẩn bị đóng gói, không thể hủy đơn"
                            >
                                <Clock class="w-3.5 h-3.5 text-blue-600" />
                                <span>Đang chuẩn bị (Không thể hủy)</span>
                            </div>

                            <!-- ĐỔI TRẢ BUTTON NẾU ĐÃ GIAO / HOÀN THÀNH -->
                            <button
                                v-if="order.trang_thai_don_hang === 'da_giao' || order.trang_thai_don_hang === 'hoan_thanh' || order.trang_thai_don_hang === 'dang_giao'"
                                @click="isReturnModalOpen = true"
                                class="px-4 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 text-xs font-bold flex items-center space-x-1.5 transition-colors shadow-sm"
                            >
                                <RotateCcw class="w-3.5 h-3.5 text-amber-700" />
                                <span>Yêu cầu Đổi / Trả hàng dập nát (24h)</span>
                            </button>
                        </div>
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

                        <!-- THÔNG BÁO RÕ RÀNG KHI ĐÃ DUYỆT VÀ ĐANG CHUẨN BỊ -->
                        <div v-if="order.trang_thai_don_hang === 'dang_chuan_bi'" class="p-4 rounded-2xl bg-blue-50/80 border border-blue-200 flex items-start sm:items-center justify-between gap-3 text-xs">
                            <div class="flex items-center space-x-2.5 text-blue-900">
                                <Clock class="w-5 h-5 text-blue-600 shrink-0" />
                                <div>
                                    <span class="font-bold block text-blue-950">Đơn hàng đã được duyệt và đang chuẩn bị đóng gói</span>
                                    <span class="text-blue-700 text-[11px]">Đơn hàng đã được xác nhận và nhân viên đang soạn hàng từ kho. Quý khách không thể tự hủy đơn hàng lúc này.</span>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-blue-100 text-blue-800 font-bold text-[11px] whitespace-nowrap shrink-0">
                                Không thể hủy
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
                                    Phương thức: <strong class="text-gray-900">{{ order.phuong_thuc_thanh_toan === 'vnpay' ? 'Quét mã VNPAY-QR' : (order.phuong_thuc_thanh_toan === 'cod' ? 'Tiền mặt khi nhận hàng (COD)' : order.phuong_thuc_thanh_toan.toUpperCase()) }}</strong>
                                </p>
                            </div>
                        </div>

                        <!-- VNPAY-QR QUICK SCAN IF UNPAID -->
                        <div
                            v-if="(order.phuong_thuc_thanh_toan === 'vnpay' || order.phuong_thuc_thanh_toan === 'chuyen_khoan') && order.trang_thai_thanh_toan !== 'da_thanh_toan'"
                            class="p-4 rounded-2xl bg-blue-50 border border-blue-200 text-xs flex flex-col sm:flex-row items-center gap-4"
                        >
                            <img
                                :src="`https://img.vietqr.io/image/VPB-0329643444-compact2.png?amount=${order.tong_thanhtoan}&addInfo=VNPAY%20${order.ma_don_hang}&accountName=PHAN%20VAN%20THIEN%20DUY`"
                                alt="Mã VNPAY-QR"
                                class="w-28 h-28 rounded-xl border border-blue-300 bg-white p-1 shrink-0"
                            />
                            <div class="space-y-1 text-slate-700">
                                <h4 class="font-bold text-blue-900 text-sm">Quét mã VNPAY-QR để thanh toán</h4>
                                <p class="text-[11px] text-slate-600">Mở ứng dụng Ngân hàng hoặc Ví VNPAY để quét mã này và thanh toán <strong class="text-blue-700">{{ formatPrice(order.tong_thanhtoan) }}</strong>.</p>
                                <p class="text-[11px] text-slate-600">Ngân hàng: <strong>VPBank</strong> - STK: <strong class="font-mono text-blue-700">0329643444</strong> (PHAN VAN THIEN DUY)</p>
                                <p class="text-[11px] text-slate-500">Nội dung chuyển khoản: <strong class="font-mono text-blue-700">VNPAY {{ order.ma_don_hang }}</strong></p>
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
                                            <button
                                                v-if="order.trang_thai_don_hang === 'da_giao' || order.trang_thai_don_hang === 'hoan_thanh'"
                                                @click="openReviewModal(item)"
                                                type="button"
                                                class="mt-1.5 inline-flex items-center space-x-1 text-[11px] font-bold text-amber-700 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 px-2.5 py-1 rounded-lg border border-amber-200 transition-colors cursor-pointer"
                                            >
                                                <Star class="w-3.5 h-3.5 text-amber-500 fill-amber-400" />
                                                <span>Đánh giá sản phẩm</span>
                                            </button>
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

        <!-- CANCEL ORDER CONFIRMATION MODAL -->
        <div v-if="isCancelModalOpen" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl animate-in fade-in duration-200">
                <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                    <h3 class="font-black text-base text-gray-900 flex items-center">
                        <Ban class="w-5 h-5 mr-2 text-red-600" /> Xác Nhận Hủy Đơn Hàng
                    </h3>
                    <button @click="isCancelModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-3.5 bg-red-50 border border-red-200 rounded-2xl text-xs text-red-800 space-y-1">
                    <p class="font-bold">Lưu ý khi hủy đơn hàng:</p>
                    <p class="text-[11px] leading-relaxed">
                        Bạn chỉ có thể tự hủy đơn khi đơn hàng đang ở trạng thái <strong>Chờ xác nhận</strong>. Khi chi nhánh đã duyệt và chuyển sang trạng thái <strong>Đang chuẩn bị</strong> hàng, đơn hàng sẽ không thể hủy được nữa.
                    </p>
                </div>

                <!-- Cancellation reason select -->
                <div class="space-y-1.5 text-xs">
                    <label class="font-bold text-gray-700 block">Chọn lý do hủy đơn *</label>
                    <select
                        v-model="cancelReasonSelect"
                        class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-red-500 focus:outline-none"
                    >
                        <option value="Đổi ý không muốn mua nữa">Đổi ý không muốn mua nữa</option>
                        <option value="Muốn thay đổi địa chỉ / số điện thoại">Muốn thay đổi địa chỉ / số điện thoại nhận</option>
                        <option value="Đặt nhầm sản phẩm hoặc số lượng">Đặt nhầm sản phẩm hoặc số lượng</option>
                        <option value="Tìm thấy nơi khác có giá hoặc thời gian giao tốt hơn">Tìm thấy nơi khác có giá hoặc thời gian giao tốt hơn</option>
                        <option value="Khác">Lý do khác...</option>
                    </select>
                </div>

                <!-- Optional Custom Note -->
                <div class="space-y-1.5 text-xs">
                    <label class="font-bold text-gray-700 block">Ghi chú thêm (tùy chọn)</label>
                    <textarea
                        v-model="cancelReasonCustom"
                        rows="2"
                        placeholder="Nhập thêm chi tiết lý do bạn muốn hủy đơn..."
                        class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-red-500 focus:outline-none text-xs"
                    ></textarea>
                </div>

                <!-- Actions -->
                <div class="flex space-x-3 pt-2">
                    <button
                        type="button"
                        @click="isCancelModalOpen = false"
                        class="flex-1 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition-colors"
                    >
                        Đóng
                    </button>
                    <button
                        type="button"
                        @click="submitCancel"
                        :disabled="isSubmittingCancel"
                        class="flex-1 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-colors disabled:opacity-50 flex items-center justify-center space-x-1"
                    >
                        <span>{{ isSubmittingCancel ? 'Đang hủy...' : 'Xác nhận hủy đơn' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- REVIEW MODAL -->
        <div
            v-if="isReviewModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
        >
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center space-x-2">
                        <Star class="w-5 h-5 text-amber-500 fill-amber-400" />
                        <h3 class="font-black text-gray-900 text-base">Đánh Giá Sản Phẩm</h3>
                    </div>
                    <button
                        @click="isReviewModalOpen = false"
                        class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-3 bg-gray-50 rounded-2xl flex items-center space-x-3">
                    <img
                        :src="selectedItemForReview?.hinh_anh_snapshot || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=200'"
                        :alt="selectedItemForReview?.ten_san_pham"
                        class="w-12 h-12 rounded-xl object-cover bg-white border border-gray-200 shrink-0"
                    />
                    <div>
                        <h4 class="font-bold text-gray-900 text-xs line-clamp-1">{{ selectedItemForReview?.ten_san_pham }}</h4>
                        <span class="text-[11px] text-gray-500">Đơn hàng: #{{ order.ma_don_hang }}</span>
                    </div>
                </div>

                <!-- Star Rating Selector -->
                <div class="space-y-2 text-center">
                    <label class="text-xs font-bold text-gray-600 block">Bạn cảm thấy sản phẩm này thế nào?</label>
                    <div class="flex items-center justify-center space-x-2">
                        <button
                            v-for="s in 5"
                            :key="s"
                            type="button"
                            @click="reviewStar = s"
                            class="p-1 hover:scale-125 transition-transform cursor-pointer"
                        >
                            <Star
                                class="w-8 h-8 transition-colors"
                                :class="s <= reviewStar ? 'fill-amber-400 text-amber-400' : 'text-gray-300 hover:text-amber-300'"
                            />
                        </button>
                    </div>
                    <p class="text-xs font-bold text-amber-600">
                        {{ reviewStar === 5 ? 'Tuyệt vời, rất tươi ngon!' : reviewStar === 4 ? 'Hài lòng' : reviewStar === 3 ? 'Bình thường' : 'Chưa hài lòng' }}
                    </p>
                </div>

                <!-- Comment Input -->
                <div class="space-y-1.5 text-xs">
                    <label class="font-bold text-gray-700 block">Nhận xét chi tiết *</label>
                    <textarea
                        v-model="reviewText"
                        rows="3"
                        placeholder="Hãy chia sẻ thêm về chất lượng sản phẩm, độ tươi, cách đóng gói..."
                        class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-amber-500 focus:outline-none text-xs"
                    ></textarea>
                </div>

                <!-- Modal Actions -->
                <div class="flex space-x-3 pt-2">
                    <button
                        type="button"
                        @click="isReviewModalOpen = false"
                        class="flex-1 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition-colors cursor-pointer"
                    >
                        Hủy
                    </button>
                    <button
                        type="button"
                        @click="submitReview"
                        :disabled="isSubmittingReview"
                        class="flex-1 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition-colors disabled:opacity-50 flex items-center justify-center space-x-1 cursor-pointer shadow-md shadow-amber-500/20"
                    >
                        <span>{{ isSubmittingReview ? 'Đang gửi...' : 'Gửi Đánh Giá' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
