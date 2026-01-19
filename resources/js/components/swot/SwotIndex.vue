<template>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 rounded-2xl p-6 lg:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>

            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium mb-3">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            {{ t('swot.strategic_analysis') }}
                        </div>
                        <h1 class="text-2xl lg:text-4xl font-bold">{{ t('swot.title') }}</h1>
                        <p class="mt-2 text-white/80 text-lg max-w-2xl">
                            {{ t('swot.subtitle') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-2">
            <div class="flex flex-wrap gap-2">
                <button
                    @click="activeTab = 'business'"
                    :class="[
                        'flex items-center gap-2 px-5 py-3 rounded-xl font-medium transition-all',
                        activeTab === 'business'
                            ? 'bg-emerald-600 text-white shadow-lg'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    {{ t('swot.my_business') }}
                    <span v-if="getTotalSwotItems(businessSwotData) > 0" class="px-2 py-0.5 bg-white/20 rounded-full text-xs">
                        {{ getTotalSwotItems(businessSwotData) }}
                    </span>
                </button>

                <button
                    v-for="competitor in competitorsList"
                    :key="competitor.id"
                    @click="activeTab = `competitor-${competitor.id}`"
                    :class="[
                        'flex items-center gap-2 px-5 py-3 rounded-xl font-medium transition-all',
                        activeTab === `competitor-${competitor.id}`
                            ? 'bg-orange-600 text-white shadow-lg'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]"
                >
                    <span
                        class="w-2 h-2 rounded-full"
                        :class="{
                            'bg-red-500': competitor.threat_level === 'critical',
                            'bg-orange-500': competitor.threat_level === 'high',
                            'bg-yellow-500': competitor.threat_level === 'medium',
                            'bg-green-500': competitor.threat_level === 'low',
                        }"
                    ></span>
                    {{ competitor.name }}
                    <span v-if="getTotalSwotItems(competitor.swot_data) > 0" class="px-2 py-0.5 bg-white/20 rounded-full text-xs">
                        {{ getTotalSwotItems(competitor.swot_data) }}
                    </span>
                </button>

                <Link
                    :href="getHref('competitors')"
                    class="flex items-center gap-2 px-5 py-3 rounded-xl font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('swot.add_competitor') }}
                </Link>
            </div>
        </div>

        <!-- Business SWOT Tab -->
        <div v-if="activeTab === 'business'" class="space-y-6">
            <!-- Business Info Header -->
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-2xl border border-emerald-200 dark:border-emerald-800 p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-emerald-600 rounded-xl flex items-center justify-center text-white text-xl font-bold">
                            {{ currentBusiness?.name?.substring(0, 2).toUpperCase() }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ currentBusiness?.name }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('swot.business_swot') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div v-if="lastUpdated" class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-medium">{{ t('swot.last_update') }}:</span> {{ formatDate(lastUpdated) }}
                        </div>
                        <button
                            @click="generateBusinessSwot"
                            :disabled="generatingBusiness"
                            class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50"
                        >
                            <svg v-if="generatingBusiness" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            {{ generatingBusiness ? t('swot.ai_analyzing') : t('swot.ai_analyze') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Business SWOT Grid -->
            <SwotGrid :swot="businessSwotData" :is-business="true" :current-business-id="currentBusiness?.id" @update="handleBusinessSwotUpdate" />

            <!-- Save Button for Business -->
            <div v-if="businessSwotChanged" class="flex justify-end">
                <button
                    @click="saveBusinessSwot"
                    :disabled="savingBusiness"
                    class="inline-flex items-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50 shadow-lg"
                >
                    <svg v-if="savingBusiness" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ savingBusiness ? t('swot.saving') : t('swot.save_changes') }}
                </button>
            </div>

            <!-- Business Recommendations -->
            <div v-if="businessSwotData?.recommendations?.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ t('swot.ai_recommendations') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('swot.business_recommendations') }}</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="(rec, index) in businessSwotData.recommendations"
                        :key="'rec-' + index"
                        class="flex items-start gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl"
                    >
                        <span class="flex-shrink-0 w-8 h-8 bg-purple-500 text-white rounded-lg flex items-center justify-center text-sm font-bold">
                            {{ index + 1 }}
                        </span>
                        <p class="text-gray-700 dark:text-gray-300">{{ rec }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Competitor SWOT Tab -->
        <div v-for="competitor in competitorsList" :key="'tab-' + competitor.id" v-show="activeTab === `competitor-${competitor.id}`" class="space-y-6">
            <!-- Competitor Info Header -->
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-2xl border border-orange-200 dark:border-orange-800 p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center text-white text-xl font-bold">
                            {{ competitor.name.substring(0, 2).toUpperCase() }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ competitor.name }}</h2>
                                <span
                                    :class="getThreatBadgeClass(competitor.threat_level)"
                                    class="px-2 py-0.5 text-xs font-medium rounded-full"
                                >
                                    {{ getThreatLevelText(competitor.threat_level) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('swot.competitor_swot') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div v-if="competitor.swot_analyzed_at" class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-medium">{{ t('swot.analysis') }}:</span> {{ formatDate(competitor.swot_analyzed_at) }}
                        </div>
                        <button
                            @click="generateCompetitorSwot(competitor)"
                            :disabled="generatingCompetitorId === competitor.id"
                            class="inline-flex items-center px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50"
                        >
                            <svg v-if="generatingCompetitorId === competitor.id" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            {{ generatingCompetitorId === competitor.id ? t('swot.ai_analyzing') : t('swot.ai_analyze') }}
                        </button>
                        <Link
                            :href="getHref('competitor', competitor.id)"
                            class="inline-flex items-center px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ t('swot.view_profile') }}
                        </Link>
                    </div>
                </div>

                <!-- Competitor Social Handles -->
                <div v-if="competitor.instagram_handle || competitor.telegram_handle" class="mt-4 pt-4 border-t border-orange-200 dark:border-orange-800">
                    <div class="flex flex-wrap gap-3">
                        <a
                            v-if="competitor.instagram_handle"
                            :href="`https://instagram.com/${competitor.instagram_handle.replace('@', '')}`"
                            target="_blank"
                            class="inline-flex items-center px-3 py-1.5 bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400 rounded-lg text-sm hover:bg-pink-200 dark:hover:bg-pink-900/50 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                            {{ competitor.instagram_handle }}
                        </a>
                        <a
                            v-if="competitor.telegram_handle"
                            :href="`https://t.me/${competitor.telegram_handle.replace('@', '')}`"
                            target="_blank"
                            class="inline-flex items-center px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg text-sm hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                            {{ competitor.telegram_handle }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Global Data Notice -->
            <div v-if="competitor.global_contributors > 0 && !competitor.swot_data" class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-indigo-800 dark:text-indigo-300">{{ t('swot.global_data') }}</p>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">
                        {{ t('swot.global_data_desc', { count: competitor.global_contributors }) }}
                        {{ t('swot.global_swot_info') }}
                    </p>
                </div>
            </div>

            <!-- Competitor SWOT Grid -->
            <SwotGrid :swot="getCompetitorSwotData(competitor)" :is-business="false" :current-business-id="currentBusiness?.id" @update="(data) => handleCompetitorSwotUpdate(competitor.id, data)" />

            <!-- Auto-save Status -->
            <div class="flex justify-end items-center gap-3">
                <div v-if="autoSavingCompetitors[competitor.id]" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ t('swot.saving') }}</span>
                </div>
                <div v-else-if="savedCompetitors[competitor.id]" class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ t('swot.saved') }}</span>
                </div>
            </div>

            <!-- Save Button for Competitor (manual) -->
            <div v-if="competitorChanges[competitor.id] && !autoSavingCompetitors[competitor.id]" class="flex justify-end">
                <button
                    @click="saveCompetitorSwot(competitor)"
                    :disabled="savingCompetitorId === competitor.id"
                    class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50 shadow-lg"
                >
                    <svg v-if="savingCompetitorId === competitor.id" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ savingCompetitorId === competitor.id ? t('swot.saving') : t('swot.save_changes') }}
                </button>
            </div>

            <!-- Comparison with Your Business -->
            <div v-if="businessSwotData?.strengths?.length && competitor.swot_data?.weaknesses?.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ t('swot.comparison') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('swot.comparison_desc', { name: competitor.name }) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Your Advantages -->
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                        <h3 class="font-semibold text-green-800 dark:text-green-300 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ t('swot.your_advantages') }}
                        </h3>
                        <ul class="space-y-2">
                            <li v-for="(strength, idx) in businessSwotData.strengths?.slice(0, 3)" :key="idx" class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <span class="text-green-500 mt-0.5">✓</span>
                                {{ strength }}
                            </li>
                        </ul>
                    </div>

                    <!-- Competitor Weaknesses (Your Opportunities) -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            {{ t('swot.competitor_weaknesses') }}
                        </h3>
                        <ul class="space-y-2">
                            <li v-for="(weakness, idx) in competitor.swot_data?.weaknesses?.slice(0, 3)" :key="idx" class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <span class="text-blue-500 mt-0.5">→</span>
                                {{ weakness }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State when no competitors -->
        <div v-if="competitorsList.length === 0 && activeTab === 'business'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="w-16 h-16 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ t('swot.add_competitors_title') }}</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                {{ t('swot.add_competitors_desc') }}
            </p>
            <Link
                :href="getHref('competitors')"
                class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-500 text-white font-semibold rounded-xl transition-colors"
            >
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ t('swot.add_competitor') }}
            </Link>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from '@/i18n';
