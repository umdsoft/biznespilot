<template>
    <div style="padding-bottom: 100px">
        <BackButton />

        <!-- Header -->
        <div class="sticky top-0 z-20 header-blur">
            <div style="padding: 12px 16px">
                <h1 style="font-size: 22px; font-weight: 700; color: var(--tg-theme-text-color)">
                    📝 Buyurtma berish
                </h1>
            </div>
        </div>

        <div style="padding: 0 16px">
            <!-- Contact Info -->
            <div style="margin-bottom: 24px">
                <div class="flex items-center" style="gap: 8px; margin-bottom: 14px">
                    <svg style="width: 20px; height: 20px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    <h2 class="section-title">Aloqa ma'lumotlari</h2>
                </div>

                <div class="form-group">
                    <label class="form-label">Ism *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Ismingiz"
                        class="form-input"
                        :class="{ 'is-error': errors.name }"
                    />
                    <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Telefon *</label>
                    <input
                        v-model="form.phone"
                        type="tel"
                        placeholder="+998 90 123 45 67"
                        class="form-input"
                        :class="{ 'is-error': errors.phone }"
                    />
                    <p v-if="errors.phone" class="form-error">{{ errors.phone }}</p>
                </div>
            </div>

            <!-- Delivery Address -->
            <div style="margin-bottom: 24px">
                <div class="flex items-center" style="gap: 8px; margin-bottom: 14px">
                    <svg style="width: 20px; height: 20px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                    </svg>
                    <h2 class="section-title">Yetkazib berish manzili</h2>
                </div>

                <!-- Selected address display -->
                <div v-if="selectedAddress" class="selected-address-card" @click="openAddressSheet">
                    <div style="flex: 1; min-width: 0">
                        <div class="flex items-center" style="gap: 6px; margin-bottom: 3px">
                            <span style="font-size: 14px; font-weight: 600; color: var(--tg-theme-text-color)">
                                {{ selectedAddress.label || 'Manzil' }}
                            </span>
                            <span v-if="selectedAddress.latitude" style="font-size: 12px; color: var(--color-success)">📍</span>
                        </div>
                        <p class="line-clamp-2" style="font-size: 13px; color: var(--tg-theme-hint-color); line-height: 1.4">
                            {{ selectedAddress.full_address || formatSelectedAddress(selectedAddress) }}
                        </p>
                        <p v-if="selectedAddress.instructions" style="font-size: 12px; color: var(--tg-theme-hint-color); margin-top: 2px; font-style: italic">
                            Mo'ljal: {{ selectedAddress.instructions }}
                        </p>
                    </div>
                    <span style="font-size: 13px; font-weight: 500; color: var(--tg-theme-button-color); flex-shrink: 0">
                        O'zgartirish
                    </span>
                </div>

                <!-- No address selected -->
                <button
                    v-else
                    @click="openAddressSheet"
                    class="add-address-trigger tap-active"
                    :class="{ 'is-error': errors.address }"
                >
                    <svg style="width: 20px; height: 20px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    <span>Manzil tanlash yoki qo'shish</span>
                </button>
                <p v-if="errors.address" class="form-error">{{ errors.address }}</p>
            </div>

            <!-- Delivery Type -->
            <div style="margin-bottom: 24px">
                <div class="flex items-center" style="gap: 8px; margin-bottom: 14px">
                    <svg style="width: 20px; height: 20px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                    </svg>
                    <h2 class="section-title">Yetkazib berish usuli</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px">
                    <button
                        v-for="dt in deliveryTypes"
                        :key="dt.value"
                        @click="selectDeliveryType(dt.value)"
                        class="radio-card tap-active"
                        :class="{ 'is-selected': form.delivery_type === dt.value }"
                    >
                        <div
                            class="radio-circle"
                            :class="{ 'is-selected': form.delivery_type === dt.value }"
                        >
                            <div v-if="form.delivery_type === dt.value" style="width: 8px; height: 8px; border-radius: 50%; background: #fff"></div>
                        </div>
                        <div style="flex: 1; margin-left: 10px">
                            <p style="font-size: 14px; font-weight: 500; color: var(--tg-theme-text-color)">{{ dt.icon }} {{ dt.label }}</p>
                            <p style="font-size: 13px; color: var(--tg-theme-hint-color); margin-top: 2px">{{ dt.description }}</p>
                        </div>
                        <span
                            v-if="dt.price > 0"
                            style="font-size: 14px; font-weight: 600; color: var(--tg-theme-text-color); flex-shrink: 0"
                        >
                            {{ formatPrice(dt.price) }}
                        </span>
                        <span v-else style="font-size: 13px; font-weight: 600; color: var(--color-success); flex-shrink: 0">Bepul</span>
                    </button>
                </div>
            </div>

            <!-- Payment Method -->
            <div style="margin-bottom: 24px">
                <div class="flex items-center" style="gap: 8px; margin-bottom: 14px">
                    <svg style="width: 20px; height: 20px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                    </svg>
                    <h2 class="section-title">To'lov usuli</h2>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px">
                    <button
                        v-for="pm in paymentMethods"
                        :key="pm.value"
                        @click="selectPaymentMethod(pm.value)"
                        class="payment-card tap-active"
                        :class="{ 'is-selected': form.payment_method === pm.value }"
                    >
                        <span class="payment-icon">{{ pm.icon }}</span>
                        <span class="payment-label" :style="{ color: form.payment_method === pm.value ? 'var(--tg-theme-button-color)' : 'var(--tg-theme-text-color)' }">{{ pm.label }}</span>
                    </button>
                </div>
            </div>

            <!-- Comment -->
            <div style="margin-bottom: 24px">
                <div class="flex items-center" style="gap: 8px; margin-bottom: 14px">
                    <svg style="width: 20px; height: 20px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                    </svg>
                    <h2 class="section-title">Izoh</h2>
                </div>
                <textarea
                    v-model="form.comment"
                    placeholder="Buyurtma uchun izoh (ixtiyoriy)"
                    rows="2"
                    class="form-textarea"
                ></textarea>
            </div>

            <!-- Promo code -->
            <div style="margin-bottom: 24px">
                <div class="flex items-center" style="gap: 8px; margin-bottom: 14px">
                    <svg style="width: 20px; height: 20px; color: var(--tg-theme-hint-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
                    </svg>
                    <h2 class="section-title">Promokod</h2>
                </div>
                <div class="flex items-center" style="gap: 0; border-radius: 12px; overflow: hidden; border: 1.5px solid var(--color-border)">
                    <div class="flex items-center flex-1" style="gap: 10px; padding: 0 14px; height: 44px">
                        <input
                            v-model="promoInput"
                            type="text"
                            placeholder="Promokod kiriting"
                            class="w-full bg-transparent outline-none"
                            style="font-size: 14px; color: var(--tg-theme-text-color); height: 100%"
                            :disabled="cart.promoApplied"
                        />
                    </div>
                    <button
                        v-if="!cart.promoApplied"
                        @click="applyPromo"
                        :disabled="!promoInput.trim() || cart.loading"
                        class="shrink-0 flex items-center justify-center gap-2"
                        :style="{ height: '44px', padding: '0 16px', backgroundColor: 'var(--tg-theme-button-color)', color: 'var(--tg-theme-button-text-color)', fontSize: '14px', fontWeight: '600', opacity: !promoInput.trim() || cart.loading ? 0.4 : 1 }"
                    >
                        <svg v-if="cart.loading" style="width: 14px; height: 14px; flex-shrink: 0" class="animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span>{{ cart.loading ? 'Tekshirilmoqda...' : 'Qo\'llash' }}</span>
                    </button>
                    <button
                        v-else
                        @click="removePromo"
                        class="shrink-0 flex items-center justify-center"
                        style="height: 44px; padding: 0 16px; font-size: 13px; font-weight: 500; color: var(--color-error)"
                    >
                        Bekor
                    </button>
                </div>
                <p v-if="cart.promoError" class="form-error" style="padding-left: 4px">{{ cart.promoError }}</p>
                <div
                    v-if="cart.promoApplied"
                    class="flex items-center"
                    style="margin-top: 8px; padding: 10px 12px; border-radius: 8px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); font-size: 13px; color: var(--color-success); font-weight: 500; gap: 8px"
                >
                    <svg style="width: 18px; height: 18px; flex-shrink: 0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Promokod qo'llandi: <strong>-{{ formatPrice(cart.discountAmount) }}</strong></span>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h2 class="section-title" style="margin-bottom: 8px">Buyurtma xulosasi</h2>

                <!-- Product list -->
                <div v-if="cart.items.length" style="margin-bottom: 12px">
                    <div
                        v-for="item in cart.items"
                        :key="item.product_id"
                        class="flex items-center"
                        style="gap: 10px; padding: 8px 0"
                    >
                        <div class="shrink-0 overflow-hidden" style="width: 44px; height: 44px; border-radius: 8px; background-color: var(--tg-theme-bg-color)">
                            <img v-if="item.image" :src="item.image" class="h-full w-full object-cover" />
                            <span v-else class="flex h-full w-full items-center justify-center" style="font-size: 14px">📦</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="line-clamp-1" style="font-size: 13px; font-weight: 500; color: var(--tg-theme-text-color)">{{ item.name }}</p>
                            <p style="font-size: 12px; color: var(--tg-theme-hint-color)">{{ item.quantity }} x {{ formatPrice(item.sale_price || item.price) }}</p>
                        </div>
                        <span class="shrink-0" style="font-size: 13px; font-weight: 600; color: var(--tg-theme-text-color)">
                            {{ formatPrice((item.sale_price || item.price) * item.quantity) }}
                        </span>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--color-divider); padding-top: 12px">
                    <div class="order-summary-row" style="padding: 6px 0">
                        <span class="label">Mahsulotlar ({{ cart.itemCount }})</span>
                        <span class="value">{{ formatPrice(cart.subtotal) }}</span>
                    </div>
                    <div v-if="cart.promoApplied" class="order-summary-row" style="padding: 6px 0">
                        <span class="label">Chegirma</span>
                        <span style="color: var(--color-success); font-weight: 500">-{{ formatPrice(cart.discountAmount) }}</span>
                    </div>
                    <div class="order-summary-row" style="padding: 6px 0">
                        <span class="label">Yetkazib berish</span>
                        <span class="value">{{ deliveryPrice > 0 ? formatPrice(deliveryPrice) : 'Bepul' }}</span>
                    </div>
                    <div class="order-summary-total">
                        <span class="label">Jami</span>
                        <span class="value">{{ formatPrice(cart.total + deliveryPrice) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit button -->
        <div class="sticky-bottom-bar">
            <button
                @click="submitOrder"
                :disabled="submitting"
                class="btn-primary"
                :style="{ opacity: submitting ? 0.7 : 1 }"
            >
                <span v-if="submitting" class="flex items-center justify-center gap-2">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ submitStep }}
                </span>
                <span v-else class="flex items-center justify-center" style="gap: 8px">
                    Tasdiqlash — {{ formatPrice(cart.total + deliveryPrice) }}
                </span>
            </button>
        </div>

        <!-- Address Sheet -->
        <AddressSheet
            v-model="showAddressSheet"
            :current-address-id="selectedAddress?.id"
            @select="onAddressSelected"
        />
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useUserStore } from '../stores/user'
import { useOrderStore } from '../stores/order'
import { useTelegram } from '../composables/useTelegram'
import { useToast } from '../composables/useToast'
import BackButton from '../components/BackButton.vue'
import AddressSheet from '../components/AddressSheet.vue'
import { formatPrice } from '../utils/formatters'

