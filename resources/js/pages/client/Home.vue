<script setup lang="ts">
import ProductCard from '@/components/storefront/ProductCard.vue';
import { useCart } from '@/composables/useCart';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Check, Clock, Copy, Flame, HeartHandshake, ShieldCheck, Sparkles, Tag, Truck } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    stores: Array<any>;
    categories: Array<any>;
    banners?: Array<any>;
    flashSales: Array<any>;
    freshToday: Array<any>;
    meatsAndSeafood: Array<any>;
    fruitsAndVeggies: Array<any>;
    coupons: Array<any>;
}>();

const { showToast } = useCart();

// Dynamic Banner Carousel State
const currentBannerIndex = ref(0);
let bannerTimer: any = null;

const defaultBanner = {
    tieu_de: 'Thực Phẩm Tươi Sống Giao Siêu Tốc 2 Giờ',
    phu_de: 'Rau củ hái sớm từ Đà Lạt, thịt mát công nghệ Oxy-Fresh, cá hồi Nauy phi lê tươi sống mỗi ngày. Kiểm soát hạn dùng chuẩn nguyên tắc FEFO cho bữa ăn trọn vị.',
    hinh_anh: 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1200&q=80',
    duong_dan: '/san-pham',
    nhan_nut: 'Khám phá thực phẩm ngay',
};

const activeBanner = computed(() => {
    if (props.banners && props.banners.length > 0) {
        return props.banners[currentBannerIndex.value] || props.banners[0];
    }
    return defaultBanner;
});

// Flash sale countdown timer simulation
const timeLeft = ref({ hours: 7, minutes: 24, seconds: 50 });
let timer: any = null;

