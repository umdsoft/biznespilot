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
                :disabled="s.id > currentStep"
                class="relative flex items-center justify-center w-10 h-10 sm:w-11 sm:h-11 rounded-full transition-all duration-300"
                :class="[
                  s.id < currentStep
                    ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/25'
                    : s.id === currentStep
                      ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 ring-4 ring-emerald-100 dark:ring-emerald-900/50'
                      : 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500'
                ]"
              >
                <component
                  :is="s.id < currentStep ? CheckIcon : s.icon"
                  class="w-5 h-5"
                />
              </button>
              <div class="ml-3 hidden sm:block">
                <p
                  class="text-sm font-semibold"
                  :class="s.id <= currentStep ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'"
                >
                  {{ s.title }}
                </p>
                <p
                  class="text-xs"
                  :class="s.id <= currentStep ? 'text-slate-500 dark:text-slate-400' : 'text-slate-300 dark:text-slate-600'"
                >
                  {{ s.description }}
                </p>
              </div>
            </div>
            <div
              v-if="index < steps.length - 1"
              class="flex-1 h-0.5 mx-2 sm:mx-4 rounded-full transition-colors"
              :class="s.id < currentStep ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'"
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
                  @click="selectedBotType = bt.key"
                  type="button"
                  class="flex flex-col items-center p-4 rounded-xl border-2 transition-all text-center"
                  :class="selectedBotType === bt.key
                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 shadow-sm'
                    : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'"
                >
                  <span class="text-2xl mb-2">{{ bt.icon }}</span>
                  <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ bt.label }}</span>
                  <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ bt.description }}</span>
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
                  <p class="text-sm text-slate-500 dark:text-slate-400">Telegram bot tokenini kiriting</p>
                </div>
              </div>

              <div v-if="bot" class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                <div class="flex items-center gap-3">
                  <CheckCircleIcon class="w-6 h-6 text-emerald-500 flex-shrink-0" />
                  <div>
                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Bot ulangan</p>
                    <p class="text-sm text-emerald-600 dark:text-emerald-400">@{{ bot.username }} - {{ bot.first_name }}</p>
                  </div>
                </div>
              </div>

              <div v-else class="space-y-5">
                <div class="p-4 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl">
                  <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-2">Bot yaratish qo'llanma:</h4>
                  <ol class="text-sm text-slate-600 dark:text-slate-400 space-y-1 list-decimal list-inside">
                    <li>Telegram da @BotFather ga yozing</li>
                    <li>/newbot buyrug'ini yuboring</li>
                    <li>Bot nomini va username kiriting</li>
                    <li>Olingan tokenni pastga joylashtiring</li>
                  </ol>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Bot Token *</label>
                  <input
                    v-model="botForm.bot_token"
                    type="text"
                    placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors font-mono text-sm"
                  />
                  <p v-if="botForm.errors.bot_token" class="mt-1 text-sm text-red-500">{{ botForm.errors.bot_token }}</p>
                </div>

                <button
                  @click="verifyBot"
                  :disabled="botForm.processing || !botForm.bot_token"
                  class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg v-if="botForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Tekshirish va ulash</span>
                </button>
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
                    <p v-if="bot" class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                      <CheckCircleIcon class="w-4 h-4" />
                      @{{ bot.username }} — ulangan
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
  store: { type: Object, default: null },
  bot: { type: Object, default: null },
  botTypes: { type: Array, default: () => [] },
  preSelectedType: { type: String, default: null },
});

const currentStep = ref(props.step);
const activating = ref(false);
const selectedBotType = ref(props.preSelectedType || props.store?.store_type || 'ecommerce');
const enabledFeatures = ref(props.store?.enabled_features || []);

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
  if (currentStep.value === 1) return !!selectedBotType.value;
  if (currentStep.value === 2) return !!storeForm.name && !!storeForm.phone;
  if (currentStep.value === 3) return true;
  return true;
});

const goToStep = (stepId) => {
  if (stepId <= currentStep.value) {
    currentStep.value = stepId;
  }
};

const nextStep = () => {
  if (currentStep.value === 1) {
    storeForm.store_type = selectedBotType.value;
    storeForm.enabled_features = enabledFeatures.value;
    currentStep.value = 2;
  } else if (currentStep.value === 2) {
    storeForm.post(route('business.store.setup.store'), {
      preserveScroll: true,
      onSuccess: () => {
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

const verifyBot = () => {
  botForm.post(route('business.store.setup.connect-bot'), {
    preserveScroll: true,
    onSuccess: () => {
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
