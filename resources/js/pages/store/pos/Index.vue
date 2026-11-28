<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    Banknote,
    Barcode,
    Check,
    CheckCircle2,
    Coins,
    Copy,
    Maximize2,
    Minus,
    Plus,
    Printer,
    QrCode,
    Search,
    ShoppingCart,
    Sparkles,
    Trash2,
    UserCheck,
    X,
    BookmarkCheck,
    Clock,
    FileText,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface ProductItem {
    id: number;
    ten_san_pham: string;
    ma_va_vach: string;
    duong_dan_seo: string;
    hinh_anh_chinh: string;
    don_vi_tinh: string;
    danh_muc: string;
    gia_ban: number;
    gia_giam?: number | null;
    ton_kho: number;
}

interface CartItem {
    san_pham_id: number;
    ten_san_pham: string;
    don_vi_tinh: string;
    don_gia: number;
    so_luong: number;
}

const props = defineProps<{
    stores: Array<any>;
    currentStore: any;
    products: Array<ProductItem>;
}>();

const toast = useToast();

const searchQuery = ref('');
const selectedCategory = ref('all');
const barcodeInput = ref('');
const cart = ref<CartItem[]>([]);
const paymentMethod = ref<'tien_mat' | 'chuyen_khoan'>('tien_mat');
const cashGiven = ref<number | null>(null);
const note = ref('');
const isSubmitting = ref(false);
const completedOrder = ref<any | null>(null);
const showSuccessModal = ref(false);

// Customer Loyalty Points State
const customerPhone = ref('');
const customerName = ref('');
const customerData = ref<any | null>(null);
const isSearchingCustomer = ref(false);
const customerSearchMsg = ref('');
const usePoints = ref(false);
const pointsToUse = ref<number>(0);

// Multi-cart Hold Orders State
interface HoldCart {
    id: number;
    time: string;
    customerPhone: string;
    customerName: string;
    customerData: any;
    cart: CartItem[];
    note: string;
}

const savedCarts = ref<HoldCart[]>([]);
const isHoldModalOpen = ref(false);

function loadSavedCarts() {
    try {
        const raw = localStorage.getItem('pos_saved_carts');
        if (raw) savedCarts.value = JSON.parse(raw);
    } catch {}
}

function persistSavedCarts() {
    try {
        localStorage.setItem('pos_saved_carts', JSON.stringify(savedCarts.value));
    } catch {}
}

function holdCurrentCart() {
    if (cart.value.length === 0) {
        toast.info('Giỏ hàng đang trống, không thể tạm lưu.');
        return;
    }
    savedCarts.value.push({
        id: Date.now(),
        time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
        customerPhone: customerPhone.value,
        customerName: customerName.value,
        customerData: customerData.value,
        cart: [...cart.value],
        note: note.value,
    });
    persistSavedCarts();
    cart.value = [];
    clearCustomer();
    note.value = '';
    cashGiven.value = null;
    toast.success('Đã tạm lưu đơn hàng. Có thể mở lại bất cứ lúc nào!');
}

function restoreHoldCart(item: HoldCart) {
    if (cart.value.length > 0 && !confirm('Giỏ hàng hiện tại đang có món, bạn có muốn thay thế bằng đơn tạm lưu này không?')) {
        return;
    }
    cart.value = [...item.cart];
    customerPhone.value = item.customerPhone;
    customerName.value = item.customerName;
    customerData.value = item.customerData;
    note.value = item.note;
    savedCarts.value = savedCarts.value.filter((c) => c.id !== item.id);
    persistSavedCarts();
    isHoldModalOpen.value = false;
    toast.success('Đã khôi phục đơn hàng thành công!');
}

function removeHoldCart(id: number) {
    savedCarts.value = savedCarts.value.filter((c) => c.id !== id);
    persistSavedCarts();
    toast.info('Đã xóa đơn tạm lưu.');
}

function handleGlobalKeydown(e: KeyboardEvent) {
    if (e.key === 'F2') {
        e.preventDefault();
        const searchEl = document.getElementById('pos-search-input');
        searchEl?.focus();
    } else if (e.key === 'F4') {
        e.preventDefault();
        const phoneEl = document.getElementById('pos-phone-input');
        phoneEl?.focus();
    } else if (e.key === 'F7') {
        e.preventDefault();
        holdCurrentCart();
    } else if (e.key === 'F9') {
        e.preventDefault();
        if (cart.value.length > 0 && !isSubmitting.value) {
            handleCheckout();
        }
    }
}

onMounted(() => {
    loadSavedCarts();
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});

async function handleCheckCustomer() {
    const phone = customerPhone.value.trim();
    if (!phone) {
        customerData.value = null;
        customerSearchMsg.value = 'Vui lòng nhập SĐT khách hàng.';
        return;
    }

    isSearchingCustomer.value = true;
    customerSearchMsg.value = '';
    try {
        const res = await fetch(`${route('store.pos.customer')}?phone=${encodeURIComponent(phone)}`);
        const data = await res.json();
        if (data.success && data.customer) {
            customerData.value = data.customer;
            customerName.value = data.customer.name;
            customerSearchMsg.value = `Thành viên: ${data.customer.name} (${data.customer.loyalty_points} điểm)`;
            const maxUsable = Math.min(data.customer.loyalty_points, Math.floor(subtotal.value / 1000));
            pointsToUse.value = maxUsable;
        } else {
            customerData.value = {
                id: null,
                name: customerName.value || `Khách ${phone}`,
                phone: phone,
                loyalty_points: 0,
                points_value: 0,
                is_new: true,
            };
            customerSearchMsg.value = 'Khách mới (chưa có điểm tích lũy). Sẽ tạo tài khoản khi thanh toán.';
        }
    } catch (e: any) {
        customerSearchMsg.value = 'Lỗi tra cứu: ' + e.message;
    } finally {
        isSearchingCustomer.value = false;
    }
}

