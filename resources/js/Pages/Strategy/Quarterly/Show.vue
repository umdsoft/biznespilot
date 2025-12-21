<template>
  <Head :title="`Q${plan.quarter} - ${plan.year}`" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
          <Link
            :href="annual ? `/business/strategy/annual/${annual.id}` : '/business/strategy'"
            class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
          >
            <ArrowLeftIcon class="w-5 h-5" />
          </Link>
          <div>
            <div class="flex items-center space-x-2">
              <span class="px-2 py-0.5 rounded text-xs font-medium" :class="quarterColorClass">
                Q{{ plan.quarter }}
              </span>
              <h1 class="text-2xl font-bold text-gray-900">{{ plan.title || plan.theme }}</h1>
            </div>
            <p class="text-gray-500">{{ quarterDateRange }}</p>
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

      <!-- Progress -->
      <div class="bg-white rounded-lg border p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Chorak progressi</h2>
          <span class="text-2xl font-bold text-indigo-600">{{ plan.completion_percent || 0 }}%</span>
        </div>
        <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
          <div
            class="h-full bg-indigo-600 rounded-full transition-all duration-500"
            :style="{ width: `${plan.completion_percent || 0}%` }"
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
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatMoney(plan.revenue_target) }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Byudjet</span>
            <BanknotesIcon class="w-5 h-5 text-purple-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatMoney(plan.budget) }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Maqsadlar</span>
            <FlagIcon class="w-5 h-5 text-blue-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ plan.quarterly_objectives?.length || 0 }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Oylar</span>
            <CalendarIcon class="w-5 h-5 text-amber-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ months.length }} / 3</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Quarterly objectives -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Chorak maqsadlari</h2>
              <button
                @click="showAddObjectiveModal = true"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline mr-1" />
                Qo'shish
              </button>
            </div>

            <div v-if="plan.quarterly_objectives?.length" class="space-y-3">
              <GoalItem
                v-for="(obj, index) in plan.quarterly_objectives"
                :key="index"
                :goal="obj"
                :index="index"
                @toggle="toggleObjective(index)"
                @delete="deleteObjective(index)"
              />
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              Hali chorak maqsadlari yo'q
            </div>
          </div>

          <!-- Key initiatives -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Asosiy tashabbuslar</h2>
              <button
                @click="showAddInitiativeModal = true"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline mr-1" />
                Qo'shish
              </button>
            </div>

            <div v-if="plan.key_initiatives?.length" class="space-y-3">
              <div
                v-for="(initiative, index) in plan.key_initiatives"
                :key="index"
                class="p-4 bg-gray-50 rounded-lg"
              >
                <div class="flex items-start justify-between">
                  <div>
                    <h4 class="font-medium text-gray-900">{{ initiative.title }}</h4>
                    <p class="text-sm text-gray-600 mt-1">{{ initiative.description }}</p>
                  </div>
                  <button
                    @click="deleteInitiative(index)"
                    class="text-gray-400 hover:text-red-500"
                  >
                    <XMarkIcon class="w-4 h-4" />
                  </button>
                </div>
                <div class="flex items-center space-x-4 mt-3 text-sm">
                  <span class="text-gray-500">Prioritet:</span>
                  <span
                    class="px-2 py-0.5 rounded text-xs font-medium"
                    :class="priorityClass(initiative.priority)"
                  >
                    {{ priorityLabel(initiative.priority) }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              Tashabbuslar yo'q
            </div>
          </div>

          <!-- Monthly plans -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Oylik rejalar</h2>
              <button
                v-if="months.length < 3"
                @click="createMonth"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline mr-1" />
                Oy qo'shish
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <Link
                v-for="month in months"
                :key="month.id"
                :href="`/business/strategy/monthly/${month.id}`"
                class="block p-4 rounded-lg border hover:shadow-md transition-shadow"
              >
                <div class="flex items-center justify-between mb-2">
                  <span class="font-semibold text-gray-900">{{ getMonthName(month.month) }}</span>
                  <span
                    class="px-2 py-0.5 rounded-full text-xs"
                    :class="getStatusClass(month.status)"
                  >
                    {{ getStatusLabel(month.status) }}
                  </span>
                </div>
                <p class="text-sm text-gray-600 mb-2">{{ month.focus_area || 'Reja mavjud' }}</p>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">{{ month.posts_target || 0 }} post</span>
                  <span class="font-medium text-indigo-600">{{ month.completion_percent || 0 }}%</span>
                </div>
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden mt-2">
                  <div
                    class="h-full bg-indigo-500 rounded-full"
                    :style="{ width: `${month.completion_percent || 0}%` }"
                  ></div>
                </div>
              </Link>

              <!-- Empty month slots -->
              <div
                v-for="m in emptyMonths"
                :key="`empty-${m}`"
                class="p-4 rounded-lg border-2 border-dashed border-gray-200 flex items-center justify-center cursor-pointer hover:border-indigo-300 hover:bg-indigo-50 transition-colors"
                @click="createMonthFor(m)"
              >
                <div class="text-center">
                  <span class="text-gray-400 font-medium">{{ getMonthName(m) }}</span>
                  <p class="text-xs text-gray-400 mt-1">Reja yarating</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right column -->
        <div class="space-y-6">
          <!-- Campaigns -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Kampaniyalar</h2>
              <button
                @click="showAddCampaignModal = true"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline" />
              </button>
            </div>

            <div v-if="plan.campaigns?.length" class="space-y-3">
              <div
                v-for="(campaign, index) in plan.campaigns"
                :key="index"
                class="p-3 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg border border-indigo-100"
              >
                <h4 class="font-medium text-gray-900">{{ campaign.name }}</h4>
                <p class="text-sm text-gray-600 mt-1">{{ campaign.goal }}</p>
                <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                  <span>{{ formatMoney(campaign.budget) }}</span>
                  <span>{{ campaign.channel }}</span>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              Kampaniyalar yo'q
            </div>
          </div>

          <!-- Milestones -->
          <div class="bg-white rounded-lg border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Muhim sanalar</h2>

            <div v-if="plan.milestones?.length" class="space-y-3">
              <div
                v-for="(milestone, index) in plan.milestones"
                :key="index"
                class="flex items-start space-x-3"
              >
                <div
                  class="w-3 h-3 rounded-full mt-1.5"
                  :class="milestone.completed ? 'bg-green-500' : 'bg-gray-300'"
                ></div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900">{{ milestone.title }}</p>
                  <p class="text-xs text-gray-500">{{ milestone.date }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              Muhim sanalar belgilanmagan
            </div>
          </div>

          <!-- Risks -->
          <div v-if="plan.risks?.length" class="bg-red-50 rounded-lg border border-red-200 p-6">
            <div class="flex items-center mb-4">
              <ExclamationTriangleIcon class="w-5 h-5 text-red-500 mr-2" />
              <h2 class="text-lg font-semibold text-red-900">Xavflar</h2>
            </div>

            <ul class="space-y-2">
              <li
                v-for="(risk, index) in plan.risks"
                :key="index"
                class="text-sm text-red-800"
              >
                {{ risk }}
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
                Chorak rejasini tahrirlash
              </DialogTitle>

              <form @submit.prevent="updatePlan" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Sarlavha</label>
                  <input
                    v-model="editForm.title"
                    type="text"
                    class="w-full rounded-lg border-gray-300"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Mavzu (Theme)</label>
                  <input
                    v-model="editForm.theme"
                    type="text"
                    class="w-full rounded-lg border-gray-300"
                  />
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Byudjet</label>
                    <input
                      v-model.number="editForm.budget"
                      type="number"
                      class="w-full rounded-lg border-gray-300"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                  <select v-model="editForm.status" class="w-full rounded-lg border-gray-300">
                    <option value="draft">Qoralama</option>
                    <option value="planning">Rejalashtirish</option>
                    <option value="active">Faol</option>
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
import GoalItem from '@/Components/strategy/GoalItem.vue';
import {
  ArrowLeftIcon,
  PencilIcon,
  PlusIcon,
  XMarkIcon,
  CurrencyDollarIcon,
  BanknotesIcon,
  FlagIcon,
  CalendarIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  plan: Object,
  annual: Object,
  months: Array,
});

const showEditModal = ref(false);
const showAddObjectiveModal = ref(false);
const showAddInitiativeModal = ref(false);
const showAddCampaignModal = ref(false);
const saving = ref(false);

const editForm = ref({
  title: props.plan.title || '',
  theme: props.plan.theme || '',
  revenue_target: props.plan.revenue_target,
  budget: props.plan.budget,
  status: props.plan.status,
});

const quarterColorClass = computed(() => {
  const colors = {
    1: 'bg-blue-100 text-blue-700',
    2: 'bg-green-100 text-green-700',
    3: 'bg-amber-100 text-amber-700',
    4: 'bg-purple-100 text-purple-700',
  };
  return colors[props.plan.quarter] || 'bg-gray-100 text-gray-700';
});

const quarterDateRange = computed(() => {
  const year = props.plan.year;
  const q = props.plan.quarter;
  const startMonth = (q - 1) * 3 + 1;
  const endMonth = q * 3;
  const months = ['', 'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
  return `${months[startMonth]} - ${months[endMonth]} ${year}`;
});

const statusClass = computed(() => {
  const classes = {
    draft: 'bg-gray-100 text-gray-700',
    planning: 'bg-blue-100 text-blue-700',
    active: 'bg-green-100 text-green-700',
    completed: 'bg-indigo-100 text-indigo-700',
  };
  return classes[props.plan.status] || 'bg-gray-100 text-gray-700';
});

const statusLabel = computed(() => {
  const labels = {
    draft: 'Qoralama',
    planning: 'Rejalashtirish',
    active: 'Faol',
    completed: 'Yakunlangan',
  };
  return labels[props.plan.status] || props.plan.status;
});

const quarterMonths = computed(() => {
  const q = props.plan.quarter;
  const startMonth = (q - 1) * 3 + 1;
  return [startMonth, startMonth + 1, startMonth + 2];
});

const existingMonths = computed(() => {
  return props.months?.map(m => m.month) || [];
});

const emptyMonths = computed(() => {
  return quarterMonths.value.filter(m => !existingMonths.value.includes(m));
});

function formatMoney(value) {
  if (!value) return '-';
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
  if (value >= 1000) return `${(value / 1000).toFixed(0)}K`;
  return value.toLocaleString();
}

function getMonthName(month) {
  const months = ['', 'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
  return months[month] || '';
}

function getStatusClass(status) {
  const classes = {
    draft: 'bg-gray-100 text-gray-600',
    planning: 'bg-blue-100 text-blue-600',
    active: 'bg-green-100 text-green-600',
    completed: 'bg-indigo-100 text-indigo-600',
  };
  return classes[status] || 'bg-gray-100 text-gray-600';
}

function getStatusLabel(status) {
  const labels = {
    draft: 'Qoralama',
    planning: 'Rejalashtirish',
    active: 'Faol',
    completed: 'Yakunlangan',
  };
  return labels[status] || status;
}

function priorityClass(priority) {
  const classes = {
    high: 'bg-red-100 text-red-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-green-100 text-green-700',
  };
  return classes[priority] || 'bg-gray-100 text-gray-700';
}

function priorityLabel(priority) {
  const labels = {
    high: 'Yuqori',
    medium: 'O\'rta',
    low: 'Past',
  };
  return labels[priority] || priority;
}

function toggleObjective(index) {
  const objectives = [...(props.plan.quarterly_objectives || [])];
  objectives[index].completed = !objectives[index].completed;

  router.put(`/business/strategy/quarterly/${props.plan.id}`, {
    quarterly_objectives: objectives,
  }, { preserveScroll: true });
}

function deleteObjective(index) {
  if (!confirm('Bu maqsadni o\'chirmoqchimisiz?')) return;

  const objectives = [...(props.plan.quarterly_objectives || [])];
  objectives.splice(index, 1);

  router.put(`/business/strategy/quarterly/${props.plan.id}`, {
    quarterly_objectives: objectives,
  }, { preserveScroll: true });
}

function deleteInitiative(index) {
  if (!confirm('Bu tashabbusni o\'chirmoqchimisiz?')) return;

  const initiatives = [...(props.plan.key_initiatives || [])];
  initiatives.splice(index, 1);

  router.put(`/business/strategy/quarterly/${props.plan.id}`, {
    key_initiatives: initiatives,
  }, { preserveScroll: true });
}

function createMonth() {
  const nextMonth = emptyMonths.value[0];
  if (nextMonth) {
    createMonthFor(nextMonth);
  }
}

function createMonthFor(month) {
  router.post('/business/strategy/monthly', {
    quarterly_plan_id: props.plan.id,
    month: month,
    year: props.plan.year,
  });
}

function updatePlan() {
  saving.value = true;
  router.put(`/business/strategy/quarterly/${props.plan.id}`, editForm.value, {
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
