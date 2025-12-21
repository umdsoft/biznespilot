<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <span class="text-xl font-bold text-gray-900">BiznesPilot AI</span>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">{{ $page.props.auth?.user?.name }}</span>
            <Link
              href="/logout"
              method="post"
              as="button"
              class="text-sm text-gray-500 hover:text-gray-700"
            >
              Chiqish
            </Link>
          </div>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative py-16 lg:py-24">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-4xl mx-auto">
          <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-medium mb-6">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
            AI-Powered Business Platform
          </div>
          <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
            Xush kelibsiz, <span class="text-blue-600">{{ $page.props.auth?.user?.name?.split(' ')[0] }}</span>!
          </h1>
          <p class="text-xl text-gray-600 mb-8 leading-relaxed">
            BiznesPilot AI - bu sizning biznesingizni AI yordamida boshqarish, marketing strategiyalarini yaratish va sotuvlarni oshirish uchun yaratilgan platforma.
          </p>
          <Link
            href="/welcome/create-business"
            class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transform transition-all duration-200 hover:scale-105 shadow-lg hover:shadow-xl"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Biznesni yaratish
          </Link>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Tizim qanday ishlaydi?</h2>
          <p class="text-lg text-gray-600">4 bosqichda biznesingizni yangi darajaga olib chiqing</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <!-- Step 1 -->
          <div class="relative">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100 h-full">
              <div class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center text-xl font-bold mb-4">
                1
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Biznes Yaratish</h3>
              <p class="text-gray-600 text-sm">
                Biznesingiz haqida asosiy ma'lumotlarni kiriting: nomi, sohasi, maqsadlari va boshqa tafsilotlar.
              </p>
              <div class="mt-4 flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Biznes profili</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Maqsadlar</span>
              </div>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="relative">
            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl p-6 border border-emerald-100 h-full">
              <div class="w-12 h-12 bg-emerald-600 text-white rounded-xl flex items-center justify-center text-xl font-bold mb-4">
                2
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Integratsiyalar</h3>
              <p class="text-gray-600 text-sm">
                Instagram, Telegram, WhatsApp va boshqa platformalarni ulang. AI chatbotlar avtomatik ishlaydi.
              </p>
              <div class="mt-4 flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs">Instagram</span>
                <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs">Telegram</span>
                <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs">WhatsApp</span>
              </div>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="relative">
            <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-2xl p-6 border border-purple-100 h-full">
              <div class="w-12 h-12 bg-purple-600 text-white rounded-xl flex items-center justify-center text-xl font-bold mb-4">
                3
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">AI Diagnostika</h3>
              <p class="text-gray-600 text-sm">
                AI sizning biznesingizni tahlil qiladi, kuchli va zaif tomonlarini aniqlaydi, tavsiyalar beradi.
              </p>
              <div class="mt-4 flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs">SWOT tahlil</span>
                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs">Tavsiyalar</span>
              </div>
            </div>
          </div>

          <!-- Step 4 -->
          <div class="relative">
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl p-6 border border-amber-100 h-full">
              <div class="w-12 h-12 bg-amber-600 text-white rounded-xl flex items-center justify-center text-xl font-bold mb-4">
                4
              </div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Dashboard & Monitoring</h3>
              <p class="text-gray-600 text-sm">
                Barcha ko'rsatkichlarni real vaqtda kuzating, alertlar oling va strategiyalarni boshqaring.
              </p>
              <div class="mt-4 flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-xs">Analytics</span>
                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-xs">Reports</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Asosiy imkoniyatlar</h2>
          <p class="text-lg text-gray-600">BiznesPilot AI bilan nimalarga erishish mumkin</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Feature 1 -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Ideal Mijoz Tahlili</h3>
            <p class="text-gray-600 text-sm">Ideal mijozingizni AI yordamida aniqlang va ular bilan to'g'ri muloqot qiling.</p>
          </div>

          <!-- Feature 2 -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">AI Chatbotlar</h3>
            <p class="text-gray-600 text-sm">Instagram, Telegram, WhatsApp uchun aqlli chatbotlar. 24/7 mijozlarga xizmat.</p>
          </div>

          <!-- Feature 3 -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Real-time Analytics</h3>
            <p class="text-gray-600 text-sm">Barcha ko'rsatkichlarni real vaqtda kuzating va qarorlar qabul qiling.</p>
          </div>

          <!-- Feature 4 -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">AI Tavsiyalar</h3>
            <p class="text-gray-600 text-sm">Sun'iy intellekt sizga qanday qadam tashlash kerakligini aytadi.</p>
          </div>

          <!-- Feature 5 -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Smart Alertlar</h3>
            <p class="text-gray-600 text-sm">Muhim o'zgarishlar haqida darhol xabar oling va tez harakat qiling.</p>
          </div>

          <!-- Feature 6 -->
          <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Avtomatik Hisobotlar</h3>
            <p class="text-gray-600 text-sm">Kunlik, haftalik va oylik hisobotlarni avtomatik ravishda oling.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-blue-600 to-indigo-600">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Hoziroq boshlang!</h2>
        <p class="text-xl text-blue-100 mb-8">
          Biznesingizni yarating va AI yordamida o'sishni boshlang
        </p>
        <Link
          href="/welcome/create-business"
          class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl hover:bg-gray-50 transform transition-all duration-200 hover:scale-105 shadow-lg"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>
          Biznesni yaratish
        </Link>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-gray-500 text-sm">
          &copy; {{ new Date().getFullYear() }} BiznesPilot AI. Barcha huquqlar himoyalangan.
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
