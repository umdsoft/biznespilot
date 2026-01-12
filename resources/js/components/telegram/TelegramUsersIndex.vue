<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-cyan-800 dark:from-blue-800 dark:via-blue-900 dark:to-cyan-950 rounded-2xl p-6 md:p-8">
      <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
          <defs>
            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
              <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
            </pattern>
          </defs>
          <rect width="100" height="100" fill="url(#grid)"/>
        </svg>
      </div>

      <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center">
          <Link
            :href="getRoute('telegram-funnels.show', bot.id)"
            class="text-white/70 hover:text-white mr-4 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </Link>
          <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">Foydalanuvchilar</h1>
            <p class="text-blue-100">@{{ bot.username }} - Bot obunachilari</p>
          </div>
        </div>

        <button
          @click="exportUsers"
          class="inline-flex items-center px-5 py-2.5 bg-white/10 border border-white/20 text-white font-semibold rounded-xl hover:bg-white/20 transition-all duration-200"
        >
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Eksport
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-3 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Jami</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.active }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Faol</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.blocked }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Bloklangan</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex flex-col md:flex-row gap-4">
        <!-- Search -->
        <div class="flex-1">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="search"
              type="text"
              placeholder="Ism, username yoki telefon bo'yicha qidirish..."
              class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white"
              @input="handleSearch"
            >
          </div>
        </div>

        <!-- Status Filter -->
        <div class="w-full md:w-48">
          <select
            v-model="statusFilter"
            @change="applyFilters"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white"
          >
            <option value="">Barcha status</option>
            <option value="active">Faol</option>
            <option value="blocked">Bloklangan</option>
          </select>
        </div>

        <!-- Tag Filter -->
        <div class="w-full md:w-48">
          <select
            v-model="tagFilter"
            @change="applyFilters"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white"
          >
            <option value="">Barcha teglar</option>
            <option v-for="tag in allTags" :key="tag" :value="tag">{{ tag }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Users List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div v-if="users.data && users.data.length > 0" class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-900/50">
            <tr>
              <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Foydalanuvchi</th>
              <th class="text-center py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
              <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Telefon</th>
              <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Teglar</th>
              <th class="text-center py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Oxirgi faollik</th>
              <th class="text-center py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qo'shilgan</th>
              <th class="text-right py-3 px-6 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amallar</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
              <td class="py-4 px-6">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" :class="user.is_blocked ? 'bg-red-100 dark:bg-red-900/30' : 'bg-blue-100 dark:bg-blue-900/30'">
                    <span class="text-lg font-semibold" :class="user.is_blocked ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'">
                      {{ getInitials(user.full_name) }}
                    </span>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ user.full_name || 'Noma\'lum' }}</p>
                    <p v-if="user.username" class="text-xs text-gray-500 dark:text-gray-400">@{{ user.username }}</p>
                  </div>
                </div>
              </td>
              <td class="py-4 px-6 text-center">
                <span :class="[
                  'px-2.5 py-1 rounded-full text-xs font-medium',
                  user.is_blocked
                    ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
                    : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                ]">
                  {{ user.is_blocked ? 'Bloklangan' : 'Faol' }}
                </span>
              </td>
              <td class="py-4 px-6 text-gray-900 dark:text-white">
                {{ user.phone || '-' }}
              </td>
              <td class="py-4 px-6">
                <div v-if="user.tags && user.tags.length > 0" class="flex flex-wrap gap-1">
                  <span
                    v-for="tag in user.tags.slice(0, 3)"
                    :key="tag"
                    class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs rounded-full"
                  >
                    {{ tag }}
                  </span>
                  <span v-if="user.tags.length > 3" class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs rounded-full">
                    +{{ user.tags.length - 3 }}
                  </span>
                </div>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="py-4 px-6 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ user.last_active_at || '-' }}
              </td>
              <td class="py-4 px-6 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ user.created_at }}
              </td>
              <td class="py-4 px-6 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="viewUser(user)"
                    class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                    title="Ko'rish"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  <button
                    @click="openAddTagModal(user)"
                    class="p-2 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors"
                    title="Teg qo'shish"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-else class="p-12 text-center">
        <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Hali foydalanuvchi yo'q</h3>
        <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
          Foydalanuvchilar bot bilan suhbatni boshlaganda bu yerda ko'rinadi.
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ users.from }} - {{ users.to }} / {{ users.total }} ta foydalanuvchi
        </div>
        <div class="flex gap-2">
          <Link
            v-for="link in users.links"
            :key="link.label"
            :href="link.url || '#'"
            :class="[
              'px-3 py-1 rounded-lg text-sm font-medium transition-colors',
              link.active
                ? 'bg-blue-600 text-white'
                : link.url
                  ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                  : 'bg-gray-50 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
            ]"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </div>

  <!-- User Detail Modal -->
  <div v-if="showUserModal" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/50" @click="showUserModal = false"></div>
      <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Foydalanuvchi ma'lumotlari</h3>
          <button @click="showUserModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div v-if="selectedUser" class="space-y-4">
          <div class="flex items-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mr-4" :class="selectedUser.is_blocked ? 'bg-red-100 dark:bg-red-900/30' : 'bg-blue-100 dark:bg-blue-900/30'">
              <span class="text-2xl font-bold" :class="selectedUser.is_blocked ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'">
                {{ getInitials(selectedUser.full_name) }}
              </span>
            </div>
            <div>
              <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ selectedUser.full_name || 'Noma\'lum' }}</p>
              <p v-if="selectedUser.username" class="text-gray-500 dark:text-gray-400">@{{ selectedUser.username }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Telegram ID</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ selectedUser.telegram_id }}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Telefon</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ selectedUser.phone || 'Ko\'rsatilmagan' }}</p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</p>
              <span :class="[
                'px-2 py-0.5 rounded-full text-xs font-medium',
                selectedUser.is_blocked
                  ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
                  : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
              ]">
                {{ selectedUser.is_blocked ? 'Bloklangan' : 'Faol' }}
              </span>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
              <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Qo'shilgan</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ selectedUser.created_at }}</p>
            </div>
          </div>

          <div v-if="selectedUser.tags && selectedUser.tags.length > 0" class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Teglar</p>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="tag in selectedUser.tags"
                :key="tag"
                class="inline-flex items-center px-2.5 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-sm rounded-full"
              >
                {{ tag }}
                <button @click="removeTag(selectedUser, tag)" class="ml-1 hover:text-red-600">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Tag Modal -->
  <div v-if="showTagModal" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/50" @click="showTagModal = false"></div>
      <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Teg qo'shish</h3>
          <button @click="showTagModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <input
            v-model="newTag"
            type="text"
            placeholder="Teg nomini kiriting..."
            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white"
            @keyup.enter="addTag"
          >

          <div v-if="allTags.length > 0" class="flex flex-wrap gap-2">
            <button
              v-for="tag in allTags"
              :key="tag"
              @click="newTag = tag"
              class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
              {{ tag }}
            </button>
          </div>

          <button
            @click="addTag"
            :disabled="!newTag"
            class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-xl transition-colors"
          >
            Teg qo'shish
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  bot: Object,
  users: Object,
  allTags: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({ total: 0, active: 0, blocked: 0 })
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  panelType: {
    type: String,
    required: true,
    validator: (value) => ['business', 'marketing'].includes(value),
  },
})

