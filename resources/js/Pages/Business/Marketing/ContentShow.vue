<template>
    <BusinessLayout title="Kontent Tafsilotlari">
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
                    Orqaga
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
                        Tahrirlash
                    </button>
                    <button
                        v-if="post.status !== 'published'"
                        @click="deletePost"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        O'chirish
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
                                <!-- Status Badge -->
                                <span
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg"
                                    :class="getStatusBadgeClass(post.status)"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="getStatusDotClass(post.status)"></span>
                                    {{ getStatusLabel(post.status) }}
                                </span>
                                <!-- Content Type Badge -->
                                <span
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg"
                                    :class="getContentTypeBadgeClass(post.content_type)"
                                >
                                    {{ getContentTypeLabel(post.content_type) }}
                                </span>
                                <!-- Format Badge -->
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    {{ getFormatLabel(post.format) }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                            <p>Yaratilgan: {{ post.created_at }}</p>
                            <p v-if="post.updated_at !== post.created_at">Yangilangan: {{ post.updated_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Platforms -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Platformalar</p>
                    <div class="flex items-center flex-wrap gap-2">
                        <div
                            v-for="platform in post.platforms"
                            :key="platform"
                            class="flex items-center px-3 py-1.5 rounded-lg"
                            :class="getPlatformBgClass(platform)"
                        >
                            <component :is="getPlatformIcon(platform)" class="w-4 h-4" :class="getPlatformIconClass(platform)" />
                            <span class="ml-2 text-sm font-medium" :class="getPlatformIconClass(platform)">{{ platform }}</span>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Kontent Matni</p>
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
                            <p v-if="post.scheduled_at_display" class="text-sm font-medium text-gray-900 dark:text-white">
                                Rejalashtirilgan: {{ post.scheduled_at_display }}
                            </p>
                            <p v-if="post.published_at" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                Nashr qilingan: {{ post.published_at }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Hashtags -->
                <div v-if="post.hashtags && post.hashtags.length > 0" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Hashtaglar</p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="tag in post.hashtags"
                            :key="tag"
                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300"
                        >
                            {{ tag.startsWith('#') ? tag : '#' + tag }}
                        </span>
                    </div>
                </div>

                <!-- Statistics (for published posts) -->
                <div v-if="post.status === 'published' && hasStats" class="px-6 py-5 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Statistika</p>
                    <div class="grid grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(post.views) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ko'rishlar</p>
                        </div>
                        <div class="text-center p-4 bg-pink-50 dark:bg-pink-900/20 rounded-xl">
                            <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ formatNumber(post.likes) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Yoqtirishlar</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(post.comments) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Izohlar</p>
                        </div>
                        <div class="text-center p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatNumber(post.shares) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ulashishlar</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Materials Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Materiallar</h2>
                    <button
                        @click="showAddMaterialModal = true"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Qo'shish
                    </button>
                </div>

                <!-- Materials List -->
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <!-- Script -->
                    <div v-if="post.ai_suggestions?.script" class="px-6 py-4">
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Senariy</h4>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ post.ai_suggestions.script }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- External URL -->
                    <div v-if="post.external_url" class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Post Havolasi</h4>
                                <a :href="post.external_url" target="_blank" class="mt-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline break-all">
                                    {{ post.external_url }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Media -->
                    <div v-if="post.media && post.media.length > 0" class="px-6 py-4">
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Media fayllar</h4>
                                <div class="grid grid-cols-4 gap-2">
                                    <div v-for="(media, index) in post.media" :key="index" class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                                        <img v-if="media.type === 'image'" :src="media.url" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!post.ai_suggestions?.script && !post.external_url && (!post.media || post.media.length === 0)" class="px-6 py-12 text-center">
                        <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Material qo'shilmagan</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Senariy, post havolasi yoki media fayllarni qo'shing
                        </p>
                    </div>
                </div>
            </div>

            <!-- Add Material Modal -->
            <Teleport to="body">
                <div v-if="showAddMaterialModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showAddMaterialModal = false"></div>

                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-auto overflow-hidden transform transition-all">
                            <!-- Header -->
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-purple-600 to-indigo-600">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/20 rounded-xl">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-white">Kontent Tahrirlovchi</h3>
                                        <p class="text-xs text-white/70">Telegram uchun tayyor post yarating</p>
                                    </div>
                                </div>
                                <button @click="showAddMaterialModal = false" class="p-2 text-white/80 hover:text-white hover:bg-white/20 rounded-xl transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <form @submit.prevent="saveMaterial" class="p-6 space-y-5">
                                <!-- Material Type Tabs -->
                                <div class="flex gap-2 p-1 bg-gray-100 dark:bg-gray-700 rounded-xl">
                                    <button
                                        type="button"
                                        @click="materialForm.type = 'script'"
                                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-medium text-sm transition-all"
                                        :class="materialForm.type === 'script'
                                            ? 'bg-white dark:bg-gray-600 text-purple-600 dark:text-purple-400 shadow-sm'
                                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Senariy / Post
                                    </button>
                                    <button
                                        type="button"
                                        @click="materialForm.type = 'url'"
                                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-medium text-sm transition-all"
                                        :class="materialForm.type === 'url'
                                            ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm'
                                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                        Havola
                                    </button>
                                </div>

                                <!-- Script Editor -->
                                <div v-if="materialForm.type === 'script'" class="space-y-4">
                                    <!-- Quick Emoji Categories -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tezkor emojilar</label>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                v-for="(emojis, category) in emojiCategories"
                                                :key="category"
                                                type="button"
                                                @click="activeEmojiCategory = activeEmojiCategory === category ? null : category"
                                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all"
                                                :class="activeEmojiCategory === category
                                                    ? 'bg-indigo-600 text-white'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                            >
                                                {{ emojis[0] }} {{ category }}
                                            </button>
                                        </div>
                                        <!-- Emoji Picker -->
                                        <div v-if="activeEmojiCategory" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                                            <div class="flex flex-wrap gap-1">
                                                <button
                                                    v-for="emoji in emojiCategories[activeEmojiCategory]"
                                                    :key="emoji"
                                                    type="button"
                                                    @click="insertEmoji(emoji)"
                                                    class="w-9 h-9 flex items-center justify-center text-xl hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                                                >
                                                    {{ emoji }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Text Editor -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Post matni</label>
                                            <div class="flex items-center gap-2">
                                                <!-- Formatting buttons -->
                                                <button
                                                    type="button"
                                                    @click="formatText('bold')"
                                                    class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                                                    title="Qalin (Ctrl+B)"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                        <path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="formatText('italic')"
                                                    class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                                                    title="Kursiv (Ctrl+I)"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path d="M10 4h4m2 0l-6 16m-2 0h4" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="formatText('code')"
                                                    class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                                                    title="Kod"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                                    </svg>
                                                </button>
                                                <div class="w-px h-4 bg-gray-300 dark:bg-gray-600"></div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ scriptLength }}/4096</span>
                                            </div>
                                        </div>
                                        <textarea
                                            ref="scriptTextarea"
                                            v-model="materialForm.script"
                                            rows="8"
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none font-mono text-sm"
                                            placeholder="Post matningizni yozing...

Masalan:
🎯 Bugungi mavzu: Marketing sirlari

✅ 1-maslahat
✅ 2-maslahat
✅ 3-maslahat

👇 Savollaringiz bo'lsa yozing!"
                                            @keydown="handleKeydown"
                                        ></textarea>
                                    </div>

                                    <!-- Quick Templates -->
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tezkor shablonlar</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button
                                                v-for="template in postTemplates"
                                                :key="template.name"
                                                type="button"
                                                @click="applyTemplate(template.content)"
                                                class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 rounded-xl text-left transition-colors"
                                            >
                                                <span class="text-xl">{{ template.icon }}</span>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ template.name }}</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Preview -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ko'rinishi</label>
                                            <button
                                                type="button"
                                                @click="copyToClipboard"
                                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                {{ copied ? 'Nusxalandi!' : 'Nusxalash' }}
                                            </button>
                                        </div>
                                        <div class="p-4 bg-gradient-to-br from-sky-50 to-blue-50 dark:from-sky-900/20 dark:to-blue-900/20 border border-sky-200 dark:border-sky-800 rounded-xl">
                                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-sky-200 dark:border-sky-700">
                                                <div class="w-8 h-8 bg-sky-500 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                                                    </svg>
                                                </div>
                                                <span class="text-sm font-semibold text-sky-700 dark:text-sky-300">Telegram Preview</span>
                                            </div>
                                            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words" v-html="formattedPreview"></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- URL Input -->
                                <div v-if="materialForm.type === 'url'" class="space-y-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Post havolasi</label>
                                        <input
                                            v-model="materialForm.url"
                                            type="url"
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                            placeholder="https://t.me/channel/123"
                                        />
                                    </div>
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                                <p class="font-medium">Qo'llab-quvvatlanadigan havolalar:</p>
                                                <ul class="mt-1 text-xs space-y-0.5 text-blue-600 dark:text-blue-400">
                                                    <li>• Telegram: t.me/channel/123</li>
                                                    <li>• Instagram: instagram.com/p/ABC123</li>
                                                    <li>• YouTube: youtube.com/watch?v=ABC123</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <button
                                        type="button"
                                        @click="showAddMaterialModal = false"
                                        class="px-5 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-medium transition-colors"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="isSavingMaterial || (materialForm.type === 'script' && !materialForm.script) || (materialForm.type === 'url' && !materialForm.url)"
                                        class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-indigo-500/25"
                                    >
                                        <span v-if="isSavingMaterial" class="flex items-center gap-2">
                                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Saqlanmoqda...
                                        </span>
                                        <span v-else class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Saqlash
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { ref, computed, h, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'

const props = defineProps({
    post: {
        type: Object,
        required: true
    }
})

// State
const showAddMaterialModal = ref(false)
const isSavingMaterial = ref(false)
const materialForm = ref({
    type: 'script',
    script: '',
    url: ''
})
const activeEmojiCategory = ref(null)
const copied = ref(false)
const scriptTextarea = ref(null)

// Emoji Categories
const emojiCategories = {
    'Mashhur': ['🔥', '✨', '💯', '🎯', '⭐', '💪', '🚀', '💡', '❤️', '👍', '🙌', '🎉'],
    'Belgilar': ['✅', '❌', '⚠️', '📌', '🔴', '🟢', '🔵', '⚡', '💬', '📢', '🔔', '📍'],
    'Strelkalar': ['👆', '👇', '👉', '👈', '↗️', '↘️', '⬆️', '⬇️', '➡️', '⬅️', '🔄', '↩️'],
    'Raqamlar': ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟', '💲', '📊'],
    'Yuzlar': ['😊', '😍', '🤔', '😎', '🥳', '😱', '🤩', '😅', '🙈', '👀', '💀', '🤝'],
    'Biznes': ['💼', '📈', '📉', '💰', '🏆', '🎓', '📱', '💻', '🛒', '🎁', '📦', '✍️']
}

// Post Templates
const postTemplates = [
    {
        name: 'Ro\'yxat post',
        icon: '📝',
        content: `🎯 [MAVZU]

✅ 1. [Birinchi nuqta]
✅ 2. [Ikkinchi nuqta]
✅ 3. [Uchinchi nuqta]

💬 Fikringizni yozing 👇`
    },
    {
        name: 'Motivatsiya',
        icon: '💪',
        content: `💡 [IQTIBOS YOKI FIKR]

✨ [Tushuntirish]

🔥 Bugun boshla, ertaga natija ko'r!

❤️ Yoqsa like bos`
    },
    {
        name: 'E\'lon',
        icon: '📢',
        content: `🔔 E'LON!

📌 [Asosiy xabar]

📅 Sana: [kun/oy]
📍 Manzil: [joy]
💰 Narx: [summa]

👇 Batafsil ma'lumot uchun yozing`
    },
    {
        name: 'Savol-javob',
        icon: '❓',
        content: `❓ SAVOL: [Savol matni]

👇 Javobingizni yozing!

💡 To'g'ri javob [vaqt]da e'lon qilinadi`
    }
]

// Computed
const scriptLength = computed(() => materialForm.value.script?.length || 0)

const formattedPreview = computed(() => {
    if (!materialForm.value.script) return '<span class="text-gray-400 italic">Post matni shu yerda ko\'rinadi...</span>'
    let text = materialForm.value.script
    // Convert Telegram-style formatting
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    text = text.replace(/__(.*?)__/g, '<em>$1</em>')
    text = text.replace(/`(.*?)`/g, '<code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">$1</code>')
    return text
})

// Functions
const insertEmoji = (emoji) => {
    const textarea = scriptTextarea.value
    if (!textarea) return

    const start = textarea.selectionStart
    const end = textarea.selectionEnd
    const text = materialForm.value.script || ''

    materialForm.value.script = text.substring(0, start) + emoji + text.substring(end)

    // Set cursor position after emoji
    nextTick(() => {
        textarea.focus()
        textarea.selectionStart = textarea.selectionEnd = start + emoji.length
    })
}

const formatText = (type) => {
    const textarea = scriptTextarea.value
    if (!textarea) return

    const start = textarea.selectionStart
    const end = textarea.selectionEnd
    const text = materialForm.value.script || ''
    const selectedText = text.substring(start, end)

    let formattedText = ''
    let cursorOffset = 0

    switch (type) {
        case 'bold':
            formattedText = `**${selectedText || 'matn'}**`
            cursorOffset = selectedText ? formattedText.length : 2
            break
        case 'italic':
            formattedText = `__${selectedText || 'matn'}__`
            cursorOffset = selectedText ? formattedText.length : 2
            break
        case 'code':
            formattedText = `\`${selectedText || 'kod'}\``
            cursorOffset = selectedText ? formattedText.length : 1
            break
    }

    materialForm.value.script = text.substring(0, start) + formattedText + text.substring(end)

    nextTick(() => {
        textarea.focus()
        if (selectedText) {
            textarea.selectionStart = start
            textarea.selectionEnd = start + formattedText.length
        } else {
            textarea.selectionStart = textarea.selectionEnd = start + cursorOffset
        }
    })
}

const applyTemplate = (content) => {
    materialForm.value.script = content
    activeEmojiCategory.value = null
}

const handleKeydown = (e) => {
    if (e.ctrlKey || e.metaKey) {
        if (e.key === 'b') {
            e.preventDefault()
            formatText('bold')
        } else if (e.key === 'i') {
            e.preventDefault()
            formatText('italic')
        }
    }
}

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(materialForm.value.script)
        copied.value = true
        setTimeout(() => copied.value = false, 2000)
    } catch (err) {
        console.error('Copy failed:', err)
    }
}

