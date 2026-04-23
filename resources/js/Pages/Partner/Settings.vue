<template>
  <AppLayout title="Partner sozlamalari">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Sozlamalar</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Shaxsiy ma'lumotlar va to'lov usullari</p>
      </div>


      <form @submit.prevent="submit" class="space-y-6">
        <!-- Personal info -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center gap-2 mb-4">
            <UserIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
            <h3 class="font-semibold text-gray-900 dark:text-white">Shaxsiy ma'lumotlar</h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Ism familiya *</label>
              <input v-model="form.full_name" type="text" required class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
              <p v-if="form.errors.full_name" class="text-xs text-red-500 mt-1">{{ form.errors.full_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telefon</label>
              <input v-model="form.phone" type="tel" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
              <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1">{{ form.errors.phone }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telegram</label>
              <input v-model="form.telegram_id" type="text" placeholder="@username" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Partner kodingiz</label>
              <input :value="partner.code" readonly class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-mono text-gray-600 dark:text-gray-300" />
            </div>
          </div>
        </div>

        <!-- Company (optional) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center gap-2 mb-4">
            <BuildingOfficeIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
            <h3 class="font-semibold text-gray-900 dark:text-white">Kompaniya (ixtiyoriy)</h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kompaniya nomi</label>
              <input v-model="form.company_name" type="text" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">INN/STIR</label>
              <input v-model="form.inn_stir" type="text" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
            </div>
          </div>
        </div>

        <!-- Bank -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center gap-2 mb-4">
            <BanknotesIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
            <h3 class="font-semibold text-gray-900 dark:text-white">Bank ma'lumotlari</h3>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Payoutlarni olish uchun to'ldiring.</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bank nomi</label>
              <input v-model="form.bank_name" type="text" placeholder="Hamkorbank" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Hisob raqami</label>
              <input v-model="form.bank_account" type="text" class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white font-mono" />
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">To'lov usuli *</label>
              <select v-model="form.preferred_payout_method" required class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white">
                <option value="bank_transfer">Bank o'tkazmasi</option>
                <option value="humo">Humo karta</option>
                <option value="uzcard">Uzcard</option>
                <option value="payme">Payme</option>
                <option value="click">Click</option>
                <option value="cash">Naqd</option>
              </select>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-semibold rounded-lg transition-colors"
          >
            {{ form.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/PartnerLayout.vue';
import { UserIcon, BuildingOfficeIcon, BanknotesIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  partner: { type: Object, required: true },
});

const form = useForm({
  full_name: props.partner.full_name || '',
  phone: props.partner.phone || '',
  telegram_id: props.partner.telegram_id || '',
  company_name: props.partner.company_name || '',
  inn_stir: props.partner.inn_stir || '',
  bank_name: props.partner.bank_name || '',
  bank_account: props.partner.bank_account || '',
  preferred_payout_method: props.partner.preferred_payout_method || 'bank_transfer',
});

const submit = () => {
  form.put(route('partner.settings.update'), { preserveScroll: true });
};
</script>
