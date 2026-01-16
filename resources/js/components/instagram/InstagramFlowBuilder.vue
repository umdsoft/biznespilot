<template>
  <div class="h-screen flex flex-col bg-gray-50 dark:bg-gray-900">
    <!-- Top Toolbar -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between shadow-sm">
      <div class="flex items-center gap-4">
        <button
          @click="$emit('close')"
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
          <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/20">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div>
            <input
              v-model="settings.name"
              type="text"
              class="bg-transparent text-lg font-bold text-gray-900 dark:text-white border-none focus:outline-none focus:ring-0 p-0 w-64 placeholder-gray-400 dark:placeholder-gray-500"
              placeholder="Avtomatizatsiya nomi..."
            />
            <p class="text-xs text-gray-500 dark:text-gray-400">Visual Flow Builder</p>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <!-- Status Selector -->
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
          <button
            @click="settings.status = 'active'"
            :class="[
              'px-3 py-1.5 rounded text-xs font-medium transition-colors',
              settings.status === 'active' ? 'bg-green-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
            ]"
          >
            Faol
          </button>
          <button
            @click="settings.status = 'draft'"
            :class="[
              'px-3 py-1.5 rounded text-xs font-medium transition-colors',
              settings.status === 'draft' ? 'bg-gray-500 dark:bg-gray-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
            ]"
          >
            Qoralama
          </button>
          <button
            @click="settings.status = 'paused'"
            :class="[
              'px-3 py-1.5 rounded text-xs font-medium transition-colors',
              settings.status === 'paused' ? 'bg-yellow-500 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
            ]"
          >
            Pauza
          </button>
        </div>

        <!-- AI Toggle -->
        <button
          @click="settings.is_ai_enabled = !settings.is_ai_enabled"
          :class="[
            'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
            settings.is_ai_enabled ? 'bg-purple-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
          ]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
          AI
        </button>

        <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

        <!-- Zoom Controls -->
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
          <button @click="zoomOut" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-gray-600 dark:text-gray-300">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
            </svg>
          </button>
          <span class="text-xs font-medium w-12 text-center text-gray-600 dark:text-gray-300">{{ Math.round(zoom * 100) }}%</span>
          <button @click="zoomIn" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-gray-600 dark:text-gray-300">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
          <button @click="resetZoom" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-gray-600 dark:text-gray-300">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
          </button>
        </div>

        <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

        <button
          @click="saveFlow"
          :disabled="isSaving || !settings.name"
          :class="[
            'inline-flex items-center px-5 py-2 text-sm font-medium rounded-lg transition-colors shadow-sm',
            settings.name
              ? 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white'
              : 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed'
          ]"
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

        <div class="flex-1 overflow-y-auto p-3 space-y-2">
          <!-- Triggers Section -->
          <div class="mb-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 px-1">Triggerlar</p>

            <!-- DM Trigger -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'trigger_keyword_dm')"
              class="p-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-purple-500/20 transition-all mb-2"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">DM Kalit so'z</p>
                  <p class="text-xs opacity-80">DMda so'z topilganda</p>
                </div>
              </div>
            </div>

            <!-- Comment Trigger -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'trigger_keyword_comment')"
              class="p-3 bg-gradient-to-r from-pink-600 to-rose-600 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-pink-500/20 transition-all mb-2"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">Comment Kalit so'z</p>
                  <p class="text-xs opacity-80">Commentda topilganda</p>
                </div>
              </div>
            </div>

            <!-- Story Mention Trigger -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'trigger_story_mention')"
              class="p-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-orange-500/20 transition-all mb-2"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">Story Mention</p>
                  <p class="text-xs opacity-80">Storyda etiketlanganda</p>
                </div>
              </div>
            </div>

            <!-- New Follower Trigger -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'trigger_new_follower')"
              class="p-3 bg-gradient-to-r from-cyan-500 to-blue-500 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-cyan-500/20 transition-all"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">Yangi Follower</p>
                  <p class="text-xs opacity-80">Follow qilganda</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Conditions Section -->
          <div class="mb-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 px-1">Shartlar</p>

            <!-- Is Follower Condition -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'condition_is_follower')"
              class="p-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-yellow-500/20 transition-all mb-2"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">Follower tekshir</p>
                  <p class="text-xs opacity-80">Obunachimi?</p>
                </div>
              </div>
            </div>

            <!-- Has Tag Condition -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'condition_has_tag')"
              class="p-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-emerald-500/20 transition-all"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">Tag tekshir</p>
                  <p class="text-xs opacity-80">Tag mavjudmi?</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions Section -->
          <div class="mb-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 px-1">Harakatlar</p>

            <!-- Send DM Action -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'action_send_dm')"
              class="p-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-blue-500/20 transition-all mb-2"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">DM Yuborish</p>
                  <p class="text-xs opacity-80">Xabar yozish</p>
                </div>
              </div>
            </div>

            <!-- Send Media Action -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'action_send_media')"
              class="p-3 bg-gradient-to-r from-pink-500 to-rose-500 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-pink-500/20 transition-all mb-2"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">Media Yuborish</p>
                  <p class="text-xs opacity-80">Rasm/Video</p>
                </div>
              </div>
            </div>

            <!-- AI Response Action -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'action_ai_response')"
              class="p-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-violet-500/20 transition-all mb-2"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">AI Javob</p>
                  <p class="text-xs opacity-80">Sun'iy intellekt</p>
                </div>
              </div>
            </div>

            <!-- Add Tag Action -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'action_add_tag')"
              class="p-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-green-500/20 transition-all mb-2"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">Tag Qo'shish</p>
                  <p class="text-xs opacity-80">Belgilash</p>
                </div>
              </div>
            </div>

            <!-- Delay Action -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'action_delay')"
              class="p-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-gray-500/20 transition-all mb-2"
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

            <!-- Reply Comment Action -->
            <div
              draggable="true"
              @dragstart="onDragStart($event, 'action_reply_comment')"
              class="p-3 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-lg cursor-move hover:shadow-lg hover:shadow-indigo-500/20 transition-all"
            >
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-sm">Commentga Javob</p>
                  <p class="text-xs opacity-80">Reply qilish</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Canvas Area -->
      <div
        ref="canvasContainer"
        class="flex-1 overflow-hidden relative bg-gray-100 dark:bg-gray-900"
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
          class="absolute inset-0 pointer-events-none canvas-grid"
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
                <polygon points="0 0, 10 3.5, 0 7" fill="#8b5cf6" />
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
              width: '240px'
            }"
            @mousedown.stop="startDragNode($event, node)"
            @click.stop="selectNode(node)"
            :class="[
              'rounded-xl shadow-lg transition-all cursor-move group',
              selectedNode?.id === node.id ? 'ring-2 ring-purple-500 ring-offset-2 ring-offset-gray-100 dark:ring-offset-gray-900' : ''
            ]"
          >
            <!-- Node Header -->
            <div :class="['rounded-t-xl px-4 py-3 flex items-center justify-between', getNodeHeaderClass(node.type)]">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center" v-html="getNodeIconSvg(node.type)">
                </div>
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
            <div class="bg-white dark:bg-gray-800 rounded-b-xl p-3 border border-gray-200 dark:border-gray-700 border-t-0">
              <!-- Trigger Node Content -->
              <template v-if="node.type.startsWith('trigger_')">
                <p v-if="node.keywords?.length" class="text-xs text-gray-600 dark:text-gray-400">
                  Kalit so'zlar: <span class="text-purple-600 dark:text-purple-400">{{ node.keywords.join(', ') }}</span>
                </p>
                <p v-else class="text-xs text-gray-400 dark:text-gray-500 italic">Sozlamalarni kiriting...</p>
              </template>

              <!-- Condition Node Content -->
              <template v-if="node.type.startsWith('condition_')">
                <div class="text-xs space-y-1">
                  <div class="flex items-center gap-1 text-yellow-600 dark:text-yellow-400">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ getConditionDescription(node) }}</span>
                  </div>
                </div>
              </template>

              <!-- Action Node Content -->
              <template v-if="node.type.startsWith('action_')">
                <p v-if="node.data?.message" class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                  {{ node.data.message }}
                </p>
                <p v-else-if="node.data?.delay_seconds" class="text-xs text-gray-600 dark:text-gray-400">
                  {{ node.data.delay_seconds }} soniya kutish
                </p>
                <p v-else-if="node.data?.tag" class="text-xs text-gray-600 dark:text-gray-400">
                  Tag: <span class="text-green-600 dark:text-green-400">#{{ node.data.tag }}</span>
                </p>
                <p v-else class="text-xs text-gray-400 dark:text-gray-500 italic">Sozlamalarni kiriting...</p>
              </template>

              <!-- Connection Points -->
              <div class="flex justify-between mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <!-- Input Point (not for triggers) -->
                <div
                  v-if="!node.type.startsWith('trigger_')"
                  class="w-6 h-6 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-purple-500 transition-colors"
                  :class="{ 'ring-2 ring-purple-400 ring-offset-1 ring-offset-white dark:ring-offset-gray-800': drawingConnection && drawingConnection.from !== node.id }"
                  @mouseup.stop="endConnection(node.id, $event)"
                  title="Kirish nuqtasi"
                >
                  <svg class="w-3 h-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                  </svg>
                </div>
                <div v-else class="w-6"></div>

                <!-- Output Points for Conditions (Ha/Yo'q) -->
                <template v-if="node.type.startsWith('condition_')">
                  <div class="flex gap-2">
                    <!-- Ha (True) output -->
                    <div class="flex flex-col items-center gap-1">
                      <div
                        class="w-6 h-6 bg-green-900/50 text-green-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-green-500 hover:text-white transition-colors"
                        @mousedown.stop="startConnection(node.id, $event, 'true')"
                        title="Ha - shart bajarilsa"
                      >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                      </div>
                      <span class="text-[10px] font-medium text-green-400">Ha</span>
                    </div>

                    <!-- Yo'q (False) output -->
                    <div class="flex flex-col items-center gap-1">
                      <div
                        class="w-6 h-6 bg-red-900/50 text-red-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-red-500 hover:text-white transition-colors"
                        @mousedown.stop="startConnection(node.id, $event, 'false')"
                        title="Yo'q - shart bajarilmasa"
                      >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </div>
                      <span class="text-[10px] font-medium text-red-400">Yo'q</span>
                    </div>
                  </div>
                </template>

                <!-- Single Output Point (for triggers and actions) -->
                <div
                  v-else
                  class="w-6 h-6 bg-purple-900/50 text-purple-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-purple-500 hover:text-white transition-colors"
                  @mousedown.stop="startConnection(node.id, $event, 'default')"
                  title="Chiqish nuqtasi - tortib tashlang"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                  </svg>
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
              <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Flow bo'sh</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Chap paneldan triggerlarni tortib tashlang</p>
          </div>
        </div>
      </div>

      <!-- Right Sidebar - Node Editor -->
      <div class="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-semibold text-gray-900 dark:text-white text-sm">
            {{ selectedNode ? 'Element tahrirlash' : 'Element tanlang' }}
          </h3>
        </div>

        <div v-if="!selectedNode" class="flex-1 flex items-center justify-center p-4">
          <div class="text-center text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
            </svg>
            <p class="text-sm">Canvas'dan element tanlang</p>
          </div>
        </div>

        <div v-else class="flex-1 overflow-y-auto p-4 space-y-4">
          <!-- Node Name -->
          <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Element nomi</label>
            <input
              v-model="selectedNode.name"
              type="text"
              class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 placeholder-gray-400 dark:placeholder-gray-500"
              placeholder="Element nomini kiriting..."
            />
          </div>

          <!-- Trigger Settings -->
          <template v-if="selectedNode.type.startsWith('trigger_')">
            <!-- Keywords for DM/Comment triggers -->
            <div v-if="['trigger_keyword_dm', 'trigger_keyword_comment'].includes(selectedNode.type)">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Kalit so'zlar</label>
              <div class="flex gap-2 mb-2">
                <input
                  v-model="newKeyword"
                  type="text"
                  @keydown.enter.prevent="addKeyword"
                  class="flex-1 px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 placeholder-gray-400 dark:placeholder-gray-500"
                  placeholder="Kalit so'z..."
                />
                <button
                  @click="addKeyword"
                  class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition-colors"
                >
                  +
                </button>
              </div>
              <div v-if="selectedNode.keywords?.length" class="flex flex-wrap gap-1">
                <span
                  v-for="(kw, i) in selectedNode.keywords"
                  :key="i"
                  class="inline-flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 text-xs rounded-full"
                >
                  {{ kw }}
                  <button @click="removeKeyword(i)" class="ml-1 hover:text-white">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
              </div>
            </div>
          </template>

          <!-- Condition Settings -->
          <template v-if="selectedNode.type.startsWith('condition_')">
            <div v-if="selectedNode.type === 'condition_has_tag'">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Tekshiriladigan tag</label>
              <input
                v-model="selectedNode.data.tag"
                type="text"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="Tag nomini kiriting..."
              />
            </div>
          </template>

          <!-- Action Settings -->
          <template v-if="selectedNode.type.startsWith('action_')">
            <!-- Send DM -->
            <div v-if="selectedNode.type === 'action_send_dm'">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Xabar matni</label>
              <textarea
                ref="dmMessageTextarea"
                v-model="selectedNode.data.message"
                rows="4"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="Xabar matni..."
              ></textarea>
              <div class="flex items-center gap-1 mt-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">O'zgaruvchilar:</span>
                <button
                  v-for="variable in messageVariables"
                  :key="variable.value"
                  @click="insertVariable(variable.value, 'dmMessageTextarea')"
                  type="button"
                  class="px-2 py-1 text-xs bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-md hover:bg-purple-200 dark:hover:bg-purple-900 transition-colors cursor-pointer"
                >
                  {{ variable.label }}
                </button>
              </div>
            </div>

            <!-- AI Response -->
            <div v-if="selectedNode.type === 'action_ai_response'">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">AI uchun kontekst</label>
              <textarea
                ref="aiContextTextarea"
                v-model="selectedNode.data.ai_context"
                rows="4"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="AI qanday javob berishi kerak..."
              ></textarea>
              <div class="flex items-center gap-1 mt-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">O'zgaruvchilar:</span>
                <button
                  v-for="variable in messageVariables"
                  :key="variable.value"
                  @click="insertVariable(variable.value, 'aiContextTextarea')"
                  type="button"
                  class="px-2 py-1 text-xs bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-md hover:bg-purple-200 dark:hover:bg-purple-900 transition-colors cursor-pointer"
                >
                  {{ variable.label }}
                </button>
              </div>
            </div>

            <!-- Delay -->
            <div v-if="selectedNode.type === 'action_delay'">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Kutish vaqti (soniya)</label>
              <input
                v-model.number="selectedNode.data.delay_seconds"
                type="number"
                min="1"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="5"
              />
            </div>

            <!-- Add Tag -->
            <div v-if="selectedNode.type === 'action_add_tag'">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Qo'shiladigan tag</label>
              <input
                v-model="selectedNode.data.tag"
                type="text"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="Tag nomi..."
              />
            </div>

            <!-- Reply Comment -->
            <div v-if="selectedNode.type === 'action_reply_comment'">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Javob matni</label>
              <textarea
                ref="replyCommentTextarea"
                v-model="selectedNode.data.message"
                rows="3"
                class="w-full px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none placeholder-gray-400 dark:placeholder-gray-500"
                placeholder="Commentga javob..."
              ></textarea>
              <div class="flex items-center gap-1 mt-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">O'zgaruvchilar:</span>
                <button
                  v-for="variable in messageVariables"
                  :key="variable.value"
                  @click="insertVariable(variable.value, 'replyCommentTextarea')"
                  type="button"
                  class="px-2 py-1 text-xs bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-md hover:bg-purple-200 dark:hover:bg-purple-900 transition-colors cursor-pointer"
                >
                  {{ variable.label }}
                </button>
              </div>
            </div>
          </template>

          <!-- Delete Button -->
          <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <button
              @click="deleteNode(selectedNode.id)"
              class="w-full px-4 py-2 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              Elementni o'chirish
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  automationId: [String, Number],
  automationName: String,
  automationStatus: {
    type: String,
    default: 'draft'
  },
  automationAiEnabled: {
    type: Boolean,
    default: false
  },
  initialNodes: {
    type: Array,
    default: () => []
  },
  initialConnections: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'save'])

