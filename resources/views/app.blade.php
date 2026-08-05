<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <title>HomeBudget</title>
    @if (! app()->runningUnitTests())
      @vite(['resources/js/main.ts'])
    @endif
  </head>
  <body>
    <div id="app"></div>
  </body>
</html>
