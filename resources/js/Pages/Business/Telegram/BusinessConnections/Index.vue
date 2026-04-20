<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps({
  bot: { type: Object, required: true },
  connections: { type: Array, default: () => [] },
  aiModes: { type: Object, default: () => ({}) },
});

const { confirm } = useConfirm();

const editingConnection = ref(null);
const saving = ref(false);

const form = ref({
  ai_auto_reply: true,
  ai_mode: 'hybrid',
  persona_prompt: '',
  settings: {
    working_hours: { enabled: false, start: '09:00', end: '18:00' },
    away_message: '',
    welcome_message: '',
  },
});

const stats = computed(() => ({
  total: props.connections.length,
  active: props.connections.filter(c => c.is_active).length,
  ai_auto: props.connections.filter(c => c.ai_auto_reply).length,
  conversations: props.connections.reduce((sum, c) => sum + (c.conversations_count || 0), 0),
}));

function openEdit(connection) {
  editingConnection.value = connection;
  form.value = {
    ai_auto_reply: connection.ai_auto_reply,
    ai_mode: connection.ai_mode,
    persona_prompt: connection.persona_prompt || '',
    settings: {
      working_hours: connection.settings?.working_hours || { enabled: false, start: '09:00', end: '18:00' },
      away_message: connection.settings?.away_message || '',
      welcome_message: connection.settings?.welcome_message || '',
    },
  };
}

async function saveSettings() {
  if (!editingConnection.value) return;
  saving.value = true;
  try {
    await axios.put(
      route('business.telegram-funnels.business-connections.update', [props.bot.id, editingConnection.value.id]),
      form.value
    );
    editingConnection.value = null;
    router.reload({ only: ['connections'] });
  } catch (e) {
    alert('Saqlashda xatolik: ' + (e.response?.data?.message || e.message));
  } finally {
    saving.value = false;
  }
}

async function toggleEnabled(connection) {
  const action = connection.is_enabled ? "to'xtatish" : 'yoqish';
  if (!await confirm({
    title: `AI ni ${action}`,
    message: `AI javoblarini ${action}ni xohlaysizmi?`,
    type: connection.is_enabled ? 'warning' : 'info',
    confirmText: action,
  })) return;

  try {
    await axios.post(route('business.telegram-funnels.business-connections.toggle-enabled', [props.bot.id, connection.id]));
    router.reload({ only: ['connections'] });
  } catch (e) {
    alert('Xatolik: ' + (e.response?.data?.message || e.message));
  }
}

async function deleteConnection(connection) {
  if (!await confirm({
    title: "Ulanishni o'chirish",
    message: "Bu yozuv o'chiriladi. Telegram akkauntdan to'liq o'chirish uchun Telegram ichidan botni o'chiring.",
    type: 'danger',
    confirmText: "O'chirish",
  })) return;

  try {
    await axios.delete(route('business.telegram-funnels.business-connections.destroy', [props.bot.id, connection.id]));
    router.reload({ only: ['connections'] });
  } catch (e) {
    alert('Xatolik: ' + (e.response?.data?.message || e.message));
  }
}
</script>

