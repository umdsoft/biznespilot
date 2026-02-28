<template>
  <Head title="Telegram Bot sozlash" />
  <BusinessLayout title="Telegram Bot sozlash">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

      <!-- Header -->
      <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
          Telegram Bot sozlash
        </h1>
        <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1">
          Telegram botingizni 4 bosqichda sozlang va ishga tushiring
        </p>
      </div>

      <!-- Progress Stepper -->
      <div class="mb-6 sm:mb-8 bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <template v-for="(s, index) in steps" :key="s.id">
            <div class="flex items-center">
              <button
                @click="goToStep(s.id)"
                :disabled="!isStepAccessible(s.id)"
                class="relative flex items-center justify-center w-10 h-10 sm:w-11 sm:h-11 rounded-full transition-all duration-300"
                :class="[
                  completedSteps.includes(s.id) && s.id !== currentStep
                    ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/25 cursor-pointer'
                    : s.id === currentStep
                      ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 ring-4 ring-emerald-100 dark:ring-emerald-900/50'
                      : 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500'
                ]"
              >
                <component
                  :is="completedSteps.includes(s.id) && s.id !== currentStep ? CheckIcon : s.icon"
                  class="w-5 h-5"
                />
              </button>
              <div class="ml-3 hidden sm:block">
                <p
                  class="text-sm font-semibold"
                  :class="s.id <= currentStep || completedSteps.includes(s.id) ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'"
                >
                  {{ s.title }}
                </p>
                <p
                  class="text-xs"
                  :class="s.id <= currentStep || completedSteps.includes(s.id) ? 'text-slate-500 dark:text-slate-400' : 'text-slate-300 dark:text-slate-600'"
                >
                  {{ s.description }}
                </p>
              </div>
            </div>
            <div
              v-if="index < steps.length - 1"
              class="flex-1 h-0.5 mx-2 sm:mx-4 rounded-full transition-colors"
              :class="completedSteps.includes(s.id) ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'"
            ></div>
          </template>
        </div>
        <!-- Mobile Step Label -->
        <div class="sm:hidden mt-3 text-center">
          <p class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ currentStepInfo.title }}
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ currentStepInfo.description }}
          </p>
        </div>
      </div>

      <!-- Main Content Card -->
      <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-5 sm:p-6 lg:p-8">
          <transition name="fade-slide" mode="out-in">

            <!-- Step 1: Bot Type Selection -->
            <div v-if="currentStep === 1" key="step-type">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-emerald-500 text-white flex items-center justify-center">
                  <SparklesIcon class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg font-bold text-slate-900 dark:text-white">Bot turini tanlang</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-400">Qanday turdagi bot yaratmoqchisiz?</p>
                </div>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                <button
                  v-for="bt in botTypes"
                  :key="bt.key"
                  @click="!isTypeUsed(bt.key) && (selectedBotType = bt.key)"
                  type="button"
                  :disabled="isTypeUsed(bt.key)"
                  class="flex flex-col items-center p-4 rounded-xl border-2 transition-all text-center relative"
                  :class="isTypeUsed(bt.key)
                    ? 'border-slate-200 dark:border-slate-700 opacity-50 cursor-not-allowed'
                    : selectedBotType === bt.key
                      ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 shadow-sm'
                      : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
                >
                  <span class="text-2xl mb-2">{{ bt.icon }}</span>
                  <span class="text-sm font-semibold" :class="isTypeUsed(bt.key) ? 'text-slate-400 dark:text-slate-500' : 'text-slate-900 dark:text-white'">{{ bt.label }}</span>
                  <span v-if="isTypeUsed(bt.key)" class="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5 font-medium">Allaqachon mavjud</span>
                  <span v-else class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ bt.description }}</span>
                </button>
              </div>
            </div>

            <!-- Step 2: Store Info -->
            <div v-else-if="currentStep === 2" key="step-info">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-emerald-500 text-white flex items-center justify-center">
                  <BuildingStorefrontIcon class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg font-bold text-slate-900 dark:text-white">Bot ma'lumotlari</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-400">Asosiy ma'lumotlarni kiriting</p>
                </div>
              </div>

              <div class="space-y-5">
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Bot nomi *</label>
                  <input
                    v-model="storeForm.name"
                    type="text"
                    placeholder="Masalan: Baraka Market"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                  />
                  <p v-if="storeForm.errors.name" class="mt-1 text-sm text-red-500">{{ storeForm.errors.name }}</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tavsif</label>
                  <textarea
                    v-model="storeForm.description"
                    rows="3"
                    placeholder="Botingiz haqida qisqacha ma'lumot"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                  ></textarea>
                  <p v-if="storeForm.errors.description" class="mt-1 text-sm text-red-500">{{ storeForm.errors.description }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                  <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Telefon raqam *</label>
                    <input
                      v-model="storeForm.phone"
                      type="text"
                      placeholder="+998 90 123 45 67"
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                    />
                    <p v-if="storeForm.errors.phone" class="mt-1 text-sm text-red-500">{{ storeForm.errors.phone }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Manzil</label>
                    <input
                      v-model="storeForm.address"
                      type="text"
                      placeholder="Toshkent sh., Chilonzor t."
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                    />
                    <p v-if="storeForm.errors.address" class="mt-1 text-sm text-red-500">{{ storeForm.errors.address }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 3: Telegram Bot -->
            <div v-else-if="currentStep === 3" key="step-bot">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-emerald-500 text-white flex items-center justify-center">
                  <ChatBubbleLeftRightIcon class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg font-bold text-slate-900 dark:text-white">Telegram bot ulash</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-400">Mavjud botni tanlang yoki yangi token kiriting</p>
                </div>
              </div>

              <!-- Connected bot status -->
              <div v-if="connectedBot" class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl mb-5">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <CheckCircleIcon class="w-6 h-6 text-emerald-500 flex-shrink-0" />
                    <div>
                      <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Bot ulangan</p>
                      <p class="text-sm text-emerald-600 dark:text-emerald-400">@{{ connectedBot.username }} - {{ connectedBot.first_name }}</p>
                    </div>
                  </div>
                  <button @click="connectedBot = null; selectedExistingBot = null" class="text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    Boshqa bot tanlash
                  </button>
                </div>
              </div>

              <div v-else class="space-y-5">
                <!-- Existing bots selection -->
                <div v-if="existingBots.length > 0">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Mavjud botlardan tanlang</label>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button
                      v-for="eb in existingBots"
                      :key="eb.id"
                      @click="selectedExistingBot = eb.id"
                      type="button"
                      class="flex items-center gap-3 p-4 rounded-xl border-2 transition-all text-left"
                      :class="selectedExistingBot === eb.id
                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                        : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
                    >
                      <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0">
                        <ChatBubbleLeftRightIcon class="w-5 h-5 text-white" />
                      </div>
                      <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ eb.first_name }}</p>
                        <p class="text-xs text-blue-500">@{{ eb.username }}</p>
                      </div>
                      <CheckCircleIcon v-if="selectedExistingBot === eb.id" class="w-5 h-5 text-emerald-500 ml-auto flex-shrink-0" />
                    </button>
                  </div>

                  <button
                    v-if="selectedExistingBot"
                    @click="connectExistingBot"
                    :disabled="connectingBot"
                    class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg v-if="connectingBot" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Botni ulash</span>
                  </button>

                  <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200 dark:border-slate-700"></div></div>
                    <div class="relative flex justify-center"><span class="bg-white dark:bg-slate-800 px-4 text-sm text-slate-400">yoki</span></div>
                  </div>
                </div>

                <!-- New bot token input -->
                <div>
                  <div class="p-4 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl mb-4">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-2">Yangi bot yaratish:</h4>
                    <ol class="text-sm text-slate-600 dark:text-slate-400 space-y-1 list-decimal list-inside">
                      <li>Telegram da @BotFather ga yozing</li>
                      <li>/newbot buyrug'ini yuboring</li>
                      <li>Bot nomini va username kiriting</li>
                      <li>Olingan tokenni pastga joylashtiring</li>
                    </ol>
                  </div>

                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Bot Token</label>
                  <input
                    v-model="botForm.bot_token"
                    type="text"
                    placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors font-mono text-sm"
                  />
                  <p v-if="botForm.errors.bot_token" class="mt-1 text-sm text-red-500">{{ botForm.errors.bot_token }}</p>

                  <button
                    @click="verifyBot"
                    :disabled="botForm.processing || !botForm.bot_token"
                    class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg v-if="botForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Tekshirish va ulash</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Step 4: Activate -->
            <div v-else-if="currentStep === 4" key="step-activate">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-emerald-500 text-white flex items-center justify-center">
                  <RocketLaunchIcon class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg font-bold text-slate-900 dark:text-white">Botni faollashtirish</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-400">Barcha sozlamalarni tekshiring va faollashtiring</p>
                </div>
              </div>

              <!-- Summary -->
              <div class="space-y-3 mb-8">
                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/30">
                  <div class="flex items-center justify-between mb-1.5">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Bot turi</h4>
                    <button @click="goToStep(1)" class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-medium">Tahrirlash</button>
                  </div>
                  <p class="text-sm text-slate-600 dark:text-slate-400">{{ botTypes.find(bt => bt.key === selectedBotType)?.label || selectedBotType }}</p>
                </div>

                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/30">
                  <div class="flex items-center justify-between mb-1.5">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Bot ma'lumotlari</h4>
                    <button @click="goToStep(2)" class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-medium">Tahrirlash</button>
                  </div>
                  <div class="text-sm text-slate-600 dark:text-slate-400 space-y-0.5">
                    <p>{{ storeForm.name || '-' }}</p>
                    <p>{{ storeForm.phone || '-' }} &middot; {{ storeForm.address || '-' }}</p>
                  </div>
                </div>

                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/30">
                  <div class="flex items-center justify-between mb-1.5">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Telegram bot</h4>
                    <button @click="goToStep(3)" class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-medium">Tahrirlash</button>
                  </div>
                  <div class="text-sm">
                    <p v-if="connectedBot" class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                      <CheckCircleIcon class="w-4 h-4" />
                      @{{ connectedBot.username }} — ulangan
                    </p>
                    <p v-else class="text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                      <ExclamationTriangleIcon class="w-4 h-4" />
                      Bot ulanmagan
                    </p>
                  </div>
                </div>

              </div>

              <!-- Activate Button -->
              <div class="text-center">
                <button
                  @click="activateStore"
                  :disabled="activating || !storeForm.name"
                  class="inline-flex items-center gap-2.5 px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-sm hover:shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed text-base"
                >
                  <RocketLaunchIcon class="w-5 h-5" />
                  <span>Botni faollashtirish</span>
                </button>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                  Faollashtirilgandan so'ng, bot Telegram orqali ishga tushadi
                </p>
              </div>
            </div>

          </transition>
        </div>

        <!-- Footer Navigation -->
        <div class="px-5 sm:px-6 lg:px-8 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
          <button
            v-if="currentStep > 1"
            @click="prevStep"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors"
          >
            <ArrowLeftIcon class="w-4 h-4" />
            Orqaga
          </button>
          <div v-else></div>

          <button
            v-if="currentStep < 4"
            @click="nextStep"
            :disabled="!canProceed"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span>Keyingi</span>
            <ArrowRightIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
  BuildingStorefrontIcon,
  ChatBubbleLeftRightIcon,
  RocketLaunchIcon,
  SparklesIcon,
  CheckIcon,
  CheckCircleIcon,
  ArrowLeftIcon,
  ArrowRightIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  step: { type: Number, default: 1 },
  completedSteps: { type: Array, default: () => [] },
  store: { type: Object, default: null },
  bot: { type: Object, default: null },
  existingBots: { type: Array, default: () => [] },
  botTypes: { type: Array, default: () => [] },
  preSelectedType: { type: String, default: null },
  usedTypes: { type: Array, default: () => [] },
});

