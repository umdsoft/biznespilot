<template>
  <SalesHeadLayout title="Pipeline Avtomatizatsiya">
    <Head title="Pipeline Avtomatizatsiya" />

    <div class="py-6">
      <div class="w-full px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-2xl font-bold text-white">Pipeline Avtomatizatsiya</h1>
            <p class="text-gray-400 mt-1">Lead bosqichlarini avtomatik o'zgartirish qoidalari</p>
          </div>
          <div class="flex gap-3">
            <button
              @click="resetToDefaults"
              class="px-4 py-2 text-sm text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg transition"
            >
              Standartga qaytarish
            </button>
            <button
              @click="showAddModal = true"
              class="px-4 py-2 text-sm text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition flex items-center gap-2"
            >
              <PlusIcon class="w-4 h-4" />
              Qoida qo'shish
            </button>
          </div>
        </div>

        <!-- Pipeline Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
          <div
            v-for="stage in pipelineStats.stages"
            :key="stage.slug"
            class="bg-gray-800 rounded-lg p-4"
          >
            <div class="flex items-center gap-2 mb-2">
              <div
                :class="['w-3 h-3 rounded-full', `bg-${stage.color}-500`]"
              ></div>
              <span class="text-sm text-gray-400">{{ stage.name }}</span>
            </div>
            <p class="text-2xl font-bold text-white">{{ stage.count }}</p>
          </div>
        </div>

        <!-- Bottlenecks Warning -->
        <div
          v-if="bottlenecks.length > 0"
          class="bg-orange-500/10 border border-orange-500/30 rounded-xl p-4 mb-6"
        >
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="w-6 h-6 text-orange-400 flex-shrink-0 mt-0.5" />
            <div>
              <h3 class="font-semibold text-orange-400 mb-2">Bottleneck lar aniqlandi</h3>
              <div class="space-y-2">
                <div
                  v-for="bottleneck in bottlenecks"
                  :key="bottleneck.stage_slug"
                  class="flex items-center gap-3 text-sm"
                >
                  <span
                    :class="[
                      'px-2 py-0.5 rounded text-xs font-medium',
                      bottleneck.severity === 'critical' ? 'bg-red-500/20 text-red-400' :
                      bottleneck.severity === 'high' ? 'bg-orange-500/20 text-orange-400' :
                      'bg-yellow-500/20 text-yellow-400'
                    ]"
                  >
                    {{ bottleneck.severity }}
                  </span>
                  <span class="text-gray-300">
                    <strong>{{ bottleneck.lead_count }}</strong> ta lead "{{ bottleneck.stage_name }}" da
                    o'rtacha <strong>{{ bottleneck.avg_days }}</strong> kun turibdi
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rules List -->
        <div class="bg-gray-800 rounded-xl overflow-hidden">
          <div class="p-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-white">Avtomatlashtirish qoidalari</h2>
          </div>

          <div v-if="rules.length === 0" class="p-8 text-center text-gray-500">
            <CogIcon class="w-12 h-12 mx-auto mb-3 opacity-50" />
            <p>Hozircha qoidalar yo'q</p>
            <button
              @click="showAddModal = true"
              class="mt-3 text-emerald-400 hover:text-emerald-300"
            >
              Birinchi qoidani qo'shing
            </button>
          </div>

          <div v-else class="divide-y divide-gray-700">
            <div
              v-for="rule in rules"
              :key="rule.id"
              :class="['p-4 flex items-center gap-4', !rule.is_active && 'opacity-50']"
            >
              <!-- Icon -->
              <div class="w-10 h-10 rounded-lg bg-gray-700 flex items-center justify-center">
                <component
                  :is="getTriggerIcon(rule.trigger_type)"
                  class="w-5 h-5 text-gray-400"
                />
              </div>

              <!-- Details -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-medium text-white">{{ rule.trigger_info.name }}</span>
                  <ArrowRightIcon class="w-4 h-4 text-gray-500" />
                  <span
                    :class="['px-2 py-0.5 rounded text-xs font-medium', `bg-${rule.to_stage_color}-500/20 text-${rule.to_stage_color}-400`]"
                  >
                    {{ rule.to_stage_name }}
                  </span>
                </div>
                <div class="text-sm text-gray-400">
                  <span v-if="rule.from_stage_slug">
                    Faqat "{{ rule.from_stage_name }}" dan
                  </span>
                  <span v-if="rule.trigger_conditions && Object.keys(rule.trigger_conditions).length">
                    • Shart: {{ formatConditions(rule.trigger_conditions) }}
                  </span>
                </div>
              </div>

              <!-- Priority -->
              <div class="text-sm text-gray-500">
                Prioritet: {{ rule.priority }}
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2">
                <button
                  @click="toggleRule(rule)"
                  :class="[
                    'px-3 py-1.5 text-xs rounded-lg transition',
                    rule.is_active
                      ? 'bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30'
                      : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
                  ]"
                >
                  {{ rule.is_active ? 'Faol' : 'O\'chiq' }}
                </button>
                <button
                  @click="deleteRule(rule)"
                  class="p-1.5 text-red-400 hover:bg-red-500/20 rounded-lg transition"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Flow Diagram -->
        <div class="mt-6 bg-gray-800 rounded-xl p-6">
          <h2 class="text-lg font-semibold text-white mb-4">Pipeline oqimi</h2>
          <div class="flex items-center gap-2 overflow-x-auto pb-4">
            <template v-for="(stage, index) in stages" :key="stage.slug">
              <div
                :class="[
                  'flex-shrink-0 px-4 py-3 rounded-lg text-center min-w-[120px]',
                  stage.is_won ? 'bg-green-500/20 border border-green-500/30' :
                  stage.is_lost ? 'bg-red-500/20 border border-red-500/30' :
                  'bg-gray-700'
                ]"
              >
                <div
                  :class="[
                    'w-3 h-3 rounded-full mx-auto mb-2',
                    `bg-${stage.color}-500`
                  ]"
                ></div>
                <span class="text-sm text-white font-medium">{{ stage.name }}</span>
                <div class="text-xs text-gray-400 mt-1">
                  {{ getStageLeadCount(stage.slug) }} ta
                </div>
              </div>
              <ArrowRightIcon
                v-if="index < stages.length - 1 && !stage.is_won && !stage.is_lost"
                class="w-5 h-5 text-gray-500 flex-shrink-0"
              />
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Rule Modal -->
    <TransitionRoot appear :show="showAddModal" as="template">
      <Dialog as="div" class="relative z-50" @close="showAddModal = false">
        <TransitionChild
          as="template"
          enter="duration-300 ease-out"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="duration-200 ease-in"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <TransitionChild
              as="template"
              enter="duration-300 ease-out"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="duration-200 ease-in"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-gray-800 shadow-2xl transition-all">
                <!-- Header -->
                <div class="border-b border-gray-700 px-6 py-4">
                  <div class="flex items-center justify-between">
                    <DialogTitle class="text-lg font-semibold text-white flex items-center gap-2">
                      <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                        <PlusIcon class="w-5 h-5 text-emerald-400" />
                      </div>
                      Yangi qoida qo'shish
                    </DialogTitle>
                    <button
                      @click="showAddModal = false"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-gray-700 transition"
                    >
                      <XMarkIcon class="w-5 h-5" />
                    </button>
                  </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="addRule" class="p-6 space-y-5">
                  <!-- Trigger Type -->
                  <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                      Trigger hodisasi
                      <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                      <select
                        v-model="newRule.trigger_type"
                        class="w-full appearance-none bg-gray-700/50 border border-gray-600 rounded-xl px-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition cursor-pointer"
                      >
                        <option value="" class="bg-gray-800">Tanlang...</option>
                        <option v-for="(info, type) in triggerTypes" :key="type" :value="type" class="bg-gray-800">
                          {{ info.name }}
                        </option>
                      </select>
                      <ChevronDownIcon class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
                    </div>
                    <p v-if="newRule.trigger_type && triggerTypes[newRule.trigger_type]" class="mt-1.5 text-xs text-gray-500">
                      {{ triggerTypes[newRule.trigger_type].description || 'Bu hodisa sodir bo\'lganda qoida ishga tushadi' }}
                    </p>
                  </div>

                  <!-- Trigger Conditions -->
                  <div v-if="newRule.trigger_type && getAvailableConditions.length > 0" class="bg-gray-700/30 rounded-xl p-4">
                    <label class="block text-sm font-medium text-gray-300 mb-3">
                      Qo'shimcha shartlar
                      <span class="text-gray-500 font-normal">(ixtiyoriy)</span>
                    </label>
                    <div class="space-y-3">
                      <div v-for="condition in getAvailableConditions" :key="condition" class="flex items-center gap-3">
                        <span class="text-sm text-gray-400 min-w-[80px]">{{ condition }}</span>
                        <input
                          v-model="newRule.trigger_conditions[condition]"
                          type="text"
                          class="flex-1 bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                          :placeholder="`${condition} qiymati`"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Stages Row -->
                  <div class="grid grid-cols-2 gap-4">
                    <!-- From Stage -->
                    <div>
                      <label class="block text-sm font-medium text-gray-300 mb-2">
                        Qaysi bosqichdan
                      </label>
                      <div class="relative">
                        <select
                          v-model="newRule.from_stage_slug"
                          class="w-full appearance-none bg-gray-700/50 border border-gray-600 rounded-xl px-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition cursor-pointer text-sm"
                        >
                          <option value="" class="bg-gray-800">Har qanday</option>
                          <option v-for="stage in stages" :key="stage.slug" :value="stage.slug" class="bg-gray-800">
                            {{ stage.name }}
                          </option>
                        </select>
                        <ChevronDownIcon class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                      </div>
                    </div>

                    <!-- To Stage -->
                    <div>
                      <label class="block text-sm font-medium text-gray-300 mb-2">
                        Qaysi bosqichga
                        <span class="text-red-400">*</span>
                      </label>
                      <div class="relative">
                        <select
                          v-model="newRule.to_stage_slug"
                          required
                          class="w-full appearance-none bg-gray-700/50 border border-gray-600 rounded-xl px-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition cursor-pointer text-sm"
                        >
                          <option value="" class="bg-gray-800">Tanlang...</option>
                          <option v-for="stage in stages" :key="stage.slug" :value="stage.slug" class="bg-gray-800">
                            {{ stage.name }}
                          </option>
                        </select>
                        <ChevronDownIcon class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                      </div>
                    </div>
                  </div>

                  <!-- Visual Arrow -->
                  <div v-if="newRule.from_stage_slug || newRule.to_stage_slug" class="flex items-center justify-center gap-3 py-2">
                    <span class="px-3 py-1.5 rounded-lg bg-gray-700 text-sm text-gray-300">
                      {{ newRule.from_stage_slug ? stages.find(s => s.slug === newRule.from_stage_slug)?.name : 'Har qanday' }}
                    </span>
                    <ArrowRightIcon class="w-5 h-5 text-emerald-400" />
                    <span v-if="newRule.to_stage_slug" class="px-3 py-1.5 rounded-lg bg-emerald-500/20 text-sm text-emerald-400 font-medium">
                      {{ stages.find(s => s.slug === newRule.to_stage_slug)?.name }}
                    </span>
                    <span v-else class="px-3 py-1.5 rounded-lg bg-gray-700 text-sm text-gray-500">
                      ?
                    </span>
                  </div>

                  <!-- Options -->
                  <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 rounded-xl bg-gray-700/30 hover:bg-gray-700/50 cursor-pointer transition group">
                      <input
                        v-model="newRule.only_if_current_stage"
                        type="checkbox"
                        class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-gray-800"
                      />
                      <div>
                        <span class="text-sm text-white group-hover:text-emerald-400 transition">Faqat tanlangan bosqichdan</span>
                        <p class="text-xs text-gray-500 mt-0.5">Lead aynan shu bosqichda bo'lgandagina ishlaydi</p>
                      </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-xl bg-gray-700/30 hover:bg-gray-700/50 cursor-pointer transition group">
                      <input
                        v-model="newRule.prevent_backward"
                        type="checkbox"
                        class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-gray-800"
                      />
                      <div>
                        <span class="text-sm text-white group-hover:text-emerald-400 transition">Orqaga o'tishni oldini ol</span>
                        <p class="text-xs text-gray-500 mt-0.5">Lead orqaga qaytmasligini ta'minlaydi</p>
                      </div>
                    </label>
                  </div>

                  <!-- Priority -->
                  <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                      Prioritet
                      <span class="text-gray-500 font-normal ml-1">({{ newRule.priority }})</span>
                    </label>
                    <input
                      v-model.number="newRule.priority"
                      type="range"
                      min="0"
                      max="100"
                      class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-emerald-500"
                    />
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                      <span>Past</span>
                      <span>O'rta</span>
                      <span>Yuqori</span>
                    </div>
                  </div>
                </form>

                <!-- Footer -->
                <div class="border-t border-gray-700 px-6 py-4 bg-gray-800/50 flex items-center justify-end gap-3">
                  <button
                    type="button"
                    @click="showAddModal = false"
                    class="px-4 py-2.5 text-sm font-medium text-gray-300 hover:text-white transition"
                  >
                    Bekor qilish
                  </button>
                  <button
                    @click="addRule"
                    :disabled="!newRule.trigger_type || !newRule.to_stage_slug"
                    class="px-5 py-2.5 text-sm font-medium bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center gap-2"
                  >
                    <PlusIcon class="w-4 h-4" />
                    Qoida qo'shish
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </SalesHeadLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild,
} from '@headlessui/vue'
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'
import {
  PlusIcon,
  ArrowRightIcon,
  TrashIcon,
  CogIcon,
  ExclamationTriangleIcon,
  PhoneIcon,
  ClipboardDocumentListIcon,
  CheckCircleIcon,
  ChatBubbleLeftIcon,
  XCircleIcon,
  CurrencyDollarIcon,
  XMarkIcon,
  ChevronDownIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  rules: Array,
  stages: Array,
  triggerTypes: Object,
  bottlenecks: Array,
  pipelineStats: Object,
})

