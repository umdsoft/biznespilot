<template>
    <div>
        <!-- Label chips -->
        <div style="margin-bottom: 16px">
            <p class="form-label">Manzil nomi</p>
            <div class="flex flex-wrap" style="gap: 8px">
                <button
                    v-for="l in labels"
                    :key="l"
                    @click="form.label = form.label === l ? '' : l"
                    class="address-chip tap-active"
                    :class="{ 'is-active': form.label === l }"
                >
                    {{ l }}
                </button>
            </div>
        </div>

        <!-- Region select -->
        <div class="form-group">
            <label class="form-label">Viloyat *</label>
            <select
                v-model="form.regionKey"
                class="form-input"
                :class="{ 'is-error': errors.city }"
            >
                <option value="" disabled>Viloyatni tanlang</option>
                <option v-for="r in regions" :key="r.key" :value="r.key">{{ r.name }}</option>
            </select>
            <p v-if="errors.city" class="form-error">{{ errors.city }}</p>
        </div>

        <!-- District select -->
        <div class="form-group">
            <label class="form-label">Tuman / Shahar *</label>
            <select
                v-model="form.districtKey"
                class="form-input"
                :class="{ 'is-error': errors.district }"
                :disabled="!form.regionKey || loadingDistricts"
            >
                <option value="" disabled>{{ loadingDistricts ? 'Yuklanmoqda...' : 'Tumanni tanlang' }}</option>
                <option v-for="d in districts" :key="d.key" :value="d.key">{{ d.name }}</option>
            </select>
            <p v-if="errors.district" class="form-error">{{ errors.district }}</p>
        </div>

        <!-- Street -->
        <div class="form-group">
            <label class="form-label">Ko'cha, uy raqami *</label>
            <input
                v-model="form.street"
                type="text"
                placeholder="Masalan: Navoiy ko'chasi, 42-uy"
                class="form-input"
                :class="{ 'is-error': errors.street }"
            />
            <p v-if="errors.street" class="form-error">{{ errors.street }}</p>
        </div>

        <!-- Apartment, Entrance, Floor (inline) -->
        <div class="flex" style="gap: 8px; margin-bottom: var(--space-lg)">
            <div style="flex: 1">
                <label class="form-label">Kvartira</label>
                <input v-model="form.apartment" type="text" placeholder="45" class="form-input" />
            </div>
            <div style="flex: 1">
                <label class="form-label">Podyezd</label>
                <input v-model="form.entrance" type="text" placeholder="2" class="form-input" />
            </div>
            <div style="flex: 1">
                <label class="form-label">Qavat</label>
                <input v-model="form.floor" type="text" placeholder="3" class="form-input" />
            </div>
        </div>

        <!-- Instructions/Landmark -->
        <div class="form-group">
            <label class="form-label">Mo'ljal</label>
            <input
                v-model="form.instructions"
                type="text"
                placeholder="Yaqin atrofdagi mo'ljal"
                class="form-input"
            />
        </div>

        <!-- Geolocation -->
        <div style="margin-bottom: 16px">
            <button
                @click="requestLocation"
                :disabled="locating"
                class="location-btn tap-active"
                :class="{ 'is-success': form.latitude }"
            >
                <svg v-if="!locating" style="width: 18px; height: 18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
                <svg v-else class="animate-spin" style="width: 18px; height: 18px" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span v-if="form.latitude">Lokatsiya belgilandi</span>
                <span v-else-if="locating">Aniqlanmoqda...</span>
                <span v-else>Lokatsiyani belgilash</span>
            </button>
            <p v-if="locationError" class="form-error" style="margin-top: 6px">{{ locationError }}</p>
        </div>

        <!-- Save as default -->
        <label class="flex items-center" style="gap: 10px; margin-bottom: 16px; cursor: pointer">
            <input
                v-model="form.is_default"
                type="checkbox"
                class="address-checkbox"
            />
            <span style="font-size: 14px; color: var(--tg-theme-text-color)">Asosiy manzil qilish</span>
        </label>

        <!-- Save error -->
        <p v-if="saveError" class="form-error" style="text-align: center; margin-bottom: 12px">{{ saveError }}</p>

        <!-- Save button -->
        <button
            @click="saveAddress"
            :disabled="saving"
            class="btn-primary"
        >
            <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span v-else>Saqlash</span>
        </button>
    </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useUserStore } from '../stores/user'
