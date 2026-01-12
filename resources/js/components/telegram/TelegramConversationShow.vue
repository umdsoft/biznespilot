<template>
  <div class="h-[calc(100vh-180px)] flex gap-6">
    <!-- Chat Area -->
    <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden">
      <!-- Chat Header -->
      <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <div class="flex items-center">
          <Link
            :href="getRoute('telegram-funnels.conversations.index', bot.id)"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 mr-4"
          >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </Link>
          <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" :class="getStatusBgClass(conversation.status)">
            <span class="text-lg font-semibold" :class="getStatusTextClass(conversation.status)">
              {{ getInitials(user.full_name) }}
            </span>
          </div>
          <div>
            <p class="font-medium text-gray-900 dark:text-white">{{ user.full_name || 'Noma\'lum' }}</p>
            <p v-if="user.username" class="text-sm text-gray-500 dark:text-gray-400">@{{ user.username }}</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span :class="getStatusBadgeClass(conversation.status)">
            {{ getStatusLabel(conversation.status) }}
          </span>

          <div class="flex items-center gap-2">
            <button
              v-if="conversation.status === 'handoff' && !conversation.assigned_operator"
              @click="assignToMe"
              class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
              Menga biriktirish
            </button>
            <button
              v-if="conversation.status !== 'closed'"
              @click="closeConversation"
              class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors"
            >
              Yopish
            </button>
            <button
              v-else
              @click="reopenConversation"
              class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
              Qayta ochish
            </button>
          </div>
        </div>
      </div>

      <!-- Messages -->
      <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
        <div
          v-for="message in messages"
          :key="message.id"
          :class="[
            'flex',
            message.direction === 'outgoing' ? 'justify-end' : 'justify-start'
          ]"
        >
          <div
            :class="[
              'max-w-[70%] rounded-2xl px-4 py-2',
              message.direction === 'outgoing'
                ? 'bg-blue-600 text-white rounded-br-md'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-bl-md'
            ]"
          >
            <!-- Operator label -->
            <div v-if="message.sender_type === 'operator'" class="text-xs opacity-70 mb-1">
              Operator
            </div>

            <!-- Command message -->
            <div v-if="getMessageType(message) === 'command'" class="flex items-center gap-2">
              <span class="text-lg">&#x26A1;</span>
              <span class="font-mono">{{ getMessageContent(message).command }}</span>
            </div>

            <!-- Contact message -->
            <div v-else-if="getMessageType(message) === 'contact'" class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-lg">&#x1F4F1;</span>
                <span class="font-medium">Kontakt yuborildi</span>
              </div>
              <p class="text-sm opacity-90">{{ getMessageContent(message).phone_number }}</p>
              <p v-if="getMessageContent(message).first_name" class="text-xs opacity-75">
                {{ getMessageContent(message).first_name }} {{ getMessageContent(message).last_name || '' }}
              </p>
            </div>

            <!-- Location message -->
            <div v-else-if="getMessageType(message) === 'location'" class="flex items-center gap-2">
              <span class="text-lg">&#x1F4CD;</span>
              <span>Lokatsiya yuborildi</span>
            </div>

            <!-- Photo message -->
            <div v-else-if="getMessageType(message) === 'photo'" class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-lg">&#x1F5BC;&#xFE0F;</span>
                <span>Rasm</span>
              </div>
              <p v-if="getMessageContent(message).caption" class="text-sm">{{ getMessageContent(message).caption }}</p>
            </div>

            <!-- Document message -->
            <div v-else-if="getMessageType(message) === 'document'" class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-lg">&#x1F4C4;</span>
                <span>{{ getMessageContent(message).file_name || 'Fayl' }}</span>
              </div>
              <p v-if="getMessageContent(message).caption" class="text-sm">{{ getMessageContent(message).caption }}</p>
            </div>

            <!-- Text message (default) -->
            <p v-else class="whitespace-pre-wrap">{{ formatMessageText(message) }}</p>

            <p :class="[
              'text-xs mt-1',
              message.direction === 'outgoing' ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400'
            ]">
              {{ message.created_at }}
            </p>
          </div>
        </div>
      </div>

      <!-- Message Input -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-end gap-3">
          <div class="flex-1">
            <textarea
              v-model="newMessage"
              @keydown.enter.exact.prevent="sendMessage"
              rows="2"
              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white resize-none"
              placeholder="Xabar yozing..."
            ></textarea>
          </div>
          <button
            @click="sendMessage"
            :disabled="!newMessage.trim() || isSending"
            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-xl transition-colors flex items-center gap-2"
          >
            <svg v-if="isSending" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- User Info Sidebar -->
    <div class="w-80 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white">Foydalanuvchi ma'lumotlari</h3>
      </div>

      <div class="p-4 space-y-4">
        <div class="flex items-center justify-center">
          <div class="w-20 h-20 rounded-full flex items-center justify-center bg-blue-100 dark:bg-blue-900/30">
            <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">
              {{ getInitials(user.full_name) }}
            </span>
          </div>
        </div>

        <div class="text-center">
          <p class="text-lg font-medium text-gray-900 dark:text-white">{{ user.full_name || 'Noma\'lum' }}</p>
          <p v-if="user.username" class="text-gray-500 dark:text-gray-400">@{{ user.username }}</p>
        </div>

        <div class="space-y-3">
          <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Telegram ID</p>
            <p class="font-medium text-gray-900 dark:text-white">{{ user.telegram_id }}</p>
          </div>

          <div v-if="user.phone" class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Telefon</p>
            <p class="font-medium text-gray-900 dark:text-white">{{ user.phone }}</p>
          </div>

          <div v-if="conversation.handoff_reason" class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <p class="text-xs text-yellow-600 dark:text-yellow-400 mb-1">Handoff sababi</p>
            <p class="font-medium text-yellow-700 dark:text-yellow-300">{{ conversation.handoff_reason }}</p>
          </div>

          <div v-if="user.tags && user.tags.length > 0" class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Teglar</p>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="tag in user.tags"
                :key="tag"
                class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs rounded-full"
              >
                {{ tag }}
              </span>
            </div>
          </div>
        </div>

        <div v-if="lead" class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
          <p class="text-xs text-green-600 dark:text-green-400 mb-1">Bog'langan Lid</p>
          <p class="font-medium text-green-700 dark:text-green-300">{{ lead.name }}</p>
          <span class="inline-block mt-1 px-2 py-0.5 bg-green-200 dark:bg-green-800/30 text-green-800 dark:text-green-200 text-xs rounded-full">
            {{ lead.status }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  bot: Object,
  conversation: Object,
  user: Object,
  messages: {
    type: Array,
    default: () => []
  },
  lead: Object,
  panelType: {
    type: String,
    default: 'business',
    validator: (value) => ['business', 'marketing'].includes(value)
  }
})

