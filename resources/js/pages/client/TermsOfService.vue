<script setup lang="ts">
import StoreLayout from '@/layouts/StoreLayout.vue';
import { Link } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, ChevronRight, Scale, ShoppingBag } from 'lucide-vue-next';

defineProps<{
    stores?: Array<any>;
}>();
</script>

<template>
    <StoreLayout title="Điều Khoản Dịch Vụ & Mua Hàng" :stores="stores">
        <!-- BREADCRUMB -->
        <div class="border-b border-gray-100 bg-white py-3">
            <div class="mx-auto flex max-w-5xl items-center space-x-2 px-4 text-xs text-gray-500">
                <Link :href="route('home')" class="transition-colors hover:text-emerald-600">Trang chủ</Link>
                <ChevronRight class="h-3.5 w-3.5 text-gray-400" />
                <span class="font-semibold text-gray-900">Điều khoản dịch vụ</span>
            </div>
        </div>

        <div class="mx-auto max-w-4xl px-4 py-10">
            <div class="space-y-8 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-10">
                <!-- HEADER -->
                <div class="border-b border-gray-100 pb-6">
                    <div class="mb-3 inline-flex items-center space-x-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                        <Scale class="h-4 w-4" />
                        <span>FreshFood Terms of Service</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl">Điều Khoản Dịch Vụ & Mua Hàng</h1>
                    <p class="mt-2 text-xs text-gray-500">Quy định mua sắm, giao nhận và cam kết chất lượng thực phẩm tại hệ thống FreshFood</p>
                </div>

                <!-- CONTENT SECTIONS -->
                <div class="space-y-6 text-xs leading-relaxed text-gray-700 sm:text-sm">
                    <section class="space-y-2">
                        <h2 class="flex items-center text-base font-bold text-gray-900">
                            <ShoppingBag class="mr-2 h-4 w-4 text-emerald-600" /> 1. Đặt hàng và Giá bán
                        </h2>
                        <p>
                            Giá niêm yết trên website là giá đã bao gồm các loại thuế phí theo quy định hiện hành. Mỗi chi nhánh cửa hàng có thể có
                            bảng giá và tình trạng tồn kho khác nhau tùy thuộc vào vị trí thực tế của khách hàng.
                        </p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="flex items-center text-base font-bold text-gray-900">
                            <CheckCircle2 class="mr-2 h-4 w-4 text-emerald-600" /> 2. Cam kết chất lượng & Date tươi mới (FEFO)
                        </h2>
                        <p>
                            FreshFood áp dụng quy trình kiểm soát hạn sử dụng nghiêm ngặt theo nguyên tắc
                            <strong>FEFO (First Expired, First Out)</strong>. Toàn bộ thực phẩm rau củ, thịt cá đều được bảo quản ở dải nhiệt độ tiêu
                            chuẩn (0-4°C hoặc đông sâu -18°C) và cam kết giao tới tay khách hàng với độ tươi ngon cao nhất.
                        </p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="flex items-center text-base font-bold text-gray-900">
                            <AlertCircle class="mr-2 h-4 w-4 text-emerald-600" /> 3. Chính sách đổi trả trong 24 giờ
                        </h2>
                        <p>
                            Khách hàng có quyền yêu cầu đổi trả hoặc hoàn tiền 100% trong vòng 24 giờ kể từ khi nhận hàng đối với các trường hợp: rau
                            dập nát do vận chuyển, hàng không đúng chủng loại đã đặt, hoặc sản phẩm có dấu hiệu kém tươi mới.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>