const router = useRouter()
const cart = useCartStore()
const userStore = useUserStore()
const orderStore = useOrderStore()
const { hapticImpact, hapticNotification } = useTelegram()
const toast = useToast()

const showAddressSheet = ref(false)
const selectedAddress = ref(null)
const errors = reactive({})
const promoInput = ref(cart.promoCode || '')
const submitting = ref(false)
const submitStep = ref('')

const form = reactive({
    name: '',
    phone: '',
    delivery_type: 'delivery',
    payment_method: 'cash',
    comment: '',
})

const deliveryTypes = [
    { value: 'delivery', label: 'Yetkazib berish', description: 'Manzilingizga yetkazamiz', price: 15000, icon: '🚚' },
    { value: 'pickup', label: 'Olib ketish', description: "Do'kondan o'zingiz olib ketasiz", price: 0, icon: '🏪' },
]

const paymentMethods = [
    { value: 'cash', label: 'Naqd', icon: '💵' },
    { value: 'card', label: 'Karta', icon: '💳' },
    { value: 'click', label: 'Click', icon: '📱' },
    { value: 'payme', label: 'Payme', icon: '📱' },
]

const deliveryPrice = computed(() => {
    const dt = deliveryTypes.find((d) => d.value === form.delivery_type)
    return dt?.price || 0
})

