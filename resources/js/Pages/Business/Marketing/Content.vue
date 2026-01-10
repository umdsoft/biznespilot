<template>
    <BusinessLayout title="Kontent Reja">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kontent Reja</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Ijtimoiy tarmoqlar uchun kontentlarni rejalashtiring va boshqaring
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-medium rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all duration-200"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Kontent Qo'shish
                </button>
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
                    <h4 class="text-sm font-semibold text-red-800 dark:text-red-300">Muddati o'tgan kontentlar mavjud!</h4>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">
                        {{ overdueCount }} ta kontent rejalashtirilgan vaqtda nashr qilinmagan. Iltimos, ularni ko'rib chiqing.
                    </p>
                </div>
                <button
                    @click="activeStatus = 'overdue'"
                    class="flex-shrink-0 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors"
                >
                    Ko'rish
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Jami</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ posts.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Qoralama</p>
                    <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ draftCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-amber-600 dark:text-amber-400">Rejalashtirilgan</p>
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
                        <p class="text-xs font-medium text-red-600 dark:text-red-400">Muddati o'tgan</p>
                        <svg v-if="overdueCount > 0" class="w-3.5 h-3.5 text-red-500 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ overdueCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Nashr qilingan</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ publishedCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-blue-600 dark:text-blue-400">Ta'limiy</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ getContentTypeCount('educational') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-pink-600 dark:text-pink-400">Ko'ngil ochar</p>
                    <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ getContentTypeCount('entertaining') }}</p>
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
                        title="Jadval ko'rinishi"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </button>
                    <button
                        @click="viewMode = 'calendar'"
                        class="p-2 rounded-md transition-all"
                        :class="viewMode === 'calendar' ? 'bg-white dark:bg-gray-600 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        title="Kalendar ko'rinishi"
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
                                {{ getMonthScheduledCount }} rejalashtirilgan
                            </span>
                            <span
                                v-if="getMonthPublishedCount > 0"
                                class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400"
                            >
                                {{ getMonthPublishedCount }} nashr qilingan
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
                            Bugun
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
                                    :title="dayHasOverduePosts(day.fullDate) ? getOverdueCountForDay(day.fullDate) + ' ta muddati o\'tgan' : ''"
                                >
                                    {{ getPostsForDay(day.fullDate).length }}
                                </span>
                            </div>

                            <!-- Posts indicators -->
                            <div class="flex flex-wrap gap-1 mt-1">
                                <div
                                    v-for="post in getPostsForDay(day.fullDate).slice(0, 3)"
                                    :key="post.id"
                                    class="w-2 h-2 rounded-full"
                                    :class="getStatusDotClass(post.status, post)"
                                    :title="post.title + (isPostOverdue(post) ? ' (Muddati o\'tgan!)' : '')"
                                ></div>
                                <span
                                    v-if="getPostsForDay(day.fullDate).length > 3"
                                    class="text-xs text-gray-400"
                                >
                                    +{{ getPostsForDay(day.fullDate).length - 3 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Day Details -->
                <div v-if="selectedDay && selectedDayPosts.length > 0" class="p-4 space-y-4">
                    <!-- Selected Day Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ formatSelectedDate }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedDayPosts.length }} ta kontent</p>
                            </div>
                        </div>
                        <button
                            @click="selectedDay = null"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            title="Yopish"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <!-- Posts -->
                    <div
                        v-for="post in selectedDayPosts"
                        :key="post.id"
                        class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-800 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all duration-200"
                    >
                        <!-- Header row -->
                        <div class="grid grid-cols-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Sarlavha</div>
                            <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tavsif</div>
                            <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Platformalar</div>
                            <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Ma'lumotlar</div>
                        </div>
                        <!-- Content row -->
                        <div class="grid grid-cols-4 items-start">
                            <!-- Title & Status -->
                            <div class="px-4 py-3 flex items-start gap-2">
                                <span
                                    class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0"
                                    :class="getStatusDotClass(post.status, post)"
                                ></span>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ post.title }}
                                    </h4>
                                    <span
                                        class="inline-flex items-center mt-1 px-2 py-0.5 text-xs font-medium rounded"
                                        :class="getStatusBadgeClass(post.status, post)"
                                    >
                                        {{ getStatusLabel(post.status, post) }}
                                        <svg v-if="isPostOverdue(post)" class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            <!-- Description -->
                            <div class="px-4 py-3">
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                                    {{ post.content }}
                                </p>
                            </div>
                            <!-- Platforms -->
                            <div class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <template v-for="(platform, idx) in getPlatforms(post.platform)" :key="idx">
                                        <div
                                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg shadow-sm"
                                            :class="getPlatformBgClass(platform)"
                                        >
                                            <component
                                                :is="getPlatformIcon(platform)"
                                                class="w-4 h-4"
                                                :class="getPlatformIconClass(platform)"
                                            />
                                            <span class="text-xs font-semibold" :class="getPlatformIconClass(platform)">
                                                {{ platform }}
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <!-- Info & Actions -->
                            <div class="px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ getPostTime(post.scheduled_at) }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ getContentTypeLabel(post.content_type) }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button
                                            @click="viewPost(post)"
                                            class="p-1.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                            title="Ko'rish"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <Link
                                            :href="`/business/marketing/content/${post.id}/edit`"
                                            class="p-1.5 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors"
                                            title="Tahrirlash"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            v-if="post.status !== 'published'"
                                            @click="deletePost(post.id)"
                                            class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                            title="O'chirish"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Day Empty State -->
                <div v-if="selectedDay && selectedDayPosts.length === 0" class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-xl">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ formatSelectedDate }}</h3>
                        </div>
                        <button
                            @click="selectedDay = null"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="text-center py-8 bg-gray-50 dark:bg-gray-900/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Bu kun uchun kontent yo'q</p>
                        <button
                            @click="openCreateModal()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Kontent qo'shish
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
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Kontent</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Platforma</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Turi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Format</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Holat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Sana/Vaqt</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Statistika</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="post in filteredPosts"
                                :key="post.id"
                                class="transition-colors"
                                :class="isPostOverdue(post) ? 'bg-red-50/50 dark:bg-red-900/10 hover:bg-red-50 dark:hover:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                            >
                                <!-- Content -->
                                <td class="px-4 py-3">
                                    <div class="max-w-xs">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ post.title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 mt-0.5">{{ post.content }}</p>
                                    </div>
                                </td>

                                <!-- Platform -->
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

                                <!-- Content Type -->
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md"
                                        :class="getContentTypeBadgeClass(post.content_type)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="getContentTypeDotClass(post.content_type)"></span>
                                        {{ getContentTypeLabel(post.content_type) }}
                                    </span>
                                </td>

                                <!-- Format -->
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                    >
                                        {{ getFormatLabel(post.format) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg"
                                        :class="getStatusBadgeClass(post.status, post)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="getStatusDotClass(post.status, post)"></span>
                                        {{ getStatusLabel(post.status, post) }}
                                        <svg v-if="isPostOverdue(post)" class="w-3.5 h-3.5 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </td>

                                <!-- Date/Time -->
                                <td class="px-4 py-3">
                                    <div v-if="post.scheduled_at || post.published_at" class="text-sm">
                                        <p class="text-gray-900 dark:text-white font-medium">
                                            {{ formatDate(post.scheduled_at || post.published_at) }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatTime(post.scheduled_at || post.published_at) }}
                                        </p>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                </td>

                                <!-- Stats -->
                                <td class="px-4 py-3">
                                    <div v-if="post.status === 'published' && hasStats(post)" class="flex items-center space-x-3 text-xs">
                                        <span v-if="post.views" class="flex items-center text-gray-500 dark:text-gray-400" title="Ko'rishlar">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ formatNumber(post.views) }}
                                        </span>
                                        <span v-if="post.likes" class="flex items-center text-pink-500" title="Yoqtirishlar">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                            {{ formatNumber(post.likes) }}
                                        </span>
                                        <span v-if="post.comments" class="flex items-center text-blue-500" title="Izohlar">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            {{ formatNumber(post.comments) }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end space-x-1">
                                        <button
                                            @click="viewPost(post)"
                                            class="p-1.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                            title="Ko'rish"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <Link
                                            :href="`/business/marketing/content/${post.id}/edit`"
                                            class="p-1.5 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors"
                                            title="Tahrirlash"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            v-if="post.status !== 'published'"
                                            @click="deletePost(post.id)"
                                            class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
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

                <!-- Empty State -->
                <div v-if="filteredPosts.length === 0" class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/40 dark:to-purple-900/40 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kontent topilmadi</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Tanlangan filtrlarga mos kontent mavjud emas
                    </p>
                    <button
                        @click="openCreateModal"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Kontent Qo'shish
                    </button>
                </div>
            </div>

            <!-- Content Strategy Tips -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- 80/20 Rule -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-5 border border-blue-100 dark:border-blue-800/30">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <span class="text-white font-bold text-sm">80/20</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Kontent Qoidasi</h4>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                <strong>80%</strong> foydali va qiziqarli kontent (ta'limiy, ilhomlantiruvchi, ko'ngil ochuvchi),
                                <strong>20%</strong> reklama va sotish kontenti
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Content Mix -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-5 border border-purple-100 dark:border-purple-800/30">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-purple-600 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Kontent Aralashmasi</h4>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                Ta'limiy, ko'ngil ochuvchi, ilhomlantiruvchi, reklama va sahna ortidan turlarini muvozanatda saqlang
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeCreateModal"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-auto overflow-hidden transform transition-all">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yangi Kontent Yaratish</h3>
                            <button @click="closeCreateModal" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitPost" class="p-6 space-y-5">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Sarlavha</label>
                                <input
                                    v-model="postForm.title"
                                    type="text"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    placeholder="Kontent sarlavhasi"
                                    required
                                />
                            </div>

                            <!-- Content -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kontent Matni</label>
                                <textarea
                                    v-model="postForm.content"
                                    rows="4"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none"
                                    placeholder="Post matni..."
                                    required
                                ></textarea>
                            </div>

                            <!-- Platforms (Multi-select) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Platformalar</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Bir nechta platformaga joylash uchun bir nechtasini tanlang</p>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="platform in availablePlatforms"
                                        :key="platform.value"
                                        class="relative flex items-center px-3 py-2 rounded-xl border cursor-pointer transition-all"
                                        :class="postForm.platforms.includes(platform.value)
                                            ? platform.selectedClass
                                            : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="platform.value"
                                            v-model="postForm.platforms"
                                            class="sr-only"
                                        />
                                        <component :is="platform.icon" class="w-4 h-4 mr-2" :class="postForm.platforms.includes(platform.value) ? 'text-white' : platform.iconClass" />
                                        <span class="text-sm font-medium" :class="postForm.platforms.includes(platform.value) ? 'text-white' : 'text-gray-700 dark:text-gray-300'">{{ platform.label }}</span>
                                        <svg v-if="postForm.platforms.includes(platform.value)" class="w-4 h-4 ml-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </label>
                                </div>
                            </div>

                            <!-- Content Type -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kontent Turi</label>
                                    <select
                                        v-model="postForm.content_type"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                        required
                                    >
                                        <option value="">Tanlang</option>
                                        <option value="educational">Ta'limiy (Educational)</option>
                                        <option value="entertaining">Ko'ngil ochuvchi (Entertaining)</option>
                                        <option value="inspirational">Ilhomlantiruvchi (Inspirational)</option>
                                        <option value="promotional">Reklama (Promotional)</option>
                                        <option value="behind_scenes">Sahna ortidan (Behind the Scenes)</option>
                                        <option value="ugc">Foydalanuvchi kontenti (UGC)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Content Type Description -->
                            <div v-if="postForm.content_type" class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 border border-gray-200 dark:border-gray-600">
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    <strong class="text-gray-900 dark:text-white">{{ getContentTypeLabel(postForm.content_type) }}:</strong>
                                    {{ getContentTypeDescription(postForm.content_type) }}
                                </p>
                            </div>

                            <!-- Format & Status -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Format</label>
                                    <select
                                        v-model="postForm.format"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                        required
                                    >
                                        <option value="">Tanlang</option>
                                        <option value="short_video">Qisqa Video (Reels/Shorts)</option>
                                        <option value="long_video">Uzun Video</option>
                                        <option value="carousel">Karusel (Slaydlar)</option>
                                        <option value="single_image">Bitta Rasm</option>
                                        <option value="story">Story</option>
                                        <option value="text_post">Matn Post</option>
                                        <option value="live">Jonli efir</option>
                                        <option value="poll">So'rovnoma</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Holat</label>
                                    <select
                                        v-model="postForm.status"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    >
                                        <option value="draft">Qoralama</option>
                                        <option value="scheduled">Rejalashtirilgan</option>
                                        <option value="published">Nashr qilingan</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Scheduled Date & Time -->
                            <div v-if="postForm.status === 'scheduled'" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            Sana
                                        </span>
                                    </label>
                                    <input
                                        v-model="postForm.scheduled_date"
                                        type="date"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                        :min="minDate"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Soat
                                        </span>
                                    </label>
                                    <input
                                        v-model="postForm.scheduled_time"
                                        type="time"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                        required
                                    />
                                </div>
                            </div>

                            <!-- Best posting times hint -->
                            <div v-if="postForm.status === 'scheduled' && postForm.platforms.length > 0" class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-3 border border-amber-100 dark:border-amber-800/30">
                                <p class="text-xs text-amber-700 dark:text-amber-300 flex items-start">
                                    <svg class="w-4 h-4 mr-1.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>
                                        <strong>Eng yaxshi vaqtlar:</strong>
                                        <template v-for="(platform, idx) in postForm.platforms" :key="platform">
                                            <span v-if="idx > 0">, </span>
                                            <span v-if="platform === 'Instagram'">Instagram: 09:00-11:00, 19:00-21:00</span>
                                            <span v-else-if="platform === 'Telegram'">Telegram: 08:00-10:00, 18:00-20:00</span>
                                            <span v-else-if="platform === 'Facebook'">Facebook: 13:00-16:00, 19:00-21:00</span>
                                            <span v-else-if="platform === 'YouTube'">YouTube: 12:00-15:00, 17:00-21:00</span>
                                            <span v-else-if="platform === 'YouTube Shorts'">Shorts: 12:00-15:00, 19:00-22:00</span>
                                        </template>
                                    </span>
                                </p>
                            </div>

                            <!-- Hashtags -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Hashtaglar</label>
                                <input
                                    v-model="hashtagInput"
                                    type="text"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    placeholder="#biznes, #marketing, #tips (vergul bilan ajrating)"
                                />
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button
                                    type="button"
                                    @click="closeCreateModal"
                                    class="px-4 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-medium transition-colors"
                                >
                                    Bekor qilish
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-medium rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all disabled:opacity-50"
                                >
                                    <span v-if="isSubmitting" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Saqlanmoqda...
                                    </span>
                                    <span v-else>Saqlash</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- View Modal -->
        <Teleport to="body">
            <div v-if="showViewModal && viewingPost" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeViewModal"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg mx-auto overflow-hidden transform transition-all">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ viewingPost.title }}</h3>
                            <button @click="closeViewModal" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6">
                            <!-- Badges -->
                            <div class="flex flex-wrap items-center gap-2 mb-4">
                                <span :class="getStatusBadgeClass(viewingPost.status)" class="px-2.5 py-1 text-xs font-medium rounded-lg">
                                    {{ getStatusLabel(viewingPost.status) }}
                                </span>
                                <span :class="getContentTypeBadgeClass(viewingPost.content_type)" class="px-2.5 py-1 text-xs font-medium rounded-lg">
                                    {{ getContentTypeLabel(viewingPost.content_type) }}
                                </span>
                                <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    {{ getFormatLabel(viewingPost.format) }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ viewingPost.platform }}</span>
                            </div>

                            <!-- Content -->
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-4">{{ viewingPost.content }}</p>

                            <!-- Dates -->
                            <div class="text-sm text-gray-500 dark:text-gray-400 space-y-1">
                                <p v-if="viewingPost.scheduled_at">Rejalashtirilgan: {{ viewingPost.scheduled_at }}</p>
                                <p v-if="viewingPost.published_at">Nashr qilingan: {{ viewingPost.published_at }}</p>
                            </div>

                            <!-- Stats -->
                            <div v-if="viewingPost.status === 'published' && hasStats(viewingPost)" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Statistika</h4>
                                <div class="grid grid-cols-4 gap-4 text-center">
                                    <div v-if="viewingPost.views">
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(viewingPost.views) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Ko'rishlar</p>
                                    </div>
                                    <div v-if="viewingPost.likes">
                                        <p class="text-xl font-bold text-pink-600 dark:text-pink-400">{{ formatNumber(viewingPost.likes) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Yoqtirishlar</p>
                                    </div>
                                    <div v-if="viewingPost.comments">
                                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(viewingPost.comments) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Izohlar</p>
                                    </div>
                                    <div v-if="viewingPost.shares">
                                        <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatNumber(viewingPost.shares) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Ulashishlar</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </BusinessLayout>
</template>

<script setup>
import { ref, computed, h } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'

const props = defineProps({
    posts: {
        type: Array,
        default: () => []
    }
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
const viewMode = ref('table') // 'table' or 'calendar'

// Calendar state
const currentDate = ref(new Date())
const calendarMonth = ref(new Date().getMonth())
const calendarYear = ref(new Date().getFullYear())
const selectedDay = ref(null)

// Weekday names in Uzbek
const weekDays = ['Dush', 'Sesh', 'Chor', 'Pay', 'Jum', 'Shan', 'Yak']

// Month names in Uzbek
const monthNames = [
    'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun',
    'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'
]

// Helper function to format date as YYYY-MM-DD in local timezone
const formatDateLocal = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

// Calendar computed properties
const calendarMonthName = computed(() => monthNames[calendarMonth.value])

const calendarDays = computed(() => {
    const year = calendarYear.value
    const month = calendarMonth.value
    const firstDay = new Date(year, month, 1)
    const lastDay = new Date(year, month + 1, 0)
    const daysInMonth = lastDay.getDate()

    // Get the day of week for the first day (0 = Sunday, adjust for Monday start)
    let startDay = firstDay.getDay() - 1
    if (startDay < 0) startDay = 6

    const days = []
    const today = new Date()
    today.setHours(0, 0, 0, 0)

    // Previous month days
    const prevMonthNum = month === 0 ? 11 : month - 1
    const prevYearNum = month === 0 ? year - 1 : year
    const daysInPrevMonth = new Date(prevYearNum, prevMonthNum + 1, 0).getDate()

    for (let i = startDay - 1; i >= 0; i--) {
        const date = daysInPrevMonth - i
        const fullDate = new Date(prevYearNum, prevMonthNum, date)
        days.push({
            date,
            fullDate: formatDateLocal(fullDate),
            isCurrentMonth: false,
            isToday: false,
            isWeekend: fullDate.getDay() === 0 || fullDate.getDay() === 6
        })
    }

    // Current month days
    for (let i = 1; i <= daysInMonth; i++) {
        const fullDate = new Date(year, month, i)
        const isToday = fullDate.getTime() === today.getTime()
        days.push({
            date: i,
            fullDate: formatDateLocal(fullDate),
            isCurrentMonth: true,
            isToday,
            isWeekend: fullDate.getDay() === 0 || fullDate.getDay() === 6
        })
    }

    // Next month days (fill to complete 6 rows = 42 cells)
    const remainingDays = 42 - days.length
    const nextMonthNum = month === 11 ? 0 : month + 1
    const nextYearNum = month === 11 ? year + 1 : year

    for (let i = 1; i <= remainingDays; i++) {
        const fullDate = new Date(nextYearNum, nextMonthNum, i)
        days.push({
            date: i,
            fullDate: formatDateLocal(fullDate),
            isCurrentMonth: false,
            isToday: false,
            isWeekend: fullDate.getDay() === 0 || fullDate.getDay() === 6
        })
    }

    return days
})

const getMonthScheduledCount = computed(() => {
    return props.posts.filter(post => {
        if (post.status !== 'scheduled' || !post.scheduled_at) return false
        const postDate = new Date(post.scheduled_at)
        return postDate.getMonth() === calendarMonth.value && postDate.getFullYear() === calendarYear.value
    }).length
})

const getMonthPublishedCount = computed(() => {
    return props.posts.filter(post => {
        if (post.status !== 'published') return false
        const postDate = new Date(post.scheduled_at || post.created_at)
        return postDate.getMonth() === calendarMonth.value && postDate.getFullYear() === calendarYear.value
    }).length
})

// Calendar methods
const prevMonth = () => {
    if (calendarMonth.value === 0) {
        calendarMonth.value = 11
        calendarYear.value--
    } else {
        calendarMonth.value--
    }
}

const nextMonth = () => {
    if (calendarMonth.value === 11) {
        calendarMonth.value = 0
        calendarYear.value++
    } else {
        calendarMonth.value++
    }
}

const goToToday = () => {
    const today = new Date()
    calendarMonth.value = today.getMonth()
    calendarYear.value = today.getFullYear()
}

const getPostsForDay = (dateStr) => {
    return props.posts.filter(post => {
        if (!post.scheduled_at) return false
        // Handle different date formats: "2026-01-10T14:00", "2026-01-10 14:00", "2026-01-10 14:00:00"
        let postDate = post.scheduled_at
        if (postDate.includes('T')) {
            postDate = postDate.split('T')[0]
        } else if (postDate.includes(' ')) {
            postDate = postDate.split(' ')[0]
        }
        return postDate === dateStr
    })
}

const getCalendarPostClass = (post) => {
    const baseClass = 'hover:opacity-80'
    // Check if overdue first
    if (isPostOverdue(post)) {
        return `${baseClass} bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200`
    }
    switch (post.status) {
        case 'scheduled':
            return `${baseClass} bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200`
        case 'published':
            return `${baseClass} bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200`
        case 'draft':
            return `${baseClass} bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300`
        default:
            return `${baseClass} bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300`
    }
}

// Get overdue posts count for a specific day
const getOverdueCountForDay = (dateStr) => {
    return getPostsForDay(dateStr).filter(post => isPostOverdue(post)).length
}

// Check if a day has any overdue posts
const dayHasOverduePosts = (dateStr) => {
    return getOverdueCountForDay(dateStr) > 0
}

// Selected day functionality
const selectDay = (day) => {
    if (selectedDay.value?.fullDate === day.fullDate) {
        selectedDay.value = null
    } else {
        selectedDay.value = day
    }
}

const selectedDayPosts = computed(() => {
    if (!selectedDay.value) return []
    return getPostsForDay(selectedDay.value.fullDate)
})

const formatSelectedDate = computed(() => {
    if (!selectedDay.value) return ''
    const [year, month, dayNum] = selectedDay.value.fullDate.split('-')
    const monthName = monthNames[parseInt(month) - 1]
    return `${parseInt(dayNum)} ${monthName} ${year}`
})

const getPostTime = (scheduledAt) => {
    if (!scheduledAt) return ''
    // Handle "2026-01-10 14:00" or "2026-01-10T14:00"
    if (scheduledAt.includes(' ')) {
        return scheduledAt.split(' ')[1]
    } else if (scheduledAt.includes('T')) {
        return scheduledAt.split('T')[1].substring(0, 5)
    }
    return ''
}

const postForm = ref({
    title: '',
    content: '',
    platforms: [],
    content_type: '',
    format: '',
    status: 'draft',
    scheduled_date: '',
    scheduled_time: '',
    scheduled_at: null,
    hashtags: []
})

// Minimum date for scheduling (today)
const minDate = computed(() => {
    const today = new Date()
    return today.toISOString().split('T')[0]
})

// Status tabs
const statusTabs = [
    { label: 'Barchasi', value: 'all' },
    { label: 'Qoralama', value: 'draft' },
    { label: 'Rejalashtirilgan', value: 'scheduled' },
    { label: 'Nashr qilingan', value: 'published' },
    { label: 'Muddati o\'tgan', value: 'overdue', class: 'text-red-600 dark:text-red-400' }
]

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

// Platform filters
const platformFilters = [
    { label: 'Barchasi', value: 'all', activeClass: 'bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800' },
    { label: 'Instagram', value: 'Instagram', icon: InstagramIcon, activeClass: 'bg-gradient-to-r from-purple-600 to-pink-600 text-white' },
    { label: 'Telegram', value: 'Telegram', icon: TelegramIcon, activeClass: 'bg-sky-600 text-white' },
    { label: 'Facebook', value: 'Facebook', icon: FacebookIcon, activeClass: 'bg-blue-600 text-white' },
    { label: 'YouTube', value: 'YouTube', icon: YouTubeIcon, activeClass: 'bg-red-600 text-white' },
    { label: 'Shorts', value: 'YouTube Shorts', icon: YouTubeShortsIcon, activeClass: 'bg-red-500 text-white' }
]

// Available platforms for multi-select
const availablePlatforms = [
    {
        value: 'Instagram',
        label: 'Instagram',
        icon: InstagramIcon,
        iconClass: 'text-pink-600 dark:text-pink-400',
        selectedClass: 'bg-gradient-to-r from-purple-600 to-pink-600 border-transparent'
    },
    {
        value: 'Telegram',
        label: 'Telegram',
        icon: TelegramIcon,
        iconClass: 'text-sky-600 dark:text-sky-400',
        selectedClass: 'bg-sky-600 border-transparent'
    },
    {
        value: 'Facebook',
        label: 'Facebook',
        icon: FacebookIcon,
        iconClass: 'text-blue-600 dark:text-blue-400',
        selectedClass: 'bg-blue-600 border-transparent'
    },
    {
        value: 'YouTube',
        label: 'YouTube',
        icon: YouTubeIcon,
        iconClass: 'text-red-600 dark:text-red-400',
        selectedClass: 'bg-red-600 border-transparent'
    },
    {
        value: 'YouTube Shorts',
        label: 'YouTube Shorts',
        icon: YouTubeShortsIcon,
        iconClass: 'text-red-500 dark:text-red-400',
        selectedClass: 'bg-red-500 border-transparent'
    }
]

// Content type filters
const contentTypeFilters = [
    { label: 'Barchasi', value: 'all', activeClass: 'bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800' },
    { label: "Ta'limiy", value: 'educational', activeClass: 'bg-blue-600 text-white' },
    { label: "Ko'ngil ochar", value: 'entertaining', activeClass: 'bg-pink-600 text-white' },
    { label: 'Ilhomlantiruvchi', value: 'inspirational', activeClass: 'bg-amber-600 text-white' },
    { label: 'Reklama', value: 'promotional', activeClass: 'bg-emerald-600 text-white' }
]

// Helper function to check if a post is overdue
const isPostOverdue = (post) => {
    if (post.status !== 'scheduled' || !post.scheduled_at) return false
    const now = new Date()
    let scheduledDate
    if (post.scheduled_at.includes('T')) {
        scheduledDate = new Date(post.scheduled_at)
    } else {
        // Handle "2026-01-10 14:00" format
        scheduledDate = new Date(post.scheduled_at.replace(' ', 'T'))
    }
    return scheduledDate < now
}

// Computed
const draftCount = computed(() => props.posts.filter(p => p.status === 'draft').length)
const scheduledCount = computed(() => props.posts.filter(p => p.status === 'scheduled' && !isPostOverdue(p)).length)
const publishedCount = computed(() => props.posts.filter(p => p.status === 'published').length)
const overdueCount = computed(() => props.posts.filter(p => isPostOverdue(p)).length)

const getContentTypeCount = (type) => {
    return props.posts.filter(p => p.content_type === type).length
}

const filteredPosts = computed(() => {
    let filtered = props.posts

    if (activeStatus.value !== 'all') {
        if (activeStatus.value === 'overdue') {
            // Filter overdue posts (scheduled but past due date)
            filtered = filtered.filter(post => isPostOverdue(post))
        } else if (activeStatus.value === 'scheduled') {
            // Show only scheduled posts that are NOT overdue
            filtered = filtered.filter(post => post.status === 'scheduled' && !isPostOverdue(post))
        } else {
            filtered = filtered.filter(post => post.status === activeStatus.value)
        }
    }

    if (activePlatform.value !== 'all') {
        filtered = filtered.filter(post => {
            const platforms = getPlatforms(post.platform)
            return platforms.includes(activePlatform.value)
        })
    }

    if (activeContentType.value !== 'all') {
        filtered = filtered.filter(post => post.content_type === activeContentType.value)
    }

    return filtered
})

// Platform helpers
const getPlatforms = (platform) => {
    if (!platform) return []
    // Check if it's a JSON array string
    if (typeof platform === 'string') {
        if (platform.startsWith('[')) {
            try {
                return JSON.parse(platform)
            } catch (e) {
                return [platform]
            }
        }
        return [platform]
    }
    // Already an array
    if (Array.isArray(platform)) return platform
    return [platform]
}

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

const getContentTypeDescription = (type) => {
    const descriptions = {
        educational: "Auditoriyangizga foydali ma'lumot, maslahatlar, qo'llanmalar va resurslar",
        entertaining: "Kulgili, qiziqarli va diqqatni tortuvchi kontent - memlar, trendlar",
        inspirational: "Motivatsion iqtiboslar, muvaffaqiyat hikoyalari, ijobiy his-tuyg'ular",
        promotional: "Mahsulot/xizmat reklama, chegirmalar, yangiliklar, mijoz sharhlari",
        behind_scenes: "Jamoangiz, ish jarayoni, ofis hayoti - brendingizni insoniylashtiradi",
        ugc: "Mijozlar tomonidan yaratilgan kontent - sharhlar, rasmlar, videolar"
    }
    return descriptions[type] || ''
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

const getContentTypeDotClass = (type) => {
    const classes = {
        educational: 'bg-blue-500',
        entertaining: 'bg-pink-500',
        inspirational: 'bg-amber-500',
        promotional: 'bg-emerald-500',
        behind_scenes: 'bg-purple-500',
        ugc: 'bg-orange-500'
    }
    return classes[type] || 'bg-gray-500'
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

// Status helpers
const getStatusLabel = (status, post = null) => {
    if (post && isPostOverdue(post)) return 'Muddati o\'tgan'
    const labels = { draft: 'Qoralama', scheduled: 'Rejalashtirilgan', published: 'Nashr qilingan' }
    return labels[status] || status
}

const getStatusBadgeClass = (status, post = null) => {
    if (post && isPostOverdue(post)) {
        return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
    }
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        scheduled: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        published: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
    }
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
}

const getStatusDotClass = (status, post = null) => {
    if (post && isPostOverdue(post)) return 'bg-red-500'
    const classes = {
        draft: 'bg-gray-400',
        scheduled: 'bg-amber-500',
        published: 'bg-emerald-500'
    }
    return classes[status] || 'bg-gray-400'
}

// Date/Time helpers
const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleDateString('uz-UZ', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatTime = (dateStr) => {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' })
}

// Helpers
const hasStats = (post) => post.views || post.likes || post.comments || post.shares

const formatNumber = (num) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
    return num?.toString() || '0'
}

// Modal handlers
const openCreateModal = () => {
    postForm.value = {
        title: '',
        content: '',
        platforms: [],
        content_type: '',
        format: '',
        status: 'draft',
        scheduled_date: '',
        scheduled_time: '',
        scheduled_at: null,
        hashtags: []
    }
    hashtagInput.value = ''
    showCreateModal.value = true
}

const closeCreateModal = () => {
    showCreateModal.value = false
}

const viewPost = (post) => {
    router.get(`/business/marketing/content/${post.id}`)
}

const closeViewModal = () => {
    showViewModal.value = false
    viewingPost.value = null
}

const submitPost = () => {
    if (postForm.value.platforms.length === 0) {
        alert('Kamida bitta platformani tanlang!')
        return
    }

    isSubmitting.value = true

    // Parse hashtags from input
    if (hashtagInput.value) {
        postForm.value.hashtags = hashtagInput.value.split(',').map(t => t.trim()).filter(t => t)
    }

    // Combine date and time into scheduled_at
    if (postForm.value.status === 'scheduled' && postForm.value.scheduled_date && postForm.value.scheduled_time) {
        postForm.value.scheduled_at = `${postForm.value.scheduled_date} ${postForm.value.scheduled_time}`
    } else {
        postForm.value.scheduled_at = null
    }

    // Map content_type to type for backend compatibility
    const formData = {
        ...postForm.value,
        platform: postForm.value.platforms, // Send platforms array as platform
        type: postForm.value.format // Backend uses 'type' for format
    }

    router.post('/business/marketing/content', formData, {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal()
            isSubmitting.value = false
        },
        onError: () => {
            isSubmitting.value = false
        }
    })
}

const deletePost = (id) => {
    if (confirm('Kontentni o\'chirishni xohlaysizmi?')) {
        router.delete(`/business/marketing/content/${id}`, {
            preserveScroll: true
        })
    }
}
</script>
