<template>
  <BusinessLayout title="Lead Tahrirlash">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-40 -mx-4 -mt-4 sm:-mx-6 lg:-mx-8">
      <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <Link
              href="/sales"
              class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              <ArrowLeftIcon class="w-5 h-5" />
            </Link>
            <div class="flex items-center space-x-3">
              <!-- Avatar -->
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-lg shadow-lg">
                {{ getInitials(form.name) }}
              </div>
              <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                  {{ form.name || 'Lead Tahrirlash' }}
                </h1>
                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                  <span v-if="form.company">{{ form.company }}</span>
                  <span v-if="form.company && lead.created_at">•</span>
                  <span>Yaratilgan: {{ lead.created_at }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Header Actions -->
          <div class="flex items-center space-x-3">
            <button
              type="button"
              @click="showDeleteModal = true"
              class="p-2 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
              title="O'chirish"
            >
              <TrashIcon class="w-5 h-5" />
            </button>
            <Link
              :href="`/sales/${lead.id}`"
              class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 font-medium transition-colors"
            >
              Bekor qilish
            </Link>
            <button
              type="button"
              @click="submit"
              :disabled="form.processing || !isFormValid"
              class="inline-flex items-center px-5 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-blue-500/25"
            >
              <CheckIcon v-if="!form.processing" class="w-5 h-5 mr-2" />
              <svg v-else class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Saqlash
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pipeline Progress -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
      <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Pipeline Bosqichi</h3>
      <div class="flex items-center">
        <button
          v-for="(stage, index) in pipelineStages"
          :key="stage.value"
          type="button"
          @click="form.status = stage.value"
          :class="[
            'flex-1 py-3 px-4 text-sm font-medium transition-all duration-200 relative',
            index === 0 ? 'rounded-l-lg' : '',
            index === pipelineStages.length - 1 ? 'rounded-r-lg' : '',
            form.status === stage.value
              ? `${stage.color} text-white shadow-lg transform scale-105 z-10`
              : isStageCompleted(stage.value)
                ? 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
        >
          {{ stage.label }}
          <CheckIcon
            v-if="isStageCompleted(stage.value) && form.status !== stage.value"
            class="w-4 h-4 absolute top-1 right-1"
          />
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - Contact Info -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Contact Information Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <UserIcon class="w-5 h-5 text-gray-400" />
              <h3 class="font-semibold text-gray-900 dark:text-white">Kontakt Ma'lumotlari</h3>
            </div>
          </div>

          <div class="p-6 space-y-5">
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Ism Familiya <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <UserIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Alisher Valiyev"
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.name }"
                />
              </div>
              <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
            </div>

            <!-- Email & Phone Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Email -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Email
                </label>
                <div class="relative">
                  <EnvelopeIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input
                    v-model="form.email"
                    type="email"
                    placeholder="alisher@example.com"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    :class="{ 'border-red-500 focus:ring-red-500': form.errors.email }"
                  />
                </div>
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">{{ form.errors.email }}</p>
              </div>

              <!-- Phone -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Telefon
                </label>
                <div class="relative">
                  <PhoneIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input
                    v-model="form.phone"
                    v-maska
                    data-maska="+998 ## ### ## ##"
                    type="tel"
                    placeholder="+998 90 123 45 67"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    :class="{ 'border-red-500 focus:ring-red-500': form.errors.phone }"
                  />
                </div>
                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">{{ form.errors.phone }}</p>
              </div>
            </div>

            <!-- Company -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Kompaniya
              </label>
              <div class="relative">
                <BuildingOfficeIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  v-model="form.company"
                  type="text"
                  placeholder="ABC Company"
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.company }"
                />
              </div>
              <p v-if="form.errors.company" class="mt-1 text-sm text-red-500">{{ form.errors.company }}</p>
            </div>
          </div>
        </div>

        <!-- Notes Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <ChatBubbleLeftRightIcon class="w-5 h-5 text-gray-400" />
              <h3 class="font-semibold text-gray-900 dark:text-white">Izohlar va Eslatmalar</h3>
            </div>
          </div>

          <div class="p-6">
            <textarea
              v-model="form.notes"
              rows="5"
              placeholder="Lead haqida muhim ma'lumotlar, muzokara natijalari, keyingi qadamlar..."
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
            ></textarea>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
              Bu yerda mijoz bilan aloqa tarixi, muhim kelishuvlar va keyingi qadamlarni yozib qo'yishingiz mumkin.
            </p>
          </div>
        </div>
      </div>

      <!-- Right Column - Lead Details -->
      <div class="space-y-6">
        <!-- Lead Score Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-2">
              <ChartBarIcon class="w-5 h-5 text-gray-400" />
              <h3 class="font-semibold text-gray-900 dark:text-white">Lead Bahosi</h3>
            </div>
          </div>

          <div class="p-6">
            <!-- Score Circle -->
            <div class="flex flex-col items-center mb-6">
              <div class="relative w-32 h-32">
                <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                  <circle
                    cx="60"
                    cy="60"
                    r="52"
                    stroke="currentColor"
                    stroke-width="12"
                    fill="transparent"
                    class="text-gray-200 dark:text-gray-700"
                  />
                  <circle
                    cx="60"
                    cy="60"
                    r="52"
                    stroke="currentColor"
                    stroke-width="12"
                    fill="transparent"
                    stroke-linecap="round"
                    :stroke-dasharray="`${(form.score / 100) * 327} 327`"
                    :class="scoreColor"
                  />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                  <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ form.score }}</span>
                </div>
              </div>
              <span class="mt-2 text-sm font-medium" :class="scoreTextColor">
                {{ scoreLabel }}
              </span>
            </div>

            <!-- Score Slider -->
            <div>
              <input
                v-model.number="form.score"
                type="range"
                min="0"
                max="100"
                class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-blue-600"
              />
              <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                <span>0</span>
                <span>25</span>
                <span>50</span>
                <span>75</span>
                <span>100</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Deal Value Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-2">
              <CurrencyDollarIcon class="w-5 h-5 text-gray-400" />
              <h3 class="font-semibold text-gray-900 dark:text-white">Bitim Qiymati</h3>
            </div>
          </div>

          <div class="p-6">
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
                UZS
              </span>
              <input
                v-model.number="form.estimated_value"
                type="number"
                min="0"
                step="1000"
                placeholder="0"
                class="w-full pl-14 pr-4 py-4 text-2xl font-bold border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              />
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
              Taxminiy bitim summasi
            </p>
          </div>
        </div>

        <!-- Lead Source Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-2">
              <GlobeAltIcon class="w-5 h-5 text-gray-400" />
              <h3 class="font-semibold text-gray-900 dark:text-white">Lead Manbasi</h3>
            </div>
          </div>

          <div class="p-6">
            <div class="space-y-2">
              <button
                v-for="channel in channels"
                :key="channel.id"
                type="button"
                @click="form.source_id = channel.id"
                :class="[
                  'w-full flex items-center space-x-3 p-3 rounded-lg border-2 transition-all',
                  form.source_id === channel.id
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                ]"
              >
                <div :class="[
                  'w-10 h-10 rounded-lg flex items-center justify-center text-white',
                  getSourceColor(channel.category)
                ]">
                  <component :is="getSourceIcon(channel.category)" class="w-5 h-5" />
                </div>
                <div class="flex-1 text-left">
                  <p class="font-medium text-gray-900 dark:text-white">{{ channel.name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ channel.category }}</p>
                </div>
                <CheckCircleIcon
                  v-if="form.source_id === channel.id"
                  class="w-5 h-5 text-blue-500"
                />
              </button>

              <!-- No Source Option -->
              <button
                type="button"
                @click="form.source_id = ''"
                :class="[
                  'w-full flex items-center space-x-3 p-3 rounded-lg border-2 transition-all',
                  !form.source_id
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                ]"
              >
                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-400 text-white">
                  <QuestionMarkCircleIcon class="w-5 h-5" />
                </div>
                <div class="flex-1 text-left">
                  <p class="font-medium text-gray-900 dark:text-white">Noma'lum</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Manba ko'rsatilmagan</p>
                </div>
                <CheckCircleIcon
                  v-if="!form.source_id"
                  class="w-5 h-5 text-blue-500"
                />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>

            <!-- Modal -->
            <Transition
              enter-active-class="transition ease-out duration-200"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition ease-in duration-150"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95"
            >
              <div v-if="showDeleteModal" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 z-10">
                <div class="text-center">
                  <div class="mx-auto w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                    <ExclamationTriangleIcon class="w-8 h-8 text-red-600 dark:text-red-400" />
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                    Leadni o'chirish
                  </h3>
                  <p class="text-gray-500 dark:text-gray-400 mb-6">
                    "{{ lead.name }}" leadini o'chirishni xohlaysizmi? Bu amalni qaytarib bo'lmaydi.
                  </p>
                  <div class="flex space-x-3">
                    <button
                      type="button"
                      @click="showDeleteModal = false"
                      class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                      Bekor qilish
                    </button>
                    <button
                      type="button"
                      @click="deleteLead"
                      :disabled="isDeleting"
                      class="flex-1 px-4 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                    >
                      <span v-if="!isDeleting">O'chirish</span>
                      <span v-else class="flex items-center justify-center">
                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                      </span>
                    </button>
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </Transition>
    </Teleport>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { vMaska } from 'maska/vue';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import {
  ArrowLeftIcon,
  CheckIcon,
  TrashIcon,
  UserIcon,
  EnvelopeIcon,
  PhoneIcon,
  BuildingOfficeIcon,
  ChatBubbleLeftRightIcon,
  ChartBarIcon,
  CurrencyDollarIcon,
  GlobeAltIcon,
  CheckCircleIcon,
  QuestionMarkCircleIcon,
  ExclamationTriangleIcon,
  MegaphoneIcon,
  UsersIcon,
  ShareIcon,
  NewspaperIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  lead: {
    type: Object,
    required: true,
  },
  channels: {
    type: Array,
    default: () => [],
  },
});

