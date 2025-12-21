<template>
  <form @submit.prevent="handleSubmit" class="space-y-8">
    <!-- Header with avatar -->
    <div class="flex items-center gap-6 p-6 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl">
      <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 flex items-center justify-center text-white text-3xl font-bold">
        {{ initials }}
      </div>
      <div>
        <h3 class="text-xl font-bold text-gray-900">Ideal Mijoz Profili</h3>
        <p class="text-gray-600">Ideal mijozingiz kim ekanligini aniqlang</p>
      </div>
    </div>

    <!-- Basic Info -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Ism (persona nomi)
        </label>
        <input
          v-model="form.name"
          type="text"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Masalan: Startup Sardor"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Yosh oralig'i
        </label>
        <select
          v-model="form.age_range"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">Tanlang</option>
          <option value="18-24">18-24</option>
          <option value="25-34">25-34</option>
          <option value="35-44">35-44</option>
          <option value="45-54">45-54</option>
          <option value="55+">55+</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Jins
        </label>
        <div class="flex gap-4">
          <label class="flex items-center gap-2">
            <input type="radio" v-model="form.gender" value="male" class="text-indigo-600" />
            <span>Erkak</span>
          </label>
          <label class="flex items-center gap-2">
            <input type="radio" v-model="form.gender" value="female" class="text-indigo-600" />
            <span>Ayol</span>
          </label>
          <label class="flex items-center gap-2">
            <input type="radio" v-model="form.gender" value="all" class="text-indigo-600" />
            <span>Hammasi</span>
          </label>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Joylashuv
        </label>
        <input
          v-model="form.location"
          type="text"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Masalan: Toshkent, O'zbekiston"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Daromad darajasi
        </label>
        <input
          v-model="form.income_level"
          type="text"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Masalan: O'rta-yuqori"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Kasb
        </label>
        <input
          v-model="form.occupation"
          type="text"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Masalan: Tadbirkor, Marketing menejeri"
        />
      </div>
    </div>

    <!-- Sabri Suby 9 Questions -->
    <div class="space-y-6">
      <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <span class="w-8 h-8 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>
        9 ta muhim savol (Sabri Suby metodi)
      </h4>
      <p class="text-sm text-gray-500">Bu savollar ideal mijozingizni chuqur tushunishga yordam beradi</p>

      <!-- Question 1 -->
      <div class="p-4 bg-gray-50 rounded-xl">
        <label class="block text-sm font-medium text-gray-900 mb-2">
          1. Ular qayerda vaqt o'tkazishadi? <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">Onlayn va oflayn - qaysi platformalar, joylar, tadbirlar?</p>
        <textarea
          v-model="form.where_spend_time"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Instagram, Telegram, kafelar, biznes tadbirlar..."
        ></textarea>
      </div>

      <!-- Question 2 -->
      <div class="p-4 bg-gray-50 rounded-xl">
        <label class="block text-sm font-medium text-gray-900 mb-2">
          2. Ular ma'lumot olish uchun qayerga murojaat qilishadi? <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">Bloglar, YouTube kanallar, podkastlar, kitoblar?</p>
        <textarea
          v-model="form.info_sources"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="YouTube, Telegram kanallar, kitoblar..."
        ></textarea>
      </div>

      <!-- Question 3 -->
      <div class="p-4 bg-gray-50 rounded-xl">
        <label class="block text-sm font-medium text-gray-900 mb-2">
          3. Ularning eng katta qiyinchiliklari nima? <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">Kundalik hayotda nima ularni bezovta qiladi?</p>
        <textarea
          v-model="form.frustrations"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Vaqt yetishmasligi, tushunmovchilik, texnologiyadan qo'rqish..."
        ></textarea>
      </div>

      <!-- Question 4 -->
      <div class="p-4 bg-gray-50 rounded-xl">
        <label class="block text-sm font-medium text-gray-900 mb-2">
          4. Ularning orzulari nima? <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">Ular nima haqida orzu qilishadi, qanday kelajak istashadi?</p>
        <textarea
          v-model="form.dreams"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Muvaffaqiyatli biznes, erkinlik, tan olinish..."
        ></textarea>
      </div>

      <!-- Question 5 -->
      <div class="p-4 bg-gray-50 rounded-xl">
        <label class="block text-sm font-medium text-gray-900 mb-2">
          5. Ularning eng katta qo'rquvlari nima? <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">Nimadan qo'rqishadi, nimani yo'qotishdan xavotir olishadi?</p>
        <textarea
          v-model="form.fears"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Muvaffaqiyatsizlik, pul yo'qotish, tanqid..."
        ></textarea>
      </div>

      <!-- Question 6 -->
      <div class="p-4 bg-gray-50 rounded-xl">
        <label class="block text-sm font-medium text-gray-900 mb-2">
          6. Ular qanday muloqot usulini afzal ko'rishadi? <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">Telegram, telefon qo'ng'irog'i, email, yuzma-yuz?</p>
        <textarea
          v-model="form.communication_preferences"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Telegram orqali tez javob, qisqa xabarlar..."
        ></textarea>
      </div>

      <!-- Question 7 -->
      <div class="p-4 bg-gray-50 rounded-xl">
        <label class="block text-sm font-medium text-gray-900 mb-2">
          7. Ular qanday til uslubini ishlatishadi? <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">Rasmiy yoki norasmiy? Qanday so'zlar ishlatishadi?</p>
        <textarea
          v-model="form.language_style"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Norasmiy, do'stona, emoji ishlatadi..."
        ></textarea>
      </div>

      <!-- Question 8 -->
      <div class="p-4 bg-gray-50 rounded-xl">
        <label class="block text-sm font-medium text-gray-900 mb-2">
          8. Ularning kundalik tartibi qanday? <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">Ertalab nima qilishadi? Qachon bo'sh?</p>
        <textarea
          v-model="form.daily_routine"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Ertalab sport, kunduzi ish, kechqurun oila..."
        ></textarea>
      </div>

      <!-- Question 9 -->
      <div class="p-4 bg-gray-50 rounded-xl">
        <label class="block text-sm font-medium text-gray-900 mb-2">
          9. Nima ularni baxtli qiladi? <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">Qanday voqealar, yutuqlar ularni xursand qiladi?</p>
        <textarea
          v-model="form.happiness_triggers"
          rows="3"
          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          placeholder="Mijoz minnatchiligi, maqsadga erishish, yangi o'rganish..."
        ></textarea>
      </div>
    </div>

    <!-- Progress indicator -->
    <div class="p-4 bg-indigo-50 rounded-xl">
      <div class="flex justify-between text-sm mb-2">
        <span class="font-medium text-indigo-900">Savollar to'ldirildi</span>
        <span class="font-bold text-indigo-600">{{ answeredCount }}/9</span>
      </div>
      <div class="h-2 bg-indigo-200 rounded-full overflow-hidden">
        <div
          class="h-full bg-indigo-600 rounded-full transition-all"
          :style="{ width: `${(answeredCount / 9) * 100}%` }"
        ></div>
      </div>
    </div>

    <!-- Submit -->
    <div class="flex justify-end gap-3 pt-4">
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
      >
        Bekor qilish
      </button>
      <button
        type="submit"
        :disabled="loading"
        class="px-6 py-3 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
      >
        <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Saqlash
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';