import { useTelegram } from '../composables/useTelegram'

const emit = defineEmits(['saved'])
const userStore = useUserStore()
const { hapticImpact, hapticNotification } = useTelegram()

const labels = ['Uy', 'Ish', 'Boshqa']
const regions = ref([])
const districts = ref([])
const loadingDistricts = ref(false)
const locating = ref(false)
const locationError = ref('')
const saving = ref(false)
const saveError = ref('')
const errors = reactive({})

const form = reactive({
    label: '',
    regionKey: '',
    districtKey: '',
    city: '',
    district: '',
    street: '',
    apartment: '',
    entrance: '',
    floor: '',
    instructions: '',
    latitude: null,
    longitude: null,
    is_default: false,
})

watch(() => form.regionKey, async (key) => {
    form.districtKey = ''
    form.district = ''
    districts.value = []

    const region = regions.value.find(r => r.key === key)
    form.city = region?.name || ''

    if (key) {
        loadingDistricts.value = true
        districts.value = await userStore.fetchDistricts(key)
        loadingDistricts.value = false
    }
})

watch(() => form.districtKey, (key) => {
    const dist = districts.value.find(d => d.key === key)
    form.district = dist?.name || ''
})

function requestLocation() {
    if (!navigator.geolocation) {
        locationError.value = 'Brauzeringiz lokatsiyani qo\'llab-quvvatlamaydi'
        return
    }

    locating.value = true
    locationError.value = ''
    hapticImpact('light')

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude
            form.longitude = position.coords.longitude
            locating.value = false
            hapticNotification('success')
        },
        (err) => {
            locating.value = false
            if (err.code === 1) {
                locationError.value = 'Lokatsiya ruxsati berilmadi'
            } else {
                locationError.value = 'Lokatsiyani aniqlab bo\'lmadi'
            }
            hapticNotification('error')
        },
        { enableHighAccuracy: true, timeout: 10000 }
    )
}

function validate() {
    const errs = {}
    if (!form.city) errs.city = 'Viloyatni tanlang'
    if (!form.district) errs.district = 'Tumanni tanlang'
    if (!form.street.trim()) errs.street = 'Ko\'cha va uy raqamini kiriting'
    Object.keys(errors).forEach(k => delete errors[k])
    Object.assign(errors, errs)
    return Object.keys(errs).length === 0
}

async function saveAddress() {
    if (!validate()) {
        hapticNotification('error')
        return
    }

    saving.value = true
    saveError.value = ''
    hapticImpact('medium')

    const result = await userStore.saveAddress({
        label: form.label || null,
        city: form.city,
        district: form.district,
        street: form.street.trim(),
        apartment: form.apartment.trim() || null,
        entrance: form.entrance.trim() || null,
        floor: form.floor.trim() || null,
        latitude: form.latitude,
        longitude: form.longitude,
        instructions: form.instructions.trim() || null,
        is_default: form.is_default,
    })

    saving.value = false

    if (result.success) {
        hapticNotification('success')
        emit('saved')
    } else {
        saveError.value = result.error
        hapticNotification('error')
    }
}

onMounted(async () => {
    regions.value = await userStore.fetchRegions()
})
</script>

<style scoped>
.address-chip {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    border: 1.5px solid var(--color-border);
    background: var(--tg-theme-bg-color);
    color: var(--tg-theme-text-color);
    transition: all 0.15s ease;
}
.address-chip.is-active {
    border-color: var(--tg-theme-button-color);
    background: color-mix(in srgb, var(--tg-theme-button-color) 10%, var(--tg-theme-bg-color));
    color: var(--tg-theme-button-color);
}

.location-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    height: 44px;
    border-radius: var(--radius-md);
    border: 1.5px dashed var(--color-border);
    background: var(--tg-theme-bg-color);
    font-size: 14px;
    font-weight: 500;
    color: var(--tg-theme-hint-color);
    transition: all 0.15s ease;
}
.location-btn.is-success {
    border-color: var(--color-success);
    border-style: solid;
    color: var(--color-success);
    background: rgba(16, 185, 129, 0.06);
}

.address-checkbox {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    accent-color: var(--tg-theme-button-color);
}
</style>
