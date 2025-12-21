<template>
  <Head title="Kontent Kalendar" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Kontent Kalendar</h1>
          <p class="text-gray-500 mt-1">Kontentlarni rejalashtiring va kuzating</p>
        </div>

        <div class="flex items-center space-x-3">
          <!-- View toggle -->
          <div class="flex bg-gray-100 rounded-lg p-1">
            <button
              v-for="v in views"
              :key="v.value"
              @click="setView(v.value)"
              class="px-3 py-1.5 text-sm rounded-md transition-colors"
              :class="view === v.value ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
            >
              {{ v.label }}
            </button>
          </div>

          <!-- Channel filter -->
          <select
            v-model="selectedChannel"
            @change="filterByChannel"
            class="rounded-lg border-gray-300 text-sm"
          >
            <option :value="null">Barcha kanallar</option>
            <option v-for="(label, key) in channels" :key="key" :value="key">{{ label }}</option>
          </select>

          <!-- Add content button -->
          <button
            @click="openCreateModal()"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
          >
            <PlusIcon class="w-5 h-5 inline mr-1" />
            Kontent qo'shish
          </button>
        </div>
      </div>

      <!-- Navigation -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
          <button
            @click="navigateDate(-1)"
            class="p-2 rounded-lg hover:bg-gray-100"
          >
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <h2 class="text-lg font-semibold text-gray-900">{{ periodLabel }}</h2>
          <button
            @click="navigateDate(1)"
            class="p-2 rounded-lg hover:bg-gray-100"
          >
            <ChevronRightIcon class="w-5 h-5" />
          </button>
        </div>

        <button
          @click="goToToday"
          class="text-sm text-indigo-600 hover:text-indigo-800"
        >
          Bugun
        </button>
      </div>

      <!-- Calendar grid (Month view) -->
      <div v-if="view === 'month'" class="bg-white rounded-lg border overflow-hidden">
        <!-- Day headers -->
        <div class="grid grid-cols-7 bg-gray-50 border-b">
          <div
            v-for="day in weekDays"
            :key="day"
            class="px-3 py-2 text-center text-sm font-medium text-gray-500"
          >
            {{ day }}
          </div>
        </div>

        <!-- Calendar cells -->
        <div class="grid grid-cols-7">
          <div
            v-for="day in calendarDays"
            :key="day.date"
            class="min-h-[120px] border-b border-r p-2"
            :class="{
              'bg-gray-50': !day.isCurrentMonth,
              'bg-indigo-50': day.isToday,
            }"
            @dragover.prevent
            @drop="handleDrop($event, day.date)"
          >
            <!-- Day number -->
            <div class="flex items-center justify-between mb-2">
              <span
                class="text-sm font-medium"
                :class="{
                  'text-gray-400': !day.isCurrentMonth,
                  'text-indigo-600': day.isToday,
                  'text-gray-900': day.isCurrentMonth && !day.isToday,
                }"
              >
                {{ day.day }}
              </span>
              <button
                @click="openCreateModal(day.date)"
                class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-gray-600"
              >
                <PlusIcon class="w-4 h-4" />
              </button>
            </div>

            <!-- Content items -->
            <div class="space-y-1">
              <ContentCalendarItem
                v-for="item in getItemsForDate(day.date)"
                :key="item.id"
                :item="item"
                @click="openViewModal(item)"
                @drag-start="startDrag"
                @drag-end="endDrag"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Week view -->
      <div v-else-if="view === 'week'" class="bg-white rounded-lg border overflow-hidden">
        <div class="grid grid-cols-7">
          <div
            v-for="day in weekDays"
            :key="day.date"
            class="border-r last:border-r-0"
          >
            <div
              class="px-3 py-2 text-center border-b"
              :class="{ 'bg-indigo-50': day.isToday }"
            >
              <div class="text-sm text-gray-500">{{ day.name }}</div>
              <div
                class="text-lg font-semibold"
                :class="day.isToday ? 'text-indigo-600' : 'text-gray-900'"
              >
                {{ day.day }}
              </div>
            </div>

            <div class="p-2 min-h-[300px] space-y-2">
              <ContentCalendarItem
                v-for="item in getItemsForDate(day.date)"
                :key="item.id"
                :item="item"
                @click="openViewModal(item)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Today's content -->
        <div class="bg-white rounded-lg border p-4">
          <h3 class="font-semibold text-gray-900 mb-3">Bugungi kontentlar</h3>
          <div v-if="todays_content?.length" class="space-y-2">
            <ContentCalendarItem
              v-for="item in todays_content"
              :key="item.id"
              :item="item"
              @click="openViewModal(item)"
            />
          </div>
          <p v-else class="text-sm text-gray-500">Bugungi kontent yo'q</p>
        </div>

        <!-- Upcoming -->
        <div class="bg-white rounded-lg border p-4">
          <h3 class="font-semibold text-gray-900 mb-3">Kelasi haftadagi</h3>
          <div v-if="upcoming_content?.length" class="space-y-2">
            <div
              v-for="item in upcoming_content"
              :key="item.id"
              class="flex items-center justify-between p-2 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100"
              @click="openViewModal(item)"
            >
              <div>
                <p class="text-sm font-medium text-gray-900 line-clamp-1">{{ item.title }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(item.scheduled_date) }}</p>
              </div>
              <span
                class="px-2 py-0.5 text-xs rounded"
                :class="getChannelClass(item.channel)"
              >
                {{ channels[item.channel] }}
              </span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-500">Rejalashtirilgan kontent yo'q</p>
        </div>

        <!-- Quick stats -->
        <div class="bg-white rounded-lg border p-4">
          <h3 class="font-semibold text-gray-900 mb-3">Statistika</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Jami kontentlar</span>
              <span class="font-medium text-gray-900">{{ items?.length || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Joylashtirilgan</span>
              <span class="font-medium text-green-600">
                {{ items?.filter(i => i.status === 'published').length || 0 }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Rejalashtirilgan</span>
              <span class="font-medium text-indigo-600">
                {{ items?.filter(i => i.status === 'scheduled').length || 0 }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Qoralama</span>
              <span class="font-medium text-gray-600">
                {{ items?.filter(i => i.status === 'draft').length || 0 }}
              </span>
            </div>
          </div>

          <Link
            href="/business/content-calendar/analytics"
            class="mt-4 block text-center text-sm text-indigo-600 hover:text-indigo-800"
          >
            Batafsil analitika
          </Link>
        </div>
      </div>
    </div>
  </div>

  <!-- Content modal (simplified) -->
  <div
    v-if="showModal"
    class="fixed inset-0 z-50 overflow-y-auto"
    @click.self="closeModal"
  >
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-black opacity-50"></div>

      <div class="relative bg-white rounded-lg max-w-lg w-full p-6 z-10">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          {{ modalMode === 'create' ? 'Yangi kontent' : 'Kontent tahrirlash' }}
        </h3>

        <form @submit.prevent="saveContent" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sarlavha</label>
            <input
              v-model="contentForm.title"
              type="text"
              required
              class="w-full rounded-lg border-gray-300"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kanal</label>
              <select
                v-model="contentForm.channel"
                required
                class="w-full rounded-lg border-gray-300"
              >
                <option v-for="(label, key) in channels" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Turi</label>
              <select
                v-model="contentForm.content_type"
                required
                class="w-full rounded-lg border-gray-300"
              >
                <option v-for="(label, key) in content_types" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sana</label>
              <input
                v-model="contentForm.scheduled_date"
                type="date"
                required
                class="w-full rounded-lg border-gray-300"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Vaqt</label>
              <input
                v-model="contentForm.scheduled_time"
                type="time"
                class="w-full rounded-lg border-gray-300"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tavsif</label>
            <textarea
              v-model="contentForm.description"
              rows="3"
              class="w-full rounded-lg border-gray-300"
            ></textarea>
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-gray-700 hover:text-gray-900"
            >
              Bekor qilish
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ loading ? 'Saqlanmoqda...' : 'Saqlash' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useContentCalendarStore } from '@/stores/contentCalendar';
import ContentCalendarItem from '@/Components/strategy/ContentCalendarItem.vue';
import {
  PlusIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  items: Array,
  grouped_items: Object,
  view: String,
  current_date: String,
  start_date: String,
  end_date: String,
  channels: Object,
  selected_channel: String,
  todays_content: Array,
  upcoming_content: Array,
  content_types: Object,
  statuses: Object,
});

const store = useContentCalendarStore();
const loading = ref(false);
const view = ref(props.view || 'month');
const selectedChannel = ref(props.selected_channel);
const showModal = ref(false);
const modalMode = ref('create');

const contentForm = ref({
  title: '',
  channel: 'instagram',
  content_type: 'post',
  scheduled_date: new Date().toISOString().split('T')[0],
  scheduled_time: '',
  description: '',
});

const views = [
  { value: 'month', label: 'Oy' },
  { value: 'week', label: 'Hafta' },
];

const weekDays = ['Dush', 'Sesh', 'Chor', 'Pay', 'Jum', 'Shan', 'Yak'];

const periodLabel = computed(() => {
  const date = new Date(props.current_date);
  if (view.value === 'month') {
    return date.toLocaleDateString('uz-UZ', { month: 'long', year: 'numeric' });
  }
  return `${props.start_date} - ${props.end_date}`;
});

const calendarDays = computed(() => {
  const days = [];
  const currentDate = new Date(props.current_date);
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();

  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);

  // Get Monday of the first week
  const startDate = new Date(firstDay);
  startDate.setDate(startDate.getDate() - ((startDate.getDay() + 6) % 7));

  // Generate 6 weeks
  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate);
    date.setDate(date.getDate() + i);

    days.push({
      date: date.toISOString().split('T')[0],
      day: date.getDate(),
      isCurrentMonth: date.getMonth() === month,
      isToday: date.toDateString() === new Date().toDateString(),
    });
  }

  return days;
});

