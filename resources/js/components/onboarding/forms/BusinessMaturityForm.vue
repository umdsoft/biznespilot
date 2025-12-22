<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Revenue Range -->
    <div>
      <label class="block text-sm font-medium text-gray-900 mb-1">
        Oylik tushum qancha?
      </label>
      <p class="text-sm text-gray-500 mb-3">Biznesingizning oylik umumiy tushumi (so'mda). Taxminiy bo'lsa ham bo'ladi.</p>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div
          v-for="range in revenueRanges"
          :key="range.value"
          @click="form.monthly_revenue_range = range.value"
          :class="[
            'relative flex flex-col items-center p-3 rounded-lg border-2 cursor-pointer transition-all text-center',
            form.monthly_revenue_range === range.value
              ? 'border-indigo-500 bg-indigo-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <span class="text-sm font-medium text-gray-900">{{ range.label }}</span>
          <span class="text-xs text-gray-500 mt-1">{{ range.description }}</span>
        </div>
      </div>
    </div>

    <!-- Main Challenges -->
    <div>
      <label class="block text-sm font-medium text-gray-900 mb-1">
        Hozir eng katta muammo nima?
      </label>
      <p class="text-sm text-gray-500 mb-3">Biznesingizni rivojlantirishga to'sqinlik qilayotgan asosiy muammolarni tanlang</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div
          v-for="challenge in challenges"
          :key="challenge.value"
          @click="toggleChallenge(challenge.value)"
          :class="[
            'flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all',
            isChallengeSelected(challenge.value)
              ? 'border-indigo-500 bg-indigo-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            isChallengeSelected(challenge.value)
              ? 'bg-indigo-600 border-indigo-600'
              : 'border-gray-300'
          ]">
            <svg v-if="isChallengeSelected(challenge.value)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">{{ challenge.label }}</span>
            <span class="text-xs text-gray-500">{{ challenge.description }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Infrastructure -->
    <div class="bg-gray-50 rounded-xl p-4">
      <h4 class="font-medium text-gray-900 mb-2">Qanday vositalardan foydalanasiz?</h4>
      <p class="text-sm text-gray-500 mb-4">Biznesingizda qaysi texnologiyalar mavjud?</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div
          @click="form.has_website = !form.has_website"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.has_website ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.has_website ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.has_website" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Web-sayt</span>
            <span class="text-xs text-gray-500">Kompaniya yoki mahsulot haqida sayt bor</span>
          </div>
        </div>
        <div
          @click="form.has_crm = !form.has_crm"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.has_crm ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.has_crm ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.has_crm" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Mijozlar bazasi (CRM)</span>
            <span class="text-xs text-gray-500">AmoCRM, Bitrix24, Excel yoki boshqa tizimda mijozlar ro'yxati bor</span>
          </div>
        </div>
        <div
          @click="form.uses_analytics = !form.uses_analytics"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.uses_analytics ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.uses_analytics ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.uses_analytics" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Statistika kuzatish</span>
            <span class="text-xs text-gray-500">Sayt tashrifchilari, sotuvlar yoki reklama natijalari kuzatiladi</span>
          </div>
        </div>
        <div
          @click="form.has_automation = !form.has_automation"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.has_automation ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.has_automation ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.has_automation" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Avtomatik xabarlar</span>
            <span class="text-xs text-gray-500">Bot, avtomatik SMS yoki email yuborish tizimi mavjud</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Processes -->
    <div class="bg-gray-50 rounded-xl p-4">
      <h4 class="font-medium text-gray-900 mb-2">Biznesingizda qanday tartiblar mavjud?</h4>
      <p class="text-sm text-gray-500 mb-4">Qaysi ishlarda aniq qoidalar va ketma-ketlik bor?</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div
          @click="form.has_documented_processes = !form.has_documented_processes"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.has_documented_processes ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.has_documented_processes ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.has_documented_processes" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Yozma ko'rsatmalar</span>
            <span class="text-xs text-gray-500">Xodimlar uchun ishni qanday bajarish bo'yicha yozilgan qoidalar bor</span>
          </div>
        </div>
        <div
          @click="form.has_sales_process = !form.has_sales_process"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.has_sales_process ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.has_sales_process ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.has_sales_process" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Sotuv tartibi</span>
            <span class="text-xs text-gray-500">Mijoz bilan birinchi aloqadan sotuvgacha aniq bosqichlar bor</span>
          </div>
        </div>
        <div
          @click="form.has_support_process = !form.has_support_process"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.has_support_process ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.has_support_process ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.has_support_process" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Mijozga xizmat ko'rsatish</span>
            <span class="text-xs text-gray-500">Shikoyat va savollarga javob berish tartibi mavjud</span>
          </div>
        </div>
        <div
          @click="form.has_marketing_process = !form.has_marketing_process"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.has_marketing_process ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.has_marketing_process ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.has_marketing_process" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Reklama va targ'ibot</span>
            <span class="text-xs text-gray-500">Kontent chiqarish, reklama berish uchun reja va tartib bor</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Marketing Channels -->
    <div>
      <label class="block text-sm font-medium text-gray-900 mb-1">
        Qayerda reklama qilasiz?
      </label>
      <p class="text-sm text-gray-500 mb-3">Hozirda foydalanayotgan kanallarni tanlang</p>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div
          v-for="channel in marketingChannels"
          :key="channel.value"
          @click="toggleMarketingChannel(channel.value)"
          :class="[
            'flex flex-col items-center p-3 rounded-lg border-2 cursor-pointer transition-all text-center',
            isMarketingChannelSelected(channel.value)
              ? 'border-indigo-500 bg-indigo-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <span class="text-sm font-medium text-gray-900">{{ channel.label }}</span>
          <span class="text-xs text-gray-500 mt-1">{{ channel.description }}</span>
        </div>
      </div>
    </div>

    <!-- Marketing Settings -->
    <div class="bg-gray-50 rounded-xl p-4">
      <h4 class="font-medium text-gray-900 mb-2">Marketing holati</h4>
      <p class="text-sm text-gray-500 mb-4">Reklama va targ'ibot ishlaringiz haqida</p>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div
          @click="form.has_marketing_budget = !form.has_marketing_budget"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.has_marketing_budget ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.has_marketing_budget ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.has_marketing_budget" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Reklama byudjeti</span>
            <span class="text-xs text-gray-500">Oylik reklama uchun ajratilgan mablag' bor</span>
          </div>
        </div>
        <div
          @click="form.tracks_marketing_metrics = !form.tracks_marketing_metrics"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.tracks_marketing_metrics ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.tracks_marketing_metrics ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.tracks_marketing_metrics" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Natijalar kuzatiladi</span>
            <span class="text-xs text-gray-500">Qancha pul sarflangan, qancha mijoz kelgani hisoblanadi</span>
          </div>
        </div>
        <div
          @click="form.has_dedicated_marketing = !form.has_dedicated_marketing"
          class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
          :class="form.has_dedicated_marketing ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-white'"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            form.has_dedicated_marketing ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300'
          ]">
            <svg v-if="form.has_dedicated_marketing" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">Marketolog bor</span>
            <span class="text-xs text-gray-500">Alohida odam yoki bo'lim reklama bilan shug'ullanadi</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Primary Goals -->
    <div>
      <label class="block text-sm font-medium text-gray-900 mb-1">
        Nimaga erishmoqchisiz?
      </label>
      <p class="text-sm text-gray-500 mb-3">Yaqin kelajakdagi asosiy maqsadlaringizni tanlang</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div
          v-for="goal in primaryGoals"
          :key="goal.value"
          @click="toggleGoal(goal.value)"
          :class="[
            'flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all',
            isGoalSelected(goal.value)
              ? 'border-indigo-500 bg-indigo-50'
              : 'border-gray-200 hover:border-gray-300'
          ]"
        >
          <div :class="[
            'w-4 h-4 mt-0.5 rounded border-2 flex items-center justify-center flex-shrink-0',
            isGoalSelected(goal.value)
              ? 'bg-indigo-600 border-indigo-600'
              : 'border-gray-300'
          ]">
            <svg v-if="isGoalSelected(goal.value)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-900 block">{{ goal.label }}</span>
            <span class="text-xs text-gray-500">{{ goal.description }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Info text -->
    <p class="text-sm text-gray-500 text-center">
      Barcha maydonlar ixtiyoriy. Keyinroq to'ldirishingiz mumkin.
    </p>

    <!-- Submit -->
    <div class="flex justify-between gap-3 pt-4">
      <button
        type="button"
        @click="handleSkip"
        class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
      >
        O'tkazib yuborish
      </button>
      <div class="flex gap-3">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
        >
          Bekor qilish
        </button>
        <button
          type="submit"
          :disabled="loading"
          class="px-6 py-3 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Saqlash
        </button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';
import { useToastStore } from '@/stores/toast';

const store = useOnboardingStore();
const toast = useToastStore();

const props = defineProps({
  maturity: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['submit', 'cancel', 'skip']);

const loading = ref(false);

// Form object with guaranteed array initialization
const form = reactive({
  monthly_revenue_range: '',
  main_challenges: [],
  has_website: false,
  has_crm: false,
  uses_analytics: false,
  has_automation: false,
  current_tools: [],
  has_documented_processes: false,
  has_sales_process: false,
  has_support_process: false,
  has_marketing_process: false,
  marketing_channels: [],
  has_marketing_budget: false,
  tracks_marketing_metrics: false,
  has_dedicated_marketing: false,
  primary_goals: [],
  growth_target: ''
});

// Simple toggle functions - NO computed getters!
function isChallengeSelected(value) {
  return Array.isArray(form.main_challenges) && form.main_challenges.includes(value);
}

function toggleChallenge(value) {
  if (!Array.isArray(form.main_challenges)) {
    form.main_challenges = [];
  }
  const index = form.main_challenges.indexOf(value);
  if (index === -1) {
    form.main_challenges.push(value);
  } else {
    form.main_challenges.splice(index, 1);
  }
}

function isMarketingChannelSelected(value) {
  return Array.isArray(form.marketing_channels) && form.marketing_channels.includes(value);
}

function toggleMarketingChannel(value) {
  if (!Array.isArray(form.marketing_channels)) {
    form.marketing_channels = [];
  }
  const index = form.marketing_channels.indexOf(value);
  if (index === -1) {
    form.marketing_channels.push(value);
  } else {
    form.marketing_channels.splice(index, 1);
  }
}

function isGoalSelected(value) {
  return Array.isArray(form.primary_goals) && form.primary_goals.includes(value);
}

function toggleGoal(value) {
  if (!Array.isArray(form.primary_goals)) {
    form.primary_goals = [];
  }
  const index = form.primary_goals.indexOf(value);
  if (index === -1) {
    form.primary_goals.push(value);
  } else {
    form.primary_goals.splice(index, 1);
  }
}

const revenueRanges = [
  { value: 'none', label: 'Hali yo\'q', description: 'Endigina boshladim' },
  { value: 'under_5m', label: '5 mln gacha', description: 'Boshlang\'ich' },
  { value: '5m_20m', label: '5-20 mln', description: 'Kichik biznes' },
  { value: '20m_50m', label: '20-50 mln', description: 'O\'sish bosqichi' },
  { value: '50m_100m', label: '50-100 mln', description: 'O\'rtacha biznes' },
  { value: '100m_500m', label: '100-500 mln', description: 'Barqaror biznes' },
  { value: '500m_1b', label: '500 mln - 1 mlrd', description: 'Yirik biznes' },
  { value: 'over_1b', label: '1 mlrd+', description: 'Katta kompaniya' }
];

const challenges = [
  { value: 'customer_acquisition', label: 'Yangi mijoz topish', description: 'Yangi mijozlar kam keladi, ko\'proq mijoz kerak' },
  { value: 'lead_generation', label: 'Lid yig\'ish', description: 'So\'rovlar kam, qiziquvchilar yetarli emas' },
  { value: 'sales_conversion', label: 'Sotuvni yopish', description: 'Mijoz bor, lekin sotib olmaydi' },
  { value: 'customer_retention', label: 'Mijozni ushlab qolish', description: 'Mijozlar bir marta olib ketadi, qaytmaydi' },
  { value: 'brand_awareness', label: 'Tanilish', description: 'Biznesni kam odam biladi, tanilishimiz past' },
  { value: 'competition', label: 'Raqobat', description: 'Raqobatchilar ko\'p, ulardan ajralib turish qiyin' },
  { value: 'pricing', label: 'Narx belgilash', description: 'Qanday narx qo\'yishni bilmayman' },
  { value: 'team_scaling', label: 'Xodim topish', description: 'Yaxshi ishchi topish qiyin, jamoa o\'sishi kerak' },
  { value: 'cash_flow', label: 'Pul aylanmasi', description: 'Pul yetishmaydi, oqim beqaror' },
  { value: 'technology', label: 'Texnologiya', description: 'Zamonaviy vositalarni joriy qilish kerak' }
];

const marketingChannels = [
  { value: 'instagram', label: 'Instagram', description: 'Rasm va videolar orqali' },
  { value: 'telegram', label: 'Telegram', description: 'Kanal yoki guruh' },
  { value: 'facebook', label: 'Facebook', description: 'Sahifa va reklamalar' },
  { value: 'google_ads', label: 'Google Ads', description: 'Qidiruv reklamalari' },
  { value: 'seo', label: 'SEO', description: 'Google\'da chiqish' },
  { value: 'email', label: 'Email', description: 'Pochta orqali xabarlar' },
  { value: 'sms', label: 'SMS', description: 'Telefonga xabar' },
  { value: 'content', label: 'Kontent', description: 'Foydali maqolalar' },
  { value: 'referral', label: 'Tavsiya', description: 'Mijozlar orqali' },
  { value: 'offline', label: 'Oflayn', description: 'Banner, buklet' }
];

const primaryGoals = [
  { value: 'increase_revenue', label: 'Daromadni oshirish', description: 'Ko\'proq sotuv va pul ishlash' },
  { value: 'expand_market', label: 'Bozorni kengaytirish', description: 'Yangi hududlar yoki mijoz guruhlariga chiqish' },
  { value: 'improve_brand', label: 'Brendni rivojlantirish', description: 'Tanilish va ishonchni oshirish' },
  { value: 'optimize_costs', label: 'Xarajatlarni kamaytirish', description: 'Kam sarf qilib, ko\'proq natija olish' },
  { value: 'automate_processes', label: 'Ishlarni avtomatlashtirish', description: 'Qo\'lda qilinadigan ishlarni kamaytirish' },
  { value: 'improve_customer_experience', label: 'Mijoz xizmatini yaxshilash', description: 'Mijozlar mamnunligini oshirish' }
];

function initializeForm() {
  if (props.maturity) {
    form.monthly_revenue_range = props.maturity.monthly_revenue_range || '';
    form.main_challenges = Array.isArray(props.maturity.main_challenges) ? [...props.maturity.main_challenges] : [];
    form.has_website = props.maturity.has_website || false;
    form.has_crm = props.maturity.has_crm || false;
    form.uses_analytics = props.maturity.uses_analytics || false;
    form.has_automation = props.maturity.has_automation || false;
    form.current_tools = Array.isArray(props.maturity.current_tools) ? [...props.maturity.current_tools] : [];
    form.has_documented_processes = props.maturity.has_documented_processes || false;
    form.has_sales_process = props.maturity.has_sales_process || false;
    form.has_support_process = props.maturity.has_support_process || false;
    form.has_marketing_process = props.maturity.has_marketing_process || false;
    form.marketing_channels = Array.isArray(props.maturity.marketing_channels) ? [...props.maturity.marketing_channels] : [];
    form.has_marketing_budget = props.maturity.has_marketing_budget || false;
    form.tracks_marketing_metrics = props.maturity.tracks_marketing_metrics || false;
    form.has_dedicated_marketing = props.maturity.has_dedicated_marketing || false;
    form.primary_goals = Array.isArray(props.maturity.primary_goals) ? [...props.maturity.primary_goals] : [];
    form.growth_target = props.maturity.growth_target || '';
  }
}

watch(() => props.maturity, () => {
  initializeForm();
}, { immediate: true, deep: true });

async function handleSubmit() {
  loading.value = true;

  try {
    await store.updateMaturityAssessment(form);
    toast.success('Muvaffaqiyatli saqlandi', 'Biznes holati ma\'lumotlari yangilandi');
    emit('submit');
  } catch (err) {
    console.error(err);
    const errorMessage = err.response?.data?.message || 'Ma\'lumotlarni saqlashda xatolik yuz berdi';
    toast.error('Xatolik', errorMessage);
  } finally {
    loading.value = false;
  }
}

function handleSkip() {
  emit('skip');
}
</script>
