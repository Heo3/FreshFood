<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    DollarSign,
    ImagePlus,
    Info,
    Package,
    Save,
    Sparkles,
    Star,
    Tag,
    Trash2,
    UploadCloud,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    categories: Array<{ id: number; ten_danh_muc: string }>;
    stores: Array<{ id: number; ten_cua_hang: string }>;
}>();

interface ImagePreviewItem {
    id: string;
    file?: File;
    url: string;
    name: string;
    size?: string;
}

const previewImages = ref<ImagePreviewItem[]>([]);
const selectedPrimaryIndex = ref<number>(0);
const isDragging = ref(false);
const manualUrlInput = ref('');
const showUrlInput = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

const form = useForm({
    ten_san_pham: '',
    ma_va_vach: '',
    duong_dan_seo: '',
    danh_muc_id: props.categories[0]?.id || '',
    gia_ban: '',
    gia_giam: '',
    don_vi_tinh: 'Kg',
    quy_cach: 'Khay 500g',
    dieu_kien_bao_quan: 'mat',
    han_su_dung_ngay: 3,
    la_tuoi_song: true,
    trang_thai: 1,
    mo_ta: '',
    hinh_anh_chinh: null as File | string | null,
    hinh_anh_phu: [] as Array<File | string>,
    anh_chinh_index: 0,
});

// Tự động tạo slug khi người dùng gõ tên sản phẩm
const autoSlug = ref(true);
watch(
    () => form.ten_san_pham,
    (newVal) => {
        if (!autoSlug.value) return;
        form.duong_dan_seo = newVal
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[đĐ]/g, 'd')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-');
    },
);

// Tính toán phần trăm giảm giá theo thời gian thực
const discountPercentage = computed(() => {
    const sellPrice = parseFloat(form.gia_ban as string);
    const promoPrice = parseFloat(form.gia_giam as string);
    if (!sellPrice || !promoPrice || promoPrice >= sellPrice) return 0;
    return Math.round(((sellPrice - promoPrice) / sellPrice) * 100);
});

// Xử lý khi chọn files từ máy tính
function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        addFiles(Array.from(target.files));
    }
}

// Xử lý kéo thả files
function handleDrop(event: DragEvent) {
    isDragging.value = false;
    if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
        addFiles(Array.from(event.dataTransfer.files));
    }
}

function addFiles(files: File[]) {
    const validImageFiles = files.filter((f) => f.type.startsWith('image/'));
    validImageFiles.forEach((file) => {
        const previewUrl = URL.createObjectURL(file);
        const sizeStr = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        previewImages.value.push({
            id: Math.random().toString(36).substring(2, 9),
            file: file,
            url: previewUrl,
            name: file.name,
            size: sizeStr,
        });
    });
}

// Thêm ảnh từ URL trực tuyến
function addImageFromUrl() {
    const url = manualUrlInput.value.trim();
    if (!url) return;
    previewImages.value.push({
        id: Math.random().toString(36).substring(2, 9),
        url: url,
        name: 'Ảnh từ liên kết',
    });
    manualUrlInput.value = '';
    showUrlInput.value = false;
}

// Đặt làm ảnh chính
function setPrimaryImage(index: number) {
    selectedPrimaryIndex.value = index;
}

// Xóa ảnh khỏi preview
function removeImage(index: number) {
    previewImages.value.splice(index, 1);
    if (selectedPrimaryIndex.value >= previewImages.value.length) {
        selectedPrimaryIndex.value = Math.max(0, previewImages.value.length - 1);
    }
}

// Gợi ý nhanh đơn vị tính
const quickUnits = ['Kg', 'Khay', 'Hộp', 'Túi', 'Gói', 'Bó', 'Quả', 'Chai'];
function selectQuickUnit(unit: string) {
    form.don_vi_tinh = unit;
}

// Điều kiện bảo quản chuẩn enum hệ thống
const storageOptions = [
    { value: 'thuong', label: 'Thường', desc: 'Nhiệt độ phòng, thoáng mát', icon: '🌡️' },
    { value: 'mat', label: 'Tủ mát', desc: 'Bảo quản mát 2 - 5°C', icon: '❄️' },
    { value: 'dong_lanh', label: 'Đông lạnh', desc: 'Ngăn đông ≤ -18°C', icon: '🧊' },
];