const showAddModal = ref(false)

const newRule = ref({
  trigger_type: '',
  trigger_conditions: {},
  from_stage_slug: '',
  to_stage_slug: '',
  only_if_current_stage: false,
  prevent_backward: true,
  priority: 0,
})

const getAvailableConditions = computed(() => {
  if (!newRule.value.trigger_type) return []
  return props.triggerTypes[newRule.value.trigger_type]?.conditions || []
})

const getTriggerIcon = (type) => {
  const icons = {
    call_log_created: PhoneIcon,
    task_created: ClipboardDocumentListIcon,
    task_completed: CheckCircleIcon,
    message_sent: ChatBubbleLeftIcon,
    lead_lost: XCircleIcon,
    sale_created: CurrencyDollarIcon,
  }
  return icons[type] || CogIcon
}

const formatConditions = (conditions) => {
  return Object.entries(conditions)
    .map(([key, value]) => `${key}=${value}`)
    .join(', ')
}

const getStageLeadCount = (slug) => {
  const stage = props.pipelineStats.stages.find(s => s.slug === slug)
  return stage?.count || 0
}

const addRule = () => {
  // Filter out empty conditions
  const conditions = Object.fromEntries(
    Object.entries(newRule.value.trigger_conditions).filter(([_, v]) => v)
  )

  router.post(route('sales-head.pipeline-automation.store'), {
    ...newRule.value,
    trigger_conditions: Object.keys(conditions).length ? conditions : null,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showAddModal.value = false
      newRule.value = {
        trigger_type: '',
        trigger_conditions: {},
        from_stage_slug: '',
        to_stage_slug: '',
        only_if_current_stage: false,
        prevent_backward: true,
        priority: 0,
      }
    }
  })
}

const toggleRule = (rule) => {
  router.post(route('sales-head.pipeline-automation.toggle', rule.id), {}, {
    preserveScroll: true,
  })
}

const deleteRule = (rule) => {
  if (confirm('Bu qoidani o\'chirmoqchimisiz?')) {
    router.delete(route('sales-head.pipeline-automation.destroy', rule.id), {
      preserveScroll: true,
    })
  }
}

const resetToDefaults = () => {
  if (confirm('Barcha qoidalar o\'chirilib, standart qoidalar tiklanadi. Davom etasizmi?')) {
    router.post(route('sales-head.pipeline-automation.reset'), {}, {
      preserveScroll: true,
    })
  }
}
</script>
