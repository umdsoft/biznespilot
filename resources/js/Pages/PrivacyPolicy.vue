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
          <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-gradient-to-b from-blue-100/60 to-transparent rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl mb-6 shadow-lg shadow-blue-500/25">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
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

            <!-- 1. Kirish -->
            <div>
              <SectionHeading number="1" :title="t.section1.title" color="blue" />
              <p class="text-slate-700 leading-relaxed mb-4">
                {{ t.section1.p1 }}
              </p>
              <p class="text-slate-700 leading-relaxed">
                {{ t.section1.p2 }}
              </p>
            </div>

            <!-- 2. To'planadigan ma'lumotlar -->
            <div>
              <SectionHeading number="2" :title="t.section2.title" color="blue" />

              <div class="space-y-6">
                <div class="bg-slate-50 rounded-2xl p-6">
                  <h3 class="font-semibold text-slate-900 mb-4">{{ t.section2.subtitle1 }}</h3>
                  <ul class="space-y-3 text-slate-700">
                    <li v-for="(item, idx) in t.section2.items1" :key="idx" class="flex items-start">
                      <CheckCircleIcon class="blue" />
                      <span><strong>{{ item.bold }}</strong> {{ item.text }}</span>
                    </li>
                  </ul>
                </div>

                <div class="bg-slate-50 rounded-2xl p-6">
                  <h3 class="font-semibold text-slate-900 mb-4">{{ t.section2.subtitle2 }}</h3>
                  <ul class="space-y-3 text-slate-700">
                    <li v-for="(item, idx) in t.section2.items2" :key="idx" class="flex items-start">
                      <CheckCircleIcon class="emerald" />
                      <span><strong>{{ item.bold }}</strong> {{ item.text }}</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- 3. Ma'lumotlardan foydalanish -->
            <div>
              <SectionHeading number="3" :title="t.section3.title" color="blue" />
              <div class="grid md:grid-cols-2 gap-4">
                <UsageCard
                  v-for="(card, idx) in t.section3.cards"
                  :key="idx"
                  :title="card.title"
                  :color="usageColors[idx]"
                  :items="card.items"
                />
              </div>
            </div>

            <!-- 4. Ma'lumotlar xavfsizligi -->
            <div>
              <SectionHeading number="4" :title="t.section4.title" color="blue" />
              <div class="grid sm:grid-cols-2 gap-4">
                <SecurityCard
                  v-for="(card, idx) in t.section4.cards"
                  :key="idx"
                  :title="card.title"
                  :description="card.description"
                  :icon="securityIconKeys[idx]"
                />
              </div>

              <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100">
                <div class="flex items-start">
                  <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                  </div>
                  <div>
                    <h4 class="font-semibold text-slate-900 mb-1">{{ t.section4.info_title }}</h4>
                    <p class="text-sm text-slate-600">{{ t.section4.info_text }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- 5. Sizning huquqlaringiz -->
            <div>
              <SectionHeading number="5" :title="t.section5.title" color="blue" />
              <div class="space-y-3">
                <RightItem v-for="(right, index) in userRights" :key="index" :number="index + 1" :title="right.title" :description="right.description" />
              </div>
            </div>

            <!-- 6. Sun'iy intellekt -->
            <div>
              <SectionHeading number="6" :title="t.section6.title" color="blue" />
              <div class="bg-gradient-to-r from-violet-50 to-purple-50 rounded-2xl p-6 border border-violet-100">
                <p class="text-slate-700 leading-relaxed mb-4">
                  {{ t.section6.intro }}
                </p>
                <ul class="space-y-3 text-slate-700">
                  <li v-for="(item, idx) in t.section6.items" :key="idx" class="flex items-start">
                    <CheckCircleIcon class="violet" />
                    <span>{{ item }}</span>
                  </li>
                </ul>
              </div>
            </div>

            <!-- 7. Bog'lanish -->
            <div>
              <SectionHeading number="7" :title="t.section7.title" color="blue" />
              <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100">
                <p class="text-slate-700 mb-5">{{ t.section7.intro }}</p>
                <div class="space-y-3">
                  <ContactItem icon="email" :label="t.section7.email_label" :value="t.section7.email" />
                  <ContactItem icon="phone" :label="t.section7.phone_label" :value="t.section7.phone" />
                  <ContactItem icon="location" :label="t.section7.address_label" :value="t.section7.address" />
                </div>
                <p class="mt-5 text-sm text-blue-700 bg-blue-100/50 rounded-xl px-4 py-2.5">
                  {{ t.section7.notice }}
                </p>
              </div>
            </div>

          </div>

          <!-- Back to Home -->
          <div class="text-center mt-10">
            <Link href="/" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium transition-colors group">
              <svg class="w-5 h-5 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
              {{ t.back_link }}
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
import translations from '@/i18n/landing/privacy-policy'

const { locale, t } = useLandingLocale(translations)

const formattedDate = computed(() => {
  const now = new Date()
  return `${String(now.getDate()).padStart(2, '0')}.${String(now.getMonth() + 1).padStart(2, '0')}.${now.getFullYear()}`
})

const userRights = computed(() => t.value.section5.rights)

const usageColors = ['blue', 'emerald', 'violet', 'amber']
const securityIconKeys = ['lock', 'shield', 'users', 'database']

/* ---- Inline sub-components ---- */

const SectionHeading = {
  props: ['number', 'title', 'color'],
  template: `
    <h2 class="text-2xl font-bold text-slate-900 mb-5 flex items-center">
      <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold mr-3"
        :class="color === 'blue' ? 'bg-blue-100 text-blue-600' : 'bg-violet-100 text-violet-600'">
        {{ number }}
      </span>
      {{ title }}
    </h2>
  `,
}

const CheckCircleIcon = {
  props: { class: { type: String, default: 'blue' } },
  template: `
    <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0"
      :class="{
        'text-blue-500': $props.class === 'blue',
        'text-emerald-500': $props.class === 'emerald',
        'text-violet-500': $props.class === 'violet'
      }"
      fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
    </svg>
  `,
}

const UsageCard = {
  props: ['title', 'color', 'items'],
  template: `
    <div class="rounded-2xl p-5 border"
      :class="{
        'bg-blue-50 border-blue-100': color === 'blue',
        'bg-emerald-50 border-emerald-100': color === 'emerald',
        'bg-violet-50 border-violet-100': color === 'violet',
        'bg-amber-50 border-amber-100': color === 'amber'
      }">
      <h4 class="font-semibold mb-2"
        :class="{
          'text-blue-800': color === 'blue',
          'text-emerald-800': color === 'emerald',
          'text-violet-800': color === 'violet',
          'text-amber-800': color === 'amber'
        }">{{ title }}</h4>
      <ul class="text-sm space-y-1"
        :class="{
          'text-blue-700': color === 'blue',
          'text-emerald-700': color === 'emerald',
          'text-violet-700': color === 'violet',
          'text-amber-700': color === 'amber'
        }">
        <li v-for="item in items" :key="item">• {{ item }}</li>
      </ul>
    </div>
  `,
}

const securityIcons = {
  lock: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
  shield: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
  users: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
  database: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>',
}

const SecurityCard = {
  props: ['title', 'description', 'icon'],
  setup(props) {
    return { iconPath: securityIcons[props.icon] }
  },
  template: `
    <div class="flex items-start p-4 bg-slate-50 rounded-2xl">
      <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="iconPath"></svg>
      </div>
      <div>
        <h4 class="font-semibold text-slate-900">{{ title }}</h4>
        <p class="text-sm text-slate-600">{{ description }}</p>
      </div>
    </div>
  `,
}

const RightItem = {
  props: ['number', 'title', 'description'],
  template: `
    <div class="flex items-start p-4 bg-slate-50 rounded-2xl hover:bg-slate-100 transition-colors">
      <span class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-sm font-bold mr-4 flex-shrink-0">{{ number }}</span>
      <div>
        <h4 class="font-semibold text-slate-900">{{ title }}</h4>
        <p class="text-sm text-slate-600">{{ description }}</p>
      </div>
    </div>
  `,
}

const contactIcons = {
  email: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
  phone: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>',
  location: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
}

const ContactItem = {
  props: ['icon', 'label', 'value'],
  setup(props) {
    return { iconPath: contactIcons[props.icon] }
  },
  template: `
    <p class="flex items-center text-slate-800">
      <svg class="w-5 h-5 text-blue-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="iconPath"></svg>
      <strong>{{ label }}</strong>&nbsp; {{ value }}
    </p>
  `,
}
</script>
