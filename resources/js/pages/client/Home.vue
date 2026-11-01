<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import ProductCard from '@/components/storefront/ProductCard.vue';
import { useCart } from '@/composables/useCart';
import {
    Sparkles,
    Flame,
    ArrowRight,
    ShieldCheck,
    Truck,
    Clock,
    Tag,
    Copy,
    Check,
    HeartHandshake,
} from 'lucide-vue-next';

defineProps<{
    stores: Array<any>;
    categories: Array<any>;
    flashSales: Array<any>;
    freshToday: Array<any>;
    meatsAndSeafood: Array<any>;
    fruitsAndVeggies: Array<any>;
    coupons: Array<any>;
}>();

const { showToast } = useCart();

// Flash sale countdown timer simulation (ví dụ: đếm ngược đến hết ngày)
const timeLeft = ref({ hours: 7, minutes: 24, seconds: 50 });
let timer: any = null;

onMounted(() => {
    timer = setInterval(() => {
        if (timeLeft.value.seconds > 0) {
            timeLeft.value.seconds--;
        } else if (timeLeft.value.minutes > 0) {
            timeLeft.value.minutes--;
            timeLeft.value.seconds = 59;
        } else if (timeLeft.value.hours > 0) {
            timeLeft.value.hours--;
            timeLeft.value.minutes = 59;
            timeLeft.value.seconds = 59;
        }
    }, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const copiedCode = ref<string | null>(null);

function copyCoupon(code: string) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code);
        copiedCode.value = code;
        showToast(`Đã sao chép mã ${code}! Nhập tại bước thanh toán.`, 'success');
        setTimeout(() => {
            copiedCode.value = null;
        }, 2000);
    }
}
</script>

