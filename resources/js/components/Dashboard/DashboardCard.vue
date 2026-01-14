<template>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div v-if="title || $slots.header" class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <slot name="header">
                <h3 class="font-semibold text-gray-900">{{ title }}</h3>
            </slot>
            <slot name="action"></slot>
        </div>
        <div :class="bodyClass">
            <slot></slot>
        </div>
        <div v-if="$slots.footer" class="px-5 py-3 bg-gray-50 border-t border-gray-100">
            <slot name="footer"></slot>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    padding: {
        type: Boolean,
        default: true,
    },
});

const bodyClass = computed(() => {
    return props.padding ? 'p-5' : '';
});
</script>