const showDeleteModal = ref(false);
const isDeleting = ref(false);

const form = useForm({
  name: props.lead.name,
  email: props.lead.email || '',
  phone: props.lead.phone || '',
  company: props.lead.company || '',
  source_id: props.lead.source_id || '',
  status: props.lead.status,
  score: props.lead.score || 0,
  estimated_value: props.lead.estimated_value || null,
  notes: props.lead.notes || '',
});

const pipelineStages = [
  { value: 'new', label: 'Yangi', color: 'bg-blue-500' },
  { value: 'contacted', label: 'Bog\'lanildi', color: 'bg-indigo-500' },
  { value: 'qualified', label: 'Qualified', color: 'bg-purple-500' },
  { value: 'proposal', label: 'Taklif', color: 'bg-orange-500' },
  { value: 'negotiation', label: 'Muzokara', color: 'bg-yellow-500' },
  { value: 'won', label: 'Yutildi', color: 'bg-green-500' },
  { value: 'lost', label: 'Yo\'qoldi', color: 'bg-red-500' },
];

const stageOrder = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];

const isFormValid = computed(() => {
  return form.name && form.name.trim().length > 0;
});

const isStageCompleted = (stage) => {
  const currentIndex = stageOrder.indexOf(form.status);
  const stageIndex = stageOrder.indexOf(stage);
  return stageIndex < currentIndex;
};