<template>
    <StoreLayout title="Trang Chủ" :stores="stores">
        <!-- 1. HERO BANNER SECTION -->
        <section class="max-w-7xl mx-auto px-4 py-6">
            <div class="relative rounded-3xl overflow-hidden bg-gradient-to-br from-emerald-900 via-emerald-800 to-teal-900 text-white p-8 md:p-14 shadow-2xl">
                <!-- Background decorative blur circles -->
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-teal-400/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 max-w-2xl">
                    <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-black bg-emerald-500/30 text-emerald-300 border border-emerald-400/30 mb-4 backdrop-blur-md">
                        <Sparkles class="w-3.5 h-3.5 mr-1.5 text-amber-300" />
                        100% NÔNG SẢN VIETGAP & HỮU CƠ TƯƠI SẠCH
                    </span>

                    <h1 class="text-3xl sm:text-5xl font-black tracking-tight leading-tight text-white mb-4">
                        Thực Phẩm Tươi Sống <br class="hidden sm:inline" />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-amber-300">
                            Giao Siêu Tốc 2 Giờ
                        </span>
                    </h1>

                    <p class="text-sm sm:text-base text-emerald-100/90 leading-relaxed mb-8">
                        Rau củ hái sớm từ Đà Lạt, thịt mát công nghệ Oxy-Fresh, cá hồi Nauy phi lê tươi sống mỗi ngày. Kiểm soát hạn dùng chuẩn nguyên tắc FEFO cho bữa ăn trọn vị.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            :href="route('products.index')"
                            class="bg-emerald-500 hover:bg-emerald-400 text-gray-950 font-black px-6 py-3.5 rounded-2xl shadow-lg shadow-emerald-500/30 flex items-center space-x-2 transition-all hover:scale-105 active:scale-95 text-sm"
                        >
                            <span>Khám phá thực phẩm ngay</span>
                            <ArrowRight class="w-4 h-4" />
                        </Link>

                        <Link
                            :href="route('products.index', { is_fresh: true })"
                            class="bg-white/10 hover:bg-white/20 text-white font-bold px-5 py-3.5 rounded-2xl backdrop-blur-md border border-white/20 text-sm transition-all"
                        >
                            Hàng tươi sáng nay
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. CATEGORY ICON TILES -->
        <section class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight">Danh Mục Nổi Bật</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Lựa chọn thực phẩm tươi sống theo nhóm nhu cầu</p>
                </div>
                <Link :href="route('products.index')" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center">
                    Xem tất cả <ArrowRight class="w-3.5 h-3.5 ml-1" />
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3.5">
                <Link
                    v-for="cat in categories"
                    :key="cat.id"
                    :href="'/danh-muc/' + cat.duong_dan_seo"
                    class="group bg-white p-4 rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:border-emerald-200 text-center transition-all duration-300 flex flex-col items-center"
                >
                    <div class="w-16 h-16 rounded-2xl overflow-hidden mb-3 bg-gray-50 group-hover:scale-110 transition-transform duration-300 shadow-inner">
                        <img
                            :src="cat.hinh_anh || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300'"
                            :alt="cat.ten_danh_muc"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    <span class="text-xs font-bold text-gray-800 group-hover:text-emerald-700 transition-colors line-clamp-1">
                        {{ cat.ten_danh_muc }}
                    </span>
                    <span class="text-[10px] text-gray-400 mt-0.5">
                        {{ cat.danh_muc_con?.length || 0 }} danh mục con
                    </span>
                </Link>
            </div>
        </section>

        <!-- 3. FLASH SALE COUNTDOWN SECTION -->
        <section v-if="flashSales?.length > 0" class="max-w-7xl mx-auto px-4 py-8">
            <div class="bg-gradient-to-br from-rose-50 via-red-50/50 to-orange-50 rounded-3xl p-6 md:p-8 border border-red-100 shadow-sm">
                <!-- Header with Countdown -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-red-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-red-600 text-white flex items-center justify-center shadow-lg shadow-red-600/30 animate-bounce">
                            <Flame class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-xl sm:text-2xl font-black text-red-900 tracking-tight">
                                    GIÁ SỐC HÔM NAY
                                </h2>
                                <span class="bg-red-600 text-white font-extrabold text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider">
                                    Flash Sale
                                </span>
                            </div>
                            <p class="text-xs text-red-700/80">Số lượng có hạn, ưu đãi kết thúc sau:</p>
                        </div>
                    </div>

                    <!-- Countdown Digits -->
                    <div class="flex items-center space-x-2 text-white font-black text-sm">
                        <div class="bg-gray-900 px-3 py-1.5 rounded-xl shadow-md min-w-[36px] text-center">
                            {{ String(timeLeft.hours).padStart(2, '0') }}
                        </div>
                        <span class="text-gray-900 font-extrabold text-lg">:</span>
                        <div class="bg-gray-900 px-3 py-1.5 rounded-xl shadow-md min-w-[36px] text-center">
                            {{ String(timeLeft.minutes).padStart(2, '0') }}
                        </div>
                        <span class="text-gray-900 font-extrabold text-lg">:</span>
                        <div class="bg-red-600 px-3 py-1.5 rounded-xl shadow-md min-w-[36px] text-center animate-pulse">
                            {{ String(timeLeft.seconds).padStart(2, '0') }}
                        </div>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <ProductCard
                        v-for="p in flashSales"
                        :key="p.id"
                        :product="p"
                    />
                </div>
            </div>
        </section>

        <!-- 4. ACTIVE VOUCHERS CAROUSEL -->
        <section v-if="coupons?.length > 0" class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center space-x-2 mb-3">
                <Tag class="w-4 h-4 text-emerald-600" />
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">Mã Giảm Giá Hôm Nay</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div
                    v-for="c in coupons"
                    :key="c.id"
                    class="bg-white p-3.5 rounded-2xl border border-emerald-100 shadow-sm flex items-center justify-between space-x-3 group hover:border-emerald-300 transition-colors"
                >
                    <div class="min-w-0">
                        <span class="text-xs font-black text-emerald-700 font-mono tracking-wider bg-emerald-50 px-2 py-0.5 rounded-md">
                            {{ c.ma_khuyen_mai }}
                        </span>
                        <p class="text-xs font-bold text-gray-800 line-clamp-1 mt-1">{{ c.ten_chuong_trinh }}</p>
                        <p class="text-[10px] text-gray-400">Đơn từ {{ new Intl.NumberFormat('vi-VN').format(c.don_hang_toi_thieu) }}đ</p>
                    </div>

                    <button
                        @click="copyCoupon(c.ma_khuyen_mai)"
                        class="p-2 rounded-xl bg-gray-50 hover:bg-emerald-600 hover:text-white text-gray-600 transition-colors shrink-0"
                        title="Sao chép mã"
                    >
                        <Check v-if="copiedCode === c.ma_khuyen_mai" class="w-4 h-4 text-emerald-600" />
                        <Copy v-else class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </section>

        <!-- 5. SẢN PHẨM TƯƠI MỚI VỀ SÁNG NAY -->
        <section class="max-w-7xl mx-auto px-4 py-8">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                        <h2 class="text-xl font-black text-gray-900 tracking-tight">Hàng Tươi Mới Về Sáng Nay</h2>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">Thu hoạch tại vườn lúc 04h sáng, nhập kho và kiểm định theo lô chuẩn FEFO</p>
                </div>

                <Link :href="route('products.index', { is_fresh: true })" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center">
                    Xem tất cả hàng tươi <ArrowRight class="w-3.5 h-3.5 ml-1" />
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <ProductCard
                    v-for="p in freshToday"
                    :key="p.id"
                    :product="p"
                />
            </div>
        </section>

        <!-- 6. THỊT & THỦY HẢI SẢN TƯƠI SỐNG -->
        <section class="max-w-7xl mx-auto px-4 py-8">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight">Thịt Sạch & Thủy Hải Sản Tươi</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Cá hồi Nauy sashimi, tôm sú Cà Mau, thịt bò Úc Black Angus chuẩn kiểm dịch</p>
                </div>
                <Link :href="'/danh-muc/thit-tuoi-trung-sach'" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center">
                    Xem thêm <ArrowRight class="w-3.5 h-3.5 ml-1" />
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <ProductCard
                    v-for="p in meatsAndSeafood"
                    :key="p.id"
                    :product="p"
                />
            </div>
        </section>

        <!-- 7. TRÁI CÂY & RAU CỦ HỮU CƠ -->
        <section class="max-w-7xl mx-auto px-4 py-8">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight">Rau Củ & Trái Cây Tươi Ngon</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Xoài Cát Hòa Lộc, Táo Envy New Zealand, Bơ 034 dẻo béo</p>
                </div>
                <Link :href="'/danh-muc/trai-cay-tuoi-ngon'" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center">
                    Xem thêm <ArrowRight class="w-3.5 h-3.5 ml-1" />
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <ProductCard
                    v-for="p in fruitsAndVeggies"
                    :key="p.id"
                    :product="p"
                />
            </div>
        </section>

        <!-- 8. QUALITY ASSURANCE BANNER -->
        <section class="max-w-7xl mx-auto px-4 py-10">
            <div class="rounded-3xl bg-emerald-50 border border-emerald-100 p-8 sm:p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-emerald-600 text-white flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-600/20">
                    <HeartHandshake class="w-8 h-8" />
                </div>
                <h3 class="text-2xl font-black text-emerald-950 mb-3">Cam Kết Chất Lượng Vàng Tại Fresh Food</h3>
                <p class="text-sm text-emerald-800 max-w-2xl mx-auto leading-relaxed mb-6">
                    Mỗi sản phẩm đưa đến tay bạn đều được chọn lọc khắt khe từ nông trại có chứng nhận VietGAP / GlobalGAP. Nếu sản phẩm bị dập nát hay hư hỏng trong quá trình vận chuyển, Fresh Food cam kết đổi mới hoặc hoàn tiền 100% trong vòng 24 giờ.
                </p>
                <div class="flex flex-wrap justify-center gap-4 text-xs font-bold text-emerald-900">
                    <span class="flex items-center bg-white px-4 py-2 rounded-xl shadow-sm border border-emerald-200">
                        <ShieldCheck class="w-4 h-4 mr-1.5 text-emerald-600" /> 100% Nguồn gốc rõ ràng
                    </span>
                    <span class="flex items-center bg-white px-4 py-2 rounded-xl shadow-sm border border-emerald-200">
                        <Truck class="w-4 h-4 mr-1.5 text-emerald-600" /> Thùng lạnh bảo quản 0-4°C
                    </span>
                    <span class="flex items-center bg-white px-4 py-2 rounded-xl shadow-sm border border-emerald-200">
                        <Clock class="w-4 h-4 mr-1.5 text-emerald-600" /> Giao hàng siêu tốc 2h
                    </span>
                </div>
            </div>
        </section>
    </StoreLayout>
</template>
