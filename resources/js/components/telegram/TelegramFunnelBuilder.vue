<template>
  <div class="h-[calc(100vh-8rem)] flex flex-col bg-gray-100 dark:bg-gray-900 -m-6 -mt-4">
    <!-- Top Toolbar -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Link
          :href="getRoute('telegram-funnels.funnels.index', bot.id)"
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
          <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </Link>
        <div>
          <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ funnel.name }}</h1>
          <p class="text-xs text-gray-500 dark:text-gray-400">Visual Funnel Builder</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <!-- Zoom Controls -->
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
          <button @click="zoomOut" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
            </svg>
          </button>
          <span class="text-xs font-medium w-12 text-center">{{ Math.round(zoom * 100) }}%</span>
          <button @click="zoomIn" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
          <button @click="resetZoom" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
          </button>
        </div>

        <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

        <!-- Preview button -->
        <button
          @click="openPreview"
          type="button"
          class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition-colors"
          title="Telegram ko'rinishi"
        >
          <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          Ko'rish
        </button>

        <!-- Test-run button -->
        <button
          @click="testRun"
          :disabled="isTestRunning"
          type="button"
          class="inline-flex items-center px-3 py-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-sm font-medium rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
          title="Botda sinab ko'rish"
        >
          <svg v-if="isTestRunning" class="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          <svg v-else class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
          </svg>
          Botda sinab ko'rish
        </button>

        <!-- Save indicator -->
        <div class="flex items-center text-xs font-medium" :class="isDirty ? 'text-amber-600' : 'text-emerald-600'">
          <template v-if="isSaving">
            <span>Saqlanmoqda...</span>
          </template>
          <template v-else-if="isDirty">
            <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
            </svg>
            <span>Saqlanmagan o'zgarishlar</span>
          </template>
          <template v-else-if="savedAt">
            <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>Saqlandi</span>
          </template>
        </div>

        <button
          @click="saveSteps"
          :disabled="isSaving"
          class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-lg transition-colors"
        >
          <svg v-if="isSaving" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          <svg v-else class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          {{ isSaving ? 'Saqlanmoqda...' : 'Saqlash' }}
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Left Sidebar - Node Types -->
      <div class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Elementlar</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Canvas'ga tortib tashlang</p>
        </div>

        <!-- Connection guide — how to wire nodes together -->
        <div class="mx-3 my-2 p-2.5 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg">
          <p class="text-[11px] font-semibold text-indigo-900 dark:text-indigo-200 mb-1.5 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Bog'lanish qanday ishlaydi
          </p>
          <ul class="text-[10.5px] text-indigo-700 dark:text-indigo-300 space-y-1 leading-snug">
            <li class="flex items-start gap-1.5">
              <span class="w-3.5 h-3.5 rounded-full bg-indigo-500 text-white text-[9px] font-bold flex items-center justify-center shrink-0 mt-0.5">+</span>
              <span>"<b>Keyingi</b>" (ko'k <b>+</b> ikon) — bosib, chiziqni keyingi blokka torting</span>
            </li>
            <li class="flex items-start gap-1.5">
              <span class="w-3.5 h-3.5 rounded-full border-2 border-gray-500 flex items-center justify-center shrink-0 mt-0.5"><span class="w-1 h-1 rounded-full bg-gray-500"></span></span>
              <span>"<b>Kelish</b>" (nishon) — chiziqni shu nuqtaga qo'yib qo'ying</span>
            </li>
          </ul>
        </div>

        <div class="flex-1 overflow-y-auto p-3 space-y-2">
          <!-- Triggers Section -->
          <div class="mb-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 px-1">Triggerlar</p>

            <!-- Start Node -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'start')"
              class="p-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow mb-2"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">Boshlash</p>
                  <p class="text-xs opacity-80">Funnel boshi</p>
                </div>
              </div>
            </div>

            <!-- Keyword Trigger Node -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'trigger_keyword')"
              class="p-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-purple-500/20 transition-all"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">Kalit so'z</p>
                  <p class="text-xs opacity-80">Xabarda topilganda</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions Section -->
          <div class="mb-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 px-1">Xabarlar</p>
          </div>

          <!-- Message Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'message')"
            class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-sm">Xabar</p>
                <p class="text-xs opacity-80">Matn yuborish</p>
              </div>
            </div>
          </div>

          <!-- Input Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'input')"
            class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-sm">Ma'lumot</p>
                <p class="text-xs opacity-80">Foydalanuvchidan so'rash</p>
              </div>
            </div>
          </div>

          <!-- Condition Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'condition')"
            class="p-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-sm">Shart</p>
                <p class="text-xs opacity-80">Shartli o'tish</p>
              </div>
            </div>
          </div>

          <!-- Action Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'action')"
            class="p-3 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-sm">Amal</p>
                <p class="text-xs opacity-80">Lid yaratish, webhook</p>
              </div>
            </div>
          </div>

          <!-- Subscribe Check Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'subscribe_check')"
            class="p-3 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-sm">Obuna tekshir</p>
                <p class="text-xs opacity-80">Kanal a'zoligini</p>
              </div>
            </div>
          </div>

          <!-- Quiz/Poll Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'quiz')"
            class="p-3 bg-gradient-to-r from-indigo-500 to-violet-500 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-sm">Savol/Quiz</p>
                <p class="text-xs opacity-80">Tanlov berish</p>
              </div>
            </div>
          </div>

          <!-- A/B Test Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'ab_test')"
            class="p-3 bg-gradient-to-r from-amber-500 to-yellow-500 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-sm">A/B Test</p>
                <p class="text-xs opacity-80">Tasodifiy bo'lish</p>
              </div>
            </div>
          </div>

          <!-- Tag Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'tag')"
            class="p-3 bg-gradient-to-r from-emerald-500 to-green-500 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-sm">Teg</p>
                <p class="text-xs opacity-80">Foydalanuvchini belgilash</p>
              </div>
            </div>
          </div>

          <!-- Delay Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'delay')"
            class="p-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-sm">Kutish</p>
                <p class="text-xs opacity-80">Vaqt kechiktirish</p>
              </div>
            </div>
          </div>

          <!-- End Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'end')"
            class="p-3 bg-gradient-to-r from-gray-700 to-gray-800 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                </svg>
              </div>
              <div>
                <p class="font-medium text-sm">Tugatish</p>
                <p class="text-xs opacity-80">Funnel tugashi</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Canvas Area -->
      <div
        ref="canvasContainer"
        class="flex-1 overflow-hidden relative bg-gray-50 dark:bg-gray-900"
        :class="{ 'canvas-connecting': interactionMode === 'connecting' }"
        @dragover.prevent
        @drop="onDrop"
        @mousedown="startPan"
        @mousemove="onPan"
        @mouseup="endPan"
        @mouseleave="endPan"
        @wheel="onWheel"
      >
        <!-- Grid Background -->
        <div
          class="absolute inset-0 pointer-events-none"
          :style="{
            backgroundImage: 'radial-gradient(circle, #d1d5db 1px, transparent 1px)',
            backgroundSize: `${20 * zoom}px ${20 * zoom}px`,
            backgroundPosition: `${panOffset.x}px ${panOffset.y}px`
          }"
        ></div>

        <!-- Canvas -->
        <div
          ref="canvas"
          class="absolute"
          :style="{
            transform: `translate(${panOffset.x}px, ${panOffset.y}px) scale(${zoom})`,
            transformOrigin: '0 0'
          }"
        >
          <!-- SVG for connections -->
          <svg
            class="absolute top-0 left-0 w-[5000px] h-[5000px]"
            @mouseup="cancelConnection"
            @mousemove="updateDrawingConnection"
          >
            <defs>
              <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                <polygon points="0 0, 10 3.5, 0 7" fill="#6366f1" />
              </marker>
              <marker id="arrowhead-green" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                <polygon points="0 0, 10 3.5, 0 7" fill="#22c55e" />
              </marker>
              <marker id="arrowhead-red" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                <polygon points="0 0, 10 3.5, 0 7" fill="#ef4444" />
              </marker>
            </defs>

            <!-- Regular connections -->
            <g v-for="connection in connections" :key="`${connection.from}-${connection.to}-${connection.type || 'default'}`">
              <path
                :d="getConnectionPath(connection)"
                fill="none"
                :stroke="getConnectionColor(connection)"
                stroke-width="2.5"
                :marker-end="`url(#${getArrowMarker(connection)})`"
                class="transition-all duration-200 cursor-pointer hover:stroke-opacity-70"
                @click.stop="deleteConnection(connection)"
              />
              <!-- Connection label for condition branches -->
              <text
                v-if="connection.type === 'true' || connection.type === 'false'"
                :x="getConnectionLabelPosition(connection).x"
                :y="getConnectionLabelPosition(connection).y"
                :fill="connection.type === 'true' ? '#22c55e' : '#ef4444'"
                font-size="11"
                font-weight="600"
                class="pointer-events-none select-none"
              >
                {{ connection.type === 'true' ? 'Ha' : 'Yo\'q' }}
              </text>
            </g>

            <!-- Drawing connection (live preview) -->
            <path
              v-if="drawingConnection"
              :d="getDrawingPath()"
              fill="none"
              :stroke="getDrawingConnectionColor()"
              stroke-width="2.5"
              stroke-dasharray="8,4"
              class="animate-pulse"
            />
          </svg>

          <!-- Nodes -->
          <div
            v-for="node in nodes"
            :key="node.id"
            :style="{
              position: 'absolute',
              left: `${node.x}px`,
              top: `${node.y}px`,
              width: '220px'
            }"
            @mousedown.stop="startDragNode($event, node)"
            @click.stop="selectNode(node)"
            :class="[
              'rounded-xl shadow-lg transition-all cursor-move',
              selectedNode?.id === node.id ? 'ring-2 ring-blue-500 ring-offset-2' : '',
              invalidNodeIds.has(node.id) ? 'funnel-node--invalid' : ''
            ]"
          >
            <!-- Node Header -->
            <div :class="['rounded-t-xl px-4 py-3 flex items-center justify-between', getNodeHeaderClass(node.type)]">
              <div class="flex items-center gap-2">
                <component :is="getNodeIcon(node.type)" class="w-5 h-5" />
                <span class="font-medium text-sm truncate">{{ node.name || getNodeLabel(node.type) }}</span>
              </div>
              <button
                @click.stop="deleteNode(node.id)"
                class="p-1 hover:bg-white/20 rounded opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Node Body -->
            <div class="bg-white dark:bg-gray-800 rounded-b-xl p-3">
              <!-- Trigger Keyword node content -->
              <template v-if="node.type === 'trigger_keyword'">
                <div class="text-xs space-y-1">
                  <div class="flex items-center gap-1 text-purple-600 dark:text-purple-400">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span v-if="node.trigger?.is_all_messages">Barcha xabarlar</span>
                    <span v-else-if="node.trigger?.keywords" class="truncate">{{ node.trigger.keywords }}</span>
                    <span v-else class="text-gray-400 italic">Kalit so'z kiriting</span>
                  </div>
                  <div v-if="node.trigger?.match_type && !node.trigger?.is_all_messages" class="text-gray-500 pl-4">
                    {{ getMatchTypeLabel(node.trigger.match_type) }}
                  </div>
                </div>
              </template>

              <!-- Regular node content (message, input, action, delay, start, end) -->
              <template v-if="!['condition', 'subscribe_check', 'quiz', 'ab_test', 'tag', 'trigger_keyword'].includes(node.type)">
                <p v-if="node.content?.text" class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                  {{ node.content.text }}
                </p>
                <p v-else class="text-xs text-gray-400 dark:text-gray-500 italic">
                  Matn kiriting...
                </p>
              </template>

              <!-- Condition node content -->
              <template v-if="node.type === 'condition'">
                <div class="text-xs space-y-1">
                  <div class="flex items-center gap-1 text-yellow-600 dark:text-yellow-400">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span v-if="node.condition?.field">{{ getFieldDisplayName(node.condition.field) }}</span>
                    <span v-else class="text-gray-400 italic">Maydon tanlang</span>
                  </div>
                  <div v-if="node.condition?.operator" class="text-gray-600 dark:text-gray-400 pl-4">
                    {{ getConditionOperatorLabel(node.condition.operator) }}
                    <span v-if="node.condition.value" class="font-medium text-gray-800 dark:text-gray-200">"{{ node.condition.value }}"</span>
                  </div>
                </div>
              </template>

              <!-- Subscribe Check node content -->
              <template v-if="node.type === 'subscribe_check'">
                <div class="text-xs space-y-1">
                  <div class="flex items-center gap-1 text-cyan-600 dark:text-cyan-400">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span v-if="node.subscribe_check?.channel_username">@{{ node.subscribe_check.channel_username }}</span>
                    <span v-else class="text-gray-400 italic">Kanal tanlang</span>
                  </div>
                </div>
              </template>

              <!-- Quiz node content -->
              <template v-if="node.type === 'quiz'">
                <div class="text-xs space-y-1">
                  <div class="flex items-center gap-1 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span v-if="node.quiz?.question" class="truncate">{{ node.quiz.question }}</span>
                    <span v-else class="text-gray-400 italic">Savol kiriting</span>
                  </div>
                  <div class="text-gray-500 pl-4">
                    {{ node.quiz?.options?.length || 0 }} ta variant
                  </div>
                </div>
              </template>

              <!-- A/B Test node content -->
              <template v-if="node.type === 'ab_test'">
                <div class="text-xs space-y-1">
                  <div class="flex items-center gap-1 text-amber-600 dark:text-amber-400">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span>{{ node.ab_test?.variants?.length || 2 }} ta variant</span>
                  </div>
                  <div class="flex gap-1 flex-wrap pl-4">
                    <span v-for="(v, i) in (node.ab_test?.variants || [])" :key="i"
                      class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 rounded text-[10px]">
                      {{ v.name }}: {{ v.percentage }}%
                    </span>
                  </div>
                </div>
              </template>

              <!-- Tag node content -->
              <template v-if="node.type === 'tag'">
                <div class="text-xs space-y-1">
                  <div class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>{{ node.tag?.action === 'add' ? 'Qo\'shish' : 'O\'chirish' }}</span>
                  </div>
                  <div class="flex gap-1 flex-wrap pl-4">
                    <span v-for="(tag, i) in (node.tag?.tags || [])" :key="i"
                      class="px-1.5 py-0.5 bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 rounded text-[10px]">
                      #{{ tag }}
                    </span>
                    <span v-if="!node.tag?.tags?.length" class="text-gray-400 italic">Teg tanlang</span>
                  </div>
                </div>
              </template>

              <!-- Input indicator -->
              <div v-if="node.type === 'input'" class="mt-2 flex items-center gap-1 text-xs text-purple-600">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                {{ getInputTypeLabel(node.input_type) }}
              </div>

              <!-- Action indicator -->
              <div v-if="node.type === 'action'" class="mt-2 flex items-center gap-1 text-xs text-red-600">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                {{ getActionTypeLabel(node.action_type) }}
              </div>

              <!-- Delay indicator -->
              <div v-if="node.type === 'delay'" class="mt-2 flex items-center gap-1 text-xs text-gray-600">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ node.delay_seconds || 5 }} soniya
              </div>

              <!-- Connection Points for Regular Nodes (message, input, action, delay, start, end, tag) -->
              <div v-if="!['condition', 'subscribe_check', 'quiz', 'ab_test'].includes(node.type)" class="flex justify-between items-end mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <!-- Input Port — "target dot" (receives connection) -->
                <div v-if="node.type !== 'start'" class="flex flex-col items-center gap-1">
                  <div
                    data-port="in"
                    class="funnel-port funnel-port-in"
                    :class="{ 'funnel-port--target': drawingConnection && drawingConnection.from !== node.id }"
                    @mousedown.stop.prevent
                    @mouseup.stop.prevent="endConnection(node.id)"
                    title="Kirish — oldingi qadamdan ulanish"
                  >
                    <!-- Target dot: outer ring + inner dot -->
                    <span class="funnel-port-icon-target"></span>
                  </div>
                  <span class="text-[9px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Kelish</span>
                </div>
                <div v-else class="w-8"></div>

                <!-- Output Port — "plus" (add next connection) -->
                <div v-if="node.type !== 'end'" class="flex flex-col items-center gap-1">
                  <div
                    data-port="out"
                    class="funnel-port funnel-port-out"
                    :class="{ 'funnel-port--source-active': drawingConnection && drawingConnection.from === node.id }"
                    @mousedown.stop.prevent="startConnection(node.id, $event, 'default')"
                    title="Keyingi qadamga ulash — tortib tashlang"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                    </svg>
                  </div>
                  <span class="text-[9px] font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wide">Keyingi</span>
                </div>
              </div>

              <!-- Connection Points for Condition Node (Two outputs: Ha/Yo'q) -->
              <div v-if="node.type === 'condition'" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="flex flex-col items-center mb-3">
                  <div
                    data-port="in"
                    class="funnel-port funnel-port-in"
                    :class="{ 'funnel-port--target': drawingConnection && drawingConnection.from !== node.id }"
                    @mousedown.stop.prevent
                    @mouseup.stop.prevent="endConnection(node.id)"
                    title="Kirish — oldingi qadamdan ulanish"
                  >
                    <span class="funnel-port-icon-target"></span>
                  </div>
                  <span class="text-[9px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-1">Kelish</span>
                </div>
                <div class="flex justify-between items-center">
                  <div class="flex flex-col items-center gap-1">
                    <div
                      data-port="out"
                      class="funnel-port funnel-port-branch funnel-port-branch--yes"
                      @mousedown.stop.prevent="startConnection(node.id, $event, 'true')"
                      title="Ha - shart bajarilsa"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="text-[10px] font-medium text-green-600 dark:text-green-400">Ha</span>
                  </div>
                  <div class="flex flex-col items-center gap-1">
                    <div
                      data-port="out"
                      class="funnel-port funnel-port-branch funnel-port-branch--no"
                      @mousedown.stop.prevent="startConnection(node.id, $event, 'false')"
                      title="Yo'q - shart bajarilmasa"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <span class="text-[10px] font-medium text-red-600 dark:text-red-400">Yo'q</span>
                  </div>
                </div>
              </div>

              <!-- Connection Points for Subscribe Check Node (Two outputs: Obuna/Obuna emas) -->
              <div v-if="node.type === 'subscribe_check'" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="flex justify-center mb-3">
                  <div
                    data-port="in"
                    class="funnel-port funnel-port-in"
                    :class="{ 'funnel-port--target': drawingConnection && drawingConnection.from !== node.id }"
                    @mousedown.stop.prevent
                    @mouseup.stop.prevent="endConnection(node.id)"
                    title="Kirish nuqtasi"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                  </div>
                </div>
                <div class="flex justify-between items-center">
                  <div class="flex flex-col items-center gap-1">
                    <div
                      data-port="out"
                      class="funnel-port funnel-port-branch funnel-port-branch--cyan"
                      @mousedown.stop.prevent="startConnection(node.id, $event, 'subscribed')"
                      title="Obuna bo'lgan"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="text-[10px] font-medium text-cyan-600 dark:text-cyan-400">Obuna</span>
                  </div>
                  <div class="flex flex-col items-center gap-1">
                    <div
                      data-port="out"
                      class="funnel-port funnel-port-branch funnel-port-branch--no"
                      @mousedown.stop.prevent="startConnection(node.id, $event, 'not_subscribed')"
                      title="Obuna bo'lmagan"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <span class="text-[10px] font-medium text-red-600 dark:text-red-400">Yo'q</span>
                  </div>
                </div>
              </div>

              <!-- Connection Points for Quiz Node (Multiple outputs based on options) -->
              <div v-if="node.type === 'quiz'" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="flex justify-center mb-3">
                  <div
                    data-port="in"
                    class="funnel-port funnel-port-in"
                    :class="{ 'funnel-port--target': drawingConnection && drawingConnection.from !== node.id }"
                    @mousedown.stop.prevent
                    @mouseup.stop.prevent="endConnection(node.id)"
                    title="Kirish nuqtasi"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                  </div>
                </div>
                <div class="flex flex-wrap justify-center gap-2">
                  <div v-for="(option, i) in (node.quiz?.options || [])" :key="i" class="flex flex-col items-center gap-1">
                    <div
                      data-port="out"
                      class="funnel-port funnel-port-option"
                      @mousedown.stop.prevent="startConnection(node.id, $event, `option_${i}`)"
                      :title="`Javob: ${option.text || (i + 1)}`"
                    >
                      {{ i + 1 }}
                    </div>
                    <span class="text-[10px] font-medium text-indigo-600 dark:text-indigo-400 max-w-[40px] truncate">{{ option.text || `#${i + 1}` }}</span>
                  </div>
                </div>
              </div>

              <!-- Connection Points for A/B Test Node (Multiple outputs based on variants) -->
              <div v-if="node.type === 'ab_test'" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="flex justify-center mb-3">
                  <div
                    data-port="in"
                    class="funnel-port funnel-port-in"
                    :class="{ 'funnel-port--target': drawingConnection && drawingConnection.from !== node.id }"
                    @mousedown.stop.prevent
                    @mouseup.stop.prevent="endConnection(node.id)"
                    title="Kirish nuqtasi"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                  </div>
                </div>
                <div class="flex justify-center gap-3">
                  <div v-for="(variant, i) in (node.ab_test?.variants || [])" :key="i" class="flex flex-col items-center gap-1">
                    <div
                      data-port="out"
                      class="funnel-port funnel-port-variant"
                      @mousedown.stop.prevent="startConnection(node.id, $event, `variant_${i}`)"
                      :title="`${variant.name}: ${variant.percentage}%`"
                    >
                      {{ variant.name }}
                    </div>
                    <span class="text-[10px] font-medium text-amber-600 dark:text-amber-400">{{ variant.percentage }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-if="nodes.length === 0"
          class="absolute inset-0 flex items-center justify-center pointer-events-none"
        >
          <div class="text-center">
            <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Funnel bo'sh</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Chap paneldan elementlarni tortib tashlang</p>
          </div>
        </div>
      </div>

      <!-- Right Sidebar - Node Editor -->
      <div class="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-semibold text-gray-900 dark:text-white text-sm">
            {{ selectedNode ? 'Qadam tahrirlash' : 'Qadam tanlang' }}
          </h3>
        </div>

        <div v-if="!selectedNode" class="flex-1 flex items-center justify-center p-4">
          <div class="text-center text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
            </svg>
            <p class="text-sm">Canvas'dan qadam tanlang</p>
          </div>
        </div>

        <div v-else class="flex-1 overflow-y-auto p-4 space-y-4">
          <!-- Node Name -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Qadam nomi</label>
            <input
              v-model="selectedNode.name"
              type="text"
              class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
              placeholder="Qadam nomini kiriting..."
            />
          </div>

          <!-- End node: funnel-level completion_message editor -->
          <div v-if="selectedNode.type === 'end'" class="p-3 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg space-y-2">
            <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Funnel yakuni</p>
            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
              Tugatish bloki foydalanuvchi funnel so'nggi qadamga yetganda ko'ringan xabar — saqlashda alohida qadam yaratilmaydi, funnel'ning yakuniy matni sifatida ishlatiladi.
            </p>
            <div>
              <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Yakuniy xabar</label>
              <textarea
                v-model="funnelCompletionMessage"
                rows="3"
                placeholder="Rahmat! Siz bizning funnel'imizni yakunladingiz."
                class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded"
              ></textarea>
              <p class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">Placeholder'lar: `{first_name}`, `{phone}`, va boshqa collected_data kalitlari.</p>
            </div>
          </div>

          <!-- Trigger Keyword Settings -->
          <div v-if="selectedNode.type === 'trigger_keyword'" class="space-y-3">
            <div class="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
              <div class="flex items-center gap-2 text-purple-700 dark:text-purple-400 mb-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-xs font-medium">Kalit so'z triggeri</span>
              </div>
              <p class="text-xs text-purple-600 dark:text-purple-400">
                Foydalanuvchi xabarida kalit so'z topilganda bu trigger ishga tushadi.
              </p>
            </div>

            <!-- All Messages Toggle -->
            <div class="flex items-center justify-between">
              <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Barcha xabarlar uchun</label>
              <button
                @click="selectedNode.trigger.is_all_messages = !selectedNode.trigger.is_all_messages"
                :class="[
                  'relative w-11 h-6 rounded-full transition-colors',
                  selectedNode.trigger?.is_all_messages ? 'bg-purple-500' : 'bg-gray-300 dark:bg-gray-600'
                ]"
              >
                <span
                  :class="[
                    'absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform',
                    selectedNode.trigger?.is_all_messages ? 'translate-x-5' : 'translate-x-0'
                  ]"
                ></span>
              </button>
            </div>

            <!-- Keywords Input (shown if not all messages) -->
            <div v-if="!selectedNode.trigger?.is_all_messages">
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Kalit so'zlar</label>
              <input
                v-model="selectedNode.trigger.keywords"
                type="text"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="narx, price, baho"
              />
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Vergul bilan ajrating: narx, price, baho</p>
            </div>

            <!-- Match Type (shown if not all messages) -->
            <div v-if="!selectedNode.trigger?.is_all_messages">
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Mos kelish turi</label>
              <select
                v-model="selectedNode.trigger.match_type"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
              >
                <option value="contains">Ichida bor (tavsiya etiladi)</option>
                <option value="exact">To'liq mos</option>
                <option value="starts_with">Bilan boshlanadi</option>
                <option value="ends_with">Bilan tugaydi</option>
                <option value="regex">Regex (ilg'or)</option>
              </select>
            </div>
          </div>

          <!-- Content Type Selection -->
          <div v-if="['message', 'input', 'start'].includes(selectedNode.type)">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Kontent turi</label>
            <div class="grid grid-cols-4 gap-1">
              <button
                v-for="cType in contentTypes"
                :key="cType.value"
                @click="setContentType(cType.value)"
                :class="[
                  'p-2 rounded-lg text-center transition-colors',
                  (selectedNode.content?.type || 'text') === cType.value
                    ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 ring-1 ring-blue-500'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]"
              >
                <div class="flex flex-col items-center gap-1">
                  <span v-html="cType.icon" class="w-5 h-5"></span>
                  <span class="text-[10px]">{{ cType.label }}</span>
                </div>
              </button>
            </div>
          </div>

          <!-- Text Content -->
          <div v-if="['message', 'input', 'start'].includes(selectedNode.type) && (selectedNode.content?.type || 'text') === 'text'">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Xabar matni</label>
            <textarea
              v-model="selectedNode.content.text"
              rows="4"
              class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none placeholder-gray-400 dark:placeholder-gray-500"
              placeholder="Xabar matni kiriting..."
            ></textarea>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              O'zgaruvchilar: {first_name}, {last_name}, {phone}, {username}
            </p>
          </div>

          <!-- Input Type -->
          <div v-if="selectedNode.type === 'input'" class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Kutilayotgan ma'lumot turi</label>
              <select
                v-model="selectedNode.input_type"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="none">Hech biri</option>
                <option value="text">Matn</option>
                <option value="email">Email</option>
                <option value="phone">Telefon raqam</option>
                <option value="number">Raqam</option>
                <option value="location">Lokatsiya</option>
                <option value="photo">Rasm</option>
                <option value="any">Istalgan</option>
              </select>
            </div>

            <!-- Input Field Key -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Saqlash kaliti
                <span class="text-gray-400 dark:text-gray-500 font-normal">(collected_data[kalit])</span>
              </label>
              <input
                v-model.trim="selectedNode.input_field"
                type="text"
                placeholder="masalan: name, phone, age"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
              />
              <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                Kiritilgan qiymat shu kalit ostida saqlanadi va `{kalit}` placeholder'i sifatida keyingi xabarlarda ishlatiladi.
              </p>
            </div>

            <!-- Validation + Retry -->
            <div class="pt-2 border-t border-gray-200 dark:border-gray-700 space-y-2">
              <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Tekshiruv va qayta urinish</p>

              <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                <input
                  type="checkbox"
                  :checked="!!selectedNode.validation?.required"
                  @change="ensureValidation(); selectedNode.validation.required = $event.target.checked"
                  class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                />
                Majburiy maydon
              </label>

              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Min uzunlik</label>
                  <input
                    :value="selectedNode.validation?.min_length ?? ''"
                    @input="ensureValidation(); selectedNode.validation.min_length = $event.target.value === '' ? null : Number($event.target.value)"
                    type="number"
                    min="0"
                    class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded"
                  />
                </div>
                <div>
                  <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Max uzunlik</label>
                  <input
                    :value="selectedNode.validation?.max_length ?? ''"
                    @input="ensureValidation(); selectedNode.validation.max_length = $event.target.value === '' ? null : Number($event.target.value)"
                    type="number"
                    min="0"
                    class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded"
                  />
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Regex pattern (ixtiyoriy)</label>
                <input
                  :value="selectedNode.validation?.pattern ?? ''"
                  @input="ensureValidation(); selectedNode.validation.pattern = $event.target.value || null"
                  type="text"
                  placeholder="^[A-Za-z0-9]+$"
                  class="w-full px-2 py-1 text-xs font-mono text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded"
                />
              </div>

              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Xatolik xabari</label>
                <input
                  :value="selectedNode.validation?.error_message ?? ''"
                  @input="ensureValidation(); selectedNode.validation.error_message = $event.target.value || null"
                  type="text"
                  placeholder="Noto'g'ri kiritish, qayta urinib ko'ring"
                  class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded"
                />
              </div>

              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Maksimum qayta urinish</label>
                <input
                  :value="selectedNode.validation?.retry_count ?? ''"
                  @input="ensureValidation(); selectedNode.validation.retry_count = $event.target.value === '' ? null : Number($event.target.value)"
                  type="number"
                  min="0"
                  max="10"
                  placeholder="3"
                  class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded"
                />
                <p class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">Bo'sh — cheksiz qayta urinish.</p>
              </div>
            </div>
          </div>

          <!-- Action Type -->
          <div v-if="selectedNode.type === 'action'" class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Amal turi</label>
              <select
                v-model="selectedNode.action_type"
                @change="ensureActionConfig()"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="none">Hech narsa</option>
                <option value="create_lead">Lid yaratish</option>
                <option value="update_user">Foydalanuvchini yangilash</option>
                <option value="handoff">Operatorga uzatish</option>
                <option value="send_notification">Xabarnoma yuborish</option>
                <option value="webhook">Webhook</option>
              </select>
            </div>

            <!-- create_lead config -->
            <div v-if="selectedNode.action_type === 'create_lead'" class="pt-2 border-t border-gray-200 dark:border-gray-700 space-y-2">
              <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Lid sozlamalari</p>
              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Ism (collected_data kaliti)</label>
                <input :value="selectedNode.action_config?.name_field ?? ''" @input="ensureActionConfig(); selectedNode.action_config.name_field = $event.target.value || null"
                  type="text" placeholder="name"
                  class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded" />
              </div>
              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Telefon kaliti</label>
                <input :value="selectedNode.action_config?.phone_field ?? ''" @input="ensureActionConfig(); selectedNode.action_config.phone_field = $event.target.value || null"
                  type="text" placeholder="phone"
                  class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded" />
              </div>
              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Email kaliti</label>
                <input :value="selectedNode.action_config?.email_field ?? ''" @input="ensureActionConfig(); selectedNode.action_config.email_field = $event.target.value || null"
                  type="text" placeholder="email"
                  class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded" />
              </div>
            </div>

            <!-- update_user config -->
            <div v-if="selectedNode.action_type === 'update_user'" class="pt-2 border-t border-gray-200 dark:border-gray-700 space-y-2">
              <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Foydalanuvchi maydonlarini yangilash</p>
              <p class="text-[10px] text-gray-500 dark:text-gray-400">collected_data'dagi qiymatlarni TelegramUser'ga ko'chiradi. `phone`, `email`, `first_name`, `last_name`, `custom_data.*` maydonlarini qo'llab-quvvatlaydi.</p>
            </div>

            <!-- handoff config -->
            <div v-if="selectedNode.action_type === 'handoff'" class="pt-2 border-t border-gray-200 dark:border-gray-700 space-y-2">
              <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Operatorga uzatish</p>
              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Sabab (ixtiyoriy)</label>
                <input :value="selectedNode.action_config?.reason ?? ''" @input="ensureActionConfig(); selectedNode.action_config.reason = $event.target.value || null"
                  type="text" placeholder="Operator yordami kerak"
                  class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded" />
              </div>
            </div>

            <!-- send_notification config -->
            <div v-if="selectedNode.action_type === 'send_notification'" class="pt-2 border-t border-gray-200 dark:border-gray-700 space-y-2">
              <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Xabarnoma operatorlarga</p>
              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Xabar matni</label>
                <textarea :value="selectedNode.action_config?.message ?? ''" @input="ensureActionConfig(); selectedNode.action_config.message = $event.target.value || null"
                  rows="3" placeholder="Yangi lid: {first_name}, telefon: {phone}"
                  class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded"></textarea>
                <p class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">`{first_name}`, `{phone}` va boshqa collected_data kalitlarini ishlatish mumkin.</p>
              </div>
              <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                <input type="checkbox"
                  :checked="selectedNode.action_config?.include_collected_data !== false"
                  @change="ensureActionConfig(); selectedNode.action_config.include_collected_data = $event.target.checked"
                  class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" />
                Barcha kiritilgan ma'lumotlarni qo'shish
              </label>
            </div>

            <!-- webhook config -->
            <div v-if="selectedNode.action_type === 'webhook'" class="pt-2 border-t border-gray-200 dark:border-gray-700 space-y-2">
              <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Webhook (HTTP POST)</p>
              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">URL (https://)</label>
                <input :value="selectedNode.action_config?.url ?? ''" @input="ensureActionConfig(); selectedNode.action_config.url = $event.target.value || null"
                  type="url" placeholder="https://example.com/webhook"
                  class="w-full px-2 py-1 text-xs font-mono text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded" />
              </div>
              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Method</label>
                <select :value="selectedNode.action_config?.method ?? 'POST'" @change="ensureActionConfig(); selectedNode.action_config.method = $event.target.value"
                  class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded">
                  <option value="POST">POST</option>
                  <option value="PUT">PUT</option>
                  <option value="PATCH">PATCH</option>
                </select>
              </div>
              <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">HMAC Secret (ixtiyoriy)</label>
                <input :value="selectedNode.action_config?.secret ?? ''" @input="ensureActionConfig(); selectedNode.action_config.secret = $event.target.value || null"
                  type="password" autocomplete="new-password" placeholder="Qabul qiluvchi server bilan umumiy sir"
                  class="w-full px-2 py-1 text-xs font-mono text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded" />
                <p class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                  So'rov bilan birga `X-BiznesPilot-Signature: sha256=&lt;hex&gt;` header yuboriladi. Saqlashda shifrlangan holda yoziladi.
                </p>
              </div>
              <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                <input type="checkbox"
                  :checked="selectedNode.action_config?.include_user !== false"
                  @change="ensureActionConfig(); selectedNode.action_config.include_user = $event.target.checked"
                  class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" />
                Foydalanuvchi ma'lumotlarini qo'shish
              </label>
            </div>
          </div>

          <!-- Delay Settings -->
          <div v-if="selectedNode.type === 'delay'">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Kutish vaqti (soniya)</label>
            <input
              v-model.number="selectedNode.delay_seconds"
              type="number"
              min="1"
              class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
              placeholder="5"
            />
          </div>

          <!-- ============================================================ -->
          <!-- Media Uploader (for message/input/start when type != text) -->
          <!-- ============================================================ -->
          <div
            v-if="['message', 'input', 'start'].includes(selectedNode.type)
              && ['photo', 'video', 'document', 'voice', 'video_note'].includes(selectedNode.content?.type)"
            class="p-3 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg space-y-3"
          >
            <div class="flex items-center justify-between">
              <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                {{ getMediaLabel(selectedNode.content.type) }} fayli
              </label>
              <span class="text-[10px] text-gray-400">{{ selectedNode.content.type }}</span>
            </div>

            <!-- Preview -->
            <div v-if="selectedNode.content.url || selectedNode.content.file_id" class="space-y-2">
              <div class="relative">
                <img
                  v-if="selectedNode.content.type === 'photo' && selectedNode.content.url"
                  :src="selectedNode.content.url"
                  class="w-full h-32 object-cover rounded-lg bg-gray-200 dark:bg-gray-700"
                  alt="Preview"
                />
                <video
                  v-else-if="['video', 'video_note'].includes(selectedNode.content.type) && selectedNode.content.url"
                  :src="selectedNode.content.url"
                  controls
                  class="w-full h-32 object-cover rounded-lg bg-black"
                ></video>
                <audio
                  v-else-if="selectedNode.content.type === 'voice' && selectedNode.content.url"
                  :src="selectedNode.content.url"
                  controls
                  class="w-full"
                ></audio>
                <div
                  v-else
                  class="flex items-center gap-2 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                >
                  <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2v6h6"/>
                  </svg>
                  <span class="text-xs text-gray-600 dark:text-gray-400 truncate">
                    {{ selectedNode.content.__local_filename || selectedNode.content.file_id || 'Fayl tanlangan' }}
                  </span>
                </div>
              </div>
              <button
                @click="clearMedia"
                class="w-full px-3 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-medium rounded-lg transition-colors"
              >
                O'chirish
              </button>
            </div>

            <!-- Upload button -->
            <label
              class="flex items-center justify-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-medium rounded-lg cursor-pointer transition-colors"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
              </svg>
              <span>Fayl tanlash</span>
              <input
                type="file"
                class="hidden"
                :accept="selectedNode.content.type === 'photo' ? 'image/*' : (selectedNode.content.type === 'video' || selectedNode.content.type === 'video_note') ? 'video/*' : selectedNode.content.type === 'voice' ? 'audio/*' : '*/*'"
                @change="onMediaUpload"
              />
            </label>

            <!-- File ID (Telegram pre-uploaded media) -->
            <div>
              <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400 mb-1">
                yoki Telegram File ID
              </label>
              <input
                v-model="selectedNode.content.file_id"
                type="text"
                class="w-full px-3 py-1.5 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="AgACAgIAAxkBAA..."
              />
            </div>

            <!-- Caption -->
            <div>
              <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400 mb-1">
                Izoh (caption, ixtiyoriy)
              </label>
              <textarea
                v-model="selectedNode.content.caption"
                rows="2"
                maxlength="1024"
                class="w-full px-3 py-1.5 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="Fayl uchun izoh..."
              ></textarea>
              <p class="text-[10px] text-gray-400 mt-0.5 text-right">
                {{ (selectedNode.content.caption || '').length }}/1024
              </p>
            </div>

            <!-- Parse mode -->
            <div>
              <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400 mb-1">
                Parse rejimi
              </label>
              <select
                v-model="selectedNode.content.parse_mode"
                class="w-full px-3 py-1.5 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option :value="null">Oddiy (none)</option>
                <option value="HTML">HTML</option>
                <option value="Markdown">Markdown</option>
                <option value="MarkdownV2">MarkdownV2</option>
              </select>
            </div>
          </div>

          <!-- ============================================================ -->
          <!-- Condition Editor -->
          <!-- ============================================================ -->
          <div v-if="selectedNode.type === 'condition'" class="space-y-3">
            <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
              <div class="flex items-center gap-2 text-yellow-700 dark:text-yellow-400 mb-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-xs font-medium">Shart (condition)</span>
              </div>
              <p class="text-xs text-yellow-700 dark:text-yellow-400">
                Foydalanuvchi ma'lumotlari yoki to'plangan javoblar asosida shart tekshiriladi.
              </p>
            </div>

            <!-- Field -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Maydon</label>
              <select
                v-model="selectedNode.condition.field"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
              >
                <option value="">-- maydon tanlang --</option>
                <optgroup v-for="group in conditionFieldOptions" :key="group.group" :label="group.group">
                  <option v-for="opt in group.options" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </optgroup>
              </select>
              <p v-if="!selectedNode.condition.field" class="text-xs text-red-500 mt-1">Maydon tanlanishi shart</p>
            </div>

            <!-- Custom field name (when field === custom_field) -->
            <div v-if="selectedNode.condition.field === 'custom_field'">
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Maxsus maydon kaliti</label>
              <input
                v-model="selectedNode.condition.custom_field"
                type="text"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="masalan: quiz_answer yoki custom_field_1"
              />
            </div>

            <!-- Operator -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Amal (operator)</label>
              <select
                v-model="selectedNode.condition.operator"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
              >
                <option value="">-- operator tanlang --</option>
                <option v-for="op in conditionOperators" :key="op.value" :value="op.value">{{ op.label }}</option>
              </select>
              <p v-if="!selectedNode.condition.operator" class="text-xs text-red-500 mt-1">Operator tanlanishi shart</p>
            </div>

            <!-- Value (hidden when operator doesn't need it) -->
            <div v-if="selectedNode.condition.operator && !operatorsWithoutValue.includes(selectedNode.condition.operator)">
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Qiymat</label>
              <input
                v-model="selectedNode.condition.value"
                type="text"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="taqqoslash uchun qiymat..."
              />
            </div>

            <!-- Ha (true) branch -->
            <div>
              <label class="block text-xs font-medium text-green-700 dark:text-green-400 mb-1">
                "Ha" — shart bajarilsa
              </label>
              <select
                v-model="selectedNode.condition_true_step_id"
                @change="updateConditionBranchConnection('true')"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-green-300 dark:border-green-700 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              >
                <option :value="null">-- tanlanmagan --</option>
                <option
                  v-for="n in nodes.filter(n => n.id !== selectedNode.id)"
                  :key="n.id"
                  :value="n.id"
                >
                  {{ n.name || getNodeLabel(n.type) }}
                </option>
              </select>
            </div>

            <!-- Yo'q (false) branch -->
            <div>
              <label class="block text-xs font-medium text-red-700 dark:text-red-400 mb-1">
                "Yo'q" — shart bajarilmasa
              </label>
              <select
                v-model="selectedNode.condition_false_step_id"
                @change="updateConditionBranchConnection('false')"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-red-300 dark:border-red-700 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
              >
                <option :value="null">-- tanlanmagan --</option>
                <option
                  v-for="n in nodes.filter(n => n.id !== selectedNode.id)"
                  :key="n.id"
                  :value="n.id"
                >
                  {{ n.name || getNodeLabel(n.type) }}
                </option>
              </select>
            </div>
          </div>

          <!-- ============================================================ -->
          <!-- Quiz Editor -->
          <!-- ============================================================ -->
          <div v-if="selectedNode.type === 'quiz'" class="space-y-3">
            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg">
              <div class="flex items-center gap-2 text-indigo-700 dark:text-indigo-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
                </svg>
                <span class="text-xs font-medium">Savol/Quiz</span>
              </div>
            </div>

            <!-- Question -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Savol matni</label>
              <textarea
                v-model="selectedNode.quiz.question"
                rows="3"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="Savolni kiriting..."
              ></textarea>
              <p v-if="!selectedNode.quiz.question?.trim()" class="text-xs text-red-500 mt-1">Savol matni bo'sh bo'lmasligi kerak</p>
            </div>

            <!-- Save answer to -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Javobni saqlash (kalit)</label>
              <input
                v-model="selectedNode.quiz.save_answer_to"
                type="text"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="quiz_answer"
              />
              <p class="text-[10px] text-gray-500 mt-1">Collected_data ga saqlanadigan kalit nomi</p>
            </div>

            <!-- Correct-answer rejimi -->
            <div class="pt-2 border-t border-gray-200 dark:border-gray-700 space-y-2">
              <div class="flex items-center justify-between">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">To'g'ri javob rejimi</label>
                <button
                  type="button"
                  @click="toggleQuizCorrectMode()"
                  :class="[
                    'relative w-11 h-6 rounded-full transition-colors',
                    hasQuizCorrectMode ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'
                  ]"
                >
                  <span :class="[
                    'absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform',
                    hasQuizCorrectMode ? 'translate-x-5' : 'translate-x-0'
                  ]"></span>
                </button>
              </div>
              <p class="text-[10px] text-gray-500 dark:text-gray-400">
                Yoqilsa — javob to'g'riligi tekshiriladi, ball yig'iladi va to'g'ri/noto'g'ri uchun alohida step ochiladi.
              </p>

              <template v-if="hasQuizCorrectMode">
                <div>
                  <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">To'g'ri javob raqami (0..{{ (selectedNode.quiz.options?.length || 1) - 1 }})</label>
                  <input
                    :value="selectedNode.quiz.correct_option_index ?? 0"
                    @input="selectedNode.quiz.correct_option_index = Number($event.target.value)"
                    type="number" min="0" :max="(selectedNode.quiz.options?.length || 1) - 1"
                    class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded" />
                </div>
                <div>
                  <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-400 mb-0.5">Ball (to'g'ri javob uchun)</label>
                  <input
                    :value="selectedNode.quiz.score_on_correct ?? 1"
                    @input="selectedNode.quiz.score_on_correct = Number($event.target.value)"
                    type="number" min="0"
                    class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-[10px] font-medium text-emerald-700 dark:text-emerald-400 mb-0.5">To'g'ri → step</label>
                    <select
                      :value="selectedNode.quiz.correct_step_id || ''"
                      @change="selectedNode.quiz.correct_step_id = $event.target.value || null"
                      class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded">
                      <option value="">— Avtomatik</option>
                      <option v-for="n in nodes.filter(x => x.id !== selectedNode.id)" :key="n.id" :value="n.id">
                        {{ n.name || n.id }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-[10px] font-medium text-red-700 dark:text-red-400 mb-0.5">Noto'g'ri → step</label>
                    <select
                      :value="selectedNode.quiz.wrong_step_id || ''"
                      @change="selectedNode.quiz.wrong_step_id = $event.target.value || null"
                      class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded">
                      <option value="">— Avtomatik</option>
                      <option v-for="n in nodes.filter(x => x.id !== selectedNode.id)" :key="n.id" :value="n.id">
                        {{ n.name || n.id }}
                      </option>
                    </select>
                  </div>
                </div>
              </template>
            </div>

            <!-- Options -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                  Variantlar ({{ selectedNode.quiz.options?.length || 0 }}/10)
                </label>
                <button
                  type="button"
                  @click="addQuizOption"
                  :disabled="(selectedNode.quiz.options?.length || 0) >= 10"
                  class="px-2 py-1 text-xs bg-indigo-100 hover:bg-indigo-200 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400 rounded disabled:opacity-50"
                >
                  + Qo'shish
                </button>
              </div>

              <p v-if="(selectedNode.quiz.options?.length || 0) < 2" class="text-xs text-red-500 mb-2">
                Kamida 2 ta variant bo'lishi kerak
              </p>

              <div class="space-y-2">
                <div
                  v-for="(option, i) in selectedNode.quiz.options"
                  :key="i"
                  class="p-2 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg space-y-1.5"
                >
                  <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold w-5 h-5 flex items-center justify-center bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400 rounded">
                      {{ i + 1 }}
                    </span>
                    <input
                      v-model="option.text"
                      type="text"
                      class="flex-1 px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 placeholder-gray-400 dark:placeholder-gray-500"
                      placeholder="Variant matni"
                    />
                    <button
                      type="button"
                      @click="removeQuizOption(i)"
                      :disabled="(selectedNode.quiz.options?.length || 0) <= 2"
                      class="p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded disabled:opacity-30 disabled:cursor-not-allowed"
                      title="O'chirish"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                  <select
                    v-model="option.next_step_id"
                    @change="updateQuizConnection(i)"
                    class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option :value="null">-- keyingi qadam --</option>
                    <option
                      v-for="n in nodes.filter(n => n.id !== selectedNode.id)"
                      :key="n.id"
                      :value="n.id"
                    >
                      {{ n.name || getNodeLabel(n.type) }}
                    </option>
                  </select>
                  <p v-if="!option.text?.trim()" class="text-[10px] text-red-500">Matn bo'sh</p>
                </div>
              </div>
            </div>
          </div>

          <!-- ============================================================ -->
          <!-- A/B Test Editor -->
          <!-- ============================================================ -->
          <div v-if="selectedNode.type === 'ab_test'" class="space-y-3">
            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
              <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span class="text-xs font-medium">A/B Test — tasodifiy bo'lish</span>
              </div>
            </div>

            <!-- Variants -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                  Variantlar ({{ selectedNode.ab_test.variants?.length || 0 }}/5)
                </label>
                <div class="flex gap-1">
                  <button
                    type="button"
                    @click="redistributePercentages"
                    class="px-2 py-1 text-[10px] bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 rounded"
                    title="Ulushlarni teng taqsimlash"
                  >
                    Teng
                  </button>
                  <button
                    type="button"
                    @click="addABVariant"
                    :disabled="(selectedNode.ab_test.variants?.length || 0) >= 5"
                    class="px-2 py-1 text-xs bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 text-amber-700 dark:text-amber-400 rounded disabled:opacity-50"
                  >
                    + Qo'shish
                  </button>
                </div>
              </div>

              <!-- Sum indicator -->
              <div
                :class="[
                  'text-xs font-medium mb-2 px-2 py-1 rounded',
                  getTotalPercentage() === 100
                    ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400'
                    : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'
                ]"
              >
                Umumiy: {{ getTotalPercentage() }}%
                <span v-if="getTotalPercentage() !== 100"> — 100% bo'lishi shart!</span>
              </div>

              <div class="space-y-2">
                <div
                  v-for="(variant, i) in selectedNode.ab_test.variants"
                  :key="i"
                  class="p-2 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg space-y-1.5"
                >
                  <div class="flex items-center gap-1.5">
                    <input
                      v-model="variant.name"
                      type="text"
                      maxlength="10"
                      class="w-14 px-2 py-1 text-xs font-medium text-center text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                      placeholder="A"
                    />
                    <input
                      v-model.number="variant.percentage"
                      type="number"
                      min="1"
                      max="100"
                      class="w-16 px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                    />
                    <span class="text-xs text-gray-500">%</span>
                    <button
                      type="button"
                      @click="removeABVariant(i)"
                      :disabled="(selectedNode.ab_test.variants?.length || 0) <= 2"
                      class="ml-auto p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded disabled:opacity-30 disabled:cursor-not-allowed"
                      title="O'chirish"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                  <select
                    v-model="variant.next_step_id"
                    @change="updateABConnection(i)"
                    class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                  >
                    <option :value="null">-- keyingi qadam --</option>
                    <option
                      v-for="n in nodes.filter(n => n.id !== selectedNode.id)"
                      :key="n.id"
                      :value="n.id"
                    >
                      {{ n.name || getNodeLabel(n.type) }}
                    </option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- ============================================================ -->
          <!-- Tag Editor -->
          <!-- ============================================================ -->
          <div v-if="selectedNode.type === 'tag'" class="space-y-3">
            <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
              <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span class="text-xs font-medium">Teglar</span>
              </div>
            </div>

            <!-- Action type -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Amal</label>
              <div class="grid grid-cols-2 gap-1">
                <button
                  type="button"
                  @click="selectedNode.tag.action = 'add'"
                  :class="[
                    'p-2 rounded-lg text-xs font-medium transition-colors',
                    selectedNode.tag.action === 'add'
                      ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                  ]"
                >
                  + Qo'shish
                </button>
                <button
                  type="button"
                  @click="selectedNode.tag.action = 'remove'"
                  :class="[
                    'p-2 rounded-lg text-xs font-medium transition-colors',
                    selectedNode.tag.action === 'remove'
                      ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 ring-1 ring-red-500'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                  ]"
                >
                  - O'chirish
                </button>
              </div>
            </div>

            <!-- Common tags -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Umumiy teglar</label>
              <div class="flex flex-wrap gap-1">
                <button
                  v-for="t in commonTagsList"
                  :key="t"
                  type="button"
                  @click="addCommonTag(t)"
                  :disabled="selectedNode.tag.tags?.includes(t)"
                  class="px-2 py-1 text-[10px] bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 rounded disabled:opacity-40 disabled:cursor-not-allowed"
                >
                  #{{ t }}
                </button>
              </div>
            </div>

            <!-- Add custom tag -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Yangi teg</label>
              <div class="flex gap-1">
                <input
                  v-model="selectedNode.tag.new_tag"
                  type="text"
                  @keyup.enter="addTag"
                  class="flex-1 px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 placeholder-gray-400 dark:placeholder-gray-500"
                  placeholder="masalan: premium_2024"
                />
                <button
                  type="button"
                  @click="addTag"
                  class="px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium rounded-lg"
                >
                  Qo'shish
                </button>
              </div>
              <p class="text-[10px] text-gray-500 mt-1">Faqat a-z, 0-9, _ ruxsat etiladi (avtomatik tozalanadi)</p>
            </div>

            <!-- Selected tags -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Tanlangan teglar ({{ selectedNode.tag.tags?.length || 0 }})
              </label>
              <div v-if="(selectedNode.tag.tags?.length || 0) === 0" class="text-xs text-gray-400 italic">
                Hali teg tanlanmagan
              </div>
              <div v-else class="flex flex-wrap gap-1">
                <span
                  v-for="(t, i) in selectedNode.tag.tags"
                  :key="i"
                  class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded"
                >
                  #{{ t }}
                  <button
                    type="button"
                    @click="removeTag(i)"
                    class="hover:text-red-500"
                    title="O'chirish"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </span>
              </div>
            </div>
          </div>

          <!-- ============================================================ -->
          <!-- Subscribe Check Editor -->
          <!-- ============================================================ -->
          <div v-if="selectedNode.type === 'subscribe_check'" class="space-y-3">
            <div class="p-3 bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-lg">
              <div class="flex items-center gap-2 text-cyan-700 dark:text-cyan-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-xs font-medium">Obuna tekshiruvi</span>
              </div>
              <p class="text-xs text-cyan-700 dark:text-cyan-400 mt-1">
                Bot kanalda admin bo'lishi kerak, aks holda tekshira olmaydi.
              </p>
            </div>

            <!-- Channel username -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Kanal username (@ belgisisiz)
              </label>
              <div class="flex items-center gap-1">
                <span class="text-sm text-gray-500 dark:text-gray-400">@</span>
                <input
                  v-model="selectedNode.subscribe_check.channel_username"
                  type="text"
                  class="flex-1 px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 placeholder-gray-400 dark:placeholder-gray-500"
                  placeholder="bizneschannel"
                />
              </div>
              <p
                v-if="selectedNode.subscribe_check.channel_username && !isValidChannelUsername(selectedNode.subscribe_check.channel_username)"
                class="text-xs text-red-500 mt-1"
              >
                Noto'g'ri username — faqat a-z, 0-9, _ (3+ belgi)
              </p>
            </div>

            <!-- Not-subscribed message -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Xabar (obuna bo'lmaganda)
              </label>
              <textarea
                v-model="selectedNode.subscribe_check.not_subscribed_message"
                rows="2"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 resize-none placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="Davom etish uchun kanalga obuna bo'ling"
              ></textarea>
            </div>

            <!-- Subscribe button text -->
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Obuna bo'lish tugmasi matni
              </label>
              <input
                v-model="selectedNode.subscribe_check.subscribe_button_text"
                type="text"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="Obuna bo'lish"
              />
            </div>

            <!-- Obuna (true) branch -->
            <div>
              <label class="block text-xs font-medium text-cyan-700 dark:text-cyan-400 mb-1">
                Obuna bo'lganda
              </label>
              <select
                v-model="selectedNode.subscribe_true_step_id"
                @change="updateSubscribeBranchConnection('true')"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-cyan-300 dark:border-cyan-700 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
              >
                <option :value="null">-- tanlanmagan --</option>
                <option
                  v-for="n in nodes.filter(n => n.id !== selectedNode.id)"
                  :key="n.id"
                  :value="n.id"
                >
                  {{ n.name || getNodeLabel(n.type) }}
                </option>
              </select>
            </div>

            <!-- Obuna emas (false) branch -->
            <div>
              <label class="block text-xs font-medium text-red-700 dark:text-red-400 mb-1">
                Obuna bo'lmaganda
              </label>
              <select
                v-model="selectedNode.subscribe_false_step_id"
                @change="updateSubscribeBranchConnection('false')"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-red-300 dark:border-red-700 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
              >
                <option :value="null">-- tanlanmagan --</option>
                <option
                  v-for="n in nodes.filter(n => n.id !== selectedNode.id)"
                  :key="n.id"
                  :value="n.id"
                >
                  {{ n.name || getNodeLabel(n.type) }}
                </option>
              </select>
            </div>
          </div>

          <!-- ============================================================ -->
          <!-- Button Editor (inline/reply keyboard for message/input/start) -->
          <!-- ============================================================ -->
          <div v-if="['message', 'input', 'start'].includes(selectedNode.type)" class="space-y-3">
            <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
              <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Tugmalar (klaviatura)</label>
              <span class="text-[10px] text-gray-500">
                {{ (selectedNode.keyboard?.buttons || []).reduce((n, r) => n + (r?.length || 0), 0) }} ta
              </span>
            </div>

            <!-- Keyboard type -->
            <div>
              <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400 mb-1">Klaviatura turi</label>
              <select
                v-model="selectedNode.keyboard.type"
                class="w-full px-3 py-1.5 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="inline">Inline (xabar ichida)</option>
                <option value="reply">Reply (pastda)</option>
                <option value="remove">Olib tashlash</option>
              </select>
            </div>

            <!-- Button rows -->
            <div v-if="selectedNode.keyboard.type !== 'remove'" class="space-y-2">
              <div
                v-for="(row, rowIndex) in (selectedNode.keyboard.buttons || [])"
                :key="rowIndex"
                class="p-2 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg space-y-2"
              >
                <div class="flex items-center justify-between">
                  <span class="text-[10px] font-medium text-gray-500 uppercase">Qator {{ rowIndex + 1 }}</span>
                  <button
                    type="button"
                    @click="removeButtonRow(rowIndex)"
                    class="text-[10px] text-red-500 hover:text-red-700"
                  >
                    Qatorni o'chirish
                  </button>
                </div>

                <div
                  v-for="(button, btnIndex) in row"
                  :key="btnIndex"
                  class="p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded space-y-1.5"
                >
                  <div class="flex items-center gap-1">
                    <input
                      v-model="button.text"
                      type="text"
                      maxlength="30"
                      class="flex-1 px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                      placeholder="Tugma matni"
                    />
                    <button
                      type="button"
                      @click="removeButton(rowIndex, btnIndex)"
                      class="p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                      title="O'chirish"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                  <p v-if="!button.text?.trim()" class="text-[10px] text-red-500">Tugma matni bo'sh</p>

                  <select
                    v-model="button.action_type"
                    @change="onButtonActionChange(button)"
                    class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="next_step">Keyingi qadam</option>
                    <option value="url">URL havolasi</option>
                    <option value="callback">Callback (callback_data)</option>
                    <option value="request_contact">Kontakt so'rash</option>
                    <option value="request_location">Lokatsiya so'rash</option>
                    <option value="webapp">WebApp</option>
                  </select>

                  <!-- next_step -->
                  <select
                    v-if="button.action_type === 'next_step'"
                    v-model="button.next_step_id"
                    class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option :value="null">-- tanlanmagan --</option>
                    <option
                      v-for="n in nodes.filter(n => n.id !== selectedNode.id)"
                      :key="n.id"
                      :value="n.id"
                    >
                      {{ n.name || getNodeLabel(n.type) }}
                    </option>
                  </select>

                  <!-- url -->
                  <div v-if="button.action_type === 'url'">
                    <input
                      v-model="button.url"
                      type="url"
                      class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                      placeholder="https://example.com"
                    />
                    <p v-if="button.url && !isValidUrl(button.url)" class="text-[10px] text-red-500 mt-0.5">
                      Noto'g'ri URL (http:// yoki https:// bilan)
                    </p>
                  </div>

                  <!-- callback -->
                  <div v-if="button.action_type === 'callback'">
                    <input
                      v-model="button.callback_data"
                      type="text"
                      maxlength="64"
                      class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                      placeholder="action_key_123"
                    />
                    <p v-if="button.callback_data && !isValidCallbackData(button.callback_data)" class="text-[10px] text-red-500 mt-0.5">
                      Faqat a-z, 0-9, _, - (max 64)
                    </p>
                  </div>

                  <!-- webapp -->
                  <div v-if="button.action_type === 'webapp'">
                    <input
                      v-model="button.url"
                      type="url"
                      class="w-full px-2 py-1 text-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 dark:placeholder-gray-500"
                      placeholder="https://yourdomain.com/webapp"
                    />
                    <p v-if="button.url && !isValidUrl(button.url)" class="text-[10px] text-red-500 mt-0.5">
                      Noto'g'ri WebApp URL (https:// bilan)
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  @click="addButtonToRow(rowIndex)"
                  class="w-full px-2 py-1 text-[11px] bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded border border-dashed border-blue-300 dark:border-blue-700"
                >
                  + Tugma qo'shish
                </button>
              </div>

              <button
                type="button"
                @click="addButtonRow"
                class="w-full px-3 py-2 text-xs bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-400 rounded-lg font-medium"
              >
                + Qator qo'shish
              </button>
            </div>
          </div>

          <!-- Next Step Connection (hidden for branching types that use their own pickers) -->
          <div v-if="!['condition', 'subscribe_check', 'quiz', 'ab_test', 'trigger_keyword', 'end'].includes(selectedNode.type)">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Keyingi qadam (avtomatik)</label>
            <select
              v-model="selectedNode.next_step_id"
              @change="updateConnection"
              class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option :value="null">Funnelni tugatish</option>
              <option
                v-for="node in nodes.filter(n => n.id !== selectedNode.id)"
                :key="node.id"
                :value="node.id"
              >
                {{ node.name || getNodeLabel(node.type) }}
              </option>
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tugma bosilmasa avtomatik o'tiladi</p>
          </div>

          <!-- Delete Button -->
          <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <button
              @click="deleteNode(selectedNode.id)"
              class="w-full px-4 py-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              Qadamni o'chirish
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Telegram chat preview modal -->
    <TelegramChatPreview
      v-if="showPreview"
      :nodes="nodes"
      :start-node-id="firstStepId"
      :bot-name="bot?.first_name || bot?.username || ''"
      @close="showPreview = false"
    />

    <!-- Shortcuts help modal -->
    <div
      v-if="showShortcutsHelp"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
      @click.self="showShortcutsHelp = false"
    >
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">Tezkor tugmalar</h3>
          <button @click="showShortcutsHelp = false" class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-5 space-y-2 text-sm">
          <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Qo'llanmani ochish</span>
            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">?</kbd>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Saqlash</span>
            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Ctrl+S</kbd>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Qadamni o'chirish</span>
            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Delete</kbd>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Qadamni nusxalash</span>
            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Ctrl+D</kbd>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Orqaga qaytish</span>
            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Ctrl+Z</kbd>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Qayta qilish</span>
            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Ctrl+Shift+Z</kbd>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Modalni yopish / bekor qilish</span>
            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">Esc</kbd>
          </div>
        </div>
      </div>
    </div>

    <!-- Toasts (bottom-right stack) -->
    <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 items-end pointer-events-none">
      <transition-group name="toast">
        <div
          v-for="t in toastList"
          :key="t.id"
          class="pointer-events-auto min-w-[240px] max-w-sm px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-start gap-2"
          :class="{
            'bg-emerald-600 text-white': t.type === 'success',
            'bg-red-600 text-white': t.type === 'error',
            'bg-blue-600 text-white': t.type === 'info',
            'bg-amber-500 text-white': t.type === 'warning',
          }"
        >
          <svg v-if="t.type === 'success'" class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <svg v-else-if="t.type === 'error'" class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
          </svg>
          <svg v-else class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="flex-1 break-words">{{ t.message }}</span>
          <button @click="removeToast(t.id)" class="opacity-70 hover:opacity-100 flex-shrink-0" aria-label="Yopish">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, h, watch, computed, nextTick } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import { debounce } from 'lodash'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { validateNodes } from '@/composables/useFunnelValidation'
import { useFunnelShortcuts } from '@/composables/useFunnelShortcuts'
import TelegramChatPreview from '@/components/telegram/funnel/TelegramChatPreview.vue'

const props = defineProps({
  bot: Object,
  funnel: Object,
  steps: {
    type: Array,
    default: () => []
  },
  panelType: {
    type: String,
    required: true,
    validator: (value) => ['business', 'marketing'].includes(value)
  }
})

// Route helper for panel-specific routes
const getRoute = (name, params = null) => {
  const prefix = props.panelType === 'business' ? 'business.' : 'marketing.'
  return params ? route(prefix + name, params) : route(prefix + name)
}

const { confirm } = useConfirm()
const { toasts: toastList, showToast, removeToast } = useToast()

// Canvas state
const canvasContainer = ref(null)
const canvas = ref(null)
const zoom = ref(1)
const panOffset = reactive({ x: 100, y: 100 })
const isPanning = ref(false)
const panStart = reactive({ x: 0, y: 0 })

// Nodes and connections
const nodes = ref([])
const selectedNode = ref(null)
const connections = ref([])

// End node / funnel completion message (funnel-level setting, funnel update PUT bilan saqlanadi)
const funnelCompletionMessage = ref(props.funnel?.completion_message || '')

// Dragging state
const draggingNode = ref(null)
const dragOffset = reactive({ x: 0, y: 0 })

// Connection drawing state
const drawingConnection = ref(null)
const mousePosition = reactive({ x: 0, y: 0 })
const isDrawing = ref(false)

// Saving state
const isSaving = ref(false)

// Polish layer state (preview, validation, autosave, undo, shortcuts)
const isTestRunning = ref(false)
const showPreview = ref(false)
const showShortcutsHelp = ref(false)
const validationErrors = ref({}) // { [nodeId]: string[] }
const isDirty = ref(false)
const savedAt = ref(null)
const DRAFT_KEY = `funnel_draft_${props.funnel?.id || 'unknown'}`

const history = ref([])
const historyIndex = ref(-1)
const HISTORY_LIMIT = 20
let suppressHistory = false

const invalidNodeIds = computed(() => new Set(Object.keys(validationErrors.value)))

const firstStepId = computed(() => {
  const first = nodes.value.find(n => n.is_first) || nodes.value[0]
  return first?.id || null
})

const openPreview = () => {
  if (!nodes.value.length) {
    showToast("Ko'rish uchun qadam yo'q", 'info')
    return
  }
  showPreview.value = true
}

// Serialize a minimal snapshot so undo/redo compares cheaply
const snapshot = () => {
  try {
    return JSON.stringify({
      nodes: nodes.value,
      connections: connections.value,
    })
  } catch (e) {
    return null
  }
}

const pushHistory = () => {
  if (suppressHistory) return
  const snap = snapshot()
  if (!snap) return
  // Drop forward history on a new change
  history.value = history.value.slice(0, historyIndex.value + 1)
  history.value.push(snap)
  if (history.value.length > HISTORY_LIMIT) {
    history.value.shift()
  } else {
    historyIndex.value++
  }
}

const applySnapshot = (snap) => {
  if (!snap) return
  try {
    const parsed = JSON.parse(snap)
    suppressHistory = true
    nodes.value = parsed.nodes || []
    connections.value = parsed.connections || []
    nextTick(() => { suppressHistory = false })
  } catch (e) {
    suppressHistory = false
  }
}

const undo = () => {
  if (historyIndex.value <= 0) return
  historyIndex.value--
  applySnapshot(history.value[historyIndex.value])
  showToast('Orqaga qaytarildi', 'info')
}

const redo = () => {
  if (historyIndex.value >= history.value.length - 1) return
  historyIndex.value++
  applySnapshot(history.value[historyIndex.value])
  showToast('Qayta bajarildi', 'info')
}

// Duplicate currently selected node
const duplicateSelected = () => {
  if (!selectedNode.value) return
  const src = selectedNode.value
  const copy = JSON.parse(JSON.stringify(src))
  copy.id = `temp-${Date.now()}`
  copy.x = (src.x || 0) + 40
  copy.y = (src.y || 0) + 40
  copy.name = (src.name || getNodeLabel(src.type)) + " (nusxa)"
  copy.is_first = false
  // Nusxa mustaqil — kirish/chiqish bog'lanishlarini tashlab yuboramiz
  copy.next_step_id = null
  copy.condition_true_step_id = null
  copy.condition_false_step_id = null
  copy.subscribe_true_step_id = null
  copy.subscribe_false_step_id = null
  nodes.value.push(copy)
  selectedNode.value = copy
  showToast('Qadam nusxalandi', 'success')
}

// Save current state to localStorage
const saveDraftToLocal = () => {
  try {
    localStorage.setItem(DRAFT_KEY, JSON.stringify({
      nodes: nodes.value,
      connections: connections.value,
      ts: Date.now(),
    }))
  } catch (e) {
    // quota/storage errors are non-fatal for the UI
  }
}

const clearLocalDraft = () => {
  try { localStorage.removeItem(DRAFT_KEY) } catch (e) { /* noop */ }
}

const debouncedAutosave = debounce(() => {
  saveDraftToLocal()
}, 1500)

// Initialize nodes from props
onMounted(async () => {
  suppressHistory = true
  if (props.steps.length > 0) {
    nodes.value = props.steps.map((step, index) => ({
      ...step,
      type: step.step_type,
      x: step.position_x ?? 100 + (index % 3) * 280,
      y: step.position_y ?? 100 + Math.floor(index / 3) * 200,
      content: step.content || { type: 'text', text: '' },
      keyboard: step.keyboard || { type: 'inline', buttons: [] },
      condition: step.condition || { field: '', operator: '', value: '', custom_field: '' },
      condition_true_step_id: step.condition_true_step_id || null,
      condition_false_step_id: step.condition_false_step_id || null
    }))

    // Build connections from next_step_id and condition branches
    nodes.value.forEach(node => {
      if (node.type === 'condition') {
        if (node.condition_true_step_id) {
          connections.value.push({ from: node.id, to: node.condition_true_step_id, type: 'true' })
        }
        if (node.condition_false_step_id) {
          connections.value.push({ from: node.id, to: node.condition_false_step_id, type: 'false' })
        }
      } else if (node.next_step_id) {
        connections.value.push({ from: node.id, to: node.next_step_id, type: 'default' })
      }
    })
  }

  // Check for a newer localStorage draft and offer to restore it
  try {
    const raw = localStorage.getItem(DRAFT_KEY)
    if (raw) {
      const draft = JSON.parse(raw)
      // Only prompt if draft is materially newer than whatever the server sent.
      // We use the server's updated_at if present, fall back to "any draft".
      const draftTs = Number(draft?.ts || 0)
      if (draftTs && Array.isArray(draft.nodes) && draft.nodes.length) {
        const ok = await confirm({
          title: 'Saqlanmagan qoralama topildi',
          message: "Saqlanmagan qoralama topildi. Tiklaymizmi?",
          confirmText: 'Tiklash',
          cancelText: 'Yo\'q',
          type: 'info',
        })
        if (ok) {
          nodes.value = draft.nodes
          connections.value = draft.connections || []
          showToast('Qoralama tiklandi', 'success')
        } else {
          clearLocalDraft()
        }
      }
    }
  } catch (e) { /* ignore */ }

  // Seed history with initial state
  await nextTick()
  suppressHistory = false
  pushHistory()
})

// Autosave + dirty tracking — any deep change marks dirty and writes draft
watch(
  [nodes, connections],
  () => {
    if (suppressHistory) return
    isDirty.value = true
    debouncedAutosave()
    pushHistory()
  },
  { deep: true }
)

// Keyboard shortcuts
useFunnelShortcuts(async (action) => {
  if (action === 'escape') {
    if (showPreview.value) { showPreview.value = false; return }
    if (showShortcutsHelp.value) { showShortcutsHelp.value = false; return }
    selectedNode.value = null
    return
  }
  if (action === 'help') { showShortcutsHelp.value = true; return }
  if (action === 'save') { saveSteps(); return }
  if (action === 'undo') { undo(); return }
  if (action === 'redo') { redo(); return }
  if (action === 'duplicate') { duplicateSelected(); return }
  if (action === 'delete') {
    if (!selectedNode.value) return
    const id = selectedNode.value.id
    const hasConn = connections.value.some(c => c.from === id || c.to === id)
    if (hasConn) {
      const ok = await confirm({
        title: "O'chirishni tasdiqlang",
        message: "Bu qadamda bog'lanishlar bor. O'chirishni xohlaysizmi?",
        type: 'danger',
        confirmText: "O'chirish",
      })
      if (!ok) return
    }
    const index = nodes.value.findIndex(n => n.id === id)
    if (index > -1) {
      nodes.value.splice(index, 1)
      connections.value = connections.value.filter(c => c.from !== id && c.to !== id)
      selectedNode.value = null
    }
  }
})

// Zoom functions
const zoomIn = () => { zoom.value = Math.min(2, zoom.value + 0.1) }
const zoomOut = () => { zoom.value = Math.max(0.25, zoom.value - 0.1) }
const resetZoom = () => { zoom.value = 1; panOffset.x = 100; panOffset.y = 100 }
const onWheel = (e) => { e.preventDefault(); e.deltaY < 0 ? zoomIn() : zoomOut() }

// ──────────────────────────────────────────────────────────────────
// Pointer-events state machine
// Exactly ONE mode active at a time. Eliminates "node drags while
// connecting" bug by checking mode in every handler.
// ──────────────────────────────────────────────────────────────────
const interactionMode = ref('idle') // 'idle' | 'panning' | 'dragging-node' | 'connecting'

// Pan — only when clicking the empty canvas (not on a node or port)
const startPan = (e) => {
  if (interactionMode.value !== 'idle') return
  if (e.target === canvasContainer.value || e.target.tagName === 'svg') {
    interactionMode.value = 'panning'
    isPanning.value = true
    panStart.x = e.clientX - panOffset.x
    panStart.y = e.clientY - panOffset.y
  }
}

const onPan = (e) => {
  // Always update mousePosition so connection-drawing preview line follows cursor
  const rect = canvasContainer.value?.getBoundingClientRect()
  if (rect) {
    mousePosition.x = (e.clientX - rect.left - panOffset.x) / zoom.value
    mousePosition.y = (e.clientY - rect.top - panOffset.y) / zoom.value
  }

  // Route to mode-specific handler — mutually exclusive
  if (interactionMode.value === 'panning' && isPanning.value) {
    panOffset.x = e.clientX - panStart.x
    panOffset.y = e.clientY - panStart.y
    return
  }

  if (interactionMode.value === 'dragging-node' && draggingNode.value && rect) {
    draggingNode.value.x = (e.clientX - rect.left - panOffset.x) / zoom.value - dragOffset.x
    draggingNode.value.y = (e.clientY - rect.top - panOffset.y) / zoom.value - dragOffset.y
    return
  }

  // In 'connecting' mode mousePosition already updated above — svg path uses it
}

const endPan = (e) => {
  // Connection mode: if mouseup happened on empty canvas (no endConnection fired),
  // cancel the connection draw so it doesn't hang
  if (interactionMode.value === 'connecting') {
    // Give endConnection a chance to fire on ports (it has @mouseup.stop)
    // by reading drawingConnection AFTER microtask
    requestAnimationFrame(() => {
      if (drawingConnection.value) cancelConnection()
      interactionMode.value = 'idle'
    })
  } else {
    interactionMode.value = 'idle'
  }

  isPanning.value = false
  draggingNode.value = null
}

// Drag and drop from sidebar
const onDragStart = (e, type) => { e.dataTransfer.setData('nodeType', type) }

const onDrop = (e) => {
  const type = e.dataTransfer.getData('nodeType')
  if (!type) return

  const rect = canvasContainer.value.getBoundingClientRect()
  const x = (e.clientX - rect.left - panOffset.x) / zoom.value
  const y = (e.clientY - rect.top - panOffset.y) / zoom.value

  const newNode = {
    id: `temp-${Date.now()}`,
    name: getNodeLabel(type),
    type: type === 'start' ? 'message' : type,
    step_type: type === 'start' ? 'message' : type,
    x, y,
    content: { type: 'text', text: type === 'start' ? 'Assalomu alaykum! Botga xush kelibsiz.' : '' },
    keyboard: { type: 'inline', buttons: [] },
    input_type: type === 'input' ? 'text' : 'none',
    input_field: null,
    action_type: type === 'action' ? 'none' : 'none',
    delay_seconds: type === 'delay' ? 5 : null,
    next_step_id: null,
    order: nodes.value.length,
    is_first: type === 'start' || type === 'trigger_keyword' || nodes.value.length === 0,
    condition: type === 'condition' ? { field: '', operator: '', value: '', custom_field: '' } : null,
    condition_true_step_id: null,
    condition_false_step_id: null,
    subscribe_check: type === 'subscribe_check' ? { channel_username: '', not_subscribed_message: "Kanalga obuna bo'lishingiz kerak!", subscribe_button_text: "Obuna bo'lish" } : null,
    subscribe_true_step_id: null,
    subscribe_false_step_id: null,
    quiz: type === 'quiz' ? { question: '', options: [{ text: '', next_step_id: null }, { text: '', next_step_id: null }], save_answer_to: 'quiz_answer' } : null,
    ab_test: type === 'ab_test' ? { variants: [{ name: 'A', percentage: 50, next_step_id: null }, { name: 'B', percentage: 50, next_step_id: null }] } : null,
    tag: type === 'tag' ? { action: 'add', tags: [], new_tag: '' } : null,
    trigger: type === 'trigger_keyword' ? { keywords: '', match_type: 'contains', is_all_messages: false } : null
  }

  nodes.value.push(newNode)
  selectedNode.value = newNode
}

// Node dragging — only from body (not ports). Mode guard prevents drag
// while a connection is in progress.
const startDragNode = (e, node) => {
  if (interactionMode.value !== 'idle') return
  // Defensive: if click originated from a port (mousedown bubbled despite .stop)
  const target = e.target
  if (target && (target.closest?.('[data-port]') || target.hasAttribute?.('data-port'))) return

  const rect = canvasContainer.value.getBoundingClientRect()
  dragOffset.x = (e.clientX - rect.left - panOffset.x) / zoom.value - node.x
  dragOffset.y = (e.clientY - rect.top - panOffset.y) / zoom.value - node.y
  draggingNode.value = node
  interactionMode.value = 'dragging-node'
}

const selectNode = (node) => {
  if (!node) { selectedNode.value = null; return }

  // Backfill missing reactive substructures so v-model bindings have something to bind to.
  if (['message', 'input', 'start'].includes(node.type)) {
    ensureStructure(node, 'content', { type: 'text', text: '', caption: '', file_id: '', url: '', parse_mode: null })
    ensureStructure(node, 'keyboard', { type: 'inline', buttons: [] })
  }
  if (node.type === 'condition') {
    ensureStructure(node, 'condition', { field: '', operator: '', value: '', custom_field: '' })
  }
  if (node.type === 'quiz') {
    ensureStructure(node, 'quiz', {
      question: '',
      options: [{ text: '', next_step_id: null }, { text: '', next_step_id: null }],
      allow_multiple: false,
      save_answer_to: 'quiz_answer',
    })
    if (!Array.isArray(node.quiz.options) || node.quiz.options.length < 2) {
      node.quiz.options = [{ text: '', next_step_id: null }, { text: '', next_step_id: null }]
    }
  }
  if (node.type === 'ab_test') {
    ensureStructure(node, 'ab_test', {
      variants: [
        { name: 'A', percentage: 50, next_step_id: null },
        { name: 'B', percentage: 50, next_step_id: null },
      ],
    })
    if (!Array.isArray(node.ab_test.variants) || node.ab_test.variants.length < 2) {
      node.ab_test.variants = [
        { name: 'A', percentage: 50, next_step_id: null },
        { name: 'B', percentage: 50, next_step_id: null },
      ]
    }
  }
  if (node.type === 'tag') {
    ensureStructure(node, 'tag', { action: 'add', tags: [], new_tag: '' })
    if (!Array.isArray(node.tag.tags)) node.tag.tags = []
  }
  if (node.type === 'subscribe_check') {
    ensureStructure(node, 'subscribe_check', {
      channel_username: '',
      not_subscribed_message: "Davom etish uchun kanalga obuna bo'ling",
      subscribe_button_text: "Obuna bo'lish",
    })
  }
  if (node.type === 'trigger_keyword') {
    ensureStructure(node, 'trigger', { keywords: '', match_type: 'contains', is_all_messages: false })
  }

  selectedNode.value = node
}

const deleteNode = async (nodeId) => {
  if (!await confirm({ title: 'O\'chirishni tasdiqlang', message: "Bu qadamni o'chirishni xohlaysizmi?", type: 'danger', confirmText: 'O\'chirish' })) return
  const index = nodes.value.findIndex(n => n.id === nodeId)
  if (index > -1) {
    nodes.value.splice(index, 1)
    connections.value = connections.value.filter(c => c.from !== nodeId && c.to !== nodeId)
    if (selectedNode.value?.id === nodeId) selectedNode.value = null
  }
}

// Connection functions
const startConnection = (nodeId, e, connectionType = 'default') => {
  e.preventDefault()
  e.stopPropagation()
  // Set mode FIRST so onPan doesn't try to drag a node
  interactionMode.value = 'connecting'
  isDrawing.value = true
  drawingConnection.value = { from: nodeId, type: connectionType }
}

const endConnection = (nodeId) => {
  // Mode reset — even if connection was rejected (same node, etc.)
  const wasConnecting = interactionMode.value === 'connecting'
  if (drawingConnection.value && drawingConnection.value.from !== nodeId) {
    const sourceNode = nodes.value.find(n => n.id === drawingConnection.value.from)
    const connectionType = drawingConnection.value.type || 'default'

    if (connectionType === 'true' || connectionType === 'false') {
      connections.value = connections.value.filter(c => !(c.from === drawingConnection.value.from && c.type === connectionType))
      connections.value.push({ from: drawingConnection.value.from, to: nodeId, type: connectionType })
      if (sourceNode) {
        if (connectionType === 'true') sourceNode.condition_true_step_id = nodeId
        else sourceNode.condition_false_step_id = nodeId
      }
    } else {
      connections.value = connections.value.filter(c => !(c.from === drawingConnection.value.from && (!c.type || c.type === 'default')))
      connections.value.push({ from: drawingConnection.value.from, to: nodeId, type: 'default' })
      if (sourceNode) sourceNode.next_step_id = nodeId
    }
  }
  cancelConnection()
}

const cancelConnection = () => {
  drawingConnection.value = null
  isDrawing.value = false
  if (interactionMode.value === 'connecting') interactionMode.value = 'idle'
}

const updateDrawingConnection = (e) => {
  if (!drawingConnection.value) return
  const rect = canvasContainer.value?.getBoundingClientRect()
  if (rect) {
    mousePosition.x = (e.clientX - rect.left - panOffset.x) / zoom.value
    mousePosition.y = (e.clientY - rect.top - panOffset.y) / zoom.value
  }
}

const updateConnection = () => {
  if (!selectedNode.value) return
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && (!c.type || c.type === 'default')))
  if (selectedNode.value.next_step_id) {
    connections.value.push({ from: selectedNode.value.id, to: selectedNode.value.next_step_id, type: 'default' })
  }
}

