<template>
  <div class="relative">
    <!-- Testimonial Card -->
    <div class="relative overflow-hidden min-h-[200px]">
      <div
        class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20 transition-all duration-500"
        :key="currentIndex"
      >
        <!-- Quote Icon -->
        <div class="absolute top-4 right-4 opacity-20">
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
          </svg>
        </div>

        <!-- Stars Rating -->
        <div class="flex items-center gap-1 mb-3">
          <svg
            v-for="star in 5"
            :key="star"
            class="w-4 h-4"
            :class="star <= currentTestimonial.rating ? 'text-yellow-400' : 'text-white/30'"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>

        <!-- Testimonial Text with fade animation -->
        <div class="relative overflow-hidden">
          <p
            class="text-white/90 italic text-sm leading-relaxed mb-4 transition-opacity duration-300"
            :class="isAnimating ? 'opacity-0' : 'opacity-100'"
          >
            "{{ currentTestimonial.text }}"
          </p>
        </div>

        <!-- Author Info -->
        <div
          class="flex items-center gap-3 transition-opacity duration-300"
          :class="isAnimating ? 'opacity-0' : 'opacity-100'"
        >
          <div
            class="w-11 h-11 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300"
            :class="avatarColors[currentIndex % avatarColors.length]"
          >
            {{ currentTestimonial.avatar }}
          </div>
          <div>
            <p class="font-semibold text-white text-sm">{{ currentTestimonial.name }}</p>
          </div>
        </div>

        <!-- Company Metric Badge -->
        <div
          v-if="currentTestimonial.metric"
          class="mt-4 pt-3 border-t border-white/20 transition-opacity duration-300"
          :class="isAnimating ? 'opacity-0' : 'opacity-100'"
        >
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <span class="text-green-400 text-xs font-medium">{{ currentTestimonial.metric }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Dots -->
    <div class="flex items-center justify-center gap-2 mt-4">
      <button
        v-for="(_, index) in testimonials"
        :key="index"
        @click="goToSlide(index)"
        class="group relative p-1"
        :aria-label="`Go to testimonial ${index + 1}`"
      >
        <span
          class="block w-2 h-2 rounded-full transition-all duration-300"
          :class="currentIndex === index
            ? 'bg-white scale-125'
            : 'bg-white/40 hover:bg-white/60'"
        ></span>
      </button>
    </div>

    <!-- Progress Bar -->
    <div class="mt-3 h-0.5 bg-white/20 rounded-full overflow-hidden">
      <div
        class="h-full rounded-full transition-all ease-linear"
        :class="isPaused ? 'bg-white/40' : 'bg-white/60'"
        :style="{
          width: progressWidth + '%',
          transitionDuration: isPaused ? '0.3s' : (autoplayInterval / 1000) + 's'
        }"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  autoplayInterval: {
    type: Number,
    default: 5000,
  },
});

const currentIndex = ref(0);
const isAnimating = ref(false);
const isPaused = ref(false);
const progressWidth = ref(0);
let autoplayTimer = null;
let progressTimer = null;

