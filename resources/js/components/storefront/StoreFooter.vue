<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ShieldCheck,
    Truck,
    RefreshCw,
    Award,
    MapPin,
    PhoneCall,
    Mail,
    Clock,
    Sparkles,
} from 'lucide-vue-next';
</script>

<template>
    <footer class="bg-gray-900 text-gray-300 pt-14 pb-8 border-t border-gray-800">
        <!-- TRUST COMMITMENT STRIP -->
        <div class="max-w-7xl mx-auto px-4 pb-12 border-b border-gray-800">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="flex items-center space-x-4 p-4 rounded-2xl bg-gray-800/60 border border-gray-700/50">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <ShieldCheck class="w-6 h-6" />
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">100% Tươi Sạch Hữu Cơ</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Tiêu chuẩn VietGAP & Organic</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 p-4 rounded-2xl bg-gray-800/60 border border-gray-700/50">
                    <div class="w-12 h-12 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center shrink-0">
                        <Truck class="w-6 h-6" />
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">Giao Hàng Siêu Tốc 2H</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Thùng giữ nhiệt lạnh chuyên dụng</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 p-4 rounded-2xl bg-gray-800/60 border border-gray-700/50">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                        <RefreshCw class="w-6 h-6" />
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">Đổi Trả Trong 24 Giờ</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Đổi mới nếu dập nát, hỏng hóc</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 p-4 rounded-2xl bg-gray-800/60 border border-gray-700/50">
                    <div class="w-12 h-12 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                        <Award class="w-6 h-6" />
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">Tích Điểm Thành Viên</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Hoàn tiền 5-10% cho mỗi đơn hàng</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN FOOTER LINKS -->
        <div class="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- BRAND INFO -->
            <div>
                <div class="flex items-center space-x-3 mb-4">
                    <img
                        src="/images/logo.png"
                        alt="Fresh Food"
                        class="h-9 w-auto object-contain rounded-lg"
                    />
                    <span class="text-xl font-black text-white">Fresh<span class="text-emerald-400">Food</span></span>
                </div>
                <p class="text-xs text-gray-400 leading-relaxed mb-4">
                    Hệ thống bán lẻ chuỗi cửa hàng thực phẩm tươi sống, hữu cơ số 1. Quản lý hạn sử dụng theo nguyên tắc FEFO, đảm bảo từng bữa ăn gia đình luôn trọn vẹn dinh dưỡng và an toàn tuyệt đối.
                </p>
                <div class="text-xs text-gray-400 space-y-2">
                    <p class="flex items-center">
                        <PhoneCall class="w-3.5 h-3.5 mr-2 text-emerald-400" /> Hotline: 1900 6868 - 028 3822 1199
                    </p>
                    <p class="flex items-center">
                        <Mail class="w-3.5 h-3.5 mr-2 text-emerald-400" /> CSKH: support@freshfood.vn
                    </p>
                    <p class="flex items-center">
                        <Clock class="w-3.5 h-3.5 mr-2 text-emerald-400" /> Giờ phục vụ: 06:00 - 22:00 hàng ngày
                    </p>
                </div>
            </div>

            <!-- CHUỖI CỬA HÀNG -->
            <div>
                <h4 class="text-white text-sm font-bold uppercase tracking-wider mb-4 flex items-center">
                    <MapPin class="w-4 h-4 mr-1.5 text-emerald-400" /> Chuỗi Chi Nhánh
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
                <h4 class="text-white text-sm font-bold uppercase tracking-wider mb-4">Danh Mục Nổi Bật</h4>
                <ul class="space-y-2 text-xs">
                    <li><Link :href="route('categories.show', { slug: 'rau-cu-huu-co' })" class="hover:text-emerald-400 transition-colors">Rau củ quả hữu cơ Đà Lạt</Link></li>
                    <li><Link :href="route('categories.show', { slug: 'trai-cay-tuoi-ngon' })" class="hover:text-emerald-400 transition-colors">Trái cây tươi & Trái cây nhập khẩu</Link></li>
                    <li><Link :href="route('categories.show', { slug: 'thit-tuoi-trung-sach' })" class="hover:text-emerald-400 transition-colors">Thịt heo VietGAP, bò Úc, gà thả vườn</Link></li>
                    <li><Link :href="route('categories.show', { slug: 'thuy-hai-san-tuoi-song' })" class="hover:text-emerald-400 transition-colors">Cá hồi Nauy tươi phi lê sashimi</Link></li>
                    <li><Link :href="route('categories.show', { slug: 'sua-bo-thuc-pham-che-bien' })" class="hover:text-emerald-400 transition-colors">Sữa tươi thanh trùng Dalatmilk</Link></li>
                    <li><Link :href="route('categories.show', { slug: 'gia-vi-nong-san-kho' })" class="hover:text-emerald-400 transition-colors">Gạo ST25 chuẩn ngon nhất thế giới</Link></li>
                </ul>
            </div>

            <!-- CHÍNH SÁCH & PHƯƠNG THỨC THANH TOÁN -->
            <div>
                <h4 class="text-white text-sm font-bold uppercase tracking-wider mb-4">Chính Sách & Hỗ Trợ</h4>
                <ul class="space-y-2 text-xs mb-6">
                    <li><Link :href="route('orders.tracking')" class="hover:text-emerald-400 transition-colors">Kiểm tra & Theo dõi đơn hàng</Link></li>
                    <li><a href="#" class="hover:text-emerald-400 transition-colors">Chính sách giao hàng siêu tốc 2h</a></li>
                    <li><a href="#" class="hover:text-emerald-400 transition-colors">Quy trình kiểm định chất lượng ATTP</a></li>
                    <li><a href="#" class="hover:text-emerald-400 transition-colors">Chính sách đổi trả & Hoàn tiền 100%</a></li>
                    <li><a href="#" class="hover:text-emerald-400 transition-colors">Quy định tích điểm & Thẻ thành viên</a></li>
                </ul>

                <h5 class="text-xs font-bold text-gray-200 mb-2">Thanh toán tiện lợi & an toàn</h5>
                <div class="flex flex-wrap gap-2 text-[10px] text-gray-300 font-bold">
                    <span class="px-2 py-1 bg-gray-800 rounded border border-gray-700">COD Tiền mặt</span>
                    <span class="px-2 py-1 bg-gray-800 rounded border border-gray-700">VietQR</span>
                    <span class="px-2 py-1 bg-gray-800 rounded border border-gray-700">MoMo</span>
                    <span class="px-2 py-1 bg-gray-800 rounded border border-gray-700">VNPay</span>
                </div>
            </div>
        </div>

        <!-- COPYRIGHT -->
        <div class="max-w-7xl mx-auto px-4 pt-6 border-t border-gray-800 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500 gap-2">
            <p>© 2026 Fresh Food Inc. Giấy phép ĐKKD số: 0312345678 do Sở KHĐT TP.HCM cấp.</p>
            <p>Hệ thống Quản lý Bán lẻ & Chuỗi Cung ứng Thực phẩm Tươi Sống FEFO</p>
        </div>
    </footer>
</template>
