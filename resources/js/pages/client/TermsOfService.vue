<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Scale, CheckCircle2, AlertCircle, ShoppingBag, ChevronRight } from 'lucide-vue-next';

defineProps<{
    stores?: Array<any>;
}>();
</script>

<template>
    <StoreLayout title="Điều Khoản Dịch Vụ & Mua Hàng" :stores="stores">
        <!-- BREADCRUMB -->
        <div class="bg-white border-b border-gray-100 py-3">
            <div class="max-w-5xl mx-auto px-4 flex items-center space-x-2 text-xs text-gray-500">
                <Link :href="route('home')" class="hover:text-emerald-600 transition-colors">Trang chủ</Link>
                <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-900 font-semibold">Điều khoản dịch vụ</span>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 py-10">
            <div class="bg-white rounded-3xl p-6 sm:p-10 border border-gray-100 shadow-sm space-y-8">
                <!-- HEADER -->
                <div class="border-b border-gray-100 pb-6">
                    <div class="inline-flex items-center space-x-2 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold mb-3">
                        <Scale class="w-4 h-4" />
                        <span>FreshFood Terms of Service</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">
                        Điều Khoản Dịch Vụ & Mua Hàng
                    </h1>
                    <p class="text-xs text-gray-500 mt-2">
                        Quy định mua sắm, giao nhận và cam kết chất lượng thực phẩm tại hệ thống FreshFood
                    </p>
                </div>

                <!-- CONTENT SECTIONS -->
                <div class="space-y-6 text-xs sm:text-sm text-gray-700 leading-relaxed">
                    <section class="space-y-2">
                        <h2 class="text-base font-bold text-gray-900 flex items-center">
                            <ShoppingBag class="w-4 h-4 mr-2 text-emerald-600" /> 1. Đặt hàng và Giá bán
                        </h2>
                        <p>
                            Giá niêm yết trên website là giá đã bao gồm các loại thuế phí theo quy định hiện hành. Mỗi chi nhánh cửa hàng có thể có bảng giá và tình trạng tồn kho khác nhau tùy thuộc vào vị trí thực tế của khách hàng.
                        </p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-bold text-gray-900 flex items-center">
                            <CheckCircle2 class="w-4 h-4 mr-2 text-emerald-600" /> 2. Cam kết chất lượng & Date tươi mới (FEFO)
                        </h2>
                        <p>
                            FreshFood áp dụng quy trình kiểm soát hạn sử dụng nghiêm ngặt theo nguyên tắc <strong>FEFO (First Expired, First Out)</strong>. Toàn bộ thực phẩm rau củ, thịt cá đều được bảo quản ở dải nhiệt độ tiêu chuẩn (0-4°C hoặc đông sâu -18°C) và cam kết giao tới tay khách hàng với độ tươi ngon cao nhất.
                        </p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-bold text-gray-900 flex items-center">
                            <AlertCircle class="w-4 h-4 mr-2 text-emerald-600" /> 3. Chính sách đổi trả trong 24 giờ
                        </h2>
                        <p>
                            Khách hàng có quyền yêu cầu đổi trả hoặc hoàn tiền 100% trong vòng 24 giờ kể từ khi nhận hàng đối với các trường hợp: rau dập nát do vận chuyển, hàng không đúng chủng loại đã đặt, hoặc sản phẩm có dấu hiệu kém tươi mới.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

