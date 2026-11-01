<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { useCart } from '@/composables/useCart';
import {
    MapPin,
    CreditCard,
    QrCode,
    Clock,
    Sparkles,
    ChevronRight,
    Coins,
} from 'lucide-vue-next';

const props = defineProps<{
    stores: Array<any>;
    addresses: Array<any>;
    user?: {
        id: number;
        name: string;
        email?: string;
        phone?: string;
        loyalty_points: number;
    } | null;
}>();

const { cartItems, selectedStoreId, subtotal, clearCart, showToast, setStore, refreshCartPrices } = useCart();

const deliveryType = ref<'delivery' | 'pickup'>('delivery');
const selectedAddressId = ref<number | null>(props.addresses?.[0]?.id || null);

const form = ref({
    cua_hang_id: selectedStoreId.value || props.stores?.[0]?.id || 1,
    ten_nguoi_nhan: props.user?.name || '',
    so_dien_thoai_nhan: props.user?.phone || '',
    dia_chi_giao: props.addresses?.[0]?.dia_chi_chi_tiet
        ? `${props.addresses[0].dia_chi_chi_tiet}, ${props.addresses[0].phuong_xa}, ${props.addresses[0].quan_huyen}, ${props.addresses[0].tinh_thanh}`
        : '',
    dia_chi_giao_hang_id: props.addresses?.[0]?.id || null,
    time_slot: '8h - 10h',
    phuong_thuc_thanh_toan: 'cod',
    ghi_chu: '',
    ma_giam_gia_code: '',
    use_points: false,
});

function onStoreBranchChange() {
    const newStoreId = Number(form.value.cua_hang_id);
    setStore(newStoreId);
    refreshCartPrices(newStoreId);
}

const isSubmitting = ref(false);

const shippingFee = computed(() => {
    if (deliveryType.value === 'pickup' || subtotal.value >= 300000) return 0;
    return 25000;
});

const pointsDiscount = computed(() => {
    if (!form.value.use_points || !props.user || props.user.loyalty_points <= 0) return 0;
    const maxUsable = Math.min(props.user.loyalty_points, Math.floor((subtotal.value * 0.5) / 1000));
    return maxUsable * 1000;
});

const finalTotal = computed(() => {
    return Math.max(0, subtotal.value + shippingFee.value - pointsDiscount.value);
});

function selectSavedAddress(addr: any) {
    selectedAddressId.value = addr.id;
    form.value.dia_chi_giao_hang_id = addr.id;
    form.value.ten_nguoi_nhan = addr.ten_nguoi_nhan;
    form.value.so_dien_thoai_nhan = addr.so_dien_thoai_nhan;
    form.value.dia_chi_giao = `${addr.dia_chi_chi_tiet}, ${addr.phuong_xa}, ${addr.quan_huyen}, ${addr.tinh_thanh}`;
}

