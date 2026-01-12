<template>
  <Head title="Strategiya yaratish" />

  <div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <Link href="/business/strategy" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-2">
          <ArrowLeftIcon class="w-4 h-4 mr-1" />
          Strategiyaga qaytish
        </Link>
        <h1 class="text-2xl font-bold text-gray-900">Strategiya yaratish</h1>
        <p class="text-gray-500 mt-1">{{ year }}-yil uchun biznes strategiyangizni yarating</p>
      </div>

      <!-- Wizard -->
      <WizardStep
        :steps="steps"
        :current-step="currentStep"
        :loading="loading"
        :can-proceed="canProceed"
        @prev="prevStep"
        @next="nextStep"
        @complete="createStrategy"
      >
        <!-- Step 1: Year and AI selection -->
        <div v-if="currentStep === 1">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Asosiy sozlamalar</h2>

          <div class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Yil</label>
              <select
                v-model="formData.year"
                class="w-full rounded-lg border-gray-300"
              >
                <option v-for="y in availableYears" :key="y" :value="y">{{ y }}-yil</option>
              </select>
            </div>

            <div>
              <label class="flex items-center space-x-3 p-4 bg-indigo-50 rounded-lg cursor-pointer">
                <input
                  type="checkbox"
                  v-model="formData.useAI"
                  class="rounded text-indigo-600"
                />
                <div>
                  <span class="font-medium text-gray-900 flex items-center">
                    <SparklesIcon class="w-5 h-5 text-indigo-600 mr-2" />
                    AI yordamida yaratish
                  </span>
                  <p class="text-sm text-gray-600 mt-1">
                    Diagnostika natijalariga asoslangan optimal strategiya tavsiya etiladi
                  </p>
                </div>
              </label>
            </div>

            <div v-if="diagnostic" class="p-4 bg-green-50 rounded-lg">
              <div class="flex items-center text-green-700 mb-2">
                <CheckCircleIcon class="w-5 h-5 mr-2" />
                <span class="font-medium">Diagnostika mavjud</span>
              </div>
              <p class="text-sm text-green-600">
                Oxirgi diagnostika: {{ diagnostic.completed_at }} - Ball: {{ diagnostic.overall_health_score }}/100
              </p>
            </div>

            <div v-else class="p-4 bg-amber-50 rounded-lg">
              <div class="flex items-center text-amber-700 mb-2">
                <ExclamationTriangleIcon class="w-5 h-5 mr-2" />
                <span class="font-medium">Diagnostika topilmadi</span>
              </div>
              <p class="text-sm text-amber-600">
                AI tavsiyalar uchun avval diagnostika o'tkazish tavsiya etiladi
              </p>
              <Link href="/business/diagnostic" class="text-sm text-indigo-600 hover:underline mt-2 inline-block">
                Diagnostika o'tkazish
              </Link>
            </div>
          </div>
        </div>

        <!-- Step 2: Vision and Goals -->
        <div v-if="currentStep === 2">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Vizyon va maqsadlar</h2>

          <div class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Biznes vizyoni
                <span class="text-gray-400 font-normal">(ixtiyoriy)</span>
              </label>
              <textarea
                v-model="formData.vision"
                rows="3"
                class="w-full rounded-lg border-gray-300"
                placeholder="1 yil ichida nimaga erishmoqchisiz?"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Strategik maqsadlar</label>
              <div class="space-y-3">
                <div
                  v-for="(goal, index) in formData.goals"
                  :key="index"
                  class="flex items-center space-x-3"
                >
                  <input
                    v-model="goal.name"
                    type="text"
                    class="flex-1 rounded-lg border-gray-300"
                    placeholder="Maqsad nomi"
                  />
                  <input
                    v-model="goal.target"
                    type="number"
                    class="w-24 rounded-lg border-gray-300"
                    placeholder="Target"
                  />
                  <input
                    v-model="goal.metric"
                    type="text"
                    class="w-28 rounded-lg border-gray-300"
                    placeholder="Metrika"
                  />
                  <button
                    @click="removeGoal(index)"
                    class="p-2 text-gray-400 hover:text-red-500"
                  >
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </div>
              <button
                @click="addGoal"
                class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 flex items-center"
              >
                <PlusIcon class="w-4 h-4 mr-1" />
                Maqsad qo'shish
              </button>
            </div>
          </div>
        </div>

        <!-- Step 3: Financial targets -->
        <div v-if="currentStep === 3">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Moliyaviy maqsadlar</h2>

          <div class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Yillik daromad maqsadi (so'm)</label>
              <input
                v-model.number="formData.revenueTarget"
                type="number"
                class="w-full rounded-lg border-gray-300"
                placeholder="100000000"
              />
              <p class="text-sm text-gray-500 mt-1">Yil davomida erishmoqchi bo'lgan umumiy daromad</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Yillik marketing byudjeti (so'm)</label>
              <input
                v-model.number="formData.annualBudget"
                type="number"
                class="w-full rounded-lg border-gray-300"
                placeholder="10000000"
              />
              <p class="text-sm text-gray-500 mt-1">Marketingga ajratilgan yillik byudjet</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Yangi mijozlar soni maqsadi</label>
              <input
                v-model.number="formData.customerTarget"
                type="number"
                class="w-full rounded-lg border-gray-300"
                placeholder="100"
              />
            </div>
          </div>
        </div>

        <!-- Step 4: Channels -->
        <div v-if="currentStep === 4">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Marketing kanallari</h2>

          <div class="space-y-4">
            <p class="text-sm text-gray-600">Asosiy marketing kanallarini tanlang</p>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <label
                v-for="channel in availableChannels"
                :key="channel.value"
                class="flex items-center p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors"
                :class="{ 'ring-2 ring-indigo-500 bg-indigo-50': formData.channels.includes(channel.value) }"
              >
                <input
                  type="checkbox"
                  :value="channel.value"
                  v-model="formData.channels"
                  class="rounded text-indigo-600 mr-3"
                />
                <span class="font-medium text-gray-900">{{ channel.label }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Step 5: Focus areas -->
        <div v-if="currentStep === 5">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Fokus sohalar</h2>

          <div class="space-y-4">
            <p class="text-sm text-gray-600">Yil davomida diqqat qaratmoqchi bo'lgan 3-5 ta asosiy soha</p>

            <div class="space-y-3">
              <div
                v-for="(area, index) in formData.focusAreas"
                :key="index"
                class="flex items-center space-x-3"
              >
                <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-medium">
                  {{ index + 1 }}
                </span>
                <input
                  v-model="formData.focusAreas[index]"
                  type="text"
                  class="flex-1 rounded-lg border-gray-300"
                  placeholder="Fokus soha"
                />
                <button
                  @click="removeFocusArea(index)"
                  class="p-2 text-gray-400 hover:text-red-500"
                >
                  <TrashIcon class="w-5 h-5" />
                </button>
              </div>
            </div>
            <button
              v-if="formData.focusAreas.length < 5"
              @click="addFocusArea"
              class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center"
            >
              <PlusIcon class="w-4 h-4 mr-1" />
              Fokus soha qo'shish
            </button>
          </div>
        </div>

        <!-- Step 6: Review -->
        <div v-if="currentStep === 6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Tekshirish</h2>

          <div class="space-y-4">
            <div class="p-4 bg-gray-50 rounded-lg">
              <h3 class="font-medium text-gray-700 mb-2">Asosiy ma'lumotlar</h3>
              <dl class="grid grid-cols-2 gap-2 text-sm">
                <dt class="text-gray-500">Yil:</dt>
                <dd class="font-medium">{{ formData.year }}</dd>
                <dt class="text-gray-500">AI yaratadi:</dt>
                <dd class="font-medium">{{ formData.useAI ? 'Ha' : 'Yo\'q' }}</dd>
              </dl>
            </div>

            <div v-if="formData.vision" class="p-4 bg-gray-50 rounded-lg">
              <h3 class="font-medium text-gray-700 mb-2">Vizyon</h3>
              <p class="text-sm">{{ formData.vision }}</p>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
              <h3 class="font-medium text-gray-700 mb-2">Moliyaviy maqsadlar</h3>
              <dl class="grid grid-cols-2 gap-2 text-sm">
                <dt class="text-gray-500">Daromad:</dt>
                <dd class="font-medium">{{ formatMoney(formData.revenueTarget) }} so'm</dd>
                <dt class="text-gray-500">Byudjet:</dt>
                <dd class="font-medium">{{ formatMoney(formData.annualBudget) }} so'm</dd>
              </dl>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
              <h3 class="font-medium text-gray-700 mb-2">Kanallar</h3>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="channel in formData.channels"
                  :key="channel"
                  class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-sm"
                >
                  {{ getChannelLabel(channel) }}
                </span>
              </div>
            </div>

            <div v-if="formData.focusAreas.length" class="p-4 bg-gray-50 rounded-lg">
              <h3 class="font-medium text-gray-700 mb-2">Fokus sohalar</h3>
              <ul class="list-disc list-inside text-sm space-y-1">
                <li v-for="area in formData.focusAreas" :key="area">{{ area }}</li>
              </ul>
            </div>
          </div>
        </div>
      </WizardStep>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useStrategyStore } from '@/stores/strategy';
