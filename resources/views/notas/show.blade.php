@extends('layouts.app')

@section('title', "Notas: {$grupo->nombre_grupo}")
@section('subtitle', $materia ? "Materia: {$materia->nombre}" : 'Seleccione una materia.')

@section('content')
    <div class="panel">
        <div class="notes-toolbar">
            <a href="{{ route('notas.index') }}" class="button secondary">Volver a grupos</a>
        </div>

        @if ($materias->isEmpty())
            <p class="dashboard-empty">No tiene materias asignadas en este grupo.</p>
        @else
            <form class="notes-controls" method="GET" action="{{ route('notas.show', $grupo->id_grupo) }}">
                <div class="field">
                    <label for="materia-select">Materia</label>
                    <select id="materia-select" name="materia" onchange="this.form.submit()">
                        @foreach ($materias as $item)
                            <option value="{{ $item->id_materia }}" @selected($materiaId === (int) $item->id_materia)>{{ $item->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($examenes->isNotEmpty())
                    <div class="field">
                        <label for="examen-select">Examen</label>
                        <select id="examen-select" name="examen" onchange="this.form.submit()">
                            @foreach ($examenes as $examen)
                                <option value="{{ $examen->id_examen }}" @selected($examenActualModel?->id_examen === $examen->id_examen)>
                                    Examen {{ $examen->numero_examen }} - {{ $examen->ponderacion }}%
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>

            @if ($materia)
                @if ($cerrado)
                    <div class="notes-alert">
                        <strong>La fecha de cierre de notas ya paso.</strong>
                        <span>No se pueden registrar ni modificar calificaciones.</span>
                    </div>
                @endif

                @php
                    $totalPostulantes = $postulantes->count();
                    $completaron = 0;
                    $aprobadosCount = 0;
                    $reprobadosCount = 0;
                    foreach ($postulantes as $postulanteResumen) {
                        $notas = $notasPorPostulante[$postulanteResumen->id_postulante] ?? [];
                        $validas = collect($notas)->filter(fn ($nota) => $nota !== null);
                        if ($validas->count() >= $examenes->count() && $examenes->isNotEmpty()) {
                            $completaron++;
                            $promedioResumen = round($validas->avg('nota'), 2);
                            if ($promedioResumen >= $notaMinima) {
                                $aprobadosCount++;
                            } else {
                                $reprobadosCount++;
                            }
                        }
                    }
                @endphp

                <section class="dashboard-metrics notes-metrics">
                    <article class="metric">
                        <span>Postulantes</span>
                        <strong>{{ $totalPostulantes }}</strong>
                        <small>En este grupo</small>
                    </article>
                    <article class="metric">
                        <span>Examen seleccionado</span>
                        <strong>{{ $examenActualModel ? 'N° ' . $examenActualModel->numero_examen : '-' }}</strong>
                        <small>{{ $examenActualModel ? $examenActualModel->ponderacion . '% de ponderacion' : 'Sin examenes configurados' }}</small>
                    </article>
                    <article class="metric">
                        <span>Aprobados</span>
                        <strong>{{ $aprobadosCount }}</strong>
                        <small>Nota minima: {{ $notaMinima }}</small>
                    </article>
                    <article class="metric">
                        <span>Pendientes</span>
                        <strong>{{ max($totalPostulantes - $completaron, 0) }}</strong>
                        <small>{{ $reprobadosCount }} reprobados</small>
                    </article>
                </section>

                @if ($totalPostulantes === 0)
                    <p class="dashboard-empty">No hay postulantes en este grupo.</p>
                @elseif ($examenes->isEmpty() || ! $examenActualModel)
                    <p class="dashboard-empty">No hay examenes configurados para este semestre.</p>
                @else
                    <form method="POST" action="{{ route('notas.store', $grupo->id_grupo) }}" id="notas-form">
                        @csrf
                        <input type="hidden" name="id_materia" value="{{ $materiaId }}">
                        <input type="hidden" name="id_examen" value="{{ $examenActualModel->id_examen }}">

                        <div class="table-scroll">
                            <table class="data-table notes-table">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>Postulante</th>
                                        @foreach ($examenes as $examen)
                                            <th class="center">Ex. {{ $examen->numero_examen }}<br><small>{{ $examen->ponderacion }}%</small></th>
                                        @endforeach
                                        <th class="center">Promedio</th>
                                        <th class="center">Estado</th>
                                        <th class="center">Nota Ex. {{ $examenActualModel->numero_examen }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($postulantes as $index => $postulante)
                                        @php
                                            $notasExamenes = $notasPorPostulante[$postulante->id_postulante] ?? [];
                                            $notasValidas = collect($notasExamenes)->filter(fn ($nota) => $nota !== null);
                                            $promedio = $notasValidas->isNotEmpty() ? round($notasValidas->avg('nota'), 2) : null;
                                            $completo = $notasValidas->count() >= $examenes->count();
                                            $estado = $completo ? ($promedio >= $notaMinima ? 'Aprobado' : 'Reprobado') : 'Pendiente';
                                            $notaActual = $notasExamenes[$examenActualModel->id_examen] ?? null;
                                        @endphp
                                        <tr class="notes-row notes-row-{{ strtolower($estado) }}">
                                            <td class="muted">{{ $index + 1 }}</td>
                                            <td>
                                                <span class="person-line">
                                                    <strong>{{ $postulante->persona?->nombre_completo ?? 'Sin nombre' }}</strong>
                                                    <span class="muted">{{ $postulante->persona?->credencial?->registro ?? 'Sin registro' }}</span>
                                                </span>
                                            </td>
                                            @foreach ($examenes as $examen)
                                                @php $nota = $notasExamenes[$examen->id_examen] ?? null; @endphp
                                                <td class="center">
                                                    @if ($nota)
                                                        <strong>{{ number_format((float) $nota->nota, 2) }}</strong>
                                                    @else
                                                        <span class="muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="center"><strong>{{ $promedio ?? '-' }}</strong></td>
                                            <td class="center">
                                                <span class="badge {{ $estado === 'Aprobado' ? 'ok' : ($estado === 'Reprobado' ? 'off' : 'neutral') }}">{{ $estado }}</span>
                                            </td>
                                            <td class="center">
                                                @if (! $cerrado)
                                                    <input
                                                        class="notes-input"
                                                        type="number"
                                                        name="notas[{{ $postulante->id_postulante }}]"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        value="{{ $notaActual ? number_format((float) $notaActual->nota, 2, '.', '') : '' }}"
                                                        placeholder="0.00"
                                                    >
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if (! $cerrado)
                            <div class="notes-actions">
                                <button type="submit" class="button primary">Guardar notas</button>
                            </div>
                        @endif
                    </form>
                @endif
            @endif
        @endif
    </div>
@endsection
