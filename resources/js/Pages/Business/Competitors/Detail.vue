<template>
    <BusinessLayout :title="competitor.name">
        <div class="p-6 space-y-6">
            <!-- Breadcrumb & Header -->
            <div class="flex flex-col gap-6">
                <!-- Breadcrumb -->
                <Link
                    :href="route('business.competitors.index')"
                    class="inline-flex items-center text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-sm"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Raqobatchilar ro'yxati
                </Link>

                <!-- Main Header Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <!-- Left: Competitor Info -->
                            <div class="flex items-center gap-4">
                                <!-- Avatar -->
                                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-indigo-500/25">
                                    {{ competitor.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ competitor.name }}</h1>
                                    <div class="flex flex-wrap items-center gap-2 mt-2">
                                        <!-- Threat Level Badge -->
                                        <span :class="getThreatBadgeClass(competitor.threat_level)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="getThreatDotClass(competitor.threat_level)"></span>
                                            {{ getThreatLevelText(competitor.threat_level) }} tahdid
                                        </span>
                                        <!-- Status Badge -->
                                        <span :class="getStatusBadgeClass(competitor.status)" class="px-2.5 py-1 rounded-full text-xs font-medium">
                                            {{ getStatusText(competitor.status) }}
                                        </span>
                                        <!-- Industry & Location -->
                                        <span v-if="competitor.industry" class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ competitor.industry }}
                                        </span>
                                        <span v-if="competitor.location" class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            {{ competitor.location }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Actions -->
                            <div class="flex flex-wrap gap-3">
                                <button
                                    @click="openEditModal"
                                    class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-200 transition-all"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Tahrirlash
                                </button>
                                <button
                                    @click="monitorNow"
                                    :disabled="monitoring"
                                    class="inline-flex items-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 rounded-xl text-sm font-medium text-white transition-all disabled:opacity-50 shadow-lg shadow-emerald-500/25"
                                >
                                    <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': monitoring }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ monitoring ? 'Tekshirilmoqda...' : 'Kuzatishni boshlash' }}
                                </button>
                            </div>
                        </div>

                        <!-- Description -->
                        <p v-if="competitor.description" class="mt-4 text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                            {{ competitor.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards Row -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- Instagram Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-pink-500 to-rose-600 rounded-2xl p-5 text-white shadow-lg shadow-pink-500/20">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <svg class="w-8 h-8 opacity-90" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                            <span v-if="getGrowthRate('instagram')" :class="getGrowthRate('instagram') > 0 ? 'bg-white/20' : 'bg-red-900/30'" class="text-xs px-2 py-0.5 rounded-full font-medium">
                                {{ getGrowthRate('instagram') > 0 ? '+' : '' }}{{ getGrowthRate('instagram')?.toFixed(1) }}%
                            </span>
                        </div>
                        <div class="text-3xl font-bold">{{ formatNumber(latest_metric?.instagram_followers) || '—' }}</div>
                        <div class="text-xs text-white/80 mt-1 font-medium">Instagram Followers</div>
                        <a v-if="competitor.instagram_handle" :href="`https://instagram.com/${competitor.instagram_handle.replace('@', '')}`" target="_blank" class="text-xs text-white/70 hover:text-white mt-2 inline-flex items-center gap-1">
                            {{ competitor.instagram_handle }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        </a>
                    </div>
                </div>

                <!-- Telegram Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-5 text-white shadow-lg shadow-blue-500/20">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <svg class="w-8 h-8 opacity-90" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold">{{ formatNumber(latest_metric?.telegram_members) || '—' }}</div>
                        <div class="text-xs text-white/80 mt-1 font-medium">Telegram A'zolar</div>
                        <a v-if="competitor.telegram_handle" :href="`https://t.me/${competitor.telegram_handle.replace('@', '')}`" target="_blank" class="text-xs text-white/70 hover:text-white mt-2 inline-flex items-center gap-1">
                            {{ competitor.telegram_handle }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        </a>
                    </div>
                </div>

                <!-- Facebook Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-5 text-white shadow-lg shadow-blue-600/20">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <svg class="w-8 h-8 opacity-90" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold">{{ formatNumber(latest_metric?.facebook_followers) || '—' }}</div>
                        <div class="text-xs text-white/80 mt-1 font-medium">Facebook Followers</div>
                        <span v-if="competitor.facebook_page" class="text-xs text-white/70 mt-2 inline-block truncate max-w-full">{{ competitor.facebook_page }}</span>
                    </div>
                </div>

                <!-- TikTok Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-5 text-white shadow-lg shadow-gray-800/20">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <svg class="w-8 h-8 opacity-90" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold">{{ formatNumber(latest_metric?.tiktok_followers) || '—' }}</div>
                        <div class="text-xs text-white/80 mt-1 font-medium">TikTok Followers</div>
                        <span v-if="competitor.tiktok_handle" class="text-xs text-white/70 mt-2 inline-block">{{ competitor.tiktok_handle }}</span>
                    </div>
                </div>

                <!-- Engagement Rate Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl p-5 text-white shadow-lg shadow-purple-500/20">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <svg class="w-8 h-8 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div class="text-3xl font-bold">{{ latest_metric?.instagram_engagement_rate?.toFixed(1) || '—' }}<span v-if="latest_metric?.instagram_engagement_rate" class="text-lg">%</span></div>
                        <div class="text-xs text-white/80 mt-1 font-medium">Engagement Rate</div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: SWOT & History (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- SWOT Analysis Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">SWOT Tahlil</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Raqobatchining kuchli va zaif tomonlari</p>
                            </div>
                            <button
                                @click="generateSwot"
                                :disabled="generating_swot"
                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white rounded-xl text-sm font-medium transition-all disabled:opacity-50 shadow-lg shadow-purple-500/25"
                            >
                                <svg class="w-4 h-4 mr-2" :class="{ 'animate-pulse': generating_swot }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                {{ generating_swot ? 'AI tahlil...' : 'AI bilan yaratish' }}
                            </button>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Strengths -->
                                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-5 border border-emerald-200 dark:border-emerald-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-semibold text-emerald-800 dark:text-emerald-300 flex items-center">
                                            <span class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center mr-2 shadow">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </span>
                                            Kuchli tomonlar
                                        </h3>
                                        <button @click="addSwotItem('strengths')" class="w-7 h-7 rounded-lg bg-emerald-200 dark:bg-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-300 dark:hover:bg-emerald-700 flex items-center justify-center transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                    <ul class="space-y-2">
                                        <li v-for="(item, index) in localSwot.strengths" :key="'s-'+index" class="flex items-start group">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-2 mr-2 flex-shrink-0"></span>
                                            <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">{{ item }}</span>
                                            <button @click="removeSwotItem('strengths', index)" class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 ml-2 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </li>
                                        <li v-if="!localSwot.strengths?.length" class="text-sm text-gray-400 dark:text-gray-500 italic">Ma'lumot yo'q</li>
                                    </ul>
                                </div>

                                <!-- Weaknesses -->
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-5 border border-red-200 dark:border-red-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-semibold text-red-800 dark:text-red-300 flex items-center">
                                            <span class="w-8 h-8 rounded-lg bg-red-500 text-white flex items-center justify-center mr-2 shadow">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </span>
                                            Zaif tomonlar
                                        </h3>
                                        <button @click="addSwotItem('weaknesses')" class="w-7 h-7 rounded-lg bg-red-200 dark:bg-red-800 text-red-700 dark:text-red-300 hover:bg-red-300 dark:hover:bg-red-700 flex items-center justify-center transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                    <ul class="space-y-2">
                                        <li v-for="(item, index) in localSwot.weaknesses" :key="'w-'+index" class="flex items-start group">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 mt-2 mr-2 flex-shrink-0"></span>
                                            <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">{{ item }}</span>
                                            <button @click="removeSwotItem('weaknesses', index)" class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 ml-2 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </li>
                                        <li v-if="!localSwot.weaknesses?.length" class="text-sm text-gray-400 dark:text-gray-500 italic">Ma'lumot yo'q</li>
                                    </ul>
                                </div>

                                <!-- Opportunities -->
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-5 border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-semibold text-blue-800 dark:text-blue-300 flex items-center">
                                            <span class="w-8 h-8 rounded-lg bg-blue-500 text-white flex items-center justify-center mr-2 shadow">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                </svg>
                                            </span>
                                            Imkoniyatlar
                                        </h3>
                                        <button @click="addSwotItem('opportunities')" class="w-7 h-7 rounded-lg bg-blue-200 dark:bg-blue-800 text-blue-700 dark:text-blue-300 hover:bg-blue-300 dark:hover:bg-blue-700 flex items-center justify-center transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                    <ul class="space-y-2">
                                        <li v-for="(item, index) in localSwot.opportunities" :key="'o-'+index" class="flex items-start group">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-2 mr-2 flex-shrink-0"></span>
                                            <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">{{ item }}</span>
                                            <button @click="removeSwotItem('opportunities', index)" class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 ml-2 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </li>
                                        <li v-if="!localSwot.opportunities?.length" class="text-sm text-gray-400 dark:text-gray-500 italic">Ma'lumot yo'q</li>
                                    </ul>
                                </div>

                                <!-- Threats -->
                                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-5 border border-amber-200 dark:border-amber-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-semibold text-amber-800 dark:text-amber-300 flex items-center">
                                            <span class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center mr-2 shadow">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </span>
                                            Tahdidlar
                                        </h3>
                                        <button @click="addSwotItem('threats')" class="w-7 h-7 rounded-lg bg-amber-200 dark:bg-amber-800 text-amber-700 dark:text-amber-300 hover:bg-amber-300 dark:hover:bg-amber-700 flex items-center justify-center transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                    <ul class="space-y-2">
                                        <li v-for="(item, index) in localSwot.threats" :key="'t-'+index" class="flex items-start group">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-2 mr-2 flex-shrink-0"></span>
                                            <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">{{ item }}</span>
                                            <button @click="removeSwotItem('threats', index)" class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 ml-2 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </li>
                                        <li v-if="!localSwot.threats?.length" class="text-sm text-gray-400 dark:text-gray-500 italic">Ma'lumot yo'q</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div v-if="swotChanged" class="mt-6 flex justify-end">
                                <button
                                    @click="saveSwot"
                                    :disabled="saving_swot"
                                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-all disabled:opacity-50 shadow-lg shadow-indigo-500/25"
                                >
                                    <svg v-if="saving_swot" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ saving_swot ? 'Saqlanmoqda...' : 'O\'zgarishlarni saqlash' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Metrics History Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Ko'rsatkichlar tarixi</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">So'nggi 90 kunlik ma'lumotlar</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table v-if="metrics.length > 0" class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-900/50">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Sana</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Instagram</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Telegram</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Engagement</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">O'sish</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="metric in metrics.slice(0, 10)" :key="metric.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                            {{ formatDate(metric.recorded_date) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ metric.instagram_followers ? formatNumber(metric.instagram_followers) : '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ metric.telegram_members ? formatNumber(metric.telegram_members) : '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                            {{ metric.instagram_engagement_rate ? metric.instagram_engagement_rate.toFixed(1) + '%' : '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="metric.follower_growth_rate" :class="metric.follower_growth_rate > 0 ? 'text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400' : 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400'" class="px-2.5 py-1 rounded-full text-xs font-medium">
                                                {{ metric.follower_growth_rate > 0 ? '+' : '' }}{{ metric.follower_growth_rate.toFixed(1) }}%
                                            </span>
                                            <span v-else class="text-gray-400 dark:text-gray-500">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-else class="p-12 text-center">
                                <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <p class="text-gray-600 dark:text-gray-300 font-medium">Hozircha ko'rsatkichlar yo'q</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Kuzatishni boshlang yoki qo'lda ma'lumot kiriting</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Sidebar -->
                <div class="space-y-6">
                    <!-- Monitoring Status Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                            <span class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mr-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </span>
                            Kuzatuv holati
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Avtomatik kuzatuv</span>
                                <span :class="competitor.auto_monitor ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'" class="px-2.5 py-1 rounded-full text-xs font-medium">
                                    {{ competitor.auto_monitor ? 'Yoqilgan' : 'O\'chirilgan' }}
                                </span>
                            </div>
                            <div v-if="competitor.auto_monitor" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Tekshiruv oralig'i</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Har {{ competitor.check_frequency_hours }} soatda</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">So'nggi tekshiruv</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ competitor.last_checked_at ? formatDate(competitor.last_checked_at) : 'Tekshirilmagan' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Manual Metrics Entry Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                            <span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center mr-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </span>
                            Qo'lda ma'lumot kiritish
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Instagram Followers</label>
                                <input v-model="manualMetrics.instagram_followers" type="number" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="0">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telegram A'zolar</label>
                                <input v-model="manualMetrics.telegram_members" type="number" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="0">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Facebook Followers</label>
                                <input v-model="manualMetrics.facebook_followers" type="number" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="0">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Engagement Rate (%)</label>
                                <input v-model="manualMetrics.instagram_engagement_rate" type="number" step="0.1" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="0.0">
                            </div>
                            <button
                                @click="saveManualMetrics"
                                :disabled="saving_metrics"
                                class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-all disabled:opacity-50"
                            >
                                {{ saving_metrics ? 'Saqlanmoqda...' : 'Ma\'lumotlarni saqlash' }}
                            </button>
                        </div>
                    </div>

                    <!-- Notes Card -->
                    <div v-if="competitor.notes" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                            <span class="w-8 h-8 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 flex items-center justify-center mr-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </span>
                            Eslatmalar
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ competitor.notes }}</p>
                    </div>

                    <!-- Danger Zone Card -->
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 p-6">
                        <h3 class="font-semibold text-red-800 dark:text-red-300 mb-3 flex items-center">
                            <span class="w-8 h-8 rounded-lg bg-red-500 text-white flex items-center justify-center mr-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </span>
                            Xavfli zona
                        </h3>
                        <p class="text-sm text-red-700 dark:text-red-400 mb-4">Bu raqobatchini o'chirish barcha ma'lumotlarni yo'q qiladi.</p>
                        <button
                            @click="deleteCompetitor"
                            class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-medium transition-all"
                        >
                            Raqobatchini o'chirish
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Add SWOT Item Modal -->
        <Teleport to="body">
            <div v-if="showSwotModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showSwotModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ getSwotCategoryName(swotModalCategory) }} qo'shish</h3>
                        <textarea
                            v-model="newSwotItem"
                            rows="3"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                            placeholder="Yangi element kiriting..."
                        ></textarea>
                        <div class="flex justify-end gap-3 mt-4">
                            <button @click="showSwotModal = false" class="px-4 py-2.5 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 text-sm font-medium transition-colors">Bekor qilish</button>
                            <button @click="confirmAddSwotItem" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-all">Qo'shish</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </BusinessLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const props = defineProps({
    competitor: Object,
    metrics: {
        type: Array,
        default: () => []
    },
    latest_metric: Object,
    swot_analysis: Object,
});

