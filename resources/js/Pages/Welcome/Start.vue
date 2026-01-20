<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <span class="text-xl font-bold text-gray-900">BiznesPilot AI</span>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">{{ $page.props.auth?.user?.name }}</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="py-12">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Success Message -->
        <div v-if="$page.props.flash?.success" class="mb-8 bg-green-50 border border-green-200 rounded-xl p-4">
          <div class="flex items-start">
            <svg class="w-6 h-6 text-green-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <h3 class="text-green-900 font-semibold">{{ $page.props.flash.success }}</h3>
              <p class="text-green-700 text-sm mt-1">{{ t('start.success_message') }}</p>
            </div>
          </div>
        </div>

        <!-- Welcome Header -->
        <div class="text-center mb-12">
          <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl mb-6">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
          </div>
          <h1 class="text-4xl font-bold text-gray-900 mb-4">
            {{ t('start.welcome') }}, {{ business.name }}!
          </h1>
          <p class="text-lg text-gray-600 max-w-2xl mx-auto">
            {{ t('start.subtitle') }}
          </p>
        </div>

        <!-- Action Cards -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">

          <!-- Card 1: Start Working -->
          <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden hover:shadow-2xl transition-all duration-300">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
              <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <h2 class="text-xl font-bold text-white">{{ t('start.new_business.title') }}</h2>
              </div>
            </div>

            <div class="p-6">
              <div class="mb-6">
                <p class="text-gray-600 mb-4">
                  {{ t('start.new_business.desc') }}
                </p>

                <div class="space-y-3 mb-6">
                  <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-sm text-gray-700">{{ t('start.new_business.feature1') }}</span>
                  </div>
                  <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-sm text-gray-700">{{ t('start.new_business.feature2') }}</span>
                  </div>
                  <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-sm text-gray-700">{{ t('start.new_business.feature3') }}</span>
                  </div>
                </div>
              </div>

              <Link
                :href="route('business.dashboard')"
                class="block w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold py-4 px-6 rounded-xl hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition-all duration-150 shadow-lg hover:shadow-xl text-center"
              >
                <span class="flex items-center justify-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                  </svg>
                  {{ t('start.new_business.button') }}
                </span>
              </Link>
            </div>
          </div>

          <!-- Card 2: Run Diagnostics -->
          <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden hover:shadow-2xl transition-all duration-300">
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
              <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <h2 class="text-xl font-bold text-white">{{ t('start.diagnostics.title') }}</h2>
              </div>
            </div>

            <div class="p-6">
              <div class="mb-6">
                <p class="text-gray-600 mb-4">
                  {{ t('start.diagnostics.desc') }}
                </p>

                <div class="space-y-3 mb-6">
                  <div class="flex items-start">
                    <svg class="w-5 h-5 text-emerald-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span class="text-sm text-gray-700">{{ t('start.diagnostics.feature1') }}</span>
                  </div>
                  <div class="flex items-start">
                    <svg class="w-5 h-5 text-emerald-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <span class="text-sm text-gray-700">{{ t('start.diagnostics.feature2') }}</span>
                  </div>
                  <div class="flex items-start">
                    <svg class="w-5 h-5 text-emerald-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="text-sm text-gray-700">{{ t('start.diagnostics.feature3') }}</span>
                  </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                  <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs text-yellow-800">
                      <strong>{{ t('start.diagnostics.note_title') }}:</strong> {{ t('start.diagnostics.note_text') }}
                    </p>
                  </div>
                </div>
              </div>

              <Link
                :href="route('onboarding.index')"
                class="block w-full bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold py-4 px-6 rounded-xl hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transform transition-all duration-150 shadow-lg hover:shadow-xl text-center"
              >
                <span class="flex items-center justify-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  {{ t('start.diagnostics.button') }}
                </span>
              </Link>
            </div>
          </div>

        </div>

        <!-- Additional Info -->
        <div class="bg-white/60 backdrop-blur-sm rounded-xl border border-gray-200 p-6">
          <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <h3 class="text-gray-900 font-semibold mb-2">{{ t('start.help.title') }}</h3>
              <ul class="text-sm text-gray-600 space-y-2">
                <li class="flex items-start">
                  <span class="inline-block w-2 h-2 bg-blue-600 rounded-full mr-2 mt-1.5 flex-shrink-0"></span>
                  <span><strong>{{ t('start.help.option1_title') }}:</strong> {{ t('start.help.option1_desc') }}</span>
                </li>
                <li class="flex items-start">
                  <span class="inline-block w-2 h-2 bg-emerald-600 rounded-full mr-2 mt-1.5 flex-shrink-0"></span>
                  <span><strong>{{ t('start.help.option2_title') }}:</strong> {{ t('start.help.option2_desc') }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  business: {
    type: Object,
    required: true,
  },
});
</script>
