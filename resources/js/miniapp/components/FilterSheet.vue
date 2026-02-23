<template>
    <Teleport to="body">
        <transition name="sheet">
            <div v-if="modelValue" class="fixed inset-0 z-50" @click.self="close">
                <div class="sheet-backdrop absolute inset-0" @click="close"></div>
                <div class="absolute bottom-0 left-0 right-0 rounded-t-2xl safe-area-bottom"
                     style="background-color: var(--tg-theme-bg-color); max-height: 70vh"
                >
                    <!-- Handle -->
                    <div class="flex justify-center pt-2 pb-1">
                        <div class="h-1 w-8 rounded-full" style="background-color: var(--tg-theme-hint-color); opacity: 0.3"></div>
                    </div>

                    <div class="px-4 pb-3 flex items-center justify-between">
                        <h3 class="text-base font-semibold" style="color: var(--tg-theme-text-color)">Filtr</h3>
                        <button
                            v-if="hasActiveFilters"
                            @click="clearAll"
                            class="text-xs font-medium tap-active"
                            style="color: var(--tg-theme-button-color)"
                        >
                            Tozalash
                        </button>
                    </div>

                    <div class="px-4 pb-4 space-y-5">
                        <!-- Price range -->
                        <div>
                            <label class="text-xs font-medium mb-2 block" style="color: var(--tg-theme-hint-color)">
                                Narx diapazoni
                            </label>
                            <div class="flex gap-2.5">
                                <div class="flex-1 relative">
                                    <input
                                        v-model.number="localMinPrice"
                                        type="number"
                                        placeholder="Dan"
                                        min="0"
                                        class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                                        :style="{
                                            backgroundColor: 'var(--tg-theme-secondary-bg-color)',
                                            color: 'var(--tg-theme-text-color)',
                                        }"
                                    />
                                </div>
                                <div class="flex items-center">
                                    <span class="text-xs" style="color: var(--tg-theme-hint-color)">—</span>
                                </div>
                                <div class="flex-1 relative">
                                    <input
                                        v-model.number="localMaxPrice"
                                        type="number"
                                        placeholder="Gacha"
                                        min="0"
                                        class="w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                                        :style="{
                                            backgroundColor: 'var(--tg-theme-secondary-bg-color)',
                                            color: 'var(--tg-theme-text-color)',
                                        }"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- In stock toggle -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm" style="color: var(--tg-theme-text-color)">Faqat mavjud</span>
                            <button
                                @click="localInStock = !localInStock"
                                class="relative h-7 w-12 rounded-full transition-colors duration-200"
                                :style="{
                                    backgroundColor: localInStock ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-secondary-bg-color)',
                                }"
                            >
                                <span
                                    class="absolute top-0.5 left-0.5 h-6 w-6 rounded-full bg-white shadow-sm transition-transform duration-200"
                                    :style="{ transform: localInStock ? 'translateX(20px)' : 'translateX(0)' }"
                                ></span>
                            </button>
                        </div>

                        <!-- Apply button -->
                        <button
                            @click="apply"
                            class="w-full rounded-xl py-3 text-sm font-semibold tap-active"
                            style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
                        >
                            Ko'rsatish
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useTelegram } from '../composables/useTelegram'

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    filters: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'apply'])
const { hapticImpact } = useTelegram()

const localMinPrice = ref(null)
const localMaxPrice = ref(null)
const localInStock = ref(false)

// Sync from parent when opened
watch(() => props.modelValue, (open) => {
    if (open) {
        localMinPrice.value = props.filters.min_price || null
        localMaxPrice.value = props.filters.max_price || null
        localInStock.value = !!props.filters.in_stock
    }
})

const hasActiveFilters = computed(() => {
    return localMinPrice.value || localMaxPrice.value || localInStock.value
})

function apply() {
    hapticImpact('light')
    emit('apply', {
        min_price: localMinPrice.value || undefined,
        max_price: localMaxPrice.value || undefined,
        in_stock: localInStock.value ? 1 : undefined,
    })
    emit('update:modelValue', false)
}

function clearAll() {
    localMinPrice.value = null
    localMaxPrice.value = null
    localInStock.value = false
    hapticImpact('light')
}

function close() {
    emit('update:modelValue', false)
}
</script>

<style scoped>
.sheet-enter-active,
.sheet-leave-active {
    transition: opacity 0.2s ease;
}
.sheet-enter-active > div:last-child,
.sheet-leave-active > div:last-child {
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.sheet-enter-from,
.sheet-leave-to {
    opacity: 0;
}
.sheet-enter-from > div:last-child,
.sheet-leave-to > div:last-child {
    transform: translateY(100%);
}

/* Remove number input spinners */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type="number"] {
    -moz-appearance: textfield;
}
</style>
