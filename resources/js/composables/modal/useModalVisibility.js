/**
 * Modal ko'rinishini boshqarish
 * Ochish/yopish, Esc tugmasi, edit item populate
 */

import { watch, onUnmounted } from 'vue';

export function useModalVisibility(props, emit, formInstance = null, options = {}) {
    const { onOpen = null, onClose = null, editItem = null } = options;

    const close = () => {
        emit('close');
        if (onClose) onClose();
    };

    const handleKeydown = (e) => {
        if (e.key === 'Escape' && props.show) {
            close();
        }
    };

    watch(() => props.show, (isOpen) => {
        if (isOpen) {
            if (formInstance) {
                if (editItem?.value) {
                    formInstance.populateForm(editItem.value);
                } else {
                    formInstance.resetForm();
                }
            }
            if (onOpen) onOpen();
            document.addEventListener('keydown', handleKeydown);
        } else {
            document.removeEventListener('keydown', handleKeydown);
        }
    });

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeydown);
    });

    return { close };
}
