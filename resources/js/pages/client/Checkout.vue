<script setup lang="ts">
import { useCart } from '@/composables/useCart';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, CheckCircle2, ChevronRight, Clock, Coins, CreditCard, LocateFixed, QrCode, Sparkles, TicketPercent, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        stores: Array<any>;
        addresses: Array<any>;
        availableCoupons?: Array<any>;
        appliedCoupon?: any;
        user?: {
            id: number;
            name: string;
            email?: string;
            phone?: string;
            loyalty_points: number;
        } | null;
    }>(),
    {
        availableCoupons: () => [],
        appliedCoupon: null,
    },
);

const { cartItems, selectedStoreId, subtotal, clearCart, showToast, setStore, refreshCartPrices } = useCart();

const deliveryType = ref<'delivery' | 'pickup'>('delivery');
const selectedAddressId = ref<number | null>(props.addresses?.[0]?.id || null);

const appliedCoupon = ref<any | null>(props.appliedCoupon || null);
const couponCode = ref(props.appliedCoupon?.code || '');
const couponError = ref('');
const isCheckingCoupon = ref(false);
const showVoucherList = ref(false);

const form = ref({
    cua_hang_id: selectedStoreId.value || props.stores?.[0]?.id || 1,
    ten_nguoi_nhan: props.user?.name || '',
    so_dien_thoai_nhan: props.user?.phone || '',
    dia_chi_giao: props.addresses?.[0]?.dia_chi_chi_tiet
        ? `${props.addresses[0].dia_chi_chi_tiet}, ${props.addresses[0].phuong_xa}, ${props.addresses[0].quan_huyen}, ${props.addresses[0].tinh_thanh}`
        : '',
    dia_chi_giao_hang_id: props.addresses?.[0]?.id || null,
    time_slot: 'Giao hỏa tốc 2h',
    phuong_thuc_thanh_toan: 'cod',
    ghi_chu: '',
    ma_giam_gia_code: props.appliedCoupon?.code || '',
    use_points: false,
});

function onStoreBranchChange() {
    const newStoreId = Number(form.value.cua_hang_id);
    setStore(newStoreId);
    refreshCartPrices(newStoreId);
}

const isLocatingGps = ref(false);
const autoDetectedNotice = ref('');
const suggestedStoreForAddress = ref<any | null>(null);

const isCrossRegionWarning = computed(() => {
    const selectedStore = props.stores.find((s) => s.id === Number(form.value.cua_hang_id));
    if (!selectedStore || !form.value.dia_chi_giao || deliveryType.value !== 'delivery') return false;
    const dc = form.value.dia_chi_giao.toLowerCase();
    const stDc = selectedStore.dia_chi.toLowerCase();

    const isAddrHn = dc.includes('hà nội') || dc.includes('ha noi');
    const isStoreHn = stDc.includes('hà nội') || stDc.includes('ha noi');

    if (isAddrHn && !isStoreHn) return true;

    const isAddrHcm =
        dc.includes('hồ chí minh') ||
        dc.includes('tp.hcm') ||
        dc.includes('tphcm') ||
        dc.includes('sài gòn') ||
        dc.includes('quận ') ||
        dc.includes('bình thạnh');

    if (isAddrHcm && isStoreHn) return true;

    return false;
});

let addressDebounce: ReturnType<typeof setTimeout> | null = null;

async function detectNearestByAddress(address: string) {
    if (!address || !address.trim() || deliveryType.value !== 'delivery') return;
    try {
        const res = await fetch(route('store.find-nearest'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
            },
            body: JSON.stringify({ address: address.trim() }),
        });
        const data = await res.json();
        if (data.success && data.store) {
            suggestedStoreForAddress.value = data.store;
            if (Number(form.value.cua_hang_id) !== data.store.id) {
                form.value.cua_hang_id = data.store.id;
                onStoreBranchChange();
                autoDetectedNotice.value = `Hệ thống đã tự động chọn chi nhánh ${data.store.ten_cua_hang} gần nơi giao của bạn nhất.`;
            }
        }
    } catch (e) {
        // silent fail
    }
}

