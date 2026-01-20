<template>
  <div>
    <div class="text-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">{{ t('auth.reset_password') }}</h2>
      <p class="mt-2 text-sm text-gray-600">
        {{ t('auth.reset_desc') }}
      </p>
    </div>

    <form v-if="!success" @submit.prevent="handleSubmit" class="space-y-4">
      <Input
        v-model="form.email"
        :label="t('auth.email')"
        type="email"
        placeholder="email@example.com"
        :error="errors.email"
        required
      />

      <Button
        type="submit"
        variant="primary"
        :loading="loading"
        full-width
      >
        {{ t('auth.send_reset_link') }}
      </Button>

      <div class="text-center">
        <router-link
          to="/auth/login"
          class="text-sm text-primary-600 hover:text-primary-500"
        >
          {{ t('auth.back_to_login') }}
        </router-link>
      </div>
    </form>

    <div v-else class="text-center">
      <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
        <svg class="w-12 h-12 mx-auto text-green-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm text-green-800">
          {{ t('auth.reset_success') }}
        </p>
      </div>

      <router-link
        to="/auth/login"
        class="text-sm text-primary-600 hover:text-primary-500"
      >
        {{ t('auth.back_to_login') }}
      </router-link>
    </div>

    <div v-if="generalError" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
      <p class="text-sm text-red-600">{{ generalError }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Input from '@/components/Input.vue';
import Button from '@/components/Button.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const form = ref({
  email: '',
});

const errors = ref({
  email: '',
});

const generalError = ref('');
const loading = ref(false);
const success = ref(false);

async function handleSubmit() {
  errors.value = { email: '' };
  generalError.value = '';
  loading.value = true;

  try {
    // TODO: Implement forgot password API call
    // await axios.post('/api/v1/auth/forgot-password', { email: form.value.email });

    // For now, just simulate success
    await new Promise(resolve => setTimeout(resolve, 1500));
    success.value = true;
  } catch (error) {
    if (error.errors) {
      errors.value.email = error.errors.email?.[0] || '';
    } else {
      generalError.value = error.message || t('auth.error_occurred');
    }
  } finally {
    loading.value = false;
  }
}
</script>
