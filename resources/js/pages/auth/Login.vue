<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Eye, EyeOff, Lock, LogIn, Mail, Sparkles } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword?: boolean;
}>();

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase
        title="Đăng Nhập Tài Khoản"
        description="Chào mừng bạn quay lại! Đăng nhập để nhận ưu đãi tích điểm và mua thực phẩm tươi sạch mỗi ngày."
    >
        <Head title="Đăng Nhập Tài Khoản - Fresh Food" />

        <!-- FLASH STATUS -->
        <div v-if="status" class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-center text-xs font-bold text-emerald-700">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- EMAIL OR PHONE INPUT -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-bold text-slate-800"> Email hoặc Số điện thoại </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <Mail class="h-4 w-4" />
                    </div>
                    <input
                        id="email"
                        v-model="form.email"
                        type="text"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="username"
                        placeholder="email@example.com hoặc 0912..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-xs text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500"
                        :class="{ 'border-red-400 bg-red-50/50': form.errors.email }"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- PASSWORD INPUT -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-xs font-bold text-slate-800"> Mật khẩu </label>
                    <Link
                        v-if="canResetPassword !== false"
                        :href="route('password.request')"
                        class="text-xs font-bold text-emerald-600 transition hover:text-emerald-700"
                        tabindex="5"
                    >
                        Quên mật khẩu?
                    </Link>
                </div>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <Lock class="h-4 w-4" />
                    </div>
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-11 text-xs text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500"
                        :class="{ 'border-red-400 bg-red-50/50': form.errors.password }"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-600"
                        tabindex="-1"
                    >
                        <EyeOff v-if="showPassword" class="h-4 w-4" />
                        <Eye v-else class="h-4 w-4" />
                    </button>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <!-- REMEMBER ME CHECKBOX -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex cursor-pointer select-none items-center space-x-2 text-xs text-slate-600">
                    <input
                        id="remember"
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        tabindex="3"
                    />
                    <span>Ghi nhớ đăng nhập</span>
                </label>
            </div>

            <!-- SUBMIT BUTTON -->
            <button
                type="submit"
                :disabled="form.processing"
                tabindex="4"
                class="flex w-full cursor-pointer items-center justify-center space-x-2 rounded-2xl bg-emerald-600 px-4 py-3.5 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-emerald-700/20 transition-all hover:bg-emerald-700 active:scale-[0.99] disabled:opacity-50"
            >
                <div v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></div>
                <LogIn v-else class="h-4 w-4" />
                <span>{{ form.processing ? 'Đang xác thực...' : 'Đăng Nhập' }}</span>
            </button>

            <!-- OR DIVIDER -->
            <div class="relative flex items-center justify-center">
                <div class="w-full border-t border-slate-200"></div>
                <span class="bg-white px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">hoặc</span>
            </div>

            <!-- GOOGLE SIGN IN BUTTON -->
            <a
                href="/auth/google"
                class="shadow-xs flex w-full items-center justify-center space-x-2.5 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50 active:scale-[0.99]"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24">
                    <path
                        fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                    />
                    <path
                        fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                    />
                    <path
                        fill="#FBBC05"
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
                    />
                    <path
                        fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
                    />
                </svg>
                <span>Đăng nhập bằng Google</span>
            </a>

            <!-- DIVIDER & SIGNUP CTA -->
            <div class="space-y-3 border-t border-slate-100 pt-4 text-center">
                <p class="text-xs text-slate-500">Chưa có tài khoản thành viên?</p>
                <Link
                    :href="route('register')"
                    class="flex w-full items-center justify-center space-x-1.5 rounded-2xl border border-emerald-200/80 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-800 transition hover:bg-emerald-100"
                    tabindex="6"
                >
                    <Sparkles class="h-3.5 w-3.5 text-emerald-600" />
                    <span>Đăng Ký Tài Khoản Mới (+Tích điểm)</span>
                    <ArrowRight class="ml-1 h-3.5 w-3.5" />
                </Link>
            </div>
        </form>
    </AuthBase>
</template>