function detectNearestByGps() {
    if (typeof window === 'undefined' || !navigator.geolocation) {
        showToast('Trình duyệt của bạn không hỗ trợ định vị GPS.', 'error');
        return;
    }
    isLocatingGps.value = true;
    navigator.geolocation.getCurrentPosition(
        async (position) => {
            try {
                const res = await fetch(route('store.find-nearest'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
                    },
                    body: JSON.stringify({
                        lat: position.coords.latitude,
                        lon: position.coords.longitude,
                    }),
                });
                const data = await res.json();
                if (data.success && data.store) {
                    form.value.cua_hang_id = data.store.id;
                    onStoreBranchChange();
                    const distMsg = data.distance_km ? ` (Cách bạn ~${data.distance_km} km)` : '';
                    autoDetectedNotice.value = `Đã tìm thấy chi nhánh gần bạn nhất: ${data.store.ten_cua_hang}${distMsg}`;
                    showToast(`Đã định vị thành công: ${data.store.ten_cua_hang}${distMsg}`, 'success');
                }
            } catch (e) {
                showToast('Lỗi khi tra cứu chi nhánh gần nhất.', 'error');
            } finally {
                isLocatingGps.value = false;
            }
        },
        (error) => {
            isLocatingGps.value = false;
            if (error.code === error.PERMISSION_DENIED) {
                showToast('Bạn đã từ chối quyền truy cập vị trí. Vui lòng nhập địa chỉ để hệ thống tự nhận diện chi nhánh.', 'info');
            } else {
                showToast('Không thể xác định vị trí hiện tại của bạn.', 'error');
            }
        },
        { timeout: 10000, enableHighAccuracy: true },
    );
}

function onAddressInput() {
    if (addressDebounce) clearTimeout(addressDebounce);
    addressDebounce = setTimeout(() => {
        detectNearestByAddress(form.value.dia_chi_giao);
    }, 600);
}

const isSubmitting = ref(false);

const shippingFee = computed(() => {
    if (deliveryType.value === 'pickup' || subtotal.value >= 300000) return 0;
    return 25000;
});

const couponDiscount = computed(() => {
    if (!appliedCoupon.value) return 0;
    if (appliedCoupon.value.type === 'phan_tram') {
        const disc = (subtotal.value * appliedCoupon.value.value) / 100;
        return appliedCoupon.value.giam_toi_da ? Math.min(disc, appliedCoupon.value.giam_toi_da) : disc;
    }
    return Math.min(subtotal.value, appliedCoupon.value.value);
});

async function handleApplyCoupon() {
    couponError.value = '';
    if (!couponCode.value.trim()) {
        couponError.value = 'Vui lòng nhập mã giảm giá';
        return;
    }

    isCheckingCoupon.value = true;
    try {
        const response = await fetch(route('cart.check-coupon'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
            },
            body: JSON.stringify({
                code: couponCode.value.trim(),
                total: subtotal.value,
            }),
        });
        const data = await response.json();

        if (data.success) {
            appliedCoupon.value = data.coupon;
            form.value.ma_giam_gia_code = data.coupon.code;
            showToast(data.message, 'success');
        } else {
            couponError.value = data.message;
            appliedCoupon.value = null;
            form.value.ma_giam_gia_code = '';
        }
    } catch (e) {
        couponError.value = 'Lỗi kết nối khi kiểm tra mã.';
    } finally {
        isCheckingCoupon.value = false;
    }
}

function removeCoupon() {
    appliedCoupon.value = null;
    couponCode.value = '';
    form.value.ma_giam_gia_code = '';
    fetch(route('cart.remove-coupon'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
        },
    }).catch(() => {});
}

function selectVoucher(c: any) {
    if (subtotal.value < c.don_hang_toi_thieu) {
        showToast(`Đơn hàng cần tối thiểu ${formatPrice(c.don_hang_toi_thieu)} để dùng mã ${c.ma_khuyen_mai}.`, 'error');
        return;
    }
    couponCode.value = c.ma_khuyen_mai;
    handleApplyCoupon();
}

