<template>
  <AdminLayout title="Yangi bildirishnoma">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <Link
          href="/dashboard/notifications"
          class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
        >
          <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Orqaga
        </Link>
        <h1 class="text-2xl font-bold text-gray-900">Yangi bildirishnoma yuborish</h1>
        <p class="mt-1 text-sm text-gray-500">
          Foydalanuvchilarga shaxsiy yoki ommaviy bildirishnoma yuborish
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Notification Type -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Bildirishnoma turi</h2>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <button
              v-for="(type, key) in types"
              :key="key"
              type="button"
              @click="form.type = key"
              class="flex flex-col items-center p-4 rounded-lg border-2 transition-all"
              :class="form.type === key
                ? 'border-blue-500 bg-blue-50'
                : 'border-gray-200 hover:border-gray-300'"
            >
              <div
                class="w-12 h-12 rounded-full flex items-center justify-center mb-2"
                :class="getTypeClass(key).bg"
              >
                <svg class="w-6 h-6" :class="getTypeClass(key).icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTypeIcon(key)" />
                </svg>
              </div>
              <span class="text-sm font-medium text-gray-900">{{ type.label }}</span>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Matn</h2>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Sarlavha <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Bildirishnoma sarlavhasi"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Xabar <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="form.message"
                rows="4"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Bildirishnoma matni..."
                required
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Havola (ixtiyoriy)
              </label>
              <input
                v-model="form.action_url"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="/business/..."
              />
            </div>

            <div v-if="form.action_url">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Tugma matni
              </label>
              <input
                v-model="form.action_text"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Ko'rish"
              />
            </div>
          </div>
        </div>

        <!-- Recipients -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Qabul qiluvchilar</h2>

          <!-- Target Selection -->
          <div class="flex flex-wrap gap-3 mb-6">
            <button
              type="button"
              @click="form.target = 'all'"
              class="flex items-center px-4 py-2 rounded-lg border-2 transition-all"
              :class="form.target === 'all'
                ? 'border-blue-500 bg-blue-50 text-blue-700'
                : 'border-gray-200 hover:border-gray-300 text-gray-700'"
            >
              <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Barcha bizneslar
            </button>
            <button
              type="button"
              @click="form.target = 'businesses'"
              class="flex items-center px-4 py-2 rounded-lg border-2 transition-all"
              :class="form.target === 'businesses'
                ? 'border-blue-500 bg-blue-50 text-blue-700'
                : 'border-gray-200 hover:border-gray-300 text-gray-700'"
            >
              <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              Tanlangan bizneslar
            </button>
            <button
              type="button"
              @click="form.target = 'users'"
              class="flex items-center px-4 py-2 rounded-lg border-2 transition-all"
              :class="form.target === 'users'
                ? 'border-blue-500 bg-blue-50 text-blue-700'
                : 'border-gray-200 hover:border-gray-300 text-gray-700'"
            >
              <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              Tanlangan foydalanuvchilar
            </button>
          </div>

          <!-- Business Selection -->
          <div v-if="form.target === 'businesses'" class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">
              Bizneslarni tanlang
            </label>
            <div class="border border-gray-300 rounded-lg max-h-64 overflow-y-auto">
              <div class="p-2">
                <input
                  v-model="businessSearch"
                  type="text"
                  placeholder="Qidirish..."
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"
                />
              </div>
              <div class="divide-y divide-gray-100">
                <label
                  v-for="business in filteredBusinesses"
                  :key="business.id"
                  class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :value="business.id"
                    v-model="form.business_ids"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  />
                  <span class="ml-3 text-sm text-gray-900">{{ business.name }}</span>
                </label>
              </div>
            </div>
            <p class="text-sm text-gray-500">
              {{ form.business_ids.length }} ta biznes tanlandi
            </p>
          </div>

          <!-- User Selection -->
          <div v-if="form.target === 'users'" class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">
              Foydalanuvchilarni tanlang
            </label>
            <div class="border border-gray-300 rounded-lg max-h-64 overflow-y-auto">
              <div class="p-2">
                <input
                  v-model="userSearch"
                  type="text"
                  placeholder="Qidirish..."
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"
                />
              </div>
              <div class="divide-y divide-gray-100">
                <label
                  v-for="user in filteredUsers"
                  :key="user.id"
                  class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :value="user.id"
                    v-model="form.user_ids"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  />
                  <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                    <p class="text-xs text-gray-500">{{ user.email }}</p>
                  </div>
                </label>
              </div>
            </div>
            <p class="text-sm text-gray-500">
              {{ form.user_ids.length }} ta foydalanuvchi tanlandi
            </p>
          </div>

          <!-- All businesses info -->
          <div v-if="form.target === 'all'" class="bg-blue-50 rounded-lg p-4">
            <div class="flex items-start">
              <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <p class="text-sm font-medium text-blue-900">Barcha faol bizneslarga yuboriladi</p>
                <p class="text-sm text-blue-700 mt-1">
                  Bu bildirishnoma tizimdagi barcha faol bizneslarning barcha foydalanuvchilariga yuboriladi.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Preview -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Ko'rinish</h2>
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-start space-x-3">
              <div
                class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                :class="getTypeClass(form.type).bg"
              >
                <svg class="w-5 h-5" :class="getTypeClass(form.type).icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTypeIcon(form.type)" />
                </svg>
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">
                  {{ form.title || 'Bildirishnoma sarlavhasi' }}
                </p>
                <p class="mt-1 text-sm text-gray-500">
                  {{ form.message || 'Bildirishnoma matni bu yerda ko\'rsatiladi...' }}
                </p>
                <p class="mt-2 text-xs text-gray-400">Hozirgina</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end space-x-4">
          <Link
            href="/dashboard/notifications"
            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Bekor qilish
          </Link>
          <button
            type="submit"
            :disabled="submitting || !canSubmit"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="submitting">Yuborilmoqda...</span>
            <span v-else>Yuborish</span>
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
  users: Array,
  businesses: Array,
  types: Object,
});

