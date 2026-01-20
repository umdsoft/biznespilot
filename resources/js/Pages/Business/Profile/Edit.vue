<template>
  <BusinessLayout :title="t('business.edit_title')">
    <div class="max-w-3xl mx-auto">
      <div class="mb-6">
        <Link :href="route('business.business.index')" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4">
          <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          {{ t('common.back') }}
        </Link>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('business.edit_title') }}</h2>
        <p class="mt-1 text-sm text-gray-600">
          {{ business.name }} {{ t('business.update_info') }}
        </p>
      </div>

      <Card>
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Asosiy Ma'lumotlar -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('business.basic_info') }}</h3>
            <div class="space-y-4">
              <Input
                v-model="form.name"
                :label="t('business.business_name')"
                placeholder="Mening biznesim"
                :error="form.errors.name"
                required
              />

              <Input
                v-model="form.industry"
                :label="t('business.industry')"
                :placeholder="t('business.industry_placeholder')"
                :error="form.errors.industry"
                required
              />

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  {{ t('business.description_optional') }}
                </label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                  :placeholder="t('business.description_placeholder')"
                />
                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>
            </div>
          </div>

          <!-- Aloqa Ma'lumotlari -->
          <div class="pt-6 border-t border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('business.contact_info') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <Input
                v-model="form.phone"
                :label="t('business.phone_optional')"
                type="tel"
                placeholder="+998901234567"
                :error="form.errors.phone"
              />

              <Input
                v-model="form.email"
                :label="t('business.email_optional')"
                type="email"
                placeholder="info@example.uz"
                :error="form.errors.email"
              />

              <Input
                v-model="form.website"
                :label="t('business.website_optional')"
                type="url"
                placeholder="https://example.uz"
                :error="form.errors.website"
                class="md:col-span-2"
              />
            </div>
          </div>

          <!-- Manzil -->
          <div class="pt-6 border-t border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('business.address_section') }}</h3>
            <div class="space-y-4">
              <Input
                v-model="form.address"
                :label="t('business.street_optional')"
                placeholder="Amir Temur ko'chasi, 107"
                :error="form.errors.address"
              />

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Input
                  v-model="form.city"
                  :label="t('business.city_optional')"
                  placeholder="Toshkent"
                  :error="form.errors.city"
                />

                <Input
                  v-model="form.country"
                  :label="t('business.country_optional')"
                  placeholder="O'zbekiston"
                  :error="form.errors.country"
                />
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="confirmDelete"
              class="px-4 py-2 text-red-600 hover:text-red-700 font-medium"
            >
              {{ t('business.delete_business') }}
            </button>
            <div class="flex items-center space-x-3">
              <Link
                :href="route('business.business.index')"
                class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
              >
                {{ t('common.cancel') }}
              </Link>
              <Button
                type="submit"
                variant="primary"
                :loading="form.processing"
              >
                {{ t('common.save') }}
              </Button>
            </div>
          </div>
        </form>
      </Card>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { useForm, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/components/Card.vue';
import Input from '@/components/Input.vue';
import Button from '@/components/Button.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  business: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  name: props.business.name,
  industry: props.business.industry,
  description: props.business.description || '',
  website: props.business.website || '',
  phone: props.business.phone || '',
  email: props.business.email || '',
  address: props.business.address || '',
  city: props.business.city || '',
  country: props.business.country || '',
});

const submit = () => {
  form.put(route('business.business.update', props.business.id));
};

const confirmDelete = () => {
  if (confirm(t('business.delete_confirm'))) {
    router.delete(route('business.business.destroy', props.business.id));
  }
};
</script>