const testimonials = [
  {
    id: 1,
    name: 'Aziz Karimov',
    avatar: 'AK',
    text: 'BiznesPilot AI bizning sotuvlarimizni 3 oyda 40% ga oshirdi! AI tahlillari juda aniq va foydali.',
    rating: 5,
    metric: 'Sotuvlar 40% oshdi',
  },
  {
    id: 2,
    name: 'Malika Rahimova',
    avatar: 'MR',
    text: 'Instagram avtomatizatsiyasi orqali kuniga 50+ mijoz bilan bog\'lanishni yo\'lga qo\'ydik. Vaqtni tejash ajoyib!',
    rating: 5,
    metric: 'Kuniga 50+ yangi mijoz',
  },
  {
    id: 3,
    name: 'Bobur Toshmatov',
    avatar: 'BT',
    text: 'CRM tizimi orqali mijozlarni boshqarish ancha osonlashdi. Hozir har bir mijozni kuzatib boramiz.',
    rating: 5,
    metric: 'Mijoz qaytishi 60% oshdi',
  },
  {
    id: 4,
    name: 'Dilnoza Azimova',
    avatar: 'DA',
    text: 'AI chatbot mijozlarimga 24/7 javob beradi. Men endi faqat muhim ishlar bilan shug\'ullanaman.',
    rating: 5,
    metric: '24/7 avtomatik javob',
  },
  {
    id: 5,
    name: 'Jasur Alimov',
    avatar: 'JA',
    text: 'Raqobatchilar tahlili funksiyasi juda foydali. Bozordagi o\'zgarishlarni darhol ko\'rib turamiz.',
    rating: 4,
    metric: 'Bozor ulushi 25% oshdi',
  },
  {
    id: 6,
    name: 'Nodira Umarova',
    avatar: 'NU',
    text: 'Marketing kampaniyalarini rejalashtirish endi juda oson. AI tavsiyalari doim to\'g\'ri chiqadi!',
    rating: 5,
    metric: 'ROI 300% oshdi',
  },
  {
    id: 7,
    name: 'Sherzod Qodirov',
    avatar: 'ShQ',
    text: 'Hisobotlar va tahlillar real vaqtda ko\'rinadi. Endi qarorlarni ma\'lumotlarga asoslanib qabul qilamiz.',
    rating: 5,
    metric: 'Qaror qabul qilish 50% tezlashdi',
  },
  {
    id: 8,
    name: 'Anvar Rahmonov',
    avatar: 'AR',
    text: 'Telegram bot orqali bemorlar bilan aloqa o\'rnatish juda qulay. Navbatga yozilish avtomatlashtirildi.',
    rating: 5,
    metric: 'Navbat samaradorligi 70% oshdi',
  },
  {
    id: 9,
    name: 'Kamola Sodiqova',
    avatar: 'KS',
    text: 'Kichik biznes uchun ideal platforma! Narxi hamyonbop, funksiyalari esa professional darajada.',
    rating: 5,
    metric: 'Oylik foyda 2x oshdi',
  },
];

const avatarColors = [
  'bg-emerald-500 text-white',
  'bg-blue-500 text-white',
  'bg-purple-500 text-white',
  'bg-pink-500 text-white',
  'bg-orange-500 text-white',
  'bg-teal-500 text-white',
  'bg-indigo-500 text-white',
  'bg-rose-500 text-white',
  'bg-cyan-500 text-white',
  'bg-amber-500 text-white',
];

const currentTestimonial = computed(() => testimonials[currentIndex.value]);

function changeSlide(newIndex) {
  if (isAnimating.value) return;

  isAnimating.value = true;

  setTimeout(() => {
    currentIndex.value = newIndex;
    setTimeout(() => {
      isAnimating.value = false;
    }, 50);
  }, 300);
}

function nextSlide() {
  const newIndex = (currentIndex.value + 1) % testimonials.length;
  changeSlide(newIndex);
}

function goToSlide(index) {
  if (index === currentIndex.value) return;
  changeSlide(index);
  resetAutoplay();
}

function startAutoplay() {
  // Reset progress
  progressWidth.value = 0;

  // Start progress animation after a small delay
  setTimeout(() => {
    progressWidth.value = 100;
  }, 50);

  // Set timer for next slide
  autoplayTimer = setTimeout(() => {
    nextSlide();
    startAutoplay();
  }, props.autoplayInterval);
}

function resetAutoplay() {
  if (autoplayTimer) {
    clearTimeout(autoplayTimer);
  }
  progressWidth.value = 0;

  setTimeout(() => {
    startAutoplay();
  }, 350);
}

function stopAutoplay() {
  if (autoplayTimer) {
    clearTimeout(autoplayTimer);
  }
  isPaused.value = true;
}

function resumeAutoplay() {
  isPaused.value = false;
  startAutoplay();
}

onMounted(() => {
  startAutoplay();
});

onUnmounted(() => {
  if (autoplayTimer) {
    clearTimeout(autoplayTimer);
  }
});
</script>
