<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import LandingLayout from '@/layouts/LandingLayout.vue'
import { useLandingTranslations } from '@/composables/useLandingTranslations'

const { locale } = useLandingTranslations()

const props = defineProps({
    posts: Object,
    categories: Array,
    filters: Object,
})

const selectedCategory = ref(props.filters?.category || '')

const categoryLabels = {
    crm: 'CRM',
    marketing: 'Marketing',
    smm: 'SMM',
    finance: locale.value === 'ru' ? 'Финансы' : 'Moliya',
    hr: 'HR',
    ai: 'AI',
    business: locale.value === 'ru' ? 'Бизнес' : 'Biznes',
    startup: 'Startup',
}

const categoryColors = {
    crm: 'bg-blue-100 text-blue-700',
    marketing: 'bg-purple-100 text-purple-700',
    smm: 'bg-pink-100 text-pink-700',
    finance: 'bg-emerald-100 text-emerald-700',
    hr: 'bg-orange-100 text-orange-700',
    ai: 'bg-indigo-100 text-indigo-700',
    business: 'bg-slate-100 text-slate-700',
    startup: 'bg-amber-100 text-amber-700',
}

const metaTitle = computed(() =>
    locale.value === 'ru'
        ? 'Блог о бизнесе, маркетинге и CRM | BiznesPilot'
        : 'Biznes, marketing va CRM haqida blog | BiznesPilot'
)

const metaDescription = computed(() =>
    locale.value === 'ru'
        ? 'Полезные статьи о CRM, маркетинге, SMM, финансах и управлении бизнесом. Практические советы для предпринимателей Узбекистана.'
        : "CRM, marketing, SMM, moliya va biznes boshqaruvi haqida foydali maqolalar. O'zbekiston tadbirkorlari uchun amaliy maslahatlar."
)

function filterByCategory(category) {
    selectedCategory.value = category
    const url = category ? `/blog/category/${category}` : '/blog'
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: false,
    })
}

function categoryUrl(category) {
    return category ? `/blog/category/${category}` : '/blog'
}

function formatDate(dateStr) {
    const date = new Date(dateStr)
    if (locale.value === 'ru') {
        return date.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' })
    }
    const months = ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr']
    return `${date.getDate()} ${months[date.getMonth()]}, ${date.getFullYear()}`
}

function estimateReadTime(content) {
    if (!content) return '3 min'
    const words = content.replace(/<[^>]*>/g, '').split(/\s+/).length
    const minutes = Math.max(2, Math.ceil(words / 200))
    return locale.value === 'ru' ? `${minutes} мин` : `${minutes} min`
}
</script>