// Settings state
const settings = reactive({
  name: props.automationName || '',
  description: '',
  status: props.automationStatus || 'draft',
  is_ai_enabled: props.automationAiEnabled || false
})

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

// Dragging state
const draggingNode = ref(null)
const dragOffset = reactive({ x: 0, y: 0 })

// Connection drawing state
const drawingConnection = ref(null)
const mousePosition = reactive({ x: 0, y: 0 })

// Form helpers
const newKeyword = ref('')

// Textarea refs
const dmMessageTextarea = ref(null)
const aiContextTextarea = ref(null)
const replyCommentTextarea = ref(null)

// Message variables
const messageVariables = [
  { label: '{username}', value: '{username}' },
  { label: '{full_name}', value: '{full_name}' }
]

// Insert variable into textarea
const insertVariable = (variable, textareaRef) => {
  const textareaRefs = {
    dmMessageTextarea,
    aiContextTextarea,
    replyCommentTextarea
  }

  const textarea = textareaRefs[textareaRef]?.value
  if (!textarea || !selectedNode.value) return

  const start = textarea.selectionStart
  const end = textarea.selectionEnd

  // Determine which data field to update
  let currentValue = ''
  let fieldName = ''

  if (textareaRef === 'dmMessageTextarea') {
    currentValue = selectedNode.value.data?.message || ''
    fieldName = 'message'
  } else if (textareaRef === 'aiContextTextarea') {
    currentValue = selectedNode.value.data?.ai_context || ''
    fieldName = 'ai_context'
  } else if (textareaRef === 'replyCommentTextarea') {
    currentValue = selectedNode.value.data?.message || ''
    fieldName = 'message'
  }

  // Insert variable at cursor position
  const newValue = currentValue.substring(0, start) + variable + currentValue.substring(end)

  // Update the node data
  if (!selectedNode.value.data) {
    selectedNode.value.data = {}
  }
  selectedNode.value.data[fieldName] = newValue

  // Refocus and set cursor position after the inserted variable
  setTimeout(() => {
    textarea.focus()
    const newPosition = start + variable.length
    textarea.setSelectionRange(newPosition, newPosition)
  }, 0)
}

