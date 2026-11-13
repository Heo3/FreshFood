<script setup lang="ts">
import { ref } from 'vue';
import StoreOpsLayout from '@/layouts/StoreOpsLayout.vue';
import {
    Truck,
    Bike,
    UserCheck,
    Clock,
    Plus,
    Edit3,
    CheckCircle2,
    AlertCircle,
    Phone,
    MapPin,
    Shield,
    X,
} from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

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

    router.post(
        route('store.delivery.update-driver', selectedDriver.value.id),
        editForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditModalOpen.value = false;
                toast.success('Đã cập nhật phương tiện và trạng thái thành công!');
            },
            onError: () => {
                toast.error('Có lỗi xảy ra khi cập nhật.');
            },
        }
    );
}

const filteredDrivers = ref<DriverItem[]>([]);
</script>

<template>
    <StoreOpsLayout title="Quản Lý Đội Xe & Nhân Viên Giao Hàng" :stores="stores" :currentStore="currentStore">
        <div class="space-y-6 max-w-6xl mx-auto">
            <!-- TOP BAR -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900 flex items-center space-x-2">
                        <Truck class="w-5 h-5 text-emerald-600" />
                        <span>Đội Xe Vận Chuyển & Nhân Viên Giao Hàng</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Quản lý phân loại <strong>Xe Tải 🚚</strong> (điều chuyển liên chi nhánh & đơn sỉ lớn) và <strong>Xe Máy 🏍️</strong> (giao đơn lẻ 2H).
                    </p>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-600">Lọc phương tiện:</span>
                    <div class="flex bg-slate-100 p-1 rounded-2xl text-xs font-bold">
                        <button
                            type="button"
                            @click="filterVehicle = 'all'"
                            class="px-3 py-1.5 rounded-xl transition"
                            :class="filterVehicle === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                        >
                            Tất cả ({{ drivers.length }})
                        </button>
                        <button
                            type="button"
                            @click="filterVehicle = 'xe_tai'"
                            class="px-3 py-1.5 rounded-xl transition flex items-center space-x-1"
                            :class="filterVehicle === 'xe_tai' ? 'bg-sky-600 text-white shadow-xs' : 'text-slate-500 hover:text-sky-700'"
                        >
                            <Truck class="w-3.5 h-3.5" />
                            <span>Xe Tải</span>
                        </button>
                        <button
                            type="button"
                            @click="filterVehicle = 'xe_may'"
                            class="px-3 py-1.5 rounded-xl transition flex items-center space-x-1"
                            :class="filterVehicle === 'xe_may' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-emerald-700'"
                        >
                            <Bike class="w-3.5 h-3.5" />
                            <span>Xe Máy</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- VEHICLE CARDS GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="driver in drivers.filter(d => filterVehicle === 'all' || d.loai_phuong_tien === filterVehicle)"
                    :key="driver.id"
                    class="bg-white rounded-3xl p-5 border transition-all hover:shadow-md space-y-4"
                    :class="driver.loai_phuong_tien === 'xe_tai' ? 'border-sky-200' : 'border-slate-200/80'"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-lg shadow-sm"
                                :class="driver.loai_phuong_tien === 'xe_tai' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700'"
                            >
                                <Truck v-if="driver.loai_phuong_tien === 'xe_tai'" class="w-6 h-6" />
                                <Bike v-else class="w-6 h-6" />
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm">{{ driver.name }}</h4>
                                <span class="text-[11px] text-slate-400 font-mono">{{ driver.phone || driver.email }}</span>
                            </div>
                        </div>

                        <span
                            class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase"
                            :class="{
                                'bg-emerald-100 text-emerald-800': driver.trang_thai_giao === 'san_sang',
                                'bg-amber-100 text-amber-800 animate-pulse': driver.trang_thai_giao === 'dang_giao',
                                'bg-slate-100 text-slate-600': driver.trang_thai_giao === 'nghi',
                            }"
                        >
                            {{ driver.trang_thai_giao === 'san_sang' ? 'Sẵn sàng' : driver.trang_thai_giao === 'dang_giao' ? 'Đang giao' : 'Nghỉ' }}
                        </span>
                    </div>

                    <!-- SPECS & PLATE -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl space-y-2 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Loại phương tiện:</span>
                            <span class="font-bold" :class="driver.loai_phuong_tien === 'xe_tai' ? 'text-sky-700' : 'text-emerald-700'">
                                {{ driver.loai_phuong_tien === 'xe_tai' ? 'Xe Tải Chở Hàng 🚚' : 'Xe Máy Giao 2H 🏍️' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Biển số xe:</span>
                            <strong class="font-mono text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-200">
                                {{ driver.bien_so_xe || 'Chưa cập nhật' }}
                            </strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Tải trọng tối đa:</span>
                            <strong class="text-slate-800">
                                {{ driver.tai_trong_kg ? `${driver.tai_trong_kg} kg` : (driver.loai_phuong_tien === 'xe_tai' ? '1500 kg' : '50 kg') }}
                            </strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Chi nhánh trực thuộc:</span>
                            <strong class="text-slate-700 line-clamp-1">{{ driver.cua_hang?.ten_cua_hang || 'Toàn hệ thống' }}</strong>
                        </div>
                    </div>

                    <!-- STATUS & EDIT ACTION -->
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-500 font-medium">
                            Đang nhận: <strong class="text-slate-800">{{ driver.don_hang_dang_giao_count || 0 }} đơn</strong>
                        </span>
                        <button
                            type="button"
                            @click="openEdit(driver)"
                            class="inline-flex items-center space-x-1 text-slate-600 hover:text-emerald-700 font-bold bg-slate-100 hover:bg-emerald-50 px-3 py-1.5 rounded-xl transition cursor-pointer"
                        >
                            <Edit3 class="w-3.5 h-3.5" />
                            <span>Chỉnh sửa xe</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- EDIT MODAL -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-black text-slate-900 text-base flex items-center space-x-2">
                        <Truck class="w-5 h-5 text-emerald-600" />
                        <span>Cập Nhật Phương Tiện: {{ selectedDriver?.name }}</span>
                    </h3>
                    <button
                        @click="isEditModalOpen = false"
                        class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Loại phương tiện *</label>
                        <select
                            v-model="editForm.loai_phuong_tien"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:border-emerald-500 font-bold"
                            @change="editForm.tai_trong_kg = editForm.loai_phuong_tien === 'xe_tai' ? 1500 : 50"
                        >
                            <option value="xe_may">Xe Máy 🏍️ (Giao đơn lẻ cho khách hàng)</option>
                            <option value="xe_tai">Xe Tải 🚚 (Chuyển kho số lượng lớn & đơn sỉ cồng kềnh)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Biển số xe *</label>
                        <input
                            v-model="editForm.bien_so_xe"
                            type="text"
                            placeholder="VD: 51D-882.34 hoặc 59-K1 234.56"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:border-emerald-500 font-mono font-bold"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tải trọng tối đa (kg)</label>
                        <input
                            v-model="editForm.tai_trong_kg"
                            type="number"
                            min="10"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Trạng thái làm việc *</label>
                        <select
                            v-model="editForm.trang_thai_giao"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:border-emerald-500 font-bold"
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
                        class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs"
                    >
                        Hủy
                    </button>
                    <button
                        type="button"
                        @click="submitUpdate"
                        class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20"
                    >
                        Lưu Cập Nhật
                    </button>
                </div>
            </div>
        </div>
    </StoreOpsLayout>
</template>

