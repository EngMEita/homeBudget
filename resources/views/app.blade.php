<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17453b">
    <meta name="application-name" content="HomeBudget">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="HomeBudget">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/build/manifest.webmanifest">
    <link rel="icon" href="/pwa-icon.svg" type="image/svg+xml">
    <title>HomeBudget</title>
    @if (! app()->runningUnitTests())
      @vite(['resources/js/main.ts'])
    @endif
  </head>
  <body>
    <div id="app"></div>
  </body>
</html>
