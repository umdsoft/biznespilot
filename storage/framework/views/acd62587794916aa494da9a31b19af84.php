<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="scroll-smooth">
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
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <!-- ========== DEFAULT SEO META TAGS ========== -->
    <title inertia><?php echo e(config('app.name', 'BiznesPilot AI')); ?></title>
    <meta name="description" content="BiznesPilot - O'zbekistondagi #1 biznes boshqaruv platformasi. Marketing, Sotuv, Moliya - hammasi bir joyda." inertia>
    <meta name="author" content="BiznesPilot">
    <meta name="robots" content="index, follow">

    <!-- ========== DEFAULT OPEN GRAPH ========== -->
    <meta property="og:type" content="website" inertia>
    <meta property="og:site_name" content="BiznesPilot">
    <meta property="og:image" content="<?php echo e(asset('images/og-image.png')); ?>" inertia>
    <meta property="og:locale" content="<?php echo e(app()->getLocale() === 'ru' ? 'ru_RU' : 'uz_UZ'); ?>">

    <!-- ========== DEFAULT TWITTER CARDS ========== -->
    <meta name="twitter:card" content="summary_large_image" inertia>
    <meta name="twitter:site" content="@biznespilot">

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
    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('favicon.svg')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('favicon-32x32.png')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('favicon-16x16.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('apple-touch-icon.png')); ?>">
    <link rel="manifest" href="<?php echo e(asset('site.webmanifest')); ?>">

    <!-- ========== PRECONNECT & DNS PREFETCH ========== -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://fonts.bunny.net">

    <!-- ========== FONTS ========== -->
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <?php echo app('Tighten\Ziggy\BladeRouteGenerator')->generate(); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
</head>
<body class="antialiased bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } elseif (config('inertia.use_script_element_for_initial_page')) { ?><script data-page="app" type="application/json"><?php echo json_encode($page); ?></script><div id="app"></div><?php } else { ?><div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div><?php } ?>

    <!-- ========== NO-SCRIPT FALLBACK ========== -->
    <noscript>
        <div style="padding: 20px; text-align: center; background: #fef2f2; color: #991b1b;">
            <?php echo e(app()->getLocale() === 'ru'
                ? 'Для работы BiznesPilot требуется JavaScript. Пожалуйста, включите JavaScript в настройках браузера.'
                : 'BiznesPilot ishlashi uchun JavaScript kerak. Iltimos, brauzer sozlamalarida JavaScript ni yoqing.'); ?>

        </div>
    </noscript>
</body>
</html>
<?php /**PATH D:\marketing startap\biznespilot\resources\views\app.blade.php ENDPATH**/ ?>