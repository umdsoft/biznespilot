<template>
  <Head title="Strategiya yaratish" />

  <BusinessLayout title="Strategiya yaratish">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
      <!-- Header -->
      <div class="mb-6 sm:mb-8">
        <Link
          href="/business/strategy"
          class="inline-flex items-center text-sm text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors mb-3"
        >
          <ArrowLeftIcon class="w-4 h-4 mr-2" />
          Strategiyaga qaytish
        </Link>
        <div class="flex items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
              Strategiya yaratish
            </h1>
            <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1">
              {{ formData.year }}-yil uchun biznes strategiyangizni yarating
            </p>
          </div>
          <div class="hidden sm:flex items-center gap-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-full px-4 py-2 shadow-lg shadow-indigo-500/25">
            <SparklesIcon class="w-5 h-5" />
            <span class="text-sm font-medium">AI Wizard</span>
          </div>
        </div>
      </div>

      <!-- Progress Stepper - Compact -->
      <div class="mb-6 sm:mb-8 bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <template v-for="(step, index) in steps" :key="step.id">
            <!-- Step Item -->
            <div class="flex items-center">
              <button
                @click="goToStep(step.id)"
                :disabled="step.id > currentStep"
                class="relative flex items-center justify-center w-10 h-10 sm:w-11 sm:h-11 rounded-full transition-all duration-300"
                :class="[
                  step.id < currentStep
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white shadow-lg shadow-indigo-500/30'
                    : step.id === currentStep
                      ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/30 ring-4 ring-indigo-100 dark:ring-indigo-900/50'
                      : 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500'
                ]"
              >
                <component
                  :is="step.id < currentStep ? CheckIcon : step.icon"
                  class="w-5 h-5"
                />
              </button>
              <div class="ml-3 hidden sm:block">
                <p
                  class="text-sm font-semibold"
                  :class="step.id <= currentStep ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'"
                >
                  {{ step.title }}
                </p>
                <p
                  class="text-xs"
                  :class="step.id <= currentStep ? 'text-slate-500 dark:text-slate-400' : 'text-slate-300 dark:text-slate-600'"
                >
                  {{ step.description }}
                </p>
              </div>
            </div>
            <!-- Connector Line -->
            <div
              v-if="index < steps.length - 1"
              class="flex-1 h-1 mx-2 sm:mx-4 rounded-full transition-colors"
              :class="step.id < currentStep ? 'bg-gradient-to-r from-indigo-500 to-purple-500' : 'bg-slate-200 dark:bg-slate-700'"
            ></div>
          </template>
        </div>
        <!-- Mobile Step Label -->
        <div class="sm:hidden mt-3 text-center">
          <p class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ steps[currentStep - 1].title }}
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ steps[currentStep - 1].description }}
          </p>
        </div>
      </div>

      <!-- Main Content Card -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-slate-900/50 border border-slate-200 dark:border-slate-700 overflow-hidden">
        <!-- Step Content -->
        <div class="p-5 sm:p-6 lg:p-8">
          <transition name="fade-slide" mode="out-in">
            <!-- Step 1: Settings -->
            <div v-if="currentStep === 1" key="step1">
              <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 text-white shadow-lg shadow-indigo-500/25">
                  <Cog6ToothIcon class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Asosiy sozlamalar</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-400">Strategiya parametrlarini sozlang</p>
                </div>
              </div>

              <div class="space-y-5">
                <!-- Year Selection -->
                <div>
                  <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">
                    Strategiya yili
                  </label>
                  <div class="grid grid-cols-2 gap-3 sm:gap-4">
                    <button
                      v-for="y in availableYears"
                      :key="y"
                      @click="formData.year = y"
                      type="button"
                      class="relative p-4 rounded-xl border-2 transition-all duration-200 text-left"
                      :class="formData.year === y
                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 shadow-lg shadow-indigo-500/10'
                        : 'border-slate-200 dark:border-slate-600 hover:border-indigo-300 dark:hover:border-indigo-700 bg-white dark:bg-slate-800'"
                    >
                      <div class="flex items-center justify-between">
                        <div>
                          <span class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">{{ y }}</span>
                          <span class="text-slate-500 dark:text-slate-400 ml-1">-yil</span>
                        </div>
                        <div
                          v-if="formData.year === y"
                          class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center"
                        >
                          <CheckIcon class="w-4 h-4 text-white" />
                        </div>
                      </div>
                      <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        {{ y === new Date().getFullYear() ? 'Joriy yil' : 'Kelgusi yil' }}
                      </p>
                    </button>
                  </div>
                </div>

                <!-- AI Option -->
                <div
                  @click="formData.useAI = !formData.useAI"
                  class="relative p-4 sm:p-5 rounded-xl border-2 cursor-pointer transition-all duration-200"
                  :class="formData.useAI
                    ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20 shadow-lg shadow-indigo-500/10'
                    : 'border-slate-200 dark:border-slate-600 hover:border-indigo-300 dark:hover:border-indigo-700'"
                >
                  <div class="flex items-start gap-4">
                    <div
                      class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center transition-all"
                      :class="formData.useAI
                        ? 'bg-gradient-to-br from-indigo-500 to-purple-500 text-white shadow-lg shadow-indigo-500/30'
                        : 'bg-slate-100 dark:bg-slate-700 text-slate-400'"
                    >
                      <SparklesIcon class="w-6 h-6" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between gap-3">
                        <h3 class="font-semibold text-slate-900 dark:text-white">AI yordamida yaratish</h3>
                        <div
                          class="flex-shrink-0 w-11 h-6 rounded-full p-0.5 transition-colors"
                          :class="formData.useAI ? 'bg-indigo-500' : 'bg-slate-300 dark:bg-slate-600'"
                        >
                          <div
                            class="w-5 h-5 rounded-full bg-white shadow-sm transition-transform"
                            :class="formData.useAI ? 'translate-x-5' : 'translate-x-0'"
                          ></div>
                        </div>
                      </div>
                      <p class="text-sm text-slate-500 dark:text-slate-400 mt-1.5">
                        Diagnostika natijalariga asoslangan optimal strategiya tavsiya etiladi
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Diagnostic Status -->
                <div
                  v-if="diagnostic"
                  class="p-4 sm:p-5 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800"
                >
                  <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/25">
                      <CheckCircleIcon class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <h3 class="font-semibold text-emerald-800 dark:text-emerald-200">Diagnostika mavjud</h3>
                      <p class="text-sm text-emerald-600 dark:text-emerald-400 mt-1">
                        Oxirgi diagnostika: {{ diagnostic.completed_at }}
                      </p>
                      <div class="mt-3 flex items-center gap-3">
                        <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                          {{ diagnostic.overall_health_score || diagnostic.overall_score || 0 }}/100
                        </div>
                        <div class="flex-1 h-2 bg-emerald-200 dark:bg-emerald-800 rounded-full overflow-hidden">
                          <div
                            class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all"
                            :style="{ width: `${diagnostic.overall_health_score || diagnostic.overall_score || 0}%` }"
                          ></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div
                  v-else
                  class="p-4 sm:p-5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800"
                >
                  <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-amber-500 flex items-center justify-center shadow-lg shadow-amber-500/25">
                      <ExclamationTriangleIcon class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <h3 class="font-semibold text-amber-800 dark:text-amber-200">Diagnostika topilmadi</h3>
                      <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                        AI tavsiyalar uchun avval diagnostika o'tkazish tavsiya etiladi
                      </p>
                      <Link
                        href="/business/diagnostic"
                        class="inline-flex items-center mt-3 text-sm font-medium text-amber-700 dark:text-amber-300 hover:underline"
                      >
                        Diagnostika o'tkazish
                        <ArrowRightIcon class="w-4 h-4 ml-1" />
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 2: Vision and Goals -->
            <div v-else-if="currentStep === 2" key="step2">
              <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 text-white shadow-lg shadow-purple-500/25">
                  <FlagIcon class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Vizyon va maqsadlar</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-400">Strategik yo'nalishlarni belgilang</p>
                </div>
              </div>

              <div class="space-y-5">
                <!-- Vision -->
                <div>
                  <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Biznes vizyoni
                    <span class="text-slate-400 font-normal ml-1">(ixtiyoriy)</span>
                  </label>
                  <textarea
                    v-model="formData.vision"
                    rows="3"
                    class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all resize-none"
                    placeholder="1 yil ichida nimaga erishmoqchisiz?"
                  ></textarea>
                  <p class="text-xs text-slate-400 mt-1.5">Misol: "O'zbekiston bo'ylab 10 ta yangi filial ochish"</p>
                </div>

                <!-- Goals -->
                <div>
                  <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">
                      Strategik maqsadlar
                    </label>
                    <button
                      @click="addGoal"
                      type="button"
                      class="inline-flex items-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700"
                    >
                      <PlusCircleIcon class="w-5 h-5 mr-1" />
                      Qo'shish
                    </button>
                  </div>

                  <div class="space-y-3">
                    <div
                      v-for="(goal, index) in formData.goals"
                      :key="index"
                      class="group p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors"
                    >
                      <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm font-bold">
                          {{ index + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3">
                            <input
                              v-model="goal.name"
                              type="text"
                              class="w-full px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm"
                              placeholder="Maqsad nomi"
                            />
                            <input
                              v-model="goal.target"
                              type="number"
                              class="w-full px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm"
                              placeholder="Target"
                            />
                            <input
                              v-model="goal.metric"
                              type="text"
                              class="w-full px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm"
                              placeholder="Metrika (%, so'm)"
                            />
                          </div>
                        </div>
                        <button
                          v-if="formData.goals.length > 1"
                          @click="removeGoal(index)"
                          type="button"
                          class="flex-shrink-0 p-2 text-slate-400 hover:text-red-500 dark:hover:text-red-400 sm:opacity-0 sm:group-hover:opacity-100 transition-all"
                        >
                          <TrashIcon class="w-5 h-5" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 3: Financial targets -->
            <div v-else-if="currentStep === 3" key="step3">
              <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white shadow-lg shadow-emerald-500/25">
                  <CurrencyDollarIcon class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Moliyaviy maqsadlar</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-400">Daromad va byudjet rejalarini kiriting</p>
                </div>
              </div>

              <div class="space-y-4">
                <!-- Revenue Target -->
                <div class="p-4 sm:p-5 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-500 flex items-center justify-center">
                      <ArrowTrendingUpIcon class="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <h3 class="font-semibold text-slate-900 dark:text-white text-sm sm:text-base">Yillik daromad maqsadi</h3>
                    </div>
                  </div>
                  <div class="relative">
                    <input
                      v-model.number="formData.revenueTarget"
                      type="number"
                      class="w-full px-4 py-3 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-base sm:text-lg font-semibold pr-16"
                      placeholder="100 000 000"
                    />
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-500">so'm</span>
                  </div>
                  <p v-if="formData.revenueTarget" class="text-sm text-emerald-600 dark:text-emerald-400 mt-2">
                    Oylik: {{ formatMoney(Math.round(formData.revenueTarget / 12)) }} so'm
                  </p>
                </div>

                <!-- Budget -->
                <div class="p-4 sm:p-5 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-500 flex items-center justify-center">
                      <BanknotesIcon class="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <h3 class="font-semibold text-slate-900 dark:text-white text-sm sm:text-base">Marketing byudjeti</h3>
                    </div>
                  </div>
                  <div class="relative">
                    <input
                      v-model.number="formData.annualBudget"
                      type="number"
                      class="w-full px-4 py-3 rounded-xl border border-blue-300 dark:border-blue-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-base sm:text-lg font-semibold pr-16"
                      placeholder="10 000 000"
                    />
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-500">so'm</span>
                  </div>
                  <p v-if="formData.annualBudget && formData.revenueTarget" class="text-sm text-blue-600 dark:text-blue-400 mt-2">
                    Daromadning {{ ((formData.annualBudget / formData.revenueTarget) * 100).toFixed(1) }}%
                  </p>
                </div>

                <!-- Customer Target -->
                <div class="p-4 sm:p-5 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-lg bg-purple-500 flex items-center justify-center">
                      <UserGroupIcon class="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <h3 class="font-semibold text-slate-900 dark:text-white text-sm sm:text-base">Yangi mijozlar maqsadi</h3>
                    </div>
                  </div>
                  <div class="relative">
                    <input
                      v-model.number="formData.customerTarget"
                      type="number"
                      class="w-full px-4 py-3 rounded-xl border border-purple-300 dark:border-purple-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 text-base sm:text-lg font-semibold pr-20"
                      placeholder="1000"
                    />
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-500">mijoz</span>
                  </div>
                  <p v-if="formData.customerTarget && formData.annualBudget" class="text-sm text-purple-600 dark:text-purple-400 mt-2">
                    CAC: {{ formatMoney(Math.round(formData.annualBudget / formData.customerTarget)) }} so'm/mijoz
                  </p>
                </div>
              </div>
            </div>

            <!-- Step 4: Channels -->
            <div v-else-if="currentStep === 4" key="step4">
              <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 text-white shadow-lg shadow-cyan-500/25">
                  <MegaphoneIcon class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Marketing kanallari</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-400">Asosiy marketing kanallarini tanlang</p>
                </div>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <button
                  v-for="channel in availableChannels"
                  :key="channel.value"
                  @click="toggleChannel(channel.value)"
                  type="button"
                  class="relative p-4 rounded-xl border-2 transition-all duration-200 text-left"
                  :class="formData.channels.includes(channel.value)
                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 shadow-lg shadow-indigo-500/10'
                    : 'border-slate-200 dark:border-slate-600 hover:border-indigo-300 dark:hover:border-indigo-700 bg-white dark:bg-slate-800'"
                >
                  <div
                    v-if="formData.channels.includes(channel.value)"
                    class="absolute top-2 right-2 w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center"
                  >
                    <CheckIcon class="w-3 h-3 text-white" />
                  </div>
                  <div
                    class="w-10 h-10 rounded-lg flex items-center justify-center mb-2 transition-colors"
                    :class="formData.channels.includes(channel.value)
                      ? channel.bgColor
                      : 'bg-slate-100 dark:bg-slate-700'"
                  >
                    <component
                      :is="channel.icon"
                      class="w-5 h-5"
                      :class="formData.channels.includes(channel.value) ? 'text-white' : 'text-slate-400'"
                    />
                  </div>
                  <h3
                    class="font-semibold text-sm"
                    :class="formData.channels.includes(channel.value) ? 'text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-400'"
                  >
                    {{ channel.label }}
                  </h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ channel.description }}</p>
                </button>
              </div>

              <div v-if="formData.channels.length > 0" class="mt-4 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                <p class="text-sm text-indigo-700 dark:text-indigo-300">
                  <span class="font-semibold">{{ formData.channels.length }}</span> ta kanal tanlandi
                </p>
              </div>
            </div>

            <!-- Step 5: Focus areas -->
            <div v-else-if="currentStep === 5" key="step5">
              <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-500/25">
                  <LightBulbIcon class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Fokus sohalar</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-400">3-5 ta asosiy soha</p>
                </div>
              </div>

              <div class="space-y-3">
                <div
                  v-for="(area, index) in formData.focusAreas"
                  :key="index"
                  class="group flex items-center gap-3"
                >
                  <div
                    class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold transition-colors"
                    :class="area
                      ? 'bg-gradient-to-br from-amber-500 to-orange-500 text-white'
                      : 'bg-slate-100 dark:bg-slate-700 text-slate-400'"
                  >
                    {{ index + 1 }}
                  </div>
                  <input
                    v-model="formData.focusAreas[index]"
                    type="text"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-sm"
                    :placeholder="focusAreaPlaceholders[index] || 'Fokus soha'"
                  />
                  <button
                    v-if="formData.focusAreas.length > 1"
                    @click="removeFocusArea(index)"
                    type="button"
                    class="flex-shrink-0 p-2 text-slate-400 hover:text-red-500 dark:hover:text-red-400 sm:opacity-0 sm:group-hover:opacity-100 transition-all"
                  >
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>

                <button
                  v-if="formData.focusAreas.length < 5"
                  @click="addFocusArea"
                  type="button"
                  class="flex items-center justify-center w-full py-3 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl text-slate-500 dark:text-slate-400 hover:border-amber-400 hover:text-amber-600 transition-colors"
                >
                  <PlusIcon class="w-5 h-5 mr-2" />
                  Fokus soha qo'shish
                </button>

                <!-- Suggestions -->
                <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                  <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">
                    Tavsiyalar:
                  </h4>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="suggestion in focusSuggestions"
                      :key="suggestion"
                      @click="addSuggestedFocus(suggestion)"
                      type="button"
                      class="px-3 py-1.5 text-xs bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-700 text-amber-700 dark:text-amber-300 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors"
                    >
                      + {{ suggestion }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 6: Review -->
            <div v-else-if="currentStep === 6" key="step6">
              <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 text-white shadow-lg shadow-green-500/25">
                  <ClipboardDocumentCheckIcon class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Tekshirish</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-400">Ma'lumotlarni tasdiqlang</p>
                </div>
              </div>

              <div class="space-y-3">
                <!-- Basic Info -->
                <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                  <h3 class="flex items-center text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    <Cog6ToothIcon class="w-4 h-4 mr-2" />
                    Asosiy
                  </h3>
                  <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-slate-500 dark:text-slate-400">Yil:</div>
                    <div class="font-semibold text-slate-900 dark:text-white">{{ formData.year }}-yil</div>
                    <div class="text-slate-500 dark:text-slate-400">AI:</div>
                    <div class="font-semibold" :class="formData.useAI ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-900 dark:text-white'">
                      {{ formData.useAI ? 'Ha' : 'Yo\'q' }}
                    </div>
                  </div>
                </div>

                <!-- Financial -->
                <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                  <h3 class="flex items-center text-sm font-semibold text-emerald-700 dark:text-emerald-300 mb-2">
                    <CurrencyDollarIcon class="w-4 h-4 mr-2" />
                    Moliyaviy
                  </h3>
                  <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-emerald-600 dark:text-emerald-400">Daromad:</div>
                    <div class="font-semibold text-emerald-700 dark:text-emerald-300">{{ formatMoney(formData.revenueTarget) }} so'm</div>
                    <div class="text-emerald-600 dark:text-emerald-400">Byudjet:</div>
                    <div class="font-semibold text-emerald-700 dark:text-emerald-300">{{ formatMoney(formData.annualBudget) }} so'm</div>
                    <div class="text-emerald-600 dark:text-emerald-400">Mijozlar:</div>
                    <div class="font-semibold text-emerald-700 dark:text-emerald-300">{{ formData.customerTarget || 0 }} ta</div>
                  </div>
                </div>

                <!-- Vision -->
                <div v-if="formData.vision" class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                  <h3 class="flex items-center text-sm font-semibold text-purple-700 dark:text-purple-300 mb-2">
                    <EyeIcon class="w-4 h-4 mr-2" />
                    Vizyon
                  </h3>
                  <p class="text-sm text-purple-700 dark:text-purple-300">{{ formData.vision }}</p>
                </div>

                <!-- Channels -->
                <div class="p-4 bg-cyan-50 dark:bg-cyan-900/20 rounded-xl">
                  <h3 class="flex items-center text-sm font-semibold text-cyan-700 dark:text-cyan-300 mb-2">
                    <MegaphoneIcon class="w-4 h-4 mr-2" />
                    Kanallar
                  </h3>
                  <div class="flex flex-wrap gap-1.5">
                    <span
                      v-for="channel in formData.channels"
                      :key="channel"
                      class="px-2 py-1 bg-cyan-100 dark:bg-cyan-800/50 text-cyan-700 dark:text-cyan-300 rounded-lg text-xs font-medium"
                    >
                      {{ getChannelLabel(channel) }}
                    </span>
                  </div>
                </div>

                <!-- Focus Areas -->
                <div v-if="formData.focusAreas.some(a => a)" class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                  <h3 class="flex items-center text-sm font-semibold text-amber-700 dark:text-amber-300 mb-2">
                    <LightBulbIcon class="w-4 h-4 mr-2" />
                    Fokus sohalar
                  </h3>
                  <div class="flex flex-wrap gap-1.5">
                    <span
                      v-for="(area, index) in formData.focusAreas.filter(a => a)"
                      :key="area"
                      class="inline-flex items-center px-2 py-1 bg-amber-100 dark:bg-amber-800/50 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-medium"
                    >
                      {{ index + 1 }}. {{ area }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </transition>
        </div>

        <!-- Footer Actions -->
        <div class="px-5 sm:px-6 lg:px-8 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <button
              v-if="currentStep > 1"
              @click="prevStep"
              type="button"
              class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
            >
              <ChevronLeftIcon class="w-4 h-4 mr-1.5" />
              Orqaga
            </button>
            <div v-else></div>

            <div class="flex items-center gap-3">
              <span class="text-sm text-slate-500 dark:text-slate-400 hidden sm:block">
                {{ currentStep }}/{{ steps.length }}
              </span>

              <button
                v-if="currentStep < steps.length"
                @click="nextStep"
                :disabled="!canProceed"
                type="button"
                class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl hover:from-indigo-600 hover:to-purple-600 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-indigo-500/25 transition-all"
              >
                Keyingi
                <ChevronRightIcon class="w-4 h-4 ml-1.5" />
              </button>

              <button
                v-else
                @click="createStrategy"
                :disabled="loading"
                type="button"
                class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl hover:from-green-600 hover:to-emerald-600 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-green-500/25 transition-all"
              >
                <template v-if="loading">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Yaratilmoqda...
                </template>
                <template v-else>
                  <RocketLaunchIcon class="w-4 h-4 mr-1.5" />
                  Yaratish
                </template>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed, markRaw } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
  ArrowLeftIcon,
  ArrowRightIcon,
  SparklesIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  PlusCircleIcon,
  TrashIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  CheckIcon,
  Cog6ToothIcon,
  FlagIcon,
  CurrencyDollarIcon,
  MegaphoneIcon,
  LightBulbIcon,
  ClipboardDocumentCheckIcon,
  ArrowTrendingUpIcon,
  BanknotesIcon,
  UserGroupIcon,
  EyeIcon,
  RocketLaunchIcon,
  GlobeAltIcon,
  ChatBubbleLeftRightIcon,
  FilmIcon,
  MusicalNoteIcon,
  MagnifyingGlassIcon,
  PhotoIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  step: Number,
  year: Number,
  templates: Object,
  diagnostic: Object,
  existing_strategy: Object,
  business: Object,
});

