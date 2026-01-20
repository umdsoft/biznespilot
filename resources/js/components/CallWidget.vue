<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { PhoneArrowUpRightIcon } from '@heroicons/vue/24/solid';

const { t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    lead: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'openSoftphone']);

// Get initials
const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

// Format phone for SIP protocol
const sipUrl = computed(() => {
    if (!props.lead.phone) return '';
    const cleanPhone = props.lead.phone.replace(/\D/g, '');
    return `sip:${cleanPhone}`;
});

// Close widget
const closeWidget = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div
                v-if="show"
                class="fixed bottom-6 right-6 z-50 w-72 bg-slate-900 rounded-2xl shadow-2xl border border-slate-700/50 overflow-hidden"
            >
                <!-- Header -->
                <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-4 py-3 flex items-center justify-between">
                    <span class="text-sm font-medium text-white">{{ t('components.call.title') }}</span>
                    <button
                        @click="closeWidget"
                        class="p-1 text-slate-400 hover:text-white hover:bg-slate-600 rounded-lg transition-colors"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <!-- Lead Info -->
                <div class="p-5 text-center">
                    <!-- Avatar -->
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl">
                        {{ getInitials(lead.name) }}
                    </div>

                    <!-- Name & Phone -->
                    <h3 class="text-base font-semibold text-white mb-1">{{ lead.name }}</h3>
                    <p class="text-slate-400 text-sm">{{ lead.phone }}</p>
                </div>

                <!-- Call Button -->
                <div class="px-5 pb-5">
                    <a
                        :href="sipUrl"
                        @click="emit('openSoftphone')"
                        class="w-full flex items-center justify-center gap-3 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition-colors shadow-lg shadow-green-600/30"
                    >
                        <PhoneArrowUpRightIcon class="w-5 h-5" />
                        {{ t('components.call.make_call') }}
                    </a>

                    <p class="text-xs text-slate-500 text-center mt-3">
                        {{ t('components.call.microsip_opens') }}
                    </p>

                    <a
                        href="https://www.microsip.org/downloads"
                        target="_blank"
                        class="block text-center text-xs text-slate-500 hover:text-slate-400 underline mt-2"
                    >
                        {{ t('components.call.download_microsip') }}
                    </a>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
