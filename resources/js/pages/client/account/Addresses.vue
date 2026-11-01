<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import AccountSidebar from '@/components/storefront/AccountSidebar.vue';
import {
    MapPin,
    Plus,
    Trash2,
    CheckCircle2,
    ChevronRight,
    X,
} from 'lucide-vue-next';

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
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <Link :href="route('account.index')" class="hover:text-emerald-600 transition-colors">Tài khoản</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Sổ địa chỉ</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- SIDEBAR -->
                <div class="lg:col-span-1">
                    <AccountSidebar activeTab="addresses" />
                </div>

                <!-- ADDRESSES MAIN -->
                <div class="lg:col-span-3 space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-black text-gray-900">Sổ Địa Chỉ Nhận Hàng</h1>
                            <p class="text-xs text-gray-500 mt-0.5">Lưu trước địa chỉ để nhận thực phẩm giao siêu tốc 2h nhanh hơn</p>
                        </div>

                        <button
                            @click="isAddModalOpen = true"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl flex items-center space-x-1.5 shadow-sm transition-colors"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Thêm địa chỉ mới</span>
                        </button>
                    </div>

                    <!-- ADDRESS LIST -->
                    <div v-if="addresses.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div
                            v-for="addr in addresses"
                            :key="addr.id"
                            class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm relative space-y-3 text-xs"
                            :class="{ 'ring-2 ring-emerald-500/20 border-emerald-200': addr.la_mac_dinh }"
                        >
                            <div class="flex items-center justify-between">
                                <strong class="font-bold text-sm text-gray-900">{{ addr.ten_nguoi_nhan }}</strong>
                                <span
                                    v-if="addr.la_mac_dinh"
                                    class="text-[10px] font-black bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full"
                                >
                                    Mặc định
                                </span>
                            </div>

                            <p class="text-gray-600 font-medium">SĐT: {{ addr.so_dien_thoai_nhan }}</p>
                            <p class="text-gray-500 leading-relaxed">
                                {{ addr.dia_chi_chi_tiet }}, {{ addr.phuong_xa }}, {{ addr.quan_huyen }}, {{ addr.tinh_thanh }}
                            </p>

                            <div class="pt-2 border-t border-gray-100 flex justify-end">
                                <button
                                    @click="deleteAddress(addr.id)"
                                    class="text-red-500 hover:text-red-700 font-bold text-[11px] flex items-center space-x-1"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                    <span>Xóa</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="bg-white rounded-3xl p-16 text-center border border-gray-100 shadow-sm">
                        <MapPin class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                        <h4 class="text-sm font-bold text-gray-800 mb-1">Chưa có địa chỉ nào</h4>
                        <p class="text-xs text-gray-400 mb-4">Hãy thêm địa chỉ nhận hàng để việc đặt hàng trở nên nhanh chóng.</p>
                        <button
                            @click="isAddModalOpen = true"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2 px-4 rounded-xl"
                        >
                            Thêm địa chỉ ngay
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADD ADDRESS MODAL -->
        <div v-if="isAddModalOpen" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl animate-in fade-in duration-200">
                <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                    <h3 class="font-black text-base text-gray-900 flex items-center">
                        <MapPin class="w-5 h-5 mr-1.5 text-emerald-600" /> Thêm Địa Chỉ Nhận Hàng
                    </h3>
                    <button @click="isAddModalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAddAddress" class="space-y-3 text-xs">
                    <div>
                        <label class="font-bold text-gray-700 block mb-1">Họ tên người nhận *</label>
                        <input
                            v-model="form.ten_nguoi_nhan"
                            type="text"
                            required
                            placeholder="Nguyễn Văn A"
                            class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="font-bold text-gray-700 block mb-1">Số điện thoại *</label>
                        <input
                            v-model="form.so_dien_thoai_nhan"
                            type="tel"
                            required
                            placeholder="0912 345 678"
                            class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="font-bold text-gray-700 block mb-1">Địa chỉ chi tiết (Số nhà, tên đường, tòa nhà) *</label>
                        <input
                            v-model="form.dia_chi_chi_tiet"
                            type="text"
                            required
                            placeholder="128 Hai Bà Trưng, Chung cư Horizon..."
                            class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="font-bold text-gray-700 block mb-1">Phường / Xã *</label>
                            <input
                                v-model="form.phuong_xa"
                                type="text"
                                required
                                placeholder="Phường Đa Kao"
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="font-bold text-gray-700 block mb-1">Quận / Huyện *</label>
                            <input
                                v-model="form.quan_huyen"
                                type="text"
                                required
                                placeholder="Quận 1"
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="font-bold text-gray-700 block mb-1">Tỉnh / Thành phố *</label>
                        <input
                            v-model="form.tinh_thanh"
                            type="text"
                            required
                            placeholder="TP. Hồ Chí Minh"
                            class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none"
                        />
                    </div>

                    <label class="flex items-center space-x-2 pt-2 cursor-pointer">
                        <input type="checkbox" v-model="form.la_mac_dinh" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                        <span class="text-gray-700 font-medium">Đặt làm địa chỉ nhận hàng mặc định</span>
                    </label>

                    <div class="flex space-x-3 pt-3">
                        <button
                            type="button"
                            @click="isAddModalOpen = false"
                            class="flex-1 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold transition-colors"
                        >
                            Hủy bỏ
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition-colors disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Đang lưu...' : 'Lưu địa chỉ' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </StoreLayout>
</template>
