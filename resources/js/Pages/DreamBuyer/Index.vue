<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
    PlusIcon,
    UserGroupIcon,
    StarIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    dreamBuyers: Array,
});

const deletingBuyer = ref(null);

const getPriorityColor = (priority) => {
    const colors = {
        high: 'bg-red-100 text-red-800 border-red-200',
        medium: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        low: 'bg-green-100 text-green-800 border-green-200',
    };
    return colors[priority] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const setPrimary = (dreamBuyer) => {
    router.post(route('business.dream-buyer.set-primary', dreamBuyer.id), {}, {
        preserveScroll: true,
    });
};

const confirmDelete = (dreamBuyer) => {
    deletingBuyer.value = dreamBuyer;
};

const deleteBuyer = () => {
    if (deletingBuyer.value) {
        router.delete(route('business.dream-buyer.destroy', deletingBuyer.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                deletingBuyer.value = null;
            },
        });
    }
};

const cancelDelete = () => {
    deletingBuyer.value = null;
};
</script>

<template>
    <BusinessLayout title="Ideal Mijozlar">
        <Head title="Ideal Mijozlar" />

        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <UserGroupIcon class="w-10 h-10 text-indigo-600" />
                            Ideal Mijozlar
                        </h1>
                        <p class="mt-2 text-gray-600">
                            "Sell Like Crazy" metodologiyasi asosida ideal mijozlar profilini yarating
                        </p>
                    </div>
                    <Link
                        :href="route('business.dream-buyer.create')"
                        class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200 gap-2"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Yangi Ideal Mijoz
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="!dreamBuyers || dreamBuyers.length === 0" class="text-center py-16">
                    <UserGroupIcon class="mx-auto h-24 w-24 text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Hali Ideal Mijoz yo'q</h3>
                    <p class="mt-2 text-gray-500">
                        9 ta savol orqali ideal mijoz profilini yarating va AI yordamida to'liq analiz oling
                    </p>
                    <div class="mt-6">
                        <Link
                            :href="route('business.dream-buyer.create')"
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200 gap-2"
                        >
                            <PlusIcon class="w-5 h-5" />
                            Ideal Mijoz Yaratish
                        </Link>
                    </div>
                </div>

                <!-- Ideal Mijozlar Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="buyer in dreamBuyers"
                        :key="buyer.id"
                        class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-200 relative group"
                    >
                        <!-- Primary Badge -->
                        <div v-if="buyer.is_primary" class="absolute top-4 right-4 z-10">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-yellow-400 text-yellow-900 shadow-md">
                                <StarIconSolid class="w-4 h-4" />
                                Primary
                            </span>
                        </div>

                        <!-- Card Header -->
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-8 text-white">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                                    <UserGroupIcon class="w-7 h-7" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold">{{ buyer.name }}</h3>
                                </div>
                            </div>
                            <p v-if="buyer.data?.tagline" class="text-indigo-100 text-sm mt-2 line-clamp-2">
                                {{ buyer.data.tagline }}
                            </p>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6">
                            <!-- Description -->
                            <p v-if="buyer.description" class="text-gray-700 text-sm mb-4 line-clamp-3">
                                {{ buyer.description }}
                            </p>

                            <!-- Priority Badge -->
                            <div v-if="buyer.priority" class="mb-4">
                                <span
                                    :class="getPriorityColor(buyer.priority)"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border"
                                >
                                    {{ buyer.priority === 'high' ? 'Yuqori' : buyer.priority === 'medium' ? "O'rta" : 'Past' }} prioritet
                                </span>
                            </div>

                            <!-- AI Profile Indicator -->
                            <div v-if="buyer.data?.avatar_name" class="flex items-center gap-2 text-sm text-indigo-600 mb-4 bg-indigo-50 rounded-lg px-3 py-2">
                                <SparklesIcon class="w-5 h-5" />
                                <span class="font-medium">AI-Generated Profile</span>
                            </div>

                            <!-- Key Stats -->
                            <div v-if="buyer.data" class="grid grid-cols-2 gap-3 mb-4 text-xs">
                                <div v-if="buyer.data.pain_points?.length" class="bg-red-50 rounded-lg p-3 border border-red-100">
                                    <div class="text-red-600 font-semibold mb-1">Pain Points</div>
                                    <div class="text-red-800 text-lg font-bold">{{ buyer.data.pain_points.length }}</div>
                                </div>
                                <div v-if="buyer.data.goals_dreams?.length" class="bg-green-50 rounded-lg p-3 border border-green-100">
                                    <div class="text-green-600 font-semibold mb-1">Goals</div>
                                    <div class="text-green-800 text-lg font-bold">{{ buyer.data.goals_dreams.length }}</div>
                                </div>
                                <div v-if="buyer.data.fears_objections?.length" class="bg-yellow-50 rounded-lg p-3 border border-yellow-100">
                                    <div class="text-yellow-600 font-semibold mb-1">Fears</div>
                                    <div class="text-yellow-800 text-lg font-bold">{{ buyer.data.fears_objections.length }}</div>
                                </div>
                                <div v-if="buyer.data.purchase_triggers?.length" class="bg-blue-50 rounded-lg p-3 border border-blue-100">
                                    <div class="text-blue-600 font-semibold mb-1">Triggers</div>
                                    <div class="text-blue-800 text-lg font-bold">{{ buyer.data.purchase_triggers.length }}</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 mt-6">
                                <Link
                                    :href="route('business.dream-buyer.show', buyer.id)"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium rounded-lg transition-colors"
                                >
                                    <EyeIcon class="w-4 h-4" />
                                    Ko'rish
                                </Link>
                                <Link
                                    :href="route('business.dream-buyer.edit', buyer.id)"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 font-medium rounded-lg transition-colors"
                                >
                                    <PencilIcon class="w-4 h-4" />
                                </Link>
                                <button
                                    @click="confirmDelete(buyer)"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 font-medium rounded-lg transition-colors"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Set Primary Button -->
                            <button
                                v-if="!buyer.is_primary"
                                @click="setPrimary(buyer)"
                                class="w-full mt-3 inline-flex items-center justify-center gap-2 px-4 py-2 border-2 border-yellow-400 hover:bg-yellow-50 text-yellow-700 font-medium rounded-lg transition-colors"
                            >
                                <StarIcon class="w-4 h-4" />
                                Primary qilish
                            </button>
                        </div>
                    </div>
                </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="deletingBuyer"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="cancelDelete"
        >
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <TrashIcon class="w-6 h-6 text-red-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Ideal Mijoz o'chirish</h3>
                        <p class="text-sm text-gray-500">Bu amalni qaytarib bo'lmaydi</p>
                    </div>
                </div>

                <p class="text-gray-700 mb-6">
                    <strong>{{ deletingBuyer.name }}</strong> nomli Ideal Mijozni o'chirishni xohlaysizmi?
                </p>

                <div class="flex gap-3">
                    <button
                        @click="cancelDelete"
                        class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
                    >
                        Bekor qilish
                    </button>
                    <button
                        @click="deleteBuyer"
                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors"
                    >
                        O'chirish
                    </button>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
