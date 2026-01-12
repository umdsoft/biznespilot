<template>
  <Head :title="`${monthName} ${plan.year}`" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
          <Link
            :href="quarterly ? `/business/strategy/quarterly/${quarterly.id}` : '/business/strategy'"
            class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
          >
            <ArrowLeftIcon class="w-5 h-5" />
          </Link>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ plan.title || `${monthName} ${plan.year}` }}</h1>
            <p class="text-gray-500">{{ plan.focus_area || 'Oylik reja' }}</p>
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
          <h2 class="text-lg font-semibold text-gray-900">Oy progressi</h2>
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
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Byudjet</span>
            <BanknotesIcon class="w-5 h-5 text-purple-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatMoney(plan.budget) }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Postlar</span>
            <DocumentTextIcon class="w-5 h-5 text-blue-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ plan.posts_target || 0 }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Storiyalar</span>
            <PhotoIcon class="w-5 h-5 text-pink-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ plan.stories_target || 0 }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Reelslar</span>
            <FilmIcon class="w-5 h-5 text-red-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ plan.reels_target || 0 }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Haftalar</span>
            <CalendarIcon class="w-5 h-5 text-amber-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ weeks.length }} / 4</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Monthly objectives -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Oy maqsadlari</h2>
              <button
                @click="showAddObjectiveModal = true"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline mr-1" />
                Qo'shish
              </button>
            </div>

            <div v-if="plan.monthly_objectives?.length" class="space-y-3">
              <GoalItem
                v-for="(obj, index) in plan.monthly_objectives"
                :key="index"
                :goal="obj"
                :index="index"
                @toggle="toggleObjective(index)"
                @delete="deleteObjective(index)"
              />
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              Hali oy maqsadlari yo'q
            </div>
          </div>

          <!-- Weekly plans -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Haftalik rejalar</h2>
              <button
                v-if="weeks.length < 5"
                @click="createWeek"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline mr-1" />
                Hafta qo'shish
              </button>
            </div>

            <div class="space-y-3">
              <Link
                v-for="week in weeks"
                :key="week.id"
                :href="`/business/strategy/weekly/${week.id}`"
                class="block p-4 rounded-lg border hover:shadow-md transition-shadow"
              >
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center space-x-3">
                    <span class="font-semibold text-gray-900">Hafta {{ week.week_of_month }}</span>
                    <span class="text-sm text-gray-500">{{ week.start_date }} - {{ week.end_date }}</span>
                  </div>
                  <span
                    class="px-2 py-0.5 rounded-full text-xs"
                    :class="getStatusClass(week.status)"
                  >
                    {{ getStatusLabel(week.status) }}
                  </span>
                </div>

                <div class="grid grid-cols-3 gap-4 mt-3">
                  <div class="text-center p-2 bg-gray-50 rounded">
                    <p class="text-xs text-gray-500">Vazifalar</p>
                    <p class="font-semibold">{{ week.completed_tasks || 0 }} / {{ week.total_tasks || 0 }}</p>
                  </div>
                  <div class="text-center p-2 bg-gray-50 rounded">
                    <p class="text-xs text-gray-500">Postlar</p>
                    <p class="font-semibold">{{ week.posts_published || 0 }} / {{ week.posts_planned || 0 }}</p>
                  </div>
                  <div class="text-center p-2 bg-gray-50 rounded">
                    <p class="text-xs text-gray-500">Progress</p>
                    <p class="font-semibold text-indigo-600">{{ week.completion_percent || 0 }}%</p>
                  </div>
                </div>

                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden mt-3">
                  <div
                    class="h-full bg-indigo-500 rounded-full"
                    :style="{ width: `${week.completion_percent || 0}%` }"
                  ></div>
                </div>
              </Link>

              <!-- Create week button -->
              <div
                v-if="weeks.length < 5"
                class="p-4 rounded-lg border-2 border-dashed border-gray-200 flex items-center justify-center cursor-pointer hover:border-indigo-300 hover:bg-indigo-50 transition-colors"
                @click="createWeek"
              >
                <div class="text-center">
                  <PlusIcon class="w-6 h-6 text-gray-400 mx-auto mb-1" />
                  <span class="text-sm text-gray-500">Yangi hafta yaratish</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Content themes -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Kontent mavzulari</h2>
              <button
                @click="showAddThemeModal = true"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline mr-1" />
                Qo'shish
              </button>
            </div>

            <div v-if="plan.content_themes?.length" class="flex flex-wrap gap-2">
              <span
                v-for="(theme, index) in plan.content_themes"
                :key="index"
                class="inline-flex items-center px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-sm"
              >
                {{ theme }}
                <button
                  @click="removeTheme(index)"
                  class="ml-2 text-indigo-400 hover:text-indigo-600"
                >
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </span>
            </div>
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              Kontent mavzulari yo'q
            </div>
          </div>
        </div>

        <!-- Right column -->
        <div class="space-y-6">
          <!-- Week breakdown -->
          <div class="bg-white rounded-lg border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Hafta bo'yicha rejalar</h2>

            <div class="space-y-4">
              <div
                v-for="(weekPlan, weekKey) in weekBreakdowns"
                :key="weekKey"
                class="p-3 bg-gray-50 rounded-lg"
              >
                <h4 class="font-medium text-gray-900 mb-2">Hafta {{ weekKey.replace('week', '') }}</h4>
                <ul class="space-y-1 text-sm text-gray-600">
                  <li v-for="(item, idx) in weekPlan.slice(0, 3)" :key="idx" class="truncate">
                    {{ item }}
                  </li>
                  <li v-if="weekPlan.length > 3" class="text-gray-400">
                    +{{ weekPlan.length - 3 }} yana
                  </li>
                </ul>
              </div>
            </div>

            <div v-if="!hasWeekBreakdowns" class="text-center py-4 text-gray-500 text-sm">
              Haftalik rejalar tuzilmagan
            </div>
          </div>

          <!-- Content Calendar link -->
          <Link
            href="/business/content-calendar"
            class="block bg-gradient-to-r from-pink-50 to-purple-50 rounded-lg border border-pink-200 p-6 hover:shadow-md transition-shadow"
          >
            <div class="flex items-center mb-3">
              <CalendarDaysIcon class="w-8 h-8 text-pink-500 mr-3" />
              <div>
                <h3 class="font-semibold text-gray-900">Kontent Kalendar</h3>
                <p class="text-sm text-gray-500">Kontentlarni rejalashtiring</p>
              </div>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ monthContentCount }} ta kontent</span>
              <span class="text-pink-600 font-medium">Ko'rish &rarr;</span>
            </div>
          </Link>

          <!-- Key events -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Muhim voqealar</h2>
              <button
                @click="showAddEventModal = true"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline" />
              </button>
            </div>

            <div v-if="plan.key_events?.length" class="space-y-3">
              <div
                v-for="(event, index) in plan.key_events"
                :key="index"
                class="flex items-start space-x-3"
              >
                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                  <CalendarIcon class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900">{{ event.title }}</p>
                  <p class="text-sm text-gray-500">{{ event.date }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              Muhim voqealar yo'q
            </div>
          </div>

          <!-- Promotions -->
          <div v-if="plan.promotions?.length" class="bg-white rounded-lg border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Aksiyalar</h2>

            <div class="space-y-3">
              <div
                v-for="(promo, index) in plan.promotions"
                :key="index"
                class="p-3 bg-green-50 rounded-lg border border-green-200"
              >
                <h4 class="font-medium text-green-800">{{ promo.title }}</h4>
                <p class="text-sm text-green-600 mt-1">{{ promo.description }}</p>
                <p class="text-xs text-green-500 mt-2">{{ promo.dates }}</p>
              </div>
            </div>
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
                Oylik rejani tahrirlash
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
                  <label class="block text-sm font-medium text-gray-700 mb-1">Asosiy fokus</label>
                  <input
                    v-model="editForm.focus_area"
                    type="text"
                    class="w-full rounded-lg border-gray-300"
                  />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Byudjet</label>
                    <input
                      v-model.number="editForm.budget"
                      type="number"
                      class="w-full rounded-lg border-gray-300"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Post maqsadi</label>
                    <input
                      v-model.number="editForm.posts_target"
                      type="number"
                      class="w-full rounded-lg border-gray-300"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Story maqsadi</label>
                    <input
                      v-model.number="editForm.stories_target"
                      type="number"
                      class="w-full rounded-lg border-gray-300"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reels maqsadi</label>
                    <input
                      v-model.number="editForm.reels_target"
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
  BanknotesIcon,
  DocumentTextIcon,
  PhotoIcon,
  FilmIcon,
  CalendarIcon,
  CalendarDaysIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  plan: Object,
  quarterly: Object,
  weeks: Array,
  content_count: Number,
});

