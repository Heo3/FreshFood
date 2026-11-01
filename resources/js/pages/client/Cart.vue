<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { useCart } from '@/composables/useCart';
import {
    ShoppingBag,
    Trash2,
    ArrowRight,
    Tag,
    Truck,
    ShieldAlert,
    ChevronRight,
    MapPin,
} from 'lucide-vue-next';

const props = defineProps<{
    stores: Array<any>;
    initialCartItems?: Array<any>;
}>();

const { cartItems, selectedStoreId, subtotal, totalItems, updateQuantity, removeFromCart, clearCart, showToast, refreshCartPrices, syncCartToServer, saveCart, syncWithServer } = useCart();

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

const couponCode = ref('');
const appliedCoupon = ref<any>(null);
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
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}
</script>

<template>
    <StoreLayout title="Giỏ Hàng" :stores="stores">
        <!-- BREADCRUMBS -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Giỏ hàng của bạn</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mb-6 flex items-center">
                Giỏ Hàng Của Bạn
                <span class="ml-3 text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full">
                    {{ totalItems }} sản phẩm
                </span>
            </h1>

            <!-- IF CART HAS ITEMS -->
            <div v-if="cartItems.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- CART ITEMS LIST (LEFT) -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Branch Notice -->
                    <div class="px-4 py-3 bg-emerald-50/80 border border-emerald-200/80 rounded-2xl flex flex-wrap items-center justify-between gap-2 shadow-xs">
                        <div class="flex items-center space-x-2 text-xs text-emerald-950">
                            <MapPin class="w-4 h-4 text-emerald-600 shrink-0" />
                            <span>Đơn hàng được phục vụ bởi: <strong class="text-emerald-800 font-bold">{{ currentStoreName }}</strong></span>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-700 bg-white/90 px-2.5 py-1 rounded-xl border border-emerald-100">
                            Tất cả sản phẩm phải cùng chi nhánh
                        </span>
                    </div>

                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                        <!-- Free shipping progress bar -->
                        <div class="mb-6 p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100 flex items-center space-x-3 text-xs">
                            <Truck class="w-5 h-5 text-emerald-600 shrink-0" />
                            <div class="flex-1">
                                <p v-if="subtotal >= 300000" class="font-bold text-emerald-800">
                                    🎉 Chúc mừng! Đơn hàng của bạn được MIỄN PHÍ giao hàng siêu tốc 2h.
                                </p>
                                <p v-else class="text-gray-700">
                                    Mua thêm <strong class="text-emerald-700 font-black">{{ formatPrice(300000 - subtotal) }}</strong> để được <strong class="text-emerald-700">MIỄN PHÍ GIAO HÀNG</strong>!
                                </p>
                                <div class="w-full bg-gray-200 h-1.5 rounded-full mt-2 overflow-hidden">
                                    <div
                                        class="bg-emerald-600 h-full rounded-full transition-all duration-300"
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
                                class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                            >
                                <div class="flex items-center space-x-3.5 min-w-0">
                                    <img
                                        :src="item.hinh_anh_chinh"
                                        :alt="item.ten_san_pham"
                                        class="w-16 h-16 rounded-2xl object-cover bg-gray-50 border border-gray-100 shrink-0"
                                    />
                                    <div class="min-w-0">
                                        <Link
                                            :href="'/san-pham/' + item.duong_dan_seo"
                                            class="font-bold text-sm text-gray-900 hover:text-emerald-600 transition-colors line-clamp-1"
                                        >
                                            {{ item.ten_san_pham }}
                                        </Link>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ item.quy_cach || ('Đơn vị: ' + item.don_vi_tinh) }}
                                        </p>
                                        <div class="text-xs font-bold text-emerald-700 mt-1">
                                            {{ formatPrice(item.gia_giam || item.gia_ban) }}
                                            <span v-if="item.gia_giam" class="text-gray-400 line-through text-[11px] ml-1 font-normal">
                                                {{ formatPrice(item.gia_ban) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end space-x-6">
                                    <!-- Quantity Stepper -->
                                    <div class="flex items-center border border-gray-200 rounded-xl bg-gray-50 p-1">
                                        <button
                                            @click="updateQuantity(item.san_pham_id, item.so_luong - 1)"
                                            class="w-7 h-7 rounded-lg bg-white shadow-sm font-bold text-gray-700 hover:bg-gray-100 flex items-center justify-center transition-colors text-xs"
                                        >
                                            -
                                        </button>
                                        <input
                                            type="number"
                                            :value="item.so_luong"
                                            @change="updateQuantity(item.san_pham_id, parseFloat(($event.target as any).value) || 1)"
                                            class="w-10 text-center text-xs font-black bg-transparent border-none focus:outline-none"
                                        />
                                        <button
                                            @click="updateQuantity(item.san_pham_id, item.so_luong + 1)"
                                            class="w-7 h-7 rounded-lg bg-white shadow-sm font-bold text-gray-700 hover:bg-gray-100 flex items-center justify-center transition-colors text-xs"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <!-- Line total -->
                                    <div class="text-right min-w-[90px]">
                                        <span class="text-sm font-black text-gray-900">
                                            {{ formatPrice((item.gia_giam || item.gia_ban) * item.so_luong) }}
                                        </span>
                                    </div>

                                    <!-- Remove button -->
                                    <button
                                        @click="removeFromCart(item.san_pham_id)"
                                        class="p-2 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50"
                                        title="Xóa khỏi giỏ"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex justify-between items-center text-xs">
                            <Link :href="route('products.index')" class="text-emerald-700 font-bold hover:underline flex items-center">
                                ← Tiếp tục mua sắm thực phẩm khác
                            </Link>
                            <button @click="handleClearCart" class="text-gray-400 hover:text-red-500 font-medium">
                                Xóa tất cả giỏ hàng
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ORDER SUMMARY & CHECKOUT (RIGHT) -->
                <div class="space-y-4">
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-5">
                        <h3 class="text-base font-black text-gray-900 pb-3 border-b border-gray-100">
                            Tóm Tắt Đơn Hàng
                        </h3>

                        <!-- Voucher Code Form -->
                        <div>
                            <label class="text-xs font-bold text-gray-700 mb-2 block flex items-center">
                                <Tag class="w-3.5 h-3.5 mr-1 text-emerald-600" /> Mã Giảm Giá / Voucher
                            </label>
                            <div class="flex space-x-2">
                                <input
                                    v-model="couponCode"
                                    type="text"
                                    placeholder="Nhập mã (VD: FRESH10)"
                                    class="flex-1 uppercase font-mono text-xs px-3 py-2.5 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                                    :disabled="!!appliedCoupon"
                                />
                                <button
                                    v-if="!appliedCoupon"
                                    @click="handleApplyCoupon"
                                    :disabled="isCheckingCoupon"
                                    class="bg-gray-900 hover:bg-emerald-600 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-colors disabled:opacity-50"
                                >
                                    {{ isCheckingCoupon ? '...' : 'Áp dụng' }}
                                </button>
                                <button
                                    v-else
                                    @click="removeCoupon"
                                    class="bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold px-3 py-2.5 rounded-xl transition-colors"
                                >
                                    Hủy mã
                                </button>
                            </div>
                            <p v-if="couponError" class="text-[11px] text-red-500 mt-1.5 flex items-center">
                                <ShieldAlert class="w-3 h-3 mr-1" /> {{ couponError }}
                            </p>
                            <div v-if="appliedCoupon" class="mt-2 p-2.5 bg-emerald-50 rounded-xl text-xs text-emerald-800 font-medium">
                                ✓ Đã áp dụng: <strong>{{ appliedCoupon.program }}</strong> (-{{ formatPrice(appliedCoupon.discount_amount) }})
                            </div>
                        </div>

                        <!-- Calculations -->
                        <div class="space-y-2.5 text-xs text-gray-600 pt-3 border-t border-gray-100">
                            <div class="flex justify-between">
                                <span>Tiền hàng:</span>
                                <strong class="text-gray-900">{{ formatPrice(subtotal) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Phí vận chuyển:</span>
                                <strong v-if="shippingFee === 0" class="text-emerald-700 font-bold">Miễn phí</strong>
                                <strong v-else class="text-gray-900">{{ formatPrice(shippingFee) }}</strong>
                            </div>
                            <div v-if="discountAmount > 0" class="flex justify-between text-emerald-700 font-bold">
                                <span>Giảm giá khuyến mãi:</span>
                                <span>-{{ formatPrice(discountAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-black text-gray-900 pt-3 border-t border-gray-100">
                                <span>Tổng thanh toán:</span>
                                <span class="text-emerald-700 text-xl font-black">{{ formatPrice(grandTotal) }}</span>
                            </div>
                        </div>

                        <!-- Proceed to Checkout -->
                        <Link
                            :href="route('checkout.index')"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 px-6 rounded-2xl flex items-center justify-center space-x-2 shadow-lg shadow-emerald-600/30 transition-all hover:scale-102 active:scale-98 text-sm"
                            id="checkout-cta-btn"
                        >
                            <span>Tiến Hành Đặt Hàng</span>
                            <ArrowRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- EMPTY CART STATE -->
            <div v-else class="bg-white rounded-3xl p-16 text-center border border-gray-100 shadow-sm max-w-xl mx-auto">
                <div class="w-20 h-20 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                    <ShoppingBag class="w-10 h-10" />
                </div>
                <h3 class="text-xl font-black text-gray-900 mb-2">Giỏ hàng của bạn đang trống</h3>
                <p class="text-xs text-gray-500 mb-6">
                    Hãy dạo quanh cửa hàng và chọn những món thực phẩm tươi ngon, bổ dưỡng cho gia đình nhé!
                </p>
                <Link
                    :href="route('products.index')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-6 py-3.5 rounded-2xl inline-flex items-center space-x-2 shadow-lg shadow-emerald-600/20 transition-all"
                >
                    <span>Khám phá sản phẩm ngay</span>
                    <ArrowRight class="w-4 h-4" />
                </Link>
            </div>
        </div>
    </StoreLayout>
</template>
