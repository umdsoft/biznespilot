<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import LandingLayout from '@/layouts/LandingLayout.vue'
import { useLandingTranslations } from '@/composables/useLandingTranslations'

const { locale } = useLandingTranslations()

const props = defineProps({
    post: Object,
    relatedPosts: Array,
})

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

function formatDate(dateStr) {
    const date = new Date(dateStr)
    if (locale.value === 'ru') {
        return date.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' })
    }
    const months = ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr']
    return `${date.getDate()} ${months[date.getMonth()]}, ${date.getFullYear()}`
}

function formatISODate(dateStr) {
    return new Date(dateStr).toISOString()
}

function estimateReadTime(content) {
    if (!content) return '3 min'
    const words = content.replace(/<[^>]*>/g, '').split(/\s+/).length
    const minutes = Math.max(2, Math.ceil(words / 200))
    return locale.value === 'ru' ? `${minutes} мин чтения` : `${minutes} min o'qish`
}

function stripHtml(html) {
    return html ? html.replace(/<[^>]*>/g, '') : ''
}

// BlogPosting JSON-LD Schema
const blogPostingSchema = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "headline": props.post.meta_title || props.post.title,
    "description": props.post.meta_description || props.post.excerpt,
    "author": {
        "@type": "Organization",
        "name": props.post.author_name || "BiznesPilot",
        "url": "https://biznespilot.uz"
    },
    "publisher": {
        "@type": "Organization",
        "name": "BiznesPilot",
        "url": "https://biznespilot.uz",
        "logo": {
            "@type": "ImageObject",
            "url": "https://biznespilot.uz/images/og-image.jpg"
        }
    },
    "datePublished": formatISODate(props.post.published_at),
    "dateModified": formatISODate(props.post.updated_at || props.post.published_at),
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": `https://biznespilot.uz/blog/${props.post.slug}`
    },
    "image": props.post.cover_image || "https://biznespilot.uz/images/og-image.jpg",
    "url": `https://biznespilot.uz/blog/${props.post.slug}`,
    "inLanguage": props.post.locale === 'ru' ? 'ru' : 'uz',
    "keywords": (props.post.tags || []).join(', '),
    "articleSection": categoryLabels[props.post.category] || props.post.category,
    "wordCount": stripHtml(props.post.content).split(/\s+/).length,
}))

// BreadcrumbList Schema
const breadcrumbSchema = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "BiznesPilot",
            "item": "https://biznespilot.uz"
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": "Blog",
            "item": "https://biznespilot.uz/blog"
        },
        {
            "@type": "ListItem",
            "position": 3,
            "name": props.post.title,
            "item": `https://biznespilot.uz/blog/${props.post.slug}`
        }
    ]
}))
</script>