const loading = ref(false);
const currentStep = ref(props.step || 1);

const steps = [
  { id: 1, title: 'Sozlamalar', description: 'Yil va AI', icon: markRaw(Cog6ToothIcon) },
  { id: 2, title: 'Maqsadlar', description: 'Vizyon', icon: markRaw(FlagIcon) },
  { id: 3, title: 'Moliya', description: 'Byudjet', icon: markRaw(CurrencyDollarIcon) },
  { id: 4, title: 'Kanallar', description: 'Marketing', icon: markRaw(MegaphoneIcon) },
  { id: 5, title: 'Fokus', description: 'Yo\'nalish', icon: markRaw(LightBulbIcon) },
  { id: 6, title: 'Tekshirish', description: 'Tasdiqlash', icon: markRaw(ClipboardDocumentCheckIcon) },
];

const availableYears = computed(() => {
  const current = new Date().getFullYear();
  return [current, current + 1];
});

const availableChannels = [
  { value: 'instagram', label: 'Instagram', description: 'Vizual kontent', icon: markRaw(PhotoIcon), bgColor: 'bg-gradient-to-br from-pink-500 to-purple-500' },
  { value: 'telegram', label: 'Telegram', description: 'Kanallar', icon: markRaw(ChatBubbleLeftRightIcon), bgColor: 'bg-gradient-to-br from-blue-500 to-cyan-500' },
  { value: 'facebook', label: 'Facebook', description: 'Keng auditoriya', icon: markRaw(GlobeAltIcon), bgColor: 'bg-gradient-to-br from-blue-600 to-blue-800' },
  { value: 'tiktok', label: 'TikTok', description: 'Qisqa video', icon: markRaw(FilmIcon), bgColor: 'bg-gradient-to-br from-slate-800 to-slate-900' },
  { value: 'youtube', label: 'YouTube', description: 'Video kontent', icon: markRaw(MusicalNoteIcon), bgColor: 'bg-gradient-to-br from-red-500 to-red-600' },
  { value: 'google', label: 'Google Ads', description: 'Qidiruv', icon: markRaw(MagnifyingGlassIcon), bgColor: 'bg-gradient-to-br from-green-500 to-blue-500' },
];

