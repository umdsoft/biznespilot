<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionRoot,
    TransitionChild,
} from '@headlessui/vue'
import {
    ChartBarIcon,
    PlusIcon,
    FireIcon,
    SunIcon,
    CloudIcon,
    SparklesIcon,
    XMarkIcon,
    PencilIcon,
    TrashIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    rules: Array,
    distribution: Object,
    hotLeads: Array,
    categories: Object,
    conditions: Object,
    scoringCategories: Object,
})

const showAddModal = ref(false)
const editingRule = ref(null)
const recalculating = ref(false)

const form = useForm({
    name: '',
    description: '',
    field: '',
    condition: 'not_null',
    value: '',
    value_type: 'string',
    points: 10,
    category: 'completeness',
    priority: 0,
})

const fieldOptions = [
    { value: 'phone', label: 'Telefon raqami' },
    { value: 'email', label: 'Email manzili' },
    { value: 'company', label: 'Kompaniya nomi' },
    { value: 'region', label: 'Viloyat' },
    { value: 'estimated_value', label: 'Taxminiy qiymat' },
    { value: 'source.code', label: 'Manba kodi' },
    { value: 'activities_count', label: 'Faoliyatlar soni' },
    { value: 'days_without_contact', label: 'Aloqasiz kunlar' },
    { value: 'lost_reason', label: 'Yo\'qotilgan sabab' },
]

const categoryIcons = {
    hot: FireIcon,
    warm: SunIcon,
    cool: SparklesIcon,
    cold: CloudIcon,
    frozen: SparklesIcon,
}

const openAddModal = () => {
    form.reset()
    editingRule.value = null
    showAddModal.value = true
}

const openEditModal = (rule) => {
    editingRule.value = rule
    form.name = rule.name
    form.description = rule.description || ''
    form.field = rule.field
    form.condition = rule.condition
    form.value = rule.value || ''
    form.value_type = rule.value_type
    form.points = rule.points
    form.category = rule.category
    form.priority = rule.priority
    showAddModal.value = true
}

const closeModal = () => {
    showAddModal.value = false
    editingRule.value = null
    form.reset()
}

