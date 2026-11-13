<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    Search,
    Barcode,
    ShoppingCart,
    Trash2,
    Plus,
    Minus,
    CheckCircle2,
    Printer,
    CreditCard,
    Banknote,
    X,
    QrCode,
    Coins,
    UserCheck,
    UserPlus,
    Sparkles,
    Maximize2,
    Copy,
    Check,
} from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

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
    return cart.value.reduce((sum, item) => sum + (item.don_gia * item.so_luong), 0);
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
                'Accept': 'application/json',
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
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- LEFT: PRODUCT CATALOG & BARCODE SCANNER (8 COLS) -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-4">
                <!-- TOP CONTROLS: BARCODE + SEARCH -->
                <div class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row gap-3">
                    <!-- BARCODE SCANNER INPUT -->
                    <div class="relative flex-1">
                        <Barcode class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="barcodeInput"
                            @keydown.enter.prevent="handleBarcodeScan"
                            type="text"
                            placeholder="Quét hoặc nhập mã vạch (Enter)..."
                            class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-none transition"
                        />
                    </div>

                    <!-- NAME SEARCH INPUT -->
                    <div class="relative flex-1">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Tìm tên sản phẩm..."
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-none transition"
                        />
                    </div>
                </div>

                <!-- CATEGORY CHIPS -->
                <div class="flex items-center space-x-2 overflow-x-auto pb-1 text-xs">
                    <button
                        @click="selectedCategory = 'all'"
                        :class="selectedCategory === 'all' ? 'bg-slate-900 text-white font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                        class="px-3.5 py-1.5 rounded-xl whitespace-nowrap transition"
                    >
                        Tất cả ({{ products.length }})
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        @click="selectedCategory = cat"
                        :class="selectedCategory === cat ? 'bg-slate-900 text-white font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                        class="px-3.5 py-1.5 rounded-xl whitespace-nowrap transition"
                    >
                        {{ cat }}
                    </button>
                </div>

                <!-- PRODUCT GRID -->
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5">
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        @click="addToCart(product)"
                        :class="product.ton_kho <= 0 ? 'opacity-50 cursor-not-allowed bg-slate-100' : 'cursor-pointer hover:border-emerald-500 hover:shadow-md active:scale-[0.98] bg-white'"
                        class="p-3 rounded-2xl border border-slate-200/80 transition flex flex-col justify-between group relative overflow-hidden"
                    >
                        <div>
                            <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-slate-50 mb-2">
                                <img :src="product.hinh_anh_chinh" :alt="product.ten_san_pham" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" />
                                <span
                                    :class="product.ton_kho > 0 ? 'bg-emerald-600 text-white' : 'bg-red-500 text-white'"
                                    class="absolute top-1.5 right-1.5 text-[9px] font-black px-1.5 py-0.5 rounded-md uppercase tracking-wider"
                                >
                                    {{ product.ton_kho > 0 ? `Còn ${product.ton_kho} ${product.don_vi_tinh}` : 'Hết hàng' }}
                                </span>
                            </div>
                            <h4 class="text-xs font-bold text-slate-800 line-clamp-2 leading-snug">{{ product.ten_san_pham }}</h4>
                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ product.ma_va_vach }}</p>
                        </div>

                        <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-black text-emerald-600">
                                    {{ formatPrice(product.gia_giam ?? product.gia_ban) }}
                                </span>
                                <span class="text-[10px] text-slate-400">/{{ product.don_vi_tinh }}</span>
                            </div>
                            <div class="w-7 h-7 rounded-lg bg-emerald-50 group-hover:bg-emerald-600 text-emerald-700 group-hover:text-white flex items-center justify-center transition">
                                <Plus class="w-4 h-4" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: POS CART & BILLING PANEL (4 COLS) -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-4">
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col h-[calc(100vh-10rem)] sticky top-28">
                    <!-- CART HEADER -->
                    <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <ShoppingCart class="w-5 h-5 text-emerald-400" />
                            <h3 class="font-bold text-sm">Hóa Đơn Bán Hàng</h3>
                            <span class="text-xs bg-slate-800 text-emerald-400 px-2 py-0.5 rounded-full font-bold">
                                {{ totalQuantity.toFixed(1) }} SP
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                @click="showQrModal = true"
                                type="button"
                                class="text-xs bg-slate-800 hover:bg-slate-700 text-blue-400 hover:text-blue-300 font-bold px-2.5 py-1 rounded-xl flex items-center space-x-1 transition border border-slate-700"
                                title="Mở mã QR quầy thanh toán"
                            >
                                <QrCode class="w-3.5 h-3.5" />
                                <span>QR Quầy</span>
                            </button>
                            <button
                                v-if="cart.length > 0"
                                @click="clearCart"
                                class="text-xs text-red-400 hover:text-red-300 font-medium"
                            >
                                Xóa hết
                            </button>
                        </div>
                    </div>

                    <!-- CART ITEMS LIST -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-3 divide-y divide-slate-100">
                        <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 text-center py-12">
                            <ShoppingCart class="w-12 h-12 text-slate-200 mb-2 stroke-1" />
                            <p class="text-xs font-medium">Chưa có sản phẩm nào trong hóa đơn</p>
                            <p class="text-[11px] text-slate-400 mt-1">Quét mã vạch hoặc bấm chọn sản phẩm để thêm</p>
                        </div>

                        <div v-for="item in cart" :key="item.san_pham_id" class="pt-3 first:pt-0 flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800 truncate">{{ item.ten_san_pham }}</p>
                                <p class="text-[11px] text-slate-500 font-medium">
                                    {{ formatPrice(item.don_gia) }} / {{ item.don_vi_tinh }}
                                </p>
                            </div>

                            <!-- QUANTITY CONTROLLER -->
                            <div class="flex items-center space-x-1.5 bg-slate-100 px-2 py-1 rounded-xl">
                                <button @click="updateQuantity(item, -1)" class="w-5 h-5 rounded hover:bg-white text-slate-700 flex items-center justify-center transition">
                                    <Minus class="w-3 h-3" />
                                </button>
                                <span class="text-xs font-black text-slate-900 w-8 text-center">{{ item.so_luong }}</span>
                                <button @click="updateQuantity(item, 1)" class="w-5 h-5 rounded hover:bg-white text-slate-700 flex items-center justify-center transition">
                                    <Plus class="w-3 h-3" />
                                </button>
                            </div>

                            <div class="text-right min-w-[70px]">
                                <p class="text-xs font-black text-slate-900">{{ formatPrice(item.don_gia * item.so_luong) }}</p>
                            </div>

                            <button @click="removeFromCart(item.san_pham_id)" class="text-slate-300 hover:text-red-500 transition">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- BILLING & PAYMENT FORM -->
                    <div class="p-4 bg-slate-50/80 border-t border-slate-200/80 space-y-3">
                        <!-- CUSTOMER & LOYALTY POINTS SECTION -->
                        <div class="bg-white p-3 rounded-2xl border border-slate-200 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                                    <Coins class="w-4 h-4 text-amber-500" />
                                    <span>Tích & Trừ Điểm Khách Hàng</span>
                                </span>
                                <button
                                    v-if="customerData || customerPhone"
                                    @click="clearCustomer"
                                    type="button"
                                    class="text-[11px] text-slate-400 hover:text-red-500 transition"
                                >
                                    Đổi khách
                                </button>
                            </div>

                            <!-- PHONE SEARCH INPUT -->
                            <div class="flex items-center space-x-2">
                                <input
                                    v-model="customerPhone"
                                    @keydown.enter.prevent="handleCheckCustomer"
                                    type="text"
                                    placeholder="Nhập SĐT khách hàng..."
                                    class="flex-1 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-none"
                                />
                                <button
                                    @click="handleCheckCustomer"
                                    :disabled="isSearchingCustomer || !customerPhone.trim()"
                                    type="button"
                                    class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold disabled:opacity-50 transition"
                                >
                                    {{ isSearchingCustomer ? 'Đang kiểm tra...' : 'Tra cứu' }}
                                </button>
                            </div>

                            <!-- CUSTOMER DETAILS / LOYALTY INFO -->
                            <div v-if="customerData" class="pt-2 border-t border-slate-100 space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center space-x-1.5">
                                        <UserCheck class="w-3.5 h-3.5 text-emerald-600" />
                                        <span class="font-bold text-slate-800">{{ customerData.name }}</span>
                                    </div>
                                    <span class="text-[11px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                        {{ customerData.loyalty_points }} điểm (= {{ formatPrice(customerData.points_value || customerData.loyalty_points * 1000) }})
                                    </span>
                                </div>

                                <!-- IF NEW CUSTOMER, ALLOW EDITING NAME -->
                                <div v-if="customerData.is_new" class="space-y-1">
                                    <input
                                        v-model="customerName"
                                        type="text"
                                        placeholder="Nhập họ tên khách hàng (tùy chọn)..."
                                        class="w-full px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500"
                                    />
                                    <p class="text-[10px] text-slate-400">Khách mới sẽ tự động tích điểm sau khi thanh toán.</p>
                                </div>

                                <!-- REDEEM POINTS CHECKBOX & INPUT -->
                                <div v-if="customerData.loyalty_points > 0" class="bg-amber-50/60 p-2.5 rounded-xl border border-amber-100 space-y-2">
                                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                                        <input
                                            type="checkbox"
                                            v-model="usePoints"
                                            class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                                        />
                                        <span class="text-xs font-bold text-slate-800">Dùng điểm trừ vào đơn này</span>
                                    </label>

                                    <div v-if="usePoints" class="flex items-center space-x-2 pt-1">
                                        <input
                                            v-model.number="pointsToUse"
                                            type="number"
                                            min="0"
                                            :max="Math.min(customerData.loyalty_points, Math.floor(subtotal / 1000))"
                                            class="w-24 px-2 py-1 bg-white border border-amber-300 rounded-lg text-xs font-black text-slate-900 focus:ring-2 focus:ring-amber-500 outline-none"
                                        />
                                        <button
                                            @click="pointsToUse = Math.min(customerData.loyalty_points, Math.floor(subtotal / 1000))"
                                            type="button"
                                            class="text-[11px] font-bold text-amber-700 bg-amber-100 hover:bg-amber-200 px-2 py-1 rounded-md transition"
                                        >
                                            Dùng tối đa
                                        </button>
                                        <span class="text-xs font-bold text-emerald-600 ml-auto">
                                            -{{ formatPrice(pointsDiscount) }}
                                        </span>
                                    </div>
                                    <p v-if="usePoints" class="text-[10px] text-slate-500">
                                        Quy đổi: 1 điểm = 1.000đ. Đã giảm {{ formatPrice(pointsDiscount) }}.
                                    </p>
                                </div>

                                <!-- WILL EARN NOTIFICATION -->
                                <div v-if="pointsWillEarn > 0" class="flex items-center space-x-1.5 text-[11px] text-emerald-700 font-medium bg-emerald-50 px-2 py-1 rounded-lg">
                                    <Sparkles class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" />
                                    <span>Đơn này tích được <strong>+{{ pointsWillEarn }} điểm</strong> (10.000đ = 1 điểm)</span>
                                </div>
                            </div>

                            <p v-else-if="customerSearchMsg" class="text-[11px] text-slate-500 italic">
                                {{ customerSearchMsg }}
                            </p>
                        </div>

                        <!-- PAYMENT METHOD -->
                        <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                            <button
                                @click="paymentMethod = 'tien_mat'"
                                :class="paymentMethod === 'tien_mat' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200'"
                                class="py-2 rounded-xl flex items-center justify-center space-x-1.5 transition"
                            >
                                <Banknote class="w-4 h-4" />
                                <span>Tiền Mặt</span>
                            </button>
                            <button
                                @click="paymentMethod = 'chuyen_khoan'"
                                :class="paymentMethod === 'chuyen_khoan' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200'"
                                class="py-2 rounded-xl flex items-center justify-center space-x-1.5 transition"
                            >
                                <QrCode class="w-4 h-4" />
                                <span>Chuyển Khoản / QR</span>
                            </button>
                        </div>

                        <!-- DYNAMIC QR PAYMENT CARD FOR POS (VPBANK) -->
                        <div
                            v-if="paymentMethod === 'chuyen_khoan'"
                            class="p-3 bg-gradient-to-br from-blue-50/80 via-white to-blue-50/40 rounded-2xl border-2 border-blue-200 space-y-2.5 shadow-sm animate-in fade-in duration-200"
                        >
                            <div class="flex items-center justify-between pb-2 border-b border-blue-100">
                                <div class="flex items-center space-x-1.5">
                                    <div class="w-5 h-5 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black text-[10px]">
                                        QR
                                    </div>
                                    <span class="text-xs font-black text-blue-950 uppercase tracking-tight">Quét Mã Chuyển Khoản / VNPAY</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800">
                                    VPBank
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <!-- QR Thumbnail (Click to enlarge) -->
                                <div
                                    @click="showQrModal = true"
                                    class="relative group cursor-pointer bg-white p-1 rounded-xl border border-blue-200 shadow-sm flex-shrink-0"
                                    title="Bấm để phóng to cho khách quét"
                                >
                                    <img
                                        :src="posQrUrl"
                                        alt="Mã QR POS"
                                        class="w-20 h-20 object-contain rounded-lg group-hover:opacity-90 transition"
                                    />
                                    <div class="absolute inset-0 bg-black/30 rounded-lg opacity-0 group-hover:opacity-100 flex items-center justify-center transition text-white">
                                        <Maximize2 class="w-4 h-4" />
                                    </div>
                                </div>

                                <!-- Bank Account Info -->
                                <div class="flex-1 min-w-0 space-y-1 text-xs">
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-medium leading-none">Chủ tài khoản</p>
                                        <p class="font-bold text-slate-800 truncate text-[11px]">{{ BANK_INFO.accountName }}</p>
                                    </div>

                                    <div>
                                        <p class="text-[10px] text-slate-400 font-medium leading-none">Số tài khoản VPBank</p>
                                        <div class="flex items-center space-x-1 mt-0.5">
                                            <span class="font-mono font-black text-blue-700 text-xs">{{ BANK_INFO.accountNumber }}</span>
                                            <button
                                                @click="copyAccountNumber"
                                                type="button"
                                                class="text-slate-400 hover:text-blue-600 transition p-0.5"
                                                title="Sao chép số tài khoản"
                                            >
                                                <Check v-if="copiedAccount" class="w-3.5 h-3.5 text-emerald-600" />
                                                <Copy v-else class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-[10px] text-slate-400 font-medium leading-none">Nội dung chuyển khoản</p>
                                        <p class="font-mono font-bold text-slate-700 text-[10px] truncate">{{ posTransferContent }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Enlarge Button for Customer -->
                            <button
                                @click="showQrModal = true"
                                type="button"
                                class="w-full py-1.5 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold flex items-center justify-center space-x-1.5 transition shadow-sm"
                            >
                                <Maximize2 class="w-3.5 h-3.5" />
                                <span>Mở To Mã QR Cho Khách Quét</span>
                            </button>
                        </div>

                        <!-- TOTALS -->
                        <div class="space-y-1.5 text-xs pt-1">
                            <div class="flex justify-between items-center text-slate-500">
                                <span>Tổng tiền hàng:</span>
                                <span class="font-bold text-slate-800">{{ formatPrice(subtotal) }}</span>
                            </div>

                            <div v-if="pointsDiscount > 0" class="flex justify-between items-center text-emerald-600 font-bold">
                                <span>Trừ điểm tích lũy ({{ pointsToUse }} điểm):</span>
                                <span>-{{ formatPrice(pointsDiscount) }}</span>
                            </div>

                            <div v-if="paymentMethod === 'tien_mat'" class="flex justify-between items-center text-slate-700">
                                <span class="font-medium">Tiền khách đưa:</span>
                                <input
                                    v-model.number="cashGiven"
                                    type="number"
                                    :placeholder="finalTotal.toString()"
                                    class="w-32 text-right px-2 py-1 bg-white border border-slate-300 rounded-lg text-xs font-black text-slate-900 focus:ring-2 focus:ring-emerald-500 outline-none"
                                />
                            </div>

                            <div v-if="paymentMethod === 'tien_mat' && cashGiven" class="flex justify-between items-center text-slate-700">
                                <span class="font-medium">Tiền thừa trả khách:</span>
                                <span class="font-black text-emerald-700 text-sm">{{ formatPrice(changeToReturn) }}</span>
                            </div>

                            <div class="flex justify-between items-center pt-2 border-t border-slate-200 text-sm">
                                <span class="font-black text-slate-900">KHÁCH PHẢI TRẢ:</span>
                                <span class="font-black text-lg text-emerald-600">{{ formatPrice(finalTotal) }}</span>
                            </div>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <button
                            @click="handleCheckout"
                            :disabled="cart.length === 0 || isSubmitting"
                            :class="cart.length === 0 || isSubmitting ? 'bg-slate-300 cursor-not-allowed text-slate-500' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-700/20 active:scale-[0.99]'"
                            class="w-full py-3 rounded-2xl font-black text-sm tracking-wide transition flex items-center justify-center space-x-2"
                        >
                            <span v-if="isSubmitting">Đang xử lý xuất kho FEFO...</span>
                            <span v-else>XÁC NHẬN THANH TOÁN ({{ formatPrice(finalTotal) }})</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PAYMENT SUCCESS MODAL & INVOICE PREVIEW -->
        <div v-if="showSuccessModal && completedOrder" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center space-x-2">
                        <CheckCircle2 class="w-6 h-6 text-emerald-600" />
                        <h3 class="font-black text-slate-900 text-base">Thanh Toán Thành Công</h3>
                    </div>
                    <button @click="showSuccessModal = false" class="text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- INVOICE CONTENT -->
                <div id="pos-receipt" class="bg-slate-50 p-4 rounded-2xl font-mono text-xs space-y-2 border border-slate-200">
                    <div class="text-center pb-2 border-b border-dashed border-slate-300">
                        <p class="font-black text-sm text-slate-900">FRESH FOOD SUPERMARKET</p>
                        <p class="text-[10px] text-slate-500">{{ currentStore?.dia_chi }}</p>
                        <p class="text-[10px] text-slate-500">Mã Đơn: {{ completedOrder.ma_don_hang }}</p>
                        <p class="text-[10px] text-slate-400">{{ new Date().toLocaleString('vi-VN') }}</p>
                    </div>

                    <div class="space-y-1.5 py-1 divide-y divide-slate-100">
                        <div v-for="item in completedOrder.chi_tiets" :key="item.id" class="flex justify-between pt-1">
                            <div>
                                <p class="font-bold text-slate-800">{{ item.ten_san_pham }}</p>
                                <p class="text-[10px] text-slate-400">{{ item.so_luong }} x {{ formatPrice(item.don_gia) }}</p>
                            </div>
                            <span class="font-bold text-slate-900">{{ formatPrice(item.thanh_tien) }}</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-dashed border-slate-300 space-y-1">
                        <div v-if="completedOrder.tien_giam_gia > 0" class="flex justify-between text-[11px] text-slate-600">
                            <span>Tiền hàng:</span>
                            <span>{{ formatPrice(completedOrder.tong_tien_hang) }}</span>
                        </div>
                        <div v-if="completedOrder.tien_giam_gia > 0" class="flex justify-between text-[11px] text-emerald-600 font-bold">
                            <span>Trừ điểm tích lũy:</span>
                            <span>-{{ formatPrice(completedOrder.tien_giam_gia) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900 text-sm">
                            <span>TỔNG THANH TOÁN:</span>
                            <span class="text-emerald-700">{{ formatPrice(completedOrder.tong_thanhtoan) }}</span>
                        </div>
                        <div class="flex justify-between text-[11px] text-slate-500">
                            <span>Phương thức:</span>
                            <span class="uppercase font-bold" :class="completedOrder.phuong_thuc_thanh_toan === 'tien_mat' ? 'text-slate-700' : 'text-blue-700'">
                                {{ completedOrder.phuong_thuc_thanh_toan === 'tien_mat' ? 'Tiền mặt' : 'Chuyển khoản (VPBank - 0329643444)' }}
                            </span>
                        </div>

                        <!-- CUSTOMER LOYALTY SUMMARY IN RECEIPT -->
                        <div v-if="completedOrder.customer_info" class="pt-2 mt-2 border-t border-dotted border-slate-300 text-[11px] text-slate-600 space-y-0.5">
                            <div class="flex justify-between">
                                <span>Khách hàng:</span>
                                <span class="font-bold text-slate-800">{{ completedOrder.customer_info.name }} ({{ completedOrder.customer_info.phone }})</span>
                            </div>
                            <div v-if="completedOrder.customer_info.points_used > 0" class="flex justify-between text-amber-700">
                                <span>Điểm đã tiêu:</span>
                                <span>-{{ completedOrder.customer_info.points_used }} điểm</span>
                            </div>
                            <div v-if="completedOrder.customer_info.points_earned > 0" class="flex justify-between text-emerald-700 font-bold">
                                <span>Điểm tích lũy mới:</span>
                                <span>+{{ completedOrder.customer_info.points_earned }} điểm</span>
                            </div>
                            <div class="flex justify-between font-bold text-slate-800 pt-0.5">
                                <span>Điểm hiện có:</span>
                                <span>{{ completedOrder.customer_info.current_points }} điểm</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-2 text-[10px] text-slate-400 italic">
                        Cảm ơn quý khách! Hàng tươi sạch mỗi ngày.
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex items-center space-x-2 pt-2">
                    <button
                        @click="printReceipt"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 font-bold text-xs text-slate-700 hover:bg-slate-50 flex items-center justify-center space-x-2 transition"
                    >
                        <Printer class="w-4 h-4" />
                        <span>In Hóa Đơn</span>
                    </button>
                    <button
                        @click="showSuccessModal = false"
                        class="flex-1 py-2.5 rounded-xl bg-emerald-600 font-bold text-xs text-white hover:bg-emerald-700 transition"
                    >
                        Tạo Đơn Tiếp Theo
                    </button>
                </div>
            </div>
        </div>

        <!-- LARGE CUSTOMER-FACING QR CODE MODAL FOR POS COUNTER -->
        <div
            v-if="showQrModal"
            class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 text-center space-y-4 animate-in fade-in zoom-in-95 duration-200">
                <!-- MODAL HEADER -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center space-x-2 text-left">
                        <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-xs">
                            QR
                        </div>
                        <div>
                            <h3 class="font-black text-slate-900 text-sm">QUÉT MÃ THANH TOÁN POS</h3>
                            <p class="text-[11px] text-slate-500">VPBank / Ví VNPAY / Mọi App Ngân Hàng</p>
                        </div>
                    </div>
                    <button
                        @click="showQrModal = false"
                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- AMOUNT DISPLAY -->
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80">
                    <p class="text-[11px] text-slate-500 font-medium uppercase tracking-wider">Số tiền cần thanh toán</p>
                    <p class="text-2xl font-black text-emerald-600 font-mono mt-0.5">
                        {{ formatPrice(finalTotal) }}
                    </p>
                </div>

                <!-- QR IMAGE -->
                <div class="bg-white p-3 rounded-2xl border-2 border-blue-100 shadow-md inline-block">
                    <img
                        :src="posQrLargeUrl"
                        alt="Mã QR Chuyển Khoản POS"
                        class="w-64 h-64 object-contain mx-auto rounded-lg"
                    />
                </div>

                <!-- BANK INFO SUMMARY -->
                <div class="bg-blue-50/60 p-3 rounded-2xl border border-blue-100 text-xs text-left space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Ngân hàng:</span>
                        <span class="font-bold text-slate-800">VPBank</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Số tài khoản:</span>
                        <div class="flex items-center space-x-1">
                            <span class="font-mono font-black text-blue-700">{{ BANK_INFO.accountNumber }}</span>
                            <button
                                @click="copyAccountNumber"
                                type="button"
                                class="text-slate-400 hover:text-blue-600 transition"
                            >
                                <Check v-if="copiedAccount" class="w-3.5 h-3.5 text-emerald-600" />
                                <Copy v-else class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Chủ tài khoản:</span>
                        <span class="font-bold text-slate-800">{{ BANK_INFO.accountName }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Nội dung:</span>
                        <span class="font-mono font-bold text-slate-800">{{ posTransferContent }}</span>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="space-y-2 pt-1">
                    <button
                        v-if="cart.length > 0"
                        @click="paymentMethod = 'chuyen_khoan'; showQrModal = false; handleCheckout();"
                        :disabled="isSubmitting"
                        class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-black text-xs transition shadow-lg shadow-emerald-700/20 flex items-center justify-center space-x-1.5"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        <span>Khách Đã Chuyển Xong - Xác Nhận Bán</span>
                    </button>
                    <button
                        @click="showQrModal = false"
                        class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition"
                    >
                        Đóng Lại
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>

