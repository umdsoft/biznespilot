<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import LandingLayout from '@/layouts/LandingLayout.vue'
import {
  ArrowRightIcon,
  PlayIcon,
  ChatBubbleLeftRightIcon,
  PhoneIcon,
  ChartBarIcon,
  UserGroupIcon,
  ExclamationTriangleIcon,
  CheckIcon,
  SparklesIcon,
  BoltIcon,
  RocketLaunchIcon,
  ShieldCheckIcon,
  ClockIcon,
  CpuChipIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { StarIcon } from '@heroicons/vue/24/solid'

const demoInput = ref('')
const demoMessages = ref([])
const demoBotTyping = ref(false)

const problems = [
  {
    emoji: '💸',
    title: 'Mijoz narx so\'radi, admin 3 soatdan keyin javob yozdi.',
    result: 'Natija: Mijoz boshqasidan sotib oldi.',
    description: 'Har bir kechikkan javob — yo\'qotilgan pul. Raqobatchingiz 30 soniyada javob bersa, siz 3 soatda — kim yutadi?',
  },
  {
    emoji: '🤬',
    title: 'Menejer telefonda mijozga qo\'pol gapirdi.',
    result: 'Natija: Mijoz qaytib kelmaydi va yomon izoh qoldiradi.',
    description: 'Bitta yomon qo\'ng\'iroq = 10 ta yo\'qotilgan mijoz. Siz buni faqat shikoyat kelganda bilasiz — juda kech.',
  },
  {
    emoji: '📉',
    title: 'Reklamaga $500 tikdingiz, lekin qancha savdo bo\'lganini bilmaysiz.',
    result: 'Natija: Pul havoga ketdi.',
    description: 'Qaysi kanal ishlayotganini bilmasangiz, har oyda pulni ko\'r-ko\'rona sarflaysiz. Bu o\'yin emas — bu biznes.',
  },
]

const testimonials = [
  {
    name: 'Aziz Karimov',
    text: 'BiznesPilot orqali Instagram sotuvlarimiz 3 barobar oshdi. Bot tungi paytda ham mijozlarga javob beradi — bu g\'aroyib!',
    rating: 5,
    metric: '3x sotuv',
  },
  {
    name: 'Nilufar Rahimova',
    text: 'Menejerlarimiz endi professional gaplashadi. AI tahlili tufayli qo\'ng\'iroqlar sifati 40% yaxshilandi.',
    rating: 5,
    metric: '+40% sifat',
  },
  {
    name: 'Bobur Aliyev',
    text: 'CRM tizimi juda qulay. Barcha lidlar bitta joyda, hech biri yo\'qolmaydi. Jamoani boshqarish osonlashdi.',
    rating: 5,
    metric: '0% lid yo\'qotish',
  },
]

const waveformHeights = [
  12, 20, 8, 28, 16, 24, 10, 32, 14, 22,
  8, 26, 18, 30, 12, 24, 8, 20, 28, 14,
  22, 32, 10, 18, 26, 8, 24, 16, 30, 12,
  20, 28, 8, 22, 14, 26, 18, 10, 24, 32,
]

// Demo bot responses
const botResponses = {
  default: 'Assalomu alaykum! Sizga qanday yordam bera olaman? Mahsulotlarimiz narxi va katalogini yuborishim mumkin.',
  narx: 'Albatta! Mahsulot narxi 450,000 so\'m. Hozir buyurtma bersangiz, yetkazib berish BEPUL! Buyurtma berasizmi?',
  ha: 'Ajoyib! Telefon raqamingizni yuboring, 5 daqiqada menejerimiz bog\'lanadi. To\'lov qabul qilindi ✅',
  boshqa: 'Tushundim! Katalogimizni ko\'rish uchun "narx" deb yozing yoki savolingizni bering — men 24/7 tayyorman!',
}

function sendDemoMessage() {
  if (!demoInput.value.trim()) return

  const userMsg = demoInput.value.trim()
  demoMessages.value.push({ from: 'user', text: userMsg })
  demoInput.value = ''

  demoBotTyping.value = true
  setTimeout(() => {
    demoBotTyping.value = false
    const lower = userMsg.toLowerCase()
    let response = botResponses.boshqa
    if (lower.includes('narx') || lower.includes('qancha') || lower.includes('necha')) {
      response = botResponses.narx
    } else if (lower.includes('ha') || lower.includes('buyurtma') || lower.includes('olaman')) {
      response = botResponses.ha
    } else if (demoMessages.value.length <= 2) {
      response = botResponses.default
    }
    demoMessages.value.push({ from: 'bot', text: response })
  }, 800)
}

// Live counter animation
const liveCounter = ref(0)
onMounted(() => {
  let target = 45
  let current = 0
  const step = () => {
    if (current < target) {
      current++
      liveCounter.value = current
      setTimeout(step, 40)
    }
  }
  setTimeout(step, 500)
})
</script>

<template>
  <LandingLayout v-slot="{ urgencyBarVisible }">

    <!-- ============================== -->
    <!-- HERO SECTION (Provocative)     -->
    <!-- ============================== -->
    <section class="relative overflow-hidden" :class="urgencyBarVisible ? 'pt-[138px] lg:pt-[170px]' : 'pt-32 lg:pt-40'" style="padding-bottom: 4rem;">
      <!-- Background -->
      <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-indigo-50/60"></div>
      <div class="absolute top-0 right-0 w-[700px] h-[700px] bg-indigo-200/25 rounded-full blur-3xl -translate-y-1/3 translate-x-1/4"></div>
      <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-amber-100/20 rounded-full blur-3xl translate-y-1/3 -translate-x-1/4"></div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">

          <!-- Left: Text Content -->
          <div class="max-w-xl mx-auto lg:mx-0 text-center lg:text-left">
            <!-- Live Social Proof -->
            <div class="inline-flex items-center px-4 py-2 bg-emerald-50 text-emerald-700 rounded-full text-sm font-medium mb-6 border border-emerald-200/60">
              <span class="relative flex h-2.5 w-2.5 mr-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
              </span>
              Bugunning o'zida <span class="font-bold text-emerald-800 mx-1">{{ liveCounter }} ta</span> tadbirkor tizimni o'rnatdi
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-[3.25rem] font-bold text-slate-900 leading-[1.1] tracking-tight">
              Raqobatchilaringiz mijozlaringizni
              <span class="text-transparent bg-clip-text bg-gradient-to-r from-rose-500 to-orange-500"> ilib ketishidan</span>
              charchamadingizmi?
            </h1>

            <p class="mt-6 text-lg sm:text-xl text-slate-600 leading-relaxed">
              BiznesPilot bilan Instagram Direct, qo'ng'iroqlar va savdoni <span class="font-semibold text-slate-900">24/7 nazoratga oling.</span>
              Siz uxlayotganda ham tizim sotadi.
            </p>

            <!-- CTA Buttons -->
            <div class="mt-10 flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
              <Link
                href="/register"
                class="group w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4.5 text-base font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl hover:from-indigo-700 hover:to-indigo-800 transition-all shadow-xl shadow-indigo-500/30 hover:shadow-2xl hover:shadow-indigo-500/40 active:scale-[0.98]"
              >
                Tizimni Hoziroq Ishga Tushirish
                <ArrowRightIcon class="w-5 h-5 transition-transform group-hover:translate-x-1" />
              </Link>
              <a
                href="#demo"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 text-base font-semibold text-slate-700 bg-white rounded-xl hover:bg-slate-50 transition-all border border-slate-200 shadow-sm active:scale-[0.98]"
              >
                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                  <PlayIcon class="w-4 h-4 text-indigo-600" />
                </div>
                1 daqiqada ko'ring
              </a>
            </div>

            <!-- Trust Micro-Copy -->
            <div class="mt-5 text-sm text-slate-500 text-center lg:text-left">
              💳 Karta shart emas. 2 daqiqada sozlanadi.
            </div>
          </div>

          <!-- Right: Rich Platform Dashboard Mockup -->
          <div class="relative hidden lg:block">

            <!-- Floating Badge: Top Right — Time Savings -->
            <div class="absolute -top-6 -right-2 z-20 bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 p-3 flex items-center gap-3 animate-float">
              <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center">
                <span class="text-white font-bold text-sm">60%</span>
              </div>
              <div>
                <p class="text-sm font-bold text-slate-900">Vaqt tejash</p>
                <p class="text-xs text-slate-500">Har kuni</p>
              </div>
            </div>

            <!-- Main Dashboard Card -->
            <div class="bg-white rounded-2xl shadow-2xl shadow-slate-300/40 border border-slate-200/80 overflow-hidden">

              <!-- Dashboard Header -->
              <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                  </div>
                  <span class="text-sm font-semibold text-slate-700">BiznesPilot</span>
                  <span class="text-xs text-slate-400 hidden xl:inline">Boshqaruv tizimi</span>
                </div>
                <!-- Tabs -->
                <div class="flex gap-1">
                  <span class="px-3 py-1.5 bg-indigo-600 text-white text-[11px] font-semibold rounded-lg">Marketing</span>
                  <span class="px-3 py-1.5 text-slate-500 text-[11px] font-medium rounded-lg hover:bg-slate-100">Sotuv</span>
                  <span class="px-3 py-1.5 text-slate-500 text-[11px] font-medium rounded-lg hover:bg-slate-100">Moliya</span>
                  <span class="px-3 py-1.5 text-slate-500 text-[11px] font-medium rounded-lg hover:bg-slate-100">Jamoa</span>
                </div>
              </div>

              <!-- Dashboard Content -->
              <div class="p-5 space-y-4">
                <!-- Stat Cards Row -->
                <div class="grid grid-cols-3 gap-3">
                  <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-xl p-3.5 border border-emerald-200/50">
                    <div class="flex items-center gap-1.5 mb-2">
                      <div class="w-4 h-4 rounded bg-emerald-500 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                      </div>
                      <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Marketing</span>
                    </div>
                    <p class="text-2xl font-bold text-slate-900">2,847</p>
                    <p class="text-[10px] text-slate-600 mt-0.5">Lidlar</p>
                  </div>
                  <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-xl p-3.5 border border-blue-200/50">
                    <div class="flex items-center gap-1.5 mb-2">
                      <div class="w-4 h-4 rounded bg-blue-500 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                      </div>
                      <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Sotuv</span>
                    </div>
                    <p class="text-2xl font-bold text-slate-900">128.5M</p>
                    <p class="text-[10px] text-slate-600 mt-0.5">Oylik daromad</p>
                  </div>
                  <div class="bg-gradient-to-br from-violet-50 to-violet-100/50 rounded-xl p-3.5 border border-violet-200/50">
                    <div class="flex items-center gap-1.5 mb-2">
                      <div class="w-4 h-4 rounded bg-violet-500 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                      </div>
                      <span class="text-[10px] font-bold text-violet-700 uppercase tracking-wider">Moliya</span>
                    </div>
                    <p class="text-2xl font-bold text-slate-900">89.2%</p>
                    <p class="text-[10px] text-slate-600 mt-0.5">Foyda margini</p>
                  </div>
                </div>

                <!-- Chart + AI Panel Row -->
                <div class="grid grid-cols-5 gap-3">
                  <div class="col-span-3 bg-slate-50/80 rounded-xl p-4 border border-slate-200/50">
                    <div class="flex items-center justify-between mb-3">
                      <span class="text-xs font-semibold text-slate-700">Yagona Dashboard</span>
                      <div class="flex items-center gap-2">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-indigo-500"></span><span class="text-[9px] text-slate-500">Sotuv</span></span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><span class="text-[9px] text-slate-500">Lidlar</span></span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span><span class="text-[9px] text-slate-500">Foyda</span></span>
                      </div>
                    </div>
                    <svg class="w-full" viewBox="0 0 300 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="indigo-fill" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stop-color="rgb(99,102,241)" stop-opacity="0.15"/>
                          <stop offset="100%" stop-color="rgb(99,102,241)" stop-opacity="0"/>
                        </linearGradient>
                      </defs>
                      <line x1="0" y1="20" x2="300" y2="20" stroke="#e2e8f0" stroke-width="0.5"/>
                      <line x1="0" y1="40" x2="300" y2="40" stroke="#e2e8f0" stroke-width="0.5"/>
                      <line x1="0" y1="60" x2="300" y2="60" stroke="#e2e8f0" stroke-width="0.5"/>
                      <path d="M0,65 C20,60 40,55 60,45 C80,35 100,40 130,30 C160,20 180,25 210,18 C240,12 260,15 280,10 L300,8" stroke="rgb(99,102,241)" stroke-width="2" fill="none"/>
                      <path d="M0,65 C20,60 40,55 60,45 C80,35 100,40 130,30 C160,20 180,25 210,18 C240,12 260,15 280,10 L300,8 V80 H0 Z" fill="url(#indigo-fill)"/>
                      <path d="M0,55 C30,50 50,48 80,40 C110,32 140,38 170,28 C200,22 230,20 260,15 L300,12" stroke="rgb(16,185,129)" stroke-width="1.5" fill="none" stroke-dasharray="4,2"/>
                      <path d="M0,50 C40,52 70,45 100,42 C130,39 160,35 190,30 C220,26 250,22 280,20 L300,18" stroke="rgb(251,191,36)" stroke-width="1.5" fill="none"/>
                      <circle cx="210" cy="18" r="3" fill="rgb(99,102,241)"/>
                      <circle cx="210" cy="18" r="6" fill="rgb(99,102,241)" opacity="0.15"/>
                    </svg>
                  </div>
                  <div class="col-span-2 space-y-2">
                    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl px-3 py-2.5 flex items-center gap-2">
                      <CpuChipIcon class="w-4 h-4 text-white/80" />
                      <span class="text-[11px] font-semibold text-white">AI Avtomatizatsiya</span>
                    </div>
                    <div class="bg-white rounded-xl p-2.5 border border-slate-200/80 flex items-start gap-2">
                      <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <CheckIcon class="w-3 h-3 text-green-600" />
                      </div>
                      <div>
                        <p class="text-[11px] font-semibold text-slate-800">Lead scoring</p>
                        <p class="text-[9px] text-slate-500">847 lid baholandi</p>
                      </div>
                    </div>
                    <div class="bg-white rounded-xl p-2.5 border border-slate-200/80 flex items-start gap-2">
                      <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                      </div>
                      <div>
                        <p class="text-[11px] font-semibold text-slate-800">Hisobot yaratish</p>
                        <p class="text-[9px] text-slate-500">Moliya + Sotuv</p>
                      </div>
                    </div>
                    <div class="bg-white rounded-xl p-2.5 border border-slate-200/80 flex items-start gap-2">
                      <div class="w-5 h-5 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <BoltIcon class="w-3 h-3 text-purple-600" />
                      </div>
                      <div>
                        <p class="text-[11px] font-semibold text-slate-800">Workflow</p>
                        <p class="text-[9px] text-slate-500">15 avto-jarayon</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Floating Badge: Bottom Left — AI 24/7 -->
            <div class="absolute -bottom-5 -left-3 z-20 bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 p-3 flex items-center gap-3 animate-float-delayed">
              <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center">
                <span class="text-white font-bold text-[10px] leading-none">AI</span>
              </div>
              <div>
                <p class="text-sm font-bold text-slate-900">Ishlaydi 24/7</p>
                <p class="text-xs text-slate-500">Avtomatizatsiya</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Social Proof Bar -->
        <div class="mt-16 pt-10 border-t border-slate-200/60">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
              <span class="text-sm text-slate-400 whitespace-nowrap">Biznesini rivojlantirmoqda:</span>
              <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 rounded-full border border-slate-200/60">
                  <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-600">T</div>
                  <span class="text-xs font-medium text-slate-700">TechStart</span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 rounded-full border border-slate-200/60">
                  <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-[10px] font-bold text-emerald-600">F</div>
                  <span class="text-xs font-medium text-slate-700">FoodExpress</span>
                </div>
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-slate-50 rounded-full border border-slate-200/60">
                  <div class="w-6 h-6 rounded-full bg-violet-100 flex items-center justify-center text-[10px] font-bold text-violet-600">E</div>
                  <span class="text-xs font-medium text-slate-700">EduPlatform</span>
                </div>
                <span class="text-sm font-bold text-indigo-600">+497</span>
              </div>
            </div>
            <div class="hidden lg:flex items-center gap-6">
              <div class="flex items-center gap-2">
                <RocketLaunchIcon class="w-5 h-5 text-indigo-500" />
                <div>
                  <span class="text-sm font-bold text-slate-900">Yagona tizim</span>
                  <span class="text-xs text-slate-500 block">foydalanmoqda</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <CpuChipIcon class="w-5 h-5 text-emerald-500" />
                <div>
                  <span class="text-sm font-bold text-slate-900">Sun'iy intellekt</span>
                  <span class="text-xs text-slate-500 block">tahlil qilmoqda</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <ShieldCheckIcon class="w-5 h-5 text-violet-500" />
                <div>
                  <span class="text-sm font-bold text-slate-900">To'liq nazorat</span>
                  <span class="text-xs text-slate-500 block">samaradorlik</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>


    <!-- ============================== -->
    <!-- MODULES OVERVIEW               -->
    <!-- ============================== -->
    <section class="py-20 md:py-28 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 rounded-full text-sm font-medium mb-6 border border-indigo-100">
            <BoltIcon class="w-4 h-4" />
            Bitta tizim — to'liq nazorat
          </div>
          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 leading-tight">
            Biznesingizning har bir bo'limi nazorat ostida
          </h2>
          <p class="mt-5 text-lg text-slate-500">
            6 ta modul. 1 ta tizim. Sun'iy intellekt kuchi.
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
          <!-- Card 1: Marketing -->
          <div class="group relative bg-white border border-slate-200 rounded-2xl p-8 hover:shadow-xl hover:shadow-emerald-100/40 hover:border-emerald-200/60 transition-all duration-300 hover:-translate-y-1">
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
              <span class="inline-block px-3 py-1 bg-emerald-500 text-white text-[11px] font-bold rounded-full shadow-lg shadow-emerald-500/25">3x ko'proq mijoz</span>
            </div>
            <div class="w-14 h-14 mb-6 mt-2 rounded-2xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
              <ChartBarIcon class="w-7 h-7 text-white" />
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Marketing boshqaruvi</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Barcha kanallardan mijozlar bir joyda. Sun'iy intellekt eng samarali reklamani topadi.
            </p>
          </div>

          <!-- Card 2: Sotuv va CRM -->
          <div class="group relative bg-white border border-slate-200 rounded-2xl p-8 hover:shadow-xl hover:shadow-orange-100/40 hover:border-orange-200/60 transition-all duration-300 hover:-translate-y-1">
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
              <span class="inline-block px-3 py-1 bg-orange-500 text-white text-[11px] font-bold rounded-full shadow-lg shadow-orange-500/25">+40% sotuv</span>
            </div>
            <div class="w-14 h-14 mb-6 mt-2 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center shadow-lg shadow-emerald-500/20">
              <UserGroupIcon class="w-7 h-7 text-white" />
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Sotuv va CRM</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Har bir mijoz, har bir bitim nazorat ostida. Sotuvchilar samaradorligi real vaqtda.
            </p>
          </div>

          <!-- Card 3: Moliya -->
          <div class="group relative bg-white border border-slate-200 rounded-2xl p-8 hover:shadow-xl hover:shadow-violet-100/40 hover:border-violet-200/60 transition-all duration-300 hover:-translate-y-1">
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
              <span class="inline-block px-3 py-1 bg-violet-500 text-white text-[11px] font-bold rounded-full shadow-lg shadow-violet-500/25">100% aniqlik</span>
            </div>
            <div class="w-14 h-14 mb-6 mt-2 rounded-2xl bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/20">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Moliya boshqaruvi</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Daromad-xarajat, qarzlar, to'lovlar — hammasi aniq. Buxgalter uchun tayyor hisobotlar.
            </p>
          </div>

          <!-- Card 4: AI -->
          <div class="group relative bg-white border border-slate-200 rounded-2xl p-8 hover:shadow-xl hover:shadow-orange-100/40 hover:border-orange-200/60 transition-all duration-300 hover:-translate-y-1">
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
              <span class="inline-block px-3 py-1 bg-orange-500 text-white text-[11px] font-bold rounded-full shadow-lg shadow-orange-500/25">60% vaqt tejash</span>
            </div>
            <div class="w-14 h-14 mb-6 mt-2 rounded-2xl bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center shadow-lg shadow-orange-500/20">
              <SparklesIcon class="w-7 h-7 text-white" />
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Sun'iy intellekt</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Kundalik vazifalarni tizim bajaradi. Muammolarni oldindan topadi va ogohlantiradi.
            </p>
          </div>

          <!-- Card 5: Dashboard -->
          <div class="group relative bg-white border border-slate-200 rounded-2xl p-8 hover:shadow-xl hover:shadow-blue-100/40 hover:border-blue-200/60 transition-all duration-300 hover:-translate-y-1">
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
              <span class="inline-block px-3 py-1 bg-blue-600 text-white text-[11px] font-bold rounded-full shadow-lg shadow-blue-600/25">To'liq nazorat</span>
            </div>
            <div class="w-14 h-14 mb-6 mt-2 rounded-2xl bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center shadow-lg shadow-blue-500/20">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Markaziy Dashboard</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Biznes holati bitta ekranda. Pul, sotuv, jamoa — hammasi real vaqtda ko'rinadi.
            </p>
          </div>

          <!-- Card 6: HR -->
          <div class="group relative bg-white border border-slate-200 rounded-2xl p-8 hover:shadow-xl hover:shadow-pink-100/40 hover:border-pink-200/60 transition-all duration-300 hover:-translate-y-1">
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
              <span class="inline-block px-3 py-1 bg-pink-500 text-white text-[11px] font-bold rounded-full shadow-lg shadow-pink-500/25">2x samaradorlik</span>
            </div>
            <div class="w-14 h-14 mb-6 mt-2 rounded-2xl bg-gradient-to-br from-pink-400 to-rose-500 flex items-center justify-center shadow-lg shadow-pink-500/20">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">HR Boshqaruvi</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Xodimlar, vazifalar, ish haqi — hammasi shaffof. Kim nima bilan band — aniq ko'rinadi.
            </p>
          </div>
        </div>
      </div>
    </section>


    <!-- ============================== -->
    <!-- PROBLEM AGITATION (Red Zone)   -->
    <!-- ============================== -->
    <section class="py-20 md:py-28 bg-gradient-to-b from-slate-50 to-rose-50/30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
          <span class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-bold text-red-600 bg-red-50 rounded-full mb-5 border border-red-200/60">
            <ExclamationTriangleIcon class="w-3.5 h-3.5" />
            Diqqat: Muammolar
          </span>
          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">
            Biznesingizda shunday <span class="text-red-500">"teshiklar"</span> bormi?
          </h2>
          <p class="mt-4 text-lg text-slate-600">Har bir teshik — yo'qotilgan pul. Tekshirib ko'ring.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
          <div
            v-for="(problem, idx) in problems"
            :key="idx"
            class="group relative bg-white rounded-2xl p-8 border-2 border-red-100/80 hover:border-red-300 hover:shadow-xl hover:shadow-red-100/50 transition-all duration-300 hover:-translate-y-1"
          >
            <!-- Red top line -->
            <div class="absolute top-0 left-4 right-4 h-1 bg-gradient-to-r from-red-400 via-rose-500 to-red-400 rounded-b-full"></div>

            <!-- Emoji + scenario -->
            <div class="text-4xl mb-5 mt-1">{{ problem.emoji }}</div>
            <h3 class="text-base font-bold text-slate-900 mb-2 leading-snug">{{ problem.title }}</h3>
            <p class="text-sm font-semibold text-red-500 mb-3">{{ problem.result }}</p>
            <p class="text-sm text-slate-500 leading-relaxed">{{ problem.description }}</p>
          </div>
        </div>

        <!-- Bottom punch line -->
        <div class="mt-14 text-center">
          <div class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-2xl shadow-xl shadow-indigo-500/25">
            <ShieldCheckIcon class="w-6 h-6" />
            <span class="text-base font-bold">BiznesPilot bu teshiklarni yopadi!</span>
            <ArrowRightIcon class="w-5 h-5" />
          </div>
        </div>
      </div>
    </section>


    <!-- ============================== -->
    <!-- SOLUTION SHOWCASE (Zigzag)     -->
    <!-- ============================== -->
    <section id="features" class="py-20 md:py-28 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-20">
          <span class="inline-block px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-full mb-4 border border-indigo-100">
            Yechimlar
          </span>
          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">Har bir muammoga — aqlli yechim</h2>
          <p class="mt-4 text-lg text-slate-600">Platformaning asosiy imkoniyatlari bilan tanishing</p>
        </div>

        <!-- Solution 1: Instagram Bot -->
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center mb-28">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-50 text-purple-700 rounded-full text-xs font-semibold mb-5 border border-purple-100">
              <ChatBubbleLeftRightIcon class="w-4 h-4" />
              Instagram Avtomatizatsiya
              <span class="px-2 py-0.5 bg-purple-600 text-white rounded-full text-[10px] font-bold ml-1">Javob tezligi 95% oshadi</span>
            </div>
            <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-4 leading-tight">
              Instagram Direct — 24/7 ishlaydigan sotuvchingiz.
            </h3>
            <p class="text-lg text-slate-600 leading-relaxed mb-8">
              Bot mijoz bilan salomlashadi, narx yuboradi va CRMga lid sifatida tushiradi.
              Siz uxlayotganda ham savdo to'xtamaydi.
            </p>
            <ul class="space-y-4">
              <li class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <CheckIcon class="w-3.5 h-3.5 text-green-600" />
                </div>
                <span class="text-sm text-slate-700">Avtomatik salomlash va narx yuborish</span>
              </li>
              <li class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <CheckIcon class="w-3.5 h-3.5 text-green-600" />
                </div>
                <span class="text-sm text-slate-700">Lidlarni avtomatik CRMga yozib borish</span>
              </li>
              <li class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <CheckIcon class="w-3.5 h-3.5 text-green-600" />
                </div>
                <span class="text-sm text-slate-700">24/7 tez javob berish imkoniyati</span>
              </li>
            </ul>
          </div>

          <!-- Visual: Chat Mockup -->
          <div class="bg-white rounded-2xl shadow-2xl shadow-purple-100/40 border border-slate-200/80 p-6 ring-1 ring-purple-100/50">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
              <div class="w-11 h-11 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400"></div>
              <div class="flex-1">
                <p class="text-sm font-semibold text-slate-900">your_business</p>
                <p class="text-xs text-green-600 font-medium">Faol · Bot yoqilgan</p>
              </div>
              <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-[10px] font-bold">DIRECT</span>
            </div>
            <div class="space-y-3">
              <div class="flex justify-start">
                <div class="bg-slate-100 rounded-2xl rounded-tl-md px-4 py-2.5 max-w-[260px]">
                  <p class="text-sm text-slate-700">Assalomu alaykum! Bu mahsulot bormi?</p>
                </div>
              </div>
              <div class="flex justify-end">
                <div class="bg-indigo-600 rounded-2xl rounded-tr-md px-4 py-2.5 max-w-[280px]">
                  <p class="text-sm text-white">Vaalaykum assalom! Ha, mahsulot mavjud. Narxi 450,000 so'm. Buyurtma berasizmi?</p>
                </div>
              </div>
              <div class="flex justify-start">
                <div class="bg-slate-100 rounded-2xl rounded-tl-md px-4 py-2.5">
                  <p class="text-sm text-slate-700">Ha, buyurtma bermoqchiman!</p>
                </div>
              </div>
              <div class="flex justify-end">
                <div class="bg-indigo-600 rounded-2xl rounded-tr-md px-4 py-2.5 max-w-[280px]">
                  <p class="text-sm text-white">Ajoyib! To'lov qabul qilindi ✅ Menejerimiz 5 daqiqada bog'lanadi.</p>
                </div>
              </div>
              <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 rounded-full text-[10px] font-semibold border border-green-200/60">
                  <CheckIcon class="w-3 h-3" /> CRMga saqlandi
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full text-[10px] font-semibold border border-indigo-200/60">
                  <CpuChipIcon class="w-3 h-3" /> Bot javob berdi — 3 soniya
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Solution 2: AI Call Center (Reversed) -->
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center mb-28">
          <div class="order-2 lg:order-1 bg-white rounded-2xl shadow-2xl shadow-blue-100/40 border border-slate-200/80 p-6 ring-1 ring-blue-100/50">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200">
                  <PhoneIcon class="w-5 h-5 text-slate-600" />
                </div>
                <div>
                  <p class="text-sm font-semibold text-slate-900">Sardor M. → Mijoz #1204</p>
                  <p class="text-xs text-slate-500">Bugun, 14:32 · 4 min 23 sek</p>
                </div>
              </div>
              <div class="px-3 py-1.5 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200/60">
                AI Score: 95%
              </div>
            </div>

            <!-- Audio Waveform -->
            <div class="bg-slate-50 rounded-xl p-4 mb-5 border border-slate-200/50">
              <div class="flex items-center gap-3">
                <button class="w-9 h-9 bg-indigo-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg shadow-indigo-500/25 hover:bg-indigo-700 transition-colors active:scale-95">
                  <PlayIcon class="w-4 h-4 text-white" />
                </button>
                <div class="flex-1 flex items-center gap-[2px] h-8">
                  <div
                    v-for="(h, i) in waveformHeights"
                    :key="i"
                    class="flex-1 rounded-full"
                    :class="i < 25 ? 'bg-indigo-400' : 'bg-indigo-200'"
                    :style="{ height: h + 'px' }"
                  ></div>
                </div>
                <span class="text-xs text-slate-500 flex-shrink-0 font-medium">4:23</span>
              </div>
            </div>

            <!-- AI Analysis Results -->
            <div class="space-y-2.5">
              <div class="flex items-center gap-3 bg-green-50/50 rounded-lg px-3 py-2 border border-green-100/60">
                <CheckIcon class="w-4 h-4 text-green-500 flex-shrink-0" />
                <span class="text-sm text-slate-700 flex-1">Salomlashish</span>
                <span class="text-xs font-semibold text-green-600">Yaxshi</span>
              </div>
              <div class="flex items-center gap-3 bg-green-50/50 rounded-lg px-3 py-2 border border-green-100/60">
                <CheckIcon class="w-4 h-4 text-green-500 flex-shrink-0" />
                <span class="text-sm text-slate-700 flex-1">Mahsulot tavsifi</span>
                <span class="text-xs font-semibold text-green-600">To'liq</span>
              </div>
              <div class="flex items-center gap-3 bg-amber-50/50 rounded-lg px-3 py-2 border border-amber-100/60">
                <ExclamationTriangleIcon class="w-4 h-4 text-amber-500 flex-shrink-0" />
                <span class="text-sm text-slate-700 flex-1">Narx aytish</span>
                <span class="text-xs font-semibold text-amber-600">E'tibor bering</span>
              </div>
              <div class="flex items-center gap-3 bg-green-50/50 rounded-lg px-3 py-2 border border-green-100/60">
                <CheckIcon class="w-4 h-4 text-green-500 flex-shrink-0" />
                <span class="text-sm text-slate-700 flex-1">Yakunlash</span>
                <span class="text-xs font-semibold text-green-600">Professional</span>
              </div>
            </div>
          </div>

          <div class="order-1 lg:order-2">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-full text-xs font-semibold mb-5 border border-blue-100">
              <SparklesIcon class="w-4 h-4" />
              AI Call Center
              <span class="px-2 py-0.5 bg-blue-600 text-white rounded-full text-[10px] font-bold ml-1">Sifat 40% oshadi</span>
            </div>
            <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-4 leading-tight">
              Har bir qo'ng'iroq sun'iy intellekt nazoratida.
            </h3>
            <p class="text-lg text-slate-600 leading-relaxed mb-8">
              Menejer mijoz bilan qo'pol gaplashdimi? Narxni noto'g'ri aytdimi?
              AI buni aniqlaydi va sizga xabar beradi.
            </p>
            <ul class="space-y-4">
              <li class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <CheckIcon class="w-3.5 h-3.5 text-green-600" />
                </div>
                <span class="text-sm text-slate-700">Qo'ng'iroqlarni avtomatik tahlil qilish</span>
              </li>
              <li class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <CheckIcon class="w-3.5 h-3.5 text-green-600" />
                </div>
                <span class="text-sm text-slate-700">Menejer xulqini baholash (AI Score)</span>
              </li>
              <li class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <CheckIcon class="w-3.5 h-3.5 text-green-600" />
                </div>
                <span class="text-sm text-slate-700">Muammoli qo'ng'iroqlar haqida ogohlantirish</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Solution 3: CRM & Team -->
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold mb-5 border border-emerald-100">
              <UserGroupIcon class="w-4 h-4" />
              CRM va Jamoa
              <span class="px-2 py-0.5 bg-emerald-600 text-white rounded-full text-[10px] font-bold ml-1">0% lid yo'qotish</span>
            </div>
            <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-4 leading-tight">
              Lidlarni yo'qotmang. Jamoani boshqaring.
            </h3>
            <p class="text-lg text-slate-600 leading-relaxed mb-8">
              Barcha mijozlar bazasi bitta joyda. Kim qancha sotayotganini aniq ko'ring.
              Jamoangiz natijasini real vaqtda kuzating.
            </p>
            <ul class="space-y-4">
              <li class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <CheckIcon class="w-3.5 h-3.5 text-green-600" />
                </div>
                <span class="text-sm text-slate-700">Barcha lidlar bitta CRM tizimida</span>
              </li>
              <li class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <CheckIcon class="w-3.5 h-3.5 text-green-600" />
                </div>
                <span class="text-sm text-slate-700">Jamoaviy natijalar va KPI monitoring</span>
              </li>
              <li class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <CheckIcon class="w-3.5 h-3.5 text-green-600" />
                </div>
                <span class="text-sm text-slate-700">Vazifalarni boshqarish va avtomatik tayinlash</span>
              </li>
            </ul>
          </div>

          <!-- Visual: CRM Dashboard Mockup -->
          <div class="bg-white rounded-2xl shadow-2xl shadow-emerald-100/40 border border-slate-200/80 p-6 ring-1 ring-emerald-100/50">
            <div class="grid grid-cols-3 gap-3 mb-5">
              <div class="bg-indigo-50 rounded-xl p-3.5 text-center border border-indigo-100/60">
                <p class="text-2xl font-bold text-indigo-600">342</p>
                <p class="text-[10px] text-slate-600 font-medium mt-1">Jami lidlar</p>
              </div>
              <div class="bg-green-50 rounded-xl p-3.5 text-center border border-green-100/60">
                <p class="text-2xl font-bold text-green-600">89</p>
                <p class="text-[10px] text-slate-600 font-medium mt-1">Sotilgan</p>
              </div>
              <div class="bg-amber-50 rounded-xl p-3.5 text-center border border-amber-100/60">
                <p class="text-2xl font-bold text-amber-600">26%</p>
                <p class="text-[10px] text-slate-600 font-medium mt-1">Konversiya</p>
              </div>
            </div>

            <div class="space-y-3.5">
              <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jamoa natijalari</p>
              <div class="flex items-center gap-3 bg-slate-50/50 rounded-xl p-2.5 border border-slate-100">
                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-600">SA</div>
                <div class="flex-1">
                  <div class="flex justify-between mb-1.5">
                    <span class="text-xs font-semibold text-slate-800">Sardor A.</span>
                    <span class="text-xs font-bold text-indigo-600">32 ta sotuv</span>
                  </div>
                  <div class="h-2 bg-slate-200/60 rounded-full">
                    <div class="h-2 bg-gradient-to-r from-indigo-500 to-indigo-400 rounded-full" style="width: 85%"></div>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3 bg-slate-50/50 rounded-xl p-2.5 border border-slate-100">
                <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center text-xs font-bold text-purple-600">NR</div>
                <div class="flex-1">
                  <div class="flex justify-between mb-1.5">
                    <span class="text-xs font-semibold text-slate-800">Nilufar R.</span>
                    <span class="text-xs font-bold text-purple-600">28 ta sotuv</span>
                  </div>
                  <div class="h-2 bg-slate-200/60 rounded-full">
                    <div class="h-2 bg-gradient-to-r from-purple-500 to-purple-400 rounded-full" style="width: 72%"></div>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3 bg-slate-50/50 rounded-xl p-2.5 border border-slate-100">
                <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-xs font-bold text-emerald-600">BT</div>
                <div class="flex-1">
                  <div class="flex justify-between mb-1.5">
                    <span class="text-xs font-semibold text-slate-800">Bobur T.</span>
                    <span class="text-xs font-bold text-emerald-600">24 ta sotuv</span>
                  </div>
                  <div class="h-2 bg-slate-200/60 rounded-full">
                    <div class="h-2 bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full" style="width: 62%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>


    <!-- ============================== -->
    <!-- INTERACTIVE MINI-DEMO          -->
    <!-- ============================== -->
    <section id="demo" class="py-20 md:py-28 bg-gradient-to-b from-indigo-50/50 to-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14">
          <span class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-bold text-indigo-600 bg-indigo-50 rounded-full mb-5 border border-indigo-100">
            <SparklesIcon class="w-3.5 h-3.5" />
            Interaktiv demo
          </span>
          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">
            Hoziroq sinab ko'ring — bot qanday ishlashini his qiling
          </h2>
          <p class="mt-4 text-lg text-slate-600">
            "Narxi qancha?" deb yozing va bot javobini ko'ring. Bu sizning biznesingizda ham shunday ishlaydi.
          </p>
        </div>

        <div class="max-w-4xl mx-auto">
          <div class="grid md:grid-cols-2 gap-6 lg:gap-8 items-start">

            <!-- Left: Input Panel -->
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0z"/></svg>
                </div>
                <div>
                  <p class="text-sm font-bold text-slate-900">Instagram Direct simulyatsiya</p>
                  <p class="text-xs text-slate-500">Mijoz sifatida yozing — bot javob beradi</p>
                </div>
              </div>

              <div class="space-y-3 mb-4">
                <button
                  @click="demoInput = 'Narxi qancha?'; sendDemoMessage()"
                  class="w-full text-left px-4 py-3 bg-slate-50 hover:bg-slate-100 rounded-xl text-sm text-slate-700 font-medium border border-slate-200/60 transition-colors active:scale-[0.98]"
                >
                  💬 "Narxi qancha?"
                </button>
                <button
                  @click="demoInput = 'Ha, buyurtma beraman'; sendDemoMessage()"
                  class="w-full text-left px-4 py-3 bg-slate-50 hover:bg-slate-100 rounded-xl text-sm text-slate-700 font-medium border border-slate-200/60 transition-colors active:scale-[0.98]"
                >
                  💬 "Ha, buyurtma beraman"
                </button>
              </div>

              <form @submit.prevent="sendDemoMessage" class="flex gap-2">
                <input
                  v-model="demoInput"
                  type="text"
                  placeholder="Yoki o'zingiz yozing..."
                  class="flex-1 px-4 py-3 bg-slate-50 rounded-xl text-sm border border-slate-200 focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100 outline-none transition-all"
                />
                <button
                  type="submit"
                  class="px-5 py-3 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/25 active:scale-95"
                >
                  Yuborish
                </button>
              </form>
            </div>

            <!-- Right: Phone Mockup -->
            <div class="bg-slate-900 rounded-[2rem] p-3 shadow-2xl shadow-slate-900/30">
              <!-- Phone notch -->
              <div class="bg-slate-900 rounded-t-[1.25rem] flex justify-center pt-2 pb-3">
                <div class="w-20 h-5 bg-slate-800 rounded-full"></div>
              </div>

              <!-- Phone Screen -->
              <div class="bg-white rounded-xl min-h-[380px] flex flex-col">
                <!-- Chat Header -->
                <div class="px-4 py-3 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-400 rounded-t-xl flex items-center gap-3">
                  <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/></svg>
                  </div>
                  <div>
                    <p class="text-white text-sm font-bold">BiznesPilot Bot</p>
                    <p class="text-white/70 text-[10px]">24/7 Onlayn</p>
                  </div>
                </div>

                <!-- Chat Messages -->
                <div class="flex-1 p-3 space-y-2.5 overflow-y-auto max-h-[280px]">
                  <!-- Default welcome -->
                  <div v-if="demoMessages.length === 0" class="space-y-2.5">
                    <div class="flex justify-start">
                      <div class="bg-slate-100 rounded-2xl rounded-tl-sm px-3 py-2 max-w-[85%]">
                        <p class="text-xs text-slate-700">Assalomu alaykum! Bizning do'konimizga xush kelibsiz. Sizga qanday yordam bera olaman?</p>
                      </div>
                    </div>
                  </div>

                  <template v-for="(msg, i) in demoMessages" :key="i">
                    <div v-if="msg.from === 'user'" class="flex justify-end">
                      <div class="bg-indigo-600 rounded-2xl rounded-tr-sm px-3 py-2 max-w-[85%]">
                        <p class="text-xs text-white">{{ msg.text }}</p>
                      </div>
                    </div>
                    <div v-else class="flex justify-start">
                      <div class="bg-slate-100 rounded-2xl rounded-tl-sm px-3 py-2 max-w-[85%]">
                        <p class="text-xs text-slate-700">{{ msg.text }}</p>
                      </div>
                    </div>
                  </template>

                  <!-- Typing indicator -->
                  <div v-if="demoBotTyping" class="flex justify-start">
                    <div class="bg-slate-100 rounded-2xl rounded-tl-sm px-4 py-2.5">
                      <div class="flex items-center gap-1">
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom CTA -->
          <div class="mt-10 text-center">
            <Link
              href="/register"
              class="group inline-flex items-center gap-2.5 px-8 py-4 text-base font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl hover:from-indigo-700 hover:to-indigo-800 transition-all shadow-xl shadow-indigo-500/25 active:scale-[0.98]"
            >
              O'z biznesingizda sinab ko'ring
              <ArrowRightIcon class="w-5 h-5 transition-transform group-hover:translate-x-1" />
            </Link>
            <p class="mt-3 text-sm text-slate-500">2 daqiqada sozlanadi. Karta kerak emas.</p>
          </div>
        </div>
      </div>
    </section>


    <!-- ============================== -->
    <!-- TESTIMONIALS                   -->
    <!-- ============================== -->
    <section class="py-20 md:py-28 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
          <span class="inline-block px-3 py-1 text-xs font-semibold text-amber-600 bg-amber-50 rounded-full mb-4 border border-amber-100">Fikrlar</span>
          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">Ular allaqachon natija olyapti</h2>
          <p class="mt-4 text-lg text-slate-600">Yuzlab tadbirkorlar BiznesPilotdan foydalanmoqda.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
          <div
            v-for="(t, idx) in testimonials"
            :key="idx"
            class="group relative bg-white border border-slate-200 rounded-2xl p-8 hover:shadow-xl hover:shadow-indigo-100/30 hover:border-indigo-200/60 transition-all duration-300 hover:-translate-y-1"
          >
            <!-- Quote icon -->
            <div class="absolute -top-3 left-6">
              <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg shadow-indigo-500/25">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H0z"/>
                </svg>
              </div>
            </div>

            <!-- Metric badge -->
            <div class="absolute -top-3 right-6">
              <span class="inline-block px-3 py-1 bg-emerald-500 text-white text-[10px] font-bold rounded-full shadow-lg shadow-emerald-500/25">{{ t.metric }}</span>
            </div>

            <div class="flex gap-0.5 mb-4 mt-3">
              <StarIcon v-for="s in t.rating" :key="s" class="w-4 h-4 text-amber-400" />
            </div>
            <p class="text-slate-700 leading-relaxed mb-6 text-sm">"{{ t.text }}"</p>
            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
              <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-200 flex items-center justify-center text-sm font-bold text-indigo-700">
                {{ t.name.split(' ').map(n => n[0]).join('') }}
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-900">{{ t.name }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>


    <!-- ============================== -->
    <!-- PRICING (Fear Removal)         -->
    <!-- ============================== -->
    <section id="pricing" class="py-20 md:py-28 bg-gradient-to-b from-slate-50 to-white border-t border-slate-200/60">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <span class="inline-block px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-full mb-4 border border-indigo-100">Narxlar</span>

          <!-- Fear-removal headline -->
          <p class="text-lg sm:text-xl font-semibold text-rose-500 mb-3">
            Birgina yo'qotilgan mijoz narxidan ham arzonroq.
          </p>

          <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">
            Investitsiya — qaytaradi. Xarajat emas.
          </h2>
          <p class="mt-4 text-lg text-slate-600">
            Yashirin to'lovlar yo'q. Istalgan vaqtda bekor qilishingiz mumkin.
          </p>
        </div>

        <!-- Price Highlight Card -->
        <div class="max-w-lg mx-auto bg-white rounded-2xl shadow-xl shadow-indigo-100/30 border-2 border-indigo-100 p-8 text-center relative overflow-hidden">
          <!-- Corner ribbon -->
          <div class="absolute top-5 -right-8 rotate-45 bg-amber-400 text-amber-900 text-[10px] font-bold px-10 py-1 shadow-md">
            Eng mashhur
          </div>

          <p class="text-sm font-semibold text-indigo-600 mb-1">Start tarifi</p>
          <p class="text-xs text-slate-500 mb-4">Kichik biznes uchun ideal boshlanish</p>
          <div class="flex items-baseline justify-center gap-1 mb-2">
            <span class="text-5xl font-bold text-slate-900">299,000</span>
            <span class="text-lg text-slate-500 font-medium">so'm/oy</span>
          </div>
          <p class="text-sm text-slate-500 mb-8">dan boshlanadi</p>

          <div class="flex flex-col sm:flex-row gap-3">
            <Link
              href="/register"
              class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-4 text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl hover:from-indigo-700 hover:to-indigo-800 transition-all shadow-lg shadow-indigo-500/25 active:scale-[0.98]"
            >
              14 Kun Bepul Boshlash
              <ArrowRightIcon class="w-4 h-4" />
            </Link>
            <Link
              href="/pricing"
              class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-4 text-sm font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-all border border-indigo-200"
            >
              Barcha tariflarni ko'rish
            </Link>
          </div>

          <div class="mt-6 flex items-center justify-center gap-4 text-xs text-slate-500">
            <span class="flex items-center gap-1"><CheckIcon class="w-3.5 h-3.5 text-green-500" /> Karta shart emas</span>
            <span class="flex items-center gap-1"><CheckIcon class="w-3.5 h-3.5 text-green-500" /> Bekor qilish bepul</span>
            <span class="flex items-center gap-1"><CheckIcon class="w-3.5 h-3.5 text-green-500" /> 14 kun tekin</span>
          </div>
        </div>
      </div>
    </section>


    <!-- ============================== -->
    <!-- FINAL CTA (Red-Hot Urgency)    -->
    <!-- ============================== -->
    <section class="relative py-24 md:py-32 overflow-hidden">
      <!-- Hot gradient background -->
      <div class="absolute inset-0 bg-gradient-to-br from-orange-600 via-rose-600 to-violet-700"></div>
      <div
        class="absolute inset-0 opacity-30"
        style="background-image: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, transparent 50%), radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1) 0%, transparent 50%);"
      ></div>

      <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/15 backdrop-blur-sm text-white rounded-full text-sm font-semibold mb-8 border border-white/20">
          <BoltIcon class="w-4 h-4" />
          Vaqt kutmaydi
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight">
          Raqobatchilaringiz allaqachon avtomatlashmoqda.
          <span class="block mt-2 text-amber-200">Siz nimani kutyapsiz?</span>
        </h2>
        <p class="mt-6 text-lg text-white/85 leading-relaxed max-w-2xl mx-auto">
          14 kunlik bepul davrni bugun faollashtiring. Hech qanday majburiyat yo'q.
          Yoqmasa — bir tugma bilan bekor qilasiz.
        </p>
        <div class="mt-10">
          <Link
            href="/register"
            class="group inline-flex items-center justify-center gap-2.5 px-12 py-5 text-lg font-bold text-slate-900 bg-white rounded-xl hover:bg-amber-50 transition-all shadow-2xl shadow-black/20 active:scale-[0.98]"
          >
            Hoziroq Bepul Boshlang
            <ArrowRightIcon class="w-5 h-5 transition-transform group-hover:translate-x-1" />
          </Link>
        </div>
        <p class="mt-6 text-sm text-white/70">
          14 kunlik bepul sinov · Karta talab qilinmaydi · 2 daqiqada ro'yxatdan o'tish
        </p>
      </div>
    </section>


  </LandingLayout>
</template>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-8px); }
}
@keyframes float-delayed {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-6px); }
}
.animate-float {
  animation: float 4s ease-in-out infinite;
}
.animate-float-delayed {
  animation: float-delayed 4s ease-in-out infinite 1s;
}
</style>
