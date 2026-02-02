<template>
  <LandingLayout v-slot="{ urgencyBarVisible }">
    <Head title="Foydalanish shartlari — BiznesPilot" />

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
          <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 mb-4">Foydalanish shartlari</h1>
          <p class="text-slate-500 text-lg">Oxirgi yangilanish: {{ formattedDate }}</p>
        </div>
      </section>

      <!-- Content -->
      <section class="pb-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8 md:p-12 space-y-10">

            <!-- 1. Kirish -->
            <div>
              <SectionHeading number="1" title="Kirish" />
              <p class="text-slate-700 leading-relaxed mb-4">
                Ushbu Foydalanish shartlari (keyingi o'rinlarda "Shartlar") BiznesPilot — O'zbekistonning yagona Biznes Operatsion Tizimi
                (keyingi o'rinlarda "Platforma", "Xizmat" yoki "Biz") bilan Siz (keyingi o'rinlarda "Foydalanuvchi" yoki "Siz")
                o'rtasidagi huquqiy munosabatlarni tartibga soladi.
              </p>
              <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
                <p class="text-amber-800 text-sm">
                  <strong>Muhim:</strong> Platformadan foydalanishni boshlash orqali Siz ushbu Shartlarni to'liq o'qib chiqqaningizni va
                  ularga rozilik bildirishingizni tasdiqlaysiz.
                </p>
              </div>
            </div>

            <!-- 2. Biznes Operatsion Tizimi -->
            <div>
              <SectionHeading number="2" title="Biznes Operatsion Tizimi" />
              <p class="text-slate-700 mb-5">
                BiznesPilot — bu biznesning barcha bo'limlarini bitta tizimga birlashtiruvchi va tadbirkorlarga to'liq nazorat imkonini beruvchi platforma.
                Platforma 4 ta asosiy moduldan iborat:
              </p>

              <div class="grid sm:grid-cols-2 gap-4">
                <ModuleCard
                  title="Marketing Boshqaruvi"
                  description="Mijozlarni boshqarish, reklama, Sun'iy intellekt tahlillari"
                  color="blue"
                  icon="chart"
                />
                <ModuleCard
                  title="Sotuv va CRM"
                  description="Sotuvlarni, mijozlarni va bitimlarni real vaqtda boshqarish"
                  color="emerald"
                  icon="currency"
                />
                <ModuleCard
                  title="HR Boshqaruvi"
                  description="Xodimlar, vazifalar va ish haqini boshqarish"
                  color="violet"
                  icon="users"
                />
                <ModuleCard
                  title="Moliya Boshqaruvi"
                  description="Daromad-xarajatlar, qarzlar, to'lovlar va moliyaviy hisobotlar"
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
                    <h4 class="font-semibold text-slate-900 mb-1">Sun'iy intellekt</h4>
                    <p class="text-sm text-slate-600">Barcha modullar Sun'iy intellekt yordamida ishlaydi — u ma'lumotlarni tahlil qiladi, muammolarni oldindan topadi va biznesni to'liq nazorat qilishingiz uchun tavsiyalar beradi.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- 3. Akkaunt va ro'yxatdan o'tish -->
            <div>
              <SectionHeading number="3" title="Akkaunt va ro'yxatdan o'tish" />
              <div class="space-y-4">
                <div class="bg-slate-50 rounded-2xl p-6">
                  <h3 class="font-semibold text-slate-900 mb-3">3.1. Platformadan foydalanish uchun Siz:</h3>
                  <ul class="space-y-2 text-slate-700">
                    <li v-for="req in requirements" :key="req" class="flex items-start">
                      <span class="w-2 h-2 bg-violet-500 rounded-full mr-3 mt-2 flex-shrink-0"></span>
                      {{ req }}
                    </li>
                  </ul>
                </div>
                <div class="bg-slate-50 rounded-2xl p-6">
                  <h3 class="font-semibold text-slate-900 mb-3">3.2. Siz quyidagilarga mas'ulsiz:</h3>
                  <ul class="space-y-2 text-slate-700">
                    <li v-for="resp in responsibilities" :key="resp" class="flex items-start">
                      <span class="w-2 h-2 bg-red-500 rounded-full mr-3 mt-2 flex-shrink-0"></span>
                      {{ resp }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- 4. Foydalanuvchi majburiyatlari -->
            <div>
              <SectionHeading number="4" title="Foydalanuvchi majburiyatlari" />
              <p class="text-slate-700 mb-4">Platformadan foydalanishda Siz quyidagilarni bajarmaslikka rozilik bildirasiz:</p>
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

            <!-- 5. Intellektual mulk -->
            <div>
              <SectionHeading number="5" title="Intellektual mulk" />
              <div class="space-y-4 text-slate-700">
                <p>
                  <strong>5.1.</strong> Platforma va uning barcha tarkibiy qismlari (dasturiy ta'minot, dizayn,
                  logotiplar, Sun'iy intellekt modellari) BiznesPilot ning mutlaq mulki hisoblanadi va mualliflik huquqi bilan himoyalangan.
                </p>
                <p>
                  <strong>5.2.</strong> Siz Platformaga yuklagan ma'lumotlar ustidan to'liq huquqni saqlab qolasiz,
                  lekin bizga ularni xizmat ko'rsatish maqsadida ishlatish huquqini berasiz.
                </p>
              </div>
            </div>

            <!-- 6. Xizmat mavjudligi -->
            <div>
              <SectionHeading number="6" title="Xizmat mavjudligi" />
              <p class="text-slate-700 mb-4">
                Biz Platformaning uzluksiz ishlashini ta'minlashga harakat qilamiz, lekin quyidagi hollarda xizmat vaqtincha to'xtatilishi mumkin:
              </p>
              <div class="grid sm:grid-cols-2 gap-3">
                <div v-for="reason in availabilityReasons" :key="reason" class="flex items-center p-3 bg-amber-50 rounded-xl border border-amber-100">
                  <svg class="w-5 h-5 text-amber-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                  </svg>
                  <span class="text-amber-800 text-sm">{{ reason }}</span>
                </div>
              </div>
              <p class="text-slate-500 text-sm mt-4">Rejalashtirilgan texnik ishlar haqida kamida 24 soat oldin xabardor qilamiz.</p>
            </div>

            <!-- 7. Javobgarlikni cheklash -->
            <div>
              <SectionHeading number="7" title="Javobgarlikni cheklash" />
              <div class="bg-slate-50 rounded-2xl p-6 space-y-4">
                <p class="text-slate-700"><strong>7.1.</strong> BiznesPilot quyidagilar uchun javobgar emas:</p>
                <ul class="space-y-2 text-slate-700 ml-4">
                  <li v-for="l in liabilities" :key="l">• {{ l }}</li>
                </ul>
                <p class="text-slate-700">
                  <strong>7.2.</strong> Bizning umumiy javobgarligimiz Siz tomonidan to'langan oxirgi 12 oylik obuna summasi bilan cheklanadi.
                </p>
              </div>
            </div>

            <!-- 8. Shartlarni bekor qilish -->
            <div>
              <SectionHeading number="8" title="Shartlarni bekor qilish" />
              <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-emerald-50 rounded-2xl p-5 border border-emerald-100">
                  <h4 class="font-semibold text-emerald-800 mb-2">Sizning huquqingiz</h4>
                  <p class="text-emerald-700 text-sm">Siz istalgan vaqtda akkauntingizni o'chirishingiz mumkin.</p>
                </div>
                <div class="bg-red-50 rounded-2xl p-5 border border-red-100">
                  <h4 class="font-semibold text-red-800 mb-2">Bizning huquqimiz</h4>
                  <p class="text-red-700 text-sm mb-2">Quyidagi hollarda akkauntingizni to'xtatishimiz mumkin:</p>
                  <ul class="text-red-700 text-sm space-y-1">
                    <li v-for="r in terminationReasons" :key="r">• {{ r }}</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- 9. Nizolarni hal qilish -->
            <div>
              <SectionHeading number="9" title="Nizolarni hal qilish" />
              <div class="space-y-3 text-slate-700">
                <p><strong>9.1.</strong> Barcha nizolar avvalo muzokaralar yo'li bilan hal qilinadi.</p>
                <p><strong>9.2.</strong> Muzokaralar natija bermaganda, nizolar O'zbekiston Respublikasi qonunchiligiga muvofiq sud tartibida ko'rib chiqiladi.</p>
                <p><strong>9.3.</strong> Sud joylashuvi: Toshkent shahri.</p>
              </div>
            </div>

            <!-- 10. Bog'lanish -->
            <div>
              <SectionHeading number="10" title="Bog'lanish" />
              <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-2xl p-6 border border-violet-100">
                <p class="text-slate-700 mb-5">Savollar yoki takliflar bo'lsa, biz bilan bog'laning:</p>
                <div class="space-y-3">
                  <ContactItem icon="company" label="Kompaniya:" value="BiznesPilot" />
                  <ContactItem icon="email" label="Email:" value="support@biznespilot.uz" />
                  <ContactItem icon="phone" label="Telefon:" value="+998 50 504 86 68" />
                  <ContactItem icon="clock" label="Ish vaqti:" value="Dushanba - Juma, 09:00 - 18:00" />
                </div>
              </div>
            </div>

            <!-- Final Note -->
            <div class="bg-slate-100 rounded-2xl p-5 text-center">
              <p class="text-slate-600 text-sm">
                Ushbu Shartlar O'zbekiston Respublikasi qonunchiligiga bo'ysunadi va
                Siz va BiznesPilot o'rtasidagi to'liq kelishuvni ifodalaydi.
              </p>
            </div>

          </div>

          <!-- Back to Home -->
          <div class="text-center mt-10">
            <Link href="/" class="inline-flex items-center text-violet-600 hover:text-violet-700 font-medium transition-colors group">
              <svg class="w-5 h-5 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
              Bosh sahifaga qaytish
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

const formattedDate = computed(() => {
  const now = new Date()
  return `${String(now.getDate()).padStart(2, '0')}.${String(now.getMonth() + 1).padStart(2, '0')}.${now.getFullYear()}`
})

const requirements = [
  "Kamida 18 yoshda bo'lishingiz kerak",
  "To'g'ri va aniq ma'lumotlarni taqdim etishingiz shart",
  "Akkauntingiz xavfsizligini ta'minlashingiz lozim",
  "Parolingizni maxfiy saqlashingiz kerak",
]

const responsibilities = [
  "Akkauntingiz orqali amalga oshirilgan barcha harakatlar uchun",
  "Akkauntga ruxsatsiz kirishni oldini olish uchun",
  "Har qanday xavfsizlik buzilishi haqida bizni zudlik bilan xabardor qilish uchun",
]

const obligations = [
  "Noqonuniy yoki zararli maqsadlarda foydalanish",
  "Boshqa foydalanuvchilarning huquqlarini buzish",
  "Viruslar yoki zararli kodlarni tarqatish",
  "Platformaning normal ishlashiga xalaqit berish",
  "Ruxsatsiz ma'lumotlarga kirish",
  "Spam yoki istalmagan xabarlar yuborish",
  "Soxta ma'lumotlarni tarqatish",
  "Intellektual mulk huquqlarini buzish",
]

const availabilityReasons = [
  "Texnik xizmat ko'rsatish",
  "Tizimni yangilash",
  "Texnik nosozliklar",
  "Fors-major holatlari",
]

const liabilities = [
  "Foydalanuvchi harakatlari natijasida yuzaga kelgan zararlar",
  "Uchinchi tomon xizmatlari bilan bog'liq muammolar",
  "Internet ulanishi yoki qurilma nosozliklari",
  "Biznes qarorlari natijasida yuzaga kelgan yo'qotishlar",
]

const terminationReasons = [
  "Shartlarni buzganingizda",
  "Noqonuniy faoliyat aniqlanganda",
  "To'lovlarni amalga oshirmaganda",
]

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