const form = ref({
  type: 'announcement',
  title: '',
  message: '',
  action_url: '',
  action_text: '',
  target: 'all',
  business_ids: [],
  user_ids: [],
});

const submitting = ref(false);
const businessSearch = ref('');
const userSearch = ref('');

const filteredBusinesses = computed(() => {
  if (!businessSearch.value) return props.businesses;
  const search = businessSearch.value.toLowerCase();
  return props.businesses.filter(b => b.name.toLowerCase().includes(search));
});

const filteredUsers = computed(() => {
  if (!userSearch.value) return props.users;
  const search = userSearch.value.toLowerCase();
  return props.users.filter(u =>
    u.name.toLowerCase().includes(search) ||
    u.email.toLowerCase().includes(search)
  );
});

const canSubmit = computed(() => {
  if (!form.value.title || !form.value.message) return false;
  if (form.value.target === 'businesses' && form.value.business_ids.length === 0) return false;
  if (form.value.target === 'users' && form.value.user_ids.length === 0) return false;
  return true;
});

const getTypeClass = (type) => {
  const classes = {
    system: { bg: 'bg-gray-100', icon: 'text-gray-600' },
    update: { bg: 'bg-green-100', icon: 'text-green-600' },
    announcement: { bg: 'bg-indigo-100', icon: 'text-indigo-600' },
    alert: { bg: 'bg-red-100', icon: 'text-red-600' },
    celebration: { bg: 'bg-yellow-100', icon: 'text-yellow-600' },
    insight: { bg: 'bg-blue-100', icon: 'text-blue-600' },
  };
  return classes[type] || classes.system;
};

const getTypeIcon = (type) => {
  const icons = {
    system: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    update: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    announcement: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
    alert: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    celebration: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
    insight: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
  };
  return icons[type] || icons.system;
};

const submit = async () => {
  if (!canSubmit.value || submitting.value) return;

  submitting.value = true;

  try {
    const response = await axios.post('/dashboard/notifications', form.value);

    if (response.data.success) {
      alert(response.data.message);
      router.visit('/dashboard/notifications');
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Xatolik yuz berdi');
  } finally {
    submitting.value = false;
  }
};
</script>
