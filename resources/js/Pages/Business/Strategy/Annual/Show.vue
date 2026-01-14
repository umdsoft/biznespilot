<template>
  <Head :title="`Yillik strategiya - ${strategy.year}`" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
          <Link
            href="/business/strategy"
            class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
          >
            <ArrowLeftIcon class="w-5 h-5" />
          </Link>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ strategy.title || `${strategy.year}-yil strategiyasi` }}</h1>
            <p class="text-gray-500">{{ strategy.vision }}</p>
          </div>
        </div>

        <div class="flex items-center space-x-3">
          <span
            class="px-3 py-1 rounded-full text-sm font-medium"
            :class="statusClass"
          >
            {{ statusLabel }}
          </span>
          <button
            @click="showEditModal = true"
            class="px-4 py-2 text-gray-700 bg-white border rounded-lg hover:bg-gray-50"
          >
            <PencilIcon class="w-4 h-4 inline mr-1" />
            Tahrirlash
          </button>
        </div>
      </div>

      <!-- Progress overview -->
      <div class="bg-white rounded-lg border p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Umumiy progress</h2>
          <span class="text-2xl font-bold text-indigo-600">{{ strategy.completion_percent || 0 }}%</span>
        </div>
        <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
          <div
            class="h-full bg-indigo-600 rounded-full transition-all duration-500"
            :style="{ width: `${strategy.completion_percent || 0}%` }"
          ></div>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Maqsadli daromad</span>
            <CurrencyDollarIcon class="w-5 h-5 text-green-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatMoney(strategy.revenue_target) }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Yillik byudjet</span>
            <BanknotesIcon class="w-5 h-5 text-purple-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatMoney(strategy.annual_budget) }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Strategik maqsadlar</span>
            <FlagIcon class="w-5 h-5 text-blue-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ strategy.strategic_goals?.length || 0 }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Choraklar</span>
            <CalendarIcon class="w-5 h-5 text-amber-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ quarters.length }} / 4</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Strategic goals -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Strategik maqsadlar</h2>
              <button
                @click="showAddGoalModal = true"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline mr-1" />
                Qo'shish
              </button>
            </div>

            <div v-if="strategy.strategic_goals?.length" class="space-y-3">
              <GoalItem
                v-for="(goal, index) in strategy.strategic_goals"
                :key="index"
                :goal="goal"
                :index="index"
                @toggle="toggleGoal(index)"
                @edit="editGoal(index)"
                @delete="deleteGoal(index)"
              />
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              Hali strategik maqsadlar yo'q
            </div>
          </div>

          <!-- Quarters -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Choraklik rejalar</h2>
              <button
                v-if="quarters.length < 4"
                @click="createQuarter"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline mr-1" />
                Chorak qo'shish
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <Link
                v-for="quarter in quarters"
                :key="quarter.id"
                :href="`/business/strategy/quarterly/${quarter.id}`"
                class="block p-4 rounded-lg border hover:shadow-md transition-shadow"
                :class="quarterBorderClass(quarter.quarter)"
              >
                <div class="flex items-center justify-between mb-2">
                  <span class="font-semibold text-gray-900">Q{{ quarter.quarter }}</span>
                  <span
                    class="px-2 py-0.5 rounded-full text-xs"
                    :class="getQuarterStatusClass(quarter.status)"
                  >
                    {{ getQuarterStatusLabel(quarter.status) }}
                  </span>
                </div>
                <p class="text-sm text-gray-600 mb-2 line-clamp-2">{{ quarter.theme || quarter.title }}</p>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">{{ formatMoney(quarter.revenue_target) }}</span>
                  <span class="font-medium text-indigo-600">{{ quarter.completion_percent || 0 }}%</span>
                </div>
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden mt-2">
                  <div
                    class="h-full bg-indigo-500 rounded-full"
                    :style="{ width: `${quarter.completion_percent || 0}%` }"
                  ></div>
                </div>
              </Link>

              <!-- Empty quarter slots -->
              <div
                v-for="q in emptyQuarters"
                :key="`empty-${q}`"
                class="p-4 rounded-lg border-2 border-dashed border-gray-200 flex items-center justify-center cursor-pointer hover:border-indigo-300 hover:bg-indigo-50 transition-colors"
                @click="createQuarterFor(q)"
              >
                <div class="text-center">
                  <span class="text-gray-400 font-medium">Q{{ q }}</span>
                  <p class="text-xs text-gray-400 mt-1">Reja yarating</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Channels strategy -->
          <div class="bg-white rounded-lg border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Kanal strategiyasi</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div
                v-for="channel in strategy.channels"
                :key="channel"
                class="p-3 rounded-lg text-center"
                :class="channelBgClass(channel)"
              >
                <component :is="channelIcon(channel)" class="w-8 h-8 mx-auto mb-2" :class="channelTextClass(channel)" />
                <span class="text-sm font-medium capitalize">{{ channel }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right column -->
        <div class="space-y-6">
          <!-- KPIs -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">KPI ko'rsatkichlar</h2>
              <Link
                :href="`/business/strategy/annual/${strategy.id}/kpis`"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                Barchasi
              </Link>
            </div>

            <div v-if="kpis.length" class="space-y-3">
              <KPICard
                v-for="kpi in kpis.slice(0, 5)"
                :key="kpi.id"
                :kpi="kpi"
                compact
              />
            </div>
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              KPI ko'rsatkichlari yo'q
            </div>
          </div>

          <!-- Budget -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Byudjet</h2>
              <Link
                :href="`/business/strategy/annual/${strategy.id}/budget`"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                Batafsil
              </Link>
            </div>

            <div class="space-y-3">
              <div
                v-for="budget in budgets.slice(0, 4)"
                :key="budget.id"
              >
                <div class="flex items-center justify-between mb-1">
                  <span class="text-sm text-gray-600 capitalize">{{ categoryLabel(budget.category) }}</span>
                  <span class="text-sm font-medium">{{ formatMoney(budget.spent) }} / {{ formatMoney(budget.planned_amount) }}</span>
                </div>
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full"
                    :class="getBudgetColor(budget.spent, budget.planned_amount)"
                    :style="{ width: `${Math.min((budget.spent / budget.planned_amount) * 100, 100)}%` }"
                  ></div>
                </div>
              </div>
            </div>

            <div class="mt-4 pt-4 border-t">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">Jami sarflangan</span>
                <span class="text-lg font-bold text-gray-900">{{ formatMoney(totalSpent) }}</span>
              </div>
            </div>
          </div>

          <!-- AI Recommendations -->
          <div v-if="strategy.ai_recommendations?.length" class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-lg border border-purple-200 p-6">
            <div class="flex items-center mb-4">
              <SparklesIcon class="w-5 h-5 text-purple-600 mr-2" />
              <h2 class="text-lg font-semibold text-purple-900">AI tavsiyalari</h2>
            </div>

            <ul class="space-y-2">
              <li
                v-for="(rec, index) in strategy.ai_recommendations.slice(0, 4)"
                :key="index"
                class="flex items-start text-sm text-purple-800"
              >
                <LightBulbIcon class="w-4 h-4 text-purple-500 mr-2 mt-0.5 flex-shrink-0" />
                {{ rec }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <TransitionRoot appear :show="showEditModal" as="template">
    <Dialog as="div" class="relative z-50" @close="showEditModal = false">
      <TransitionChild
        enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
        leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-2xl bg-white rounded-xl shadow-xl p-6">
              <DialogTitle class="text-lg font-semibold text-gray-900 mb-4">
                Strategiyani tahrirlash
              </DialogTitle>

              <form @submit.prevent="updateStrategy" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Sarlavha</label>
                  <input
                    v-model="editForm.title"
                    type="text"
                    class="w-full rounded-lg border-gray-300"
                    placeholder="Strategiya nomi"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Vizyon</label>
                  <textarea
                    v-model="editForm.vision"
                    rows="3"
                    class="w-full rounded-lg border-gray-300"
                    placeholder="Yillik vizyon..."
                  ></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maqsadli daromad</label>
                    <input
                      v-model.number="editForm.revenue_target"
                      type="number"
                      class="w-full rounded-lg border-gray-300"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Yillik byudjet</label>
                    <input
                      v-model.number="editForm.annual_budget"
                      type="number"
                      class="w-full rounded-lg border-gray-300"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                  <select v-model="editForm.status" class="w-full rounded-lg border-gray-300">
                    <option value="draft">Qoralama</option>
                    <option value="active">Faol</option>
                    <option value="paused">To'xtatilgan</option>
                    <option value="completed">Yakunlangan</option>
                  </select>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                  <button
                    type="button"
                    @click="showEditModal = false"
                    class="px-4 py-2 text-gray-700 bg-white border rounded-lg hover:bg-gray-50"
                  >
                    Bekor qilish
                  </button>
                  <button
                    type="submit"
                    :disabled="saving"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                  >
                    {{ saving ? 'Saqlanmoqda...' : 'Saqlash' }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import GoalItem from '@/components/strategy/GoalItem.vue';
import KPICard from '@/components/strategy/KPICard.vue';
import {
  ArrowLeftIcon,
  PencilIcon,
  PlusIcon,
  CurrencyDollarIcon,
  BanknotesIcon,
  FlagIcon,
  CalendarIcon,
  SparklesIcon,
  LightBulbIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  strategy: Object,
  quarters: Array,
  kpis: Array,
  budgets: Array,
});

const showEditModal = ref(false);
const showAddGoalModal = ref(false);
const saving = ref(false);

const editForm = ref({
  title: props.strategy.title || '',
  vision: props.strategy.vision || '',
  revenue_target: props.strategy.revenue_target,
  annual_budget: props.strategy.annual_budget,
  status: props.strategy.status,
});

const statusClass = computed(() => {
  const classes = {
    draft: 'bg-gray-100 text-gray-700',
    active: 'bg-green-100 text-green-700',
    paused: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-blue-100 text-blue-700',
  };
  return classes[props.strategy.status] || 'bg-gray-100 text-gray-700';
});

const statusLabel = computed(() => {
  const labels = {
    draft: 'Qoralama',
    active: 'Faol',
    paused: 'To\'xtatilgan',
    completed: 'Yakunlangan',
  };
  return labels[props.strategy.status] || props.strategy.status;
});

const totalSpent = computed(() => {
  return props.budgets?.reduce((sum, b) => sum + (b.spent || 0), 0) || 0;
});

const existingQuarters = computed(() => {
  return props.quarters?.map(q => q.quarter) || [];
});

const emptyQuarters = computed(() => {
  return [1, 2, 3, 4].filter(q => !existingQuarters.value.includes(q));
});

function formatMoney(value) {
  if (!value) return '-';
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
  if (value >= 1000) return `${(value / 1000).toFixed(0)}K`;
  return value.toLocaleString();
}

function categoryLabel(category) {
  const labels = {
    marketing: 'Marketing',
    advertising: 'Reklama',
    content: 'Kontent',
    tools: 'Asboblar',
    team: 'Jamoa',
    other: 'Boshqa',
  };
  return labels[category] || category;
}

function getBudgetColor(spent, planned) {
  if (!planned) return 'bg-gray-300';
  const percent = (spent / planned) * 100;
  if (percent >= 100) return 'bg-red-500';
  if (percent >= 80) return 'bg-yellow-500';
  return 'bg-green-500';
}

function quarterBorderClass(quarter) {
  const colors = {
    1: 'border-blue-200 bg-blue-50/50',
    2: 'border-green-200 bg-green-50/50',
    3: 'border-amber-200 bg-amber-50/50',
    4: 'border-purple-200 bg-purple-50/50',
  };
  return colors[quarter] || 'border-gray-200';
}

function getQuarterStatusClass(status) {
  const classes = {
    draft: 'bg-gray-100 text-gray-600',
    planning: 'bg-blue-100 text-blue-600',
    active: 'bg-green-100 text-green-600',
    completed: 'bg-indigo-100 text-indigo-600',
  };
  return classes[status] || 'bg-gray-100 text-gray-600';
}

function getQuarterStatusLabel(status) {
  const labels = {
    draft: 'Qoralama',
    planning: 'Rejalashtirish',
    active: 'Faol',
    completed: 'Yakunlangan',
  };
  return labels[status] || status;
}

function channelIcon(channel) {
  // Return a simple div with channel initial for now
  return {
    template: `<div class="w-8 h-8 rounded-full flex items-center justify-center bg-current/10 text-lg font-bold">${channel.charAt(0).toUpperCase()}</div>`,
  };
}

function channelBgClass(channel) {
  const classes = {
    instagram: 'bg-pink-50',
    telegram: 'bg-sky-50',
    facebook: 'bg-blue-50',
    tiktok: 'bg-gray-50',
    youtube: 'bg-red-50',
  };
  return classes[channel] || 'bg-gray-50';
}

function channelTextClass(channel) {
  const classes = {
    instagram: 'text-pink-600',
    telegram: 'text-sky-600',
    facebook: 'text-blue-600',
    tiktok: 'text-gray-800',
    youtube: 'text-red-600',
  };
  return classes[channel] || 'text-gray-600';
}

function toggleGoal(index) {
  const goals = [...(props.strategy.strategic_goals || [])];
  goals[index].completed = !goals[index].completed;

  router.put(`/business/strategy/annual/${props.strategy.id}`, {
    strategic_goals: goals,
  }, { preserveScroll: true });
}

function editGoal(index) {
  // TODO: Implement goal editing
}

function deleteGoal(index) {
  if (!confirm('Bu maqsadni o\'chirmoqchimisiz?')) return;

  const goals = [...(props.strategy.strategic_goals || [])];
  goals.splice(index, 1);

  router.put(`/business/strategy/annual/${props.strategy.id}`, {
    strategic_goals: goals,
  }, { preserveScroll: true });
}

function createQuarter() {
  const nextQuarter = emptyQuarters.value[0];
  if (nextQuarter) {
    createQuarterFor(nextQuarter);
  }
}

function createQuarterFor(quarter) {
  router.post('/business/strategy/quarterly', {
    annual_strategy_id: props.strategy.id,
    quarter: quarter,
    year: props.strategy.year,
  });
}

function updateStrategy() {
  saving.value = true;
  router.put(`/business/strategy/annual/${props.strategy.id}`, editForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
    },
    onFinish: () => {
      saving.value = false;
    },
  });
}
</script>
