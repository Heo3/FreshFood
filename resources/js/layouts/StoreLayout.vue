<script setup lang="ts">
import MobileBottomNav from '@/components/storefront/MobileBottomNav.vue';
import StoreFooter from '@/components/storefront/StoreFooter.vue';
import StoreHeader from '@/components/storefront/StoreHeader.vue';
import FloatingChatWidget from '@/components/chat/FloatingChatWidget.vue';
import ToastContainer from '@/components/ui/ToastContainer.vue';
import { useCart } from '@/composables/useCart';
import { Head, usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Info } from 'lucide-vue-next';
import { onMounted, watch } from 'vue';

defineProps<{
    title?: string;
    stores?: Array<any>;
}>();

const page = usePage();
const { toast, syncWithServer, showToast } = useCart();

watch(
    () => (page.props as any).flash,
    (flash: any) => {
        if (flash?.success) {
            showToast(flash.success, 'success');
        } else if (flash?.error) {
            showToast(flash.error, 'error');
        } else if (flash?.info) {
            showToast(flash.info, 'info');
        }
    },
    { immediate: true, deep: true },
);

onMounted(() => {
    if (page.props.serverCart) {
        syncWithServer(page.props.serverCart as any[]);
    }
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-neutral-50/50 font-sans text-gray-900 antialiased selection:bg-emerald-500 selection:text-white">
        <Head :title="title ? `${title} - Fresh Food Thực Phẩm Tươi Sạch` : 'Fresh Food - Chuỗi Cửa Hàng Thực Phẩm Tươi Sống Hàng Đầu'" />

        <!-- HEADER -->
        <StoreHeader :stores="stores" />

        <!-- MAIN BODY CONTENT -->
        <main class="flex-1 pb-20 lg:pb-0">
            <slot />
        </main>

        <!-- FOOTER -->
        <StoreFooter />

        <!-- MOBILE BOTTOM NAVIGATION BAR -->
        <MobileBottomNav />

        <!-- TOAST NOTIFICATION POPUP -->
        <div
            v-if="toast.show"
            class="fixed bottom-6 right-6 z-50 flex items-center space-x-2.5 rounded-2xl border border-gray-700 bg-gray-900/95 px-4 py-3 text-white shadow-2xl backdrop-blur-md duration-200 animate-in fade-in slide-in-from-bottom-4"
        >
            <CheckCircle2 v-if="toast.type === 'success'" class="h-5 w-5 shrink-0 text-emerald-400" />
            <AlertCircle v-else-if="toast.type === 'error'" class="h-5 w-5 shrink-0 text-red-400" />
            <Info v-else class="h-5 w-5 shrink-0 text-sky-400" />

            <span class="text-xs font-semibold tracking-wide">{{ toast.message }}</span>
        </div>

        <!-- GLOBAL TOAST NOTIFICATION CONTAINER -->
        <ToastContainer />

        <!-- SMART AI & CSKH FLOATING CHAT WIDGET -->
        <FloatingChatWidget />
    </div>
</template>
