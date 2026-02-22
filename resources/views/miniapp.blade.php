<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>{{ $storeName ?? "Do'kon" }}</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @vite(['resources/css/miniapp.css', 'resources/js/miniapp/app.js'])
</head>
<body>
    <div id="miniapp" data-store-slug="{{ $storeSlug }}" data-api-url="{{ $apiUrl }}"></div>
</body>
</html>
