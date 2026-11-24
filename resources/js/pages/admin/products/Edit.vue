<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    DollarSign,
    ImagePlus,
    Info,
    Package,
    Plus,
    Save,
    Sparkles,
    Star,
    Store,
    Tag,
    Trash2,
    UploadCloud,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    product: {
        id: number;
        ten_san_pham: string;
        ma_va_vach: string;
        duong_dan_seo: string;
        danh_muc_id: number;
        gia_ban: number;
        gia_giam: number | null;
        don_vi_tinh: string;
        quy_cach: string | null;
        dieu_kien_bao_quan: string | null;
        han_su_dung_ngay: number | null;
        hinh_anh_chinh: string | null;
        la_tuoi_song: boolean;
        trang_thai: number;
        mo_ta: string | null;
        hinh_anhs?: Array<{
            id: number;
            duong_dan_anh: string;
            thu_tu: number;
            la_anh_chinh: boolean;
        }>;
        gia_theo_cua_hang?: Array<{
            id: number;
            cua_hang_id: number;
            gia_ban: number;
            gia_giam: number | null;
            cua_hang?: {
                id: number;
                ten_cua_hang: string;
            };
        }>;
    };
    categories: Array<{ id: number; ten_danh_muc: string }>;
    stores: Array<{ id: number; ten_cua_hang: string }>;
}>();

const newUploadedFiles = ref<File[]>([]);
const newPreviewUrls = ref<string[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const form = useForm({
    ten_san_pham: props.product.ten_san_pham,
    ma_va_vach: props.product.ma_va_vach,
    duong_dan_seo: props.product.duong_dan_seo,
    danh_muc_id: props.product.danh_muc_id,
    gia_ban: props.product.gia_ban,
    gia_giam: props.product.gia_giam || '',
    don_vi_tinh: props.product.don_vi_tinh,
    quy_cach: props.product.quy_cach || '',
    dieu_kien_bao_quan: props.product.dieu_kien_bao_quan || 'thuong',
    han_su_dung_ngay: props.product.han_su_dung_ngay || '',
    hinh_anh_chinh: props.product.hinh_anh_chinh || '',
    la_tuoi_song: Boolean(props.product.la_tuoi_song),
    trang_thai: props.product.trang_thai,
    mo_ta: props.product.mo_ta || '',
    hinh_anh_phu: [] as File[],
});

const branchForm = useForm({
    cua_hang_id: props.stores[0]?.id || '',
    gia_ban: props.product.gia_ban,
    gia_giam: props.product.gia_giam || '',
});

// Tính toán phần trăm giảm giá theo thời gian thực
const discountPercentage = computed(() => {
    const sellPrice = parseFloat(form.gia_ban as any);
    const promoPrice = parseFloat(form.gia_giam as any);
    if (!sellPrice || !promoPrice || promoPrice >= sellPrice) return 0;
    return Math.round(((sellPrice - promoPrice) / sellPrice) * 100);
});

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        addNewFiles(Array.from(target.files));
    }
}

function handleDrop(event: DragEvent) {
    isDragging.value = false;
    if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
        addNewFiles(Array.from(event.dataTransfer.files));
    }
}

function addNewFiles(files: File[]) {
    const imageFiles = files.filter((f) => f.type.startsWith('image/'));
    imageFiles.forEach((file) => {
        newUploadedFiles.value.push(file);
        newPreviewUrls.value.push(URL.createObjectURL(file));
    });
}

function removeNewFile(index: number) {
    newUploadedFiles.value.splice(index, 1);
    newPreviewUrls.value.splice(index, 1);
}

function deleteExistingImage(imgId: number) {
    if (confirm('Bạn có chắc chắn muốn xóa ảnh này khỏi sản phẩm?')) {
        router.delete(route('admin.products.delete-image', imgId), {
            preserveScroll: true,
        });
    }
}

