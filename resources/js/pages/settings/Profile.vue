<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import {
    User as UserIcon,
    Phone,
    Mail,
    Lock,
    Eye,
    EyeOff,
    CheckCircle2,
    Shield,
    Coins,
    ChevronRight,
    Save,
    KeyRound,
    AlertTriangle,
} from 'lucide-vue-next';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage();
const authUser = page.props.auth?.user as any;
const isCustomer = authUser?.role === 'khach_hang';

// Profile Info Form
const profileForm = useForm({
    name: authUser?.name || '',
    phone: authUser?.phone || '',
    email: authUser?.email || '',
});

const isProfileSaved = ref(false);

const updateProfile = () => {
    isProfileSaved.value = false;
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            isProfileSaved.value = true;
            setTimeout(() => {
                isProfileSaved.value = false;
            }, 4000);
        },
    });
};

// Password Form
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const isPasswordSaved = ref(false);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    isPasswordSaved.value = false;
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            isPasswordSaved.value = true;
            passwordForm.reset();
            setTimeout(() => {
                isPasswordSaved.value = false;
            }, 4000);
        },
    });
};

function formatPrice(val: number): string {
    return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
}
</script>

<template>
    <Head title="Cài Đặt Thông Tin Cá Nhân - Fresh Food" />

    <!-- FOR CUSTOMERS: USE STOREFRONT LAYOUT -->
    <StoreLayout v-if="isCustomer" title="Cài Đặt Thông Tin Cá Nhân">
        <!-- BREADCRUMBS -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <Link :href="route('account.index')" class="hover:text-emerald-600 transition-colors">Tài khoản</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Cài đặt thông tin</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- SIDEBAR -->
                <div class="lg:col-span-1">
                    <AccountSidebar activeTab="profile" />
                </div>

                <!-- MAIN SETTINGS CONTENT -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- PROFILE HEADER CARD -->
                    <div class="bg-gradient-to-r from-emerald-800 to-teal-800 text-white p-6 rounded-3xl shadow-sm flex flex-col sm:flex-row items-center sm:justify-between gap-4">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm border border-white/30 text-white font-black text-2xl flex items-center justify-center shrink-0">
                                {{ authUser?.name?.charAt(0) || 'U' }}
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h2 class="text-lg font-black">{{ authUser?.name }}</h2>
                                    <span class="bg-emerald-500/30 border border-emerald-300/40 text-[11px] font-bold px-2.5 py-0.5 rounded-full">
                                        Thành viên
                                    </span>
                                </div>
                                <p class="text-xs text-emerald-100/90 mt-0.5 flex items-center space-x-2">
                                    <span>{{ authUser?.email }}</span>
                                    <span v-if="authUser?.phone">• {{ authUser?.phone }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm p-3.5 rounded-2xl border border-white/20 text-center sm:text-right shrink-0">
                            <div class="flex items-center justify-center sm:justify-end space-x-1.5 text-amber-300 font-black text-sm">
                                <Coins class="w-4 h-4 text-amber-300" />
                                <span>{{ authUser?.loyalty_points || 0 }} Điểm tích lũy</span>
                            </div>
                            <p class="text-[10px] text-emerald-100 mt-0.5">
                                Quy đổi tương đương {{ formatPrice((authUser?.loyalty_points || 0) * 1000) }}
                            </p>
                        </div>
                    </div>

                    <!-- FORM 1: PERSONAL INFORMATION -->
                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <div class="flex items-center space-x-2">
                                <UserIcon class="w-5 h-5 text-emerald-600" />
                                <div>
                                    <h3 class="font-black text-sm text-gray-900">Thông Tin Cá Nhân</h3>
                                    <p class="text-[11px] text-gray-400">Cập nhật họ tên, số điện thoại và email của bạn</p>
                                </div>
                            </div>

                            <span v-if="isProfileSaved" class="inline-flex items-center space-x-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-xl">
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                <span>Đã lưu thành công!</span>
                            </span>
                        </div>

                        <form @submit.prevent="updateProfile" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- NAME -->
                                <div class="space-y-1.5">
                                    <label for="name" class="block text-xs font-bold text-gray-700">Họ và tên</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <UserIcon class="w-4 h-4" />
                                        </div>
                                        <input
                                            id="name"
                                            v-model="profileForm.name"
                                            type="text"
                                            required
                                            class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
                                            :class="{ 'border-red-400': profileForm.errors.name }"
                                        />
                                    </div>
                                    <InputError :message="profileForm.errors.name" />
                                </div>

                                <!-- PHONE -->
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <label for="phone" class="block text-xs font-bold text-gray-700">Số điện thoại</label>
                                        <span class="text-[10px] text-emerald-600 font-medium">Tích điểm tại POS</span>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <Phone class="w-4 h-4" />
                                        </div>
                                        <input
                                            id="phone"
                                            v-model="profileForm.phone"
                                            type="tel"
                                            placeholder="0912 345 678"
                                            class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 font-mono focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
                                            :class="{ 'border-red-400': profileForm.errors.phone }"
                                        />
                                    </div>
                                    <InputError :message="profileForm.errors.phone" />
                                </div>
                            </div>

                            <!-- EMAIL -->
                            <div class="space-y-1.5">
                                <label for="email" class="block text-xs font-bold text-gray-700">Địa chỉ Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <Mail class="w-4 h-4" />
                                    </div>
                                    <input
                                        id="email"
                                        v-model="profileForm.email"
                                        type="email"
                                        required
                                        class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
                                        :class="{ 'border-red-400': profileForm.errors.email }"
                                    />
                                </div>
                                <InputError :message="profileForm.errors.email" />
                            </div>

                            <!-- SUBMIT BUTTON -->
                            <div class="flex items-center justify-end pt-2">
                                <button
                                    type="submit"
                                    :disabled="profileForm.processing"
                                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-700/20 transition flex items-center space-x-1.5 disabled:opacity-50 cursor-pointer"
                                >
                                    <Save class="w-3.5 h-3.5" />
                                    <span>{{ profileForm.processing ? 'Đang lưu...' : 'Lưu Thay Đổi' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- FORM 2: CHANGE PASSWORD -->
                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <div class="flex items-center space-x-2">
                                <KeyRound class="w-5 h-5 text-emerald-600" />
                                <div>
                                    <h3 class="font-black text-sm text-gray-900">Bảo Mật & Đổi Mật Khẩu</h3>
                                    <p class="text-[11px] text-gray-400">Đảm bảo mật khẩu của bạn có độ dài tối thiểu 8 ký tự</p>
                                </div>
                            </div>

                            <span v-if="isPasswordSaved" class="inline-flex items-center space-x-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-xl">
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                <span>Đã đổi mật khẩu thành công!</span>
                            </span>
                        </div>

                        <form @submit.prevent="updatePassword" class="space-y-4">
                            <!-- CURRENT PASSWORD -->
                            <div class="space-y-1.5">
                                <label for="current_password" class="block text-xs font-bold text-gray-700">Mật khẩu hiện tại</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <Lock class="w-4 h-4" />
                                    </div>
                                    <input
                                        id="current_password"
                                        v-model="passwordForm.current_password"
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        placeholder="Nhập mật khẩu đang dùng..."
                                        class="w-full pl-9 pr-10 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
                                        :class="{ 'border-red-400': passwordForm.errors.current_password }"
                                    />
                                    <button
                                        type="button"
                                        @click="showCurrentPassword = !showCurrentPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                    >
                                        <EyeOff v-if="showCurrentPassword" class="w-4 h-4" />
                                        <Eye v-else class="w-4 h-4" />
                                    </button>
                                </div>
                                <InputError :message="passwordForm.errors.current_password" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- NEW PASSWORD -->
                                <div class="space-y-1.5">
                                    <label for="new_password" class="block text-xs font-bold text-gray-700">Mật khẩu mới</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <Lock class="w-4 h-4" />
                                        </div>
                                        <input
                                            id="new_password"
                                            v-model="passwordForm.password"
                                            :type="showNewPassword ? 'text' : 'password'"
                                            placeholder="Tối thiểu 8 ký tự..."
                                            class="w-full pl-9 pr-10 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
                                            :class="{ 'border-red-400': passwordForm.errors.password }"
                                        />
                                        <button
                                            type="button"
                                            @click="showNewPassword = !showNewPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                        >
                                            <EyeOff v-if="showNewPassword" class="w-4 h-4" />
                                            <Eye v-else class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <InputError :message="passwordForm.errors.password" />
                                </div>

                                <!-- CONFIRM NEW PASSWORD -->
                                <div class="space-y-1.5">
                                    <label for="password_confirmation" class="block text-xs font-bold text-gray-700">Xác nhận mật khẩu mới</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <Lock class="w-4 h-4" />
                                        </div>
                                        <input
                                            id="password_confirmation"
                                            v-model="passwordForm.password_confirmation"
                                            :type="showConfirmPassword ? 'text' : 'password'"
                                            placeholder="Nhập lại mật khẩu mới..."
                                            class="w-full pl-9 pr-10 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
                                            :class="{ 'border-red-400': passwordForm.errors.password_confirmation }"
                                        />
                                        <button
                                            type="button"
                                            @click="showConfirmPassword = !showConfirmPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                        >
                                            <EyeOff v-if="showConfirmPassword" class="w-4 h-4" />
                                            <Eye v-else class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <InputError :message="passwordForm.errors.password_confirmation" />
                                </div>
                            </div>

                            <!-- SUBMIT BUTTON -->
                            <div class="flex items-center justify-end pt-2">
                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 active:scale-[0.99] text-white font-bold text-xs rounded-xl shadow-md transition flex items-center space-x-1.5 disabled:opacity-50 cursor-pointer"
                                >
                                    <KeyRound class="w-3.5 h-3.5" />
                                    <span>{{ passwordForm.processing ? 'Đang cập nhật...' : 'Cập Nhật Mật Khẩu' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </StoreLayout>

    <!-- FOR STAFF / ADMIN: USE ADMIN PORTAL LAYOUT -->
    <AdminLayout v-else title="Cài Đặt Tài Khoản">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Thông Tin Tài Khoản Quản Trị</h2>
                        <p class="text-xs text-slate-500">Cập nhật hồ sơ và mật khẩu đăng nhập hệ thống</p>
                    </div>
                    <span v-if="isProfileSaved" class="inline-flex items-center space-x-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-xl">
                        <CheckCircle2 class="w-3.5 h-3.5" />
                        <span>Đã lưu thành công!</span>
                    </span>
                </div>

                <form @submit.prevent="updateProfile" class="mt-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Họ và tên</label>
                            <input
                                v-model="profileForm.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                            />
                            <InputError :message="profileForm.errors.name" />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Số điện thoại</label>
                            <input
                                v-model="profileForm.phone"
                                type="tel"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                            />
                            <InputError :message="profileForm.errors.phone" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Email</label>
                        <input
                            v-model="profileForm.email"
                            type="email"
                            required
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                        />
                        <InputError :message="profileForm.errors.email" />
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition"
                        >
                            {{ profileForm.processing ? 'Đang lưu...' : 'Lưu Thay Đổi' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ADMIN PASSWORD CHANGE -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-black text-sm text-slate-900">Đổi Mật Khẩu</h3>
                    <span v-if="isPasswordSaved" class="inline-flex items-center space-x-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-xl">
                        <CheckCircle2 class="w-3.5 h-3.5" />
                        <span>Đã đổi mật khẩu thành công!</span>
                    </span>
                </div>

                <form @submit.prevent="updatePassword" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Mật khẩu hiện tại</label>
                        <input
                            v-model="passwordForm.current_password"
                            type="password"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                        />
                        <InputError :message="passwordForm.errors.current_password" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Mật khẩu mới</label>
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                            />
                            <InputError :message="passwordForm.errors.password" />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Xác nhận mật khẩu mới</label>
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                            />
                            <InputError :message="passwordForm.errors.password_confirmation" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-md transition"
                        >
                            {{ passwordForm.processing ? 'Đang cập nhật...' : 'Cập Nhật Mật Khẩu' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