const pointsDiscount = computed(() => {
    if (!form.value.use_points || !props.user || props.user.loyalty_points <= 0) return 0;
    const remainingGoods = Math.max(0, subtotal.value - couponDiscount.value);
    const maxUsable = Math.min(props.user.loyalty_points, Math.floor(remainingGoods / 1000));
    return maxUsable * 1000;
});

const finalTotal = computed(() => {
    return Math.max(0, subtotal.value + shippingFee.value - couponDiscount.value - pointsDiscount.value);
});

const pointsWillEarn = computed(() => {
    const tienThucTra = Math.max(0, subtotal.value - couponDiscount.value - pointsDiscount.value);
    return Math.floor(tienThucTra / 10000);
});

function selectSavedAddress(addr: any) {
    selectedAddressId.value = addr.id;
    form.value.dia_chi_giao_hang_id = addr.id;
    form.value.ten_nguoi_nhan = addr.ten_nguoi_nhan;
    form.value.so_dien_thoai_nhan = addr.so_dien_thoai_nhan;
    form.value.dia_chi_giao = `${addr.dia_chi_chi_tiet}, ${addr.phuong_xa}, ${addr.quan_huyen}, ${addr.tinh_thanh}`;
    detectNearestByAddress(form.value.dia_chi_giao);
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

    router.post(
        route('checkout.store'),
        {
            ...form.value,
            khung_gio_giao: form.value.time_slot,
            items: cartItems.value.map((i) => ({
                san_pham_id: i.san_pham_id,
                so_luong: i.so_luong,
            })),
        },
        {
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
        },
    );
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}
</script>