// Saving state
const isSaving = ref(false)

// Global mouseup handler to cancel connection drawing
const handleGlobalMouseUp = () => {
  if (drawingConnection.value) {
    drawingConnection.value = null
  }
}

// Initialize from props
onMounted(() => {
  if (props.initialNodes.length > 0) {
    nodes.value = props.initialNodes.map(node => ({
      ...node,
      x: node.position?.x ?? node.x ?? 100,
      y: node.position?.y ?? node.y ?? 100,
      data: node.data || {},
      keywords: node.keywords || []
    }))
  }

  if (props.initialConnections.length > 0) {
    connections.value = [...props.initialConnections]
  }

  // Add global mouseup listener to cancel connections
  document.addEventListener('mouseup', handleGlobalMouseUp)
})

// Cleanup on unmount
onUnmounted(() => {
  document.removeEventListener('mouseup', handleGlobalMouseUp)
})

// Zoom functions
const zoomIn = () => { zoom.value = Math.min(2, zoom.value + 0.1) }
const zoomOut = () => { zoom.value = Math.max(0.25, zoom.value - 0.1) }
const resetZoom = () => { zoom.value = 1; panOffset.x = 100; panOffset.y = 100 }
const onWheel = (e) => { e.preventDefault(); e.deltaY < 0 ? zoomIn() : zoomOut() }

