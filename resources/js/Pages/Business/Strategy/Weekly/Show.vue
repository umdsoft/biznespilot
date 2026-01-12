<template>
  <Head :title="`Hafta ${plan.week_of_month} - ${monthName} ${plan.year}`" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
          <Link
            :href="monthly ? `/business/strategy/monthly/${monthly.id}` : '/business/strategy'"
            class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
          >
            <ArrowLeftIcon class="w-5 h-5" />
          </Link>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ plan.title || `Hafta ${plan.week_of_month}` }}</h1>
            <p class="text-gray-500">{{ plan.start_date }} - {{ plan.end_date }}</p>
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
          <h2 class="text-lg font-semibold text-gray-900">Hafta progressi</h2>
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
            <span class="text-sm text-gray-500">Vazifalar</span>
            <ClipboardDocumentListIcon class="w-5 h-5 text-blue-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">
            {{ plan.completed_tasks || 0 }} / {{ plan.total_tasks || 0 }}
          </p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Postlar</span>
            <DocumentTextIcon class="w-5 h-5 text-purple-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">
            {{ plan.posts_published || 0 }} / {{ plan.posts_planned || 0 }}
          </p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Lid maqsadi</span>
            <UserGroupIcon class="w-5 h-5 text-green-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ plan.lead_target || 0 }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Sotuv maqsadi</span>
            <CurrencyDollarIcon class="w-5 h-5 text-amber-500" />
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ plan.sales_target || 0 }}</p>
        </div>

        <div class="bg-white rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">Fokus</span>
            <SparklesIcon class="w-5 h-5 text-pink-500" />
          </div>
          <p class="text-lg font-semibold text-gray-900 mt-1 truncate">{{ plan.focus || '-' }}</p>
        </div>
      </div>

      <!-- Daily tasks grid -->
      <div class="bg-white rounded-lg border p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Kunlik vazifalar</h2>

        <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
          <div
            v-for="day in days"
            :key="day.key"
            class="rounded-lg border p-3"
            :class="isToday(day.key) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'"
          >
            <div class="flex items-center justify-between mb-3">
              <span class="font-medium text-gray-900">{{ day.label }}</span>
              <button
                @click="addTaskForDay(day.key)"
                class="p-1 text-gray-400 hover:text-indigo-600 rounded"
              >
                <PlusIcon class="w-4 h-4" />
              </button>
            </div>

            <div class="space-y-2 min-h-[100px]">
              <TaskItem
                v-for="(task, index) in getDayTasks(day.key)"
                :key="index"
                :task="task"
                :day="day.key"
                :index="index"
                @toggle="toggleTask(day.key, index)"
                @delete="deleteTask(day.key, index)"
              />

              <div
                v-if="!getDayTasks(day.key).length"
                class="text-center py-4 text-gray-400 text-sm"
              >
                Vazifalar yo'q
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Priority tasks -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Ustuvor vazifalar</h2>
              <button
                @click="showAddPriorityModal = true"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline mr-1" />
                Qo'shish
              </button>
            </div>

            <div v-if="plan.priority_tasks?.length" class="space-y-3">
              <div
                v-for="(task, index) in plan.priority_tasks"
                :key="index"
                class="flex items-start space-x-3 p-3 rounded-lg"
                :class="task.completed ? 'bg-green-50' : 'bg-amber-50'"
              >
                <button
                  @click="togglePriorityTask(index)"
                  class="mt-0.5"
                >
                  <div
                    class="w-5 h-5 rounded border-2 flex items-center justify-center"
                    :class="task.completed ? 'bg-green-500 border-green-500' : 'border-amber-400'"
                  >
                    <CheckIcon v-if="task.completed" class="w-3 h-3 text-white" />
                  </div>
                </button>
                <div class="flex-1">
                  <p
                    class="font-medium"
                    :class="task.completed ? 'text-green-700 line-through' : 'text-amber-900'"
                  >
                    {{ task.title }}
                  </p>
                  <p class="text-sm text-gray-500 mt-1">{{ task.description }}</p>
                </div>
                <button
                  @click="deletePriorityTask(index)"
                  class="text-gray-400 hover:text-red-500"
                >
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              Ustuvor vazifalar yo'q
            </div>
          </div>

          <!-- Content schedule -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Kontent jadvali</h2>
              <Link
                href="/business/content-calendar"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                Kalendarga o'tish
              </Link>
            </div>

            <div v-if="weekContent.length" class="space-y-3">
              <div
                v-for="content in weekContent"
                :key="content.id"
                class="flex items-center space-x-4 p-3 rounded-lg border"
              >
                <div
                  class="w-10 h-10 rounded-lg flex items-center justify-center"
                  :class="getChannelBg(content.channel)"
                >
                  <span class="text-lg font-bold" :class="getChannelText(content.channel)">
                    {{ content.channel?.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 truncate">{{ content.title }}</p>
                  <p class="text-sm text-gray-500">
                    {{ content.scheduled_date }} {{ content.scheduled_time || '' }}
                  </p>
                </div>
                <span
                  class="px-2 py-0.5 rounded-full text-xs"
                  :class="getContentStatusClass(content.status)"
                >
                  {{ getContentStatusLabel(content.status) }}
                </span>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              Bu hafta uchun kontent yo'q
            </div>
          </div>
        </div>

        <!-- Right column -->
        <div class="space-y-6">
          <!-- Week goals -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Hafta maqsadlari</h2>
              <button
                @click="showAddGoalModal = true"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PlusIcon class="w-4 h-4 inline" />
              </button>
            </div>

            <div v-if="plan.goals?.length" class="space-y-2">
              <GoalItem
                v-for="(goal, index) in plan.goals"
                :key="index"
                :goal="goal"
                :index="index"
                compact
                @toggle="toggleGoal(index)"
                @delete="deleteGoal(index)"
              />
            </div>
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              Maqsadlar yo'q
            </div>
          </div>

          <!-- Notes -->
          <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Eslatmalar</h2>
              <button
                @click="editNotes"
                class="text-sm text-indigo-600 hover:text-indigo-800"
              >
                <PencilIcon class="w-4 h-4 inline" />
              </button>
            </div>

            <div v-if="plan.notes" class="prose prose-sm max-w-none text-gray-600">
              {{ plan.notes }}
            </div>
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              Eslatmalar yo'q
            </div>
          </div>

          <!-- Retrospective (if week completed) -->
          <div v-if="plan.status === 'completed'" class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg border border-indigo-200 p-6">
            <div class="flex items-center mb-4">
              <ChartBarIcon class="w-5 h-5 text-indigo-600 mr-2" />
              <h2 class="text-lg font-semibold text-indigo-900">Hafta natijalari</h2>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-indigo-700">Vazifalar bajarildi</span>
                <span class="font-semibold text-indigo-900">
                  {{ Math.round(((plan.completed_tasks || 0) / (plan.total_tasks || 1)) * 100) }}%
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-indigo-700">Postlar joylandi</span>
                <span class="font-semibold text-indigo-900">
                  {{ plan.posts_published || 0 }} / {{ plan.posts_planned || 0 }}
                </span>
              </div>
              <div v-if="plan.retrospective" class="pt-3 border-t border-indigo-200">
                <p class="text-sm text-indigo-800">{{ plan.retrospective }}</p>
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
                Haftalik rejani tahrirlash
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
                  <label class="block text-sm font-medium text-gray-700 mb-1">Fokus</label>
                  <input
                    v-model="editForm.focus"
                    type="text"
                    class="w-full rounded-lg border-gray-300"
                    placeholder="Bu hafta nimaga e'tibor qaratamiz?"
                  />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lid maqsadi</label>
                    <input
                      v-model.number="editForm.lead_target"
                      type="number"
                      class="w-full rounded-lg border-gray-300"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sotuv maqsadi</label>
                    <input
                      v-model.number="editForm.sales_target"
                      type="number"
                      class="w-full rounded-lg border-gray-300"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rejalashtirilgan postlar</label>
                    <input
                      v-model.number="editForm.posts_planned"
                      type="number"
                      class="w-full rounded-lg border-gray-300"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select v-model="editForm.status" class="w-full rounded-lg border-gray-300">
                      <option value="draft">Qoralama</option>
                      <option value="active">Faol</option>
                      <option value="completed">Yakunlangan</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Eslatmalar</label>
                  <textarea
                    v-model="editForm.notes"
                    rows="3"
                    class="w-full rounded-lg border-gray-300"
                  ></textarea>
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

  <!-- Add Task Modal -->
  <TransitionRoot appear :show="showAddTaskModal" as="template">
    <Dialog as="div" class="relative z-50" @close="showAddTaskModal = false">
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
            <DialogPanel class="w-full max-w-md bg-white rounded-xl shadow-xl p-6">
              <DialogTitle class="text-lg font-semibold text-gray-900 mb-4">
                Vazifa qo'shish - {{ getDayLabel(selectedDay) }}
              </DialogTitle>

              <form @submit.prevent="saveTask" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Vazifa</label>
                  <input
                    v-model="newTask.title"
                    type="text"
                    class="w-full rounded-lg border-gray-300"
                    placeholder="Vazifa nomi..."
                    required
                  />
                </div>

                <div class="flex justify-end space-x-3">
                  <button
                    type="button"
                    @click="showAddTaskModal = false"
                    class="px-4 py-2 text-gray-700 bg-white border rounded-lg hover:bg-gray-50"
                  >
                    Bekor qilish
                  </button>
                  <button
                    type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                  >
                    Qo'shish
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
import TaskItem from '@/Components/strategy/TaskItem.vue';
import {
  ArrowLeftIcon,
  PencilIcon,
  PlusIcon,
  XMarkIcon,
  CheckIcon,
  ClipboardDocumentListIcon,
  DocumentTextIcon,
  UserGroupIcon,
  CurrencyDollarIcon,
  SparklesIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  plan: Object,
  monthly: Object,
  content: Array,
});

const showEditModal = ref(false);
const showAddTaskModal = ref(false);
const showAddPriorityModal = ref(false);
const showAddGoalModal = ref(false);
const saving = ref(false);
const selectedDay = ref('monday');
const newTask = ref({ title: '' });

const editForm = ref({
  title: props.plan.title || '',
  focus: props.plan.focus || '',
  lead_target: props.plan.lead_target,
  sales_target: props.plan.sales_target,
  posts_planned: props.plan.posts_planned,
  status: props.plan.status,
  notes: props.plan.notes || '',
});

const days = [
  { key: 'monday', label: 'Dush' },
  { key: 'tuesday', label: 'Sesh' },
  { key: 'wednesday', label: 'Chor' },
  { key: 'thursday', label: 'Pay' },
  { key: 'friday', label: 'Jum' },
  { key: 'saturday', label: 'Shan' },
  { key: 'sunday', label: 'Yak' },
];

const monthName = computed(() => {
  const months = ['', 'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
  return months[props.plan.month] || '';
});

const statusClass = computed(() => {
  const classes = {
    draft: 'bg-gray-100 text-gray-700',
    active: 'bg-green-100 text-green-700',
    completed: 'bg-indigo-100 text-indigo-700',
  };
  return classes[props.plan.status] || 'bg-gray-100 text-gray-700';
});

const statusLabel = computed(() => {
  const labels = {
    draft: 'Qoralama',
    active: 'Faol',
    completed: 'Yakunlangan',
  };
  return labels[props.plan.status] || props.plan.status;
});

const weekContent = computed(() => {
  return props.content || [];
});

function isToday(dayKey) {
  const today = new Date();
  const dayIndex = days.findIndex(d => d.key === dayKey);
  return today.getDay() === (dayIndex + 1) % 7;
}

function getDayTasks(dayKey) {
  return props.plan[dayKey] || [];
}

function getDayLabel(dayKey) {
  const day = days.find(d => d.key === dayKey);
  return day?.label || dayKey;
}

function addTaskForDay(dayKey) {
  selectedDay.value = dayKey;
  newTask.value = { title: '' };
  showAddTaskModal.value = true;
}

function saveTask() {
  const dayTasks = [...(props.plan[selectedDay.value] || [])];
  dayTasks.push({
    title: newTask.value.title,
    completed: false,
  });

  router.put(`/business/strategy/weekly/${props.plan.id}`, {
    [selectedDay.value]: dayTasks,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showAddTaskModal.value = false;
      newTask.value = { title: '' };
    },
  });
}

function toggleTask(dayKey, index) {
  const dayTasks = [...(props.plan[dayKey] || [])];
  dayTasks[index].completed = !dayTasks[index].completed;

  router.put(`/business/strategy/weekly/${props.plan.id}`, {
    [dayKey]: dayTasks,
  }, { preserveScroll: true });
}

function deleteTask(dayKey, index) {
  const dayTasks = [...(props.plan[dayKey] || [])];
  dayTasks.splice(index, 1);

  router.put(`/business/strategy/weekly/${props.plan.id}`, {
    [dayKey]: dayTasks,
  }, { preserveScroll: true });
}

function togglePriorityTask(index) {
  const tasks = [...(props.plan.priority_tasks || [])];
  tasks[index].completed = !tasks[index].completed;

  router.put(`/business/strategy/weekly/${props.plan.id}`, {
    priority_tasks: tasks,
  }, { preserveScroll: true });
}

function deletePriorityTask(index) {
  if (!confirm('Bu vazifani o\'chirmoqchimisiz?')) return;

  const tasks = [...(props.plan.priority_tasks || [])];
  tasks.splice(index, 1);

  router.put(`/business/strategy/weekly/${props.plan.id}`, {
    priority_tasks: tasks,
  }, { preserveScroll: true });
}

function toggleGoal(index) {
  const goals = [...(props.plan.goals || [])];
  goals[index].completed = !goals[index].completed;

  router.put(`/business/strategy/weekly/${props.plan.id}`, {
    goals: goals,
  }, { preserveScroll: true });
}

function deleteGoal(index) {
  if (!confirm('Bu maqsadni o\'chirmoqchimisiz?')) return;

  const goals = [...(props.plan.goals || [])];
  goals.splice(index, 1);

  router.put(`/business/strategy/weekly/${props.plan.id}`, {
    goals: goals,
  }, { preserveScroll: true });
}

function editNotes() {
  showEditModal.value = true;
}

function getChannelBg(channel) {
  const classes = {
    instagram: 'bg-pink-100',
    telegram: 'bg-sky-100',
    facebook: 'bg-blue-100',
    tiktok: 'bg-gray-100',
    youtube: 'bg-red-100',
  };
  return classes[channel] || 'bg-gray-100';
}

function getChannelText(channel) {
  const classes = {
    instagram: 'text-pink-600',
    telegram: 'text-sky-600',
    facebook: 'text-blue-600',
    tiktok: 'text-gray-800',
    youtube: 'text-red-600',
  };
  return classes[channel] || 'text-gray-600';
}

function getContentStatusClass(status) {
  const classes = {
    idea: 'bg-gray-100 text-gray-600',
    draft: 'bg-yellow-100 text-yellow-700',
    scheduled: 'bg-indigo-100 text-indigo-700',
    published: 'bg-green-100 text-green-700',
  };
  return classes[status] || 'bg-gray-100 text-gray-600';
}

function getContentStatusLabel(status) {
  const labels = {
    idea: 'G\'oya',
    draft: 'Qoralama',
    scheduled: 'Rejalashtirilgan',
    published: 'Joylashtirilgan',
  };
  return labels[status] || status;
}

function updatePlan() {
  saving.value = true;
  router.put(`/business/strategy/weekly/${props.plan.id}`, editForm.value, {
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