const store = useOnboardingStore();

const props = defineProps({
  dreamBuyer: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['submit', 'cancel']);

const loading = ref(false);

const form = reactive({
  name: props.dreamBuyer?.name || '',
  age_range: props.dreamBuyer?.age_range || '',
  gender: props.dreamBuyer?.gender || 'all',
  location: props.dreamBuyer?.location || '',
  income_level: props.dreamBuyer?.income_level || '',
  occupation: props.dreamBuyer?.occupation || '',
  where_spend_time: props.dreamBuyer?.where_spend_time || '',
  info_sources: props.dreamBuyer?.info_sources || '',
  frustrations: props.dreamBuyer?.frustrations || '',
  dreams: props.dreamBuyer?.dreams || '',
  fears: props.dreamBuyer?.fears || '',
  communication_preferences: props.dreamBuyer?.communication_preferences || '',
  language_style: props.dreamBuyer?.language_style || '',
  daily_routine: props.dreamBuyer?.daily_routine || '',
  happiness_triggers: props.dreamBuyer?.happiness_triggers || ''
});

const initials = computed(() => {
  if (!form.name) return 'DB';
  return form.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
});

const answeredCount = computed(() => {
  const questions = [
    'where_spend_time',
    'info_sources',
    'frustrations',
    'dreams',
    'fears',
    'communication_preferences',
    'language_style',
    'daily_routine',
    'happiness_triggers'
  ];
  return questions.filter(q => form[q]?.trim()).length;
});

async function handleSubmit() {
  loading.value = true;

  try {
    await store.updateDreamBuyer(form);
    emit('submit');
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
}
</script>
