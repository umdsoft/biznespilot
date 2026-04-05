<template>
  <Head :title="`${posting.title} — ${posting.business_name}`" />
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-2xl mx-auto px-4 py-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <span class="text-sm font-bold text-blue-700 dark:text-blue-400">{{ posting.business_name?.charAt(0) }}</span>
          </div>
          <span class="text-sm text-gray-500 dark:text-gray-400">{{ posting.business_name }}</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ posting.title }}</h1>
        <div class="flex flex-wrap items-center gap-3 mt-3 text-sm text-gray-500 dark:text-gray-400">
          <span v-if="posting.department" class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" /></svg>
            {{ posting.department }}
          </span>
          <span v-if="posting.location" class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            {{ posting.location }}
          </span>
          <span v-if="posting.salary_min || posting.salary_max" class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
            {{ posting.salary_min ? Number(posting.salary_min).toLocaleString() : '' }}{{ posting.salary_min && posting.salary_max ? ' — ' : '' }}{{ posting.salary_max ? Number(posting.salary_max).toLocaleString() : '' }} so'm
          </span>
        </div>
      </div>
    </div>

    <div class="max-w-2xl mx-auto px-4 py-8">
      <!-- Success -->
      <div v-if="$page.props.flash?.success" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded-xl p-6 text-center mb-6">
        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
          <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        </div>
        <h3 class="text-lg font-semibold text-emerald-800 dark:text-emerald-300 mb-1">Ariza qabul qilindi!</h3>
        <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ $page.props.flash.success }}</p>
      </div>

      <!-- Error -->
      <div v-if="$page.props.flash?.error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl p-4 mb-6">
        <p class="text-sm text-red-700 dark:text-red-400">{{ $page.props.flash.error }}</p>
      </div>

      <!-- Description -->
      <div v-if="posting.description && !$page.props.flash?.success" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Lavozim haqida</h2>
        <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed whitespace-pre-line">{{ posting.description }}</div>
      </div>

      <!-- Requirements -->
      <div v-if="posting.requirements && !$page.props.flash?.success" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Talablar</h2>
        <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed whitespace-pre-line">{{ posting.requirements }}</div>
      </div>

      <!-- Application Form -->
      <div v-if="!$page.props.flash?.success" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Ariza topshirish</h2>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Quyidagi ma'lumotlarni to'ldiring</p>
        </div>
        <form @submit.prevent="submitForm" class="p-5 space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ism-familiya *</label>
            <input v-model="form.candidate_name" type="text" required class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="To'liq ismingiz" />
          </div>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon *</label>
              <input v-model="form.candidate_phone" type="tel" required class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="+998 90 123 45 67" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
              <input v-model="form.candidate_email" type="email" class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="email@example.com" />
            </div>
          </div>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hozirgi ish joyi</label>
              <input v-model="form.current_company" type="text" class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Kompaniya nomi" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tajriba (yil)</label>
              <input v-model="form.years_of_experience" type="number" min="0" max="50" class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="3" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kutilayotgan ish haqi (so'm)</label>
            <input v-model="form.expected_salary" type="number" min="0" class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="5000000" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">LinkedIn profil</label>
            <input v-model="form.linkedin_url" type="url" class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://linkedin.com/in/..." />
          </div>

          <!-- Custom form fields -->
          <div v-for="(field, fi) in (posting.form_fields || [])" :key="fi">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ field.label }} <span v-if="field.required" class="text-red-500">*</span>
            </label>
            <input v-if="field.type === 'text'" v-model="form.custom_answers[fi]" type="text" :required="field.required" class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            <textarea v-else-if="field.type === 'textarea'" v-model="form.custom_answers[fi]" rows="3" :required="field.required" class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
            <select v-else-if="field.type === 'select'" v-model="form.custom_answers[fi]" :required="field.required" class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="">Tanlang</option>
              <option v-for="opt in (field.options || [])" :key="opt" :value="opt">{{ opt }}</option>
            </select>
          </div>

          <!-- Cover letter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Qo'shimcha ma'lumot</label>
            <textarea v-model="form.cover_letter" rows="4" class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none" placeholder="O'zingiz haqingizda qisqacha yozing..."></textarea>
          </div>

          <button type="submit" :disabled="form.processing" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50">
            {{ form.processing ? 'Yuborilmoqda...' : 'Ariza yuborish' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({ posting: Object });

const form = useForm({
  candidate_name: '',
  candidate_phone: '',
  candidate_email: '',
  current_company: '',
  years_of_experience: null,
  expected_salary: null,
  linkedin_url: '',
  cover_letter: '',
  custom_answers: reactive((props.posting.form_fields || []).map(() => '')),
});

const submitForm = () => {
  form.post(`/vacancy/${props.posting.slug || props.posting.id}/apply`, {
    preserveScroll: true,
  });
};
</script>
