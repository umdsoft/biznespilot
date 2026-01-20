<template>
    <component :is="layoutComponent" :title="t('dream_buyer.new_profile')">
        <div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <!-- Back Link -->
                <button
                    @click="goBack"
                    class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-6 transition-colors group"
                >
                    <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ t('common.back') }}
                </button>

                <!-- Header -->
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                            {{ t('dream_buyer.create_title') }}
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">
                            {{ t('dream_buyer.create_desc') }}
                        </p>
                    </div>
                </div>

                <!-- Progress Section -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 mb-8">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                            <component :is="steps[currentStep].icon" class="w-7 h-7 text-white" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ steps[currentStep].title }}</h2>
                                <span class="text-sm font-bold" :class="progressPercent < 50 ? 'text-orange-500' : 'text-green-500'">
                                    {{ progressPercent }}%
                                    <span class="font-normal text-gray-400">Tugallandi</span>
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Qadam {{ currentStep + 1 }} / {{ steps.length }}</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-6">
                        <div
                            class="h-full rounded-full transition-all duration-500 ease-out bg-gradient-to-r from-orange-500 via-amber-500 to-green-500"
                            :style="{ width: progressPercent + '%' }"
                        ></div>
                    </div>

                    <!-- Step Circles -->
                    <div class="flex items-center justify-between">
                        <template v-for="(step, index) in steps" :key="index">
                            <button
                                type="button"
                                @click="goToStep(index)"
                                :disabled="!canGoToStep(index)"
                                class="relative group flex flex-col items-center"
                            >
                                <div
                                    :class="[
                                        'w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 border-2',
                                        currentStep === index
                                            ? 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white border-indigo-500 shadow-lg shadow-indigo-500/40 scale-110'
                                            : isStepComplete(index)
                                                ? 'bg-green-500 text-white border-green-500'
                                                : canGoToStep(index)
                                                    ? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600 cursor-pointer'
                                                    : 'bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border-gray-200 dark:border-gray-700'
                                    ]"
                                >
                                    <svg v-if="isStepComplete(index) && currentStep !== index" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span v-else>{{ index + 1 }}</span>
                                </div>

                                <!-- Step Label -->
                                <span
                                    :class="[
                                        'mt-2 text-xs font-medium text-center whitespace-nowrap transition-colors',
                                        currentStep === index
                                            ? 'text-indigo-600 dark:text-indigo-400'
                                            : 'text-gray-500 dark:text-gray-400'
                                    ]"
                                >
                                    {{ step.shortTitle }}
                                </span>
                            </button>

                            <!-- Connector Line -->
                            <div
                                v-if="index < steps.length - 1"
                                class="flex-1 h-0.5 mx-1 rounded-full transition-all duration-500 -mt-4"
                                :class="index < currentStep ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit">
                    <!-- Step Content -->
                    <transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 translate-y-4"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-4"
                        mode="out-in"
                    >
                        <div :key="currentStep">
                            <!-- Step Card -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <!-- Step Header with Gradient -->
                                <div :class="['px-6 py-5 bg-gradient-to-r', steps[currentStep].headerGradient || 'from-indigo-500 to-purple-600']">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                            <component :is="steps[currentStep].icon" class="w-6 h-6 text-white" />
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-bold text-white">{{ steps[currentStep].title }}</h2>
                                            <p class="text-white/80 text-sm">{{ steps[currentStep].description }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step Form Fields -->
                                <div class="p-6 sm:p-8">
                                    <!-- Step 1: Profil Nomi -->
                                    <div v-if="currentStep === 0" class="space-y-6">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                Profil Nomi <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.name"
                                                type="text"
                                                required
                                                placeholder="Masalan: Tashvishli Ona Sabina, Muvaffaqiyatga Intiluvchi Jasur"
                                                class="w-full px-4 py-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-indigo-500 dark:focus:border-indigo-400 focus:bg-white dark:focus:bg-gray-700 focus:ring-4 focus:ring-indigo-500/10 transition-all text-lg"
                                            />
                                            <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                Qisqa Tavsif <span class="text-gray-400">(ixtiyoriy)</span>
                                            </label>
                                            <textarea
                                                v-model="form.description"
                                                rows="3"
                                                placeholder="Bu Ideal Mijoz haqida qisqacha..."
                                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-indigo-500 dark:focus:border-indigo-400 focus:bg-white dark:focus:bg-gray-700 focus:ring-4 focus:ring-indigo-500/10 transition-all resize-none"
                                            ></textarea>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Prioritet</label>
                                                <select
                                                    v-model="form.priority"
                                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all"
                                                >
                                                    <option value="low">Past</option>
                                                    <option value="medium">O'rta</option>
                                                    <option value="high">Yuqori</option>
                                                </select>
                                            </div>
                                            <div class="flex items-end">
                                                <label class="flex items-center gap-3 w-full px-4 py-3 rounded-xl border-2 cursor-pointer transition-all"
                                                    :class="form.is_primary
                                                        ? 'border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20'
                                                        : 'border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-500'"
                                                >
                                                    <input type="checkbox" v-model="form.is_primary" class="sr-only" />
                                                    <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all"
                                                        :class="form.is_primary
                                                            ? 'bg-yellow-400 border-yellow-400'
                                                            : 'border-gray-300 dark:border-gray-500'"
                                                    >
                                                        <svg v-if="form.is_primary" class="w-3 h-3 text-yellow-900" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                                        </svg>
                                                    </div>
                                                    <span class="text-sm font-medium" :class="form.is_primary ? 'text-yellow-700 dark:text-yellow-400' : 'text-gray-700 dark:text-gray-300'">
                                                        Primary
                                                    </span>
                                                    <span class="text-xs text-gray-400">Asosiy mijoz sifatida belgilash</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2: Qayerda vaqt o'tkazadi -->
                                    <div v-else-if="currentStep === 1" class="space-y-4">
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <button
                                                v-for="tag in quickPlaces"
                                                :key="tag"
                                                type="button"
                                                @click="toggleTag('where_spend_time', tag)"
                                                :class="[
                                                    'px-3 py-1.5 rounded-full text-sm font-medium transition-all',
                                                    form.where_spend_time.includes(tag)
                                                        ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30'
                                                        : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50'
                                                ]"
                                            >
                                                {{ tag }}
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="form.where_spend_time"
                                            rows="5"
                                            required
                                            placeholder="Instagram, Facebook, Telegram, YouTube, LinkedIn, offline tadbirlar, biznes konferensiyalar, ofis muhiti..."
                                            class="w-full px-4 py-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 dark:focus:border-blue-400 focus:bg-white dark:focus:bg-gray-700 focus:ring-4 focus:ring-blue-500/10 transition-all resize-none"
                                        ></textarea>
                                        <p v-if="form.errors.where_spend_time" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.where_spend_time }}</p>
                                    </div>

                                    <!-- Step 3: Ma'lumot manbalari -->
                                    <div v-else-if="currentStep === 2" class="space-y-4">
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <button
                                                v-for="tag in quickSources"
                                                :key="tag"
                                                type="button"
                                                @click="toggleTag('info_sources', tag)"
                                                :class="[
                                                    'px-3 py-1.5 rounded-full text-sm font-medium transition-all',
                                                    form.info_sources.includes(tag)
                                                        ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-500/30'
                                                        : 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 hover:bg-cyan-200 dark:hover:bg-cyan-900/50'
                                                ]"
                                            >
                                                {{ tag }}
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="form.info_sources"
                                            rows="5"
                                            required
                                            placeholder="Google qidiruvi, YouTube video darslar, mutaxassislar maslahati, do'stlar tavsiyasi, bloglar, podcastlar, kitoblar..."
                                            class="w-full px-4 py-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-cyan-500 dark:focus:border-cyan-400 focus:bg-white dark:focus:bg-gray-700 focus:ring-4 focus:ring-cyan-500/10 transition-all resize-none"
                                        ></textarea>
                                        <p v-if="form.errors.info_sources" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.info_sources }}</p>
                                    </div>

                                    <!-- Step 4: Frustratsiyalar -->
                                    <div v-else-if="currentStep === 3" class="space-y-4">
                                        <textarea
                                            v-model="form.frustrations"
                                            rows="6"
                                            required
                                            placeholder="Vaqt yetishmasligi, natijalarga erisha olmaslik, pulni behuda sarflash, noto'g'ri qarorlar qabul qilish, raqobatchilardan orqada qolish..."
                                            class="w-full px-4 py-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-red-500 dark:focus:border-red-400 focus:bg-white dark:focus:bg-gray-700 focus:ring-4 focus:ring-red-500/10 transition-all resize-none"
                                        ></textarea>
                                        <p v-if="form.errors.frustrations" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.frustrations }}</p>
                                    </div>

                                    <!-- Step 5: Orzular -->
                                    <div v-else-if="currentStep === 4" class="space-y-4">
                                        <textarea
                                            v-model="form.dreams"
                                            rows="6"
                                            required
                                            placeholder="Moliyaviy erkinlik, ko'proq erkin vaqt, muvaffaqiyatli biznes, sog'lom hayot tarzi, oilaga ko'proq vaqt, hurmat qozonish, sohasida tan olinish..."
                                            class="w-full px-4 py-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-green-500 dark:focus:border-green-400 focus:bg-white dark:focus:bg-gray-700 focus:ring-4 focus:ring-green-500/10 transition-all resize-none"
                                        ></textarea>
                                        <p v-if="form.errors.dreams" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.dreams }}</p>
                                    </div>

                                    <!-- Step 6: Qo'rquvlar -->
                                    <div v-else-if="currentStep === 5" class="space-y-4">
                                        <textarea
                                            v-model="form.fears"
                                            rows="6"
                                            required
                                            placeholder="Muvaffaqiyatsizlik, pul yo'qotish, noto'g'ri qaror qabul qilish, vaqtni behuda sarflash, boshqalar oldida sharmanda bo'lish..."
                                            class="w-full px-4 py-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-orange-500 dark:focus:border-orange-400 focus:bg-white dark:focus:bg-gray-700 focus:ring-4 focus:ring-orange-500/10 transition-all resize-none"
                                        ></textarea>
                                        <p v-if="form.errors.fears" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.fears }}</p>
                                    </div>

                                    <!-- Step 7: Kommunikatsiya -->
                                    <div v-else-if="currentStep === 6" class="space-y-4">
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <button
                                                v-for="tag in quickComms"
                                                :key="tag"
                                                type="button"
                                                @click="toggleTag('communication_preferences', tag)"
                                                :class="[
                                                    'px-3 py-1.5 rounded-full text-sm font-medium transition-all',
                                                    form.communication_preferences.includes(tag)
                                                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/30'
                                                        : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 hover:bg-purple-200 dark:hover:bg-purple-900/50'
                                                ]"
                                            >
                                                {{ tag }}
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="form.communication_preferences"
                                            rows="5"
                                            required
                                            placeholder="Telegram, WhatsApp, email, telefon qo'ng'iroq, video call, yuzma-yuz uchrashuv..."
                                            class="w-full px-4 py-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-purple-500 dark:focus:border-purple-400 focus:bg-white dark:focus:bg-gray-700 focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"
                                        ></textarea>
                                        <p v-if="form.errors.communication_preferences" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.communication_preferences }}</p>
                                    </div>

                                    <!-- Step 8: Til uslubi -->
                                    <div v-else-if="currentStep === 7" class="space-y-4">
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <button
                                                v-for="tag in quickStyles"
                                                :key="tag"
                                                type="button"
                                                @click="toggleTag('language_style', tag)"
                                                :class="[
                                                    'px-3 py-1.5 rounded-full text-sm font-medium transition-all',
                                                    form.language_style.includes(tag)
                                                        ? 'bg-pink-600 text-white shadow-lg shadow-pink-500/30'
                                                        : 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400 hover:bg-pink-200 dark:hover:bg-pink-900/50'
                                                ]"
                                            >
                                                {{ tag }}
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="form.language_style"
                                            rows="5"
                                            placeholder="Rasmiy, do'stona, oddiy, professional, hissiyotli, mantiqiy, texnik atamalar bilan..."
                                            class="w-full px-4 py-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-pink-500 dark:focus:border-pink-400 focus:bg-white dark:focus:bg-gray-700 focus:ring-4 focus:ring-pink-500/10 transition-all resize-none"
                                        ></textarea>
                                    </div>

                                    <!-- Step 9: Yakuniy ko'rish -->
                                    <div v-else-if="currentStep === 8" class="space-y-6">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="p-4 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800">
                                                <label class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wide">Profil Nomi</label>
                                                <p class="text-gray-900 dark:text-white font-bold text-lg mt-1">{{ form.name || '—' }}</p>
                                            </div>
                                            <div class="p-4 bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl border border-yellow-100 dark:border-yellow-800">
                                                <label class="text-xs font-semibold text-yellow-600 dark:text-yellow-400 uppercase tracking-wide">Prioritet</label>
                                                <p class="text-gray-900 dark:text-white font-bold text-lg mt-1 capitalize">{{ form.priority }} {{ form.is_primary ? '(Primary)' : '' }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <SummaryItem label="Qayerda topish mumkin" :value="form.where_spend_time" color="blue" icon="location" />
                                            <SummaryItem label="Ma'lumot manbalari" :value="form.info_sources" color="cyan" icon="search" />
                                            <SummaryItem label="Frustratsiyalar" :value="form.frustrations" color="red" icon="fire" />
                                            <SummaryItem label="Orzular va maqsadlar" :value="form.dreams" color="green" icon="star" />
                                            <SummaryItem label="Qo'rquvlar" :value="form.fears" color="orange" icon="shield" />
                                            <SummaryItem label="Kommunikatsiya" :value="form.communication_preferences" color="purple" icon="chat" />
                                            <SummaryItem v-if="form.language_style" label="Til uslubi" :value="form.language_style" color="pink" icon="pencil" />
                                        </div>
                                    </div>

                                    <!-- Tip Box -->
                                    <div v-if="steps[currentStep].tip" class="mt-6 p-4 rounded-xl bg-gradient-to-r" :class="steps[currentStep].tipBg">
                                        <div class="flex gap-3">
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-white/50">
                                                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-800">Maslahat</p>
                                                <p class="text-sm text-gray-700 mt-0.5">{{ steps[currentStep].tip }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Navigation -->
                    <div class="mt-8 flex items-center justify-between">
                        <button
                            v-if="currentStep > 0"
                            type="button"
                            @click="prevStep"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            {{ t('common.previous') }}
                        </button>
                        <div v-else></div>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="goBack"
                                class="px-5 py-3 text-gray-500 dark:text-gray-400 font-medium hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
                            >
                                {{ t('common.cancel') }}
                            </button>

                            <button
                                v-if="currentStep < steps.length - 1"
                                type="button"
                                @click="nextStep"
                                :disabled="!isCurrentStepValid"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-indigo-600 disabled:hover:to-purple-600"
                            >
                                {{ t('common.next') }}
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <button
                                v-else
                                type="submit"
                                :disabled="form.processing || !isFormValid"
                                class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-700 shadow-lg shadow-green-500/30 hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ form.processing ? t('common.loading') : t('dream_buyer.create_button') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </component>
</template>

<script setup>
import { ref, computed, h } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const { t } = useI18n();

const props = defineProps({
    panelType: {
        type: String,
        required: true,
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v),
    },
});

// Layout selection
const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        marketing: MarketingLayout,
        finance: FinanceLayout,
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});