// Pan functions
const startPan = (e) => {
  if (e.target === canvasContainer.value || e.target.tagName === 'svg') {
    isPanning.value = true
    panStart.x = e.clientX - panOffset.x
    panStart.y = e.clientY - panOffset.y
  }
}

const onPan = (e) => {
  mousePosition.x = (e.clientX - panOffset.x) / zoom.value
  mousePosition.y = (e.clientY - canvasContainer.value?.getBoundingClientRect().top - panOffset.y) / zoom.value

  if (isPanning.value) {
    panOffset.x = e.clientX - panStart.x
    panOffset.y = e.clientY - panStart.y
  }

  if (draggingNode.value) {
    const rect = canvasContainer.value.getBoundingClientRect()
    draggingNode.value.x = (e.clientX - rect.left - panOffset.x) / zoom.value - dragOffset.x
    draggingNode.value.y = (e.clientY - rect.top - panOffset.y) / zoom.value - dragOffset.y
  }
}

const endPan = () => {
  isPanning.value = false
  draggingNode.value = null
  // Cancel any drawing connection when mouse is released
  if (drawingConnection.value) {
    drawingConnection.value = null
  }
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
    id: `node-${Date.now()}`,
    name: getNodeLabel(type),
    type,
    x, y,
    data: getDefaultNodeData(type),
    keywords: type.includes('keyword') ? [] : undefined
  }

  nodes.value.push(newNode)
  selectedNode.value = newNode
}