const updateConditionConnection = (branchType) => {
  if (!selectedNode.value || selectedNode.value.type !== 'condition') return
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && c.type === branchType))
  const targetId = branchType === 'true' ? selectedNode.value.condition_true_step_id : selectedNode.value.condition_false_step_id
  if (targetId) connections.value.push({ from: selectedNode.value.id, to: targetId, type: branchType })
}

const deleteConnection = async (connection) => {
  if (!await confirm({ title: 'O\'chirishni tasdiqlang', message: "Bu bog'lanishni o'chirishni xohlaysizmi?", type: 'danger', confirmText: 'O\'chirish' })) return
  const sourceNode = nodes.value.find(n => n.id === connection.from)

  if (connection.type === 'true' && sourceNode) sourceNode.condition_true_step_id = null
  else if (connection.type === 'false' && sourceNode) sourceNode.condition_false_step_id = null
  else if (connection.type === 'subscribed' && sourceNode) sourceNode.subscribe_true_step_id = null
  else if (connection.type === 'not_subscribed' && sourceNode) sourceNode.subscribe_false_step_id = null
  else if (connection.type?.startsWith('option_') && sourceNode) {
    const optionIndex = parseInt(connection.type.split('_')[1])
    if (sourceNode.quiz?.options?.[optionIndex]) sourceNode.quiz.options[optionIndex].next_step_id = null
  } else if (connection.type?.startsWith('variant_') && sourceNode) {
    const variantIndex = parseInt(connection.type.split('_')[1])
    if (sourceNode.ab_test?.variants?.[variantIndex]) sourceNode.ab_test.variants[variantIndex].next_step_id = null
  } else if (sourceNode) sourceNode.next_step_id = null

  connections.value = connections.value.filter(c => !(c.from === connection.from && c.to === connection.to && c.type === connection.type))
}

