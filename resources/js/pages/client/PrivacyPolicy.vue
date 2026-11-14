<script setup lang="ts">
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Eye, FileText, Lock, ShieldCheck } from 'lucide-vue-next';

defineProps<{
    stores?: Array<any>;
}>();
</script>

<template>
    <StoreLayout title="Chính Sách Bảo Mật Thông Tin" :stores="stores">
        <!-- BREADCRUMB -->
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-5xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">Chính sách bảo mật</span>
            </div>
        </div>

        <div class="mx-auto max-w-4xl px-4 py-10">
            <div class="space-y-8 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-10">
                <!-- HEADER -->
                <div class="border-b border-gray-100 pb-6">
                    <div class="mb-3 inline-flex items-center space-x-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                        <ShieldCheck class="h-4 w-4" />
                        <span>FreshFood Privacy Policy</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl">Chính Sách Bảo Mật Thông Tin</h1>
                    <p class="mt-2 text-xs text-gray-500">
                        Cập nhật lần cuối: Tháng 11/2026 • Áp dụng cho toàn bộ nền tảng thương mại điện tử FreshFood
                    </p>
                </div>

                <!-- CONTENT SECTIONS -->
                <div class="space-y-6 text-xs leading-relaxed text-gray-700 sm:text-sm">
                    <section class="space-y-2">
                        <h2 class="flex items-center text-base font-bold text-gray-900">
                            <Lock class="mr-2 h-4 w-4 text-emerald-600" /> 1. Mục đích thu thập thông tin cá nhân
                        </h2>
                        <p>
                            FreshFood thu thập thông tin khách hàng (bao gồm họ tên, số điện thoại, địa chỉ nhận hàng, email và thông tin tài khoản
                            Google khi bạn chọn Đăng nhập bằng Google) nhằm các mục đích sau:
                        </p>
                        <ul class="list-disc space-y-1 pl-5 text-gray-600">
                            <li>Xử lý và giao hàng tận nơi siêu tốc trong vòng 2 giờ.</li>
                            <li>Thông báo tiến độ xử lý đơn hàng và tích lũy điểm thưởng thành viên (Loyalty Points).</li>
                            <li>Hỗ trợ đổi trả, bảo hành sản phẩm tươi sống theo cam kết chất lượng.</li>
                            <li>Cá nhân hóa trải nghiệm mua sắm và cung cấp bảng giá tối ưu theo chi nhánh gần nhất.</li>
                        </ul>
                    </section>

                    <section class="space-y-2">
                        <h2 class="flex items-center text-base font-bold text-gray-900">
                            <Eye class="mr-2 h-4 w-4 text-emerald-600" /> 2. Phạm vi sử dụng thông tin
                        </h2>
                        <p>
                            Thông tin thu thập chỉ được sử dụng trong nội bộ chuỗi hệ thống cửa hàng FreshFood và đơn vị vận chuyển giao hàng được ủy
                            quyền. Chúng tôi cam kết <strong>không bán, chia sẻ hoặc trao đổi</strong> thông tin cá nhân của quý khách cho bất kỳ bên
                            thứ ba nào vì mục đích thương mại.
                        </p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="flex items-center text-base font-bold text-gray-900">
                            <ShieldCheck class="mr-2 h-4 w-4 text-emerald-600" /> 3. Xác thực tài khoản bên thứ ba (Google OAuth)
                        </h2>
                        <p>
                            Khi quý khách lựa chọn hình thức <em>"Đăng nhập bằng Google"</em>, FreshFood chỉ yêu cầu các quyền thông tin cơ bản công
                            khai gồm: Tên hiển thị, Địa chỉ Email và Ảnh đại diện (Avatar). FreshFood không yêu cầu hay lưu trữ mật khẩu tài khoản
                            Google của quý khách.
                        </p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="flex items-center text-base font-bold text-gray-900">
                            <FileText class="mr-2 h-4 w-4 text-emerald-600" /> 4. Quyền của khách hàng đối với dữ liệu
                        </h2>
                        <p>
                            Quý khách có toàn quyền xem, chỉnh sửa hoặc yêu cầu xóa dữ liệu cá nhân của mình bất kỳ lúc nào bằng cách truy cập vào
                            trang <strong>Cài đặt tài khoản</strong> hoặc liên hệ trực tiếp với bộ phận chăm sóc khách hàng của FreshFood qua số
                            Hotline: <strong>0329.643.444</strong>.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
