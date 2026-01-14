<template>
  <div class="max-w-8xl mx-auto">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <Link :href="getHref('')" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-2">
          <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Marketing
        </Link>
        <h2 class="text-2xl font-bold text-gray-900">Marketing Kanallari</h2>
        <p class="mt-1 text-sm text-gray-600">
          Barcha marketing kanallaringizni boshqaring
        </p>
      </div>
      <button
        @click="openCreateModal"
        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
      >
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Kanal Qo'shish
      </button>
    </div>

    <!-- Channels Grid -->
    <div v-if="channels.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <Card
        v-for="channel in channels"
        :key="channel.id"
        class="hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between mb-4">
          <div :class="getChannelIconClass(channel.type)" class="w-12 h-12 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </div>
          <span
            :class="getStatusClass(channel.status)"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          >
            {{ getStatusLabel(channel.status) }}
          </span>
        </div>

        <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ channel.name }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ channel.platform }}</p>

        <div class="space-y-2 mb-4">
          <div v-if="channel.followers_count" class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Kuzatuvchilar:</span>
            <span class="font-medium text-gray-900">{{ formatNumber(channel.followers_count) }}</span>
          </div>
          <div v-if="channel.monthly_reach" class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Oylik qamrov:</span>
            <span class="font-medium text-gray-900">{{ formatNumber(channel.monthly_reach) }}</span>
          </div>
          <div v-if="channel.engagement_rate" class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Engagement:</span>
            <span class="font-medium text-gray-900">{{ channel.engagement_rate }}%</span>
          </div>
        </div>

        <div v-if="channel.url" class="mb-4">
          <a
            :href="channel.url"
            target="_blank"
            class="text-sm text-primary-600 hover:text-primary-700 inline-flex items-center"
          >
            Kanalga o'tish
            <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
          </a>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
          <span class="text-xs text-gray-500">{{ channel.created_at }}</span>
          <div class="flex items-center space-x-2">
            <button
              @click="openEditModal(channel)"
              class="p-1.5 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
              title="Tahrirlash"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="confirmDelete(channel)"
              class="p-1.5 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
              title="O'chirish"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </Card>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Hech qanday kanal mavjud emas</h3>
      <p class="text-gray-600 mb-6">Birinchi marketing kanalingizni qo'shing</p>
      <button
        @click="openCreateModal"
        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
      >
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Kanal Qo'shish
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" @close="closeModal">
      <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          {{ isEditing ? 'Kanalni Tahrirlash' : 'Yangi Kanal Qo\'shish' }}
        </h3>

        <form @submit.prevent="submit" class="space-y-4">
          <Input
            v-model="form.name"
            label="Kanal nomi"
            placeholder="Instagram Asosiy Sahifa"
            :error="form.errors.name"
            required
          />

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Turi *
              </label>
              <select
                v-model="form.type"
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                required
              >
                <option value="">Tanlang</option>
                <option value="social_media">Ijtimoiy Tarmoq</option>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
                <option value="advertising">Reklama</option>
                <option value="other">Boshqa</option>
              </select>
              <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">
                {{ form.errors.type }}
              </p>
            </div>

            <Input
              v-model="form.platform"
              label="Platforma"
              placeholder="Instagram, Facebook..."
              :error="form.errors.platform"
              required
            />
          </div>

          <Input
            v-model="form.url"
            label="URL"
            placeholder="https://instagram.com/yourpage"
            :error="form.errors.url"
          />

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Holat *
            </label>
            <select
              v-model="form.status"
              class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
              required
            >
              <option value="active">Faol</option>
              <option value="inactive">Faol emas</option>
              <option value="paused">To'xtatilgan</option>
            </select>
            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
              {{ form.errors.status }}
            </p>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <Input
              v-model.number="form.followers_count"
              label="Kuzatuvchilar soni"
              type="number"
              placeholder="0"
              :error="form.errors.followers_count"
            />

            <Input
              v-model.number="form.monthly_reach"
              label="Oylik qamrov"
              type="number"
              placeholder="0"
              :error="form.errors.monthly_reach"
            />

            <Input
              v-model.number="form.engagement_rate"
              label="Engagement (%)"
              type="number"
              step="0.01"
              placeholder="0.00"
              :error="form.errors.engagement_rate"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Izohlar
            </label>
            <textarea
              v-model="form.notes"
              rows="3"
              class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
              placeholder="Qo'shimcha izohlar..."
            ></textarea>
            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
              {{ form.errors.notes }}
            </p>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
            >
              Bekor qilish
            </button>
            <Button
              type="submit"
              variant="primary"
              :loading="form.processing"
            >
              {{ isEditing ? 'Saqlash' : 'Qo\'shish' }}
            </Button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import Card from '@/components/Card.vue';
import Input from '@/components/Input.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';

const props = defineProps({
  panelType: {
    type: String,
    required: true,
    validator: (value) => ['business', 'marketing'].includes(value),
  },
  channels: {
    type: Array,
    default: () => [],
  },
});

// Helper to generate correct href based on panel type
const getHref = (path) => {
  const prefix = props.panelType === 'business' ? '/business/marketing' : '/marketing';
  return prefix + path;
};

// Helper to generate correct API endpoint based on panel type
const getApiPath = (path) => {
  const prefix = props.panelType === 'business' ? '/business/marketing' : '/marketing';
  return prefix + path;
};

const showModal = ref(false);
const isEditing = ref(false);
const editingChannel = ref(null);

const form = useForm({
  name: '',
  type: '',
  platform: '',
  url: '',
  status: 'active',
  followers_count: null,
  monthly_reach: null,
  engagement_rate: null,
  notes: '',
});

const openCreateModal = () => {
  isEditing.value = false;
  editingChannel.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (channel) => {
  isEditing.value = true;
  editingChannel.value = channel;
  form.name = channel.name;
  form.type = channel.type;
  form.platform = channel.platform;
  form.url = channel.url || '';
  form.status = channel.status;
  form.followers_count = channel.followers_count;
  form.monthly_reach = channel.monthly_reach;
  form.engagement_rate = channel.engagement_rate;
  form.notes = channel.notes || '';
  form.clearErrors();
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.reset();
  form.clearErrors();
};

const submit = () => {
  if (isEditing.value) {
    form.put(getApiPath(`/channels/${editingChannel.value.id}`), {
      onSuccess: () => {
        closeModal();
      },
    });
  } else {
    form.post(getApiPath('/channels'), {
      onSuccess: () => {
        closeModal();
      },
    });
  }
};

const confirmDelete = (channel) => {
  if (confirm(`Rostdan ham "${channel.name}" kanalini o'chirmoqchimisiz?`)) {
    router.delete(getApiPath(`/channels/${channel.id}`));
  }
};

const formatNumber = (num) => {
  return new Intl.NumberFormat('uz-UZ').format(num);
};

const getChannelIconClass = (type) => {
  const classes = {
    social_media: 'bg-blue-100 text-blue-600',
    email: 'bg-green-100 text-green-600',
    sms: 'bg-yellow-100 text-yellow-600',
    advertising: 'bg-red-100 text-red-600',
    other: 'bg-gray-100 text-gray-600',
  };
  return classes[type] || classes.other;
};

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    paused: 'bg-yellow-100 text-yellow-800',
  };
  return classes[status] || classes.inactive;
};

const getStatusLabel = (status) => {
  const labels = {
    active: 'Faol',
    inactive: 'Faol emas',
    paused: 'To\'xtatilgan',
  };
  return labels[status] || status;
};
</script>
