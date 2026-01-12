<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';

const props = defineProps({
    dreamBuyer: { type: Object, default: null },
    isEdit: { type: Boolean, default: false },
});

const currentStep = ref(1);
const totalSteps = 5;

const form = useForm({
    name: props.dreamBuyer?.name || '',
    description: props.dreamBuyer?.description || '',
    where_spend_time: props.dreamBuyer?.where_spend_time || '',
    info_sources: props.dreamBuyer?.info_sources || '',
    frustrations: props.dreamBuyer?.frustrations || '',
    dreams: props.dreamBuyer?.dreams || '',
    fears: props.dreamBuyer?.fears || '',
    communication_preferences: props.dreamBuyer?.communication_preferences || '',
    language_style: props.dreamBuyer?.language_style || '',
    daily_routine: props.dreamBuyer?.daily_routine || '',
    happiness_triggers: props.dreamBuyer?.happiness_triggers || '',
    priority: props.dreamBuyer?.priority || 'medium',
    is_primary: props.dreamBuyer?.is_primary || false,
    generate_profile: false,
});

const steps = [
    { id: 1, title: 'Asosiy ma\'lumotlar', icon: '👤' },
    { id: 2, title: 'Qayerda?', icon: '📍' },
    { id: 3, title: 'Muammolar', icon: '😤' },
    { id: 4, title: 'Orzular', icon: '✨' },
    { id: 5, title: 'Xulosa', icon: '🎯' },
];

const canGoNext = computed(() => {
    switch (currentStep.value) {
        case 1: return form.name.trim().length > 0;
        case 2: return form.where_spend_time.trim().length > 0;
        case 3: return form.frustrations.trim().length > 0;
        case 4: return form.dreams.trim().length > 0;
        default: return true;
    }
});

const nextStep = () => {
    if (currentStep.value < totalSteps && canGoNext.value) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submit = () => {
    const routeName = props.isEdit
        ? route('marketing.dream-buyer.update', props.dreamBuyer.id)
        : route('marketing.dream-buyer.store');

    const method = props.isEdit ? 'put' : 'post';

    form[method](routeName);
};
</script>

<template>
    <MarketingLayout :title="isEdit ? 'Profilni tahrirlash' : 'Yangi Ideal Mijoz'">
        <Head :title="isEdit ? 'Profilni tahrirlash' : 'Yangi Ideal Mijoz'" />

        <div class="max-w-3xl mx-auto">
            <!-- Progress -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Qadam {{ currentStep }} / {{ totalSteps }}
                    </span>
                    <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                        {{ steps[currentStep - 1]?.title }}
                    </span>
                </div>
                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 transition-all duration-300"
                        :style="{ width: `${(currentStep / totalSteps) * 100}%` }"
                    ></div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg overflow-hidden">
                <form @submit.prevent="submit">
                    <!-- Step 1: Basic Info -->
                    <div v-show="currentStep === 1" class="p-8">
                        <div class="text-center mb-8">
                            <span class="text-4xl mb-4 block">👤</span>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Asosiy ma'lumotlar</h2>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Ideal mijozingizga nom bering</p>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Profil nomi *</label>
                                <input v-model="form.name" type="text" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" placeholder="Masalan: Yosh tadbirkor" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Qisqacha tavsif</label>
                                <textarea v-model="form.description" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" placeholder="Bu mijoz haqida qisqacha..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Where -->
                    <div v-show="currentStep === 2" class="p-8">
                        <div class="text-center mb-8">
                            <span class="text-4xl mb-4 block">📍</span>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Qayerda vaqt o'tkazadi?</h2>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Mijozlaringiz qayerda bo'ladi</p>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Vaqt o'tkazadigan joylar *</label>
                                <textarea v-model="form.where_spend_time" rows="4" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" placeholder="Instagram, Telegram, YouTube..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ma'lumot manbalari</label>
                                <textarea v-model="form.info_sources" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" placeholder="Bloglar, podkastlar, do'stlar..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Problems -->
                    <div v-show="currentStep === 3" class="p-8">
                        <div class="text-center mb-8">
                            <span class="text-4xl mb-4 block">😤</span>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Muammolari nima?</h2>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Qanday qiyinchiliklarga duch keladi</p>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Frustratsiyalar *</label>
                                <textarea v-model="form.frustrations" rows="4" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" placeholder="Vaqt yetishmaydi, pulim yo'q..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Qo'rquvlari</label>
                                <textarea v-model="form.fears" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" placeholder="Muvaffaqiyatsizlik, aldanish..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Dreams -->
                    <div v-show="currentStep === 4" class="p-8">
                        <div class="text-center mb-8">
                            <span class="text-4xl mb-4 block">✨</span>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Nimani xohlaydi?</h2>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Orzulari va maqsadlari</p>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Orzulari *</label>
                                <textarea v-model="form.dreams" rows="4" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" placeholder="Ko'proq pul ishlash, erkinlik..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Baxt omillari</label>
                                <textarea v-model="form.happiness_triggers" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" placeholder="Oila bilan vaqt, sayohat..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Summary -->
                    <div v-show="currentStep === 5" class="p-8">
                        <div class="text-center mb-8">
                            <span class="text-4xl mb-4 block">🎯</span>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Xulosa</h2>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">Profilingiz tayyor</p>
                        </div>

                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl p-6 border border-indigo-200 dark:border-indigo-800">
                            <h3 class="font-bold text-xl text-gray-900 dark:text-gray-100 mb-4">{{ form.name || 'Nomsiz profil' }}</h3>
                            <p v-if="form.description" class="text-gray-600 dark:text-gray-400 mb-4">{{ form.description }}</p>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div v-if="form.where_spend_time">
                                    <span class="text-gray-500 dark:text-gray-400">Qayerda:</span>
                                    <p class="text-gray-900 dark:text-gray-100">{{ form.where_spend_time.substring(0, 50) }}...</p>
                                </div>
                                <div v-if="form.frustrations">
                                    <span class="text-gray-500 dark:text-gray-400">Muammolar:</span>
                                    <p class="text-gray-900 dark:text-gray-100">{{ form.frustrations.substring(0, 50) }}...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="px-8 py-6 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <button
                            v-if="currentStep > 1"
                            type="button"
                            @click="prevStep"
                            class="px-6 py-2.5 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                        >
                            Orqaga
                        </button>
                        <div v-else></div>

                        <button
                            v-if="currentStep < totalSteps"
                            type="button"
                            @click="nextStep"
                            :disabled="!canGoNext"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:from-indigo-600 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                        >
                            Keyingi
                        </button>
                        <button
                            v-else
                            type="submit"
                            :disabled="form.processing"
                            class="px-8 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:from-indigo-600 hover:to-purple-700 disabled:opacity-50 transition-all"
                        >
                            {{ form.processing ? 'Saqlanmoqda...' : (isEdit ? 'Yangilash' : 'Yaratish') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MarketingLayout>
</template>
