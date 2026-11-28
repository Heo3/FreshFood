<script setup lang="ts">
import { useCart } from '@/composables/useCart';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Check, ChevronRight, MapPin, ShieldAlert, ShoppingBag, Sparkles, Tag, TicketPercent, Trash2, Truck } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        stores: Array<any>;
        initialCartItems?: Array<any>;
        availableCoupons?: Array<any>;
        appliedCoupon?: any;
    }>(),
    {
        availableCoupons: () => [],
        appliedCoupon: null,
    },
);

const {
    cartItems,
    selectedStoreId,
    subtotal,
    totalItems,
    updateQuantity,
    removeFromCart,
    clearCart,
    showToast,
    refreshCartPrices,
    syncCartToServer,
    saveCart,
    syncWithServer,
} = useCart();

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

function handleProceedCheckout() {
    if (!authUser.value) {
        router.visit(route('login'));
        return;
    }
    const targetUrl = props.appliedCoupon
        ? `${route('checkout.index')}?coupon=${encodeURIComponent(props.appliedCoupon.code)}`
        : route('checkout.index');
    router.visit(targetUrl);
}

const currentStoreName = computed(() => {
    return props.stores?.find((s) => s.id === selectedStoreId.value)?.ten_cua_hang || 'Chi nhánh đã chọn';
});

function handleClearCart() {
    if (window.confirm('Bạn có chắc chắn muốn xóa toàn bộ sản phẩm trong giỏ hàng không?')) {
        clearCart();
    }
}

onMounted(() => {
    if (props.initialCartItems && props.initialCartItems.length > 0) {
        const formattedDbItems = props.initialCartItems.map((dbItem: any) => ({
            san_pham_id: dbItem.san_pham_id,
            ten_san_pham: dbItem.san_pham?.ten_san_pham || '',
            duong_dan_seo: dbItem.san_pham?.duong_dan_seo || '',
            hinh_anh_chinh: dbItem.san_pham?.hinh_anh_chinh || '',
            don_vi_tinh: dbItem.san_pham?.don_vi_tinh || 'kg',
            quy_cach: dbItem.san_pham?.quy_cach,
            gia_ban: Number(dbItem.san_pham?.gia_ban || 0),
            gia_giam: dbItem.san_pham?.gia_giam ? Number(dbItem.san_pham.gia_giam) : null,
            so_luong: Number(dbItem.so_luong),
        }));
        syncWithServer(formattedDbItems);
    } else if (cartItems.value.length > 0) {
        syncCartToServer();
    }

    if (cartItems.value.length > 0) {
        refreshCartPrices();
    }
});

const couponCode = ref(props.appliedCoupon?.code || '');
const appliedCoupon = ref<any>(props.appliedCoupon || null);
const couponError = ref('');
const isCheckingCoupon = ref(false);

const shippingFee = computed(() => {
    if (subtotal.value === 0) return 0;
    return subtotal.value >= 300000 ? 0 : 25000;
});

const discountAmount = computed(() => {
    return appliedCoupon.value?.discount_amount || 0;
});

const grandTotal = computed(() => {
    return Math.max(0, subtotal.value + shippingFee.value - discountAmount.value);
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
            showToast(data.message, 'success');
        } else {
            couponError.value = data.message;
            appliedCoupon.value = null;
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
    fetch(route('cart.remove-coupon'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
        },
    }).catch(() => {});
}

const showVoucherList = ref(false);

function selectVoucher(c: any) {
    if (subtotal.value < c.don_hang_toi_thieu) {
        showToast(`Đơn hàng cần tối thiểu ${formatPrice(c.don_hang_toi_thieu)} để dùng mã ${c.ma_khuyen_mai}.`, 'error');
        return;
    }
    couponCode.value = c.ma_khuyen_mai;
    handleApplyCoupon();
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}
</script>

