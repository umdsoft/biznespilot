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
            width: 64px; height: 64px; border-radius: 16px;
            background: var(--tg-theme-button-color, #2563eb);
            display: flex; align-items: center; justify-content: center; margin-bottom: 8px;
        }
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
    <div id="miniapp" data-store-slug="{{ $storeSlug }}" data-api-url="{{ $apiUrl }}">
        <div class="miniapp-loading">
            <div class="store-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="store-name">{{ $storeName ?? "Do'kon" }}</div>
            <div class="loading-bar"><div class="loading-bar-inner"></div></div>
        </div>
    </div>
</body>
</html>
