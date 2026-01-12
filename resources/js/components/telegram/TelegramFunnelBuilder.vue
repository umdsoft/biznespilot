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

        <div class="flex-1 overflow-y-auto p-3 space-y-2">
          <!-- Start Node -->
          <div
            draggable="true"
            @dragstart="onDragStart($event, 'start')"
            class="p-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg cursor-move hover:shadow-lg transition-shadow"
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
              selectedNode?.id === node.id ? 'ring-2 ring-blue-500 ring-offset-2' : ''
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
              <!-- Regular node content (message, input, action, delay, start, end) -->
              <template v-if="!['condition', 'subscribe_check', 'quiz', 'ab_test', 'tag'].includes(node.type)">
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
              <div v-if="!['condition', 'subscribe_check', 'quiz', 'ab_test'].includes(node.type)" class="flex justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <!-- Input Point -->
                <div
                  v-if="node.type !== 'start'"
                  class="w-6 h-6 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-500 hover:text-white transition-colors"
                  :class="{ 'ring-2 ring-blue-400 ring-offset-1': drawingConnection && drawingConnection.from !== node.id }"
                  @mouseup.stop="endConnection(node.id)"
                  title="Kirish nuqtasi"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                  </svg>
                </div>
                <div v-else class="w-6"></div>

                <!-- Output Point -->
                <div
                  v-if="node.type !== 'end'"
                  class="w-6 h-6 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-indigo-500 hover:text-white transition-colors"
                  @mousedown.stop="startConnection(node.id, $event, 'default')"
                  title="Chiqish nuqtasi - tortib tashlang"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                  </svg>
                </div>
              </div>

              <!-- Connection Points for Condition Node (Two outputs: Ha/Yo'q) -->
              <div v-if="node.type === 'condition'" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <!-- Input Point -->
                <div class="flex justify-center mb-3">
                  <div
                    class="w-6 h-6 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-500 hover:text-white transition-colors"
                    :class="{ 'ring-2 ring-blue-400 ring-offset-1': drawingConnection && drawingConnection.from !== node.id }"
                    @mouseup.stop="endConnection(node.id)"
                    title="Kirish nuqtasi"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                  </div>
                </div>

                <!-- Two Output Points: Ha (True) / Yo'q (False) -->
                <div class="flex justify-between items-center">
                  <!-- Ha (True) output -->
                  <div class="flex flex-col items-center gap-1">
                    <div
                      class="w-7 h-7 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-green-500 hover:text-white transition-colors font-bold text-xs"
                      @mousedown.stop="startConnection(node.id, $event, 'true')"
                      title="Ha - shart bajarilsa"
                    >
                      checkmark
                    </div>
                    <span class="text-[10px] font-medium text-green-600 dark:text-green-400">Ha</span>
                  </div>

                  <!-- Yo'q (False) output -->
                  <div class="flex flex-col items-center gap-1">
                    <div
                      class="w-7 h-7 bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-red-500 hover:text-white transition-colors font-bold text-xs"
                      @mousedown.stop="startConnection(node.id, $event, 'false')"
                      title="Yo'q - shart bajarilmasa"
                    >
                      x
                    </div>
                    <span class="text-[10px] font-medium text-red-600 dark:text-red-400">Yo'q</span>
                  </div>
                </div>
              </div>

              <!-- Connection Points for Subscribe Check Node (Two outputs: Obuna/Obuna emas) -->
              <div v-if="node.type === 'subscribe_check'" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <!-- Input Point -->
                <div class="flex justify-center mb-3">
                  <div
                    class="w-6 h-6 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-500 hover:text-white transition-colors"
                    :class="{ 'ring-2 ring-blue-400 ring-offset-1': drawingConnection && drawingConnection.from !== node.id }"
                    @mouseup.stop="endConnection(node.id)"
                    title="Kirish nuqtasi"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                  </div>
                </div>

                <!-- Two Output Points: Obuna bo'lgan / Obuna bo'lmagan -->
                <div class="flex justify-between items-center">
                  <div class="flex flex-col items-center gap-1">
                    <div
                      class="w-7 h-7 bg-cyan-100 dark:bg-cyan-900 text-cyan-600 dark:text-cyan-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-cyan-500 hover:text-white transition-colors font-bold text-xs"
                      @mousedown.stop="startConnection(node.id, $event, 'subscribed')"
                      title="Obuna bo'lgan"
                    >
                      checkmark
                    </div>
                    <span class="text-[10px] font-medium text-cyan-600 dark:text-cyan-400">Obuna</span>
                  </div>

                  <div class="flex flex-col items-center gap-1">
                    <div
                      class="w-7 h-7 bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-red-500 hover:text-white transition-colors font-bold text-xs"
                      @mousedown.stop="startConnection(node.id, $event, 'not_subscribed')"
                      title="Obuna bo'lmagan"
                    >
                      x
                    </div>
                    <span class="text-[10px] font-medium text-red-600 dark:text-red-400">Yo'q</span>
                  </div>
                </div>
              </div>

              <!-- Connection Points for Quiz Node (Multiple outputs based on options) -->
              <div v-if="node.type === 'quiz'" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <!-- Input Point -->
                <div class="flex justify-center mb-3">
                  <div
                    class="w-6 h-6 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-500 hover:text-white transition-colors"
                    :class="{ 'ring-2 ring-blue-400 ring-offset-1': drawingConnection && drawingConnection.from !== node.id }"
                    @mouseup.stop="endConnection(node.id)"
                    title="Kirish nuqtasi"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                  </div>
                </div>

                <!-- Output Points for each option -->
                <div class="flex flex-wrap justify-center gap-2">
                  <div v-for="(option, i) in (node.quiz?.options || [])" :key="i" class="flex flex-col items-center gap-1">
                    <div
                      class="w-6 h-6 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-indigo-500 hover:text-white transition-colors font-bold text-[10px]"
                      @mousedown.stop="startConnection(node.id, $event, `option_${i}`)"
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
                <!-- Input Point -->
                <div class="flex justify-center mb-3">
                  <div
                    class="w-6 h-6 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-500 hover:text-white transition-colors"
                    :class="{ 'ring-2 ring-blue-400 ring-offset-1': drawingConnection && drawingConnection.from !== node.id }"
                    @mouseup.stop="endConnection(node.id)"
                    title="Kirish nuqtasi"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                  </div>
                </div>

                <!-- Output Points for each variant -->
                <div class="flex justify-center gap-3">
                  <div v-for="(variant, i) in (node.ab_test?.variants || [])" :key="i" class="flex flex-col items-center gap-1">
                    <div
                      class="w-7 h-7 bg-amber-100 dark:bg-amber-900 text-amber-600 dark:text-amber-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-amber-500 hover:text-white transition-colors font-bold text-xs"
                      @mousedown.stop="startConnection(node.id, $event, `variant_${i}`)"
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
          <div v-if="selectedNode.type === 'input'">
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

          <!-- Action Type -->
          <div v-if="selectedNode.type === 'action'">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Amal turi</label>
            <select
              v-model="selectedNode.action_type"
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

          <!-- Next Step Connection -->
          <div>
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
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, h } from 'vue'
import { Link } from '@inertiajs/vue3'

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
const isDrawing = ref(false)