const updateBranchConnection = (nodeType, branchType) => {
  if (!selectedNode.value) return
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && c.type === branchType))
  let targetId = null
  if (nodeType === 'subscribe_check') {
    targetId = branchType === 'subscribed' ? selectedNode.value.subscribe_true_step_id : selectedNode.value.subscribe_false_step_id
  }
  if (targetId) connections.value.push({ from: selectedNode.value.id, to: targetId, type: branchType })
}

// Quiz functions
const addQuizOption = () => {
  if (!selectedNode.value?.quiz) return
  selectedNode.value.quiz.options.push({ text: '', next_step_id: null })
}

const removeQuizOption = (index) => {
  if (!selectedNode.value?.quiz) return
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && c.type === `option_${index}`))
  selectedNode.value.quiz.options.splice(index, 1)
  rebuildQuizConnections()
}

const updateQuizConnection = (index) => {
  if (!selectedNode.value?.quiz?.options?.[index]) return
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && c.type === `option_${index}`))
  const targetId = selectedNode.value.quiz.options[index].next_step_id
  if (targetId) connections.value.push({ from: selectedNode.value.id, to: targetId, type: `option_${index}` })
}

const rebuildQuizConnections = () => {
  if (!selectedNode.value?.quiz) return
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && c.type?.startsWith('option_')))
  selectedNode.value.quiz.options.forEach((option, i) => {
    if (option.next_step_id) connections.value.push({ from: selectedNode.value.id, to: option.next_step_id, type: `option_${i}` })
  })
}

