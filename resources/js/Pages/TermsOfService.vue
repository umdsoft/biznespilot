<template>
  <LandingLayout v-slot="{ urgencyBarVisible }">
    <Head :title="t.meta_title" />

    <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-50">
      <!-- Hero Header -->
      <section
        class="relative pt-32 pb-16 overflow-hidden"
        :class="urgencyBarVisible ? 'pt-44' : 'pt-32'"
      >
        <div class="absolute inset-0">
          <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-gradient-to-b from-violet-100/60 to-transparent rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl mb-6 shadow-lg shadow-violet-500/25">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 mb-4">{{ t.page_title }}</h1>
          <p class="text-slate-500 text-lg">{{ t.last_updated }} {{ formattedDate }}</p>
        </div>
      </section>

      <!-- Content -->
      <section class="pb-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8 md:p-12 space-y-10">

            <!-- 1. Kirish / Введение -->
            <div>
              <SectionHeading number="1" :title="t.s1.title" />
              <p class="text-slate-700 leading-relaxed mb-4">
                {{ t.s1.paragraph }}
              </p>
              <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
                <p class="text-amber-800 text-sm">
                  <strong>{{ t.s1.warning_label }}</strong> {{ t.s1.warning_text }}
                </p>
              </div>
            </div>

            <!-- 2. Biznes Operatsion Tizimi / Бизнес Операционная Система -->
            <div>
              <SectionHeading number="2" :title="t.s2.title" />
              <p class="text-slate-700 mb-5">
                {{ t.s2.paragraph }}
              </p>

              <div class="grid sm:grid-cols-2 gap-4">
                <ModuleCard
                  :title="t.s2.modules[0].title"
                  :description="t.s2.modules[0].description"
                  color="blue"
                  icon="chart"
                />
                <ModuleCard
                  :title="t.s2.modules[1].title"
                  :description="t.s2.modules[1].description"
                  color="emerald"
                  icon="currency"
                />
                <ModuleCard
                  :title="t.s2.modules[2].title"
                  :description="t.s2.modules[2].description"
                  color="violet"
                  icon="users"
                />
                <ModuleCard
                  :title="t.s2.modules[3].title"
                  :description="t.s2.modules[3].description"
                  color="amber"
                  icon="calculator"
                />
              </div>

              <div class="mt-4 bg-gradient-to-r from-indigo-50 to-violet-50 rounded-2xl p-5 border border-indigo-100">
                <div class="flex items-start">
                  <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                  </div>
                  <div>
                    <h4 class="font-semibold text-slate-900 mb-1">{{ t.s2.ai_title }}</h4>
                    <p class="text-sm text-slate-600">{{ t.s2.ai_text }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- 3. Akkaunt va ro'yxatdan o'tish / Аккаунт и регистрация -->
            <div>
              <SectionHeading number="3" :title="t.s3.title" />
              <div class="space-y-4">
                <div class="bg-slate-50 rounded-2xl p-6">
                  <h3 class="font-semibold text-slate-900 mb-3">{{ t.s3.requirements_heading }}</h3>
                  <ul class="space-y-2 text-slate-700">
                    <li v-for="req in requirements" :key="req" class="flex items-start">
                      <span class="w-2 h-2 bg-violet-500 rounded-full mr-3 mt-2 flex-shrink-0"></span>
                      {{ req }}
                    </li>
                  </ul>
                </div>
                <div class="bg-slate-50 rounded-2xl p-6">
                  <h3 class="font-semibold text-slate-900 mb-3">{{ t.s3.responsibilities_heading }}</h3>
                  <ul class="space-y-2 text-slate-700">
                    <li v-for="resp in responsibilities" :key="resp" class="flex items-start">
                      <span class="w-2 h-2 bg-red-500 rounded-full mr-3 mt-2 flex-shrink-0"></span>
                      {{ resp }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- 4. Foydalanuvchi majburiyatlari / Обязательства пользователя -->
            <div>
              <SectionHeading number="4" :title="t.s4.title" />
              <p class="text-slate-700 mb-4">{{ t.s4.paragraph }}</p>
              <div class="bg-red-50 rounded-2xl p-6 border border-red-100">
                <div class="grid sm:grid-cols-2 gap-3">
                  <div v-for="ob in obligations" :key="ob" class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 text-sm">{{ ob }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- 5. Intellektual mulk / Интеллектуальная собственность -->
            <div>
              <SectionHeading number="5" :title="t.s5.title" />
              <div class="space-y-4 text-slate-700">
                <p>
                  <strong>{{ t.s5.p1_label }}</strong> {{ t.s5.p1_text }}
                </p>
                <p>
                  <strong>{{ t.s5.p2_label }}</strong> {{ t.s5.p2_text }}
                </p>
              </div>
            </div>

            <!-- 6. Xizmat mavjudligi / Доступность сервиса -->
            <div>
              <SectionHeading number="6" :title="t.s6.title" />
              <p class="text-slate-700 mb-4">
                {{ t.s6.paragraph }}
              </p>
              <div class="grid sm:grid-cols-2 gap-3">
                <div v-for="reason in availabilityReasons" :key="reason" class="flex items-center p-3 bg-amber-50 rounded-xl border border-amber-100">
                  <svg class="w-5 h-5 text-amber-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                  </svg>
                  <span class="text-amber-800 text-sm">{{ reason }}</span>
                </div>
              </div>
              <p class="text-slate-500 text-sm mt-4">{{ t.s6.notice }}</p>
            </div>

            <!-- 7. Javobgarlikni cheklash / Ограничение ответственности -->
            <div>
              <SectionHeading number="7" :title="t.s7.title" />
              <div class="bg-slate-50 rounded-2xl p-6 space-y-4">
                <p class="text-slate-700"><strong>{{ t.s7.p1_label }}</strong> {{ t.s7.p1_text }}</p>
                <ul class="space-y-2 text-slate-700 ml-4">
                  <li v-for="l in liabilities" :key="l">&#8226; {{ l }}</li>
                </ul>
                <p class="text-slate-700">
                  <strong>{{ t.s7.p2_label }}</strong> {{ t.s7.p2_text }}
                </p>
              </div>
            </div>

            <!-- 8. Shartlarni bekor qilish / Расторжение -->
            <div>
              <SectionHeading number="8" :title="t.s8.title" />
              <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-emerald-50 rounded-2xl p-5 border border-emerald-100">
                  <h4 class="font-semibold text-emerald-800 mb-2">{{ t.s8.your_right_title }}</h4>
                  <p class="text-emerald-700 text-sm">{{ t.s8.your_right_text }}</p>
                </div>
                <div class="bg-red-50 rounded-2xl p-5 border border-red-100">
                  <h4 class="font-semibold text-red-800 mb-2">{{ t.s8.our_right_title }}</h4>
                  <p class="text-red-700 text-sm mb-2">{{ t.s8.our_right_text }}</p>
                  <ul class="text-red-700 text-sm space-y-1">
                    <li v-for="r in terminationReasons" :key="r">&#8226; {{ r }}</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- 9. Nizolarni hal qilish / Разрешение споров -->
            <div>
              <SectionHeading number="9" :title="t.s9.title" />
              <div class="space-y-3 text-slate-700">
                <p><strong>{{ t.s9.p1_label }}</strong> {{ t.s9.p1_text }}</p>
                <p><strong>{{ t.s9.p2_label }}</strong> {{ t.s9.p2_text }}</p>
                <p><strong>{{ t.s9.p3_label }}</strong> {{ t.s9.p3_text }}</p>
              </div>
            </div>

            <!-- 10. Bog'lanish / Контакты -->
            <div>
              <SectionHeading number="10" :title="t.s10.title" />
              <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-2xl p-6 border border-violet-100">
                <p class="text-slate-700 mb-5">{{ t.s10.intro }}</p>
                <div class="space-y-3">
                  <ContactItem icon="company" :label="t.s10.company_label" :value="t.s10.company_value" />
                  <ContactItem icon="email" :label="t.s10.email_label" :value="t.s10.email_value" />
                  <ContactItem icon="phone" :label="t.s10.phone_label" :value="t.s10.phone_value" />
                  <ContactItem icon="clock" :label="t.s10.hours_label" :value="t.s10.hours_value" />
                </div>
              </div>
            </div>

            <!-- Final Note -->
            <div class="bg-slate-100 rounded-2xl p-5 text-center">
              <p class="text-slate-600 text-sm">
                {{ t.final_note }}
              </p>
            </div>

          </div>

          <!-- Back to Home -->
          <div class="text-center mt-10">
            <Link href="/" class="inline-flex items-center text-violet-600 hover:text-violet-700 font-medium transition-colors group">
              <svg class="w-5 h-5 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
              {{ t.back_home }}
            </Link>
          </div>
        </div>
      </section>
    </div>
  </LandingLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import LandingLayout from '@/layouts/LandingLayout.vue'
import { useLandingLocale } from '@/i18n/landing/locale'
import translations from '@/i18n/landing/terms'

const { locale, t } = useLandingLocale(translations)

const formattedDate = computed(() => {
  const now = new Date()
  return `${String(now.getDate()).padStart(2, '0')}.${String(now.getMonth() + 1).padStart(2, '0')}.${now.getFullYear()}`
})

const requirements = computed(() => t.value.s3.requirements)
const responsibilities = computed(() => t.value.s3.responsibilities)
const obligations = computed(() => t.value.s4.obligations)
const availabilityReasons = computed(() => t.value.s6.reasons)
const liabilities = computed(() => t.value.s7.liabilities)
const terminationReasons = computed(() => t.value.s8.termination_reasons)

/* ---- Inline sub-components ---- */

const SectionHeading = {
  props: ['number', 'title'],
  template: `
    <h2 class="text-2xl font-bold text-slate-900 mb-5 flex items-center">
      <span class="w-9 h-9 bg-violet-100 text-violet-600 rounded-xl flex items-center justify-center text-sm font-bold mr-3">{{ number }}</span>
      {{ title }}
    </h2>
  `,
}

const moduleIcons = {
  chart: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>',
  currency: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
  users: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
  calculator: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
}

const ModuleCard = {
  props: ['title', 'description', 'color', 'icon'],
  setup(props) {
    return { iconPath: moduleIcons[props.icon] }
  },
  template: `
    <div class="rounded-2xl p-5 border"
      :class="{
        'bg-gradient-to-br from-blue-50 to-indigo-50 border-blue-100': color === 'blue',
        'bg-gradient-to-br from-emerald-50 to-green-50 border-emerald-100': color === 'emerald',
        'bg-gradient-to-br from-violet-50 to-purple-50 border-violet-100': color === 'violet',
        'bg-gradient-to-br from-amber-50 to-yellow-50 border-amber-100': color === 'amber'
      }">
      <div class="flex items-center mb-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3"
          :class="{
            'bg-blue-100': color === 'blue',
            'bg-emerald-100': color === 'emerald',
            'bg-violet-100': color === 'violet',
            'bg-amber-100': color === 'amber'
          }">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            :class="{
              'text-blue-600': color === 'blue',
              'text-emerald-600': color === 'emerald',
              'text-violet-600': color === 'violet',
              'text-amber-600': color === 'amber'
            }" v-html="iconPath"></svg>
        </div>
        <h4 class="font-semibold text-slate-900">{{ title }}</h4>
      </div>
      <p class="text-sm text-slate-600">{{ description }}</p>
    </div>
  `,
}

const contactIcons = {
  company: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
  email: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
  phone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>',
  clock: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
}

const ContactItem = {
  props: ['icon', 'label', 'value'],
  setup(props) {
    return { iconPath: contactIcons[props.icon] }
  },
  template: `
    <p class="flex items-center text-slate-800">
      <svg class="w-5 h-5 text-violet-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="iconPath"></svg>
      <strong>{{ label }}</strong>&nbsp; {{ value }}
    </p>
  `,
}
</script>
