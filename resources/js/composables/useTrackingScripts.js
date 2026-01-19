import { onMounted } from 'vue';

/**
 * Composable for injecting tracking scripts (GA4, Yandex Metrika, Facebook Pixel)
 * @param {Object} trackingScripts - Object with tracking IDs { ga4: 'G-XXXXXX', yandex: '12345678', facebook: '123456789012345' }
 */
export function useTrackingScripts(trackingScripts) {
    onMounted(() => {
        if (!trackingScripts || Object.keys(trackingScripts).length === 0) {
            return;
        }

        // Inject Google Analytics 4
        if (trackingScripts.ga4) {
            injectGA4(trackingScripts.ga4);
        }

        // Inject Yandex Metrika
        if (trackingScripts.yandex) {
            injectYandexMetrika(trackingScripts.yandex);
        }

        // Inject Facebook Pixel
        if (trackingScripts.facebook) {
            injectFacebookPixel(trackingScripts.facebook);
        }
    });
}

/**
 * Inject Google Analytics 4
 */
function injectGA4(measurementId) {
    // Check if already loaded
    if (window.gtag) return;

    // Load gtag.js
    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`;
    document.head.appendChild(script);

    // Initialize gtag
    window.dataLayer = window.dataLayer || [];
    window.gtag = function() { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', measurementId);

    console.log('[Analytics] GA4 initialized:', measurementId);
}

/**
 * Inject Yandex Metrika
 */
function injectYandexMetrika(counterId) {
    // Check if already loaded
    if (window.ym) return;

    // Initialize ym
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {
            if (document.scripts[j].src === r) { return; }
        }
        k=e.createElement(t);
        a=e.getElementsByTagName(t)[0];
        k.async=1;
        k.src=r;
        a.parentNode.insertBefore(k,a);
    })(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

    window.ym(counterId, "init", {
        clickmap: true,
        trackLinks: true,
        accurateTrackBounce: true,
        webvisor: true
    });

    console.log('[Analytics] Yandex Metrika initialized:', counterId);
}

/**
 * Inject Facebook Pixel
 */
function injectFacebookPixel(pixelId) {
    // Check if already loaded
    if (window.fbq) return;

    // Initialize Facebook Pixel
    !function(f,b,e,v,n,t,s){
        if(f.fbq)return;
        n=f.fbq=function(){
            n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)
        };
        if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];
        t=b.createElement(e);
        t.async=!0;
        t.src=v;
        s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s);
    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');

    window.fbq('init', pixelId);
    window.fbq('track', 'PageView');

    console.log('[Analytics] Facebook Pixel initialized:', pixelId);
}

/**
 * Track custom event in all analytics
 */
export function trackEvent(eventName, eventParams = {}) {
    // GA4
    if (window.gtag) {
        window.gtag('event', eventName, eventParams);
    }

    // Yandex Metrika
    if (window.ym) {
        window.ym('reachGoal', eventName, eventParams);
    }

    // Facebook Pixel
    if (window.fbq) {
        window.fbq('track', eventName, eventParams);
    }
}

/**
 * Track form submission
 */
export function trackFormSubmit(formName, formData = {}) {
    trackEvent('form_submit', {
        form_name: formName,
        ...formData
    });

    // Facebook specific Lead event
    if (window.fbq) {
        window.fbq('track', 'Lead', formData);
    }
}
