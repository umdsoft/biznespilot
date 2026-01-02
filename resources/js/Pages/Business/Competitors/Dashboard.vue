<template>
    <BusinessLayout title="Raqobatchilar Dashboard">
        <div class="p-6 space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Raqobatchilar Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Raqobatchilar tahlili va monitoring</p>
                </div>
                <Link
                    :href="route('business.competitors.index')"
                    class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Ro'yxat
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.total_competitors || 0 }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami Raqobatchilar</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.active_monitoring || 0 }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Faol Kuzatuv</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-xl">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.high_threats || 0 }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Yuqori Tahdid</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.unread_alerts || 0 }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Yangi Ogohlantirishlar</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insights Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Top Threat -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Eng Katta Tahdid</h3>
                    </div>
                    <div v-if="insights?.top_threat" class="space-y-2">
                        <Link
                            :href="route('business.competitors.show', insights.top_threat.id)"
                            class="text-lg font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
                        >
                            {{ insights.top_threat.name }}
                        </Link>
                        <div class="flex items-center gap-2">
                            <span :class="getThreatBadgeClass(insights.top_threat.threat_level)" class="px-2.5 py-1 text-xs font-medium rounded-lg">
                                {{ getThreatLevelText(insights.top_threat.threat_level) }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">tahdid darajasi</span>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-4 text-gray-400 dark:text-gray-500">
                        <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm">Tahdid yo'q</p>
                    </div>
                </div>

                <!-- Fastest Growing -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Eng Tez O'sayotgan</h3>
                    </div>
                    <div v-if="insights?.fastest_growing" class="space-y-2">
                        <Link
                            :href="route('business.competitors.show', insights.fastest_growing.id)"
                            class="text-lg font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
                        >
                            {{ insights.fastest_growing.name }}
                        </Link>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-lg">
                                +{{ insights.fastest_growing.growth_rate?.toFixed(1) || 0 }}%
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">o'sish</span>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-4 text-gray-400 dark:text-gray-500">
                        <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p class="text-sm">Ma'lumot yo'q</p>
                    </div>
                </div>

                <!-- Most Engaging -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Eng Faol Auditoriya</h3>
                    </div>
                    <div v-if="insights?.most_engaging" class="space-y-2">
                        <Link
                            :href="route('business.competitors.show', insights.most_engaging.id)"
                            class="text-lg font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
                        >
                            {{ insights.most_engaging.name }}
                        </Link>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-medium rounded-lg">
                                {{ insights.most_engaging.engagement_rate?.toFixed(1) || 0 }}%
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">engagement</span>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-4 text-gray-400 dark:text-gray-500">
                        <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p class="text-sm">Ma'lumot yo'q</p>
                    </div>
                </div>
            </div>

            <!-- Recent Alerts & Competitors List -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Alerts -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">So'nggi Ogohlantirishlar</h3>
                        <Link
                            :href="route('business.competitors.alerts')"
                            class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
                        >
                            Barchasini ko'rish
                        </Link>
                    </div>

                    <div class="p-5">
                        <div v-if="unread_alerts && unread_alerts.length > 0" class="space-y-3">
                            <div
                                v-for="alert in unread_alerts"
                                :key="alert.id"
                                class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 transition-colors"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span :class="getSeverityBadgeClass(alert.severity)" class="px-2 py-0.5 text-xs font-medium rounded-md">
                                                {{ getSeverityText(alert.severity) }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDate(alert.created_at) }}
                                            </span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">{{ alert.title }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ alert.message }}</p>
                                    </div>
                                    <button
                                        @click="markAlertAsRead(alert.id)"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        title="O'qildi deb belgilash"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-500">
                            <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-full mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                            <p class="text-sm">Yangi ogohlantirishlar yo'q</p>
                        </div>
                    </div>
                </div>

                <!-- Top Competitors -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Asosiy Raqobatchilar</h3>
                    </div>

                    <div class="p-5">
                        <div v-if="competitors && competitors.length > 0" class="space-y-3">
                            <div
                                v-for="competitor in competitors.slice(0, 5)"
                                :key="competitor.id"
                                class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 hover:border-indigo-200 dark:hover:border-indigo-700 transition-colors group"
                            >
                                <div class="flex-1 min-w-0">
                                    <Link
                                        :href="route('business.competitors.show', competitor.id)"
                                        class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                                    >
                                        {{ competitor.name }}
                                    </Link>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ competitor.industry || 'Soha belgilanmagan' }}
                                        </span>
                                        <span :class="getThreatBadgeClass(competitor.threat_level)" class="px-2 py-0.5 text-xs font-medium rounded-md">
                                            {{ getThreatLevelText(competitor.threat_level) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <span v-if="competitor.instagram_handle" class="text-pink-600 dark:text-pink-400" :title="competitor.instagram_handle">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                        </svg>
                                    </span>
                                    <span v-if="competitor.telegram_handle" class="text-blue-500 dark:text-blue-400" :title="competitor.telegram_handle">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                        </svg>
                                    </span>
                                    <span v-if="competitor.facebook_page" class="text-blue-600 dark:text-blue-400" :title="competitor.facebook_page">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    </span>
                                    <span v-if="competitor.tiktok_handle" class="text-gray-900 dark:text-gray-100" :title="competitor.tiktok_handle">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-500">
                            <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-full mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <p class="text-sm">Raqobatchilar mavjud emas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const props = defineProps({
    stats: Object,
    insights: Object,
    competitors: Array,
    unread_alerts: Array,
});

function getThreatLevelText(level) {
    const levels = {
        low: 'Past',
        medium: 'O\'rta',
        high: 'Yuqori',
        critical: 'Kritik'
    };
    return levels[level] || level;
}

function getThreatBadgeClass(level) {
    const classes = {
        low: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        medium: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        high: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
        critical: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
    };
    return classes[level] || classes.medium;
}

function getSeverityText(severity) {
    const severities = {
        low: 'Past',
        medium: 'O\'rta',
        high: 'Yuqori',
        critical: 'Kritik'
    };
    return severities[severity] || severity;
}

function getSeverityBadgeClass(severity) {
    const classes = {
        low: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        medium: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        high: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
        critical: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
    };
    return classes[severity] || classes.medium;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = Math.floor((now - date) / (1000 * 60 * 60));

    if (diffInHours < 1) return 'Hozirgina';
    if (diffInHours < 24) return `${diffInHours} soat oldin`;
    if (diffInHours < 48) return 'Kecha';

    return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' });
}

function markAlertAsRead(alertId) {
    router.post(route('business.competitors.alerts.read', alertId), {}, {
        preserveScroll: true,
    });
}
</script>
