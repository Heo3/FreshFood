<script setup lang="ts">
import { onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import StoreHeader from '@/components/storefront/StoreHeader.vue';
import StoreFooter from '@/components/storefront/StoreFooter.vue';
import { useCart } from '@/composables/useCart';
import { CheckCircle2, AlertCircle, Info } from 'lucide-vue-next';

defineProps<{
    title?: string;
    stores?: Array<any>;
}>();

const page = usePage();
const { toast, syncWithServer } = useCart();

onMounted(() => {
    if (page.props.serverCart) {
        syncWithServer(page.props.serverCart as any[]);
    }
});
</script>

<template>
    <div class="min-h-screen bg-neutral-50/50 flex flex-col font-sans text-gray-900 antialiased selection:bg-emerald-500 selection:text-white">
        <Head :title="title ? `${title} - Fresh Food Thực Phẩm Tươi Sạch` : 'Fresh Food - Chuỗi Cửa Hàng Thực Phẩm Tươi Sống Hàng Đầu'" />

        <!-- HEADER -->
        <StoreHeader :stores="stores" />

        <!-- MAIN BODY CONTENT -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- FOOTER -->
        <StoreFooter />

        <!-- TOAST NOTIFICATION POPUP -->
        <div
            v-if="toast.show"
            class="fixed bottom-6 right-6 z-50 flex items-center space-x-2.5 bg-gray-900/95 backdrop-blur-md text-white px-4 py-3 rounded-2xl shadow-2xl border border-gray-700 animate-in fade-in slide-in-from-bottom-4 duration-200"
        >
            <CheckCircle2 v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-400 shrink-0" />
            <AlertCircle v-else-if="toast.type === 'error'" class="w-5 h-5 text-red-400 shrink-0" />
            <Info v-else class="w-5 h-5 text-sky-400 shrink-0" />

            <span class="text-xs font-semibold tracking-wide">{{ toast.message }}</span>
        </div>
    </div>
</template>
