<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @inject('settingService', 'App\Services\SettingService')
        @php
            $appSetting = $settingService->getSettings();
            $appLogo = $appSetting->logo_url ?: asset('gudang.png');
            $appName = $appSetting->app_name ?? config('app.name');
            $displayTitle = $title ? str_replace('Stockify', $appName, $title) : $appName;
        @endphp

        <link rel="icon" href="{{ $appLogo }}">
        <link rel="apple-touch-icon" href="{{ $appLogo }}">

        <title>{{ $displayTitle }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body>
        <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

        @if (request()->routeIs('login') || request()->routeIs('register') ||request()->routeIs('test'))

        @else
            <livewire:navbar />
        @endif
        {{ $slot }}


        @livewireScripts
    </body>
</html>
