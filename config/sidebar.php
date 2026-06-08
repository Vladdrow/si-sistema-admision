<?php

return [
    [
        'title' => 'Inicio',
        'items' => [
            ['label' => 'Panel principal', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'grid'],
            ['label' => 'Gestionar contrasena', 'route' => 'password.edit', 'active' => 'password.*', 'icon' => 'key'],
        ],
    ],
    [
        'title' => 'Seguridad',
        'items' => [
            ['label' => 'Credenciales', 'route' => 'credenciales.index', 'active' => 'credenciales.*', 'icon' => 'key', 'admin' => true],
            ['label' => 'Parametros del Proceso de admision', 'route' => 'parametros.index', 'active' => 'parametros.*', 'icon' => 'sliders', 'admin' => true],
            ['label' => 'Reportes', 'route' => 'reportes.index', 'active' => 'reportes.*', 'icon' => 'chart'],
            ['label' => 'Bitacora', 'route' => 'bitacora.index', 'active' => 'bitacora.*', 'icon' => 'clipboard', 'admin' => true],
        ],
    ],
    [
        'title' => 'Personas',
        'items' => [
            ['label' => 'Postulantes', 'route' => 'postulantes.index', 'active' => 'postulantes.*', 'icon' => 'user', 'admin' => true],
            ['label' => 'Docentes', 'route' => 'docentes.index', 'active' => 'docentes.*', 'icon' => 'teacher', 'admin' => true],
            ['label' => 'Personal administrativo', 'route' => 'personal.index', 'active' => 'personal.*', 'icon' => 'briefcase', 'admin' => true],
            ['label' => 'Pagos', 'route' => 'pagos.index', 'active' => 'pagos.*', 'icon' => 'cash', 'admin' => true],
        ],
    ],
    [
        'title' => 'Academico',
        'items' => [
            ['label' => 'Grupos', 'route' => 'grupos.index', 'active' => 'grupos.*', 'icon' => 'users', 'admin' => true],
            ['label' => 'Plantillas de horario', 'route' => 'plantillas.index', 'active' => 'plantillas.*', 'icon' => 'calendar', 'admin' => true],
            ['label' => 'Horarios', 'route' => 'horarios.index', 'active' => 'horarios.*', 'icon' => 'clock', 'admin' => true],
            ['label' => 'Notas', 'route' => 'notas.consulta', 'active' => 'notas.consulta*', 'icon' => 'grade', 'admin' => true],
            ['label' => 'Admision', 'route' => 'admision.index', 'active' => 'admision.*', 'icon' => 'check'],
        ],
    ],
];
