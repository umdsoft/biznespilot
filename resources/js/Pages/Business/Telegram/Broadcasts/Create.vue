<template>
  <BusinessLayout :title="`Yangi Broadcast - @${bot.username}`">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center">
        <Link
          :href="route('business.telegram-funnels.broadcasts.index', bot.id)"
          class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mr-4 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Yangi Broadcast</h1>
          <p class="text-gray-500 dark:text-gray-400">@{{ bot.username }} - Ommaviy xabar yuborish</p>
        </div>
      </div>

      <!-- Form -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Broadcast ma'lumotlari</h3>
        </div>

        <div class="p-6 space-y-6">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Broadcast nomi *</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-white"
              placeholder="Masalan: Yangi yil tabrigi"
            >
          </div>

          <!-- Content Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Xabar turi</label>
            <div class="grid grid-cols-4 gap-3">
              <button
                v-for="type in contentTypes"
                :key="type.value"
                @click="form.content.type = type.value"
                :class="[
                  'p-4 rounded-xl border-2 transition-all duration-200 flex flex-col items-center gap-2',
                  form.content.type === type.value
                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                ]"
              >
                <svg class="w-6 h-6" :class="form.content.type === type.value ? 'text-purple-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="type.icon" />
                </svg>
                <span :class="form.content.type === type.value ? 'text-purple-600 font-medium' : 'text-gray-600 dark:text-gray-400'">
                  {{ type.label }}
                </span>
              </button>
            </div>
          </div>

          <!-- Text Content -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ form.content.type === 'text' ? 'Xabar matni *' : 'Caption (ixtiyoriy)' }}
            </label>
            <textarea
              v-model="form.content.text"
              rows="4"
              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-white"
              placeholder="Xabar matnini yozing..."
            ></textarea>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">HTML formatini qo'llab-quvvatlaydi: &lt;b&gt;, &lt;i&gt;, &lt;a&gt;, &lt;code&gt;</p>
          </div>

          <!-- File ID (for media) -->
          <div v-if="form.content.type !== 'text'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">File ID *</label>
            <input
              v-model="form.content.file_id"
              type="text"
              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-white"
              placeholder="Telegram file_id ni kiriting"
            >
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fayl Telegram serveriga yuklangan bo'lishi kerak</p>
          </div>
        </div>
      </div>

      <!-- Target Filter -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Qabul qiluvchilar</h3>
        </div>

        <div class="p-6 space-y-6">
          <!-- Recipients count -->
          <div class="flex items-center justify-between p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
            <div>
              <p class="text-sm text-purple-600 dark:text-purple-400">Taxminiy qabul qiluvchilar</p>
              <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ totalUsers }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-800/30 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>

          <!-- Tags filter -->
          <div v-if="availableTags.length > 0">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teglar bo'yicha filter (ixtiyoriy)</label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="tag in availableTags"
                :key="tag"
                @click="toggleTag(tag)"
                :class="[
                  'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
                  selectedTags.includes(tag)
                    ? 'bg-purple-600 text-white'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]"
              >
                {{ tag }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Schedule -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rejalashtirish</h3>
        </div>

        <div class="p-6">
          <div class="flex items-center gap-4">
            <button
              @click="isScheduled = false"
              :class="[
                'flex-1 p-4 rounded-xl border-2 transition-all duration-200',
                !isScheduled
                  ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                  : 'border-gray-200 dark:border-gray-700'
              ]"
            >
              <div class="flex items-center justify-center gap-3">
                <svg class="w-6 h-6" :class="!isScheduled ? 'text-purple-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span :class="!isScheduled ? 'text-purple-600 font-medium' : 'text-gray-600 dark:text-gray-400'">Hoziroq yuborish</span>
              </div>
            </button>

            <button
              @click="isScheduled = true"
              :class="[
                'flex-1 p-4 rounded-xl border-2 transition-all duration-200',
                isScheduled
                  ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                  : 'border-gray-200 dark:border-gray-700'
              ]"
            >
              <div class="flex items-center justify-center gap-3">
                <svg class="w-6 h-6" :class="isScheduled ? 'text-purple-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span :class="isScheduled ? 'text-purple-600 font-medium' : 'text-gray-600 dark:text-gray-400'">Keyinroq yuborish</span>
              </div>
            </button>
          </div>

          <div v-if="isScheduled" class="mt-4">
            <input
              v-model="form.scheduled_at"
              type="datetime-local"
              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 dark:text-white"
            >
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-4">
        <Link
          :href="route('business.telegram-funnels.broadcasts.index', bot.id)"
          class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
          Bekor qilish
        </Link>
        <button
          @click="saveDraft"
          :disabled="isSaving"
          class="px-6 py-3 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white font-medium rounded-xl transition-colors"
        >
          Qoralama saqlash
        </button>
        <button
          @click="createAndStart"
          :disabled="isSaving || !isValid"
          class="px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white font-medium rounded-xl transition-colors flex items-center gap-2"
        >
          <svg v-if="isSaving" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ isScheduled ? 'Rejalashtirilgan yaratish' : 'Yaratish va Boshlash' }}
        </button>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'

const props = defineProps({
  bot: Object,
  availableTags: {
    type: Array,
    default: () => []
  },
  totalUsers: {
    type: Number,
    default: 0
  }
})

const contentTypes = [
  { value: 'text', label: 'Matn', icon: 'M4 6h16M4 12h16M4 18h7' },
  { value: 'photo', label: 'Rasm', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
  { value: 'video', label: 'Video', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
  { value: 'document', label: 'Fayl', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
]

const form = reactive({
  name: '',
  content: {
    type: 'text',
    text: '',
    file_id: '',
  },
  scheduled_at: null,
})

const selectedTags = ref([])
const isScheduled = ref(false)
const isSaving = ref(false)

const isValid = computed(() => {
  if (!form.name) return false
  if (form.content.type === 'text' && !form.content.text) return false
  if (form.content.type !== 'text' && !form.content.file_id) return false
  return true
})

const toggleTag = (tag) => {
  const index = selectedTags.value.indexOf(tag)
  if (index > -1) {
    selectedTags.value.splice(index, 1)
  } else {
    selectedTags.value.push(tag)
  }
}

const createBroadcast = async (startImmediately = false) => {
  isSaving.value = true

  try {
    const response = await fetch(route('business.telegram-funnels.broadcasts.store', props.bot.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        name: form.name,
        content: form.content,
        target_filter: selectedTags.value.length > 0 ? { tags: selectedTags.value } : null,
        scheduled_at: isScheduled.value ? form.scheduled_at : null,
      })
    })

    const data = await response.json()

    if (data.success) {
      if (startImmediately && !isScheduled.value) {
        // Start the broadcast immediately
        await fetch(route('business.telegram-funnels.broadcasts.start', [props.bot.id, data.broadcast.id]), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        })
      }
      router.visit(route('business.telegram-funnels.broadcasts.index', props.bot.id))
    } else {
      alert(data.message || 'Xatolik yuz berdi')
    }
  } catch (error) {
    alert('Server bilan bog\'lanishda xatolik')
  } finally {
    isSaving.value = false
  }
}

const saveDraft = () => createBroadcast(false)
const createAndStart = () => createBroadcast(true)
</script>
