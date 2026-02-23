import { shallowRef, ref, computed, onMounted, onUnmounted } from 'vue'

const webapp = shallowRef(null)
const isReady = ref(false)
const themeParams = ref({})

export function useTelegram() {
    const tg = window?.Telegram?.WebApp

    if (tg && !webapp.value) {
        webapp.value = tg
    }

    const initData = computed(() => webapp.value?.initData || '')
    const initDataUnsafe = computed(() => webapp.value?.initDataUnsafe || {})
    const user = computed(() => initDataUnsafe.value?.user || null)
    const userId = computed(() => user.value?.id || null)
    const firstName = computed(() => user.value?.first_name || '')
    const lastName = computed(() => user.value?.last_name || '')
    const username = computed(() => user.value?.username || '')
    const languageCode = computed(() => user.value?.language_code || 'uz')
    const startParam = computed(() => initDataUnsafe.value?.start_param || '')
    const platform = computed(() => webapp.value?.platform || 'unknown')
    const version = computed(() => webapp.value?.version || '6.0')
    const colorScheme = computed(() => webapp.value?.colorScheme || 'light')

    function ready() {
        if (webapp.value) {
            webapp.value.ready()
            isReady.value = true
            syncThemeParams()
        }
    }

    function expand() {
        webapp.value?.expand()
    }

    function close() {
        webapp.value?.close()
    }

    function syncThemeParams() {
        if (webapp.value?.themeParams) {
            themeParams.value = { ...webapp.value.themeParams }
        }
    }

    // BackButton
    function showBackButton(callback) {
        if (webapp.value?.BackButton) {
            webapp.value.BackButton.show()
            webapp.value.BackButton.onClick(callback)
        }
    }

    function hideBackButton() {
        if (webapp.value?.BackButton) {
            webapp.value.BackButton.hide()
            webapp.value.BackButton.offClick()
        }
    }

    // MainButton
    function showMainButton(text, callback, options = {}) {
        if (webapp.value?.MainButton) {
            const btn = webapp.value.MainButton
            btn.setText(text)

            if (options.color) btn.color = options.color
            if (options.textColor) btn.textColor = options.textColor

            btn.onClick(callback)
            btn.show()

            if (options.progress) {
                btn.showProgress(false)
            }
        }
    }

    function hideMainButton() {
        if (webapp.value?.MainButton) {
            webapp.value.MainButton.hide()
            webapp.value.MainButton.offClick()
            webapp.value.MainButton.hideProgress()
        }
    }

    function setMainButtonLoading(loading) {
        if (webapp.value?.MainButton) {
            if (loading) {
                webapp.value.MainButton.showProgress(false)
                webapp.value.MainButton.disable()
            } else {
                webapp.value.MainButton.hideProgress()
                webapp.value.MainButton.enable()
            }
        }
    }

    // HapticFeedback
    function hapticImpact(style = 'light') {
        // style: 'light' | 'medium' | 'heavy' | 'rigid' | 'soft'
        webapp.value?.HapticFeedback?.impactOccurred(style)
    }

    function hapticNotification(type = 'success') {
        // type: 'error' | 'success' | 'warning'
        webapp.value?.HapticFeedback?.notificationOccurred(type)
    }

    function hapticSelection() {
        webapp.value?.HapticFeedback?.selectionChanged()
    }

    // Popups
    function showAlert(message) {
        return new Promise((resolve) => {
            if (webapp.value?.showAlert) {
                webapp.value.showAlert(message, resolve)
            } else {
                alert(message)
                resolve()
            }
        })
    }

    function showConfirm(message) {
        return new Promise((resolve) => {
            if (webapp.value?.showConfirm) {
                webapp.value.showConfirm(message, resolve)
            } else {
                resolve(confirm(message))
            }
        })
    }

    // Open links
    function openLink(url, options = {}) {
        webapp.value?.openLink(url, options)
    }

    function openTelegramLink(url) {
        webapp.value?.openTelegramLink(url)
    }

    // Theme change listener
    let themeHandler = null

    onMounted(() => {
        if (webapp.value) {
            themeHandler = () => syncThemeParams()
            webapp.value.onEvent('themeChanged', themeHandler)
        }
    })

    onUnmounted(() => {
        if (webapp.value && themeHandler) {
            webapp.value.offEvent('themeChanged', themeHandler)
        }
    })

    return {
        webapp,
        isReady,
        initData,
        initDataUnsafe,
        user,
        userId,
        firstName,
        lastName,
        username,
        languageCode,
        startParam,
        platform,
        version,
        colorScheme,
        themeParams,
        ready,
        expand,
        close,
        showBackButton,
        hideBackButton,
        showMainButton,
        hideMainButton,
        setMainButtonLoading,
        hapticImpact,
        hapticNotification,
        hapticSelection,
        showAlert,
        showConfirm,
        openLink,
        openTelegramLink,
    }
}
