<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import {
    ArrowLeftIcon,
    PaperAirplaneIcon,
    GiftIcon,
    UserGroupIcon,
    CheckIcon,
    ChatBubbleLeftRightIcon,
    DevicePhoneMobileIcon,
    EnvelopeIcon,
    CalendarIcon,
    ClockIcon,
    TagIcon,
    CurrencyDollarIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    panelType: String,
    offers: Array,
    leads: Array,
    channels: Object,
});

const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        saleshead: SalesHeadLayout,
        operator: OperatorLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});

const getRoutePrefix = () => {
    const prefixMap = {
        saleshead: 'sales-head',
        business: 'business',
        operator: 'operator',
    };
    return prefixMap[props.panelType] || props.panelType;
};

const form = useForm({
    offer_id: '',
    lead_ids: [],
    channel: 'telegram',
    custom_price: null,
    discount: null,
    discount_code: '',
    scheduled_at: '',
    expires_at: '',
    notes: '',
    send_immediately: true,
});

const selectedOffer = computed(() => {
    return props.offers.find(o => o.id === form.offer_id);
});

const searchQuery = ref('');
const statusFilter = ref('');
const showTelegramOnly = ref(false);

const filteredLeads = computed(() => {
    let result = props.leads;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(l =>
            l.name?.toLowerCase().includes(query) ||
            l.phone?.includes(query) ||
            l.email?.toLowerCase().includes(query)
        );
    }

    if (statusFilter.value) {
        result = result.filter(l => l.status === statusFilter.value);
    }

    if (showTelegramOnly.value) {
        result = result.filter(l => l.has_telegram);
    }

    return result;
});

const isAllSelected = computed(() => {
    return filteredLeads.value.length > 0 &&
        filteredLeads.value.every(l => form.lead_ids.includes(l.id));
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        form.lead_ids = form.lead_ids.filter(id =>
            !filteredLeads.value.find(l => l.id === id)
        );
    } else {
        const newIds = filteredLeads.value.map(l => l.id);
        form.lead_ids = [...new Set([...form.lead_ids, ...newIds])];
    }
};

const toggleLead = (leadId) => {
    const index = form.lead_ids.indexOf(leadId);
    if (index > -1) {
        form.lead_ids.splice(index, 1);
    } else {
        form.lead_ids.push(leadId);
    }
};

const getChannelIcon = (channel) => {
    const icons = {
        telegram: ChatBubbleLeftRightIcon,
        sms: DevicePhoneMobileIcon,
        email: EnvelopeIcon,
        whatsapp: ChatBubbleLeftRightIcon,
        manual: UserGroupIcon,
    };
    return icons[channel] || ChatBubbleLeftRightIcon;
};

const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('uz-UZ').format(price);
};

const finalPrice = computed(() => {
    const base = form.custom_price || selectedOffer.value?.pricing || 0;
    const discount = form.discount || 0;
    return Math.max(0, base - discount);
});

const submit = () => {
    form.post(route(`${getRoutePrefix()}.offer-automation.store`));
};

// Auto-filter for telegram when channel is telegram
watch(() => form.channel, (newChannel) => {
    if (newChannel === 'telegram') {
        showTelegramOnly.value = true;
    }
});

const statusLabels = {
    new: 'Yangi',
    contacted: 'Bog\'lanildi',
    qualified: 'Malakali',
    proposal: 'Taklif',
    negotiation: 'Muzokara',
};
</script>