import SwotGrid from './SwotGrid.vue';

const { t } = useI18n();

const props = defineProps({
    currentBusiness: {
        type: Object,
        required: true,
    },
    swot: {
        type: Object,
        default: () => ({}),
    },
    competitorCount: {
        type: Number,
        default: 0,
    },
    lastUpdated: {
        type: String,
        default: null,
    },
    competitors: {
        type: Array,
        default: () => [],
    },
    panelType: {
        type: String,
        required: true,
        validator: (v) => ['business', 'marketing'].includes(v),
    },
});

const activeTab = ref('business');
const generatingBusiness = ref(false);
const generatingCompetitorId = ref(null);
const savingBusiness = ref(false);
const savingCompetitorId = ref(null);
const businessSwotChanged = ref(false);
const competitorChanges = ref({});
const autoSavingCompetitors = ref({});
const savedCompetitors = ref({});

// Reactive copy of competitors to update swot_data
const competitorsList = ref([...props.competitors]);

const businessSwotData = ref({
    strengths: props.swot?.strengths || [],
    weaknesses: props.swot?.weaknesses || [],
    opportunities: props.swot?.opportunities || [],
    threats: props.swot?.threats || [],
    recommendations: props.swot?.recommendations || [],
});

const getHref = (page, id = null) => {
    const prefix = props.panelType === 'business' ? '/business' : '/marketing';

    const routes = {
        competitors: `${prefix}/competitors`,
        competitor: `${prefix}/competitors/${id}`,
    };

    return routes[page] || prefix;
};

