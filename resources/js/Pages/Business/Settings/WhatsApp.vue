<script setup>
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClipboardDocumentIcon,
    PaperAirplaneIcon,
    Cog6ToothIcon,
} from '@heroicons/vue/24/outline';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const page = usePage();
const currentBusiness = page.props.currentBusiness || page.props.auth?.currentBusiness;

const webhookInfo = ref(null);
const loadingInfo = ref(true);
const testPhone = ref('');
const testMessage = ref('Salom! Bu WhatsApp test xabaridir.');
const sendingTest = ref(false);
const testResult = ref(null);

// Template sending
const templatePhone = ref('');
const templateName = ref('hello_world');
const sendingTemplate = ref(false);
const templateResult = ref(null);

// Button message
const buttonPhone = ref('');
const buttonBody = ref('Bizning xizmatlarimiz haqida ko\'proq ma\'lumot olishni xohlaysizmi?');
const buttons = ref([
    { id: 'btn_yes', title: 'Ha, xohlayman' },
    { id: 'btn_no', title: 'Yo\'q, rahmat' },
]);
const sendingButtons = ref(false);
const buttonResult = ref(null);

// Load webhook info
const loadWebhookInfo = async () => {
    loadingInfo.value = true;
    try {
        const response = await fetch(`/webhooks/whatsapp/${currentBusiness.id}/info`);
        const data = await response.json();
        if (data.success) {
            webhookInfo.value = data;
        }
    } catch (error) {
        console.error('Error loading webhook info:', error);
    } finally {
        loadingInfo.value = false;
    }
};

// Copy to clipboard
const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert('Nusxalandi!');
};