<template>
  <LandingLayout v-slot="{ urgencyBarVisible }">
    <Head>
      <title>{{ post.meta_title || post.title }} | BiznesPilot</title>
      <meta name="description" :content="post.meta_description || post.excerpt" />
      <meta name="author" :content="post.author_name || 'BiznesPilot'" />
      <meta name="keywords" :content="(post.tags || []).join(', ')" />
      <meta property="og:title" :content="post.meta_title || post.title" />
      <meta property="og:description" :content="post.meta_description || post.excerpt" />
      <meta property="og:url" :content="`https://biznespilot.uz/blog/${post.slug}`" />
      <meta property="og:type" content="article" />
      <meta property="og:image" :content="post.cover_image || 'https://biznespilot.uz/images/og-image.jpg'" />
      <meta property="article:published_time" :content="formatISODate(post.published_at)" />
      <meta property="article:section" :content="categoryLabels[post.category] || post.category" />
      <meta v-for="tag in (post.tags || []).slice(0, 5)" :key="tag" property="article:tag" :content="tag" />
      <link rel="canonical" :href="`https://biznespilot.uz/blog/${post.slug}`" />
      <link rel="alternate" hreflang="uz" :href="`https://biznespilot.uz/blog/${post.slug}`" />
      <link rel="alternate" hreflang="ru" :href="`https://biznespilot.uz/blog/${post.slug}`" />
      <link rel="alternate" hreflang="x-default" :href="`https://biznespilot.uz/blog/${post.slug}`" />
      <script type="application/ld+json" v-html="blogPostingSchema" />
      <script type="application/ld+json" v-html="breadcrumbSchema" />
    </Head>

    <article>
      <!-- Hero / Header -->
      <section
        class="relative py-16 overflow-hidden bg-gradient-to-b from-slate-50 via-white to-white"
        :class="urgencyBarVisible ? 'pt-40' : 'pt-28'"
      >
        <div class="absolute inset-0">
          <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-96 h-96 bg-blue-300 rounded-full opacity-15 blur-3xl"></div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <!-- Breadcrumb -->
          <nav class="mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center gap-2 text-sm text-gray-500">
              <li>
                <Link href="/" class="hover:text-blue-600 transition-colors">BiznesPilot</Link>
              </li>
              <li>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </li>
              <li>
                <Link href="/blog" class="hover:text-blue-600 transition-colors">Blog</Link>
              </li>
              <li>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </li>
              <li class="text-gray-700 font-medium truncate max-w-xs">{{ post.title }}</li>
            </ol>
          </nav>

          <!-- Category & Meta -->
          <div class="flex flex-wrap items-center gap-3 mb-5">
            <Link
              :href="`/blog?category=${post.category}`"
              class="px-3 py-1 rounded-full text-xs font-semibold transition-colors hover:opacity-80"
              :class="categoryColors[post.category] || 'bg-gray-100 text-gray-700'"
            >
              {{ categoryLabels[post.category] || post.category }}
            </Link>
            <span class="text-sm text-gray-500">{{ formatDate(post.published_at) }}</span>
            <span class="w-1 h-1 rounded-full bg-gray-300"></span>
            <span class="text-sm text-gray-500">{{ estimateReadTime(post.content) }}</span>
            <span class="w-1 h-1 rounded-full bg-gray-300"></span>
            <span class="text-sm text-gray-500">
              {{ post.views_count }} {{ locale === 'ru' ? 'просмотров' : "ko'rish" }}
            </span>
          </div>

          <!-- Title -->
          <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 leading-tight">
            {{ post.title }}
          </h1>

          <!-- Excerpt -->
          <p class="text-lg text-slate-600 leading-relaxed">
            {{ post.excerpt }}
          </p>

          <!-- Author -->
          <div class="mt-6 flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-800">{{ post.author_name || 'BiznesPilot' }}</p>
              <p class="text-xs text-gray-500">{{ formatDate(post.published_at) }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Article Content -->
      <section class="py-12 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="prose prose-lg prose-slate max-w-none prose-headings:font-bold prose-headings:text-slate-900 prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4 prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-3 prose-p:text-slate-600 prose-p:leading-relaxed prose-a:text-blue-600 prose-a:no-underline hover:prose-a:underline prose-strong:text-slate-800 prose-li:text-slate-600 prose-ul:my-4 prose-ol:my-4"
            v-html="post.content"
          />

          <!-- Tags -->
          <div v-if="post.tags && post.tags.length" class="mt-10 pt-8 border-t border-gray-200">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-sm font-semibold text-gray-700 mr-1">
                {{ locale === 'ru' ? 'Теги:' : 'Teglar:' }}
              </span>
              <Link
                v-for="tag in post.tags"
                :key="tag"
                :href="`/blog?category=${post.category}`"
                class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm hover:bg-blue-100 hover:text-blue-700 transition-colors"
              >
                #{{ tag }}
              </Link>
            </div>
          </div>

          <!-- Share -->
          <div class="mt-8 pt-8 border-t border-gray-200">
            <p class="text-sm font-semibold text-gray-700 mb-3">
              {{ locale === 'ru' ? 'Поделиться:' : 'Ulashish:' }}
            </p>
            <div class="flex gap-3">
              <a
                :href="`https://t.me/share/url?url=https://biznespilot.uz/blog/${post.slug}&text=${encodeURIComponent(post.title)}`"
                target="_blank"
                rel="noopener"
                class="flex items-center gap-2 px-4 py-2 bg-[#0088cc] text-white rounded-lg text-sm font-medium hover:bg-[#0077b5] transition-colors"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                Telegram
              </a>
              <a
                :href="`https://www.facebook.com/sharer/sharer.php?u=https://biznespilot.uz/blog/${post.slug}`"
                target="_blank"
                rel="noopener"
                class="flex items-center gap-2 px-4 py-2 bg-[#1877F2] text-white rounded-lg text-sm font-medium hover:bg-[#166FE5] transition-colors"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                Facebook
              </a>
              <a
                :href="`https://twitter.com/intent/tweet?url=https://biznespilot.uz/blog/${post.slug}&text=${encodeURIComponent(post.title)}`"
                target="_blank"
                rel="noopener"
                class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                X
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- Related Posts -->
      <section v-if="relatedPosts && relatedPosts.length" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="text-2xl font-bold text-slate-900 mb-8">
            {{ locale === 'ru' ? 'Похожие статьи' : "O'xshash maqolalar" }}
          </h2>
          <div class="grid md:grid-cols-3 gap-8">
            <Link
              v-for="related in relatedPosts"
              :key="related.id"
              :href="`/blog/${related.slug}`"
              class="group bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 hover:border-blue-200"
            >
              <div class="relative h-40 overflow-hidden">
                <img
                  v-if="related.cover_image"
                  :src="related.cover_image"
                  :alt="related.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                />
                <div v-else class="w-full h-full bg-gradient-to-br from-blue-500 via-indigo-500 to-violet-600 flex items-center justify-center">
                  <svg class="w-12 h-12 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                  </svg>
                </div>
                <div class="absolute top-3 left-3">
                  <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="categoryColors[related.category] || 'bg-gray-100 text-gray-700'">
                    {{ categoryLabels[related.category] || related.category }}
                  </span>
                </div>
              </div>
              <div class="p-5">
                <p class="text-xs text-gray-500 mb-2">{{ formatDate(related.published_at) }}</p>
                <h3 class="font-bold text-slate-900 group-hover:text-blue-600 transition-colors line-clamp-2">
                  {{ related.title }}
                </h3>
              </div>
            </Link>
          </div>
        </div>
      </section>

      <!-- CTA Section -->
      <section class="py-16 bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 class="text-3xl font-bold text-white mb-4">
            {{ locale === 'ru'
              ? 'Попробуйте BiznesPilot бесплатно'
              : "BiznesPilot ni bepul sinab ko'ring"
            }}
          </h2>
          <p class="text-blue-100 text-lg mb-8">
            {{ locale === 'ru'
              ? 'CRM, маркетинг, финансы и HR — всё в одной платформе. 14 дней бесплатно.'
              : "CRM, marketing, moliya va HR — barchasi bitta platformada. 14 kun bepul."
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
    </article>
  </LandingLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
