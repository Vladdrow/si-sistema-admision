<aside class="app-sidebar" id="app-sidebar" data-sidebar>
    <div class="sidebar-brand">
        <span>Sistema de Admision</span>
        <button class="icon-button sidebar-close" type="button" data-sidebar-close aria-label="Cerrar menu">
            <x-app-icon name="close" />
        </button>
    </div>

    <div class="userbox">
        <strong>{{ auth()->user()?->persona?->nombre_completo ?? auth()->user()?->registro }}</strong>
        <span>{{ auth()->user()?->rol }}</span>
    </div>

    @php
        $user = auth()->user();
        $sidebarGroups = match (true) {
            $user?->esPostulante() => [
                [
                    'title' => 'Inicio',
                    'items' => [
                        ['label' => 'Panel principal', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'grid'],
                        ['label' => 'Mi horario', 'route' => 'horarios.index', 'active' => 'horarios.*', 'icon' => 'clock'],
                        ['label' => 'Materias y notas', 'route' => 'dashboard', 'fragment' => 'mis-notas', 'active' => 'dashboard', 'icon' => 'grade'],
                        ['label' => 'Gestionar contrasena', 'route' => 'password.edit', 'active' => 'password.*', 'icon' => 'key'],
                    ],
                ],
            ],
            $user?->esDocente() => [
                [
                    'title' => 'Inicio',
                    'items' => [
                        ['label' => 'Panel principal', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'grid'],
                        ['label' => 'Mi horario', 'route' => 'horarios.index', 'active' => 'horarios.*', 'icon' => 'clock'],
                        ['label' => 'Mis grupos', 'route' => 'dashboard', 'fragment' => 'mis-grupos', 'active' => 'dashboard', 'icon' => 'users'],
                        ['label' => 'Gestionar notas', 'route' => 'notas.index', 'active' => 'notas.*', 'icon' => 'grade'],
                        ['label' => 'Gestionar contrasena', 'route' => 'password.edit', 'active' => 'password.*', 'icon' => 'key'],
                    ],
                ],
            ],
            $user?->esPersonalAdministrativo() => [
                [
                    'title' => 'Inicio',
                    'items' => [
                        ['label' => 'Panel principal', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'grid'],
                        ['label' => 'Gestionar contrasena', 'route' => 'password.edit', 'active' => 'password.*', 'icon' => 'key'],
                    ],
                ],
                [
                    'title' => 'Personas',
                    'items' => [
                        ['label' => 'Postulantes', 'route' => 'postulantes.index', 'active' => 'postulantes.*', 'icon' => 'user'],
                        ['label' => 'Docentes', 'route' => 'docentes.index', 'active' => 'docentes.*', 'icon' => 'teacher'],
                        ['label' => 'Pagos', 'route' => 'pagos.index', 'active' => 'pagos.*', 'icon' => 'cash'],
                    ],
                ],
                [
                    'title' => 'Academico',
                    'items' => [
                        ['label' => 'Grupos', 'route' => 'grupos.index', 'active' => 'grupos.*', 'icon' => 'users'],
                        ['label' => 'Plantillas de horario', 'route' => 'plantillas.index', 'active' => 'plantillas.*', 'icon' => 'calendar'],
                        ['label' => 'Horarios', 'route' => 'horarios.index', 'active' => 'horarios.*', 'icon' => 'clock'],
                        ['label' => 'Notas', 'route' => 'notas.consulta', 'active' => 'notas.consulta*', 'icon' => 'grade'],
                        ['label' => 'Admision', 'route' => 'admision.index', 'active' => 'admision.*', 'icon' => 'check'],
                        ['label' => 'Reportes', 'route' => 'reportes.index', 'active' => 'reportes.*', 'icon' => 'chart'],
                    ],
                ],
            ],
            default => config('sidebar'),
        };
    @endphp

    @foreach ($sidebarGroups as $group)
        <div class="nav-title">{{ $group['title'] }}</div>

        @foreach ($group['items'] as $item)
            @continue(($item['admin'] ?? false) && ! auth()->user()?->esAdministrador())

            @php
                $href = isset($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
                $href .= isset($item['fragment']) ? "#{$item['fragment']}" : '';
                $active = isset($item['active']) && request()->routeIs($item['active']);
                $disabled = $href === '#';
            @endphp

            <a class="nav-link {{ $active ? 'active' : '' }} {{ $disabled ? 'disabled' : '' }}" href="{{ $href }}">
                <span class="nav-link-content">
                    <x-app-icon :name="$item['icon'] ?? 'circle'" />
                    <span>{{ $item['label'] }}</span>
                </span>
            </a>
        @endforeach
    @endforeach

    <div class="nav-title">Sesion</div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="button danger sidebar-logout">
            <x-app-icon name="logout" />
            <span>Cerrar sesion</span>
        </button>
    </form>
</aside>