// State
const generating_swot = ref(false);
const saving_swot = ref(false);
const monitoring = ref(false);
const saving_metrics = ref(false);
const showSwotModal = ref(false);
const swotModalCategory = ref('');
const newSwotItem = ref('');

// Local SWOT state for editing
const localSwot = reactive({
    strengths: [...(props.swot_analysis?.strengths || props.competitor?.strengths || [])],
    weaknesses: [...(props.swot_analysis?.weaknesses || props.competitor?.weaknesses || [])],
    opportunities: [...(props.swot_analysis?.opportunities || [])],
    threats: [...(props.swot_analysis?.threats || [])],
});

// Manual metrics
const manualMetrics = reactive({
    instagram_followers: '',
    telegram_members: '',
    facebook_followers: '',
    instagram_engagement_rate: '',
});

// Computed
const swotChanged = computed(() => {
    const original = props.swot_analysis || { strengths: [], weaknesses: [], opportunities: [], threats: [] };
    const origStrengths = original.strengths || props.competitor?.strengths || [];
    const origWeaknesses = original.weaknesses || props.competitor?.weaknesses || [];
    const origOpportunities = original.opportunities || [];
    const origThreats = original.threats || [];

    return JSON.stringify(localSwot.strengths) !== JSON.stringify(origStrengths) ||
           JSON.stringify(localSwot.weaknesses) !== JSON.stringify(origWeaknesses) ||
           JSON.stringify(localSwot.opportunities) !== JSON.stringify(origOpportunities) ||
           JSON.stringify(localSwot.threats) !== JSON.stringify(origThreats);
});

