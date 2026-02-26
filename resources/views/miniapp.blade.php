<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>{{ $storeName ?? "Do'kon" }}</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        if (window.Telegram && window.Telegram.WebApp) {
            window.Telegram.WebApp.ready();
            window.Telegram.WebApp.expand();
        }
        // Telegram Desktop puts tgWebAppData in URL hash — this breaks Vue Router's hash history.
        // SDK already read the data, so we can safely clean the hash for Vue Router.
        if (window.location.hash && window.location.hash.includes('tgWebAppData')) {
            history.replaceState(null, '', window.location.pathname + window.location.search + '#/');
        }
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--tg-theme-bg-color, #ffffff);
            color: var(--tg-theme-text-color, #000000);
            -webkit-font-smoothing: antialiased;
        }
        .miniapp-loading {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; min-height: 100vh; gap: 16px; padding: 20px;
        }
        .miniapp-loading .store-icon {
            width: 72px; height: 72px; border-radius: 50%;
            background: var(--tg-theme-button-color, #2563eb);
            display: flex; align-items: center; justify-content: center; margin-bottom: 8px;
            overflow: hidden;
        }
        .miniapp-loading .store-icon img { width: 100%; height: 100%; object-fit: cover; }
        .miniapp-loading .store-icon svg { width: 32px; height: 32px; color: var(--tg-theme-button-text-color, #fff); }
        .miniapp-loading .store-name { font-size: 18px; font-weight: 600; color: var(--tg-theme-text-color, #000); }
        .miniapp-loading .loading-bar { width: 120px; height: 3px; background: var(--tg-theme-secondary-bg-color, #f0f0f0); border-radius: 3px; overflow: hidden; margin-top: 4px; }
        .miniapp-loading .loading-bar-inner { width: 40%; height: 100%; background: var(--tg-theme-button-color, #2563eb); border-radius: 3px; animation: loading-slide 1.2s ease-in-out infinite; }
        @keyframes loading-slide { 0% { transform: translateX(-100%); } 50% { transform: translateX(200%); } 100% { transform: translateX(-100%); } }
    </style>

    @php
        $cssFile = '';
        $jsFile = '';
        $cssImports = [];
        $baseAsset = '/build';
        try {
            $manifestPath = public_path('build/manifest.json');
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                $cssFile = $manifest['resources/css/miniapp.css']['file'] ?? '';
                $jsFile = $manifest['resources/js/miniapp/app.js']['file'] ?? '';
                $cssImports = $manifest['resources/js/miniapp/app.js']['css'] ?? [];
            }
        } catch (\Throwable $e) {
            \Log::error('[MiniApp] Manifest error', ['error' => $e->getMessage()]);
        }
    @endphp

    @if($cssFile)
        <link rel="stylesheet" href="{{ $baseAsset }}/{{ $cssFile }}" />
    @endif
    @foreach($cssImports as $css)
        <link rel="stylesheet" href="{{ $baseAsset }}/{{ $css }}" />
    @endforeach

    @if($jsFile)
        <script type="module" src="{{ $baseAsset }}/{{ $jsFile }}"></script>
    @endif
</head>
<body>
    <div id="miniapp" data-store-slug="{{ $storeSlug }}" data-store-type="{{ $storeType ?? 'ecommerce' }}" data-api-url="{{ $apiUrl }}">
        <div class="miniapp-loading">
            <div class="store-icon">
                @if(!empty($storeLogoUrl))
                    <img src="{{ $storeLogoUrl }}" alt="{{ $storeName ?? "Do'kon" }}" />
                @else
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z"/>
                    </svg>
                @endif
            </div>
            <div class="store-name">{{ $storeName ?? "Do'kon" }}</div>
            <div class="loading-bar"><div class="loading-bar-inner"></div></div>
        </div>
    </div>
</body>
</html>
