@props(['name' => 'circle'])

@php
    $paths = [
        'grid' => 'M3 3h7v7H3V3Zm11 0h7v7h-7V3ZM3 14h7v7H3v-7Zm11 0h7v7h-7v-7Z',
        'key' => 'M14.5 6.5a5 5 0 1 0 1.1 5.9L22 6v4h-2V8h-2V6h-3.5ZM7 13a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z',
        'sliders' => 'M4 7h8m4 0h4M4 17h4m4 0h8M12 5v4M8 15v4',
        'chart' => 'M4 19V5m0 14h16M8 16v-5m4 5V8m4 8v-7',
        'clipboard' => 'M9 4h6l1 2h3v15H5V6h3l1-2Zm0 6h6m-6 4h6',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9a7 7 0 0 1 14 0',
        'teacher' => 'M4 5h16v10H4V5Zm5 16 3-6 3 6M8 9h8',
        'briefcase' => 'M9 6V4h6v2h5v13H4V6h5Zm0 5h6',
        'cash' => 'M3 7h18v10H3V7Zm9 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm-6-3h1m10 0h1',
        'users' => 'M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 21a5 5 0 0 1 10 0m-2-3a5 5 0 0 1 10 0',
        'calendar' => 'M6 3v4m12-4v4M4 8h16v12H4V8Zm4 4h2m4 0h2m-8 4h2m4 0h2',
        'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13v5l3 2',
        'grade' => 'M5 4h14v16H5V4Zm4 5h6m-6 4h6m-6 4h3',
        'check' => 'M20 6 9 17l-5-5',
        'menu' => 'M4 7h16M4 12h16M4 17h16',
        'close' => 'M6 6l12 12M18 6 6 18',
        'logout' => 'M10 4H5v16h5m4-4 4-4-4-4m4 4H9',
    ];

    $path = $paths[$name] ?? 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z';
@endphp

<svg {{ $attributes->merge(['class' => 'app-icon', 'viewBox' => '0 0 24 24', 'aria-hidden' => 'true']) }}>
    <path d="{{ $path }}" />
</svg>
