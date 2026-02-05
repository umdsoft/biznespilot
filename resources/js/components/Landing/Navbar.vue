<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useLandingTranslations } from '@/composables/useLandingTranslations'

const props = defineProps({
  urgencyBarVisible: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['close-urgency'])

const { locale, nav } = useLandingTranslations()

const mobileMenuOpen = ref(false)

const page = usePage()
const currentPath = computed(() => (page.url || '/').split('?')[0])
const isOnLandingPage = computed(() => currentPath.value === '/')

const navLinks = computed(() => [
  { name: nav.value.features, href: '/#features', anchor: '#features' },
  { name: nav.value.modules, href: '/#modules', anchor: '#modules' },
  { name: nav.value.pricing, href: '/pricing' },
])

function isActive(link) {
  if (link.href === '/pricing') {
    return currentPath.value === '/pricing'
  }
  return false
}

function closeMobileMenu() {
  mobileMenuOpen.value = false
}
</script>

<template>
  <!-- Urgency Bar -->
  <div
    v-if="urgencyBarVisible"
    class="fixed top-0 left-0 right-0 z-[60] bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-center gap-3 relative">
      <p class="text-sm font-semibold text-white text-center">
        <span class="hidden sm:inline">🔥 {{ nav.urgency_text }}</span>
        <span class="sm:hidden">🔥 {{ nav.urgency_text_short }}</span>
        <span class="underline decoration-2 decoration-white/60 ml-1 font-bold">{{ nav.urgency_highlight }}</span>
      </p>
      <button
        @click="emit('close-urgency')"
        class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-white/80 hover:text-white transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>

  <!-- Main Navbar -->
  <nav
    class="fixed left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200/50 transition-all"
    :class="urgencyBarVisible ? 'top-[42px]' : 'top-0'"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <Link href="/" class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/25">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <span class="text-xl font-bold text-slate-900">BiznesPilot</span>
        </Link>

        <!-- Desktop Navigation -->
        <div class="hidden md:flex items-center space-x-8">
          <template v-for="link in navLinks" :key="link.name">
            <!-- Anchor links: use <a> on landing page for smooth scroll, <Link> elsewhere -->
            <a
              v-if="link.anchor && isOnLandingPage"
              :href="link.anchor"
              class="text-sm font-medium transition-colors"
              :class="isActive(link) ? 'text-indigo-600 font-semibold' : 'text-slate-600 hover:text-slate-900'"
            >
              {{ link.name }}
            </a>
            <Link
              v-else
              :href="link.href"
              class="text-sm font-medium transition-colors"
              :class="isActive(link) ? 'text-indigo-600 font-semibold' : 'text-slate-600 hover:text-slate-900'"
            >
              {{ link.name }}
            </Link>
          </template>
        </div>

        <!-- CTA Buttons -->
        <div class="hidden sm:flex items-center space-x-3">
          <!-- Language Switcher -->
          <div class="flex items-center bg-slate-100 rounded-full p-0.5">
            <a href="/lang/uz-latn"
               class="flex items-center justify-center w-8 h-8 rounded-full transition-all"
               :class="locale !== 'ru' ? 'bg-white shadow-sm ring-1 ring-indigo-500/50' : 'hover:bg-slate-200'"
               title="O'zbekcha">
              <svg class="w-5 h-5 rounded-full" viewBox="0 0 512 512">
                <mask id="uzNav"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
                <g mask="url(#uzNav)">
                  <path fill="#0099b5" d="M0 0h512v167H0z"/>
                  <path fill="#fff" d="M0 178h512v156H0z"/>
                  <path fill="#1eb53a" d="M0 345h512v167H0z"/>
                  <path fill="#ce1126" d="M0 167h512v11H0zM0 334h512v11H0z"/>
                  <circle cx="163" cy="89" r="45" fill="#fff"/>
                  <circle cx="176" cy="89" r="40" fill="#0099b5"/>
                </g>
              </svg>
            </a>
            <a href="/lang/ru"
               class="flex items-center justify-center w-8 h-8 rounded-full transition-all"
               :class="locale === 'ru' ? 'bg-white shadow-sm ring-1 ring-indigo-500/50' : 'hover:bg-slate-200'"
               title="Русский">
              <svg class="w-5 h-5 rounded-full" viewBox="0 0 512 512">
                <mask id="ruNav"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
                <g mask="url(#ruNav)">
                  <path fill="#fff" d="M0 0h512v170.7H0z"/>
                  <path fill="#0039a6" d="M0 170.7h512v170.6H0z"/>
                  <path fill="#d52b1e" d="M0 341.3h512V512H0z"/>
                </g>
              </svg>
            </a>
          </div>

          <Link href="/login" class="text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors">
            {{ nav.login }}
          </Link>
          <Link
            href="/register"
            class="relative inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/30 active:scale-[0.98]"
          >
            {{ nav.get_started }}
            <span class="absolute -top-2.5 -right-2 px-2 py-0.5 bg-amber-400 text-amber-900 text-[10px] font-bold rounded-full shadow-md">{{ nav.free_badge }}</span>
          </Link>
        </div>

        <!-- Mobile menu button -->
        <button
          class="sm:hidden p-2 text-slate-600 hover:text-slate-900"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div v-if="mobileMenuOpen" class="sm:hidden bg-white border-b border-slate-200">
      <div class="px-4 py-4 space-y-3">
        <template v-for="link in navLinks" :key="link.name">
          <a
            v-if="link.anchor && isOnLandingPage"
            :href="link.anchor"
            class="block text-base font-medium transition-colors"
            :class="isActive(link) ? 'text-indigo-600 font-semibold' : 'text-slate-600 hover:text-slate-900'"
            @click="closeMobileMenu"
          >
            {{ link.name }}
          </a>
          <Link
            v-else
            :href="link.href"
            class="block text-base font-medium transition-colors"
            :class="isActive(link) ? 'text-indigo-600 font-semibold' : 'text-slate-600 hover:text-slate-900'"
            @click="closeMobileMenu"
          >
            {{ link.name }}
          </Link>
        </template>

        <!-- Mobile Language Switcher -->
        <div class="flex items-center justify-center space-x-3 py-2">
          <a href="/lang/uz-latn"
             class="flex items-center justify-center w-11 h-11 rounded-xl transition-all"
             :class="locale !== 'ru' ? 'bg-indigo-50 ring-2 ring-indigo-500' : 'bg-slate-100 hover:bg-slate-200'">
            <svg class="w-7 h-7 rounded-full" viewBox="0 0 512 512">
              <mask id="uzNavM"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
              <g mask="url(#uzNavM)">
                <path fill="#0099b5" d="M0 0h512v167H0z"/>
                <path fill="#fff" d="M0 178h512v156H0z"/>
                <path fill="#1eb53a" d="M0 345h512v167H0z"/>
                <path fill="#ce1126" d="M0 167h512v11H0zM0 334h512v11H0z"/>
                <circle cx="163" cy="89" r="45" fill="#fff"/>
                <circle cx="176" cy="89" r="40" fill="#0099b5"/>
              </g>
            </svg>
          </a>
          <a href="/lang/ru"
             class="flex items-center justify-center w-11 h-11 rounded-xl transition-all"
             :class="locale === 'ru' ? 'bg-indigo-50 ring-2 ring-indigo-500' : 'bg-slate-100 hover:bg-slate-200'">
            <svg class="w-7 h-7 rounded-full" viewBox="0 0 512 512">
              <mask id="ruNavM"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
              <g mask="url(#ruNavM)">
                <path fill="#fff" d="M0 0h512v170.7H0z"/>
                <path fill="#0039a6" d="M0 170.7h512v170.6H0z"/>
                <path fill="#d52b1e" d="M0 341.3h512V512H0z"/>
              </g>
            </svg>
          </a>
        </div>

        <div class="pt-3 border-t border-slate-200 space-y-3">
          <Link href="/login" class="block text-base font-medium text-slate-600 hover:text-slate-900">
            {{ nav.login }}
          </Link>
          <Link
            href="/register"
            class="block text-center px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700"
          >
            {{ nav.free_mobile }}
          </Link>
        </div>
      </div>
    </div>
  </nav>
</template>
