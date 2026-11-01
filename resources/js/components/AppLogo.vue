<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-emerald-950 p-0.5 shadow-sm">
        <AppLogoIcon class="size-7 object-contain" />
    </div>
    <div class="ml-1.5 grid flex-1 text-left text-sm leading-tight">
        <span class="mb-0.5 truncate font-extrabold text-emerald-800 dark:text-emerald-400">Fresh Food</span>
        <span class="truncate text-[10px] text-muted-foreground font-medium">Tươi Sạch Mỗi Ngày</span>
    </div>
</template>
