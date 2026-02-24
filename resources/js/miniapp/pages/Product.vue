<template>
    <div style="padding-bottom: 100px">
        <BackButton />

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center" style="padding: 80px 0">
            <LoadingSpinner />
        </div>

        <!-- Not found -->
        <div v-else-if="!product" class="empty-state" style="min-height: 40vh">
            <div class="empty-state-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <p class="empty-state-title">Mahsulot topilmadi</p>
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
                <!-- Image counter -->
                <div
                    v-if="images.length > 1"
                    class="absolute"
                    style="top: 12px; right: 12px; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; background: rgba(0,0,0,0.5); color: #fff"
                >
                    {{ currentImage + 1 }}/{{ images.length }}
                </div>
                <!-- Image dots -->
                <div v-if="images.length > 1" class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5">
                    <span
                        v-for="(_, i) in images"
                        :key="i"
                        class="rounded-full transition-all duration-200"
                        :style="{ height: '6px', width: i === currentImage ? '16px' : '6px', opacity: i === currentImage ? 1 : 0.5, backgroundColor: 'var(--tg-theme-button-color)' }"
                    />
                </div>
                <!-- Discount badge -->
                <div
                    v-if="discountPercent > 0"
                    class="absolute"
                    style="top: 12px; left: 12px; padding: 4px 10px; border-radius: 10px; font-size: 13px; font-weight: 700; color: #fff; background-color: var(--color-error)"
                >
                    -{{ discountPercent }}%
                </div>
            </div>

            <!-- Product Info -->
            <div style="padding: 16px">
                <h1 style="font-size: 20px; font-weight: 700; line-height: 1.3; color: var(--tg-theme-text-color)">
                    {{ product.name }}
                </h1>

                <!-- Price -->
                <div class="flex items-center" style="margin-top: 10px; gap: 8px">
                    <span style="font-size: 22px; font-weight: 700; color: var(--tg-theme-text-color)">
                        {{ formatPrice(effectivePrice) }}
                    </span>
                    <span
                        v-if="product.sale_price"
                        style="font-size: 15px; text-decoration: line-through; color: var(--tg-theme-hint-color)"
                    >
                        {{ formatPrice(product.price) }}
                    </span>
                </div>

                <!-- Rating -->
                <div v-if="product.rating" class="flex items-center" style="margin-top: 8px; gap: 4px">
                    <svg style="width: 16px; height: 16px" viewBox="0 0 20 20" fill="var(--tg-theme-button-color)">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span style="font-size: 14px; font-weight: 500; color: var(--tg-theme-text-color)">{{ product.rating }}</span>
                    <span v-if="product.reviews_count" style="font-size: 13px; color: var(--tg-theme-hint-color)">
                        ({{ product.reviews_count }} ta baho)
                    </span>
                </div>

                <!-- Stock -->
                <p v-if="product.stock !== null && product.stock <= 5 && product.stock > 0" style="margin-top: 6px; font-size: 13px; color: var(--color-warning); font-weight: 500">
                    Faqat {{ product.stock }} ta qoldi
                </p>
                <p v-if="product.stock === 0" style="margin-top: 6px; font-size: 13px; color: var(--color-error); font-weight: 500">
                    Tugagan
                </p>

                <!-- Variants -->
                <div v-if="product.variants && product.variants.length" style="margin-top: 20px">
                    <p style="font-size: 14px; font-weight: 600; color: var(--tg-theme-text-color); margin-bottom: 10px">
                        {{ product.variant_label || 'Variant' }}
                    </p>
                    <div class="flex flex-wrap" style="gap: 8px">
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            @click="selectVariant(variant)"
                            class="tap-active"
                            :style="selectedVariant?.id === variant.id
                                ? { padding: '8px 16px', borderRadius: '10px', backgroundColor: 'var(--tg-theme-button-color)', color: 'var(--tg-theme-button-text-color)', fontSize: '14px', fontWeight: '600', border: 'none' }
                                : { padding: '8px 16px', borderRadius: '10px', backgroundColor: 'var(--tg-theme-secondary-bg-color)', color: 'var(--tg-theme-text-color)', fontSize: '14px', fontWeight: '500', border: 'none' }"
                        >
                            {{ variant.name }}
                            <span v-if="variant.price_diff" style="margin-left: 4px; opacity: 0.7; font-size: 12px">
                                +{{ formatPrice(variant.price_diff) }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="product.description" style="margin-top: 20px">
                    <p class="section-title" style="font-size: 15px; margin-bottom: 8px">Tavsif</p>
                    <p
                        :class="{ 'line-clamp-4': !showFullDescription }"
                        style="font-size: 14px; line-height: 1.6; color: var(--tg-theme-hint-color)"
                    >
                        {{ product.description }}
                    </p>
                    <button
                        v-if="product.description.length > 200"
                        @click="showFullDescription = !showFullDescription"
                        style="margin-top: 6px; font-size: 14px; font-weight: 500; color: var(--tg-theme-link-color); background: none; border: none; padding: 0; cursor: pointer"
                    >
                        {{ showFullDescription ? 'Yopish' : "Ko'proq o'qish" }}
                    </button>
                </div>

                <!-- Attributes -->
                <div v-if="product.attributes && product.attributes.length" style="margin-top: 20px">
                    <p class="section-title" style="font-size: 15px; margin-bottom: 10px">Xususiyatlar</p>
                    <div style="padding: 14px; border-radius: 14px; background-color: var(--tg-theme-secondary-bg-color)">
                        <div
                            v-for="(attr, idx) in product.attributes"
                            :key="attr.name"
                            class="flex justify-between"
                            :style="{
                                padding: '8px 0',
                                fontSize: '14px',
                                borderBottom: idx < product.attributes.length - 1 ? '1px solid var(--color-divider)' : 'none'
                            }"
                        >
                            <span style="color: var(--tg-theme-hint-color)">{{ attr.name }}</span>
                            <span style="font-weight: 500; color: var(--tg-theme-text-color)">{{ attr.value }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Bottom action bar -->
        <div
            v-if="product && product.stock !== 0"
            class="sticky-bottom-bar"
        >
            <div class="flex items-center" style="gap: 12px">
                <!-- Quantity controls if in cart -->
                <div
                    v-if="cartItem"
                    class="flex items-center"
                    style="gap: 4px"
                >
                    <button @click="decrementQty" class="qty-btn">−</button>
                    <span class="qty-value">{{ cartItem.quantity }}</span>
                    <button @click="incrementQty" class="qty-btn" style="color: var(--tg-theme-button-color)">+</button>
                </div>

                <!-- Add to cart button -->
                <button @click="addToCart" class="btn-primary" style="flex: 1">
                    <span v-if="justAdded" class="inline-flex items-center gap-1">
                        <svg style="width: 16px; height: 16px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Qo'shildi!
                    </span>
                    <span v-else>
                        {{ cartItem ? `Savatda — ${formatPrice(cartItem.quantity * effectivePrice)}` : "Savatga qo'shish" }}
                    </span>
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
import { useToast } from '../composables/useToast'
import { formatPrice } from '../utils/formatters'

const props = defineProps({
    slug: { type: String, required: true },
})

const storeInfo = useStoreInfo()
const cart = useCartStore()
const { hapticImpact, hapticNotification } = useTelegram()
const toast = useToast()

const product = ref(null)
const loading = ref(true)
const selectedVariant = ref(null)
const currentImage = ref(0)
const showFullDescription = ref(false)
const justAdded = ref(false)

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

function selectVariant(variant) {
    selectedVariant.value = variant
    hapticImpact('light')
}

function addToCart() {
    if (!product.value) return
    cart.addItem(product.value, 1, selectedVariant.value)
    hapticNotification('success')
    toast.success("Savatga qo'shildi")

    justAdded.value = true
    setTimeout(() => { justAdded.value = false }, 800)
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