const getTotalSwotItems = (swot) => {
    if (!swot) return 0;
    return (swot.strengths?.length || 0) +
           (swot.weaknesses?.length || 0) +
           (swot.opportunities?.length || 0) +
           (swot.threats?.length || 0);
};

const getThreatLevelText = (level) => {
    return t(`competitors.modal.${level}`) || level;
};

const getThreatBadgeClass = (level) => {
    const classes = {
        low: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        medium: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        high: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
        critical: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    };
    return classes[level] || classes.medium;
};

const generateBusinessSwot = async () => {
    generatingBusiness.value = true;
    const prefix = props.panelType === 'business' ? '/business' : '/marketing';

    try {
        const response = await axios.post(`${prefix}/swot/generate`);

        if (response.data.swot) {
            businessSwotData.value = response.data.swot;
        }
    } catch (error) {
        console.error('Business SWOT generation failed:', error);
        alert(t('swot.error_business_swot'));
    } finally {
        generatingBusiness.value = false;
    }
};

const generateCompetitorSwot = async (competitor) => {
    generatingCompetitorId.value = competitor.id;
    const prefix = props.panelType === 'business' ? '/business' : '/marketing';

    try {
        const response = await axios.post(`${prefix}/competitors/${competitor.id}/swot/generate`);

        if (response.data.swot) {
            // Update the competitor's swot_data
            const idx = competitorsList.value.findIndex(c => c.id === competitor.id);
            if (idx !== -1) {
                competitorsList.value[idx].swot_data = response.data.swot;
                competitorsList.value[idx].swot_analyzed_at = new Date().toISOString();
            }
        }
    } catch (error) {
        console.error('Competitor SWOT generation failed:', error);
        alert(t('swot.error_competitor_swot'));
    } finally {
        generatingCompetitorId.value = null;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('uz-UZ', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

// Get competitor SWOT data - prefer local, fall back to global
const getCompetitorSwotData = (competitor) => {
    // First check if we have local changes in our list
    const localCompetitor = competitorsList.value.find(c => c.id === competitor.id);
    if (localCompetitor?.swot_data && Object.keys(localCompetitor.swot_data).length > 0) {
        return localCompetitor.swot_data;
    }

    // Fall back to effective_swot_data from server (includes global)
    if (competitor.effective_swot_data) {
        return competitor.effective_swot_data;
    }

    return {
        strengths: [],
        weaknesses: [],
        opportunities: [],
        threats: [],
    };
};

// Handle manual SWOT updates
const handleBusinessSwotUpdate = (newSwotData) => {
    businessSwotData.value = { ...newSwotData };
    businessSwotChanged.value = true;

    // Auto-save after a short delay
    autoSaveBusinessSwot(newSwotData);
};

// Business auto-save timer
const businessAutoSaveTimer = ref(null);

const autoSaveBusinessSwot = (swotData) => {
    // Clear existing timer
    if (businessAutoSaveTimer.value) {
        clearTimeout(businessAutoSaveTimer.value);
    }

    // Set new timer - save after 1 second of no changes
    businessAutoSaveTimer.value = setTimeout(async () => {
        const prefix = props.panelType === 'business' ? '/business' : '/marketing';

        try {
            await axios.put(`${prefix}/swot`, {
                swot_data: swotData,
            });
            businessSwotChanged.value = false;
            console.log('Business SWOT auto-saved');
        } catch (error) {
            console.error('Business SWOT auto-save failed:', error);
        }
    }, 1000);
};

const handleCompetitorSwotUpdate = (competitorId, newSwotData) => {
    const idx = competitorsList.value.findIndex(c => c.id === competitorId);
    if (idx !== -1) {
        competitorsList.value[idx].swot_data = { ...newSwotData };
        competitorChanges.value[competitorId] = true;

        // Auto-save after a short delay
        autoSaveCompetitorSwot(competitorId, newSwotData);
    }
};

// Debounce timers for auto-save
const autoSaveTimers = ref({});

const autoSaveCompetitorSwot = (competitorId, swotData) => {
    // Clear existing timer
    if (autoSaveTimers.value[competitorId]) {
        clearTimeout(autoSaveTimers.value[competitorId]);
    }

    // Clear saved status
    delete savedCompetitors.value[competitorId];

    // Set new timer - save after 1 second of no changes
    autoSaveTimers.value[competitorId] = setTimeout(async () => {
        const prefix = props.panelType === 'business' ? '/business' : '/marketing';

        // Show saving indicator
        autoSavingCompetitors.value[competitorId] = true;

        try {
            await axios.put(`${prefix}/competitors/${competitorId}/swot`, {
                swot_data: swotData,
            });
            delete competitorChanges.value[competitorId];

            // Show saved indicator
            savedCompetitors.value[competitorId] = true;

            // Clear saved indicator after 3 seconds
            setTimeout(() => {
                delete savedCompetitors.value[competitorId];
            }, 3000);
        } catch (error) {
            console.error('Auto-save failed:', error);
        } finally {
            delete autoSavingCompetitors.value[competitorId];
        }
    }, 1000);
};

// Save functions
const saveBusinessSwot = async () => {
    savingBusiness.value = true;
    const prefix = props.panelType === 'business' ? '/business' : '/marketing';

    try {
        await axios.put(`${prefix}/swot`, {
            swot_data: businessSwotData.value,
        });
        businessSwotChanged.value = false;
    } catch (error) {
        console.error('Business SWOT save failed:', error);
        alert(t('swot.error_save_business'));
    } finally {
        savingBusiness.value = false;
    }
};

const saveCompetitorSwot = async (competitor) => {
    savingCompetitorId.value = competitor.id;
    const prefix = props.panelType === 'business' ? '/business' : '/marketing';

    // Get the updated SWOT data from our local list
    const localCompetitor = competitorsList.value.find(c => c.id === competitor.id);
    const swotData = localCompetitor?.swot_data || competitor.swot_data || {};

    try {
        await axios.put(`${prefix}/competitors/${competitor.id}/swot`, {
            swot_data: swotData,
        });
        delete competitorChanges.value[competitor.id];
    } catch (error) {
        console.error('Competitor SWOT save failed:', error);
        alert(t('swot.error_save_competitor'));
    } finally {
        savingCompetitorId.value = null;
    }
};
</script>
