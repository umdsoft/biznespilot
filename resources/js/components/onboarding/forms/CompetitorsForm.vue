<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Raqobatchilar Tahlili</h3>
        <p class="text-sm text-gray-500">Asosiy raqobatchilaringizni kiriting va tahlil qiling</p>
      </div>
      <button
        @click="showAddForm = true"
        v-if="!showAddForm"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Raqobatchi qo'shish
      </button>
    </div>

    <!-- Add/Edit Form -->
    <div v-if="showAddForm || editingCompetitor" class="bg-gray-50 rounded-xl p-6 space-y-4">
      <h4 class="font-medium text-gray-900">
        {{ editingCompetitor ? 'Raqobatchini tahrirlash' : 'Yangi raqobatchi' }}
      </h4>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Kompaniya nomi *
          </label>
          <input
            v-model="form.name"
            type="text"
            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Masalan: ABC Company"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Web-sayt
          </label>
          <input
            v-model="form.website"
            type="url"
            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="https://example.com"
          />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Tavsif
        </label>
        <textarea
          v-model="form.description"
          rows="2"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Raqobatchi haqida qisqacha ma'lumot..."
        ></textarea>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Raqobat darajasi
          </label>
          <select
            v-model="form.threat_level"
            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="low">Past</option>
            <option value="medium">O'rta</option>
            <option value="high">Yuqori</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Bozor ulushi (taxminan)
          </label>
          <input
            v-model="form.market_share"
            type="text"
            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Masalan: 15%"
          />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Kuchli tomonlari
        </label>
        <textarea
          v-model="form.strengths"
          rows="2"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Raqobatchining kuchli tomonlari..."
        ></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Zaif tomonlari
        </label>
        <textarea
          v-model="form.weaknesses"
          rows="2"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Raqobatchining zaif tomonlari..."
        ></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Narx segmenti
        </label>
        <div class="flex gap-4">
          <label v-for="segment in priceSegments" :key="segment.value" class="flex items-center gap-2">
            <input
              type="radio"
              v-model="form.price_segment"
              :value="segment.value"
              class="text-indigo-600"
            />
            <span class="text-sm">{{ segment.label }}</span>
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
          @click="saveCompetitor"
          :disabled="!form.name || saving"
          class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ editingCompetitor ? 'Saqlash' : 'Qo\'shish' }}
        </button>
      </div>
    </div>

    <!-- Competitors List -->
    <div v-if="competitors.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="competitor in competitors"
        :key="competitor.id"
        class="bg-white border border-gray-200 rounded-xl p-5 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-400 to-orange-400 flex items-center justify-center text-white font-bold text-lg">
              {{ competitor.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <h4 class="font-semibold text-gray-900">{{ competitor.name }}</h4>
              <a
                v-if="competitor.website"
                :href="competitor.website"
                target="_blank"
                class="text-xs text-indigo-600 hover:underline"
              >
                {{ competitor.website }}
              </a>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <button
              @click="editCompetitor(competitor)"
              class="p-2 text-gray-400 hover:text-indigo-600 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="deleteCompetitor(competitor.id)"
              class="p-2 text-gray-400 hover:text-red-600 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>

        <p v-if="competitor.description" class="text-sm text-gray-600 mb-3">
          {{ competitor.description }}
        </p>

        <div class="flex items-center gap-2 mb-3">
          <span
            :class="[
              'px-2 py-0.5 text-xs rounded-full',
              threatColors[competitor.threat_level] || 'bg-gray-100 text-gray-600'
            ]"
          >
            {{ threatLabels[competitor.threat_level] || 'Noma\'lum' }} xavf
          </span>
          <span v-if="competitor.market_share" class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-600">
            {{ competitor.market_share }} bozor
          </span>
          <span v-if="competitor.price_segment" class="px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-600">
            {{ priceSegmentLabels[competitor.price_segment] || competitor.price_segment }}
          </span>
        </div>

        <div v-if="competitor.strengths || competitor.weaknesses" class="text-xs space-y-1">
          <div v-if="competitor.strengths" class="flex gap-2">
            <span class="text-green-600 font-medium">+</span>
            <span class="text-gray-600">{{ competitor.strengths }}</span>
          </div>
          <div v-if="competitor.weaknesses" class="flex gap-2">
            <span class="text-red-600 font-medium">-</span>
            <span class="text-gray-600">{{ competitor.weaknesses }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!showAddForm" class="text-center py-12 bg-gray-50 rounded-xl">
      <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>
      <h4 class="text-lg font-medium text-gray-900 mb-2">Hali raqobatchi qo'shilmagan</h4>
      <p class="text-gray-500 mb-4">Raqobatchilarni aniqlash - bozorni tushunish uchun muhim</p>
      <button
        @click="showAddForm = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
      >
        Birinchi raqobatchini qo'shish
      </button>
    </div>

    <!-- Info text -->
    <p class="text-sm text-gray-500 text-center">
      Raqobatchilarni tahlil qilish - strategik ustunlik yaratishda muhim qadam
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

const competitors = ref([]);
const showAddForm = ref(false);
const editingCompetitor = ref(null);
const saving = ref(false);

const form = reactive({
  name: '',
  website: '',
  description: '',
  threat_level: 'medium',
  market_share: '',
  strengths: '',
  weaknesses: '',
  price_segment: 'mid'
});

const priceSegments = [
  { value: 'low', label: 'Arzon' },
  { value: 'mid', label: 'O\'rta' },
  { value: 'premium', label: 'Premium' }
];

const threatColors = {
  low: 'bg-green-100 text-green-600',
  medium: 'bg-yellow-100 text-yellow-600',
  high: 'bg-red-100 text-red-600'
};

const threatLabels = {
  low: 'Past',
  medium: 'O\'rta',
  high: 'Yuqori'
};

const priceSegmentLabels = {
  low: 'Arzon',
  mid: 'O\'rta',
  premium: 'Premium'
};

onMounted(async () => {
  await loadCompetitors();
});

async function loadCompetitors() {
  try {
    const data = await store.fetchCompetitors();
    competitors.value = data || [];
  } catch (err) {
    console.error('Failed to load competitors:', err);
  }
}

function resetForm() {
  form.name = '';
  form.website = '';
  form.description = '';
  form.threat_level = 'medium';
  form.market_share = '';
  form.strengths = '';
  form.weaknesses = '';
  form.price_segment = 'mid';
}

function cancelForm() {
  showAddForm.value = false;
  editingCompetitor.value = null;
  resetForm();
}

function editCompetitor(competitor) {
  editingCompetitor.value = competitor;
  form.name = competitor.name;
  form.website = competitor.website || '';
  form.description = competitor.description || '';
  form.threat_level = competitor.threat_level || 'medium';
  form.market_share = competitor.market_share || '';
  form.strengths = competitor.strengths || '';
  form.weaknesses = competitor.weaknesses || '';
  form.price_segment = competitor.price_segment || 'mid';
  showAddForm.value = false;
}

async function saveCompetitor() {
  if (!form.name) return;

  saving.value = true;

  try {
    if (editingCompetitor.value) {
      await store.updateCompetitor(editingCompetitor.value.id, { ...form });
      toast.success('Muvaffaqiyatli saqlandi', 'Raqobatchi yangilandi');
    } else {
      await store.storeCompetitor({ ...form });
      toast.success('Muvaffaqiyatli qo\'shildi', 'Yangi raqobatchi qo\'shildi');
    }

    await loadCompetitors();
    cancelForm();
  } catch (err) {
    console.error('Failed to save competitor:', err);
    const errorMessage = err.response?.data?.message || 'Ma\'lumotlarni saqlashda xatolik yuz berdi';
    toast.error('Xatolik', errorMessage);
  } finally {
    saving.value = false;
  }
}

async function deleteCompetitor(id) {
  if (!confirm('Rostdan ham bu raqobatchini o\'chirmoqchimisiz?')) return;

  try {
    await store.deleteCompetitor(id);
    await loadCompetitors();
    toast.success('O\'chirildi', 'Raqobatchi muvaffaqiyatli o\'chirildi');
  } catch (err) {
    console.error('Failed to delete competitor:', err);
    toast.error('Xatolik', 'Raqobatchini o\'chirishda xatolik yuz berdi');
  }
}
</script>
