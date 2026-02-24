<template>
    <div style="padding-bottom: 100px">
        <BackButton />

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center" style="padding: 80px 0">
            <LoadingSpinner />
        </div>

        <!-- Not found -->
        <div v-else-if="!course" class="empty-state" style="min-height: 40vh">
            <div class="empty-state-icon">🎓</div>
            <p class="empty-state-title">Kurs topilmadi</p>
        </div>

        <template v-else>
            <!-- Cover image -->
            <div class="relative" style="aspect-ratio: 16/9; overflow: hidden">
                <img
                    v-if="course.image || course.image_url"
                    :src="course.image || course.image_url"
                    :alt="course.name"
                    class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                    <span style="font-size: 56px">🎓</span>
                </div>
                <!-- Discount badge -->
                <div v-if="discountPercent > 0" class="absolute top-3 left-3 px-2.5 py-1 rounded-xl text-sm font-bold text-white" style="background: var(--color-error)">
                    -{{ discountPercent }}%
                </div>
                <!-- Level badge -->
                <div v-if="attrs.level" class="absolute top-3 right-3 px-2.5 py-1 rounded-xl text-sm font-medium" :style="levelStyle(attrs.level)">
                    {{ levelLabel(attrs.level) }}
                </div>
            </div>

            <!-- Course info -->
            <div style="padding: 16px">
                <h1 style="font-size: 20px; font-weight: 700; line-height: 1.3; color: var(--tg-theme-text-color)">
                    {{ course.name }}
                </h1>

                <!-- Price -->
                <div class="flex items-center" style="margin-top: 10px; gap: 8px">
                    <span style="font-size: 22px; font-weight: 700; color: var(--tg-theme-text-color)">
                        {{ formatPrice(course.price) }}
                    </span>
                    <span
                        v-if="attrs.has_discount"
                        style="font-size: 15px; text-decoration: line-through; color: var(--tg-theme-hint-color)"
                    >
                        {{ formatPrice(attrs.compare_price) }}
                    </span>
                </div>

                <!-- Quick stats row -->
                <div class="flex flex-wrap gap-2 mt-3">
                    <div v-if="attrs.duration_hours" class="stat-chip">
                        <svg style="width: 14px; height: 14px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ attrs.duration_hours }} soat
                    </div>
                    <div v-if="attrs.format" class="stat-chip">
                        <svg style="width: 14px; height: 14px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                        {{ formatLabel(attrs.format) }}
                    </div>
                    <div v-if="attrs.certificate_included" class="stat-chip" style="color: var(--color-success)">
                        <svg style="width: 14px; height: 14px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg>
                        Sertifikat
                    </div>
                    <div v-if="attrs.enrolled_count" class="stat-chip">
                        <svg style="width: 14px; height: 14px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        {{ attrs.enrolled_count }} o'quvchi
                    </div>
                </div>

                <!-- Instructor card -->
                <div v-if="attrs.instructor" class="mt-4 flex items-center gap-3 p-3 rounded-2xl" style="background: var(--tg-theme-secondary-bg-color)">
                    <div class="shrink-0 w-12 h-12 rounded-full overflow-hidden" style="background: var(--tg-theme-bg-color)">
                        <img v-if="course.instructor_photo" :src="course.instructor_photo" :alt="attrs.instructor" class="w-full h-full object-cover" />
                        <div v-else class="flex w-full h-full items-center justify-center" style="font-size: 22px">
                            👨‍🏫
                        </div>
                    </div>
                    <div style="min-width: 0">
                        <p style="font-size: 11px; color: var(--tg-theme-hint-color)">O'qituvchi</p>
                        <p class="text-sm font-semibold truncate" style="color: var(--tg-theme-text-color)">{{ attrs.instructor }}</p>
                    </div>
                </div>

                <!-- Spots left -->
                <div
                    v-if="spotsInfo"
                    class="mt-3 flex items-center gap-2 p-3 rounded-xl"
                    :style="{ background: spotsInfo.urgent ? 'rgba(239,68,68,0.1)' : 'rgba(245,158,11,0.1)' }"
                >
                    <svg style="width: 16px; height: 16px" :style="{ color: spotsInfo.urgent ? '#ef4444' : '#f59e0b' }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <span class="text-sm font-medium" :style="{ color: spotsInfo.urgent ? '#ef4444' : '#f59e0b' }">{{ spotsInfo.text }}</span>
                </div>

                <!-- Start date -->
                <div v-if="attrs.start_date" class="mt-3 flex items-center gap-2 p-3 rounded-xl" style="background: var(--tg-theme-secondary-bg-color)">
                    <svg style="width: 16px; height: 16px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                    <div>
                        <span class="text-sm" style="color: var(--tg-theme-text-color)">Boshlanish: <strong>{{ formatDate(attrs.start_date) }}</strong></span>
                        <span v-if="attrs.end_date" class="text-sm" style="color: var(--tg-theme-hint-color)"> — {{ formatDate(attrs.end_date) }}</span>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="course.description" style="margin-top: 20px">
                    <p class="section-title" style="font-size: 15px; margin-bottom: 8px">Kurs haqida</p>
                    <p
                        :class="{ 'line-clamp-4': !showFullDesc }"
                        style="font-size: 14px; line-height: 1.6; color: var(--tg-theme-hint-color); white-space: pre-line"
                    >{{ course.description }}</p>
                    <button
                        v-if="course.description.length > 200"
                        @click="showFullDesc = !showFullDesc"
                        style="margin-top: 6px; font-size: 14px; font-weight: 500; color: var(--tg-theme-link-color); background: none; border: none; padding: 0"
                    >{{ showFullDesc ? 'Yopish' : "Ko'proq o'qish" }}</button>
                </div>

                <!-- What you'll learn -->
                <div v-if="course.what_you_learn" style="margin-top: 20px">
                    <p class="section-title" style="font-size: 15px; margin-bottom: 10px">Nimalarni o'rganasiz</p>
                    <div class="p-3 rounded-2xl" style="background: var(--tg-theme-secondary-bg-color)">
                        <div
                            v-for="(item, idx) in learnItems"
                            :key="idx"
                            class="flex gap-2"
                            :style="{ padding: '6px 0', borderBottom: idx < learnItems.length - 1 ? '1px solid var(--color-divider)' : 'none' }"
                        >
                            <svg class="shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--color-success)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span class="text-sm" style="color: var(--tg-theme-text-color)">{{ item }}</span>
                        </div>
                    </div>
                </div>

                <!-- Requirements -->
                <div v-if="course.requirements" style="margin-top: 20px">
                    <p class="section-title" style="font-size: 15px; margin-bottom: 10px">Talablar</p>
                    <div class="p-3 rounded-2xl" style="background: var(--tg-theme-secondary-bg-color)">
                        <div
                            v-for="(item, idx) in requirementItems"
                            :key="idx"
                            class="flex gap-2"
                            :style="{ padding: '6px 0', borderBottom: idx < requirementItems.length - 1 ? '1px solid var(--color-divider)' : 'none' }"
                        >
                            <span class="shrink-0 mt-0.5" style="width: 16px; text-align: center; font-size: 10px; color: var(--tg-theme-hint-color)">●</span>
                            <span class="text-sm" style="color: var(--tg-theme-text-color)">{{ item }}</span>
                        </div>
                    </div>
                </div>

                <!-- Lessons / Curriculum -->
                <div v-if="lessons.length" style="margin-top: 20px">
                    <div class="flex items-center justify-between" style="margin-bottom: 10px">
                        <p class="section-title" style="font-size: 15px">
                            Dars rejasi
                            <span class="text-xs font-normal" style="color: var(--tg-theme-hint-color)"> ({{ lessons.length }} ta dars)</span>
                        </p>
                        <span v-if="totalLessonMinutes" class="text-xs" style="color: var(--tg-theme-hint-color)">{{ totalLessonMinutes }} min</span>
                    </div>
                    <div class="rounded-2xl overflow-hidden" style="background: var(--tg-theme-secondary-bg-color)">
                        <div
                            v-for="(lesson, idx) in visibleLessons"
                            :key="lesson.id || idx"
                            class="flex items-center gap-3 px-3"
                            :style="{ padding: '12px', borderBottom: idx < visibleLessons.length - 1 ? '1px solid var(--color-divider)' : 'none' }"
                        >
                            <div
                                class="shrink-0 flex items-center justify-center rounded-full"
                                style="width: 32px; height: 32px; font-size: 13px; font-weight: 600"
                                :style="lesson.is_free_preview
                                    ? { background: 'rgba(16, 185, 129, 0.15)', color: '#10b981' }
                                    : { background: 'var(--tg-theme-bg-color)', color: 'var(--tg-theme-hint-color)' }"
                            >
                                {{ idx + 1 }}
                            </div>
                            <div style="flex: 1; min-width: 0">
                                <p class="text-sm font-medium truncate" style="color: var(--tg-theme-text-color)">{{ lesson.title }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span v-if="lesson.duration_minutes" class="text-xs" style="color: var(--tg-theme-hint-color)">{{ lesson.duration_minutes }} min</span>
                                    <span v-if="lesson.is_free_preview" class="text-[10px] font-medium px-1.5 py-0.5 rounded" style="background: rgba(16, 185, 129, 0.15); color: #10b981">Bepul</span>
                                </div>
                            </div>
                            <svg v-if="lesson.is_free_preview" style="width: 16px; height: 16px; color: var(--color-success)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/>
                            </svg>
                            <svg v-else style="width: 16px; height: 16px; color: var(--tg-theme-hint-color); opacity: 0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                        </div>
                    </div>
                    <button
                        v-if="lessons.length > 5 && !showAllLessons"
                        @click="showAllLessons = true"
                        class="w-full mt-2 py-2 text-sm font-medium tap-active rounded-xl"
                        style="color: var(--tg-theme-link-color); background: none; border: none"
                    >
                        Barcha {{ lessons.length }} ta darsni ko'rish
                    </button>
                </div>
            </div>
        </template>

        <!-- Bottom action bar -->
        <div v-if="course && !loading" class="sticky-bottom-bar">
            <div class="flex items-center" style="gap: 12px">
                <!-- Quantity controls if in cart -->
                <div v-if="cartItem" class="flex items-center" style="gap: 4px">
                    <button @click="removeFromCart" class="qty-btn">−</button>
                    <span class="qty-value">{{ cartItem.quantity }}</span>
                    <button @click="incrementQty" class="qty-btn" style="color: var(--tg-theme-button-color)">+</button>
                </div>

                <!-- Add to cart / Enroll button -->
                <button @click="addToCart" class="btn-primary" style="flex: 1">
                    <span v-if="justAdded" class="inline-flex items-center gap-1">
                        <svg style="width: 16px; height: 16px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Qo'shildi!
                    </span>
                    <span v-else>
                        {{ cartItem ? `Savatda — ${formatPrice(cartItem.quantity * course.price)}` : "Kursga yozilish" }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useStoreInfo } from '../../stores/store'
import { useCartStore } from '../../stores/cart'
import { useTelegram } from '../../composables/useTelegram'
import { useToast } from '../../composables/useToast'
import BackButton from '../../components/BackButton.vue'
import LoadingSpinner from '../../components/LoadingSpinner.vue'
import { formatPrice } from '../../utils/formatters'

const props = defineProps({
    slug: { type: String, required: true },
})

const storeInfo = useStoreInfo()
const cart = useCartStore()
const { hapticImpact, hapticNotification } = useTelegram()
const toast = useToast()

const course = ref(null)
const lessons = ref([])
const loading = ref(true)
const showFullDesc = ref(false)
const showAllLessons = ref(false)
const justAdded = ref(false)

const attrs = computed(() => course.value?.attributes || {})

const discountPercent = computed(() => attrs.value.discount_percent || 0)

const learnItems = computed(() => {
    if (!course.value?.what_you_learn) return []
    return course.value.what_you_learn.split('\n').map(s => s.replace(/^[-•*]\s*/, '').trim()).filter(Boolean)
})

const requirementItems = computed(() => {
    if (!course.value?.requirements) return []
    return course.value.requirements.split('\n').map(s => s.replace(/^[-•*]\s*/, '').trim()).filter(Boolean)
})

const visibleLessons = computed(() => {
    if (showAllLessons.value) return lessons.value
    return lessons.value.slice(0, 5)
})

const totalLessonMinutes = computed(() => {
    return lessons.value.reduce((sum, l) => sum + (l.duration_minutes || 0), 0)
})

const spotsInfo = computed(() => {
    const max = attrs.value.max_students
    const enrolled = attrs.value.enrolled_count || 0
    if (!max) return null
    const remaining = max - enrolled
    if (remaining <= 0) return { text: "Joy qolmadi", urgent: true }
    if (remaining <= 5) return { text: `Faqat ${remaining} ta joy qoldi!`, urgent: true }
    if (remaining <= 10) return { text: `${remaining} ta joy qoldi`, urgent: false }
    return null
})

const cartItem = computed(() => {
    if (!course.value) return null
    return cart.items.find(item => item.product_id === course.value.id)
})

function levelLabel(level) {
    const map = { beginner: "Boshlang'ich", intermediate: "O'rta", advanced: 'Yuqori' }
    return map[level] || level
}

function levelStyle(level) {
    const styles = {
        beginner: { background: 'rgba(16, 185, 129, 0.85)', color: '#fff' },
        intermediate: { background: 'rgba(245, 158, 11, 0.85)', color: '#fff' },
        advanced: { background: 'rgba(239, 68, 68, 0.85)', color: '#fff' },
    }
    return styles[level] || { background: 'rgba(0,0,0,0.6)', color: '#fff' }
}

function formatLabel(format) {
    const map = { online: 'Online', offline: 'Offline', hybrid: 'Aralash' }
    return map[format] || format
}

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return d.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long', year: 'numeric' })
}

function addToCart() {
    if (!course.value) return
    cart.addItem({
        id: course.value.id,
        name: course.value.name,
        price: course.value.price,
        sale_price: attrs.value.has_discount ? course.value.price : null,
        image: course.value.image || course.value.image_url || '',
        slug: course.value.slug,
        stock: 99,
        catalog_type: 'course',
    }, 1)
    hapticNotification('success')
    toast.success("Savatga qo'shildi")
    justAdded.value = true
    setTimeout(() => { justAdded.value = false }, 800)
}

function incrementQty() {
    if (cartItem.value) {
        cart.incrementQuantity(course.value.id, null)
    }
}

function removeFromCart() {
    if (cartItem.value) {
        cart.decrementQuantity(course.value.id, null)
    }
}

onMounted(async () => {
    loading.value = true
    try {
        const data = await storeInfo.fetchCatalogItem(props.slug)
        if (data) {
            course.value = data
            lessons.value = data.lessons || []
        }
    } catch (err) {
        console.error('[CourseDetail] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    background: var(--tg-theme-secondary-bg-color);
    color: var(--tg-theme-text-color);
}
</style>
