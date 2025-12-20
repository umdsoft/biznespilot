<template>
  <BusinessLayout title="Yangi Raqib">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <Link href="/competitors" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4">
          <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Raqobatchilar
        </Link>
        <h2 class="text-2xl font-bold text-gray-900">Yangi Raqib Qo'shish</h2>
        <p class="mt-1 text-sm text-gray-600">Raqib ma'lumotlarini to'ldiring</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card title="Asosiy Ma'lumotlar">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              v-model="form.name"
              label="Kompaniya nomi"
              placeholder="ABC Company"
              :error="form.errors.name"
              required
              class="md:col-span-2"
            />

            <Input
              v-model="form.website"
              label="Veb-sayt"
              placeholder="https://example.com"
              :error="form.errors.website"
              class="md:col-span-2"
            />

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Tavsif</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                placeholder="Raqib haqida qisqacha ma'lumot..."
              ></textarea>
              <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
            </div>
          </div>
        </Card>

        <Card title="SWOT Tahlili">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kuchli Tomonlar</label>
              <textarea
                v-model="form.strengths"
                rows="3"
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                placeholder="Raqibning ustunliklari..."
              ></textarea>
              <p v-if="form.errors.strengths" class="mt-1 text-sm text-red-600">{{ form.errors.strengths }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Zaif Tomonlar</label>
              <textarea
                v-model="form.weaknesses"
                rows="3"
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                placeholder="Raqibning kamchiliklari..."
              ></textarea>
              <p v-if="form.errors.weaknesses" class="mt-1 text-sm text-red-600">{{ form.errors.weaknesses }}</p>
            </div>
          </div>
        </Card>

        <Card title="Mahsulotlar va Narxlash">
          <div class="space-y-4">
            <TagInput
              v-model="form.products"
              label="Mahsulotlar"
              placeholder="Mahsulot nomi..."
              :error="form.errors.products"
            />

            <TagInput
              v-model="form.pricing"
              label="Narxlash Strategiyalari"
              placeholder="Premium narxlash, Chegirmalar..."
              :error="form.errors.pricing"
            />
          </div>
        </Card>

        <Card title="Marketing Strategiyalari">
          <TagInput
            v-model="form.marketing_strategies"
            label="Marketing Strategiyalari"
            placeholder="SEO, Social Media, Email Marketing..."
            :error="form.errors.marketing_strategies"
          />
        </Card>

        <Card title="Baholash">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tahdid Darajasi (0-10) *</label>
              <div class="flex items-center space-x-3">
                <input
                  v-model.number="form.threat_level"
                  type="range"
                  min="0"
                  max="10"
                  class="flex-1"
                />
                <span class="text-lg font-semibold text-gray-900 w-16">{{ form.threat_level }}/10</span>
              </div>
              <p v-if="form.errors.threat_level" class="mt-1 text-sm text-red-600">{{ form.errors.threat_level }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Holat</label>
              <div class="flex items-center">
                <input
                  v-model="form.is_active"
                  type="checkbox"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <span class="ml-2 text-sm text-gray-700">Faol raqib</span>
              </div>
            </div>
          </div>
        </Card>

        <div class="flex items-center justify-end space-x-3 pt-6">
          <Link
            href="/competitors"
            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
          >
            Bekor qilish
          </Link>
          <Button type="submit" variant="primary" :loading="form.processing">
            Raqib Qo'shish
          </Button>
        </div>
      </form>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import Card from '@/components/Card.vue';
import Input from '@/components/Input.vue';
import Button from '@/components/Button.vue';
import TagInput from '@/components/TagInput.vue';

const form = useForm({
  name: '',
  website: '',
  description: '',
  strengths: '',
  weaknesses: '',
  products: [],
  pricing: [],
  marketing_strategies: [],
  threat_level: 5,
  is_active: true,
});

const submit = () => {
  form.post('/competitors');
};
</script>
