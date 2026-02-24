<template>
    <div class="pb-safe">
        <!-- Header -->
        <div class="header-blur sticky top-0 z-30">
            <div class="flex items-center gap-3" style="padding: 12px 16px">
                <button @click="goBack" class="tap-active flex items-center justify-center w-9 h-9 rounded-full" style="background: var(--tg-theme-secondary-bg-color)">
                    <svg style="width: 20px; height: 20px; color: var(--tg-theme-text-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </button>
                <h1 class="text-base font-bold" style="color: var(--tg-theme-text-color)">So'rov tafsiloti</h1>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" style="padding: 16px">
            <div class="skeleton" style="height: 80px; border-radius: 16px; margin-bottom: 16px"></div>
            <div class="skeleton" style="height: 200px; border-radius: 16px"></div>
        </div>

        <template v-else-if="request">
            <!-- Status -->
            <div style="padding: 16px">
                <div class="rounded-2xl p-4" style="background: var(--tg-theme-secondary-bg-color)">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                            {{ request.service_name || request.service?.name || 'So\'rov' }}
                        </span>
                        <span
                            class="text-xs font-medium px-2.5 py-1 rounded-full"
                            :style="{ background: store.getStatusBg(request.status), color: store.getStatusColor(request.status) }"
                        >{{ store.getStatusLabel(request.status) }}</span>
                    </div>
                    <p v-if="request.total_price" class="text-lg font-bold" style="color: var(--tg-theme-button-color)">{{ formatPrice(request.total_price) }}</p>
                </div>
            </div>

            <!-- Timeline -->
            <div v-if="request.timeline?.length" style="padding: 0 16px 16px">
                <h3 class="text-sm font-semibold mb-3" style="color: var(--tg-theme-text-color)">Jarayon</h3>
                <div class="relative" style="padding-left: 24px">
                    <div
                        v-for="(event, i) in request.timeline"
                        :key="i"
                        class="relative mb-4 last:mb-0"
                    >
                        <!-- Vertical line -->
                        <div
                            v-if="i < request.timeline.length - 1"
                            class="absolute"
                            style="left: -18px; top: 12px; bottom: -20px; width: 2px; background: var(--color-border)"
                        ></div>
                        <!-- Dot -->
                        <div
                            class="absolute w-3 h-3 rounded-full"
                            :style="{
                                left: '-21px',
                                top: '4px',
                                background: i === 0 ? 'var(--tg-theme-button-color)' : 'var(--color-border)',
                            }"
                        ></div>
                        <div>
                            <p class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ event.title }}</p>
                            <p v-if="event.description" class="text-xs mt-0.5" style="color: var(--tg-theme-hint-color)">{{ event.description }}</p>
                            <p class="text-[10px] mt-1" style="color: var(--tg-theme-hint-color)">{{ formatDateTime(event.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div style="padding: 0 16px 16px">
                <h3 class="text-sm font-semibold mb-2" style="color: var(--tg-theme-text-color)">Tavsif</h3>
                <div class="rounded-xl p-3" style="background: var(--tg-theme-secondary-bg-color)">
                    <p class="text-sm" style="color: var(--tg-theme-text-color); line-height: 1.6; white-space: pre-wrap">{{ request.description }}</p>
                </div>
            </div>

            <!-- Images -->
            <div v-if="request.images?.length" style="padding: 0 16px 16px">
                <h3 class="text-sm font-semibold mb-2" style="color: var(--tg-theme-text-color)">Rasmlar</h3>
                <div class="flex gap-2 overflow-x-auto no-scrollbar">
                    <div
                        v-for="(img, i) in request.images"
                        :key="i"
                        class="shrink-0 w-24 h-24 rounded-xl overflow-hidden"
                    >
                        <img :src="img.url || img" class="w-full h-full object-cover" />
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div style="padding: 0 16px 16px">
                <div class="rounded-2xl p-4" style="background: var(--tg-theme-secondary-bg-color)">
                    <div v-if="request.address" class="flex items-start justify-between mb-3">
                        <span class="text-sm shrink-0" style="color: var(--tg-theme-hint-color)">Manzil</span>
                        <span class="text-sm text-right ml-4" style="color: var(--tg-theme-text-color)">{{ request.address }}</span>
                    </div>
                    <div v-if="request.preferred_date" class="flex items-center justify-between mb-3">
                        <span class="text-sm" style="color: var(--tg-theme-hint-color)">Qulay sana</span>
                        <span class="text-sm" style="color: var(--tg-theme-text-color)">{{ formatDate(request.preferred_date) }}</span>
                    </div>
                    <div v-if="request.master || request.staff" class="flex items-center justify-between mb-3">
                        <span class="text-sm" style="color: var(--tg-theme-hint-color)">Usta</span>
                        <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ (request.master || request.staff)?.name }}</span>
                    </div>
                    <div v-if="request.phone" class="flex items-center justify-between">
                        <span class="text-sm" style="color: var(--tg-theme-hint-color)">Telefon</span>
                        <span class="text-sm" style="color: var(--tg-theme-text-color)">{{ request.phone }}</span>
                    </div>
                </div>
            </div>

            <!-- Cancel -->
            <div v-if="canCancel" style="padding: 0 16px 16px">
                <button @click="handleCancel" class="w-full text-center py-3 rounded-xl text-sm font-medium" style="color: var(--color-error); background: var(--tg-theme-secondary-bg-color)">
                    So'rovni bekor qilish
                </button>
            </div>

            <!-- Created at -->
            <div style="padding: 0 16px 24px">
                <p class="text-xs" style="color: var(--tg-theme-hint-color)">
                    Yaratilgan: {{ formatDateTime(request.created_at) }}
                </p>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useServiceRequestStore } from '../../stores/serviceRequest'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const props = defineProps({ id: String })
const router = useRouter()
const route = useRoute()
const store = useServiceRequestStore()
const { hapticImpact, showConfirm, hapticNotification } = useTelegram()

const loading = ref(true)
const request = ref(null)

const canCancel = computed(() =>
    request.value && ['pending'].includes(request.value.status)
)

function formatDate(dateStr) {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long', year: 'numeric' })
}

function formatDateTime(dateStr) {
    if (!dateStr) return ''
    return new Date(dateStr).toLocaleString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function handleCancel() {
    const confirmed = await showConfirm("So'rovni bekor qilmoqchimisiz?")
    if (!confirmed) return
    hapticImpact('medium')
    const success = await store.cancelRequest(request.value.id)
    if (success) {
        hapticNotification('success')
        request.value = { ...request.value, status: 'cancelled' }
    }
}

function goBack() {
    hapticImpact('light')
    router.back()
}

onMounted(async () => {
    loading.value = true
    try {
        const requestId = props.id || route.params.id
        request.value = await store.fetchRequest(requestId)
    } catch (err) {
        console.error('[RequestDetail] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
