<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import { router } from '@inertiajs/vue3';
import { Bike, Edit3, Truck, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface DriverItem {
    id: number;
    name: string;
    phone?: string;
    email: string;
    store_id: number;
    loai_phuong_tien: 'xe_may' | 'xe_tai';
    bien_so_xe?: string;
    tai_trong_kg?: number;
    trang_thai_giao: 'san_sang' | 'dang_giao' | 'nghi';
    cua_hang?: { id: number; ten_cua_hang: string };
    don_hang_dang_giao_count?: number;
}

const props = defineProps<{
    stores: Array<any>;
    currentStore: any;
    drivers: Array<DriverItem>;
}>();

const toast = useToast();
const selectedDriver = ref<DriverItem | null>(null);
const isEditModalOpen = ref(false);
const filterVehicle = ref<'all' | 'xe_tai' | 'xe_may'>('all');

const editForm = ref({
    loai_phuong_tien: 'xe_may' as 'xe_may' | 'xe_tai',
    bien_so_xe: '',
    tai_trong_kg: 50,
    trang_thai_giao: 'san_sang' as 'san_sang' | 'dang_giao' | 'nghi',
});

function openEdit(driver: DriverItem) {
    selectedDriver.value = driver;
    editForm.value = {
        loai_phuong_tien: driver.loai_phuong_tien || 'xe_may',
        bien_so_xe: driver.bien_so_xe || '',
        tai_trong_kg: driver.tai_trong_kg || (driver.loai_phuong_tien === 'xe_tai' ? 1500 : 50),
        trang_thai_giao: driver.trang_thai_giao || 'san_sang',
    };
    isEditModalOpen.value = true;
}

function submitUpdate() {
    if (!selectedDriver.value) return;

    router.post(route('store.delivery.update-driver', selectedDriver.value.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            toast.success('Đã cập nhật phương tiện và trạng thái thành công!');
        },
        onError: () => {
            toast.error('Có lỗi xảy ra khi cập nhật.');
        },
    });
}

const filteredDrivers = ref<DriverItem[]>([]);
</script>

