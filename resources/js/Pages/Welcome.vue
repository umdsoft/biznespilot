<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50">
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
            <Link
              href="/logout"
              method="post"
              as="button"
              class="text-sm text-gray-500 hover:text-gray-700"
            >
              {{ t('common.logout') }}
            </Link>
          </div>
        </div>
      </div>
    </header>

    <!-- Hero Section - Problem/Solution -->
    <section class="relative py-16 lg:py-20 overflow-hidden">
      <!-- Background decoration -->
      <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-96 h-96 bg-blue-200 rounded-full opacity-20 blur-3xl"></div>
      <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-96 h-96 bg-indigo-200 rounded-full opacity-20 blur-3xl"></div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-4xl mx-auto">
          <!-- Welcome badge -->
          <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-700 rounded-full text-sm font-medium mb-6 animate-pulse">
            <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-ping"></span>
            <span class="w-2 h-2 bg-green-500 rounded-full mr-2 absolute"></span>
            {{ t('welcome.account_ready') }}
          </div>

          <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-6 leading-tight">
            {{ t('welcome.greeting') }}, <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">{{ $page.props.auth?.user?.name?.split(' ')[0] }}</span>!
          </h1>

          <!-- Problem statement -->
          <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-6 mb-8 border border-gray-200/50">
            <p class="text-lg text-gray-600 leading-relaxed">
              <span class="font-semibold text-gray-800">{{ t('welcome.problem_statement_1') }}</span> {{ t('welcome.problem_statement_2') }} <span class="text-red-500 font-medium">{{ t('welcome.problem_statement_3') }}</span> {{ t('welcome.problem_statement_4') }}
              <br class="hidden md:block" />
              BiznesPilot AI <span class="text-blue-600 font-semibold">{{ t('welcome.solution_1') }}</span> {{ t('welcome.solution_2') }} <span class="text-emerald-600 font-semibold">{{ t('welcome.solution_3') }}</span> {{ t('welcome.solution_4') }}
            </p>
          </div>

          <!-- CTA Buttons -->
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link
              href="/welcome/create-business"
              class="group inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transform transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl"
            >
              <svg class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              {{ t('welcome.create_business') }}
              <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </Link>
            <span class="text-sm text-gray-500">{{ t('welcome.ready_in_2_min') }}</span>
          </div>
        </div>

        <!-- Trust indicators -->
        <div class="mt-12 flex flex-wrap items-center justify-center gap-8 text-gray-400">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm text-gray-600">{{ t('welcome.no_credit_card') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm text-gray-600">{{ t('welcome.free_trial_14_days') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm text-gray-600">{{ t('welcome.cancel_anytime') }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-white border-y border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
          <div class="text-center">
            <div class="text-3xl lg:text-4xl font-bold text-blue-600 mb-1">40%</div>
            <div class="text-sm text-gray-600">{{ t('welcome.stats.sales_growth') }}</div>
          </div>
          <div class="text-center">
            <div class="text-3xl lg:text-4xl font-bold text-emerald-600 mb-1">10x</div>
            <div class="text-sm text-gray-600">{{ t('welcome.stats.marketing_efficiency') }}</div>
          </div>
          <div class="text-center">
            <div class="text-3xl lg:text-4xl font-bold text-purple-600 mb-1">24/7</div>
            <div class="text-sm text-gray-600">{{ t('welcome.stats.ai_chatbot') }}</div>
          </div>
          <div class="text-center">
            <div class="text-3xl lg:text-4xl font-bold text-amber-600 mb-1">50+</div>
            <div class="text-sm text-gray-600">{{ t('welcome.stats.happy_businesses') }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Problem/Pain Points Section -->
    <section class="py-16 bg-gradient-to-b from-white to-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-3 py-1 bg-red-100 text-red-600 rounded-full text-sm font-medium mb-4">{{ t('welcome.familiar_problems') }}</span>
          <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ t('welcome.problems_familiar') }}</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
          <div class="bg-white rounded-xl p-6 border border-red-100 hover:border-red-200 transition-colors">
            <div class="w-12 h-12 bg-red-100 text-red-500 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ t('welcome.pain_points.no_time.title') }}</h3>
            <p class="text-gray-600 text-sm">{{ t('welcome.pain_points.no_time.desc') }}</p>
          </div>

          <div class="bg-white rounded-xl p-6 border border-red-100 hover:border-red-200 transition-colors">
            <div class="w-12 h-12 bg-red-100 text-red-500 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ t('welcome.pain_points.no_customers.title') }}</h3>
            <p class="text-gray-600 text-sm">{{ t('welcome.pain_points.no_customers.desc') }}</p>
          </div>

          <div class="bg-white rounded-xl p-6 border border-red-100 hover:border-red-200 transition-colors">
            <div class="w-12 h-12 bg-red-100 text-red-500 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ t('welcome.pain_points.no_results.title') }}</h3>
            <p class="text-gray-600 text-sm">{{ t('welcome.pain_points.no_results.desc') }}</p>
          </div>
        </div>

        <!-- Solution Arrow -->
        <div class="text-center mb-12">
          <div class="inline-flex flex-col items-center">
            <svg class="w-8 h-8 text-blue-500 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
            <span class="mt-2 text-blue-600 font-semibold">{{ t('welcome.solution_all') }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-sm font-medium mb-4">{{ t('welcome.how_it_works') }}</span>
          <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ t('welcome.steps_to_success') }}</h2>
          <p class="text-lg text-gray-600">{{ t('welcome.no_tech_required') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <!-- Step 1 -->
          <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl blur opacity-0 group-hover:opacity-25 transition duration-300"></div>
            <div class="relative bg-white rounded-2xl p-6 border border-gray-200 h-full">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-lg">
                  1
                </div>
                <div class="ml-3 h-1 flex-1 bg-gradient-to-r from-blue-200 to-transparent rounded hidden lg:block"></div>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ t('welcome.steps.step1.title') }}</h3>
              <p class="text-gray-600 text-sm mb-4">
                {{ t('welcome.steps.step1.desc') }}
              </p>
              <div class="flex items-center text-blue-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ t('welcome.steps.step1.time') }}
              </div>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl blur opacity-0 group-hover:opacity-25 transition duration-300"></div>
            <div class="relative bg-white rounded-2xl p-6 border border-gray-200 h-full">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-lg">
                  2
                </div>
                <div class="ml-3 h-1 flex-1 bg-gradient-to-r from-emerald-200 to-transparent rounded hidden lg:block"></div>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ t('welcome.steps.step2.title') }}</h3>
              <p class="text-gray-600 text-sm mb-4">
                {{ t('welcome.steps.step2.desc') }}
              </p>
              <div class="flex gap-2 mt-auto">
                <span class="px-2 py-1 bg-pink-100 text-pink-600 rounded text-xs font-medium">Instagram</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-xs font-medium">Telegram</span>
              </div>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-purple-600 to-violet-600 rounded-2xl blur opacity-0 group-hover:opacity-25 transition duration-300"></div>
            <div class="relative bg-white rounded-2xl p-6 border border-gray-200 h-full">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-lg">
                  3
                </div>
                <div class="ml-3 h-1 flex-1 bg-gradient-to-r from-purple-200 to-transparent rounded hidden lg:block"></div>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ t('welcome.steps.step3.title') }}</h3>
              <p class="text-gray-600 text-sm mb-4">
                {{ t('welcome.steps.step3.desc') }}
              </p>
              <div class="flex items-center text-purple-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                AI Powered
              </div>
            </div>
          </div>

          <!-- Step 4 -->
          <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-amber-600 to-orange-600 rounded-2xl blur opacity-0 group-hover:opacity-25 transition duration-300"></div>
            <div class="relative bg-white rounded-2xl p-6 border border-gray-200 h-full">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-lg">
                  4
                </div>
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ t('welcome.steps.step4.title') }}</h3>
              <p class="text-gray-600 text-sm mb-4">
                {{ t('welcome.steps.step4.desc') }}
              </p>
              <div class="flex items-center text-amber-600 text-sm font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                Real-time Analytics
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section with Benefits -->
    <section class="py-16 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-600 rounded-full text-sm font-medium mb-4">{{ t('welcome.what_you_get') }}</span>
          <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ t('welcome.everything_for_growth') }}</h2>
          <p class="text-lg text-gray-600">{{ t('welcome.all_tools_one_platform') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Feature 1 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-blue-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ t('welcome.features.ideal_customer.title') }}</h3>
            <p class="text-gray-600 mb-4">{{ t('welcome.features.ideal_customer.desc') }}</p>
            <div class="flex items-center text-blue-600 font-medium text-sm">
              <span class="mr-2">{{ t('welcome.features.result') }}:</span>
              <span class="text-emerald-600">{{ t('welcome.features.ideal_customer.result') }}</span>
            </div>
          </div>

          <!-- Feature 2 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-emerald-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ t('welcome.features.ai_chatbots.title') }}</h3>
            <p class="text-gray-600 mb-4">{{ t('welcome.features.ai_chatbots.desc') }}</p>
            <div class="flex items-center text-emerald-600 font-medium text-sm">
              <span class="mr-2">{{ t('welcome.features.result') }}:</span>
              <span class="text-emerald-600">{{ t('welcome.features.ai_chatbots.result') }}</span>
            </div>
          </div>

          <!-- Feature 3 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-purple-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ t('welcome.features.marketing_analytics.title') }}</h3>
            <p class="text-gray-600 mb-4">{{ t('welcome.features.marketing_analytics.desc') }}</p>
            <div class="flex items-center text-purple-600 font-medium text-sm">
              <span class="mr-2">{{ t('welcome.features.result') }}:</span>
              <span class="text-emerald-600">{{ t('welcome.features.marketing_analytics.result') }}</span>
            </div>
          </div>

          <!-- Feature 4 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-amber-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ t('welcome.features.marketing_planning.title') }}</h3>
            <p class="text-gray-600 mb-4">{{ t('welcome.features.marketing_planning.desc') }}</p>
            <div class="flex items-center text-amber-600 font-medium text-sm">
              <span class="mr-2">{{ t('welcome.features.result') }}:</span>
              <span class="text-emerald-600">{{ t('welcome.features.marketing_planning.result') }}</span>
            </div>
          </div>

          <!-- Feature 5 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-rose-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-rose-500 to-rose-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ t('welcome.features.crm_sales.title') }}</h3>
            <p class="text-gray-600 mb-4">{{ t('welcome.features.crm_sales.desc') }}</p>
            <div class="flex items-center text-rose-600 font-medium text-sm">
              <span class="mr-2">{{ t('welcome.features.result') }}:</span>
              <span class="text-emerald-600">{{ t('welcome.features.crm_sales.result') }}</span>
            </div>
          </div>

          <!-- Feature 6 -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg hover:border-indigo-200 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ t('welcome.features.auto_reports.title') }}</h3>
            <p class="text-gray-600 mb-4">{{ t('welcome.features.auto_reports.desc') }}</p>
            <div class="flex items-center text-indigo-600 font-medium text-sm">
              <span class="mr-2">{{ t('welcome.features.result') }}:</span>
              <span class="text-emerald-600">{{ t('welcome.features.auto_reports.result') }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- All-in-One Platform Section -->
    <section class="py-20 bg-gradient-to-b from-white via-slate-50 to-white overflow-hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-16">
          <div class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-full text-sm font-medium text-blue-700 mb-8 shadow-sm">
            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span class="mr-1">✨</span>
            {{ t('welcome.platform.badge') }}
          </div>
          <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-6 leading-tight">
            {{ t('welcome.platform.title') }}
          </h2>
          <p class="text-xl text-gray-600">
            {{ t('welcome.platform.subtitle') }}
          </p>
        </div>

        <!-- 6 Module Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Card 1: Marketing Automation -->
          <div class="relative bg-white rounded-3xl p-8 shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500 group overflow-hidden">
            <!-- Badge -->
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-sm font-semibold rounded-full shadow-lg">
              {{ t('welcome.modules.marketing.badge') }}
            </div>
            <!-- Icon -->
            <div class="mt-6 mb-6">
              <svg class="w-16 h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="8" y="24" width="8" height="32" rx="2" fill="#60A5FA"/>
                <rect x="20" y="16" width="8" height="40" rx="2" fill="#3B82F6"/>
                <rect x="32" y="8" width="8" height="48" rx="2" fill="#2563EB"/>
                <path d="M52 8L58 14L52 20" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M44 14H58" stroke="#10B981" stroke-width="3" stroke-linecap="round"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
              <span class="mr-2">📈</span>
              {{ t('welcome.modules.marketing.title') }}
            </h3>
            <p class="text-gray-600 leading-relaxed">
              {{ t('welcome.modules.marketing.desc') }}
            </p>
          </div>

          <!-- Card 2: Sotuv va CRM -->
          <div class="relative bg-white rounded-3xl p-8 shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500 group overflow-hidden">
            <!-- Badge -->
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1.5 bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold rounded-full shadow-lg">
              {{ t('welcome.modules.sales.badge') }}
            </div>
            <!-- Icon -->
            <div class="mt-6 mb-6">
              <svg class="w-16 h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="24" cy="20" r="8" fill="#10B981"/>
                <circle cx="40" cy="20" r="8" fill="#34D399"/>
                <circle cx="32" cy="36" r="8" fill="#6EE7B7"/>
                <path d="M16 52C16 44 23 40 32 40C41 40 48 44 48 52" stroke="#10B981" stroke-width="4" stroke-linecap="round"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
              <span class="mr-2">💰</span>
              {{ t('welcome.modules.sales.title') }}
            </h3>
            <p class="text-gray-600 leading-relaxed">
              {{ t('welcome.modules.sales.desc') }}
            </p>
          </div>

          <!-- Card 3: Moliya Boshqaruvi -->
          <div class="relative bg-white rounded-3xl p-8 shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500 group overflow-hidden">
            <!-- Badge -->
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1.5 bg-gradient-to-r from-red-500 to-pink-500 text-white text-sm font-semibold rounded-full shadow-lg">
              {{ t('welcome.modules.finance.badge') }}
            </div>
            <!-- Icon -->
            <div class="mt-6 mb-6">
              <svg class="w-16 h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="12" y="8" width="40" height="48" rx="4" fill="#8B5CF6" opacity="0.2"/>
                <rect x="16" y="12" width="32" height="40" rx="2" fill="#8B5CF6"/>
                <circle cx="32" cy="28" r="10" fill="#A78BFA"/>
                <text x="32" y="32" text-anchor="middle" fill="white" font-size="12" font-weight="bold">$</text>
                <path d="M24 44H40" stroke="white" stroke-width="2" stroke-linecap="round"/>
                <circle cx="44" cy="12" r="6" fill="#22C55E"/>
                <path d="M42 12L43.5 13.5L46 11" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
              <span class="mr-2">🧾</span>
              {{ t('welcome.modules.finance.title') }}
            </h3>
            <p class="text-gray-600 leading-relaxed">
              {{ t('welcome.modules.finance.desc') }}
            </p>
          </div>

          <!-- Card 4: AI Tahlil -->
          <div class="relative bg-white rounded-3xl p-8 shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500 group overflow-hidden">
            <!-- Badge -->
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1.5 bg-gradient-to-r from-purple-500 to-violet-500 text-white text-sm font-semibold rounded-full shadow-lg">
              {{ t('welcome.modules.time_saving.badge') }}
            </div>
            <!-- Icon -->
            <div class="mt-6 mb-6">
              <svg class="w-16 h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="32" cy="32" r="24" stroke="#F472B6" stroke-width="4" stroke-dasharray="8 4"/>
                <path d="M32 16V32L44 44" stroke="#EC4899" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="32" cy="32" r="4" fill="#EC4899"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
              <span class="mr-2">⏱️</span>
              {{ t('welcome.modules.time_saving.title') }}
            </h3>
            <p class="text-gray-600 leading-relaxed">
              {{ t('welcome.modules.time_saving.desc') }}
            </p>
          </div>

          <!-- Card 5: Onlayn Nazorat -->
          <div class="relative bg-white rounded-3xl p-8 shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500 group overflow-hidden">
            <!-- Badge -->
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1.5 bg-gradient-to-r from-cyan-500 to-blue-500 text-white text-sm font-semibold rounded-full shadow-lg">
              {{ t('welcome.modules.online_control.badge') }}
            </div>
            <!-- Icon -->
            <div class="mt-6 mb-6">
              <svg class="w-16 h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="8" y="16" width="20" height="14" rx="2" fill="#06B6D4"/>
                <rect x="8" y="34" width="20" height="14" rx="2" fill="#0891B2"/>
                <rect x="36" y="16" width="20" height="32" rx="2" fill="#22D3EE"/>
                <path d="M46 32L42 36L44 38L50 32L44 26L42 28L46 32Z" fill="white"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
              <span class="mr-2">📊</span>
              {{ t('welcome.modules.online_control.title') }}
            </h3>
            <p class="text-gray-600 leading-relaxed">
              {{ t('welcome.modules.online_control.desc') }}
            </p>
          </div>

          <!-- Card 6: Jamoa Samaradorligi -->
          <div class="relative bg-white rounded-3xl p-8 shadow-lg border border-gray-100 hover:shadow-2xl transition-all duration-500 group overflow-hidden">
            <!-- Badge -->
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1.5 bg-gradient-to-r from-orange-500 to-amber-500 text-white text-sm font-semibold rounded-full shadow-lg">
              {{ t('welcome.modules.team.badge') }}
            </div>
            <!-- Icon -->
            <div class="mt-6 mb-6">
              <svg class="w-16 h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="20" cy="24" r="8" fill="#F472B6"/>
                <circle cx="44" cy="24" r="8" fill="#FB7185"/>
                <circle cx="32" cy="44" r="10" fill="#EC4899"/>
                <path d="M20 36C14 38 12 44 12 48" stroke="#F472B6" stroke-width="3" stroke-linecap="round"/>
                <path d="M44 36C50 38 52 44 52 48" stroke="#FB7185" stroke-width="3" stroke-linecap="round"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
              <span class="mr-2">👥</span>
              {{ t('welcome.modules.team.title') }}
            </h3>
            <p class="text-gray-600 leading-relaxed">
              {{ t('welcome.modules.team.desc') }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- AI Powered Features Carousel -->
    <FeaturesCarousel />

    <!-- Testimonials Section -->
    <section class="py-16 bg-gradient-to-r from-blue-600 to-indigo-600 relative overflow-hidden">
      <!-- Background pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-full h-full" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-12">
          <span class="inline-block px-3 py-1 bg-white/20 text-white rounded-full text-sm font-medium mb-4">{{ t('welcome.testimonials.badge') }}</span>
          <h2 class="text-3xl font-bold text-white mb-4">{{ t('welcome.testimonials.title') }}</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <p class="text-white/90 mb-4 italic">"{{ t('welcome.testimonials.review1.text') }}"</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white font-bold">AK</div>
              <div>
                <p class="text-white font-medium">{{ t('welcome.testimonials.review1.name') }}</p>
                <p class="text-white/60 text-sm">{{ t('welcome.testimonials.review1.role') }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <p class="text-white/90 mb-4 italic">"{{ t('welcome.testimonials.review2.text') }}"</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center text-white font-bold">MR</div>
              <div>
                <p class="text-white font-medium">{{ t('welcome.testimonials.review2.name') }}</p>
                <p class="text-white/60 text-sm">{{ t('welcome.testimonials.review2.role') }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
            <div class="flex items-center gap-1 mb-4">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <p class="text-white/90 mb-4 italic">"{{ t('welcome.testimonials.review3.text') }}"</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">GK</div>
              <div>
                <p class="text-white font-medium">{{ t('welcome.testimonials.review3.name') }}</p>
                <p class="text-white/60 text-sm">{{ t('welcome.testimonials.review3.role') }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-20 bg-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-3xl p-10 border border-gray-200">
          <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-6">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ t('welcome.free_trial_14_days') }}
          </div>
          <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
            {{ t('welcome.cta.title_1') }} <span class="text-blue-600">{{ t('welcome.cta.title_highlight') }}</span> {{ t('welcome.cta.title_2') }}
          </h2>
          <p class="text-xl text-gray-600 mb-8">
            {{ t('welcome.cta.desc_1') }}
            <br class="hidden md:block" />
            {{ t('welcome.cta.desc_2') }}
          </p>
          <Link
            href="/welcome/create-business"
            class="group inline-flex items-center px-10 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-lg font-semibold rounded-2xl hover:from-blue-700 hover:to-indigo-700 transform transition-all duration-300 hover:scale-105 shadow-xl hover:shadow-2xl"
          >
            {{ t('welcome.cta.button') }}
            <svg class="w-5 h-5 ml-3 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </Link>
          <p class="mt-4 text-sm text-gray-500">{{ t('welcome.no_credit_card') }}</p>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div class="col-span-1 md:col-span-2">
            <div class="flex items-center space-x-3 mb-4">
              <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <span class="text-xl font-bold">BiznesPilot AI</span>
            </div>
            <p class="text-gray-400 text-sm max-w-md">
              {{ t('welcome.footer.desc') }}
            </p>
          </div>
          <div>
            <h4 class="font-semibold mb-4">{{ t('welcome.footer.product') }}</h4>
            <ul class="space-y-2 text-gray-400 text-sm">
              <li>{{ t('nav.dream_buyer') }}</li>
              <li>{{ t('welcome.features.ai_chatbots.title') }}</li>
              <li>CRM</li>
              <li>Analytics</li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-4">{{ t('welcome.footer.contact') }}</h4>
            <ul class="space-y-2 text-gray-400 text-sm">
              <li>support@biznespilot.uz</li>
              <li>+998 50 504 86 68</li>
              <li>{{ t('welcome.footer.location') }}</li>
            </ul>
          </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
          &copy; {{ new Date().getFullYear() }} BiznesPilot AI. {{ t('welcome.footer.rights') }}
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import FeaturesCarousel from '@/components/Landing/FeaturesCarousel.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();
</script>
