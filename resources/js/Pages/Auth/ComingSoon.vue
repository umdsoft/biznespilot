<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

// Countdown to January 25, 2026
const targetDate = new Date('2026-01-25T00:00:00+05:00');

const countdown = ref({
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0
});

let countdownInterval = null;

const updateCountdown = () => {
    const now = new Date();
    const diff = targetDate - now;

    if (diff <= 0) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        return;
    }

    countdown.value = {
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
        seconds: Math.floor((diff % (1000 * 60)) / 1000)
    };
};

onMounted(() => {
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
});

const padZero = (num) => String(num).padStart(2, '0');

const features = computed(() => [
    {
        icon: '📞',
        title: t('auth.coming_soon.feature_ai_calls'),
        desc: t('auth.coming_soon.feature_ai_calls_desc')
    },
    {
        icon: '🤖',
        title: t('auth.coming_soon.feature_ai_marketing'),
        desc: t('auth.coming_soon.feature_ai_marketing_desc')
    },
    {
        icon: '📊',
        title: t('auth.coming_soon.feature_custdev'),
        desc: t('auth.coming_soon.feature_custdev_desc')
    },
    {
        icon: '🎯',
        title: t('auth.coming_soon.feature_competitor'),
        desc: t('auth.coming_soon.feature_competitor_desc')
    },
    {
        icon: '📈',
        title: t('auth.coming_soon.feature_sales'),
        desc: t('auth.coming_soon.feature_sales_desc')
    },
    {
        icon: '💬',
        title: t('auth.coming_soon.feature_inbox'),
        desc: t('auth.coming_soon.feature_inbox_desc')
    }
]);

const stats = computed(() => [
    { value: '10x', label: t('auth.coming_soon.stat_faster') },
    { value: '100%', label: t('auth.coming_soon.stat_automated') },
    { value: '24/7', label: t('auth.coming_soon.stat_ai_helper') }
]);
</script>