// Route helpers
const getRoutePrefix = () => props.panelType;
const goBack = () => router.get(`/${props.panelType}/dream-buyer`);

// Summary Item Component
const SummaryItem = {
    props: ['label', 'value', 'color', 'icon'],
    setup(props) {
        const colorClasses = {
            blue: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
            cyan: 'bg-cyan-50 dark:bg-cyan-900/20 border-cyan-200 dark:border-cyan-800',
            red: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
            green: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
            orange: 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800',
            purple: 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800',
            pink: 'bg-pink-50 dark:bg-pink-900/20 border-pink-200 dark:border-pink-800',
        };
        const labelColors = {
            blue: 'text-blue-600 dark:text-blue-400',
            cyan: 'text-cyan-600 dark:text-cyan-400',
            red: 'text-red-600 dark:text-red-400',
            green: 'text-green-600 dark:text-green-400',
            orange: 'text-orange-600 dark:text-orange-400',
            purple: 'text-purple-600 dark:text-purple-400',
            pink: 'text-pink-600 dark:text-pink-400',
        };
        return () => h('div', { class: `p-4 rounded-xl border ${colorClasses[props.color] || colorClasses.blue}` }, [
            h('label', { class: `text-xs font-semibold uppercase tracking-wide block mb-1 ${labelColors[props.color] || labelColors.blue}` }, props.label),
            h('p', { class: 'text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line' }, props.value || '—')
        ]);
    }
};

