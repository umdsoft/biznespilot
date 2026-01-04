<template>
    <BusinessLayout title="Raqobatchini tahrirlash">
        <!-- Header -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 -mt-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-8 mb-8">
            <div class="max-w-4xl mx-auto">
                <Link
                    :href="route('business.competitors.show', competitor.id)"
                    class="inline-flex items-center text-slate-400 hover:text-white transition-colors mb-4"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Orqaga
                </Link>
                <h1 class="text-2xl font-bold text-white">{{ competitor.name }} ni tahrirlash</h1>
                <p class="text-slate-400 mt-1">Raqobatchi ma'lumotlarini yangilang</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="max-w-4xl mx-auto space-y-6 pb-12">
            <!-- Basic Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Asosiy ma'lumotlar</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomi *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Raqobatchi nomi"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tavsif</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Raqobatchi haqida qisqacha..."
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Soha</label>
                            <input
                                v-model="form.industry"
                                type="text"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                :placeholder="currentBusiness?.industry_name || 'Soha'"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Joylashuv</label>
                            <input
                                v-model="form.location"
                                type="text"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                :placeholder="currentBusiness?.region || 'Viloyat'"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Veb-sayt</label>
                            <input
                                v-model="form.website"
                                type="url"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="https://example.com"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Ijtimoiy tarmoqlar</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                    Instagram
                                </span>
                            </label>
                            <input
                                v-model="form.instagram_handle"
                                type="text"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="@username"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                    </svg>
                                    Telegram
                                </span>
                            </label>
                            <input
                                v-model="form.telegram_handle"
                                type="text"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="@channel"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                    Facebook
                                </span>
                            </label>
                            <input
                                v-model="form.facebook_page"
                                type="text"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Sahifa nomi yoki URL"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                                    </svg>
                                    TikTok
                                </span>
                            </label>
                            <input
                                v-model="form.tiktok_handle"
                                type="text"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="@username"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                    YouTube
                                </span>
                            </label>
                            <input
                                v-model="form.youtube_channel"
                                type="text"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Kanal nomi yoki URL"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Threat Level & Status -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Baholash</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Tahdid darajasi *</label>
                            <div class="space-y-2">
                                <label v-for="level in threatLevels" :key="level.value" class="flex items-center p-3 border rounded-lg cursor-pointer transition-all" :class="form.threat_level === level.value ? level.activeClass : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" v-model="form.threat_level" :value="level.value" class="sr-only" />
                                    <span class="w-3 h-3 rounded-full mr-3" :class="level.dotClass"></span>
                                    <span class="font-medium" :class="form.threat_level === level.value ? level.textClass : 'text-gray-700'">{{ level.label }}</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Holati *</label>
                            <div class="space-y-2">
                                <label v-for="status in statuses" :key="status.value" class="flex items-center p-3 border rounded-lg cursor-pointer transition-all" :class="form.status === status.value ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" v-model="form.status" :value="status.value" class="sr-only" />
                                    <span class="w-3 h-3 rounded-full mr-3" :class="status.dotClass"></span>
                                    <span class="font-medium" :class="form.status === status.value ? 'text-indigo-700' : 'text-gray-700'">{{ status.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monitoring Settings -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Kuzatuv sozlamalari</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span class="font-medium text-gray-900">Avtomatik kuzatuv</span>
                            <p class="text-sm text-gray-500">Ijtimoiy tarmoqlarni avtomatik tekshirish</p>
                        </div>
                        <button
                            type="button"
                            @click="form.auto_monitor = !form.auto_monitor"
                            :class="form.auto_monitor ? 'bg-indigo-600' : 'bg-gray-200'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out"
                        >
                            <span :class="form.auto_monitor ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-0.5 ml-0.5"></span>
                        </button>
                    </div>

                    <div v-if="form.auto_monitor" class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tekshiruv oralig'i (soat)</label>
                        <select v-model="form.check_frequency_hours" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option :value="6">Har 6 soatda</option>
                            <option :value="12">Har 12 soatda</option>
                            <option :value="24">Kuniga bir marta</option>
                            <option :value="48">Har 2 kunda</option>
                            <option :value="168">Haftada bir marta</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Eslatmalar</h2>
                </div>
                <div class="p-6">
                    <textarea
                        v-model="form.notes"
                        rows="4"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Qo'shimcha eslatmalar..."
                    ></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4 pt-4">
                <Link
                    :href="route('business.competitors.show', competitor.id)"
                    class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
                >
                    Bekor qilish
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                    {{ form.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                </button>
            </div>
        </form>
    </BusinessLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const props = defineProps({
    competitor: {
        type: Object,
        required: true,
    },
    currentBusiness: Object,
});

const threatLevels = [
    { value: 'low', label: 'Past', dotClass: 'bg-emerald-500', activeClass: 'border-emerald-500 bg-emerald-50', textClass: 'text-emerald-700' },
    { value: 'medium', label: 'O\'rta', dotClass: 'bg-yellow-500', activeClass: 'border-yellow-500 bg-yellow-50', textClass: 'text-yellow-700' },
    { value: 'high', label: 'Yuqori', dotClass: 'bg-orange-500', activeClass: 'border-orange-500 bg-orange-50', textClass: 'text-orange-700' },
    { value: 'critical', label: 'Kritik', dotClass: 'bg-red-500', activeClass: 'border-red-500 bg-red-50', textClass: 'text-red-700' },
];

const statuses = [
    { value: 'active', label: 'Faol', dotClass: 'bg-emerald-500' },
    { value: 'inactive', label: 'Nofaol', dotClass: 'bg-gray-400' },
    { value: 'archived', label: 'Arxivlangan', dotClass: 'bg-blue-500' },
];

const form = useForm({
    name: props.competitor.name || '',
    description: props.competitor.description || '',
    website: props.competitor.website || '',
    industry: props.competitor.industry || '',
    location: props.competitor.location || '',
    instagram_handle: props.competitor.instagram_handle || '',
    telegram_handle: props.competitor.telegram_handle || '',
    facebook_page: props.competitor.facebook_page || '',
    tiktok_handle: props.competitor.tiktok_handle || '',
    youtube_channel: props.competitor.youtube_channel || '',
    threat_level: props.competitor.threat_level || 'medium',
    status: props.competitor.status || 'active',
    auto_monitor: props.competitor.auto_monitor || false,
    check_frequency_hours: props.competitor.check_frequency_hours || 24,
    notes: props.competitor.notes || '',
    strengths: props.competitor.strengths || [],
    weaknesses: props.competitor.weaknesses || [],
});

const submit = () => {
    form.put(route('business.competitors.update', props.competitor.id), {
        preserveScroll: true,
    });
};
</script>
