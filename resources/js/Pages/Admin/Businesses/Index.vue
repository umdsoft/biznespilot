<template>
    <AdminLayout :title="t('admin.businesses.title')">
        <div class="min-h-screen">
            <!-- Header -->
            <div class="border-b border-gray-200 dark:border-gray-700/50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm sticky top-0 z-10">
                <div class="max-w-[1600px] mx-auto px-6 py-5">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ t('admin.businesses.title') }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ stats.total }} {{ t('admin.businesses.total_businesses').toLowerCase() }}
                    </p>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-6 py-6">
                <!-- Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div v-for="stat in statCards" :key="stat.label" class="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center gap-3">
                            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', stat.iconBg]">
                                <component :is="stat.icon" :class="['w-5 h-5', stat.iconColor]" />
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stat.value }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="filters.search"
                            type="text"
                            :placeholder="t('admin.businesses.search_placeholder')"
                            class="w-full pl-10 pr-4 py-2.5 text-sm bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 dark:focus:border-emerald-500 text-gray-900 dark:text-white placeholder-gray-400 transition-colors"
                        />
                    </div>
                    <select v-model="filters.status" class="px-4 py-2.5 text-sm bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg text-gray-900 dark:text-white min-w-[160px]">
                        <option value="">{{ t('common.status') }}: {{ t('admin.common.all') }}</option>
                        <option value="active">{{ t('admin.common.active') }}</option>
                        <option value="inactive">{{ t('admin.common.inactive') }}</option>
                    </select>
                    <select v-model="filters.sort" class="px-4 py-2.5 text-sm bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg text-gray-900 dark:text-white min-w-[160px]">
                        <option value="newest">{{ t('admin.common.newest') }}</option>
                        <option value="oldest">{{ t('admin.common.oldest') }}</option>
                        <option value="name">{{ t('admin.common.by_name') }}</option>
                        <option value="customers">{{ t('admin.businesses.by_customers') }}</option>
                    </select>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-visible">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50/80 dark:bg-gray-900/30">
                                    <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">
                                        {{ t('admin.businesses.business') }}
                                    </th>
                                    <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5 hidden md:table-cell">
                                        {{ t('admin.businesses.owner') }}
                                    </th>
                                    <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">
                                        Obuna
                                    </th>
                                    <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5 hidden lg:table-cell">
                                        {{ t('admin.businesses.customers') }}
                                    </th>
                                    <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">
                                        {{ t('common.status') }}
                                    </th>
                                    <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5 hidden sm:table-cell">
                                        {{ t('admin.common.created') }}
                                    </th>
                                    <th class="w-20 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 py-3.5">
                                        Amallar
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                                <tr
                                    v-for="business in filteredBusinesses"
                                    :key="business.id"
                                    class="group hover:bg-emerald-50/30 dark:hover:bg-emerald-900/5 transition-colors"
                                >
                                    <!-- Business -->
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center text-sm font-bold text-white shadow-sm">
                                                {{ business.name?.charAt(0).toUpperCase() || 'B' }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ business.name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ business.category || '—' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Owner -->
                                    <td class="px-5 py-4 hidden md:table-cell">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ business.owner_name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ business.owner_email }}</p>
                                    </td>

                                    <!-- Subscription -->
                                    <td class="px-5 py-4">
                                        <div v-if="business.subscription" class="space-y-1">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                {{ business.subscription.plan_name }}
                                            </span>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                <ClockIcon class="w-3 h-3" />
                                                {{ business.subscription.days_remaining }} kun qoldi
                                                <span class="text-gray-400 dark:text-gray-500">&middot;</span>
                                                {{ business.subscription.ends_at }}
                                            </p>
                                        </div>
                                        <div v-else>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border border-red-200/50 dark:border-red-500/20">
                                                <XCircleIcon class="w-3.5 h-3.5" />
                                                Obuna yo'q
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Customers -->
                                    <td class="px-5 py-4 text-center hidden lg:table-cell">
                                        <span class="inline-flex items-center justify-center min-w-[32px] px-2 py-0.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700/50 rounded-md">
                                            {{ business.customers_count }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-5 py-4 text-center">
                                        <span :class="getStatusBadgeClass(business.status)" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg">
                                            <span :class="business.status === 'active' ? 'bg-emerald-500' : 'bg-gray-400'" class="w-1.5 h-1.5 rounded-full"></span>
                                            {{ business.status === 'active' ? t('admin.common.active') : t('admin.common.inactive') }}
                                        </span>
                                    </td>

                                    <!-- Created -->
                                    <td class="px-5 py-4 hidden sm:table-cell">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ business.created_at }}</span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-3 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                @click="viewBusiness(business.id)"
                                                class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                title="Ko'rish"
                                            >
                                                <EyeIcon class="w-4.5 h-4.5" />
                                            </button>
                                            <button
                                                @click="openSubscriptionModal(business)"
                                                class="p-1.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                                                title="Obuna berish"
                                            >
                                                <CreditCardIcon class="w-4.5 h-4.5" />
                                            </button>
                                            <button
                                                @click="toggleStatus(business)"
                                                class="p-1.5 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors"
                                                :title="business.status === 'active' ? t('admin.businesses.deactivate') : t('admin.businesses.activate')"
                                            >
                                                <PauseCircleIcon v-if="business.status === 'active'" class="w-4.5 h-4.5" />
                                                <PlayCircleIcon v-else class="w-4.5 h-4.5" />
                                            </button>
                                            <button
                                                @click="confirmDelete(business)"
                                                class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                title="O'chirish"
                                            >
                                                <TrashIcon class="w-4.5 h-4.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredBusinesses.length === 0" class="py-16 text-center">
                        <BuildingOffice2Icon class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('admin.businesses.not_found') }}</p>
                    </div>

                    <!-- Footer -->
                    <div v-if="filteredBusinesses.length > 0" class="px-5 py-3 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-800/30">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ filteredBusinesses.length }} / {{ businesses.length }} {{ t('admin.common.results') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Subscription Modal -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition-opacity duration-150"
                    leave-active-class="transition-opacity duration-100"
                    enter-from-class="opacity-0"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showSubscriptionModal" @click="showSubscriptionModal = false" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                        <Transition
                            enter-active-class="transition-all duration-200"
                            leave-active-class="transition-all duration-100"
                            enter-from-class="opacity-0 scale-95 translate-y-2"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="showSubscriptionModal" @click.stop class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <!-- Header with gradient -->
                                <div class="px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                                    <h3 class="text-lg font-bold">Obuna tayinlash</h3>
                                    <p class="text-sm text-indigo-200 mt-0.5">
                                        {{ subscriptionTarget?.name }}
                                    </p>
                                    <div v-if="subscriptionTarget?.subscription" class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-white/15 rounded-lg text-sm">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                        Hozirgi: <strong>{{ subscriptionTarget.subscription.plan_name }}</strong>
                                        &middot; {{ subscriptionTarget.subscription.days_remaining }} kun qoldi
                                    </div>
                                </div>

                                <!-- Form -->
                                <div class="px-6 py-5 space-y-5">
                                    <!-- Plan Selection -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Tarifni tanlang</label>
                                        <div class="grid gap-2">
                                            <label
                                                v-for="plan in plans"
                                                :key="plan.id"
                                                :class="[
                                                    'flex items-center justify-between p-3.5 rounded-xl border-2 cursor-pointer transition-all',
                                                    subForm.plan_id === plan.id
                                                        ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20 shadow-sm shadow-indigo-100'
                                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                                ]"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <div :class="[
                                                        'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors',
                                                        subForm.plan_id === plan.id
                                                            ? 'border-indigo-500 bg-indigo-500'
                                                            : 'border-gray-300 dark:border-gray-600'
                                                    ]">
                                                        <svg v-if="subForm.plan_id === plan.id" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                        </svg>
                                                    </div>
                                                    <input type="radio" :value="plan.id" v-model="subForm.plan_id" class="sr-only" />
                                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ plan.name }}</span>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                                                        {{ formatCurrency(subForm.billing_cycle === 'yearly' ? plan.price_yearly : plan.price_monthly) }}
                                                        <span class="text-xs font-normal text-gray-400">UZS</span>
                                                    </p>
                                                    <p class="text-[10px] text-gray-500">
                                                        {{ subForm.billing_cycle === 'yearly' ? 'yillik' : 'oylik' }}
                                                    </p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Billing Cycle & Duration -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">To'lov davri</label>
                                            <div class="grid grid-cols-2 gap-2">
                                                <button
                                                    v-for="cycle in [{value: 'monthly', label: 'Oylik'}, {value: 'yearly', label: 'Yillik'}]"
                                                    :key="cycle.value"
                                                    type="button"
                                                    @click="subForm.billing_cycle = cycle.value"
                                                    :class="[
                                                        'px-3 py-2.5 text-sm font-semibold rounded-xl border-2 transition-all',
                                                        subForm.billing_cycle === cycle.value
                                                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400'
                                                            : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'
                                                    ]"
                                                >
                                                    {{ cycle.label }}
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Muddat</label>
                                            <div class="grid grid-cols-2 gap-2">
                                                <button
                                                    v-for="m in [1, 3, 6, 12]"
                                                    :key="m"
                                                    type="button"
                                                    @click="subForm.duration_months = m"
                                                    :class="[
                                                        'px-3 py-2.5 text-sm font-semibold rounded-xl border-2 transition-all',
                                                        subForm.duration_months === m
                                                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400'
                                                            : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'
                                                    ]"
                                                >
                                                    {{ m }} oy
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex gap-3">
                                    <button
                                        @click="showSubscriptionModal = false"
                                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        @click="assignSubscription"
                                        :disabled="!subForm.plan_id || isSubmitting"
                                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                                    >
                                        <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            Tayinlanmoqda...
                                        </span>
                                        <span v-else>Tayinlash</span>
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </Transition>
            </Teleport>

            <!-- Delete Modal -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition-opacity duration-150"
                    leave-active-class="transition-opacity duration-100"
                    enter-from-class="opacity-0"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showDeleteModal" @click="showDeleteModal = false" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                        <Transition
                            enter-active-class="transition-all duration-150"
                            leave-active-class="transition-all duration-100"
                            enter-from-class="opacity-0 scale-95"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="showDeleteModal" @click.stop class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-5 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 bg-red-100 dark:bg-red-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                                        <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ t('admin.businesses.delete_business') }}</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ t('admin.businesses.delete_confirm') }} <strong class="text-gray-900 dark:text-white">{{ businessToDelete?.name }}</strong>?
                                        </p>
                                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">
                                            {{ t('admin.businesses.delete_warning') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-5">
                                    <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button @click="deleteBusiness" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                                        {{ t('common.delete') }}
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from '@/i18n'
import {
    BuildingOffice2Icon,
    MagnifyingGlassIcon,
    EyeIcon,
    CreditCardIcon,
    TrashIcon,
    PauseCircleIcon,
    PlayCircleIcon,
    ClockIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    BuildingOfficeIcon,
    CheckCircleIcon,
    NoSymbolIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline'

const { t } = useI18n()

const props = defineProps({
    businesses: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total: 0, active: 0, inactive: 0, this_month: 0 }) },
    plans: { type: Array, default: () => [] },
})