// A/B Test functions
// Input editor helper: validation object'ni init qiladi (reactivity uchun).
const ensureValidation = () => {
  if (!selectedNode.value) return
  if (!selectedNode.value.validation || typeof selectedNode.value.validation !== 'object') {
    selectedNode.value.validation = {
      required: false,
      min_length: null,
      max_length: null,
      pattern: null,
      error_message: null,
      retry_count: null,
    }
  }
}

// Action editor helper: action_config object'ni init qiladi.
const ensureActionConfig = () => {
  if (!selectedNode.value) return
  if (!selectedNode.value.action_config || typeof selectedNode.value.action_config !== 'object') {
    selectedNode.value.action_config = {}
  }
}

// Tag node'da `new_tag` — UI helper (current input); JSON'ga saqlashda olib tashlaymiz.
const stripTagHelper = (tag) => {
  if (!tag || typeof tag !== 'object') return tag
  const { new_tag, ...rest } = tag
  return rest
}

// Quiz node'da legacy `allow_multiple` maydoni hali ham eski DB row'larda bo'lishi
// mumkin. Engine uni o'qimaydi, shunchaki noisy. Saqlashda olib tashlaymiz.
const stripQuizLegacy = (quiz) => {
  if (!quiz || typeof quiz !== 'object') return quiz
  const { allow_multiple, ...rest } = quiz
  return rest
}