const getDefaultNodeData = (type) => {
  switch (type) {
    case 'action_send_dm':
    case 'action_reply_comment':
      return { message: '' }
    case 'action_ai_response':
      return { ai_context: '' }
    case 'action_delay':
      return { delay_seconds: 5 }
    case 'action_add_tag':
    case 'condition_has_tag':
      return { tag: '' }
    default:
      return {}
  }
}

// Node dragging
const startDragNode = (e, node) => {
  const rect = canvasContainer.value.getBoundingClientRect()
  dragOffset.x = (e.clientX - rect.left - panOffset.x) / zoom.value - node.x
  dragOffset.y = (e.clientY - rect.top - panOffset.y) / zoom.value - node.y
  draggingNode.value = node
}

const selectNode = (node) => { selectedNode.value = node }

const deleteNode = (nodeId) => {
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
  drawingConnection.value = { from: nodeId, type: connectionType }
}

const endConnection = (nodeId, e) => {
  // Stop event propagation to prevent global handler from interfering
  if (e) {
    e.preventDefault()
    e.stopPropagation()
  }

  if (drawingConnection.value && drawingConnection.value.from !== nodeId) {
    const connectionType = drawingConnection.value.type || 'default'

    // Remove existing connections of the same type from this node
    connections.value = connections.value.filter(c =>
      !(c.from === drawingConnection.value.from && c.type === connectionType)
    )

    // Add new connection
    connections.value.push({
      from: drawingConnection.value.from,
      to: nodeId,
      type: connectionType
    })
  }

  // Always clear the drawing connection
  drawingConnection.value = null
}

