<template>
  <!-- Modal overlay -->
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
    @click.self="$emit('close')"
  >
    <div
      class="relative w-full max-w-md mx-4 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden"
      style="max-height: 90vh"
    >
      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-3 bg-[#517da2] text-white">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.02.01-.12-.04-.17s-.14-.04-.2-.02c-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
            </svg>
          </div>
          <div>
            <p class="text-sm font-semibold leading-tight">
              {{ botName || 'Bot' }}
            </p>
            <p class="text-xs opacity-80 leading-tight">Telegram ko'rinishi</p>
          </div>
        </div>
        <button
          @click="$emit('close')"
          class="p-2 hover:bg-white/10 rounded-lg transition-colors"
          aria-label="Yopish"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Chat area -->
      <div
        class="overflow-y-auto px-3 py-4 space-y-2"
        :style="{
          background: '#c7d2d8',
          backgroundImage: 'linear-gradient(180deg, #c7d2d8 0%, #b7c3cc 100%)',
          maxHeight: 'calc(90vh - 8rem)',
          minHeight: '420px',
        }"
      >
        <div v-if="!previewSteps.length" class="text-center text-gray-600 text-xs py-8">
          Ko'rsatish uchun qadam topilmadi
        </div>

        <template v-for="(step, idx) in previewSteps" :key="idx">
          <!-- Delay divider -->
          <div
            v-if="step.type === 'delay'"
            class="flex justify-center py-2"
          >
            <span class="text-xs italic text-gray-600 bg-white/50 px-3 py-1 rounded-full">
              ⏱ {{ step.delay_seconds || 5 }}s...
            </span>
          </div>

          <!-- Condition: show both branches -->
          <div
            v-else-if="step.type === 'condition'"
            class="flex gap-2 justify-start items-stretch"
          >
            <div class="flex-1 bg-green-50 border border-green-200 rounded-lg p-2">
              <p class="text-[10px] font-semibold text-green-700 mb-1">Agar HA:</p>
              <p class="text-xs text-gray-700">
                {{ conditionBranchLabel(step, 'true') }}
              </p>
            </div>
            <div class="flex-1 bg-red-50 border border-red-200 rounded-lg p-2">
              <p class="text-[10px] font-semibold text-red-700 mb-1">Agar YO'Q:</p>
              <p class="text-xs text-gray-700">
                {{ conditionBranchLabel(step, 'false') }}
              </p>
            </div>
          </div>

          <!-- Bot bubble (message / input / start / quiz / subscribe_check) -->
          <div
            v-else
            class="flex flex-col max-w-[85%]"
          >
            <!-- Photo/video placeholder -->
            <div
              v-if="isMediaStep(step)"
              class="bg-white rounded-xl rounded-bl-sm shadow overflow-hidden mb-0.5"
            >
              <div
                class="w-full h-36 flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 text-gray-500"
              >
                <div class="text-center">
                  <svg class="w-10 h-10 mx-auto mb-1 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path v-if="step.content?.type === 'video'" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <p class="text-[10px] uppercase tracking-wider">
                    {{ mediaLabel(step.content?.type) }}
                  </p>
                </div>
              </div>
              <div
                v-if="bubbleText(step)"
                class="px-3 py-2 text-sm text-gray-900"
                v-html="renderTextWithVars(bubbleText(step))"
              ></div>
            </div>

            <!-- Text bubble -->
            <div
              v-else-if="bubbleText(step)"
              class="bg-white rounded-xl rounded-bl-sm shadow px-3 py-2 text-sm text-gray-900 whitespace-pre-wrap break-words"
              v-html="renderTextWithVars(bubbleText(step))"
            ></div>

            <!-- Quiz options as button rows -->
            <div
              v-if="step.type === 'quiz' && step.quiz?.options?.length"
              class="mt-1 space-y-1"
            >
              <div
                v-for="(opt, oIdx) in step.quiz.options"
                :key="oIdx"
                class="bg-white/90 border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-center text-blue-700 font-medium cursor-default"
              >
                {{ opt.text || `Variant ${oIdx + 1}` }}
              </div>
            </div>

            <!-- Subscribe-check CTA -->
            <div
              v-if="step.type === 'subscribe_check'"
              class="mt-1 space-y-1"
            >
              <div
                class="bg-white/90 border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-center text-blue-700 font-medium"
              >
                {{ step.subscribe_check?.subscribe_button_text || "Obuna bo'lish" }}
              </div>
            </div>

            <!-- Inline keyboard buttons -->
            <div
              v-if="keyboardRows(step).length"
              class="mt-1 space-y-1"
            >
              <div
                v-for="(row, rIdx) in keyboardRows(step)"
                :key="rIdx"
                class="flex gap-1"
              >
                <div
                  v-for="(btn, bIdx) in row"
                  :key="bIdx"
                  class="flex-1 bg-white/90 border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-center text-blue-700 font-medium cursor-default truncate"
                  :title="btn.text || ''"
                >
                  {{ btn.text || '(tugma)' }}
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ previewSteps.length }} ta qadam ko'rsatildi
        </p>
        <button
          @click="$emit('close')"
          class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-sm font-medium text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
        >
          Yopish
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';