// Quiz correct-mode toggle — correct_option_index mavjud bo'lsa rejim yoqilgan.
const hasQuizCorrectMode = computed(() => {
  const q = selectedNode.value?.quiz
  return q && q.correct_option_index !== undefined && q.correct_option_index !== null
})
const toggleQuizCorrectMode = () => {
  if (!selectedNode.value?.quiz) return
  if (hasQuizCorrectMode.value) {
    delete selectedNode.value.quiz.correct_option_index
    delete selectedNode.value.quiz.correct_step_id
    delete selectedNode.value.quiz.wrong_step_id
    delete selectedNode.value.quiz.score_on_correct
  } else {
    selectedNode.value.quiz.correct_option_index = 0
    selectedNode.value.quiz.correct_step_id = null
    selectedNode.value.quiz.wrong_step_id = null
    selectedNode.value.quiz.score_on_correct = 1
  }
}

const addABVariant = () => {
  if (!selectedNode.value?.ab_test) return
  const variantLetters = ['A', 'B', 'C', 'D']
  const newIndex = selectedNode.value.ab_test.variants.length
  if (newIndex >= 4) return
  selectedNode.value.ab_test.variants.push({ name: variantLetters[newIndex], percentage: Math.floor(100 / (newIndex + 1)), next_step_id: null })
  redistributePercentages()
}

