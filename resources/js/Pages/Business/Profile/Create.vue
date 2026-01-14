<template>
  <BusinessLayout title="Yangi Biznes">
    <div class="max-w-3xl mx-auto">
      <div class="mb-6">
        <Link :href="route('business.business.index')" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4">
          <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Orqaga
        </Link>
        <h2 class="text-2xl font-bold text-gray-900">Yangi Biznes Yaratish</h2>
        <p class="mt-1 text-sm text-gray-600">
          Biznes ma'lumotlarini to'ldiring
        </p>
      </div>

      <Card>
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Asosiy Ma'lumotlar -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Asosiy Ma'lumotlar</h3>
            <div class="space-y-4">
              <Input
                v-model="form.name"
                label="Biznes nomi"
                placeholder="Mening biznesim"
                :error="form.errors.name"
                required
              />

              <Input
                v-model="form.industry"
                label="Soha"
                placeholder="Masalan: Restoran, Elektron savdo"
                :error="form.errors.industry"
                required
              />

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Tavsif (ixtiyoriy)
                </label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="Biznesingiz haqida qisqacha..."
                />
                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>
            </div>
          </div>

          <!-- Aloqa Ma'lumotlari -->
          <div class="pt-6 border-t border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Aloqa Ma'lumotlari</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <Input
                v-model="form.phone"
                label="Telefon (ixtiyoriy)"
                type="tel"
                placeholder="+998901234567"
                :error="form.errors.phone"
              />

              <Input
                v-model="form.email"
                label="Email (ixtiyoriy)"
                type="email"
                placeholder="info@example.uz"
                :error="form.errors.email"
              />

              <Input
                v-model="form.website"
                label="Veb-sayt (ixtiyoriy)"
                type="url"
                placeholder="https://example.uz"
                :error="form.errors.website"
                class="md:col-span-2"
              />
            </div>
          </div>

          <!-- Manzil -->
          <div class="pt-6 border-t border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Manzil</h3>
            <div class="space-y-4">
              <Input
                v-model="form.address"
                label="Ko'cha manzili (ixtiyoriy)"
                placeholder="Amir Temur ko'chasi, 107"
                :error="form.errors.address"
              />

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Input
                  v-model="form.city"
                  label="Shahar (ixtiyoriy)"
                  placeholder="Toshkent"
                  :error="form.errors.city"
                />

                <Input
                  v-model="form.country"
                  label="Mamlakat (ixtiyoriy)"
                  placeholder="O'zbekiston"
                  :error="form.errors.country"
                />
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
            <Link
              :href="route('business.business.index')"
              class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
            >
              Bekor qilish
            </Link>
            <Button
              type="submit"
              variant="primary"
              :loading="form.processing"
            >
              Biznes Yaratish
            </Button>
          </div>
        </form>
      </Card>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/components/Card.vue';
import Input from '@/components/Input.vue';
import Button from '@/components/Button.vue';

const form = useForm({
  name: '',
  industry: '',
  description: '',
  website: '',
  phone: '',
  email: '',
  address: '',
  city: '',
  country: 'O\'zbekiston',
});

const submit = () => {
  form.post(route('business.business.store'));
};
</script>