// Icons
const UserIcon = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' })]) };
const LocationIcon = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' }), h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M15 11a3 3 0 11-6 0 3 3 0 016 0z' })]) };
const SearchIcon = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' })]) };
const FireIcon = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z' }), h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z' })]) };
const StarIcon = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' })]) };
const ShieldIcon = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01' })]) };
const ChatIcon = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' })]) };
const PencilIcon = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' })]) };
const CheckIcon = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' })]) };

const currentStep = ref(0);

const steps = [
    {
        title: "Asosiy Ma'lumot",
        shortTitle: 'Asosiy',
        description: 'Ideal Mijoz profili nomi va tavsifi',
        icon: UserIcon,
        headerGradient: 'from-indigo-500 to-purple-600',
        field: 'name',
        tip: "Profil nomini 2-3 so'zdan iborat aniq va esda qoladigan qilib nomlang.",
        tipBg: 'from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30',
    },
    {
        title: "Qayerda vaqt o'tkazadi?",
        shortTitle: 'Vaqt',
        description: 'Ideal mijozingiz eng ko\'p qayerda vaqt o\'tkazadi?',
        icon: LocationIcon,
        headerGradient: 'from-blue-500 to-cyan-500',
        field: 'where_spend_time',
        tip: "Onlayn va offlayn joylarni ham yozing. Aniqroq bo'lsa, marketing samaraliroq bo'ladi.",
        tipBg: 'from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30',
    },
    {
        title: "Qayerdan ma'lumot oladi?",
        shortTitle: "Ma'lumot",
        description: 'Qaror qabul qilishdan oldin qayerdan ma\'lumot izlaydi?',
        icon: SearchIcon,
        headerGradient: 'from-cyan-500 to-teal-500',
        field: 'info_sources',
        tip: "Ular ishonchli deb hisoblaydigan manbalarni aniqlang - bu sizning kontent strategiyangizni belgilaydi.",
        tipBg: 'from-cyan-100 to-teal-100 dark:from-cyan-900/30 dark:to-teal-900/30',
    },
    {
        title: "Frustratsiyalari nima?",
        shortTitle: 'Muamm...',
        description: 'Eng katta qiyinchiliklari va frustratsiyalari',
        icon: FireIcon,
        headerGradient: 'from-red-500 to-rose-500',
        field: 'frustrations',
        tip: "Bu pain pointlar sizning marketing xabarlaringizning asosi bo'ladi.",
        tipBg: 'from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30',
    },
    {
        title: "Orzulari nima?",
        shortTitle: 'Orzular',
        description: 'Ular nimaga erishmoqchi, nima orzu qiladi?',
        icon: StarIcon,
        headerGradient: 'from-green-500 to-emerald-500',
        field: 'dreams',
        tip: "Moddiy va hissiy orzularni ham yozing. 'Hurmat qozonish', 'O'z sohasida tan olinish' kabi.",
        tipBg: 'from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30',
    },
    {
        title: "Qo'rquvlari nima?",
        shortTitle: "Qo'rquvlar",
        description: 'Eng katta qo\'rquvlari va e\'tirozlari',
        icon: ShieldIcon,
        headerGradient: 'from-orange-500 to-amber-500',
        field: 'fears',
        tip: "Qo'rquvlarni bilish e'tirozlarni yengishga yordam beradi.",
        tipBg: 'from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30',
    },
    {
        title: "Qanday muloqot afzal?",
        shortTitle: 'Kommuni...',
        description: 'Qaysi kommunikatsiya kanallarini afzal ko\'radi?',
        icon: ChatIcon,
        headerGradient: 'from-purple-500 to-violet-500',
        field: 'communication_preferences',
        tip: "Bu sizning qaysi kanalda marketing qilishingizni belgilaydi.",
        tipBg: 'from-purple-100 to-violet-100 dark:from-purple-900/30 dark:to-violet-900/30',
    },
    {
        title: "Qanday tilda gapiradi?",
        shortTitle: 'Til',
        description: 'Qanday til uslubi va jargonni ishlatadi?',
        icon: PencilIcon,
        headerGradient: 'from-pink-500 to-rose-500',
        field: 'language_style',
        required: false,
        tip: "Bu sizning kontent va reklamalaringiz qanday yozilishi kerakligini belgilaydi.",
        tipBg: 'from-pink-100 to-rose-100 dark:from-pink-900/30 dark:to-rose-900/30',
    },
    {
        title: "Kundalik hayot",
        shortTitle: 'Kundalik',
        description: 'Barcha ma\'lumotlarni tekshiring va tasdiqlang',
        icon: CheckIcon,
        headerGradient: 'from-emerald-500 to-green-600',
        field: null,
    },
];

