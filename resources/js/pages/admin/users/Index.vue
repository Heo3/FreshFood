<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    Plus,
    Search,
    Users,
    Edit2,
    Lock,
    Unlock,
    Store,
    X,
    Save,
    ShieldAlert,
    CheckCircle2,
} from 'lucide-vue-next';

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
            loyalty_points: number;
            cua_hang?: {
                id: number;
                ten_cua_hang: string;
            };
        }>;
        links: Array<any>;
    };
    stores: Array<{ id: number; ten_cua_hang: string }>;
    filters: {
        search?: string;
        role?: string;
        store_id?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');
const storeId = ref(props.filters.store_id || '');
const status = ref(props.filters.status !== undefined && props.filters.status !== null ? String(props.filters.status) : '');
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
});

function openCreateModal() {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    form.role = 'nhan_vien';
    form.store_id = props.stores[0]?.id || '';
    form.status = 1;
    showModal.value = true;
}

function openEditModal(user: any) {
    editingUser.value = user;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone || '';
    form.role = user.role;
    form.store_id = user.store_id !== null && user.store_id !== undefined ? user.store_id : '';
    form.password = '';
    form.status = user.status !== undefined ? user.status : 1;
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

    if (confirm(`Bạn có chắc muốn ${actionText} tài khoản "${user.name}"?\n\n${noteText}`)) {
        router.post(route('admin.users.toggle-status', user.id), {}, {
            preserveScroll: true,
        });
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
        { preserveState: true }
    );
}

function getRoleBadge(r: string) {
    switch (r) {
        case 'admin':
            return { label: 'Admin Tối Cao', class: 'bg-[#dc3545] text-white' };
        case 'quan_ly':
            return { label: 'Quản Lý Cửa Hàng', class: 'bg-[#6f42c1] text-white' };
        case 'nhan_vien':
            return { label: 'Nhân Viên Bán Hàng/Kho', class: 'bg-[#28a745] text-white' };
        case 'khach_hang':
            return { label: 'Khách Hàng', class: 'bg-[#6c757d] text-white' };
        default:
            return { label: r, class: 'bg-[#6c757d] text-white' };
    }
}
</script>

