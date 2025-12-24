<template>
  <Head :title="`Diagnostika #${diagnostic.version}`" />

  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/50 to-indigo-50">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-xl border-b border-gray-200/50 sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-4">
            <Link href="/business/diagnostic" class="flex items-center gap-4 hover:opacity-80 transition-opacity">
              <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/25">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <div>
                <h1 class="text-lg font-bold text-gray-900">Diagnostika #{{ diagnostic.version }}</h1>
                <p class="text-xs text-gray-500">{{ diagnostic.completed_at }}</p>
              </div>
            </Link>
          </div>
          <div class="flex items-center gap-3">
            <button
              @click="downloadReport"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center text-sm"
            >
              <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
              Hisobotni yuklab olish
            </button>
            <Link
              href="/business/diagnostic"
              class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm"
            >
              Yangi diagnostika
            </Link>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Status Level Hero -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden mb-8">
        <div class="p-8" :class="statusLevelBgClass">
          <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
            <div class="flex items-center gap-6">
              <div class="text-6xl">{{ statusLevelEmoji }}</div>
              <div>
                <h2 class="text-2xl font-bold" :class="statusLevelTextClass">
                  {{ statusLevelLabel }}
                </h2>
                <p v-if="diagnostic.status_message" class="text-gray-600 mt-1 max-w-lg">
                  {{ diagnostic.status_message }}
                </p>
              </div>
            </div>
            <HealthScoreGauge
              :score="diagnostic.overall_score"
              :size="180"
              :animate="true"
            />
          </div>
        </div>

        <!-- Category Scores -->
        <div class="p-6 border-t border-gray-100">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <CategoryScoreCard
              v-for="(score, category) in diagnostic.category_scores"
              :key="category"
              :category="category"
              :score="score"
            />
          </div>
        </div>
      </div>

      <!-- Money Loss + Expected Results Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <MoneyLossCard
          v-if="diagnostic.money_loss_analysis"
          :money-loss="diagnostic.money_loss_analysis"
        />
        <ExpectedResultsCard
          v-if="diagnostic.expected_results"
          :expected-results="diagnostic.expected_results"
        />
      </div>

      <!-- Action Plan -->
      <div class="mb-8">
        <ActionPlanCard
          v-if="diagnostic.action_plan"
          :action-plan="diagnostic.action_plan"
          :max-steps="100"
        />
      </div>

      <!-- Detailed Analysis Tabs -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden mb-8">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-100">
          <div class="flex overflow-x-auto">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="px-6 py-4 text-sm font-medium whitespace-nowrap transition-colors border-b-2"
              :class="activeTab === tab.id
                ? 'border-indigo-600 text-indigo-600 bg-indigo-50/50'
                : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
            >
              <component :is="tab.icon" class="w-4 h-4 inline mr-2" />
              {{ tab.label }}
            </button>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
          <!-- Ideal Customer Analysis -->
          <div v-if="activeTab === 'customer'" class="space-y-6">
            <div v-if="diagnostic.ideal_customer_analysis">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Ideal Mijoz Tahlili</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-if="diagnostic.ideal_customer_analysis.demographics" class="bg-purple-50 rounded-xl p-4">
                  <h4 class="font-medium text-purple-900 mb-2">Demografiya</h4>
                  <p class="text-purple-700 text-sm">{{ formatDemographics(diagnostic.ideal_customer_analysis.demographics) }}</p>
                </div>
                <div v-if="diagnostic.ideal_customer_analysis.pain_points" class="bg-red-50 rounded-xl p-4">
                  <h4 class="font-medium text-red-900 mb-2">Og'riq nuqtalari</h4>
                  <ul class="text-red-700 text-sm space-y-1">
                    <li v-for="(point, i) in diagnostic.ideal_customer_analysis.pain_points" :key="i">
                      • {{ point }}
                    </li>
                  </ul>
                </div>
                <div v-if="diagnostic.ideal_customer_analysis.desires" class="bg-green-50 rounded-xl p-4">
                  <h4 class="font-medium text-green-900 mb-2">Istaklar</h4>
                  <ul class="text-green-700 text-sm space-y-1">
                    <li v-for="(desire, i) in diagnostic.ideal_customer_analysis.desires" :key="i">
                      • {{ desire }}
                    </li>
                  </ul>
                </div>
                <div v-if="diagnostic.ideal_customer_analysis.behavior" class="bg-blue-50 rounded-xl p-4">
                  <h4 class="font-medium text-blue-900 mb-2">Xulq-atvor</h4>
                  <p class="text-blue-700 text-sm">{{ diagnostic.ideal_customer_analysis.behavior }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Offer Strength -->
          <div v-if="activeTab === 'offer'" class="space-y-6">
            <div v-if="normalizedOfferStrength">
              <!-- Header with overall score -->
              <div class="flex items-center justify-between mb-6">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Taklif Kuchi ($100M Offers)</h3>
                  <p class="text-sm text-gray-500 mt-1">Alex Hormozi metodologiyasi asosida</p>
                </div>
                <div class="flex items-center gap-3">
                  <div class="text-right">
                    <p class="text-sm text-gray-500">Umumiy ball</p>
                    <p class="text-2xl font-bold" :class="normalizedOfferStrength.score >= 70 ? 'text-green-600' : normalizedOfferStrength.score >= 40 ? 'text-yellow-600' : 'text-red-600'">
                      {{ normalizedOfferStrength.score }}/100
                    </p>
                  </div>
                </div>
              </div>

              <!-- Score Cards -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-4 border border-indigo-100">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-indigo-600 uppercase tracking-wide">{{ normalizedOfferStrength.scoreLabels.value }}</span>
                    <span class="text-xs text-gray-400">/10</span>
                  </div>
                  <p class="text-3xl font-bold text-indigo-600">{{ normalizedOfferStrength.valueScore }}</p>
                  <div class="mt-2 h-1.5 bg-indigo-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full" :style="{ width: `${normalizedOfferStrength.valueScore * 10}%` }"></div>
                  </div>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-100">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-green-600 uppercase tracking-wide">{{ normalizedOfferStrength.scoreLabels.uniqueness }}</span>
                    <span class="text-xs text-gray-400">/10</span>
                  </div>
                  <p class="text-3xl font-bold text-green-600">{{ normalizedOfferStrength.uniquenessScore }}</p>
                  <div class="mt-2 h-1.5 bg-green-100 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500 rounded-full" :style="{ width: `${normalizedOfferStrength.uniquenessScore * 10}%` }"></div>
                  </div>
                </div>
                <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-xl p-4 border border-orange-100">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-orange-600 uppercase tracking-wide">{{ normalizedOfferStrength.scoreLabels.urgency }}</span>
                    <span class="text-xs text-gray-400">/10</span>
                  </div>
                  <p class="text-3xl font-bold text-orange-600">{{ normalizedOfferStrength.urgencyScore }}</p>
                  <div class="mt-2 h-1.5 bg-orange-100 rounded-full overflow-hidden">
                    <div class="h-full bg-orange-500 rounded-full" :style="{ width: `${normalizedOfferStrength.urgencyScore * 10}%` }"></div>
                  </div>
                </div>
                <div class="bg-gradient-to-br from-pink-50 to-rose-50 rounded-xl p-4 border border-pink-100">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-pink-600 uppercase tracking-wide">{{ normalizedOfferStrength.scoreLabels.guarantee }}</span>
                    <span class="text-xs text-gray-400">/10</span>
                  </div>
                  <p class="text-3xl font-bold text-pink-600">{{ normalizedOfferStrength.guaranteeScore }}</p>
                  <div class="mt-2 h-1.5 bg-pink-100 rounded-full overflow-hidden">
                    <div class="h-full bg-pink-500 rounded-full" :style="{ width: `${normalizedOfferStrength.guaranteeScore * 10}%` }"></div>
                  </div>
                </div>
              </div>

              <!-- Formula explanation -->
              <div v-if="normalizedOfferStrength.calculatedValue" class="bg-gray-50 rounded-xl p-4 mb-6">
                <h4 class="font-medium text-gray-900 mb-2 flex items-center gap-2">
                  <SparklesIcon class="w-4 h-4 text-indigo-600" />
                  Taklif Qiymati Formulasi
                </h4>
                <p class="text-sm text-gray-600 mb-2">
                  Qiymat = (Orzu natijasi × Ishonch darajasi) / (Vaqt sarfi × Harakat sarfi)
                </p>
                <p class="text-lg font-semibold text-indigo-600">
                  Hisoblangan qiymat: {{ normalizedOfferStrength.calculatedValue }}
                </p>
              </div>

              <!-- Improvements -->
              <div v-if="normalizedOfferStrength.improvements?.length" class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl p-5 border border-yellow-200">
                <h4 class="font-medium text-yellow-900 mb-3 flex items-center gap-2">
                  <LightBulbIcon class="w-5 h-5 text-yellow-600" />
                  Yaxshilash tavsiyalari
                </h4>
                <ul class="space-y-3">
                  <li v-for="(imp, i) in normalizedOfferStrength.improvements" :key="i" class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-yellow-200 rounded-full flex items-center justify-center text-xs font-medium text-yellow-800">{{ i + 1 }}</span>
                    <span class="text-sm text-yellow-800">{{ imp }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Channels Analysis -->
          <div v-if="activeTab === 'channels'" class="space-y-6">
            <div v-if="normalizedChannels.channels.length || diagnostic.channels_analysis">
              <!-- Header -->
              <div class="flex items-center justify-between mb-6">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Marketing Kanallari Tahlili</h3>
                  <p class="text-sm text-gray-500 mt-1">Faol kanallaringiz va ularning samaradorligi</p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full">Yuqori</span>
                  <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">O'rta</span>
                  <span class="px-3 py-1 bg-red-100 text-red-700 text-xs rounded-full">Past</span>
                </div>
              </div>

              <!-- Channels Grid -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div
                  v-for="(channel, i) in normalizedChannels.channels"
                  :key="i"
                  class="bg-white rounded-xl border p-4 hover:shadow-md transition-shadow"
                  :class="channel.connected ? 'border-gray-200' : 'border-red-200 bg-red-50/30'"
                >
                  <!-- Channel Header -->
                  <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                      <div
                        class="w-10 h-10 rounded-lg flex items-center justify-center"
                        :class="channel.name === 'Instagram' ? 'bg-gradient-to-br from-purple-500 to-pink-500' :
                                channel.name === 'Telegram' ? 'bg-blue-500' :
                                channel.name === 'WhatsApp' ? 'bg-green-500' :
                                channel.name === 'Facebook' ? 'bg-blue-600' :
                                channel.name === 'TikTok' ? 'bg-black' : 'bg-gray-500'"
                      >
                        <span class="text-white text-lg font-bold">{{ channel.name.charAt(0) }}</span>
                      </div>
                      <div>
                        <h4 class="font-medium text-gray-900">{{ channel.name }}</h4>
                        <span v-if="!channel.connected" class="text-xs text-red-600">Ulanmagan</span>
                        <span v-else class="text-xs text-green-600">Ulangan</span>
                      </div>
                    </div>
                    <span
                      class="px-2 py-1 text-xs rounded-full font-medium"
                      :class="channel.effectiveness === 'high' ? 'bg-green-100 text-green-700' :
                              channel.effectiveness === 'medium' ? 'bg-yellow-100 text-yellow-700' :
                              'bg-red-100 text-red-700'"
                    >
                      {{ channel.effectiveness === 'high' ? 'Yuqori' :
                         channel.effectiveness === 'medium' ? 'O\'rta' : 'Past' }}
                    </span>
                  </div>

                  <!-- Stats if available -->
                  <div v-if="channel.followers || channel.engagement_rate" class="flex items-center gap-4 mb-3 text-sm">
                    <div v-if="channel.followers" class="text-gray-600">
                      <span class="font-medium text-gray-900">{{ channel.followers.toLocaleString() }}</span> obunachilar
                    </div>
                    <div v-if="channel.engagement_rate" class="text-gray-600">
                      <span class="font-medium text-gray-900">{{ channel.engagement_rate }}%</span> engagement
                    </div>
                  </div>

                  <!-- Score bar -->
                  <div v-if="channel.score !== undefined" class="mb-3">
                    <div class="flex items-center justify-between text-xs mb-1">
                      <span class="text-gray-500">Samaradorlik</span>
                      <span class="font-medium" :class="channel.score >= 70 ? 'text-green-600' : channel.score >= 40 ? 'text-yellow-600' : 'text-red-600'">{{ channel.score }}/100</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all"
                        :class="channel.score >= 70 ? 'bg-green-500' : channel.score >= 40 ? 'bg-yellow-500' : 'bg-red-500'"
                        :style="{ width: `${channel.score}%` }"
                      ></div>
                    </div>
                  </div>

                  <!-- Problems -->
                  <div v-if="channel.problems?.length" class="mb-3">
                    <p class="text-xs text-red-600 font-medium mb-1">Muammolar:</p>
                    <ul class="text-xs text-red-600 space-y-0.5">
                      <li v-for="(problem, j) in channel.problems" :key="j">• {{ problem }}</li>
                    </ul>
                  </div>

                  <!-- Recommendation -->
                  <div v-if="channel.recommendation" class="pt-3 border-t border-gray-100">
                    <p class="text-sm text-gray-600">
                      <span class="font-medium text-indigo-600">Tavsiya:</span> {{ channel.recommendation }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Empty state -->
              <div v-if="!normalizedChannels.channels.length" class="text-center py-8 bg-gray-50 rounded-xl">
                <MegaphoneIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                <p class="text-gray-600">Hech qanday kanal ulanmagan</p>
                <p class="text-sm text-gray-500 mt-1">Marketing kanallaringizni ulang va samaradorlikni kuzating</p>
              </div>

              <!-- Recommended Channels -->
              <div v-if="normalizedChannels.recommendedChannels.length" class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-5 border border-indigo-100">
                <h4 class="font-medium text-indigo-900 mb-3 flex items-center gap-2">
                  <SparklesIcon class="w-5 h-5 text-indigo-600" />
                  Tavsiya etilgan yangi kanallar
                </h4>
                <p class="text-sm text-indigo-700 mb-3">Bu kanallar sizning biznesingiz uchun samarali bo'lishi mumkin:</p>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="(ch, i) in normalizedChannels.recommendedChannels"
                    :key="i"
                    class="px-4 py-2 bg-white border border-indigo-200 text-indigo-700 text-sm rounded-lg hover:bg-indigo-100 transition-colors cursor-pointer"
                  >
                    {{ ch }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Funnel Analysis -->
          <div v-if="activeTab === 'funnel'" class="space-y-6">
            <div v-if="normalizedFunnel.stages.length || diagnostic.funnel_analysis">
              <!-- Header -->
              <div class="flex items-center justify-between mb-6">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Sotuv Funneli Tahlili</h3>
                  <p class="text-sm text-gray-500 mt-1">Mijozlar sayohati va konversiya ko'rsatkichlari</p>
                </div>
                <div v-if="normalizedFunnel.overallConversion" class="text-right">
                  <p class="text-sm text-gray-500">Umumiy konversiya</p>
                  <p class="text-2xl font-bold" :class="normalizedFunnel.overallConversion >= 5 ? 'text-green-600' : normalizedFunnel.overallConversion >= 2 ? 'text-yellow-600' : 'text-red-600'">
                    {{ normalizedFunnel.overallConversion }}%
                  </p>
                </div>
              </div>

              <!-- Funnel Visualization -->
              <div class="relative mb-6">
                <div class="space-y-3">
                  <div
                    v-for="(stage, i) in normalizedFunnel.stages"
                    :key="i"
                    class="relative group"
                  >
                    <div
                      class="rounded-xl flex items-center justify-between px-5 py-4 transition-all hover:shadow-md cursor-pointer"
                      :class="stage.health === 'good' ? 'bg-gradient-to-r from-green-50 to-green-100 border border-green-200' :
                              stage.health === 'warning' ? 'bg-gradient-to-r from-yellow-50 to-yellow-100 border border-yellow-200' :
                              'bg-gradient-to-r from-red-50 to-red-100 border border-red-200'"
                      :style="{ width: `${Math.max(100 - i * 12, 40)}%`, marginLeft: `${i * 6}%` }"
                    >
                      <div class="flex items-center gap-3">
                        <div
                          class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm"
                          :class="stage.health === 'good' ? 'bg-green-500' : stage.health === 'warning' ? 'bg-yellow-500' : 'bg-red-500'"
                        >
                          {{ i + 1 }}
                        </div>
                        <div>
                          <span class="font-medium text-gray-900">{{ stage.name }}</span>
                          <p v-if="stage.count" class="text-xs text-gray-500">{{ stage.count.toLocaleString() }} kishi</p>
                        </div>
                      </div>
                      <div class="text-right">
                        <span class="text-lg font-bold" :class="stage.health === 'good' ? 'text-green-600' : stage.health === 'warning' ? 'text-yellow-600' : 'text-red-600'">
                          {{ stage.conversionRate }}%
                        </span>
                        <p v-if="stage.dropRate" class="text-xs text-red-500">-{{ stage.dropRate }}% tushum</p>
                      </div>
                    </div>

                    <!-- Problem/Solution tooltip on hover -->
                    <div v-if="stage.problem" class="absolute left-full top-1/2 -translate-y-1/2 ml-4 opacity-0 group-hover:opacity-100 transition-opacity z-10 w-64">
                      <div class="bg-white rounded-lg shadow-lg border p-3">
                        <p class="text-xs text-red-600 font-medium mb-1">Muammo:</p>
                        <p class="text-sm text-gray-700 mb-2">{{ stage.problem }}</p>
                        <p v-if="stage.solution" class="text-xs text-green-600 font-medium mb-1">Yechim:</p>
                        <p v-if="stage.solution" class="text-sm text-gray-700">{{ stage.solution }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Connecting arrows -->
                <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200 -z-10" style="margin-left: 6%;"></div>
              </div>

              <!-- Biggest Leak -->
              <div v-if="normalizedFunnel.biggestLeak" class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl p-5 border border-red-200 mb-6">
                <h4 class="font-medium text-red-900 mb-3 flex items-center gap-2">
                  <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
                  Eng katta yo'qotish joyi
                </h4>
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-lg font-semibold text-red-700">{{ normalizedFunnel.biggestLeak.stage }}</p>
                    <p class="text-sm text-red-600">{{ normalizedFunnel.biggestLeak.loss_percent }}% mijozlar yo'qotilmoqda</p>
                  </div>
                  <div v-if="normalizedFunnel.biggestLeak.estimated_loss" class="text-right">
                    <p class="text-sm text-gray-500">Taxminiy yo'qotish</p>
                    <p class="text-xl font-bold text-red-600">{{ formatCurrency(normalizedFunnel.biggestLeak.estimated_loss) }}</p>
                  </div>
                </div>
              </div>

              <!-- Bottlenecks -->
              <div v-if="normalizedFunnel.bottlenecks.length" class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl p-5 border border-yellow-200">
                <h4 class="font-medium text-yellow-900 mb-3 flex items-center gap-2">
                  <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600" />
                  Muammoli joylar va yechimlar
                </h4>
                <ul class="space-y-3">
                  <li v-for="(bn, i) in normalizedFunnel.bottlenecks" :key="i" class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-yellow-200 rounded-full flex items-center justify-center text-xs font-medium text-yellow-800">{{ i + 1 }}</span>
                    <span class="text-sm text-yellow-800">{{ bn }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Risks -->
          <div v-if="activeTab === 'risks'" class="space-y-6">
            <div v-if="normalizedRisks.threats.length || normalizedRisks.opportunities.length || normalizedRisks.timeline.length || diagnostic.risks">
              <!-- Header -->
              <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Xavflar va Imkoniyatlar</h3>
                <p class="text-sm text-gray-500 mt-1">Biznesingiz oldida turgan xavflar va foydalanish mumkin bo'lgan imkoniyatlar</p>
              </div>

              <!-- Timeline risks (old format) -->
              <div v-if="normalizedRisks.timeline.length" class="mb-6">
                <h4 class="font-medium text-gray-900 mb-4">Vaqt bo'yicha xavflar</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div
                    v-for="(item, i) in normalizedRisks.timeline"
                    :key="i"
                    class="bg-gradient-to-br rounded-xl p-5 border"
                    :class="i === 0 ? 'from-yellow-50 to-orange-50 border-yellow-200' :
                            i === 1 ? 'from-orange-50 to-red-50 border-orange-200' :
                            'from-red-50 to-red-100 border-red-200'"
                  >
                    <div class="flex items-center gap-2 mb-3">
                      <div
                        class="w-10 h-10 rounded-full flex items-center justify-center"
                        :class="i === 0 ? 'bg-yellow-100' : i === 1 ? 'bg-orange-100' : 'bg-red-100'"
                      >
                        <ClockIcon
                          class="w-5 h-5"
                          :class="i === 0 ? 'text-yellow-600' : i === 1 ? 'text-orange-600' : 'text-red-600'"
                        />
                      </div>
                      <span class="font-medium" :class="i === 0 ? 'text-yellow-700' : i === 1 ? 'text-orange-700' : 'text-red-700'">
                        {{ item.period }}
                      </span>
                    </div>
                    <p class="text-sm text-gray-700 mb-3">{{ item.description }}</p>
                    <div v-if="item.estimatedLoss" class="pt-3 border-t" :class="i === 0 ? 'border-yellow-200' : i === 1 ? 'border-orange-200' : 'border-red-200'">
                      <p class="text-xs text-gray-500">Taxminiy yo'qotish</p>
                      <p class="text-lg font-bold" :class="i === 0 ? 'text-yellow-600' : i === 1 ? 'text-orange-600' : 'text-red-600'">
                        {{ formatCurrency(item.estimatedLoss) }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Threats and Opportunities Grid -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Threats -->
                <div v-if="normalizedRisks.threats.length" class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl p-5 border border-red-200">
                  <h4 class="font-medium text-red-900 mb-4 flex items-center gap-2">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                      <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
                    </div>
                    Xavflar
                  </h4>
                  <ul class="space-y-3">
                    <li v-for="(threat, i) in normalizedRisks.threats" :key="i" class="flex items-start gap-3">
                      <span class="flex-shrink-0 w-6 h-6 bg-red-200 rounded-full flex items-center justify-center text-xs font-medium text-red-800">{{ i + 1 }}</span>
                      <span class="text-sm text-red-800">{{ threat }}</span>
                    </li>
                  </ul>
                </div>

                <!-- Opportunities -->
                <div v-if="normalizedRisks.opportunities.length" class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border border-green-200">
                  <h4 class="font-medium text-green-900 mb-4 flex items-center gap-2">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                      <LightBulbIcon class="w-5 h-5 text-green-600" />
                    </div>
                    Imkoniyatlar
                  </h4>
                  <ul class="space-y-3">
                    <li v-for="(opp, i) in normalizedRisks.opportunities" :key="i" class="flex items-start gap-3">
                      <span class="flex-shrink-0 w-6 h-6 bg-green-200 rounded-full flex items-center justify-center text-xs font-medium text-green-800">{{ i + 1 }}</span>
                      <span class="text-sm text-green-800">{{ opp }}</span>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Empty state -->
              <div v-if="!normalizedRisks.threats.length && !normalizedRisks.opportunities.length && !normalizedRisks.timeline.length" class="text-center py-8 bg-gray-50 rounded-xl">
                <ExclamationTriangleIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                <p class="text-gray-600">Xavflar va imkoniyatlar tahlili mavjud emas</p>
              </div>
            </div>
          </div>

          <!-- SWOT Analysis -->
          <div v-if="activeTab === 'swot'" class="space-y-6">
            <SWOTCard :swot="diagnostic.swot" />
          </div>

          <!-- Platform Recommendations -->
          <div v-if="activeTab === 'platform'" class="space-y-6">
            <div v-if="normalizedPlatformRecommendations.length">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Platforma Tavsiyalari</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="(rec, i) in normalizedPlatformRecommendations"
                  :key="i"
                  class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-4"
                >
                  <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                      <CubeIcon class="w-5 h-5 text-indigo-600" />
                    </div>
                    <h4 class="font-medium text-gray-900">{{ rec.module }}</h4>
                  </div>
                  <p class="text-sm text-gray-600 mb-2">{{ rec.reason }}</p>
                  <span class="text-xs text-indigo-600 font-medium">{{ rec.priority }} ustuvorlik</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Stories -->
      <div class="mb-8">
        <SuccessStoriesCard
          v-if="diagnostic.similar_businesses"
          :similar-businesses="diagnostic.similar_businesses"
        />
      </div>

      <!-- AI Insights -->
      <div v-if="diagnostic.ai_insights" class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden mb-8">
        <div class="p-6 bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
              <SparklesIcon class="w-6 h-6 text-indigo-600" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-900">AI Tahlili</h3>
              <p class="text-sm text-gray-500">Claude AI tomonidan tayyorlangan</p>
            </div>
          </div>
        </div>
        <div class="p-6">
          <div class="prose prose-sm max-w-none text-gray-700">
            <p class="whitespace-pre-line">{{ diagnostic.ai_insights }}</p>
          </div>
        </div>
      </div>

      <!-- Start Working CTA Section -->
      <div class="bg-gradient-to-br from-emerald-500 via-green-500 to-teal-600 rounded-3xl p-8 relative overflow-hidden mb-8">
        <!-- Background decorations -->
        <div class="absolute inset-0 opacity-10">
          <div class="absolute top-0 right-0 w-72 h-72 bg-white rounded-full -translate-y-1/3 translate-x-1/3"></div>
          <div class="absolute bottom-0 left-0 w-56 h-56 bg-white rounded-full translate-y-1/3 -translate-x-1/3"></div>
          <div class="absolute top-1/2 left-1/2 w-40 h-40 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
        </div>

        <div class="relative">
          <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
            <!-- Left content -->
            <div class="flex-1 text-center lg:text-left">
              <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full mb-4">
                <RocketLaunchIcon class="w-5 h-5 text-white" />
                <span class="text-sm font-semibold text-white">Tahlil yakunlandi</span>
              </div>

              <h2 class="text-2xl sm:text-3xl font-bold text-white mb-3">
                Ishni boshlash vaqti keldi!
              </h2>

              <p class="text-emerald-100 text-lg mb-6 max-w-xl">
                Diagnostika natijalariga ko'ra sizning biznesingiz uchun aniq harakat rejasi tayyor.
                Endi Dashboard'ga o'ting va birinchi qadamni qo'ying.
              </p>

              <!-- Quick stats -->
              <div class="flex flex-wrap justify-center lg:justify-start gap-4 mb-6">
                <div class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl">
                  <CheckCircleIcon class="w-5 h-5 text-white" />
                  <span class="text-white font-medium">{{ diagnostic.action_plan?.total_steps || 3 }} qadam tayyor</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl">
                  <ClockIcon class="w-5 h-5 text-white" />
                  <span class="text-white font-medium">~{{ diagnostic.action_plan?.total_time_hours || 5 }} soat</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl">
                  <BanknotesIcon class="w-5 h-5 text-white" />
                  <span class="text-white font-medium">+{{ formatPotentialSavings(diagnostic.action_plan?.total_potential_savings) }} potensial</span>
                </div>
              </div>
            </div>

            <!-- Right - CTA buttons -->
            <div class="flex flex-col gap-4">
              <button
                @click="goToDashboard"
                class="group flex items-center justify-center gap-3 px-8 py-4 bg-white text-emerald-600 font-bold text-lg rounded-2xl shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300"
              >
                <PlayIcon class="w-6 h-6" />
                <span>Dashboard'ga o'tish</span>
                <ArrowRightIcon class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
              </button>

              <Link
                v-if="diagnostic.action_plan?.steps?.[0]?.module_route"
                :href="diagnostic.action_plan.steps[0].module_route"
                class="group flex items-center justify-center gap-3 px-8 py-4 bg-white/20 backdrop-blur-sm text-white font-semibold text-lg rounded-2xl border-2 border-white/30 hover:bg-white/30 hover:border-white/50 transition-all duration-300"
              >
                <SparklesIcon class="w-6 h-6" />
                <span>{{ diagnostic.action_plan.steps[0].title || 'Birinchi qadamni boshlash' }}</span>
              </Link>
            </div>
          </div>

          <!-- First step preview -->
          <div v-if="diagnostic.action_plan?.steps?.[0]" class="mt-8 p-6 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="text-2xl font-bold text-emerald-600">1</span>
              </div>
              <div class="flex-1">
                <h4 class="text-white font-semibold text-lg mb-1">
                  {{ diagnostic.action_plan.steps[0].title }}
                </h4>
                <p class="text-emerald-100 text-sm mb-3">
                  {{ diagnostic.action_plan.steps[0].why }}
                </p>
                <div class="flex flex-wrap gap-3">
                  <span class="inline-flex items-center gap-1 px-3 py-1 bg-white/20 rounded-lg text-white text-sm">
                    <ClockIcon class="w-4 h-4" />
                    {{ diagnostic.action_plan.steps[0].time_minutes }} daqiqa
                  </span>
                  <span class="inline-flex items-center gap-1 px-3 py-1 bg-white/20 rounded-lg text-white text-sm">
                    <TrophyIcon class="w-4 h-4" />
                    {{ diagnostic.action_plan.steps[0].similar_business_result }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Meta Info -->
      <div class="mt-8 text-center text-sm text-gray-500">
        <p>
          Diagnostika {{ formatTashkentTime(diagnostic.completed_at_raw) }} sanasida yakunlandi |
          {{ diagnostic.tokens_used?.toLocaleString() }} token ishlatildi |
          {{ diagnostic.generation_time_ms }}ms
        </p>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDiagnosticStore } from '@/stores/diagnostic';
import HealthScoreGauge from '@/Components/diagnostic/HealthScoreGauge.vue';
import CategoryScoreCard from '@/Components/diagnostic/CategoryScoreCard.vue';
import MoneyLossCard from '@/Components/diagnostic/MoneyLossCard.vue';
import ActionPlanCard from '@/Components/diagnostic/ActionPlanCard.vue';
import ExpectedResultsCard from '@/Components/diagnostic/ExpectedResultsCard.vue';
import SuccessStoriesCard from '@/Components/diagnostic/SuccessStoriesCard.vue';
import SWOTCard from '@/Components/diagnostic/SWOTCard.vue';
import {
  DocumentArrowDownIcon,
  SparklesIcon,
  ArrowRightIcon,
  UserGroupIcon,
  GiftIcon,
  MegaphoneIcon,
  FunnelIcon,
  ExclamationTriangleIcon,
  LightBulbIcon,
  Squares2X2Icon,
  CubeIcon,
  ClockIcon,
  PlayIcon,
  CheckCircleIcon,
  RocketLaunchIcon,
  BanknotesIcon,
  TrophyIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  diagnostic: {
    type: Object,
    required: true,
  },
  questions: {
    type: Array,
    default: () => [],
  },
  actionProgress: {
    type: Array,
    default: () => [],
  },
  kpis: {
    type: Object,
    default: null,
  },
});

const store = useDiagnosticStore();
const activeTab = ref('customer');

const tabs = [
  { id: 'customer', label: 'Ideal Mijoz', icon: UserGroupIcon },
  { id: 'offer', label: 'Taklif Kuchi', icon: GiftIcon },
  { id: 'channels', label: 'Kanallar', icon: MegaphoneIcon },
  { id: 'funnel', label: 'Funnel', icon: FunnelIcon },
  { id: 'risks', label: 'Xavflar', icon: ExclamationTriangleIcon },
  { id: 'swot', label: 'SWOT', icon: Squares2X2Icon },
  { id: 'platform', label: 'Platforma', icon: CubeIcon },
];

const statusLevelInfo = computed(() => {
  const levels = {
    critical: { label: 'Xavfli holat', emoji: '😰', bg: 'bg-red-50', text: 'text-red-800' },
    weak: { label: 'Zaif holat', emoji: '😐', bg: 'bg-orange-50', text: 'text-orange-800' },
    medium: { label: "O'rtacha holat", emoji: '🙂', bg: 'bg-yellow-50', text: 'text-yellow-800' },
    good: { label: 'Yaxshi holat', emoji: '😊', bg: 'bg-green-50', text: 'text-green-800' },
    excellent: { label: "Zo'r holat", emoji: '🚀', bg: 'bg-blue-50', text: 'text-blue-800' },
  };
  return levels[props.diagnostic.status_level] || levels.medium;
});

const statusLevelLabel = computed(() => statusLevelInfo.value.label);
const statusLevelEmoji = computed(() => statusLevelInfo.value.emoji);
const statusLevelBgClass = computed(() => statusLevelInfo.value.bg);
const statusLevelTextClass = computed(() => statusLevelInfo.value.text);

function funnelStageClass(health) {
  if (health === 'good') return 'bg-green-100';
  if (health === 'warning') return 'bg-yellow-100';
  return 'bg-red-100';
}

// Format demographics - handle both string and object formats
function formatDemographics(demographics) {
  if (typeof demographics === 'string') {
    return demographics;
  }
  if (typeof demographics === 'object' && demographics !== null) {
    const parts = [];
    if (demographics.age_range) parts.push(`${demographics.age_range} yoshdagi`);
    if (demographics.occupation) parts.push(demographics.occupation.toLowerCase());
    if (demographics.location) parts.push(`${demographics.location} shahrida yashovchi`);
    return parts.join(' ') || JSON.stringify(demographics);
  }
  return String(demographics);
}

// Normalize platform recommendations - handle both old and new formats
const normalizedPlatformRecommendations = computed(() => {
  const recs = props.diagnostic.platform_recommendations || [];
  return recs.map(rec => {
    // Old format: rec.module is an object with name, description, route, etc.
    if (typeof rec.module === 'object' && rec.module !== null) {
      return {
        module: rec.module.name || 'Modul',
        reason: rec.module.description || rec.reason || '',
        priority: rec.priority || 'o\'rta',
        route: rec.module.route || rec.route || null,
      };
    }
    // New format: rec.module is a string
    return {
      module: rec.module || 'Modul',
      reason: rec.reason || '',
      priority: rec.priority || 'o\'rta',
      route: rec.route || null,
    };
  });
});

// Normalize channels analysis - handle both old and new formats
const normalizedChannels = computed(() => {
  const analysis = props.diagnostic.channels_analysis;
  if (!analysis) return { channels: [], recommendedChannels: [] };

  // New format: { channels: [...], recommended_channels: [...] }
  if (Array.isArray(analysis.channels)) {
    return {
      channels: analysis.channels.map(ch => ({
        name: ch.name,
        effectiveness: ch.effectiveness || 'low',
        recommendation: ch.recommendation || '',
        connected: true,
        score: ch.score || 0,
      })),
      recommendedChannels: analysis.recommended_channels || [],
    };
  }

  // Old format: { instagram: {...}, telegram: {...} }
  const channels = [];
  const channelNames = { instagram: 'Instagram', telegram: 'Telegram', whatsapp: 'WhatsApp', facebook: 'Facebook', tiktok: 'TikTok' };

  for (const [key, value] of Object.entries(analysis)) {
    if (typeof value === 'object' && value !== null && key !== 'recommended_channels') {
      const effectiveness = value.connected ? (value.score >= 70 ? 'high' : value.score >= 40 ? 'medium' : 'low') : 'low';
      channels.push({
        name: channelNames[key] || key,
        effectiveness,
        recommendation: value.recommendation || (value.recommendations ? value.recommendations.join('. ') : ''),
        connected: value.connected || false,
        score: value.score || 0,
        followers: value.followers || 0,
        engagement_rate: value.engagement_rate || 0,
        problems: value.problems || [],
      });
    }
  }

  return {
    channels,
    recommendedChannels: analysis.recommended_channels || [],
  };
});

// Normalize funnel analysis - handle both old and new formats
const normalizedFunnel = computed(() => {
  const analysis = props.diagnostic.funnel_analysis;
  if (!analysis) return { stages: [], bottlenecks: [], overallConversion: 0, biggestLeak: null };

  const stages = (analysis.stages || []).map(stage => ({
    name: stage.name,
    conversionRate: stage.conversion_rate ?? stage.percent ?? 0,
    health: stage.health || (stage.drop_rate > 60 ? 'bad' : stage.drop_rate > 30 ? 'warning' : 'good'),
    count: stage.count || 0,
    dropRate: stage.drop_rate || 0,
    problem: stage.problem || '',
    solution: stage.solution || '',
  }));

  // Handle bottlenecks - could be array of strings or derived from stages
  let bottlenecks = analysis.bottlenecks || [];
  if (!bottlenecks.length && stages.some(s => s.problem)) {
    bottlenecks = stages.filter(s => s.problem).map(s => `${s.name}: ${s.problem}`);
  }

  return {
    stages,
    bottlenecks,
    overallConversion: analysis.overall_conversion || 0,
    biggestLeak: analysis.biggest_leak || null,
  };
});

// Normalize risks - handle both old and new formats
const normalizedRisks = computed(() => {
  const risks = props.diagnostic.risks;
  if (!risks) return { threats: [], opportunities: [], timeline: [] };

  // New format: { threats: [...], opportunities: [...] }
  if (Array.isArray(risks.threats) || Array.isArray(risks.opportunities)) {
    return {
      threats: risks.threats || [],
      opportunities: risks.opportunities || [],
      timeline: [],
    };
  }

  // Old format: { 3_months: {...}, 6_months: {...}, 12_months: {...} }
  const timeline = [];
  const threats = [];
  const timeLabels = { '3_months': '3 oy', '6_months': '6 oy', '12_months': '12 oy' };

  for (const [key, value] of Object.entries(risks)) {
    if (typeof value === 'object' && value !== null) {
      timeline.push({
        period: timeLabels[key] || key,
        description: value.description || '',
        estimatedLoss: value.estimated_loss || 0,
      });
      if (value.description) {
        threats.push(`${timeLabels[key] || key} ichida: ${value.description}`);
      }
    }
  }

  return {
    threats,
    opportunities: [],
    timeline,
  };
});

// Normalize offer strength - handle both old and new formats
const normalizedOfferStrength = computed(() => {
  const offer = props.diagnostic.offer_strength;
  if (!offer) return null;

  // Check if old format (has dream_outcome, perceived_likelihood, etc.)
  const isOldFormat = 'dream_outcome' in offer || 'perceived_likelihood' in offer;

  if (isOldFormat) {
    return {
      score: offer.score || 0,
      valueScore: offer.dream_outcome || 0,
      uniquenessScore: offer.perceived_likelihood || 0,
      urgencyScore: offer.time_delay || 0,
      guaranteeScore: offer.effort_required || 0,
      calculatedValue: offer.calculated_value || 0,
      improvements: (offer.recommendations || []).map(r =>
        typeof r === 'string' ? r : `${r.element}: ${r.advice} (hozirgi: ${r.current}/10, maqsad: ${r.target}/10)`
      ),
      scoreLabels: {
        value: 'Orzu natijasi',
        uniqueness: 'Ishonch darajasi',
        urgency: 'Vaqt sarfi',
        guarantee: 'Harakat sarfi',
      },
    };
  }

  // New format
  return {
    score: offer.score || 0,
    valueScore: offer.value_score || 0,
    uniquenessScore: offer.uniqueness_score || 0,
    urgencyScore: offer.urgency_score || 0,
    guaranteeScore: offer.guarantee_score || 0,
    calculatedValue: 0,
    improvements: offer.improvements || [],
    scoreLabels: {
      value: 'Qiymat',
      uniqueness: 'Noyoblik',
      urgency: 'Shoshilinchlik',
      guarantee: 'Kafolat',
    },
  };
});

// Format currency helper
function formatCurrency(amount) {
  return new Intl.NumberFormat('uz-UZ').format(amount) + ' UZS';
}

function formatPotentialSavings(amount) {
  if (!amount) return '0 so\'m';
  return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
}

function formatTashkentTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('uz-UZ', {
    timeZone: 'Asia/Tashkent',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function goToDashboard() {
  router.post('/business/diagnostic/complete-and-go');
}

async function downloadReport() {
  try {
    await store.downloadReport(props.diagnostic.id);
  } catch (error) {
    console.error('Failed to download report:', error);
  }
}
</script>
