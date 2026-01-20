<template>
  <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <div class="w-full max-w-md">
      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl mb-4">
            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ t('auth.invite.title') }}</h1>
          <p class="text-gray-600">
            {{ t('auth.invite.invited_to') }} <span class="font-semibold text-blue-600">{{ business?.name }}</span>
          </p>
        </div>

        <!-- Invitation Details -->
        <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-3">
          <div class="flex justify-between items-center">
            <span class="text-gray-500">{{ t('auth.invite.inviter') }}</span>
            <span class="font-medium text-gray-900">{{ inviter?.name }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-500">{{ t('auth.invite.department') }}</span>
            <span class="font-medium text-gray-900">{{ department }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-500">{{ t('auth.invite.role') }}</span>
            <span class="font-medium text-gray-900">{{ role }}</span>
          </div>
        </div>

        <!-- Form (if needs password) -->
        <form v-if="needsPassword" @submit.prevent="acceptInvite" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('auth.invite.your_name') }}</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
              :placeholder="t('auth.invite.full_name_placeholder')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('auth.password') }}</label>
            <input
              v-model="form.password"
              type="password"
              required
              minlength="8"
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
              :placeholder="t('auth.password_min')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('auth.invite.confirm_password') }}</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
              :placeholder="t('auth.password_reenter')"
            />
          </div>

          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

          <button
            type="submit"
            :disabled="isLoading"
            class="w-full py-3 px-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold rounded-xl hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isLoading ? t('auth.loading') : t('auth.invite.accept') }}
          </button>
        </form>

        <!-- Simple accept button (if already has account) -->
        <div v-else class="space-y-4">
          <p class="text-center text-gray-600 text-sm">
            Email: <span class="font-medium">{{ email }}</span>
          </p>

          <p v-if="error" class="text-sm text-red-600 text-center">{{ error }}</p>

          <button
            @click="acceptInvite"
            :disabled="isLoading"
            class="w-full py-3 px-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold rounded-xl hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isLoading ? t('auth.loading') : t('auth.invite.accept') }}
          </button>
        </div>

        <!-- Back to login -->
        <p class="mt-6 text-center text-sm text-gray-500">
          {{ t('auth.invite.different_account') }}
          <a :href="route('login')" class="text-blue-600 hover:text-blue-700 font-medium">
            {{ t('auth.login_link') }}
          </a>
        </p>
      </div>

      <!-- Footer -->
      <p class="mt-8 text-center text-xs text-gray-500">
        &copy; {{ new Date().getFullYear() }} BiznesPilot. {{ t('auth.copyright') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  token: String,
  business: Object,
  inviter: Object,
  department: String,
  role: String,
  email: String,
  needsPassword: Boolean,
});

const form = ref({
  name: '',
  password: '',
  password_confirmation: '',
});

const isLoading = ref(false);
const error = ref('');

const acceptInvite = async () => {
  isLoading.value = true;
  error.value = '';

  try {
    const payload = props.needsPassword ? form.value : {};

    const response = await fetch(route('invite.accept', props.token), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (response.ok && data.success) {
      window.location.href = data.redirect || '/business';
    } else {
      error.value = data.error || data.message || t('auth.error_occurred');
    }
  } catch (err) {
    console.error('Failed to accept invite:', err);
    error.value = t('auth.invite.network_error');
  } finally {
    isLoading.value = false;
  }
};
</script>