// Route helper based on panel type
const getRoute = (name, params = null) => {
  const prefix = props.panelType === 'business' ? 'business.' : 'marketing.'
  return params ? route(prefix + name, params) : route(prefix + name)
}

const messagesContainer = ref(null)
const newMessage = ref('')
const isSending = ref(false)

const getInitials = (name) => {
  if (!name) return '?'
  const parts = name.split(' ')
  return parts.map(p => p[0]).join('').toUpperCase().slice(0, 2)
}

// Message content helpers
const getMessageContent = (message) => {
  const content = message.content
  if (!content) return {}
  if (typeof content === 'string') {
    try {
      return JSON.parse(content)
    } catch {
      return { text: content }
    }
  }
  return content
}

const getMessageType = (message) => {
  const content = getMessageContent(message)

  // Check for command
  if (content.command) return 'command'

  // Check for contact
  if (content.phone_number) return 'contact'

  // Check for location
  if (content.latitude && content.longitude) return 'location'

  // Check for photo
  if (content.photo || content.file_id) return 'photo'

  // Check for document
  if (content.document || content.file_name) return 'document'

  // Default to text
  return 'text'
}

const formatMessageText = (message) => {
  const content = getMessageContent(message)

  // If it's already a string with text property
  if (content.text) return content.text

  // If it's a plain string
  if (typeof message.content === 'string') return message.content

  // For unknown objects, show a friendly message
  if (typeof content === 'object' && Object.keys(content).length > 0) {
    // Check for common patterns
    if (content.message) return content.message
    if (content.body) return content.body

    // Unknown format - don't show raw JSON
    return '[Xabar]'
  }

  return message.content || ''
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Faol',
    handoff: 'Handoff',
    closed: 'Yopilgan',
  }
  return labels[status] || status
}

const getStatusBgClass = (status) => {
  const classes = {
    active: 'bg-green-100 dark:bg-green-900/30',
    handoff: 'bg-yellow-100 dark:bg-yellow-900/30',
    closed: 'bg-gray-100 dark:bg-gray-700',
  }
  return classes[status] || classes.active
}

const getStatusTextClass = (status) => {
  const classes = {
    active: 'text-green-600 dark:text-green-400',
    handoff: 'text-yellow-600 dark:text-yellow-400',
    closed: 'text-gray-600 dark:text-gray-400',
  }
  return classes[status] || classes.active
}

const getStatusBadgeClass = (status) => {
  const classes = {
    active: 'px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    handoff: 'px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
    closed: 'px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
  }
  return classes[status] || classes.active
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || isSending.value) return

  isSending.value = true

  try {
    const response = await fetch(getRoute('telegram-funnels.conversations.send', [props.bot.id, props.conversation.id]), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ text: newMessage.value })
    })

    const data = await response.json()

    if (data.success) {
      newMessage.value = ''
      router.reload({ only: ['messages'] })
    } else {
      alert(data.message || 'Xabar yuborishda xatolik')
    }
  } catch (error) {
    alert('Server bilan bog\'lanishda xatolik')
  } finally {
    isSending.value = false
  }
}

const assignToMe = async () => {
  await fetch(getRoute('telegram-funnels.conversations.assign', [props.bot.id, props.conversation.id]), {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
  })
  router.reload()
}

const closeConversation = async () => {
  if (confirm('Suhbatni yopishni xohlaysizmi?')) {
    await fetch(getRoute('telegram-funnels.conversations.close', [props.bot.id, props.conversation.id]), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })
    router.reload()
  }
}

const reopenConversation = async () => {
  await fetch(getRoute('telegram-funnels.conversations.reopen', [props.bot.id, props.conversation.id]), {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
  })
  router.reload()
}

onMounted(() => {
  scrollToBottom()
})
</script>
