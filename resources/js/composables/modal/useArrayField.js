/**
 * Forma ichidagi massiv maydonini boshqarish
 * Subtasks, items, hashtags va h.k. uchun
 */

import { ref, computed } from 'vue';

export function useArrayField(form, field) {
    const newItem = ref('');

    const addItem = (item = null) => {
        if (!form.value[field]) {
            form.value[field] = [];
        }

        const itemToAdd = item || (newItem.value.trim() ? { title: newItem.value.trim() } : null);
        if (itemToAdd) {
            form.value[field].push(itemToAdd);
            newItem.value = '';
        }
    };

    const removeItem = (index) => {
        if (form.value[field] && index >= 0 && index < form.value[field].length) {
            form.value[field].splice(index, 1);
        }
    };

    const updateItem = (index, value) => {
        if (form.value[field] && index >= 0 && index < form.value[field].length) {
            form.value[field][index] = value;
        }
    };

    const itemCount = computed(() => form.value[field]?.length || 0);

    return { newItem, addItem, removeItem, updateItem, itemCount };
}
