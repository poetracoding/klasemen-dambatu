@php
    $_headLiga = $liga ?? \App\Models\Liga::current();
    $_headIconUrl = $_headLiga?->appIconUrl();
    $_headThemeColor = $_headLiga?->theme_color ?: '#4f46e5';
    $_headTagline = $_headLiga?->app_tagline ?: 'Portal resmi klasemen, jadwal, berita, dan arena hiburan ' . ($_headLiga?->nama_liga ?? 'Liga Dam Batu') . '.';
@endphp
<meta name="theme-color" content="{{ $_headThemeColor }}">
<meta name="description" content="{{ $_headTagline }}">
@if ($_headIconUrl)
    <link rel="icon" href="{{ $_headIconUrl }}">
    <link rel="shortcut icon" href="{{ $_headIconUrl }}">
    <link rel="apple-touch-icon" href="{{ $_headIconUrl }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
@endif
