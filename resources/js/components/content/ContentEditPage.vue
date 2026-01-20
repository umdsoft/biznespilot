<template>
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Back Button -->
        <div class="flex items-center justify-between">
            <button @click="goBack" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ t('common.back') }}
            </button>
        </div>

        <!-- Edit Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('content.edit.title') }}</h1>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-5">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.title') }}</label>
                    <input v-model="form.title" type="text" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" :placeholder="t('content.form.title_placeholder')" required />
                </div>

                <!-- Content -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.content_text') }}</label>
                    <textarea v-model="form.content" rows="5" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none" :placeholder="t('content.form.content_placeholder')" required></textarea>
                </div>

                <!-- Platforms -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.platforms') }}</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ t('content.form.platforms_hint') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <label v-for="platform in availablePlatforms" :key="platform.value" class="relative flex items-center px-3 py-2 rounded-xl border cursor-pointer transition-all" :class="form.platforms.includes(platform.value) ? platform.selectedClass : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                            <input type="checkbox" :value="platform.value" v-model="form.platforms" class="sr-only" />
                            <component :is="platform.icon" class="w-4 h-4 mr-2" :class="form.platforms.includes(platform.value) ? 'text-white' : platform.iconClass" />
                            <span class="text-sm font-medium" :class="form.platforms.includes(platform.value) ? 'text-white' : 'text-gray-700 dark:text-gray-300'">{{ platform.label }}</span>
                            <svg v-if="form.platforms.includes(platform.value)" class="w-4 h-4 ml-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </label>
                    </div>
                </div>

                <!-- Content Type & Format -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.content_type') }}</label>
                        <select v-model="form.content_type" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required>
                            <option value="">{{ t('common.select') }}</option>
                            <option value="educational">{{ t('content.type.educational') }}</option>
                            <option value="entertaining">{{ t('content.type.entertaining') }}</option>
                            <option value="inspirational">{{ t('content.type.inspirational') }}</option>
                            <option value="promotional">{{ t('content.type.promotional') }}</option>
                            <option value="behind_scenes">{{ t('content.type.behind_scenes') }}</option>
                            <option value="ugc">{{ t('content.type.ugc') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.format') }}</label>
                        <select v-model="form.format" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required>
                            <option value="">{{ t('common.select') }}</option>
                            <option value="short_video">{{ t('content.format.short_video') }}</option>
                            <option value="long_video">{{ t('content.format.long_video') }}</option>
                            <option value="carousel">{{ t('content.format.carousel') }}</option>
                            <option value="single_image">{{ t('content.format.single_image') }}</option>
                            <option value="story">{{ t('content.format.story') }}</option>
                            <option value="text_post">{{ t('content.format.text_post') }}</option>
                            <option value="live">{{ t('content.format.live') }}</option>
                            <option value="poll">{{ t('content.format.poll') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.status') }}</label>
                    <select v-model="form.status" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="draft">{{ t('content.status.draft') }}</option>
                        <option value="scheduled">{{ t('content.status.scheduled') }}</option>
                        <option value="published">{{ t('content.status.published') }}</option>
                    </select>
                </div>

                <!-- Scheduled Date & Time -->
                <div v-if="form.status === 'scheduled'" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.date') }}</label>
                        <input v-model="form.scheduled_date" type="date" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.time') }}</label>
                        <input v-model="form.scheduled_time" type="time" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required />
                    </div>
                </div>

                <!-- Hashtags -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.hashtags') }}</label>
                    <input v-model="hashtagInput" type="text" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" :placeholder="t('content.form.hashtags_placeholder')" />
                </div>

                <!-- External URL -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.external_url') }}</label>
                    <input v-model="form.external_url" type="url" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="https://instagram.com/p/..." />
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" @click="goBack" class="px-4 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-medium transition-colors">{{ t('common.cancel') }}</button>
                    <button type="submit" :disabled="isSubmitting" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-medium rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all disabled:opacity-50">
                        <span v-if="isSubmitting">{{ t('common.saving') }}...</span>
                        <span v-else>{{ t('common.save') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, h } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from '@/i18n'

const props = defineProps({
    post: { type: Object, required: true },
    panelType: { type: String, default: 'business', validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v) }
})

const { t } = useI18n()

// Universal panel config for all 5 panels
const panelConfig = computed(() => {
    const baseUrls = {
        business: '/business/content',
        marketing: '/marketing/content',
        finance: '/finance/content',
        operator: '/operator/content',
        saleshead: '/saleshead/content',
    };
    return { baseUrl: baseUrls[props.panelType] || '/business/content' };
})

// Platform Icons
const InstagramIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' })]) } }
const TelegramIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z' })]) } }
const FacebookIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' })]) } }
const YouTubeIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z' })]) } }
const YouTubeShortsIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M17.77 10.32c-.77-.32-1.2-.5-1.2-.5L18 9.06c1.84-.96 2.53-3.23 1.56-5.06s-3.24-2.53-5.07-1.56L6 6.94c-1.29.68-2.07 2.04-2 3.49.07 1.42.93 2.67 2.22 3.25.03.01 1.2.5 1.2.5L6 14.93c-1.83.97-2.53 3.24-1.56 5.07.97 1.83 3.24 2.53 5.07 1.56l8.5-4.5c1.29-.68 2.06-2.04 1.99-3.49-.07-1.42-.94-2.68-2.23-3.25zM10 14.65v-5.3L15 12l-5 2.65z' })]) } }

const availablePlatforms = [
    { value: 'Instagram', label: 'Instagram', icon: InstagramIcon, iconClass: 'text-pink-600 dark:text-pink-400', selectedClass: 'bg-gradient-to-r from-purple-600 to-pink-600 border-transparent' },
    { value: 'Telegram', label: 'Telegram', icon: TelegramIcon, iconClass: 'text-sky-600 dark:text-sky-400', selectedClass: 'bg-sky-600 border-transparent' },
    { value: 'Facebook', label: 'Facebook', icon: FacebookIcon, iconClass: 'text-blue-600 dark:text-blue-400', selectedClass: 'bg-blue-600 border-transparent' },
    { value: 'YouTube', label: 'YouTube', icon: YouTubeIcon, iconClass: 'text-red-600 dark:text-red-400', selectedClass: 'bg-red-600 border-transparent' },
    { value: 'YouTube Shorts', label: 'YouTube Shorts', icon: YouTubeShortsIcon, iconClass: 'text-red-500 dark:text-red-400', selectedClass: 'bg-red-500 border-transparent' }
]

const isSubmitting = ref(false)
const hashtagInput = ref('')
const form = ref({ title: '', content: '', platforms: [], content_type: '', format: '', status: 'draft', scheduled_date: '', scheduled_time: '', scheduled_at: null, hashtags: [], external_url: '' })

onMounted(() => {
    form.value.title = props.post.title || ''
    form.value.content = props.post.content || ''
    form.value.platforms = props.post.platforms || []
    form.value.content_type = props.post.content_type || ''
    form.value.format = props.post.format || ''
    form.value.status = props.post.status || 'draft'
    form.value.external_url = props.post.external_url || ''
    form.value.hashtags = props.post.hashtags || []
    if (props.post.scheduled_at) {
        const [date, time] = props.post.scheduled_at.split('T')
        form.value.scheduled_date = date
        form.value.scheduled_time = time?.substring(0, 5) || ''
    }
    if (form.value.hashtags && form.value.hashtags.length > 0) {
        hashtagInput.value = form.value.hashtags.join(', ')
    }
})

const goBack = () => router.get(`${panelConfig.value.baseUrl}/${props.post.id}`)

const submitForm = () => {
    if (form.value.platforms.length === 0) { alert(t('content.alert.select_platform')); return }
    isSubmitting.value = true
    if (hashtagInput.value) form.value.hashtags = hashtagInput.value.split(',').map(t => t.trim()).filter(t => t)
    if (form.value.status === 'scheduled' && form.value.scheduled_date && form.value.scheduled_time) form.value.scheduled_at = `${form.value.scheduled_date} ${form.value.scheduled_time}`
    else form.value.scheduled_at = null
    const formData = { ...form.value, platform: form.value.platforms }
    router.put(`${panelConfig.value.baseUrl}/${props.post.id}`, formData, { preserveScroll: true, onSuccess: () => { isSubmitting.value = false }, onError: () => { isSubmitting.value = false } })
}
</script>
