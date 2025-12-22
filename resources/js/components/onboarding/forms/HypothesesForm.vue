<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Biznes Gipotezalari</h3>
        <p class="text-sm text-gray-500">Sinab ko'rmoqchi bo'lgan g'oyalar va gipotezalarni kiriting</p>
      </div>
      <button
        @click="showAddForm = true"
        v-if="!showAddForm"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Gipoteza qo'shish
      </button>
    </div>

    <!-- Add/Edit Form -->
    <div v-if="showAddForm || editingHypothesis" class="bg-gray-50 rounded-xl p-6 space-y-4">
      <h4 class="font-medium text-gray-900">
        {{ editingHypothesis ? 'Gipotezani tahrirlash' : 'Yangi gipoteza' }}
      </h4>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Gipoteza nomi *
        </label>
        <input
          v-model="form.title"
          type="text"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Masalan: TikTok marketing samarali bo'ladi"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Batafsil tavsif
        </label>
        <textarea
          v-model="form.description"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Gipoteza haqida batafsil yozing..."
        ></textarea>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Kategoriya
          </label>
          <select
            v-model="form.category"
            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="marketing">Marketing</option>
            <option value="product">Mahsulot</option>
            <option value="sales">Sotuvlar</option>
            <option value="pricing">Narxlash</option>
            <option value="customer">Mijozlar</option>
            <option value="operations">Operatsiyalar</option>
            <option value="other">Boshqa</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Ustuvorlik
          </label>
          <select
            v-model="form.priority"
            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="low">Past</option>
            <option value="medium">O'rta</option>
            <option value="high">Yuqori</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Kutilgan natija
        </label>
        <textarea
          v-model="form.expected_outcome"
          rows="2"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Agar gipoteza to'g'ri bo'lsa, qanday natija kutasiz?"
        ></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Tekshirish usuli
        </label>
        <textarea
          v-model="form.validation_method"
          rows="2"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Bu gipotezani qanday tekshirish mumkin?"
        ></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Holati
        </label>
        <div class="flex flex-wrap gap-3">
          <label v-for="status in statusOptions" :key="status.value" class="flex items-center gap-2">
            <input
              type="radio"
              v-model="form.status"
              :value="status.value"
              class="text-indigo-600"
            />
            <span class="text-sm">{{ status.label }}</span>
          </label>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-4">
        <button
          type="button"
          @click="cancelForm"
          class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
        >
          Bekor qilish
        </button>
        <button
          @click="saveHypothesis"
          :disabled="!form.title || saving"
          class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ editingHypothesis ? 'Saqlash' : 'Qo\'shish' }}
        </button>
      </div>
    </div>

    <!-- Hypotheses List -->
    <div v-if="hypotheses.length > 0" class="space-y-3">
      <div
        v-for="hypothesis in hypotheses"
        :key="hypothesis.id"
        class="bg-white border border-gray-200 rounded-xl p-5 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span
                :class="[
                  'w-3 h-3 rounded-full',
                  statusDotColors[hypothesis.status] || 'bg-gray-300'
                ]"
              ></span>
              <h4 class="font-medium text-gray-900">{{ hypothesis.title }}</h4>
            </div>

            <div class="flex flex-wrap items-center gap-2 mb-3">
              <span
                :class="[
                  'px-2 py-0.5 text-xs rounded-full',
                  statusColors[hypothesis.status] || 'bg-gray-100 text-gray-600'
                ]"
              >
                {{ statusLabels[hypothesis.status] || hypothesis.status }}
              </span>
              <span class="px-2 py-0.5 text-xs rounded-full bg-indigo-100 text-indigo-600">
                {{ categoryLabels[hypothesis.category] || hypothesis.category }}
              </span>
              <span
                :class="[
                  'px-2 py-0.5 text-xs rounded-full',
                  priorityColors[hypothesis.priority] || 'bg-gray-100 text-gray-600'
                ]"
              >
                {{ priorityLabels[hypothesis.priority] || hypothesis.priority }}
              </span>
            </div>

            <p v-if="hypothesis.description" class="text-sm text-gray-600 mb-2">
              {{ hypothesis.description }}
            </p>

            <div v-if="hypothesis.expected_outcome" class="text-xs text-gray-500 mb-1">
              <span class="font-medium">Kutilgan natija:</span> {{ hypothesis.expected_outcome }}
            </div>

            <div v-if="hypothesis.validation_method" class="text-xs text-gray-500">
              <span class="font-medium">Tekshirish:</span> {{ hypothesis.validation_method }}
            </div>
          </div>

          <div class="flex items-center gap-1 ml-4">
            <button
              @click="editHypothesis(hypothesis)"
              class="p-2 text-gray-400 hover:text-indigo-600 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="deleteHypothesis(hypothesis.id)"
              class="p-2 text-gray-400 hover:text-red-600 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!showAddForm" class="text-center py-12 bg-gray-50 rounded-xl">
      <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
      </svg>
      <h4 class="text-lg font-medium text-gray-900 mb-2">Hali gipoteza qo'shilmagan</h4>
      <p class="text-gray-500 mb-4">Sinab ko'rmoqchi bo'lgan g'oyalaringizni qo'shing</p>
      <button
        @click="showAddForm = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
      >
        Birinchi gipotezani qo'shish
      </button>
    </div>

    <!-- Info text -->
    <p class="text-sm text-gray-500 text-center">
      Gipotezalarni sinash - innovatsion o'sish uchun muhim qadam
    </p>

    <!-- Action Buttons -->
    <div class="flex justify-between gap-3 pt-4">
      <button
        type="button"
        @click="$emit('skip')"
        class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
      >
        O'tkazib yuborish
      </button>
      <div class="flex gap-3">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
        >
          Bekor qilish
        </button>
        <button
          @click="$emit('submit')"
          class="px-6 py-3 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors"
        >
          Davom etish
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';
import { useToastStore } from '@/stores/toast';

