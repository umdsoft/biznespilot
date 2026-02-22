<template>
    <button
        @click="goToItem"
        class="overflow-hidden rounded-xl text-left"
        style="background-color: var(--tg-theme-secondary-bg-color)"
    >
        <div class="relative aspect-square w-full overflow-hidden">
            <img
                v-if="item.image"
                :src="item.image"
                :alt="item.name"
                class="h-full w-full object-cover"
                loading="lazy"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center text-3xl"
                style="background-color: var(--tg-theme-bg-color)"
            >
                💆
            </div>

            <!-- Duration badge -->
            <div
                v-if="item.duration_minutes"
                class="absolute top-2 right-2 rounded-md px-1.5 py-0.5 text-[10px] font-bold text-white bg-blue-500"
            >
                {{ item.duration_minutes }} min
            </div>
        </div>

        <div class="p-2.5">
            <p
                class="line-clamp-2 text-xs font-medium leading-tight"
                style="color: var(--tg-theme-text-color)"
            >
                {{ item.name }}
            </p>

            <div class="mt-1.5 flex items-center gap-1.5">
                <span class="text-sm font-bold" style="color: var(--tg-theme-text-color)">
                    {{ formatPrice(item.price) }}
                </span>
            </div>
        </div>
    </button>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useTelegram } from '../composables/useTelegram'

const props = defineProps({
    item: { type: Object, required: true },
})

const router = useRouter()
const { hapticImpact } = useTelegram()

function formatPrice(price) {
    if (!price) return "0 so'm"
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + " so'm"
}

function goToItem() {
    hapticImpact('light')
    router.push({ name: 'catalog-item', params: { slug: props.item.slug } })
}
</script>
