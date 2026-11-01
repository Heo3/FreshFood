import { ref, computed } from 'vue';

export interface CartItem {
    san_pham_id: number;
    ten_san_pham: string;
    duong_dan_seo: string;
    hinh_anh_chinh: string;
    don_vi_tinh: string;
    quy_cach?: string;
    gia_ban: number;
    gia_giam?: number | null;
    so_luong: number;
    cua_hang_id?: number;
}

const STORAGE_KEY = 'freshfood_cart_v1';
const STORE_KEY = 'freshfood_selected_store_id';

const cartItems = ref<CartItem[]>([]);
const selectedStoreId = ref<number>(1);
const toast = ref<{ show: boolean; message: string; type: 'success' | 'error' | 'info' }>({
    show: false,
    message: '',
    type: 'success',
});

// Load from localStorage on initialization
if (typeof window !== 'undefined') {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            cartItems.value = JSON.parse(saved);
        }
        const savedStore = localStorage.getItem(STORE_KEY);
        if (savedStore) {
            selectedStoreId.value = parseInt(savedStore, 10);
        }
    } catch (e) {
        console.error('Lỗi khi đọc giỏ hàng từ localStorage', e);
    }
}

function saveCart() {
    if (typeof window !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cartItems.value));
        localStorage.setItem(STORE_KEY, selectedStoreId.value.toString());
    }
}

function getCsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const metaTag = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    if (metaTag?.content) return metaTag.content;

    const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (cookieMatch) {
        return decodeURIComponent(cookieMatch[1]);
    }
    return '';
}

async function addToCartServer(productId: number, quantity: number, storeId: number, resetCart: boolean = false) {
    if (typeof window === 'undefined') return;
    try {
        const token = getCsrfToken();
        const response = await fetch('/api/cart/add', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-XSRF-TOKEN': token,
            },
            body: JSON.stringify({
                san_pham_id: productId,
                so_luong: quantity,
                store_id: storeId,
                reset_cart: resetCart,
            }),
        });
        if (!response.ok) {
            const data = await response.json();
            if (response.status === 409 && data.code === 'DIFFERENT_STORE') {
                return { success: false, conflict: true, data };
            }
            console.warn('Lỗi từ server khi thêm vào giỏ hàng:', response.status);
            return { success: false, data };
        }
        return { success: true };
    } catch (e) {
        console.error('Lỗi khi lưu giỏ hàng vào CSDL:', e);
        return { success: false };
    }
}

async function updateQuantityServer(productId: number, quantity: number, storeId: number) {
    if (typeof window === 'undefined') return;
    try {
        const token = getCsrfToken();
        await fetch('/api/cart/update', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-XSRF-TOKEN': token,
            },
            body: JSON.stringify({
                san_pham_id: productId,
                so_luong: quantity,
                store_id: storeId,
            }),
        });
    } catch (e) {
        console.error('Lỗi khi cập nhật giỏ hàng trong CSDL:', e);
    }
}

async function removeFromCartServer(productId: number, storeId: number) {
    if (typeof window === 'undefined') return;
    try {
        const token = getCsrfToken();
        await fetch('/api/cart/remove', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-XSRF-TOKEN': token,
            },
            body: JSON.stringify({
                san_pham_id: productId,
                store_id: storeId,
            }),
        });
    } catch (e) {
        console.error('Lỗi khi xóa sản phẩm trong CSDL:', e);
    }
}

async function clearCartServer() {
    if (typeof window === 'undefined') return;
    try {
        const token = getCsrfToken();
        await fetch('/api/cart/clear', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-XSRF-TOKEN': token,
            },
        });
    } catch (e) {
        console.error('Lỗi khi xóa toàn bộ giỏ hàng trong CSDL:', e);
    }
}

async function syncCartToServer() {
    if (typeof window === 'undefined' || cartItems.value.length === 0) return;
    try {
        const token = getCsrfToken();
        await fetch('/api/cart/sync', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-XSRF-TOKEN': token,
            },
            body: JSON.stringify({
                store_id: selectedStoreId.value,
                items: cartItems.value.map((i) => ({
                    san_pham_id: i.san_pham_id,
                    so_luong: i.so_luong,
                })),
            }),
        });
    } catch (e) {
        console.error('Lỗi khi đồng bộ giỏ hàng vào CSDL:', e);
    }
}

function syncWithServer(serverCartItems?: any[]) {
    if (serverCartItems && serverCartItems.length > 0) {
        if (cartItems.value.length === 0) {
            // Không tự động khôi phục nếu dữ liệu server thuộc chi nhánh khác chi nhánh đang chọn
            const serverStoreId = serverCartItems[0].cua_hang_id;
            if (serverStoreId && Number(serverStoreId) !== Number(selectedStoreId.value)) {
                return;
            }
            cartItems.value = serverCartItems.map((item: any) => ({
                san_pham_id: item.san_pham_id,
                ten_san_pham: item.ten_san_pham,
                duong_dan_seo: item.duong_dan_seo,
                hinh_anh_chinh: item.hinh_anh_chinh,
                don_vi_tinh: item.don_vi_tinh,
                quy_cach: item.quy_cach,
                gia_ban: Number(item.gia_ban),
                gia_giam: item.gia_giam !== null && item.gia_giam !== undefined ? Number(item.gia_giam) : null,
                so_luong: Number(item.so_luong),
                cua_hang_id: item.cua_hang_id || selectedStoreId.value,
            }));
            saveCart();
        } else {
            syncCartToServer();
        }
    } else if (cartItems.value.length > 0) {
        syncCartToServer();
    }
}