function openAddressSheet() {
    showAddressSheet.value = true
    hapticImpact('light')
}

function onAddressSelected(addr) {
    selectedAddress.value = addr
    hapticImpact('medium')
}

function selectDeliveryType(type) {
    form.delivery_type = type
    hapticImpact('light')
}

function selectPaymentMethod(method) {
    form.payment_method = method
    hapticImpact('light')
}

function applyPromo() {
    cart.applyPromo(promoInput.value)
}

function removePromo() {
    cart.clearPromo()
    promoInput.value = ''
}

function formatSelectedAddress(addr) {
    return [addr.city, addr.district, addr.street].filter(Boolean).join(', ')
}

function validate() {
    const errs = {}
    if (!form.name.trim()) errs.name = 'Ism kiritilishi shart'
    if (!form.phone.trim()) errs.phone = 'Telefon kiritilishi shart'
    if (form.delivery_type === 'delivery' && !selectedAddress.value) {
        errs.address = 'Manzil tanlang'
    }
    Object.keys(errors).forEach((key) => delete errors[key])
    Object.assign(errors, errs)
    return Object.keys(errs).length === 0
}

async function submitOrder() {
    if (!validate()) {
        hapticNotification('error')
        window.scrollTo({ top: 0, behavior: 'smooth' })
        toast.error('Ma\'lumotlarni to\'liq kiriting')
        return
    }

    submitting.value = true
    hapticImpact('medium')

    try {
        // Step 1: Sync cart to server
        submitStep.value = 'Savat tekshirilmoqda...'
        const synced = await cart.syncToServer()
        if (!synced) {
            toast.error('Savatni sinxronlashda xatolik')
            hapticNotification('error')
            return
        }

        // Step 2: Build order data
        submitStep.value = 'Buyurtma yaratilmoqda...'
        const addr = selectedAddress.value
        const deliveryAddress = addr ? {
            street: addr.street || addr.full_address || '',
            city: addr.city || '',
            district: addr.district || '',
            apartment: addr.apartment || '',
            entrance: addr.entrance || '',
            floor: addr.floor || '',
            comment: addr.instructions || '',
            latitude: addr.latitude || null,
            longitude: addr.longitude || null,
        } : { street: '', city: '' }

        const orderData = {
            customer_name: form.name.trim(),
            customer_phone: form.phone.trim(),
            delivery_address: deliveryAddress,
            delivery_type: form.delivery_type,
            payment_method: form.payment_method,
            notes: form.comment.trim() || null,
            promo_code: cart.promoApplied ? cart.promoCode : null,
        }

        // Step 3: Create order
        const result = await orderStore.createOrder(orderData)

        if (result) {
            hapticNotification('success')
            toast.success('Buyurtma muvaffaqiyatli yaratildi!')
            cart.clearCart()

            // Kichik kutish — toast ko'rinsin
            await new Promise((r) => setTimeout(r, 600))

            if (result.payment_url && ['click', 'payme'].includes(form.payment_method)) {
                router.push({
                    name: 'payment',
                    query: {
                        order_number: result.order?.order_number || '',
                        payment_url: result.payment_url,
                    },
                })
            } else {
                router.push({
                    name: 'order-detail',
                    params: { number: result.order?.order_number || '' },
                })
            }
        } else {
            toast.error(orderStore.error || 'Buyurtma yaratishda xatolik')
            hapticNotification('error')
        }
    } catch (err) {
        toast.error('Kutilmagan xatolik yuz berdi')
        hapticNotification('error')
        console.error('[MiniApp] Submit order error:', err)
    } finally {
        submitting.value = false
        submitStep.value = ''
    }
}

onMounted(async () => {
    if (cart.isEmpty) {
        router.replace({ name: 'home' })
        return
    }
    form.name = userStore.fullName || ''
    form.phone = userStore.phone || ''

    // Fetch profile to get addresses
    await userStore.fetchProfile()

    // Auto-select default address
    if (userStore.defaultAddress) {
        selectedAddress.value = userStore.defaultAddress
    }
})
</script>

<style scoped>
.selected-address-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: var(--radius-md);
    border: 1.5px solid var(--tg-theme-button-color);
    background: color-mix(in srgb, var(--tg-theme-button-color) 6%, var(--tg-theme-bg-color));
    cursor: pointer;
    transition: all 0.15s ease;
}

.add-address-trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    height: 52px;
    border-radius: var(--radius-md);
    border: 1.5px dashed var(--color-border);
    background: transparent;
    font-size: 14px;
    font-weight: 500;
    color: var(--tg-theme-button-color);
    transition: all 0.15s ease;
}
.add-address-trigger.is-error {
    border-color: var(--color-error);
    color: var(--color-error);
}
</style>
