@extends('layouts.app')

@section('title', $isPostulante ? 'Mi panel academico' : ($isDocente ? 'Panel docente' : 'Panel principal'))
@section('subtitle', $isPostulante ? 'Horario, materias y notas del postulante.' : ($isDocente ? 'Horario y grupos asignados.' : 'Resumen operativo del Sistema de Admision FICCT-UAGRM.'))

@section('content')
    @if ($isPostulante)
        @php
            $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'];
            $totalMaterias = $materias->count();
            $materiasConPromedio = $materias->filter(fn ($materia) => $materia['promedio'] !== null);
            $promedioGeneral = $materiasConPromedio->isNotEmpty() ? round($materiasConPromedio->avg('promedio'), 2) : null;
        @endphp

        <section class="student-hero">
            <div>
                <span class="dashboard-eyebrow">FICCT - UAGRM</span>
                <h2>{{ $postulante?->persona?->nombre_completo ?? auth()->user()?->registro }}</h2>
                <p>Este es tu espacio de seguimiento: primero tu horario semanal y despues el avance por materia con notas de examenes y promedio.</p>
            </div>
            <div class="student-status-card">
                <span>Estado de admision</span>
                <strong>{{ $postulante?->estado_admision ?? 'Sin registro' }}</strong>
                <small>{{ $grupo ? "Grupo {$grupo->nombre_grupo}" : 'Aun sin grupo asignado' }}</small>
            </div>
        </section>

        <section class="dashboard-metrics student-metrics">
            <article class="metric">
                <span>Grupo</span>
                <strong>{{ $grupo->nombre_grupo ?? '-' }}</strong>
                <small>{{ $grupo->semestre ?? 'Sin semestre asignado' }}</small>
            </article>
            <article class="metric">
                <span>Materias</span>
                <strong>{{ $totalMaterias }}</strong>
                <small>Registradas para seguimiento</small>
            </article>
            <article class="metric">
                <span>Promedio general</span>
                <strong>{{ $promedioGeneral ?? '-' }}</strong>
                <small>Segun notas registradas</small>
            </article>
            <article class="metric">
                <span>Nota minima</span>
                <strong>{{ $notaMinima }}</strong>
                <small>Referencia de aprobacion</small>
            </article>
        </section>

        <section class="dashboard-panel student-schedule-panel" id="mi-horario">
            <div class="dashboard-panel-header">
                <div>
                    <h3>Mi horario</h3>
                    <p>{{ $grupo ? 'Bloques asignados para tu grupo actual.' : 'Cuando seas asignado a un grupo, tu horario aparecera aqui.' }}</p>
                </div>
                <span class="badge neutral">Semana academica</span>
            </div>

            <div class="student-schedule-grid">
                @foreach ($dias as $diaNumero => $diaNombre)
                    <article class="student-day">
                        <h4>{{ $diaNombre }}</h4>
                        @forelse (($horario[$diaNumero] ?? collect()) as $bloque)
                            <div class="student-time-block">
                                <strong>{{ substr((string) $bloque->hora_inicio, 0, 5) }} - {{ substr((string) $bloque->hora_fin, 0, 5) }}</strong>
                                <span>{{ $bloque->modalidad }}{{ $bloque->aula ? " - Aula {$bloque->aula}" : '' }}</span>
                                <small>{{ $bloque->docente ?: ($bloque->plantilla ?: 'Bloque academico') }}</small>
                            </div>
                        @empty
                            <p class="dashboard-empty">Sin clases</p>
                        @endforelse
                    </article>
                @endforeach
            </div>
        </section>

        <section class="student-subsection" id="mis-notas">
            <div class="dashboard-panel-header">
                <div>
                    <h3>Materias y notas</h3>
                    <p>Detalle por examen y promedio calculado por materia.</p>
                </div>
            </div>

            <div class="subject-grid">
                @forelse ($materias as $materia)
                    <article class="subject-card">
                        <div class="subject-card-head">
                            <div>
                                <span>{{ $materia['codigo'] }}</span>
                                <h4>{{ $materia['nombre'] }}</h4>
                            </div>
                            <span class="badge {{ $materia['estado'] === 'Aprobado' ? 'ok' : ($materia['estado'] === 'En riesgo' ? 'off' : 'neutral') }}">
                                {{ $materia['estado'] }}
                            </span>
                        </div>

                        <div class="exam-list">
                            @forelse ($materia['notas'] as $nota)
                                <div class="exam-row">
                                    <span>Examen {{ $nota->numero_examen }}</span>
                                    <strong>{{ number_format((float) $nota->nota, 2) }}</strong>
                                </div>
                            @empty
                                <p class="dashboard-empty">Aun no hay notas registradas.</p>
                            @endforelse
                        </div>

                        <div class="subject-average">
                            <span>Promedio</span>
                            <strong>{{ $materia['promedio'] ?? '-' }}</strong>
                        </div>
                    </article>
                @empty
                    <div class="dashboard-panel">
                        <p class="dashboard-empty">Aun no hay materias registradas para mostrar.</p>
                    </div>
                @endforelse
            </div>
        </section>
    @elseif ($isDocente)
        @php
            $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'];
            $totalPostulantes = $grupos->sum('postulantes');
        @endphp

        <section class="teacher-hero">
            <div>
                <span class="dashboard-eyebrow">FICCT - UAGRM</span>
                <h2>{{ $docente?->persona?->nombre_completo ?? auth()->user()?->registro }}</h2>
                <p>Revise sus bloques de clase y los grupos asignados para el curso preuniversitario.</p>
            </div>
            <div class="student-status-card">
                <span>Registro docente</span>
                <strong>{{ auth()->user()?->registro }}</strong>
                <small>{{ $docente?->titulo_profesional ?? 'Docente asignado' }}</small>
            </div>
        </section>

        <section class="dashboard-metrics student-metrics">
            <article class="metric">
                <span>Grupos asignados</span>
                <strong>{{ $grupos->count() }}</strong>
                <small>Segun horario actual</small>
            </article>
            <article class="metric">
                <span>Postulantes</span>
                <strong>{{ $totalPostulantes }}</strong>
                <small>Total en sus grupos</small>
            </article>
            <article class="metric">
                <span>Bloques semanales</span>
                <strong>{{ $horario->flatten(1)->count() }}</strong>
                <small>Clases asignadas</small>
            </article>
            <article class="metric">
                <span>Rol</span>
                <strong>Docente</strong>
                <small>Seguimiento academico</small>
            </article>
        </section>

        <section class="dashboard-panel student-schedule-panel" id="mi-horario">
            <div class="dashboard-panel-header">
                <div>
                    <h3>Mi horario</h3>
                    <p>{{ $horario->isNotEmpty() ? 'Bloques de clase asignados por dia.' : 'Aun no tiene bloques de horario asignados.' }}</p>
                </div>
                <span class="badge neutral">Semana docente</span>
            </div>

            <div class="student-schedule-grid">
                @foreach ($dias as $diaNumero => $diaNombre)
                    <article class="student-day">
                        <h4>{{ $diaNombre }}</h4>
                        @forelse (($horario[$diaNumero] ?? collect()) as $bloque)
                            <div class="student-time-block teacher-block">
                                <strong>{{ substr((string) $bloque->hora_inicio, 0, 5) }} - {{ substr((string) $bloque->hora_fin, 0, 5) }}</strong>
                                <span>{{ $bloque->nombre_grupo }}{{ $bloque->aula ? " - Aula {$bloque->aula}" : '' }}</span>
                                <small>{{ $bloque->modalidad }}{{ $bloque->turno ? " - {$bloque->turno}" : '' }}</small>
                            </div>
                        @empty
                            <p class="dashboard-empty">Sin clases</p>
                        @endforelse
                    </article>
                @endforeach
            </div>
        </section>

        <section class="student-subsection" id="mis-grupos">
            <div class="dashboard-panel-header">
                <div>
                    <h3>Mis grupos</h3>
                    <p>Vista rapida de los grupos asignados. Luego cada card podra abrir el listado de postulantes.</p>
                </div>
            </div>

            <div class="teacher-group-grid">
                @forelse ($grupos as $grupo)
                    <article class="teacher-group-card" aria-label="Grupo {{ $grupo['nombre'] }}">
                        <div class="teacher-group-top">
                            <span>{{ $grupo['semestre'] ?? 'Sin semestre' }}</span>
                            <strong>{{ $grupo['nombre'] }}</strong>
                        </div>
                        <div class="teacher-group-stats">
                            <div>
                                <span>Postulantes</span>
                                <strong>{{ $grupo['postulantes'] }}</strong>
                            </div>
                            <div>
                                <span>Bloques</span>
                                <strong>{{ $grupo['bloques'] }}</strong>
                            </div>
                        </div>
                        <small>{{ $grupo['turno'] ?? 'Turno no definido' }}</small>
                    </article>
                @empty
                    <div class="dashboard-panel">
                        <p class="dashboard-empty">Aun no tiene grupos asignados.</p>
                    </div>
                @endforelse
            </div>
        </section>
    @else
        <section class="dashboard-hero">
            <div>
                <span class="dashboard-eyebrow">FICCT - UAGRM</span>
                <h2>Gestion del proceso de admision</h2>
                <p>Revise el estado general del sistema, parametros vigentes, seguridad de acceso y actividad administrativa reciente.</p>
            </div>
            <div class="dashboard-user">
                <span>Sesion activa</span>
                <strong>{{ auth()->user()?->persona?->nombre_completo ?? auth()->user()?->registro }}</strong>
                <small>{{ auth()->user()?->rol }} - {{ auth()->user()?->registro }}</small>
            </div>
        </section>

        <section class="dashboard-metrics">
            <article class="metric">
                <span>Postulantes</span>
                <strong>{{ $resumen['postulantes'] }}</strong>
                <small>Registros del proceso</small>
            </article>
            <article class="metric">
                <span>Docentes</span>
                <strong>{{ $resumen['docentes'] }}</strong>
                <small>Disponibles para grupos</small>
            </article>
            <article class="metric">
                <span>Personal administrativo</span>
                <strong>{{ $resumen['personal'] }}</strong>
                <small>Usuarios de gestion</small>
            </article>
            <article class="metric">
                <span>Plantillas de horario</span>
                <strong>{{ $resumen['plantillas'] }}</strong>
                <small>Bloques reutilizables</small>
            </article>
        </section>

        <div class="dashboard-grid">
            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <div>
                        <h3>Parametro de admision</h3>
                        <p>{{ $parametroVigente ? 'Proceso actualmente dentro del periodo de inscripcion.' : 'No hay un periodo de inscripcion activo.' }}</p>
                    </div>
                    @if ($isAdmin)
                        <a class="button secondary compact" href="{{ route('parametros.index') }}">Gestionar</a>
                    @endif
                </div>

                @php($parametro = $parametroVigente ?? $ultimoParametro)
                @if ($parametro)
                    <div class="dashboard-facts">
                        <div><span>Semestre</span><strong>{{ $parametro->semestre?->nombre }}</strong></div>
                        <div><span>Inscripcion</span><strong>{{ $parametro->fecha_inicio_inscripcion?->format('d/m/Y') }} - {{ $parametro->fecha_cierre_inscripcion?->format('d/m/Y') }}</strong></div>
                        <div><span>Monto</span><strong>Bs {{ $parametro->monto_pago }}</strong></div>
                        <div><span>Nota minima</span><strong>{{ $parametro->nota_minima_aprobacion }}</strong></div>
                    </div>
                @else
                    <p class="dashboard-empty">Aun no se configuraron parametros de admision.</p>
                @endif
            </section>

            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <div>
                        <h3>Estado de postulantes</h3>
                        <p>Distribucion por estado de admision.</p>
                    </div>
                    @if ($isAdmin)
                        <a class="button secondary compact" href="{{ route('postulantes.index') }}">Revisar</a>
                    @endif
                </div>

                <div class="status-list">
                    @forelse ($postulantesPorEstado as $estado => $total)
                        <div class="status-row">
                            <span>{{ $estado ?: 'Sin estado' }}</span>
                            <strong>{{ $total }}</strong>
                        </div>
                    @empty
                        <p class="dashboard-empty">Todavia no hay postulantes registrados.</p>
                    @endforelse
                </div>
            </section>

            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <div>
                        <h3>Seguridad y acceso</h3>
                        <p>Estado de credenciales del sistema.</p>
                    </div>
                    @if ($isAdmin)
                        <a class="button secondary compact" href="{{ route('credenciales.index') }}">Gestionar</a>
                    @endif
                </div>

                <div class="dashboard-facts two">
                    <div><span>Activas</span><strong>{{ $resumen['credenciales_activas'] }}</strong></div>
                    <div><span>Inactivas</span><strong>{{ $resumen['credenciales_inactivas'] }}</strong></div>
                    <div><span>Personas registradas</span><strong>{{ $resumen['personas'] }}</strong></div>
                </div>
            </section>

            <section class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <div>
                        <h3>Accesos rapidos</h3>
                        <p>Modulos de uso frecuente.</p>
                    </div>
                </div>

                <div class="quick-actions">
                    @if ($isAdmin)
                        <a href="{{ route('plantillas.index') }}">Plantillas de horario</a>
                        <a href="{{ route('docentes.index') }}">Docentes</a>
                        <a href="{{ route('personal.index') }}">Personal administrativo</a>
                        <a href="{{ route('bitacora.index') }}">Bitacora</a>
                    @endif
                    <a href="{{ route('password.edit') }}">Gestionar contrasena</a>
                </div>
            </section>
        </div>

        @if ($isAdmin)
            <section class="dashboard-panel activity-panel">
                <div class="dashboard-panel-header">
                    <div>
                        <h3>Actividad reciente</h3>
                        <p>Ultimos movimientos registrados en la bitacora.</p>
                    </div>
                    <a class="button secondary compact" href="{{ route('bitacora.index') }}">Ver bitacora</a>
                </div>

                <div class="activity-list">
                    @forelse ($actividadReciente as $actividad)
                        <div class="activity-item">
                            <div>
                                <strong>{{ $actividad->accion }} - {{ $actividad->modulo }}</strong>
                                <span>{{ $actividad->descripcion }}</span>
                            </div>
                            <small>{{ $actividad->fecha_hora?->format('d/m/Y H:i') }}</small>
                        </div>
                    @empty
                        <p class="dashboard-empty">Aun no hay actividad registrada.</p>
                    @endforelse
                </div>
            </section>
        @endif
    @endif
@endsection