// Platform Icons
const InstagramIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' })
        ])
    }
}

const TelegramIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z' })
        ])
    }
}

const FacebookIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' })
        ])
    }
}

const YouTubeIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z' })
        ])
    }
}

const YouTubeShortsIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M17.77 10.32c-.77-.32-1.2-.5-1.2-.5L18 9.06c1.84-.96 2.53-3.23 1.56-5.06s-3.24-2.53-5.07-1.56L6 6.94c-1.29.68-2.07 2.04-2 3.49.07 1.42.93 2.67 2.22 3.25.03.01 1.2.5 1.2.5L6 14.93c-1.83.97-2.53 3.24-1.56 5.07.97 1.83 3.24 2.53 5.07 1.56l8.5-4.5c1.29-.68 2.06-2.04 1.99-3.49-.07-1.42-.94-2.68-2.23-3.25zM10 14.65v-5.3L15 12l-5 2.65z' })
        ])
    }
}

const DefaultPlatformIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
        ])
    }
}

// Computed
const hasStats = computed(() => {
    return props.post.views || props.post.likes || props.post.comments || props.post.shares
})

// Helpers
const goBack = () => {
    router.get('/business/marketing/content')
}

const editPost = () => {
    router.get(`/business/marketing/content/${props.post.id}/edit`)
}

