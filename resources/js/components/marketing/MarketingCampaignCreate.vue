<template>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <Link :href="getHref('/campaigns')" class="text-purple-600 hover:text-purple-700 mb-4 inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Orqaga
                </Link>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mt-2">Yangi Marketing Kampaniya</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Mijozlaringizga xabar yuborish uchun yangi kampaniya yarating
                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Campaign Name -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Kampaniya Nomi *
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        placeholder="Masalan: Yangi Mahsulot E'loni"
                    />
                    <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                </div>

                <!-- Campaign Type -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                        Kampaniya Turi *
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                            @click="form.type = 'broadcast'"
                            class="cursor-pointer border-2 rounded-lg p-4 transition-all"
                            :class="form.type === 'broadcast' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-purple-300'"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Ommaviy</h4>
                                <div v-if="form.type === 'broadcast'" class="w-5 h-5 bg-purple-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Bir vaqtning o'zida barchaga yuborish</p>
                        </div>

                        <div
                            @click="form.type = 'drip'"
                            class="cursor-pointer border-2 rounded-lg p-4 transition-all"
                            :class="form.type === 'drip' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-purple-300'"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Drip</h4>
                                <div v-if="form.type === 'drip'" class="w-5 h-5 bg-purple-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Ketma-ket xabarlar seriyasi</p>
                        </div>

                        <div
                            @click="form.type = 'trigger'"
                            class="cursor-pointer border-2 rounded-lg p-4 transition-all"
                            :class="form.type === 'trigger' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-purple-300'"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Trigger</h4>
                                <div v-if="form.type === 'trigger'" class="w-5 h-5 bg-purple-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Harakatga asoslangan avtomatik</p>
                        </div>
                    </div>
                </div>

                <!-- Channel Selection -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                        Kanal *
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div
                            v-for="channel in channels"
                            :key="channel.value"
                            @click="form.channel = channel.value"
                            class="cursor-pointer border-2 rounded-lg p-3 text-center transition-all"
                            :class="form.channel === channel.value ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-purple-300'"
                        >
                            <div class="text-2xl mb-1">{{ channel.icon }}</div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ channel.label }}</div>
                        </div>
                    </div>
                </div>

                <!-- Message Template -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Xabar Matni *
                        </label>
                        <button
                            type="button"
                            @click="generateAI"
                            :disabled="aiLoading"
                            class="text-sm px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:from-purple-600 hover:to-pink-600 disabled:opacity-50"
                        >
                            <span v-if="!aiLoading">AI bilan yaratish</span>
                            <span v-else>Yaratilmoqda...</span>
                        </button>
                    </div>

                    <div v-if="showAIPrompt" class="mb-4">
                        <input
                            v-model="aiGoal"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            placeholder="Masalan: Yangi chegirma taklifini e'lon qilish"
                            @keyup.enter="generateAIMessage"
                        />
                        <button
                            @click="generateAIMessage"
                            :disabled="!aiGoal || aiLoading"
                            class="mt-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50"
                        >
                            Yaratish
                        </button>
                    </div>

                    <textarea
                        v-model="form.message_template"
                        rows="6"
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        placeholder="Xabar matnini kiriting...&#10;&#10;Quyidagi placeholderlardan foydalanishingiz mumkin:&#10;{customer_name} - Mijoz ismi&#10;{business_name} - Biznes nomi&#10;{offer_name} - Taklif nomi"
                    ></textarea>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <strong>Placeholderlar:</strong> {customer_name}, {business_name}, {offer_name}, {offer_price}
                    </p>
                </div>

                <!-- Target Audience -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                        Maqsadli Auditoriya
                    </label>
                    <select
                        v-model="form.target_audience"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="all">Barcha mijozlar</option>
                        <option value="active">Faol mijozlar (so'nggi 30 kun)</option>
                        <option value="recent">Yangi mijozlar (so'nggi 7 kun)</option>
                        <option value="unconverted">Xarid qilmaganlar</option>
                    </select>
                </div>

                <!-- Schedule -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                        Yuborish vaqti
                    </label>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input
                                v-model="form.schedule_type"
                                type="radio"
                                value="immediate"
                                class="w-4 h-4 text-purple-600 focus:ring-purple-500"
                            />
                            <span class="ml-3 text-sm text-gray-900 dark:text-white">Darhol yuborish</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                v-model="form.schedule_type"
                                type="radio"
                                value="scheduled"
                                class="w-4 h-4 text-purple-600 focus:ring-purple-500"
                            />
                            <span class="ml-3 text-sm text-gray-900 dark:text-white">Rejalashtirilgan vaqtda</span>
                        </label>
                    </div>

                    <div v-if="form.schedule_type === 'scheduled'" class="mt-4">
                        <input
                            v-model="form.scheduled_at"
                            type="datetime-local"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        />
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end space-x-3">
                    <Link
                        :href="getHref('/campaigns')"
                        class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        Bekor qilish
                    </Link>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50"
                    >
                        {{ processing ? 'Saqlanmoqda...' : 'Kampaniya Yaratish' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    panelType: {
        type: String,
        required: true,
        validator: (value) => ['business', 'marketing'].includes(value),
    },
    currentBusiness: Object,
    errors: Object
})

// Helper to generate correct href based on panel type
const getHref = (path) => {
    const prefix = props.panelType === 'business' ? '/business/marketing' : '/marketing';
    return prefix + path;
};

// Helper to get API endpoint based on panel type
const getApiPath = (path) => {
    const prefix = props.panelType === 'business' ? '/business/marketing' : '/marketing';
    return prefix + path;
};

const form = useForm({
    name: '',
    type: 'broadcast',
    channel: 'telegram',
    message_template: '',
    target_audience: 'all',
    schedule_type: 'immediate',
    scheduled_at: null,
    settings: {}
})

const channels = [
    { value: 'instagram', label: 'Instagram', icon: '' },
    { value: 'telegram', label: 'Telegram', icon: '' },
    { value: 'facebook', label: 'Facebook', icon: '' },
    { value: 'email', label: 'Email', icon: '' },
    { value: 'all', label: 'Barchasi', icon: '' }
]

const processing = ref(false)
const aiLoading = ref(false)
const showAIPrompt = ref(false)
const aiGoal = ref('')

const generateAI = () => {
    showAIPrompt.value = !showAIPrompt.value
}

const generateAIMessage = async () => {
    if (!aiGoal.value) return

    aiLoading.value = true
    try {
        const response = await axios.post(getApiPath('/campaigns/generate-ai'), {
            campaign_goal: aiGoal.value
        })

        if (response.data.success) {
            form.message_template = response.data.message
            showAIPrompt.value = false
            aiGoal.value = ''
        }
    } catch (error) {
        alert('AI xabar yaratishda xatolik yuz berdi')
    } finally {
        aiLoading.value = false
    }
}

const submit = () => {
    processing.value = true
    form.post(getApiPath('/campaigns'), {
        onFinish: () => {
            processing.value = false
        }
    })
}
</script>