function setView(v) {
  view.value = v;
  router.get('/business/content-calendar', { view: v, date: props.current_date }, { preserveState: true });
}

function filterByChannel() {
  router.get('/business/content-calendar', {
    view: view.value,
    date: props.current_date,
    channel: selectedChannel.value
  }, { preserveState: true });
}

function navigateDate(direction) {
  const current = new Date(props.current_date);
  if (view.value === 'month') {
    current.setMonth(current.getMonth() + direction);
  } else {
    current.setDate(current.getDate() + (direction * 7));
  }
  router.get('/business/content-calendar', {
    view: view.value,
    date: current.toISOString().split('T')[0],
    channel: selectedChannel.value
  }, { preserveState: true });
}

function goToToday() {
  router.get('/business/content-calendar', {
    view: view.value,
    date: new Date().toISOString().split('T')[0],
    channel: selectedChannel.value
  }, { preserveState: true });
}

function getItemsForDate(date) {
  return props.items?.filter(item => item.scheduled_date === date) || [];
}

function getChannelClass(channel) {
  const classes = {
    instagram: 'bg-pink-100 text-pink-700',
    telegram: 'bg-sky-100 text-sky-700',
    facebook: 'bg-blue-100 text-blue-700',
  };
  return classes[channel] || 'bg-gray-100 text-gray-700';
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
}

function openCreateModal(date = null) {
  modalMode.value = 'create';
  contentForm.value = {
    title: '',
    channel: 'instagram',
    content_type: 'post',
    scheduled_date: date || new Date().toISOString().split('T')[0],
    scheduled_time: '',
    description: '',
  };
  showModal.value = true;
}

function openViewModal(item) {
  modalMode.value = 'edit';
  contentForm.value = { ...item };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

async function saveContent() {
  loading.value = true;

  const method = modalMode.value === 'create' ? 'post' : 'put';
  const url = modalMode.value === 'create'
    ? '/business/content-calendar'
    : `/business/content-calendar/${contentForm.value.id}`;

  router[method](url, contentForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
    },
    onFinish: () => {
      loading.value = false;
    },
  });
}

function startDrag(item) {
  store.startDrag(item);
}

function endDrag() {
  store.endDrag();
}

function handleDrop(event, date) {
  const data = event.dataTransfer.getData('text/plain');
  if (data) {
    const item = JSON.parse(data);
    if (item.scheduled_date !== date) {
      router.post(`/business/content-calendar/${item.id}/move`, { date }, {
        preserveScroll: true,
      });
    }
  }
}
</script>
