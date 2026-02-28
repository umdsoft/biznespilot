<template>
    <span
        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
        :style="{ backgroundColor: bgColor, color: color }"
    >
        <component :is="iconComponent" class="w-3.5 h-3.5" />
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue'
import {
    ShoppingBagIcon,
    TruckIcon,
    CalendarDaysIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    type: { type: String, default: null },
    label: { type: String, default: '' },
    color: { type: String, default: '#6B7280' },
    bgColor: { type: String, default: '#F3F4F6' },
    icon: { type: String, default: null },
})

const iconMap = {
    ShoppingBagIcon,
    TruckIcon,
    CalendarDaysIcon,
    WrenchScrewdriverIcon,
}

const iconComponent = computed(() => {
    return props.icon ? (iconMap[props.icon] || null) : null
})
</script>