const props = defineProps({
    nodes: { type: Array, default: () => [] },
    startNodeId: { type: String, default: null },
    botName: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const MAX_STEPS = 40; // safety cap

// Build a linear preview path: first node -> follow next_step_id (or condition/quiz)
// until hitting an end/branch/already-visited node.
const previewSteps = computed(() => {
    const { nodes, startNodeId } = props;
    if (!Array.isArray(nodes) || !nodes.length) return [];

    const byId = new Map(nodes.map((n) => [n.id, n]));
    let current = null;

    if (startNodeId && byId.has(startNodeId)) {
        current = byId.get(startNodeId);
    } else {
        current = nodes.find((n) => n.type === 'start') || nodes.find((n) => n.is_first) || nodes[0];
    }

    const out = [];
    const visited = new Set();

    while (current && out.length < MAX_STEPS && !visited.has(current.id)) {
        visited.add(current.id);
        out.push(current);

        const t = current.type;
        if (t === 'end') break;

        if (t === 'condition') {
            // Render both branches side-by-side and stop the linear trace
            break;
        }

        if (t === 'quiz' || t === 'ab_test' || t === 'subscribe_check') {
            // branching — stop after showing branching step
            break;
        }

        // Follow first button's next_step_id if present, otherwise next_step_id
        let nextId = current.next_step_id || null;
        const rows = current.keyboard?.buttons || [];
        if (!nextId) {
            for (const row of rows) {
                for (const btn of row || []) {
                    if (btn?.action_type === 'next_step' && btn?.next_step_id) {
                        nextId = btn.next_step_id;
                        break;
                    }
                }
                if (nextId) break;
            }
        }

        current = nextId ? byId.get(nextId) : null;
    }

    return out;
});

function bubbleText(step) {
    return (step.content?.text || step.content?.caption || '').trim();
}

function isMediaStep(step) {
    const ct = step.content?.type;
    return ct && ct !== 'text';
}

function mediaLabel(type) {
    const map = {
        photo: 'Rasm',
        video: 'Video',
        voice: 'Ovozli xabar',
        video_note: 'Dumaloq video',
        document: 'Fayl',
    };
    return map[type] || type || 'Media';
}

function keyboardRows(step) {
    if (step.type === 'quiz' || step.type === 'subscribe_check') return [];
    const rows = step.keyboard?.buttons || [];
    return rows.filter((r) => Array.isArray(r) && r.length);
}

function conditionBranchLabel(step, branch) {
    const targetId =
        branch === 'true'
            ? step.condition_true_step_id
            : step.condition_false_step_id;
    if (!targetId) return '(davomi biriktirilmagan)';
    const target = props.nodes.find((n) => n.id === targetId);
    if (!target) return '(mavjud emas)';
    return bubbleText(target) || target.name || '(nomsiz qadam)';
}

// Render {variable} placeholders as grey-highlighted spans, but keep the
// literal text (per spec — no substitution).
function renderTextWithVars(text) {
    if (!text) return '';
    const escaped = String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    return escaped.replace(
        /\{([a-zA-Z0-9_]+)\}/g,
        '<span class="inline-block bg-gray-200 text-gray-600 px-1 rounded text-[11px] font-mono">{$1}</span>'
    );
}

// Esc key -> close
function onKey(e) {
    if (e.key === 'Escape') {
        emit('close');
    }
}
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>