const currentStep = ref(props.step);
const completedSteps = ref(props.completedSteps);
const activating = ref(false);
const connectingBot = ref(false);
// Allaqachon ishlatilgan turni tekshirish (har turdan faqat 1 ta ruxsat)
const isTypeUsed = (type) => props.usedTypes.includes(type);

// Default turni tanlash — agar tanlangan tur allaqachon ishlatilgan bo'lsa, birinchi bo'sh turni olish
const getDefaultType = () => {
  const preferred = props.preSelectedType || props.store?.store_type || 'ecommerce';
  if (!props.usedTypes.includes(preferred)) return preferred;
  const available = props.botTypes.find(bt => !props.usedTypes.includes(bt.key));
  return available?.key || preferred;
};
const selectedBotType = ref(getDefaultType());
const enabledFeatures = ref(props.store?.enabled_features || []);
const selectedExistingBot = ref(null);
const connectedBot = ref(props.bot);

const steps = [
  { id: 1, title: 'Bot turi', description: 'Bot turini tanlang', icon: SparklesIcon },
  { id: 2, title: "Bot ma'lumotlari", description: 'Asosiy sozlamalar', icon: BuildingStorefrontIcon },
  { id: 3, title: 'Telegram bot', description: 'Botni ulash', icon: ChatBubbleLeftRightIcon },
  { id: 4, title: 'Faollashtirish', description: "Botni ishga tushirish", icon: RocketLaunchIcon },
];