const cancelConnection = () => { drawingConnection.value = null }

const updateDrawingConnection = (e) => {
  if (!drawingConnection.value) return
  const rect = canvasContainer.value?.getBoundingClientRect()
  if (rect) {
    mousePosition.x = (e.clientX - rect.left - panOffset.x) / zoom.value
    mousePosition.y = (e.clientY - rect.top - panOffset.y) / zoom.value
  }
}

const deleteConnection = (connection) => {
  connections.value = connections.value.filter(c =>
    !(c.from === connection.from && c.to === connection.to && c.type === connection.type)
  )
}

// Keyword management
const addKeyword = () => {
  if (!selectedNode.value || !newKeyword.value.trim()) return
  if (!selectedNode.value.keywords) selectedNode.value.keywords = []
  selectedNode.value.keywords.push(newKeyword.value.trim().toLowerCase())
  newKeyword.value = ''
}

const removeKeyword = (index) => {
  if (selectedNode.value?.keywords) {
    selectedNode.value.keywords.splice(index, 1)
  }
}

// Get connection path
const getConnectionPath = (connection) => {
  const fromNode = nodes.value.find(n => n.id === connection.from)
  const toNode = nodes.value.find(n => n.id === connection.to)
  if (!fromNode || !toNode) return ''

  let fromX, fromY

  if (fromNode.type.startsWith('condition_')) {
    if (connection.type === 'true') { fromX = fromNode.x + 80; fromY = fromNode.y + 130 }
    else if (connection.type === 'false') { fromX = fromNode.x + 160; fromY = fromNode.y + 130 }
    else { fromX = fromNode.x + 120; fromY = fromNode.y + 120 }
  } else {
    fromX = fromNode.x + 120
    fromY = fromNode.y + 120
  }

  const toX = toNode.x + 120
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

  if (fromNode.type.startsWith('condition_')) {
    if (drawingConnection.value.type === 'true') { fromX = fromNode.x + 80; fromY = fromNode.y + 130 }
    else if (drawingConnection.value.type === 'false') { fromX = fromNode.x + 160; fromY = fromNode.y + 130 }
    else { fromX = fromNode.x + 120; fromY = fromNode.y + 120 }
  } else {
    fromX = fromNode.x + 120
    fromY = fromNode.y + 120
  }

  const deltaY = Math.abs(mousePosition.y - fromY)
  const controlOffset = Math.min(deltaY * 0.4, 60)

  return `M ${fromX} ${fromY} C ${fromX} ${fromY + controlOffset}, ${mousePosition.x} ${mousePosition.y - controlOffset}, ${mousePosition.x} ${mousePosition.y}`
}

