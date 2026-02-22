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
                🍔
            </div>

            <div
                v-if="item.preparation_time_minutes"
                class="absolute top-2 right-2 rounded-md px-1.5 py-0.5 text-[10px] font-bold text-white bg-orange-500"
            >
                ~{{ item.preparation_time_minutes }} min
            </div>
        </div>

        <div class="p-2.5">
            <p
                class="line-clamp-2 text-xs font-medium leading-tight"
                style="color: var(--tg-theme-text-color)"
            >
                {{ item.name }}
            </p>

            <div class="mt-1 flex flex-wrap gap-1" v-if="item.dietary_tags?.length">
                <span
                    v-for="tag in item.dietary_tags.slice(0, 2)"
                    :key="tag"
                    class="rounded px-1 py-0.5 text-[9px]"
                    style="background-color: var(--tg-theme-bg-color); color: var(--tg-theme-hint-color)"
                >
                    {{ tag }}
                </span>
            </div>

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
