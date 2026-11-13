import { ref } from 'vue';

export interface ToastItem {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title?: string;
    message: string;
    duration?: number;
}

const toasts = ref<ToastItem[]>([]);

export function useToast() {
    function addToast(item: Omit<ToastItem, 'id'>) {
        const id = Math.random().toString(36).substring(2, 9);
        const toast: ToastItem = {
            id,
            duration: 3500,
            ...item,
        };

        toasts.value.push(toast);

        if (toast.duration && toast.duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, toast.duration);
        }

        return id;
    }

    function removeToast(id: string) {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }

    function success(message: string, title: string = 'Thành công') {
        return addToast({ type: 'success', title, message });
    }

    function error(message: string, title: string = 'Lỗi') {
        return addToast({ type: 'error', title, message });
    }

    function warning(message: string, title: string = 'Cảnh báo') {
        return addToast({ type: 'warning', title, message });
    }

    function info(message: string, title: string = 'Thông báo') {
        return addToast({ type: 'info', title, message });
    }

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        warning,
        info,
    };
}