// Saving state
const isSaving = ref(false)

// Initialize nodes from props
onMounted(() => {
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

const endPan = () => { isPanning.value = false; draggingNode.value = null }

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
    is_first: type === 'start' || nodes.value.length === 0,
    condition: type === 'condition' ? { field: '', operator: '', value: '', custom_field: '' } : null,
    condition_true_step_id: null,
    condition_false_step_id: null,
    subscribe_check: type === 'subscribe_check' ? { channel_id: '', channel_username: '', not_subscribed_message: "Kanalga obuna bo'lishingiz kerak!", subscribe_button_text: "Obuna bo'lish" } : null,
    subscribe_true_step_id: null,
    subscribe_false_step_id: null,
    quiz: type === 'quiz' ? { question: '', options: [{ text: '', next_step_id: null }, { text: '', next_step_id: null }], allow_multiple: false, save_answer_to: '' } : null,
    ab_test: type === 'ab_test' ? { variants: [{ name: 'A', percentage: 50, next_step_id: null }, { name: 'B', percentage: 50, next_step_id: null }] } : null,
    tag: type === 'tag' ? { action: 'add', tags: [], new_tag: '' } : null
  }

  nodes.value.push(newNode)
  selectedNode.value = newNode
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
  if (!confirm("Bu qadamni o'chirishni xohlaysizmi?")) return
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
  isDrawing.value = true
  drawingConnection.value = { from: nodeId, type: connectionType }
}