function submit() {
    // Chuẩn bị dữ liệu hình ảnh vào form Inertia
    const filesToUpload: File[] = [];
    const urlsToSubmit: string[] = [];

    previewImages.value.forEach((item) => {
        if (item.file) {
            filesToUpload.push(item.file);
        } else if (item.url) {
            urlsToSubmit.push(item.url);
        }
    });

    form.hinh_anh_phu = [...filesToUpload, ...urlsToSubmit];
    form.anh_chinh_index = selectedPrimaryIndex.value;

    form.post(route('admin.products.store'), {
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout
        title="Thêm Sản Phẩm Mới"
        :breadcrumbs="[
            { name: 'Admin', href: route('admin.dashboard') },
            { name: 'Sản phẩm', href: route('admin.products.index') },
            { name: 'Thêm mới' },
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
                        <span>Quay lại danh mục sản phẩm</span>
                    </Link>
                    <h2 class="text-xl font-black tracking-tight text-slate-900 sm:text-2xl">Thêm Sản Phẩm Mới Vào Hệ Thống</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Nhập đầy đủ thông tin mô tả, thiết lập bảng giá niêm yết và tải lên bộ sưu tập hình ảnh chất lượng cao.
                    </p>
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
                        <span>{{ form.processing ? 'Đang lưu sản phẩm...' : 'Hoàn Tất & Lưu Sản Phẩm' }}</span>
                    </button>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- CỘT TRÁI (2 COLS): THÔNG TIN CƠ BẢN & GIÁ & BẢO QUẢN -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- SECTION 1: THÔNG TIN CƠ BẢN -->
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
                                    placeholder="Ví dụ: Rau Cải Bó Xôi Hữu Cơ Đà Lạt"
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
                                    <p v-if="form.errors.danh_muc_id" class="mt-1 text-[11px] font-bold text-red-500">
                                        {{ form.errors.danh_muc_id }}
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-1 block font-bold text-slate-700"> Mã Barcode / SKU </label>
                                    <input
                                        v-model="form.ma_va_vach"
                                        type="text"
                                        placeholder="Để trống hệ thống sẽ tự sinh (VD: SP000123)"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 font-mono text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <p v-if="form.errors.ma_va_vach" class="mt-1 text-[11px] font-bold text-red-500">
                                        {{ form.errors.ma_va_vach }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label class="font-bold text-slate-700"> Đường dẫn SEO (Slug) </label>
                                    <button
                                        type="button"
                                        @click="autoSlug = !autoSlug"
                                        class="text-[11px] font-semibold text-emerald-600 hover:underline"
                                    >
                                        {{ autoSlug ? 'Tự động tạo từ tên' : 'Tự nhập thủ công' }}
                                    </button>
                                </div>
                                <div class="flex rounded-xl border border-slate-200 bg-slate-50/60">
                                    <span class="inline-flex select-none items-center px-3 text-slate-400">/san-pham/</span>
                                    <input
                                        v-model="form.duong_dan_seo"
                                        type="text"
                                        placeholder="rau-cai-bo-xoi-huu-co"
                                        class="w-full border-none bg-transparent py-2 pr-3 font-mono text-slate-900 outline-none focus:ring-0"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block font-bold text-slate-700"> Mô Tả Sản Phẩm & Chứng Nhận Nông Sản </label>
                                <textarea
                                    v-model="form.mo_ta"
                                    rows="4"
                                    placeholder="Mô tả nguồn gốc xuất xứ, chứng chỉ VietGAP/GlobalGAP/Organic, hướng dẫn sử dụng và hàm lượng dinh dưỡng..."
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 leading-relaxed text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: BẢNG GIÁ & QUY CÁCH ĐÓNG GÓI -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
                        <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                            <DollarSign class="h-5 w-5 text-emerald-600" />
                            <h3 class="text-sm font-black text-slate-900">Giá Bán & Đơn Vị Tính</h3>
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
                                            placeholder="VD: 35000"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 pl-3.5 pr-10 text-sm font-black text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                        />
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400">đ</span>
                                    </div>
                                    <p v-if="form.errors.gia_ban" class="mt-1 text-[11px] font-bold text-red-500">{{ form.errors.gia_ban }}</p>
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
                                            placeholder="VD: 29000 (phải nhỏ hơn giá chuẩn)"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2.5 pl-3.5 pr-10 text-sm font-black text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                        />
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400">đ</span>
                                    </div>
                                    <p v-if="form.errors.gia_giam" class="mt-1 text-[11px] font-bold text-red-500">{{ form.errors.gia_giam }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block font-bold text-slate-700"> Đơn Vị Tính <span class="text-red-500">*</span> </label>
                                    <input
                                        v-model="form.don_vi_tinh"
                                        type="text"
                                        required
                                        placeholder="Kg, Khay, Túi, Gói..."
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 font-bold text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <button
                                            v-for="u in quickUnits"
                                            :key="u"
                                            type="button"
                                            @click="selectQuickUnit(u)"
                                            class="rounded-lg border border-slate-200 bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-700"
                                        >
                                            {{ u }}
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block font-bold text-slate-700"> Quy Cách Đóng Gói </label>
                                    <input
                                        v-model="form.quy_cach"
                                        type="text"
                                        placeholder="VD: Khay 500g, Túi lưới 1kg, Bó 300g..."
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: HẠN SỬ DỤNG & BẢO QUẢN TIÊU CHUẨN (HỖ TRỢ FEFO) -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
                        <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                            <Sparkles class="h-5 w-5 text-emerald-600" />
                            <h3 class="text-sm font-black text-slate-900">Bảo Quản & Quản Lý Lô Date (FEFO)</h3>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block font-bold text-slate-700"> Hạn Sử Dụng Tiêu Chuẩn (Số Ngày) </label>
                                    <input
                                        v-model.number="form.han_su_dung_ngay"
                                        type="number"
                                        min="1"
                                        placeholder="3"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 font-bold text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                    <p class="mt-1 text-[11px] text-slate-500">Dùng để tự động tính Date hết hạn khi nhập kho hàng loạt.</p>
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
                </div>

                <!-- CỘT PHẢI (1 COL): BỘ SƯU TẬP ẢNH & THIẾT LẬP TRẠNG THÁI -->
                <div class="space-y-6">
                    <!-- SECTION 4: THƯ VIỆN & UPLOAD NHIỀU ẢNH -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
                        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2">
                                <ImagePlus class="h-5 w-5 text-emerald-600" />
                                <h3 class="text-sm font-black text-slate-900">Bộ Sưu Tập Hình Ảnh</h3>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-black text-emerald-700">
                                {{ previewImages.length }} ảnh
                            </span>
                        </div>

                        <!-- KHU VỰC KÉO THẢ ẢNH -->
                        <div
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                            @click="fileInputRef?.click()"
                            class="group relative flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed p-6 text-center transition-all duration-200"
                            :class="
                                isDragging
                                    ? 'border-emerald-500 bg-emerald-50/70 scale-[0.99]'
                                    : 'border-slate-300 bg-slate-50 hover:border-emerald-400 hover:bg-emerald-50/30'
                            "
                        >
                            <input
                                ref="fileInputRef"
                                type="file"
                                multiple
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                class="hidden"
                                @change="handleFileChange"
                            />
                            <div
                                class="mb-2.5 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm transition group-hover:scale-110 group-hover:text-emerald-600"
                            >
                                <UploadCloud class="h-6 w-6" />
                            </div>
                            <p class="text-xs font-bold text-slate-800">
                                Kéo thả ảnh vào đây hoặc <span class="text-emerald-600 underline">chọn từ máy tính</span>
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">Hỗ trợ JPG, PNG, WEBP lên đến 10MB (Có thể chọn nhiều ảnh cùng lúc)</p>
                        </div>

                        <!-- TÙY CHỌN NHẬP URL ONLINE -->
                        <div class="mt-3">
                            <button
                                type="button"
                                @click="showUrlInput = !showUrlInput"
                                class="text-[11px] font-semibold text-slate-500 hover:text-emerald-600"
                            >
                                {{ showUrlInput ? '− Ẩn nhập liên kết URL' : '+ Hoặc dán link ảnh trực tuyến' }}
                            </button>
                            <div v-if="showUrlInput" class="mt-2 flex gap-1.5">
                                <input
                                    v-model="manualUrlInput"
                                    type="url"
                                    placeholder="https://images.unsplash.com/..."
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs outline-none focus:border-emerald-500 focus:bg-white"
                                />
                                <button
                                    type="button"
                                    @click="addImageFromUrl"
                                    class="shrink-0 rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800"
                                >
                                    Thêm
                                </button>
                            </div>
                        </div>

                        <!-- DANH SÁCH ẢNH PREVIEW & CHỌN ẢNH CHÍNH -->
                        <div v-if="previewImages.length > 0" class="mt-5 space-y-2.5">
                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600">
                                <span>Danh sách ảnh đã chọn:</span>
                                <span class="text-emerald-600">Bấm ⭐ để đổi ảnh đại diện</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-2">
                                <div
                                    v-for="(img, idx) in previewImages"
                                    :key="img.id"
                                    class="group relative overflow-hidden rounded-2xl border bg-slate-100 transition-all"
                                    :class="
                                        selectedPrimaryIndex === idx
                                            ? 'border-2 border-emerald-500 ring-2 ring-emerald-500/20'
                                            : 'border-slate-200 hover:border-slate-300'
                                    "
                                >
                                    <div class="aspect-4/3 w-full overflow-hidden bg-slate-100">
                                        <img :src="img.url" class="h-full w-full object-cover transition group-hover:scale-105" alt="Preview" />
                                    </div>

                                    <!-- BADGE ẢNH CHÍNH -->
                                    <div
                                        v-if="selectedPrimaryIndex === idx"
                                        class="absolute left-1.5 top-1.5 flex items-center gap-1 rounded-md bg-emerald-600 px-1.5 py-0.5 text-[9px] font-black text-white shadow-sm"
                                    >
                                        <Star class="h-2.5 w-2.5 fill-current" />
                                        <span>Ảnh chính</span>
                                    </div>

                                    <!-- NÚT THAO TÁC HOVER -->
                                    <div
                                        class="absolute inset-0 flex items-center justify-center gap-1.5 bg-slate-900/50 opacity-0 transition group-hover:opacity-100"
                                    >
                                        <button
                                            type="button"
                                            @click="setPrimaryImage(idx)"
                                            class="rounded-lg p-1.5 text-white transition hover:bg-white/20"
                                            :class="{ 'text-amber-300': selectedPrimaryIndex === idx }"
                                            title="Đặt làm ảnh đại diện"
                                        >
                                            <Star class="h-4 w-4" :class="{ 'fill-current': selectedPrimaryIndex === idx }" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="removeImage(idx)"
                                            class="rounded-lg p-1.5 text-white transition hover:bg-red-500"
                                            title="Xóa ảnh này"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-4 text-center">
                            <p class="text-xs text-slate-400">Chưa có ảnh nào. Bạn nên thêm ít nhất 1 ảnh để khách hàng dễ nhận biết sản phẩm.</p>
                        </div>
                    </div>

                    <!-- SECTION 5: THIẾT LẬP PHÂN LOẠI & TRẠNG THÁI KINH DOANH -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
                        <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                            <Tag class="h-5 w-5 text-emerald-600" />
                            <h3 class="text-sm font-black text-slate-900">Trạng Thái & Phân Loại</h3>
                        </div>

                        <div class="space-y-4 text-xs">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border p-3.5 transition"
                                :class="
                                    form.la_tuoi_song
                                        ? 'border-emerald-200 bg-emerald-50/50 text-emerald-900'
                                        : 'border-slate-200 bg-slate-50 text-slate-700'
                                "
                            >
                                <input
                                    v-model="form.la_tuoi_song"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500"
                                />
                                <div>
                                    <p class="font-bold">Thực Phẩm Tươi Sống (Date Ngắn)</p>
                                    <p class="mt-0.5 text-[11px] leading-relaxed text-slate-500">
                                        Ưu tiên tự động xuất kho theo nguyên tắc FEFO (Hạn gần nhất xuất trước) để chống hủy hao hụt.
                                    </p>
                                </div>
                            </label>

                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border p-3.5 transition"
                                :class="
                                    form.trang_thai === 1
                                        ? 'border-emerald-200 bg-emerald-50/50 text-emerald-900'
                                        : 'border-slate-200 bg-slate-50 text-slate-700'
                                "
                            >
                                <input
                                    v-model="form.trang_thai"
                                    type="checkbox"
                                    :true-value="1"
                                    :false-value="0"
                                    class="mt-0.5 h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500"
                                />
                                <div>
                                    <p class="font-bold">Kích Hoạt Kinh Doanh Ngay</p>
                                    <p class="mt-0.5 text-[11px] leading-relaxed text-slate-500">
                                        Hiển thị sản phẩm trên Storefront và ứng dụng đặt hàng trực tuyến.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- TIPS BOX -->
                    <div class="rounded-3xl border border-sky-100 bg-sky-50/60 p-5 text-xs text-sky-900">
                        <div class="flex items-center gap-2 font-bold">
                            <Info class="h-4 w-4 text-sky-600" />
                            <span>Mẹo thiết lập giá chi nhánh</span>
                        </div>
                        <p class="mt-1 text-[11px] leading-relaxed text-sky-700">
                            Sau khi tạo sản phẩm với giá bán chuẩn ở đây, bạn có thể vào trang chi tiết hoặc danh sách sản phẩm để cấu hình bảng
                            giá riêng cho từng chi nhánh (nếu có giá ưu đãi theo khu vực).
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