const focusAreaPlaceholders = [
  'Mijozlar xizmatini yaxshilash',
  'Onlayn savdoni rivojlantirish',
  'Brend taniqliligini oshirish',
  'Yangi bozorga chiqish',
  'Xarajatlarni optimallashtirish',
];

const focusSuggestions = [
  'Mijozlar tajribasi',
  'Raqamli transformatsiya',
  'Savdo optimizatsiyasi',
  'Kontent marketing',
  'Sodiqlik dasturi',
];

const formData = ref({
  year: props.year || new Date().getFullYear(),
  useAI: true,
  vision: '',
  goals: [{ name: '', target: null, metric: '' }],
  revenueTarget: null,
  annualBudget: null,
  customerTarget: null,
  channels: ['instagram', 'telegram'],
  focusAreas: [''],
});

const canProceed = computed(() => {
  switch (currentStep.value) {
    case 3:
      return formData.value.revenueTarget || formData.value.annualBudget;
    case 4:
      return formData.value.channels.length > 0;
    default:
      return true;
  }
});

function goToStep(stepId) {
  if (stepId <= currentStep.value) {
    currentStep.value = stepId;
  }
}

function prevStep() {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
}

function nextStep() {
  if (currentStep.value < steps.length && canProceed.value) {
    currentStep.value++;
  }
}

