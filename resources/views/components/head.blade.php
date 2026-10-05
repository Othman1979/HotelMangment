@props(['title' => null])
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' - ' : '' }}{{ __('Hotel Management') }}</title>
<link rel="stylesheet" href="{{ asset(app()->getLocale() === 'ar' ? 'lib/bootstrap/dist/css/bootstrap.rtl.min.css' : 'lib/bootstrap/dist/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
<link rel="stylesheet" href="{{ asset('css/hotel.css') }}?v={{ filemtime(public_path('css/hotel.css')) }}">
<meta name="theme-color" content="#f3f3f3">
