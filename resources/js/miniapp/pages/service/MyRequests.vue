<template>
    <div class="pb-nav">
        <!-- Header -->
        <div class="header-blur sticky top-0 z-30">
            <div style="padding: 12px 16px">
                <h1 style="font-size: 22px; font-weight: 700; color: var(--tg-theme-text-color)">So'rovlar</h1>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="store.loading && store.requests.length === 0" style="padding: 16px">
            <div v-for="i in 3" :key="i" class="skeleton" style="height: 90px; border-radius: 14px; margin-bottom: 12px"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="store.requests.length === 0" class="empty-state" style="padding: 64px 20px">
            <div class="empty-state-icon">📋</div>
            <p class="empty-state-title">So'rovlar yo'q</p>
            <p class="empty-state-subtitle">Xizmat tanlab so'rov yuboring</p>
            <button @click="goHome" class="btn-primary" style="margin-top: 16px; max-width: 200px">Xizmatlarga o'tish</button>
        </div>

        <!-- Requests list -->
        <div v-else style="padding: 0 16px">
            <!-- Active -->
            <div v-if="store.activeRequests.length" style="margin-bottom: 24px">
                <h2 class="text-xs font-semibold uppercase tracking-wide mb-3" style="color: var(--tg-theme-hint-color)">Faol so'rovlar</h2>
                <div class="flex flex-col gap-3">
                    <button
                        v-for="req in store.activeRequests"
                        :key="req.id"
                        @click="goToRequest(req.id)"
                        class="w-full text-left tap-active rounded-2xl p-4"
                        style="background: var(--tg-theme-secondary-bg-color)"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold line-clamp-1" style="color: var(--tg-theme-text-color)">
                                {{ req.service_name || req.service?.name || 'So\'rov' }}
                            </span>
                            <span
                                class="text-xs font-medium px-2.5 py-1 rounded-full shrink-0 ml-2"
                                :style="{ background: store.getStatusBg(req.status), color: store.getStatusColor(req.status) }"
                            >{{ store.getStatusLabel(req.status) }}</span>
                        </div>
                        <p class="text-xs line-clamp-2 mb-2" style="color: var(--tg-theme-hint-color)">{{ req.description }}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs" style="color: var(--tg-theme-hint-color)">{{ formatDate(req.created_at) }}</span>
                            <span v-if="req.total_price" class="text-sm font-bold" style="color: var(--tg-theme-text-color)">{{ formatPrice(req.total_price) }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Past -->
            <div v-if="store.pastRequests.length">
                <h2 class="text-xs font-semibold uppercase tracking-wide mb-3" style="color: var(--tg-theme-hint-color)">Yakunlangan</h2>
                <div class="flex flex-col gap-3">
                    <button
                        v-for="req in store.pastRequests"
                        :key="req.id"
                        @click="goToRequest(req.id)"
                        class="w-full text-left tap-active rounded-2xl p-4"
                        style="background: var(--tg-theme-secondary-bg-color)"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold line-clamp-1" style="color: var(--tg-theme-text-color)">
                                {{ req.service_name || req.service?.name || 'So\'rov' }}
                            </span>
                            <span
                                class="text-xs font-medium px-2.5 py-1 rounded-full shrink-0 ml-2"
                                :style="{ background: store.getStatusBg(req.status), color: store.getStatusColor(req.status) }"
                            >{{ store.getStatusLabel(req.status) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs" style="color: var(--tg-theme-hint-color)">{{ formatDate(req.created_at) }}</span>
                            <span v-if="req.total_price" class="text-sm font-bold" style="color: var(--tg-theme-text-color)">{{ formatPrice(req.total_price) }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Load more -->
            <div v-if="store.hasMore" class="flex justify-center" style="padding: 24px 0">
                <div v-if="store.loading" class="skeleton" style="width: 24px; height: 24px; border-radius: 50%"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useServiceRequestStore } from '../../stores/serviceRequest'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const router = useRouter()
const store = useServiceRequestStore()
const { hapticImpact } = useTelegram()

function formatDate(dateStr) {
    if (!dateStr) return ''
    return new Date(dateStr).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' })
}

function goToRequest(id) {
    hapticImpact('light')
    router.push({ name: 'request-detail', params: { id } })
}

function goHome() {
    hapticImpact('light')
    router.push({ name: 'home' })
}

onMounted(() => {
    store.fetchRequests(true)
})
</script>
