<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { ShieldCheck, Lock, Eye, FileText, ChevronRight } from 'lucide-vue-next';

defineProps<{
    stores?: Array<any>;
}>();
</script>

<template>
    <StoreLayout title="Chính Sách Bảo Mật Thông Tin" :stores="stores">
        <!-- BREADCRUMB -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-5xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Chính sách bảo mật</span>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 py-10">
            <div class="bg-white rounded-3xl p-6 sm:p-10 border border-gray-100 shadow-sm space-y-8">
                <!-- HEADER -->
                <div class="border-b border-gray-100 pb-6">
                    <div class="inline-flex items-center space-x-2 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold mb-3">
                        <ShieldCheck class="w-4 h-4" />
                        <span>FreshFood Privacy Policy</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">
                        Chính Sách Bảo Mật Thông Tin
                    </h1>
                    <p class="text-xs text-gray-500 mt-2">
                        Cập nhật lần cuối: Tháng 11/2026 • Áp dụng cho toàn bộ nền tảng thương mại điện tử FreshFood
                    </p>
                </div>

                <!-- CONTENT SECTIONS -->
                <div class="space-y-6 text-xs sm:text-sm text-gray-700 leading-relaxed">
                    <section class="space-y-2">
                        <h2 class="text-base font-bold text-gray-900 flex items-center">
                            <Lock class="w-4 h-4 mr-2 text-emerald-600" /> 1. Mục đích thu thập thông tin cá nhân
                        </h2>
                        <p>
                            FreshFood thu thập thông tin khách hàng (bao gồm họ tên, số điện thoại, địa chỉ nhận hàng, email và thông tin tài khoản Google khi bạn chọn Đăng nhập bằng Google) nhằm các mục đích sau:
                        </p>
                        <ul class="list-disc pl-5 space-y-1 text-gray-600">
                            <li>Xử lý và giao hàng tận nơi siêu tốc trong vòng 2 giờ.</li>
                            <li>Thông báo tiến độ xử lý đơn hàng và tích lũy điểm thưởng thành viên (Loyalty Points).</li>
                            <li>Hỗ trợ đổi trả, bảo hành sản phẩm tươi sống theo cam kết chất lượng.</li>
                            <li>Cá nhân hóa trải nghiệm mua sắm và cung cấp bảng giá tối ưu theo chi nhánh gần nhất.</li>
                        </ul>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-bold text-gray-900 flex items-center">
                            <Eye class="w-4 h-4 mr-2 text-emerald-600" /> 2. Phạm vi sử dụng thông tin
                        </h2>
                        <p>
                            Thông tin thu thập chỉ được sử dụng trong nội bộ chuỗi hệ thống cửa hàng FreshFood và đơn vị vận chuyển giao hàng được ủy quyền. Chúng tôi cam kết <strong>không bán, chia sẻ hoặc trao đổi</strong> thông tin cá nhân của quý khách cho bất kỳ bên thứ ba nào vì mục đích thương mại.
                        </p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-bold text-gray-900 flex items-center">
                            <ShieldCheck class="w-4 h-4 mr-2 text-emerald-600" /> 3. Xác thực tài khoản bên thứ ba (Google OAuth)
                        </h2>
                        <p>
                            Khi quý khách lựa chọn hình thức <em>"Đăng nhập bằng Google"</em>, FreshFood chỉ yêu cầu các quyền thông tin cơ bản công khai gồm: Tên hiển thị, Địa chỉ Email và Ảnh đại diện (Avatar). FreshFood không yêu cầu hay lưu trữ mật khẩu tài khoản Google của quý khách.
                        </p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-bold text-gray-900 flex items-center">
                            <FileText class="w-4 h-4 mr-2 text-emerald-600" /> 4. Quyền của khách hàng đối với dữ liệu
                        </h2>
                        <p>
                            Quý khách có toàn quyền xem, chỉnh sửa hoặc yêu cầu xóa dữ liệu cá nhân của mình bất kỳ lúc nào bằng cách truy cập vào trang <strong>Cài đặt tài khoản</strong> hoặc liên hệ trực tiếp với bộ phận chăm sóc khách hàng của FreshFood qua số Hotline: <strong>0329.643.444</strong>.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

