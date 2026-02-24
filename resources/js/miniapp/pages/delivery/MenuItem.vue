<template>
    <div class="pb-safe">
        <!-- Back button -->
        <div class="header-blur sticky top-0 z-30">
            <div class="flex items-center gap-3" style="padding: 12px 16px">
                <button @click="goBack" class="tap-active flex items-center justify-center w-9 h-9 rounded-full" style="background: var(--tg-theme-secondary-bg-color)">
                    <svg style="width: 20px; height: 20px; color: var(--tg-theme-text-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </button>
                <h1 class="text-base font-bold truncate" style="color: var(--tg-theme-text-color)">{{ item?.name || 'Yuklanmoqda...' }}</h1>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" style="padding: 16px">
            <div class="skeleton" style="width: 100%; aspect-ratio: 4/3; border-radius: 16px; margin-bottom: 16px"></div>
            <div class="skeleton" style="height: 24px; width: 60%; border-radius: 8px; margin-bottom: 12px"></div>
            <div class="skeleton" style="height: 16px; width: 100%; border-radius: 8px; margin-bottom: 8px"></div>
            <div class="skeleton" style="height: 16px; width: 80%; border-radius: 8px"></div>
        </div>

        <template v-else-if="item">
            <!-- Image -->
            <div class="w-full aspect-[4/3] overflow-hidden" style="background: var(--tg-theme-secondary-bg-color)">
                <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center">
                    <span style="font-size: 64px">🍔</span>
                </div>
            </div>

            <!-- Info -->
            <div style="padding: 16px">
                <h2 class="text-xl font-bold" style="color: var(--tg-theme-text-color)">{{ item.name }}</h2>
                <p v-if="item.description" class="text-sm mt-2" style="color: var(--tg-theme-hint-color); line-height: 1.5">{{ item.description }}</p>

                <!-- Preparation time -->
                <div v-if="item.preparation_time_minutes" class="flex items-center gap-2 mt-3" style="color: var(--tg-theme-hint-color)">
                    <svg style="width: 16px; height: 16px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm">~{{ item.preparation_time_minutes }} min</span>
                </div>

                <!-- Modifiers (size, addons) -->
                <div v-for="modifier in modifiers" :key="modifier.id" style="margin-top: 20px">
                    <h3 class="text-sm font-semibold mb-2" style="color: var(--tg-theme-text-color)">
                        {{ modifier.name }}
                        <span v-if="modifier.is_required" class="text-xs" style="color: var(--color-error)">*</span>
                    </h3>

                    <!-- Single select (radio) -->
                    <div v-if="modifier.max_selections <= 1" class="flex flex-col gap-2">
                        <button
                            v-for="option in modifier.options"
                            :key="option.id"
                            @click="selectOption(modifier.id, option)"
                            class="flex items-center justify-between rounded-xl px-4 py-3 tap-active"
                            :style="{
                                border: '1.5px solid ' + (isSelected(modifier.id, option.id) ? 'var(--tg-theme-button-color)' : 'var(--color-border)'),
                                background: isSelected(modifier.id, option.id) ? 'color-mix(in srgb, var(--tg-theme-button-color) 8%, var(--tg-theme-bg-color))' : 'var(--tg-theme-bg-color)',
                            }"
                        >
                            <span class="text-sm" style="color: var(--tg-theme-text-color)">{{ option.name }}</span>
                            <span v-if="option.price_adjustment > 0" class="text-xs font-medium" style="color: var(--tg-theme-hint-color)">+{{ formatPrice(option.price_adjustment) }}</span>
                        </button>
                    </div>

                    <!-- Multi select (checkbox) -->
                    <div v-else class="flex flex-col gap-2">
                        <button
                            v-for="option in modifier.options"
                            :key="option.id"
                            @click="toggleOption(modifier.id, option)"
                            class="flex items-center justify-between rounded-xl px-4 py-3 tap-active"
                            :style="{
                                border: '1.5px solid ' + (isSelected(modifier.id, option.id) ? 'var(--tg-theme-button-color)' : 'var(--color-border)'),
                                background: isSelected(modifier.id, option.id) ? 'color-mix(in srgb, var(--tg-theme-button-color) 8%, var(--tg-theme-bg-color))' : 'var(--tg-theme-bg-color)',
                            }"
                        >
                            <span class="text-sm" style="color: var(--tg-theme-text-color)">{{ option.name }}</span>
                            <div class="flex items-center gap-2">
                                <span v-if="option.price_adjustment > 0" class="text-xs font-medium" style="color: var(--tg-theme-hint-color)">+{{ formatPrice(option.price_adjustment) }}</span>
                                <div class="w-5 h-5 rounded flex items-center justify-center" :style="{ background: isSelected(modifier.id, option.id) ? 'var(--tg-theme-button-color)' : 'var(--color-border)' }">
                                    <svg v-if="isSelected(modifier.id, option.id)" style="width: 14px; height: 14px; color: #fff" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Quantity -->
                <div class="flex items-center justify-between mt-6 mb-4">
                    <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">Miqdori</span>
                    <div class="flex items-center gap-3">
                        <button @click="quantity > 1 && quantity--" class="w-9 h-9 rounded-full flex items-center justify-center tap-active" style="background: var(--tg-theme-secondary-bg-color); font-size: 18px; color: var(--tg-theme-text-color)">-</button>
                        <span class="text-base font-bold" style="color: var(--tg-theme-text-color); min-width: 24px; text-align: center">{{ quantity }}</span>
                        <button @click="quantity++" class="w-9 h-9 rounded-full flex items-center justify-center tap-active" style="background: var(--tg-theme-button-color); font-size: 18px; color: var(--tg-theme-button-text-color)">+</button>
                    </div>
                </div>
            </div>

            <!-- Add to cart button -->
            <div class="sticky-bottom-bar">
                <button @click="addToCart" class="btn-primary" :disabled="!canAdd">
                    Savatga qo'shish — {{ formatPrice(totalPrice) }}
                </button>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useStoreInfo } from '../../stores/store'
