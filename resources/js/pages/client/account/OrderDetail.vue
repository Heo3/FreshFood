<script setup lang="ts">
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import { useToast } from '@/composables/useToast';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Calendar, ChevronRight, Clock, CreditCard, RotateCcw, Star, X } from 'lucide-vue-next';
import { ref } from 'vue';

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
    items:
        props.order.chi_tiets?.map((item) => ({
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
    router.post(
        route('account.return.store'),
        {
            don_hang_id: props.order.id,
            loai_doi_tra: returnForm.value.loai_doi_tra,
            ly_do: returnForm.value.ly_do,
            items: selectedItems.map((i) => ({
                san_pham_id: i.san_pham_id,
                so_luong: i.so_luong,
                don_gia: i.don_gia,
            })),
        },
        {
            onSuccess: () => {
                isReturnModalOpen.value = false;
            },
            onFinish: () => {
                isSubmittingReturn.value = false;
            },
        },
    );
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
        },
    );
}

function submitCancel() {
    const finalReason =
        cancelReasonSelect.value === 'Khác'
            ? cancelReasonCustom.value.trim()
            : cancelReasonCustom.value.trim()
              ? `${cancelReasonSelect.value} - ${cancelReasonCustom.value.trim()}`
              : cancelReasonSelect.value;

    isSubmittingCancel.value = true;
    router.post(
        route('account.orders.cancel', props.order.id),
        {
            ly_do: finalReason || 'Khách hàng yêu cầu hủy đơn',
        },
        {
            onSuccess: () => {
                isCancelModalOpen.value = false;
            },
            onFinish: () => {
                isSubmittingCancel.value = false;
            },
        },
    );
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
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-7xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <Link :href="route('account.orders')" class="transition-colors hover:text-emerald-600">Đơn hàng</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">{{ order.ma_don_hang }}</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <!-- SIDEBAR -->
                <div class="lg:col-span-1">
                    <AccountSidebar activeTab="orders" />
                </div>

                <!-- ORDER DETAIL MAIN -->
                <div class="space-y-6 lg:col-span-3">
                    <!-- TOP BAR -->
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <Link :href="route('account.orders')" class="flex items-center text-xs font-bold text-gray-500 hover:text-emerald-600">
                            <ArrowLeft class="mr-1 h-4 w-4" /> Quay lại danh sách đơn hàng
                        </Link>

                        <div class="flex items-center space-x-2">
                            <!-- NÚT HỦY ĐƠN HÀNG KHI CHỜ XÁC NHẬN -->
                            <button
                                v-if="order.trang_thai_don_hang === 'cho_xac_nhan'"
                                @click="isCancelModalOpen = true"
                                class="shadow-xs flex items-center space-x-1.5 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-xs font-bold text-red-700 transition-colors hover:bg-red-100"
                            >
                                <Ban class="h-3.5 w-3.5 text-red-600" />
                                <span>Hủy đơn hàng</span>
                            </button>

                            <!-- THÔNG BÁO KHÔNG THỂ HỦY KHI ĐÃ DUYỆT VÀ ĐANG CHUẨN BỊ -->
                            <div
                                v-else-if="order.trang_thai_don_hang === 'dang_chuan_bi'"
                                class="flex items-center space-x-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-1.5 text-xs font-bold text-blue-800"
                                title="Đơn hàng đã được duyệt và đang chuẩn bị đóng gói, không thể hủy đơn"
                            >
                                <Clock class="h-3.5 w-3.5 text-blue-600" />
                                <span>Đang chuẩn bị (Không thể hủy)</span>
                            </div>

                            <!-- ĐỔI TRẢ BUTTON NẾU ĐÃ GIAO / HOÀN THÀNH -->
                            <button
                                v-if="
                                    order.trang_thai_don_hang === 'da_giao' ||
                                    order.trang_thai_don_hang === 'hoan_thanh' ||
                                    order.trang_thai_don_hang === 'dang_giao'
                                "
                                @click="isReturnModalOpen = true"
                                class="flex items-center space-x-1.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-bold text-amber-900 shadow-sm transition-colors hover:bg-amber-100"
                            >
                                <RotateCcw class="h-3.5 w-3.5 text-amber-700" />
                                <span>Yêu cầu Đổi / Trả hàng dập nát (24h)</span>
                            </button>
                        </div>
                    </div>

                    <!-- ORDER SUMMARY CARD -->
                    <div class="space-y-6 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                        <div class="flex flex-col justify-between gap-3 border-b border-gray-100 pb-6 sm:flex-row sm:items-center">
                            <div>
                                <span class="mb-0.5 block text-xs text-gray-400">Chi tiết đơn hàng</span>
                                <h1 class="font-mono text-xl font-black text-gray-900 sm:text-2xl">{{ order.ma_don_hang }}</h1>
                                <p class="mt-1 flex items-center text-xs text-gray-500">
                                    <Calendar class="mr-1 h-3.5 w-3.5 text-gray-400" />
                                    Ngày đặt: {{ new Date(order.created_at).toLocaleString('vi-VN') }}
                                </p>
                            </div>

                            <span
                                class="w-fit rounded-full px-3.5 py-1 text-xs font-black uppercase tracking-wider"
                                :class="statusMap[order.trang_thai_don_hang]?.class || 'bg-gray-100 text-gray-800'"
                            >
                                {{ statusMap[order.trang_thai_don_hang]?.label || order.trang_thai_don_hang }}
                            </span>
                        </div>

                        <!-- THÔNG BÁO RÕ RÀNG KHI ĐÃ DUYỆT VÀ ĐANG CHUẨN BỊ -->
                        <div
                            v-if="order.trang_thai_don_hang === 'dang_chuan_bi'"
                            class="flex items-start justify-between gap-3 rounded-2xl border border-blue-200 bg-blue-50/80 p-4 text-xs sm:items-center"
                        >
                            <div class="flex items-center space-x-2.5 text-blue-900">
                                <Clock class="h-5 w-5 shrink-0 text-blue-600" />
                                <div>
                                    <span class="block font-bold text-blue-950">Đơn hàng đã được duyệt và đang chuẩn bị đóng gói</span>
                                    <span class="text-[11px] text-blue-700"
                                        >Đơn hàng đã được xác nhận và nhân viên đang soạn hàng từ kho. Quý khách không thể tự hủy đơn hàng lúc
                                        này.</span
                                    >
                                </div>
                            </div>
                            <span class="shrink-0 whitespace-nowrap rounded-lg bg-blue-100 px-2.5 py-1 text-[11px] font-bold text-blue-800">
                                Không thể hủy
                            </span>
                        </div>

                        <!-- ADDRESS & BRANCH INFO -->
                        <div class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2">
                            <div class="rounded-2xl bg-gray-50 p-4">
                                <span class="mb-1 block font-bold text-gray-400">Địa chỉ nhận hàng:</span>
                                <strong class="block text-sm text-gray-900">{{ order.ten_nguoi_nhan }}</strong>
                                <span class="mb-1 block text-gray-600">{{ order.so_dien_thoai_nhan }}</span>
                                <p class="font-medium leading-relaxed text-gray-700">{{ order.dia_chi_giao }}</p>
                            </div>

                            <div class="rounded-2xl bg-gray-50 p-4">
                                <span class="mb-1 block font-bold text-gray-400">Chi nhánh & Thanh toán:</span>
                                <strong class="block text-sm text-emerald-800">{{ order.cua_hang?.ten_cua_hang }}</strong>
                                <p class="mb-2 text-[11px] text-gray-500">{{ order.cua_hang?.dia_chi }}</p>
                                <p class="text-gray-700">
                                    Phương thức:
                                    <strong class="text-gray-900">{{
                                        order.phuong_thuc_thanh_toan === 'vnpay'
                                            ? 'Quét mã VNPAY-QR'
                                            : order.phuong_thuc_thanh_toan === 'cod'
                                              ? 'Tiền mặt khi nhận hàng (COD)'
                                              : order.phuong_thuc_thanh_toan.toUpperCase()
                                    }}</strong>
                                </p>
                            </div>
                        </div>

                        <!-- VNPAY-QR QUICK SCAN IF UNPAID -->
                        <div
                            v-if="
                                (order.phuong_thuc_thanh_toan === 'vnpay' || order.phuong_thuc_thanh_toan === 'chuyen_khoan') &&
                                order.trang_thai_thanh_toan !== 'da_thanh_toan'
                            "
                            class="flex flex-col items-center gap-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-xs sm:flex-row"
                        >
                            <img
                                :src="`https://img.vietqr.io/image/VPB-0329643444-compact2.png?amount=${order.tong_thanhtoan}&addInfo=VNPAY%20${order.ma_don_hang}&accountName=PHAN%20VAN%20THIEN%20DUY`"
                                alt="Mã VNPAY-QR"
                                class="h-28 w-28 shrink-0 rounded-xl border border-blue-300 bg-white p-1"
                            />
                            <div class="space-y-1 text-slate-700">
                                <h4 class="text-sm font-bold text-blue-900">Quét mã VNPAY-QR để thanh toán</h4>
                                <p class="text-[11px] text-slate-600">
                                    Mở ứng dụng Ngân hàng hoặc Ví VNPAY để quét mã này và thanh toán
                                    <strong class="text-blue-700">{{ formatPrice(order.tong_thanhtoan) }}</strong
                                    >.
                                </p>
                                <p class="text-[11px] text-slate-600">
                                    Ngân hàng: <strong>VPBank</strong> - STK: <strong class="font-mono text-blue-700">0329643444</strong> (PHAN VAN
                                    THIEN DUY)
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    Nội dung chuyển khoản: <strong class="font-mono text-blue-700">VNPAY {{ order.ma_don_hang }}</strong>
                                </p>
                                <button
                                    v-if="order.phuong_thuc_thanh_toan === 'vnpay'"
                                    @click="router.post(route('checkout.vnpay-payment', order.ma_don_hang))"
                                    type="button"
                                    class="mt-2 inline-flex items-center space-x-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700 cursor-pointer"
                                >
                                    <CreditCard class="h-3.5 w-3.5" />
                                    <span>Mở Cổng Thanh Toán VNPay Sandbox</span>
                                </button>
                            </div>
                        </div>

                        <!-- ITEMS TABLE -->
                        <div>
                            <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400">Sản phẩm trong đơn:</h3>
                            <div class="divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-100 text-xs">
                                <div
                                    v-for="item in order.chi_tiets"
                                    :key="item.id"
                                    class="flex items-center justify-between p-4 transition-colors hover:bg-gray-50"
                                >
                                    <div class="flex items-center space-x-3.5">
                                        <img
                                            :src="item.hinh_anh_snapshot || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=200'"
                                            :alt="item.ten_san_pham"
                                            class="h-12 w-12 shrink-0 rounded-xl bg-gray-100 object-cover"
                                        />
                                        <div>
                                            <p class="font-bold text-gray-900">{{ item.ten_san_pham }}</p>
                                            <p class="text-[11px] text-gray-400">{{ item.so_luong }} × {{ formatPrice(item.don_gia) }}</p>
                                            <button
                                                v-if="order.trang_thai_don_hang === 'da_giao' || order.trang_thai_don_hang === 'hoan_thanh'"
                                                @click="openReviewModal(item)"
                                                type="button"
                                                class="mt-1.5 inline-flex cursor-pointer items-center space-x-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 transition-colors hover:bg-amber-100 hover:text-amber-800"
                                            >
                                                <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-500" />
                                                <span>Đánh giá sản phẩm</span>
                                            </button>
                                        </div>
                                    </div>
                                    <span class="font-black text-gray-900">{{ formatPrice(item.thanh_tien) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- PRICE BREAKDOWN -->
                        <div class="ml-auto max-w-xs space-y-2 border-t border-gray-100 pt-4 text-xs text-gray-600">
                            <div class="flex justify-between">
                                <span>Tiền hàng:</span>
                                <strong class="text-gray-900">{{ formatPrice(order.tong_tien_hang) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Phí vận chuyển:</span>
                                <strong class="text-gray-900">{{ formatPrice(order.phi_giao_hang) }}</strong>
                            </div>
                            <div v-if="order.tien_giam_gia > 0" class="flex justify-between font-bold text-emerald-700">
                                <span>Giảm giá / Voucher:</span>
                                <span>-{{ formatPrice(order.tien_giam_gia) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-black text-gray-900">
                                <span>Tổng thanh toán:</span>
                                <span class="text-lg font-black text-emerald-700">{{ formatPrice(order.tong_thanhtoan) }}</span>
                            </div>
                        </div>

                        <!-- RETURN TICKETS HISTORY IF ANY -->
                        <div v-if="order.phieu_doi_tras?.length > 0" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-xs">
                            <h4 class="mb-2 flex items-center font-bold text-amber-950">
                                <RotateCcw class="mr-1 h-4 w-4 text-amber-700" /> Lịch sử yêu cầu đổi trả của đơn:
                            </h4>
                            <div v-for="p in order.phieu_doi_tras" :key="p.id" class="border-t border-amber-200/60 py-2">
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="font-mono font-bold">{{ p.ma_phieu }}</span>
                                    <span
                                        class="rounded px-2 py-0.5 text-[10px] font-bold"
                                        :class="
                                            p.trang_thai === 1
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : p.trang_thai === 2
                                                  ? 'bg-red-100 text-red-800'
                                                  : 'bg-amber-100 text-amber-800'
                                        "
                                    >
                                        {{ p.trang_thai === 1 ? 'Đã duyệt' : p.trang_thai === 2 ? 'Từ chối' : 'Đang xử lý' }}
                                    </span>
                                </div>
                                <p class="text-gray-700">Lý do: {{ p.ly_do }}</p>
                                <p class="text-[11px] text-gray-500">Số tiền hoàn/đổi: {{ formatPrice(p.tong_tien_hoan) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RETURN/EXCHANGE MODAL -->
        <div v-if="isReturnModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg space-y-4 rounded-3xl bg-white p-6 shadow-2xl duration-200 animate-in fade-in">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="flex items-center text-base font-black text-gray-900">
                        <RotateCcw class="mr-2 h-5 w-5 text-amber-600" /> Yêu Cầu Đổi / Trả Thực Phẩm
                    </h3>
                    <button @click="isReturnModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <p class="text-xs leading-relaxed text-gray-500">
                    Fresh Food cam kết đổi mới hoặc hoàn tiền 100% nếu thực phẩm bị dập nát, ôi thiu trong vòng 24h kể từ khi nhận hàng.
                </p>

                <!-- Select products to return -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-700">Chọn sản phẩm cần đổi/trả:</label>
                    <div class="max-h-40 space-y-1.5 overflow-y-auto rounded-xl border border-gray-100 p-2 text-xs">
                        <label
                            v-for="item in returnForm.items"
                            :key="item.san_pham_id"
                            class="flex cursor-pointer items-center space-x-2 rounded-lg p-2 hover:bg-gray-50"
                        >
                            <input type="checkbox" v-model="item.selected" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                            <span class="flex-1 truncate font-medium text-gray-800">{{ item.ten_san_pham }}</span>
                            <span class="shrink-0 text-gray-400">{{ item.so_luong }} × {{ formatPrice(item.don_gia) }}</span>
                        </label>
                    </div>
                </div>

                <!-- Return method -->
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <label
                        class="flex cursor-pointer items-center space-x-2 rounded-xl border p-3"
                        :class="
                            returnForm.loai_doi_tra === 'hoan_tien'
                                ? 'border-emerald-600 bg-emerald-50 font-bold text-emerald-900'
                                : 'border-gray-200'
                        "
                    >
                        <input type="radio" value="hoan_tien" v-model="returnForm.loai_doi_tra" class="text-emerald-600" />
                        <span>Hoàn tiền đơn</span>
                    </label>
                    <label
                        class="flex cursor-pointer items-center space-x-2 rounded-xl border p-3"
                        :class="
                            returnForm.loai_doi_tra === 'doi_hang' ? 'border-emerald-600 bg-emerald-50 font-bold text-emerald-900' : 'border-gray-200'
                        "
                    >
                        <input type="radio" value="doi_hang" v-model="returnForm.loai_doi_tra" class="text-emerald-600" />
                        <span>Đổi sản phẩm mới</span>
                    </label>
                </div>

                <!-- Reason input -->
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-700">Mô tả tình trạng hư hỏng / dập nát *</label>
                    <textarea
                        v-model="returnForm.ly_do"
                        rows="3"
                        placeholder="Vui lòng mô tả chi tiết: rau bị dập úng, cá có mùi lạ, hộp trái cây bị vỡ..."
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs focus:border-emerald-500 focus:outline-none"
                    ></textarea>
                </div>

                <!-- Actions -->
                <div class="flex space-x-3 pt-2">
                    <button
                        type="button"
                        @click="isReturnModalOpen = false"
                        class="flex-1 rounded-xl bg-gray-100 py-3 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Hủy bỏ
                    </button>
                    <button
                        type="button"
                        @click="submitReturn"
                        :disabled="isSubmittingReturn"
                        class="flex-1 rounded-xl bg-emerald-600 py-3 text-xs font-bold text-white transition-colors hover:bg-emerald-700 disabled:opacity-50"
                    >
                        {{ isSubmittingReturn ? 'Đang gửi...' : 'Gửi yêu cầu' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- CANCEL ORDER CONFIRMATION MODAL -->
        <div v-if="isCancelModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md space-y-4 rounded-3xl bg-white p-6 shadow-2xl duration-200 animate-in fade-in">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="flex items-center text-base font-black text-gray-900">
                        <Ban class="mr-2 h-5 w-5 text-red-600" /> Xác Nhận Hủy Đơn Hàng
                    </h3>
                    <button @click="isCancelModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-1 rounded-2xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-800">
                    <p class="font-bold">Lưu ý khi hủy đơn hàng:</p>
                    <p class="text-[11px] leading-relaxed">
                        Bạn chỉ có thể tự hủy đơn khi đơn hàng đang ở trạng thái <strong>Chờ xác nhận</strong>. Khi chi nhánh đã duyệt và chuyển sang
                        trạng thái <strong>Đang chuẩn bị</strong> hàng, đơn hàng sẽ không thể hủy được nữa.
                    </p>
                </div>

                <!-- Cancellation reason select -->
                <div class="space-y-1.5 text-xs">
                    <label class="block font-bold text-gray-700">Chọn lý do hủy đơn *</label>
                    <select
                        v-model="cancelReasonSelect"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 focus:border-red-500 focus:outline-none"
                    >
                        <option value="Đổi ý không muốn mua nữa">Đổi ý không muốn mua nữa</option>
                        <option value="Muốn thay đổi địa chỉ / số điện thoại">Muốn thay đổi địa chỉ / số điện thoại nhận</option>
                        <option value="Đặt nhầm sản phẩm hoặc số lượng">Đặt nhầm sản phẩm hoặc số lượng</option>
                        <option value="Tìm thấy nơi khác có giá hoặc thời gian giao tốt hơn">
                            Tìm thấy nơi khác có giá hoặc thời gian giao tốt hơn
                        </option>
                        <option value="Khác">Lý do khác...</option>
                    </select>
                </div>

                <!-- Optional Custom Note -->
                <div class="space-y-1.5 text-xs">
                    <label class="block font-bold text-gray-700">Ghi chú thêm (tùy chọn)</label>
                    <textarea
                        v-model="cancelReasonCustom"
                        rows="2"
                        placeholder="Nhập thêm chi tiết lý do bạn muốn hủy đơn..."
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs focus:border-red-500 focus:outline-none"
                    ></textarea>
                </div>

                <!-- Actions -->
                <div class="flex space-x-3 pt-2">
                    <button
                        type="button"
                        @click="isCancelModalOpen = false"
                        class="flex-1 rounded-xl bg-gray-100 py-3 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Đóng
                    </button>
                    <button
                        type="button"
                        @click="submitCancel"
                        :disabled="isSubmittingCancel"
                        class="flex flex-1 items-center justify-center space-x-1 rounded-xl bg-red-600 py-3 text-xs font-bold text-white transition-colors hover:bg-red-700 disabled:opacity-50"
                    >
                        <span>{{ isSubmittingCancel ? 'Đang hủy...' : 'Xác nhận hủy đơn' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- REVIEW MODAL -->
        <div v-if="isReviewModalOpen" class="backdrop-blur-xs fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md space-y-5 rounded-3xl bg-white p-6 shadow-2xl duration-200 animate-in fade-in zoom-in">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <Star class="h-5 w-5 fill-amber-400 text-amber-500" />
                        <h3 class="text-base font-black text-gray-900">Đánh Giá Sản Phẩm</h3>
                    </div>
                    <button
                        @click="isReviewModalOpen = false"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition hover:bg-gray-200 hover:text-gray-700"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex items-center space-x-3 rounded-2xl bg-gray-50 p-3">
                    <img
                        :src="selectedItemForReview?.hinh_anh_snapshot || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=200'"
                        :alt="selectedItemForReview?.ten_san_pham"
                        class="h-12 w-12 shrink-0 rounded-xl border border-gray-200 bg-white object-cover"
                    />
                    <div>
                        <h4 class="line-clamp-1 text-xs font-bold text-gray-900">{{ selectedItemForReview?.ten_san_pham }}</h4>
                        <span class="text-[11px] text-gray-500">Đơn hàng: #{{ order.ma_don_hang }}</span>
                    </div>
                </div>

                <!-- Star Rating Selector -->
                <div class="space-y-2 text-center">
                    <label class="block text-xs font-bold text-gray-600">Bạn cảm thấy sản phẩm này thế nào?</label>
                    <div class="flex items-center justify-center space-x-2">
                        <button
                            v-for="s in 5"
                            :key="s"
                            type="button"
                            @click="reviewStar = s"
                            class="cursor-pointer p-1 transition-transform hover:scale-125"
                        >
                            <Star
                                class="h-8 w-8 transition-colors"
                                :class="s <= reviewStar ? 'fill-amber-400 text-amber-400' : 'text-gray-300 hover:text-amber-300'"
                            />
                        </button>
                    </div>
                    <p class="text-xs font-bold text-amber-600">
                        {{
                            reviewStar === 5
                                ? 'Tuyệt vời, rất tươi ngon!'
                                : reviewStar === 4
                                  ? 'Hài lòng'
                                  : reviewStar === 3
                                    ? 'Bình thường'
                                    : 'Chưa hài lòng'
                        }}
                    </p>
                </div>

                <!-- Comment Input -->
                <div class="space-y-1.5 text-xs">
                    <label class="block font-bold text-gray-700">Nhận xét chi tiết *</label>
                    <textarea
                        v-model="reviewText"
                        rows="3"
                        placeholder="Hãy chia sẻ thêm về chất lượng sản phẩm, độ tươi, cách đóng gói..."
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs focus:border-amber-500 focus:outline-none"
                    ></textarea>
                </div>

                <!-- Modal Actions -->
                <div class="flex space-x-3 pt-2">
                    <button
                        type="button"
                        @click="isReviewModalOpen = false"
                        class="flex-1 cursor-pointer rounded-xl bg-gray-100 py-3 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Hủy
                    </button>
                    <button
                        type="button"
                        @click="submitReview"
                        :disabled="isSubmittingReview"
                        class="flex flex-1 cursor-pointer items-center justify-center space-x-1 rounded-xl bg-amber-500 py-3 text-xs font-bold text-white shadow-md shadow-amber-500/20 transition-colors hover:bg-amber-600 disabled:opacity-50"
                    >
                        <span>{{ isSubmittingReview ? 'Đang gửi...' : 'Gửi Đánh Giá' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
