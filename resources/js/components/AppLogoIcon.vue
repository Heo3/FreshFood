<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img src="/logo.png" alt="Fresh Food Logo" :class="['rounded-lg object-contain', className]" v-bind="$attrs" />
</template>
