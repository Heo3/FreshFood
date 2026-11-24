<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import {
    Bike,
    Check,
    CheckCircle2,
    Edit2,
    Eye,
    Key,
    Lock,
    Minus,
    Plus,
    Save,
    Search,
    Shield,
    ShieldAlert,
    ShieldCheck,
    Store,
    Truck,
    Unlock,
    UserCheck,
    Users,
    X,
    XCircle,
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            phone: string | null;
            role: string;
            store_id: number | null;
            status: number;
            loai_phuong_tien?: 'xe_may' | 'xe_tai' | null;
            bien_so_xe?: string | null;
            tai_trong_kg?: number | null;
            trang_thai_giao?: string | null;
            loyalty_points: number;
            cua_hang?: {
                id: number;
                ten_cua_hang: string;
            };
        }>;
        links: Array<any>;
    };
    stores: Array<{ id: number; ten_cua_hang: string }>;
    stats?: {
        total: number;
        admins: number;
        managers: number;
        staff: number;
        delivery: number;
        customers: number;
        active: number;
        locked: number;
    };
    filters: {
        search?: string;
        role?: string;
        store_id?: string;
        status?: string;
    };
}>();

const activeTab = ref<'users' | 'matrix'>('users');
const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');
const storeId = ref(props.filters.store_id || '');
const status = ref(
    props.filters.status !== undefined && props.filters.status !== null
        ? String(props.filters.status)
        : '',
);
const showModal = ref(false);
const editingUser = ref<any | null>(null);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    role: 'nhan_vien',
    store_id: props.stores[0]?.id || '',
    password: '',
    status: 1,
    loai_phuong_tien: 'xe_may' as 'xe_may' | 'xe_tai',
    bien_so_xe: '',
    tai_trong_kg: null as number | null,
    trang_thai_giao: 'san_sang',
});

function openCreateModal() {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    form.role = 'nhan_vien';
    form.store_id = props.stores[0]?.id || '';
    form.status = 1;
    form.loai_phuong_tien = 'xe_may';
    form.bien_so_xe = '';
    form.tai_trong_kg = null;
    form.trang_thai_giao = 'san_sang';
    showModal.value = true;
}

function openEditModal(user: any) {
    editingUser.value = user;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone || '';
    form.role = user.role;
    form.store_id =
        user.store_id !== null && user.store_id !== undefined ? user.store_id : '';
    form.password = '';
    form.status = user.status !== undefined ? user.status : 1;
    form.loai_phuong_tien = user.loai_phuong_tien || 'xe_may';
    form.bien_so_xe = user.bien_so_xe || '';
    form.tai_trong_kg = user.tai_trong_kg || null;
    form.trang_thai_giao = user.trang_thai_giao || 'san_sang';
    showModal.value = true;
}

function submitForm() {
    if (editingUser.value) {
        form.put(route('admin.users.update', editingUser.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                editingUser.value = null;
                form.reset();
            },
        });
    } else {
        form.post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
}

function toggleStatus(user: any) {
    const isLocking = user.status === 1;
    const actionText = isLocking ? 'KHÓA / ẨN' : 'MỞ KHÓA';
    const noteText = isLocking
        ? 'Tài khoản này sẽ bị khóa và không thể đăng nhập. Toàn bộ lịch sử đơn hàng, kho và chứng từ do nhân sự này thực hiện vẫn được giữ nguyên vẹn để bảo đảm tính toàn vẹn dữ liệu.'
        : 'Tài khoản này sẽ được mở khóa và có thể đăng nhập hoạt động bình thường.';

    if (
        confirm(
            `Bạn có chắc muốn ${actionText} tài khoản "${user.name}"?\n\n${noteText}`,
        )
    ) {
        router.post(
            route('admin.users.toggle-status', user.id),
            {},
            {
                preserveScroll: true,
            },
        );
    }
}

function handleFilter() {
    router.get(
        route('admin.users.index'),
        {
            search: search.value,
            role: role.value,
            store_id: storeId.value,
            status: status.value,
        },
        { preserveState: true },
    );
}

