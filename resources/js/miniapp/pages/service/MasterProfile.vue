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
                <h1 class="text-base font-bold" style="color: var(--tg-theme-text-color)">Usta profili</h1>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" style="padding: 16px; text-align: center">
            <div class="skeleton mx-auto" style="width: 96px; height: 96px; border-radius: 50%; margin-bottom: 16px"></div>
            <div class="skeleton mx-auto" style="height: 20px; width: 40%; border-radius: 8px; margin-bottom: 8px"></div>
            <div class="skeleton mx-auto" style="height: 16px; width: 60%; border-radius: 8px"></div>
        </div>

        <template v-else-if="master">
            <!-- Profile header -->
            <div style="padding: 24px 16px; text-align: center">
                <div class="mx-auto w-24 h-24 rounded-full overflow-hidden" style="background: var(--tg-theme-secondary-bg-color)">
                    <img v-if="master.photo_url" :src="master.photo_url" :alt="master.name" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center" style="font-size: 40px">👤</div>
                </div>
                <h2 class="text-lg font-bold mt-3" style="color: var(--tg-theme-text-color)">{{ master.name }}</h2>
                <p v-if="master.position" class="text-sm" style="color: var(--tg-theme-hint-color)">{{ master.position }}</p>
            </div>

            <!-- Specializations -->
            <div v-if="master.specializations?.length" style="padding: 0 16px 16px">
                <h3 class="text-sm font-semibold mb-2" style="color: var(--tg-theme-text-color)">Mutaxassisliklar</h3>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="spec in master.specializations"
                        :key="spec"
                        class="text-xs px-3 py-1.5 rounded-full"
                        style="background: var(--tg-theme-secondary-bg-color); color: var(--tg-theme-text-color)"
                    >{{ spec }}</span>
                </div>
            </div>

            <!-- Bio -->
            <div v-if="master.bio" style="padding: 0 16px 16px">
                <h3 class="text-sm font-semibold mb-2" style="color: var(--tg-theme-text-color)">Haqida</h3>
                <div class="rounded-xl p-3" style="background: var(--tg-theme-secondary-bg-color)">
                    <p class="text-sm" style="color: var(--tg-theme-text-color); line-height: 1.6; white-space: pre-wrap">{{ master.bio }}</p>
                </div>
            </div>

            <!-- Contact -->
            <div v-if="master.phone" style="padding: 0 16px 16px">
                <div class="rounded-xl p-3 flex items-center justify-between" style="background: var(--tg-theme-secondary-bg-color)">
                    <div>
                        <p class="text-xs" style="color: var(--tg-theme-hint-color)">Telefon</p>
                        <p class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ master.phone }}</p>
                    </div>
                    <a :href="'tel:' + master.phone" class="w-10 h-10 rounded-full flex items-center justify-center tap-active" style="background: var(--tg-theme-button-color)">
                        <svg style="width: 18px; height: 18px; color: var(--tg-theme-button-text-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Reviews section (placeholder for future) -->
            <div v-if="master.reviews?.length" style="padding: 0 16px 16px">
                <h3 class="text-sm font-semibold mb-3" style="color: var(--tg-theme-text-color)">Sharhlar</h3>
                <div class="flex flex-col gap-3">
                    <div
                        v-for="review in master.reviews"
                        :key="review.id"
                        class="rounded-xl p-3"
                        style="background: var(--tg-theme-secondary-bg-color)"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ review.author }}</span>
                            <div class="flex items-center gap-0.5">
                                <span v-for="i in 5" :key="i" class="text-xs" :style="{ opacity: i <= review.rating ? 1 : 0.3 }">⭐</span>
                            </div>
                        </div>
                        <p class="text-xs" style="color: var(--tg-theme-hint-color); line-height: 1.5">{{ review.text }}</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useServiceRequestStore } from '../../stores/serviceRequest'
import { useTelegram } from '../../composables/useTelegram'

const props = defineProps({ id: String })
const router = useRouter()
const route = useRoute()
const store = useServiceRequestStore()
const { hapticImpact } = useTelegram()

const loading = ref(true)
const master = ref(null)

function goBack() {
    hapticImpact('light')
    router.back()
}

onMounted(async () => {
    loading.value = true
    try {
        const masterId = props.id || route.params.id
        master.value = await store.fetchMaster(masterId)
    } catch (err) {
        console.error('[MasterProfile] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>