const storeForm = useForm({
  name: props.store?.name || '',
  description: props.store?.description || '',
  phone: props.store?.phone || '',
  address: props.store?.address || '',
  currency: props.store?.currency || 'UZS',
  store_type: props.preSelectedType || props.store?.store_type || 'ecommerce',
  enabled_features: props.store?.enabled_features || [],
});

const botForm = useForm({
  bot_token: '',
});

const currentStepInfo = computed(() => {
  return steps.find(s => s.id === currentStep.value) || steps[0];
});

const canProceed = computed(() => {
  if (currentStep.value === 1) return !!selectedBotType.value && !isTypeUsed(selectedBotType.value);
  if (currentStep.value === 2) return !!storeForm.name;
  if (currentStep.value === 3) return !!connectedBot.value;
  return true;
});

const isStepAccessible = (stepId) => {
  // Can go to current step, any previous step, or any completed step
  return stepId <= currentStep.value || props.completedSteps.includes(stepId);
};

const goToStep = (stepId) => {
  if (isStepAccessible(stepId)) {
    currentStep.value = stepId;
  }
};

const nextStep = () => {
  if (currentStep.value === 1) {
    storeForm.store_type = selectedBotType.value;
    storeForm.enabled_features = enabledFeatures.value;
    if (!completedSteps.value.includes(1)) completedSteps.value.push(1);
    currentStep.value = 2;
  } else if (currentStep.value === 2) {
    storeForm.post(route('business.store.setup.store'), {
      preserveScroll: true,
      onSuccess: () => {
        if (!completedSteps.value.includes(2)) completedSteps.value.push(2);
        currentStep.value = 3;
      },
    });
  } else if (currentStep.value === 3) {
    currentStep.value = 4;
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const connectExistingBot = () => {
  if (!selectedExistingBot.value) return;
  connectingBot.value = true;
  router.post(route('business.store.setup.connect-existing-bot'), {
    bot_id: selectedExistingBot.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      const bot = props.existingBots.find(b => b.id === selectedExistingBot.value);
      connectedBot.value = bot || { username: '...', first_name: '...' };
      if (!completedSteps.value.includes(3)) completedSteps.value.push(3);
      currentStep.value = 4;
    },
    onFinish: () => {
      connectingBot.value = false;
    },
  });
};

const verifyBot = () => {
  botForm.post(route('business.store.setup.connect-bot'), {
    preserveScroll: true,
    onSuccess: () => {
      if (!completedSteps.value.includes(3)) completedSteps.value.push(3);
      currentStep.value = 4;
    },
  });
};

const activateStore = () => {
  activating.value = true;
  router.post(route('business.store.setup.activate'), {}, {
    onFinish: () => {
      activating.value = false;
    },
  });
};
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
