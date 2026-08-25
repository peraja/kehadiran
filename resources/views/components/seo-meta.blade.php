@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'url' => null,
    'type' => 'website',
    'robots' => 'index, follow',
])

@php
    $defaultTitle = 'eRapat | Pemkab Sinjai';
    $finalTitle = $title ? $title : $defaultTitle;

    $defaultDescription = 'Manajemen Rapat Pemerintah Kabupaten Sinjai';
    $finalDescription = $description ? $description : $defaultDescription;

    $finalImage = $image ? $image : asset('img/meta.png');
    $finalUrl = $url ? $url : url()->current();
    $finalRobots = $robots ? $robots : 'index, follow';
@endphp

<!-- Primary Meta Tags -->
<title>{{ $finalTitle }}</title>
<meta name="title" content="{{ $finalTitle }}">
<meta name="description" content="{{ $finalDescription }}">
<meta name="robots" content="{{ $finalRobots }}">
<link rel="canonical" href="{{ $finalUrl }}">

<!-- Open Graph / Facebook / WhatsApp -->
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="eRapat Pemkab Sinjai">
<meta property="og:url" content="{{ $finalUrl }}">
<meta property="og:title" content="{{ $finalTitle }}">
<meta property="og:description" content="{{ $finalDescription }}">
<meta property="og:image" content="{{ $finalImage }}">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $finalUrl }}">
<meta name="twitter:title" content="{{ $finalTitle }}">
<meta name="twitter:description" content="{{ $finalDescription }}">
<meta name="twitter:image" content="{{ $finalImage }}">