const scoreColor = computed(() => {
  if (form.score >= 75) return 'text-green-500';
  if (form.score >= 50) return 'text-yellow-500';
  if (form.score >= 25) return 'text-orange-500';
  return 'text-red-500';
});

const scoreTextColor = computed(() => {
  if (form.score >= 75) return 'text-green-600 dark:text-green-400';
  if (form.score >= 50) return 'text-yellow-600 dark:text-yellow-400';
  if (form.score >= 25) return 'text-orange-600 dark:text-orange-400';
  return 'text-red-600 dark:text-red-400';
});

const scoreLabel = computed(() => {
  if (form.score >= 75) return 'A\'lo';
  if (form.score >= 50) return 'Yaxshi';
  if (form.score >= 25) return 'O\'rtacha';
  return 'Past';
});

const getInitials = (name) => {
  if (!name) return '?';
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

const getSourceColor = (category) => {
  const colors = {
    social: 'bg-blue-500',
    organic: 'bg-green-500',
    paid: 'bg-purple-500',
    referral: 'bg-orange-500',
    direct: 'bg-gray-500',
    other: 'bg-gray-400',
  };
  return colors[category] || colors.other;
};

const getSourceIcon = (category) => {
  const icons = {
    social: ShareIcon,
    organic: GlobeAltIcon,
    paid: MegaphoneIcon,
    referral: UsersIcon,
    direct: NewspaperIcon,
    other: QuestionMarkCircleIcon,
  };
  return icons[category] || icons.other;
};

const submit = () => {
  form.put(route('business.sales.update', props.lead.id));
};

const deleteLead = () => {
  isDeleting.value = true;
  router.delete(route('business.sales.destroy', props.lead.id), {
    onFinish: () => {
      isDeleting.value = false;
      showDeleteModal.value = false;
    },
  });
};
</script>