import { useCartStore } from '../../stores/cart'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const props = defineProps({ slug: String })
const router = useRouter()
const route = useRoute()
const storeInfo = useStoreInfo()
const cart = useCartStore()
const { hapticImpact, hapticNotification } = useTelegram()

const loading = ref(true)
const item = ref(null)
const modifiers = ref([])
const selections = reactive({}) // { modifier_id: [option_id, ...] }
const quantity = ref(1)

const totalPrice = computed(() => {
    if (!item.value) return 0
    let price = item.value.price || 0
    for (const [modId, optIds] of Object.entries(selections)) {
        const mod = modifiers.value.find(m => m.id === modId)
        if (!mod) continue
        for (const optId of optIds) {
            const opt = mod.options.find(o => o.id === optId)
            if (opt?.price_adjustment) price += opt.price_adjustment
        }
    }
    return price * quantity.value
})

const canAdd = computed(() => {
    for (const mod of modifiers.value) {
        if (mod.is_required && (!selections[mod.id] || selections[mod.id].length === 0)) {
            return false
        }
    }
    return true
})

function isSelected(modId, optId) {
    return selections[modId]?.includes(optId) || false
}

function selectOption(modId, option) {
    selections[modId] = [option.id]
    hapticImpact('light')
}

function toggleOption(modId, option) {
    if (!selections[modId]) selections[modId] = []
    const idx = selections[modId].indexOf(option.id)
    if (idx > -1) {
        selections[modId].splice(idx, 1)
    } else {
        const mod = modifiers.value.find(m => m.id === modId)
        if (mod && selections[modId].length >= (mod.max_selections || 10)) return
        selections[modId].push(option.id)
    }
    hapticImpact('light')
}

function addToCart() {
    if (!canAdd.value || !item.value) return

    const selectedModifiers = []
    for (const [modId, optIds] of Object.entries(selections)) {
        const mod = modifiers.value.find(m => m.id === modId)
        if (!mod) continue
        for (const optId of optIds) {
            const opt = mod.options.find(o => o.id === optId)
            if (opt) {
                selectedModifiers.push({
                    modifier_id: mod.id,
                    modifier_name: mod.name,
                    option_id: opt.id,
                    option_name: opt.name,
                    price: opt.price_adjustment || 0,
                })
            }
        }
    }

    // Pass base price + modifiers separately — cart calculates total
    cart.addItem({
        id: item.value.id,
        name: item.value.name,
        price: item.value.price || 0,
        image: item.value.image,
        slug: item.value.slug,
        stock: 99,
    }, quantity.value, null, selectedModifiers)

    hapticNotification('success')
    router.push({ name: 'cart' })
}

function goBack() {
    hapticImpact('light')
    router.back()
}

onMounted(async () => {
    loading.value = true
    try {
        const itemSlug = props.slug || route.params.slug
        const data = await storeInfo.fetchCatalogItem(itemSlug)
        if (data) {
            item.value = data.item || data.data || data
            modifiers.value = item.value.modifiers || []
            // Pre-select default options
            for (const mod of modifiers.value) {
                if (mod.is_required && mod.options?.length) {
                    const defaultOpt = mod.options.find(o => o.is_default) || mod.options[0]
                    selections[mod.id] = [defaultOpt.id]
                }
            }
        }
    } catch (err) {
        console.error('[MenuItem] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>
