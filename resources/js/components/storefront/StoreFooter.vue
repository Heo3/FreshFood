<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Award, Clock, Mail, MapPin, PhoneCall, RefreshCw, ShieldCheck, Truck } from 'lucide-vue-next';
</script>

<template>
    <footer class="border-t border-gray-800 bg-gray-900 pb-8 pt-14 text-gray-300">
        <!-- TRUST COMMITMENT STRIP -->
        <div class="mx-auto max-w-7xl border-b border-gray-800 px-4 pb-12">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center space-x-4 rounded-2xl border border-gray-700/50 bg-gray-800/60 p-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400">
                        <ShieldCheck class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">100% Tươi Sạch Hữu Cơ</h4>
                        <p class="mt-0.5 text-xs text-gray-400">Tiêu chuẩn VietGAP & Organic</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 rounded-2xl border border-gray-700/50 bg-gray-800/60 p-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-500/20 text-teal-400">
                        <Truck class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Giao Hàng Siêu Tốc 2H</h4>
                        <p class="mt-0.5 text-xs text-gray-400">Thùng giữ nhiệt lạnh chuyên dụng</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 rounded-2xl border border-gray-700/50 bg-gray-800/60 p-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400">
                        <RefreshCw class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Đổi Trả Trong 24 Giờ</h4>
                        <p class="mt-0.5 text-xs text-gray-400">Đổi mới nếu dập nát, hỏng hóc</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 rounded-2xl border border-gray-700/50 bg-gray-800/60 p-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-rose-500/20 text-rose-400">
                        <Award class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Tích Điểm Thành Viên</h4>
                        <p class="mt-0.5 text-xs text-gray-400">Hoàn tiền 5-10% cho mỗi đơn hàng</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN FOOTER LINKS -->
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 py-12 md:grid-cols-2 lg:grid-cols-4">
            <!-- BRAND INFO -->
            <div>
                <div class="mb-4 flex items-center space-x-3">
                    <img src="/images/logo.png" alt="Fresh Food" class="h-9 w-auto rounded-lg object-contain bg-white p-0.5" />
                    <span class="text-xl font-black text-white">Fresh<span class="text-emerald-400">Food</span></span>
                </div>
                <p class="mb-4 text-xs leading-relaxed text-gray-400">
                    Hệ thống bán lẻ chuỗi cửa hàng thực phẩm tươi sống, hữu cơ số 1. Quản lý hạn sử dụng theo nguyên tắc FEFO, đảm bảo từng bữa ăn gia
                    đình luôn trọn vẹn dinh dưỡng và an toàn tuyệt đối.
                </p>
                <div class="space-y-2 text-xs text-gray-400">
                    <p class="flex items-center"><PhoneCall class="mr-2 h-3.5 w-3.5 text-emerald-400" /> Hotline: 1900 6868 - 028 3822 1199</p>
                    <p class="flex items-center"><Mail class="mr-2 h-3.5 w-3.5 text-emerald-400" /> CSKH: support@freshfood.vn</p>
                    <p class="flex items-center"><Clock class="mr-2 h-3.5 w-3.5 text-emerald-400" /> Giờ phục vụ: 06:00 - 22:00 hàng ngày</p>
                </div>
            </div>

            <!-- CHUỖI CỬA HÀNG -->
            <div>
                <h4 class="mb-4 flex items-center text-sm font-bold uppercase tracking-wider text-white">
                    <MapPin class="mr-1.5 h-4 w-4 text-emerald-400" /> Chuỗi Chi Nhánh
                </h4>
                <ul class="space-y-3 text-xs text-gray-400">
                    <li class="border-b border-gray-800 pb-2">
                        <strong class="text-gray-200">Chi nhánh 1 (Quận 1):</strong>
                        <p class="mt-0.5">128 Hai Bà Trưng, P. Đa Kao, Quận 1, TP.HCM</p>
                    </li>
                    <li class="border-b border-gray-800 pb-2">
                        <strong class="text-gray-200">Chi nhánh 2 (Quận 7):</strong>
                        <p class="mt-0.5">85 Nguyễn Đức Cảnh, P. Tân Phong, Quận 7, TP.HCM</p>
                    </li>
                    <li class="border-b border-gray-800 pb-2">
                        <strong class="text-gray-200">Chi nhánh 3 (Bình Thạnh):</strong>
                        <p class="mt-0.5">208 Nguyễn Hữu Cảnh, P. 22, Bình Thạnh, TP.HCM</p>
                    </li>
                    <li>
                        <strong class="text-gray-200">Chi nhánh 4 (Hà Nội):</strong>
                        <p class="mt-0.5">72 Trần Thái Tông, Cầu Giấy, Hà Nội</p>
                    </li>
                </ul>
            </div>

            <!-- DANH MỤC THỰC PHẨM -->
            <div>
                <h4 class="mb-4 text-sm font-bold uppercase tracking-wider text-white">Danh Mục Nổi Bật</h4>
                <ul class="space-y-2 text-xs">
                    <li>
                        <Link :href="route('categories.show', { slug: 'rau-cu-huu-co' })" class="transition-colors hover:text-emerald-400"
                            >Rau củ quả hữu cơ Đà Lạt</Link
                        >
                    </li>
                    <li>
                        <Link :href="route('categories.show', { slug: 'trai-cay-tuoi-ngon' })" class="transition-colors hover:text-emerald-400"
                            >Trái cây tươi & Trái cây nhập khẩu</Link
                        >
                    </li>
                    <li>
                        <Link :href="route('categories.show', { slug: 'thit-tuoi-trung-sach' })" class="transition-colors hover:text-emerald-400"
                            >Thịt heo VietGAP, bò Úc, gà thả vườn</Link
                        >
                    </li>
                    <li>
                        <Link :href="route('categories.show', { slug: 'thuy-hai-san-tuoi-song' })" class="transition-colors hover:text-emerald-400"
                            >Cá hồi Nauy tươi phi lê sashimi</Link
                        >
                    </li>
                    <li>
                        <Link :href="route('categories.show', { slug: 'sua-bo-thuc-pham-che-bien' })" class="transition-colors hover:text-emerald-400"
                            >Sữa tươi thanh trùng Dalatmilk</Link
                        >
                    </li>
                    <li>
                        <Link :href="route('categories.show', { slug: 'gia-vi-nong-san-kho' })" class="transition-colors hover:text-emerald-400"
                            >Gạo ST25 chuẩn ngon nhất thế giới</Link
                        >
                    </li>
                </ul>
            </div>

            <!-- CHÍNH SÁCH & PHƯƠNG THỨC THANH TOÁN -->
            <div>
                <h4 class="mb-4 text-sm font-bold uppercase tracking-wider text-white">Chính Sách & Hỗ Trợ</h4>
                <ul class="mb-6 space-y-2 text-xs">
                    <li>
                        <Link :href="route('orders.tracking')" class="transition-colors hover:text-emerald-400">Kiểm tra & Theo dõi đơn hàng</Link>
                    </li>
                    <li><Link :href="route('privacy')" class="transition-colors hover:text-emerald-400">Chính sách bảo mật thông tin</Link></li>
                    <li><Link :href="route('terms')" class="transition-colors hover:text-emerald-400">Điều khoản dịch vụ & Đổi trả</Link></li>
                    <li><Link :href="route('terms')" class="transition-colors hover:text-emerald-400">Chính sách giao hàng siêu tốc 2h</Link></li>
                    <li>
                        <Link :href="route('privacy')" class="transition-colors hover:text-emerald-400">Quy trình kiểm định chất lượng ATTP</Link>
                    </li>
                </ul>

                <h5 class="mb-2 text-xs font-bold text-gray-200">Thanh toán tiện lợi & an toàn</h5>
                <div class="flex flex-wrap gap-2 text-[10px] font-bold text-gray-300">
                    <span class="rounded border border-gray-700 bg-gray-800 px-2 py-1">COD Tiền mặt</span>
                    <span class="rounded border border-gray-700 bg-gray-800 px-2 py-1">VietQR</span>
                    <span class="rounded border border-gray-700 bg-gray-800 px-2 py-1">MoMo</span>
                    <span class="rounded border border-gray-700 bg-gray-800 px-2 py-1">VNPay</span>
                </div>
            </div>
        </div>

        <!-- COPYRIGHT -->
        <div
            class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 border-t border-gray-800 px-4 pt-6 text-xs text-gray-500 sm:flex-row"
        >
            <p>© 2026 Fresh Food Inc. Giấy phép ĐKKD số: 0312345678 do Sở KHĐT TP.HCM cấp.</p>
            <p>Hệ thống Quản lý Bán lẻ & Chuỗi Cung ứng Thực phẩm Tươi Sống FEFO</p>
        </div>
    </footer>
</template>
