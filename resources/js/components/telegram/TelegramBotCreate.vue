<template>
  <div class="max-w-2xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <Link
        :href="getRoute('telegram-funnels.index')"
        class="inline-flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 mb-4"
      >
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Orqaga
      </Link>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Yangi Bot Qo'shish</h1>
      <p class="mt-2 text-gray-500 dark:text-gray-400">Telegram botingizni ulang va funnellarni sozlang</p>
    </div>

    <!-- Steps -->
    <div class="space-y-6">
      <!-- Step 1: Get Token -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
              <span class="text-blue-600 dark:text-blue-400 font-bold">1</span>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Bot Token Oling</h3>
              <p class="text-gray-600 dark:text-gray-400 mb-4">
                Telegram @BotFather dan yangi bot yarating yoki mavjud botingiz tokenini oling.
              </p>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 space-y-2 text-sm">
                <p class="text-gray-700 dark:text-gray-300">1. Telegramda <code class="bg-gray-200 dark:bg-gray-600 px-1.5 py-0.5 rounded">@BotFather</code> ni oching</p>
                <p class="text-gray-700 dark:text-gray-300">2. <code class="bg-gray-200 dark:bg-gray-600 px-1.5 py-0.5 rounded">/newbot</code> buyrug'ini yuboring</p>
                <p class="text-gray-700 dark:text-gray-300">3. Bot nomini va username ni kiriting</p>
                <p class="text-gray-700 dark:text-gray-300">4. Olingan tokenni pastdagi maydonga kiriting</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2: Enter Token -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
              <span class="text-green-600 dark:text-green-400 font-bold">2</span>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Tokenni Kiriting</h3>
              <p class="text-gray-600 dark:text-gray-400 mb-4">
                BotFather dan olingan tokenni pastga kiriting.
              </p>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bot Token</label>
                <input
                  v-model="botToken"
                  type="text"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white transition-all font-mono text-sm"
                  placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                />
                <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bot Preview -->
      <div v-if="botInfo" class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border border-green-200 dark:border-green-800 overflow-hidden">
        <div class="p-6">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-green-500/30">
              <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-sm text-green-600 dark:text-green-400 font-medium mb-1">Bot topildi!</p>
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ botInfo.first_name }}</h3>
              <p class="text-gray-600 dark:text-gray-400">@{{ botInfo.username }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="flex justify-end gap-4">
        <Link
          :href="getRoute('telegram-funnels.index')"
          class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
          Bekor qilish
        </Link>
        <button
          @click="verifyAndCreate"
          :disabled="!botToken || isLoading"
          class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/30 disabled:shadow-none flex items-center gap-2"
        >
          <svg v-if="isLoading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ isLoading ? 'Tekshirilmoqda...' : 'Botni Qo\'shish' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  panelType: {
    type: String,
    default: 'business',
    validator: (value) => ['business', 'marketing'].includes(value)
  }
})

const botToken = ref('')
const botInfo = ref(null)
const isLoading = ref(false)
const error = ref('')

const getRoute = (name, params = null) => {
  const prefix = props.panelType === 'business' ? 'business.' : 'marketing.'
  return params ? route(prefix + name, params) : route(prefix + name)
}

const verifyAndCreate = async () => {
  if (!botToken.value) return

  isLoading.value = true
  error.value = ''
  botInfo.value = null

  try {
    const response = await fetch(getRoute('telegram-funnels.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ bot_token: botToken.value })
    })

    const data = await response.json()

    if (data.success) {
      botInfo.value = data.bot
      // Redirect to bot page after short delay
      setTimeout(() => {
        router.visit(getRoute('telegram-funnels.show', data.bot.id))
      }, 1000)
    } else {
      error.value = data.message || 'Bot tokenini tekshirishda xatolik'
    }
  } catch (err) {
    error.value = 'Server bilan bog\'lanishda xatolik'
  } finally {
    isLoading.value = false
  }
}
</script>
