<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    leadForm: Object,
    isEmbed: Boolean,
    slug: String,
});

const formData = ref({});
const isSubmitting = ref(false);
const isSubmitted = ref(false);
const submitResult = ref(null);
const errors = ref({});

// Initialize form data
onMounted(() => {
    props.leadForm.fields.forEach(field => {
        formData.value[field.id] = '';
    });

    // Get UTM parameters from URL
    const urlParams = new URLSearchParams(window.location.search);
    window.utmParams = {
        utm_source: urlParams.get('utm_source'),
        utm_medium: urlParams.get('utm_medium'),
        utm_campaign: urlParams.get('utm_campaign'),
        utm_term: urlParams.get('utm_term'),
        utm_content: urlParams.get('utm_content'),
    };
});

const validateField = (field) => {
    const value = formData.value[field.id];

    if (field.required && !value) {
        return 'Bu maydon majburiy';
    }

    if (value) {
        switch (field.type) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    return 'Noto\'g\'ri email format';
                }
                break;
            case 'phone':
                const phoneRegex = /^\+?[0-9\s\-()]{7,20}$/;
                if (!phoneRegex.test(value.replace(/\s/g, ''))) {
                    return 'Noto\'g\'ri telefon format';
                }
                break;
        }
    }

    return null;
};

const validateForm = () => {
    errors.value = {};
    let isValid = true;

    props.leadForm.fields.forEach(field => {
        const error = validateField(field);
        if (error) {
            errors.value[field.id] = error;
            isValid = false;
        }
    });

    return isValid;
};