function clearCustomer() {
    customerPhone.value = '';
    customerName.value = '';
    customerData.value = null;
    customerSearchMsg.value = '';
    usePoints.value = false;
    pointsToUse.value = 0;
}

const categories = computed(() => {
    const set = new Set<string>();
    props.products.forEach((p) => {
        if (p.danh_muc) set.add(p.danh_muc);
    });
    return Array.from(set);
});

const filteredProducts = computed(() => {
    return props.products.filter((p) => {
        const matchesCategory = selectedCategory.value === 'all' || p.danh_muc === selectedCategory.value;
        const q = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !q || p.ten_san_pham.toLowerCase().includes(q) || p.ma_va_vach.includes(q);
        return matchesCategory && matchesSearch;
    });
});

function addToCart(product: ProductItem, qty: number = 1) {
    if (product.ton_kho <= 0) {
        toast.warning(`Sản phẩm "${product.ten_san_pham}" hiện đang hết hàng tại chi nhánh!`);
        return;
    }

    const price = product.gia_giam ?? product.gia_ban;
    const existing = cart.value.find((i) => i.san_pham_id === product.id);

    if (existing) {
        if (existing.so_luong + qty > product.ton_kho) {
            toast.warning(`Số lượng vượt quá tồn kho khả dụng (${product.ton_kho} ${product.don_vi_tinh})!`);
            return;
        }
        existing.so_luong = Number((existing.so_luong + qty).toFixed(2));
    } else {
        cart.value.push({
            san_pham_id: product.id,
            ten_san_pham: product.ten_san_pham,
            don_vi_tinh: product.don_vi_tinh,
            don_gia: price,
            so_luong: qty,
        });
    }
}

function updateQuantity(item: CartItem, delta: number) {
    const product = props.products.find((p) => p.id === item.san_pham_id);
    const newQty = Number((item.so_luong + delta).toFixed(2));

    if (newQty <= 0) {
        removeFromCart(item.san_pham_id);
        return;
    }

    if (product && newQty > product.ton_kho) {
        toast.warning(`Số lượng vượt quá tồn kho khả dụng (${product.ton_kho} ${product.don_vi_tinh})!`);
        return;
    }

    item.so_luong = newQty;
}

function removeFromCart(productId: number) {
    cart.value = cart.value.filter((i) => i.san_pham_id !== productId);
}

function clearCart() {
    cart.value = [];
    cashGiven.value = null;
    note.value = '';
}

function handleBarcodeScan() {
    const code = barcodeInput.value.trim();
    if (!code) return;

    const found = props.products.find((p) => p.ma_va_vach === code);
    if (found) {
        addToCart(found, 1);
        barcodeInput.value = '';
        toast.success(`Đã thêm "${found.ten_san_pham}"`);
    } else {
        toast.warning(`Không tìm thấy sản phẩm có mã vạch "${code}"`);
    }
}

const totalQuantity = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.so_luong, 0);
});

const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.don_gia * item.so_luong, 0);
});

const pointsDiscount = computed(() => {
    if (!usePoints.value || !customerData.value || customerData.value.loyalty_points <= 0) return 0;
    const maxPossible = Math.min(customerData.value.loyalty_points, Math.floor(subtotal.value / 1000));
    const toUse = Math.min(pointsToUse.value || 0, maxPossible);
    return toUse * 1000;
});

const finalTotal = computed(() => {
    return Math.max(0, subtotal.value - pointsDiscount.value);
});

const pointsWillEarn = computed(() => {
    if (!customerPhone.value.trim()) return 0;
    return Math.floor(finalTotal.value / 10000); // 10.000đ = 1 điểm
});

const changeToReturn = computed(() => {
    if (!cashGiven.value || cashGiven.value < finalTotal.value) return 0;
    return cashGiven.value - finalTotal.value;
});

// Bank Transfer QR Code Configuration (VPBank)
const showQrModal = ref(false);
const copiedAccount = ref(false);

const BANK_INFO = {
    bankCode: 'VPB',
    bankName: 'VPBank (Ngân Hàng TMCP Việt Nam Thịnh Vượng)',
    accountNumber: '0329643444',
    accountName: 'PHAN VAN THIEN DUY',
};

const posTransferContent = computed(() => {
    const storeCode = props.currentStore?.ma_cua_hang || 'CH';
    const phone = customerPhone.value.trim();
    if (phone) {
        return `POS ${storeCode} ${phone.slice(-4)}`;
    }
    return `POS ${storeCode}`;
});

const posQrUrl = computed(() => {
    const amount = finalTotal.value;
    const memo = encodeURIComponent(posTransferContent.value);
    return `https://img.vietqr.io/image/${BANK_INFO.bankCode}-${BANK_INFO.accountNumber}-compact2.png?amount=${amount}&addInfo=${memo}&accountName=${encodeURIComponent(BANK_INFO.accountName)}`;
});

const posQrLargeUrl = computed(() => {
    const amount = finalTotal.value;
    const memo = encodeURIComponent(posTransferContent.value);
    return `https://img.vietqr.io/image/${BANK_INFO.bankCode}-${BANK_INFO.accountNumber}-print.png?amount=${amount}&addInfo=${memo}&accountName=${encodeURIComponent(BANK_INFO.accountName)}`;
});

async function copyAccountNumber() {
    try {
        await navigator.clipboard.writeText(BANK_INFO.accountNumber);
        copiedAccount.value = true;
        setTimeout(() => {
            copiedAccount.value = false;
        }, 2000);
    } catch {
        // fallback
    }
}