const showEditModal = ref(false);
const showAddObjectiveModal = ref(false);
const showAddThemeModal = ref(false);
const showAddEventModal = ref(false);
const saving = ref(false);

const editForm = ref({
  title: props.plan.title || '',
  focus_area: props.plan.focus_area || '',
  budget: props.plan.budget,
  posts_target: props.plan.posts_target,
  stories_target: props.plan.stories_target,
  reels_target: props.plan.reels_target,
  status: props.plan.status,
});

const monthName = computed(() => {
  const months = ['', 'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
  return months[props.plan.month] || '';
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

const weekBreakdowns = computed(() => {
  const result = {};
  ['week1', 'week2', 'week3', 'week4'].forEach(key => {
    if (props.plan[key] && props.plan[key].length > 0) {
      result[key] = props.plan[key];
    }
  });
  return result;
});

const hasWeekBreakdowns = computed(() => {
  return Object.keys(weekBreakdowns.value).length > 0;
});

const monthContentCount = computed(() => {
  return props.content_count || 0;
});

function formatMoney(value) {
  if (!value) return '-';
  if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
  if (value >= 1000) return `${(value / 1000).toFixed(0)}K`;
  return value.toLocaleString();
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

function toggleObjective(index) {
  const objectives = [...(props.plan.monthly_objectives || [])];
  objectives[index].completed = !objectives[index].completed;

  router.put(`/business/strategy/monthly/${props.plan.id}`, {
    monthly_objectives: objectives,
  }, { preserveScroll: true });
}

function deleteObjective(index) {
  if (!confirm('Bu maqsadni o\'chirmoqchimisiz?')) return;

  const objectives = [...(props.plan.monthly_objectives || [])];
  objectives.splice(index, 1);

  router.put(`/business/strategy/monthly/${props.plan.id}`, {
    monthly_objectives: objectives,
  }, { preserveScroll: true });
}

function removeTheme(index) {
  const themes = [...(props.plan.content_themes || [])];
  themes.splice(index, 1);

  router.put(`/business/strategy/monthly/${props.plan.id}`, {
    content_themes: themes,
  }, { preserveScroll: true });
}

function createWeek() {
  const nextWeek = (props.weeks?.length || 0) + 1;
  router.post('/business/strategy/weekly', {
    monthly_plan_id: props.plan.id,
    week_of_month: nextWeek,
    year: props.plan.year,
    month: props.plan.month,
  });
}

function updatePlan() {
  saving.value = true;
  router.put(`/business/strategy/monthly/${props.plan.id}`, editForm.value, {
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