function getRoleBadge(r: string) {
    switch (r) {
        case 'admin':
            return {
                label: 'Admin Tối Cao',
                class: 'bg-red-600 text-white',
                dot: 'bg-red-200',
                icon: ShieldAlert,
            };
        case 'quan_ly':
            return {
                label: 'Quản Lý Cửa Hàng',
                class: 'bg-purple-600 text-white',
                dot: 'bg-purple-200',
                icon: ShieldCheck,
            };
        case 'nhan_vien':
            return {
                label: 'Nhân Viên Bán Hàng/Kho',
                class: 'bg-emerald-600 text-white',
                dot: 'bg-emerald-200',
                icon: Store,
            };
        case 'giao_hang':
            return {
                label: 'Nhân Viên Giao Hàng',
                class: 'bg-blue-600 text-white',
                dot: 'bg-blue-200',
                icon: Truck,
            };
        case 'khach_hang':
            return {
                label: 'Khách Hàng',
                class: 'bg-slate-500 text-white',
                dot: 'bg-slate-200',
                icon: Users,
            };
        default:
            return {
                label: r,
                class: 'bg-slate-600 text-white',
                dot: 'bg-slate-200',
                icon: Users,
            };
    }
}

// Bảng ma trận phân quyền chi tiết cho Tab Ma Trận
const permissionMatrix = [
    {
        module: 'Cấu hình Hệ thống & Chuỗi cửa hàng',
        desc: 'Thiết lập tham số toàn chuỗi, thêm chi nhánh, phân quyền nhân sự',
        admin: 'Toàn quyền',
        quan_ly: 'Chỉ xem chi nhánh của mình',
        nhan_vien: 'Không có quyền',
        giao_hang: 'Không có quyền',
    },
    {
        module: 'Quản lý Sản phẩm & Bảng giá',
        desc: 'Tạo sản phẩm, tải ảnh, phân loại danh mục, điều chỉnh giá bán',
        admin: 'Toàn quyền toàn chuỗi',
        quan_ly: 'Cài giá riêng chi nhánh',
        nhan_vien: 'Chỉ tra cứu giá',
        giao_hang: 'Chỉ xem món trên đơn',
    },
    {
        module: 'Kho & Quản lý Lô Hạn (FEFO)',
        desc: 'Tạo phiếu nhập kho, xuất hủy, kiểm kê, điều chuyển kho liên chi nhánh',
        admin: 'Toàn quyền duyệt toàn hệ thống',
        quan_ly: 'Toàn quyền tại chi nhánh',
        nhan_vien: 'Tạo phiếu & kiểm kê',
        giao_hang: 'Ký nhận phiếu chuyển (Xe tải)',
    },
    {
        module: 'Bán hàng Thu ngân tại quầy (POS)',
        desc: 'Mở ca, quét mã vạch bán hàng, áp mã giảm giá, in hóa đơn bán lẻ',
        admin: 'Giám sát doanh thu',
        quan_ly: 'Duyệt hủy hóa đơn & ca',
        nhan_vien: 'Toàn quyền bán hàng',
        giao_hang: 'Không có quyền',
    },
    {
        module: 'Điều phối & Giao hàng (Fleet)',
        desc: 'Phân tài xế cho đơn online, cập nhật trạng thái đơn, thu tiền COD',
        admin: 'Giám sát toàn đội xe',
        quan_ly: 'Điều phối & quản lý xe',
        nhan_vien: 'Đóng gói hàng giao',
        giao_hang: 'Nhận đơn, giao & thu COD',
    },
    {
        module: 'Báo cáo & Phân tích Doanh thu',
        desc: 'Xem biểu đồ doanh số, lợi nhuận, hàng sắp hết hạn, hiệu suất ca',
        admin: 'Báo cáo tài chính toàn chuỗi',
        quan_ly: 'Báo cáo tại chi nhánh',
        nhan_vien: 'Báo cáo kết ca thu ngân',
        giao_hang: 'Báo cáo tiền COD cuối ngày',
    },
];
</script>