async function handleCheckout() {
    if (cart.value.length === 0) {
        toast.warning('Vui lòng thêm sản phẩm vào giỏ hàng POS!');
        return;
    }

    if (paymentMethod.value === 'tien_mat' && cashGiven.value && cashGiven.value < finalTotal.value) {
        toast.warning('Tiền khách đưa chưa đủ thanh toán!');
        return;
    }

    isSubmitting.value = true;
    try {
        const response = await fetch(route('store.pos.checkout'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
            },
            body: JSON.stringify({
                items: cart.value.map((i) => ({
                    san_pham_id: i.san_pham_id,
                    so_luong: i.so_luong,
                    don_gia: i.don_gia,
                })),
                phuong_thuc_thanh_toan: paymentMethod.value,
                tien_khach_dua: cashGiven.value ?? finalTotal.value,
                customer_phone: customerPhone.value.trim() || null,
                customer_name: customerName.value.trim() || null,
                use_points: usePoints.value,
                points_to_use: pointsToUse.value,
                ghi_chu: note.value,
                store_id: props.currentStore?.id,
            }),
        });

        const data = await response.json();
        if (data.success) {
            completedOrder.value = {
                ...data.don_hang,
                customer_info: data.customer,
            };
            showSuccessModal.value = true;
            toast.success(`Thanh toán thành công đơn #${data.don_hang.ma_don_hang}`);
            cart.value = [];
            cashGiven.value = null;
            note.value = '';
            clearCustomer();
        } else {
            toast.error(data.message || 'Có lỗi xảy ra khi xuất kho.');
        }
    } catch (e: any) {
        toast.error('Lỗi kết nối máy chủ: ' + e.message);
    } finally {
        isSubmitting.value = false;
    }
}

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
}

function printReceipt() {
    window.print();
}
</script>

