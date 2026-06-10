@extends('layouts.app')

@section('title', 'Generar Reportes')
@section('subtitle', 'Consulta consolidada de postulantes, notas, grupos, docentes y resultados del proceso.')

@section('content')
    @php
        $exportParams = ['semestre' => $semestreId, 'reporte' => $reportType];
        $currentReport = $reportOptions[$reportType] ?? 'Reporte';
        $comparativaPromedios = $comparativaGestiones->pluck('promedio_general')->filter(fn ($item) => $item !== null);
        $metricTotalInscritos = $reportType === 'comparativa_gestiones' ? $comparativaGestiones->sum('inscritos') : $postulantesReporte->count();
        $metricAprobados = $reportType === 'comparativa_gestiones' ? $comparativaGestiones->sum('aprobados') : $aprobados->count();
        $metricReprobados = $reportType === 'comparativa_gestiones' ? $comparativaGestiones->sum('reprobados') : $reprobados->count();
        $metricPromedio = $reportType === 'comparativa_gestiones'
            ? ($comparativaPromedios->isNotEmpty() ? round((float) $comparativaPromedios->avg(), 2) : null)
            : $promedioGeneral;
        $metricGrupos = $reportType === 'comparativa_gestiones' ? $comparativaGestiones->sum('grupos') : $grupos->count();
    @endphp

    <div class="reports-layout">
        <section class="panel report-console">
            <form method="GET" action="{{ route('reportes.index') }}" class="reports-filter" data-auto-submit-form>
                <div>
                    <label for="semestre">Semestre</label>
                    <select id="semestre" name="semestre" data-filter-field data-auto-submit>
                        @foreach ($semestres as $semestre)
                            <option value="{{ $semestre->id_semestre }}" @selected((int) $semestreId === (int) $semestre->id_semestre)>
                                {{ $semestre->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="reporte">Reporte</label>
                    <select id="reporte" name="reporte" data-filter-field data-auto-submit>
                        @foreach ($reportOptions as $value => $label)
                            <option value="{{ $value }}" @selected($reportType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('reportes.export', $exportParams + ['formato' => 'csv']) }}" class="button secondary" data-export="csv">CSV (Excel)</a>
                <a href="{{ route('reportes.export', $exportParams + ['formato' => 'pdf']) }}" class="button secondary" data-export="pdf" target="_blank">PDF</a>
            </form>
        </section>

        <section class="dashboard-metrics report-summary">
            <article class="metric">
                <span>{{ $reportType === 'comparativa_gestiones' ? 'Total historico' : 'Total inscritos' }}</span>
                <strong>{{ $metricTotalInscritos }}</strong>
                <small>{{ $reportType === 'comparativa_gestiones' ? 'Postulantes de todas las gestiones.' : 'Postulantes asignados al semestre.' }}</small>
            </article>
            <article class="metric">
                <span>Aprobados</span>
                <strong>{{ $metricAprobados }}</strong>
                <small>{{ $metricTotalInscritos > 0 ? number_format(($metricAprobados / $metricTotalInscritos) * 100, 1) : 0 }}% del total.</small>
            </article>
            <article class="metric">
                <span>Reprobados</span>
                <strong>{{ $metricReprobados }}</strong>
                <small>{{ $metricTotalInscritos > 0 ? number_format(($metricReprobados / $metricTotalInscritos) * 100, 1) : 0 }}% del total.</small>
            </article>
            <article class="metric">
                <span>Promedio general</span>
                <strong>{{ $metricPromedio !== null ? number_format($metricPromedio, 2) : 'N/A' }}</strong>
                <small>Calculado con las ponderaciones.</small>
            </article>
            <article class="metric">
                <span>{{ $reportType === 'comparativa_gestiones' ? 'Grupos historicos' : 'Grupos habilitados' }}</span>
                <strong>{{ $metricGrupos }}</strong>
                <small>{{ $reportType === 'comparativa_gestiones' ? 'Suma de grupos por gestion.' : 'Capacidad ' . ($capacidadGrupo ?: 'N/A') . '.' }}</small>
            </article>
        </section>

        <section class="panel">
            <div class="reports-section-head">
                <div>
                    <h2>{{ $currentReport }}</h2>
                    <p>
                        @switch($reportType)
                            @case('aprobados')
                                Misma lista general filtrada por estado academico aprobado.
                                @break
                            @case('reprobados')
                                Misma lista general filtrada por estado academico reprobado.
                                @break
                            @case('promedios')
                                Promedio final por postulante y promedio general del semestre.
                                @break
                            @case('grupos')
                                Grupos creados para el semestre y ocupacion registrada.
                                @break
                            @case('materias')
                                Rendimiento por materia: promedio, aprobados, reprobados y pendientes.
                                @break
                            @case('docentes')
                                Docentes vinculados a cada grupo y materia asignada.
                                @break
                            @case('grupos_aprobados')
                                Ranking de grupos ordenados por mayor cantidad de aprobados.
                                @break
                            @case('ranking_admision')
                                Admitidos ordenados por carrera y promedio para revisar el merito de ingreso.
                                @break
                            @case('distribucion_carrera')
                                Resumen de preferencias, cupos y admitidos por carrera.
                                @break
                            @case('comparativa_gestiones')
                                Comparacion historica de inscripcion, rendimiento e ingreso entre semestres.
                                @break
                            @case('final_admitidos')
                                Cierre del proceso con los admitidos finales agrupados por carrera.
                                @break
                            @default
                                Datos principales, grupo, opciones de carrera, promedio y estado.
                        @endswitch
                    </p>
                </div>
                <span class="badge neutral">
                    @switch($reportType)
                        @case('materias')
                            {{ $estadisticasMateria->count() }} materias
                            @break
                        @case('grupos')
                            {{ $grupos->count() }} grupos
                            @break
                        @case('docentes')
                            {{ $docentesPorGrupo->count() }} grupos
                            @break
                        @case('grupos_aprobados')
                            {{ $gruposConAprobados->count() }} grupos
                            @break
                        @case('ranking_admision')
                        @case('final_admitidos')
                            {{ $reportRows->total() }} admitidos
                            @break
                        @case('distribucion_carrera')
                            {{ $reportRows->total() }} carreras
                            @break
                        @case('comparativa_gestiones')
                            {{ $reportRows->total() }} gestiones
                            @break
                        @case('aprobados')
                        @case('reprobados')
                        @case('promedios')
                            {{ $reportRows->total() }} postulantes
                            @break
                        @default
                            {{ $reportRows->total() }} registros
                    @endswitch
                </span>
            </div>

            @if (in_array($reportType, ['general', 'aprobados', 'reprobados'], true))
                <div class="table-wrap">
                    <table class="table reports-table">
                        <thead>
                            <tr>
                                <th>Postulante</th>
                                <th>CI</th>
                                <th>Grupo</th>
                                <th>1ra opcion</th>
                                <th>2da opcion</th>
                                <th>Admitido en</th>
                                <th>Promedio</th>
                                <th>Estado academico</th>
                                <th>Estado admision</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportRows as $fila)
                                <tr>
                                    <td data-label="Postulante">{{ $fila['nombre'] }}</td>
                                    <td data-label="CI">{{ $fila['ci'] }}</td>
                                    <td data-label="Grupo">{{ $fila['grupo'] }}</td>
                                    <td data-label="1ra opcion">{{ $fila['primera_opcion'] }}</td>
                                    <td data-label="2da opcion">{{ $fila['segunda_opcion'] }}</td>
                                    <td data-label="Admitido en">{{ $fila['carrera_admitida'] }}</td>
                                    <td data-label="Promedio">{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pendiente' }}</td>
                                    <td data-label="Estado academico">
                                        <span class="badge {{ $fila['estado_academico'] === 'Aprobado' ? 'ok' : ($fila['estado_academico'] === 'Reprobado' ? 'off' : 'neutral') }}">
                                            {{ $fila['estado_academico'] }}
                                        </span>
                                    </td>
                                    <td data-label="Estado admision">{{ $fila['estado_admision'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" data-label="Postulantes">No hay postulantes para este criterio.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($reportType === 'promedios')
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Postulante</th>
                                <th>CI</th>
                                <th>Grupo</th>
                                <th>Promedio final</th>
                                <th>Estado academico</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportRows as $fila)
                                <tr>
                                    <td data-label="Postulante">{{ $fila['nombre'] }}</td>
                                    <td data-label="CI">{{ $fila['ci'] }}</td>
                                    <td data-label="Grupo">{{ $fila['grupo'] }}</td>
                                    <td data-label="Promedio final">{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pendiente' }}</td>
                                    <td data-label="Estado academico">{{ $fila['estado_academico'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" data-label="Promedios">No hay promedios disponibles.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($reportType === 'grupos')
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Grupo</th>
                                <th>Postulantes</th>
                                <th>Capacidad configurada</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportRows as $grupo)
                                <tr>
                                    <td data-label="Grupo">{{ $grupo->nombre_grupo }}</td>
                                    <td data-label="Postulantes">{{ $grupo->postulanteGrupos()->count() }}</td>
                                    <td data-label="Capacidad configurada">{{ $capacidadGrupo ?: 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" data-label="Grupos">No hay grupos habilitados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($reportType === 'materias')
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Materia</th>
                                <th>Promedio</th>
                                <th>Aprobados</th>
                                <th>Reprobados</th>
                                <th>Pendientes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportRows as $fila)
                                <tr>
                                    <td data-label="Materia">{{ $fila['materia']->nombre }}</td>
                                    <td data-label="Promedio">{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pendiente' }}</td>
                                    <td data-label="Aprobados">{{ $fila['aprobados'] }}</td>
                                    <td data-label="Reprobados">{{ $fila['reprobados'] }}</td>
                                    <td data-label="Pendientes">{{ $fila['pendientes'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" data-label="Materias">No hay datos suficientes de notas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($reportType === 'docentes')
                <div class="reports-list compact-report-list">
                    @forelse ($reportRows as $grupo)
                        <div>
                            <strong>{{ $grupo['grupo'] }}</strong>
                            @foreach ($grupo['asignaciones'] as $asignacion)
                                <span>{{ $asignacion['docente'] }} - {{ $asignacion['materia'] }}</span>
                            @endforeach
                        </div>
                    @empty
                        <p class="dashboard-empty">No hay docentes asignados a grupos.</p>
                    @endforelse
                </div>
            @elseif ($reportType === 'grupos_aprobados')
                <div class="reports-list compact-report-list">
                    @forelse ($reportRows as $fila)
                        <div>
                            <strong>{{ $fila['grupo']->nombre_grupo }}</strong>
                            <span>{{ $fila['aprobados'] }} aprobados de {{ $fila['postulantes'] }} postulantes</span>
                        </div>
                    @empty
                        <p class="dashboard-empty">No hay grupos para comparar.</p>
                    @endforelse
                </div>
            @elseif ($reportType === 'ranking_admision')
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Carrera</th>
                                <th>Pos.</th>
                                <th>Postulante</th>
                                <th>CI</th>
                                <th>Promedio</th>
                                <th>Ingreso por</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportRows as $fila)
                                <tr>
                                    <td data-label="Carrera">{{ $fila['carrera'] }}</td>
                                    <td data-label="Pos.">{{ $fila['posicion'] }}</td>
                                    <td data-label="Postulante">{{ $fila['nombre'] }}</td>
                                    <td data-label="CI">{{ $fila['ci'] }}</td>
                                    <td data-label="Promedio">{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pendiente' }}</td>
                                    <td data-label="Ingreso por">{{ $fila['ingreso_por'] }}</td>
                                    <td data-label="Estado">{{ $fila['estado_admision'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" data-label="Ranking">No hay admitidos para ranking.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($reportType === 'distribucion_carrera')
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Carrera</th>
                                <th>1ra opcion</th>
                                <th>2da opcion</th>
                                <th>Admitidos</th>
                                <th>Cupos</th>
                                <th>Estudiantes actuales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportRows as $fila)
                                <tr>
                                    <td data-label="Carrera">{{ $fila['carrera'] }}</td>
                                    <td data-label="1ra opcion">{{ $fila['primera_opcion'] }}</td>
                                    <td data-label="2da opcion">{{ $fila['segunda_opcion'] }}</td>
                                    <td data-label="Admitidos">{{ $fila['admitidos'] }}</td>
                                    <td data-label="Cupos">{{ $fila['cupos'] }}</td>
                                    <td data-label="Estudiantes actuales">{{ $fila['estudiantes_actuales'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" data-label="Distribucion">No hay carreras registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($reportType === 'comparativa_gestiones')
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Semestre</th>
                                <th>Estado</th>
                                <th>Inscritos</th>
                                <th>Aprobados</th>
                                <th>Reprobados</th>
                                <th>Admitidos</th>
                                <th>% ingreso</th>
                                <th>Promedio</th>
                                <th>Grupos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportRows as $fila)
                                <tr>
                                    <td data-label="Semestre">{{ $fila['semestre'] }}</td>
                                    <td data-label="Estado">{{ $fila['estado'] }}</td>
                                    <td data-label="Inscritos">{{ $fila['inscritos'] }}</td>
                                    <td data-label="Aprobados">{{ $fila['aprobados'] }}</td>
                                    <td data-label="Reprobados">{{ $fila['reprobados'] }}</td>
                                    <td data-label="Admitidos">{{ $fila['admitidos'] }}</td>
                                    <td data-label="% ingreso">{{ number_format((float) $fila['porcentaje_ingreso'], 2) }}%</td>
                                    <td data-label="Promedio">{{ $fila['promedio_general'] !== null ? number_format((float) $fila['promedio_general'], 2) : 'N/A' }}</td>
                                    <td data-label="Grupos">{{ $fila['grupos'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" data-label="Comparativa">No hay semestres para comparar.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($reportType === 'final_admitidos')
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Carrera admitida</th>
                                <th>Pos.</th>
                                <th>Postulante</th>
                                <th>CI</th>
                                <th>Promedio</th>
                                <th>1ra opcion</th>
                                <th>2da opcion</th>
                                <th>Ingreso por</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportRows as $fila)
                                <tr>
                                    <td data-label="Carrera admitida">{{ $fila['carrera'] }}</td>
                                    <td data-label="Pos.">{{ $fila['posicion'] }}</td>
                                    <td data-label="Postulante">{{ $fila['nombre'] }}</td>
                                    <td data-label="CI">{{ $fila['ci'] }}</td>
                                    <td data-label="Promedio">{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pendiente' }}</td>
                                    <td data-label="1ra opcion">{{ $fila['primera_opcion'] }}</td>
                                    <td data-label="2da opcion">{{ $fila['segunda_opcion'] }}</td>
                                    <td data-label="Ingreso por">{{ $fila['ingreso_por'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" data-label="Admitidos">No hay admitidos finales.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($reportRows->hasPages())
                <div class="pagination-wrap">
                    {{ $reportRows->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>

    <script>
        document.querySelectorAll('[data-auto-submit]').forEach((field) => {
            field.addEventListener('change', () => {
                const form = field.closest('[data-auto-submit-form]');
                form?.querySelector('input[name="page"]')?.remove();
                form?.submit();
            });
        });
    </script>
@endsection
