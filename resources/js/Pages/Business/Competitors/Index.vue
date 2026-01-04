<template>
    <BusinessLayout title="Raqobatchilar">
        <div class="p-6 space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Raqobatchilar</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Raqobatchilaringizni kuzating va tahlil qiling</p>
                </div>
                <div class="flex gap-3">
                    <Link
                        :href="route('business.competitors.dashboard')"
                        class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Dashboard
                    </Link>
                    <button
                        @click="openAddModal"
                        class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-sm font-medium text-white transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Yangi Raqobatchi
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ totalCompetitors }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Jami</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ activeCount }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Faol</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ criticalCount }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Kritik tahdid</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ highCount }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Yuqori tahdid</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Nomi yoki tavsif bo'yicha qidiring..."
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <!-- Threat Level Filter -->
                    <div>
                        <select
                            v-model="filters.threat_level"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">Barcha tahdidlar</option>
                            <option value="low">Past tahdid</option>
                            <option value="medium">O'rta tahdid</option>
                            <option value="high">Yuqori tahdid</option>
                            <option value="critical">Kritik tahdid</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select
                            v-model="filters.status"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">Barcha statuslar</option>
                            <option value="active">Faol</option>
                            <option value="inactive">Nofaol</option>
                            <option value="archived">Arxivlangan</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Competitors Table -->
            <div v-if="filteredCompetitors.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">Raqobatchilar topilmadi</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Yangi raqobatchi qo'shish uchun yuqoridagi tugmani bosing.</p>
                <button
                    @click="openAddModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-sm font-medium text-white transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Raqobatchi qo'shish
                </button>
            </div>

            <!-- Professional Table View -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Raqobatchi</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    <span class="flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4 text-pink-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                        </svg>
                                        Instagram
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    <span class="flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                                        Telegram
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    <span class="flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        Facebook
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Tahdid</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Kuzatuv</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="competitor in filteredCompetitors"
                                :key="competitor.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            >
                                <!-- Competitor Info -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                            {{ competitor.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <Link
                                                :href="route('business.competitors.show', competitor.id)"
                                                class="font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 truncate block"
                                            >
                                                {{ competitor.name }}
                                            </Link>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ competitor.industry || competitor.location || 'Ma\'lumot yo\'q' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Instagram -->
                                <td class="px-4 py-3 text-center">
                                    <div v-if="competitor.instagram_handle" class="flex flex-col items-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatNumber(getLatestMetric(competitor, 'instagram_followers')) }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ competitor.instagram_handle }}</span>
                                    </div>
                                    <span v-else class="text-gray-300 dark:text-gray-600">—</span>
                                </td>

                                <!-- Telegram -->
                                <td class="px-4 py-3 text-center">
                                    <div v-if="competitor.telegram_handle" class="flex flex-col items-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatNumber(getLatestMetric(competitor, 'telegram_members')) }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ competitor.telegram_handle }}</span>
                                    </div>
                                    <span v-else class="text-gray-300 dark:text-gray-600">—</span>
                                </td>

                                <!-- Facebook -->
                                <td class="px-4 py-3 text-center">
                                    <div v-if="competitor.facebook_page" class="flex flex-col items-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatNumber(getLatestMetric(competitor, 'facebook_followers')) }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[80px]">{{ competitor.facebook_page }}</span>
                                    </div>
                                    <span v-else class="text-gray-300 dark:text-gray-600">—</span>
                                </td>

                                <!-- Threat Level -->
                                <td class="px-4 py-3 text-center">
                                    <span
                                        :class="getThreatBadgeClass(competitor.threat_level)"
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="getThreatDotClass(competitor.threat_level)"></span>
                                        {{ getThreatLevelText(competitor.threat_level) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3 text-center">
                                    <span
                                        :class="getStatusBadgeClass(competitor.status)"
                                        class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md"
                                    >
                                        {{ getStatusText(competitor.status) }}
                                    </span>
                                </td>

                                <!-- Auto Monitor -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-col items-center">
                                        <span
                                            :class="competitor.auto_monitor ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500'"
                                            class="text-xs font-medium"
                                        >
                                            {{ competitor.auto_monitor ? 'Avtomatik' : 'O\'chirilgan' }}
                                        </span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ competitor.last_checked_at ? formatDate(competitor.last_checked_at) : 'Tekshirilmagan' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link
                                            :href="route('business.competitors.show', competitor.id)"
                                            class="p-1.5 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                                            title="Ko'rish"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="editCompetitor(competitor)"
                                            class="p-1.5 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                            title="Tahrirlash"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="monitorNow(competitor)"
                                            :disabled="competitor.monitoring"
                                            class="p-1.5 text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors disabled:opacity-50"
                                            title="Hozir tekshirish"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteCompetitor(competitor)"
                                            class="p-1.5 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                            title="O'chirish"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer with Pagination Info -->
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                    Jami {{ filteredCompetitors.length }} ta raqobatchi
                </div>
            </div>
        </div>

        <!-- Add/Edit Competitor Modal -->
        <Teleport to="body">
            <div v-if="showAddModal || showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>

                    <!-- Modal Content -->
                    <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-xl">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ showEditModal ? 'Raqobatchini Tahrirlash' : 'Yangi Raqobatchi Qo\'shish' }}
                            </h3>
                            <button
                                @click="closeModal"
                                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <form @submit.prevent="submitForm" class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Name -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nomi *</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        placeholder="Raqobatchi nomi"
                                    />
                                </div>

                                <!-- Description -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tavsif</label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                                        placeholder="Raqobatchi haqida qisqacha ma'lumot"
                                    ></textarea>
                                </div>

                                <!-- Industry (auto-filled from business) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Soha
                                        <span class="text-xs text-gray-400 ml-1">(biznes bilan bir xil)</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            :value="businessIndustry || 'Biznes sozlamalarida belgilanmagan'"
                                            type="text"
                                            disabled
                                            :class="[
                                                'w-full px-4 py-2.5 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm cursor-not-allowed pr-10',
                                                businessIndustry ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500 italic'
                                            ]"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location (Viloyat select) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Viloyat
                                        <span v-if="businessLocation" class="text-xs text-gray-400 ml-1">(biznes: {{ businessLocation }})</span>
                                    </label>
                                    <select
                                        v-model="form.location"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                        <option value="">Viloyatni tanlang</option>
                                        <option v-for="region in uzbekistanRegions" :key="region.value" :value="region.value">
                                            {{ region.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Threat Level -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tahdid Darajasi *</label>
                                    <select
                                        v-model="form.threat_level"
                                        required
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                        <option value="low">Past</option>
                                        <option value="medium">O'rta</option>
                                        <option value="high">Yuqori</option>
                                        <option value="critical">Kritik</option>
                                    </select>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                                    <select
                                        v-model="form.status"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                        <option value="active">Faol</option>
                                        <option value="inactive">Nofaol</option>
                                        <option value="archived">Arxivlangan</option>
                                    </select>
                                </div>

                                <!-- Platforms Section -->
                                <div class="md:col-span-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Platformalar</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Instagram -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                                <span class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073z"/>
                                                    </svg>
                                                    Instagram
                                                </span>
                                            </label>
                                            <input
                                                v-model="form.instagram_handle"
                                                type="text"
                                                placeholder="@username"
                                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                        </div>

                                        <!-- Telegram -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                                <span class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                                    </svg>
                                                    Telegram
                                                </span>
                                            </label>
                                            <input
                                                v-model="form.telegram_handle"
                                                type="text"
                                                placeholder="@username"
                                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                        </div>

                                        <!-- Facebook -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                                <span class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                    </svg>
                                                    Facebook
                                                </span>
                                            </label>
                                            <input
                                                v-model="form.facebook_page"
                                                type="text"
                                                placeholder="Page ID yoki URL"
                                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                        </div>

                                        <!-- TikTok -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                                <span class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-gray-900 dark:text-gray-100" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                                                    </svg>
                                                    TikTok
                                                </span>
                                            </label>
                                            <input
                                                v-model="form.tiktok_handle"
                                                type="text"
                                                placeholder="@username"
                                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Auto Monitor -->
                                <div class="md:col-span-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            v-model="form.auto_monitor"
                                            type="checkbox"
                                            class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Avtomatik kuzatishni yoqish</span>
                                    </label>
                                </div>

                                <!-- Check Frequency -->
                                <div v-if="form.auto_monitor">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tekshiruv chastotasi (soat)</label>
                                    <input
                                        v-model.number="form.check_frequency_hours"
                                        type="number"
                                        min="1"
                                        max="168"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors"
                                >
                                    Bekor qilish
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-sm font-medium text-white transition-colors disabled:opacity-50"
                                >
                                    {{ showEditModal ? 'Saqlash' : 'Qo\'shish' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </BusinessLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const props = defineProps({
    competitors: [Array, Object],
    stats: Object,
    currentBusiness: Object,
});

const showAddModal = ref(false);
const showEditModal = ref(false);
const editingCompetitor = ref(null);

const filters = ref({
    search: '',
    threat_level: '',
    status: '',
});

// O'zbekiston viloyatlari ro'yxati (computed dan oldin bo'lishi kerak)
const uzbekistanRegions = [
    { value: 'Toshkent shahri', label: 'Toshkent shahri' },
    { value: 'Toshkent viloyati', label: 'Toshkent viloyati' },
    { value: 'Andijon viloyati', label: 'Andijon viloyati' },
    { value: 'Buxoro viloyati', label: 'Buxoro viloyati' },
    { value: 'Farg\'ona viloyati', label: 'Farg\'ona viloyati' },
    { value: 'Jizzax viloyati', label: 'Jizzax viloyati' },
    { value: 'Xorazm viloyati', label: 'Xorazm viloyati' },
    { value: 'Namangan viloyati', label: 'Namangan viloyati' },
    { value: 'Navoiy viloyati', label: 'Navoiy viloyati' },
    { value: 'Qashqadaryo viloyati', label: 'Qashqadaryo viloyati' },
    { value: 'Qoraqalpog\'iston Respublikasi', label: 'Qoraqalpog\'iston Respublikasi' },
    { value: 'Samarqand viloyati', label: 'Samarqand viloyati' },
    { value: 'Sirdaryo viloyati', label: 'Sirdaryo viloyati' },
    { value: 'Surxondaryo viloyati', label: 'Surxondaryo viloyati' },
    { value: 'Butun O\'zbekiston', label: 'Butun O\'zbekiston' },
];

// Biznes ma'lumotlarini olish (industry_name, region)
const businessIndustry = computed(() => {
    const business = props.currentBusiness;
    if (!business) return '';
    // industry_name (controllerdan) yoki industry yoki category
    return business.industry_name || business.industry || business.category || '';
});

const businessLocation = computed(() => {
    const business = props.currentBusiness;
    if (!business) return '';
    // region yoki city
    const region = business.region || business.city || '';
    // Agar region qiymati viloyatlar ro'yxatida bo'lsa, qaytarish
    if (region) {
        // Exact match tekshirish
        const found = uzbekistanRegions.find(r => r.value === region || r.label === region);
        if (found) return found.value;
        // Partial match tekshirish (masalan "Toshkent" -> "Toshkent shahri")
        const partial = uzbekistanRegions.find(r =>
            r.value.toLowerCase().includes(region.toLowerCase()) ||
            region.toLowerCase().includes(r.value.toLowerCase().replace(' viloyati', '').replace(' shahri', ''))
        );
        if (partial) return partial.value;
    }
    return region;
});

const form = useForm({
    name: '',
    description: '',
    industry: '',
    location: '',
    threat_level: 'medium',
    status: 'active',
    instagram_handle: '',
    telegram_handle: '',
    facebook_page: '',
    tiktok_handle: '',
    auto_monitor: true,
    check_frequency_hours: 24,
});

// Yangi raqobatchi qo'shish modalini ochish
function openAddModal() {
    form.reset();
    form.industry = businessIndustry.value; // Biznes sohasini avtomatik qo'yish
    form.location = businessLocation.value; // Biznes viloyatini avtomatik qo'yish
    showAddModal.value = true;
}

// Computed stats
const competitorsList = computed(() => {
    return Array.isArray(props.competitors)
        ? props.competitors
        : (props.competitors?.data || []);
});

const totalCompetitors = computed(() => competitorsList.value.length);
const activeCount = computed(() => competitorsList.value.filter(c => c.status === 'active').length);
const criticalCount = computed(() => competitorsList.value.filter(c => c.threat_level === 'critical').length);
const highCount = computed(() => competitorsList.value.filter(c => c.threat_level === 'high').length);

const filteredCompetitors = computed(() => {
    let result = competitorsList.value;

    if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        result = result.filter(c =>
            c.name.toLowerCase().includes(search) ||
            (c.description && c.description.toLowerCase().includes(search))
        );
    }

    if (filters.value.threat_level) {
        result = result.filter(c => c.threat_level === filters.value.threat_level);
    }

    if (filters.value.status) {
        result = result.filter(c => c.status === filters.value.status);
    }

    return result;
});

function editCompetitor(competitor) {
    editingCompetitor.value = competitor;
    form.name = competitor.name;
    form.description = competitor.description || '';
    form.industry = competitor.industry || '';
    form.location = competitor.location || '';
    form.threat_level = competitor.threat_level;
    form.status = competitor.status;
    form.instagram_handle = competitor.instagram_handle || '';
    form.telegram_handle = competitor.telegram_handle || '';
    form.facebook_page = competitor.facebook_page || '';
    form.tiktok_handle = competitor.tiktok_handle || '';
    form.auto_monitor = competitor.auto_monitor;
    form.check_frequency_hours = competitor.check_frequency_hours;
    showEditModal.value = true;
}

function closeModal() {
    showAddModal.value = false;
    showEditModal.value = false;
    editingCompetitor.value = null;
    form.reset();
}

function submitForm() {
    if (showEditModal.value) {
        form.put(route('business.competitors.update', editingCompetitor.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('business.competitors.store'), {
            onSuccess: () => closeModal(),
        });
    }
}

function monitorNow(competitor) {
    if (confirm('Bu raqobatchini hoziroq tekshirishni xohlaysizmi?')) {
        router.post(route('business.competitors.monitor', competitor.id), {}, {
            onSuccess: () => {
                alert('Tekshiruv boshlandi. Natijalar biroz vaqt ichida tayyorlanadi.');
            },
        });
    }
}

function deleteCompetitor(competitor) {
    if (confirm(`${competitor.name} raqobatchini o'chirishni tasdiqlaysizmi?`)) {
        router.delete(route('business.competitors.destroy', competitor.id));
    }
}

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

function getStatusText(status) {
    const statuses = {
        active: 'Faol',
        inactive: 'Nofaol',
        archived: 'Arxivlangan'
    };
    return statuses[status] || status;
}

function getStatusBadgeClass(status) {
    const classes = {
        active: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        inactive: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
        archived: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
    };
    return classes[status] || classes.inactive;
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

// Format large numbers (e.g., 15000 → "15K", 1500000 → "1.5M")
function formatNumber(num) {
    if (num === null || num === undefined) return '—';
    if (num === 0) return '0';

    if (num >= 1000000) {
        return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
    }
    return num.toString();
}

// Get the latest metric value for a competitor
function getLatestMetric(competitor, field) {
    // Metrics are eagerly loaded from controller
    const latestMetric = competitor.metrics?.[0];
    if (!latestMetric) return null;
    return latestMetric[field] ?? null;
}

// Get dot color class for threat level indicator
function getThreatDotClass(level) {
    const classes = {
        low: 'bg-green-500',
        medium: 'bg-yellow-500',
        high: 'bg-orange-500',
        critical: 'bg-red-500'
    };
    return classes[level] || classes.medium;
}
</script>