// Methods
function getThreatLevelText(level) {
    const levels = { low: 'Past', medium: 'O\'rta', high: 'Yuqori', critical: 'Kritik' };
    return levels[level] || level;
}

function getStatusText(status) {
    const statuses = { active: 'Faol', inactive: 'Nofaol', archived: 'Arxivlangan' };
    return statuses[status] || status;
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

function getThreatDotClass(level) {
    const classes = {
        low: 'bg-green-500',
        medium: 'bg-yellow-500',
        high: 'bg-orange-500',
        critical: 'bg-red-500'
    };
    return classes[level] || classes.medium;
}

function getStatusBadgeClass(status) {
    const classes = {
        active: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
        inactive: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
        archived: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
    };
    return classes[status] || classes.active;
}

function formatNumber(num) {
    if (!num) return null;
    if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
    return num.toLocaleString();
}

function formatDate(dateString) {
    if (!dateString) return '—';
    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = Math.floor((now - date) / (1000 * 60 * 60));
    if (diffInHours < 1) return 'Hozirgina';
    if (diffInHours < 24) return `${diffInHours} soat oldin`;
    if (diffInHours < 48) return 'Kecha';
    return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' });
}

function getGrowthRate(platform) {
    if (platform === 'instagram') return props.latest_metric?.follower_growth_rate;
    return null;
}

function getSwotCategoryName(category) {
    const names = {
        strengths: 'Kuchli tomon',
        weaknesses: 'Zaif tomon',
        opportunities: 'Imkoniyat',
        threats: 'Tahdid'
    };
    return names[category] || category;
}

function addSwotItem(category) {
    swotModalCategory.value = category;
    newSwotItem.value = '';
    showSwotModal.value = true;
}

function confirmAddSwotItem() {
    if (newSwotItem.value.trim()) {
        localSwot[swotModalCategory.value].push(newSwotItem.value.trim());
    }
    showSwotModal.value = false;
}

function removeSwotItem(category, index) {
    localSwot[category].splice(index, 1);
}

function saveSwot() {
    saving_swot.value = true;
    router.put(route('business.competitors.update', props.competitor.id), {
        ...props.competitor,
        strengths: localSwot.strengths,
        weaknesses: localSwot.weaknesses,
    }, {
        preserveScroll: true,
        onFinish: () => {
            saving_swot.value = false;
        },
    });
}

function generateSwot() {
    if (confirm('AI yordamida SWOT tahlil yaratilsinmi?')) {
        generating_swot.value = true;
        router.post(route('business.competitors.swot.generate', props.competitor.id), {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.swot_analysis) {
                    localSwot.strengths = [...(page.props.swot_analysis.strengths || [])];
                    localSwot.weaknesses = [...(page.props.swot_analysis.weaknesses || [])];
                    localSwot.opportunities = [...(page.props.swot_analysis.opportunities || [])];
                    localSwot.threats = [...(page.props.swot_analysis.threats || [])];
                }
            },
            onFinish: () => {
                generating_swot.value = false;
            },
        });
    }
}