const deletePost = () => {
    if (confirm('Kontentni o\'chirishni xohlaysizmi?')) {
        router.delete(`/business/marketing/content/${props.post.id}`)
    }
}

const saveMaterial = () => {
    isSavingMaterial.value = true

    const data = {}
    if (materialForm.value.type === 'script') {
        data.ai_suggestions = { ...props.post.ai_suggestions, script: materialForm.value.script }
    } else {
        data.external_url = materialForm.value.url
    }

    router.put(`/business/marketing/content/${props.post.id}`, {
        ...props.post,
        platform: props.post.platforms,
        ...data
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAddMaterialModal.value = false
            isSavingMaterial.value = false
            materialForm.value = { type: 'script', script: '', url: '' }
        },
        onError: () => {
            isSavingMaterial.value = false
        }
    })
}

const formatNumber = (num) => {
    if (!num) return '0'
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
    return num.toString()
}

// Platform helpers
const getPlatformIcon = (platform) => {
    const icons = {
        'Instagram': InstagramIcon,
        'Telegram': TelegramIcon,
        'Facebook': FacebookIcon,
        'YouTube': YouTubeIcon,
        'YouTube Shorts': YouTubeShortsIcon
    }
    return icons[platform] || DefaultPlatformIcon
}

const getPlatformBgClass = (platform) => {
    const classes = {
        'Instagram': 'bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/40 dark:to-pink-900/40',
        'Telegram': 'bg-sky-100 dark:bg-sky-900/40',
        'Facebook': 'bg-blue-100 dark:bg-blue-900/40',
        'YouTube': 'bg-red-100 dark:bg-red-900/40',
        'YouTube Shorts': 'bg-red-50 dark:bg-red-900/30'
    }
    return classes[platform] || 'bg-gray-100 dark:bg-gray-700'
}