const statCards = computed(() => [
    { value: props.stats.total, label: t('admin.businesses.total_businesses'), icon: BuildingOfficeIcon, iconBg: 'bg-blue-50 dark:bg-blue-500/10', iconColor: 'text-blue-600 dark:text-blue-400' },
    { value: props.stats.active, label: t('admin.businesses.active_businesses'), icon: CheckCircleIcon, iconBg: 'bg-emerald-50 dark:bg-emerald-500/10', iconColor: 'text-emerald-600 dark:text-emerald-400' },
    { value: props.stats.inactive, label: t('admin.businesses.inactive_businesses'), icon: NoSymbolIcon, iconBg: 'bg-amber-50 dark:bg-amber-500/10', iconColor: 'text-amber-600 dark:text-amber-400' },
    { value: props.stats.this_month, label: t('admin.businesses.new_this_month'), icon: PlusIcon, iconBg: 'bg-violet-50 dark:bg-violet-500/10', iconColor: 'text-violet-600 dark:text-violet-400' },
])

const filters = ref({ search: '', status: '', sort: 'newest' })
const showDeleteModal = ref(false)
const businessToDelete = ref(null)
const showSubscriptionModal = ref(false)
const subscriptionTarget = ref(null)
const isSubmitting = ref(false)
const subForm = ref({ plan_id: null, billing_cycle: 'monthly', duration_months: 1 })

