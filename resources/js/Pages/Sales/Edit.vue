<template>
  <BusinessLayout title="Lead Tahrirlash">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <Link href="/sales" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4">
          <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Sotuv
        </Link>
        <h2 class="text-2xl font-bold text-gray-900">Lead Tahrirlash</h2>
        <p class="mt-1 text-sm text-gray-600">
          {{ lead.name }} ma'lumotlarini yangilash
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Asosiy Ma'lumotlar -->
        <Card title="Asosiy Ma'lumotlar">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              v-model="form.name"
              label="Ism Familiya"
              placeholder="Alisher Valiyev"
              :error="form.errors.name"
              required
              class="md:col-span-2"
            />

            <Input
              v-model="form.email"
              label="Email"
              type="email"
              placeholder="alisher@example.com"
              :error="form.errors.email"
            />

            <Input
              v-model="form.phone"
              label="Telefon"
              placeholder="+998 90 123 45 67"
              :error="form.errors.phone"
            />

            <Input
              v-model="form.company"
              label="Kompaniya"
              placeholder="ABC Company"
              :error="form.errors.company"
              class="md:col-span-2"
            />
          </div>
        </Card>

        <!-- Lead Ma'lumotlari -->
        <Card title="Lead Ma'lumotlari">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Holat *
              </label>
              <select
                v-model="form.status"
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                required
              >
                <option value="new">Yangi</option>
                <option value="contacted">Bog'lanildi</option>
                <option value="qualified">Qualified</option>
                <option value="proposal">Taklif</option>
                <option value="negotiation">Muzokara</option>
                <option value="won">Yutildi</option>
                <option value="lost">Yo'qoldi</option>
              </select>
              <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                {{ form.errors.status }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Manba
              </label>
              <select
                v-model="form.source_id"
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="">Tanlang</option>
                <option
                  v-for="channel in channels"
                  :key="channel.id"
                  :value="channel.id"
                >
                  {{ channel.name }}
                </option>
              </select>
              <p v-if="form.errors.source_id" class="mt-1 text-sm text-red-600">
                {{ form.errors.source_id }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Ball (0-100)
              </label>
              <div class="flex items-center space-x-3">
                <input
                  v-model.number="form.score"
                  type="range"
                  min="0"
                  max="100"
                  class="flex-1"
                />
                <span class="text-sm font-medium text-gray-900 w-12">{{ form.score }}</span>
              </div>
              <p v-if="form.errors.score" class="mt-1 text-sm text-red-600">
                {{ form.errors.score }}
              </p>
            </div>

            <Input
              v-model.number="form.estimated_value"
              label="Taxminiy qiymat (so'm)"
              type="number"
              step="0.01"
              placeholder="0.00"
              :error="form.errors.estimated_value"
            />
          </div>
        </Card>

        <!-- Qo'shimcha Ma'lumotlar -->
        <Card title="Qo'shimcha Ma'lumotlar">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Izohlar
            </label>
            <textarea
              v-model="form.notes"
              rows="5"
              class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
              placeholder="Lead haqida qo'shimcha ma'lumotlar..."
            ></textarea>
            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
              {{ form.errors.notes }}
            </p>
          </div>
        </Card>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-6">
          <button
            type="button"
            @click="confirmDelete"
            class="px-4 py-2 text-red-600 hover:text-red-700 font-medium"
          >
            Lead O'chirish
          </button>
          <div class="flex items-center space-x-3">
            <Link
              href="/sales"
              class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
            >
              Bekor qilish
            </Link>
            <Button
              type="submit"
              variant="primary"
              :loading="form.processing"
            >
              Saqlash
            </Button>
          </div>
        </div>
      </form>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { useForm, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/components/Card.vue';
import Input from '@/components/Input.vue';
import Button from '@/components/Button.vue';

const props = defineProps({
  lead: {
    type: Object,
    required: true,
  },
  channels: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  name: props.lead.name,
  email: props.lead.email || '',
  phone: props.lead.phone || '',
  company: props.lead.company || '',
  source_id: props.lead.source_id || '',
  status: props.lead.status,
  score: props.lead.score || 0,
  estimated_value: props.lead.estimated_value || null,
  notes: props.lead.notes || '',
});

const submit = () => {
  form.put(`/sales/${props.lead.id}`);
};

const confirmDelete = () => {
  if (confirm(`Rostdan ham "${props.lead.name}" leadini o'chirmoqchimisiz?`)) {
    router.delete(`/sales/${props.lead.id}`);
  }
};
</script>
