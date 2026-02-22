<template>
    <div class="pb-24">
        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-20">
            <LoadingSpinner />
        </div>

        <!-- Error -->
        <div v-else-if="!item" class="px-4 py-10 text-center">
            <p class="text-sm" style="color: var(--tg-theme-hint-color)">Element topilmadi</p>
            <button
                @click="$router.back()"
                class="mt-3 rounded-lg px-4 py-2 text-sm font-medium"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                Orqaga
            </button>
        </div>

        <template v-else>
            <!-- Image -->
            <div class="relative aspect-square w-full overflow-hidden" style="background-color: var(--tg-theme-secondary-bg-color)">
                <img
                    v-if="item.image || item.images?.[0]"
                    :src="item.image || item.images[0]"
                    :alt="item.name"
                    class="h-full w-full object-cover"
                />
                <div v-else class="flex h-full w-full items-center justify-center text-5xl">
                    {{ typeEmoji }}
                </div>
            </div>

            <!-- Info -->
            <div class="px-4 py-4">
                <h1 class="text-xl font-bold" style="color: var(--tg-theme-text-color)">{{ item.name }}</h1>

                <div class="mt-2 flex items-center gap-2">
                    <span class="text-xl font-bold" style="color: var(--tg-theme-text-color)">
                        {{ formatPrice(item.price) }}
                    </span>
                    <span
                        v-if="item.compare_price"
                        class="text-sm line-through"
                        style="color: var(--tg-theme-hint-color)"
                    >
                        {{ formatPrice(item.compare_price) }}
                    </span>
                </div>

                <!-- Type-specific attributes -->
                <div v-if="attributesList.length" class="mt-3 flex flex-wrap gap-2">
                    <span
                        v-for="attr in attributesList"
                        :key="attr.label"
                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs"
                        style="background-color: var(--tg-theme-secondary-bg-color); color: var(--tg-theme-text-color)"
                    >
                        <span style="color: var(--tg-theme-hint-color)">{{ attr.label }}:</span>
                        {{ attr.value }}
                    </span>
                </div>

                <!-- Description -->
                <div v-if="item.description" class="mt-4">
                    <p class="text-sm leading-relaxed whitespace-pre-line" style="color: var(--tg-theme-hint-color)">
                        {{ item.description }}
                    </p>
                </div>

                <!-- Rating -->
                <div v-if="item.average_rating" class="mt-3 flex items-center gap-1">
                    <span class="text-amber-500">★</span>
                    <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ item.average_rating }}</span>
                    <span class="text-xs" style="color: var(--tg-theme-hint-color)">({{ item.reviews_count || 0 }})</span>
                </div>
            </div>
        </template>

        <!-- Add to Cart button -->
        <div
            v-if="item && !loading"
            class="fixed bottom-0 left-0 right-0 px-4 py-3 border-t"
            style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-secondary-bg-color)"
        >
            <button
                @click="addToCart"
                class="w-full rounded-xl py-3 text-center text-sm font-semibold"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                Savatga qo'shish — {{ formatPrice(item.price) }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useStoreInfo } from '../stores/store'
import { useCartStore } from '../stores/cart'
import { useTelegram } from '../composables/useTelegram'
import LoadingSpinner from '../components/LoadingSpinner.vue'

const props = defineProps({
    slug: { type: String, required: true },
})

const route = useRoute()
const storeInfo = useStoreInfo()
const cart = useCartStore()
const { showBackButton, hapticNotification } = useTelegram()

const item = ref(null)
const loading = ref(true)

const typeEmoji = computed(() => {
    const map = {
        service: '💆', menu_item: '🍔', course: '📚', membership: '🏋️',
        property: '🏠', vehicle: '🚗', event: '🎫', tour: '✈️',
        service_request: '🔧', content_plan: '📱', custom_item: '📦',
    }
    return map[item.value?.catalog_type] || '📦'
})

const attributesList = computed(() => {
    if (!item.value?.attributes) return []
    const attrs = item.value.attributes
    const list = []
    if (attrs.duration_minutes) list.push({ label: 'Davomiylik', value: `${attrs.duration_minutes} min` })
    if (attrs.preparation_time_minutes) list.push({ label: 'Tayyorlov', value: `${attrs.preparation_time_minutes} min` })
    if (attrs.rooms) list.push({ label: 'Xonalar', value: attrs.rooms })
    if (attrs.area_sqm) list.push({ label: 'Maydon', value: `${attrs.area_sqm} m²` })
    if (attrs.brand) list.push({ label: 'Brand', value: attrs.brand })
    if (attrs.year) list.push({ label: 'Yil', value: attrs.year })
    if (attrs.venue) list.push({ label: 'Joy', value: attrs.venue })
    if (attrs.destination) list.push({ label: 'Manzil', value: attrs.destination })
    if (attrs.level) list.push({ label: 'Daraja', value: attrs.level })
    if (attrs.instructor) list.push({ label: "O'qituvchi", value: attrs.instructor })
    return list
})

function formatPrice(price) {
    if (!price) return "0 so'm"
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + " so'm"
}

function addToCart() {
    cart.addItem({
        id: item.value.id,
        name: item.value.name,
        price: item.value.price,
        sale_price: item.value.compare_price && item.value.compare_price > item.value.price ? item.value.price : null,
        image: item.value.image || item.value.images?.[0] || '',
        slug: item.value.slug,
        stock: 99,
        catalog_type: item.value.catalog_type,
    })
    hapticNotification('success')
}

onMounted(async () => {
    showBackButton()
    loading.value = true
    item.value = await storeInfo.fetchCatalogItem(props.slug)
    loading.value = false
})
</script>