<template>
  <LandingLayout v-slot="{ urgencyBarVisible }">
    <Head>
      <title>{{ metaTitle }}</title>
      <meta name="description" :content="metaDescription" />
      <meta property="og:title" :content="metaTitle" />
      <meta property="og:description" :content="metaDescription" />
      <meta property="og:url" content="https://biznespilot.uz/blog" />
      <meta property="og:type" content="blog" />
      <link rel="canonical" href="https://biznespilot.uz/blog" />
      <link rel="alternate" hreflang="uz" href="https://biznespilot.uz/blog" />
      <link rel="alternate" hreflang="ru" href="https://biznespilot.uz/blog" />
      <link rel="alternate" hreflang="x-default" href="https://biznespilot.uz/blog" />
    </Head>

    <!-- Hero Section -->
    <section
      class="relative py-20 overflow-hidden bg-gradient-to-b from-slate-50 via-white to-white"
      :class="urgencyBarVisible ? 'pt-44' : 'pt-32'"
    >
      <div class="absolute inset-0">
        <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-96 h-96 bg-blue-300 rounded-full opacity-20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-80 h-80 bg-indigo-300 rounded-full opacity-15 blur-3xl"></div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl mb-6 shadow-xl shadow-blue-500/25">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
          </div>
          <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 mb-4">
            <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
              {{ locale === 'ru' ? 'Блог' : 'Blog' }}
            </span>
            {{ locale === 'ru' ? ' BiznesPilot' : ' BiznesPilot' }}
          </h1>
          <p class="text-lg text-slate-600 leading-relaxed">
            {{ locale === 'ru'
              ? 'Полезные статьи о бизнесе, маркетинге, продажах и технологиях для предпринимателей Узбекистана'
              : "Biznes, marketing, sotuvlar va texnologiyalar haqida foydali maqolalar — O'zbekiston tadbirkorlari uchun"
            }}
          </p>
        </div>
      </div>
    </section>

    <!-- Category Filters -->
    <section class="sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-gray-100 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 py-4 overflow-x-auto scrollbar-hide">
          <Link
            :href="categoryUrl('')"
            class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all"
            :class="!selectedCategory
              ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            preserve-state
          >
            {{ locale === 'ru' ? 'Все статьи' : 'Barcha maqolalar' }}
          </Link>
          <Link
            v-for="cat in categories"
            :key="cat"
            :href="categoryUrl(cat)"
            class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all"
            :class="selectedCategory === cat
              ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            preserve-state
          >
            {{ categoryLabels[cat] || cat }}
          </Link>
        </div>
      </div>
    </section>

    <!-- Blog Posts Grid -->
    <section class="py-16 bg-gray-50/50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Posts Grid -->
        <div v-if="posts.data.length" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <Link
            v-for="post in posts.data"
            :key="post.id"
            :href="`/blog/${post.slug}`"
            class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-blue-200 hover:-translate-y-1"
          >
            <!-- Cover Image or Gradient Placeholder -->
            <div class="relative h-48 overflow-hidden">
              <img
                v-if="post.cover_image"
                :src="post.cover_image"
                :alt="post.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
              <div
                v-else
                class="w-full h-full bg-gradient-to-br from-blue-500 via-indigo-500 to-violet-600 flex items-center justify-center"
              >
                <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
              </div>
              <!-- Category Badge -->
              <div class="absolute top-3 left-3">
                <span
                  class="px-3 py-1 rounded-full text-xs font-semibold"
                  :class="categoryColors[post.category] || 'bg-gray-100 text-gray-700'"
                >
                  {{ categoryLabels[post.category] || post.category }}
                </span>
              </div>
            </div>

            <!-- Content -->
            <div class="p-6">
              <div class="flex items-center gap-3 text-sm text-gray-500 mb-3">
                <span>{{ formatDate(post.published_at) }}</span>
                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                <span>{{ estimateReadTime(post.content) }}</span>
              </div>
              <h2 class="text-lg font-bold text-slate-900 mb-2 group-hover:text-blue-600 transition-colors line-clamp-2">
                {{ post.title }}
              </h2>
              <p class="text-gray-600 text-sm leading-relaxed line-clamp-3">
                {{ post.excerpt }}
              </p>

              <!-- Tags -->
              <div v-if="post.tags && post.tags.length" class="mt-4 flex flex-wrap gap-1.5">
                <span
                  v-for="tag in post.tags.slice(0, 3)"
                  :key="tag"
                  class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-xs"
                >
                  #{{ tag }}
                </span>
              </div>
            </div>
          </Link>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-20">
          <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
          </svg>
          <h3 class="text-lg font-semibold text-gray-600 mb-2">
            {{ locale === 'ru' ? 'Статьи не найдены' : 'Maqolalar topilmadi' }}
          </h3>
          <p class="text-gray-500 mb-6">
            {{ locale === 'ru'
              ? 'В этой категории пока нет статей'
              : "Bu kategoriyada hali maqolalar yo'q"
            }}
          </p>
          <button
            @click="filterByCategory('')"
            class="px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium"
          >
            {{ locale === 'ru' ? 'Все статьи' : 'Barcha maqolalar' }}
          </button>
        </div>

        <!-- Pagination -->
        <div v-if="posts.last_page > 1" class="mt-12 flex justify-center items-center gap-2">
          <Link
            v-for="link in posts.links"
            :key="link.label"
            :href="link.url || '#'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
            :class="link.active
              ? 'bg-blue-600 text-white shadow-md'
              : link.url
                ? 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'
                : 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none'"
            v-html="link.label"
            preserve-scroll
          />
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">
          {{ locale === 'ru'
            ? 'Готовы автоматизировать свой бизнес?'
            : "Biznesingizni avtomatlashtirishga tayyormisiz?"
          }}
        </h2>
        <p class="text-blue-100 text-lg mb-8">
          {{ locale === 'ru'
            ? '14 дней бесплатно. Без привязки карты.'
            : "14 kun bepul sinab ko'ring. Karta talab qilinmaydi."
          }}
        </p>
        <Link
          href="/register"
          class="inline-flex items-center px-8 py-3.5 bg-white text-blue-600 rounded-xl font-bold text-lg hover:bg-blue-50 transition-colors shadow-xl shadow-black/10"
        >
          {{ locale === 'ru' ? 'Начать бесплатно' : 'Bepul boshlash' }}
          <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>
        </Link>
      </div>
    </section>
  </LandingLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
