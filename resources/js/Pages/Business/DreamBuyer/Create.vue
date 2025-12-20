<template>
    <BusinessLayout title="Yangi Dream Buyer">
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <Link
                    :href="route('business.dream-buyer.index')"
                    class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Orqaga
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Yangi Dream Buyer Yaratish</h1>
                <p class="mt-2 text-gray-600">"Sell Like Crazy" metodologiyasi asosida ideal mijozingizni tavsiflang</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Profil Nomi *
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Masalan: Tadbirkor Aziz, Tashvishli Ona Sabina"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Qisqa Tavsif
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Bu Dream Buyer profili haqida qisqacha..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Ularni qayerda topishingiz mumkin? *
                            </label>
                            <textarea
                                v-model="form.where_spend_time"
                                rows="4"
                                required
                                placeholder="Instagram, Facebook, LinkedIn, offline tadbirlar, ofisda, uyda..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <p v-if="form.errors.where_spend_time" class="mt-1 text-sm text-red-600">{{ form.errors.where_spend_time }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Qaror qabul qilishdan oldin qayerdan ma'lumot olishadi? *
                            </label>
                            <textarea
                                v-model="form.info_sources"
                                rows="4"
                                required
                                placeholder="Google, YouTube, mutaxassis maslahat, do'stlar tavsiyasi, bloglar..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <p v-if="form.errors.info_sources" class="mt-1 text-sm text-red-600">{{ form.errors.info_sources }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Eng katta frustratsiyalari va qiyinchiliklari? *
                            </label>
                            <textarea
                                v-model="form.frustrations"
                                rows="4"
                                required
                                placeholder="Vaqt yetishmasligi, natijalarga erisha olmaslik, pul isrof qilish..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <p v-if="form.errors.frustrations" class="mt-1 text-sm text-red-600">{{ form.errors.frustrations }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Orzulari va umidlari? *
                            </label>
                            <textarea
                                v-model="form.dreams"
                                rows="4"
                                required
                                placeholder="Moliyaviy erkinlik, ko'proq vaqt, muvaffaqiyatli biznes, sog'lom hayot..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <p v-if="form.errors.dreams" class="mt-1 text-sm text-red-600">{{ form.errors.dreams }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Eng katta qo'rquvlari? *
                            </label>
                            <textarea
                                v-model="form.fears"
                                rows="4"
                                required
                                placeholder="Muvaffaqiyatsizlik, pul yo'qotish, noto'g'ri qaror qabul qilish..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <p v-if="form.errors.fears" class="mt-1 text-sm text-red-600">{{ form.errors.fears }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Kommunikatsiya afzalliklari *
                            </label>
                            <textarea
                                v-model="form.communication_preferences"
                                rows="3"
                                required
                                placeholder="Video qo'ng'iroq, matn, email, ijtimoiy tarmoq, yuzma-yuz uchrashuv..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <p v-if="form.errors.communication_preferences" class="mt-1 text-sm text-red-600">{{ form.errors.communication_preferences }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Til va jargon
                            </label>
                            <textarea
                                v-model="form.language_style"
                                rows="3"
                                placeholder="Rasmiy, do'stona, hissiyotli, mantiqiy, texnik, oddiy..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <p v-if="form.errors.language_style" class="mt-1 text-sm text-red-600">{{ form.errors.language_style }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Prioritet
                                </label>
                                <select
                                    v-model="form.priority"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="low">Past</option>
                                    <option value="medium">O'rta</option>
                                    <option value="high">Yuqori</option>
                                </select>
                            </div>

                            <div class="flex items-center">
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="form.is_primary"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm font-medium text-gray-700">Primary qilish</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <Link
                        :href="route('business.dream-buyer.index')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                    >
                        Bekor qilish
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Saqlash
                    </button>
                </div>
            </form>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const form = useForm({
    name: '',
    description: '',
    where_spend_time: '',
    info_sources: '',
    frustrations: '',
    dreams: '',
    fears: '',
    communication_preferences: '',
    language_style: '',
    priority: 'medium',
    is_primary: false,
});

const submit = () => {
    form.post(route('business.dream-buyer.store'));
};
</script>
