<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    User,
    Phone,
    Mail,
    Lock,
    Eye,
    EyeOff,
    UserPlus,
    Coins,
    ArrowRight,
} from 'lucide-vue-next';

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase
        title="Đăng Ký Thành Viên"
        description="Đăng ký tài khoản để tích điểm đổi thưởng, nhận voucher và theo dõi đơn hàng dễ dàng."
    >
        <Head title="Đăng Ký Thành Viên - Fresh Food" />

        <!-- LOYALTY PRIVILEGE BADGE -->
        <div class="mb-5 p-3 rounded-2xl bg-amber-50/80 border border-amber-200/80 flex items-center space-x-2.5 text-xs text-amber-900">
            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                <Coins class="w-4 h-4" />
            </div>
            <div>
                <p class="font-bold">Đặc quyền thành viên Fresh Food</p>
                <p class="text-[11px] text-amber-700">Tích lũy 1 điểm mỗi 10.000đ thực trả. 1 điểm = 1.000đ trừ trực tiếp vào đơn.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-3.5">
            <!-- FULL NAME -->
            <div class="space-y-1.5">
                <label for="name" class="block text-xs font-bold text-slate-800">
                    Họ và tên
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <User class="w-4 h-4" />
                    </div>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="name"
                        placeholder="Ví dụ: Nguyễn Văn An"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                        :class="{ 'border-red-400 bg-red-50/50': form.errors.name }"
                    />
                </div>
                <InputError :message="form.errors.name" />
            </div>

            <!-- PHONE NUMBER -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="phone" class="block text-xs font-bold text-slate-800">
                        Số điện thoại
                    </label>
                    <span class="text-[10px] text-slate-400">Dùng tích điểm tại quầy POS</span>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Phone class="w-4 h-4" />
                    </div>
                    <input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        required
                        tabindex="2"
                        autocomplete="tel"
                        placeholder="0912 345 678"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-900 font-mono focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                        :class="{ 'border-red-400 bg-red-50/50': form.errors.phone }"
                    />
                </div>
                <InputError :message="form.errors.phone" />
            </div>

            <!-- EMAIL ADDRESS -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-bold text-slate-800">
                    Địa chỉ Email
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Mail class="w-4 h-4" />
                    </div>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        tabindex="3"
                        autocomplete="email"
                        placeholder="nguyenvanan@example.com"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                        :class="{ 'border-red-400 bg-red-50/50': form.errors.email }"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- PASSWORD -->
            <div class="space-y-1.5">
                <label for="password" class="block text-xs font-bold text-slate-800">
                    Mật khẩu
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Lock class="w-4 h-4" />
                    </div>
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        tabindex="4"
                        autocomplete="new-password"
                        placeholder="Tối thiểu 8 ký tự"
                        class="w-full pl-10 pr-11 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                        :class="{ 'border-red-400 bg-red-50/50': form.errors.password }"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition"
                        tabindex="-1"
                    >
                        <EyeOff v-if="showPassword" class="w-4 h-4" />
                        <Eye v-else class="w-4 h-4" />
                    </button>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <!-- CONFIRM PASSWORD -->
            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-xs font-bold text-slate-800">
                    Xác nhận lại mật khẩu
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Lock class="w-4 h-4" />
                    </div>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        required
                        tabindex="5"
                        autocomplete="new-password"
                        placeholder="Nhập lại mật khẩu"
                        class="w-full pl-10 pr-11 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                        :class="{ 'border-red-400 bg-red-50/50': form.errors.password_confirmation }"
                    />
                    <button
                        type="button"
                        @click="showConfirmPassword = !showConfirmPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition"
                        tabindex="-1"
                    >
                        <EyeOff v-if="showConfirmPassword" class="w-4 h-4" />
                        <Eye v-else class="w-4 h-4" />
                    </button>
                </div>
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <!-- SUBMIT BUTTON -->
            <button
                type="submit"
                :disabled="form.processing"
                tabindex="6"
                class="w-full mt-2 py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-emerald-700/20 transition-all flex items-center justify-center space-x-2 disabled:opacity-50 cursor-pointer"
            >
                <div v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                <UserPlus v-else class="w-4 h-4" />
                <span>{{ form.processing ? 'Đang tạo tài khoản...' : 'Tạo Tài Khoản Thành Viên' }}</span>
            </button>

            <!-- OR DIVIDER -->
            <div class="relative flex items-center justify-center my-1">
                <div class="border-t border-slate-200 w-full"></div>
                <span class="bg-white px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">hoặc</span>
            </div>

            <!-- GOOGLE SIGN UP BUTTON -->
            <a
                href="/auth/google"
                class="w-full py-3 px-4 bg-white hover:bg-slate-50 active:scale-[0.99] text-slate-700 font-bold text-xs rounded-2xl border border-slate-200 transition shadow-xs flex items-center justify-center space-x-2.5"
            >
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Đăng ký nhanh với Google</span>
            </a>

            <!-- FOOTER LINK -->
            <div class="pt-4 border-t border-slate-100 text-center space-y-2">
                <p class="text-xs text-slate-500">
                    Đã có tài khoản thành viên Fresh Food?
                </p>
                <Link
                    :href="route('login')"
                    class="inline-flex items-center space-x-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 hover:underline transition"
                    tabindex="7"
                >
                    <span>Đăng nhập ngay</span>
                    <ArrowRight class="w-3.5 h-3.5" />
                </Link>
            </div>
        </form>
    </AuthBase>
</template>