const removeABVariant = (index) => {
  if (!selectedNode.value?.ab_test || selectedNode.value.ab_test.variants.length <= 2) return
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && c.type === `variant_${index}`))
  selectedNode.value.ab_test.variants.splice(index, 1)
  rebuildABConnections()
  redistributePercentages()
}

const updateABConnection = (index) => {
  if (!selectedNode.value?.ab_test?.variants?.[index]) return
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && c.type === `variant_${index}`))
  const targetId = selectedNode.value.ab_test.variants[index].next_step_id
  if (targetId) connections.value.push({ from: selectedNode.value.id, to: targetId, type: `variant_${index}` })
}

const rebuildABConnections = () => {
  if (!selectedNode.value?.ab_test) return
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && c.type?.startsWith('variant_')))
  selectedNode.value.ab_test.variants.forEach((variant, i) => {
    if (variant.next_step_id) connections.value.push({ from: selectedNode.value.id, to: variant.next_step_id, type: `variant_${i}` })
  })
}

const redistributePercentages = () => {
  if (!selectedNode.value?.ab_test) return
  const count = selectedNode.value.ab_test.variants.length
  const basePercentage = Math.floor(100 / count)
  const remainder = 100 - (basePercentage * count)
  selectedNode.value.ab_test.variants.forEach((variant, i) => { variant.percentage = basePercentage + (i === 0 ? remainder : 0) })
}

const getTotalPercentage = () => {
  if (!selectedNode.value?.ab_test?.variants) return 0
  return selectedNode.value.ab_test.variants.reduce((sum, v) => sum + (v.percentage || 0), 0)
}

// Tag functions
const addTag = () => {
  if (!selectedNode.value?.tag || !selectedNode.value.tag.new_tag?.trim()) return
  const newTag = selectedNode.value.tag.new_tag.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_')
  if (!selectedNode.value.tag.tags.includes(newTag)) selectedNode.value.tag.tags.push(newTag)
  selectedNode.value.tag.new_tag = ''
}

const removeTag = (index) => { if (selectedNode.value?.tag) selectedNode.value.tag.tags.splice(index, 1) }

const addCommonTag = (tag) => {
  if (!selectedNode.value?.tag) return
  if (!selectedNode.value.tag.tags.includes(tag)) selectedNode.value.tag.tags.push(tag)
}