const getPlatformIconClass = (platform) => {
    const classes = {
        'Instagram': 'text-pink-600 dark:text-pink-400',
        'Telegram': 'text-sky-600 dark:text-sky-400',
        'Facebook': 'text-blue-600 dark:text-blue-400',
        'YouTube': 'text-red-600 dark:text-red-400',
        'YouTube Shorts': 'text-red-500 dark:text-red-400'
    }
    return classes[platform] || 'text-gray-600 dark:text-gray-400'
}

// Status helpers
const getStatusLabel = (status) => {
    const labels = { draft: 'Qoralama', scheduled: 'Rejalashtirilgan', published: 'Nashr qilingan' }
    return labels[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        scheduled: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        published: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
    }
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
}

const getStatusDotClass = (status) => {
    const classes = {
        draft: 'bg-gray-400',
        scheduled: 'bg-amber-500',
        published: 'bg-emerald-500'
    }
    return classes[status] || 'bg-gray-400'
}

// Content Type helpers
const getContentTypeLabel = (type) => {
    const labels = {
        educational: "Ta'limiy",
        entertaining: "Ko'ngil ochuvchi",
        inspirational: 'Ilhomlantiruvchi',
        promotional: 'Reklama',
        behind_scenes: 'Sahna ortidan',
        ugc: 'Foydalanuvchi kontenti'
    }
    return labels[type] || type || '—'
}

const getContentTypeBadgeClass = (type) => {
    const classes = {
        educational: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        entertaining: 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300',
        inspirational: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        promotional: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        behind_scenes: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        ugc: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'
    }
    return classes[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
}

// Format helpers
const getFormatLabel = (format) => {
    const labels = {
        short_video: 'Qisqa Video',
        long_video: 'Uzun Video',
        carousel: 'Karusel',
        single_image: 'Rasm',
        story: 'Story',
        text_post: 'Matn',
        live: 'Jonli efir',
        poll: "So'rovnoma"
    }
    return labels[format] || format || '—'
}
</script>
