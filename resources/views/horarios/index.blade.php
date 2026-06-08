@extends('layouts.app')

@php
    $rol = auth()->user()?->rol;
    $isPostulante = $rol === 'Postulante';
    $isDocente = $rol === 'Docente';
    $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'];
    $materiaColors = ['#dbeafe', '#dcfce7', '#fef3c7', '#fce7f3', '#e0e7ff', '#ccfbf1', '#f5f5f4', '#ffedd5'];
@endphp

@if ($isPostulante)
    @section('title', 'Mi Horario')
    @section('subtitle', $grupo ? "Grupo: {$grupo->nombre_grupo}" : 'Aun no tiene grupo asignado.')

    @section('content')
        <div class="panel">
            @if (! $grupo)
                <p class="dashboard-empty">Aun no tiene un grupo asignado.</p>
            @elseif ($horarios->isEmpty())
                <p class="dashboard-empty">El horario de su grupo aun no esta disponible.</p>
            @else
                <div class="week-planner">
                    @foreach ($dias as $num => $nombre)
                        <div class="day-column">
                            <h4>{{ $nombre }}</h4>
                            @php $diaHorarios = $horarios->filter(fn($h) => (int) $h->detalle->dia === $num); @endphp
                            @forelse ($diaHorarios as $h)
                                @php $color = $h->detalle->id_materia ? $materiaColors[$h->detalle->id_materia % count($materiaColors)] : ''; @endphp
                                <div class="schedule-block" style="{{ $color ? "border-left:4px solid {$color}; background:{$color};" : '' }}">
                                    <strong>{{ substr((string) $h->detalle->hora_inicio, 0, 5) }} - {{ substr((string) $h->detalle->hora_fin, 0, 5) }}</strong>
                                    <span>{{ $h->detalle->materia?->nombre ?? 'Sin materia' }} / {{ $h->detalle->modalidad }}</span>
                                    <small>{{ $h->docente?->persona?->nombre_completo ?? 'Sin docente' }} — {{ $h->aula?->nombre ?? 'Sin aula' }}</small>
                                </div>
                            @empty
                                <span class="muted">Sin clases</span>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endsection

@elseif ($isDocente)
    @section('title', 'Mi Horario')
    @section('subtitle', 'Materias asignadas en todos sus grupos.')

    @section('content')
        <div class="panel">
            <form method="GET" action="{{ route('horarios.index') }}" class="reports-filter">
                <div>
                    <label for="semestre-docente">Semestre</label>
                    <select id="semestre-docente" name="semestre" onchange="this.form.submit()">
                        @foreach ($semestres as $semestre)
                            <option value="{{ $semestre->id_semestre }}" @selected((int) $semestreId === (int) $semestre->id_semestre)>
                                {{ $semestre->nombre }} - {{ $semestre->estado }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($grupos->isEmpty())
                <p class="dashboard-empty">No tiene asignaciones horarias.</p>
            @else
                @foreach ($grupos as $grupo)
                    @php $horarios = $horariosPorGrupo[$grupo->id_grupo] ?? collect(); @endphp
                    <div style="margin-bottom:24px;">
                        <h3 style="margin-bottom:8px;">{{ $grupo->nombre_grupo }} — {{ $grupo->semestre?->nombre }}</h3>
                        <div class="week-planner">
                            @foreach ($dias as $num => $nombre)
                                <div class="day-column">
                                    <h4>{{ $nombre }}</h4>
                                    @php $diaHorarios = $horarios->filter(fn($h) => (int) $h->detalle->dia === $num); @endphp
                                    @forelse ($diaHorarios as $h)
                                        @php $color = $h->detalle->id_materia ? $materiaColors[$h->detalle->id_materia % count($materiaColors)] : ''; @endphp
                                        <div class="schedule-block" style="{{ $color ? "border-left:4px solid {$color}; background:{$color};" : '' }}">
                                            <strong>{{ substr((string) $h->detalle->hora_inicio, 0, 5) }} - {{ substr((string) $h->detalle->hora_fin, 0, 5) }}</strong>
                                            <span>{{ $h->detalle->materia?->nombre ?? 'Sin materia' }} / {{ $h->detalle->modalidad }}</span>
                                            <small>{{ $h->aula?->nombre ?? 'Sin aula' }}</small>
                                        </div>
                                    @empty
                                        <span class="muted">Sin clases</span>
                                    @endforelse
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endsection

@else
    @section('title', 'Consultar Horarios')
    @section('subtitle', 'Seleccione un grupo para ver su horario asignado.')

    @section('content')
        <div class="panel">
            <x-filter-panel :action="route('horarios.index')">
                <div>
                    <label for="buscar">Buscar por grupo o semestre</label>
                    <input id="buscar" name="buscar" value="{{ $search ?? '' }}" data-filter-field placeholder="Ej. Grupo 1, 2026">
                </div>
                <div>
                    <label for="semestre">Semestre</label>
                    <select id="semestre" name="semestre" data-filter-field>
                        @foreach ($semestres as $semestre)
                            <option value="{{ $semestre->id_semestre }}" @selected((int) $semestreId === (int) $semestre->id_semestre)>
                                {{ $semestre->nombre }} - {{ $semestre->estado }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="estado">Horario</label>
                    <select id="estado" name="estado" data-filter-field>
                        <option value="">Todos</option>
                        <option value="con" @selected(($status ?? '') === 'con')>Con horario</option>
                        <option value="sin" @selected(($status ?? '') === 'sin')>Sin horario</option>
                    </select>
                </div>
                <x-slot:actions>
                    <a href="{{ route('horarios.index') }}" class="button secondary" data-clear-filters>Limpiar</a>
                </x-slot:actions>
            </x-filter-panel>

            <div data-results>
                @include('horarios.partials.table', ['grupos' => $grupos, 'horariosPorGrupo' => $horariosPorGrupo])
            </div>
        </div>

        <script>
        (function () {
            document.querySelector('[data-results]')?.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-toggle-horario]');
                if (!btn) return;
                const grupoId = btn.dataset.grupoId;
                const container = document.getElementById('horario-' + grupoId);
                if (!container) return;
                const isHidden = container.style.display === 'none';
                container.style.display = isHidden ? 'table-row' : 'none';
                btn.textContent = isHidden ? 'Ocultar horario' : 'Ver horario';
            });
        })();
        </script>
    @endsection
@endif
