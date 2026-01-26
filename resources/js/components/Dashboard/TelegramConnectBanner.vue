<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 -translate-y-4"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 -translate-y-4"
  >
    <!-- Connected State -->
    <div
      v-if="hasTelegramLinked"
      class="relative overflow-hidden mb-6 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl shadow-lg"
    >
      <!-- Background pattern -->
      <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
          <pattern id="telegram-connected-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
            <circle cx="10" cy="10" r="2" fill="currentColor" />
          </pattern>
          <rect x="0" y="0" width="100" height="100" fill="url(#telegram-connected-pattern)" />
        </svg>
      </div>

      <!-- Content -->
      <div class="relative p-4 flex items-center justify-between flex-wrap gap-4">
        <!-- Left side: Icon + Text -->
        <div class="flex items-center gap-3">
          <!-- Success Icon -->
          <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>

          <!-- Text content -->
          <div class="text-white">
            <h3 class="text-base font-semibold flex items-center gap-2">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
              </svg>
              {{ t('telegram.connected_title') }}
            </h3>
            <p class="text-white/80 text-sm">{{ t('telegram.connected_desc') }}</p>
          </div>
        </div>

        <!-- Right side: Disconnect Button -->
        <button
          @click="disconnectTelegram"
          :disabled="isDisconnecting"
          class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl text-sm font-medium transition-all duration-200 disabled:opacity-75 disabled:cursor-wait"
        >
          <svg v-if="isDisconnecting" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          <svg v-else class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
          </svg>
          {{ t('telegram.disconnect_button') }}
        </button>
      </div>
    </div>

    <!-- Not Connected State -->
    <div
      v-else-if="!dismissed"
      class="relative overflow-hidden mb-6 bg-gradient-to-r from-[#0088cc] to-[#00a8e6] rounded-2xl shadow-lg"
    >
      <!-- Background pattern -->
      <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
          <pattern id="telegram-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
            <circle cx="10" cy="10" r="2" fill="currentColor" />
          </pattern>
          <rect x="0" y="0" width="100" height="100" fill="url(#telegram-pattern)" />
        </svg>
      </div>

      <!-- Content -->
      <div class="relative p-6 flex items-center justify-between flex-wrap gap-4">
        <!-- Left side: Icon + Text -->
        <div class="flex items-center gap-4">
          <!-- Telegram Icon -->
          <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
              <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
            </svg>
          </div>

          <!-- Text content -->
          <div class="text-white">
            <h3 class="text-lg font-bold mb-1">{{ t('telegram.connect_banner_title') }}</h3>
            <p class="text-white/80 text-sm max-w-md">
              {{ t('telegram.connect_banner_desc') }}
            </p>
          </div>
        </div>

        <!-- Right side: Button + Close -->
        <div class="flex items-center gap-3">
          <!-- Connect Button -->
          <button
            @click="generateConnectLink"
            :disabled="isGenerating"
            class="inline-flex items-center px-5 py-2.5 bg-white text-[#0088cc] rounded-xl font-semibold text-sm hover:bg-white/90 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-75 disabled:cursor-wait"
          >
            <svg v-if="isGenerating" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <svg v-else class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="currentColor">
              <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
            </svg>
            {{ t('telegram.connect_button') }}
          </button>

          <!-- Dismiss Button -->
          <button
            @click="dismiss"
            class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
            :title="t('common.dismiss')"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Modal for QR/Link -->
      <Teleport to="body">
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            @click.self="showModal = false"
          >
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
              <!-- Header -->
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                  {{ t('telegram.connect_modal_title') }}
                </h3>
                <button
                  @click="showModal = false"
                  class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Instructions -->
              <div class="space-y-4 mb-6">
                <div v-for="(instruction, index) in connectInstructions" :key="index" class="flex items-start gap-3">
                  <div class="w-6 h-6 bg-[#0088cc] rounded-full flex items-center justify-center flex-shrink-0 text-white text-sm font-bold">
                    {{ index + 1 }}
                  </div>
                  <p class="text-gray-600 dark:text-gray-300 text-sm">{{ instruction }}</p>
                </div>
              </div>

              <!-- Connect Link -->
              <a
                :href="connectLink"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-[#0088cc] to-[#00a8e6] text-white rounded-xl font-semibold text-base hover:from-[#0077b3] hover:to-[#0097cc] transition-all duration-200 shadow-lg hover:shadow-xl"
                @click="showModal = false"
              >
                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                </svg>
                {{ t('telegram.open_telegram') }}
              </a>

              <!-- Expiry notice -->
              <p v-if="expiresAt" class="mt-4 text-center text-xs text-gray-400">
                {{ t('telegram.link_expires') }}: {{ formatTime(expiresAt) }}
              </p>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from '@/i18n';

const { t } = useI18n();
const page = usePage();

// State
const dismissed = ref(false);
const isGenerating = ref(false);
const isDisconnecting = ref(false);
const showModal = ref(false);
const connectLink = ref('');
const expiresAt = ref(null);
const connectInstructions = ref([]);

// Computed
const hasTelegramLinked = computed(() => {
  return page.props.auth?.user?.has_telegram_linked ?? false;
});

// Methods
const dismiss = () => {
  dismissed.value = true;
  // Optionally save to localStorage to persist dismissal
  localStorage.setItem('telegram_banner_dismissed', 'true');
};

const generateConnectLink = async () => {
  if (isGenerating.value) return;

  isGenerating.value = true;
  try {
    const response = await axios.post('/business/settings/telegram/connect');
    if (response.data.success) {
      connectLink.value = response.data.link;
      expiresAt.value = response.data.expires_at;
      connectInstructions.value = response.data.instructions || [];
      showModal.value = true;
    }
  } catch (error) {
    console.error('Failed to generate Telegram connect link:', error);
    // Handle already connected case
    if (error.response?.status === 400 && error.response?.data?.connected) {
      // Reload page to update UI
      window.location.reload();
    }
  } finally {
    isGenerating.value = false;
  }
};

const formatTime = (isoString) => {
  if (!isoString) return '';
  const date = new Date(isoString);
  return date.toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' });
};

const disconnectTelegram = async () => {
  if (isDisconnecting.value) return;

  if (!confirm(t('telegram.disconnect_confirm'))) return;

  isDisconnecting.value = true;
  try {
    const response = await axios.delete('/business/settings/telegram/disconnect');
    if (response.data.success) {
      // Reload page to update UI
      window.location.reload();
    }
  } catch (error) {
    console.error('Failed to disconnect Telegram:', error);
    // If already disconnected (cache mismatch), reload to sync UI
    if (error.response?.status === 400) {
      window.location.reload();
    }
  } finally {
    isDisconnecting.value = false;
  }
};

// Check if previously dismissed (but show again after 24 hours)
const checkDismissed = () => {
  const dismissedAt = localStorage.getItem('telegram_banner_dismissed_at');
  if (dismissedAt) {
    const dayAgo = Date.now() - 24 * 60 * 60 * 1000;
    if (parseInt(dismissedAt) > dayAgo) {
      dismissed.value = true;
    } else {
      localStorage.removeItem('telegram_banner_dismissed');
      localStorage.removeItem('telegram_banner_dismissed_at');
    }
  }
};

// Initialize
checkDismissed();
</script>