<template>
    <Head :title="t('auth.coming_soon.page_title')" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
        <div class="max-w-5xl mx-auto px-4 py-12">
            <!-- Header -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-600 rounded-2xl shadow-lg shadow-blue-500/30 mb-6">
                    <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h1 class="text-4xl sm:text-5xl font-bold text-white mb-3">BiznesPilot</h1>
                <p class="text-xl text-blue-300">{{ t('auth.coming_soon.tagline') }}</p>
            </div>

            <!-- Countdown -->
            <div class="text-center mb-12">
                <p class="text-gray-400 text-sm mb-4 uppercase tracking-wider">{{ t('auth.coming_soon.countdown_label') }}</p>
                <div class="flex items-center justify-center gap-3 sm:gap-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 sm:p-5 min-w-[75px] sm:min-w-[90px]">
                        <div class="text-3xl sm:text-5xl font-bold text-white">{{ padZero(countdown.days) }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ t('auth.coming_soon.days') }}</div>
                    </div>
                    <div class="text-2xl text-blue-400 font-bold">:</div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 sm:p-5 min-w-[75px] sm:min-w-[90px]">
                        <div class="text-3xl sm:text-5xl font-bold text-white">{{ padZero(countdown.hours) }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ t('auth.coming_soon.hours') }}</div>
                    </div>
                    <div class="text-2xl text-blue-400 font-bold">:</div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 sm:p-5 min-w-[75px] sm:min-w-[90px]">
                        <div class="text-3xl sm:text-5xl font-bold text-white">{{ padZero(countdown.minutes) }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ t('auth.coming_soon.minutes') }}</div>
                    </div>
                    <div class="text-2xl text-blue-400 font-bold">:</div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 sm:p-5 min-w-[75px] sm:min-w-[90px]">
                        <div class="text-3xl sm:text-5xl font-bold text-white">{{ padZero(countdown.seconds) }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ t('auth.coming_soon.seconds') }}</div>
                    </div>
                </div>
            </div>

            <!-- Main Value Proposition -->
            <div class="bg-white/5 backdrop-blur-xl rounded-3xl p-8 sm:p-10 border border-white/10 mb-10">
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-green-500/20 text-green-300 rounded-full text-sm font-medium mb-6">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        {{ t('auth.coming_soon.launch_date') }}
                    </span>

                    <h2 class="text-2xl sm:text-3xl font-bold text-white mb-4" v-html="t('auth.coming_soon.hero_title')">
                    </h2>

                    <p class="text-gray-300 text-lg leading-relaxed" v-html="t('auth.coming_soon.hero_desc')">
                    </p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mb-10">
                    <div v-for="stat in stats" :key="stat.label" class="text-center p-4 bg-white/5 rounded-2xl">
                        <div class="text-3xl sm:text-4xl font-bold text-blue-400 mb-1">{{ stat.value }}</div>
                        <div class="text-sm text-gray-400">{{ stat.label }}</div>
                    </div>
                </div>

                <!-- Features Grid -->
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="p-5 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 hover:border-blue-500/30 transition-all group"
                    >
                        <div class="text-3xl mb-3">{{ feature.icon }}</div>
                        <h3 class="text-lg font-bold text-white mb-2 group-hover:text-blue-300 transition-colors">
                            {{ feature.title }}
                        </h3>
                        <p class="text-gray-400 text-sm leading-relaxed">{{ feature.desc }}</p>
                    </div>
                </div>
            </div>

            <!-- Problem → Solution -->
            <div class="grid sm:grid-cols-2 gap-6 mb-10">
                <!-- Problem -->
                <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-red-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-red-300">{{ t('auth.coming_soon.problems') }}</h3>
                    </div>
                    <ul class="space-y-3 text-gray-300 text-sm">
                        <li class="flex items-start gap-2">
                            <span class="text-red-400 mt-0.5">•</span>
                            {{ t('auth.coming_soon.problem_1') }}
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-red-400 mt-0.5">•</span>
                            {{ t('auth.coming_soon.problem_2') }}
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-red-400 mt-0.5">•</span>
                            {{ t('auth.coming_soon.problem_3') }}
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-red-400 mt-0.5">•</span>
                            {{ t('auth.coming_soon.problem_4') }}
                        </li>
                    </ul>
                </div>

                <!-- Solution -->
                <div class="bg-green-500/10 border border-green-500/20 rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-green-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-green-300">{{ t('auth.coming_soon.solution') }}</h3>
                    </div>
                    <ul class="space-y-3 text-gray-300 text-sm">
                        <li class="flex items-start gap-2">
                            <span class="text-green-400 mt-0.5">✓</span>
                            {{ t('auth.coming_soon.solution_1') }}
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-400 mt-0.5">✓</span>
                            <span v-html="t('auth.coming_soon.solution_2')"></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-400 mt-0.5">✓</span>
                            {{ t('auth.coming_soon.solution_3') }}
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-400 mt-0.5">✓</span>
                            {{ t('auth.coming_soon.solution_4') }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- CTA -->
            <div class="text-center">
                <p class="text-gray-400 mb-4">{{ t('auth.coming_soon.follow_us') }}</p>
                <div class="flex items-center justify-center gap-4">
                    <a
                        href="https://instagram.com/biznespilot.uz"
                        target="_blank"
                        class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold rounded-xl transition-all"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                        Instagram
                    </a>
                    <a
                        href="https://t.me/biznespilot"
                        target="_blank"
                        class="flex items-center gap-2 px-6 py-3 bg-[#0088cc] hover:bg-[#0077b5] text-white font-semibold rounded-xl transition-all"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                        Telegram
                    </a>
                </div>

                <!-- Login link -->
                <p class="text-gray-500 mt-8 text-sm">
                    {{ t('auth.have_account') }}
                    <a href="/login" class="text-blue-400 hover:text-blue-300 font-medium">{{ t('auth.login_link') }}</a>
                </p>
            </div>

            <!-- Footer -->
            <footer class="border-t border-white/10 mt-12 pt-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-white font-semibold">BiznesPilot AI</span>
                    </div>
                    <p class="text-gray-500 text-sm">
                        &copy; {{ new Date().getFullYear() }} BiznesPilot. {{ t('auth.copyright') }}
                    </p>
                </div>
            </footer>
        </div>
    </div>
</template>