<template>
    <AdminLayout
        title="Nhân Sự & Phân Quyền"
        :breadcrumbs="[{ name: 'Admin', href: route('admin.dashboard') }, { name: 'Nhân sự' }]"
    >
        <div class="space-y-4">
            <!-- ADMINLTE FILTER CARD -->
            <div class="bg-white rounded-sm border-t-3 border-[#007bff] border-x border-b border-gray-200 shadow-xs p-3.5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative w-full sm:w-56">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Tên, email hoặc SĐT..."
                                class="w-full pl-3 pr-8 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                                @keyup.enter="handleFilter"
                            />
                            <Search
                                class="w-3.5 h-3.5 text-gray-400 absolute right-2.5 top-2.5 cursor-pointer"
                                @click="handleFilter"
                            />
                        </div>

                        <select
                            v-model="role"
                            class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                            @change="handleFilter"
                        >
                            <option value="">-- Tất cả vai trò --</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                            <option value="quan_ly">Quản lý chi nhánh</option>
                            <option value="nhan_vien">Nhân viên bán hàng/kho</option>
                            <option value="khach_hang">Khách hàng</option>
                        </select>

                        <select
                            v-model="storeId"
                            class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                            @change="handleFilter"
                        >
                            <option value="">-- Tất cả chi nhánh --</option>
                            <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }}</option>
                        </select>

                        <select
                            v-model="status"
                            class="px-2.5 py-1.5 bg-gray-50 border border-gray-300 rounded-sm text-xs focus:outline-hidden focus:border-[#007bff]"
                            @change="handleFilter"
                        >
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="1">Đang hoạt động</option>
                            <option value="0">Đã khóa / Ẩn</option>
                        </select>
                    </div>

                    <button
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-[#007bff] hover:bg-[#0069d9] text-white rounded-sm text-xs font-semibold shadow-2xs transition-colors shrink-0"
                        @click="openCreateModal"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>Thêm Nhân Sự</span>
                    </button>
                </div>
            </div>

            <!-- USERS TABLE CARD -->
            <div class="bg-white rounded-sm border border-gray-200 shadow-xs overflow-hidden">
                <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-xs font-bold text-[#343a40]">Danh Sách Tài Khoản Hệ Thống</span>
                    <span class="text-[11px] text-gray-500">{{ users.data.length }} tài khoản</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-200 text-gray-700 font-bold uppercase text-[10px]">
                                <th class="py-2.5 px-4">Tài Khoản</th>
                                <th class="py-2.5 px-4">Email / SĐT</th>
                                <th class="py-2.5 px-4">Vai Trò</th>
                                <th class="py-2.5 px-4">Chi Nhánh Phân Công</th>
                                <th class="py-2.5 px-4 text-center">Trạng Thái</th>
                                <th class="py-2.5 px-4 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="u in users.data" :key="u.id" class="hover:bg-gray-50 transition-colors">
                                <td class="py-2.5 px-4">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-7 h-7 rounded-full bg-gray-300 text-gray-700 font-bold flex items-center justify-center text-[11px]">
                                            {{ u.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900">{{ u.name }}</div>
                                            <span class="text-gray-400 font-mono text-[10px]">ID: #{{ u.id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-4 text-gray-700">
                                    <div>{{ u.email }}</div>
                                    <span class="text-gray-400 font-mono text-[11px]">{{ u.phone || 'Chưa có SĐT' }}</span>
                                </td>
                                <td class="py-2.5 px-4">
                                    <span
                                        :class="[
                                            'inline-block px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase shadow-2xs',
                                            getRoleBadge(u.role).class
                                        ]"
                                    >
                                        {{ getRoleBadge(u.role).label }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-gray-600">
                                    <span v-if="u.cua_hang" class="inline-flex items-center space-x-1">
                                        <Store class="w-3 h-3 text-gray-400" />
                                        <span>{{ u.cua_hang.ten_cua_hang }}</span>
                                    </span>
                                    <span v-else class="text-gray-400 italic">Trụ sở toàn chuỗi</span>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase shadow-2xs',
                                            u.status === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        ]"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="u.status === 1 ? 'bg-green-600' : 'bg-red-600'"></span>
                                        {{ u.status === 1 ? 'Hoạt động' : 'Đã khóa / Ẩn' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                    <button
                                        class="px-2.5 py-1 bg-gray-100 hover:bg-[#007bff] hover:text-white text-gray-700 rounded-sm font-semibold transition-colors shadow-2xs"
                                        title="Chỉnh sửa thông tin"
                                        @click="openEditModal(u)"
                                    >
                                        <Edit2 class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Sửa</span>
                                    </button>
                                    <button
                                        v-if="u.status === 1"
                                        class="px-2.5 py-1 bg-amber-50 hover:bg-amber-600 hover:text-white text-amber-700 border border-amber-200 hover:border-amber-600 rounded-sm font-semibold transition-colors shadow-2xs"
                                        title="Khóa / Ẩn tài khoản để bảo toàn lịch sử giao dịch"
                                        @click="toggleStatus(u)"
                                    >
                                        <Lock class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Khóa / Ẩn</span>
                                    </button>
                                    <button
                                        v-else
                                        class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 border border-emerald-200 hover:border-emerald-600 rounded-sm font-semibold transition-colors shadow-2xs"
                                        title="Mở khóa cho tài khoản hoạt động trở lại"
                                        @click="toggleStatus(u)"
                                    >
                                        <Unlock class="w-3.5 h-3.5 inline mr-1" />
                                        <span>Mở Khóa</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ADMINLTE MODAL -->
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            >
                <div class="bg-white rounded-sm max-w-lg w-full shadow-xl border border-gray-300 overflow-hidden text-xs">
                    <div class="px-4 py-3 bg-[#007bff] text-white flex items-center justify-between">
                        <h4 class="font-bold text-sm">
                            {{ editingUser ? 'Cập Nhật Tài Khoản' : 'Thêm Nhân Sự Mới' }}
                        </h4>
                        <button class="text-white hover:text-gray-200 p-1" @click="showModal = false">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form class="p-4 space-y-3" @submit.prevent="submitForm">
                        <div
                            v-if="Object.keys(form.errors).length > 0"
                            class="p-2.5 bg-red-50 border-l-3 border-[#dc3545] text-red-700 text-xs rounded-r-sm space-y-1"
                        >
                            <p class="font-bold">Vui lòng kiểm tra lại các mục lỗi bên dưới:</p>
                            <ul class="list-disc list-inside text-[11px]">
                                <li v-for="(err, field) in form.errors" :key="field">{{ err }}</li>
                            </ul>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Họ Và Tên <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Nguyễn Văn A"
                                :class="[
                                    'w-full px-2.5 py-1.5 border rounded-sm focus:outline-hidden focus:border-[#007bff]',
                                    form.errors.name ? 'border-red-400 bg-red-50/50' : 'border-gray-300'
                                ]"
                            />
                            <p v-if="form.errors.name" class="text-red-500 text-[11px] mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">
                                    Email Đăng Nhập <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="nhanvien@freshfood.vn"
                                    :class="[
                                        'w-full px-2.5 py-1.5 border rounded-sm focus:outline-hidden focus:border-[#007bff]',
                                        form.errors.email ? 'border-red-400 bg-red-50/50' : 'border-gray-300'
                                    ]"
                                />
                                <p v-if="form.errors.email" class="text-red-500 text-[11px] mt-1">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Số Điện Thoại</label>
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    placeholder="0987654321"
                                    :class="[
                                        'w-full px-2.5 py-1.5 border rounded-sm focus:outline-hidden focus:border-[#007bff]',
                                        form.errors.phone ? 'border-red-400 bg-red-50/50' : 'border-gray-300'
                                    ]"
                                />
                                <p v-if="form.errors.phone" class="text-red-500 text-[11px] mt-1">{{ form.errors.phone }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">
                                    Vai Trò Hệ Thống <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.role"
                                    :class="[
                                        'w-full px-2.5 py-1.5 border rounded-sm bg-white focus:outline-hidden focus:border-[#007bff]',
                                        form.errors.role ? 'border-red-400 bg-red-50/50' : 'border-gray-300'
                                    ]"
                                >
                                    <option value="nhan_vien">Nhân Viên Bán Hàng/Kho</option>
                                    <option value="quan_ly">Quản Lý Chi Nhánh</option>
                                    <option value="admin">Quản Trị Viên (Admin)</option>
                                    <option value="khach_hang">Khách Hàng</option>
                                </select>
                                <p v-if="form.errors.role" class="text-red-500 text-[11px] mt-1">{{ form.errors.role }}</p>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Chi Nhánh Làm Việc</label>
                                <select
                                    v-model="form.store_id"
                                    :class="[
                                        'w-full px-2.5 py-1.5 border rounded-sm bg-white focus:outline-hidden focus:border-[#007bff]',
                                        form.errors.store_id ? 'border-red-400 bg-red-50/50' : 'border-gray-300'
                                    ]"
                                >
                                    <option value="">-- Toàn chuỗi (Không gán) --</option>
                                    <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.ten_cua_hang }}</option>
                                </select>
                                <p v-if="form.errors.store_id" class="text-red-500 text-[11px] mt-1">{{ form.errors.store_id }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                Mật Khẩu {{ editingUser ? '(Bỏ trống nếu không đổi)' : '*' }}
                            </label>
                            <input
                                v-model="form.password"
                                type="password"
                                :required="!editingUser"
                                placeholder="••••••••"
                                :class="[
                                    'w-full px-2.5 py-1.5 border rounded-sm focus:outline-hidden focus:border-[#007bff]',
                                    form.errors.password ? 'border-red-400 bg-red-50/50' : 'border-gray-300'
                                ]"
                            />
                            <p v-if="form.errors.password" class="text-red-500 text-[11px] mt-1">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Trạng Thái Tài Khoản</label>
                            <select
                                v-model="form.status"
                                class="w-full px-2.5 py-1.5 border border-gray-300 rounded-sm bg-white focus:outline-hidden focus:border-[#007bff]"
                            >
                                <option :value="1">Đang hoạt động (Được phép đăng nhập)</option>
                                <option :value="0">Đã khóa / Ẩn (Ngừng đăng nhập - Bảo toàn lịch sử)</option>
                            </select>
                            <p v-if="form.errors.status" class="text-red-500 text-[11px] mt-1">{{ form.errors.status }}</p>
                        </div>

                        <div class="pt-3 border-t border-gray-200 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-sm font-semibold transition-colors"
                                @click="showModal = false"
                            >
                                Hủy Bỏ
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-1.5 bg-[#007bff] hover:bg-[#0069d9] text-white rounded-sm font-semibold shadow-2xs transition-colors flex items-center space-x-1"
                            >
                                <Save class="w-3.5 h-3.5" />
                                <span>{{ form.processing ? 'Đang lưu...' : (editingUser ? 'Lưu Thay Đổi' : 'Tạo Tài Khoản') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
