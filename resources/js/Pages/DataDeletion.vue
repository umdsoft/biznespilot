<template>
  <LandingLayout v-slot="{ urgencyBarVisible }">
    <Head title="Ma'lumotlarni o'chirish — BiznesPilot" />

    <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-50">
      <!-- Hero Header -->
      <section
        class="relative pt-32 pb-16 overflow-hidden"
        :class="urgencyBarVisible ? 'pt-44' : 'pt-32'"
      >
        <div class="absolute inset-0">
          <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-gradient-to-b from-red-100/60 to-transparent rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl mb-6 shadow-lg shadow-red-500/25">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </div>
          <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 mb-4">Ma'lumotlarni o'chirish</h1>
          <p class="text-slate-500 text-lg">Foydalanuvchi ma'lumotlarini o'chirish bo'yicha ko'rsatmalar</p>
        </div>
      </section>

      <!-- Content -->
      <section class="pb-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8 md:p-12 space-y-10">

            <!-- 1. Umumiy ma'lumot -->
            <div>
              <div class="flex items-center mb-4">
                <span class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 rounded-lg font-bold text-sm mr-3">1</span>
                <h2 class="text-xl font-bold text-slate-900">Umumiy ma'lumot</h2>
              </div>
              <p class="text-slate-700 leading-relaxed">
                BiznesPilot foydalanuvchilarning shaxsiy ma'lumotlarini himoya qilishga sodiq. Siz istalgan vaqtda o'z ma'lumotlaringizni o'chirishni so'rashingiz mumkin. Biz O'zbekiston Respublikasining "Shaxsiy ma'lumotlar to'g'risida"gi Qonuniga muvofiq ish olib boramiz.
              </p>
            </div>

            <!-- 2. Ma'lumotlarni o'chirish usullari -->
            <div>
              <div class="flex items-center mb-4">
                <span class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 rounded-lg font-bold text-sm mr-3">2</span>
                <h2 class="text-xl font-bold text-slate-900">Ma'lumotlarni o'chirish usullari</h2>
              </div>

              <div class="space-y-6">
                <!-- Usul 1 -->
                <div class="bg-slate-50 rounded-2xl p-6">
                  <h3 class="font-semibold text-slate-900 mb-3">2.1. Dastur orqali o'chirish</h3>
                  <ol class="space-y-2 text-slate-700 list-decimal list-inside">
                    <li>BiznesPilot hisobingizga kiring</li>
                    <li><strong>Sozlamalar</strong> bo'limiga o'ting</li>
                    <li><strong>"Hisobni o'chirish"</strong> tugmasini bosing</li>
                    <li>Tasdiqlash uchun parolingizni kiriting</li>
                    <li>Barcha ma'lumotlaringiz 30 kun ichida to'liq o'chiriladi</li>
                  </ol>
                </div>

                <!-- Usul 2 -->
                <div class="bg-slate-50 rounded-2xl p-6">
                  <h3 class="font-semibold text-slate-900 mb-3">2.2. Email orqali so'rov yuborish</h3>
                  <p class="text-slate-700 mb-3">
                    Quyidagi email manziliga ma'lumotlarni o'chirish so'rovini yuboring:
                  </p>
                  <a href="mailto:support@biznespilot.uz" class="inline-flex items-center px-4 py-2 bg-red-50 text-red-700 rounded-xl font-medium hover:bg-red-100 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    support@biznespilot.uz
                  </a>
                  <p class="text-slate-600 text-sm mt-3">
                    So'rovingizda ro'yxatdan o'tgan email manzilingiz va to'liq ismingizni ko'rsating.
                  </p>
                </div>
              </div>
            </div>

            <!-- 3. O'chiriladigan ma'lumotlar -->
            <div>
              <div class="flex items-center mb-4">
                <span class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 rounded-lg font-bold text-sm mr-3">3</span>
                <h2 class="text-xl font-bold text-slate-900">O'chiriladigan ma'lumotlar</h2>
              </div>
              <p class="text-slate-700 leading-relaxed mb-4">
                So'rov tasdiqlangandan so'ng quyidagi ma'lumotlar to'liq o'chiriladi:
              </p>
              <ul class="space-y-3 text-slate-700">
                <li class="flex items-start">
                  <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  <span>Shaxsiy ma'lumotlar (ism, email, telefon raqami)</span>
                </li>
                <li class="flex items-start">
                  <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  <span>Biznes ma'lumotlari va sozlamalari</span>
                </li>
                <li class="flex items-start">
                  <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  <span>Facebook/Instagram integratsiya tokenlari</span>
                </li>
                <li class="flex items-start">
                  <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  <span>Chat tarixi va chatbot sozlamalari</span>
                </li>
                <li class="flex items-start">
                  <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  <span>To'lov tarixi va tranzaksiya ma'lumotlari</span>
                </li>
                <li class="flex items-start">
                  <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  <span>Yuklangan fayllar va media kontentlar</span>
                </li>
              </ul>
            </div>

            <!-- 4. Muddat -->
            <div>
              <div class="flex items-center mb-4">
                <span class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 rounded-lg font-bold text-sm mr-3">4</span>
                <h2 class="text-xl font-bold text-slate-900">O'chirish muddati</h2>
              </div>
              <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
                <div class="flex items-start">
                  <svg class="w-6 h-6 text-amber-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <div>
                    <p class="text-slate-800 font-medium mb-2">So'rov qabul qilingandan so'ng:</p>
                    <ul class="text-slate-700 space-y-1">
                      <li>Ma'lumotlar <strong>30 kun</strong> ichida to'liq o'chiriladi</li>
                      <li>Shu muddat ichida so'rovni bekor qilish imkoniyati mavjud</li>
                      <li>O'chirish yakunlangandan so'ng ma'lumotlarni qayta tiklash imkonsiz</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- 5. Bog'lanish -->
            <div>
              <div class="flex items-center mb-4">
                <span class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 rounded-lg font-bold text-sm mr-3">5</span>
                <h2 class="text-xl font-bold text-slate-900">Bog'lanish</h2>
              </div>
              <p class="text-slate-700 leading-relaxed mb-4">
                Ma'lumotlarni o'chirish bo'yicha savollaringiz bo'lsa, biz bilan bog'laning:
              </p>
              <div class="grid sm:grid-cols-2 gap-4">
                <div class="bg-slate-50 rounded-2xl p-5">
                  <p class="text-sm text-slate-500 mb-1">Email</p>
                  <a href="mailto:support@biznespilot.uz" class="text-red-600 font-medium hover:text-red-700">support@biznespilot.uz</a>
                </div>
                <div class="bg-slate-50 rounded-2xl p-5">
                  <p class="text-sm text-slate-500 mb-1">Telegram</p>
                  <a href="https://t.me/biznespilot" target="_blank" class="text-red-600 font-medium hover:text-red-700">@biznespilot</a>
                </div>
              </div>
            </div>

          </div>

          <!-- Back to Home -->
          <div class="text-center mt-10">
            <Link href="/" class="inline-flex items-center text-red-600 hover:text-red-700 font-medium transition-colors group">
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
import { Head, Link } from '@inertiajs/vue3'
import LandingLayout from '@/layouts/LandingLayout.vue'
</script>
