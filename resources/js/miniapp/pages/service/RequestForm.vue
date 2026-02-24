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
                <h1 class="text-base font-bold" style="color: var(--tg-theme-text-color)">So'rov yuborish</h1>
            </div>
        </div>

        <div style="padding: 16px">
            <!-- Service info -->
            <div class="rounded-2xl p-4 mb-4" style="background: var(--tg-theme-secondary-bg-color)">
                <p class="text-sm font-semibold" style="color: var(--tg-theme-text-color)">{{ serviceName }}</p>
                <p v-if="pricingType !== 'quote'" class="text-sm mt-1" style="color: var(--tg-theme-button-color)">{{ formatPrice(servicePrice) }}</p>
                <p v-else class="text-xs mt-1" style="color: var(--tg-theme-hint-color)">Narx kelishiladi</p>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label class="text-sm font-semibold mb-2 block" style="color: var(--tg-theme-text-color)">
                    Tavsif <span style="color: var(--color-error)">*</span>
                </label>
                <textarea
                    v-model="description"
                    placeholder="Muammo yoki so'rovingizni batafsil yozing..."
                    class="form-input"
                    style="min-height: 120px; resize: none"
                ></textarea>
            </div>

            <!-- Images -->
            <div class="mb-4">
                <label class="text-sm font-semibold mb-2 block" style="color: var(--tg-theme-text-color)">Rasmlar (ixtiyoriy)</label>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="(img, i) in imageUrls"
                        :key="i"
                        class="relative w-20 h-20 rounded-xl overflow-hidden"
                    >
                        <img :src="img" class="w-full h-full object-cover" />
                        <button
                            @click="removeImage(i)"
                            class="absolute top-1 right-1 w-5 h-5 rounded-full flex items-center justify-center"
                            style="background: rgba(0,0,0,0.6)"
                        >
                            <svg style="width: 12px; height: 12px; color: #fff" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <label
                        v-if="images.length < 5"
                        class="w-20 h-20 rounded-xl flex flex-col items-center justify-center tap-active cursor-pointer"
                        style="border: 2px dashed var(--color-border)"
                    >
                        <svg style="width: 24px; height: 24px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 002.25-2.25V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
                        </svg>
                        <span class="text-[10px] mt-1" style="color: var(--tg-theme-hint-color)">Rasm</span>
                        <input type="file" accept="image/*" multiple class="hidden" @change="handleImages" />
                    </label>
                </div>
            </div>

            <!-- Address -->
            <div v-if="requiresAddress" class="mb-4">
                <label class="text-sm font-semibold mb-2 block" style="color: var(--tg-theme-text-color)">
                    Manzil <span style="color: var(--color-error)">*</span>
                </label>
                <textarea
                    v-model="address"
                    placeholder="To'liq manzilni kiriting..."
                    class="form-input"
                    style="min-height: 60px; resize: none"
                ></textarea>
            </div>

            <!-- Preferred date -->
            <div class="mb-4">
                <label class="text-sm font-semibold mb-2 block" style="color: var(--tg-theme-text-color)">Qulay sana (ixtiyoriy)</label>
                <input type="date" v-model="preferredDate" class="form-input" :min="todayStr" />
            </div>

            <!-- Phone -->
            <div class="mb-4">
                <label class="text-sm font-semibold mb-2 block" style="color: var(--tg-theme-text-color)">Telefon raqam</label>
                <input
                    v-model="phone"
                    type="tel"
                    placeholder="+998 90 123 45 67"
                    class="form-input"
                />
            </div>

            <!-- Notes -->
            <div class="mb-4">
                <label class="text-sm font-semibold mb-2 block" style="color: var(--tg-theme-text-color)">Qo'shimcha izoh</label>
                <textarea
                    v-model="notes"
                    placeholder="Ixtiyoriy..."
                    class="form-input"
                    style="min-height: 60px; resize: none"
                ></textarea>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="sticky-bottom-bar">
            <button
                @click="submitRequest"
                :disabled="!canSubmit || store.creating"
                class="btn-primary"
            >
                <span v-if="store.creating">Yuborilmoqda...</span>
                <span v-else>So'rov yuborish</span>
            </button>
            <p v-if="store.error" class="form-error mt-2 text-center">{{ store.error }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useServiceRequestStore } from '../../stores/serviceRequest'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const router = useRouter()
const route = useRoute()
const store = useServiceRequestStore()
const { hapticImpact, hapticNotification } = useTelegram()

const serviceId = route.query.service_id
const serviceName = route.query.service_name || 'Xizmat'
const servicePrice = Number(route.query.service_price) || 0
const pricingType = route.query.pricing_type || 'fixed'
const requiresAddress = route.query.requires_address === '1'

const description = ref('')
const address = ref('')
const phone = ref('')
const notes = ref('')
const preferredDate = ref('')
const images = ref([])
const imageUrls = ref([])

const todayStr = new Date().toISOString().split('T')[0]

const canSubmit = computed(() => {
    if (!description.value.trim()) return false
    if (requiresAddress && !address.value.trim()) return false
    return true
})

function handleImages(e) {
    const files = Array.from(e.target.files || [])
    const remaining = 5 - images.value.length
    const newFiles = files.slice(0, remaining)

    for (const file of newFiles) {
        images.value.push(file)
        imageUrls.value.push(URL.createObjectURL(file))
    }
    e.target.value = ''
}

function removeImage(index) {
    URL.revokeObjectURL(imageUrls.value[index])
    images.value.splice(index, 1)
    imageUrls.value.splice(index, 1)
}

async function submitRequest() {
    if (!canSubmit.value) return
    hapticImpact('medium')

    const formData = new FormData()
    formData.append('service_id', serviceId)
    formData.append('description', description.value.trim())
    if (address.value.trim()) formData.append('address', address.value.trim())
    if (phone.value.trim()) formData.append('phone', phone.value.trim())
    if (notes.value.trim()) formData.append('notes', notes.value.trim())
    if (preferredDate.value) formData.append('preferred_date', preferredDate.value)

    for (const img of images.value) {
        formData.append('images[]', img)
    }

    const request = await store.createRequest(formData)
    if (request) {
        hapticNotification('success')
        router.replace({ name: 'request-detail', params: { id: request.id } })
    }
}

function goBack() {
    hapticImpact('light')
    router.back()
}
</script>
