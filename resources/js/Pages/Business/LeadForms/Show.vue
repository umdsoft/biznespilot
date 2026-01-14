<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
    leadForm: Object,
    recentSubmissions: Array,
    dailyStats: Array,
    utmStats: Array,
});

const copiedLink = ref(false);
const copiedEmbed = ref(false);
const showEmbedModal = ref(false);
const showQRModal = ref(false);
const baseUrl = ref('');

onMounted(() => {
    baseUrl.value = window.location.origin;
});

// Social share functions
const shareToTelegram = () => {
    const text = encodeURIComponent(`${props.leadForm.title} - ${props.leadForm.description || ''}`);
    const url = encodeURIComponent(props.leadForm.public_url);
    window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank');
};

const shareToWhatsApp = () => {
    const text = encodeURIComponent(`${props.leadForm.title}\n${props.leadForm.public_url}`);
    window.open(`https://wa.me/?text=${text}`, '_blank');
};

const shareToFacebook = () => {
    const url = encodeURIComponent(props.leadForm.public_url);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
};

const shareToLinkedIn = () => {
    const url = encodeURIComponent(props.leadForm.public_url);
    const title = encodeURIComponent(props.leadForm.title);
    window.open(`https://www.linkedin.com/shareArticle?mini=true&url=${url}&title=${title}`, '_blank');
};

const copyLink = () => {
    navigator.clipboard.writeText(props.leadForm.public_url);
    copiedLink.value = true;
    setTimeout(() => copiedLink.value = false, 2000);
};

const copyEmbedCode = () => {
    const embedCode = `<iframe src="${props.leadForm.public_url}?embed=1" width="100%" height="500" frameborder="0"></iframe>`;
    navigator.clipboard.writeText(embedCode);
    copiedEmbed.value = true;
    setTimeout(() => copiedEmbed.value = false, 2000);
};

const toggleStatus = () => {
    router.post(route('business.lead-forms.toggle-status', props.leadForm.id), {}, {
        preserveScroll: true,
    });
};

const getLeadMagnetIcon = (type) => {
    const icons = {
        none: '📝',
        file: '📄',
        video: '🎬',
        link: '🔗',
        coupon: '🎟️',
        text: '💬',
    };
    return icons[type] || '📝';
};

const getStatusColor = (status) => {
    const colors = {
        new: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        contacted: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400',
        qualified: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
        proposal: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
        negotiation: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        won: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        lost: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    };
    return colors[status] || colors.new;
};
</script>