const submitForm = () => {
    if (editingRule.value) {
        form.put(route('sales-head.lead-scoring.update', editingRule.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    } else {
        form.post(route('sales-head.lead-scoring.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    }
}

const toggleRule = (rule) => {
    router.post(route('sales-head.lead-scoring.toggle', rule.id), {}, {
        preserveScroll: true,
    })
}

const deleteRule = (rule) => {
    if (confirm(`"${rule.name}" qoidasini o'chirishni xohlaysizmi?`)) {
        router.delete(route('sales-head.lead-scoring.destroy', rule.id), {
            preserveScroll: true,
        })
    }
}

const recalculateAll = () => {
    if (confirm('Barcha leadlarni qayta baholashni xohlaysizmi? Bu biroz vaqt olishi mumkin.')) {
        recalculating.value = true
        router.post(route('sales-head.lead-scoring.recalculate-all'), {}, {
            preserveScroll: true,
            onFinish: () => {
                recalculating.value = false
            },
        })
    }
}

const resetToDefaults = () => {
    if (confirm('Barcha qoidalar o\'chiriladi va standart qoidalar tiklanadi. Davom etasizmi?')) {
        router.post(route('sales-head.lead-scoring.reset'), {}, {
            preserveScroll: true,
        })
    }
}

const totalLeads = computed(() => props.distribution?.total || 0)

const getCategoryStyle = (category) => {
    const styles = {
        hot: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        warm: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        cool: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        cold: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        frozen: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    }
    return styles[category] || styles.frozen
}

const getPointsStyle = (points) => {
    if (points > 0) return 'text-green-600 dark:text-green-400'
    if (points < 0) return 'text-red-600 dark:text-red-400'
    return 'text-gray-600 dark:text-gray-400'
}
</script>

<template>
    <SalesHeadLayout title="Lead Scoring">
        <Head title="Lead Scoring - Lidlar Ballari" />

        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Lead Scoring</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Lidlarni avtomatik baholash va kategoriyalash
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <button
                    @click="recalculateAll"
                    :disabled="recalculating"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                >
                    <ArrowPathIcon class="w-4 h-4 mr-2" :class="{ 'animate-spin': recalculating }" />
                    Qayta baholash
                </button>
                <button
                    @click="openAddModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700"
                >
                    <PlusIcon class="w-4 h-4 mr-2" />
                    Qoida qo'shish
                </button>
            </div>
        </div>

        <!-- Score Distribution Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div
                v-for="(info, key) in scoringCategories"
                :key="key"
                class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ info.name }}</p>
                        <p class="text-2xl font-bold mt-1" :style="{ color: info.color }">
                            {{ distribution?.categories?.[key]?.count || 0 }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ info.min }}-{{ info.max }} ball</p>
                    </div>
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center"
                        :style="{ backgroundColor: info.color + '20' }"
                    >
                        <component :is="categoryIcons[key]" class="w-6 h-6" :style="{ color: info.color }" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Rules List -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Scoring Qoidalari</h2>
                        <button
                            @click="resetToDefaults"
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                        >
                            Standartga tiklash
                        </button>
                    </div>

                    <div v-if="rules.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                        <ChartBarIcon class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
                        <p>Hozircha qoidalar yo'q</p>
                        <button @click="openAddModal" class="mt-2 text-indigo-600 hover:text-indigo-700">
                            Birinchi qoida qo'shing
                        </button>
                    </div>

                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="rule in rules"
                            :key="rule.id"
                            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ rule.name }}</h3>
                                        <span
                                            class="px-2 py-0.5 text-xs rounded-full"
                                            :class="getCategoryStyle(rule.category)"
                                        >
                                            {{ rule.category_info?.name || rule.category }}
                                        </span>
                                        <span
                                            class="px-2 py-0.5 text-xs font-bold rounded"
                                            :class="getPointsStyle(rule.points)"
                                        >
                                            {{ rule.points > 0 ? '+' : '' }}{{ rule.points }} ball
                                        </span>
                                    </div>
                                    <p v-if="rule.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ rule.description }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ rule.field }} {{ rule.condition_label }} {{ rule.value || '' }}
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2 ml-4">
                                    <button
                                        @click="toggleRule(rule)"
                                        class="p-1.5 rounded-lg transition-colors"
                                        :class="rule.is_active
                                            ? 'text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30'
                                            : 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                    >
                                        <CheckCircleIcon class="w-5 h-5" />
                                    </button>
                                    <button
                                        @click="openEditModal(rule)"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                    >
                                        <PencilIcon class="w-5 h-5" />
                                    </button>
                                    <button
                                        @click="deleteRule(rule)"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                    >
                                        <TrashIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hot Leads Sidebar -->
            <div class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center space-x-2">
                            <FireIcon class="w-5 h-5 text-red-500" />
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Issiq Lidlar</h2>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Top 10 yuqori balli lidlar</p>
                    </div>

                    <div v-if="hotLeads.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                        <p>Hozircha issiq lidlar yo'q</p>
                    </div>

                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="lead in hotLeads"
                            :key="lead.id"
                            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ lead.name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ lead.company || lead.phone }}</p>
                                </div>
                                <div class="text-right">
                                    <div
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-bold"
                                        :class="getCategoryStyle(lead.score_category)"
                                    >
                                        {{ lead.score }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Statistika</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Jami lidlar</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ totalLeads }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">O'rtacha ball</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ distribution?.average_score || 0 }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Faol qoidalar</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ rules.filter(r => r.is_active).length }} / {{ rules.length }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <TransitionRoot appear :show="showAddModal" as="template">
            <Dialog as="div" @close="closeModal" class="relative z-50">
                <TransitionChild
                    as="template"
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as="template"
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl transition-all">
                                <div class="flex items-center justify-between mb-6">
                                    <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ editingRule ? 'Qoidani tahrirlash' : 'Yangi qoida qo\'shish' }}
                                    </DialogTitle>
                                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <XMarkIcon class="w-5 h-5" />
                                    </button>
                                </div>

                                <form @submit.prevent="submitForm" class="space-y-4">
                                    <!-- Name -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Qoida nomi *
                                        </label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            required
                                            placeholder="Masalan: Telefon raqami mavjud"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Kategoriya *
                                        </label>
                                        <select
                                            v-model="form.category"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        >
                                            <option v-for="(info, key) in categories" :key="key" :value="key">
                                                {{ info.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Field -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Maydon *
                                        </label>
                                        <select
                                            v-model="form.field"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        >
                                            <option value="">Maydonni tanlang</option>
                                            <option v-for="opt in fieldOptions" :key="opt.value" :value="opt.value">
                                                {{ opt.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Condition -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Shart *
                                            </label>
                                            <select
                                                v-model="form.condition"
                                                required
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            >
                                                <option v-for="(label, key) in conditions" :key="key" :value="key">
                                                    {{ label }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Qiymat
                                            </label>
                                            <input
                                                v-model="form.value"
                                                type="text"
                                                placeholder="Shart qiymati"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                        </div>
                                    </div>

                                    <!-- Points -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Ball *
                                            </label>
                                            <input
                                                v-model.number="form.points"
                                                type="number"
                                                required
                                                min="-50"
                                                max="50"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            />
                                            <p class="text-xs text-gray-500 mt-1">Ijobiy yoki salbiy (-50 dan +50 gacha)</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Qiymat turi
                                            </label>
                                            <select
                                                v-model="form.value_type"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            >
                                                <option value="string">Matn</option>
                                                <option value="number">Raqam</option>
                                                <option value="boolean">Ha/Yo'q</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Tavsif
                                        </label>
                                        <textarea
                                            v-model="form.description"
                                            rows="2"
                                            placeholder="Qoida haqida qisqacha ma'lumot"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        ></textarea>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <button
                                            type="button"
                                            @click="closeModal"
                                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        >
                                            Bekor qilish
                                        </button>
                                        <button
                                            type="submit"
                                            :disabled="form.processing"
                                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                                        >
                                            {{ editingRule ? 'Saqlash' : 'Qo\'shish' }}
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
            </div>
        </div>
    </SalesHeadLayout>
</template>