function monitorNow() {
    monitoring.value = true;
    router.post(route('business.competitors.monitor', props.competitor.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            monitoring.value = false;
        },
    });
}

function saveManualMetrics() {
    const data = {};
    if (manualMetrics.instagram_followers) data.instagram_followers = parseInt(manualMetrics.instagram_followers);
    if (manualMetrics.telegram_members) data.telegram_members = parseInt(manualMetrics.telegram_members);
    if (manualMetrics.facebook_followers) data.facebook_followers = parseInt(manualMetrics.facebook_followers);
    if (manualMetrics.instagram_engagement_rate) data.instagram_engagement_rate = parseFloat(manualMetrics.instagram_engagement_rate);

    if (Object.keys(data).length === 0) {
        alert('Kamida bitta maydonni to\'ldiring');
        return;
    }

    saving_metrics.value = true;
    router.post(route('business.competitors.metrics.record', props.competitor.id), data, {
        preserveScroll: true,
        onSuccess: () => {
            manualMetrics.instagram_followers = '';
            manualMetrics.telegram_members = '';
            manualMetrics.facebook_followers = '';
            manualMetrics.instagram_engagement_rate = '';
        },
        onFinish: () => {
            saving_metrics.value = false;
        },
    });
}

function openEditModal() {
    router.get(route('business.competitors.edit', props.competitor.id));
}

function deleteCompetitor() {
    if (confirm('Haqiqatan ham bu raqobatchini o\'chirmoqchimisiz? Bu amalni ortga qaytarib bo\'lmaydi.')) {
        router.delete(route('business.competitors.destroy', props.competitor.id));
    }
}
</script>
