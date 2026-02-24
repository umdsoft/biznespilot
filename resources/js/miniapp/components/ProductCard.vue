<template>
    <button
        @click="goToProduct"
        class="overflow-hidden text-left tap-active"
        style="border-radius: 16px; background-color: var(--tg-theme-bg-color); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm)"
    >
        <!-- Image -->
        <div class="relative aspect-square w-full overflow-hidden" style="border-radius: 12px; margin: 0">
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
                style="background-color: var(--tg-theme-secondary-bg-color)"
            >
                📦
            </div>

            <!-- Discount badge -->
            <div
                v-if="discountPercent > 0"
                class="absolute top-2 left-2 flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white"
                style="background-color: var(--color-error); border-radius: 8px"
            >
                -{{ discountPercent }}%
            </div>

            <!-- Out of stock overlay -->
            <div
                v-if="product.stock === 0"
                class="absolute inset-0 flex items-center justify-center bg-black/40"
            >
                <span class="px-2.5 py-1 text-xs font-semibold text-gray-800" style="background: rgba(255,255,255,0.9); border-radius: 8px">
                    Tugagan
                </span>
            </div>

            <!-- Quick add button -->
            <button
                v-if="product.stock !== 0 && !isInCart"
                @click.stop="quickAdd"
                class="absolute bottom-2 right-2 flex items-center justify-center tap-active"
                style="width: 32px; height: 32px; border-radius: 10px; background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); box-shadow: var(--shadow-md)"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>

            <!-- In cart indicator -->
            <button
                v-else-if="product.stock !== 0 && isInCart"
                @click.stop="goToCart"
                class="absolute bottom-2 right-2 flex items-center justify-center"
                style="width: 32px; height: 32px; border-radius: 10px; background-color: var(--color-success); color: #fff; box-shadow: var(--shadow-md)"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </button>
        </div>

        <!-- Info -->
        <div style="padding: 10px 12px">
            <p
                class="line-clamp-2"
                style="font-size: 14px; font-weight: 500; line-height: 1.35; color: var(--tg-theme-text-color)"
            >
                {{ product.name }}
            </p>

            <div class="mt-1.5 flex items-baseline gap-1.5">
                <span style="font-size: 15px; font-weight: 700; color: var(--tg-theme-text-color)">
                    {{ formatPrice(effectivePrice) }}
                </span>
                <span
                    v-if="product.sale_price"
                    style="font-size: 12px; text-decoration: line-through; color: var(--tg-theme-hint-color)"
                >
                    {{ formatPrice(product.price) }}
                </span>
            </div>

            <!-- Rating -->
            <div v-if="product.rating" class="mt-1 flex items-center gap-0.5">
                <svg style="width: 12px; height: 12px" viewBox="0 0 20 20" fill="var(--tg-theme-button-color)">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span style="font-size: 11px; font-weight: 500; color: var(--tg-theme-hint-color)">
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
import { useToast } from '../composables/useToast'
import { formatPrice } from '../utils/formatters'

const props = defineProps({
    product: { type: Object, required: true },
})

const router = useRouter()
const cart = useCartStore()
const { hapticImpact } = useTelegram()
const toast = useToast()

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
    toast.success("Savatga qo'shildi")
}

function goToCart() {
    hapticImpact('light')
    router.push({ name: 'cart' })
}
</script>
