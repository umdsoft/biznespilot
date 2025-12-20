<script setup>
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import {
    SparklesIcon,
    ChatBubbleLeftRightIcon,
    Cog6ToothIcon,
    CheckCircleIcon,
    XCircleIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const currentBusiness = page.props.currentBusiness || page.props.auth?.currentBusiness;

// Chatbot config
const config = ref({
    is_active: true,
    ai_enabled: true,
    auto_greet: true,
    greeting_message: 'Assalomu alaykum! Bizga xush kelibsiz. Sizga qanday yordam bera olamiz?',
    fallback_message: 'Kechirasiz, savol tushunilmadi. Iltimos, boshqacha so\'rang.',
    outside_hours_message: 'Ish vaqti tugadi. Ish kunlari 9:00-18:00 da ishlmaymiz.',
    business_hours_enabled: false,
    business_hours_start: '09:00',
    business_hours_end: '18:00',
    lead_auto_create: true,
    ai_creativity_level: 7,
    use_dream_buyer_context: true,
    use_offer_context: true,
});

const loading = ref(false);
const saveSuccess = ref(false);

// Quick reply templates
const templates = ref([
    { id: 1, trigger: 'salom', response: 'Assalomu alaykum! Sizga qanday yordam bera olamiz?' },
    { id: 2, trigger: 'narx', response: 'Takliflarimiz haqida ma\'lumot olish uchun telefon raqamingizni qoldiring.' },
    { id: 3, trigger: 'xarid', response: 'Xarid uchun operator bilan bog\'lanamiz. Bir daqiqa kuting.' },
]);

const newTemplate = ref({ trigger: '', response: '' });

// Load config
const loadConfig = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/api/whatsapp/${currentBusiness.id}/ai-config`);
        const data = await response.json();
        if (data.success) {
            config.value = { ...config.value, ...data.config };
            if (data.templates) {
                templates.value = data.templates;
            }
        }
    } catch (error) {
        console.error('Error loading config:', error);
    } finally {
        loading.value = false;
    }
};

// Save config
const saveConfig = async () => {
    loading.value = true;
    saveSuccess.value = false;
    try {
        const response = await fetch(`/api/whatsapp/${currentBusiness.id}/ai-config`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(config.value),
        });

        const data = await response.json();
        if (data.success) {
            saveSuccess.value = true;
            setTimeout(() => saveSuccess.value = false, 3000);
        } else {
            alert('Xatolik: ' + data.message);
        }
    } catch (error) {
        alert('Xatolik: ' + error.message);
    } finally {
        loading.value = false;
    }
};

// Add template
const addTemplate = () => {
    if (newTemplate.value.trigger && newTemplate.value.response) {
        templates.value.push({
            id: Date.now(),
            ...newTemplate.value,
        });
        newTemplate.value = { trigger: '', response: '' };
    }
};

// Remove template
const removeTemplate = (id) => {
    templates.value = templates.value.filter(t => t.id !== id);
};

// Save templates
const saveTemplates = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/api/whatsapp/${currentBusiness.id}/ai-templates`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ templates: templates.value }),
        });

        const data = await response.json();
        if (data.success) {
            alert('Templatelar saqlandi!');
        } else {
            alert('Xatolik: ' + data.message);
        }
    } catch (error) {
        alert('Xatolik: ' + error.message);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadConfig();
});
</script>

