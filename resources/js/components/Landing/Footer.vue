<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <footer class="relative bg-gray-900 text-gray-400 overflow-hidden">
    <!-- Background decorations -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-blue-500 rounded-full filter blur-[100px] opacity-10"></div>
      <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-violet-500 rounded-full filter blur-[100px] opacity-10"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-12">
      <!-- Main footer content -->
      <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-12 mb-16">
        <!-- Brand -->
        <div class="lg:col-span-2">
          <Link href="/" class="flex items-center space-x-3 mb-6 group">
            <div class="relative">
              <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <div class="absolute inset-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl filter blur-lg opacity-50 group-hover:opacity-70 transition-opacity"></div>
            </div>
            <span class="text-2xl font-black text-white">BiznesPilot</span>
          </Link>
          <p class="text-gray-400 mb-8 max-w-sm leading-relaxed">
            Instagram va Telegram savdolarini avtomatlashtiruvchi yagona platforma. CRM, chatbot va AI tahlil bir joyda.
          </p>

          <!-- Social links -->
          <div class="flex space-x-3">
            <a href="https://t.me/biznespilot" target="_blank" class="group relative w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-[#0088cc] transition-all duration-300 overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-br from-[#0088cc] to-[#0077b5] opacity-0 group-hover:opacity-100 transition-opacity"></div>
              <svg class="relative w-5 h-5 text-gray-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
              </svg>
            </a>
            <a href="https://instagram.com/biznespilot" target="_blank" class="group relative w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-[#E4405F] transition-all duration-300 overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-br from-[#833AB4] via-[#E4405F] to-[#FCAF45] opacity-0 group-hover:opacity-100 transition-opacity"></div>
              <svg class="relative w-5 h-5 text-gray-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
              </svg>
            </a>
            <a href="https://youtube.com/@biznespilot" target="_blank" class="group relative w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-[#FF0000] transition-all duration-300 overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-br from-[#FF0000] to-[#CC0000] opacity-0 group-hover:opacity-100 transition-opacity"></div>
              <svg class="relative w-5 h-5 text-gray-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
              </svg>
            </a>
            <a href="https://linkedin.com/company/biznespilot" target="_blank" class="group relative w-11 h-11 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-[#0A66C2] transition-all duration-300 overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-br from-[#0A66C2] to-[#004182] opacity-0 group-hover:opacity-100 transition-opacity"></div>
              <svg class="relative w-5 h-5 text-gray-400 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
              </svg>
            </a>
          </div>
        </div>

        <!-- Product -->
        <div>
          <h4 class="text-white font-bold mb-6 flex items-center">
            <span class="w-8 h-0.5 bg-gradient-to-r from-blue-500 to-indigo-500 mr-3"></span>
            Mahsulot
          </h4>
          <ul class="space-y-4">
            <li>
              <Link href="/#features" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                Imkoniyatlar
              </Link>
            </li>
            <li>
              <Link href="/pricing" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                Narxlar
              </Link>
            </li>
            <li>
              <a href="#" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                Integratsiyalar
              </a>
            </li>
          </ul>
        </div>

        <!-- Company -->
        <div>
          <h4 class="text-white font-bold mb-6 flex items-center">
            <span class="w-8 h-0.5 bg-gradient-to-r from-emerald-500 to-teal-500 mr-3"></span>
            Kompaniya
          </h4>
          <ul class="space-y-4">
            <li>
              <Link href="/about" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                Biz haqimizda
              </Link>
            </li>
            <li>
              <Link href="/about#contact" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                Aloqa
              </Link>
            </li>
            <li>
              <a href="https://t.me/biznespilot" target="_blank" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                Vakansiyalar
              </a>
            </li>
          </ul>
        </div>

        <!-- Legal -->
        <div>
          <h4 class="text-white font-bold mb-6 flex items-center">
            <span class="w-8 h-0.5 bg-gradient-to-r from-violet-500 to-purple-500 mr-3"></span>
            Huquqiy
          </h4>
          <ul class="space-y-4">
            <li>
              <Link href="/privacy-policy" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-violet-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                Maxfiylik siyosati
              </Link>
            </li>
            <li>
              <Link href="/terms" class="group flex items-center text-gray-400 hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-600 group-hover:text-violet-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                Foydalanish shartlari
              </Link>
            </li>
          </ul>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="pt-8 border-t border-gray-800">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <p class="text-gray-500 text-sm">&copy; {{ new Date().getFullYear() }} BiznesPilot. Barcha huquqlar himoyalangan.</p>
          <div class="flex items-center gap-6">
            <div class="flex items-center gap-2 text-sm text-gray-500">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
              <span>O'zbekcha</span>
            </div>
            <div class="flex items-center gap-2 text-gray-500 text-sm">
              <span>Made with</span>
              <svg class="w-4 h-4 text-red-500 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>
              <span>in</span>
              <span class="flex items-center gap-1">
                <span class="w-4 h-3 rounded-sm overflow-hidden flex flex-col">
                  <span class="flex-1 bg-[#0099B5]"></span>
                  <span class="h-[2px] bg-[#CE1126]"></span>
                  <span class="flex-1 bg-white"></span>
                  <span class="h-[2px] bg-[#CE1126]"></span>
                  <span class="flex-1 bg-[#1EB53A]"></span>
                </span>
                <span class="font-medium text-white">Uzbekistan</span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>
