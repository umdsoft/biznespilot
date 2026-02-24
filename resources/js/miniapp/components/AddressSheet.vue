<template>
    <Teleport to="body">
        <transition name="sheet">
            <div v-if="modelValue" class="fixed inset-0 z-50" @click.self="close">
                <div class="sheet-backdrop absolute inset-0" @click="close"></div>
                <div
                    class="absolute bottom-0 left-0 right-0 rounded-t-2xl safe-area-bottom"
                    style="background-color: var(--tg-theme-bg-color); max-height: 90vh; display: flex; flex-direction: column"
                >
                    <!-- Handle -->
                    <div class="flex justify-center pt-2 pb-1 shrink-0">
                        <div class="h-1 w-8 rounded-full" style="background-color: var(--tg-theme-hint-color); opacity: 0.3"></div>
                    </div>

                    <!-- Header -->
                    <div class="flex items-center justify-between px-4 pb-3 shrink-0">
                        <h3 style="font-size: 17px; font-weight: 600; color: var(--tg-theme-text-color)">
                            {{ showForm ? 'Yangi manzil' : 'Manzilni tanlang' }}
                        </h3>
                        <button v-if="showForm" @click="showForm = false" class="btn-ghost" style="font-size: 14px; color: var(--tg-theme-hint-color)">
                            Orqaga
                        </button>
                    </div>

                    <!-- Scrollable content -->
                    <div class="overflow-y-auto" style="flex: 1; padding: 0 16px 20px; -webkit-overflow-scrolling: touch">
                        <!-- Add new address form -->
                        <template v-if="showForm">
                            <AddressForm @saved="onAddressSaved" />
                        </template>

                        <!-- Saved addresses list -->
                        <template v-else>
                            <!-- Address cards -->
                            <div v-if="userStore.savedAddresses.length" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px">
                                <button
                                    v-for="addr in userStore.savedAddresses"
                                    :key="addr.id"
                                    @click="selectAddress(addr)"
                                    class="address-card tap-active"
                                    :class="{ 'is-selected': selectedId === addr.id }"
                                >
                                    <div style="flex: 1; min-width: 0">
                                        <div class="flex items-center" style="gap: 6px; margin-bottom: 4px">
                                            <span style="font-size: 14px; font-weight: 600; color: var(--tg-theme-text-color)">
                                                {{ addr.label || 'Manzil' }}
                                            </span>
                                            <span
                                                v-if="addr.is_default"
                                                style="font-size: 11px; font-weight: 500; padding: 1px 8px; border-radius: 10px; background: rgba(16,185,129,0.1); color: var(--color-success)"
                                            >
                                                Asosiy
                                            </span>
                                            <span
                                                v-if="addr.latitude"
                                                style="font-size: 11px; color: var(--tg-theme-hint-color)"
                                            >
                                                📍
                                            </span>
                                        </div>
                                        <p class="line-clamp-2" style="font-size: 13px; color: var(--tg-theme-hint-color); line-height: 1.4">
                                            {{ addr.full_address || formatAddress(addr) }}
                                        </p>
                                    </div>

                                    <!-- Radio circle -->
                                    <div
                                        class="radio-circle shrink-0"
                                        :class="{ 'is-selected': selectedId === addr.id }"
                                    >
                                        <div v-if="selectedId === addr.id" style="width: 8px; height: 8px; border-radius: 50%; background: #fff"></div>
                                    </div>
                                </button>
                            </div>

                            <!-- Empty state -->
                            <div v-if="!userStore.savedAddresses.length" style="text-align: center; padding: 24px 0">
                                <p style="font-size: 36px; margin-bottom: 8px">📍</p>
                                <p style="font-size: 15px; font-weight: 500; color: var(--tg-theme-text-color); margin-bottom: 4px">Saqlangan manzil yo'q</p>
                                <p style="font-size: 13px; color: var(--tg-theme-hint-color)">Yangi manzil qo'shib, keyingi safar tezroq buyurtma bering</p>
                            </div>

                            <!-- Add new button -->
                            <button @click="openForm" class="add-address-btn tap-active">
                                <svg style="width: 18px; height: 18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                Yangi manzil qo'shish
                            </button>

                            <!-- Confirm button (when address selected) -->
                            <button
                                v-if="selectedId && userStore.savedAddresses.length"
                                @click="confirmSelection"
                                class="btn-primary"
                                style="margin-top: 12px"
                            >
                                Tanlash
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useUserStore } from '../stores/user'
import { useTelegram } from '../composables/useTelegram'
import AddressForm from './AddressForm.vue'

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    currentAddressId: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue', 'select'])
const userStore = useUserStore()
const { hapticImpact } = useTelegram()

const showForm = ref(false)
const selectedId = ref(null)

watch(() => props.modelValue, (val) => {
    if (val) {
        selectedId.value = props.currentAddressId || null
        showForm.value = false
    }
})

function selectAddress(addr) {
    selectedId.value = addr.id
    hapticImpact('light')
}

function confirmSelection() {
    const addr = userStore.savedAddresses.find(a => a.id === selectedId.value)
    if (addr) {
        emit('select', addr)
        close()
    }
}

function openForm() {
    showForm.value = true
    hapticImpact('light')
}

async function onAddressSaved() {
    showForm.value = false
    // Refetch profile to ensure addresses are synced
    await userStore.fetchProfile()
    // Auto-select the newest address
    const addresses = userStore.savedAddresses
    if (addresses.length) {
        const newest = addresses[0]
        selectedId.value = newest.id
        emit('select', newest)
    }
    close()
}

function close() {
    emit('update:modelValue', false)
}

function formatAddress(addr) {
    return [addr.city, addr.district, addr.street].filter(Boolean).join(', ')
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

.address-card {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 14px;
    border-radius: var(--radius-md);
    border: 1.5px solid var(--color-border);
    background: var(--tg-theme-bg-color);
    text-align: left;
    transition: all 0.15s ease;
}
.address-card.is-selected {
    border-color: var(--tg-theme-button-color);
    background: color-mix(in srgb, var(--tg-theme-button-color) 6%, var(--tg-theme-bg-color));
}

.add-address-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    height: 46px;
    border-radius: var(--radius-md);
    border: 1.5px dashed var(--color-border);
    background: transparent;
    font-size: 14px;
    font-weight: 500;
    color: var(--tg-theme-button-color);
}
</style>