const getConnectionColor = (connection) => {
  if (connection.type === 'true') return '#22c55e'
  if (connection.type === 'false') return '#ef4444'
  return '#8b5cf6'
}

const getArrowMarker = (connection) => {
  if (connection.type === 'true') return 'arrowhead-green'
  if (connection.type === 'false') return 'arrowhead-red'
  return 'arrowhead'
}

const getDrawingConnectionColor = () => {
  if (!drawingConnection.value) return '#8b5cf6'
  if (drawingConnection.value.type === 'true') return '#22c55e'
  if (drawingConnection.value.type === 'false') return '#ef4444'
  return '#8b5cf6'
}

const getConnectionLabelPosition = (connection) => {
  const fromNode = nodes.value.find(n => n.id === connection.from)
  const toNode = nodes.value.find(n => n.id === connection.to)
  if (!fromNode || !toNode) return { x: 0, y: 0 }

  let fromX
  if (fromNode.type.startsWith('condition_')) {
    if (connection.type === 'true') fromX = fromNode.x + 80
    else if (connection.type === 'false') fromX = fromNode.x + 160
    else fromX = fromNode.x + 120
  } else fromX = fromNode.x + 120

  const fromY = fromNode.type.startsWith('condition_') ? fromNode.y + 130 : fromNode.y + 120
  const toX = toNode.x + 120
  const toY = toNode.y + 10

  return { x: (fromX + toX) / 2 - 10, y: (fromY + toY) / 2 }
}