<template>
    <StoreOpsLayout title="Thu Ngân POS Bán Lẻ" :stores="stores" :currentStore="currentStore">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- LEFT: PRODUCT CATALOG & BARCODE SCANNER (8 COLS) -->
            <div class="space-y-4 lg:col-span-7 xl:col-span-8">
                <!-- TOP CONTROLS: BARCODE + SEARCH -->
                <div class="flex flex-col gap-3 rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm sm:flex-row">
                    <!-- BARCODE SCANNER INPUT -->
                    <div class="relative flex-1">
                        <Barcode class="absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="barcodeInput"
                            @keydown.enter.prevent="handleBarcodeScan"
                            type="text"
                            placeholder="Quét hoặc nhập mã vạch (Enter)..."
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 font-mono text-xs font-bold outline-none transition focus:bg-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <!-- NAME SEARCH INPUT -->
                    <div class="relative flex-1">
                        <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            id="pos-search-input"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm tên sản phẩm (F2)..."
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-xs outline-none transition focus:bg-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <!-- CATEGORY CHIPS -->
                <div class="flex items-center space-x-2 overflow-x-auto pb-1 text-xs">
                    <button
                        @click="selectedCategory = 'all'"
                        :class="
                            selectedCategory === 'all'
                                ? 'bg-slate-900 font-bold text-white'
                                : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-100'
                        "
                        class="whitespace-nowrap rounded-xl px-3.5 py-1.5 transition"
                    >
                        Tất cả ({{ products.length }})
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        @click="selectedCategory = cat"
                        :class="
                            selectedCategory === cat
                                ? 'bg-slate-900 font-bold text-white'
                                : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-100'
                        "
                        class="whitespace-nowrap rounded-xl px-3.5 py-1.5 transition"
                    >
                        {{ cat }}
                    </button>
                </div>

                <!-- PRODUCT GRID -->
                <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 xl:grid-cols-4">
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        @click="addToCart(product)"
                        :class="
                            product.ton_kho <= 0
                                ? 'cursor-not-allowed bg-slate-100 opacity-50'
                                : 'cursor-pointer bg-white hover:border-emerald-500 hover:shadow-md active:scale-[0.98]'
                        "
                        class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 p-3 transition"
                    >
                        <div>
                            <div class="relative mb-2 aspect-square w-full overflow-hidden rounded-xl bg-slate-50">
                                <img
                                    :src="product.hinh_anh_chinh"
                                    :alt="product.ten_san_pham"
                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                                />
                                <span
                                    :class="product.ton_kho > 0 ? 'bg-emerald-600 text-white' : 'bg-red-500 text-white'"
                                    class="absolute right-1.5 top-1.5 rounded-md px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                >
                                    {{ product.ton_kho > 0 ? `Còn ${product.ton_kho} ${product.don_vi_tinh}` : 'Hết hàng' }}
                                </span>
                            </div>
                            <h4 class="line-clamp-2 text-xs font-bold leading-snug text-slate-800">{{ product.ten_san_pham }}</h4>
                            <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ product.ma_va_vach }}</p>
                        </div>

                        <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2">
                            <div>
                                <span class="text-xs font-black text-emerald-600">
                                    {{ formatPrice(product.gia_giam ?? product.gia_ban) }}
                                </span>
                                <span class="text-[10px] text-slate-400">/{{ product.don_vi_tinh }}</span>
                            </div>
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-600 group-hover:text-white"
                            >
                                <Plus class="h-4 w-4" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: POS CART & BILLING PANEL (4 COLS) -->
            <div class="space-y-4 lg:col-span-5 xl:col-span-4">
                <div
                    class="sticky top-28 flex h-[calc(100vh-10rem)] flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm"
                >
                    <!-- CART HEADER -->
                    <div class="flex items-center justify-between bg-slate-900 p-4 text-white">
                        <div class="flex items-center space-x-2">
                            <ShoppingCart class="h-5 w-5 text-emerald-400" />
                            <h3 class="text-sm font-bold">Hóa Đơn Bán Hàng</h3>
                            <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs font-bold text-emerald-400">
                                {{ totalQuantity.toFixed(1) }} SP
                            </span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <button
                                v-if="savedCarts.length > 0"
                                @click="isHoldModalOpen = true"
                                type="button"
                                class="flex items-center space-x-1 rounded-xl border border-amber-600/50 bg-amber-500/20 px-2.5 py-1 text-xs font-bold text-amber-300 transition hover:bg-amber-500/30"
                                title="Mở danh sách đơn hàng tạm lưu"
                            >
                                <Clock class="h-3.5 w-3.5 text-amber-400" />
                                <span>Đơn chờ ({{ savedCarts.length }})</span>
                            </button>
                            <button
                                v-if="cart.length > 0"
                                @click="holdCurrentCart"
                                type="button"
                                class="flex items-center space-x-1 rounded-xl border border-slate-700 bg-slate-800 px-2.5 py-1 text-xs font-bold text-emerald-400 transition hover:bg-slate-700"
                                title="Tạm lưu đơn này để tính cho khách khác (F7)"
                            >
                                <BookmarkCheck class="h-3.5 w-3.5" />
                                <span>Tạm lưu (F7)</span>
                            </button>
                            <button
                                @click="showQrModal = true"
                                type="button"
                                class="flex items-center space-x-1 rounded-xl border border-slate-700 bg-slate-800 px-2.5 py-1 text-xs font-bold text-blue-400 transition hover:bg-slate-700 hover:text-blue-300"
                                title="Mở mã QR quầy thanh toán"
                            >
                                <QrCode class="h-3.5 w-3.5" />
                                <span>QR Quầy</span>
                            </button>
                            <button v-if="cart.length > 0" @click="clearCart" class="text-xs font-medium text-red-400 hover:text-red-300">
                                Xóa hết
                            </button>
                        </div>
                    </div>

                    <!-- CART ITEMS LIST -->
                    <div class="flex-1 space-y-3 divide-y divide-slate-100 overflow-y-auto p-4">
                        <div v-if="cart.length === 0" class="flex h-full flex-col items-center justify-center py-12 text-center text-slate-400">
                            <ShoppingCart class="mb-2 h-12 w-12 stroke-1 text-slate-200" />
                            <p class="text-xs font-medium">Chưa có sản phẩm nào trong hóa đơn</p>
                            <p class="mt-1 text-[11px] text-slate-400">Quét mã vạch hoặc bấm chọn sản phẩm để thêm</p>
                        </div>

                        <div v-for="item in cart" :key="item.san_pham_id" class="flex items-center justify-between gap-3 pt-3 first:pt-0">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-bold text-slate-800">{{ item.ten_san_pham }}</p>
                                <p class="text-[11px] font-medium text-slate-500">{{ formatPrice(item.don_gia) }} / {{ item.don_vi_tinh }}</p>
                            </div>

                            <!-- QUANTITY CONTROLLER -->
                            <div class="flex items-center space-x-1.5 rounded-xl bg-slate-100 px-2 py-1">
                                <button
                                    @click="updateQuantity(item, -1)"
                                    class="flex h-5 w-5 items-center justify-center rounded text-slate-700 transition hover:bg-white"
                                >
                                    <Minus class="h-3 w-3" />
                                </button>
                                <span class="w-8 text-center text-xs font-black text-slate-900">{{ item.so_luong }}</span>
                                <button
                                    @click="updateQuantity(item, 1)"
                                    class="flex h-5 w-5 items-center justify-center rounded text-slate-700 transition hover:bg-white"
                                >
                                    <Plus class="h-3 w-3" />
                                </button>
                            </div>

                            <div class="min-w-[70px] text-right">
                                <p class="text-xs font-black text-slate-900">{{ formatPrice(item.don_gia * item.so_luong) }}</p>
                            </div>

                            <button @click="removeFromCart(item.san_pham_id)" class="text-slate-300 transition hover:text-red-500">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- BILLING & PAYMENT FORM -->
                    <div class="space-y-3 border-t border-slate-200/80 bg-slate-50/80 p-4">
                        <!-- CUSTOMER & LOYALTY POINTS SECTION -->
                        <div class="space-y-2.5 rounded-2xl border border-slate-200 bg-white p-3">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center space-x-1.5 text-xs font-bold text-slate-800">
                                    <Coins class="h-4 w-4 text-amber-500" />
                                    <span>Tích & Trừ Điểm Khách Hàng</span>
                                </span>
                                <button
                                    v-if="customerData || customerPhone"
                                    @click="clearCustomer"
                                    type="button"
                                    class="text-[11px] text-slate-400 transition hover:text-red-500"
                                >
                                    Đổi khách
                                </button>
                            </div>

                            <!-- PHONE SEARCH INPUT -->
                            <div class="flex items-center space-x-2">
                                <input
                                    id="pos-phone-input"
                                    v-model="customerPhone"
                                    @keydown.enter.prevent="handleCheckCustomer"
                                    type="text"
                                    placeholder="Nhập SĐT khách hàng (F4)..."
                                    class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 font-mono text-xs outline-none focus:bg-white focus:ring-2 focus:ring-emerald-500"
                                />
                                <button
                                    @click="handleCheckCustomer"
                                    :disabled="isSearchingCustomer || !customerPhone.trim()"
                                    type="button"
                                    class="rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-slate-800 disabled:opacity-50"
                                >
                                    {{ isSearchingCustomer ? 'Đang kiểm tra...' : 'Tra cứu' }}
                                </button>
                            </div>

                            <!-- CUSTOMER DETAILS / LOYALTY INFO -->
                            <div v-if="customerData" class="space-y-2 border-t border-slate-100 pt-2">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center space-x-1.5">
                                        <UserCheck class="h-3.5 w-3.5 text-emerald-600" />
                                        <span class="font-bold text-slate-800">{{ customerData.name }}</span>
                                    </div>
                                    <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-600">
                                        {{ customerData.loyalty_points }} điểm (=
                                        {{ formatPrice(customerData.points_value || customerData.loyalty_points * 1000) }})
                                    </span>
                                </div>

                                <!-- IF NEW CUSTOMER, ALLOW EDITING NAME -->
                                <div v-if="customerData.is_new" class="space-y-1">
                                    <input
                                        v-model="customerName"
                                        type="text"
                                        placeholder="Nhập họ tên khách hàng (tùy chọn)..."
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs outline-none focus:ring-1 focus:ring-emerald-500"
                                    />
                                    <p class="text-[10px] text-slate-400">Khách mới sẽ tự động tích điểm sau khi thanh toán.</p>
                                </div>

                                <!-- REDEEM POINTS CHECKBOX & INPUT -->
                                <div v-if="customerData.loyalty_points > 0" class="space-y-2 rounded-xl border border-amber-100 bg-amber-50/60 p-2.5">
                                    <label class="flex cursor-pointer select-none items-center space-x-2">
                                        <input type="checkbox" v-model="usePoints" class="h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500" />
                                        <span class="text-xs font-bold text-slate-800">Dùng điểm trừ vào đơn này</span>
                                    </label>

                                    <div v-if="usePoints" class="flex items-center space-x-2 pt-1">
                                        <input
                                            v-model.number="pointsToUse"
                                            type="number"
                                            min="0"
                                            :max="Math.min(customerData.loyalty_points, Math.floor(subtotal / 1000))"
                                            class="w-24 rounded-lg border border-amber-300 bg-white px-2 py-1 text-xs font-black text-slate-900 outline-none focus:ring-2 focus:ring-amber-500"
                                        />
                                        <button
                                            @click="pointsToUse = Math.min(customerData.loyalty_points, Math.floor(subtotal / 1000))"
                                            type="button"
                                            class="rounded-md bg-amber-100 px-2 py-1 text-[11px] font-bold text-amber-700 transition hover:bg-amber-200"
                                        >
                                            Dùng tối đa
                                        </button>
                                        <span class="ml-auto text-xs font-bold text-emerald-600"> -{{ formatPrice(pointsDiscount) }} </span>
                                    </div>
                                    <p v-if="usePoints" class="text-[10px] text-slate-500">
                                        Quy đổi: 1 điểm = 1.000đ. Đã giảm {{ formatPrice(pointsDiscount) }}.
                                    </p>
                                </div>

                                <!-- WILL EARN NOTIFICATION -->
                                <div
                                    v-if="pointsWillEarn > 0"
                                    class="flex items-center space-x-1.5 rounded-lg bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700"
                                >
                                    <Sparkles class="h-3.5 w-3.5 flex-shrink-0 text-emerald-600" />
                                    <span
                                        >Đơn này tích được <strong>+{{ pointsWillEarn }} điểm</strong> (10.000đ = 1 điểm)</span
                                    >
                                </div>
                            </div>

                            <p v-else-if="customerSearchMsg" class="text-[11px] italic text-slate-500">
                                {{ customerSearchMsg }}
                            </p>
                        </div>

                        <!-- PAYMENT METHOD -->
                        <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                            <button
                                @click="paymentMethod = 'tien_mat'"
                                :class="
                                    paymentMethod === 'tien_mat'
                                        ? 'bg-emerald-600 text-white shadow-sm'
                                        : 'border border-slate-200 bg-white text-slate-700'
                                "
                                class="flex items-center justify-center space-x-1.5 rounded-xl py-2 transition"
                            >
                                <Banknote class="h-4 w-4" />
                                <span>Tiền Mặt</span>
                            </button>
                            <button
                                @click="paymentMethod = 'chuyen_khoan'"
                                :class="
                                    paymentMethod === 'chuyen_khoan'
                                        ? 'bg-emerald-600 text-white shadow-sm'
                                        : 'border border-slate-200 bg-white text-slate-700'
                                "
                                class="flex items-center justify-center space-x-1.5 rounded-xl py-2 transition"
                            >
                                <QrCode class="h-4 w-4" />
                                <span>Chuyển Khoản / QR</span>
                            </button>
                        </div>

                        <!-- DYNAMIC QR PAYMENT CARD FOR POS (VPBANK) -->
                        <div
                            v-if="paymentMethod === 'chuyen_khoan'"
                            class="space-y-2.5 rounded-2xl border-2 border-blue-200 bg-gradient-to-br from-blue-50/80 via-white to-blue-50/40 p-3 shadow-sm duration-200 animate-in fade-in"
                        >
                            <div class="flex items-center justify-between border-b border-blue-100 pb-2">
                                <div class="flex items-center space-x-1.5">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-lg bg-blue-600 text-[10px] font-black text-white">
                                        QR
                                    </div>
                                    <span class="text-xs font-black uppercase tracking-tight text-blue-950">Quét Mã Chuyển Khoản / VNPAY</span>
                                </div>
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-black text-blue-800"> VPBank </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <!-- QR Thumbnail (Click to enlarge) -->
                                <div
                                    @click="showQrModal = true"
                                    class="group relative flex-shrink-0 cursor-pointer rounded-xl border border-blue-200 bg-white p-1 shadow-sm"
                                    title="Bấm để phóng to cho khách quét"
                                >
                                    <img
                                        :src="posQrUrl"
                                        alt="Mã QR POS"
                                        class="h-20 w-20 rounded-lg object-contain transition group-hover:opacity-90"
                                    />
                                    <div
                                        class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/30 text-white opacity-0 transition group-hover:opacity-100"
                                    >
                                        <Maximize2 class="h-4 w-4" />
                                    </div>
                                </div>

                                <!-- Bank Account Info -->
                                <div class="min-w-0 flex-1 space-y-1 text-xs">
                                    <div>
                                        <p class="text-[10px] font-medium leading-none text-slate-400">Chủ tài khoản</p>
                                        <p class="truncate text-[11px] font-bold text-slate-800">{{ BANK_INFO.accountName }}</p>
                                    </div>

                                    <div>
                                        <p class="text-[10px] font-medium leading-none text-slate-400">Số tài khoản VPBank</p>
                                        <div class="mt-0.5 flex items-center space-x-1">
                                            <span class="font-mono text-xs font-black text-blue-700">{{ BANK_INFO.accountNumber }}</span>
                                            <button
                                                @click="copyAccountNumber"
                                                type="button"
                                                class="p-0.5 text-slate-400 transition hover:text-blue-600"
                                                title="Sao chép số tài khoản"
                                            >
                                                <Check v-if="copiedAccount" class="h-3.5 w-3.5 text-emerald-600" />
                                                <Copy v-else class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-[10px] font-medium leading-none text-slate-400">Nội dung chuyển khoản</p>
                                        <p class="truncate font-mono text-[10px] font-bold text-slate-700">{{ posTransferContent }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Enlarge Button for Customer -->
                            <button
                                @click="showQrModal = true"
                                type="button"
                                class="flex w-full items-center justify-center space-x-1.5 rounded-xl bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700"
                            >
                                <Maximize2 class="h-3.5 w-3.5" />
                                <span>Mở To Mã QR Cho Khách Quét</span>
                            </button>
                        </div>

                        <!-- TOTALS -->
                        <div class="space-y-1.5 pt-1 text-xs">
                            <div class="flex items-center justify-between text-slate-500">
                                <span>Tổng tiền hàng:</span>
                                <span class="font-bold text-slate-800">{{ formatPrice(subtotal) }}</span>
                            </div>

                            <div v-if="pointsDiscount > 0" class="flex items-center justify-between font-bold text-emerald-600">
                                <span>Trừ điểm tích lũy ({{ pointsToUse }} điểm):</span>
                                <span>-{{ formatPrice(pointsDiscount) }}</span>
                            </div>

                            <div v-if="paymentMethod === 'tien_mat'" class="flex items-center justify-between text-slate-700">
                                <span class="font-medium">Tiền khách đưa:</span>
                                <input
                                    v-model.number="cashGiven"
                                    type="number"
                                    :placeholder="finalTotal.toString()"
                                    class="w-32 rounded-lg border border-slate-300 bg-white px-2 py-1 text-right text-xs font-black text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>

                            <div v-if="paymentMethod === 'tien_mat' && cashGiven" class="flex items-center justify-between text-slate-700">
                                <span class="font-medium">Tiền thừa trả khách:</span>
                                <span class="text-sm font-black text-emerald-700">{{ formatPrice(changeToReturn) }}</span>
                            </div>

                            <div class="flex items-center justify-between border-t border-slate-200 pt-2 text-sm">
                                <span class="font-black text-slate-900">KHÁCH PHẢI TRẢ:</span>
                                <span class="text-lg font-black text-emerald-600">{{ formatPrice(finalTotal) }}</span>
                            </div>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <button
                            @click="handleCheckout"
                            :disabled="cart.length === 0 || isSubmitting"
                            :class="
                                cart.length === 0 || isSubmitting
                                    ? 'cursor-not-allowed bg-slate-300 text-slate-500'
                                    : 'bg-emerald-600 text-white shadow-lg shadow-emerald-700/20 hover:bg-emerald-700 active:scale-[0.99]'
                            "
                            class="flex w-full items-center justify-center space-x-2 rounded-2xl py-3 text-sm font-black tracking-wide transition"
                        >
                            <span v-if="isSubmitting">Đang xử lý xuất kho FEFO...</span>
                            <span v-else>XÁC NHẬN THANH TOÁN ({{ formatPrice(finalTotal) }})</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PAYMENT SUCCESS MODAL & INVOICE PREVIEW -->
        <div
            v-if="showSuccessModal && completedOrder"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md space-y-4 rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600" />
                        <h3 class="text-base font-black text-slate-900">Thanh Toán Thành Công</h3>
                    </div>
                    <button @click="showSuccessModal = false" class="text-slate-400 hover:text-slate-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- INVOICE CONTENT -->
                <div id="pos-receipt" class="space-y-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 font-mono text-xs">
                    <div class="border-b border-dashed border-slate-300 pb-2 text-center">
                        <p class="text-sm font-black text-slate-900">FRESH FOOD SUPERMARKET</p>
                        <p class="text-[10px] text-slate-500">{{ currentStore?.dia_chi }}</p>
                        <p class="text-[10px] text-slate-500">Mã Đơn: {{ completedOrder.ma_don_hang }}</p>
                        <p class="text-[10px] text-slate-400">{{ new Date().toLocaleString('vi-VN') }}</p>
                    </div>

                    <div class="space-y-1.5 divide-y divide-slate-100 py-1">
                        <div v-for="item in completedOrder.chi_tiets" :key="item.id" class="flex justify-between pt-1">
                            <div>
                                <p class="font-bold text-slate-800">{{ item.ten_san_pham }}</p>
                                <p class="text-[10px] text-slate-400">{{ item.so_luong }} x {{ formatPrice(item.don_gia) }}</p>
                            </div>
                            <span class="font-bold text-slate-900">{{ formatPrice(item.thanh_tien) }}</span>
                        </div>
                    </div>

                    <div class="space-y-1 border-t border-dashed border-slate-300 pt-2">
                        <div v-if="completedOrder.tien_giam_gia > 0" class="flex justify-between text-[11px] text-slate-600">
                            <span>Tiền hàng:</span>
                            <span>{{ formatPrice(completedOrder.tong_tien_hang) }}</span>
                        </div>
                        <div v-if="completedOrder.tien_giam_gia > 0" class="flex justify-between text-[11px] font-bold text-emerald-600">
                            <span>Trừ điểm tích lũy:</span>
                            <span>-{{ formatPrice(completedOrder.tien_giam_gia) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold text-slate-900">
                            <span>TỔNG THANH TOÁN:</span>
                            <span class="text-emerald-700">{{ formatPrice(completedOrder.tong_thanhtoan) }}</span>
                        </div>
                        <div class="flex justify-between text-[11px] text-slate-500">
                            <span>Phương thức:</span>
                            <span
                                class="font-bold uppercase"
                                :class="completedOrder.phuong_thuc_thanh_toan === 'tien_mat' ? 'text-slate-700' : 'text-blue-700'"
                            >
                                {{ completedOrder.phuong_thuc_thanh_toan === 'tien_mat' ? 'Tiền mặt' : 'Chuyển khoản (VPBank - 0329643444)' }}
                            </span>
                        </div>

                        <!-- CUSTOMER LOYALTY SUMMARY IN RECEIPT -->
                        <div
                            v-if="completedOrder.customer_info"
                            class="mt-2 space-y-0.5 border-t border-dotted border-slate-300 pt-2 text-[11px] text-slate-600"
                        >
                            <div class="flex justify-between">
                                <span>Khách hàng:</span>
                                <span class="font-bold text-slate-800"
                                    >{{ completedOrder.customer_info.name }} ({{ completedOrder.customer_info.phone }})</span
                                >
                            </div>
                            <div v-if="completedOrder.customer_info.points_used > 0" class="flex justify-between text-amber-700">
                                <span>Điểm đã tiêu:</span>
                                <span>-{{ completedOrder.customer_info.points_used }} điểm</span>
                            </div>
                            <div v-if="completedOrder.customer_info.points_earned > 0" class="flex justify-between font-bold text-emerald-700">
                                <span>Điểm tích lũy mới:</span>
                                <span>+{{ completedOrder.customer_info.points_earned }} điểm</span>
                            </div>
                            <div class="flex justify-between pt-0.5 font-bold text-slate-800">
                                <span>Điểm hiện có:</span>
                                <span>{{ completedOrder.customer_info.current_points }} điểm</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 text-center text-[10px] italic text-slate-400">Cảm ơn quý khách! Hàng tươi sạch mỗi ngày.</div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex items-center space-x-2 pt-2">
                    <button
                        @click="printReceipt"
                        class="flex flex-1 items-center justify-center space-x-2 rounded-xl border border-slate-200 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        <Printer class="h-4 w-4" />
                        <span>In Hóa Đơn</span>
                    </button>
                    <button
                        @click="showSuccessModal = false"
                        class="flex-1 rounded-xl bg-emerald-600 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-700"
                    >
                        Tạo Đơn Tiếp Theo
                    </button>
                </div>
            </div>
        </div>

        <!-- LARGE CUSTOMER-FACING QR CODE MODAL FOR POS COUNTER -->
        <div v-if="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm">
            <div
                class="w-full max-w-sm space-y-4 rounded-3xl border border-slate-100 bg-white p-6 text-center shadow-2xl duration-200 animate-in fade-in zoom-in-95"
            >
                <!-- MODAL HEADER -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2 text-left">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-600 text-xs font-black text-white">QR</div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900">QUÉT MÃ THANH TOÁN POS</h3>
                            <p class="text-[11px] text-slate-500">VPBank / Ví VNPAY / Mọi App Ngân Hàng</p>
                        </div>
                    </div>
                    <button
                        @click="showQrModal = false"
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition hover:bg-slate-200"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- AMOUNT DISPLAY -->
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50 p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Số tiền cần thanh toán</p>
                    <p class="mt-0.5 font-mono text-2xl font-black text-emerald-600">
                        {{ formatPrice(finalTotal) }}
                    </p>
                </div>

                <!-- QR IMAGE -->
                <div class="inline-block rounded-2xl border-2 border-blue-100 bg-white p-3 shadow-md">
                    <img :src="posQrLargeUrl" alt="Mã QR Chuyển Khoản POS" class="mx-auto h-64 w-64 rounded-lg object-contain" />
                </div>

                <!-- BANK INFO SUMMARY -->
                <div class="space-y-1 rounded-2xl border border-blue-100 bg-blue-50/60 p-3 text-left text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Ngân hàng:</span>
                        <span class="font-bold text-slate-800">VPBank</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Số tài khoản:</span>
                        <div class="flex items-center space-x-1">
                            <span class="font-mono font-black text-blue-700">{{ BANK_INFO.accountNumber }}</span>
                            <button @click="copyAccountNumber" type="button" class="text-slate-400 transition hover:text-blue-600">
                                <Check v-if="copiedAccount" class="h-3.5 w-3.5 text-emerald-600" />
                                <Copy v-else class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Chủ tài khoản:</span>
                        <span class="font-bold text-slate-800">{{ BANK_INFO.accountName }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Nội dung:</span>
                        <span class="font-mono font-bold text-slate-800">{{ posTransferContent }}</span>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="space-y-2 pt-1">
                    <button
                        v-if="cart.length > 0"
                        @click="
                            paymentMethod = 'chuyen_khoan';
                            showQrModal = false;
                            handleCheckout();
                        "
                        :disabled="isSubmitting"
                        class="flex w-full items-center justify-center space-x-1.5 rounded-xl bg-emerald-600 py-3 text-xs font-black text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-700"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        <span>Khách Đã Chuyển Xong - Xác Nhận Bán</span>
                    </button>
                    <button
                        @click="showQrModal = false"
                        class="w-full rounded-xl bg-slate-100 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-200"
                    >
                        Đóng Lại
                    </button>
                </div>
            </div>
        </div>

        <!-- HOLD ORDERS MODAL -->
        <div v-if="isHoldModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg space-y-4 rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <Clock class="h-5 w-5 text-amber-500" />
                        <h3 class="text-sm font-black text-slate-900">DANH SÁCH ĐƠN HÀNG TẠM LƯU</h3>
                    </div>
                    <button @click="isHoldModalOpen = false" class="rounded-lg bg-slate-100 p-1.5 text-slate-400 hover:text-slate-600">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div v-if="savedCarts.length === 0" class="py-8 text-center text-xs text-slate-400">
                    Không có đơn hàng nào đang tạm lưu.
                </div>

                <div v-else class="max-h-80 space-y-3 overflow-y-auto">
                    <div
                        v-for="item in savedCarts"
                        :key="item.id"
                        class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50 p-3.5 text-xs"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="rounded-md bg-amber-100 px-2 py-0.5 font-mono text-[10px] font-bold text-amber-800">{{ item.time }}</span>
                                <strong class="text-slate-900">{{ item.customerName || (item.customerPhone ? 'Khách ' + item.customerPhone : 'Khách vãng lai') }}</strong>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                {{ item.cart.length }} mặt hàng: {{ item.cart.map((c) => c.ten_san_pham).slice(0, 2).join(', ') }}{{ item.cart.length > 2 ? '...' : '' }}
                            </p>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button
                                @click="restoreHoldCart(item)"
                                class="rounded-xl bg-emerald-600 px-3 py-1.5 font-bold text-white transition hover:bg-emerald-700"
                            >
                                Mở lại
                            </button>
                            <button
                                @click="removeHoldCart(item.id)"
                                class="rounded-xl bg-red-50 p-1.5 text-red-600 transition hover:bg-red-100"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRINTABLE THERMAL RECEIPT (FOR THERMAL BILL PRINTER 80MM) -->
        <div id="pos-receipt-print" class="hidden font-mono text-black print:block">
            <div class="mx-auto w-[80mm] p-2 leading-relaxed">
                <div class="border-b border-dashed border-black pb-2 text-center">
                    <h2 class="text-base font-black uppercase tracking-wider">{{ currentStore?.ten_cua_hang || 'FRESHFOOD' }}</h2>
                    <p class="text-[11px]">{{ currentStore?.dia_chi }}</p>
                    <p class="text-[11px]">Hotline: {{ currentStore?.so_dien_thoai || '1900 6868' }}</p>
                    <h3 class="mt-2 text-sm font-black">HÓA ĐƠN BÁN HÀNG</h3>
                    <p class="text-[10px]">Mã đơn: #{{ completedOrder?.don_hang?.ma_don_hang || 'POS' }}</p>
                    <p class="text-[10px]">Ngày: {{ new Date().toLocaleString('vi-VN') }}</p>
                </div>

                <div class="border-b border-dashed border-black py-2 text-[11px]">
                    <p v-if="completedOrder?.don_hang?.ten_nguoi_nhan">Khách hàng: {{ completedOrder.don_hang.ten_nguoi_nhan }}</p>
                    <p v-if="completedOrder?.don_hang?.so_dien_thoai_nhan">SĐT: {{ completedOrder.don_hang.so_dien_thoai_nhan }}</p>
                </div>

                <table class="my-2 w-full text-left text-[11px]">
                    <thead>
                        <tr class="border-b border-black">
                            <th class="py-1">Mặt hàng</th>
                            <th class="py-1 text-center">SL</th>
                            <th class="py-1 text-right">Đơn giá</th>
                            <th class="py-1 text-right">T.Tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in completedOrder?.chi_tiets || cart" :key="item.id || item.san_pham_id" class="border-b border-gray-200">
                            <td class="py-1">{{ item.ten_san_pham }}</td>
                            <td class="py-1 text-center">{{ item.so_luong }}</td>
                            <td class="py-1 text-right">{{ formatPrice(item.don_gia) }}</td>
                            <td class="py-1 text-right font-bold">{{ formatPrice(item.thanh_tien || item.don_gia * item.so_luong) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="space-y-1 border-t border-dashed border-black pt-2 text-right text-[11px]">
                    <div class="flex justify-between">
                        <span>Tổng tiền hàng:</span>
                        <span>{{ formatPrice(completedOrder?.don_hang?.tong_tien_hang || subtotal) }}</span>
                    </div>
                    <div v-if="(completedOrder?.don_hang?.tien_giam_gia || pointsDiscount) > 0" class="flex justify-between text-red-600">
                        <span>Giảm giá / Điểm:</span>
                        <span>-{{ formatPrice(completedOrder?.don_hang?.tien_giam_gia || pointsDiscount) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-black pt-1 text-sm font-black">
                        <span>THANH TOÁN:</span>
                        <span>{{ formatPrice(completedOrder?.don_hang?.tong_thanhtoan || finalTotal) }}</span>
                    </div>
                </div>

                <div class="mt-3 border-t border-dashed border-black pt-3 text-center text-[10px]">
                    <p class="font-bold">CẢM ƠN QUÝ KHÁCH VÀ HẸN GẶP LẠI!</p>
                    <p>Thực phẩm tươi sạch mỗi ngày - FreshFood</p>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #pos-receipt-print,
    #pos-receipt-print * {
        visibility: visible !important;
    }
    #pos-receipt-print {
        display: block !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 80mm !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
}
</style>
