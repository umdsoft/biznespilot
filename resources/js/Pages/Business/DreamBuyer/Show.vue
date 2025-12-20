<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    SparklesIcon,
    LightBulbIcon,
    MegaphoneIcon,
    DocumentTextIcon,
    ClockIcon,
    ChatBubbleLeftRightIcon,
    HeartIcon,
    ExclamationTriangleIcon,
    TrophyIcon,
    UserGroupIcon,
    StarIcon
} from '@heroicons/vue/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    dreamBuyer: {
        type: Object,
        required: true,
    },
});

const generatingContent = ref(false);
const generatingAds = ref(false);
const contentIdeas = ref(null);
const adCopy = ref(null);
const productForAd = ref('');

const profile = props.dreamBuyer.data || {};

const generateContentIdeas = async () => {
    generatingContent.value = true;

    try {
        const response = await fetch(route('business.dream-buyer.content-ideas', props.dreamBuyer.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();
        contentIdeas.value = data.content_ideas;
    } catch (error) {
        console.error('Error generating content ideas:', error);
    } finally {
        generatingContent.value = false;
    }
};

const generateAdCopy = async () => {
    if (!productForAd.value.trim()) {
        alert('Iltimos, mahsulot/xizmat nomini kiriting');
        return;
    }

    generatingAds.value = true;

    try {
        const response = await fetch(route('business.dream-buyer.ad-copy', props.dreamBuyer.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ product: productForAd.value }),
        });

        const data = await response.json();
        adCopy.value = data.ad_copy;
    } catch (error) {
        console.error('Error generating ad copy:', error);
    } finally {
        generatingAds.value = false;
    }
};

const deleteBuyer = () => {
    if (confirm(`${props.dreamBuyer.name} nomli Dream Buyerni o'chirishni xohlaysizmi?`)) {
        router.delete(route('business.dream-buyer.destroy', props.dreamBuyer.id));
    }
};
</script>