function makePrimaryImage(imgId: number) {
    router.post(
        route('admin.products.set-primary-image', imgId),
        {},
        {
            preserveScroll: true,
        },
    );
}

const storageOptions = [
    { value: 'thuong', label: 'Thường', desc: 'Nhiệt độ phòng, thoáng mát', icon: '🌡️' },
    { value: 'mat', label: 'Tủ mát', desc: 'Bảo quản mát 2 - 5°C', icon: '❄️' },
    { value: 'dong_lanh', label: 'Đông lạnh', desc: 'Ngăn đông ≤ -18°C', icon: '🧊' },
];

function submit() {
    form.hinh_anh_phu = newUploadedFiles.value;

    // Gửi POST kèm _method: PUT để hỗ trợ upload multipart/form-data trong PHP
    router.post(
        route('admin.products.update', props.product.id),
        {
            _method: 'put',
            ...form.data(),
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                newUploadedFiles.value = [];
                newPreviewUrls.value = [];
            },
        },
    );
}

function submitBranchPrice() {
    branchForm.post(route('admin.products.branch-price', props.product.id), {
        preserveScroll: true,
    });
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
}
</script>

<template>
    <AdminLayout
        :title="`Sửa Sản Phẩm: ${product.ten_san_pham}`"
        :breadcrumbs="[
            { name: 'Admin', href: route('admin.dashboard') },
            { name: 'Sản phẩm', href: route('admin.products.index') },
            { name: 'Chỉnh sửa' },
        ]"
    >
        <div class="space-y-6">
            <!-- TOP BAR -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <Link
                        :href="route('admin.products.index')"
                        class="mb-2 inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 transition hover:text-emerald-600"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        <span>Quay lại danh sách sản phẩm</span>
                    </Link>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-black tracking-tight text-slate-900 sm:text-2xl">
                            {{ product.ten_san_pham }}
                        </h2>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-[11px] font-black"
                            :class="product.trang_thai === 1 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'"
                        >
                            {{ product.trang_thai === 1 ? 'Đang bán' : 'Tạm ẩn' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.products.index')"
                        class="cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                    >
                        Hủy Bỏ
                    </Link>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:opacity-50"
                    >
                        <Save class="h-4 w-4" />
                        <span>{{ form.processing ? 'Đang lưu...' : 'Lưu Thay Đổi' }}</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- CỘT TRÁI (2 COLS): THÔNG TIN CƠ BẢN & BẢNG GIÁ CHI NHÁNH -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- SECTION 1: THÔNG TIN CHI TIẾT SẢN PHẨM -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
                        <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                            <Package class="h-5 w-5 text-emerald-600" />
                            <h3 class="text-sm font-black text-slate-900">Thông Tin Nhận Diện Sản Phẩm</h3>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div>
                                <label class="mb-1 block font-bold text-slate-700"> Tên Sản Phẩm <span class="text-red-500">*</span> </label>
                                <input
                                    v-model="form.ten_san_pham"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm font-semibold text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                />
                                <p v-if="form.errors.ten_san_pham" class="mt-1 text-[11px] font-bold text-red-500">
                                    {{ form.errors.ten_san_pham }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block font-bold text-slate-700">
                                        Danh Mục Hàng Hóa <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.danh_muc_id"
                                        required
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 font-bold text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                    >
                                        <option v-for="c in categories" :key="c.id" :value="c.id">
                                            {{ c.ten_danh_muc }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-1 block font-bold text-slate-700"> Mã Barcode / SKU </label>
                                    <input
                                        v-model="form.ma_va_vach"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 font-mono text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-slate-700"> Đường dẫn SEO (Slug) </label>
                                <div class="flex rounded-xl border border-slate-200 bg-slate-50/60">
                                    <span class="inline-flex select-none items-center px-3 text-slate-400">/san-pham/</span>
                                    <input
                                        v-model="form.duong_dan_seo"
                                        type="text"
                                        class="w-full border-none bg-transparent py-2 pr-3 font-mono text-slate-900 outline-none focus:ring-0"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-slate-700"> Mô Tả Chi Tiết Sản Phẩm </label>
                                <textarea
                                    v-model="form.mo_ta"
                                    rows="4"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 leading-relaxed text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: BẢNG GIÁ & QUY CÁCH -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
                        <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                            <DollarSign class="h-5 w-5 text-emerald-600" />
                            <h3 class="text-sm font-black text-slate-900">Giá Bán Niêm Yết & Quy Cách</h3>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block font-bold text-slate-700">
                                        Giá Bán Chuẩn (VNĐ) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="form.gia_ban"
                                            type="number"
                                            required
                                            min="0"
                                            step="500"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 pl-3.5 pr-10 text-sm font-black text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                        />
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400">đ</span>
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-1 flex items-center justify-between">
                                        <label class="font-bold text-slate-700">Giá Khuyến Mãi (Nếu có)</label>
                                        <span v-if="discountPercentage > 0" class="rounded-md bg-rose-50 px-2 py-0.5 font-bold text-rose-600">
                                            Giảm {{ discountPercentage }}%
                                        </span>
                                    </div>
                                    <div class="relative">
                                        <input
                                            v-model="form.gia_giam"
                                            type="number"
                                            min="0"
                                            step="500"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 pl-3.5 pr-10 text-sm font-black text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                        />
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400">đ</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block font-bold text-slate-700"> Đơn Vị Tính <span class="text-red-500">*</span> </label>
                                    <input
                                        v-model="form.don_vi_tinh"
                                        type="text"
                                        required
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 font-bold text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                </div>

                                <div>
                                    <label class="mb-1 block font-bold text-slate-700"> Quy Cách Đóng Gói </label>
                                    <input
                                        v-model="form.quy_cach"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block font-bold text-slate-700"> Hạn Sử Dụng Tiêu Chuẩn (Số Ngày) </label>
                                    <input
                                        v-model.number="form.han_su_dung_ngay"
                                        type="number"
                                        min="1"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 font-bold text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                </div>

                                <div>
                                    <label class="mb-1 block font-bold text-slate-700"> Điều Kiện Bảo Quản </label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button
                                            v-for="opt in storageOptions"
                                            :key="opt.value"
                                            type="button"
                                            @click="form.dieu_kien_bao_quan = opt.value"
                                            :class="[
                                                'flex flex-col items-center justify-center rounded-xl border p-2 text-center transition cursor-pointer',
                                                form.dieu_kien_bao_quan === opt.value
                                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-900 ring-2 ring-emerald-500/20 shadow-xs'
                                                    : 'border-slate-200 bg-slate-50/60 text-slate-600 hover:border-slate-300 hover:bg-white',
                                            ]"
                                        >
                                            <span class="text-base">{{ opt.icon }}</span>
                                            <span class="mt-1 text-xs font-bold">{{ opt.label }}</span>
                                            <span class="mt-0.5 hidden text-[10px] text-slate-500 sm:block leading-tight">{{ opt.desc }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: BẢNG GIÁ RIÊNG CHO TỪNG CHI NHÁNH -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
                        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <Store class="h-5 w-5 text-emerald-600" />
                                <h3 class="text-sm font-black text-slate-900">Cấu Hình Giá Riêng Theo Chi Nhánh</h3>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-400">Áp dụng khi bán tại cửa hàng cụ thể</span>
                        </div>

                        <!-- BẢNG GIÁ ĐANG ÁP DỤNG -->
                        <div v-if="product.gia_theo_cua_hang && product.gia_theo_cua_hang.length > 0" class="mb-5 overflow-hidden rounded-2xl border border-slate-100">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 font-bold text-slate-600">
                                    <tr>
                                        <th class="px-3.5 py-2.5">Chi Nhánh</th>
                                        <th class="px-3.5 py-2.5">Giá Bán Riêng</th>
                                        <th class="px-3.5 py-2.5">Giá Giảm Riêng</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="g in product.gia_theo_cua_hang" :key="g.id">
                                        <td class="px-3.5 py-2 font-bold text-slate-800">{{ g.cua_hang?.ten_cua_hang }}</td>
                                        <td class="px-3.5 py-2 font-bold text-emerald-700">{{ formatCurrency(g.gia_ban) }}</td>
                                        <td class="px-3.5 py-2 text-rose-600 font-bold">{{ g.gia_giam ? formatCurrency(g.gia_giam) : '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- FORM CẬP NHẬT GIÁ CHI NHÁNH -->
                        <form @submit.prevent="submitBranchPrice" class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 text-xs">
                            <p class="mb-3 font-bold text-slate-800">Thêm hoặc cập nhật giá cho chi nhánh:</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1 block font-bold text-slate-700">Chọn Chi Nhánh</label>
                                    <select
                                        v-model="branchForm.cua_hang_id"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-900 outline-none focus:border-emerald-500"
                                    >
                                        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block font-bold text-slate-700">Giá Bán Riêng (VNĐ)</label>
                                    <input
                                        v-model="branchForm.gia_ban"
                                        type="number"
                                        required
                                        min="0"
                                        step="500"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-bold text-slate-900 outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block font-bold text-slate-700">Giá Giảm Riêng (VNĐ)</label>
                                    <input
                                        v-model="branchForm.gia_giam"
                                        type="number"
                                        min="0"
                                        step="500"
                                        placeholder="Để trống nếu không giảm"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-bold text-slate-900 outline-none focus:border-emerald-500"
                                    />
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="branchForm.processing"
                                    class="cursor-pointer rounded-xl bg-slate-900 px-4 py-2 font-bold text-white transition hover:bg-slate-800 disabled:opacity-50"
                                >
                                    {{ branchForm.processing ? 'Đang lưu...' : 'Lưu Giá Chi Nhánh' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- CỘT PHẢI (1 COL): THƯ VIỆN HÌNH ẢNH & THAO TÁC TRỰC TIẾP -->
                <div class="space-y-6">
                    <!-- SECTION 4: THƯ VIỆN HÌNH ẢNH HIỆN CÓ -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
                        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <ImagePlus class="h-5 w-5 text-emerald-600" />
                                <h3 class="text-sm font-black text-slate-900">Thư Viện Ảnh Sản Phẩm</h3>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-black text-emerald-700">
                                {{ (product.hinh_anhs?.length || 0) + newUploadedFiles.length }} ảnh
                            </span>
                        </div>

                        <!-- ẢNH ĐẠI DIỆN HIỆN TẠI -->
                        <div v-if="product.hinh_anh_chinh" class="mb-4">
                            <p class="mb-1.5 text-[11px] font-bold text-slate-600">Ảnh đại diện đang hiển thị:</p>
                            <div class="aspect-16/9 w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                                <img :src="product.hinh_anh_chinh" class="h-full w-full object-cover" alt="Main" />
                            </div>
                        </div>

                        <!-- DANH SÁCH ẢNH TRONG HỆ THỐNG -->
                        <div v-if="product.hinh_anhs && product.hinh_anhs.length > 0" class="space-y-2">
                            <p class="text-[11px] font-bold text-slate-600">Các ảnh hiện có trong kho:</p>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div
                                    v-for="img in product.hinh_anhs"
                                    :key="img.id"
                                    class="group relative overflow-hidden rounded-2xl border bg-slate-100 transition"
                                    :class="img.la_anh_chinh ? 'border-2 border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200'"
                                >
                                    <div class="aspect-4/3 w-full overflow-hidden bg-slate-100">
                                        <img :src="img.duong_dan_anh" class="h-full w-full object-cover transition group-hover:scale-105" alt="Gallery" />
                                    </div>

                                    <div
                                        v-if="img.la_anh_chinh"
                                        class="absolute left-1.5 top-1.5 flex items-center gap-1 rounded-md bg-emerald-600 px-1.5 py-0.5 text-[9px] font-black text-white shadow-sm"
                                    >
                                        <Star class="h-2.5 w-2.5 fill-current" />
                                        <span>Ảnh chính</span>
                                    </div>

                                    <!-- Nút đặt ảnh chính và xóa ảnh -->
                                    <div class="absolute inset-0 flex items-center justify-center gap-1.5 bg-slate-900/60 opacity-0 transition group-hover:opacity-100">
                                        <button
                                            v-if="!img.la_anh_chinh"
                                            type="button"
                                            @click="makePrimaryImage(img.id)"
                                            class="rounded-lg p-1.5 text-white transition hover:bg-white/20"
                                            title="Đặt làm ảnh đại diện"
                                        >
                                            <Star class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteExistingImage(img.id)"
                                            class="rounded-lg p-1.5 text-white transition hover:bg-red-500"
                                            title="Xóa ảnh này"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHU VỰC TẢI THÊM ẢNH MỚI -->
                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <p class="mb-2 text-[11px] font-bold text-slate-700">Tải thêm ảnh mới:</p>
                            <div
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop"
                                @click="fileInputRef?.click()"
                                class="group relative flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed p-4 text-center transition"
                                :class="isDragging ? 'border-emerald-500 bg-emerald-50' : 'border-slate-300 bg-slate-50 hover:border-emerald-400'"
                            >
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png,image/webp,image/gif"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                                <UploadCloud class="h-6 w-6 text-slate-400 transition group-hover:text-emerald-600" />
                                <p class="mt-1 text-xs font-bold text-slate-700">Bấm để chọn thêm ảnh từ máy</p>
                            </div>

                            <!-- PREVIEW ẢNH MỚI CHỜ LƯU -->
                            <div v-if="newPreviewUrls.length > 0" class="mt-3 space-y-2">
                                <p class="text-[10px] font-bold text-emerald-700">Ảnh mới sẽ được lưu khi bấm "Lưu Thay Đổi":</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <div
                                        v-for="(url, idx) in newPreviewUrls"
                                        :key="idx"
                                        class="group relative aspect-square overflow-hidden rounded-xl border border-emerald-300 bg-emerald-50"
                                    >
                                        <img :src="url" class="h-full w-full object-cover" alt="New" />
                                        <button
                                            type="button"
                                            @click="removeNewFile(idx)"
                                            class="absolute right-1 top-1 rounded-full bg-slate-900/70 p-1 text-white hover:bg-red-500"
                                        >
                                            <Trash2 class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: TRẠNG THÁI & PHÂN LOẠI -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
                        <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                            <Tag class="h-5 w-5 text-emerald-600" />
                            <h3 class="text-sm font-black text-slate-900">Trạng Thái & Phân Loại</h3>
                        </div>

                        <div class="space-y-4 text-xs">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border p-3.5 transition"
                                :class="form.la_tuoi_song ? 'border-emerald-200 bg-emerald-50/50 text-emerald-900' : 'border-slate-200 bg-slate-50'"
                            >
                                <input v-model="form.la_tuoi_song" type="checkbox" class="mt-0.5 h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500" />
                                <div>
                                    <p class="font-bold">Thực Phẩm Tươi Sống (Xuất FEFO)</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">Ưu tiên xuất lô date gần nhất.</p>
                                </div>
                            </label>

                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border p-3.5 transition"
                                :class="form.trang_thai === 1 ? 'border-emerald-200 bg-emerald-50/50 text-emerald-900' : 'border-slate-200 bg-slate-50'"
                            >
                                <input
                                    v-model="form.trang_thai"
                                    type="checkbox"
                                    :true-value="1"
                                    :false-value="0"
                                    class="mt-0.5 h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500"
                                />
                                <div>
                                    <p class="font-bold">Kích Hoạt Kinh Doanh</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">Hiển thị cho khách hàng đặt mua.</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