// Get connection path
const getConnectionPath = (connection) => {
  const fromNode = nodes.value.find(n => n.id === connection.from)
  const toNode = nodes.value.find(n => n.id === connection.to)
  if (!fromNode || !toNode) return ''

  let fromX, fromY

  if (fromNode.type === 'condition') {
    if (connection.type === 'true') { fromX = fromNode.x + 35; fromY = fromNode.y + 160 }
    else if (connection.type === 'false') { fromX = fromNode.x + 185; fromY = fromNode.y + 160 }
    else { fromX = fromNode.x + 110; fromY = fromNode.y + 150 }
  } else if (fromNode.type === 'subscribe_check') {
    if (connection.type === 'subscribed') { fromX = fromNode.x + 35; fromY = fromNode.y + 140 }
    else if (connection.type === 'not_subscribed') { fromX = fromNode.x + 185; fromY = fromNode.y + 140 }
    else { fromX = fromNode.x + 110; fromY = fromNode.y + 130 }
  } else if (fromNode.type === 'quiz') {
    const optionCount = fromNode.quiz?.options?.length || 2
    if (connection.type?.startsWith('option_')) {
      const optionIndex = parseInt(connection.type.split('_')[1])
      const spacing = 180 / (optionCount + 1)
      fromX = fromNode.x + 30 + (spacing * (optionIndex + 1))
      fromY = fromNode.y + 160
    } else { fromX = fromNode.x + 110; fromY = fromNode.y + 150 }
  } else if (fromNode.type === 'ab_test') {
    const variantCount = fromNode.ab_test?.variants?.length || 2
    if (connection.type?.startsWith('variant_')) {
      const variantIndex = parseInt(connection.type.split('_')[1])
      const spacing = 180 / (variantCount + 1)
      fromX = fromNode.x + 30 + (spacing * (variantIndex + 1))
      fromY = fromNode.y + 140
    } else { fromX = fromNode.x + 110; fromY = fromNode.y + 130 }
  } else { fromX = fromNode.x + 110; fromY = fromNode.y + 120 }

  const toX = toNode.x + 110
  const toY = toNode.y + 10
  const deltaY = Math.abs(toY - fromY)
  const controlOffset = Math.min(deltaY * 0.5, 80)

  return `M ${fromX} ${fromY} C ${fromX} ${fromY + controlOffset}, ${toX} ${toY - controlOffset}, ${toX} ${toY}`
}

const getDrawingPath = () => {
  if (!drawingConnection.value) return ''
  const fromNode = nodes.value.find(n => n.id === drawingConnection.value.from)
  if (!fromNode) return ''

  let fromX, fromY

  if (fromNode.type === 'condition') {
    if (drawingConnection.value.type === 'true') { fromX = fromNode.x + 35; fromY = fromNode.y + 160 }
    else if (drawingConnection.value.type === 'false') { fromX = fromNode.x + 185; fromY = fromNode.y + 160 }
    else { fromX = fromNode.x + 110; fromY = fromNode.y + 150 }
  } else if (fromNode.type === 'subscribe_check') {
    if (drawingConnection.value.type === 'subscribed') { fromX = fromNode.x + 35; fromY = fromNode.y + 140 }
    else if (drawingConnection.value.type === 'not_subscribed') { fromX = fromNode.x + 185; fromY = fromNode.y + 140 }
    else { fromX = fromNode.x + 110; fromY = fromNode.y + 130 }
  } else if (fromNode.type === 'quiz') {
    const optionCount = fromNode.quiz?.options?.length || 2
    if (drawingConnection.value.type?.startsWith('option_')) {
      const optionIndex = parseInt(drawingConnection.value.type.split('_')[1])
      const spacing = 180 / (optionCount + 1)
      fromX = fromNode.x + 30 + (spacing * (optionIndex + 1))
      fromY = fromNode.y + 160
    } else { fromX = fromNode.x + 110; fromY = fromNode.y + 150 }
  } else if (fromNode.type === 'ab_test') {
    const variantCount = fromNode.ab_test?.variants?.length || 2
    if (drawingConnection.value.type?.startsWith('variant_')) {
      const variantIndex = parseInt(drawingConnection.value.type.split('_')[1])
      const spacing = 180 / (variantCount + 1)
      fromX = fromNode.x + 30 + (spacing * (variantIndex + 1))
      fromY = fromNode.y + 140
    } else { fromX = fromNode.x + 110; fromY = fromNode.y + 130 }
  } else { fromX = fromNode.x + 110; fromY = fromNode.y + 120 }

  const deltaY = Math.abs(mousePosition.y - fromY)
  const controlOffset = Math.min(deltaY * 0.4, 60)

  return `M ${fromX} ${fromY} C ${fromX} ${fromY + controlOffset}, ${mousePosition.x} ${mousePosition.y - controlOffset}, ${mousePosition.x} ${mousePosition.y}`
}

const getConnectionColor = (connection) => {
  if (connection.type === 'true' || connection.type === 'subscribed') return '#22c55e'
  if (connection.type === 'false' || connection.type === 'not_subscribed') return '#ef4444'
  if (connection.type?.startsWith('option_')) return '#6366f1'
  if (connection.type?.startsWith('variant_')) return '#f59e0b'
  return '#6366f1'
}

const getArrowMarker = (connection) => {
  if (connection.type === 'true' || connection.type === 'subscribed') return 'arrowhead-green'
  if (connection.type === 'false' || connection.type === 'not_subscribed') return 'arrowhead-red'
  return 'arrowhead'
}

const getDrawingConnectionColor = () => {
  if (!drawingConnection.value) return '#6366f1'
  if (drawingConnection.value.type === 'true' || drawingConnection.value.type === 'subscribed') return '#22c55e'
  if (drawingConnection.value.type === 'false' || drawingConnection.value.type === 'not_subscribed') return '#ef4444'
  if (drawingConnection.value.type?.startsWith('option_')) return '#6366f1'
  if (drawingConnection.value.type?.startsWith('variant_')) return '#f59e0b'
  return '#6366f1'
}

const getConnectionLabelPosition = (connection) => {
  const fromNode = nodes.value.find(n => n.id === connection.from)
  const toNode = nodes.value.find(n => n.id === connection.to)
  if (!fromNode || !toNode) return { x: 0, y: 0 }

  let fromX
  if (fromNode.type === 'condition') {
    if (connection.type === 'true') fromX = fromNode.x + 35
    else if (connection.type === 'false') fromX = fromNode.x + 185
    else fromX = fromNode.x + 110
  } else fromX = fromNode.x + 110

  const fromY = fromNode.type === 'condition' ? fromNode.y + 160 : fromNode.y + 120
  const toX = toNode.x + 110
  const toY = toNode.y + 10

  return { x: (fromX + toX) / 2 - 10, y: (fromY + toY) / 2 }
}

// Content types for media selection
const contentTypes = [
  { value: 'text', label: 'Matn', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg>' },
  { value: 'photo', label: 'Rasm', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>' },
  { value: 'video', label: 'Video', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M10 9l5 3-5 3V9z"/></svg>' },
  { value: 'voice', label: 'Ovoz', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v10M8 5v6M4 7v2M16 5v6M20 7v2"/><path d="M12 15a4 4 0 004-4V5a4 4 0 00-8 0v6a4 4 0 004 4z"/><path d="M17 11a5 5 0 01-10 0"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>' },
  { value: 'video_note', label: 'Aylana', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M10 9l5 3-5 3V9z"/></svg>' },
  { value: 'document', label: 'Fayl', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>' },
]

const setContentType = (type) => {
  if (!selectedNode.value) return
  const oldText = selectedNode.value.content?.text || selectedNode.value.content?.caption || ''
  selectedNode.value.content = {
    type,
    text: type === 'text' ? oldText : '',
    caption: type !== 'text' ? oldText : '',
    file_id: selectedNode.value.content?.file_id || '',
    url: selectedNode.value.content?.url || '',
    duration: selectedNode.value.content?.duration || null,
  }
}

const getMediaLabel = (type) => {
  const labels = { photo: 'Rasm', video: 'Video', voice: 'Ovozli xabar', video_note: 'Dumaloq video', document: 'Fayl' }
  return labels[type] || type
}

// Ensure a reactive sub-structure exists on a node; set defaults if missing/falsy.
const ensureStructure = (node, key, defaults) => {
  if (!node) return
  if (!node[key] || typeof node[key] !== 'object') {
    node[key] = JSON.parse(JSON.stringify(defaults))
  } else {
    // Backfill missing keys without overwriting existing ones
    Object.keys(defaults).forEach((k) => {
      if (node[key][k] === undefined) node[key][k] = JSON.parse(JSON.stringify(defaults[k]))
    })
  }
}

// Condition field options (matches FunnelEngineService + getFieldValue)
const conditionFieldOptions = [
  { group: 'Foydalanuvchi', options: [
    { value: 'first_name', label: 'Ism' },
    { value: 'last_name', label: 'Familiya' },
    { value: 'username', label: 'Username' },
    { value: 'phone', label: 'Telefon' },
    { value: 'email', label: 'Email' },
    { value: 'language_code', label: 'Til kodi' },
    { value: 'user_id', label: 'Telegram ID' },
    { value: 'is_premium', label: 'Premium' },
  ]},
  { group: 'Tizim', options: [
    { value: 'has_tag', label: 'Istalgan teg mavjud' },
    { value: 'quiz_answer', label: 'Quiz javobi' },
    { value: 'interaction_count', label: 'Interaksiya soni' },
    { value: 'custom_field', label: 'Maxsus maydon (has_tag:vip, user.x, variables.y)' },
  ]},
]

const conditionOperators = [
  { value: 'equals', label: 'teng (==)' },
  { value: 'not_equals', label: 'teng emas (!=)' },
  { value: 'contains', label: "o'z ichiga oladi" },
  { value: 'not_contains', label: "o'z ichiga olmaydi" },
  { value: 'starts_with', label: 'bilan boshlanadi' },
  { value: 'ends_with', label: 'bilan tugaydi' },
  { value: 'is_set', label: "mavjud (bo'sh emas)" },
  { value: 'is_empty', label: "bo'sh" },
  { value: 'greater_than', label: 'katta (>)' },
  { value: 'less_than', label: 'kichik (<)' },
  { value: 'greater_or_equal', label: 'katta yoki teng (>=)' },
  { value: 'less_or_equal', label: 'kichik yoki teng (<=)' },
  { value: 'is_true', label: 'ha (true)' },
  { value: 'is_false', label: "yo'q (false)" },
]

// Operators that don't require a value input
const operatorsWithoutValue = ['is_set', 'is_empty', 'is_true', 'is_false']

// Common pre-defined tags for quick-add
const commonTagsList = ['vip', 'hot', 'cold', 'converted', 'abandoned', 'lead', 'customer']

// Validators
const isValidUrl = (url) => {
  if (!url) return false
  try {
    const u = new URL(url)
    return u.protocol === 'http:' || u.protocol === 'https:'
  } catch { return false }
}

const isValidCallbackData = (data) => {
  if (!data) return false
  return /^[a-zA-Z0-9_\-]+$/.test(data) && data.length <= 64
}

const isValidChannelUsername = (u) => {
  if (!u) return false
  const cleaned = String(u).replace(/^@/, '')
  return /^[a-zA-Z0-9_]{3,}$/.test(cleaned)
}

// Media upload — public diskda faylni saqlaydi va barqaror URL qaytaradi.
// Endpoint: POST /business/telegram-funnels/{bot}/funnels/upload-media
const uploadFunnelMedia = async (file, kind = 'photo') => {
  const form = new FormData()
  form.append('file', file)
  form.append('kind', kind)
  const url = `/business/telegram-funnels/${props.bot.id}/funnels/upload-media`
  const res = await axios.post(url, form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  if (!res?.data?.success) {
    throw new Error(res?.data?.message || 'Upload failed')
  }
  return {
    url: res.data.url,
    file_id: res.data.file_id || '',
    path: res.data.path || '',
    original_name: res.data.original_name || file.name,
  }
}

const onMediaUpload = async (event) => {
  const file = event.target.files?.[0]
  if (!file || !selectedNode.value) return
  try {
    const kind = selectedNode.value.content?.type || 'photo'
    const { url, file_id } = await uploadFunnelMedia(file, kind)
    if (!selectedNode.value.content) selectedNode.value.content = { type: kind, text: '', caption: '' }
    selectedNode.value.content.url = url
    if (file_id) selectedNode.value.content.file_id = file_id
    selectedNode.value.content.__local_filename = file.name
    showToast("Fayl yuklandi", 'success')
  } catch (e) {
    console.error('Media upload failed', e)
    showToast(e?.response?.data?.message || 'Fayl yuklashda xatolik', 'error')
  } finally {
    // Reset the input so re-selecting the same file still triggers change
    event.target.value = ''
  }
}

const clearMedia = () => {
  if (!selectedNode.value?.content) return
  selectedNode.value.content.url = ''
  selectedNode.value.content.file_id = ''
  selectedNode.value.content.__local_filename = ''
}

// Subscribe check branch connection sync
const updateSubscribeBranchConnection = (branchType) => {
  if (!selectedNode.value || selectedNode.value.type !== 'subscribe_check') return
  const connType = branchType === 'true' ? 'subscribed' : 'not_subscribed'
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && c.type === connType))
  const targetId = branchType === 'true'
    ? selectedNode.value.subscribe_true_step_id
    : selectedNode.value.subscribe_false_step_id
  if (targetId) connections.value.push({ from: selectedNode.value.id, to: targetId, type: connType })
}

const updateConditionBranchConnection = (branchType) => {
  if (!selectedNode.value || selectedNode.value.type !== 'condition') return
  connections.value = connections.value.filter(c => !(c.from === selectedNode.value.id && c.type === branchType))
  const targetId = branchType === 'true'
    ? selectedNode.value.condition_true_step_id
    : selectedNode.value.condition_false_step_id
  if (targetId) connections.value.push({ from: selectedNode.value.id, to: targetId, type: branchType })
}

// Button functions
const addButtonRow = () => {
  if (!selectedNode.value.keyboard) selectedNode.value.keyboard = { type: 'inline', buttons: [] }
  selectedNode.value.keyboard.buttons.push([{ text: '', action_type: 'next_step', callback_data: '', url: '', next_step_id: null, request_contact: false, request_location: false }])
}

const removeButtonRow = (rowIndex) => { selectedNode.value.keyboard.buttons.splice(rowIndex, 1) }

const addButtonToRow = (rowIndex) => {
  selectedNode.value.keyboard.buttons[rowIndex].push({ text: '', action_type: 'next_step', callback_data: '', url: '', next_step_id: null, request_contact: false, request_location: false })
}

const removeButton = (rowIndex, btnIndex) => {
  selectedNode.value.keyboard.buttons[rowIndex].splice(btnIndex, 1)
  if (selectedNode.value.keyboard.buttons[rowIndex].length === 0) selectedNode.value.keyboard.buttons.splice(rowIndex, 1)
}

const onButtonActionChange = (button) => {
  button.callback_data = ''
  button.url = ''
  button.next_step_id = null
  button.request_contact = button.action_type === 'request_contact'
  button.request_location = button.action_type === 'request_location'
}

// Helper functions
const getNodeHeaderClass = (type) => {
  const classes = {
    start: 'bg-gradient-to-r from-green-500 to-green-600 text-white',
    trigger_keyword: 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white',
    message: 'bg-gradient-to-r from-blue-500 to-blue-600 text-white',
    input: 'bg-gradient-to-r from-purple-500 to-purple-600 text-white',
    condition: 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white',
    action: 'bg-gradient-to-r from-red-500 to-pink-500 text-white',
    delay: 'bg-gradient-to-r from-gray-500 to-gray-600 text-white',
    end: 'bg-gradient-to-r from-gray-700 to-gray-800 text-white',
    subscribe_check: 'bg-gradient-to-r from-cyan-500 to-teal-500 text-white',
    quiz: 'bg-gradient-to-r from-indigo-500 to-violet-500 text-white',
    ab_test: 'bg-gradient-to-r from-amber-500 to-yellow-500 text-white',
    tag: 'bg-gradient-to-r from-emerald-500 to-green-500 text-white'
  }
  return classes[type] || classes.message
}

const getNodeLabel = (type) => {
  const labels = { start: 'Boshlash', trigger_keyword: "Kalit so'z", message: 'Xabar', input: "Ma'lumot", condition: 'Shart', action: 'Amal', delay: 'Kutish', end: 'Tugatish', subscribe_check: 'Obuna tekshir', quiz: 'Savol/Quiz', ab_test: 'A/B Test', tag: 'Teg' }
  return labels[type] || type
}

const getNodeIcon = (type) => {
  return {
    render() {
      const icons = {
        start: h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z' })]),
        trigger_keyword: h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' })]),
        message: h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' })]),
        input: h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' })]),
        condition: h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })]),
        action: h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 10V3L4 14h7v7l9-11h-7z' })]),
        delay: h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' })]),
        end: h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }), h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z' })])
      }
      return icons[type] || icons.message
    }
  }
}

