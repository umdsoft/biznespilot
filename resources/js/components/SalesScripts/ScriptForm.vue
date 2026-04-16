<template>
  <div class="p-4 sm:p-6 max-w-5xl mx-auto">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <Link :href="route('business.sales-scripts.index')"
        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </Link>
      <h1 class="text-xl font-bold">{{ mode === 'create' ? 'Yangi skript' : 'Skriptni tahrirlash' }}</h1>
    </div>

    <!-- Xatolar -->
    <div v-if="errorMessage" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4 text-sm">
      {{ errorMessage }}
    </div>

    <!-- Asosiy ma'lumot -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-4">
      <h2 class="font-semibold mb-4">📝 Asosiy ma'lumot</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Skript nomi *</label>
          <input v-model="form.name" type="text" required
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg text-sm"
            placeholder="Masalan: IELTS kursi sotuv skripti" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Skript turi</label>
          <select v-model="form.script_type"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg text-sm">
            <option value="general">Umumiy</option>
            <option value="inbound">Kiruvchi qo'ng'iroq</option>
            <option value="outbound">Chiquvchi qo'ng'iroq</option>
            <option value="follow_up">Follow-up</option>
          </select>
        </div>
      </div>

      <div class="mt-4">
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tavsif</label>
        <textarea v-model="form.description" rows="2"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg text-sm"
          placeholder="Skript qaysi mahsulot va qaysi auditoriyaga?" />
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
        <div>
          <label class="block text-xs font-medium mb-1">Min davomiylik (sek)</label>
          <input v-model.number="form.ideal_duration_min" type="number" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-gray-700" />
        </div>
        <div>
          <label class="block text-xs font-medium mb-1">Max davomiylik (sek)</label>
          <input v-model.number="form.ideal_duration_max" type="number" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-gray-700" />
        </div>
        <div>
          <label class="block text-xs font-medium mb-1">Operator min gapiradi (%)</label>
          <input v-model.number="form.ideal_talk_ratio_min" type="number" step="1" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-gray-700" />
        </div>
        <div>
          <label class="block text-xs font-medium mb-1">Operator max gapiradi (%)</label>
          <input v-model.number="form.ideal_talk_ratio_max" type="number" step="1" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-gray-700" />
        </div>
      </div>

      <label class="flex items-center gap-2 mt-4 text-sm">
        <input v-model="form.is_default" type="checkbox" class="rounded" />
        Standart skript (boshqa skript yo'q bo'lsa, shu qo'llaniladi)
      </label>
    </div>

    <!-- Bosqichlar -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-4">
      <h2 class="font-semibold mb-4">🎯 7 ta bosqich</h2>

      <div class="space-y-3">
        <div v-for="(stageKey, index) in stageKeys" :key="stageKey"
          class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
          <button @click="toggleStage(stageKey)" type="button"
            class="w-full flex items-center justify-between p-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <div class="flex items-center gap-3">
              <span class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 flex items-center justify-center text-xs font-bold">{{ index + 1 }}</span>
              <div>
                <div class="font-medium text-sm">{{ stageLabels[stageKey] || stageKey }}</div>
                <div class="text-xs text-gray-500">
                  {{ (form.stages[stageKey]?.required || []).length }} majburiy,
                  {{ (form.stages[stageKey]?.forbidden || []).length }} taqiqlangan
                </div>
              </div>
            </div>
            <svg :class="{ 'rotate-180': expandedStage === stageKey }" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>

          <div v-show="expandedStage === stageKey" class="p-4 bg-gray-50 dark:bg-gray-900/30 border-t border-gray-200 dark:border-gray-700 space-y-4">
            <!-- Misol -->
            <div>
              <label class="block text-xs font-medium mb-1">💬 Misol matn</label>
              <textarea v-model="form.stages[stageKey].example" rows="2"
                class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-gray-800" />
            </div>

            <!-- Majburiy frazalar -->
            <PhraseList
              :items="form.stages[stageKey].required"
              label="✅ Majburiy frazalar"
              color="green"
              placeholder="Masalan: assalomu alaykum"
              @update="form.stages[stageKey].required = $event"
            />

            <!-- Taqiqlangan frazalar -->
            <PhraseList
              :items="form.stages[stageKey].forbidden"
              label="❌ Taqiqlangan frazalar"
              color="red"
              placeholder="Masalan: bilmayman"
              @update="form.stages[stageKey].forbidden = $event"
            />

            <!-- Tips -->
            <PhraseList
              :items="form.stages[stageKey].tips"
              label="💡 Maslahatlar"
              color="blue"
              placeholder="Masalan: Mijoz ismini ishlating"
              @update="form.stages[stageKey].tips = $event"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Saqlash -->
    <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
      <Link :href="route('business.sales-scripts.index')"
        class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
        Bekor qilish
      </Link>
      <button @click="save" :disabled="saving"
        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
        {{ saving ? 'Saqlanmoqda...' : (mode === 'create' ? 'Yaratish' : 'Yangilash') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import PhraseList from './PhraseList.vue';

const props = defineProps({
  script: { type: Object, default: null },
  defaultTemplate: { type: Object, default: () => ({}) },
  stageLabels: { type: Object, default: () => ({}) },
  mode: { type: String, default: 'create' },
});

const emit = defineEmits(['saved']);

const saving = ref(false);
const expandedStage = ref(null);
const errorMessage = ref('');

const stageKeys = ['greeting', 'discovery', 'presentation', 'objection_handling', 'closing', 'rapport', 'cta'];

const initStages = () => {
  const stages = {};
  for (const key of stageKeys) {
    const src = (props.script?.stages || props.defaultTemplate || {})[key] || {};
    stages[key] = {
      required: Array.isArray(src.required) ? [...src.required] : [],
      forbidden: Array.isArray(src.forbidden) ? [...src.forbidden] : [],
      tips: Array.isArray(src.tips) ? [...src.tips] : [],
      example: src.example || '',
    };
  }
  return stages;
};

const form = reactive({
  name: props.script?.name || '',
  description: props.script?.description || '',
  script_type: props.script?.script_type || 'general',
  stages: initStages(),
  ideal_duration_min: props.script?.ideal_duration_min || 120,
  ideal_duration_max: props.script?.ideal_duration_max || 600,
  ideal_talk_ratio_min: props.script?.ideal_talk_ratio_min || 30,
  ideal_talk_ratio_max: props.script?.ideal_talk_ratio_max || 60,
  is_default: !!props.script?.is_default,
});

const toggleStage = (key) => {
  expandedStage.value = expandedStage.value === key ? null : key;
};

const save = async () => {
  if (!form.name.trim()) {
    errorMessage.value = 'Skript nomini kiriting';
    return;
  }
  saving.value = true;
  errorMessage.value = '';

  try {
    if (props.mode === 'create') {
      await axios.post('/business/sales-scripts', form);
    } else {
      await axios.put(`/business/sales-scripts/${props.script.id}`, form);
    }
    emit('saved');
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Saqlashda xato yuz berdi';
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  expandedStage.value = 'greeting';
});
</script>
