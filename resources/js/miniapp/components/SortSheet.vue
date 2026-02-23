<template>
    <Teleport to="body">
        <transition name="sheet">
            <div v-if="modelValue" class="fixed inset-0 z-50" @click.self="close">
                <div class="sheet-backdrop absolute inset-0" @click="close"></div>
                <div class="absolute bottom-0 left-0 right-0 rounded-t-2xl safe-area-bottom"
                     style="background-color: var(--tg-theme-bg-color)"
                >
                    <!-- Handle -->
                    <div class="flex justify-center pt-2 pb-1">
                        <div class="h-1 w-8 rounded-full" style="background-color: var(--tg-theme-hint-color); opacity: 0.3"></div>
                    </div>

                    <div class="px-4 pb-2">
                        <h3 class="text-base font-semibold" style="color: var(--tg-theme-text-color)">Saralash</h3>
                    </div>

                    <div class="px-2 pb-4">
                        <button
                            v-for="option in sortOptions"
                            :key="option.value"
                            @click="select(option.value)"
                            class="flex w-full items-center justify-between rounded-xl px-3 py-3 tap-active"
                            :style="option.value === current ? { backgroundColor: 'var(--tg-theme-secondary-bg-color)' } : {}"
                        >
                            <span class="text-sm" style="color: var(--tg-theme-text-color)">{{ option.label }}</span>
                            <svg v-if="option.value === current" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="var(--tg-theme-button-color)" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import { useTelegram } from '../composables/useTelegram'

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    current: { type: String, default: 'default' },
})

const emit = defineEmits(['update:modelValue', 'select'])
const { hapticImpact } = useTelegram()

const sortOptions = [
    { value: 'default', label: 'Standart' },
    { value: 'price_asc', label: 'Arzon avval' },
    { value: 'price_desc', label: 'Qimmat avval' },
    { value: 'newest', label: 'Yangi avval' },
    { value: 'name_asc', label: 'Nomi bo\'yicha (A-Z)' },
]

function select(value) {
    hapticImpact('light')
    emit('select', value)
    emit('update:modelValue', false)
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
</style>