<template>
  <BusinessLayout title="Business Bot — Premium akkaunt ulanishlari">
    <Head title="Business Connections" />

    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-3">
        <Link :href="route('business.telegram-funnels.show', bot.id)" class="text-slate-400 hover:text-slate-600">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </Link>
        <div class="flex-1">
          <h1 class="text-xl font-bold text-slate-900 dark:text-white">Business Bot — Premium ulanishlar</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400">@{{ bot.username }} — AI mijozlaringiz bilan sizning nomingizdan gaplashadi</p>
        </div>
      </div>

      <!-- Info Banner -->
      <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-700 rounded-xl p-5">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-indigo-900 dark:text-indigo-100 mb-2">Qanday ishlaydi?</h3>
            <ol class="text-sm text-indigo-800 dark:text-indigo-200 space-y-1 list-decimal list-inside">
              <li>Telegram Premium bo'ling (biznes egasi)</li>
              <li>Telegram → Sozlamalar → Telegram Biznes uchun → Chat-botlar</li>
              <li>Bot username kiriting: <code class="bg-indigo-100 dark:bg-indigo-800 px-1.5 py-0.5 rounded">@{{ bot.username }}</code></li>
              <li>AI mijozlaringizga sizning nomingizdan javob beradi</li>
            </ol>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
          <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total }}</div>
          <div class="text-xs text-slate-500">Jami ulanishlar</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
          <div class="text-2xl font-bold text-emerald-600">{{ stats.active }}</div>
          <div class="text-xs text-slate-500">Faol</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
          <div class="text-2xl font-bold text-indigo-600">{{ stats.ai_auto }}</div>
          <div class="text-xs text-slate-500">AI avtomatik</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
          <div class="text-2xl font-bold text-amber-600">{{ stats.conversations }}</div>
          <div class="text-xs text-slate-500">Suhbatlar</div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="connections.length === 0" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
          <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Hali ulanishlar yo'q</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
          Telegram Premium foydalanuvchilari bu botni biznes akkauntlariga ulaganda ular shu yerda ko'rinadi.
        </p>
      </div>

      <!-- Connections List -->
      <div v-else class="space-y-3">
        <div
          v-for="connection in connections"
          :key="connection.id"
          class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3 flex-1">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold">
                {{ connection.owner_name?.charAt(0) || '?' }}
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <h3 class="font-semibold text-slate-900 dark:text-white">{{ connection.owner_name }}</h3>
                  <span v-if="connection.owner_username" class="text-xs text-slate-500">@{{ connection.owner_username }}</span>
                  <span
                    v-if="connection.is_active"
                    class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400"
                  >
                    <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                    Faol
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400"
                  >
                    Nofaol
                  </span>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                  <span>Ulandi: {{ connection.connected_at }}</span>
                  <span v-if="connection.last_activity_at">Faollik: {{ connection.last_activity_at }}</span>
                  <span>{{ connection.conversations_count }} suhbat</span>
                </div>
                <div class="mt-2 flex items-center gap-2">
                  <span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 font-medium">
                    AI: {{ connection.ai_mode_label }}
                  </span>
                  <span v-if="!connection.can_reply" class="text-[11px] text-amber-600">
                    ⚠ Ruxsat yo'q (can_reply=false)
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button
                @click="toggleEnabled(connection)"
                :class="[
                  'px-3 py-1.5 text-xs font-medium rounded-lg transition-colors',
                  connection.is_enabled
                    ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 hover:bg-amber-100'
                    : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100'
                ]"
              >
                {{ connection.is_enabled ? "To'xtatish" : 'Yoqish' }}
              </button>
              <button
                @click="openEdit(connection)"
                class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200"
              >
                Sozlash
              </button>
              <button
                @click="deleteConnection(connection)"
                class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="editingConnection" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="editingConnection = null"></div>
      <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-start justify-between mb-5">
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">Sozlamalar</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400">{{ editingConnection.owner_name }}</p>
            </div>
            <button @click="editingConnection = null" class="text-slate-400 hover:text-slate-600">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="space-y-5">
            <!-- AI Mode -->
            <div>
              <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">AI rejimi</label>
              <div class="grid grid-cols-3 gap-2">
                <label
                  v-for="(label, value) in aiModes"
                  :key="value"
                  :class="[
                    'cursor-pointer rounded-xl border-2 p-3 text-center transition-colors',
                    form.ai_mode === value
                      ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30'
                      : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'
                  ]"
                >
                  <input type="radio" v-model="form.ai_mode" :value="value" class="sr-only" />
                  <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ label }}</div>
                  <div class="text-[10px] text-slate-500 mt-1">
                    {{ value === 'auto' ? 'To\'liq avtomatik' : value === 'hybrid' ? 'Tasdiqlash bilan' : 'Qo\'lda javob' }}
                  </div>
                </label>
              </div>
            </div>

            <!-- AI Auto Reply -->
            <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg cursor-pointer">
              <input type="checkbox" v-model="form.ai_auto_reply" class="rounded text-indigo-600" />
              <div class="flex-1">
                <div class="text-sm font-semibold text-slate-900 dark:text-white">AI javoblarini yoqish</div>
                <div class="text-xs text-slate-500">Mijozlar xabarlariga AI javob beradi</div>
              </div>
            </label>

            <!-- Persona Prompt -->
            <div>
              <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                Shaxsiy ovoz (Persona)
              </label>
              <textarea
                v-model="form.persona_prompt"
                rows="4"
                placeholder="Masalan: Sen Umidbek, pitseriya egasisan. Mijozlar bilan samimiy va do'stona gaplashasan..."
                class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
              ></textarea>
              <p class="text-[11px] text-slate-500 mt-1">AI sizning uslubingizda javob beradi</p>
            </div>

            <!-- Working Hours -->
            <div>
              <label class="flex items-center gap-3 mb-3">
                <input type="checkbox" v-model="form.settings.working_hours.enabled" class="rounded text-indigo-600" />
                <span class="text-sm font-semibold text-slate-900 dark:text-white">Ish vaqtini cheklash</span>
              </label>
              <div v-if="form.settings.working_hours.enabled" class="grid grid-cols-2 gap-3 pl-7">
                <div>
                  <label class="text-xs text-slate-500">Boshlanish</label>
                  <input type="time" v-model="form.settings.working_hours.start" class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800" />
                </div>
                <div>
                  <label class="text-xs text-slate-500">Tugash</label>
                  <input type="time" v-model="form.settings.working_hours.end" class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800" />
                </div>
              </div>
            </div>

            <!-- Away Message -->
            <div>
              <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                Ish vaqtidan tashqari xabar
              </label>
              <textarea
                v-model="form.settings.away_message"
                rows="2"
                placeholder="Salom! Hozir ofisda emasmiz. Ertaga 09:00 da javob beramiz."
                class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
              ></textarea>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-slate-200 dark:border-slate-700">
            <button @click="editingConnection = null" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200">
              Bekor qilish
            </button>
            <button @click="saveSettings" :disabled="saving" class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
              {{ saving ? 'Saqlanmoqda...' : 'Saqlash' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>