<template>
    <BusinessLayout title="Lead Forma">
        <Head :title="leadForm.name" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('business.lead-forms.index')"
                        class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition-colors"
                    >
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div
                        class="w-12 h-12 rounded-2xl flex items-center justify-center text-white text-2xl"
                        :style="{ backgroundColor: leadForm.theme_color }"
                    >
                        {{ getLeadMagnetIcon(leadForm.lead_magnet_type) }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ leadForm.name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ leadForm.title }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="toggleStatus"
                        :class="[
                            'px-4 py-2 rounded-xl text-sm font-medium transition-colors',
                            leadForm.is_active
                                ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 hover:bg-green-200'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200'
                        ]"
                    >
                        {{ leadForm.is_active ? 'Faol' : 'Nofaol' }}
                    </button>
                    <Link
                        :href="route('business.lead-forms.edit', leadForm.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Tahrirlash
                    </Link>
                    <a
                        :href="leadForm.public_url"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-medium transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Formani ko'rish
                    </a>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ leadForm.views_count?.toLocaleString() || 0 }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Ko'rishlar</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ leadForm.submissions_count?.toLocaleString() || 0 }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Lidlar</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ leadForm.conversion_rate }}%</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Konversiya</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ leadForm.created_at }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Yaratilgan</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Share Links -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Header -->
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/50 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-gray-100">Ulashish</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Formani ijtimoiy tarmoqlarda ulashing</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-5">
                            <!-- Direct Link -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Havola</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        :value="leadForm.public_url"
                                        readonly
                                        class="flex-1 px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                    />
                                    <button
                                        @click="copyLink"
                                        :class="[
                                            'px-5 py-3 rounded-xl text-sm font-medium transition-all duration-200',
                                            copiedLink
                                                ? 'bg-green-500 text-white'
                                                : 'bg-emerald-500 hover:bg-emerald-600 text-white hover:shadow-lg hover:shadow-emerald-500/25'
                                        ]"
                                    >
                                        <span v-if="copiedLink" class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Nusxalandi
                                        </span>
                                        <span v-else class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                            </svg>
                                            Nusxalash
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Social Share Buttons -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Ijtimoiy tarmoqlar</label>
                                <div class="grid grid-cols-4 gap-3">
                                    <!-- Telegram -->
                                    <button
                                        @click="shareToTelegram"
                                        class="flex flex-col items-center gap-2 p-4 bg-gradient-to-br from-sky-50 to-blue-50 dark:from-sky-900/20 dark:to-blue-900/20 hover:from-sky-100 hover:to-blue-100 dark:hover:from-sky-900/30 dark:hover:to-blue-900/30 rounded-xl border border-sky-200 dark:border-sky-800 transition-all duration-200 hover:shadow-md group"
                                    >
                                        <div class="w-10 h-10 bg-sky-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Telegram</span>
                                    </button>

                                    <!-- WhatsApp -->
                                    <button
                                        @click="shareToWhatsApp"
                                        class="flex flex-col items-center gap-2 p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 hover:from-green-100 hover:to-emerald-100 dark:hover:from-green-900/30 dark:hover:to-emerald-900/30 rounded-xl border border-green-200 dark:border-green-800 transition-all duration-200 hover:shadow-md group"
                                    >
                                        <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">WhatsApp</span>
                                    </button>

                                    <!-- Facebook -->
                                    <button
                                        @click="shareToFacebook"
                                        class="flex flex-col items-center gap-2 p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 hover:from-blue-100 hover:to-indigo-100 dark:hover:from-blue-900/30 dark:hover:to-indigo-900/30 rounded-xl border border-blue-200 dark:border-blue-800 transition-all duration-200 hover:shadow-md group"
                                    >
                                        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Facebook</span>
                                    </button>

                                    <!-- LinkedIn -->
                                    <button
                                        @click="shareToLinkedIn"
                                        class="flex flex-col items-center gap-2 p-4 bg-gradient-to-br from-blue-50 to-sky-50 dark:from-blue-900/20 dark:to-sky-900/20 hover:from-blue-100 hover:to-sky-100 dark:hover:from-blue-900/30 dark:hover:to-sky-900/30 rounded-xl border border-blue-200 dark:border-blue-800 transition-all duration-200 hover:shadow-md group"
                                    >
                                        <div class="w-10 h-10 bg-blue-700 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">LinkedIn</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <!-- QR Code -->
                                <button
                                    @click="showQRModal = true"
                                    class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium transition-all duration-200 hover:shadow-md"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    QR Kod
                                </button>

                                <!-- Embed Code -->
                                <button
                                    @click="showEmbedModal = true"
                                    class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium transition-all duration-200 hover:shadow-md"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                    </svg>
                                    Embed Kod
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Submissions -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">So'nggi Lidlar</h3>
                        </div>

                        <div v-if="recentSubmissions?.length" class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div
                                v-for="submission in recentSubmissions"
                                :key="submission.id"
                                class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ submission.form_data?.name || 'Nomsiz' }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ submission.form_data?.phone || submission.form_data?.email || '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            v-if="submission.lead"
                                            :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(submission.lead.status)]"
                                        >
                                            {{ submission.lead.status }}
                                        </span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ submission.created_at }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="submission.utm_source" class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded">
                                        {{ submission.utm_source }}
                                    </span>
                                    <span v-if="submission.utm_campaign" class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded">
                                        {{ submission.utm_campaign }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            Hali lidlar yo'q
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Form Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4">Forma Ma'lumotlari</h3>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Maydonlar soni</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ leadForm.fields?.length || 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Lead Magnet</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ getLeadMagnetIcon(leadForm.lead_magnet_type) }}
                                    {{ leadForm.lead_magnet_type === 'none' ? 'Yo\'q' : leadForm.lead_magnet_title || 'Ha' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Boshlang'ich status</span>
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(leadForm.default_status)]">
                                    {{ leadForm.default_status }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Lead ball</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ leadForm.default_score }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- UTM Stats -->
                    <div v-if="utmStats?.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4">Manbalar</h3>

                        <div class="space-y-2">
                            <div
                                v-for="stat in utmStats"
                                :key="stat.utm_source"
                                class="flex items-center justify-between py-2"
                            >
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ stat.utm_source }}</span>
                                <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full text-xs font-medium">
                                    {{ stat.count }} lid
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- API Info -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-100 dark:border-blue-800 p-6">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-2">API Integratsiya</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Facebook, Google Ads va boshqa platformalardan webhook orqali lidlarni qabul qiling.
                        </p>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                            <code class="text-xs text-gray-700 dark:text-gray-300 break-all">
                                POST {{ baseUrl }}/api/lead-forms/{{ leadForm.slug }}/submit
                            </code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Embed Modal -->
        <Teleport to="body">
            <div v-if="showEmbedModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showEmbedModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Embed Kod</h3>

                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Ushbu kodni saytingizga joylashtiring. Forma to'g'ridan-to'g'ri saytingizda ko'rinadi.
                        </p>

                        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 mb-4">
                            <code class="text-sm text-gray-700 dark:text-gray-300 break-all">
                                &lt;iframe src="{{ leadForm.public_url }}?embed=1" width="100%" height="500" frameborder="0"&gt;&lt;/iframe&gt;
                            </code>
                        </div>

                        <div class="flex gap-3">
                            <button
                                @click="showEmbedModal = false"
                                class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                            >
                                Yopish
                            </button>
                            <button
                                @click="copyEmbedCode"
                                class="flex-1 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl transition-colors"
                            >
                                {{ copiedEmbed ? 'Nusxalandi!' : 'Nusxalash' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- QR Code Modal -->
        <Teleport to="body">
            <div v-if="showQRModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showQRModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-sm w-full p-6 border border-gray-200 dark:border-gray-700">
                        <div class="text-center">
                            <div class="w-14 h-14 mx-auto bg-emerald-100 dark:bg-emerald-900/50 rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">QR Kod</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                                Ushbu QR kodni skanerlash orqali formaga o'tish mumkin
                            </p>

                            <!-- QR Code Image using Google Charts API -->
                            <div class="bg-white p-4 rounded-xl border-2 border-gray-100 dark:border-gray-600 inline-block mb-6">
                                <img
                                    :src="`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(leadForm.public_url)}`"
                                    alt="QR Code"
                                    class="w-48 h-48"
                                />
                            </div>

                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-4 break-all">
                                {{ leadForm.public_url }}
                            </p>

                            <div class="flex gap-3">
                                <button
                                    @click="showQRModal = false"
                                    class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                                >
                                    Yopish
                                </button>
                                <a
                                    :href="`https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodeURIComponent(leadForm.public_url)}&format=png`"
                                    download="qr-code.png"
                                    target="_blank"
                                    class="flex-1 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl transition-colors text-center"
                                >
                                    Yuklab olish
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </BusinessLayout>
</template>
