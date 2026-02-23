<template>
    <div class="pb-28">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-10 px-4 py-3" style="background-color: var(--tg-theme-bg-color)">
            <h1 class="text-lg font-bold" style="color: var(--tg-theme-text-color)">
                Buyurtma berish
            </h1>
        </div>

        <div class="px-4 space-y-4">
            <!-- Contact Info -->
            <div class="rounded-2xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                <h2 class="mb-3 text-[13px] font-bold" style="color: var(--tg-theme-text-color)">
                    Aloqa ma'lumotlari
                </h2>

                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs" style="color: var(--tg-theme-hint-color)">Ism *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Ismingiz"
                            class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                            style="background-color: var(--tg-theme-bg-color); color: var(--tg-theme-text-color)"
                        />
                        <p v-if="errors.name" class="mt-0.5 text-xs text-red-500">{{ errors.name }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs" style="color: var(--tg-theme-hint-color)">Telefon *</label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            placeholder="+998 90 123 45 67"
                            class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                            style="background-color: var(--tg-theme-bg-color); color: var(--tg-theme-text-color)"
                        />
                        <p v-if="errors.phone" class="mt-0.5 text-xs text-red-500">{{ errors.phone }}</p>
                    </div>
                </div>
            </div>

            <!-- Delivery Address -->
            <div class="rounded-2xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                <h2 class="mb-3 text-[13px] font-bold" style="color: var(--tg-theme-text-color)">
                    Yetkazib berish manzili
                </h2>

                <!-- Saved addresses -->
                <div v-if="userStore.savedAddresses.length" class="mb-3 space-y-2">
                    <button
                        v-for="addr in userStore.savedAddresses"
                        :key="addr.id"
                        @click="selectAddress(addr)"
                        class="w-full rounded-lg p-3 text-left text-sm transition-all"
                        :class="selectedAddressId === addr.id ? 'border-2' : 'border'"
                        :style="selectedAddressId === addr.id
                            ? { borderColor: 'var(--tg-theme-button-color)', backgroundColor: 'var(--tg-theme-bg-color)' }
                            : { borderColor: 'transparent', backgroundColor: 'var(--tg-theme-bg-color)' }"
                    >
                        <p class="font-medium" style="color: var(--tg-theme-text-color)">{{ addr.label || 'Manzil' }}</p>
                        <p class="mt-0.5 text-xs" style="color: var(--tg-theme-hint-color)">{{ addr.address }}</p>
                    </button>
                </div>

                <!-- New address form -->
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs" style="color: var(--tg-theme-hint-color)">Manzil *</label>
                        <textarea
                            v-model="form.address"
                            placeholder="Shahar, ko'cha, uy, kvartira"
                            rows="2"
                            class="w-full resize-none rounded-lg px-3 py-2.5 text-sm outline-none"
                            style="background-color: var(--tg-theme-bg-color); color: var(--tg-theme-text-color)"
                        />
                        <p v-if="errors.address" class="mt-0.5 text-xs text-red-500">{{ errors.address }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs" style="color: var(--tg-theme-hint-color)">Mo'ljal</label>
                        <input
                            v-model="form.landmark"
                            type="text"
                            placeholder="Yaqin atrofdagi mo'ljal"
                            class="w-full rounded-lg px-3 py-2.5 text-sm outline-none"
                            style="background-color: var(--tg-theme-bg-color); color: var(--tg-theme-text-color)"
                        />
                    </div>
                </div>
            </div>

            <!-- Delivery Type -->
            <div class="rounded-2xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                <h2 class="mb-3 text-[13px] font-bold" style="color: var(--tg-theme-text-color)">
                    Yetkazib berish usuli
                </h2>
                <div class="space-y-2">
                    <button
                        v-for="dt in deliveryTypes"
                        :key="dt.value"
                        @click="selectDeliveryType(dt.value)"
                        class="flex w-full items-center gap-3 rounded-lg p-3"
                        :style="{ backgroundColor: 'var(--tg-theme-bg-color)' }"
                    >
                        <div
                            class="flex h-5 w-5 items-center justify-center rounded-full border-2"
                            :style="form.delivery_type === dt.value
                                ? { borderColor: 'var(--tg-theme-button-color)', backgroundColor: 'var(--tg-theme-button-color)' }
                                : { borderColor: 'var(--tg-theme-hint-color)' }"
                        >
                            <div v-if="form.delivery_type === dt.value" class="h-2 w-2 rounded-full bg-white" />
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ dt.label }}</p>
                            <p class="text-xs" style="color: var(--tg-theme-hint-color)">{{ dt.description }}</p>
                        </div>
                        <span
                            v-if="dt.price > 0"
                            class="ml-auto text-sm font-medium"
                            style="color: var(--tg-theme-text-color)"
                        >
                            {{ formatPrice(dt.price) }}
                        </span>
                        <span v-else class="ml-auto text-xs text-green-600">Bepul</span>
                    </button>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="rounded-2xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                <h2 class="mb-3 text-[13px] font-bold" style="color: var(--tg-theme-text-color)">
                    To'lov usuli
                </h2>
                <div class="space-y-2">
                    <button
                        v-for="pm in paymentMethods"
                        :key="pm.value"
                        @click="selectPaymentMethod(pm.value)"
                        class="flex w-full items-center gap-3 rounded-lg p-3"
                        :style="{ backgroundColor: 'var(--tg-theme-bg-color)' }"
                    >
                        <div
                            class="flex h-5 w-5 items-center justify-center rounded-full border-2"
                            :style="form.payment_method === pm.value
                                ? { borderColor: 'var(--tg-theme-button-color)', backgroundColor: 'var(--tg-theme-button-color)' }
                                : { borderColor: 'var(--tg-theme-hint-color)' }"
                        >
                            <div v-if="form.payment_method === pm.value" class="h-2 w-2 rounded-full bg-white" />
                        </div>
                        <span class="text-lg">{{ pm.icon }}</span>
                        <p class="text-sm font-medium" style="color: var(--tg-theme-text-color)">{{ pm.label }}</p>
                    </button>
                </div>
            </div>

            <!-- Comment -->
            <div class="rounded-2xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                <h2 class="mb-3 text-[13px] font-bold" style="color: var(--tg-theme-text-color)">
                    Izoh
                </h2>
                <textarea
                    v-model="form.comment"
                    placeholder="Buyurtma uchun izoh (ixtiyoriy)"
                    rows="2"
                    class="w-full resize-none rounded-lg px-3 py-2.5 text-sm outline-none"
                    style="background-color: var(--tg-theme-bg-color); color: var(--tg-theme-text-color)"
                />
            </div>

            <!-- Order Summary -->
            <div class="rounded-2xl p-4" style="background-color: var(--tg-theme-secondary-bg-color)">
                <h2 class="mb-3 text-[13px] font-bold" style="color: var(--tg-theme-text-color)">
                    Buyurtma xulosasi
                </h2>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <span style="color: var(--tg-theme-hint-color)">Mahsulotlar ({{ cart.itemCount }})</span>
                        <span style="color: var(--tg-theme-text-color)">{{ formatPrice(cart.subtotal) }}</span>
                    </div>
                    <div v-if="cart.promoApplied" class="flex justify-between">
                        <span style="color: var(--tg-theme-hint-color)">Chegirma</span>
                        <span class="text-green-600">-{{ formatPrice(cart.discountAmount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--tg-theme-hint-color)">Yetkazib berish</span>
                        <span style="color: var(--tg-theme-text-color)">{{ deliveryPrice > 0 ? formatPrice(deliveryPrice) : 'Bepul' }}</span>
                    </div>
                    <div class="border-t pt-2 mt-2" style="border-color: var(--tg-theme-bg-color)">
                        <div class="flex justify-between">
                            <span class="font-semibold" style="color: var(--tg-theme-text-color)">Jami</span>
                            <span class="text-base font-bold" style="color: var(--tg-theme-text-color)">
                                {{ formatPrice(cart.total + deliveryPrice) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error message -->
        <div v-if="orderStore.error" class="mx-4 mt-3 rounded-lg bg-red-50 p-3 text-center text-sm text-red-600">
            {{ orderStore.error }}
        </div>

        <!-- Submit button -->
        <div
            class="fixed bottom-0 left-0 right-0 z-20 border-t px-4 py-3 safe-area-bottom"
            style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-secondary-bg-color)"
        >
            <button
                @click="submitOrder"
                :disabled="orderStore.creating"
                class="w-full rounded-xl py-3.5 text-center text-sm font-semibold disabled:opacity-60"
                style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color)"
            >
                <span v-if="orderStore.creating">Yuklanmoqda...</span>
                <span v-else>Buyurtma berish — {{ formatPrice(cart.total + deliveryPrice) }}</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useUserStore } from '../stores/user'
import { useOrderStore } from '../stores/order'
import { useTelegram } from '../composables/useTelegram'
import BackButton from '../components/BackButton.vue'
import { formatPrice } from '../utils/formatters'

const router = useRouter()
const cart = useCartStore()
const userStore = useUserStore()
const orderStore = useOrderStore()
const { hapticImpact, hapticNotification } = useTelegram()

const selectedAddressId = ref(null)
const errors = reactive({})

const form = reactive({
    name: '',
    phone: '',
    address: '',
    landmark: '',
    delivery_type: 'delivery',
    payment_method: 'cash',
    comment: '',
})

const deliveryTypes = [
    { value: 'delivery', label: 'Yetkazib berish', description: 'Manzilingizga yetkazamiz', price: 15000 },
    { value: 'pickup', label: 'Olib ketish', description: "Do'kondan o'zingiz olib ketasiz", price: 0 },
]

const paymentMethods = [
    { value: 'cash', label: 'Naqd pul', icon: '💵' },
    { value: 'card', label: 'Plastik karta', icon: '💳' },
    { value: 'click', label: 'Click', icon: '📱' },
    { value: 'payme', label: 'Payme', icon: '📱' },
]

const deliveryPrice = computed(() => {
    const dt = deliveryTypes.find((d) => d.value === form.delivery_type)
    return dt?.price || 0
})

function selectAddress(addr) {
    selectedAddressId.value = addr.id
    form.address = addr.address
    form.landmark = addr.landmark || ''
    hapticImpact('light')
}

function selectDeliveryType(type) {
    form.delivery_type = type
    hapticImpact('light')
}

function selectPaymentMethod(method) {
    form.payment_method = method
    hapticImpact('light')
}

function validate() {
    const errs = {}

    if (!form.name.trim()) errs.name = 'Ism kiritilishi shart'
    if (!form.phone.trim()) errs.phone = 'Telefon kiritilishi shart'
    if (form.delivery_type === 'delivery' && !form.address.trim()) {
        errs.address = 'Manzil kiritilishi shart'
    }

    Object.keys(errors).forEach((key) => delete errors[key])
    Object.assign(errors, errs)

    return Object.keys(errs).length === 0
}

async function submitOrder() {
    if (!validate()) {
        hapticNotification('error')
        return
    }

    hapticImpact('medium')

    const orderData = {
        ...cart.getCartPayload(),
        customer_name: form.name.trim(),
        customer_phone: form.phone.trim(),
        address: form.address.trim(),
        landmark: form.landmark.trim(),
        delivery_type: form.delivery_type,
        payment_method: form.payment_method,
        comment: form.comment.trim(),
    }

    const order = await orderStore.createOrder(orderData)

    if (order) {
        hapticNotification('success')

        if (order.payment_url && ['click', 'payme'].includes(form.payment_method)) {
            cart.clearCart()
            router.push({
                name: 'payment',
                query: {
                    order_number: order.order_number,
                    payment_url: order.payment_url,
                },
            })
        } else {
            cart.clearCart()
            router.push({
                name: 'order-detail',
                params: { number: order.order_number },
            })
        }
    } else {
        hapticNotification('error')
    }
}

onMounted(() => {
    if (cart.isEmpty) {
        router.replace({ name: 'home' })
        return
    }

    // Pre-fill from user profile
    form.name = userStore.fullName || ''
    form.phone = userStore.phone || ''
})
</script>
