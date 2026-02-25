<template>
  <Head title="Promo kodlar" />
  <BusinessLayout title="Promo kodlar">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Promo kodlar</h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Chegirma kodlarini boshqaring
          </p>
        </div>
        <button
          @click="openModal(null)"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
        >
          <PlusIcon class="w-4 h-4" />
          Yangi promo kod
        </button>
      </div>

      <!-- Promo Codes Table -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div v-if="promoCodes && promoCodes.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50">
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Kod</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Turi</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Qiymat</th>
                <th class="px-5 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Foydalanish</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Muddat</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Holat</th>
                <th class="px-5 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Amallar</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
              <tr
                v-for="promo in promoCodes"
                :key="promo.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
              >
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-1 bg-slate-100 dark:bg-slate-700 rounded-md font-mono text-sm font-bold text-slate-900 dark:text-white">
                    {{ promo.code }}
                  </span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm text-slate-600 dark:text-slate-400">
                    {{ promo.type === 'fixed' ? 'Belgilangan summa' : 'Foiz' }}
                  </span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm font-semibold text-slate-900 dark:text-white">
                    {{ promo.type === 'fixed' ? formatPrice(promo.value) : promo.value + '%' }}
                  </span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-center">
                  <div class="text-sm">
                    <span class="font-semibold text-slate-900 dark:text-white">{{ promo.used_count || 0 }}</span>
                    <span class="text-slate-400"> / </span>
                    <span class="text-slate-600 dark:text-slate-400">{{ promo.max_uses || 'cheksiz' }}</span>
                  </div>
                  <!-- Usage bar -->
                  <div v-if="promo.max_uses" class="w-20 mx-auto mt-1 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all"
                      :class="getUsageBarColor(promo)"
                      :style="{ width: Math.min(((promo.used_count || 0) / promo.max_uses) * 100, 100) + '%' }"
                    ></div>
                  </div>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <div class="text-sm text-slate-600 dark:text-slate-400">
                    <p v-if="promo.starts_at">{{ formatDate(promo.starts_at) }}</p>
                    <p v-if="promo.expires_at" class="text-xs" :class="isExpired(promo.expires_at) ? 'text-red-500' : 'text-slate-400'">
                      {{ isExpired(promo.expires_at) ? 'Muddati tugagan: ' : 'gacha: ' }}{{ formatDate(promo.expires_at) }}
                    </p>
                    <p v-if="!promo.starts_at && !promo.expires_at" class="text-slate-400">Muddatsiz</p>
                  </div>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <button
                    @click="toggleActive(promo)"
                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                    :class="promo.is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"
                  >
                    <span
                      class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow-sm"
                      :class="promo.is_active ? 'translate-x-4' : 'translate-x-0.5'"
                    />
                  </button>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="openModal(promo)"
                      class="p-1.5 text-slate-400 hover:text-blue-600 transition-colors"
                      title="Tahrirlash"
                    >
                      <PencilIcon class="w-4 h-4" />
                    </button>
                    <button
                      @click="confirmDelete(promo)"
                      class="p-1.5 text-slate-400 hover:text-red-600 transition-colors"
                      title="O'chirish"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-16">
          <TicketIcon class="w-16 h-16 mx-auto mb-4 text-slate-300 dark:text-slate-600" />
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Promo kodlar yo'q</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Birinchi promo kodingizni yarating</p>
          <button
            @click="openModal(null)"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
          >
            <PlusIcon class="w-4 h-4" />
            Promo kod yaratish
          </button>
        </div>
      </div>

      <!-- Promo codes count -->
      <p v-if="promoCodes && promoCodes.length > 0" class="text-sm text-slate-500 dark:text-slate-400">
        Jami: {{ promoCodes.length }} ta promo kod
      </p>

      <!-- Add/Edit Modal -->
      <Modal v-model="showModal" :title="editingPromo ? 'Promo kodni tahrirlash' : 'Yangi promo kod'" max-width="lg">
        <form @submit.prevent="savePromo" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Promo kod *</label>
            <div class="flex gap-2">
              <input
                v-model="promoForm.code"
                type="text"
                placeholder="SPRING2026"
                class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors font-mono uppercase"
              />
              <button
                type="button"
                @click="generateCode"
                class="px-3 py-2.5 text-sm font-medium text-emerald-600 hover:text-emerald-700 border border-emerald-300 dark:border-emerald-700 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors whitespace-nowrap"
              >
                Generatsiya
              </button>
            </div>
            <p v-if="promoForm.errors.code" class="mt-1 text-sm text-red-500">{{ promoForm.errors.code }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Turi *</label>
              <select
                v-model="promoForm.type"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              >
                <option value="fixed">Belgilangan summa</option>
                <option value="percent">Foiz</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Qiymat * {{ promoForm.type === 'percent' ? '(%)' : "(so'm)" }}
              </label>
              <input
                v-model.number="promoForm.value"
                type="number"
                min="0"
                :max="promoForm.type === 'percent' ? 100 : undefined"
                step="1"
                placeholder="0"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
              <p v-if="promoForm.errors.value" class="mt-1 text-sm text-red-500">{{ promoForm.errors.value }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Min. buyurtma summasi (so'm)</label>
              <input
                v-model.number="promoForm.min_order_amount"
                type="number"
                min="0"
                step="1000"
                placeholder="0"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Max. foydalanish soni</label>
              <input
                v-model.number="promoForm.max_uses"
                type="number"
                min="0"
                placeholder="Cheksiz"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Boshlanish sanasi</label>
              <input
                v-model="promoForm.starts_at"
                type="date"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tugash sanasi</label>
              <input
                v-model="promoForm.expires_at"
                type="date"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
            >
              Bekor qilish
            </button>
            <button
              type="submit"
              :disabled="promoForm.processing"
              class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              <svg v-if="promoForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ editingPromo ? 'Saqlash' : 'Yaratish' }}
            </button>
          </div>
        </form>
      </Modal>

      <!-- Delete Confirmation Modal -->
      <Modal v-model="showDeleteModal" title="Promo kodni o'chirish" max-width="md">
        <div class="space-y-4">
          <p class="text-sm text-slate-600 dark:text-slate-400">
            <span class="font-mono font-bold text-slate-900 dark:text-white">{{ deletingPromo?.code }}</span>
            promo kodini o'chirishni xohlaysizmi?
          </p>
          <div class="flex items-center justify-end gap-3">
            <button
              @click="showDeleteModal = false"
              class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
            >
              Bekor qilish
            </button>
            <button
              @click="deletePromo"
              :disabled="deleting"
              class="inline-flex items-center gap-2 px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              O'chirish
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Modal from '@/components/Modal.vue';
import {
  PlusIcon,
  PencilIcon,
  TrashIcon,
  TicketIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  promoCodes: { type: Array, default: () => [] },
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingPromo = ref(null);
const deletingPromo = ref(null);
const deleting = ref(false);

const promoForm = useForm({
  code: '',
  type: 'fixed',
  value: null,
  min_order_amount: null,
  max_uses: null,
  starts_at: '',
  expires_at: '',
});

const formatPrice = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' });
};

const isExpired = (dateString) => {
  if (!dateString) return false;
  return new Date(dateString) < new Date();
};

const getUsageBarColor = (promo) => {
  const ratio = (promo.used_count || 0) / (promo.max_uses || 1);
  if (ratio >= 0.9) return 'bg-red-500';
  if (ratio >= 0.7) return 'bg-amber-500';
  return 'bg-emerald-500';
};

const generateCode = () => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let code = '';
  for (let i = 0; i < 8; i++) {
    code += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  promoForm.code = code;
};

const openModal = (promo) => {
  editingPromo.value = promo;
  if (promo) {
    promoForm.code = promo.code;
    promoForm.type = promo.type;
    promoForm.value = promo.value;
    promoForm.min_order_amount = promo.min_order_amount;
    promoForm.max_uses = promo.max_uses;
    promoForm.starts_at = promo.starts_at ? promo.starts_at.substring(0, 10) : '';
    promoForm.expires_at = promo.expires_at ? promo.expires_at.substring(0, 10) : '';
  } else {
    promoForm.reset();
    promoForm.type = 'fixed';
  }
  showModal.value = true;
};

const savePromo = () => {
  if (editingPromo.value) {
    promoForm.put(route('business.store.promo-codes.update', editingPromo.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    promoForm.post(route('business.store.promo-codes.store'), {
      preserveScroll: true,
      onSuccess: () => {
        showModal.value = false;
      },
    });
  }
};

const toggleActive = (promo) => {
  router.post(route('business.store.promo-codes.toggle', promo.id), {}, {
    preserveScroll: true,
    preserveState: true,
  });
};

const confirmDelete = (promo) => {
  deletingPromo.value = promo;
  showDeleteModal.value = true;
};

const deletePromo = () => {
  if (!deletingPromo.value) return;
  deleting.value = true;
  router.delete(route('business.store.promo-codes.destroy', deletingPromo.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      deletingPromo.value = null;
    },
    onFinish: () => {
      deleting.value = false;
    },
  });
};
</script>
