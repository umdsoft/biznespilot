<template>
    <div class="pb-nav">
        <!-- Header -->
        <div class="header-blur sticky top-0 z-30">
            <div style="padding: 12px 16px">
                <div class="flex items-center gap-3">
                    <img v-if="storeInfo.logo" :src="storeInfo.logo" class="h-10 w-10 rounded-xl object-cover" />
                    <div v-else class="flex h-10 w-10 items-center justify-center rounded-xl" style="background: var(--tg-theme-button-color)">
                        <span style="font-size: 20px">🎓</span>
                    </div>
                    <div style="flex: 1; min-width: 0">
                        <h1 class="text-base font-bold truncate" style="color: var(--tg-theme-text-color)">{{ storeInfo.name }}</h1>
                        <p v-if="storeInfo.description" class="text-xs truncate" style="color: var(--tg-theme-hint-color)">{{ storeInfo.description }}</p>
                    </div>
                </div>

                <!-- Search -->
                <button
                    @click="goToSearch"
                    class="flex w-full items-center tap-active"
                    style="margin-top: 12px; height: 44px; padding: 0 16px 0 40px; border-radius: 12px; background-color: var(--color-bg-tertiary); border: none; position: relative"
                >
                    <svg style="width: 18px; height: 18px; color: var(--tg-theme-hint-color); position: absolute; left: 14px; top: 50%; transform: translateY(-50%)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span style="font-size: 14px; color: var(--tg-theme-hint-color)">Kurs qidirish...</span>
                </button>
            </div>
        </div>

        <!-- Categories -->
        <div v-if="storeInfo.categories.length > 1" style="padding: 8px 0">
            <div class="flex overflow-x-auto gap-2 px-4 no-scrollbar">
                <button
                    v-for="cat in [{ id: 'all', name: 'Barchasi' }, ...storeInfo.categories]"
                    :key="cat.id"
                    @click="filterCategory(cat.id)"
                    class="shrink-0 rounded-full px-4 py-2 text-sm font-medium tap-active whitespace-nowrap"
                    :style="{
                        background: activeCategory === cat.id ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-secondary-bg-color)',
                        color: activeCategory === cat.id ? 'var(--tg-theme-button-text-color)' : 'var(--tg-theme-text-color)',
                    }"
                >
                    {{ cat.name }}
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" style="padding: 16px">
            <div v-for="i in 4" :key="i" class="skeleton" style="height: 140px; border-radius: 16px; margin-bottom: 12px"></div>
        </div>

        <template v-else>
            <!-- Featured courses -->
            <div v-if="featuredCourses.length && activeCategory === 'all'" style="padding: 12px 16px 4px">
                <h2 class="section-title" style="margin-bottom: 10px">Mashhur kurslar</h2>
                <div class="overflow-x-auto no-scrollbar" style="margin: 0 -16px; padding: 0 16px">
                    <div class="flex" style="width: max-content; gap: 12px; padding-right: 16px">
                        <button
                            v-for="course in featuredCourses"
                            :key="course.id"
                            @click="goToCourse(course)"
                            class="shrink-0 overflow-hidden rounded-2xl text-left tap-active"
                            style="width: 260px; background: var(--tg-theme-secondary-bg-color)"
                        >
                            <div class="relative" style="height: 130px">
                                <img v-if="course.image || course.image_url" :src="course.image || course.image_url" :alt="course.name" class="h-full w-full object-cover" loading="lazy" />
                                <div v-else class="flex h-full w-full items-center justify-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                                    <span style="font-size: 40px">🎓</span>
                                </div>
                                <div v-if="course.attributes?.discount_percent > 0" class="absolute top-2 left-2 px-2 py-0.5 rounded-lg text-xs font-bold text-white" style="background: var(--color-error)">
                                    -{{ course.attributes.discount_percent }}%
                                </div>
                                <div v-if="course.attributes?.level" class="absolute top-2 right-2 px-2 py-0.5 rounded-lg text-xs font-medium" style="background: rgba(0,0,0,0.6); color: #fff">
                                    {{ levelLabel(course.attributes.level) }}
                                </div>
                            </div>
                            <div style="padding: 10px 12px">
                                <p class="text-sm font-semibold line-clamp-2" style="color: var(--tg-theme-text-color); line-height: 1.35">{{ course.name }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span v-if="course.attributes?.instructor" class="text-xs truncate" style="color: var(--tg-theme-hint-color)">{{ course.attributes.instructor }}</span>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-sm font-bold" style="color: var(--tg-theme-text-color)">{{ formatPrice(course.price) }}</span>
                                        <span v-if="course.attributes?.has_discount" class="text-xs line-through" style="color: var(--tg-theme-hint-color)">{{ formatPrice(course.compare_price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- All courses -->
            <div style="padding: 12px 16px 16px">
                <h2 v-if="activeCategory === 'all' && featuredCourses.length" class="section-title" style="margin-bottom: 10px">Barcha kurslar</h2>

                <div v-if="filteredCourses.length" class="flex flex-col gap-3">
                    <button
                        v-for="course in filteredCourses"
                        :key="course.id"
                        @click="goToCourse(course)"
                        class="flex gap-3 rounded-2xl p-3 tap-active text-left"
                        style="background: var(--tg-theme-secondary-bg-color)"
                    >
                        <div class="relative shrink-0 w-24 h-24 rounded-xl overflow-hidden">
                            <img v-if="course.image || course.image_url" :src="course.image || course.image_url" :alt="course.name" class="w-full h-full object-cover" loading="lazy" />
                            <div v-else class="w-full h-full flex items-center justify-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                                <span style="font-size: 28px">🎓</span>
                            </div>
                            <div v-if="course.attributes?.discount_percent > 0" class="absolute top-1 left-1 px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background: var(--color-error)">
                                -{{ course.attributes.discount_percent }}%
                            </div>
                        </div>
                        <div style="flex: 1; min-width: 0">
                            <p class="text-sm font-semibold line-clamp-2" style="color: var(--tg-theme-text-color)">{{ course.name }}</p>

                            <!-- Meta: instructor + level -->
                            <div class="flex items-center gap-2 mt-1">
                                <span v-if="course.attributes?.instructor" class="text-xs truncate" style="color: var(--tg-theme-hint-color)">{{ course.attributes.instructor }}</span>
                                <span v-if="course.attributes?.level" class="shrink-0 text-[10px] font-medium px-1.5 py-0.5 rounded" :style="levelStyle(course.attributes.level)">
                                    {{ levelLabel(course.attributes.level) }}
                                </span>
                            </div>

                            <!-- Meta: duration + students -->
                            <div class="flex items-center gap-3 mt-1.5">
                                <span v-if="course.attributes?.duration_hours" class="flex items-center gap-1 text-xs" style="color: var(--tg-theme-hint-color)">
                                    <svg style="width: 12px; height: 12px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ course.attributes.duration_hours }} soat
                                </span>
                                <span v-if="course.attributes?.enrolled_count" class="flex items-center gap-1 text-xs" style="color: var(--tg-theme-hint-color)">
                                    <svg style="width: 12px; height: 12px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                    {{ course.attributes.enrolled_count }}
                                </span>
                                <span v-if="course.attributes?.certificate_included" class="flex items-center gap-1 text-xs" style="color: var(--color-success)">
                                    <svg style="width: 12px; height: 12px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg>
                                    Sertifikat
                                </span>
                            </div>

                            <!-- Price -->
                            <div class="flex items-center justify-between mt-2">
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-sm font-bold" style="color: var(--tg-theme-text-color)">{{ formatPrice(course.price) }}</span>
                                    <span v-if="course.attributes?.has_discount" class="text-xs line-through" style="color: var(--tg-theme-hint-color)">{{ formatPrice(course.compare_price) }}</span>
                                </div>
                                <span
                                    class="flex items-center justify-center w-7 h-7 rounded-full"
                                    style="background: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); font-size: 18px; font-weight: 300"
                                >+</span>
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Empty -->
                <div v-if="!filteredCourses.length && !loading" class="empty-state" style="padding: 48px 0">
                    <div class="empty-state-icon">🎓</div>
                    <p class="empty-state-title">Kurslar hozircha yo'q</p>
                    <p class="empty-state-desc">Tez orada yangi kurslar qo'shiladi</p>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreInfo } from '../../stores/store'
import { useTelegram } from '../../composables/useTelegram'
import { formatPrice } from '../../utils/formatters'

const router = useRouter()
const storeInfo = useStoreInfo()
const { hapticImpact, hideBackButton } = useTelegram()

const loading = ref(true)
const courses = ref([])
const activeCategory = ref('all')

const featuredCourses = computed(() => courses.value.filter(c => c.is_featured))

const filteredCourses = computed(() => {
    if (activeCategory.value === 'all') return courses.value
    return courses.value.filter(c => c.category_id === activeCategory.value)
})

function levelLabel(level) {
    const map = { beginner: 'Boshlang\'ich', intermediate: 'O\'rta', advanced: 'Yuqori' }
    return map[level] || level
}

function levelStyle(level) {
    const styles = {
        beginner: { background: 'rgba(16, 185, 129, 0.15)', color: '#10b981' },
        intermediate: { background: 'rgba(245, 158, 11, 0.15)', color: '#f59e0b' },
        advanced: { background: 'rgba(239, 68, 68, 0.15)', color: '#ef4444' },
    }
    return styles[level] || { background: 'var(--tg-theme-secondary-bg-color)', color: 'var(--tg-theme-hint-color)' }
}

function filterCategory(catId) {
    activeCategory.value = catId
    hapticImpact('light')
}

function goToCourse(course) {
    hapticImpact('light')
    router.push({ name: 'course-detail', params: { slug: course.slug } })
}

function goToSearch() {
    hapticImpact('light')
    router.push({ name: 'search' })
}

onMounted(async () => {
    hideBackButton()
    loading.value = true
    try {
        const data = await storeInfo.fetchCatalogItems({ per_page: 100 })
        courses.value = data.items || data.data || []
    } catch (err) {
        console.error('[CourseHome] Load error:', err)
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
