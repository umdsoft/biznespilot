<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsSettingsController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Show analytics settings page
     */
    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        $settings = $business->settings ?? [];

        return Inertia::render('Business/Settings/Analytics', [
            'analyticsSettings' => [
                'ga4_measurement_id' => $settings['ga4_measurement_id'] ?? null,
                'ga4_enabled' => $settings['ga4_enabled'] ?? false,
                'yandex_metrika_id' => $settings['yandex_metrika_id'] ?? null,
                'yandex_metrika_enabled' => $settings['yandex_metrika_enabled'] ?? false,
                'facebook_pixel_id' => $settings['facebook_pixel_id'] ?? null,
                'facebook_pixel_enabled' => $settings['facebook_pixel_enabled'] ?? false,
            ],
        ]);
    }

    /**
     * Update analytics settings
     */
    public function update(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'ga4_measurement_id' => ['nullable', 'string', 'max:50', 'regex:/^G-[A-Z0-9]+$/i'],
            'ga4_enabled' => ['boolean'],
            'yandex_metrika_id' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'yandex_metrika_enabled' => ['boolean'],
            'facebook_pixel_id' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'facebook_pixel_enabled' => ['boolean'],
        ], [
            'ga4_measurement_id.regex' => 'GA4 Measurement ID formati noto\'g\'ri (masalan: G-XXXXXXXXXX)',
            'yandex_metrika_id.regex' => 'Yandex Metrika ID faqat raqamlardan iborat bo\'lishi kerak',
            'facebook_pixel_id.regex' => 'Facebook Pixel ID faqat raqamlardan iborat bo\'lishi kerak',
        ]);

        $settings = $business->settings ?? [];

        // Update analytics settings
        $settings['ga4_measurement_id'] = $validated['ga4_measurement_id'] ?? null;
        $settings['ga4_enabled'] = $validated['ga4_enabled'] ?? false;
        $settings['yandex_metrika_id'] = $validated['yandex_metrika_id'] ?? null;
        $settings['yandex_metrika_enabled'] = $validated['yandex_metrika_enabled'] ?? false;
        $settings['facebook_pixel_id'] = $validated['facebook_pixel_id'] ?? null;
        $settings['facebook_pixel_enabled'] = $validated['facebook_pixel_enabled'] ?? false;

        $business->settings = $settings;
        $business->save();

        return back()->with('success', 'Analytics sozlamalari saqlandi');
    }

    /**
     * Get tracking scripts for frontend
     */
    public function getTrackingScripts()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['scripts' => []]);
        }

        $settings = $business->settings ?? [];
        $scripts = [];

        // Google Analytics 4
        if (!empty($settings['ga4_enabled']) && !empty($settings['ga4_measurement_id'])) {
            $scripts['ga4'] = [
                'id' => $settings['ga4_measurement_id'],
                'script' => $this->generateGA4Script($settings['ga4_measurement_id']),
            ];
        }

        // Yandex Metrika
        if (!empty($settings['yandex_metrika_enabled']) && !empty($settings['yandex_metrika_id'])) {
            $scripts['yandex'] = [
                'id' => $settings['yandex_metrika_id'],
                'script' => $this->generateYandexScript($settings['yandex_metrika_id']),
            ];
        }

        // Facebook Pixel
        if (!empty($settings['facebook_pixel_enabled']) && !empty($settings['facebook_pixel_id'])) {
            $scripts['facebook'] = [
                'id' => $settings['facebook_pixel_id'],
                'script' => $this->generateFacebookPixelScript($settings['facebook_pixel_id']),
            ];
        }

        return response()->json(['scripts' => $scripts]);
    }

    /**
     * Generate GA4 script
     */
    private function generateGA4Script(string $measurementId): string
    {
        return <<<HTML
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$measurementId}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{$measurementId}');
</script>
HTML;
    }

    /**
     * Generate Yandex Metrika script
     */
    private function generateYandexScript(string $counterId): string
    {
        return <<<HTML
<!-- Yandex.Metrika counter -->
<script type="text/javascript">
   (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
   m[i].l=1*new Date();
   for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
   k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
   (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

   ym({$counterId}, "init", {
        clickmap:true,
        trackLinks:true,
        accurateTrackBounce:true,
        webvisor:true
   });
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/{$counterId}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->
HTML;
    }

    /**
     * Generate Facebook Pixel script
     */
    private function generateFacebookPixelScript(string $pixelId): string
    {
        return <<<HTML
<!-- Facebook Pixel Code -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '{$pixelId}');
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1"/></noscript>
<!-- End Facebook Pixel Code -->
HTML;
    }
}
