<!DOCTYPE html>
<html lang="{{ $locale ?? 'uz-latn' }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- SEO Meta Tags -->
    <title>{{ $translations['meta_title'] ?? 'BiznesPilot' }}</title>
    <meta name="description" content="{{ $translations['meta_description'] ?? '' }}">
    <meta name="keywords" content="BiznesPilot, CRM, Marketing, AI, Uzbekistan, Startups, Lead Management, Telegram Bot">
    <meta name="author" content="BiznesPilot">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="{{ $translations['meta_title'] ?? 'BiznesPilot' }}">
    <meta property="og:description" content="{{ $translations['meta_description'] ?? '' }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:locale" content="{{ $locale === 'ru' ? 'ru_RU' : 'uz_UZ' }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url('/') }}">
    <meta property="twitter:title" content="{{ $translations['meta_title'] ?? 'BiznesPilot' }}">
    <meta property="twitter:description" content="{{ $translations['meta_description'] ?? '' }}">
    <meta property="twitter:image" content="{{ asset('images/og-image.png') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                    },
                },
            },
        }
    </script>

    <!-- Custom Styles -->
    <style>
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Gradient background */
        .gradient-bg {
            background: linear-gradient(135deg, #eff6ff 0%, #f5f3ff 50%, #fdf4ff 100%);
        }

        /* Card hover effect */
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        /* Button glow effect */
        .btn-glow {
            position: relative;
            overflow: hidden;
        }
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
        .btn-glow:hover::before {
            left: 100%;
        }

        /* FAQ Accordion */
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .faq-item.active .faq-answer {
            max-height: 500px;
        }
        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }
        .faq-icon {
            transition: transform 0.3s ease;
        }

        /* Mobile menu */
        .mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .mobile-menu.active {
            max-height: 400px;
        }

        /* Hero animations */
        @keyframes blob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(20px, -30px) scale(1.1); }
            50% { transform: translate(-20px, 20px) scale(0.9); }
            75% { transform: translate(30px, 10px) scale(1.05); }
        }
        .animate-blob {
            animation: blob 15s ease-in-out infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        @keyframes bounce-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-bounce-slow {
            animation: bounce-slow 3s ease-in-out infinite;
        }

        /* CTA gradient animation */
        @keyframes gradient-x {
            0%, 100% { transform: translateX(-100%); }
            50% { transform: translateX(100%); }
        }
        .animate-gradient-x {
            animation: gradient-x 15s ease-in-out infinite;
        }

        /* Benefits float animation */
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-white">
    @yield('content')

    <!-- JavaScript for interactions -->
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('active');
        }

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

        // Language switcher
        function switchLanguage(locale) {
            window.location.href = '/lang/' + locale;
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('shadow-md', 'bg-white/95', 'backdrop-blur-sm');
            } else {
                navbar.classList.remove('shadow-md', 'bg-white/95', 'backdrop-blur-sm');
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