const filteredBusinesses = computed(() => {
    let result = [...props.businesses]

    if (filters.value.search) {
        const q = filters.value.search.toLowerCase()
        result = result.filter(b =>
            b.name?.toLowerCase().includes(q) ||
            b.owner_name?.toLowerCase().includes(q) ||
            b.owner_email?.toLowerCase().includes(q)
        )
    }
    if (filters.value.status) {
        result = result.filter(b => b.status === filters.value.status)
    }

    const sortMap = {
        newest: (a, b) => new Date(b.created_at_raw) - new Date(a.created_at_raw),
        oldest: (a, b) => new Date(a.created_at_raw) - new Date(b.created_at_raw),
        name: (a, b) => (a.name || '').localeCompare(b.name || ''),
        customers: (a, b) => b.customers_count - a.customers_count,
    }
    if (sortMap[filters.value.sort]) result.sort(sortMap[filters.value.sort])

    return result
})

const getStatusBadgeClass = (status) =>
    status === 'active'
        ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-500/20'
        : 'bg-gray-50 dark:bg-gray-500/10 text-gray-600 dark:text-gray-400 border border-gray-200/50 dark:border-gray-500/20'

const formatCurrency = (amount) => amount ? new Intl.NumberFormat('uz-UZ').format(amount) : '0'

const viewBusiness = (id) => router.visit(`/dashboard/businesses/${id}`)

const toggleStatus = (business) => {
    router.put(`/dashboard/businesses/${business.id}/status`, {
        status: business.status === 'active' ? 'inactive' : 'active'
    }, { preserveScroll: true })
}

const openSubscriptionModal = (business) => {
    subscriptionTarget.value = business
    subForm.value = { plan_id: business.subscription?.plan_id || null, billing_cycle: 'monthly', duration_months: 1 }
    showSubscriptionModal.value = true
}

const assignSubscription = () => {
    if (!subscriptionTarget.value || !subForm.value.plan_id) return
    isSubmitting.value = true
    router.post(`/dashboard/businesses/${subscriptionTarget.value.id}/assign-subscription`, subForm.value, {
        preserveScroll: true,
        onSuccess: () => { showSubscriptionModal.value = false; subscriptionTarget.value = null; isSubmitting.value = false },
        onError: () => { isSubmitting.value = false },
    })
}

const confirmDelete = (business) => { businessToDelete.value = business; showDeleteModal.value = true }

const deleteBusiness = () => {
    if (!businessToDelete.value) return
    router.delete(`/dashboard/businesses/${businessToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false; businessToDelete.value = null },
    })
}
</script>