function handlePlaceOrder() {
    if (cartItems.value.length === 0) {
        showToast('Giỏ hàng đang trống!', 'error');
        return;
    }

    if (!form.value.ten_nguoi_nhan || !form.value.so_dien_thoai_nhan) {
        showToast('Vui lòng điền họ tên và số điện thoại người nhận.', 'error');
        return;
    }

    if (deliveryType.value === 'delivery' && !form.value.dia_chi_giao) {
        showToast('Vui lòng điền địa chỉ giao hàng.', 'error');
        return;
    }

    isSubmitting.value = true;

    router.post(route('checkout.store'), {
        ...form.value,
        items: cartItems.value.map((i) => ({
            san_pham_id: i.san_pham_id,
            so_luong: i.so_luong,
        })),
    }, {
        onSuccess: () => {
            clearCart();
        },
        onError: (errors) => {
            console.error(errors);
            showToast('Có lỗi xảy ra, vui lòng kiểm tra lại thông tin.', 'error');
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}
</script>

<template>
    <StoreLayout title="Thanh Toán Đơn Hàng" :stores="stores">
        <!-- BREADCRUMBS -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <Link :href="route('cart.index')" class="hover:text-emerald-600 transition-colors">Giỏ hàng</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Thanh toán</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mb-8">
                Xác Nhận & Đặt Hàng
            </h1>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- FORM SECTIONS (LEFT 2 COLS) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 1. HÌNH THỨC NHẬN HÀNG -->
                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                        <h3 class="text-sm font-black text-gray-900 flex items-center">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs mr-2">1</span>
                            Hình thức nhận hàng
                        </h3>

                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="deliveryType = 'delivery'"
                                class="p-4 rounded-2xl border-2 text-left transition-all"
                                :class="deliveryType === 'delivery' ? 'border-emerald-600 bg-emerald-50/50 text-emerald-900' : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                            >
                                <strong class="text-xs block font-bold">Giao hàng tận nơi (Siêu tốc 2h)</strong>
                                <span class="text-[11px] text-gray-500">Thùng giữ nhiệt lạnh giao tận cửa</span>
                            </button>
                            <button
                                type="button"
                                @click="deliveryType = 'pickup'"
                                class="p-4 rounded-2xl border-2 text-left transition-all"
                                :class="deliveryType === 'pickup' ? 'border-emerald-600 bg-emerald-50/50 text-emerald-900' : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                            >
                                <strong class="text-xs block font-bold">Nhận tại cửa hàng</strong>
                                <span class="text-[11px] text-gray-500">Chuẩn bị sẵn, ghé lấy không chờ</span>
                            </button>
                        </div>

                        <!-- CỬA HÀNG XUẤT KHO -->
                        <div>
                            <label class="text-xs font-bold text-gray-700 block mb-1.5">Chi nhánh xuất kho:</label>
                            <select
                                v-model="form.cua_hang_id"
                                @change="onStoreBranchChange"
                                class="w-full text-xs font-medium bg-gray-50 border border-gray-200 rounded-xl p-3 focus:outline-none focus:border-emerald-500"
                            >
                                <option v-for="s in stores" :key="s.id" :value="s.id">
                                    {{ s.ten_cua_hang }} ({{ s.dia_chi }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- 2. THÔNG TIN NGƯỜI NHẬN & ĐỊA CHỈ -->
                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                        <h3 class="text-sm font-black text-gray-900 flex items-center">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs mr-2">2</span>
                            Thông tin người nhận & Địa chỉ
                        </h3>

                        <!-- Saved addresses chips if user has any -->
                        <div v-if="addresses?.length > 0" class="space-y-2 mb-4">
                            <label class="text-xs font-bold text-gray-600 block">Chọn từ sổ địa chỉ đã lưu:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <button
                                    v-for="addr in addresses"
                                    :key="addr.id"
                                    type="button"
                                    @click="selectSavedAddress(addr)"
                                    class="text-left p-3 rounded-xl border text-xs transition-colors"
                                    :class="selectedAddressId === addr.id ? 'border-emerald-600 bg-emerald-50 text-emerald-900 font-medium' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                                >
                                    <div class="flex items-center justify-between">
                                        <strong class="text-gray-900">{{ addr.ten_nguoi_nhan }}</strong>
                                        <span class="text-[10px] text-gray-400">{{ addr.so_dien_thoai_nhan }}</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1 line-clamp-1">{{ addr.dia_chi_chi_tiet }}</p>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-700 block mb-1">Họ tên người nhận *</label>
                                <input
                                    v-model="form.ten_nguoi_nhan"
                                    type="text"
                                    placeholder="Nguyễn Văn A"
                                    class="w-full text-xs p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-700 block mb-1">Số điện thoại nhận hàng *</label>
                                <input
                                    v-model="form.so_dien_thoai_nhan"
                                    type="tel"
                                    placeholder="0912 345 678"
                                    class="w-full text-xs p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <div v-if="deliveryType === 'delivery'">
                            <label class="text-xs font-bold text-gray-700 block mb-1">Địa chỉ nhận hàng chi tiết *</label>
                            <input
                                v-model="form.dia_chi_giao"
                                type="text"
                                placeholder="Số nhà, tên đường, tòa nhà/căn hộ, Phường, Quận, Thành phố"
                                class="w-full text-xs p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                            />
                        </div>

                        <!-- Khung giờ nhận hàng -->
                        <div>
                            <label class="text-xs font-bold text-gray-700 block mb-1.5 flex items-center">
                                <Clock class="w-3.5 h-3.5 mr-1 text-emerald-600" /> Khung giờ giao hàng tươi sống mong muốn:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <button
                                    v-for="slot in ['8h - 10h', '10h - 12h', '14h - 16h', '16h - 18h']"
                                    :key="slot"
                                    type="button"
                                    @click="form.time_slot = slot"
                                    class="py-2 px-3 rounded-xl border text-xs font-bold text-center transition-all"
                                    :class="form.time_slot === slot ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'"
                                >
                                    {{ slot }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-700 block mb-1">Ghi chú cho shipper / đóng gói (tùy chọn)</label>
                            <textarea
                                v-model="form.ghi_chu"
                                rows="2"
                                placeholder="Ví dụ: Gọi trước khi giao 10 phút, để tại quầy lễ tân..."
                                class="w-full text-xs p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- 3. PHƯƠNG THỨC THANH TOÁN -->
                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                        <h3 class="text-sm font-black text-gray-900 flex items-center">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs mr-2">3</span>
                            Phương thức thanh toán
                        </h3>

                        <div class="space-y-2.5">
                            <label class="flex items-center space-x-3 p-3.5 rounded-2xl border cursor-pointer transition-colors"
                                :class="form.phuong_thuc_thanh_toan === 'cod' ? 'border-emerald-600 bg-emerald-50/40' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="radio" value="cod" v-model="form.phuong_thuc_thanh_toan" class="text-emerald-600 focus:ring-emerald-500" />
                                <div>
                                    <strong class="text-xs text-gray-900 block font-bold">Thanh toán khi nhận hàng (COD)</strong>
                                    <span class="text-[11px] text-gray-500">Nhận hàng, kiểm tra thực phẩm tươi ngon rồi thanh toán tiền mặt</span>
                                </div>
                            </label>

                            <label class="flex items-center space-x-3 p-3.5 rounded-2xl border cursor-pointer transition-colors"
                                :class="form.phuong_thuc_thanh_toan === 'chuyen_khoan' ? 'border-emerald-600 bg-emerald-50/40' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="radio" value="chuyen_khoan" v-model="form.phuong_thuc_thanh_toan" class="text-emerald-600 focus:ring-emerald-500" />
                                <div class="flex items-center space-x-2">
                                    <QrCode class="w-4 h-4 text-emerald-600" />
                                    <div>
                                        <strong class="text-xs text-gray-900 block font-bold">Quét mã VietQR chuyển khoản ngân hàng</strong>
                                        <span class="text-[11px] text-gray-500">Chuyển khoản 24/7 tự động xác nhận đơn tức thì</span>
                                    </div>
                                </div>
                            </label>

                            <label class="flex items-center space-x-3 p-3.5 rounded-2xl border cursor-pointer transition-colors"
                                :class="form.phuong_thuc_thanh_toan === 'vnpay' ? 'border-emerald-600 bg-emerald-50/40' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="radio" value="vnpay" v-model="form.phuong_thuc_thanh_toan" class="text-emerald-600 focus:ring-emerald-500" />
                                <div>
                                    <strong class="text-xs text-gray-900 block font-bold">Cổng thanh toán VNPAY</strong>
                                    <span class="text-[11px] text-gray-500">Hỗ trợ thẻ ATM, thẻ quốc tế Visa/Mastercard</span>
                                </div>
                            </label>

                            <label class="flex items-center space-x-3 p-3.5 rounded-2xl border cursor-pointer transition-colors"
                                :class="form.phuong_thuc_thanh_toan === 'momo' ? 'border-emerald-600 bg-emerald-50/40' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="radio" value="momo" v-model="form.phuong_thuc_thanh_toan" class="text-emerald-600 focus:ring-emerald-500" />
                                <div>
                                    <strong class="text-xs text-gray-900 block font-bold">Ví điện tử MoMo</strong>
                                    <span class="text-[11px] text-gray-500">Thanh toán qua app MoMo nhanh chóng</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ORDER SUMMARY (RIGHT 1 COL) -->
                <div>
                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm sticky top-28 space-y-5">
                        <h3 class="text-base font-black text-gray-900 pb-3 border-b border-gray-100">
                            Đơn Hàng Của Bạn ({{ cartItems.length }} món)
                        </h3>

                        <!-- Cart items list small -->
                        <div class="max-h-56 overflow-y-auto divide-y divide-gray-100 text-xs">
                            <div v-for="item in cartItems" :key="item.san_pham_id" class="py-2.5 flex items-center justify-between space-x-2">
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-gray-800 truncate">{{ item.ten_san_pham }}</p>
                                    <p class="text-[11px] text-gray-400">
                                        {{ item.so_luong }} × {{ formatPrice(item.gia_giam || item.gia_ban) }}
                                    </p>
                                </div>
                                <span class="font-extrabold text-gray-900 shrink-0">
                                    {{ formatPrice((item.gia_giam || item.gia_ban) * item.so_luong) }}
                                </span>
                            </div>
                        </div>

                        <!-- DÙNG ĐIỂM TÍCH LŨY -->
                        <div v-if="user && user.loyalty_points > 0" class="p-3 bg-amber-50 rounded-2xl border border-amber-200/60">
                            <label class="flex items-center space-x-2 text-xs font-bold text-amber-900 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.use_points"
                                    class="rounded border-amber-400 text-amber-600 focus:ring-amber-500"
                                />
                                <span class="flex items-center">
                                    <Coins class="w-4 h-4 mr-1 text-amber-600" />
                                    Dùng {{ user.loyalty_points }} điểm (giảm tối đa {{ formatPrice(user.loyalty_points * 1000) }})
                                </span>
                            </label>
                        </div>

                        <!-- CALCULATIONS -->
                        <div class="space-y-2 text-xs text-gray-600 pt-3 border-t border-gray-100">
                            <div class="flex justify-between">
                                <span>Tiền hàng:</span>
                                <strong class="text-gray-900">{{ formatPrice(subtotal) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Phí vận chuyển:</span>
                                <strong v-if="shippingFee === 0" class="text-emerald-700 font-bold">Miễn phí</strong>
                                <strong v-else class="text-gray-900">{{ formatPrice(shippingFee) }}</strong>
                            </div>
                            <div v-if="pointsDiscount > 0" class="flex justify-between text-amber-700 font-bold">
                                <span>Giảm từ điểm thưởng:</span>
                                <span>-{{ formatPrice(pointsDiscount) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-black text-gray-900 pt-3 border-t border-gray-100">
                                <span>Tổng cộng:</span>
                                <span class="text-emerald-700 text-xl font-black">{{ formatPrice(finalTotal) }}</span>
                            </div>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <button
                            type="button"
                            @click="handlePlaceOrder"
                            :disabled="isSubmitting"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 px-6 rounded-2xl flex items-center justify-center space-x-2 shadow-lg shadow-emerald-600/30 transition-all hover:scale-102 active:scale-98 text-sm disabled:opacity-50"
                            id="place-order-btn"
                        >
                            <span v-if="isSubmitting">Đang xử lý đơn hàng...</span>
                            <span v-else>Xác Nhận Đặt Hàng</span>
                        </button>

                        <p class="text-[10px] text-gray-400 text-center leading-relaxed">
                            Bằng việc bấm đặt hàng, bạn đồng ý với Điều khoản dịch vụ và Chính sách bảo mật của Fresh Food.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
