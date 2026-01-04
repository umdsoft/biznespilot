<template>
    <div class="space-y-6">
        <!-- Tab Navigation with Scan All Button -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-2">
            <div class="flex items-center gap-2">
                <nav class="flex-1 flex space-x-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium transition-all',
                            activeTab === tab.id
                                ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        <component :is="tab.icon" class="w-5 h-5" />
                        <span class="hidden sm:inline">{{ tab.label }}</span>
                        <span v-if="tab.count" class="ml-1 px-2 py-0.5 rounded-full text-xs bg-indigo-500 text-white">
                            {{ tab.count }}
                        </span>
                    </button>
                </nav>
                <!-- Full Scan Button -->
                <button
                    @click="runFullScan"
                    :disabled="isScanning"
                    class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-xl text-sm font-medium transition-all disabled:opacity-50 shadow-lg shadow-emerald-500/25"
                >
                    <svg :class="['w-4 h-4', { 'animate-spin': isScanning }]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span class="hidden md:inline">{{ isScanning ? 'Skanerlanmoqda...' : 'To\'liq skanerlash' }}</span>
                </button>
            </div>
        </div>

        <!-- Content Analysis Tab -->
        <div v-show="activeTab === 'content'" class="space-y-6">
            <!-- Scan Content Button -->
            <div v-if="!contentInsights.total_posts && (competitor.instagram_handle || competitor.telegram_handle)"
                 class="bg-gradient-to-r from-pink-50 to-rose-50 dark:from-pink-900/20 dark:to-rose-900/20 rounded-xl border border-pink-200 dark:border-pink-800 p-6 text-center">
                <div class="w-16 h-16 mx-auto bg-pink-100 dark:bg-pink-900/30 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Kontent tahlilini boshlang</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {{ competitor.instagram_handle ? 'Instagram' : '' }}{{ competitor.instagram_handle && competitor.telegram_handle ? ' va ' : '' }}{{ competitor.telegram_handle ? 'Telegram' : '' }} postlarini avtomatik tahlil qilish
                </p>
                <button
                    @click="scanContent"
                    :disabled="scanningContent"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-pink-600 hover:bg-pink-700 text-white rounded-xl font-medium transition-all disabled:opacity-50"
                >
                    <svg :class="['w-5 h-5', { 'animate-spin': scanningContent }]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ scanningContent ? 'Tahlil qilinmoqda...' : 'Kontentni tahlil qilish' }}
                </button>
            </div>

            <!-- Content Stats Overview -->
            <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ contentInsights.total_posts || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Jami postlar (30 kun)</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ contentInsights.avg_engagement_rate?.toFixed(1) || 0 }}%</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">O'rtacha engagement</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-pink-600 dark:text-pink-400">{{ contentInsights.viral_posts || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Viral postlar</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ contentInsights.posts_per_day?.toFixed(1) || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Post/kun</div>
                </div>
            </div>

            <!-- Best Posting Times -->
            <div v-if="contentInsights.total_posts" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Eng yaxshi post vaqtlari</h3>
                    <button @click="scanContent" :disabled="scanningContent" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1">
                        <svg :class="['w-4 h-4', { 'animate-spin': scanningContent }]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Yangilash
                    </button>
                </div>
                <div v-if="Object.keys(contentInsights.best_posting_times || {}).length" class="space-y-3">
                    <div v-for="(data, hour) in contentInsights.best_posting_times" :key="hour" class="flex items-center gap-4">
                        <div class="w-16 text-sm font-medium text-gray-600 dark:text-gray-400">{{ hour }}:00</div>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-indigo-500 h-full rounded-full" :style="{ width: Math.min((data.avg_engagement || 0) * 10, 100) + '%' }"></div>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ data.posts }} post</div>
                    </div>
                </div>
                <p v-else class="text-gray-500 dark:text-gray-400 text-sm">Ma'lumot to'planmoqda...</p>
            </div>

            <!-- Top Hashtags -->
            <div v-if="contentInsights.total_posts" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Top hashtaglar</h3>
                <div v-if="Object.keys(contentInsights.top_hashtags || {}).length" class="flex flex-wrap gap-2">
                    <span
                        v-for="(count, tag) in contentInsights.top_hashtags"
                        :key="tag"
                        class="px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm font-medium"
                    >
                        #{{ tag }} <span class="text-indigo-400 dark:text-indigo-500">{{ count }}</span>
                    </span>
                </div>
                <p v-else class="text-gray-500 dark:text-gray-400 text-sm">Hashtaglar topilmadi</p>
            </div>
        </div>

        <!-- Ads Tab -->
        <div v-show="activeTab === 'ads'" class="space-y-6">
            <!-- Info Banner -->
            <div v-if="!adInsights.total_ads"
                 class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Raqobatchi reklamalarini kuzating</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Meta Ad Library'dan reklamalarni qo'lda tekshirib, muhim ma'lumotlarni shu yerga qo'shing. Avtomatik skanerlash cheklangan.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <a
                                :href="metaAdLibraryUrl"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Meta Ad Library'ni ochish
                            </a>
                            <button
                                @click="scanAds"
                                :disabled="scanningAds"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
                            >
                                <svg :class="['w-4 h-4', { 'animate-spin': scanningAds }]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ scanningAds ? 'Tekshirilmoqda...' : 'Avtomatik tekshirish' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ads Stats -->
            <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ adInsights.active_ads || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Faol reklamalar</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ adInsights.total_ads || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Jami (30 kun)</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ adInsights.avg_ad_lifespan || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">O'rtacha kun</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ adInsights.video_ads_percent || 0 }}%</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Video reklamalar</div>
                </div>
            </div>

            <!-- Ad Types Chart -->
            <div v-if="adInsights.total_ads" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Reklama turlari</h3>
                    <button @click="scanAds" :disabled="scanningAds" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1">
                        <svg :class="['w-4 h-4', { 'animate-spin': scanningAds }]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Yangilash
                    </button>
                </div>
                <div v-if="Object.keys(adInsights.ad_types || {}).length" class="space-y-3">
                    <div v-for="(count, type) in adInsights.ad_types" :key="type" class="flex items-center gap-4">
                        <div class="w-24 text-sm font-medium text-gray-600 dark:text-gray-400 capitalize">{{ type || 'Noma\'lum' }}</div>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                            <div
                                class="h-full rounded-full"
                                :class="getAdTypeColor(type)"
                                :style="{ width: getAdTypePercent(type) + '%' }"
                            ></div>
                        </div>
                        <div class="w-12 text-sm text-right font-medium text-gray-900 dark:text-gray-100">{{ count }}</div>
                    </div>
                </div>
                <p v-else class="text-gray-500 dark:text-gray-400 text-sm">Reklamalar topilmadi</p>
            </div>

        </div>

        <!-- Prices Tab -->
        <div v-show="activeTab === 'prices'" class="space-y-6">
            <!-- Price Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ priceInsights.total_products || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Mahsulotlar</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ priceInsights.products_on_sale || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Chegirmada</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ priceInsights.avg_discount?.toFixed(0) || 0 }}%</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">O'rtacha chegirma</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ priceInsights.active_promotions || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Faol aksiyalar</div>
                </div>
            </div>

            <!-- Price Trend -->
            <div v-if="priceInsights.total_products" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Narx tendensiyasi</h3>
                    <span :class="getPriceTrendClass(priceInsights.price_trend)" class="px-3 py-1 rounded-full text-sm font-medium">
                        {{ getPriceTrendText(priceInsights.price_trend) }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    So'nggi 30 kunda {{ priceInsights.price_changes_30d || 0 }} ta narx o'zgarishi kuzatildi
                </p>
            </div>

        </div>

        <!-- Reviews Tab -->
        <div v-show="activeTab === 'reviews'" class="space-y-6">
            <!-- Scan Reviews Empty State -->
            <div v-if="!reviewInsights.total_reviews && !Object.keys(reviewInsights.platforms || {}).length"
                 class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-xl border border-amber-200 dark:border-amber-800 p-6 text-center">
                <div class="w-16 h-16 mx-auto bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Sharhlarni kuzatishni boshlang</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Google, 2GIS yoki Yandex sharh manbasini qo'shing
                </p>
            </div>

            <!-- Review Stats -->
            <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-2">
                        <span class="text-3xl font-bold text-amber-500">{{ reviewInsights.avg_rating?.toFixed(1) || '—' }}</span>
                        <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">O'rtacha reyting</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ reviewInsights.total_reviews || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Jami sharhlar</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ reviewInsights.response_rate || 0 }}%</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Javob berish</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ reviewInsights.critical_reviews || 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Salbiy sharhlar</div>
                </div>
            </div>

            <!-- Sentiment Breakdown -->
            <div v-if="reviewInsights.total_reviews" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sharh sentiment tahlili</h3>
                    <button @click="scanReviews" :disabled="scanningReviews" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1">
                        <svg :class="['w-4 h-4', { 'animate-spin': scanningReviews }]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Yangilash
                    </button>
                </div>
                <div v-if="reviewInsights.sentiment_breakdown" class="flex items-center gap-1 h-8 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                    <div
                        class="h-full bg-emerald-500 flex items-center justify-center text-white text-xs font-medium transition-all"
                        :style="{ width: getSentimentPercent('positive') + '%' }"
                        v-if="getSentimentPercent('positive') > 5"
                    >
                        {{ reviewInsights.sentiment_breakdown.positive }}
                    </div>
                    <div
                        class="h-full bg-gray-400 flex items-center justify-center text-white text-xs font-medium transition-all"
                        :style="{ width: getSentimentPercent('neutral') + '%' }"
                        v-if="getSentimentPercent('neutral') > 5"
                    >
                        {{ reviewInsights.sentiment_breakdown.neutral }}
                    </div>
                    <div
                        class="h-full bg-red-500 flex items-center justify-center text-white text-xs font-medium transition-all"
                        :style="{ width: getSentimentPercent('negative') + '%' }"
                        v-if="getSentimentPercent('negative') > 5"
                    >
                        {{ reviewInsights.sentiment_breakdown.negative }}
                    </div>
                </div>
                <div class="flex items-center justify-center gap-6 mt-4 text-sm">
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Ijobiy</span>
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-gray-400"></span> Neytral</span>
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500"></span> Salbiy</span>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, h, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    competitor: Object,
    contentInsights: { type: Object, default: () => ({}) },
    adInsights: { type: Object, default: () => ({}) },
    priceInsights: { type: Object, default: () => ({}) },
    reviewInsights: { type: Object, default: () => ({}) },
});