function addGoal() {
  formData.value.goals.push({ name: '', target: null, metric: '' });
}

function removeGoal(index) {
  if (formData.value.goals.length > 1) {
    formData.value.goals.splice(index, 1);
  }
}

function addFocusArea() {
  if (formData.value.focusAreas.length < 5) {
    formData.value.focusAreas.push('');
  }
}

function removeFocusArea(index) {
  if (formData.value.focusAreas.length > 1) {
    formData.value.focusAreas.splice(index, 1);
  }
}

function addSuggestedFocus(suggestion) {
  const emptyIndex = formData.value.focusAreas.findIndex(a => !a);
  if (emptyIndex !== -1) {
    formData.value.focusAreas[emptyIndex] = suggestion;
  } else if (formData.value.focusAreas.length < 5) {
    formData.value.focusAreas.push(suggestion);
  }
}

function toggleChannel(value) {
  const index = formData.value.channels.indexOf(value);
  if (index === -1) {
    formData.value.channels.push(value);
  } else {
    formData.value.channels.splice(index, 1);
  }
}

function getChannelLabel(value) {
  const channel = availableChannels.find(c => c.value === value);
  return channel?.label || value;
}

function formatMoney(value) {
  if (!value) return '0';
  return value.toLocaleString('uz-UZ');
}

async function createStrategy() {
  loading.value = true;

  const data = {
    year: formData.value.year,
    use_ai: formData.value.useAI,
    vision_statement: formData.value.vision,
    revenue_target: formData.value.revenueTarget,
    annual_budget: formData.value.annualBudget,
    customer_target: formData.value.customerTarget,
    strategic_goals: formData.value.goals.filter(g => g.name),
    focus_areas: formData.value.focusAreas.filter(a => a),
    primary_channels: formData.value.channels,
  };

  router.post('/business/strategy/annual', data, {
    onFinish: () => {
      loading.value = false;
    },
  });
}
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateX(20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>
