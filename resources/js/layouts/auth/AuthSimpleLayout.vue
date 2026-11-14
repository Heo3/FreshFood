<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Coins, ShieldCheck, Sparkles, Truck } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col justify-between overflow-hidden bg-gradient-to-br from-emerald-50/70 via-slate-50 to-teal-50/50 font-sans selection:bg-emerald-500 selection:text-white"
    >
        <!-- BACKGROUND DECORATIVE ORBS -->
        <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-teal-200/30 blur-3xl"></div>

        <!-- TOP BAR: BACK TO STORE BUTTON -->
        <header class="z-10 mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <Link
                :href="route('home')"
                class="hover:scale-102 inline-flex items-center space-x-2 rounded-2xl border border-slate-200/80 bg-white/80 px-3.5 py-2 text-xs font-bold text-slate-600 shadow-sm backdrop-blur-sm transition-all hover:bg-white hover:text-emerald-700"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                <span>Quay lại Cửa hàng</span>
            </Link>

            <div class="hidden items-center space-x-2 text-xs font-medium text-slate-500 sm:flex">
                <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
                <span>Hệ thống siêu thị thực phẩm sạch số 1</span>
            </div>
        </header>

        <!-- MAIN CARD CONTAINER -->
        <main class="z-10 mx-auto w-full max-w-md px-4 py-6 sm:max-w-lg">
            <div
                class="rounded-3xl border border-emerald-100 bg-white/95 p-6 shadow-2xl shadow-emerald-950/10 backdrop-blur-xl transition-all sm:p-10"
            >
                <!-- LOGO & BRAND BADGE -->
                <div class="mb-6 flex flex-col items-center text-center">
                    <Link :href="route('home')" class="group flex flex-col items-center">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 p-2.5 shadow-lg shadow-emerald-600/20 transition-transform group-hover:scale-105"
                        >
                            <AppLogoIcon class="h-full w-full object-contain brightness-0 invert filter" />
                        </div>
                        <span
                            class="mt-2.5 inline-flex items-center space-x-1 rounded-full bg-emerald-100/80 px-2.5 py-0.5 text-[11px] font-black uppercase tracking-wider text-emerald-800"
                        >
                            <Sparkles class="h-3 w-3 text-emerald-600" />
                            <span>Fresh Food Supermarket</span>
                        </span>
                    </Link>

                    <h1 v-if="title" class="mt-4 text-xl font-black tracking-tight text-slate-900 sm:text-2xl">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="mt-1.5 max-w-sm text-xs leading-relaxed text-slate-500 sm:text-sm">
                        {{ description }}
                    </p>
                </div>

                <!-- FORM SLOT -->
                <slot />
            </div>
        </main>

        <!-- FOOTER TRUST BADGES -->
        <footer class="z-10 mx-auto w-full max-w-4xl px-4 py-6">
            <div class="grid grid-cols-1 gap-3 text-center sm:grid-cols-3 sm:text-left">
                <div
                    class="flex items-center justify-center space-x-2 rounded-xl border border-slate-200/50 bg-white/60 p-2 text-xs text-slate-600 backdrop-blur-sm sm:justify-start"
                >
                    <ShieldCheck class="h-4 w-4 shrink-0 text-emerald-600" />
                    <span>100% Tươi Sạch Chuẩn VietGAP</span>
                </div>
                <div
                    class="flex items-center justify-center space-x-2 rounded-xl border border-slate-200/50 bg-white/60 p-2 text-xs text-slate-600 backdrop-blur-sm sm:justify-start"
                >
                    <Truck class="h-4 w-4 shrink-0 text-teal-600" />
                    <span>Giao Hỏa Tốc Trong 2 Giờ</span>
                </div>
                <div
                    class="flex items-center justify-center space-x-2 rounded-xl border border-slate-200/50 bg-white/60 p-2 text-xs text-slate-600 backdrop-blur-sm sm:justify-start"
                >
                    <Coins class="h-4 w-4 shrink-0 text-amber-500" />
                    <span>Tích Lũy Điểm 10.000đ = 1 Điểm</span>
                </div>
            </div>

            <p class="mt-4 text-center text-[11px] text-slate-400">
                &copy; {{ new Date().getFullYear() }} Fresh Food. Bản quyền thuộc về Hệ thống siêu thị Fresh Food.
            </p>
        </footer>
    </div>
</template>
