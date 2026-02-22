<template>
    <button
        @click="goToProduct"
        class="overflow-hidden rounded-xl text-left"
        style="background-color: var(--tg-theme-secondary-bg-color)"
    >
        <!-- Image -->
        <div class="relative aspect-square w-full overflow-hidden">
            <img
                v-if="product.image || product.images?.[0]"
                :src="product.image || product.images[0]"
                :alt="product.name"
                class="h-full w-full object-cover"
                loading="lazy"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center text-3xl"
                style="background-color: var(--tg-theme-bg-color)"
            >
                📦
            </div>

            <!-- Discount badge -->
            <div
                v-if="discountPercent > 0"
                class="absolute top-2 left-2 rounded-md px-1.5 py-0.5 text-[10px] font-bold text-white"
                style="background-color: #ef4444"
            >
                -{{ discountPercent }}%
            </div>

            <!-- Out of stock overlay -->
            <div
                v-if="product.stock === 0"
                class="absolute inset-0 flex items-center justify-center bg-black/40"
            >
                <span class="rounded-lg bg-white/90 px-2 py-1 text-xs font-semibold text-gray-800">
                    Tugagan
                </span>
            </div>
        </div>

        <!-- Info -->
        <div class="p-2.5">
            <p
                class="line-clamp-2 text-xs font-medium leading-tight"
                style="color: var(--tg-theme-text-color)"
            >
                {{ product.name }}
            </p>

            <div class="mt-1.5 flex items-center gap-1.5">
                <span class="text-sm font-bold" style="color: var(--tg-theme-text-color)">
                    {{ formatPrice(effectivePrice) }}
                </span>
                <span
                    v-if="product.sale_price"
                    class="text-[10px] line-through"
                    style="color: var(--tg-theme-hint-color)"
                >
                    {{ formatPrice(product.price) }}
                </span>
            </div>
        </div>
    </button>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTelegram } from '../composables/useTelegram'

const props = defineProps({
    product: { type: Object, required: true },
})

const router = useRouter()
const { hapticImpact } = useTelegram()

const effectivePrice = computed(() => {
    return props.product.sale_price || props.product.price
})

const discountPercent = computed(() => {
    if (!props.product.sale_price || !props.product.price) return 0
    return Math.round((1 - props.product.sale_price / props.product.price) * 100)
})

function formatPrice(price) {
    if (!price) return "0 so'm"
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + " so'm"
}

function goToProduct() {
    hapticImpact('light')
    router.push({ name: 'product', params: { slug: props.product.slug } })
}
</script>
