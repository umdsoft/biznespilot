<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <script>
        // Prevent flash of unstyled content - apply dark mode immediately
        (function() {
            const stored = localStorage.getItem('darkMode');
            if (stored === 'true' || (stored === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- ========== DEFAULT SEO META TAGS ========== -->
    <title inertia>{{ $seoTitle ?? config('app.name', 'BiznesPilot AI') }}</title>
    <meta name="description" content="{{ $seoDescription ?? "BiznesPilot - O'zbekistondagi #1 biznes boshqaruv platformasi. Marketing, Sotuv, Moliya, HR - hammasi bir joyda. AI yordamchi 24/7." }}" inertia>
    <meta name="keywords" content="BiznesPilot, CRM tizimi, biznes avtomatlashtirish, sotuvlarni boshqarish, marketing avtomatizatsiya, AI biznes, Telegram bot CRM, Uzbekistan CRM">
    <meta name="author" content="BiznesPilot">
    <meta name="publisher" content="BiznesPilot LLC">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" inertia>
    <meta name="googlebot" content="index, follow" inertia>
    <meta name="language" content="{{ app()->getLocale() === 'ru' ? 'Russian' : 'Uzbek' }}">
    <meta name="copyright" content="BiznesPilot {{ date('Y') }}">

    <!-- ========== VERIFICATION TAGS ========== -->
    @if(config('services.verification.google'))
    <meta name="google-site-verification" content="{{ config('services.verification.google') }}">
    @endif
    @if(config('services.verification.yandex'))
    <meta name="yandex-verification" content="{{ config('services.verification.yandex') }}">
    @endif
    @if(config('services.verification.facebook'))
    <meta name="facebook-domain-verification" content="{{ config('services.verification.facebook') }}">
    @endif

    <!-- ========== CANONICAL ========== -->
    <link rel="canonical" href="{{ url()->current() }}" inertia>

    <!-- ========== HREFLANG ALTERNATES (UZ / RU / x-default) ========== -->
    @php
        $currentPath = request()->getPathInfo() === '/' ? '' : request()->getPathInfo();
        $host = config('app.url');
    @endphp
    <link rel="alternate" hreflang="uz" href="{{ $host }}{{ $currentPath }}">
    <link rel="alternate" hreflang="ru" href="{{ $host }}{{ $currentPath }}?lang=ru">
    <link rel="alternate" hreflang="x-default" href="{{ $host }}{{ $currentPath }}">

    <!-- ========== DEFAULT OPEN GRAPH ========== -->
    <meta property="og:type" content="{{ $seoType ?? 'website' }}" inertia>
    <meta property="og:site_name" content="BiznesPilot">
    <meta property="og:url" content="{{ url()->current() }}" inertia>
    <meta property="og:title" content="{{ $seoTitle ?? config('app.name', 'BiznesPilot AI') }}" inertia>
    <meta property="og:description" content="{{ $seoDescription ?? "BiznesPilot - O'zbekistondagi #1 biznes boshqaruv platformasi. Marketing, Sotuv, Moliya, HR - hammasi bir joyda." }}" inertia>
    <meta property="og:image" content="{{ $seoImage ?? asset('images/og-image.svg') }}" inertia>
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ru' ? 'ru_RU' : 'uz_UZ' }}">
    <meta property="og:locale:alternate" content="{{ app()->getLocale() === 'ru' ? 'uz_UZ' : 'ru_RU' }}">

    <!-- ========== DEFAULT TWITTER CARDS ========== -->
    <meta name="twitter:card" content="summary_large_image" inertia>
    <meta name="twitter:site" content="@biznespilot">
    <meta name="twitter:title" content="{{ $seoTitle ?? config('app.name', 'BiznesPilot AI') }}" inertia>
    <meta name="twitter:description" content="{{ $seoDescription ?? "BiznesPilot - O'zbekistondagi #1 biznes boshqaruv platformasi." }}" inertia>
    <meta name="twitter:image" content="{{ $seoImage ?? asset('images/og-image.svg') }}" inertia>

    <!-- ========== MOBILE & APP META ========== -->
    <meta name="theme-color" content="#2563eb" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1e293b" media="(prefers-color-scheme: dark)">
    <meta name="msapplication-TileColor" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="BiznesPilot">
    <meta name="application-name" content="BiznesPilot">
    <meta name="format-detection" content="telephone=yes">

    <!-- ========== FAVICON & ICONS ========== -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <!-- ========== PRECONNECT & DNS PREFETCH ========== -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://fonts.bunny.net">

    <!-- ========== FONTS ========== -->
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead

    {{-- ========== STRUCTURED DATA (SSR — Googlebot-visible) ========== --}}
    @php
        $seoSameAs = array_values(array_filter([
            config('services.social.facebook'),
            config('services.social.instagram'),
            config('services.social.telegram'),
            config('services.social.youtube'),
        ]));
        $seoSchemaGraph = [
            [
                '@type' => 'Organization',
                '@id' => config('app.url') . '#organization',
                'name' => 'BiznesPilot',
                'legalName' => 'BiznesPilot LLC',
                'url' => config('app.url'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo-full.svg'),
                    'width' => 512,
                    'height' => 512,
                ],
                'description' => "O'zbekistondagi #1 biznes boshqaruv platformasi. Marketing, Sotuv, Moliya, HR.",
                'sameAs' => $seoSameAs,
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'contactType' => 'customer service',
                    'email' => config('services.contact.email', 'support@biznespilot.uz'),
                    'availableLanguage' => ['Uzbek', 'Russian'],
                ],
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressCountry' => 'UZ',
                    'addressLocality' => 'Tashkent',
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => config('app.url') . '#website',
                'url' => config('app.url'),
                'name' => 'BiznesPilot',
                'publisher' => ['@id' => config('app.url') . '#organization'],
                'inLanguage' => ['uz', 'ru'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => config('app.url') . '/?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type' => 'SoftwareApplication',
                '@id' => config('app.url') . '#software',
                'name' => 'BiznesPilot',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web, iOS, Android',
                'description' => "CRM, marketing avtomatizatsiya, HR, Telegram chatbotlar, do'kon qurish.",
                'url' => config('app.url'),
                'publisher' => ['@id' => config('app.url') . '#organization'],
                'offers' => [
                    ['@type' => 'Offer', 'name' => 'Free Starter', 'price' => '0', 'priceCurrency' => 'UZS'],
                    ['@type' => 'Offer', 'name' => 'Business', 'price' => '299000', 'priceCurrency' => 'UZS'],
                    ['@type' => 'Offer', 'name' => 'Enterprise', 'price' => '999000', 'priceCurrency' => 'UZS'],
                ],
                'aggregateRating' => [
                    '@type' => 'AggregateRating',
                    'ratingValue' => '4.8',
                    'reviewCount' => '127',
                ],
            ],
        ];
        $seoSchemaJson = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $seoSchemaGraph],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    @endphp
    <script type="application/ld+json">{!! $seoSchemaJson !!}</script>

    {{-- ========== ANALYTICS TRACKING ========== --}}
    @if(config('services.analytics.ga4_id'))
    <!-- Google Analytics 4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.analytics.ga4_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json(config('services.analytics.ga4_id')));
    </script>
    @endif

    @if(config('services.analytics.yandex_id'))
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id={{ config('services.analytics.yandex_id') }}', 'ym');

        ym({{ config('services.analytics.yandex_id') }}, 'init', {
            ssr: true,
            webvisor: true,
            clickmap: true,
            ecommerce: "dataLayer",
            referrer: document.referrer,
            url: location.href,
            accurateTrackBounce: true,
            trackLinks: true
        });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/{{ config('services.analytics.yandex_id') }}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    @endif

    @if(config('services.analytics.meta_pixel_id'))
    <!-- Meta Pixel Code -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json(config('services.analytics.meta_pixel_id')));
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ config('services.analytics.meta_pixel_id') }}&ev=PageView&noscript=1"/></noscript>
    @endif
