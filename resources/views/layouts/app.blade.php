<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Admision')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
    <div class="mobile-header">
        <span class="mobile-brand">@yield('title', 'Sistema de Admision')</span>
        <button class="icon-button" type="button" data-sidebar-open aria-controls="app-sidebar" aria-expanded="false" aria-label="Abrir menu">
            <x-app-icon name="menu" />
        </button>
    </div>

    <div class="sidebar-backdrop" data-sidebar-backdrop></div>

    <div class="shell">
        @include('partials.sidebar')

        <main class="main">
            <div class="topbar">
                <div>
                    <h1 class="title">@yield('title')</h1>
                    @hasSection('subtitle')
                        <p class="subtitle">@yield('subtitle')</p>
                    @endif
                </div>
                @yield('topbar')
            </div>

            @if (session('status'))
                <div class="alert ok">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="content-wrap">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