// Send test message
const sendTestMessage = async () => {
    if (!testPhone.value || !testMessage.value) {
        alert('Telefon raqam va xabarni kiriting');
        return;
    }

    sendingTest.value = true;
    testResult.value = null;

    try {
        const response = await fetch(`/webhooks/whatsapp/${currentBusiness.id}/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                phone: testPhone.value,
                message: testMessage.value,
            }),
        });

        const data = await response.json();
        testResult.value = data;

        if (data.success) {
            alert('Test xabar yuborildi!');
        } else {
            alert('Xatolik: ' + data.message);
        }
    } catch (error) {
        testResult.value = { success: false, message: error.message };
        alert('Xatolik: ' + error.message);
    } finally {
        sendingTest.value = false;
    }
};

// Send template message
const sendTemplateMessage = async () => {
    if (!templatePhone.value || !templateName.value) {
        alert('Telefon raqam va template nomini kiriting');
        return;
    }

    sendingTemplate.value = true;
    templateResult.value = null;

    try {
        const response = await fetch(`/webhooks/whatsapp/${currentBusiness.id}/template`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                phone: templatePhone.value,
                template_name: templateName.value,
                language_code: 'en',
            }),
        });

        const data = await response.json();
        templateResult.value = data;

        if (data.success) {
            alert('Template xabar yuborildi!');
        } else {
            alert('Xatolik: ' + data.message);
        }
    } catch (error) {
        templateResult.value = { success: false, message: error.message };
        alert('Xatolik: ' + error.message);
    } finally {
        sendingTemplate.value = false;
    }
};

// Send button message
const sendButtonMessage = async () => {
    if (!buttonPhone.value || !buttonBody.value) {
        alert('Telefon raqam va xabarni kiriting');
        return;
    }

    sendingButtons.value = true;
    buttonResult.value = null;

    try {
        const response = await fetch(`/webhooks/whatsapp/${currentBusiness.id}/buttons`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                phone: buttonPhone.value,
                body_text: buttonBody.value,
                buttons: buttons.value,
                header_text: 'BiznеsPilot',
            }),
        });

        const data = await response.json();
        buttonResult.value = data;

        if (data.success) {
            alert('Button xabar yuborildi!');
        } else {
            alert('Xatolik: ' + data.message);
        }
    } catch (error) {
        buttonResult.value = { success: false, message: error.message };
        alert('Xatolik: ' + error.message);
    } finally {
        sendingButtons.value = false;
    }
};

onMounted(() => {
    loadWebhookInfo();
});
</script>

<template>
    <BusinessLayout title="WhatsApp Settings">
        <Head title="WhatsApp Integration" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <ChatBubbleLeftRightIcon class="w-10 h-10 text-green-600" />
                        WhatsApp Business Integration
                    </h1>
                    <p class="mt-2 text-gray-600">
                        WhatsApp Business API orqali mijozlar bilan muloqot qiling
                    </p>
                </div>

                <!-- Webhook Configuration -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <Cog6ToothIcon class="w-6 h-6 text-gray-700" />
                        <h2 class="text-xl font-bold text-gray-900">Webhook Configuration</h2>
                    </div>

                    <div v-if="loadingInfo" class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
                        <p class="mt-2 text-gray-500">Ma'lumotlar yuklanmoqda...</p>
                    </div>

                    <div v-else-if="webhookInfo" class="space-y-4">
                        <!-- Webhook URL -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Webhook URL
                            </label>
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    :value="webhookInfo.webhook_url"
                                    readonly
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700 font-mono text-sm"
                                />
                                <button
                                    @click="copyToClipboard(webhookInfo.webhook_url)"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors flex items-center gap-2"
                                >
                                    <ClipboardDocumentIcon class="w-5 h-5" />
                                    Nusxalash
                                </button>
                            </div>
                        </div>

                        <!-- Verify Token -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Verify Token
                            </label>
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    :value="webhookInfo.verify_token"
                                    readonly
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700 font-mono text-sm"
                                />
                                <button
                                    @click="copyToClipboard(webhookInfo.verify_token)"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors flex items-center gap-2"
                                >
                                    <ClipboardDocumentIcon class="w-5 h-5" />
                                    Nusxalash
                                </button>
                            </div>
                        </div>

                        <!-- Setup Instructions -->
                        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h3 class="font-semibold text-blue-900 mb-3">Setup Instructions:</h3>
                            <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
                                <li v-for="(instruction, index) in webhookInfo.setup_instructions" :key="index">
                                    {{ instruction }}
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Test Message Section -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Test Message</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Telefon raqam (998901234567 formatida)
                            </label>
                            <input
                                v-model="testPhone"
                                type="text"
                                placeholder="998901234567"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Xabar matni
                            </label>
                            <textarea
                                v-model="testMessage"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            ></textarea>
                        </div>

                        <button
                            @click="sendTestMessage"
                            :disabled="sendingTest"
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-semibold rounded-lg transition-colors gap-2"
                        >
                            <PaperAirplaneIcon class="w-5 h-5" />
                            {{ sendingTest ? 'Yuborilmoqda...' : 'Test Xabar Yuborish' }}
                        </button>

                        <div v-if="testResult" :class="[
                            'p-4 rounded-lg flex items-start gap-3',
                            testResult.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
                        ]">
                            <component :is="testResult.success ? CheckCircleIcon : XCircleIcon"
                                :class="['w-6 h-6', testResult.success ? 'text-green-600' : 'text-red-600']" />
                            <div>
                                <p :class="['font-semibold', testResult.success ? 'text-green-900' : 'text-red-900']">
                                    {{ testResult.message }}
                                </p>
                                <pre v-if="testResult.data" class="mt-2 text-xs text-gray-600 overflow-auto">{{ JSON.stringify(testResult.data, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template Message Section -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Template Message</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Telefon raqam
                            </label>
                            <input
                                v-model="templatePhone"
                                type="text"
                                placeholder="998901234567"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Template nomi
                            </label>
                            <input
                                v-model="templateName"
                                type="text"
                                placeholder="hello_world"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            />
                            <p class="mt-1 text-sm text-gray-500">Template avval Meta Business Manager da tasdiqlanishi kerak</p>
                        </div>

                        <button
                            @click="sendTemplateMessage"
                            :disabled="sendingTemplate"
                            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-semibold rounded-lg transition-colors gap-2"
                        >
                            <PaperAirplaneIcon class="w-5 h-5" />
                            {{ sendingTemplate ? 'Yuborilmoqda...' : 'Template Yuborish' }}
                        </button>

                        <div v-if="templateResult" :class="[
                            'p-4 rounded-lg flex items-start gap-3',
                            templateResult.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
                        ]">
                            <component :is="templateResult.success ? CheckCircleIcon : XCircleIcon"
                                :class="['w-6 h-6', templateResult.success ? 'text-green-600' : 'text-red-600']" />
                            <div>
                                <p :class="['font-semibold', templateResult.success ? 'text-green-900' : 'text-red-900']">
                                    {{ templateResult.message }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interactive Buttons Section -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Interactive Button Message</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Telefon raqam
                            </label>
                            <input
                                v-model="buttonPhone"
                                type="text"
                                placeholder="998901234567"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Xabar matni
                            </label>
                            <textarea
                                v-model="buttonBody"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            ></textarea>
                        </div>

                        <button
                            @click="sendButtonMessage"
                            :disabled="sendingButtons"
                            class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white font-semibold rounded-lg transition-colors gap-2"
                        >
                            <PaperAirplaneIcon class="w-5 h-5" />
                            {{ sendingButtons ? 'Yuborilmoqda...' : 'Button Xabar Yuborish' }}
                        </button>

                        <div v-if="buttonResult" :class="[
                            'p-4 rounded-lg flex items-start gap-3',
                            buttonResult.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
                        ]">
                            <component :is="buttonResult.success ? CheckCircleIcon : XCircleIcon"
                                :class="['w-6 h-6', buttonResult.success ? 'text-green-600' : 'text-red-600']" />
                            <div>
                                <p :class="['font-semibold', buttonResult.success ? 'text-green-900' : 'text-red-900']">
                                    {{ buttonResult.message }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