import WizardStep from '@/Components/strategy/WizardStep.vue';
import {
  ArrowLeftIcon,
  SparklesIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  step: Number,
  year: Number,
  templates: Object,
  diagnostic: Object,
  existing_strategy: Object,
  business: Object,
});

const store = useStrategyStore();
const loading = ref(false);
const currentStep = ref(props.step || 1);

const steps = [
  { id: 1, title: 'Sozlamalar', description: 'Yil va AI' },
  { id: 2, title: 'Maqsadlar', description: 'Vizyon va maqsadlar' },
  { id: 3, title: 'Moliya', description: 'Byudjet va targetlar' },
  { id: 4, title: 'Kanallar', description: 'Marketing kanallari' },
  { id: 5, title: 'Fokus', description: 'Asosiy yo\'nalishlar' },
  { id: 6, title: 'Tekshirish', description: 'Ma\'lumotlarni tasdiqlash' },
];

const availableYears = computed(() => {
  const current = new Date().getFullYear();
  return [current, current + 1];
});

const availableChannels = [
  { value: 'instagram', label: 'Instagram' },
  { value: 'telegram', label: 'Telegram' },
  { value: 'facebook', label: 'Facebook' },
  { value: 'tiktok', label: 'TikTok' },
  { value: 'youtube', label: 'YouTube' },
  { value: 'google', label: 'Google Ads' },
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

function prevStep() {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
}

function nextStep() {
  if (currentStep.value < steps.length) {
    currentStep.value++;
  }
}

function addGoal() {
  formData.value.goals.push({ name: '', target: null, metric: '' });
}

function removeGoal(index) {
  formData.value.goals.splice(index, 1);
}

function addFocusArea() {
  if (formData.value.focusAreas.length < 5) {
    formData.value.focusAreas.push('');
  }
}

function removeFocusArea(index) {
  formData.value.focusAreas.splice(index, 1);
}

function getChannelLabel(value) {
  const channel = availableChannels.find(c => c.value === value);
  return channel?.label || value;
}

function formatMoney(value) {
  if (!value) return '0';
  return value.toLocaleString();
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