// Route helpers based on panel type
const getRoute = (name, params = null) => {
  const prefix = props.panelType === 'business' ? 'business.' : 'marketing.';
  if (Array.isArray(params)) {
    return route(prefix + name, params);
  }
  return params ? route(prefix + name, params) : route(prefix + name);
};

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const tagFilter = ref(props.filters.tag || '')

const showUserModal = ref(false)
const showTagModal = ref(false)
const selectedUser = ref(null)
const newTag = ref('')

let searchTimeout = null

const getInitials = (name) => {
  if (!name) return '?'
  const parts = name.split(' ')
  return parts.map(p => p[0]).join('').toUpperCase().slice(0, 2)
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

const applyFilters = () => {
  router.get(getRoute('telegram-funnels.users.index', props.bot.id), {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    tag: tagFilter.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const viewUser = async (user) => {
  selectedUser.value = user
  showUserModal.value = true
}

const openAddTagModal = (user) => {
  selectedUser.value = user
  newTag.value = ''
  showTagModal.value = true
}

const addTag = async () => {
  if (!newTag.value || !selectedUser.value) return

  await fetch(getRoute('telegram-funnels.users.add-tag', [props.bot.id, selectedUser.value.id]), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ tag: newTag.value })
  })

  showTagModal.value = false
  router.reload()
}

const removeTag = async (user, tag) => {
  await fetch(getRoute('telegram-funnels.users.remove-tag', [props.bot.id, user.id]), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ tag })
  })

  router.reload()
}

const exportUsers = async () => {
  const response = await fetch(getRoute('telegram-funnels.users.export', props.bot.id) + '?' + new URLSearchParams({
    status: statusFilter.value || '',
    tag: tagFilter.value || '',
  }), {
    headers: {
      'Accept': 'application/json'
    }
  })

  const data = await response.json()

  if (data.success && data.users) {
    // Convert to CSV
    const headers = ['Telegram ID', 'Username', 'Ism', 'Familiya', 'Telefon', 'Til', 'Bloklangan', 'Teglar', 'Oxirgi faollik', 'Qo\'shilgan']
    const rows = data.users.map(u => [
      u.telegram_id,
      u.username,
      u.first_name,
      u.last_name,
      u.phone,
      u.language,
      u.is_blocked,
      u.tags,
      u.last_active,
      u.joined
    ])

    const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n')
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `users-${props.bot.username}-${new Date().toISOString().split('T')[0]}.csv`
    link.click()
  }
}
</script>
