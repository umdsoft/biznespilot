<!DOCTYPE html>
<html lang="{{ $locale ?? 'uz-latn' }}" class="scroll-smooth" dir="ltr" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- ========== PRIMARY SEO META TAGS ========== -->
    <title>{{ $translations['meta_title'] ?? 'BiznesPilot - Biznes Boshqaruv Platformasi' }}</title>
    <meta name="description" content="{{ $translations['meta_description'] ?? '' }}">
    <meta name="keywords" content="{{ $locale === 'ru'
        ? 'BiznesPilot, CRM система Узбекистан, автоматизация бизнеса, управление продажами, маркетинг автоматизация, финансовый учет, AI для бизнеса, Telegram бот CRM, lead management, бизнес аналитика, ERP система, управление клиентами, sales automation, бесплатная CRM'
        : 'BiznesPilot, CRM tizimi Ozbekiston, biznes avtomatlashtirish, sotuvlarni boshqarish, marketing avtomatizatsiya, moliyaviy hisobot, AI biznes uchun, Telegram bot CRM, lead management, biznes analitika, ERP tizimi, mijozlarni boshqarish, sales automation, bepul CRM' }}">
    <meta name="author" content="BiznesPilot">
    <meta name="publisher" content="BiznesPilot LLC">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large">
    <meta name="bingbot" content="index, follow">
    <meta name="revisit-after" content="3 days">
    <meta name="rating" content="general">
    <meta name="distribution" content="global">
    <meta name="language" content="{{ $locale === 'ru' ? 'Russian' : 'Uzbek' }}">
    <meta name="copyright" content="BiznesPilot {{ date('Y') }}">

    <!-- ========== CANONICAL & ALTERNATE LANGUAGES (hreflang) ========== -->
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="alternate" hreflang="uz" href="{{ url('/') }}">
    <link rel="alternate" hreflang="uz-Latn-UZ" href="{{ url('/') }}">
    <link rel="alternate" hreflang="ru" href="{{ url('/lang/ru') }}">
    <link rel="alternate" hreflang="ru-UZ" href="{{ url('/lang/ru') }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

    <!-- ========== OPEN GRAPH / FACEBOOK ========== -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="BiznesPilot">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $translations['meta_title'] ?? 'BiznesPilot' }}">
    <meta property="og:description" content="{{ $translations['meta_description'] ?? '' }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:image:secure_url" content="{{ asset('images/og-image.png') }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="BiznesPilot - {{ $locale === 'ru' ? 'Платформа управления бизнесом #1 в Узбекистане' : 'O\'zbekistondagi #1 biznes boshqaruv platformasi' }}">
    <meta property="og:locale" content="{{ $locale === 'ru' ? 'ru_RU' : 'uz_UZ' }}">
    <meta property="og:locale:alternate" content="{{ $locale === 'ru' ? 'uz_UZ' : 'ru_RU' }}">
    <meta property="og:updated_time" content="{{ now()->toIso8601String() }}">

    <!-- ========== TWITTER CARDS ========== -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@biznespilot">
    <meta name="twitter:creator" content="@biznespilot">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ $translations['meta_title'] ?? 'BiznesPilot' }}">
    <meta name="twitter:description" content="{{ $translations['meta_description'] ?? '' }}">
    <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">
    <meta name="twitter:image:alt" content="BiznesPilot - {{ $locale === 'ru' ? 'Платформа управления бизнесом' : 'Biznes boshqaruv platformasi' }}">
    <meta name="twitter:domain" content="{{ parse_url(url('/'), PHP_URL_HOST) }}">

    <!-- ========== MOBILE & APP META ========== -->
    <meta name="theme-color" content="#2563eb">
    <meta name="msapplication-TileColor" content="#2563eb">
    <meta name="msapplication-navbutton-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="BiznesPilot">
    <meta name="application-name" content="BiznesPilot">
    <meta name="format-detection" content="telephone=yes">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- ========== GEO & BUSINESS LOCATION ========== -->
    <meta name="geo.region" content="UZ">
    <meta name="geo.placename" content="Tashkent, Uzbekistan">
    <meta name="geo.position" content="41.2995;69.2401">
    <meta name="ICBM" content="41.2995, 69.2401">

    <!-- ========== VERIFICATION TAGS (add your IDs) ========== -->
    {{-- <meta name="google-site-verification" content="YOUR_GOOGLE_VERIFICATION_CODE"> --}}
    {{-- <meta name="yandex-verification" content="YOUR_YANDEX_VERIFICATION_CODE"> --}}
    {{-- <meta name="facebook-domain-verification" content="YOUR_FB_VERIFICATION_CODE"> --}}

    <!-- ========== FAVICON & ICONS ========== -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="mask-icon" href="{{ asset('safari-pinned-tab.svg') }}" color="#2563eb">

    <!-- ========== PRECONNECT & DNS PREFETCH ========== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- ========== FONTS (with display swap for performance) ========== -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- ========== COMPILED TAILWIND CSS (via Vite) ========== -->
    @vite(['resources/css/app.css'])

    <!-- Custom Styles -->
    <style>
        /* Smooth scrolling */
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Card hover effect */
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        /* Button glow effect */
        .btn-glow { position: relative; overflow: hidden; }
        .btn-glow::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        .btn-glow:hover::before { left: 100%; }

        /* FAQ Accordion */
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .faq-item.active .faq-answer { max-height: 500px; }
        .faq-item.active .faq-icon { transform: rotate(180deg); }
        .faq-icon { transition: transform 0.3s ease; }

        /* Mobile menu */
        .mobile-menu { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .mobile-menu.active { max-height: 400px; }

        /* ========== ANIMATIONS ========== */
        /* Scroll-triggered slide up animation */
        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up { animation: slideUp 0.5s ease-out forwards; }

        /* Hero blob animation */
        @keyframes blob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(20px, -30px) scale(1.1); }
            50% { transform: translate(-20px, 20px) scale(0.9); }
            75% { transform: translate(30px, 10px) scale(1.05); }
        }
        .animate-blob { animation: blob 15s ease-in-out infinite; }
        .animation-delay-2000 { animation-delay: 2s; }

        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }

        /* Bounce slow */
        @keyframes bounce-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-bounce-slow { animation: bounce-slow 3s ease-in-out infinite; }

        /* CTA gradient animation */
        @keyframes gradient-x {
            0%, 100% { transform: translateX(-100%); }
            50% { transform: translateX(100%); }
        }
        .animate-gradient-x { animation: gradient-x 15s ease-in-out infinite; }

        /* ========== FLOATING BUTTONS ========== */
        .floating-btn {
            position: fixed;
            z-index: 40;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 28px rgba(0, 0, 0, 0.2);
        }
        #back-to-top {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        #back-to-top.visible {
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-white">
    <!-- ========== SCHEMA.ORG JSON-LD STRUCTURED DATA ========== -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@graph": [
            {
                "@@type": "Organization",
                "@@id": "{{ url('/') }}#organization",
                "name": "BiznesPilot",
                "alternateName": ["Biznes Pilot", "БизнесПилот"],
                "url": "{{ url('/') }}",
                "logo": {
                    "@@type": "ImageObject",
                    "url": "{{ asset('images/logo.png') }}",
                    "width": 512,
                    "height": 512
                },
                "image": "{{ asset('images/og-image.png') }}",
                "description": {!! json_encode($locale === 'ru' ? 'Платформа управления бизнесом #1 в Узбекистане. Маркетинг, Продажи, Финансы - всё в одном месте.' : 'O\'zbekistondagi #1 biznes boshqaruv platformasi. Marketing, Sotuv, Moliya - hammasi bir joyda.', JSON_UNESCAPED_UNICODE) !!},
                "foundingDate": "2024",
                "founders": [
                    {
                        "@@type": "Person",
                        "name": "BiznesPilot Team"
                    }
                ],
                "address": {
                    "@@type": "PostalAddress",
                    "addressLocality": "Tashkent",
                    "addressRegion": "Tashkent",
                    "addressCountry": "UZ"
                },
                "geo": {
                    "@@type": "GeoCoordinates",
                    "latitude": "41.2995",
                    "longitude": "69.2401"
                },
                "areaServed": {
                    "@@type": "Country",
                    "name": "Uzbekistan"
                },
                "sameAs": [
                    "https://t.me/biznespilot",
                    "https://instagram.com/biznespilot",
                    "https://facebook.com/biznespilot",
                    "https://linkedin.com/company/biznespilot"
                ],
                "contactPoint": [
                    {
                        "@@type": "ContactPoint",
                        "telephone": "+998505048668",
                        "contactType": "customer service",
                        "availableLanguage": ["Uzbek", "Russian", "English"],
                        "areaServed": "UZ"
                    },
                    {
                        "@@type": "ContactPoint",
                        "telephone": "+998505048668",
                        "contactType": "sales",
                        "availableLanguage": ["Uzbek", "Russian"],
                        "areaServed": "UZ"
                    }
                ]
            },
            {
                "@@type": "WebSite",
                "@@id": "{{ url('/') }}#website",
                "url": "{{ url('/') }}",
                "name": "BiznesPilot",
                "description": {!! json_encode($translations['meta_description'] ?? '', JSON_UNESCAPED_UNICODE) !!},
                "publisher": {
                    "@@id": "{{ url('/') }}#organization"
                },
                "inLanguage": ["uz", "ru"],
                "potentialAction": {
                    "@@type": "SearchAction",
                    "target": {
                        "@@type": "EntryPoint",
                        "urlTemplate": "{{ url('/') }}/search?q={search_term_string}"
                    },
                    "query-input": "required name=search_term_string"
                }
            },
            {
                "@@type": "WebPage",
                "@@id": "{{ url()->current() }}#webpage",
                "url": "{{ url()->current() }}",
                "name": {!! json_encode($translations['meta_title'] ?? 'BiznesPilot', JSON_UNESCAPED_UNICODE) !!},
                "description": {!! json_encode($translations['meta_description'] ?? '', JSON_UNESCAPED_UNICODE) !!},
                "isPartOf": {
                    "@@id": "{{ url('/') }}#website"
                },
                "about": {
                    "@@id": "{{ url('/') }}#organization"
                },
                "datePublished": "2024-01-01",
                "dateModified": "{{ now()->toIso8601String() }}",
                "inLanguage": "{{ $locale === 'ru' ? 'ru' : 'uz' }}",
                "breadcrumb": {
                    "@@type": "BreadcrumbList",
                    "itemListElement": [
                        {
                            "@@type": "ListItem",
                            "position": 1,
                            "name": "{{ $locale === 'ru' ? 'Главная' : 'Bosh sahifa' }}",
                            "item": "{{ url('/') }}"
                        }
                    ]
                }
            },
            {
                "@@type": "SoftwareApplication",
                "@@id": "{{ url('/') }}#software",
                "name": "BiznesPilot",
                "applicationCategory": "BusinessApplication",
                "applicationSubCategory": "CRM, ERP, Marketing Automation",
                "operatingSystem": "Web Browser, iOS, Android",
                "offers": {
                    "@@type": "Offer",
                    "price": "0",
                    "priceCurrency": "UZS",
                    "description": "{{ $locale === 'ru' ? '14 дней бесплатно' : '14 kun bepul' }}",
                    "availability": "https://schema.org/InStock",
                    "priceValidUntil": "{{ now()->addYear()->format('Y-m-d') }}"
                },
                "aggregateRating": {
                    "@@type": "AggregateRating",
                    "ratingValue": "4.9",
                    "ratingCount": "500",
                    "bestRating": "5",
                    "worstRating": "1"
                },
                "featureList": [
                    "{{ $locale === 'ru' ? 'Маркетинг автоматизация' : 'Marketing avtomatizatsiya' }}",
                    "{{ $locale === 'ru' ? 'CRM и управление продажами' : 'CRM va sotuvlarni boshqarish' }}",
                    "{{ $locale === 'ru' ? 'Финансовый учет' : 'Moliyaviy hisobot' }}",
                    "{{ $locale === 'ru' ? 'AI-помощник 24/7' : 'AI yordamchi 24/7' }}",
                    "{{ $locale === 'ru' ? 'Telegram бот интеграция' : 'Telegram bot integratsiya' }}",
                    "{{ $locale === 'ru' ? 'Реальное время аналитика' : 'Real-time analitika' }}"
                ],
                "screenshot": "{{ asset('images/og-image.png') }}",
                "provider": {
                    "@@id": "{{ url('/') }}#organization"
                }
            },
            {
                "@@type": "FAQPage",
                "@@id": "{{ url('/') }}#faq",
                "mainEntity": [
                    @if(isset($translations['faq']['items']) && count($translations['faq']['items']) > 0)
                    @foreach($translations['faq']['items'] as $index => $item)
                    {
                        "@@type": "Question",
                        "name": {!! json_encode($item['question'], JSON_UNESCAPED_UNICODE) !!},
                        "acceptedAnswer": {
                            "@@type": "Answer",
                            "text": {!! json_encode($item['answer'], JSON_UNESCAPED_UNICODE) !!}
                        }
                    }@if(!$loop->last),@endif
                    @endforeach
                    @endif
                ]
            },
            {
                "@@type": "LocalBusiness",
                "@@id": "{{ url('/') }}#localbusiness",
                "name": "BiznesPilot",
                "image": "{{ asset('images/logo.png') }}",
                "priceRange": "$$",
                "address": {
                    "@@type": "PostalAddress",
                    "addressLocality": "Tashkent",
                    "addressCountry": "UZ"
                },
                "geo": {
                    "@@type": "GeoCoordinates",
                    "latitude": "41.2995",
                    "longitude": "69.2401"
                },
                "openingHoursSpecification": {
                    "@@type": "OpeningHoursSpecification",
                    "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                    "opens": "09:00",
                    "closes": "18:00"
                },
                "sameAs": [
                    "https://t.me/biznespilot",
                    "https://instagram.com/biznespilot"
                ]
            }
        ]
    }
    </script>

    @yield('content')

    <!-- ========== FLOATING BUTTONS ========== -->
    <!-- Telegram Contact Button -->
    <a href="https://t.me/biznespilot" target="_blank" rel="noopener noreferrer"
       class="floating-btn text-white"
       style="bottom: 1.5rem; right: 1.5rem; background-color: #0088cc;"
       aria-label="Telegram orqali bog'lanish">
        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
        </svg>
    </a>

    <!-- Back to Top Button -->
    <button id="back-to-top" onclick="window.scrollTo({top:0,behavior:'smooth'})"
            class="floating-btn bg-gray-800 hover:bg-gray-700 text-white"
            style="bottom: 1.5rem; right: 5.5rem;"
            aria-label="Yuqoriga qaytish">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
        </svg>
    </button>

    <!-- JavaScript for interactions -->
    <script>
        // FAQ accordion
        function toggleFaq(element) {
            const item = element.closest('.faq-item');
            const allItems = document.querySelectorAll('.faq-item');

            allItems.forEach(faq => {
                if (faq !== item) {
                    faq.classList.remove('active');
                }
            });

            item.classList.toggle('active');
        }

        // Back-to-top visibility
        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('back-to-top');
            if (backToTop) {
                if (window.scrollY > 600) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            }
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-slide-up');
                    entry.target.style.opacity = '1';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    </script>
</body>
</html>
