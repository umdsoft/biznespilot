<template>
  <div
    class="relative h-full"
    :class="[
      isPopular ? 'z-20 lg:-mx-2' : 'z-10',
      isPremium ? 'z-15' : ''
    ]"
  >
    <!-- Scale wrapper for popular -->
    <div :class="isPopular ? 'lg:scale-[1.02] origin-center' : ''">
      <!-- Glow effect -->
      <div
        v-if="isPopular"
        class="absolute -inset-[2px] bg-gradient-to-b from-purple-500 via-violet-500 to-purple-600 rounded-3xl opacity-100"
      ></div>
      <div
        v-else-if="isPremium"
        class="absolute -inset-[2px] bg-gradient-to-b from-amber-400 via-orange-500 to-amber-500 rounded-3xl opacity-100"
      ></div>

      <div
        class="relative bg-white rounded-3xl transition-all duration-500 h-full flex flex-col overflow-hidden"
        :class="[
          isPopular || isPremium ? '' : 'border-2 border-gray-200 hover:border-gray-300 hover:shadow-2xl',
          isPopular ? 'shadow-2xl shadow-purple-500/20' : '',
          isPremium ? 'shadow-2xl shadow-amber-500/20' : 'shadow-lg'
        ]"
      >
        <!-- Colored header bar -->
        <div
          class="h-2"
          :class="headerBarColor"
        ></div>

        <div class="p-6 sm:p-7 lg:p-8 xl:p-10 flex flex-col flex-1">
          <!-- Badge -->
          <div v-if="isPopular" class="absolute -top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
            <span class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-gradient-to-r from-purple-600 to-violet-600 text-white text-sm font-bold rounded-full shadow-xl shadow-purple-500/30">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              ENG FOYDALI
            </span>
          </div>

          <div v-else-if="isPremium" class="absolute -top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
            <span class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-bold rounded-full shadow-xl shadow-amber-500/30">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" />
              </svg>
              VIP
            </span>
          </div>

          <!-- Icon & Plan Header -->
          <div class="flex items-start gap-4 mb-6 lg:mb-8" :class="{ 'mt-4': isPopular || isPremium }">
            <div
              class="w-14 h-14 lg:w-16 lg:h-16 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg"
              :class="iconBgClass"
            >
              <component :is="planIcon" class="w-7 h-7 lg:w-8 lg:h-8" />
            </div>
            <div class="pt-1">
              <h3 class="text-xl lg:text-2xl font-bold text-gray-900">{{ plan.name }}</h3>
              <p class="text-gray-500 mt-1 text-sm lg:text-base">{{ plan.description }}</p>
            </div>
          </div>

          <!-- Price -->
          <div class="mb-6 lg:mb-8">
            <div class="flex items-end gap-1">
              <span class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight">{{ formattedPrice }}</span>
              <span class="text-gray-400 text-base lg:text-lg mb-1 lg:mb-2 font-medium">so'm/oy</span>
            </div>
            <div v-if="isYearly" class="mt-3 flex items-center gap-3">
              <span class="text-gray-400 line-through text-lg">{{ formattedMonthlyPrice }} so'm</span>
              <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-bold rounded-full">2 oy bepul!</span>
            </div>
            <div v-if="isPopular && !isYearly" class="mt-3 inline-flex items-center gap-1.5 text-purple-600 text-sm font-semibold bg-purple-50 px-3 py-1.5 rounded-full">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
              </svg>
              Eng foydali tanlov
            </div>
          </div>

          <!-- Divider -->
          <div class="border-t border-gray-100 mb-6 lg:mb-8"></div>

          <!-- Features -->
          <ul class="space-y-3 lg:space-y-4 mb-8 lg:mb-10 flex-1">
            <li
              v-for="(feature, index) in plan.highlights"
              :key="index"
              class="flex items-start gap-3"
            >
              <div
                class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                :class="checkBgClass"
              >
                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
              <span class="text-gray-700 text-base lg:text-lg leading-relaxed">{{ feature }}</span>
            </li>
          </ul>

          <!-- CTA Button -->
          <button
            @click="$emit('select', plan)"
            class="w-full py-4 lg:py-5 px-6 rounded-xl lg:rounded-2xl font-bold text-center text-base lg:text-lg transition-all duration-300 transform hover:scale-[1.02] hover:-translate-y-1"
            :class="buttonClasses"
          >
            {{ plan.cta || 'Boshlash' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, h } from 'vue';

const props = defineProps({
  plan: {
    type: Object,
    required: true
  },
  isYearly: {
    type: Boolean,
    default: false
  },
  isPopular: {
    type: Boolean,
    default: false
  },
  isPremium: {
    type: Boolean,
    default: false
  }
});

defineEmits(['select']);

const formattedPrice = computed(() => {
  const price = props.isYearly
    ? Math.round(props.plan.monthlyPrice * 10 / 12)
    : props.plan.monthlyPrice;
  return price.toLocaleString('uz-UZ');
});

const formattedMonthlyPrice = computed(() => {
  return props.plan.monthlyPrice.toLocaleString('uz-UZ');
});

const headerBarColor = computed(() => {
  if (props.plan.id === 'start') return 'bg-gradient-to-r from-blue-500 to-blue-600';
  if (props.plan.id === 'standard') return 'bg-gradient-to-r from-emerald-500 to-teal-500';
  if (props.plan.id === 'business') return 'bg-gradient-to-r from-purple-500 to-violet-600';
  if (props.plan.id === 'premium') return 'bg-gradient-to-r from-amber-500 to-orange-500';
  return 'bg-gradient-to-r from-blue-500 to-blue-600';
});

const iconBgClass = computed(() => {
  if (props.plan.id === 'start') return 'text-blue-600 bg-gradient-to-br from-blue-50 to-blue-100';
  if (props.plan.id === 'standard') return 'text-emerald-600 bg-gradient-to-br from-emerald-50 to-emerald-100';
  if (props.plan.id === 'business') return 'text-purple-600 bg-gradient-to-br from-purple-50 to-purple-100';
  if (props.plan.id === 'premium') return 'text-amber-600 bg-gradient-to-br from-amber-50 to-amber-100';
  return 'text-blue-600 bg-gradient-to-br from-blue-50 to-blue-100';
});

const checkBgClass = computed(() => {
  if (props.isPopular) return 'bg-purple-500';
  if (props.isPremium) return 'bg-amber-500';
  if (props.plan.id === 'start') return 'bg-blue-500';
  if (props.plan.id === 'standard') return 'bg-emerald-500';
  return 'bg-green-500';
});

const planIcon = computed(() => {
  const icons = {
    start: {
      render() {
        return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M13 10V3L4 14h7v7l9-11h-7z' })
        ]);
      }
    },
    standard: {
      render() {
        return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' })
        ]);
      }
    },
    business: {
      render() {
        return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })
        ]);
      }
    },
    premium: {
      render() {
        return h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z' })
        ]);
      }
    }
  };
  return icons[props.plan.id] || icons.start;
});

const buttonClasses = computed(() => {
  if (props.isPopular) {
    return 'bg-gradient-to-r from-purple-600 to-violet-600 text-white hover:from-purple-700 hover:to-violet-700 shadow-xl shadow-purple-500/30 hover:shadow-2xl hover:shadow-purple-500/40';
  }
  if (props.isPremium) {
    return 'bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:from-amber-600 hover:to-orange-600 shadow-xl shadow-amber-500/30 hover:shadow-2xl hover:shadow-amber-500/40';
  }
  return 'bg-gray-900 text-white hover:bg-gray-800 shadow-lg hover:shadow-xl';
});
</script>