// Helper functions
const getNodeHeaderClass = (type) => {
  const classes = {
    'trigger_keyword_dm': 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white',
    'trigger_keyword_comment': 'bg-gradient-to-r from-pink-600 to-rose-600 text-white',
    'trigger_story_mention': 'bg-gradient-to-r from-orange-500 to-amber-500 text-white',
    'trigger_story_reply': 'bg-gradient-to-r from-orange-500 to-amber-500 text-white',
    'trigger_new_follower': 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white',
    'condition_is_follower': 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white',
    'condition_has_tag': 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white',
    'action_send_dm': 'bg-gradient-to-r from-blue-600 to-blue-700 text-white',
    'action_send_media': 'bg-gradient-to-r from-pink-500 to-rose-500 text-white',
    'action_ai_response': 'bg-gradient-to-r from-violet-600 to-purple-600 text-white',
    'action_add_tag': 'bg-gradient-to-r from-green-600 to-emerald-600 text-white',
    'action_delay': 'bg-gradient-to-r from-gray-600 to-gray-700 text-white',
    'action_reply_comment': 'bg-gradient-to-r from-indigo-600 to-blue-600 text-white',
  }
  return classes[type] || 'bg-gradient-to-r from-gray-600 to-gray-700 text-white'
}

const getNodeLabel = (type) => {
  const labels = {
    'trigger_keyword_dm': 'DM Kalit so\'z',
    'trigger_keyword_comment': 'Comment Kalit so\'z',
    'trigger_story_mention': 'Story Mention',
    'trigger_story_reply': 'Story Javob',
    'trigger_new_follower': 'Yangi Follower',
    'condition_is_follower': 'Follower tekshir',
    'condition_has_tag': 'Tag tekshir',
    'action_send_dm': 'DM Yuborish',
    'action_send_media': 'Media Yuborish',
    'action_ai_response': 'AI Javob',
    'action_add_tag': 'Tag Qo\'shish',
    'action_delay': 'Kutish',
    'action_reply_comment': 'Comment Javob',
  }
  return labels[type] || type
}

const getNodeIconSvg = (type) => {
  const icons = {
    'trigger_keyword_dm': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>',
    'trigger_keyword_comment': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>',
    'trigger_story_mention': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>',
    'trigger_story_reply': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>',
    'trigger_new_follower': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>',
    'condition_is_follower': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    'condition_has_tag': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>',
    'action_send_dm': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>',
    'action_send_media': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>',
    'action_ai_response': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>',
    'action_add_tag': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>',
    'action_delay': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    'action_reply_comment': '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>',
  }
  return icons[type] || '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>'
}

const getConditionDescription = (node) => {
  switch (node.type) {
    case 'condition_is_follower':
      return 'Foydalanuvchi followermi?'
    case 'condition_has_tag':
      return node.data?.tag ? `Tag: #${node.data.tag}` : 'Tag tekshirish'
    default:
      return 'Shart'
  }
}

// Save function
const saveFlow = async () => {
  if (!settings.name) {
    alert('Avtomatizatsiya nomini kiriting')
    return
  }

  isSaving.value = true
  try {
    const flowData = {
      settings: {
        name: settings.name,
        description: settings.description,
        status: settings.status,
        is_ai_enabled: settings.is_ai_enabled
      },
      nodes: nodes.value.map(node => ({
        id: node.id,
        type: node.type,
        name: node.name,
        x: Math.round(node.x),
        y: Math.round(node.y),
        data: node.data,
        keywords: node.keywords
      })),
      connections: connections.value
    }

    emit('save', flowData)
  } catch (error) {
    console.error('Save error:', error)
    alert('Saqlashda xatolik')
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

.canvas-grid {
  background-image: radial-gradient(circle, #d1d5db 1px, transparent 1px);
  background-size: 20px 20px;
}

:root.dark .canvas-grid,
.dark .canvas-grid {
  background-image: radial-gradient(circle, #374151 1px, transparent 1px);
}

@media (prefers-color-scheme: dark) {
  .canvas-grid {
    background-image: radial-gradient(circle, #374151 1px, transparent 1px);
  }
}
</style>
