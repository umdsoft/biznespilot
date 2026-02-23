<template>
    <div :style="gridStyle">
        <div v-for="i in count" :key="i">
            <!-- Product card skeleton -->
            <div v-if="type === 'card'" class="overflow-hidden rounded-xl" :style="{ backgroundColor: 'var(--tg-theme-secondary-bg-color)' }">
                <div class="skeleton aspect-square w-full" style="border-radius: 0"></div>
                <div class="px-2.5 py-2 space-y-2">
                    <div class="skeleton h-3.5 w-3/4"></div>
                    <div class="skeleton h-4 w-1/2"></div>
                </div>
            </div>

            <!-- Category chip skeleton -->
            <div v-else-if="type === 'category'" class="flex flex-col items-center gap-2">
                <div class="skeleton h-14 w-14 rounded-2xl"></div>
                <div class="skeleton h-2.5 w-10"></div>
            </div>

            <!-- Text line skeleton -->
            <div v-else-if="type === 'text'" class="skeleton h-3.5 rounded" :style="{ width: textWidths[i % textWidths.length] }"></div>

            <!-- Banner skeleton -->
            <div v-else-if="type === 'banner'" class="skeleton w-full rounded-2xl" style="aspect-ratio: 2/1"></div>

            <!-- Order card skeleton -->
            <div v-else-if="type === 'order'" class="rounded-2xl p-3.5 space-y-2.5" :style="{ backgroundColor: 'var(--tg-theme-secondary-bg-color)' }">
                <div class="flex justify-between">
                    <div class="skeleton h-4 w-24"></div>
                    <div class="skeleton h-4 w-16"></div>
                </div>
                <div class="skeleton h-3.5 w-32"></div>
                <div class="flex gap-1.5">
                    <div class="skeleton h-7 w-7 rounded-lg" v-for="j in 3" :key="j"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: { type: String, default: 'card' },   // card | category | text | banner | order
    count: { type: Number, default: 4 },
})

const textWidths = ['75%', '60%', '85%', '50%', '70%']

const gridStyle = computed(() => {
    if (props.type === 'card') return { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '10px' }
    if (props.type === 'category') return { display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '16px' }
    if (props.type === 'text') return { display: 'grid', gap: '8px' }
    if (props.type === 'order') return { display: 'grid', gap: '10px' }
    return { display: 'grid', gap: '10px' }
})
</script>
