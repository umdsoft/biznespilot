<template>
  <AppLayout title="Hamkor bo'ling">
    <div class="max-w-5xl mx-auto">
      <!-- Hero -->
      <div class="relative bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-2xl p-8 md:p-12 text-white mb-8 overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent_50%)]"></div>
        <div class="relative">
          <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur px-3 py-1 rounded-full text-xs font-semibold mb-4">
            <SparklesIcon class="w-4 h-4" />
            Partner dasturi
          </div>
          <h1 class="text-3xl md:text-4xl font-bold mb-3 leading-tight">
            Hamkor bo'ling va 10-20% oylik<br class="hidden md:block" />
            <span class="bg-gradient-to-r from-yellow-300 to-orange-300 bg-clip-text text-transparent">
              passiv daromad qozoning
            </span>
          </h1>
          <p class="text-lg text-indigo-100 max-w-2xl">
            BiznesPilot AI platformasini tavsiya qiling — mijoz to'lov qilganda komissiya oling. Birinchi to'lovda yuqori stavka, keyingi to'lovlarda barqaror passiv daromad.
          </p>
        </div>
      </div>

      <!-- Calculator -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <div class="flex items-center gap-2 mb-4">
          <CalculatorIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daromad kalkulyatori</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Mijozlar soni: <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ clientCount }}</span>
            </label>
            <input
              type="range"
              v-model.number="clientCount"
              min="1"
              max="50"
              class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-600"
            />
            <div class="flex justify-between text-xs text-gray-500 mt-1">
              <span>1</span><span>25</span><span>50</span>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tarif</label>
            <select
              v-model="selectedPlan"
              class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white"
            >
              <option value="299000">Business — 299 000 so'm</option>
              <option value="999000">Enterprise — 999 000 so'm</option>
            </select>
          </div>
          <div class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-lg p-4 border border-emerald-200 dark:border-emerald-800">
            <p class="text-xs text-emerald-700 dark:text-emerald-300 font-medium mb-2">Taxminiy daromad</p>
            <div class="space-y-1.5 mb-3">
              <div class="flex items-baseline justify-between">
                <span class="text-xs text-gray-600 dark:text-gray-400">Birinchi to'lovdan:</span>
                <span class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ formatMoney(firstPaymentIncome) }}</span>
              </div>
              <div class="flex items-baseline justify-between">
                <span class="text-xs text-gray-600 dark:text-gray-400">Keyingi har oy:</span>
                <span class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ formatMoney(monthlyRecurring) }}</span>
              </div>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 border-t border-emerald-200 dark:border-emerald-800 pt-2">
              Masalan: yil oxirigacha mijoz to'lab qolsa — <span class="font-semibold text-emerald-700 dark:text-emerald-300">{{ formatMoney(firstPaymentIncome + monthlyRecurring * 11) }}</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Tier showcase -->
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tier dasturi</h3>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div
          v-for="tier in tiers"
          :key="tier.tier"
          :class="['rounded-xl p-5 border-2', tierBorder(tier.tier)]"
        >
          <div class="text-3xl mb-2">{{ tier.icon }}</div>
          <h4 :class="['font-bold text-lg mb-2', tierText(tier.tier)]">{{ tier.name }}</h4>
          <div class="space-y-1 text-sm mb-3">
            <p class="flex justify-between"><span class="text-gray-500">1-to'lov:</span><span class="font-semibold">{{ (tier.first_payment_rate * 100).toFixed(0) }}%</span></p>
            <p class="flex justify-between"><span class="text-gray-500">Keyingi to'lovlar:</span><span class="font-semibold">{{ (tier.lifetime_rate * 100).toFixed(0) }}%</span></p>
            <p class="flex justify-between"><span class="text-gray-500">Min ref:</span><span class="font-semibold">{{ tier.min_active_referrals }}+</span></p>
          </div>
          <ul v-if="tier.perks?.length" class="space-y-1 pt-3 border-t border-gray-200 dark:border-gray-700">
            <li v-for="(p, i) in tier.perks" :key="i" class="text-xs text-gray-600 dark:text-gray-300 flex gap-1.5">
              <CheckIcon class="w-3.5 h-3.5 text-green-500 shrink-0 mt-0.5" />
              <span>{{ p }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Apply form -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 md:p-8">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Ariza topshirish</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Barcha maydonlarni to'ldiring. Admin 24 soat ichida ko'rib chiqadi.</p>

        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Ism familiya *</label>
              <input v-model="form.full_name" type="text" required class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
              <p v-if="form.errors.full_name" class="text-xs text-red-500 mt-1">{{ form.errors.full_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telefon *</label>
              <input v-model="form.phone" type="tel" required placeholder="+998 90 123 45 67" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
              <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1">{{ form.errors.phone }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telegram</label>
            <input v-model="form.telegram_id" type="text" placeholder="@username" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Partner turi *</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
              <label
                v-for="type in partnerTypes"
                :key="type.value"
                :class="[
                  'flex flex-col items-center gap-1 p-3 rounded-lg border-2 cursor-pointer transition-colors',
                  form.partner_type === type.value
                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300'
                ]"
              >
                <input type="radio" v-model="form.partner_type" :value="type.value" class="sr-only" />
                <component :is="type.icon" class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                <span class="text-xs font-medium text-gray-900 dark:text-gray-100">{{ type.label }}</span>
              </label>
            </div>
            <p v-if="form.errors.partner_type" class="text-xs text-red-500 mt-1">{{ form.errors.partner_type }}</p>
          </div>

          <div v-if="showCompany" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kompaniya nomi</label>
              <input v-model="form.company_name" type="text" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">INN/STIR</label>
              <input v-model="form.inn_stir" type="text" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
            </div>
          </div>

          <label class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg cursor-pointer">
            <input v-model="form.agreement_accepted" type="checkbox" required class="mt-0.5 w-4 h-4 text-indigo-600 rounded" />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Men <a href="/docs/partner-oferta" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">oferta shartnomasi</a> bilan tanishdim va roziman *
            </span>
          </label>
          <p v-if="form.errors.agreement_accepted" class="text-xs text-red-500 -mt-3">{{ form.errors.agreement_accepted }}</p>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full md:w-auto px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:opacity-60 text-white font-semibold rounded-lg transition-all shadow-md"
          >
            {{ form.processing ? 'Yuborilmoqda...' : 'Ariza yuborish' }}
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/PartnerLayout.vue';
import {
  SparklesIcon,
  CalculatorIcon,
  CheckIcon,
  UserIcon,
  BuildingOfficeIcon,
  MegaphoneIcon,
  CodeBracketIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  tiers: { type: Array, default: () => [] },
});

const clientCount = ref(10);
const selectedPlan = ref('299000');

const formatMoney = (v) => new Intl.NumberFormat('uz-UZ').format(Math.round(v || 0)) + " so'm";

// Bronze (default) stavka: birinchi to'lov 10%, keyingi to'lovlar 5%
const FIRST_PAYMENT_RATE = 0.10;
const LIFETIME_RATE = 0.05;

const firstPaymentIncome = computed(() =>
  clientCount.value * Number(selectedPlan.value) * FIRST_PAYMENT_RATE
);

const monthlyRecurring = computed(() =>
  clientCount.value * Number(selectedPlan.value) * LIFETIME_RATE
);

const partnerTypes = [
  { value: 'individual', label: 'Jismoniy shaxs', icon: UserIcon },
  { value: 'agency', label: 'Agentlik', icon: BuildingOfficeIcon },
  { value: 'influencer', label: 'Influenser', icon: MegaphoneIcon },
  { value: 'integrator', label: 'Integrator', icon: CodeBracketIcon },
];

const form = useForm({
  full_name: '',
  phone: '',
  telegram_id: '',
  partner_type: 'individual',
  company_name: '',
  inn_stir: '',
  agreement_accepted: false,
});

const showCompany = computed(() =>
  ['agency', 'integrator'].includes(form.partner_type)
);

const submit = () => {
  form.post(route('partner.apply.submit'));
};

const tierBorder = (t) => ({
  bronze: 'border-amber-300 bg-amber-50 dark:bg-amber-900/10',
  silver: 'border-slate-300 bg-slate-50 dark:bg-slate-800/40',
  gold: 'border-yellow-300 bg-yellow-50 dark:bg-yellow-900/10',
  platinum: 'border-indigo-300 bg-indigo-50 dark:bg-indigo-900/10',
}[t] || 'border-gray-200');

const tierText = (t) => ({
  bronze: 'text-amber-700 dark:text-amber-300',
  silver: 'text-slate-700 dark:text-slate-200',
  gold: 'text-yellow-700 dark:text-yellow-300',
  platinum: 'text-indigo-700 dark:text-indigo-300',
}[t] || 'text-gray-900');
</script>
