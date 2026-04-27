/**
 * Meta Pixel composable — Facebook/Instagram targetdan kelganlarni kuzatish.
 *
 * Pixel ID app.blade.php'da `META_PIXEL_ID` orqali load qilingan (avtomatik PageView).
 * Bu composable qo'shimcha event'larni jonatadi:
 *
 *   trackAddToCart()          — "Ro'yxatdan o'tish" tugmasi bosilganda
 *   trackCompleteRegistration() — muvaffaqiyatli ro'yxatdan o'tgandan keyin
 *   trackLead()               — lead form yuborilganda (B2B SaaS uchun)
 *   trackPurchase(value, currency='UZS') — to'lov muvaffaqiyatli bo'lganda
 *
 * Server-side fallback yo'q — agar pixel ID config'da yo'q bo'lsa, fbq mavjud bo'lmaydi
 * va event'lar silently no-op qiladi (xato chiqarmaydi).
 */
export function useMetaPixel() {
  const isAvailable = () => typeof window !== 'undefined' && typeof window.fbq === 'function'

  const track = (eventName, params = {}) => {
    if (!isAvailable()) return false
    try {
      if (Object.keys(params).length > 0) {
        window.fbq('track', eventName, params)
      } else {
        window.fbq('track', eventName)
      }
      return true
    } catch (e) {
      // Console only; pixel xatosi user flow'ni buzmasligi kerak
      // eslint-disable-next-line no-console
      console.warn('[MetaPixel]', eventName, e?.message)
      return false
    }
  }

  return {
    isAvailable,
    track,

    // Standard events
    trackPageView: () => track('PageView'),
    trackAddToCart: (params = {}) => track('AddToCart', params),
    trackInitiateCheckout: (params = {}) => track('InitiateCheckout', params),
    trackCompleteRegistration: (params = {}) => track('CompleteRegistration', params),
    trackLead: (params = {}) => track('Lead', params),
    trackPurchase: (value, currency = 'UZS') => track('Purchase', { value, currency }),
    trackSubscribe: (params = {}) => track('Subscribe', params),
    trackContact: () => track('Contact'),

    // Custom event (har qanday nom)
    trackCustom: (eventName, params = {}) => {
      if (!isAvailable()) return false
      try {
        window.fbq('trackCustom', eventName, params)
        return true
      } catch (e) {
        return false
      }
    },
  }
}
