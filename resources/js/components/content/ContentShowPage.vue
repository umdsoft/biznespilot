<template>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Back Button & Actions -->
        <div class="flex items-center justify-between">
            <button
                @click="goBack"
                class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ t('common.back') }}
            </button>
            <div class="flex items-center space-x-2">
                <button
                    v-if="post.status !== 'published'"
                    @click="editPost"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ t('common.edit') }}
                </button>
                <button
                    v-if="post.status !== 'published'"
                    @click="deletePost"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    {{ t('common.delete') }}
                </button>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ post.title }}</h1>
                        <div class="flex items-center flex-wrap gap-2 mt-3">
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg" :class="getStatusBadgeClass(post.status)">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="getStatusDotClass(post.status)"></span>
                                {{ getStatusLabel(post.status) }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg" :class="getContentTypeBadgeClass(post.content_type)">
                                {{ getContentTypeLabel(post.content_type) }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                {{ getFormatLabel(post.format) }}
                            </span>
                        </div>
                    </div>
                    <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                        <p>{{ t('content.show.created') }}: {{ post.created_at }}</p>
                        <p v-if="post.updated_at !== post.created_at">{{ t('content.show.updated') }}: {{ post.updated_at }}</p>
                    </div>
                </div>
            </div>

            <!-- Platforms -->
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ t('content.form.platforms') }}</p>
                <div class="flex items-center flex-wrap gap-2">
                    <div v-for="platform in post.platforms" :key="platform" class="flex items-center px-3 py-1.5 rounded-lg" :class="getPlatformBgClass(platform)">
                        <component :is="getPlatformIcon(platform)" class="w-4 h-4" :class="getPlatformIconClass(platform)" />
                        <span class="ml-2 text-sm font-medium" :class="getPlatformIconClass(platform)">{{ platform }}</span>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="px-6 py-5">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ t('content.form.content_text') }}</p>
                <div class="prose dark:prose-invert max-w-none">
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ post.content }}</p>
                </div>
            </div>

            <!-- Scheduling Info -->
            <div v-if="post.scheduled_at_display || post.published_at" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-amber-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <p v-if="post.scheduled_at_display" class="text-sm font-medium text-gray-900 dark:text-white">{{ t('content.status.scheduled') }}: {{ post.scheduled_at_display }}</p>
                        <p v-if="post.published_at" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ t('content.status.published') }}: {{ post.published_at }}</p>
                    </div>
                </div>
            </div>

            <!-- Hashtags -->
            <div v-if="post.hashtags && post.hashtags.length > 0" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ t('content.form.hashtags') }}</p>
                <div class="flex flex-wrap gap-2">
                    <span v-for="tag in post.hashtags" :key="tag" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                        {{ tag.startsWith('#') ? tag : '#' + tag }}
                    </span>
                </div>
            </div>

            <!-- Statistics -->
            <div v-if="post.status === 'published' && hasStats" class="px-6 py-5 border-t border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">{{ t('content.show.statistics') }}</p>
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(post.views) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('content.stats.views') }}</p>
                    </div>
                    <div class="text-center p-4 bg-pink-50 dark:bg-pink-900/20 rounded-xl">
                        <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ formatNumber(post.likes) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('content.stats.likes') }}</p>
                    </div>
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(post.comments) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('content.stats.comments') }}</p>
                    </div>
                    <div class="text-center p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatNumber(post.shares) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('content.stats.shares') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, h } from 'vue'
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
const DefaultPlatformIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [h('circle', { cx: '12', cy: '12', r: '10' })]) } }

const hasStats = computed(() => props.post.views || props.post.likes || props.post.comments || props.post.shares)

const goBack = () => router.get(panelConfig.value.baseUrl)
const editPost = () => router.get(`${panelConfig.value.baseUrl}/${props.post.id}/edit`)
const deletePost = () => { if (confirm(t('content.confirm.delete'))) router.delete(`${panelConfig.value.baseUrl}/${props.post.id}`) }

const formatNumber = (num) => { if (!num) return '0'; if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'; if (num >= 1000) return (num / 1000).toFixed(1) + 'K'; return num.toString() }

const getPlatformIcon = (platform) => ({ 'Instagram': InstagramIcon, 'Telegram': TelegramIcon, 'Facebook': FacebookIcon, 'YouTube': YouTubeIcon, 'YouTube Shorts': YouTubeShortsIcon }[platform] || DefaultPlatformIcon)
const getPlatformBgClass = (platform) => ({ 'Instagram': 'bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/40 dark:to-pink-900/40', 'Telegram': 'bg-sky-100 dark:bg-sky-900/40', 'Facebook': 'bg-blue-100 dark:bg-blue-900/40', 'YouTube': 'bg-red-100 dark:bg-red-900/40', 'YouTube Shorts': 'bg-red-50 dark:bg-red-900/30' }[platform] || 'bg-gray-100 dark:bg-gray-700')
const getPlatformIconClass = (platform) => ({ 'Instagram': 'text-pink-600 dark:text-pink-400', 'Telegram': 'text-sky-600 dark:text-sky-400', 'Facebook': 'text-blue-600 dark:text-blue-400', 'YouTube': 'text-red-600 dark:text-red-400', 'YouTube Shorts': 'text-red-500 dark:text-red-400' }[platform] || 'text-gray-600 dark:text-gray-400')

const getStatusLabel = (status) => {
    const labels = {
        draft: t('content.status.draft'),
        scheduled: t('content.status.scheduled'),
        published: t('content.status.published')
    }
    return labels[status] || status
}
const getStatusBadgeClass = (status) => ({ draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', scheduled: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', published: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300')
const getStatusDotClass = (status) => ({ draft: 'bg-gray-400', scheduled: 'bg-amber-500', published: 'bg-emerald-500' }[status] || 'bg-gray-400')

const getContentTypeLabel = (type) => {
    const labels = {
        educational: t('content.type.educational'),
        entertaining: t('content.type.entertaining'),
        inspirational: t('content.type.inspirational'),
        promotional: t('content.type.promotional'),
        behind_scenes: t('content.type.behind_scenes'),
        ugc: t('content.type.ugc')
    }
    return labels[type] || type || '-'
}
const getContentTypeBadgeClass = (type) => ({ educational: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', entertaining: 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300', inspirational: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', promotional: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', behind_scenes: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300', ugc: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' }[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300')

const getFormatLabel = (format) => {
    const labels = {
        short_video: t('content.format.short_video'),
        long_video: t('content.format.long_video'),
        carousel: t('content.format.carousel'),
        single_image: t('content.format.single_image'),
        story: t('content.format.story'),
        text_post: t('content.format.text_post'),
        live: t('content.format.live'),
        poll: t('content.format.poll')
    }
    return labels[format] || format || '-'
}
</script>