const quickPlaces = ['Instagram', 'Facebook', 'Telegram', 'YouTube', 'LinkedIn', 'TikTok', 'Twitter/X', 'Offline tadbirlar'];
const quickSources = ['Google', 'YouTube', "Do'stlar", 'Mutaxassislar', 'Bloglar', 'Podcastlar', 'Kitoblar', 'Kurslar'];
const quickComms = ['Telegram', 'WhatsApp', 'Email', 'Telefon', 'Video call', 'Yuzma-yuz', 'SMS', 'Instagram DM'];
const quickStyles = ['Rasmiy', "Do'stona", 'Oddiy', 'Professional', 'Hissiyotli', 'Mantiqiy', 'Texnik', 'Hazil aralash'];

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

const toggleTag = (field, tag) => {
    const currentValue = form[field];
    if (currentValue.includes(tag)) {
        form[field] = currentValue
            .split(',')
            .map(t => t.trim())
            .filter(t => t !== tag)
            .join(', ');
    } else {
        form[field] = currentValue ? `${currentValue}, ${tag}` : tag;
    }
};

const progressPercent = computed(() => {
    return Math.round(((currentStep.value + 1) / steps.length) * 100);
});

const isStepComplete = (index) => {
    const step = steps[index];
    if (!step.field) return currentStep.value > index;
    if (step.required === false) return true;
    return form[step.field] && form[step.field].trim() !== '';
};

const canGoToStep = (index) => {
    if (index <= currentStep.value) return true;
    for (let i = 0; i < index; i++) {
        if (!isStepComplete(i)) return false;
    }
    return true;
};

const goToStep = (index) => {
    if (canGoToStep(index)) {
        currentStep.value = index;
    }
};

const isCurrentStepValid = computed(() => {
    const step = steps[currentStep.value];
    if (!step.field) return true;
    if (step.required === false) return true;
    return form[step.field] && form[step.field].trim() !== '';
});

const isFormValid = computed(() => {
    return form.name &&
           form.where_spend_time &&
           form.info_sources &&
           form.frustrations &&
           form.dreams &&
           form.fears &&
           form.communication_preferences;
});

const nextStep = () => {
    if (currentStep.value < steps.length - 1 && isCurrentStepValid.value) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const submit = () => {
    const prefix = getRoutePrefix();
    form.post(route(`${prefix}.dream-buyer.store`));
};
</script>