<template>
    <AdminLayout
        title="Nhân Sự & Phân Quyền"
        :breadcrumbs="[
            { name: 'Admin', href: route('admin.dashboard') },
            { name: 'Nhân sự & Phân quyền' },
        ]"
    >
        <div class="space-y-5">
            <!-- 1. STAT CARDS THỐNG KÊ NHANH NHÂN SỰ -->
            <div
                v-if="stats"
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"
            >
                <div
                    class="flex items-center space-x-3 rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xs transition hover:shadow-md"
                >
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700"
                    >
                        <Users class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-slate-500">
                            Tổng Tài Khoản
                        </div>
                        <div class="text-lg font-black text-slate-900">
                            {{ stats.total }}
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center space-x-3 rounded-2xl border border-purple-100 bg-purple-50/50 p-3.5 shadow-xs transition hover:shadow-md"
                >
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-600 text-white"
                    >
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-purple-700">
                            Admin & Quản Lý
                        </div>
                        <div class="text-lg font-black text-purple-950">
                            {{ stats.admins + stats.managers }}
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center space-x-3 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-3.5 shadow-xs transition hover:shadow-md"
                >
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white"
                    >
                        <Store class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-emerald-700">
                            Nhân Viên Bán/Kho
                        </div>
                        <div class="text-lg font-black text-emerald-950">
                            {{ stats.staff }}
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center space-x-3 rounded-2xl border border-blue-100 bg-blue-50/50 p-3.5 shadow-xs transition hover:shadow-md"
                >
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white"
                    >
                        <Truck class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-blue-700">
                            Tài Xế Giao Hàng
                        </div>
                        <div class="text-lg font-black text-blue-950">
                            {{ stats.delivery }}
                        </div>
                    </div>
                </div>

                <div
                    class="col-span-2 flex items-center space-x-3 rounded-2xl border border-green-100 bg-green-50/50 p-3.5 shadow-xs transition hover:shadow-md sm:col-span-1"
                >
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-600 text-white"
                    >
                        <UserCheck class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-green-700">
                            Đang Hoạt Động
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <span class="text-lg font-black text-green-950">{{
                                stats.active
                            }}</span>
                            <span
                                v-if="stats.locked > 0"
                                class="text-[11px] font-semibold text-red-600"
                            >
                                ({{ stats.locked }} đã khóa)
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. TAB CONTROLS (DANH SÁCH / MA TRẬN PHÂN QUYỀN) -->
            <div class="flex items-center justify-between border-b border-slate-200">
                <div class="flex space-x-2">
                    <button
                        @click="activeTab = 'users'"
                        :class="[
                            'flex items-center space-x-2 border-b-2 px-4 py-2.5 text-xs font-bold transition-colors cursor-pointer',
                            activeTab === 'users'
                                ? 'border-emerald-600 text-emerald-700'
                                : 'border-transparent text-slate-500 hover:text-slate-800',
                        ]"
                    >
                        <Users class="h-4 w-4" />
                        <span>Danh Sách Nhân Sự & Tài Khoản</span>
                        <span
                            class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600"
                        >
                            {{ users.data.length }}
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'matrix'"
                        :class="[
                            'flex items-center space-x-2 border-b-2 px-4 py-2.5 text-xs font-bold transition-colors cursor-pointer',
                            activeTab === 'matrix'
                                ? 'border-emerald-600 text-emerald-700'
                                : 'border-transparent text-slate-500 hover:text-slate-800',
                        ]"
                    >
                        <Shield class="h-4 w-4" />
                        <span>Ma Trận Phân Quyền Vai Trò</span>
                        <span
                            class="rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-700"
                        >
                            5 Vai Trò
                        </span>
                    </button>
                </div>

                <button
                    class="shadow-2xs mb-2 inline-flex shrink-0 items-center space-x-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700 cursor-pointer"
                    @click="openCreateModal"
                >
                    <Plus class="h-4 w-4" />
                    <span>Thêm Nhân Sự Mới</span>
                </button>
            </div>

            <!-- TAB 1: DANH SÁCH NHÂN SỰ -->
            <div v-if="activeTab === 'users'" class="space-y-4">
                <!-- FILTER TOOLBAR CARD -->
                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xs"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2.5"
                    >
                        <div
                            class="flex min-w-[280px] flex-1 flex-wrap items-center gap-2"
                        >
                            <div class="relative w-full sm:w-60">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Tên, email hoặc SĐT..."
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-3.5 pr-8 text-xs text-slate-800 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                    @keyup.enter="handleFilter"
                                />
                                <Search
                                    class="absolute right-3 top-2.5 h-4 w-4 cursor-pointer text-slate-400"
                                    @click="handleFilter"
                                />
                            </div>

                            <select
                                v-model="role"
                                class="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs text-slate-800 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                @change="handleFilter"
                            >
                                <option value="">-- Tất cả vai trò --</option>
                                <option value="admin">
                                    👑 Quản trị viên (Admin)
                                </option>
                                <option value="quan_ly">
                                    👔 Quản lý chi nhánh
                                </option>
                                <option value="nhan_vien">
                                    💼 Nhân viên bán hàng / Kho
                                </option>
                                <option value="giao_hang">
                                    🚚 Nhân viên giao hàng (Shipper)
                                </option>
                                <option value="khach_hang">
                                    🛒 Khách hàng
                                </option>
                            </select>

                            <select
                                v-model="storeId"
                                class="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs text-slate-800 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                @change="handleFilter"
                            >
                                <option value="">-- Tất cả chi nhánh --</option>
                                <option
                                    v-for="s in stores"
                                    :key="s.id"
                                    :value="s.id"
                                >
                                    {{ s.ten_cua_hang }}
                                </option>
                            </select>

                            <select
                                v-model="status"
                                class="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs text-slate-800 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                @change="handleFilter"
                            >
                                <option value="">
                                    -- Tất cả trạng thái --
                                </option>
                                <option value="1">🟢 Đang hoạt động</option>
                                <option value="0">🔴 Đã khóa / Ẩn</option>
                            </select>
                        </div>

                        <button
                            class="flex items-center space-x-1 rounded-xl bg-slate-700 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-slate-800 cursor-pointer"
                            @click="handleFilter"
                        >
                            <span>Lọc Dữ Liệu</span>
                        </button>
                    </div>
                </div>

                <!-- TABLE CARD -->
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-4 py-3"
                    >
                        <span class="text-xs font-bold text-slate-800"
                            >Danh Sách Nhân Sự & Phân Công Chi Nhánh</span
                        >
                        <span class="text-[11px] text-slate-500"
                            >Hiển thị {{ users.data.length }} kết quả</span
                        >
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr
                                    class="border-b border-slate-200 bg-slate-100/70 text-[10px] font-bold uppercase tracking-wider text-slate-600"
                                >
                                    <th class="px-4 py-3">Nhân Sự</th>
                                    <th class="px-4 py-3">Email & SĐT</th>
                                    <th class="px-4 py-3">Vai Trò Hệ Thống</th>
                                    <th class="px-4 py-3">Chi Nhánh / Xe</th>
                                    <th class="px-4 py-3 text-center">
                                        Trạng Thái
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        Thao Tác
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="u in users.data"
                                    :key="u.id"
                                    class="transition-colors hover:bg-slate-50/80"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="flex items-center space-x-3"
                                        >
                                            <div
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-black text-white shadow-xs"
                                                :class="
                                                    getRoleBadge(u.role).class
                                                "
                                            >
                                                {{
                                                    u.name
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </div>
                                            <div>
                                                <div
                                                    class="font-bold text-slate-900"
                                                >
                                                    {{ u.name }}
                                                </div>
                                                <span
                                                    class="font-mono text-[10px] text-slate-400"
                                                    >Mã: #{{ u.id }}</span
                                                >
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800">
                                            {{ u.email }}
                                        </div>
                                        <span
                                            class="font-mono text-[11px] text-slate-500"
                                        >
                                            {{ u.phone || 'Chưa cập nhật SĐT' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'inline-flex items-center space-x-1.5 rounded-lg px-2.5 py-1 text-[11px] font-bold shadow-2xs',
                                                getRoleBadge(u.role).class,
                                            ]"
                                        >
                                            <component
                                                :is="getRoleBadge(u.role).icon"
                                                class="h-3.5 w-3.5"
                                            />
                                            <span>{{
                                                getRoleBadge(u.role).label
                                            }}</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            v-if="u.cua_hang"
                                            class="flex items-center space-x-1 font-medium text-slate-700"
                                        >
                                            <Store
                                                class="h-3.5 w-3.5 text-slate-400"
                                            />
                                            <span>{{
                                                u.cua_hang.ten_cua_hang
                                            }}</span>
                                        </div>
                                        <div
                                            v-else
                                            class="italic text-slate-400"
                                        >
                                            Trụ sở toàn chuỗi
                                        </div>

                                        <!-- THÔNG TIN PHƯƠNG TIỆN NẾU LÀ GIAO HÀNG -->
                                        <div
                                            v-if="
                                                u.role === 'giao_hang' &&
                                                u.bien_so_xe
                                            "
                                            class="mt-1 flex items-center space-x-1.5 text-[10px] font-bold text-blue-700"
                                        >
                                            <component
                                                :is="
                                                    u.loai_phuong_tien ===
                                                    'xe_tai'
                                                        ? Truck
                                                        : Bike
                                                "
                                                class="h-3 w-3"
                                            />
                                            <span>{{
                                                u.loai_phuong_tien === 'xe_tai'
                                                    ? 'Xe tải'
                                                    : 'Xe máy'
                                            }}</span>
                                            <span>• BKS: {{ u.bien_so_xe }}</span>
                                            <span v-if="u.tai_trong_kg"
                                                >({{ u.tai_trong_kg }}kg)</span
                                            >
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold',
                                                u.status === 1
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : 'bg-red-100 text-red-800',
                                            ]"
                                        >
                                            <span
                                                class="mr-1.5 h-1.5 w-1.5 rounded-full"
                                                :class="
                                                    u.status === 1
                                                        ? 'bg-emerald-500'
                                                        : 'bg-red-500'
                                                "
                                            />
                                            {{
                                                u.status === 1
                                                    ? 'Hoạt động'
                                                    : 'Đã khóa'
                                            }}
                                        </span>
                                    </td>
                                    <td class="space-x-1.5 px-4 py-3 text-right">
                                        <button
                                            class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700 transition hover:border-slate-300 hover:bg-white cursor-pointer"
                                            title="Chỉnh sửa thông tin"
                                            @click="openEditModal(u)"
                                        >
                                            <Edit2
                                                class="mr-1 inline h-3.5 w-3.5"
                                            />
                                            <span>Sửa</span>
                                        </button>
                                        <button
                                            v-if="u.status === 1"
                                            class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 transition hover:bg-amber-600 hover:text-white cursor-pointer"
                                            title="Khóa tài khoản (bảo toàn lịch sử đơn & kho)"
                                            @click="toggleStatus(u)"
                                        >
                                            <Lock
                                                class="mr-1 inline h-3.5 w-3.5"
                                            />
                                            <span>Khóa</span>
                                        </button>
                                        <button
                                            v-else
                                            class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 transition hover:bg-emerald-600 hover:text-white cursor-pointer"
                                            title="Mở khóa hoạt động"
                                            @click="toggleStatus(u)"
                                        >
                                            <Unlock
                                                class="mr-1 inline h-3.5 w-3.5"
                                            />
                                            <span>Mở</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: MA TRẬN PHÂN QUYỀN HỆ THỐNG -->
            <div v-else-if="activeTab === 'matrix'" class="space-y-4">
                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs"
                >
                    <div class="mb-4">
                        <h3 class="text-sm font-black text-slate-900">
                            Bảng Ma Trận Phân Quyền Vai Trò Hệ Thống (RBAC)
                        </h3>
                        <p class="text-xs text-slate-500">
                            Quy định ranh giới truy cập nghiệp vụ giữa các vai
                            trò để đảm bảo tính an toàn dữ liệu và bảo mật vận
                            hành chuỗi.
                        </p>
                    </div>

                    <div
                        class="overflow-x-auto rounded-xl border border-slate-200"
                    >
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr
                                    class="border-b border-slate-200 bg-slate-50 font-bold text-slate-700"
                                >
                                    <th class="px-4 py-3.5 w-1/4">
                                        Phân Hệ Nghiệp Vụ
                                    </th>
                                    <th class="px-4 py-3.5 text-center">
                                        <div
                                            class="inline-flex items-center space-x-1 rounded-md bg-red-100 px-2 py-0.5 text-red-800 font-bold"
                                        >
                                            <ShieldAlert class="h-3 w-3" />
                                            <span>Admin</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3.5 text-center">
                                        <div
                                            class="inline-flex items-center space-x-1 rounded-md bg-purple-100 px-2 py-0.5 text-purple-800 font-bold"
                                        >
                                            <ShieldCheck class="h-3 w-3" />
                                            <span>Quản Lý</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3.5 text-center">
                                        <div
                                            class="inline-flex items-center space-x-1 rounded-md bg-emerald-100 px-2 py-0.5 text-emerald-800 font-bold"
                                        >
                                            <Store class="h-3 w-3" />
                                            <span>Nhân Viên</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3.5 text-center">
                                        <div
                                            class="inline-flex items-center space-x-1 rounded-md bg-blue-100 px-2 py-0.5 text-blue-800 font-bold"
                                        >
                                            <Truck class="h-3 w-3" />
                                            <span>Giao Hàng</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(row, idx) in permissionMatrix"
                                    :key="idx"
                                    class="hover:bg-slate-50/60"
                                >
                                    <td class="px-4 py-3.5">
                                        <div class="font-bold text-slate-900">
                                            {{ row.module }}
                                        </div>
                                        <div
                                            class="mt-0.5 text-[11px] text-slate-500"
                                        >
                                            {{ row.desc }}
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3.5 text-center font-semibold text-slate-700"
                                    >
                                        <span
                                            class="rounded-md bg-red-50 px-2 py-1 text-[11px] text-red-700 font-bold"
                                        >
                                            {{ row.admin }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3.5 text-center font-semibold text-slate-700"
                                    >
                                        <span
                                            class="rounded-md bg-purple-50 px-2 py-1 text-[11px] text-purple-700 font-bold"
                                        >
                                            {{ row.quan_ly }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3.5 text-center font-semibold text-slate-700"
                                    >
                                        <span
                                            class="rounded-md bg-emerald-50 px-2 py-1 text-[11px] text-emerald-700 font-bold"
                                        >
                                            {{ row.nhan_vien }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3.5 text-center font-semibold text-slate-700"
                                    >
                                        <span
                                            class="rounded-md bg-blue-50 px-2 py-1 text-[11px] text-blue-700 font-bold"
                                        >
                                            {{ row.giao_hang }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MODAL THÊM / SỬA NHÂN SỰ NÂNG CAO -->
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
            >
                <div
                    class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200 bg-white text-xs shadow-2xl duration-200 animate-in fade-in zoom-in-95"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-6 py-4"
                    >
                        <div class="flex items-center space-x-2.5">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 font-bold"
                            >
                                <Users class="h-5 w-5" />
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">
                                    {{
                                        editingUser
                                            ? 'Cập Nhật Nhân Sự'
                                            : 'Thêm Nhân Sự Mới'
                                    }}
                                </h4>
                                <p class="text-[11px] text-slate-500">
                                    Thiết lập quyền truy cập và chi nhánh phân
                                    công
                                </p>
                            </div>
                        </div>
                        <button
                            class="rounded-full p-1.5 text-slate-400 hover:bg-slate-200/60 hover:text-slate-700 cursor-pointer"
                            @click="showModal = false"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form
                        class="max-h-[80vh] overflow-y-auto p-6 space-y-4"
                        @submit.prevent="submitForm"
                    >
                        <!-- Danh sách lỗi nếu có -->
                        <div
                            v-if="Object.keys(form.errors).length > 0"
                            class="rounded-2xl border border-red-200 bg-red-50 p-3 text-xs text-red-700"
                        >
                            <p class="font-bold">
                                Vui lòng kiểm tra lại các mục lỗi bên dưới:
                            </p>
                            <ul class="mt-1 list-inside list-disc text-[11px]">
                                <li
                                    v-for="(err, field) in form.errors"
                                    :key="field"
                                >
                                    {{ err }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-slate-700">
                                Họ Và Tên <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Nguyễn Văn A"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="mb-1 block font-bold text-slate-700"
                                >
                                    Email Đăng Nhập
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="nhanvien@freshfood.vn"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-1 block font-bold text-slate-700"
                                    >Số Điện Thoại</label
                                >
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    placeholder="0987654321"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="mb-1 block font-bold text-slate-700"
                                >
                                    Vai Trò Hệ Thống
                                    <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.role"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 font-bold text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                >
                                    <option value="nhan_vien">
                                        💼 Nhân Viên Bán Hàng / Kho
                                    </option>
                                    <option value="quan_ly">
                                        👔 Quản Lý Chi Nhánh
                                    </option>
                                    <option value="giao_hang">
                                        🚚 Nhân Viên Giao Hàng (Shipper/Tài xế)
                                    </option>
                                    <option value="admin">
                                        👑 Quản Trị Viên (Admin)
                                    </option>
                                    <option value="khach_hang">
                                        🛒 Khách Hàng
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1 block font-bold text-slate-700"
                                    >Chi Nhánh Làm Việc</label
                                >
                                <select
                                    v-model="form.store_id"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                                >
                                    <option value="">
                                        -- Toàn chuỗi (Không gán) --
                                    </option>
                                    <option
                                        v-for="s in stores"
                                        :key="s.id"
                                        :value="s.id"
                                    >
                                        {{ s.ten_cua_hang }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- CẤU HÌNH PHƯƠNG TIỆN NẾU CHỌN VAI TRÒ GIAO HÀNG -->
                        <div
                            v-if="form.role === 'giao_hang'"
                            class="rounded-2xl border border-blue-200 bg-blue-50/50 p-4 space-y-3"
                        >
                            <div class="flex items-center space-x-2">
                                <Truck class="h-4 w-4 text-blue-600" />
                                <span class="font-bold text-blue-900"
                                    >Thông Tin Phương Tiện Giao Hàng</span
                                >
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label
                                        class="mb-1 block text-[11px] font-bold text-slate-700"
                                        >Loại Xe</label
                                    >
                                    <select
                                        v-model="form.loai_phuong_tien"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-blue-500"
                                    >
                                        <option value="xe_may">
                                            🏍️ Xe máy (Giao đơn lẻ)
                                        </option>
                                        <option value="xe_tai">
                                            🚚 Xe tải (Chuyển kho / Sỉ)
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="mb-1 block text-[11px] font-bold text-slate-700"
                                        >Biển Số Xe</label
                                    >
                                    <input
                                        v-model="form.bien_so_xe"
                                        type="text"
                                        placeholder="59A-123.45"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-mono font-bold text-slate-900 outline-none focus:border-blue-500"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="mb-1 block text-[11px] font-bold text-slate-700"
                                        >Tải Trọng (Kg)</label
                                    >
                                    <input
                                        v-model.number="form.tai_trong_kg"
                                        type="number"
                                        min="1"
                                        placeholder="500"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-900 outline-none focus:border-blue-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-slate-700">
                                Mật Khẩu
                                {{
                                    editingUser
                                        ? '(Bỏ trống nếu không đổi)'
                                        : '*'
                                }}
                            </label>
                            <input
                                v-model="form.password"
                                type="password"
                                :required="!editingUser"
                                placeholder="••••••••"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block font-bold text-slate-700"
                                >Trạng Thái Tài Khoản</label
                            >
                            <select
                                v-model="form.status"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 font-bold text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20"
                            >
                                <option :value="1">
                                    🟢 Đang hoạt động (Được phép đăng nhập)
                                </option>
                                <option :value="0">
                                    🔴 Đã khóa / Ẩn (Ngừng đăng nhập - Bảo toàn
                                    lịch sử)
                                </option>
                            </select>
                        </div>

                        <div
                            class="flex items-center justify-end space-x-2 border-t border-slate-100 pt-4"
                        >
                            <button
                                type="button"
                                class="rounded-xl bg-slate-100 px-4 py-2 font-bold text-slate-700 transition hover:bg-slate-200 cursor-pointer"
                                @click="showModal = false"
                            >
                                Hủy Bỏ
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="shadow-2xs flex items-center space-x-1.5 rounded-xl bg-emerald-600 px-5 py-2 font-bold text-white transition hover:bg-emerald-700 cursor-pointer"
                            >
                                <Save class="h-4 w-4" />
                                <span>{{
                                    form.processing
                                        ? 'Đang lưu...'
                                        : editingUser
                                          ? 'Lưu Thay Đổi'
                                          : 'Tạo Nhân Sự'
                                }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