</head>
<body class="antialiased bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
    {{--
        SEO/Crawler-visible content (Google OAuth verifier, search engines).
        Inertia mounts to <div id="app"> and replaces its content via JavaScript.
        Bu blok #app dan TASHQARIDA — har doim initial HTML'da bo'ladi.
        Vue ishga tushgandan keyin sr-only class'i orqali ekrandan yashirinadi.
    --}}
    <div id="seo-fallback" class="seo-fallback" aria-hidden="false">
        <header>
            <h1>BiznesPilot AI — O'zbekistondagi #1 biznes boshqaruv platformasi</h1>
        </header>
        <main>
            <p><strong>BiznesPilot</strong> — O'zbekiston bizneslari uchun yaratilgan kompleks biznes operatsion tizimi.
                Marketing, sotuvlar, moliya, HR boshqaruvi, mijozlar bazasi va AI yordamchi — barchasi bitta platformada.</p>
            <p>BiznesPilot is a comprehensive business management platform for SMEs in Uzbekistan.
                It provides CRM, marketing automation, sales pipeline, HR management, finance tracking,
                Telegram bot integration, Google Ads / Yandex Direct campaign analytics, and AI-powered
                business assistant — all in one unified workspace.</p>
            <p><strong>Asosiy imkoniyatlar / Key features:</strong></p>
            <ul>
                <li>CRM tizimi va mijozlar bazasi (Customer Relationship Management)</li>
                <li>Sotuvlar pipeline va lead management</li>
                <li>Marketing kampaniyalari va ROI analytics</li>
                <li>Telegram bot orqali do'kon yaratish (e-commerce, services, delivery, courses, lead capture)</li>
                <li>Google Ads, Google Analytics, YouTube va Yandex Direct/Metrika integratsiyalari</li>
                <li>HR boshqaruvi: xodimlar, KPI, davomat, ish haqi</li>
                <li>Moliya: kassa, daromad/xarajatlar, hisobotlar</li>
                <li>AI biznes maslahatchi va avtomatlashtirish</li>
            </ul>
        </main>
        <footer>
            <nav aria-label="Legal links">
                <a href="/privacy-policy" rel="nofollow">Privacy Policy / Maxfiylik siyosati</a> |
                <a href="/terms" rel="nofollow">Terms of Service / Xizmat shartlari</a> |
                <a href="/data-deletion" rel="nofollow">Data Deletion / Ma'lumotlarni o'chirish</a> |
                <a href="/about" rel="nofollow">About Us / Biz haqimizda</a> |
                <a href="/pricing" rel="nofollow">Pricing / Narxlar</a>
            </nav>
            <p>&copy; {{ date('Y') }} BiznesPilot. Barcha huquqlar himoyalangan.
                Operated by BiznesPilot LLC, Uzbekistan.</p>
        </footer>
    </div>
    <style>
        /* Vue ishga tushgandan keyin SEO blok yashirinadi —
           foydalanuvchi takror ko'rinishni ko'rmaydi, faqat crawler/verifier ko'radi */
        .seo-fallback {
            position: absolute;
            width: 1px; height: 1px;
            padding: 0; margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            white-space: nowrap;
            border: 0;
        }
    </style>

    @inertia

    <!-- ========== NO-SCRIPT FALLBACK (JS o'chirilgan brauzerlar uchun) ========== -->
    <noscript>
        <div style="padding: 20px; text-align: center; background: #fef2f2; color: #991b1b;">
            {{ app()->getLocale() === 'ru'
                ? 'Для работы BiznesPilot требуется JavaScript. Пожалуйста, включите JavaScript в настройках браузера.'
                : 'BiznesPilot ishlashi uchun JavaScript kerak. Iltimos, brauzer sozlamalarida JavaScript ni yoqing.' }}
        </div>
        <hr>
        <div style="padding: 20px;">
            <h1>BiznesPilot AI</h1>
            <p>O'zbekistondagi #1 biznes boshqaruv platformasi.
                CRM, marketing, moliya, HR — bitta platformada.</p>
            <p>
                <a href="/privacy-policy">Privacy Policy</a> |
                <a href="/terms">Terms of Service</a> |
                <a href="/data-deletion">Data Deletion</a> |
                <a href="/about">About Us</a>
            </p>
        </div>
    </noscript>
</body>
</html>
