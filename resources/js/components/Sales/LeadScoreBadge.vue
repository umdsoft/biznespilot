<script setup>
import { computed } from 'vue'
import { FireIcon, SunIcon, CloudIcon, SparklesIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    score: {
        type: Number,
        default: 0,
    },
    category: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
    },
    showIcon: {
        type: Boolean,
        default: true,
    },
    showLabel: {
        type: Boolean,
        default: false,
    },
})

const categoryInfo = computed(() => {
    const categories = {
        hot: { name: 'Issiq', color: '#ef4444', bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', icon: FireIcon },
        warm: { name: 'Iliq', color: '#f97316', bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-700 dark:text-orange-400', icon: SunIcon },
        cool: { name: 'Salqin', color: '#eab308', bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-700 dark:text-yellow-400', icon: SparklesIcon },
        cold: { name: 'Sovuq', color: '#3b82f6', bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-700 dark:text-blue-400', icon: CloudIcon },
        frozen: { name: 'Muzlagan', color: '#6b7280', bg: 'bg-gray-100 dark:bg-gray-700', text: 'text-gray-700 dark:text-gray-400', icon: SparklesIcon },
    }

    // Kategoriya berilgan bo'lsa, uni ishlatish
    if (props.category && categories[props.category]) {
        return categories[props.category]
    }

    // Score asosida kategoriyani aniqlash
    const score = props.score || 0
    if (score >= 80) return categories.hot
    if (score >= 60) return categories.warm
    if (score >= 40) return categories.cool
    if (score >= 20) return categories.cold
    return categories.frozen
})

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-1.5 py-0.5 text-xs',
        md: 'px-2 py-1 text-sm',
        lg: 'px-3 py-1.5 text-base',
    }
    return sizes[props.size] || sizes.md
})

const iconSizeClasses = computed(() => {
    const sizes = {
        sm: 'w-3 h-3',
        md: 'w-4 h-4',
        lg: 'w-5 h-5',
    }
    return sizes[props.size] || sizes.md
})
</script>

<template>
    <span
        class="inline-flex items-center font-semibold rounded-full"
        :class="[categoryInfo.bg, categoryInfo.text, sizeClasses]"
        :title="categoryInfo.name"
    >
        <component
            v-if="showIcon"
            :is="categoryInfo.icon"
            :class="[iconSizeClasses, showLabel || score !== undefined ? 'mr-1' : '']"
        />
        <span v-if="score !== undefined && score !== null">{{ score }}</span>
        <span v-if="showLabel" class="ml-1">{{ categoryInfo.name }}</span>
    </span>
</template>