const activeTab = ref('content');
const isScanning = ref(false);
const scanningContent = ref(false);
const scanningAds = ref(false);
const scanningReviews = ref(false);


// Meta Ad Library URL
const metaAdLibraryUrl = computed(() => {
    const searchTerm = props.competitor?.facebook_page || props.competitor?.name || '';
    return `https://www.facebook.com/ads/library/?active_status=active&ad_type=all&country=UZ&q=${encodeURIComponent(searchTerm)}`;
});

// Icon components
const ContentIcon = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' })
        ]);
    }
};

const AdsIcon = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z' }),
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z' })
        ]);
    }
};

const PriceIcon = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
        ]);
    }
};

const ReviewsIcon = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' })
        ]);
    }
};

const tabs = [
    { id: 'content', label: 'Kontent', icon: ContentIcon, count: props.contentInsights?.total_posts },
    { id: 'ads', label: 'Reklamalar', icon: AdsIcon, count: props.adInsights?.active_ads },
    { id: 'prices', label: 'Narxlar', icon: PriceIcon, count: props.priceInsights?.total_products },
    { id: 'reviews', label: 'Sharhlar', icon: ReviewsIcon, count: props.reviewInsights?.total_reviews },
];

// Full scan - triggers monitoring job
function runFullScan() {
    isScanning.value = true;
    router.post(route('business.competitors.monitor', props.competitor.id), {}, {
        preserveScroll: true,
        onFinish: () => { isScanning.value = false; }
    });
}