<template>
    <BusinessLayout title="WhatsApp AI Settings">
        <Head title="WhatsApp AI Configuration" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <SparklesIcon class="w-10 h-10 text-purple-600" />
                        WhatsApp AI Chatbot Configuration
                    </h1>
                    <p class="mt-2 text-gray-600">
                        AI-powered avtomatik javoblar va mijozlar bilan muloqot sozlamalari
                    </p>
                </div>

                <!-- Main Settings -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <Cog6ToothIcon class="w-6 h-6 text-gray-700" />
                        <h2 class="text-xl font-bold text-gray-900">Asosiy Sozlamalar</h2>
                    </div>

                    <div class="space-y-6">
                        <!-- Enable AI -->
                        <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                            <div>
                                <h3 class="font-semibold text-gray-900">AI Chatbot Yoqish</h3>
                                <p class="text-sm text-gray-600">Xabarlarga avtomatik AI javoblar berish</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="config.ai_enabled"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        <!-- Auto Greet -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h3 class="font-semibold text-gray-900">Avtomatik Salomlashish</h3>
                                <p class="text-sm text-gray-600">Yangi mijozlarga avtomatik salom berish</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="config.auto_greet"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        <!-- Greeting Message -->
                        <div v-if="config.auto_greet">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Salomlashish Xabari
                            </label>
                            <textarea
                                v-model="config.greeting_message"
                                rows="2"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            ></textarea>
                        </div>

                        <!-- AI Creativity Level -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                AI Ijodkorlik Darajasi: {{ config.ai_creativity_level }}/10
                            </label>
                            <input
                                type="range"
                                v-model="config.ai_creativity_level"
                                min="1"
                                max="10"
                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-purple-600"
                            />
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>Aniq javoblar</span>
                                <span>Ijodiy javoblar</span>
                            </div>
                        </div>

                        <!-- Context Settings -->
                        <div class="border-t pt-6">
                            <h3 class="font-semibold text-gray-900 mb-4">Kontekst Sozlamalari</h3>

                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="config.use_dream_buyer_context"
                                        class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500"
                                    />
                                    <label class="ml-2 text-sm text-gray-700">
                                        Dream Buyer ma'lumotlaridan foydalanish
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="config.use_offer_context"
                                        class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500"
                                    />
                                    <label class="ml-2 text-sm text-gray-700">
                                        Aktiv Takliflar haqida ma'lumot berish
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="config.lead_auto_create"
                                        class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500"
                                    />
                                    <label class="ml-2 text-sm text-gray-700">
                                        Avtomatik Lead yaratish (xarid bosqichida)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Business Hours -->
                        <div class="border-t pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold text-gray-900">Ish Vaqti</h3>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="config.business_hours_enabled"
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                </label>
                            </div>

                            <div v-if="config.business_hours_enabled" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Boshlanish
                                    </label>
                                    <input
                                        type="time"
                                        v-model="config.business_hours_start"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Tugash
                                    </label>
                                    <input
                                        type="time"
                                        v-model="config.business_hours_end"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    />
                                </div>
                            </div>

                            <div v-if="config.business_hours_enabled" class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Ish vaqtidan tashqari xabar
                                </label>
                                <textarea
                                    v-model="config.outside_hours_message"
                                    rows="2"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Fallback Message -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Fallback Xabar (AI javob bera olmasa)
                            </label>
                            <textarea
                                v-model="config.fallback_message"
                                rows="2"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="mt-6 flex items-center gap-4">
                        <button
                            @click="saveConfig"
                            :disabled="loading"
                            class="px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white font-semibold rounded-lg transition-colors"
                        >
                            {{ loading ? 'Saqlanmoqda...' : 'Sozlamalarni Saqlash' }}
                        </button>

                        <div v-if="saveSuccess" class="flex items-center gap-2 text-green-600">
                            <CheckCircleIcon class="w-5 h-5" />
                            <span class="font-medium">Saqlandi!</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Reply Templates -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <ChatBubbleLeftRightIcon class="w-6 h-6 text-gray-700" />
                        <h2 class="text-xl font-bold text-gray-900">Tez Javob Templatelar</h2>
                    </div>

                    <!-- Template List -->
                    <div class="space-y-3 mb-6">
                        <div
                            v-for="template in templates"
                            :key="template.id"
                            class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg"
                        >
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-700">
                                    Trigger: <span class="text-purple-600">{{ template.trigger }}</span>
                                </div>
                                <div class="text-sm text-gray-600 mt-1">{{ template.response }}</div>
                            </div>
                            <button
                                @click="removeTemplate(template.id)"
                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            >
                                <TrashIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Add Template -->
                    <div class="border-t pt-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Yangi Template Qo'shish</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Trigger Kalit So'z
                                </label>
                                <input
                                    v-model="newTemplate.trigger"
                                    type="text"
                                    placeholder="masalan: narx, xarid"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Javob Matni
                                </label>
                                <input
                                    v-model="newTemplate.response"
                                    type="text"
                                    placeholder="Javob xabari"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <button
                                @click="addTemplate"
                                class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors gap-2"
                            >
                                <PlusIcon class="w-5 h-5" />
                                Template Qo'shish
                            </button>
                            <button
                                @click="saveTemplates"
                                :disabled="loading"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white font-semibold rounded-lg transition-colors"
                            >
                                Barchasini Saqlash
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