const endConnection = (nodeId) => {
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

const cancelConnection = () => { drawingConnection.value = null; isDrawing.value = false }

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

const deleteConnection = (connection) => {
  if (!confirm("Bu bog'lanishni o'chirishni xohlaysizmi?")) return
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
  const labels = { start: 'Boshlash', message: 'Xabar', input: "Ma'lumot", condition: 'Shart', action: 'Amal', delay: 'Kutish', end: 'Tugatish', subscribe_check: 'Obuna tekshir', quiz: 'Savol/Quiz', ab_test: 'A/B Test', tag: 'Teg' }
  return labels[type] || type
}

const getNodeIcon = (type) => {
  return {
    render() {
      const icons = {
        start: h('svg', { class: 'w-5 h-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z' })]),
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

const getConditionOperatorLabel = (operator) => {
  const labels = { 'equals': 'teng', 'not_equals': 'teng emas', 'contains': "o'z ichiga oladi", 'not_contains': "o'z ichiga olmaydi", 'starts_with': 'bilan boshlanadi', 'ends_with': 'bilan tugaydi', 'is_set': "mavjud", 'is_empty': "bo'sh", 'greater_than': 'katta', 'less_than': 'kichik', 'greater_or_equal': 'katta yoki teng', 'less_or_equal': 'kichik yoki teng', 'is_true': 'ha (true)', 'is_false': "yo'q (false)" }
  return labels[operator] || operator
}

const getFieldDisplayName = (field) => {
  const names = { 'first_name': 'Ism', 'last_name': 'Familiya', 'username': 'Username', 'phone': 'Telefon', 'email': 'Email', 'language_code': 'Til kodi', 'is_premium': 'Premium', 'user_id': 'Telegram ID', 'custom_field': 'Maxsus maydon', 'has_tag': 'Teg mavjudligi', 'quiz_answer': 'Quiz javobi', 'interaction_count': 'Interaksiya soni', 'last_message_date': 'Oxirgi xabar', 'funnel_step': 'Joriy qadam' }
  return names[field] || field
}

// Save function
const saveSteps = async () => {
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
      action_type: node.action_type || 'none',
      action_config: node.action_config,
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
      quiz: node.type === 'quiz' ? node.quiz : null,
      ab_test: node.type === 'ab_test' ? node.ab_test : null,
      tag: node.type === 'tag' ? node.tag : null
    }))

    const firstStep = nodes.value.find(n => n.is_first) || nodes.value[0]

    const response = await fetch(getRoute('telegram-funnels.funnels.save-steps', [props.bot.id, props.funnel.id]), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ steps: stepsData, first_step_id: firstStep?.id })
    })

    const data = await response.json()

    if (data.success) {
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
          subscribe_check: step.subscribe_check || { channel_id: '', channel_username: '', not_subscribed_message: '', subscribe_button_text: '' },
          subscribe_true_step_id: step.subscribe_true_step_id || null,
          subscribe_false_step_id: step.subscribe_false_step_id || null,
          quiz: step.quiz || { question: '', options: [], allow_multiple: false, save_answer_to: '' },
          ab_test: step.ab_test || { variants: [] },
          tag: step.tag || { action: 'add', tags: [], new_tag: '' }
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
      alert('Funnel saqlandi!')
    } else {
      alert(data.message || 'Xatolik yuz berdi')
    }
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
</style>