<template>
    <StoreOpsLayout title="Quản Lý Đội Xe & Nhân Viên Giao Hàng" :stores="stores" :currentStore="currentStore">
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- TOP BAR -->
            <div
                class="flex flex-col items-start justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:flex-row sm:items-center"
            >
                <div>
                    <h2 class="flex items-center space-x-2 text-lg font-black text-slate-900">
                        <Truck class="h-5 w-5 text-emerald-600" />
                        <span>Đội Xe Vận Chuyển & Nhân Viên Giao Hàng</span>
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Quản lý phân loại <strong>Xe Tải 🚚</strong> (điều chuyển liên chi nhánh & đơn sỉ lớn) và <strong>Xe Máy 🏍️</strong> (giao đơn
                        lẻ 2H).
                    </p>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-600">Lọc phương tiện:</span>
                    <div class="flex rounded-2xl bg-slate-100 p-1 text-xs font-bold">
                        <button
                            type="button"
                            @click="filterVehicle = 'all'"
                            class="rounded-xl px-3 py-1.5 transition"
                            :class="filterVehicle === 'all' ? 'shadow-xs bg-white text-slate-900' : 'text-slate-500 hover:text-slate-900'"
                        >
                            Tất cả ({{ drivers.length }})
                        </button>
                        <button
                            type="button"
                            @click="filterVehicle = 'xe_tai'"
                            class="flex items-center space-x-1 rounded-xl px-3 py-1.5 transition"
                            :class="filterVehicle === 'xe_tai' ? 'shadow-xs bg-sky-600 text-white' : 'text-slate-500 hover:text-sky-700'"
                        >
                            <Truck class="h-3.5 w-3.5" />
                            <span>Xe Tải</span>
                        </button>
                        <button
                            type="button"
                            @click="filterVehicle = 'xe_may'"
                            class="flex items-center space-x-1 rounded-xl px-3 py-1.5 transition"
                            :class="filterVehicle === 'xe_may' ? 'shadow-xs bg-emerald-600 text-white' : 'text-slate-500 hover:text-emerald-700'"
                        >
                            <Bike class="h-3.5 w-3.5" />
                            <span>Xe Máy</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- VEHICLE CARDS GRID -->
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="driver in drivers.filter((d) => filterVehicle === 'all' || d.loai_phuong_tien === filterVehicle)"
                    :key="driver.id"
                    class="space-y-4 rounded-3xl border bg-white p-5 transition-all hover:shadow-md"
                    :class="driver.loai_phuong_tien === 'xe_tai' ? 'border-sky-200' : 'border-slate-200/80'"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg font-black shadow-sm"
                                :class="driver.loai_phuong_tien === 'xe_tai' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700'"
                            >
                                <Truck v-if="driver.loai_phuong_tien === 'xe_tai'" class="h-6 w-6" />
                                <Bike v-else class="h-6 w-6" />
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">{{ driver.name }}</h4>
                                <span class="font-mono text-[11px] text-slate-400">{{ driver.phone || driver.email }}</span>
                            </div>
                        </div>

                        <span
                            class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase"
                            :class="{
                                'bg-emerald-100 text-emerald-800': driver.trang_thai_giao === 'san_sang',
                                'animate-pulse bg-amber-100 text-amber-800': driver.trang_thai_giao === 'dang_giao',
                                'bg-slate-100 text-slate-600': driver.trang_thai_giao === 'nghi',
                            }"
                        >
                            {{ driver.trang_thai_giao === 'san_sang' ? 'Sẵn sàng' : driver.trang_thai_giao === 'dang_giao' ? 'Đang giao' : 'Nghỉ' }}
                        </span>
                    </div>

                    <!-- SPECS & PLATE -->
                    <div class="space-y-2 rounded-2xl bg-slate-50 p-3.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Loại phương tiện:</span>
                            <span class="font-bold" :class="driver.loai_phuong_tien === 'xe_tai' ? 'text-sky-700' : 'text-emerald-700'">
                                {{ driver.loai_phuong_tien === 'xe_tai' ? 'Xe Tải Chở Hàng 🚚' : 'Xe Máy Giao 2H 🏍️' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Biển số xe:</span>
                            <strong class="rounded border border-slate-200 bg-white px-2 py-0.5 font-mono text-slate-900">
                                {{ driver.bien_so_xe || 'Chưa cập nhật' }}
                            </strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Tải trọng tối đa:</span>
                            <strong class="text-slate-800">
                                {{ driver.tai_trong_kg ? `${driver.tai_trong_kg} kg` : driver.loai_phuong_tien === 'xe_tai' ? '1500 kg' : '50 kg' }}
                            </strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Chi nhánh trực thuộc:</span>
                            <strong class="line-clamp-1 text-slate-700">{{ driver.cua_hang?.ten_cua_hang || 'Toàn hệ thống' }}</strong>
                        </div>
                    </div>

                    <!-- STATUS & EDIT ACTION -->
                    <div class="flex items-center justify-between border-t border-slate-100 pt-2 text-xs">
                        <span class="text-[11px] font-medium text-slate-500">
                            Đang nhận: <strong class="text-slate-800">{{ driver.don_hang_dang_giao_count || 0 }} đơn</strong>
                        </span>
                        <button
                            type="button"
                            @click="openEdit(driver)"
                            class="inline-flex cursor-pointer items-center space-x-1 rounded-xl bg-slate-100 px-3 py-1.5 font-bold text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-700"
                        >
                            <Edit3 class="h-3.5 w-3.5" />
                            <span>Chỉnh sửa xe</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- EDIT MODAL -->
        <div v-if="isEditModalOpen" class="backdrop-blur-xs fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md space-y-5 rounded-3xl bg-white p-6 shadow-2xl duration-200 animate-in fade-in zoom-in">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="flex items-center space-x-2 text-base font-black text-slate-900">
                        <Truck class="h-5 w-5 text-emerald-600" />
                        <span>Cập Nhật Phương Tiện: {{ selectedDriver?.name }}</span>
                    </h3>
                    <button
                        @click="isEditModalOpen = false"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:text-slate-700"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="mb-1 block font-bold text-slate-700">Loại phương tiện *</label>
                        <select
                            v-model="editForm.loai_phuong_tien"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 font-bold focus:border-emerald-500"
                            @change="editForm.tai_trong_kg = editForm.loai_phuong_tien === 'xe_tai' ? 1500 : 50"
                        >
                            <option value="xe_may">Xe Máy 🏍️ (Giao đơn lẻ cho khách hàng)</option>
                            <option value="xe_tai">Xe Tải 🚚 (Chuyển kho số lượng lớn & đơn sỉ cồng kềnh)</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block font-bold text-slate-700">Biển số xe *</label>
                        <input
                            v-model="editForm.bien_so_xe"
                            type="text"
                            placeholder="VD: 51D-882.34 hoặc 59-K1 234.56"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 font-mono font-bold focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block font-bold text-slate-700">Tải trọng tối đa (kg)</label>
                        <input
                            v-model="editForm.tai_trong_kg"
                            type="number"
                            min="10"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block font-bold text-slate-700">Trạng thái làm việc *</label>
                        <select
                            v-model="editForm.trang_thai_giao"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 font-bold focus:border-emerald-500"
                        >
                            <option value="san_sang">Sẵn sàng nhận chuyến</option>
                            <option value="dang_giao">Đang đi giao hàng</option>
                            <option value="nghi">Đang nghỉ ca / bảo dưỡng xe</option>
                        </select>
                    </div>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="flex-1 rounded-xl bg-slate-100 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200"
                    >
                        Hủy
                    </button>
                    <button
                        type="button"
                        @click="submitUpdate"
                        class="flex-1 rounded-xl bg-emerald-600 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-700"
                    >
                        Lưu Cập Nhật
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>
