<template>
    <div class="pb-24">
        <BackButton />

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-20">
            <LoadingSpinner />
        </div>

        <!-- Not found -->
        <div v-else-if="!product" class="px-4 py-10 text-center">
            <p class="text-sm" style="color: var(--tg-theme-hint-color)">Mahsulot topilmadi</p>
        </div>

        <template v-else>
            <!-- Image Carousel -->
            <div class="relative">
                <div class="overflow-hidden">
                    <div
                        class="flex transition-transform duration-300"
                        :style="{ transform: `translateX(-${currentImage * 100}%)` }"
                        @touchstart="onTouchStart"
                        @touchmove="onTouchMove"
                        @touchend="onTouchEnd"
                    >
                        <div
                            v-for="(img, i) in images"
                            :key="i"
                            class="w-full shrink-0"
                        >
                            <img
                                :src="img"
                                :alt="product.name"
                                class="aspect-square w-full object-cover"
                            />
                        </div>
                    </div>
                </div>
                <!-- Image dots -->
                <div v-if="images.length > 1" class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5">
                    <span
                        v-for="(_, i) in images"
                        :key="i"
                        class="h-1.5 rounded-full transition-all duration-200"
                        :class="i === currentImage ? 'w-4' : 'w-1.5 opacity-50'"
                        style="background-color: var(--tg-theme-button-color)"
                    />
                </div>
                <!-- Discount badge -->
                <div
                    v-if="discountPercent > 0"
                    class="absolute top-3 left-3 rounded-lg px-2 py-1 text-xs font-bold text-white"
                    style="background-color: #ef4444"
                >
                    -{{ discountPercent }}%
                </div>
            </div>

            <!-- Product Info -->
            <div class="px-4 pt-4">
                <h1 class="text-lg font-semibold leading-snug" style="color: var(--tg-theme-text-color)">
                    {{ product.name }}
                </h1>

                <!-- Price -->
                <div class="mt-2 flex items-center gap-2">
                    <span class="text-xl font-bold" style="color: var(--tg-theme-text-color)">
                        {{ formatPrice(effectivePrice) }}
                    </span>
                    <span
                        v-if="product.sale_price"
                        class="text-sm line-through"
                        style="color: var(--tg-theme-hint-color)"
                    >
                        {{ formatPrice(product.price) }}
                    </span>
                </div>

                <!-- Stock -->
                <p v-if="product.stock !== null && product.stock <= 5 && product.stock > 0" class="mt-1 text-xs text-orange-500">
                    Faqat {{ product.stock }} ta qoldi
                </p>
                <p v-if="product.stock === 0" class="mt-1 text-xs text-red-500">
                    Tugagan
                </p>

                <!-- Variants -->
                <div v-if="product.variants && product.variants.length" class="mt-4">
                    <p class="mb-2 text-sm font-medium" style="color: var(--tg-theme-text-color)">
                        {{ product.variant_label || 'Variant' }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            @click="selectVariant(variant)"
                            class="rounded-lg border px-3 py-2 text-sm transition-all"
                            :class="selectedVariant?.id === variant.id
                                ? 'border-2 font-medium'
                                : 'border opacity-70'"
                            :style="selectedVariant?.id === variant.id
                                ? { borderColor: 'var(--tg-theme-button-color)', color: 'var(--tg-theme-button-color)' }
                                : { borderColor: 'var(--tg-theme-hint-color)', color: 'var(--tg-theme-text-color)' }"
                        >
                            {{ variant.name }}
                            <span v-if="variant.price_diff" class="ml-1 text-xs opacity-70">
                                +{{ formatPrice(variant.price_diff) }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="product.description" class="mt-4">
                    <p class="mb-2 text-sm font-medium" style="color: var(--tg-theme-text-color)">
                        Tavsif
                    </p>
                    <p
                        class="text-sm leading-relaxed"
                        style="color: var(--tg-theme-hint-color)"
                        :class="{ 'line-clamp-4': !showFullDescription }"
                    >
                        {{ product.description }}
                    </p>
                    <button
                        v-if="product.description.length > 200"
                        @click="showFullDescription = !showFullDescription"
                        class="mt-1 text-sm font-medium"
                        style="color: var(--tg-theme-link-color)"
                    >
                        {{ showFullDescription ? 'Yopish' : "Ko'proq o'qish" }}
                    </button>
                </div>

                <!-- Attributes -->
                <div v-if="product.attributes && product.attributes.length" class="mt-4">
                    <p class="mb-2 text-sm font-medium" style="color: var(--tg-theme-text-color)">
                        Xususiyatlar
                    </p>
                    <div
                        class="rounded-xl p-3"
                        style="background-color: var(--tg-theme-secondary-bg-color)"
                    >
                        <div
                            v-for="attr in product.attributes"
                            :key="attr.name"
                            class="flex justify-between py-1.5 text-sm"
                        >
                            <span style="color: var(--tg-theme-hint-color)">{{ attr.name }}</span>
                            <span class="font-medium" style="color: var(--tg-theme-text-color)">{{ attr.value }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Bottom action bar -->
        <div
            v-if="product && product.stock !== 0"
            class="fixed bottom-0 left-0 right-0 z-20 border-t px-4 py-3"
            style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-secondary-bg-color)"
        >
            <div class="flex items-center gap-3">
                <!-- Quantity controls if in cart -->
                <div
                    v-if="cartItem"
                    class="flex items-center gap-3 rounded-xl px-3 py-2"
                    style="background-color: var(--tg-theme-secondary-bg-color)"
                >
                    <button
                        @click="decrementQty"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-lg font-bold"
                        style="color: var(--tg-theme-text-color)"
                    >
                        -
                    </button>
                    <span class="min-w-[20px] text-center font-semibold" style="color: var(--tg-theme-text-color)">
                        {{ cartItem.quantity }}
                    </span>
                    <button
                        @click="incrementQty"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-lg font-bold"
                        style="color: var(--tg-theme-text-color)"
                    >
                        +
                    </button>
                </div>

                <!-- Add to cart button -->
                <button
                    @click="addToCart"
                    class="flex-1 rounded-xl py-3 text-center text-sm font-semibold"
                    style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
                >
                    {{ cartItem ? `Savatda — ${formatPrice(cartItem.quantity * effectivePrice)}` : "Savatga qo'shish" }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useStoreInfo } from '../stores/store'
import { useCartStore } from '../stores/cart'
import { useTelegram } from '../composables/useTelegram'
import BackButton from '../components/BackButton.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'

const props = defineProps({
    slug: { type: String, required: true },
})

const storeInfo = useStoreInfo()
const cart = useCartStore()
const { hapticImpact, hapticNotification } = useTelegram()

const product = ref(null)
const loading = ref(true)
const selectedVariant = ref(null)
const currentImage = ref(0)
const showFullDescription = ref(false)

// Touch handling for carousel
let touchStartX = 0
let touchDeltaX = 0

const images = computed(() => {
    if (!product.value) return []
    if (product.value.images?.length) return product.value.images
    if (product.value.image) return [product.value.image]
    return []
})

const effectivePrice = computed(() => {
    if (!product.value) return 0
    const basePrice = product.value.sale_price || product.value.price
    const diff = selectedVariant.value?.price_diff || 0
    return basePrice + diff
})

const discountPercent = computed(() => {
    if (!product.value?.sale_price || !product.value?.price) return 0
    return Math.round((1 - product.value.sale_price / product.value.price) * 100)
})

const cartItem = computed(() => {
    if (!product.value) return null
    return cart.items.find(
        (item) => item.product_id === product.value.id && item.variant_id === (selectedVariant.value?.id || null)
    )
})

function formatPrice(price) {
    if (!price) return "0 so'm"
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + " so'm"
}

function selectVariant(variant) {
    selectedVariant.value = variant
    hapticImpact('light')
}

function addToCart() {
    if (!product.value) return
    cart.addItem(product.value, 1, selectedVariant.value)
    hapticNotification('success')
}

function incrementQty() {
    if (cartItem.value) {
        cart.incrementQuantity(product.value.id, selectedVariant.value?.id || null)
    }
}

function decrementQty() {
    if (cartItem.value) {
        cart.decrementQuantity(product.value.id, selectedVariant.value?.id || null)
    }
}

function onTouchStart(e) {
    touchStartX = e.touches[0].clientX
}

function onTouchMove(e) {
    touchDeltaX = e.touches[0].clientX - touchStartX
}

function onTouchEnd() {
    if (Math.abs(touchDeltaX) > 50) {
        if (touchDeltaX < 0 && currentImage.value < images.value.length - 1) {
            currentImage.value++
        } else if (touchDeltaX > 0 && currentImage.value > 0) {
            currentImage.value--
        }
    }
    touchDeltaX = 0
}

onMounted(async () => {
    loading.value = true
    product.value = await storeInfo.fetchProduct(props.slug)
    if (product.value?.variants?.length) {
        selectedVariant.value = product.value.variants[0]
    }
    loading.value = false
})
</script>