<template>
    <StoreLayout title="Giỏ Hàng" :stores="stores">
        <!-- BREADCRUMBS -->
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-7xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">Giỏ hàng của bạn</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-3 sm:px-4 py-4 sm:py-8 pb-28 lg:pb-8">
            <h1 class="mb-4 sm:mb-6 flex items-center text-xl font-black tracking-tight text-gray-900 sm:text-3xl">
                Giỏ Hàng Của Bạn
                <span class="ml-3 rounded-full bg-emerald-50 px-2.5 sm:px-3 py-0.5 sm:py-1 text-xs font-bold text-emerald-700"> {{ totalItems }} sản phẩm </span>
            </h1>

            <!-- IF CART HAS ITEMS -->
            <div v-if="cartItems.length > 0" class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:gap-8">
                <!-- CART ITEMS LIST (LEFT) -->
                <div class="space-y-4 lg:col-span-2">
                    <!-- Branch Notice -->
                    <div
                        class="shadow-xs flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-emerald-200/80 bg-emerald-50/80 p-3 sm:px-4 sm:py-3"
                    >
                        <div class="flex items-center space-x-2 text-xs text-emerald-950">
                            <MapPin class="h-4 w-4 shrink-0 text-emerald-600" />
                            <span
                                >Đơn hàng được phục vụ bởi: <strong class="font-bold text-emerald-800">{{ currentStoreName }}</strong></span
                            >
                        </div>
                        <span class="rounded-xl border border-emerald-100 bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                            Tất cả sản phẩm phải cùng chi nhánh
                        </span>
                    </div>

                    <div class="rounded-2xl sm:rounded-3xl border border-gray-100 bg-white p-4 sm:p-6 shadow-sm">
                        <!-- Free shipping progress bar -->
                        <div class="mb-4 sm:mb-6 flex items-center space-x-3 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-3 sm:p-4 text-xs">
                            <Truck class="h-5 w-5 shrink-0 text-emerald-600" />
                            <div class="flex-1">
                                <p v-if="subtotal >= 300000" class="font-bold text-emerald-800">
                                    🎉 Chúc mừng! Đơn hàng của bạn được MIỄN PHÍ giao hàng siêu tốc 2h.
                                </p>
                                <p v-else class="text-gray-700">
                                    Mua thêm <strong class="font-black text-emerald-700">{{ formatPrice(300000 - subtotal) }}</strong> để được
                                    <strong class="text-emerald-700">MIỄN PHÍ GIAO HÀNG</strong>!
                                </p>
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        class="h-full rounded-full bg-emerald-600 transition-all duration-300"
                                        :style="{ width: `${Math.min(100, (subtotal / 300000) * 100)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Item table -->
                        <div class="divide-y divide-gray-100">
                            <div
                                v-for="item in cartItems"
                                :key="item.san_pham_id"
                                class="flex flex-col justify-between gap-3 py-3.5 sm:flex-row sm:items-center sm:gap-4 sm:py-4"
                            >
                                <div class="flex min-w-0 items-center space-x-3">
                                    <img
                                        :src="item.hinh_anh_chinh"
                                        :alt="item.ten_san_pham"
                                        class="h-14 w-14 sm:h-16 sm:w-16 shrink-0 rounded-xl sm:rounded-2xl border border-gray-100 bg-gray-50 object-cover"
                                    />
                                    <div class="min-w-0">
                                        <Link
                                            :href="'/san-pham/' + item.duong_dan_seo"
                                            class="line-clamp-1 text-xs sm:text-sm font-bold text-gray-900 transition-colors hover:text-emerald-600"
                                        >
                                            {{ item.ten_san_pham }}
                                        </Link>
                                        <p class="mt-0.5 text-[11px] sm:text-xs text-gray-400">
                                            {{ item.quy_cach || 'Đơn vị: ' + item.don_vi_tinh }}
                                        </p>
                                        <div class="mt-1 text-xs font-bold text-emerald-700">
                                            {{ formatPrice(item.gia_giam || item.gia_ban) }}
                                            <span v-if="item.gia_giam" class="ml-1 text-[10px] sm:text-[11px] font-normal text-gray-400 line-through">
                                                {{ formatPrice(item.gia_ban) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between space-x-4 sm:justify-end sm:space-x-6">
                                    <!-- Quantity Stepper -->
                                    <div class="flex items-center rounded-xl border border-gray-200 bg-gray-50 p-1">
                                        <button
                                            @click="updateQuantity(item.san_pham_id, item.so_luong - 1)"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-xs font-bold text-gray-700 shadow-sm transition-colors hover:bg-gray-100"
                                        >
                                            -
                                        </button>
                                        <input
                                            type="number"
                                            :value="item.so_luong"
                                            @change="updateQuantity(item.san_pham_id, parseFloat(($event.target as any).value) || 1)"
                                            class="w-10 border-none bg-transparent text-center text-xs font-black focus:outline-none"
                                        />
                                        <button
                                            @click="updateQuantity(item.san_pham_id, item.so_luong + 1)"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-xs font-bold text-gray-700 shadow-sm transition-colors hover:bg-gray-100"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <!-- Line total -->
                                    <div class="min-w-[80px] sm:min-w-[90px] text-right">
                                        <span class="text-xs sm:text-sm font-black text-gray-900">
                                            {{ formatPrice((item.gia_giam || item.gia_ban) * item.so_luong) }}
                                        </span>
                                    </div>

                                    <!-- Remove button -->
                                    <button
                                        @click="removeFromCart(item.san_pham_id)"
                                        class="rounded-lg p-1.5 sm:p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                        title="Xóa khỏi giỏ"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-gray-100 pt-4 text-xs">
                            <Link :href="route('products.index')" class="flex items-center font-bold text-emerald-700 hover:underline">
                                ← Tiếp tục mua sắm
                            </Link>
                            <button @click="handleClearCart" class="font-medium text-gray-400 hover:text-red-500">Xóa tất cả</button>
                        </div>
                    </div>
                </div>

                <!-- ORDER SUMMARY & CHECKOUT (RIGHT) -->
                <div class="space-y-4">
                    <div class="space-y-4 sm:space-y-5 rounded-2xl sm:rounded-3xl border border-gray-100 bg-white p-4 sm:p-6 shadow-sm">
                        <h3 class="border-b border-gray-100 pb-3 text-sm sm:text-base font-black text-gray-900">Tóm Tắt Đơn Hàng</h3>

                        <!-- Voucher Code Form -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="flex items-center text-xs font-bold text-gray-700">
                                    <Tag class="mr-1 h-3.5 w-3.5 text-emerald-600" /> Mã Giảm Giá / Voucher
                                </label>
                                <button
                                    v-if="availableCoupons && availableCoupons.length > 0"
                                    type="button"
                                    @click="showVoucherList = !showVoucherList"
                                    class="flex cursor-pointer items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline"
                                >
                                    <TicketPercent class="h-3.5 w-3.5" />
                                    <span>{{ showVoucherList ? 'Thu gọn' : `Xem mã (${availableCoupons.length})` }}</span>
                                </button>
                            </div>

                            <div class="flex space-x-2">
                                <input
                                    v-model="couponCode"
                                    type="text"
                                    placeholder="Nhập mã (VD: FRESH10)"
                                    class="flex-1 rounded-xl border border-gray-200 px-3 py-2.5 font-mono text-xs uppercase focus:border-emerald-500 focus:outline-none"
                                    :disabled="!!appliedCoupon"
                                />
                                <button
                                    v-if="!appliedCoupon"
                                    @click="handleApplyCoupon"
                                    :disabled="isCheckingCoupon"
                                    class="cursor-pointer rounded-xl bg-gray-900 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-emerald-600 disabled:opacity-50"
                                >
                                    {{ isCheckingCoupon ? '...' : 'Áp dụng' }}
                                </button>
                                <button
                                    v-else
                                    @click="removeCoupon"
                                    class="cursor-pointer rounded-xl bg-red-50 px-3 py-2.5 text-xs font-bold text-red-600 transition-colors hover:bg-red-100"
                                >
                                    Hủy mã
                                </button>
                            </div>
                            <p v-if="couponError" class="mt-1.5 flex items-center text-[11px] text-red-500">
                                <ShieldAlert class="mr-1 h-3 w-3" /> {{ couponError }}
                            </p>
                            <div
                                v-if="appliedCoupon"
                                class="mt-2 flex items-center justify-between rounded-xl bg-emerald-50 p-2.5 text-xs font-medium text-emerald-800"
                            >
                                <div>
                                    ✓ Đã áp dụng: <strong>{{ appliedCoupon.program }}</strong>
                                </div>
                                <span class="font-bold text-emerald-700">-{{ formatPrice(appliedCoupon.discount_amount) }}</span>
                            </div>

                            <!-- AVAILABLE VOUCHERS LIST -->
                            <div
                                v-if="showVoucherList && availableCoupons && availableCoupons.length > 0"
                                class="space-y-2 border-t border-gray-100 pt-2"
                            >
                                <p class="flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-gray-500">
                                    <Sparkles class="h-3 w-3 text-amber-500" />
                                    <span>Voucher khả dụng cho bạn</span>
                                </p>
                                <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                                    <div
                                        v-for="c in availableCoupons"
                                        :key="c.id"
                                        class="flex items-center justify-between gap-2 rounded-xl border border-dashed p-2.5 transition-all"
                                        :class="
                                            subtotal >= c.don_hang_toi_thieu
                                                ? 'border-emerald-300 bg-emerald-50/40'
                                                : 'border-gray-200 bg-gray-50/70 opacity-75'
                                        "
                                    >
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span
                                                    class="rounded bg-emerald-100 px-1.5 py-0.5 font-mono text-xs font-extrabold uppercase text-emerald-700"
                                                >
                                                    {{ c.ma_khuyen_mai }}
                                                </span>
                                                <span class="text-[10px] text-gray-400">HSD: {{ c.ngay_ket_thuc }}</span>
                                            </div>
                                            <p class="mt-0.5 truncate text-xs font-bold text-gray-800">{{ c.ten_chuong_trinh }}</p>
                                            <p class="text-[10px] text-gray-500">
                                                Đơn tối thiểu: <b>{{ formatPrice(c.don_hang_toi_thieu) }}</b>
                                                <span v-if="c.giam_toi_da"> &bull; Tối đa {{ formatPrice(c.giam_toi_da) }}</span>
                                            </p>
                                        </div>

                                        <div>
                                            <button
                                                v-if="appliedCoupon?.code === c.ma_khuyen_mai"
                                                type="button"
                                                class="flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white"
                                                disabled
                                            >
                                                <Check class="h-3 w-3" />
                                                <span>Đang dùng</span>
                                            </button>
                                            <button
                                                v-else-if="subtotal >= c.don_hang_toi_thieu"
                                                type="button"
                                                @click="selectVoucher(c)"
                                                class="cursor-pointer rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white transition hover:bg-emerald-700"
                                            >
                                                Áp dụng
                                            </button>
                                            <span
                                                v-else
                                                class="block whitespace-nowrap rounded bg-amber-50 px-2 py-1 text-center text-[10px] font-medium text-amber-700"
                                            >
                                                Thiếu {{ formatPrice(c.don_hang_toi_thieu - subtotal) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calculations -->
                        <div class="space-y-2.5 border-t border-gray-100 pt-3 text-xs text-gray-600">
                            <div class="flex justify-between">
                                <span>Tiền hàng:</span>
                                <strong class="text-gray-900">{{ formatPrice(subtotal) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Phí vận chuyển:</span>
                                <strong v-if="shippingFee === 0" class="font-bold text-emerald-700">Miễn phí</strong>
                                <strong v-else class="text-gray-900">{{ formatPrice(shippingFee) }}</strong>
                            </div>
                            <div v-if="discountAmount > 0" class="flex justify-between font-bold text-emerald-700">
                                <span>Giảm giá khuyến mãi:</span>
                                <span>-{{ formatPrice(discountAmount) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-100 pt-3 text-base font-black text-gray-900">
                                <span>Tổng thanh toán:</span>
                                <span class="text-xl font-black text-emerald-700">{{ formatPrice(grandTotal) }}</span>
                            </div>
                        </div>

                        <!-- Proceed to Checkout -->
                        <button
                            @click="handleProceedCheckout"
                            class="hover:scale-102 active:scale-98 flex w-full items-center justify-center space-x-2 rounded-2xl bg-emerald-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-emerald-600/30 transition-all hover:bg-emerald-700"
                            id="checkout-cta-btn"
                        >
                            <span>{{ !authUser ? 'Đăng Nhập Để Mua Hàng' : 'Tiến Hành Đặt Hàng' }}</span>
                            <ArrowRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- EMPTY CART STATE -->
            <div v-else class="mx-auto max-w-xl rounded-2xl sm:rounded-3xl border border-gray-100 bg-white p-8 sm:p-16 text-center shadow-sm">
                <div class="mx-auto mb-4 flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-2xl sm:rounded-3xl bg-emerald-50 text-emerald-600">
                    <ShoppingBag class="h-8 w-8 sm:h-10 sm:w-10" />
                </div>
                <h3 class="mb-2 text-lg sm:text-xl font-black text-gray-900">Giỏ hàng của bạn đang trống</h3>
                <p class="mb-6 text-xs text-gray-500">Hãy dạo quanh cửa hàng và chọn những món thực phẩm tươi ngon, bổ dưỡng cho gia đình nhé!</p>
                <Link
                    :href="route('products.index')"
                    class="inline-flex items-center space-x-2 rounded-2xl bg-emerald-600 px-6 py-3.5 text-xs font-black text-white shadow-lg shadow-emerald-600/20 transition-all hover:bg-emerald-700"
                >
                    <span>Khám phá sản phẩm ngay</span>
                    <ArrowRight class="h-4 w-4" />
                </Link>
            </div>
        </div>

        <!-- MOBILE STICKY CHECKOUT BAR -->
        <div
            v-if="cartItems.length > 0"
            class="fixed bottom-14 left-0 right-0 z-30 flex items-center justify-between border-t border-slate-200/90 bg-white/95 px-4 py-2.5 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] backdrop-blur-md lg:hidden"
        >
            <div>
                <span class="text-[10px] text-gray-500">Tổng thanh toán:</span>
                <p class="text-base font-black text-emerald-700 leading-tight">{{ formatPrice(grandTotal) }}</p>
            </div>
            <button
                @click="handleProceedCheckout"
                class="flex items-center space-x-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-md shadow-emerald-600/30 active:scale-95"
            >
                <span>{{ !authUser ? 'Đăng nhập để mua' : 'Đặt hàng' }}</span>
                <ArrowRight class="h-3.5 w-3.5" />
            </button>
        </div>
    </StoreLayout>
</template>
