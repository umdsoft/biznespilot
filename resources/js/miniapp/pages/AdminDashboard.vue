<template>
    <div class="pb-20">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold" style="color: var(--tg-theme-text-color)">
                        Boshqaruv paneli
                    </h1>
                    <p v-if="dashboard" class="text-xs" style="color: var(--tg-theme-hint-color)">
                        {{ dashboard.store_name }}
                    </p>
                </div>
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-full"
                    style="background-color: var(--tg-theme-button-color)"
                >
                    <svg class="h-5 w-5" style="color: var(--tg-theme-button-text-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-20">
            <LoadingSpinner />
        </div>

        <!-- Error -->
        <div v-else-if="error" class="px-4 py-10 text-center">
            <p class="text-sm" style="color: var(--tg-theme-hint-color)">{{ error }}</p>
            <button
                @click="loadDashboard"
                class="mt-3 rounded-lg px-4 py-2 text-sm font-medium"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                Qayta yuklash
            </button>
        </div>

        <template v-else-if="dashboard">
            <div class="px-4 space-y-4">
                <!-- Today Stats -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                        <p class="text-xs font-medium" style="color: var(--tg-theme-hint-color)">
                            Bugungi buyurtmalar
                        </p>
                        <p class="mt-1 text-2xl font-bold" style="color: var(--tg-theme-text-color)">
                            {{ dashboard.today.orders }}
                        </p>
                    </div>
                    <div class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                        <p class="text-xs font-medium" style="color: var(--tg-theme-hint-color)">
                            Bugungi daromad
                        </p>
                        <p class="mt-1 text-2xl font-bold" style="color: var(--tg-theme-text-color)">
                            {{ formatPrice(dashboard.today.revenue) }}
                        </p>
                    </div>
                </div>

                <!-- Pending Orders Alert -->
                <button
                    v-if="dashboard.pending_orders > 0"
                    @click="goToOrders"
                    class="w-full rounded-xl p-4 text-left"
                    style="background-color: #fef3c7"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                            style="background-color: #f59e0b"
                        >
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold" style="color: #92400e">
                                {{ dashboard.pending_orders }} ta faol buyurtma
                            </p>
                            <p class="mt-0.5 text-xs" style="color: #a16207">
                                Tekshirish va tasdiqlash kerak
                            </p>
                        </div>
                        <svg class="ml-auto h-5 w-5 shrink-0" style="color: #a16207" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>

                <!-- Total Stats -->
                <div class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <h2 class="mb-3 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                        Umumiy statistika
                    </h2>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm" style="color: var(--tg-theme-hint-color)">Jami buyurtmalar</span>
                            <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                                {{ dashboard.total.orders }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm" style="color: var(--tg-theme-hint-color)">Jami daromad</span>
                            <span class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                                {{ formatPrice(dashboard.total.revenue) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-2 gap-3">
                    <button
                        @click="goToOrders"
                        class="flex items-center gap-3 rounded-xl p-4"
                        style="background-color: var(--tg-theme-secondary-bg-color)"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            style="background-color: var(--tg-theme-button-color); opacity: 0.15"
                        >
                            <svg class="h-5 w-5" style="color: var(--tg-theme-button-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">Buyurtmalar</p>
                            <p class="text-xs" style="color: var(--tg-theme-hint-color)">Boshqarish</p>
                        </div>
                    </button>
                    <button
                        @click="goToStats"
                        class="flex items-center gap-3 rounded-xl p-4"
                        style="background-color: var(--tg-theme-secondary-bg-color)"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            style="background-color: var(--tg-theme-button-color); opacity: 0.15"
                        >
                            <svg class="h-5 w-5" style="color: var(--tg-theme-button-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">Statistika</p>
                            <p class="text-xs" style="color: var(--tg-theme-hint-color)">7 kunlik</p>
                        </div>
                    </button>
                </div>

                <!-- 7-day mini chart -->
                <div v-if="stats" class="rounded-xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                    <h2 class="mb-3 text-sm font-semibold" style="color: var(--tg-theme-text-color)">
                        Oxirgi 7 kun
                    </h2>
                    <div class="flex items-end justify-between gap-1" style="height: 80px">
                        <div
                            v-for="day in stats.daily"
                            :key="day.date"
                            class="flex flex-1 flex-col items-center gap-1"
                        >
                            <div
                                class="w-full rounded-t"
                                :style="{
                                    backgroundColor: 'var(--tg-theme-button-color)',
                                    height: getBarHeight(day.revenue) + 'px',
                                    minHeight: '4px',
                                    opacity: day.revenue > 0 ? 1 : 0.2,
                                }"
                            />
                            <span class="text-[9px]" style="color: var(--tg-theme-hint-color)">
                                {{ formatShortDate(day.date) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'
import { useTelegram } from '../composables/useTelegram'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import BackButton from '../components/BackButton.vue'

const router = useRouter()
const { get } = useApi()
const { hapticImpact } = useTelegram()

const dashboard = ref(null)
const stats = ref(null)
const loading = ref(false)
const error = ref(null)

function formatPrice(price) {
    if (!price) return "0 so'm"
    return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + " so'm"
}

function formatShortDate(dateStr) {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleDateString('uz-Latn', { day: 'numeric', month: 'short' })
}

function getBarHeight(revenue) {
    if (!stats.value?.daily) return 4
    const maxRevenue = Math.max(...stats.value.daily.map(d => d.revenue), 1)
    return Math.max(4, Math.round((revenue / maxRevenue) * 60))
}

function goToOrders() {
    hapticImpact('light')
    router.push({ name: 'admin-orders' })
}

function goToStats() {
    hapticImpact('light')
    // Stats are shown inline on dashboard, but can be extended
    // For now scroll to the chart section
}

async function loadDashboard() {
    loading.value = true
    error.value = null
    try {
        const [dashboardData, statsData] = await Promise.all([
            get('/admin/dashboard'),
            get('/admin/stats'),
        ])
        dashboard.value = dashboardData
        stats.value = statsData
    } catch (err) {
        if (err.response?.status === 403) {
            error.value = 'Sizda admin huquqi yo\'q'
        } else {
            error.value = 'Ma\'lumotlarni yuklashda xatolik'
        }
        console.error('[MiniApp Admin] Dashboard error:', err)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    loadDashboard()
})
</script>
