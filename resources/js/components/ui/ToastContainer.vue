<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import {
    CheckCircle2,
    AlertCircle,
    AlertTriangle,
    Info,
    X,
} from 'lucide-vue-next';

const { toasts, removeToast } = useToast();

const iconMap = {
    success: CheckCircle2,
    error: AlertCircle,
    warning: AlertTriangle,
    info: Info,
};

const bgMap = {
    success: 'bg-emerald-50 border-emerald-200 text-emerald-900',
    error: 'bg-red-50 border-red-200 text-red-900',
    warning: 'bg-amber-50 border-amber-200 text-amber-900',
    info: 'bg-blue-50 border-blue-200 text-blue-900',
};

const iconColorMap = {
    success: 'text-emerald-600',
    error: 'text-red-600',
    warning: 'text-amber-600',
    info: 'text-blue-600',
};
</script>

<template>
    <div
        aria-live="assertive"
        class="fixed top-4 right-4 z-[9999] flex flex-col space-y-2.5 max-w-sm w-full pointer-events-none px-3 sm:px-0"
    >
        <TransitionGroup
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="[bgMap[toast.type]]"
                class="pointer-events-auto p-3.5 rounded-2xl border shadow-xl flex items-start space-x-3 backdrop-blur-md"
            >
                <component
                    :is="iconMap[toast.type]"
                    :class="[iconColorMap[toast.type]]"
                    class="w-5 h-5 flex-shrink-0 mt-0.5"
                />

                <div class="flex-1 min-w-0">
                    <p v-if="toast.title" class="text-xs font-black leading-snug">
                        {{ toast.title }}
                    </p>
                    <p class="text-xs font-medium leading-relaxed mt-0.5 text-slate-700">
                        {{ toast.message }}
                    </p>
                </div>

                <button
                    @click="removeToast(toast.id)"
                    class="text-slate-400 hover:text-slate-600 p-1 -mr-1 -mt-1 rounded-lg transition"
                >
                    <X class="w-3.5 h-3.5" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