<template>
    <BusinessLayout :title="dreamBuyer.name">
        <Head :title="dreamBuyer.name" />

        <div class="py-12">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('business.dream-buyer.index')"
                        class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4"
                    >
                        <ArrowLeftIcon class="w-4 h-4 mr-1" />
                        Barcha Dream Buyers
                    </Link>

                    <!-- Hero Card -->
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-xl shadow-xl p-8 text-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                                        <UserGroupIcon class="w-10 h-10" />
                                    </div>
                                    <div>
                                        <h1 class="text-3xl font-bold">{{ dreamBuyer.name }}</h1>
                                        <p v-if="profile.tagline" class="text-indigo-100 mt-1">
                                            {{ profile.tagline }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="dreamBuyer.description" class="mb-4">
                                    <p class="text-indigo-100">{{ dreamBuyer.description }}</p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span v-if="dreamBuyer.is_primary" class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-sm font-semibold">
                                        <StarIconSolid class="w-4 h-4" />
                                        Primary
                                    </span>
                                    <span v-if="profile.avatar_name" class="inline-flex items-center gap-1 px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-semibold">
                                        <SparklesIcon class="w-4 h-4" />
                                        AI-Generated
                                    </span>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <Link
                                    :href="route('business.dream-buyer.edit', dreamBuyer.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-lg transition-all"
                                >
                                    <PencilIcon class="w-4 h-4" />
                                    Tahrirlash
                                </Link>
                                <button
                                    @click="deleteBuyer"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Quote -->
                        <div v-if="profile.quote" class="mt-6 p-4 bg-white/10 backdrop-blur-sm rounded-lg border-l-4 border-white/50">
                            <p class="text-white italic">"{{ profile.quote }}"</p>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column (2/3) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Demographics -->
                        <div v-if="profile.demographics" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <UserGroupIcon class="w-6 h-6 text-indigo-600" />
                                Demografik Ma'lumotlar
                            </h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-if="profile.demographics.age_range">
                                    <label class="text-sm font-medium text-gray-500">Yosh</label>
                                    <p class="text-gray-900 font-medium">{{ profile.demographics.age_range }}</p>
                                </div>
                                <div v-if="profile.demographics.gender">
                                    <label class="text-sm font-medium text-gray-500">Jins</label>
                                    <p class="text-gray-900 font-medium">{{ profile.demographics.gender }}</p>
                                </div>
                                <div v-if="profile.demographics.location">
                                    <label class="text-sm font-medium text-gray-500">Joylashuv</label>
                                    <p class="text-gray-900 font-medium">{{ profile.demographics.location }}</p>
                                </div>
                                <div v-if="profile.demographics.occupation">
                                    <label class="text-sm font-medium text-gray-500">Kasb</label>
                                    <p class="text-gray-900 font-medium">{{ profile.demographics.occupation }}</p>
                                </div>
                                <div v-if="profile.demographics.income_level">
                                    <label class="text-sm font-medium text-gray-500">Daromad</label>
                                    <p class="text-gray-900 font-medium">{{ profile.demographics.income_level }}</p>
                                </div>
                                <div v-if="profile.demographics.education">
                                    <label class="text-sm font-medium text-gray-500">Ta'lim</label>
                                    <p class="text-gray-900 font-medium">{{ profile.demographics.education }}</p>
                                </div>
                                <div v-if="profile.demographics.family_status">
                                    <label class="text-sm font-medium text-gray-500">Oila</label>
                                    <p class="text-gray-900 font-medium">{{ profile.demographics.family_status }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Psychographics -->
                        <div v-if="profile.psychographics" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <HeartIcon class="w-6 h-6 text-purple-600" />
                                Psixografik Ma'lumotlar
                            </h2>

                            <div v-if="profile.psychographics.personality_traits?.length" class="mb-4">
                                <label class="text-sm font-medium text-gray-700 mb-2 block">Shaxsiy xususiyatlar</label>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="trait in profile.psychographics.personality_traits"
                                        :key="trait"
                                        class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium"
                                    >
                                        {{ trait }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="profile.psychographics.values?.length" class="mb-4">
                                <label class="text-sm font-medium text-gray-700 mb-2 block">Qadriyatlar</label>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="value in profile.psychographics.values"
                                        :key="value"
                                        class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium"
                                    >
                                        {{ value }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="profile.psychographics.interests?.length" class="mb-4">
                                <label class="text-sm font-medium text-gray-700 mb-2 block">Qiziqishlar</label>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="interest in profile.psychographics.interests"
                                        :key="interest"
                                        class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium"
                                    >
                                        {{ interest }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="profile.psychographics.lifestyle">
                                <label class="text-sm font-medium text-gray-700 mb-1 block">Hayot tarzi</label>
                                <p class="text-gray-900">{{ profile.psychographics.lifestyle }}</p>
                            </div>
                        </div>

                        <!-- Pain Points -->
                        <div v-if="profile.pain_points?.length" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
                                Pain Points (Muammolar)
                            </h2>
                            <ul class="space-y-2">
                                <li
                                    v-for="point in profile.pain_points"
                                    :key="point"
                                    class="flex items-start gap-3 p-3 bg-red-50 border border-red-100 rounded-lg"
                                >
                                    <span class="text-red-600 font-bold">•</span>
                                    <span class="text-gray-800">{{ point }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Goals & Dreams -->
                        <div v-if="profile.goals_dreams?.length" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <TrophyIcon class="w-6 h-6 text-green-600" />
                                Maqsadlar va Orzular
                            </h2>
                            <ul class="space-y-2">
                                <li
                                    v-for="goal in profile.goals_dreams"
                                    :key="goal"
                                    class="flex items-start gap-3 p-3 bg-green-50 border border-green-100 rounded-lg"
                                >
                                    <span class="text-green-600 font-bold">✓</span>
                                    <span class="text-gray-800">{{ goal }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Fears & Objections -->
                        <div v-if="profile.fears_objections?.length" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <ExclamationTriangleIcon class="w-6 h-6 text-yellow-600" />
                                Qo'rquvlar va E'tirozlar
                            </h2>
                            <ul class="space-y-2">
                                <li
                                    v-for="fear in profile.fears_objections"
                                    :key="fear"
                                    class="flex items-start gap-3 p-3 bg-yellow-50 border border-yellow-100 rounded-lg"
                                >
                                    <span class="text-yellow-600 font-bold">⚠</span>
                                    <span class="text-gray-800">{{ fear }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Daily Journey -->
                        <div v-if="profile.daily_journey" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <ClockIcon class="w-6 h-6 text-blue-600" />
                                Kundalik Hayot
                            </h2>
                            <div class="space-y-3">
                                <div v-if="profile.daily_journey.morning" class="p-3 bg-orange-50 border border-orange-100 rounded-lg">
                                    <label class="text-sm font-semibold text-orange-700 block mb-1">🌅 Ertalab</label>
                                    <p class="text-gray-800">{{ profile.daily_journey.morning }}</p>
                                </div>
                                <div v-if="profile.daily_journey.afternoon" class="p-3 bg-yellow-50 border border-yellow-100 rounded-lg">
                                    <label class="text-sm font-semibold text-yellow-700 block mb-1">☀️ Tushdan keyin</label>
                                    <p class="text-gray-800">{{ profile.daily_journey.afternoon }}</p>
                                </div>
                                <div v-if="profile.daily_journey.evening" class="p-3 bg-indigo-50 border border-indigo-100 rounded-lg">
                                    <label class="text-sm font-semibold text-indigo-700 block mb-1">🌙 Kechqurun</label>
                                    <p class="text-gray-800">{{ profile.daily_journey.evening }}</p>
                                </div>
                                <div v-if="profile.daily_journey.peak_time" class="p-3 bg-green-50 border border-green-100 rounded-lg">
                                    <label class="text-sm font-semibold text-green-700 block mb-1">⚡ Eng faol vaqt</label>
                                    <p class="text-gray-800">{{ profile.daily_journey.peak_time }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (1/3) -->
                    <div class="space-y-6">
                        <!-- Communication Style -->
                        <div v-if="profile.communication_style" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <ChatBubbleLeftRightIcon class="w-6 h-6 text-indigo-600" />
                                Muloqot
                            </h2>

                            <div v-if="profile.communication_style.preferred_channels?.length" class="mb-4">
                                <label class="text-sm font-medium text-gray-700 mb-2 block">Afzal Kanallar</label>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="channel in profile.communication_style.preferred_channels"
                                        :key="channel"
                                        class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs font-medium"
                                    >
                                        {{ channel }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="profile.communication_style.tone" class="mb-4">
                                <label class="text-sm font-medium text-gray-700 mb-1 block">Ohang</label>
                                <p class="text-gray-900 text-sm">{{ profile.communication_style.tone }}</p>
                            </div>

                            <div v-if="profile.communication_style.language_tips?.length" class="mb-4">
                                <label class="text-sm font-medium text-gray-700 mb-2 block">Maslahatlar</label>
                                <ul class="space-y-1">
                                    <li v-for="tip in profile.communication_style.language_tips" :key="tip" class="text-sm text-gray-800 flex items-start gap-2">
                                        <span class="text-green-600">✓</span>
                                        <span>{{ tip }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="profile.communication_style.avoid?.length">
                                <label class="text-sm font-medium text-gray-700 mb-2 block">Ishlatmaslik</label>
                                <ul class="space-y-1">
                                    <li v-for="avoid in profile.communication_style.avoid" :key="avoid" class="text-sm text-gray-800 flex items-start gap-2">
                                        <span class="text-red-600">✗</span>
                                        <span>{{ avoid }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Purchase Triggers -->
                        <div v-if="profile.purchase_triggers?.length" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <SparklesIcon class="w-6 h-6 text-yellow-600" />
                                Xarid Trigerlari
                            </h2>
                            <ul class="space-y-2">
                                <li
                                    v-for="trigger in profile.purchase_triggers"
                                    :key="trigger"
                                    class="text-sm text-gray-800 p-2 bg-yellow-50 border border-yellow-100 rounded-lg"
                                >
                                    {{ trigger }}
                                </li>
                            </ul>
                        </div>

                        <!-- Marketing Insights -->
                        <div v-if="profile.marketing_insights" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <LightBulbIcon class="w-6 h-6 text-orange-600" />
                                Marketing Insights
                            </h2>

                            <div v-if="profile.marketing_insights.best_approach" class="mb-4">
                                <label class="text-sm font-medium text-gray-700 mb-1 block">Eng yaxshi yondashuv</label>
                                <p class="text-sm text-gray-900">{{ profile.marketing_insights.best_approach }}</p>
                            </div>

                            <div v-if="profile.marketing_insights.messaging_tips?.length" class="mb-4">
                                <label class="text-sm font-medium text-gray-700 mb-2 block">Xabar Maslahatlar</label>
                                <ul class="space-y-1">
                                    <li v-for="tip in profile.marketing_insights.messaging_tips" :key="tip" class="text-xs text-gray-800">
                                        • {{ tip }}
                                    </li>
                                </ul>
                            </div>

                            <div v-if="profile.marketing_insights.content_ideas?.length" class="mb-4">
                                <label class="text-sm font-medium text-gray-700 mb-2 block">Kontent G'oyalari</label>
                                <ul class="space-y-1">
                                    <li v-for="idea in profile.marketing_insights.content_ideas" :key="idea" class="text-xs text-gray-800">
                                        • {{ idea }}
                                    </li>
                                </ul>
                            </div>

                            <div v-if="profile.marketing_insights.offer_suggestions?.length">
                                <label class="text-sm font-medium text-gray-700 mb-2 block">Taklif Tavsiyalari</label>
                                <ul class="space-y-1">
                                    <li v-for="offer in profile.marketing_insights.offer_suggestions" :key="offer" class="text-xs text-gray-800">
                                        • {{ offer }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- AI Tools -->
                        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-md p-6 text-white">
                            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                                <SparklesIcon class="w-6 h-6" />
                                AI Marketing Tools
                            </h2>
                            <div class="space-y-3">
                                <button
                                    @click="generateContentIdeas"
                                    :disabled="generatingContent"
                                    class="w-full px-4 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-lg transition-all flex items-center justify-center gap-2"
                                >
                                    <DocumentTextIcon class="w-5 h-5" />
                                    <span v-if="generatingContent">Yaratilmoqda...</span>
                                    <span v-else>Kontent G'oyalari</span>
                                </button>

                                <div>
                                    <input
                                        v-model="productForAd"
                                        type="text"
                                        placeholder="Mahsulot/Xizmat nomi"
                                        class="w-full px-4 py-2 mb-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50"
                                    />
                                    <button
                                        @click="generateAdCopy"
                                        :disabled="generatingAds || !productForAd.trim()"
                                        class="w-full px-4 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                                    >
                                        <MegaphoneIcon class="w-5 h-5" />
                                        <span v-if="generatingAds">Yaratilmoqda...</span>
                                        <span v-else>Reklama Matni</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generated Content Ideas -->
                <div v-if="contentIdeas?.length" class="mt-6 bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <DocumentTextIcon class="w-7 h-7 text-indigo-600" />
                        Kontent G'oyalari
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="(idea, index) in contentIdeas"
                            :key="index"
                            class="p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-300 transition-colors"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-1 bg-indigo-100 text-indigo-800 text-xs font-semibold rounded">{{ idea.type }}</span>
                                <span class="text-xs text-gray-500">{{ idea.platform }}</span>
                            </div>
                            <h3 class="font-bold text-gray-900 mb-2">{{ idea.title }}</h3>
                            <p class="text-sm text-gray-700 mb-2">{{ idea.key_message }}</p>
                            <p class="text-xs text-indigo-600 font-medium">CTA: {{ idea.cta }}</p>
                        </div>
                    </div>
                </div>

                <!-- Generated Ad Copy -->
                <div v-if="adCopy?.length" class="mt-6 bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <MegaphoneIcon class="w-7 h-7 text-purple-600" />
                        Reklama Matnlari - {{ productForAd }}
                    </h2>
                    <div class="space-y-4">
                        <div
                            v-for="(ad, index) in adCopy"
                            :key="index"
                            class="p-5 border-2 border-purple-200 rounded-lg hover:border-purple-400 transition-colors"
                        >
                            <span class="inline-block px-3 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded-full mb-3">
                                {{ ad.type }}
                            </span>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">{{ ad.headline }}</h3>
                            <p class="text-gray-700 mb-3">{{ ad.body }}</p>
                            <p class="text-purple-600 font-semibold">{{ ad.cta }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