const store = useOnboardingStore();
const toast = useToastStore();

const emit = defineEmits(['submit', 'cancel', 'skip']);

const hypotheses = ref([]);
const showAddForm = ref(false);
const editingHypothesis = ref(null);
const saving = ref(false);

const form = reactive({
  title: '',
  description: '',
  category: 'marketing',
  priority: 'medium',
  expected_outcome: '',
  validation_method: '',
  status: 'pending'
});

const statusOptions = [
  { value: 'pending', label: 'Kutilmoqda' },
  { value: 'testing', label: 'Sinovda' },
  { value: 'validated', label: 'Tasdiqlangan' },
  { value: 'invalidated', label: 'Rad etilgan' }
];

const statusColors = {
  pending: 'bg-gray-100 text-gray-600',
  testing: 'bg-blue-100 text-blue-600',
  validated: 'bg-green-100 text-green-600',
  invalidated: 'bg-red-100 text-red-600'
};

const statusDotColors = {
  pending: 'bg-gray-400',
  testing: 'bg-blue-500',
  validated: 'bg-green-500',
  invalidated: 'bg-red-500'
};

const statusLabels = {
  pending: 'Kutilmoqda',
  testing: 'Sinovda',
  validated: 'Tasdiqlangan',
  invalidated: 'Rad etilgan'
};

const categoryLabels = {
  marketing: 'Marketing',
  product: 'Mahsulot',
  sales: 'Sotuvlar',
  pricing: 'Narxlash',
  customer: 'Mijozlar',
  operations: 'Operatsiyalar',
  other: 'Boshqa'
};

const priorityColors = {
  low: 'bg-green-100 text-green-600',
  medium: 'bg-yellow-100 text-yellow-600',
  high: 'bg-orange-100 text-orange-600'
};

const priorityLabels = {
  low: 'Past',
  medium: 'O\'rta',
  high: 'Yuqori'
};

onMounted(async () => {
  await loadHypotheses();
});

async function loadHypotheses() {
  try {
    const data = await store.fetchHypotheses();
    hypotheses.value = data || [];
  } catch (err) {
    console.error('Failed to load hypotheses:', err);
  }
}

function resetForm() {
  form.title = '';
  form.description = '';
  form.category = 'marketing';
  form.priority = 'medium';
  form.expected_outcome = '';
  form.validation_method = '';
  form.status = 'pending';
}

function cancelForm() {
  showAddForm.value = false;
  editingHypothesis.value = null;
  resetForm();
}

function editHypothesis(hypothesis) {
  editingHypothesis.value = hypothesis;
  form.title = hypothesis.title;
  form.description = hypothesis.description || '';
  form.category = hypothesis.category || 'marketing';
  form.priority = hypothesis.priority || 'medium';
  form.expected_outcome = hypothesis.expected_outcome || '';
  form.validation_method = hypothesis.validation_method || '';
  form.status = hypothesis.status || 'pending';
  showAddForm.value = false;
}

async function saveHypothesis() {
  if (!form.title) return;

  saving.value = true;

  try {
    if (editingHypothesis.value) {
      await store.updateHypothesis(editingHypothesis.value.id, { ...form });
      toast.success('Muvaffaqiyatli saqlandi', 'Gipoteza yangilandi');
    } else {
      await store.storeHypothesis({ ...form });
      toast.success('Muvaffaqiyatli qo\'shildi', 'Yangi gipoteza qo\'shildi');
    }

    await loadHypotheses();
    cancelForm();
  } catch (err) {
    console.error('Failed to save hypothesis:', err);
    const errorMessage = err.response?.data?.message || 'Ma\'lumotlarni saqlashda xatolik yuz berdi';
    toast.error('Xatolik', errorMessage);
  } finally {
    saving.value = false;
  }
}

async function deleteHypothesis(id) {
  if (!confirm('Rostdan ham bu gipotezani o\'chirmoqchimisiz?')) return;

  try {
    await store.deleteHypothesis(id);
    await loadHypotheses();
    toast.success('O\'chirildi', 'Gipoteza muvaffaqiyatli o\'chirildi');
  } catch (err) {
    console.error('Failed to delete hypothesis:', err);
    toast.error('Xatolik', 'Gipotezani o\'chirishda xatolik yuz berdi');
  }
}
</script>