// Individual scans
function scanContent() {
    scanningContent.value = true;
    router.post(route('business.competitors.content.analyze', props.competitor.id), {}, {
        preserveScroll: true,
        onFinish: () => { scanningContent.value = false; }
    });
}

function scanAds() {
    scanningAds.value = true;
    router.post(route('business.competitors.ads.scan', props.competitor.id), {}, {
        preserveScroll: true,
        onFinish: () => { scanningAds.value = false; }
    });
}

function scanReviews() {
    scanningReviews.value = true;
    router.post(route('business.competitors.reviews.scan', props.competitor.id), {}, {
        preserveScroll: true,
        onFinish: () => { scanningReviews.value = false; }
    });
}


// Helper functions
function getAdTypeColor(type) {
    const colors = { image: 'bg-blue-500', video: 'bg-purple-500', carousel: 'bg-pink-500' };
    return colors[type] || 'bg-gray-500';
}

function getAdTypePercent(type) {
    const total = Object.values(props.adInsights?.ad_types || {}).reduce((a, b) => a + b, 0);
    if (!total) return 0;
    return ((props.adInsights.ad_types[type] || 0) / total) * 100;
}

function getPriceTrendClass(trend) {
    const classes = {
        increasing: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        decreasing: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
        stable: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    };
    return classes[trend] || classes.stable;
}

function getPriceTrendText(trend) {
    const texts = { increasing: 'Narxlar oshmoqda', decreasing: 'Narxlar tushmoqda', stable: 'Barqaror' };
    return texts[trend] || 'Noma\'lum';
}

function getSentimentPercent(type) {
    const breakdown = props.reviewInsights?.sentiment_breakdown || {};
    const total = (breakdown.positive || 0) + (breakdown.neutral || 0) + (breakdown.negative || 0);
    if (!total) return 0;
    return ((breakdown[type] || 0) / total) * 100;
}
</script>