<template>
    <component :is="layoutComponent" title="Taklif Yuborish">
        <Head title="Taklif Yuborish" />

        <div class="py-6 min-h-screen bg-gradient-to-br from-gray-50 via-white to-purple-50/30 dark:from-gray-900 dark:via-gray-800 dark:to-purple-900/20">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route(`${getRoutePrefix()}.offer-automation.index`)"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-purple-600 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-4"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        Orqaga
                    </Link>

                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                            <PaperAirplaneIcon class="w-6 h-6 text-white" />
                        </div>
                        Taklif Yuborish
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">
                        Tanlangan lidlarga taklif yuborish
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Step 1: Select Offer -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 border-b border-purple-100 dark:border-purple-800">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <GiftIcon class="w-5 h-5 text-purple-600" />
                                1. Taklifni Tanlang
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="offer in offers"
                                    :key="offer.id"
                                    @click="form.offer_id = offer.id"
                                    class="p-4 border-2 rounded-xl cursor-pointer transition-all"
                                    :class="form.offer_id === offer.id
                                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                        : 'border-gray-200 dark:border-gray-600 hover:border-purple-300'"
                                >
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-bold text-gray-900 dark:text-white">{{ offer.name }}</h3>
                                        <CheckCircleIcon
                                            v-if="form.offer_id === offer.id"
                                            class="w-6 h-6 text-purple-600"
                                        />
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-purple-600 font-semibold">{{ formatPrice(offer.pricing) }} so'm</span>
                                        <span class="text-green-600">{{ offer.conversion_rate }}% CR</span>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        Qiymat bali: {{ offer.value_score }}
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.offer_id" class="mt-2 text-sm text-red-600">{{ form.errors.offer_id }}</p>
                        </div>
                    </div>

                    <!-- Step 2: Select Channel -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/30 dark:to-cyan-900/30 border-b border-blue-100 dark:border-blue-800">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <ChatBubbleLeftRightIcon class="w-5 h-5 text-blue-600" />
                                2. Kanal Tanlang
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                <button
                                    v-for="(label, key) in channels"
                                    :key="key"
                                    type="button"
                                    @click="form.channel = key"
                                    class="p-4 border-2 rounded-xl flex flex-col items-center gap-2 transition-all"
                                    :class="form.channel === key
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-600 hover:border-blue-300'"
                                >
                                    <component :is="getChannelIcon(key)" class="w-6 h-6" :class="form.channel === key ? 'text-blue-600' : 'text-gray-400'" />
                                    <span class="text-sm font-medium" :class="form.channel === key ? 'text-blue-600' : 'text-gray-600 dark:text-gray-400'">
                                        {{ label }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Select Leads -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border-b border-green-100 dark:border-green-800">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <UserGroupIcon class="w-5 h-5 text-green-600" />
                                    3. Lidlarni Tanlang
                                    <span class="ml-2 px-2.5 py-0.5 bg-green-100 text-green-700 rounded-full text-sm">
                                        {{ form.lead_ids.length }} ta tanlandi
                                    </span>
                                </h2>
                            </div>
                        </div>
                        <div class="p-6">
                            <!-- Filters -->
                            <div class="flex flex-wrap items-center gap-4 mb-4">
                                <div class="relative flex-1 min-w-48">
                                    <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Ism, telefon yoki email..."
                                        class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    />
                                </div>

                                <select
                                    v-model="statusFilter"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                >
                                    <option value="">Barcha holatlar</option>
                                    <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                                </select>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="showTelegramOnly"
                                        class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                    />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Faqat Telegram</span>
                                </label>

                                <button
                                    type="button"
                                    @click="toggleSelectAll"
                                    class="px-4 py-2 text-sm font-medium text-purple-600 hover:text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors"
                                >
                                    {{ isAllSelected ? 'Barchasini olib tashlash' : 'Barchasini tanlash' }}
                                </button>
                            </div>

                            <!-- Leads List -->
                            <div class="max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-xl">
                                <div
                                    v-for="lead in filteredLeads"
                                    :key="lead.id"
                                    @click="toggleLead(lead.id)"
                                    class="flex items-center gap-4 p-4 border-b border-gray-100 dark:border-gray-700 last:border-b-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    :class="{ 'bg-purple-50 dark:bg-purple-900/20': form.lead_ids.includes(lead.id) }"
                                >
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-6 h-6 rounded border-2 flex items-center justify-center transition-colors"
                                            :class="form.lead_ids.includes(lead.id)
                                                ? 'bg-purple-600 border-purple-600'
                                                : 'border-gray-300 dark:border-gray-600'"
                                        >
                                            <CheckIcon v-if="form.lead_ids.includes(lead.id)" class="w-4 h-4 text-white" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ lead.name }}</p>
                                        <p class="text-sm text-gray-500 truncate">{{ lead.phone }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="px-2 py-1 text-xs font-medium rounded-full"
                                            :class="{
                                                'bg-blue-100 text-blue-700': lead.status === 'new',
                                                'bg-yellow-100 text-yellow-700': lead.status === 'contacted',
                                                'bg-green-100 text-green-700': lead.status === 'qualified',
                                                'bg-purple-100 text-purple-700': lead.status === 'proposal',
                                                'bg-orange-100 text-orange-700': lead.status === 'negotiation',
                                            }"
                                        >
                                            {{ statusLabels[lead.status] }}
                                        </span>
                                        <ChatBubbleLeftRightIcon
                                            v-if="lead.has_telegram"
                                            class="w-5 h-5 text-blue-500"
                                            title="Telegram mavjud"
                                        />
                                    </div>
                                </div>
                                <div v-if="filteredLeads.length === 0" class="p-8 text-center text-gray-500">
                                    Lidlar topilmadi
                                </div>
                            </div>
                            <p v-if="form.errors.lead_ids" class="mt-2 text-sm text-red-600">{{ form.errors.lead_ids }}</p>
                        </div>
                    </div>

                    <!-- Step 4: Options -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/30 dark:to-orange-900/30 border-b border-yellow-100 dark:border-yellow-800">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <TagIcon class="w-5 h-5 text-yellow-600" />
                                4. Qo'shimcha Sozlamalar
                            </h2>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Custom Price -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Maxsus Narx (ixtiyoriy)
                                </label>
                                <div class="relative">
                                    <CurrencyDollarIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                    <input
                                        v-model.number="form.custom_price"
                                        type="number"
                                        :placeholder="selectedOffer ? formatPrice(selectedOffer.pricing) : '0'"
                                        class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <!-- Discount -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Chegirma Summasi
                                </label>
                                <input
                                    v-model.number="form.discount"
                                    type="number"
                                    placeholder="0"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <!-- Discount Code -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Chegirma Kodi
                                </label>
                                <input
                                    v-model="form.discount_code"
                                    type="text"
                                    placeholder="MAXSUS20"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <!-- Final Price Preview -->
                            <div class="p-4 bg-green-50 dark:bg-green-900/30 rounded-xl">
                                <p class="text-sm text-green-700 dark:text-green-400 mb-1">Yakuniy Narx</p>
                                <p class="text-2xl font-bold text-green-800 dark:text-green-300">{{ formatPrice(finalPrice) }} so'm</p>
                            </div>

                            <!-- Schedule -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <CalendarIcon class="w-4 h-4 inline mr-1" />
                                    Rejalashtirilgan Vaqt
                                </label>
                                <input
                                    v-model="form.scheduled_at"
                                    type="datetime-local"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <!-- Expiry -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <ClockIcon class="w-4 h-4 inline mr-1" />
                                    Amal Qilish Muddati
                                </label>
                                <input
                                    v-model="form.expires_at"
                                    type="datetime-local"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <!-- Notes -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Qo'shimcha Izoh
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Ichki izohlar..."
                                ></textarea>
                            </div>

                            <!-- Send Immediately -->
                            <div class="md:col-span-2">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="form.send_immediately"
                                        :disabled="!!form.scheduled_at"
                                        class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                    />
                                    <span class="text-gray-700 dark:text-gray-300">Darhol yuborish</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-between p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700">
                        <div class="text-gray-600 dark:text-gray-400">
                            <span class="font-semibold text-purple-600">{{ form.lead_ids.length }}</span> ta lidga
                            <span class="font-semibold">{{ selectedOffer?.name || 'taklif' }}</span> yuboriladi
                        </div>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.offer_id || form.lead_ids.length === 0"
                            class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-purple-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg"
                        >
                            <PaperAirplaneIcon class="w-5 h-5" />
                            {{ form.processing ? 'Yuborilmoqda...' : 'Yuborish' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </component>
</template>