<template>
    <StoreLayout title="Thanh Toán Đơn Hàng" :stores="stores">
        <!-- BREADCRUMBS -->
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-7xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <Link :href="route('cart.index')" class="transition-colors hover:text-emerald-600">Giỏ hàng</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">Thanh toán</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-3 sm:px-4 py-4 sm:py-8 pb-28 lg:pb-8">
            <h1 class="mb-4 sm:mb-8 text-xl sm:text-3xl font-black tracking-tight text-gray-900">Xác Nhận & Đặt Hàng</h1>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:gap-8">
                <!-- FORM SECTIONS (LEFT 2 COLS) -->
                <div class="space-y-4 sm:space-y-6 lg:col-span-2">
                    <!-- 1. HÌNH THỨC NHẬN HÀNG -->
                    <div class="space-y-4 rounded-2xl sm:rounded-3xl border border-gray-100 bg-white p-4 sm:p-6 shadow-sm">
                        <h3 class="flex items-center text-sm font-black text-gray-900">
                            <span class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-xs text-white">1</span>
                            Hình thức nhận hàng
                        </h3>

                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="deliveryType = 'delivery'"
                                class="rounded-2xl border-2 p-4 text-left transition-all"
                                :class="
                                    deliveryType === 'delivery'
                                        ? 'border-emerald-600 bg-emerald-50/50 text-emerald-900'
                                        : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                "
                            >
                                <strong class="block text-xs font-bold">Giao hàng tận nơi (Siêu tốc 2h)</strong>
                                <span class="text-[11px] text-gray-500">Thùng giữ nhiệt lạnh giao tận cửa</span>
                            </button>
                            <button
                                type="button"
                                @click="deliveryType = 'pickup'"
                                class="rounded-2xl border-2 p-4 text-left transition-all"
                                :class="
                                    deliveryType === 'pickup'
                                        ? 'border-emerald-600 bg-emerald-50/50 text-emerald-900'
                                        : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                "
                            >
                                <strong class="block text-xs font-bold">Nhận tại cửa hàng</strong>
                                <span class="text-[11px] text-gray-500">Chuẩn bị sẵn, ghé lấy không chờ</span>
                            </button>
                        </div>

                        <!-- CỬA HÀNG XUẤT KHO -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-gray-700">Chi nhánh xuất kho giao hàng:</label>
                                <button
                                    type="button"
                                    @click="detectNearestByGps"
                                    :disabled="isLocatingGps"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100 hover:text-emerald-800"
                                    title="Tự động tính khoảng cách GPS để chọn chi nhánh gần nhất"
                                >
                                    <LocateFixed class="h-3.5 w-3.5 text-emerald-600" :class="{ 'animate-spin': isLocatingGps }" />
                                    <span>{{ isLocatingGps ? 'Đang định vị GPS...' : '📍 Định vị gần tôi' }}</span>
                                </button>
                            </div>
                            <select
                                v-model="form.cua_hang_id"
                                @change="onStoreBranchChange"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs font-bold focus:border-emerald-500 focus:outline-none"
                            >
                                <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }} ({{ s.dia_chi }})</option>
                            </select>

                            <!-- AUTO DETECTED BANNER -->
                            <div
                                v-if="autoDetectedNotice"
                                class="flex items-start justify-between gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900 animate-in fade-in"
                            >
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-600" />
                                    <span class="text-[11px] font-medium">{{ autoDetectedNotice }}</span>
                                </div>
                                <button @click="autoDetectedNotice = ''" class="cursor-pointer p-0.5 text-emerald-600 hover:text-emerald-800">
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- CROSS-REGION WARNING -->
                            <div
                                v-if="isCrossRegionWarning"
                                class="flex items-start gap-2.5 rounded-xl border border-amber-300 bg-amber-50 p-3.5 text-xs text-amber-950 animate-in fade-in"
                            >
                                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
                                <div>
                                    <strong class="block font-black text-amber-950">Khoảng cách quá xa - Khác khu vực địa lý!</strong>
                                    <p class="mt-0.5 text-[11px] leading-relaxed text-amber-800">
                                        Địa chỉ nhận hàng của bạn và chi nhánh đang chọn nằm ở hai thành phố/vùng khác nhau. Thực phẩm tươi sống không
                                        thể giao liên tỉnh siêu tốc trong 2h. Vui lòng bấm vào nút <strong>"Định vị gần tôi"</strong> hoặc chọn chi
                                        nhánh cùng khu vực.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. THÔNG TIN NGƯỜI NHẬN & ĐỊA CHỈ -->
                    <div class="space-y-4 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h3 class="flex items-center text-sm font-black text-gray-900">
                            <span class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-xs text-white">2</span>
                            Thông tin người nhận & Địa chỉ
                        </h3>

                        <!-- Saved addresses chips if user has any -->
                        <div v-if="addresses?.length > 0" class="mb-4 space-y-2">
                            <label class="block text-xs font-bold text-gray-600">Chọn từ sổ địa chỉ đã lưu:</label>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <button
                                    v-for="addr in addresses"
                                    :key="addr.id"
                                    type="button"
                                    @click="selectSavedAddress(addr)"
                                    class="rounded-xl border p-3 text-left text-xs transition-colors"
                                    :class="
                                        selectedAddressId === addr.id
                                            ? 'border-emerald-600 bg-emerald-50 font-medium text-emerald-900'
                                            : 'border-gray-200 text-gray-600 hover:bg-gray-50'
                                    "
                                >
                                    <div class="flex items-center justify-between">
                                        <strong class="text-gray-900">{{ addr.ten_nguoi_nhan }}</strong>
                                        <span class="text-[10px] text-gray-400">{{ addr.so_dien_thoai_nhan }}</span>
                                    </div>
                                    <p class="mt-1 line-clamp-1 text-[11px] text-gray-500">{{ addr.dia_chi_chi_tiet }}</p>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-700">Họ tên người nhận *</label>
                                <input
                                    v-model="form.ten_nguoi_nhan"
                                    type="text"
                                    placeholder="Nguyễn Văn A"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs focus:border-emerald-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-700">Số điện thoại nhận hàng *</label>
                                <input
                                    v-model="form.so_dien_thoai_nhan"
                                    type="tel"
                                    placeholder="0912 345 678"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs focus:border-emerald-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <div v-if="deliveryType === 'delivery'">
                            <div class="mb-1 flex items-center justify-between">
                                <label class="text-xs font-bold text-gray-700">Địa chỉ nhận hàng chi tiết *</label>
                                <span class="flex items-center gap-1 text-[10px] font-semibold text-emerald-700">
                                    <CheckCircle2 class="h-3 w-3 text-emerald-600" />
                                    <span>Tự động nhận diện chi nhánh gần nhất khi gõ</span>
                                </span>
                            </div>
                            <input
                                v-model="form.dia_chi_giao"
                                @input="onAddressInput"
                                type="text"
                                placeholder="Số nhà, tên đường, Phường, Quận (hoặc Huyện), Tỉnh/Thành phố..."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs focus:border-emerald-500 focus:outline-none"
                            />
                        </div>

                        <!-- Khung giờ nhận hàng -->
                        <div>
                            <label class="mb-1.5 block flex items-center text-xs font-bold text-gray-700">
                                <Clock class="mr-1 h-3.5 w-3.5 text-emerald-600" /> Khung giờ giao hàng tươi sống mong muốn:
                            </label>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <button
                                    v-for="slot in ['Giao hỏa tốc 2h', '8h - 10h', '10h - 12h', '14h - 16h', '16h - 18h', '18h30 - 20h30']"
                                    :key="slot"
                                    type="button"
                                    @click="form.time_slot = slot"
                                    class="rounded-xl border px-3 py-2 text-center text-xs font-bold transition-all"
                                    :class="
                                        form.time_slot === slot
                                            ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                                            : 'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100'
                                    "
                                >
                                    {{ slot }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-bold text-gray-700">Ghi chú cho shipper / đóng gói (tùy chọn)</label>
                            <textarea
                                v-model="form.ghi_chu"
                                rows="2"
                                placeholder="Ví dụ: Gọi trước khi giao 10 phút, để tại quầy lễ tân..."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs focus:border-emerald-500 focus:outline-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- 3. PHƯƠNG THỨC THANH TOÁN -->
                    <div class="space-y-4 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h3 class="flex items-center text-sm font-black text-gray-900">
                            <span class="mr-2 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-xs text-white">3</span>
                            Phương thức thanh toán
                        </h3>

                        <div class="space-y-3">
                            <!-- COD PAYMENT -->
                            <label
                                class="flex cursor-pointer items-start space-x-3.5 rounded-2xl border p-4 transition-all"
                                :class="
                                    form.phuong_thuc_thanh_toan === 'cod'
                                        ? 'border-emerald-600 bg-emerald-50/40 shadow-sm ring-1 ring-emerald-600/20'
                                        : 'border-gray-200 hover:bg-gray-50'
                                "
                            >
                                <input
                                    type="radio"
                                    value="cod"
                                    v-model="form.phuong_thuc_thanh_toan"
                                    class="mt-1 text-emerald-600 focus:ring-emerald-500"
                                />
                                <div class="flex-1">
                                    <strong class="block text-xs font-bold text-gray-900">Thanh toán khi nhận hàng (COD)</strong>
                                    <span class="mt-0.5 block text-[11px] leading-relaxed text-gray-500">
                                        Nhận hàng tận nơi, kiểm tra thực phẩm tươi ngon đúng yêu cầu rồi thanh toán tiền mặt cho shipper.
                                    </span>
                                </div>
                            </label>

                            <!-- VNPAY SANDBOX PAYMENT -->
                            <label
                                class="flex cursor-pointer items-start space-x-3.5 rounded-2xl border p-4 transition-all"
                                :class="
                                    form.phuong_thuc_thanh_toan === 'vnpay'
                                        ? 'border-blue-600 bg-blue-50/40 shadow-sm ring-1 ring-blue-600/20'
                                        : 'border-gray-200 hover:bg-gray-50'
                                "
                            >
                                <input
                                    type="radio"
                                    value="vnpay"
                                    v-model="form.phuong_thuc_thanh_toan"
                                    class="mt-1 text-blue-600 focus:ring-blue-500"
                                />
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <CreditCard class="h-4 w-4 text-blue-600" />
                                        <strong class="text-xs font-bold text-gray-900">Cổng thanh toán VNPAY Sandbox</strong>
                                        <span class="rounded bg-blue-100 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-blue-700">
                                            Thử Nghiệm
                                        </span>
                                    </div>
                                    <span class="mt-0.5 block text-[11px] leading-relaxed text-gray-500">
                                        Chuyển hướng an toàn sang Cổng thanh toán VNPay Sandbox. Hỗ trợ quét mã VNPAY-QR, Thẻ ATM nội địa (NCB, Vietcombank,...) và Thẻ quốc tế.
                                    </span>

                                    <!-- VNPAY SANDBOX TEST CARD INFO -->
                                    <div
                                        v-if="form.phuong_thuc_thanh_toan === 'vnpay'"
                                        class="mt-3 rounded-xl border border-blue-200 bg-blue-50/90 p-3 text-[11px] text-blue-900 space-y-1.5"
                                    >
                                        <div class="flex items-center font-bold text-blue-800">
                                            <Sparkles class="mr-1.5 h-3.5 w-3.5 text-blue-600" />
                                            <span>Thông tin thẻ TEST thanh toán VNPay Sandbox:</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-1 text-[11px]">
                                            <div>Ngân hàng: <strong class="font-bold">NCB</strong></div>
                                            <div>Số thẻ: <strong class="font-mono font-bold">9704198526191432198</strong></div>
                                            <div>Tên chủ thẻ: <strong class="font-bold">NGUYEN VAN A</strong></div>
                                            <div>Ngày phát hành: <strong class="font-bold">07/15</strong> • OTP: <strong class="font-bold">123456</strong></div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ORDER SUMMARY (RIGHT 1 COL) -->
                <div>
                    <div class="sticky top-28 space-y-5 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h3 class="border-b border-gray-100 pb-3 text-base font-black text-gray-900">
                            Đơn Hàng Của Bạn ({{ cartItems.length }} món)
                        </h3>

                        <!-- Cart items list small -->
                        <div class="max-h-56 divide-y divide-gray-100 overflow-y-auto text-xs">
                            <div v-for="item in cartItems" :key="item.san_pham_id" class="flex items-center justify-between space-x-2 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-bold text-gray-800">{{ item.ten_san_pham }}</p>
                                    <p class="text-[11px] text-gray-400">{{ item.so_luong }} × {{ formatPrice(item.gia_giam || item.gia_ban) }}</p>
                                </div>
                                <span class="shrink-0 font-extrabold text-gray-900">
                                    {{ formatPrice((item.gia_giam || item.gia_ban) * item.so_luong) }}
                                </span>
                            </div>
                        </div>

                        <!-- MÃ GIẢM GIÁ / VOUCHER -->
                        <div class="space-y-2 rounded-2xl border border-gray-200/80 bg-gray-50 p-3">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-xs font-bold text-gray-800">
                                    <TicketPercent class="h-4 w-4 text-emerald-600" />
                                    <span>Mã Giảm Giá / Voucher</span>
                                </span>
                                <button
                                    v-if="availableCoupons && availableCoupons.length > 0"
                                    type="button"
                                    @click="showVoucherList = !showVoucherList"
                                    class="cursor-pointer text-[11px] font-bold text-emerald-600 transition hover:text-emerald-700"
                                >
                                    {{ showVoucherList ? 'Thu gọn' : `Xem mã (${availableCoupons.length})` }}
                                </button>
                            </div>

                            <!-- IF APPLIED -->
                            <div
                                v-if="appliedCoupon"
                                class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-2.5"
                            >
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="rounded bg-emerald-100 px-1.5 py-0.5 font-mono text-xs font-extrabold uppercase text-emerald-800"
                                        >
                                            {{ appliedCoupon.code }}
                                        </span>
                                        <span class="text-[11px] font-bold text-emerald-700">-{{ formatPrice(couponDiscount) }}</span>
                                    </div>
                                    <p class="mt-0.5 text-[10px] text-emerald-600">{{ appliedCoupon.program }}</p>
                                </div>
                                <button
                                    type="button"
                                    @click="removeCoupon"
                                    class="rounded px-2 py-1 text-xs font-bold text-red-500 transition hover:bg-red-50 hover:text-red-700"
                                >
                                    Gỡ bỏ
                                </button>
                            </div>

                            <!-- INPUT TO ENTER CODE -->
                            <div v-else class="space-y-1.5">
                                <div class="flex gap-2">
                                    <input
                                        v-model="couponCode"
                                        @keydown.enter.prevent="handleApplyCoupon"
                                        type="text"
                                        placeholder="Nhập mã ưu đãi..."
                                        class="flex-1 rounded-xl border border-gray-300 bg-white px-3 py-2 font-mono text-xs uppercase outline-none focus:ring-2 focus:ring-emerald-500"
                                    />
                                    <button
                                        type="button"
                                        @click="handleApplyCoupon"
                                        :disabled="isCheckingCoupon || !couponCode.trim()"
                                        class="rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-emerald-700 disabled:bg-gray-300"
                                    >
                                        {{ isCheckingCoupon ? '...' : 'Áp dụng' }}
                                    </button>
                                </div>
                                <p v-if="couponError" class="text-[11px] text-red-500">{{ couponError }}</p>
                            </div>

                            <!-- VOUCHER POPUP LIST -->
                            <div v-if="showVoucherList" class="mt-2 max-h-48 space-y-2 overflow-y-auto border-t border-gray-200 pt-2">
                                <div
                                    v-for="c in availableCoupons"
                                    :key="c.id"
                                    class="flex items-center justify-between gap-2 rounded-xl border p-2 text-xs"
                                    :class="
                                        appliedCoupon?.code === c.ma_khuyen_mai ? 'border-emerald-500 bg-emerald-50/50' : 'border-gray-200 bg-white'
                                    "
                                >
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1">
                                            <span class="rounded bg-emerald-50 px-1 font-mono text-[11px] font-bold text-emerald-700">
                                                {{ c.ma_khuyen_mai }}
                                            </span>
                                            <span class="text-[10px] text-gray-400">HSD: {{ c.ngay_ket_thuc }}</span>
                                        </div>
                                        <p class="mt-0.5 truncate text-[11px] font-medium text-gray-700">{{ c.ten_chuong_trinh }}</p>
                                        <p class="text-[10px] text-gray-400">Đơn từ {{ formatPrice(c.don_hang_toi_thieu) }}</p>
                                    </div>
                                    <div>
                                        <button
                                            v-if="appliedCoupon?.code === c.ma_khuyen_mai"
                                            type="button"
                                            disabled
                                            class="rounded bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white"
                                        >
                                            Đang dùng
                                        </button>
                                        <button
                                            v-else-if="subtotal >= c.don_hang_toi_thieu"
                                            type="button"
                                            @click="selectVoucher(c)"
                                            class="cursor-pointer rounded bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white hover:bg-emerald-700"
                                        >
                                            Dùng
                                        </button>
                                        <span v-else class="rounded bg-amber-50 px-1.5 py-0.5 text-[9px] text-amber-700">
                                            Thiếu {{ formatPrice(c.don_hang_toi_thieu - subtotal) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DÙNG ĐIỂM TÍCH LŨY -->
                        <div v-if="user && user.loyalty_points > 0" class="rounded-2xl border border-amber-200/60 bg-amber-50 p-3">
                            <label class="flex cursor-pointer items-center space-x-2 text-xs font-bold text-amber-900">
                                <input
                                    type="checkbox"
                                    v-model="form.use_points"
                                    class="rounded border-amber-400 text-amber-600 focus:ring-amber-500"
                                />
                                <span class="flex items-center">
                                    <Coins class="mr-1 h-4 w-4 text-amber-600" />
                                    Dùng {{ Math.min(user.loyalty_points, Math.floor(Math.max(0, subtotal - couponDiscount) / 1000)) }} điểm (giảm
                                    {{
                                        formatPrice(Math.min(user.loyalty_points, Math.floor(Math.max(0, subtotal - couponDiscount) / 1000)) * 1000)
                                    }})
                                </span>
                            </label>
                        </div>

                        <div v-if="user" class="flex items-center justify-between rounded-xl bg-emerald-50 p-2.5 text-xs text-emerald-800">
                            <span>Tích lũy từ đơn này:</span>
                            <strong class="font-extrabold text-emerald-700">+{{ pointsWillEarn }} điểm (10k = 1 điểm)</strong>
                        </div>

                        <!-- CALCULATIONS -->
                        <div class="space-y-2 border-t border-gray-100 pt-3 text-xs text-gray-600">
                            <div class="flex justify-between">
                                <span>Tiền hàng:</span>
                                <strong class="text-gray-900">{{ formatPrice(subtotal) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Phí vận chuyển:</span>
                                <strong v-if="shippingFee === 0" class="font-bold text-emerald-700">Miễn phí</strong>
                                <strong v-else class="text-gray-900">{{ formatPrice(shippingFee) }}</strong>
                            </div>
                            <div v-if="couponDiscount > 0" class="flex justify-between font-bold text-emerald-700">
                                <span>Giảm giá voucher ({{ appliedCoupon.code }}):</span>
                                <span>-{{ formatPrice(couponDiscount) }}</span>
                            </div>
                            <div v-if="pointsDiscount > 0" class="flex justify-between font-bold text-amber-700">
                                <span>Giảm từ điểm thưởng:</span>
                                <span>-{{ formatPrice(pointsDiscount) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-100 pt-3 text-base font-black text-gray-900">
                                <span>Tổng cộng:</span>
                                <span class="text-xl font-black text-emerald-700">{{ formatPrice(finalTotal) }}</span>
                            </div>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <button
                            type="button"
                            @click="handlePlaceOrder"
                            :disabled="isSubmitting"
                            class="hover:scale-102 active:scale-98 flex w-full items-center justify-center space-x-2 rounded-2xl bg-emerald-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-emerald-600/30 transition-all hover:bg-emerald-700 disabled:opacity-50"
                            id="place-order-btn"
                        >
                            <span v-if="isSubmitting">Đang xử lý đơn hàng...</span>
                            <span v-else>Xác Nhận Đặt Hàng</span>
                        </button>

                        <p class="text-center text-[10px] leading-relaxed text-gray-400">
                            Bằng việc bấm đặt hàng, bạn đồng ý với Điều khoản dịch vụ và Chính sách bảo mật của Fresh Food.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- MOBILE STICKY PLACE ORDER BAR -->
        <div
            v-if="cartItems.length > 0"
            class="fixed bottom-14 left-0 right-0 z-30 flex items-center justify-between border-t border-slate-200/90 bg-white/95 px-4 py-2.5 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] backdrop-blur-md lg:hidden"
        >
            <div>
                <span class="text-[10px] text-gray-500">Tổng thanh toán:</span>
                <p class="text-base font-black text-emerald-700 leading-tight">{{ formatPrice(finalTotal) }}</p>
            </div>
            <button
                type="button"
                @click="handlePlaceOrder"
                :disabled="isSubmitting"
                class="flex items-center space-x-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-md shadow-emerald-600/30 active:scale-95 disabled:opacity-50"
            >
                <span v-if="isSubmitting">Đang xử lý...</span>
                <span v-else>Đặt hàng ngay</span>
                <ArrowRight v-if="!isSubmitting" class="h-3.5 w-3.5" />
            </button>
        </div>
    </StoreLayout>
</template>