const submitForm = async () => {
    if (!validateForm()) return;

    isSubmitting.value = true;

    try {
        const response = await fetch(`/f/${props.slug}/submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                ...formData.value,
                ...window.utmParams,
            }),
        });

        const result = await response.json();

        if (result.success) {
            submitResult.value = result;
            isSubmitted.value = true;

            // Handle redirect if specified
            if (result.redirect_url) {
                setTimeout(() => {
                    window.location.href = result.redirect_url;
                }, 3000);
            }
        } else {
            errors.value = { form: result.error || 'Xatolik yuz berdi' };
        }
    } catch (error) {
        errors.value = { form: 'Tarmoq xatosi. Qayta urinib ko\'ring.' };
    } finally {
        isSubmitting.value = false;
    }
};

const getInputType = (fieldType) => {
    const types = {
        text: 'text',
        email: 'email',
        phone: 'tel',
        number: 'number',
    };
    return types[fieldType] || 'text';
};

const formatPhoneNumber = (event, fieldId) => {
    let value = event.target.value.replace(/\D/g, '');

    if (value.startsWith('998')) {
        value = '+' + value;
    } else if (!value.startsWith('+')) {
        if (value.length > 0) {
            value = '+998' + value;
        }
    }

    // Format: +998 XX XXX XX XX
    if (value.length > 4) {
        value = value.slice(0, 4) + ' ' + value.slice(4);
    }
    if (value.length > 7) {
        value = value.slice(0, 7) + ' ' + value.slice(7);
    }
    if (value.length > 11) {
        value = value.slice(0, 11) + ' ' + value.slice(11);
    }
    if (value.length > 14) {
        value = value.slice(0, 14) + ' ' + value.slice(14);
    }

    formData.value[fieldId] = value.slice(0, 17);
};

// Convert video URLs to embed format
const getVideoEmbedUrl = (url) => {
    if (!url) return '';

    // YouTube
    const youtubeMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
    if (youtubeMatch) {
        return `https://www.youtube.com/embed/${youtubeMatch[1]}?rel=0&modestbranding=1`;
    }

    // Vimeo
    const vimeoMatch = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
    if (vimeoMatch) {
        return `https://player.vimeo.com/video/${vimeoMatch[1]}?dnt=1`;
    }

    // Kinescope
    const kinescopeMatch = url.match(/kinescope\.io\/(?:embed\/)?([a-zA-Z0-9]+)/);
    if (kinescopeMatch) {
        return `https://kinescope.io/embed/${kinescopeMatch[1]}`;
    }

    // If already an embed URL, return as is
    if (url.includes('/embed/') || url.includes('player.vimeo.com')) {
        return url;
    }

    return url;
};

const themeColor = computed(() => props.leadForm.theme_color || '#6366f1');
</script>

<template>
    <div :class="['min-h-screen', isEmbed ? 'bg-transparent' : 'bg-gray-50 dark:bg-gray-900']">
        <Head :title="leadForm.title" />

        <div :class="['w-full', isEmbed ? '' : 'min-h-screen flex items-center justify-center p-4']">
            <div :class="['w-full', isEmbed ? '' : 'max-w-md']">
                <!-- Form Card -->
                <div v-if="!isSubmitted" class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
                    <!-- Header -->
                    <div
                        class="px-8 py-10 text-center text-white"
                        :style="{ backgroundColor: themeColor }"
                    >
                        <h1 class="text-2xl font-bold leading-tight">{{ leadForm.title }}</h1>
                        <p v-if="leadForm.description" class="mt-3 text-white/90 text-base">
                            {{ leadForm.description }}
                        </p>
                        <div v-if="leadForm.has_lead_magnet && leadForm.lead_magnet_title" class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <span>🎁</span>
                            <span>{{ leadForm.lead_magnet_title }}</span>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitForm" class="p-8 space-y-5">
                        <!-- Error Message -->
                        <div v-if="errors.form" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-400 text-sm">
                            {{ errors.form }}
                        </div>

                        <!-- Fields -->
                        <div v-for="field in leadForm.fields" :key="field.id" class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                            </label>

                            <!-- Text/Email/Number Input -->
                            <input
                                v-if="['text', 'email', 'number'].includes(field.type)"
                                v-model="formData[field.id]"
                                :type="getInputType(field.type)"
                                :placeholder="field.placeholder"
                                :class="[
                                    'w-full px-4 py-3.5 bg-white dark:bg-gray-700 border-2 rounded-xl focus:ring-2 focus:border-transparent transition-all text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500',
                                    errors[field.id]
                                        ? 'border-red-300 dark:border-red-500 focus:ring-red-500'
                                        : 'border-gray-200 dark:border-gray-600 focus:ring-opacity-50'
                                ]"
                                :style="{ '--tw-ring-color': themeColor }"
                            />

                            <!-- Phone Input -->
                            <input
                                v-else-if="field.type === 'phone'"
                                :value="formData[field.id]"
                                @input="formatPhoneNumber($event, field.id)"
                                type="tel"
                                :placeholder="field.placeholder || '+998 90 123 45 67'"
                                :class="[
                                    'w-full px-4 py-3.5 bg-white dark:bg-gray-700 border-2 rounded-xl focus:ring-2 focus:border-transparent transition-all text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500',
                                    errors[field.id]
                                        ? 'border-red-300 dark:border-red-500 focus:ring-red-500'
                                        : 'border-gray-200 dark:border-gray-600 focus:ring-opacity-50'
                                ]"
                                :style="{ '--tw-ring-color': themeColor }"
                            />

                            <!-- Textarea -->
                            <textarea
                                v-else-if="field.type === 'textarea'"
                                v-model="formData[field.id]"
                                :placeholder="field.placeholder"
                                rows="3"
                                :class="[
                                    'w-full px-4 py-3.5 bg-white dark:bg-gray-700 border-2 rounded-xl focus:ring-2 focus:border-transparent transition-all resize-none text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500',
                                    errors[field.id]
                                        ? 'border-red-300 dark:border-red-500 focus:ring-red-500'
                                        : 'border-gray-200 dark:border-gray-600 focus:ring-opacity-50'
                                ]"
                                :style="{ '--tw-ring-color': themeColor }"
                            ></textarea>

                            <!-- Select -->
                            <select
                                v-else-if="field.type === 'select'"
                                v-model="formData[field.id]"
                                :class="[
                                    'w-full px-4 py-3.5 bg-white dark:bg-gray-700 border-2 rounded-xl focus:ring-2 focus:border-transparent transition-all text-gray-900 dark:text-gray-100',
                                    errors[field.id]
                                        ? 'border-red-300 dark:border-red-500 focus:ring-red-500'
                                        : 'border-gray-200 dark:border-gray-600 focus:ring-opacity-50'
                                ]"
                                :style="{ '--tw-ring-color': themeColor }"
                            >
                                <option value="">{{ field.placeholder || 'Tanlang...' }}</option>
                                <option v-for="option in field.options" :key="option" :value="option">
                                    {{ option }}
                                </option>
                            </select>

                            <!-- Checkbox -->
                            <label v-else-if="field.type === 'checkbox'" class="flex items-center gap-3 cursor-pointer p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <input
                                    v-model="formData[field.id]"
                                    type="checkbox"
                                    class="w-5 h-5 rounded border-gray-300 dark:border-gray-500"
                                    :style="{ accentColor: themeColor }"
                                />
                                <span class="text-gray-700 dark:text-gray-200">{{ field.placeholder || field.label }}</span>
                            </label>

                            <!-- Error -->
                            <p v-if="errors[field.id]" class="text-red-500 text-xs mt-1">
                                {{ errors[field.id] }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="w-full py-4 mt-2 text-white text-lg font-bold rounded-xl shadow-lg transition-all transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                            :style="{
                                backgroundColor: themeColor,
                                boxShadow: `0 10px 30px -5px ${themeColor}50`
                            }"
                        >
                            <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Yuborilmoqda...
                            </span>
                            <span v-else>{{ leadForm.submit_button_text || 'Yuborish' }}</span>
                        </button>
                    </form>
                </div>

                <!-- Success State -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="p-10 text-center">
                        <!-- Success Icon -->
                        <div
                            class="w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-6 shadow-lg"
                            :style="{ backgroundColor: themeColor + '15' }"
                        >
                            <svg
                                class="w-12 h-12"
                                :style="{ color: themeColor }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-3">Rahmat!</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-8 text-lg">
                            {{ submitResult?.message || 'Ma\'lumotlaringiz muvaffaqiyatli qabul qilindi!' }}
                        </p>

                        <!-- Lead Magnet -->
                        <div v-if="submitResult?.lead_magnet" class="mb-8">
                            <div
                                class="p-6 rounded-2xl border-2 border-dashed"
                                :style="{ backgroundColor: themeColor + '08', borderColor: themeColor + '30' }"
                            >
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-4">
                                    {{ submitResult.lead_magnet.title || 'Sizning sovg\'angiz:' }}
                                </p>

                                <!-- File Download -->
                                <a
                                    v-if="submitResult.lead_magnet.type === 'file'"
                                    :href="submitResult.lead_magnet.download_url"
                                    class="inline-flex items-center gap-2 px-6 py-3 text-white font-semibold rounded-xl transition-all"
                                    :style="{ backgroundColor: themeColor }"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Yuklab olish
                                </a>

                                <!-- Link -->
                                <a
                                    v-else-if="submitResult.lead_magnet.type === 'link'"
                                    :href="submitResult.lead_magnet.link"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-6 py-3 text-white font-semibold rounded-xl transition-all"
                                    :style="{ backgroundColor: themeColor }"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Ochish
                                </a>

                                <!-- Video Embed -->
                                <div
                                    v-else-if="submitResult.lead_magnet.type === 'video'"
                                    class="w-full"
                                >
                                    <div class="relative w-full rounded-xl overflow-hidden shadow-lg" style="padding-bottom: 56.25%;">
                                        <iframe
                                            :src="getVideoEmbedUrl(submitResult.lead_magnet.link)"
                                            class="absolute inset-0 w-full h-full"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen
                                        ></iframe>
                                    </div>
                                    <p class="mt-3 text-xs text-gray-400 dark:text-gray-500 text-center">
                                        Video faqat ko'rish uchun - yuklab olib bo'lmaydi
                                    </p>
                                </div>

                                <!-- Coupon/Text -->
                                <div
                                    v-else-if="submitResult.lead_magnet.type === 'coupon' || submitResult.lead_magnet.type === 'text'"
                                    class="p-4 bg-white dark:bg-gray-800 rounded-xl border-2 border-dashed"
                                    :style="{ borderColor: themeColor }"
                                >
                                    <p
                                        class="text-xl font-bold"
                                        :style="{ color: themeColor }"
                                    >
                                        {{ submitResult.lead_magnet.text }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Redirect Notice -->
                        <p v-if="submitResult?.redirect_url" class="text-sm text-gray-500 dark:text-gray-400">
                            3 soniyadan keyin yo'naltirilasiz...
                        </p>
                    </div>
                </div>

                <!-- Powered by -->
                <div v-if="!isEmbed" class="mt-8 text-center">
                    <a
                        href="https://biznespilot.uz"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                        </svg>
                        Powered by BiznesPilot
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
input:focus, textarea:focus, select:focus {
    --tw-ring-opacity: 0.5;
}
</style>
