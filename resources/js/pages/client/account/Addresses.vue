<script setup lang="ts">
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ChevronRight, MapPin, Plus, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    addresses: Array<any>;
}>();

const isAddModalOpen = ref(false);
const form = ref({
    ten_nguoi_nhan: '',
    so_dien_thoai_nhan: '',
    dia_chi_chi_tiet: '',
    phuong_xa: '',
    quan_huyen: '',
    tinh_thanh: 'TP. Hồ Chí Minh',
    la_mac_dinh: false,
});

const isSubmitting = ref(false);

function submitAddAddress() {
    isSubmitting.value = true;
    router.post(route('account.addresses.store'), form.value, {
        onSuccess: () => {
            isAddModalOpen.value = false;
            form.value = {
                ten_nguoi_nhan: '',
                so_dien_thoai_nhan: '',
                dia_chi_chi_tiet: '',
                phuong_xa: '',
                quan_huyen: '',
                tinh_thanh: 'TP. Hồ Chí Minh',
                la_mac_dinh: false,
            };
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}

function deleteAddress(id: number) {
    if (confirm('Bạn có chắc chắn muốn xóa địa chỉ này không?')) {
        router.delete(route('account.addresses.destroy', { id }));
    }
}
</script>

<template>
    <StoreLayout title="Sổ Địa Chỉ Nhận Hàng">
        <!-- BREADCRUMBS -->
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-7xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <Link :href="route('account.index')" class="transition-colors hover:text-emerald-600">Tài khoản</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">Sổ địa chỉ</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <!-- SIDEBAR -->
                <div class="lg:col-span-1">
                    <AccountSidebar activeTab="addresses" />
                </div>

                <!-- ADDRESSES MAIN -->
                <div class="space-y-6 lg:col-span-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-black text-gray-900">Sổ Địa Chỉ Nhận Hàng</h1>
                            <p class="mt-0.5 text-xs text-gray-500">Lưu trước địa chỉ để nhận thực phẩm giao siêu tốc 2h nhanh hơn</p>
                        </div>

                        <button
                            @click="isAddModalOpen = true"
                            class="flex items-center space-x-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
                        >
                            <Plus class="h-4 w-4" />
                            <span>Thêm địa chỉ mới</span>
                        </button>
                    </div>

                    <!-- ADDRESS LIST -->
                    <div v-if="addresses.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div
                            v-for="addr in addresses"
                            :key="addr.id"
                            class="relative space-y-3 rounded-3xl border border-gray-100 bg-white p-5 text-xs shadow-sm"
                            :class="{ 'border-emerald-200 ring-2 ring-emerald-500/20': addr.la_mac_dinh }"
                        >
                            <div class="flex items-center justify-between">
                                <strong class="text-sm font-bold text-gray-900">{{ addr.ten_nguoi_nhan }}</strong>
                                <span v-if="addr.la_mac_dinh" class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black text-emerald-700">
                                    Mặc định
                                </span>
                            </div>

                            <p class="font-medium text-gray-600">SĐT: {{ addr.so_dien_thoai_nhan }}</p>
                            <p class="leading-relaxed text-gray-500">
                                {{ addr.dia_chi_chi_tiet }}, {{ addr.phuong_xa }}, {{ addr.quan_huyen }}, {{ addr.tinh_thanh }}
                            </p>

                            <div class="flex justify-end border-t border-gray-100 pt-2">
                                <button
                                    @click="deleteAddress(addr.id)"
                                    class="flex items-center space-x-1 text-[11px] font-bold text-red-500 hover:text-red-700"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    <span>Xóa</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-3xl border border-gray-100 bg-white p-16 text-center shadow-sm">
                        <MapPin class="mx-auto mb-3 h-12 w-12 text-gray-300" />
                        <h4 class="mb-1 text-sm font-bold text-gray-800">Chưa có địa chỉ nào</h4>
                        <p class="mb-4 text-xs text-gray-400">Hãy thêm địa chỉ nhận hàng để việc đặt hàng trở nên nhanh chóng.</p>
                        <button
                            @click="isAddModalOpen = true"
                            class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700"
                        >
                            Thêm địa chỉ ngay
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADD ADDRESS MODAL -->
        <div v-if="isAddModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md space-y-4 rounded-3xl bg-white p-6 shadow-2xl duration-200 animate-in fade-in">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="flex items-center text-base font-black text-gray-900">
                        <MapPin class="mr-1.5 h-5 w-5 text-emerald-600" /> Thêm Địa Chỉ Nhận Hàng
                    </h3>
                    <button @click="isAddModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAddAddress" class="space-y-3 text-xs">
                    <div>
                        <label class="mb-1 block font-bold text-gray-700">Họ tên người nhận *</label>
                        <input
                            v-model="form.ten_nguoi_nhan"
                            type="text"
                            required
                            placeholder="Nguyễn Văn A"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 focus:border-emerald-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block font-bold text-gray-700">Số điện thoại *</label>
                        <input
                            v-model="form.so_dien_thoai_nhan"
                            type="tel"
                            required
                            placeholder="0912 345 678"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 focus:border-emerald-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block font-bold text-gray-700">Địa chỉ chi tiết (Số nhà, tên đường, tòa nhà) *</label>
                        <input
                            v-model="form.dia_chi_chi_tiet"
                            type="text"
                            required
                            placeholder="128 Hai Bà Trưng, Chung cư Horizon..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 focus:border-emerald-500 focus:outline-none"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1 block font-bold text-gray-700">Phường / Xã *</label>
                            <input
                                v-model="form.phuong_xa"
                                type="text"
                                required
                                placeholder="Phường Đa Kao"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 focus:border-emerald-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block font-bold text-gray-700">Quận / Huyện *</label>
                            <input
                                v-model="form.quan_huyen"
                                type="text"
                                required
                                placeholder="Quận 1"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 focus:border-emerald-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block font-bold text-gray-700">Tỉnh / Thành phố *</label>
                        <input
                            v-model="form.tinh_thanh"
                            type="text"
                            required
                            placeholder="TP. Hồ Chí Minh"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 focus:border-emerald-500 focus:outline-none"
                        />
                    </div>

                    <label class="flex cursor-pointer items-center space-x-2 pt-2">
                        <input type="checkbox" v-model="form.la_mac_dinh" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                        <span class="font-medium text-gray-700">Đặt làm địa chỉ nhận hàng mặc định</span>
                    </label>

                    <div class="flex space-x-3 pt-3">
                        <button
                            type="button"
                            @click="isAddModalOpen = false"
                            class="flex-1 rounded-xl bg-gray-100 py-2.5 font-bold text-gray-700 transition-colors hover:bg-gray-200"
                        >
                            Hủy bỏ
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="flex-1 rounded-xl bg-emerald-600 py-2.5 font-bold text-white transition-colors hover:bg-emerald-700 disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Đang lưu...' : 'Lưu địa chỉ' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </StoreLayout>
</template>
