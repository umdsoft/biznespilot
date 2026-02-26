<template>
    <div class="pb-nav">
        <!-- Header -->
        <div class="sticky top-0 z-20 header-blur">
            <div style="padding: 12px 16px; display: flex; align-items: center; gap: 12px">
                <button @click="goBack" class="tap-active" style="padding: 4px">
                    <svg style="width: 24px; height: 24px; color: var(--tg-theme-text-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </button>
                <h1 style="font-size: 18px; font-weight: 700; color: var(--tg-theme-text-color)">
                    {{ storeInfo.name }}
                </h1>
            </div>
        </div>

        <div style="padding: 0 16px 24px">
            <!-- Store Card -->
            <div style="text-align: center; padding: 24px 0 20px">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 50%; overflow: hidden; margin-bottom: 12px"
                    :style="{ background: storeInfo.logo ? 'transparent' : 'var(--tg-theme-button-color)' }"
                >
                    <img v-if="storeInfo.logo" :src="storeInfo.logo" :alt="storeInfo.name" style="width: 100%; height: 100%; object-fit: cover" />
                    <svg v-else style="width: 32px; height: 32px; color: var(--tg-theme-button-text-color)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72"/>
                    </svg>
                </div>
                <h2 style="font-size: 20px; font-weight: 700; color: var(--tg-theme-text-color)">{{ storeInfo.name }}</h2>
                <p v-if="storeInfo.description" style="font-size: 14px; color: var(--tg-theme-hint-color); margin-top: 4px">{{ storeInfo.description }}</p>
            </div>

            <!-- Biz haqimizda -->
            <div v-if="storeInfo.aboutUs" class="info-card" style="margin-bottom: 12px">
                <h3 class="info-title">
                    <svg style="width: 18px; height: 18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                    </svg>
                    Biz haqimizda
                </h3>
                <p class="info-text" style="white-space: pre-line">{{ storeInfo.aboutUs }}</p>
            </div>

            <!-- Bog'lanish -->
            <div class="info-card" style="margin-bottom: 12px">
                <h3 class="info-title">
                    <svg style="width: 18px; height: 18px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                    </svg>
                    Bog'lanish
                </h3>
                <div class="info-links">
                    <a v-if="storeInfo.phone" :href="'tel:' + storeInfo.phone" class="info-link tap-active">
                        <div class="info-link-icon" style="background: #10b981">
                            <svg style="width: 16px; height: 16px; color: #fff" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="info-link-label">Telefon</span>
                            <span class="info-link-value">{{ storeInfo.phone }}</span>
                        </div>
                    </a>
                    <a v-if="storeInfo.telegram" :href="telegramUrl" class="info-link tap-active" @click.prevent="openTelegramLink(telegramUrl)">
                        <div class="info-link-icon" style="background: #0088cc">
                            <svg style="width: 16px; height: 16px; color: #fff" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.461-1.901-.903-1.056-.692-1.653-1.123-2.678-1.799-1.185-.781-.417-1.21.258-1.911.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.49-1.302.48-.428-.008-1.252-.242-1.865-.44-.751-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635.099-.002.321.023.465.141.12.098.153.229.169.344.016.117.036.341.02.527z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="info-link-label">Telegram</span>
                            <span class="info-link-value">{{ storeInfo.telegram }}</span>
                        </div>
                    </a>
                    <a v-if="storeInfo.instagram" :href="instagramUrl" class="info-link tap-active" @click.prevent="openLink(instagramUrl)">
                        <div class="info-link-icon" style="background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)">
                            <svg style="width: 16px; height: 16px; color: #fff" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="info-link-label">Instagram</span>
                            <span class="info-link-value">{{ storeInfo.instagram }}</span>
                        </div>
                    </a>
                    <a v-if="storeInfo.website" :href="websiteUrl" class="info-link tap-active" @click.prevent="openLink(websiteUrl)">
                        <div class="info-link-icon" style="background: #6366f1">
                            <svg style="width: 16px; height: 16px; color: #fff" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
                            </svg>
                        </div>
                        <div>
                            <span class="info-link-label">Veb-sayt</span>
                            <span class="info-link-value">{{ storeInfo.website }}</span>
                        </div>
                    </a>
                    <div v-if="storeInfo.address" class="info-link">
                        <div class="info-link-icon" style="background: #f97316">
                            <svg style="width: 16px; height: 16px; color: #fff" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="info-link-label">Manzil</span>
                            <span class="info-link-value">{{ storeInfo.address }}</span>
                        </div>
                    </div>
                    <div v-if="storeInfo.workingHours" class="info-link">
                        <div class="info-link-icon" style="background: #8b5cf6">
                            <svg style="width: 16px; height: 16px; color: #fff" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="info-link-label">Ish vaqti</span>
                            <span class="info-link-value">{{ storeInfo.workingHours }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No info -->
            <div v-if="!storeInfo.aboutUs && !storeInfo.phone && !storeInfo.address" class="empty-state" style="padding: 40px 0">
                <div class="empty-state-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                    </svg>
                </div>
                <p class="empty-state-title">Ma'lumotlar kiritilmagan</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreInfo } from '../stores/store'
import { useTelegram } from '../composables/useTelegram'

const router = useRouter()
const storeInfo = useStoreInfo()
const { showBackButton, hideBackButton, openLink, openTelegramLink } = useTelegram()

const telegramUrl = computed(() => {
    const handle = storeInfo.telegram?.replace('@', '') || ''
    return handle ? `https://t.me/${handle}` : ''
})

const instagramUrl = computed(() => {
    const handle = storeInfo.instagram?.replace('@', '') || ''
    return handle ? `https://instagram.com/${handle}` : ''
})

const websiteUrl = computed(() => {
    const url = storeInfo.website || ''
    return url.startsWith('http') ? url : `https://${url}`
})

function goBack() {
    router.back()
}

onMounted(() => {
    showBackButton(goBack)
})
</script>

<style scoped>
.info-card {
    background: var(--tg-theme-secondary-bg-color, #f1f5f9);
    border-radius: 16px;
    padding: 16px;
}
.info-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 600;
    color: var(--tg-theme-text-color);
    margin-bottom: 10px;
}
.info-text {
    font-size: 14px;
    line-height: 1.6;
    color: var(--tg-theme-hint-color);
}
.info-links {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.info-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    text-decoration: none;
    color: inherit;
}
.info-link-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.info-link-label {
    display: block;
    font-size: 12px;
    color: var(--tg-theme-hint-color);
    line-height: 1.2;
}
.info-link-value {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: var(--tg-theme-text-color);
    line-height: 1.3;
}
</style>