export function useCart() {
    const totalItems = computed(() => {
        return cartItems.value.reduce((sum, item) => sum + item.so_luong, 0);
    });

    const subtotal = computed(() => {
        return cartItems.value.reduce((sum, item) => {
            const price = item.gia_giam ?? item.gia_ban;
            return sum + (price * item.so_luong);
        }, 0);
    });

    function showToast(message: string, type: 'success' | 'error' | 'info' = 'success') {
        toast.value = { show: true, message, type };
        setTimeout(() => {
            toast.value.show = false;
        }, 3000);
    }

    async function addToCart(product: any, quantity: number = 1, storeId?: number) {
        const targetStoreId = Number(storeId || selectedStoreId.value);
        const qtyToAdd = Math.max(0.1, quantity);

        // Nếu sản phẩm được thêm từ chi nhánh khác với chi nhánh đang có trong giỏ hàng
        if (cartItems.value.length > 0 && selectedStoreId.value !== targetStoreId) {
            const confirmed = window.confirm(
                'Giỏ hàng hiện tại đang có sản phẩm của chi nhánh khác. Thêm sản phẩm này sẽ làm mới (xóa) giỏ hàng cũ để bạn mua tại chi nhánh mới. Bạn có đồng ý không?'
            );
            if (!confirmed) {
                return false;
            }
            cartItems.value = [];
            saveCart();
            await clearCartServer();
            selectedStoreId.value = targetStoreId;
            saveCart();
        } else if (selectedStoreId.value !== targetStoreId) {
            selectedStoreId.value = targetStoreId;
            saveCart();
        }

        const existing = cartItems.value.find((item) => item.san_pham_id === product.id);

        // Ưu tiên lấy giá cấu hình theo chi nhánh (table gia_san_pham_cua_hang)
        const storeGia = product.gia_theo_cua_hang?.find(
            (g: any) => Number(g.cua_hang_id) === Number(targetStoreId)
        );
        const effectiveGiaBan = storeGia ? Number(storeGia.gia_ban) : Number(product.gia_ban);
        const effectiveGiaGiam = storeGia
            ? (storeGia.gia_giam !== null && storeGia.gia_giam !== undefined ? Number(storeGia.gia_giam) : null)
            : (product.gia_giam ? Number(product.gia_giam) : null);

        if (existing) {
            existing.so_luong += qtyToAdd;
            existing.gia_ban = effectiveGiaBan;
            existing.gia_giam = effectiveGiaGiam;
            existing.cua_hang_id = targetStoreId;
        } else {
            cartItems.value.push({
                san_pham_id: product.id,
                ten_san_pham: product.ten_san_pham,
                duong_dan_seo: product.duong_dan_seo,
                hinh_anh_chinh: product.hinh_anh_chinh,
                don_vi_tinh: product.don_vi_tinh,
                quy_cach: product.quy_cach,
                gia_ban: effectiveGiaBan,
                gia_giam: effectiveGiaGiam,
                so_luong: qtyToAdd,
                cua_hang_id: targetStoreId,
            });
        }
        saveCart();
        showToast(`Đã thêm "${product.ten_san_pham}" vào giỏ hàng!`, 'success');

        // Lưu vào CSDL (table gio_hang)
        addToCartServer(product.id, qtyToAdd, targetStoreId);
        return true;
    }

    async function refreshCartPrices(storeId?: number) {
        const targetStoreId = storeId || selectedStoreId.value;
        if (cartItems.value.length === 0) return;

        try {
            const token = (document.querySelector('meta[name="csrf-token"]') as any)?.content || '';
            const response = await fetch('/api/cart/refresh-prices', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    store_id: targetStoreId,
                    product_ids: cartItems.value.map((i) => i.san_pham_id),
                }),
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.prices) {
                    for (const item of cartItems.value) {
                        const priceInfo = data.prices[item.san_pham_id];
                        if (priceInfo) {
                            item.gia_ban = Number(priceInfo.gia_ban);
                            item.gia_giam = priceInfo.gia_giam !== null ? Number(priceInfo.gia_giam) : null;
                        }
                    }
                    saveCart();
                }
            }
        } catch (e) {
            console.error('Lỗi khi cập nhật giá giỏ hàng theo chi nhánh:', e);
        }
    }

    function updateQuantity(productId: number, newQty: number) {
        if (newQty <= 0) {
            removeFromCart(productId);
            return;
        }
        const item = cartItems.value.find((i) => i.san_pham_id === productId);
        if (item) {
            item.so_luong = Number(newQty.toFixed(1));
            saveCart();
            // Cập nhật số lượng vào CSDL
            updateQuantityServer(productId, item.so_luong, selectedStoreId.value);
        }
    }

    function removeFromCart(productId: number) {
        cartItems.value = cartItems.value.filter((i) => i.san_pham_id !== productId);
        saveCart();
        showToast('Đã xóa sản phẩm khỏi giỏ hàng.', 'info');
        // Xóa sản phẩm khỏi CSDL
        removeFromCartServer(productId, selectedStoreId.value);
    }

    async function clearCart() {
        cartItems.value = [];
        saveCart();
        // Xóa sạch giỏ hàng trong CSDL
        await clearCartServer();
    }

    function setStore(storeId: number) {
        selectedStoreId.value = storeId;
        // Tự động chuyển toàn bộ sản phẩm trong giỏ hàng sang chi nhánh mới
        for (const item of cartItems.value) {
            item.cua_hang_id = storeId;
        }
        saveCart();
        refreshCartPrices(storeId);
        // Đồng bộ giỏ hàng theo chi nhánh mới trong CSDL
        syncCartToServer();
    }

    return {
        cartItems,
        selectedStoreId,
        totalItems,
        subtotal,
        toast,
        addToCart,
        refreshCartPrices,
        syncCartToServer,
        updateQuantity,
        removeFromCart,
        clearCart,
        clearCartServer,
        setStore,
        showToast,
        syncWithServer,
        saveCart,
    };
}
