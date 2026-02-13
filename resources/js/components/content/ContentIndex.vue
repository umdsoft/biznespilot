<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('content.index.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ t('content.index.subtitle') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    :href="route('business.marketing.content-ai.smart-plan.index')"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white text-sm font-medium rounded-xl shadow-lg shadow-cyan-500/25 hover:shadow-cyan-500/40 transition-all duration-200"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Smart Reja
                </a>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-medium rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all duration-200"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('content.index.add_content') }}
                </button>
            </div>
        </div>

        <!-- Overdue Alert Banner -->
        <div
            v-if="overdueCount > 0"
            class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl"
        >
            <div class="flex-shrink-0 p-2 bg-red-100 dark:bg-red-900/40 rounded-lg">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-semibold text-red-800 dark:text-red-300">{{ t('content.index.overdue_alert') }}</h4>
                <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">
                    {{ t('content.index.overdue_description', { count: overdueCount }) }}
                </p>
            </div>
            <button
                @click="activeStatus = 'overdue'"
                class="flex-shrink-0 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors"
            >
                {{ t('common.view') }}
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ t('content.stats.total') }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ posts.length }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ t('content.status.draft') }}</p>
                <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ draftCount }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-amber-600 dark:text-amber-400">{{ t('content.status.scheduled') }}</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ scheduledCount }}</p>
            </div>
            <div
                class="rounded-xl p-4 border cursor-pointer transition-all"
                :class="overdueCount > 0
                    ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 hover:border-red-400'
                    : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700'"
                @click="activeStatus = 'overdue'"
            >
                <div class="flex items-center gap-1.5">
                    <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ t('content.status.overdue') }}</p>
                    <svg v-if="overdueCount > 0" class="w-3.5 h-3.5 text-red-500 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ overdueCount }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400">{{ t('content.status.published') }}</p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ publishedCount }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-blue-600 dark:text-blue-400">{{ t('content.type.educational') }}</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ getContentTypeCount('educational') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-pink-600 dark:text-pink-400">{{ t('content.type.entertaining') }}</p>
                <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ getContentTypeCount('entertaining') }}</p>
            </div>
        </div>

        <!-- Analytics Summary (published posts with links) -->
        <div v-if="analyticsStats.totalViews > 0 || analyticsStats.linkedPosts > 0" class="bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 dark:from-indigo-900/20 dark:via-purple-900/20 dark:to-pink-900/20 rounded-xl p-4 border border-indigo-100 dark:border-indigo-800/30">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Kanal analitikasi
                </h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ analyticsStats.linkedPosts }} ta post ulangan</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                    <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">Jami ko'rishlar</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatNumber(analyticsStats.totalViews) }}</p>
                </div>
                <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                    <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">Jami layklar</p>
                    <p class="text-lg font-bold text-pink-600 dark:text-pink-400">{{ formatNumber(analyticsStats.totalLikes) }}</p>
                </div>
                <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                    <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">O'rtacha ER</p>
                    <p class="text-lg font-bold" :class="analyticsStats.avgER >= 5 ? 'text-emerald-600 dark:text-emerald-400' : analyticsStats.avgER >= 3 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300'">{{ analyticsStats.avgER.toFixed(1) }}%</p>
                </div>
                <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                    <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase">Jami izohlar</p>
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(analyticsStats.totalComments) }}</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3 bg-white dark:bg-gray-800 rounded-xl p-3 border border-gray-100 dark:border-gray-700">
            <!-- Status Filter -->
            <div class="flex items-center gap-1">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    @click="activeStatus = tab.value"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all flex items-center gap-1.5"
                    :class="[
                        activeStatus === tab.value
                            ? (tab.value === 'overdue' ? 'bg-red-600 text-white' : 'bg-indigo-600 text-white')
                            : (tab.value === 'overdue' && overdueCount > 0
                                ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700')
                    ]"
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.value === 'overdue' && overdueCount > 0"
                        class="px-1.5 py-0.5 text-[10px] font-bold rounded-full"
                        :class="activeStatus === 'overdue' ? 'bg-white/20 text-white' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400'"
                    >
                        {{ overdueCount }}
                    </span>
                </button>
            </div>

            <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>

            <!-- Platform Filter -->
            <div class="flex items-center gap-1">
                <button
                    v-for="platform in platformFilters"
                    :key="platform.value"
                    @click="activePlatform = platform.value"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all flex items-center"
                    :class="activePlatform === platform.value
                        ? platform.activeClass
                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                >
                    <component v-if="platform.icon" :is="platform.icon" class="w-3.5 h-3.5 mr-1" />
                    {{ platform.label }}
                </button>
            </div>

            <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>

            <!-- Content Type Filter -->
            <div class="flex items-center gap-1">
                <button
                    v-for="type in contentTypeFilters"
                    :key="type.value"
                    @click="activeContentType = type.value"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all"
                    :class="activeContentType === type.value
                        ? type.activeClass
                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                >
                    {{ type.label }}
                </button>
            </div>

            <div class="flex-1"></div>

            <!-- View Toggle -->
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                <button
                    @click="viewMode = 'table'"
                    class="p-2 rounded-md transition-all"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    :title="t('content.view.table')"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </button>
                <button
                    @click="viewMode = 'calendar'"
                    class="p-2 rounded-md transition-all"
                    :class="viewMode === 'calendar' ? 'bg-white dark:bg-gray-600 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    :title="t('content.view.calendar')"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Calendar View -->
        <div v-if="viewMode === 'calendar'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <!-- Calendar Header -->
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ calendarMonthName }} {{ calendarYear }}
                    </h3>
                    <div class="flex items-center gap-1">
                        <span
                            v-if="getMonthScheduledCount > 0"
                            class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400"
                        >
                            {{ getMonthScheduledCount }} {{ t('content.calendar.scheduled_count') }}
                        </span>
                        <span
                            v-if="getMonthPublishedCount > 0"
                            class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400"
                        >
                            {{ getMonthPublishedCount }} {{ t('content.calendar.published_count') }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="prevMonth"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        @click="goToToday"
                        class="px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        {{ t('content.calendar.today') }}
                    </button>
                    <button
                        @click="nextMonth"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="p-4">
                <!-- Weekday Headers -->
                <div class="grid grid-cols-7 mb-2">
                    <div
                        v-for="day in weekDays"
                        :key="day"
                        class="text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase py-2"
                    >
                        {{ day }}
                    </div>
                </div>

                <!-- Calendar Days -->
                <div class="grid grid-cols-7 gap-1">
                    <div
                        v-for="(day, index) in calendarDays"
                        :key="index"
                        @click="selectDay(day)"
                        class="min-h-[100px] border rounded-lg p-2 transition-all cursor-pointer"
                        :class="{
                            'bg-gray-50 dark:bg-gray-900/30 border-gray-100 dark:border-gray-700': !day.isCurrentMonth,
                            'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800': day.isToday && selectedDay?.fullDate !== day.fullDate,
                            'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-gray-100 dark:border-gray-700': day.isCurrentMonth && !day.isToday && selectedDay?.fullDate !== day.fullDate,
                            'ring-2 ring-indigo-500 border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30': selectedDay?.fullDate === day.fullDate
                        }"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span
                                class="text-sm font-medium"
                                :class="{
                                    'text-gray-400 dark:text-gray-600': !day.isCurrentMonth,
                                    'text-green-600 dark:text-green-400 font-bold': day.isToday,
                                    'text-gray-900 dark:text-white': day.isCurrentMonth && !day.isToday,
                                    'text-red-500 dark:text-red-400': day.isWeekend && day.isCurrentMonth && !day.isToday
                                }"
                            >
                                {{ day.date }}
                            </span>
                            <span
                                v-if="getPostsForDay(day.fullDate).length > 0"
                                class="w-5 h-5 flex items-center justify-center text-xs font-bold text-white rounded-full"
                                :class="dayHasOverduePosts(day.fullDate) ? 'bg-red-500 animate-pulse' : 'bg-indigo-500'"
                            >
                                {{ getPostsForDay(day.fullDate).length }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <div
                                v-for="post in getPostsForDay(day.fullDate).slice(0, 3)"
                                :key="post.id"
                                class="w-2 h-2 rounded-full"
                                :class="getStatusDotClass(post.status, post)"
                            ></div>
                            <span v-if="getPostsForDay(day.fullDate).length > 3" class="text-xs text-gray-400">
                                +{{ getPostsForDay(day.fullDate).length - 3 }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Day Details -->
            <div v-if="selectedDay && selectedDayPosts.length > 0" class="p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ formatSelectedDate }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('content.calendar.content_count', { count: selectedDayPosts.length }) }}</p>
                        </div>
                    </div>
                    <button @click="selectedDay = null" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div v-for="post in selectedDayPosts" :key="post.id" class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ post.title }}</h4>
                                <!-- Instagram link indicator -->
                                <span v-if="post.has_instagram_link || post.instagram_link" class="inline-flex items-center">
                                    <svg class="w-4 h-4 text-pink-500" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/>
                                    </svg>
                                    <span v-if="isTopPerformer(post)" class="ml-1">🔥</span>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ post.content }}</p>
                            <!-- Stats for published posts -->
                            <div v-if="post.status === 'published' && (hasStats(post) || hasLinks(post))" class="mt-2 space-y-0.5">
                                <template v-for="(link, platform) in getPostLinks(post)" :key="'cal-stat-' + platform">
                                    <div v-if="link.views || link.likes" class="flex items-center gap-2 text-xs">
                                        <component :is="getPlatformIcon(platform)" class="w-3 h-3" :class="getPlatformIconClass(platform)" />
                                        <span v-if="link.views" class="text-gray-500">{{ formatNumber(link.views) }}</span>
                                        <span v-if="link.likes" class="text-pink-500">{{ formatNumber(link.likes) }}</span>
                                        <span v-if="link.engagement_rate > 0" :class="link.engagement_rate >= 5 ? 'text-emerald-600 font-semibold' : 'text-gray-400'">{{ parseFloat(link.engagement_rate).toFixed(1) }}%</span>
                                    </div>
                                </template>
                                <div v-if="!hasLinks(post) && hasStats(post)" class="flex items-center gap-3 text-xs">
                                    <span v-if="getPostViews(post)" class="text-gray-500">{{ formatNumber(getPostViews(post)) }}</span>
                                    <span v-if="getPostLikes(post)" class="text-pink-500">{{ formatNumber(getPostLikes(post)) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 ml-2">
                            <button @click="viewPost(post)" class="p-2 text-gray-500 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                            <button v-if="post.status !== 'published'" @click="openEditModal(post)" class="p-2 text-gray-500 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button v-if="post.status !== 'published'" @click="deletePost(post.id)" class="p-2 text-gray-500 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <span :class="getStatusBadgeClass(post.status, post)" class="px-2 py-0.5 text-xs font-medium rounded">{{ getStatusLabel(post.status, post) }}</span>
                        <template v-for="(platform, idx) in getPlatforms(post.platform)" :key="idx">
                            <span class="px-2 py-0.5 text-xs font-medium rounded" :class="getPlatformBgClass(platform)">{{ platform }}</span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Selected Day Empty State -->
            <div v-if="selectedDay && selectedDayPosts.length === 0" class="p-4">
                <div class="text-center py-8 bg-gray-50 dark:bg-gray-900/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ t('content.calendar.no_content_for_day') }}</p>
                    <button @click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ t('content.index.add_content') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Content Table -->
        <div v-if="viewMode === 'table'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ t('content.table.content') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ t('content.table.platform') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ t('content.table.type') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ t('content.table.format') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ t('content.table.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ t('content.table.datetime') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ t('content.table.statistics') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ t('content.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr
                            v-for="post in filteredPosts"
                            :key="post.id"
                            @click="viewPost(post)"
                            class="transition-colors cursor-pointer"
                            :class="isPostOverdue(post) ? 'bg-red-50/50 dark:bg-red-900/10 hover:bg-red-50 dark:hover:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                        >
                            <td class="px-4 py-3">
                                <div class="max-w-xs">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ post.title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 mt-0.5">{{ post.content }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center flex-wrap gap-1">
                                    <template v-for="(platform, index) in getPlatforms(post.platform)" :key="index">
                                        <div class="flex items-center px-2 py-1 rounded-lg" :class="getPlatformBgClass(platform)">
                                            <component :is="getPlatformIcon(platform)" class="w-3.5 h-3.5" :class="getPlatformIconClass(platform)" />
                                            <span class="ml-1.5 text-xs font-medium" :class="getPlatformIconClass(platform)">{{ platform }}</span>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md" :class="getContentTypeBadgeClass(post.content_type)">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="getContentTypeDotClass(post.content_type)"></span>
                                    {{ getContentTypeLabel(post.content_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    {{ getFormatLabel(post.format) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg" :class="getStatusBadgeClass(post.status, post)">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="getStatusDotClass(post.status, post)"></span>
                                    {{ getStatusLabel(post.status, post) }}
                                    <svg v-if="isPostOverdue(post)" class="w-3.5 h-3.5 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="post.scheduled_at || post.published_at" class="text-sm">
                                    <p class="text-gray-900 dark:text-white font-medium">{{ formatDate(post.scheduled_at || post.published_at) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatTime(post.scheduled_at || post.published_at) }}</p>
                                </div>
                                <span v-else class="text-xs text-gray-400 dark:text-gray-500">-</span>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="post.status === 'published' && (hasStats(post) || hasLinks(post))" class="space-y-1">
                                    <!-- Per-platform stats -->
                                    <template v-for="(link, platform) in getPostLinks(post)" :key="'stat-' + platform">
                                        <div v-if="link.views || link.likes || link.comments || link.forwards" class="flex items-center gap-1.5 text-[11px]">
                                            <component :is="getPlatformIcon(platform)" class="w-3 h-3 flex-shrink-0" :class="getPlatformIconClass(platform)" />
                                            <span v-if="link.views" class="text-gray-500 dark:text-gray-400">{{ formatNumber(link.views) }}</span>
                                            <span v-if="link.likes" class="text-pink-500">{{ formatNumber(link.likes) }}</span>
                                            <span v-if="link.comments" class="text-blue-500">{{ formatNumber(link.comments) }}</span>
                                            <span v-if="link.forwards" class="text-sky-500">{{ formatNumber(link.forwards) }}fwd</span>
                                            <span v-if="link.engagement_rate > 0" :class="link.engagement_rate >= 5 ? 'text-emerald-600 font-semibold' : link.engagement_rate >= 3 ? 'text-blue-600' : 'text-gray-400'">
                                                {{ parseFloat(link.engagement_rate).toFixed(1) }}%
                                            </span>
                                        </div>
                                        <div v-else-if="link.external_url" class="flex items-center gap-1.5 text-[11px] text-gray-400">
                                            <component :is="getPlatformIcon(platform)" class="w-3 h-3 flex-shrink-0" :class="getPlatformIconClass(platform)" />
                                            <span>Kutilmoqda...</span>
                                        </div>
                                    </template>
                                    <!-- Fallback to direct stats if no links -->
                                    <div v-if="!hasLinks(post) && hasStats(post)" class="flex items-center gap-2 text-xs">
                                        <span v-if="getPostViews(post)" class="text-gray-500 dark:text-gray-400">{{ formatNumber(getPostViews(post)) }}</span>
                                        <span v-if="getPostLikes(post)" class="text-pink-500">{{ formatNumber(getPostLikes(post)) }}</span>
                                        <span v-if="getPostComments(post)" class="text-blue-500">{{ formatNumber(getPostComments(post)) }}</span>
                                    </div>
                                    <!-- Top performer + sync -->
                                    <div class="flex items-center gap-1">
                                        <span v-if="isTopPerformer(post)" class="text-orange-500 text-xs" title="Top Performer">🔥</span>
                                        <button
                                            v-if="hasLinks(post)"
                                            @click.stop="syncPost(post.id)"
                                            :disabled="syncingPostId === post.id"
                                            class="p-0.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                            title="Sinxronlash"
                                        >
                                            <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': syncingPostId === post.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-gray-400 dark:text-gray-500">-</span>
                            </td>
                            <td class="px-4 py-3 text-right" @click.stop>
                                <div class="flex items-center justify-end space-x-1">
                                    <button @click="openEditModal(post)" class="p-2 text-gray-500 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors" :title="t('common.edit')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button v-if="post.status !== 'published'" @click="deletePost(post.id)" class="p-2 text-gray-500 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" :title="t('common.delete')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-if="filteredPosts.length === 0" class="flex flex-col items-center justify-center py-16 px-4">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ t('content.empty.title') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 text-center">{{ t('content.empty.description') }}</p>
                <button @click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('content.index.add_content') }}
                </button>
            </div>
        </div>

        <!-- Content Tips -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-6 border border-indigo-100 dark:border-indigo-800/30">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-bold text-lg">80/20</span>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">{{ t('content.tips.rule_title') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ t('content.tips.rule_description') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-2xl p-6 border border-amber-100 dark:border-amber-800/30">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-bold text-xl">o</span>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">{{ t('content.tips.mix_title') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ t('content.tips.mix_description') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeCreateModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl mx-auto overflow-hidden transform transition-all">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl" :class="editingPost ? 'bg-amber-100 dark:bg-amber-900/40' : 'bg-indigo-100 dark:bg-indigo-900/40'">
                                <svg v-if="editingPost" class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <svg v-else class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ editingPost ? t('content.modal.edit_title') : t('content.modal.create_title') }}</h3>
                        </div>
                        <button @click="closeCreateModal" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitPost" class="flex flex-col max-h-[80vh]">
                      <div class="p-6 space-y-5 overflow-y-auto flex-1">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.title') }}</label>
                            <input v-model="postForm.title" type="text" required class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" :placeholder="t('content.form.title_placeholder')" />
                        </div>

                        <!-- Muammo tanlash -->
                        <div v-if="painPoints.length > 0">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Mijoz muammosi</label>
                            <select v-model="selectedPainPointId" @change="handlePainPointSelect" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                <option value="">Muammosiz yaratish</option>
                                <optgroup v-for="group in painPointGroups" :key="group.category" :label="group.label">
                                    <option v-for="p in group.items" :key="p.id" :value="p.id">{{ p.text }}</option>
                                </optgroup>
                            </select>
                            <!-- Tanlangan muammo haqida qo'shimcha ma'lumot -->
                            <div v-if="selectedPainPoint" class="mt-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <p class="text-xs font-medium text-amber-700 dark:text-amber-300 mb-1">{{ selectedPainPoint.category_label }}</p>
                                <p class="text-sm text-amber-900 dark:text-amber-100">{{ selectedPainPoint.text }}</p>
                                <div v-if="selectedPainPoint.hooks?.length" class="mt-2 space-y-1">
                                    <p class="text-xs text-amber-600 dark:text-amber-400">Boshlash g'oyalari:</p>
                                    <button v-for="(hook, i) in selectedPainPoint.hooks.slice(0, 2)" :key="i" type="button" @click="useHook(hook)" class="block w-full text-left text-xs px-2 py-1 bg-white dark:bg-gray-700 rounded border border-amber-200 dark:border-amber-700 text-gray-700 dark:text-gray-300 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                                        {{ hook }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- AI Generation Panel -->
                        <div v-if="!editingPost" class="rounded-xl border-2 transition-all" :class="showAiPanel ? 'border-purple-300 dark:border-purple-700 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20' : 'border-dashed border-gray-300 dark:border-gray-600'">
                            <button v-if="!showAiPanel" type="button" @click="showAiPanel = true" :disabled="aiRemaining === 0" class="w-full flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium rounded-xl transition-all" :class="aiRemaining === 0 ? 'text-gray-400 dark:text-gray-500 cursor-not-allowed' : 'text-purple-700 dark:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-900/20'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423L16.5 15.75l.394 1.183a2.25 2.25 0 001.423 1.423L19.5 18.75l-1.183.394a2.25 2.25 0 00-1.423 1.423z" /></svg>
                                {{ aiRemaining === 0 ? 'AI limiti tugadi' : 'AI bilan yaratish' }}
                                <span v-if="aiRemaining !== null && aiRemaining > 0" class="text-xs text-purple-500 dark:text-purple-400">({{ aiRemaining }} ta qoldi)</span>
                            </button>
                            <div v-else class="p-4 space-y-3">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="text-sm font-semibold text-purple-800 dark:text-purple-200 flex items-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                                        AI Kontent Yaratish
                                        <span v-if="aiRemaining !== null" class="text-xs font-normal text-purple-500 dark:text-purple-400">({{ aiRemaining }} ta qoldi)</span>
                                    </h4>
                                    <button type="button" @click="showAiPanel = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <!-- Offer select -->
                                <div v-if="activeOffers.length > 0">
                                    <select v-model="aiForm.offer_id" @change="handleAiOfferSelect" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-purple-200 dark:border-purple-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <option value="">Taklifsiz yaratish</option>
                                        <option v-for="offer in activeOffers" :key="offer.id" :value="offer.id">{{ offer.name }}</option>
                                    </select>
                                </div>
                                <!-- Topic -->
                                <input v-model="aiForm.topic" type="text" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-purple-200 dark:border-purple-700 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Mavzu yoki g'oya kiriting..." />
                                <!-- Purpose + Channel -->
                                <div class="grid grid-cols-2 gap-2">
                                    <select v-model="aiForm.purpose" class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-purple-200 dark:border-purple-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <option value="engage">Jalb qilish</option>
                                        <option value="educate">O'rgatish</option>
                                        <option value="sell">Sotish</option>
                                        <option value="inspire">Ilhomlantirish</option>
                                        <option value="announce">E'lon qilish</option>
                                        <option value="entertain">Ko'ngil ochish</option>
                                    </select>
                                    <select v-model="aiForm.target_channel" class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-purple-200 dark:border-purple-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <option value="">Umumiy</option>
                                        <option value="instagram">Instagram</option>
                                        <option value="telegram">Telegram</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="tiktok">TikTok</option>
                                    </select>
                                </div>
                                <!-- Error -->
                                <p v-if="aiError" class="text-xs text-red-600 dark:text-red-400">{{ aiError }}</p>
                                <!-- Generate button -->
                                <button type="button" @click="generateAiContent" :disabled="!aiForm.topic || aiGenerating" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 rounded-lg shadow-md shadow-purple-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg v-if="aiGenerating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                                    {{ aiGenerating ? 'Yaratilmoqda...' : 'AI bilan yaratish' }}
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.content_text') }}</label>
                            <textarea v-model="postForm.content" rows="4" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none" :placeholder="t('content.form.content_placeholder')"></textarea>
                        </div>

                        <!-- Platforms -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.platforms') }}</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ t('content.form.platforms_hint') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="platform in availablePlatforms"
                                    :key="platform.value"
                                    type="button"
                                    @click="togglePlatform(platform.value)"
                                    class="flex items-center gap-2 px-3 py-2 rounded-xl border-2 transition-all"
                                    :class="postForm.platforms.includes(platform.value)
                                        ? platform.selectedClass + ' text-white'
                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
                                >
                                    <component :is="platform.icon" class="w-4 h-4" :class="postForm.platforms.includes(platform.value) ? 'text-white' : platform.iconClass" />
                                    <span class="text-sm font-medium" :class="postForm.platforms.includes(platform.value) ? 'text-white' : 'text-gray-700 dark:text-gray-300'">{{ platform.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Content Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.content_type') }}</label>
                            <select v-model="postForm.content_type" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                <option value="">{{ t('common.select') }}</option>
                                <option value="educational">{{ t('content.type.educational_full') }}</option>
                                <option value="entertaining">{{ t('content.type.entertaining_full') }}</option>
                                <option value="inspirational">{{ t('content.type.inspirational_full') }}</option>
                                <option value="promotional">{{ t('content.type.promotional_full') }}</option>
                                <option value="behind_scenes">{{ t('content.type.behind_scenes_full') }}</option>
                            </select>
                        </div>

                        <!-- Format and Status -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.format') }}</label>
                                <select v-model="postForm.format" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.status') }}</label>
                                <select v-model="postForm.status" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                    <option value="draft">{{ t('content.status.draft') }}</option>
                                    <option value="scheduled">{{ t('content.status.scheduled') }}</option>
                                    <option value="published">{{ t('content.status.published') }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Scheduled Date & Time -->
                        <div v-if="postForm.status === 'scheduled'" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.date') }}</label>
                                <input v-model="postForm.scheduled_date" type="date" :min="minDate" required class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.time') }}</label>
                                <input v-model="postForm.scheduled_time" type="time" required class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" />
                            </div>
                        </div>

                        <!-- Hashtags -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('content.form.hashtags') }}</label>
                            <input v-model="hashtagInput" type="text" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" :placeholder="t('content.form.hashtags_placeholder')" />
                        </div>

                        <!-- Platform Links (for published posts) -->
                        <div v-if="postForm.status === 'published' && postForm.platforms.length > 0">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Platforma linklari</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Post joylashtirilgan URL manzillarni kiriting (statistikani sinxronlash uchun)</p>
                            <div class="space-y-2">
                                <div v-for="platform in postForm.platforms" :key="'link-' + platform" class="flex items-center gap-2">
                                    <div class="flex items-center gap-1.5 min-w-[120px] px-2.5 py-2 rounded-lg" :class="getPlatformBgClass(platform)">
                                        <component :is="getPlatformIcon(platform)" class="w-3.5 h-3.5" :class="getPlatformIconClass(platform)" />
                                        <span class="text-xs font-medium" :class="getPlatformIconClass(platform)">{{ platform }}</span>
                                    </div>
                                    <input
                                        v-model="postForm.platform_links[platform]"
                                        type="url"
                                        class="flex-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                        :placeholder="platform === 'Instagram' ? 'https://instagram.com/p/...' : platform === 'Telegram' ? 'https://t.me/channel/123' : 'https://...'"
                                    />
                                </div>
                            </div>
                        </div>

                      </div>
                      <!-- Actions (sticky at bottom) -->
                      <div class="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-shrink-0">
                            <button type="button" @click="closeCreateModal" class="px-4 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-medium transition-colors">
                                {{ t('common.cancel') }}
                            </button>
                            <button type="submit" :disabled="isSubmitting" class="px-5 py-2.5 text-white font-medium rounded-xl shadow-lg transition-all disabled:opacity-50" :class="editingPost ? 'bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 shadow-amber-500/25 hover:shadow-amber-500/40' : 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 shadow-indigo-500/25 hover:shadow-indigo-500/40'">
                                <span v-if="isSubmitting">{{ t('common.saving') }}...</span>
                                <span v-else>{{ editingPost ? t('common.update') : t('common.save') }}</span>
                            </button>
                      </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Detail Slide-Over Panel -->
    <Teleport to="body">
        <div v-if="showViewModal && viewingPost" class="fixed inset-0 z-50">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeViewModal"></div>
            <div class="fixed inset-y-0 right-0 w-full max-w-xl bg-white dark:bg-gray-800 shadow-2xl overflow-y-auto">
                <!-- Header -->
                <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0 pr-4">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white leading-tight">{{ viewingPost.title }}</h2>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span :class="getStatusBadgeClass(viewingPost.status, viewingPost)" class="px-2.5 py-1 text-xs font-medium rounded-lg">{{ getStatusLabel(viewingPost.status, viewingPost) }}</span>
                                <span :class="getContentTypeBadgeClass(viewingPost.content_type)" class="px-2.5 py-1 text-xs font-medium rounded-lg">{{ getContentTypeLabel(viewingPost.content_type) }}</span>
                                <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ getFormatLabel(viewingPost.format) }}</span>
                                <template v-for="(p, idx) in getPlatforms(viewingPost.platform)" :key="idx">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-lg" :class="getPlatformBgClass(p)">{{ p }}</span>
                                </template>
                            </div>
                        </div>
                        <button @click="closeViewModal" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-6">
                    <!-- Schedule Info -->
                    <div v-if="viewingPost.scheduled_at || viewingPost.published_at" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p v-if="viewingPost.scheduled_at" class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(viewingPost.scheduled_at) }}, {{ formatTime(viewingPost.scheduled_at) }}</p>
                            <p v-if="viewingPost.published_at" class="text-xs text-emerald-600 dark:text-emerald-400">Nashr: {{ viewingPost.published_at }}</p>
                            <p v-else class="text-xs text-gray-500 dark:text-gray-400">Rejalashtirilgan vaqt</p>
                        </div>
                    </div>

                    <!-- Content -->
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Kontent</h4>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">{{ viewingPost.content }}</p>
                        </div>
                    </div>

                    <!-- Platform Analytics -->
                    <div v-if="viewingPost.status === 'published' && viewingPost.links && Object.keys(viewingPost.links).length > 0" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kanal statistikasi</h4>
                            <button
                                @click="syncPost(viewingPost.id)"
                                :disabled="syncingPostId === viewingPost.id"
                                class="flex items-center gap-1 px-2 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                            >
                                <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': syncingPostId === viewingPost.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ syncingPostId === viewingPost.id ? 'Sinxronlanmoqda...' : 'Sinxronlash' }}
                            </button>
                        </div>
                        <div v-for="(link, platform) in viewingPost.links" :key="'detail-' + platform" class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <component :is="getPlatformIcon(platform)" class="w-4 h-4" :class="getPlatformIconClass(platform)" />
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ platform }}</span>
                                </div>
                                <div v-if="link.synced_at" class="text-[10px] text-gray-400 dark:text-gray-500">{{ link.synced_at }}</div>
                                <div v-else class="text-[10px] text-amber-500">Sinxronlanmagan</div>
                            </div>
                            <a v-if="link.external_url" :href="link.external_url" target="_blank" class="text-xs text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-300 truncate block mb-2" @click.stop>{{ link.external_url }}</a>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="text-center">
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatNumber(link.views || 0) }}</p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Ko'rishlar</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-pink-600 dark:text-pink-400">{{ formatNumber(link.likes || 0) }}</p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Layklar</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(link.comments || 0) }}</p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Izohlar</p>
                                </div>
                                <div v-if="link.shares" class="text-center">
                                    <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ formatNumber(link.shares) }}</p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Ulashishlar</p>
                                </div>
                                <div v-if="link.saves" class="text-center">
                                    <p class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ formatNumber(link.saves) }}</p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Saqlanganlar</p>
                                </div>
                                <div v-if="link.forwards" class="text-center">
                                    <p class="text-lg font-bold text-sky-600 dark:text-sky-400">{{ formatNumber(link.forwards) }}</p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Forward</p>
                                </div>
                            </div>
                            <div v-if="link.engagement_rate > 0" class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Engagement Rate</span>
                                <span class="text-sm font-bold" :class="link.engagement_rate >= 5 ? 'text-emerald-600 dark:text-emerald-400' : link.engagement_rate >= 3 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300'">{{ parseFloat(link.engagement_rate).toFixed(2) }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Smart Suggestions Section (Hybrid Algorithm) -->
                    <div v-if="viewingPost.ai_suggestions" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 rounded-lg" :class="viewingPost.ai_suggestions.source === 'algorithm' ? 'bg-gradient-to-br from-cyan-500 to-blue-600' : viewingPost.ai_suggestions.source === 'niche_learning' ? 'bg-gradient-to-br from-emerald-500 to-teal-600' : viewingPost.ai_suggestions.source === 'pain_point' ? 'bg-gradient-to-br from-orange-500 to-red-600' : 'bg-gradient-to-br from-purple-500 to-indigo-600'">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Smart Tavsiyalar</h4>
                                        <span v-if="viewingPost.ai_suggestions.is_ai_generated" class="px-1.5 py-0.5 text-[10px] font-bold bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 rounded-full flex items-center gap-0.5">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                            AI
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500">{{ viewingPost.ai_suggestions.source_label || 'Ichki algoritm' }}</p>
                                </div>
                            </div>
                            <!-- Confidence Badge -->
                            <div v-if="viewingPost.ai_suggestions.confidence" class="flex items-center gap-1.5">
                                <div class="w-16 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full" :class="viewingPost.ai_suggestions.confidence >= 80 ? 'bg-emerald-500' : viewingPost.ai_suggestions.confidence >= 60 ? 'bg-blue-500' : 'bg-amber-500'" :style="{ width: viewingPost.ai_suggestions.confidence + '%' }"></div>
                                </div>
                                <span class="text-[10px] font-medium" :class="viewingPost.ai_suggestions.confidence >= 80 ? 'text-emerald-600 dark:text-emerald-400' : viewingPost.ai_suggestions.confidence >= 60 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400'">{{ viewingPost.ai_suggestions.confidence }}%</span>
                            </div>
                        </div>

                        <!-- Source Details -->
                        <div v-if="viewingPost.ai_suggestions.source_details" class="px-3 py-2 rounded-lg text-xs" :class="viewingPost.ai_suggestions.source === 'algorithm' ? 'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800/40' : viewingPost.ai_suggestions.source === 'niche_learning' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40' : viewingPost.ai_suggestions.source === 'pain_point' ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 border border-orange-200 dark:border-orange-800/40' : 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/40'">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>{{ viewingPost.ai_suggestions.source_details }}</span>
                            </div>
                        </div>

                        <!-- AI Hooklar (AI bilan boyitilgan bo'lsa) -->
                        <div v-if="viewingPost.ai_suggestions.ai_hooks?.length" class="space-y-2">
                            <p class="text-xs font-medium text-violet-600 dark:text-violet-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                AI hooklar
                            </p>
                            <div v-for="(hook, i) in viewingPost.ai_suggestions.ai_hooks" :key="'ai-hook-'+i" class="flex items-start gap-2 p-3 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800/40 rounded-xl">
                                <span class="text-violet-400 font-bold text-sm mt-0.5">{{ i + 1 }}.</span>
                                <p class="text-sm text-violet-800 dark:text-violet-200 italic">{{ hook }}</p>
                            </div>
                        </div>

                        <!-- Oddiy hooklar (AI bo'lmasa) -->
                        <div v-else-if="viewingPost.ai_suggestions.hooks?.length" class="space-y-2">
                            <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Boshlash uchun g'oyalar</p>
                            <div v-for="(hook, i) in viewingPost.ai_suggestions.hooks" :key="'hook-'+i" class="flex items-start gap-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 rounded-xl">
                                <span class="text-emerald-500 font-bold text-sm mt-0.5">"</span>
                                <p class="text-sm text-emerald-800 dark:text-emerald-200 italic">{{ hook }}</p>
                            </div>
                        </div>

                        <!-- AI Ssenariy -->
                        <div v-if="viewingPost.ai_suggestions.ai_script" class="space-y-2">
                            <p class="text-xs font-medium text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                Ssenariy
                            </p>
                            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 rounded-xl">
                                <p class="text-sm text-amber-800 dark:text-amber-200 whitespace-pre-line">{{ viewingPost.ai_suggestions.ai_script }}</p>
                            </div>
                        </div>

                        <!-- AI post yozuvi -->
                        <div v-if="viewingPost.ai_suggestions.ai_caption && viewingPost.ai_suggestions.is_ai_generated" class="space-y-2">
                            <p class="text-xs font-medium text-sky-600 dark:text-sky-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                Post yozuvi
                            </p>
                            <div class="p-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800/40 rounded-xl">
                                <p class="text-sm text-sky-800 dark:text-sky-200 whitespace-pre-line">{{ viewingPost.ai_suggestions.ai_caption }}</p>
                            </div>
                        </div>

                        <!-- AI CTA -->
                        <div v-if="viewingPost.ai_suggestions.ai_cta" class="space-y-2">
                            <p class="text-xs font-medium text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                Harakatga chaqiruv
                            </p>
                            <div class="p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/40 rounded-xl">
                                <p class="text-sm text-rose-800 dark:text-rose-200 italic">"{{ viewingPost.ai_suggestions.ai_cta }}"</p>
                            </div>
                        </div>

                        <!-- Content Tips -->
                        <div v-if="viewingPost.ai_suggestions.content_tips && Object.keys(viewingPost.ai_suggestions.content_tips).length" class="space-y-2">
                            <p class="text-xs font-medium text-blue-600 dark:text-blue-400">Amaliy maslahatlar</p>
                            <ul class="space-y-1.5">
                                <li v-for="(tip, key) in viewingPost.ai_suggestions.content_tips" :key="'tip-'+key" class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span>{{ tip }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- CTA Suggestions -->
                        <div v-if="viewingPost.ai_suggestions.cta_suggestions?.length" class="space-y-2">
                            <p class="text-xs font-medium text-amber-600 dark:text-amber-400">Harakatga chaqiruv</p>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="(cta, i) in viewingPost.ai_suggestions.cta_suggestions" :key="'cta-'+i" class="px-3 py-1.5 text-xs font-medium bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40 rounded-lg">
                                    {{ cta }}
                                </span>
                            </div>
                        </div>

                        <!-- Caption Rules -->
                        <div v-if="viewingPost.ai_suggestions.caption_rules && Object.keys(viewingPost.ai_suggestions.caption_rules).length" class="space-y-2">
                            <p class="text-xs font-medium text-purple-600 dark:text-purple-400">Yozuv qoidalari</p>
                            <ul class="space-y-1.5">
                                <li v-for="(rule, key) in viewingPost.ai_suggestions.caption_rules" v-show="typeof rule === 'string'" :key="'rule-'+key" class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 text-purple-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span>{{ rule }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Algorithm Signals -->
                        <div v-if="viewingPost.ai_suggestions.algorithm_signals && Object.keys(viewingPost.ai_suggestions.algorithm_signals).length" class="space-y-2">
                            <p class="text-xs font-medium text-pink-600 dark:text-pink-400">Instagramda muhim ko'rsatkichlar</p>
                            <ul class="space-y-1.5">
                                <li v-for="(signal, key) in viewingPost.ai_suggestions.algorithm_signals" :key="'signal-'+key" class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 text-pink-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                    <span>{{ typeof signal === 'object' ? signal.description : signal }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Pain Point -->
                        <div v-if="viewingPost.ai_suggestions.pain_text" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 rounded-xl">
                            <p class="text-xs font-medium text-red-600 dark:text-red-400 mb-1">Mijoz muammosi</p>
                            <p class="text-sm text-red-800 dark:text-red-200">{{ viewingPost.ai_suggestions.pain_text }}</p>
                        </div>
                    </div>

                    <!-- Hashtags -->
                    <div v-if="viewingPost.hashtags?.length">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Teglar</h4>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="(tag, i) in viewingPost.hashtags" :key="'tag-'+i" class="px-2.5 py-1 text-xs font-medium bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 rounded-lg">
                                {{ tag.startsWith('#') ? tag : '#' + tag }}
                            </span>
                        </div>
                    </div>

                    <!-- Statistics (published only) -->
                    <div v-if="viewingPost.status === 'published' && hasStats(viewingPost)">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Statistika</h4>
                        <div class="grid grid-cols-4 gap-3">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatNumber(getPostViews(viewingPost)) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Ko'rishlar</p>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                                <p class="text-lg font-bold text-pink-600 dark:text-pink-400">{{ formatNumber(getPostLikes(viewingPost)) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Yoqtirishlar</p>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(getPostComments(viewingPost)) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Izohlar</p>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                                <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ formatNumber(getPostShares(viewingPost)) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Ulashish</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="sticky bottom-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                    <!-- Quick Status Change -->
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mr-1">Holat:</span>
                        <button
                            v-for="st in statusActions"
                            :key="st.value"
                            @click="changeStatus(viewingPost.id, st.value)"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-all"
                            :class="viewingPost.status === st.value ? st.activeClass : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        >
                            {{ st.label }}
                        </button>
                    </div>
                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <button @click="editFromPanel()" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white text-sm font-medium rounded-xl transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            {{ t('common.edit') }}
                        </button>
                        <button v-if="viewingPost.status !== 'published'" @click="closeViewModal(); deletePost(viewingPost.id)" class="px-4 py-2.5 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-sm font-medium rounded-xl border border-red-200 dark:border-red-800/40 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, h } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { useI18n } from '@/i18n'

const props = defineProps({
    posts: { type: Array, default: () => [] },
    activeOffers: { type: Array, default: () => [] },
    aiRemaining: { type: [Number, null], default: null },
    painPoints: { type: Array, default: () => [] },
    panelType: { type: String, default: 'business', validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v) }
})

const { t } = useI18n()

// Panel config - universal for all 5 panels
const panelConfig = computed(() => {
    const prefix = props.panelType === 'business' ? 'business.marketing' : props.panelType;
    return {
        routePrefix: `${prefix}.content`,
        storeRoute: `${prefix}.content.store`,
        updateRoute: `${prefix}.content.update`,
        showRoute: `${prefix}.content.show`,
        destroyRoute: `${prefix}.content.destroy`
    };
})

// State
const activeStatus = ref('all')
const activePlatform = ref('all')
const activeContentType = ref('all')
const showCreateModal = ref(false)
const showViewModal = ref(false)
const viewingPost = ref(null)
const isSubmitting = ref(false)
const hashtagInput = ref('')
const viewMode = ref('table')
const editingPost = ref(null) // null = create mode, post object = edit mode

// Calendar state
const calendarMonth = ref(new Date().getMonth())
const calendarYear = ref(new Date().getFullYear())
const selectedDay = ref(null)

const weekDays = computed(() => [
    t('content.calendar.mon'), t('content.calendar.tue'), t('content.calendar.wed'),
    t('content.calendar.thu'), t('content.calendar.fri'), t('content.calendar.sat'), t('content.calendar.sun')
])
const monthNames = computed(() => [
    t('content.calendar.january'), t('content.calendar.february'), t('content.calendar.march'),
    t('content.calendar.april'), t('content.calendar.may'), t('content.calendar.june'),
    t('content.calendar.july'), t('content.calendar.august'), t('content.calendar.september'),
    t('content.calendar.october'), t('content.calendar.november'), t('content.calendar.december')
])

const formatDateLocal = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

const calendarMonthName = computed(() => monthNames.value[calendarMonth.value])

const calendarDays = computed(() => {
    const year = calendarYear.value
    const month = calendarMonth.value
    const firstDay = new Date(year, month, 1)
    const lastDay = new Date(year, month + 1, 0)
    const daysInMonth = lastDay.getDate()
    let startDay = firstDay.getDay() - 1
    if (startDay < 0) startDay = 6
    const days = []
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const prevMonthNum = month === 0 ? 11 : month - 1
    const prevYearNum = month === 0 ? year - 1 : year
    const daysInPrevMonth = new Date(prevYearNum, prevMonthNum + 1, 0).getDate()
    for (let i = startDay - 1; i >= 0; i--) {
        const date = daysInPrevMonth - i
        const fullDate = new Date(prevYearNum, prevMonthNum, date)
        days.push({ date, fullDate: formatDateLocal(fullDate), isCurrentMonth: false, isToday: false, isWeekend: fullDate.getDay() === 0 || fullDate.getDay() === 6 })
    }
    for (let i = 1; i <= daysInMonth; i++) {
        const fullDate = new Date(year, month, i)
        const isToday = fullDate.getTime() === today.getTime()
        days.push({ date: i, fullDate: formatDateLocal(fullDate), isCurrentMonth: true, isToday, isWeekend: fullDate.getDay() === 0 || fullDate.getDay() === 6 })
    }
    const remainingDays = 42 - days.length
    const nextMonthNum = month === 11 ? 0 : month + 1
    const nextYearNum = month === 11 ? year + 1 : year
    for (let i = 1; i <= remainingDays; i++) {
        const fullDate = new Date(nextYearNum, nextMonthNum, i)
        days.push({ date: i, fullDate: formatDateLocal(fullDate), isCurrentMonth: false, isToday: false, isWeekend: fullDate.getDay() === 0 || fullDate.getDay() === 6 })
    }
    return days
})

const getMonthScheduledCount = computed(() => props.posts.filter(post => post.status === 'scheduled' && post.scheduled_at && new Date(post.scheduled_at).getMonth() === calendarMonth.value && new Date(post.scheduled_at).getFullYear() === calendarYear.value).length)
const getMonthPublishedCount = computed(() => props.posts.filter(post => post.status === 'published' && new Date(post.scheduled_at || post.created_at).getMonth() === calendarMonth.value && new Date(post.scheduled_at || post.created_at).getFullYear() === calendarYear.value).length)

const prevMonth = () => { if (calendarMonth.value === 0) { calendarMonth.value = 11; calendarYear.value-- } else { calendarMonth.value-- } }
const nextMonth = () => { if (calendarMonth.value === 11) { calendarMonth.value = 0; calendarYear.value++ } else { calendarMonth.value++ } }
const goToToday = () => { const today = new Date(); calendarMonth.value = today.getMonth(); calendarYear.value = today.getFullYear() }

const getPostsForDay = (dateStr) => props.posts.filter(post => { if (!post.scheduled_at) return false; let postDate = post.scheduled_at; if (postDate.includes('T')) postDate = postDate.split('T')[0]; else if (postDate.includes(' ')) postDate = postDate.split(' ')[0]; return postDate === dateStr })
const getOverdueCountForDay = (dateStr) => getPostsForDay(dateStr).filter(post => isPostOverdue(post)).length
const dayHasOverduePosts = (dateStr) => getOverdueCountForDay(dateStr) > 0
const selectDay = (day) => { selectedDay.value = selectedDay.value?.fullDate === day.fullDate ? null : day }
const selectedDayPosts = computed(() => selectedDay.value ? getPostsForDay(selectedDay.value.fullDate) : [])
const formatSelectedDate = computed(() => { if (!selectedDay.value) return ''; const [year, month, dayNum] = selectedDay.value.fullDate.split('-'); return `${parseInt(dayNum)} ${monthNames.value[parseInt(month) - 1]} ${year}` })

const postForm = ref({ title: '', content: '', platforms: [], content_type: '', format: '', status: 'draft', scheduled_date: '', scheduled_time: '', scheduled_at: null, hashtags: [], platform_links: {} })
const minDate = computed(() => new Date().toISOString().split('T')[0])

const statusTabs = computed(() => [
    { label: t('content.filter.all'), value: 'all' },
    { label: t('content.status.draft'), value: 'draft' },
    { label: t('content.status.scheduled'), value: 'scheduled' },
    { label: t('content.status.published'), value: 'published' },
    { label: t('content.status.overdue'), value: 'overdue' }
])

// Platform Icons
const InstagramIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' })]) } }
const TelegramIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z' })]) } }
const FacebookIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' })]) } }
const YouTubeIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z' })]) } }
const YouTubeShortsIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M17.77 10.32c-.77-.32-1.2-.5-1.2-.5L18 9.06c1.84-.96 2.53-3.23 1.56-5.06s-3.24-2.53-5.07-1.56L6 6.94c-1.29.68-2.07 2.04-2 3.49.07 1.42.93 2.67 2.22 3.25.03.01 1.2.5 1.2.5L6 14.93c-1.83.97-2.53 3.24-1.56 5.07.97 1.83 3.24 2.53 5.07 1.56l8.5-4.5c1.29-.68 2.06-2.04 1.99-3.49-.07-1.42-.94-2.68-2.23-3.25zM10 14.65v-5.3L15 12l-5 2.65z' })]) } }
const DefaultPlatformIcon = { render() { return h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [h('circle', { cx: '12', cy: '12', r: '10' })]) } }

const platformFilters = computed(() => [
    { label: t('content.filter.all'), value: 'all', activeClass: 'bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800' },
    { label: 'Instagram', value: 'Instagram', icon: InstagramIcon, activeClass: 'bg-gradient-to-r from-purple-600 to-pink-600 text-white' },
    { label: 'Telegram', value: 'Telegram', icon: TelegramIcon, activeClass: 'bg-sky-600 text-white' },
    { label: 'Facebook', value: 'Facebook', icon: FacebookIcon, activeClass: 'bg-blue-600 text-white' },
    { label: 'YouTube', value: 'YouTube', icon: YouTubeIcon, activeClass: 'bg-red-600 text-white' },
    { label: 'Shorts', value: 'YouTube Shorts', icon: YouTubeShortsIcon, activeClass: 'bg-red-500 text-white' }
])

const availablePlatforms = [
    { value: 'Instagram', label: 'Instagram', icon: InstagramIcon, iconClass: 'text-pink-600 dark:text-pink-400', selectedClass: 'bg-gradient-to-r from-purple-600 to-pink-600 border-transparent' },
    { value: 'Telegram', label: 'Telegram', icon: TelegramIcon, iconClass: 'text-sky-600 dark:text-sky-400', selectedClass: 'bg-sky-600 border-transparent' },
    { value: 'Facebook', label: 'Facebook', icon: FacebookIcon, iconClass: 'text-blue-600 dark:text-blue-400', selectedClass: 'bg-blue-600 border-transparent' },
    { value: 'YouTube', label: 'YouTube', icon: YouTubeIcon, iconClass: 'text-red-600 dark:text-red-400', selectedClass: 'bg-red-600 border-transparent' },
    { value: 'YouTube Shorts', label: 'YouTube Shorts', icon: YouTubeShortsIcon, iconClass: 'text-red-500 dark:text-red-400', selectedClass: 'bg-red-500 border-transparent' }
]

const contentTypeFilters = computed(() => [
    { label: t('content.filter.all'), value: 'all', activeClass: 'bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800' },
    { label: t('content.type.educational'), value: 'educational', activeClass: 'bg-blue-600 text-white' },
    { label: t('content.type.entertaining'), value: 'entertaining', activeClass: 'bg-pink-600 text-white' },
    { label: t('content.type.inspirational'), value: 'inspirational', activeClass: 'bg-amber-600 text-white' },
    { label: t('content.type.promotional'), value: 'promotional', activeClass: 'bg-emerald-600 text-white' }
])

const isPostOverdue = (post) => { if (post.status !== 'scheduled' || !post.scheduled_at) return false; const now = new Date(); let scheduledDate = post.scheduled_at.includes('T') ? new Date(post.scheduled_at) : new Date(post.scheduled_at.replace(' ', 'T')); return scheduledDate < now }

// Analytics summary
const analyticsStats = computed(() => {
    let totalViews = 0, totalLikes = 0, totalComments = 0, linkedPosts = 0
    const rates = []
    props.posts.filter(p => p.status === 'published' && p.links).forEach(post => {
        const links = post.links || {}
        if (Object.keys(links).length === 0) return
        linkedPosts++
        Object.values(links).forEach(l => {
            totalViews += (l.views || 0)
            totalLikes += (l.likes || 0)
            totalComments += (l.comments || 0)
            if (l.engagement_rate > 0) rates.push(parseFloat(l.engagement_rate))
        })
    })
    const avgER = rates.length > 0 ? rates.reduce((a, b) => a + b, 0) / rates.length : 0
    return { totalViews, totalLikes, totalComments, linkedPosts, avgER }
})

const draftCount = computed(() => props.posts.filter(p => p.status === 'draft').length)
const scheduledCount = computed(() => props.posts.filter(p => p.status === 'scheduled' && !isPostOverdue(p)).length)
const publishedCount = computed(() => props.posts.filter(p => p.status === 'published').length)
const overdueCount = computed(() => props.posts.filter(p => isPostOverdue(p)).length)
const getContentTypeCount = (type) => props.posts.filter(p => p.content_type === type).length

const filteredPosts = computed(() => {
    let filtered = props.posts
    if (activeStatus.value !== 'all') {
        if (activeStatus.value === 'overdue') filtered = filtered.filter(post => isPostOverdue(post))
        else if (activeStatus.value === 'scheduled') filtered = filtered.filter(post => post.status === 'scheduled' && !isPostOverdue(post))
        else filtered = filtered.filter(post => post.status === activeStatus.value)
    }
    if (activePlatform.value !== 'all') filtered = filtered.filter(post => getPlatforms(post.platform).includes(activePlatform.value))
    if (activeContentType.value !== 'all') filtered = filtered.filter(post => post.content_type === activeContentType.value)
    return filtered
})

const getPlatforms = (platform) => { if (!platform) return []; if (typeof platform === 'string') { if (platform.startsWith('[')) { try { return JSON.parse(platform) } catch (e) { return [platform] } } return [platform] } if (Array.isArray(platform)) return platform; return [platform] }
const getPlatformIcon = (platform) => ({ 'Instagram': InstagramIcon, 'Telegram': TelegramIcon, 'Facebook': FacebookIcon, 'YouTube': YouTubeIcon, 'YouTube Shorts': YouTubeShortsIcon }[platform] || DefaultPlatformIcon)
const getPlatformBgClass = (platform) => ({ 'Instagram': 'bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/40 dark:to-pink-900/40', 'Telegram': 'bg-sky-100 dark:bg-sky-900/40', 'Facebook': 'bg-blue-100 dark:bg-blue-900/40', 'YouTube': 'bg-red-100 dark:bg-red-900/40', 'YouTube Shorts': 'bg-red-50 dark:bg-red-900/30' }[platform] || 'bg-gray-100 dark:bg-gray-700')
const getPlatformIconClass = (platform) => ({ 'Instagram': 'text-pink-600 dark:text-pink-400', 'Telegram': 'text-sky-600 dark:text-sky-400', 'Facebook': 'text-blue-600 dark:text-blue-400', 'YouTube': 'text-red-600 dark:text-red-400', 'YouTube Shorts': 'text-red-500 dark:text-red-400' }[platform] || 'text-gray-600 dark:text-gray-400')

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
const getContentTypeDotClass = (type) => ({ educational: 'bg-blue-500', entertaining: 'bg-pink-500', inspirational: 'bg-amber-500', promotional: 'bg-emerald-500', behind_scenes: 'bg-purple-500', ugc: 'bg-orange-500' }[type] || 'bg-gray-500')

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

const getStatusLabel = (status, post = null) => {
    if (post && isPostOverdue(post)) return t('content.status.overdue')
    const labels = {
        draft: t('content.status.draft'),
        scheduled: t('content.status.scheduled'),
        published: t('content.status.published')
    }
    return labels[status] || status
}
const getStatusBadgeClass = (status, post = null) => { if (post && isPostOverdue(post)) return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'; return { draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', scheduled: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', published: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }
const getStatusDotClass = (status, post = null) => { if (post && isPostOverdue(post)) return 'bg-red-500'; return { draft: 'bg-gray-400', scheduled: 'bg-amber-500', published: 'bg-emerald-500' }[status] || 'bg-gray-400' }

const formatDate = (dateStr) => { if (!dateStr) return ''; const date = new Date(dateStr); return date.toLocaleDateString('uz-UZ', { day: '2-digit', month: '2-digit', year: 'numeric' }) }
const formatTime = (dateStr) => { if (!dateStr) return ''; const date = new Date(dateStr); return date.toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' }) }
const hasStats = (post) => getPostViews(post) || getPostLikes(post) || getPostComments(post) || post.shares || hasLinks(post)
const formatNumber = (num) => { if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'; if (num >= 1000) return (num / 1000).toFixed(1) + 'K'; return num?.toString() || '0' }

// Platform links stats helpers
const getPostLinks = (post) => post.links || {}
const getLinkStats = (post, platform) => getPostLinks(post)[platform] || null
const getTotalStat = (post, field) => {
    const links = getPostLinks(post)
    let total = 0
    Object.values(links).forEach(l => { total += (l[field] || 0) })
    return total || (post[field] ?? 0)
}
const getPostViews = (post) => getTotalStat(post, 'views')
const getPostLikes = (post) => getTotalStat(post, 'likes')
const getPostComments = (post) => getTotalStat(post, 'comments')
const getPostShares = (post) => getTotalStat(post, 'shares')
const getPostSaves = (post) => getTotalStat(post, 'saves')
const getPostForwards = (post) => getTotalStat(post, 'forwards')
const getEngagementRate = (post) => {
    const links = getPostLinks(post)
    const rates = Object.values(links).map(l => parseFloat(l.engagement_rate || 0)).filter(r => r > 0)
    if (rates.length) return rates.reduce((a, b) => a + b, 0) / rates.length
    if (post.engagement_rate) return parseFloat(post.engagement_rate)
    return 0
}
const hasLinks = (post) => Object.keys(getPostLinks(post)).length > 0
const isTopPerformer = (post) => getEngagementRate(post) >= 5
const getEngagementRateClass = (post) => {
    const rate = getEngagementRate(post)
    if (rate >= 5) return 'text-emerald-600 dark:text-emerald-400 font-semibold'
    if (rate >= 3) return 'text-blue-600 dark:text-blue-400'
    if (rate >= 1) return 'text-gray-600 dark:text-gray-400'
    return 'text-gray-400 dark:text-gray-500'
}
// Sync state
const syncingPostId = ref(null)
const syncPost = async (postId) => {
    syncingPostId.value = postId
    try {
        const prefix = props.panelType === 'business' ? 'business.marketing' : props.panelType
        await axios.post(route(`${prefix}.content.sync-stats`, postId), {}, { headers: { Accept: 'application/json' } })
        router.reload({ only: ['posts'], preserveScroll: true })
    } catch (e) {
        console.error('Sync error:', e)
    } finally {
        syncingPostId.value = null
    }
}

// Status actions for detail panel
const statusActions = [
    { value: 'draft', label: t('content.status.draft'), activeClass: 'bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-500' },
    { value: 'scheduled', label: t('content.status.scheduled'), activeClass: 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-700' },
    { value: 'published', label: t('content.status.published'), activeClass: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700' },
]

const changeStatus = (postId, newStatus) => {
    const post = viewingPost.value
    if (!post) return
    // Send all required fields along with new status
    let platforms = getPlatforms(post.platform)
    const formData = {
        title: post.title,
        content: post.content || post.title,
        platform: platforms,
        content_type: post.content_type || 'educational',
        format: post.format || 'text_post',
        status: newStatus,
        scheduled_at: post.scheduled_at || null,
        hashtags: post.hashtags || [],
    }
    router.put(route(panelConfig.value.updateRoute, postId), formData, {
        preserveScroll: true,
        onSuccess: () => {
            if (viewingPost.value && viewingPost.value.id === postId) {
                viewingPost.value = { ...viewingPost.value, status: newStatus }
            }
        }
    })
}

// Pain point state
const selectedPainPointId = ref('')
const selectedPainPoint = computed(() => {
    if (!selectedPainPointId.value) return null
    return props.painPoints.find(p => p.id === selectedPainPointId.value) || null
})
const painPointGroups = computed(() => {
    const groups = {}
    props.painPoints.forEach(p => {
        const key = p.category
        if (!groups[key]) groups[key] = { category: key, label: p.category_label, items: [] }
        groups[key].items.push(p)
    })
    return Object.values(groups)
})
const handlePainPointSelect = () => {
    const p = selectedPainPoint.value
    if (p) {
        // AI topicga muammo textini qo'yish
        aiForm.value.topic = p.text
        // Agar taklif qilingan topic bo'lsa, birinchisini ishlatish
        if (p.topics?.length && !postForm.value.title) {
            postForm.value.title = p.topics[0]
        }
        // Muammo uchun maqsadni educate qilish
        aiForm.value.purpose = 'educate'
    }
}
const useHook = (hook) => {
    // Hook ni kontent boshiga qo'shish
    if (postForm.value.content) {
        postForm.value.content = hook + '\n\n' + postForm.value.content
    } else {
        postForm.value.content = hook + '\n\n'
    }
}

// AI Generation state
const showAiPanel = ref(false)
const aiGenerating = ref(false)
const aiError = ref('')
const aiForm = ref({ topic: '', purpose: 'engage', target_channel: '', offer_id: '' })

const handleAiOfferSelect = () => {
    if (aiForm.value.offer_id) {
        const selected = props.activeOffers?.find(o => o.id === aiForm.value.offer_id)
        if (selected) {
            aiForm.value.topic = selected.name
            aiForm.value.purpose = 'sell'
        }
    }
}

const generateAiContent = async () => {
    if (!aiForm.value.topic) return
    aiGenerating.value = true
    aiError.value = ''
    try {
        // Muammo kontekstini qo'shish
        let additionalPrompt = null
        const pain = selectedPainPoint.value
        if (pain) {
            additionalPrompt = `Mijoz muammosi: ${pain.text}`
            if (pain.hooks?.length) additionalPrompt += `\nBoshlash uchun hook: ${pain.hooks[0]}`
        }

        const response = await axios.post(route('business.marketing.content-ai.generate'), {
            topic: aiForm.value.topic,
            content_type: 'post',
            purpose: aiForm.value.purpose,
            target_channel: aiForm.value.target_channel || null,
            offer_id: aiForm.value.offer_id || null,
            additional_prompt: additionalPrompt,
        }, { headers: { Accept: 'application/json' } })

        const gen = response.data.generation
        if (!postForm.value.title) postForm.value.title = aiForm.value.topic
        postForm.value.content = gen.generated_content || ''
        // Map AI purpose → content_type
        const purposeMap = { sell: 'promotional', educate: 'educational', inspire: 'inspirational', engage: 'entertaining', announce: 'promotional', entertain: 'entertaining' }
        if (!postForm.value.content_type) postForm.value.content_type = purposeMap[aiForm.value.purpose] || ''
        showAiPanel.value = false
    } catch (e) {
        aiError.value = e.response?.data?.error || e.response?.data?.message || 'Xatolik yuz berdi'
    } finally {
        aiGenerating.value = false
    }
}

const openCreateModal = () => {
    editingPost.value = null
    postForm.value = { title: '', content: '', platforms: [], content_type: '', format: '', status: 'draft', scheduled_date: '', scheduled_time: '', scheduled_at: null, hashtags: [], platform_links: {} }
    hashtagInput.value = ''
    selectedPainPointId.value = ''
    showAiPanel.value = false
    aiForm.value = { topic: '', purpose: 'engage', target_channel: '', offer_id: '' }
    aiError.value = ''
    showCreateModal.value = true
}

const openEditModal = (post) => {
    editingPost.value = post
    // Parse platforms
    let platforms = post.platform
    if (typeof platforms === 'string') {
        if (platforms.startsWith('[')) { try { platforms = JSON.parse(platforms) } catch (e) { platforms = [platforms] } }
        else { platforms = [platforms] }
    }
    if (!Array.isArray(platforms)) platforms = [platforms]
    // Parse scheduled date/time
    let scheduledDate = ''
    let scheduledTime = ''
    if (post.scheduled_at) {
        const parts = post.scheduled_at.includes('T') ? post.scheduled_at.split('T') : post.scheduled_at.split(' ')
        scheduledDate = parts[0] || ''
        scheduledTime = (parts[1] || '').substring(0, 5)
    }
    // Parse hashtags
    let hashtags = post.hashtags
    if (typeof hashtags === 'string') { try { hashtags = JSON.parse(hashtags) } catch (e) { hashtags = [] } }
    if (!Array.isArray(hashtags)) hashtags = []

    // Parse platform links from existing links data
    const platformLinks = {}
    if (post.links) {
        Object.entries(post.links).forEach(([platform, link]) => {
            platformLinks[platform] = link.external_url || ''
        })
    }

    postForm.value = {
        title: post.title || '',
        content: post.content || '',
        platforms: platforms.filter(Boolean),
        content_type: post.content_type || '',
        format: post.format || '',
        status: post.status || 'draft',
        scheduled_date: scheduledDate,
        scheduled_time: scheduledTime,
        scheduled_at: post.scheduled_at || null,
        hashtags: hashtags,
        platform_links: platformLinks,
    }
    hashtagInput.value = hashtags.join(', ')
    showCreateModal.value = true
}

const closeCreateModal = () => { showCreateModal.value = false; editingPost.value = null }
const viewPost = (post) => { viewingPost.value = post; showViewModal.value = true }
const closeViewModal = () => { showViewModal.value = false; viewingPost.value = null }
const editFromPanel = () => { const post = viewingPost.value; closeViewModal(); if (post) openEditModal(post) }

const submitPost = () => {
    if (postForm.value.platforms.length === 0) { alert(t('content.alert.select_platform')); return }
    isSubmitting.value = true
    if (hashtagInput.value) postForm.value.hashtags = hashtagInput.value.split(',').map(t => t.trim()).filter(t => t)
    if (postForm.value.status === 'scheduled' && postForm.value.scheduled_date && postForm.value.scheduled_time) postForm.value.scheduled_at = `${postForm.value.scheduled_date} ${postForm.value.scheduled_time}`
    else if (postForm.value.status !== 'scheduled') postForm.value.scheduled_at = null
    // Build platform_links array for backend
    const platformLinksArray = postForm.value.platforms
        .filter(p => postForm.value.platform_links[p])
        .map(p => ({ platform: p, external_url: postForm.value.platform_links[p] }))
    const formData = { ...postForm.value, platform: postForm.value.platforms, type: postForm.value.format, platform_links: platformLinksArray }

    if (editingPost.value) {
        // Update existing post
        router.put(route(panelConfig.value.updateRoute, editingPost.value.id), formData, {
            preserveScroll: true,
            onSuccess: () => { closeCreateModal(); isSubmitting.value = false },
            onError: () => { isSubmitting.value = false }
        })
    } else {
        // Create new post
        router.post(route(panelConfig.value.storeRoute), formData, {
            preserveScroll: true,
            onSuccess: () => { closeCreateModal(); isSubmitting.value = false },
            onError: () => { isSubmitting.value = false }
        })
    }
}

const deletePost = (id) => { if (confirm(t('content.confirm.delete'))) router.delete(route(panelConfig.value.destroyRoute, id), { preserveScroll: true }) }
const togglePlatform = (platform) => { const index = postForm.value.platforms.indexOf(platform); if (index === -1) postForm.value.platforms.push(platform); else postForm.value.platforms.splice(index, 1) }
</script>