onMounted(() => {
    if (props.banners && props.banners.length > 1) {
        bannerTimer = setInterval(() => {
            currentBannerIndex.value = (currentBannerIndex.value + 1) % props.banners.length;
        }, 5000);
    }
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
    if (bannerTimer) clearInterval(bannerTimer);
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
        <!-- 1. HERO BANNER SECTION (DYNAMIC CAROUSEL) -->
        <section class="mx-auto max-w-7xl px-3 sm:px-4 py-3 sm:py-6">
            <div
                class="relative flex min-h-[320px] sm:min-h-[420px] items-center overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-950 text-white shadow-xl sm:shadow-2xl"
            >
                <!-- Background Banner Image with Gradient Overlay -->
                <div
                    class="absolute inset-0 bg-cover bg-center transition-all duration-700"
                    :style="{
                        backgroundImage: `url(${activeBanner.hinh_anh})`,
                    }"
                >
                    <div class="absolute inset-0 bg-gradient-to-r from-emerald-950/95 via-emerald-900/80 to-transparent"></div>
                </div>

                <!-- Background decorative blur circles -->
                <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-teal-400/20 blur-3xl"></div>

                <div class="relative z-10 max-w-2xl p-5 sm:p-8 md:p-14">
                    <span
                        class="mb-3 sm:mb-4 inline-flex items-center rounded-full border border-emerald-400/30 bg-emerald-500/30 px-2.5 sm:px-3.5 py-1 sm:py-1.5 text-[10px] sm:text-xs font-black text-emerald-300 backdrop-blur-md"
                    >
                        <Sparkles class="mr-1.5 h-3.5 w-3.5 text-amber-300" />
                        100% NÔNG SẢN VIETGAP & HỮU CƠ
                    </span>

                    <h1 class="mb-2 sm:mb-4 text-2xl font-black leading-tight tracking-tight text-white sm:text-5xl">
                        {{ activeBanner.tieu_de }}
                    </h1>

                    <p class="mb-5 sm:mb-8 max-w-xl text-xs sm:text-base leading-relaxed text-emerald-100/90 line-clamp-2 sm:line-clamp-none">
                        {{ activeBanner.phu_de || 'Rau củ hái sớm từ Đà Lạt, thịt mát công nghệ Oxy-Fresh, cá hồi Nauy phi lê tươi sống mỗi ngày.' }}
                    </p>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3">
                        <Link
                            :href="activeBanner.duong_dan || route('products.index')"
                            class="flex items-center justify-center space-x-2 rounded-xl sm:rounded-2xl bg-emerald-500 px-5 sm:px-6 py-3 sm:py-3.5 text-xs sm:text-sm font-black text-gray-950 shadow-lg shadow-emerald-500/30 transition-all hover:bg-emerald-400 active:scale-95"
                        >
                            <span>{{ activeBanner.nhan_nut || 'Khám phá thực phẩm ngay' }}</span>
                            <ArrowRight class="h-4 w-4" />
                        </Link>

                        <Link
                            :href="route('products.index', { is_fresh: true })"
                            class="flex items-center justify-center rounded-xl sm:rounded-2xl border border-white/20 bg-white/10 px-4 sm:px-5 py-2.5 sm:py-3.5 text-xs sm:text-sm font-bold text-white backdrop-blur-md transition-all hover:bg-white/20"
                        >
                            Hàng tươi sáng nay
                        </Link>
                    </div>

                    <!-- BANNER DOTS -->
                    <div v-if="banners && banners.length > 1" class="mt-5 sm:mt-8 flex items-center space-x-2">
                        <button
                            v-for="(b, idx) in banners"
                            :key="b.id"
                            @click="currentBannerIndex = idx"
                            :class="currentBannerIndex === idx ? 'w-8 bg-emerald-400' : 'w-2 bg-white/40 hover:bg-white/70'"
                            class="h-2 cursor-pointer rounded-full transition-all duration-300"
                        ></button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. CATEGORY ICON TILES -->
        <section class="mx-auto max-w-7xl px-3 sm:px-4 py-4 sm:py-6">
            <div class="mb-4 sm:mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg sm:text-xl font-black tracking-tight text-gray-900">Danh Mục Nổi Bật</h2>
                    <p class="mt-0.5 text-[11px] sm:text-xs text-gray-500">Lựa chọn thực phẩm tươi sống theo nhóm nhu cầu</p>
                </div>
                <Link :href="route('products.index')" class="flex items-center text-xs font-bold text-emerald-600 hover:text-emerald-700">
                    Xem tất cả <ArrowRight class="ml-1 h-3.5 w-3.5" />
                </Link>
            </div>

            <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-3 md:grid-cols-6 sm:gap-3.5">
                <Link
                    v-for="cat in categories"
                    :key="cat.id"
                    :href="'/danh-muc/' + cat.duong_dan_seo"
                    class="group flex flex-col items-center rounded-2xl sm:rounded-3xl border border-gray-100 bg-white p-2.5 sm:p-4 text-center shadow-sm transition-all duration-300 hover:border-emerald-200 hover:shadow-xl"
                >
                    <div
                        class="mb-2 sm:mb-3 h-12 w-12 sm:h-16 sm:w-16 overflow-hidden rounded-xl sm:rounded-2xl bg-gray-50 shadow-inner transition-transform duration-300 group-hover:scale-110"
                    >
                        <img
                            :src="cat.hinh_anh || 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300'"
                            :alt="cat.ten_danh_muc"
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <span class="line-clamp-1 text-[11px] sm:text-xs font-bold text-gray-800 transition-colors group-hover:text-emerald-700">
                        {{ cat.ten_danh_muc }}
                    </span>
                    <span class="mt-0.5 text-[9px] sm:text-[10px] text-gray-400"> {{ cat.danh_muc_con?.length || 0 }} danh mục con </span>
                </Link>
            </div>
        </section>

        <!-- 3. FLASH SALE COUNTDOWN SECTION -->
        <section v-if="flashSales?.length > 0" class="mx-auto max-w-7xl px-3 sm:px-4 py-4 sm:py-8">
            <div class="rounded-2xl sm:rounded-3xl border border-red-100 bg-gradient-to-br from-rose-50 via-red-50/50 to-orange-50 p-4 sm:p-6 md:p-8 shadow-sm">
                <!-- Header with Countdown -->
                <div class="mb-4 sm:mb-6 flex flex-col justify-between gap-3 border-b border-red-100 pb-3.5 sm:pb-4 sm:flex-row sm:items-center">
                    <div class="flex items-center space-x-2.5 sm:space-x-3">
                        <div
                            class="flex h-9 w-9 sm:h-10 sm:w-10 animate-bounce items-center justify-center rounded-xl sm:rounded-2xl bg-red-600 text-white shadow-lg shadow-red-600/30"
                        >
                            <Flame class="h-5 w-5 sm:h-6 sm:w-6" />
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-base sm:text-2xl font-black tracking-tight text-red-900">GIÁ SỐC HÔM NAY</h2>
                                <span class="rounded-full bg-red-600 px-2 py-0.5 text-[9px] sm:text-[10px] font-extrabold uppercase tracking-wider text-white">
                                    Flash Sale
                                </span>
                            </div>
                            <p class="text-[11px] sm:text-xs text-red-700/80">Số lượng có hạn, ưu đãi kết thúc sau:</p>
                        </div>
                    </div>

                    <!-- Countdown Digits -->
                    <div class="flex items-center space-x-1.5 sm:space-x-2 text-xs sm:text-sm font-black text-white">
                        <div class="min-w-[32px] sm:min-w-[36px] rounded-lg sm:rounded-xl bg-gray-900 px-2 sm:px-3 py-1 sm:py-1.5 text-center shadow-md">
                            {{ String(timeLeft.hours).padStart(2, '0') }}
                        </div>
                        <span class="text-base sm:text-lg font-extrabold text-gray-900">:</span>
                        <div class="min-w-[32px] sm:min-w-[36px] rounded-lg sm:rounded-xl bg-gray-900 px-2 sm:px-3 py-1 sm:py-1.5 text-center shadow-md">
                            {{ String(timeLeft.minutes).padStart(2, '0') }}
                        </div>
                        <span class="text-base sm:text-lg font-extrabold text-gray-900">:</span>
                        <div class="min-w-[32px] sm:min-w-[36px] animate-pulse rounded-lg sm:rounded-xl bg-red-600 px-2 sm:px-3 py-1 sm:py-1.5 text-center shadow-md">
                            {{ String(timeLeft.seconds).padStart(2, '0') }}
                        </div>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-2 gap-2.5 sm:gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <ProductCard v-for="p in flashSales" :key="p.id" :product="p" />
                </div>
            </div>
        </section>

        <!-- 4. ACTIVE VOUCHERS CAROUSEL -->
        <section v-if="coupons?.length > 0" class="mx-auto max-w-7xl px-3 sm:px-4 py-3 sm:py-4">
            <div class="mb-3 flex items-center space-x-2">
                <Tag class="h-4 w-4 text-emerald-600" />
                <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900">Mã Giảm Giá Hôm Nay</h3>
            </div>

            <div class="grid grid-cols-1 gap-2.5 sm:gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="c in coupons"
                    :key="c.id"
                    class="group flex items-center justify-between space-x-3 rounded-2xl border border-emerald-100 bg-white p-3 sm:p-3.5 shadow-sm transition-colors hover:border-emerald-300"
                >
                    <div class="min-w-0">
                        <span class="rounded-md bg-emerald-50 px-2 py-0.5 font-mono text-[11px] sm:text-xs font-black tracking-wider text-emerald-700">
                            {{ c.ma_khuyen_mai }}
                        </span>
                        <p class="mt-1 line-clamp-1 text-xs font-bold text-gray-800">{{ c.ten_chuong_trinh }}</p>
                        <p class="text-[10px] text-gray-400">Đơn từ {{ new Intl.NumberFormat('vi-VN').format(c.don_hang_toi_thieu) }}đ</p>
                    </div>

                    <button
                        @click="copyCoupon(c.ma_khuyen_mai)"
                        class="shrink-0 rounded-xl bg-gray-50 p-2 text-gray-600 transition-colors hover:bg-emerald-600 hover:text-white"
                        title="Sao chép mã"
                    >
                        <Check v-if="copiedCode === c.ma_khuyen_mai" class="h-4 w-4 text-emerald-600" />
                        <Copy v-else class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </section>

        <!-- 5. SẢN PHẨM TƯƠI MỚI VỀ SÁNG NAY -->
        <section class="mx-auto max-w-7xl px-3 sm:px-4 py-4 sm:py-8">
            <div class="mb-4 sm:mb-5 flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="h-2.5 w-2.5 sm:h-3 sm:w-3 animate-ping rounded-full bg-emerald-500"></span>
                        <h2 class="text-base sm:text-xl font-black tracking-tight text-gray-900">Hàng Tươi Mới Về Sáng Nay</h2>
                    </div>
                    <p class="mt-0.5 text-[11px] sm:text-xs text-gray-500">Thu hoạch tại vườn lúc 04h sáng, nhập kho và kiểm định theo lô chuẩn FEFO</p>
                </div>

                <Link
                    :href="route('products.index', { is_fresh: true })"
                    class="flex items-center text-xs font-bold text-emerald-600 hover:text-emerald-700"
                >
                    Xem tất cả <ArrowRight class="ml-1 h-3.5 w-3.5" />
                </Link>
            </div>

            <div class="grid grid-cols-2 gap-2.5 sm:gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <ProductCard v-for="p in freshToday" :key="p.id" :product="p" />
            </div>
        </section>

        <!-- 6. THỊT & THỦY HẢI SẢN TƯƠI SỐNG -->
        <section class="mx-auto max-w-7xl px-3 sm:px-4 py-4 sm:py-8">
            <div class="mb-4 sm:mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-base sm:text-xl font-black tracking-tight text-gray-900">Thịt Sạch & Thủy Hải Sản Tươi</h2>
                    <p class="mt-0.5 text-[11px] sm:text-xs text-gray-500">Cá hồi Nauy sashimi, tôm sú Cà Mau, thịt bò Úc Black Angus chuẩn kiểm dịch</p>
                </div>
                <Link :href="'/danh-muc/thit-tuoi-trung-sach'" class="flex items-center text-xs font-bold text-emerald-600 hover:text-emerald-700">
                    Xem thêm <ArrowRight class="ml-1 h-3.5 w-3.5" />
                </Link>
            </div>

            <div class="grid grid-cols-2 gap-2.5 sm:gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <ProductCard v-for="p in meatsAndSeafood" :key="p.id" :product="p" />
            </div>
        </section>

        <!-- 7. TRÁI CÂY & RAU CỦ HỮU CƠ -->
        <section class="mx-auto max-w-7xl px-3 sm:px-4 py-4 sm:py-8">
            <div class="mb-4 sm:mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-base sm:text-xl font-black tracking-tight text-gray-900">Rau Củ & Trái Cây Tươi Ngon</h2>
                    <p class="mt-0.5 text-[11px] sm:text-xs text-gray-500">Xoài Cát Hòa Lộc, Táo Envy New Zealand, Bơ 034 dẻo béo</p>
                </div>
                <Link :href="'/danh-muc/trai-cay-tuoi-ngon'" class="flex items-center text-xs font-bold text-emerald-600 hover:text-emerald-700">
                    Xem thêm <ArrowRight class="ml-1 h-3.5 w-3.5" />
                </Link>
            </div>

            <div class="grid grid-cols-2 gap-2.5 sm:gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <ProductCard v-for="p in fruitsAndVeggies" :key="p.id" :product="p" />
            </div>
        </section>

        <!-- 8. QUALITY ASSURANCE BANNER -->
        <section class="mx-auto max-w-7xl px-3 sm:px-4 py-6 sm:py-10">
            <div class="rounded-2xl sm:rounded-3xl border border-emerald-100 bg-emerald-50 p-5 text-center sm:p-12">
                <div
                    class="mx-auto mb-3 sm:mb-4 flex h-12 w-12 sm:h-16 sm:w-16 items-center justify-center rounded-xl sm:rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20"
                >
                    <HeartHandshake class="h-6 w-6 sm:h-8 sm:w-8" />
                </div>
                <h3 class="mb-2 sm:mb-3 text-lg sm:text-2xl font-black text-emerald-950">Cam Kết Chất Lượng Vàng Tại Fresh Food</h3>
                <p class="mx-auto mb-5 sm:mb-6 max-w-2xl text-xs sm:text-sm leading-relaxed text-emerald-800">
                    Mỗi sản phẩm đưa đến tay bạn đều được chọn lọc khắt khe từ nông trại có chứng nhận VietGAP / GlobalGAP. Nếu sản phẩm bị dập nát
                    hay hư hỏng trong quá trình vận chuyển, Fresh Food cam kết đổi mới hoặc hoàn tiền 100% trong vòng 24 giờ.
                </p>
                <div class="flex flex-wrap justify-center gap-2.5 sm:gap-4 text-[11px] sm:text-xs font-bold text-emerald-900">
                    <span class="flex items-center rounded-xl border border-emerald-200 bg-white px-3 sm:px-4 py-1.5 sm:py-2 shadow-sm">
                        <ShieldCheck class="mr-1.5 h-3.5 w-3.5 sm:h-4 sm:w-4 text-emerald-600" /> 100% Nguồn gốc rõ ràng
                    </span>
                    <span class="flex items-center rounded-xl border border-emerald-200 bg-white px-3 sm:px-4 py-1.5 sm:py-2 shadow-sm">
                        <Truck class="mr-1.5 h-3.5 w-3.5 sm:h-4 sm:w-4 text-emerald-600" /> Thùng lạnh bảo quản 0-4°C
                    </span>
                    <span class="flex items-center rounded-xl border border-emerald-200 bg-white px-3 sm:px-4 py-1.5 sm:py-2 shadow-sm">
                        <Clock class="mr-1.5 h-3.5 w-3.5 sm:h-4 sm:w-4 text-emerald-600" /> Giao hàng siêu tốc 2h
                    </span>
                </div>
            </div>
        </section>
    </StoreLayout>
</template>