const getInputTypeLabel = (type) => {
  const labels = { none: 'Hech biri', text: 'Matn', email: 'Email', phone: 'Telefon', number: 'Raqam', location: 'Lokatsiya', photo: 'Rasm', any: 'Istalgan' }
  return labels[type] || type
}

const getActionTypeLabel = (type) => {
  const labels = { none: 'Hech narsa', create_lead: 'Lid yaratish', update_user: 'Foydalanuvchi yangilash', handoff: 'Operatorga', send_notification: 'Xabarnoma', webhook: 'Webhook' }
  return labels[type] || type
}

const getMatchTypeLabel = (type) => {
  const labels = { exact: "To'liq mos", contains: "Ichida bor", starts_with: "Bilan boshlanadi", ends_with: "Bilan tugaydi", regex: "Regex" }
  return labels[type] || type
}

const getConditionOperatorLabel = (operator) => {
  const labels = { 'equals': 'teng', 'not_equals': 'teng emas', 'contains': "o'z ichiga oladi", 'not_contains': "o'z ichiga olmaydi", 'starts_with': 'bilan boshlanadi', 'ends_with': 'bilan tugaydi', 'is_set': "mavjud", 'is_empty': "bo'sh", 'greater_than': 'katta', 'less_than': 'kichik', 'greater_or_equal': 'katta yoki teng', 'less_or_equal': 'kichik yoki teng', 'is_true': 'ha (true)', 'is_false': "yo'q (false)" }
  return labels[operator] || operator
}

const getFieldDisplayName = (field) => {
  const names = { 'first_name': 'Ism', 'last_name': 'Familiya', 'username': 'Username', 'phone': 'Telefon', 'email': 'Email', 'language_code': 'Til kodi', 'is_premium': 'Premium', 'user_id': 'Telegram ID', 'custom_field': 'Maxsus maydon', 'has_tag': 'Teg mavjudligi', 'quiz_answer': 'Quiz javobi', 'interaction_count': 'Interaksiya soni' }
  return names[field] || field
}

// Test-run: ask backend to run this funnel for the authenticated operator's
// linked Telegram account. Shows disabled/connect hint if not linked.
const testRun = async () => {
  if (isTestRunning.value) return
  isTestRunning.value = true
  try {
    const res = await axios.post(
      getRoute('telegram-funnels.funnels.test-run', [props.bot.id, props.funnel.id])
    )
    if (res.data?.success) {
      showToast(res.data.message || 'Testga botga yuborildi', 'success')
    } else {
      showToast(res.data?.message || 'Xatolik yuz berdi', 'error')
    }
  } catch (error) {
    const data = error.response?.data
    if (data?.code === 'telegram_not_linked') {
      const ok = await confirm({
        title: 'Telegram bog\'lanmagan',
        message: "Telegramga bog'lanmagansiz — sozlamalarga o'tib bog'lanasizmi?",
        type: 'info',
        confirmText: "Bog'lanish",
        cancelText: 'Yo\'q',
      })
      if (ok) window.location.href = data.connect_url || '/settings/telegram'
    } else if (data?.code === 'bot_not_started') {
      showToast(data.message, 'warning')
    } else {
      showToast(data?.message || 'Sinovni yuborib bo\'lmadi', 'error')
    }
  } finally {
    isTestRunning.value = false
  }
}

// Save function
const saveSteps = async () => {
  // Validate before sending
  const result = validateNodes(nodes.value)
  validationErrors.value = result.errors
  if (!result.valid) {
    showToast(result.firstError || "Funnelda xatoliklar bor — qizil qadamlarni tekshiring", 'error')
    // Focus first invalid node on the canvas
    const firstId = Object.keys(result.errors)[0]
    if (firstId) {
      const bad = nodes.value.find(n => n.id === firstId)
      if (bad) selectedNode.value = bad
    }
    return
  }

  isSaving.value = true
  try {
    const validNodes = nodes.value.filter(n => n.type !== 'end')

    const stepsData = validNodes.map((node, index) => ({
      id: String(node.id).startsWith('temp-') ? null : node.id,
      name: node.name,
      step_type: node.step_type || node.type,
      content: node.content,
      keyboard: node.keyboard,
      input_type: node.input_type || 'none',
      input_field: node.input_field,
      validation: node.validation || null,
      action_type: node.action_type || 'none',
      action_config: node.action_config || null,
      next_step_id: ['condition', 'subscribe_check', 'quiz', 'ab_test'].includes(node.type) ? null : node.next_step_id,
      delay_seconds: node.delay_seconds,
      order: index,
      position_x: Math.round(node.x),
      position_y: Math.round(node.y),
      condition: node.type === 'condition' ? node.condition : null,
      condition_true_step_id: node.condition_true_step_id || null,
      condition_false_step_id: node.condition_false_step_id || null,
      subscribe_check: node.type === 'subscribe_check' ? node.subscribe_check : null,
      subscribe_true_step_id: node.subscribe_true_step_id || null,
      subscribe_false_step_id: node.subscribe_false_step_id || null,
      quiz: node.type === 'quiz' ? stripQuizLegacy(node.quiz) : null,
      ab_test: node.type === 'ab_test' ? node.ab_test : null,
      tag: node.type === 'tag' ? stripTagHelper(node.tag) : null,
      trigger: node.type === 'trigger_keyword' ? node.trigger : null
    }))

    const firstStep = nodes.value.find(n => n.is_first) || nodes.value[0]

    const response = await axios.post(
      getRoute('telegram-funnels.funnels.save-steps', [props.bot.id, props.funnel.id]),
      { steps: stepsData, first_step_id: firstStep?.id }
    )

    // Completion message — funnel-level; alohida PUT (faqat o'zgargan bo'lsa).
    if ((funnelCompletionMessage.value || '') !== (props.funnel?.completion_message || '')) {
      try {
        await axios.put(
          getRoute('telegram-funnels.funnels.update', [props.bot.id, props.funnel.id]),
          { completion_message: funnelCompletionMessage.value || null }
        )
        if (props.funnel) props.funnel.completion_message = funnelCompletionMessage.value || null
      } catch (e) {
        console.warn('Failed to save completion_message', e)
      }
    }

    if (response.data.success) {
      const data = response.data
      if (data.steps) {
        const endNodes = nodes.value.filter(n => n.type === 'end')

        nodes.value = data.steps.map(step => ({
          ...step,
          type: step.step_type,
          x: step.position_x ?? 100,
          y: step.position_y ?? 100,
          content: step.content || { type: 'text', text: '' },
          keyboard: step.keyboard || { type: 'inline', buttons: [] },
          condition: step.condition || { field: '', operator: '', value: '', custom_field: '' },
          condition_true_step_id: step.condition_true_step_id || null,
          condition_false_step_id: step.condition_false_step_id || null,
          subscribe_check: step.subscribe_check || { channel_username: '', not_subscribed_message: '', subscribe_button_text: '' },
          subscribe_true_step_id: step.subscribe_true_step_id || null,
          subscribe_false_step_id: step.subscribe_false_step_id || null,
          quiz: step.quiz || { question: '', options: [], allow_multiple: false, save_answer_to: '' },
          ab_test: step.ab_test || { variants: [] },
          tag: step.tag || { action: 'add', tags: [], new_tag: '' },
          trigger: step.trigger || { keywords: '', match_type: 'contains', is_all_messages: false }
        }))

        nodes.value.push(...endNodes)

        connections.value = []
        nodes.value.forEach(node => {
          if (node.type === 'condition') {
            if (node.condition_true_step_id) connections.value.push({ from: node.id, to: node.condition_true_step_id, type: 'true' })
            if (node.condition_false_step_id) connections.value.push({ from: node.id, to: node.condition_false_step_id, type: 'false' })
          } else if (node.type === 'subscribe_check') {
            if (node.subscribe_true_step_id) connections.value.push({ from: node.id, to: node.subscribe_true_step_id, type: 'subscribed' })
            if (node.subscribe_false_step_id) connections.value.push({ from: node.id, to: node.subscribe_false_step_id, type: 'not_subscribed' })
          } else if (node.type === 'quiz' && node.quiz?.options) {
            node.quiz.options.forEach((option, i) => { if (option.next_step_id) connections.value.push({ from: node.id, to: option.next_step_id, type: `option_${i}` }) })
          } else if (node.type === 'ab_test' && node.ab_test?.variants) {
            node.ab_test.variants.forEach((variant, i) => { if (variant.next_step_id) connections.value.push({ from: node.id, to: variant.next_step_id, type: `variant_${i}` }) })
          } else if (node.next_step_id) connections.value.push({ from: node.id, to: node.next_step_id, type: 'default' })
        })
      }
      showToast('Funnel saqlandi!', 'success')
      isDirty.value = false
      savedAt.value = Date.now()
      validationErrors.value = {}
      clearLocalDraft()
    } else {
      showToast(response.data.message || 'Xatolik yuz berdi', 'error')
    }
  } catch (error) {
    console.error('Save error:', error)
    if (error.response?.status === 419) {
      showToast('Sessiya tugagan. Sahifani yangilang.', 'error')
    } else {
      showToast(error.response?.data?.message || 'Saqlashda xatolik', 'error')
    }
  } finally {
    isSaving.value = false
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Invalid node ring — red pulse to draw attention to validation errors */
.funnel-node--invalid {
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.9), 0 0 0 6px rgba(239, 68, 68, 0.25);
  animation: funnel-node-invalid-pulse 1.4s ease-in-out infinite;
}

@keyframes funnel-node-invalid-pulse {
  0%, 100% {
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.9), 0 0 0 6px rgba(239, 68, 68, 0.25);
  }
  50% {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 1), 0 0 0 10px rgba(239, 68, 68, 0.15);
  }
}

/* Toast enter/leave animations */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(24px);
}

/* ══════════════════════════════════════════════════════════════════
   Connection ports — professional state machine visuals
   ══════════════════════════════════════════════════════════════════ */
.funnel-port {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  cursor: pointer;
  user-select: none;
  transition: transform 0.15s ease, box-shadow 0.2s ease, background-color 0.2s ease;
  /* Invisible hit-area extension for easier clicking */
  position: relative;
}
.funnel-port::before {
  content: '';
  position: absolute;
  inset: -6px;
  border-radius: 9999px;
}
.funnel-port:hover {
  transform: scale(1.2);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
.funnel-port:active {
  transform: scale(0.95);
}

/* Input target-dot: solid filled circle — "receiver" visual */
.funnel-port-icon-target {
  width: 10px;
  height: 10px;
  border-radius: 9999px;
  background-color: currentColor;
  box-shadow: 0 0 0 2px white, 0 0 0 3px currentColor;
}
.dark .funnel-port-icon-target {
  box-shadow: 0 0 0 2px rgb(31 41 55), 0 0 0 3px currentColor;
}

/* Input port — grey default, blue when can receive a connection */
.funnel-port-in {
  background-color: rgb(229 231 235); /* gray-200 */
  color: rgb(75 85 99); /* gray-600 */
}
.dark .funnel-port-in {
  background-color: rgb(75 85 99);
  color: rgb(229 231 235);
}
.funnel-port-in:hover,
.funnel-port--target {
  background-color: rgb(59 130 246); /* blue-500 */
  color: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.35);
}
.funnel-port--target {
  animation: funnel-port-pulse 1.3s ease-in-out infinite;
}
@keyframes funnel-port-pulse {
  0%, 100% { box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.35); }
  50%      { box-shadow: 0 0 0 8px rgba(59, 130, 246, 0.15); }
}

/* Output port — indigo default */
.funnel-port-out {
  background-color: rgb(224 231 255); /* indigo-100 */
  color: rgb(79 70 229); /* indigo-600 */
}
.dark .funnel-port-out {
  background-color: rgb(49 46 129);
  color: rgb(165 180 252);
}
.funnel-port-out:hover {
  background-color: rgb(99 102 241); /* indigo-500 */
  color: white;
}
.funnel-port--source-active {
  background-color: rgb(99 102 241);
  color: white;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.35);
}

/* Branch ports (condition, subscribe_check) */
.funnel-port-branch {
  width: 30px; height: 30px;
}
.funnel-port-branch--yes {
  background-color: rgb(220 252 231);
  color: rgb(22 163 74);
}
.dark .funnel-port-branch--yes { background-color: rgb(20 83 45); color: rgb(134 239 172); }
.funnel-port-branch--yes:hover { background-color: rgb(34 197 94); color: white; }

.funnel-port-branch--no {
  background-color: rgb(254 226 226);
  color: rgb(220 38 38);
}
.dark .funnel-port-branch--no { background-color: rgb(127 29 29); color: rgb(252 165 165); }
.funnel-port-branch--no:hover { background-color: rgb(239 68 68); color: white; }

.funnel-port-branch--cyan {
  background-color: rgb(207 250 254);
  color: rgb(8 145 178);
}
.dark .funnel-port-branch--cyan { background-color: rgb(22 78 99); color: rgb(165 243 252); }
.funnel-port-branch--cyan:hover { background-color: rgb(6 182 212); color: white; }

/* Quiz option / AB variant ports */
.funnel-port-option {
  width: 26px; height: 26px;
  background-color: rgb(224 231 255);
  color: rgb(79 70 229);
  font-weight: 700;
  font-size: 11px;
}
.dark .funnel-port-option { background-color: rgb(49 46 129); color: rgb(165 180 252); }
.funnel-port-option:hover { background-color: rgb(99 102 241); color: white; }

.funnel-port-variant {
  width: 30px; height: 30px;
  background-color: rgb(254 243 199);
  color: rgb(180 83 9);
  font-weight: 700;
  font-size: 12px;
}
.dark .funnel-port-variant { background-color: rgb(120 53 15); color: rgb(253 230 138); }
.funnel-port-variant:hover { background-color: rgb(245 158 11); color: white; }

/* Canvas cursor when drawing a connection */
.canvas-connecting,
.canvas-connecting * {
  cursor: crosshair !important;
}
</style>
