<template>
    <button
        @click="goToProduct"
        class="overflow-hidden rounded-xl text-left tap-active"
        :style="{ backgroundColor: 'var(--tg-theme-secondary-bg-color)', boxShadow: '0 1px 3px rgba(0,0,0,0.08)' }"
    >
        <!-- Image -->
        <div class="relative aspect-square w-full overflow-hidden">
            <!-- Skeleton while loading -->
            <div v-if="!imageLoaded && hasImage" class="skeleton absolute inset-0" style="border-radius: 0"></div>

            <img
                v-if="hasImage"
                :src="product.image || product.images[0]"
                :alt="product.name"
                class="h-full w-full object-cover transition-opacity duration-200"
                :class="imageLoaded ? 'opacity-100' : 'opacity-0'"
                loading="lazy"
                @load="imageLoaded = true"
            />
            <div
                v-if="!hasImage"
                class="flex h-full w-full items-center justify-center text-3xl"
                style="background-color: var(--tg-theme-bg-color)"
            >
                📦
            </div>

            <!-- Discount badge -->
            <div
                v-if="discountPercent > 0"
                class="absolute top-1.5 left-1.5 rounded-md px-1.5 py-0.5 text-[10px] font-bold text-white"
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

            <!-- Quick add button -->
            <button
                v-if="product.stock !== 0 && !isInCart"
                @click.stop="quickAdd"
                class="absolute bottom-2 right-2 flex h-7 w-7 items-center justify-center rounded-full shadow-md tap-active"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>

            <!-- In cart indicator -->
            <button
                v-else-if="product.stock !== 0 && isInCart"
                @click.stop="goToCart"
                class="absolute bottom-2 right-2 flex h-7 w-7 items-center justify-center rounded-full shadow-md"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </button>
        </div>

        <!-- Info -->
        <div class="px-2.5 py-2">
            <p
                class="line-clamp-2 text-[13px] font-medium leading-snug"
                style="color: var(--tg-theme-text-color)"
            >
                {{ product.name }}
            </p>

            <div class="mt-1.5 flex items-baseline gap-1.5">
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

            <!-- Rating -->
            <div v-if="product.rating" class="mt-1 flex items-center gap-0.5">
                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="var(--tg-theme-button-color)">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span class="text-[10px] font-medium" style="color: var(--tg-theme-hint-color)">
                    {{ product.rating }}
                </span>
            </div>
        </div>
    </button>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTelegram } from '../composables/useTelegram'
import { useCartStore } from '../stores/cart'
import { formatPrice } from '../utils/formatters'

const props = defineProps({
    product: { type: Object, required: true },
})

const router = useRouter()
const cart = useCartStore()
const { hapticImpact } = useTelegram()

const imageLoaded = ref(false)

const hasImage = computed(() => !!(props.product.image || props.product.images?.[0]))

const effectivePrice = computed(() => {
    return props.product.sale_price || props.product.price
})

const discountPercent = computed(() => {
    if (!props.product.sale_price || !props.product.price) return 0
    return Math.round((1 - props.product.sale_price / props.product.price) * 100)
})

const isInCart = computed(() => {
    return cart.items.some(item => item.product_id === props.product.id)
})

function goToProduct() {
    hapticImpact('light')
    router.push({ name: 'product', params: { slug: props.product.slug } })
}

function quickAdd() {
    cart.addItem(props.product, 1)
}

function goToCart() {
    hapticImpact('light')
    router.push({ name: 'cart' })
}
</script>
