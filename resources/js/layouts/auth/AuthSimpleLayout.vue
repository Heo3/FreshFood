<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ShieldCheck, Truck, Coins, Sparkles } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-emerald-50/70 via-slate-50 to-teal-50/50 flex flex-col justify-between relative overflow-hidden font-sans selection:bg-emerald-500 selection:text-white">
        <!-- BACKGROUND DECORATIVE ORBS -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-emerald-200/40 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-teal-200/30 rounded-full blur-3xl pointer-events-none"></div>

        <!-- TOP BAR: BACK TO STORE BUTTON -->
        <header class="w-full max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between z-10">
            <Link
                :href="route('home')"
                class="inline-flex items-center space-x-2 text-xs font-bold text-slate-600 hover:text-emerald-700 bg-white/80 hover:bg-white backdrop-blur-sm px-3.5 py-2 rounded-2xl border border-slate-200/80 shadow-sm transition-all hover:scale-102"
            >
                <ArrowLeft class="w-3.5 h-3.5" />
                <span>Quay lại Cửa hàng</span>
            </Link>

            <div class="hidden sm:flex items-center space-x-2 text-xs text-slate-500 font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Hệ thống siêu thị thực phẩm sạch số 1</span>
            </div>
        </header>

        <!-- MAIN CARD CONTAINER -->
        <main class="w-full max-w-md sm:max-w-lg mx-auto px-4 py-6 z-10">
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl border border-emerald-100 shadow-2xl shadow-emerald-950/10 p-6 sm:p-10 transition-all">
                <!-- LOGO & BRAND BADGE -->
                <div class="flex flex-col items-center text-center mb-6">
                    <Link :href="route('home')" class="group flex flex-col items-center">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 p-2.5 shadow-lg shadow-emerald-600/20 group-hover:scale-105 transition-transform flex items-center justify-center">
                            <AppLogoIcon class="w-full h-full object-contain filter brightness-0 invert" />
                        </div>
                        <span class="mt-2.5 inline-flex items-center space-x-1 text-[11px] font-black uppercase tracking-wider text-emerald-800 bg-emerald-100/80 px-2.5 py-0.5 rounded-full">
                            <Sparkles class="w-3 h-3 text-emerald-600" />
                            <span>Fresh Food Supermarket</span>
                        </span>
                    </Link>

                    <h1 v-if="title" class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-4">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-xs sm:text-sm text-slate-500 mt-1.5 max-w-sm leading-relaxed">
                        {{ description }}
                    </p>
                </div>

                <!-- FORM SLOT -->
                <slot />
            </div>
        </main>

        <!-- FOOTER TRUST BADGES -->
        <footer class="w-full max-w-4xl mx-auto px-4 py-6 z-10">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-center sm:text-left">
                <div class="flex items-center justify-center sm:justify-start space-x-2 text-xs text-slate-600 bg-white/60 backdrop-blur-sm p-2 rounded-xl border border-slate-200/50">
                    <ShieldCheck class="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>100% Tươi Sạch Chuẩn VietGAP</span>
                </div>
                <div class="flex items-center justify-center sm:justify-start space-x-2 text-xs text-slate-600 bg-white/60 backdrop-blur-sm p-2 rounded-xl border border-slate-200/50">
                    <Truck class="w-4 h-4 text-teal-600 shrink-0" />
                    <span>Giao Hỏa Tốc Trong 2 Giờ</span>
                </div>
                <div class="flex items-center justify-center sm:justify-start space-x-2 text-xs text-slate-600 bg-white/60 backdrop-blur-sm p-2 rounded-xl border border-slate-200/50">
                    <Coins class="w-4 h-4 text-amber-500 shrink-0" />
                    <span>Tích Lũy Điểm 10.000đ = 1 Điểm</span>
                </div>
            </div>

            <p class="text-center text-[11px] text-slate-400 mt-4">
                &copy; {{ new Date().getFullYear() }} Fresh Food. Bản quyền thuộc về Hệ thống siêu thị Fresh Food.
            </p>
        </footer>
    </div>
</template>
