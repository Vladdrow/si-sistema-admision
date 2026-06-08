@extends('layouts.app')

@section('title', 'Generar Reportes')
@section('subtitle', 'Consulta consolidada de postulantes, notas, grupos, docentes y resultados del proceso.')

@section('content')
    <div class="reports-layout">
        <section class="panel">
            <form method="GET" action="{{ route('reportes.index') }}" class="reports-filter">
                <div>
                    <label for="semestre">Semestre</label>
                    <select id="semestre" name="semestre" data-filter-field>
                        <?php foreach ($semestres as $semestre) { ?>
                            <option value="{{ $semestre->id_semestre }}" @selected((int) $semestreId === (int) $semestre->id_semestre)>
                                {{ $semestre->nombre }}
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <button type="submit">Consultar</button>
                <a href="{{ route('reportes.export', ['formato' => 'csv', 'semestre' => $semestreId]) }}" class="button secondary" data-export="csv">CSV (Excel)</a>
                <a href="{{ route('reportes.export', ['formato' => 'pdf', 'semestre' => $semestreId]) }}" class="button secondary" data-export="pdf" target="_blank">PDF (imprimir)</a>
            </form>
        </section>

        <section class="grid">
            <div class="metric">
                <span>Total inscritos</span>
                <strong>{{ $postulantesReporte->count() }}</strong>
                <small>Postulantes asignados al semestre.</small>
            </div>
            <div class="metric">
                <span>Aprobados</span>
                <strong>{{ $aprobados->count() }}</strong>
                <small>Con notas completas y promedio suficiente.</small>
            </div>
            <div class="metric">
                <span>Reprobados</span>
                <strong>{{ $reprobados->count() }}</strong>
                <small>Con notas completas bajo la nota minima.</small>
            </div>
            <div class="metric">
                <span>Promedio general</span>
                <strong>{{ $promedioGeneral !== null ? number_format($promedioGeneral, 2) : 'N/A' }}</strong>
                <small>Calculado con las ponderaciones del semestre.</small>
            </div>
            <div class="metric">
                <span>Grupos habilitados</span>
                <strong>{{ $grupos->count() }}</strong>
                <small>Calculados: {{ $gruposCalculados }} con capacidad {{ $capacidadGrupo ?: 'N/A' }}.</small>
            </div>
        </section>

        <section class="panel">
            <div class="reports-section-head">
                <div>
                    <h2>Lista general de postulantes</h2>
                    <p>Datos principales, grupo, opciones de carrera, promedio y estado.</p>
                </div>
                <span class="badge neutral">{{ $postulantesReporte->count() }} registros</span>
            </div>
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
                        <?php if ($postulantesReporte->isEmpty()) { ?>
                            <tr><td colspan="9" data-label="Postulantes">No hay postulantes para el semestre seleccionado.</td></tr>
                        <?php } ?>
                        <?php foreach ($postulantesReporte as $fila) { ?>
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
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="reports-two-column">
            <section class="panel">
                <div class="reports-section-head">
                    <div>
                        <h2>Postulantes aprobados</h2>
                        <p>Reporte obligatorio de aprobados.</p>
                    </div>
                    <span class="badge ok">{{ $aprobados->count() }}</span>
                </div>
                <div class="reports-list">
                    <?php if ($aprobados->isEmpty()) { ?>
                        <p class="dashboard-empty">No hay aprobados.</p>
                    <?php } ?>
                    <?php foreach ($aprobados as $fila) { ?>
                        <div>
                            <strong>{{ $fila['nombre'] }}</strong>
                            <span>{{ $fila['grupo'] }} - Promedio {{ number_format((float) $fila['promedio'], 2) }}</span>
                        </div>
                    <?php } ?>
                </div>
            </section>

            <section class="panel">
                <div class="reports-section-head">
                    <div>
                        <h2>Postulantes reprobados</h2>
                        <p>Reporte obligatorio de reprobados.</p>
                    </div>
                    <span class="badge off">{{ $reprobados->count() }}</span>
                </div>
                <div class="reports-list">
                    <?php if ($reprobados->isEmpty()) { ?>
                        <p class="dashboard-empty">No hay reprobados.</p>
                    <?php } ?>
                    <?php foreach ($reprobados as $fila) { ?>
                        <div>
                            <strong>{{ $fila['nombre'] }}</strong>
                            <span>{{ $fila['grupo'] }} - Promedio {{ number_format((float) $fila['promedio'], 2) }}</span>
                        </div>
                    <?php } ?>
                </div>
            </section>
        </div>

        <section class="panel">
            <div class="reports-section-head">
                <div>
                    <h2>Cantidad de grupos habilitados</h2>
                    <p>Grupos reales del semestre y cantidad de postulantes asignados.</p>
                </div>
                <span class="badge neutral">{{ $grupos->count() }} grupos</span>
            </div>
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
                        <?php if ($grupos->isEmpty()) { ?>
                            <tr><td colspan="3" data-label="Grupos">No hay grupos habilitados.</td></tr>
                        <?php } ?>
                        <?php foreach ($grupos as $grupo) { ?>
                            <tr>
                                <td data-label="Grupo">{{ $grupo->nombre_grupo }}</td>
                                <td data-label="Postulantes">{{ $grupo->postulanteGrupos()->count() }}</td>
                                <td data-label="Capacidad configurada">{{ $capacidadGrupo ?: 'N/A' }}</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="reports-section-head">
                <div>
                    <h2>Estadisticas por materia</h2>
                    <p>Promedio y cantidad de aprobados, reprobados y pendientes por materia.</p>
                </div>
                <span class="badge neutral">{{ $estadisticasMateria->count() }} materias</span>
            </div>
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
                        <?php if ($estadisticasMateria->isEmpty()) { ?>
                            <tr><td colspan="5" data-label="Materias">No hay datos suficientes de notas.</td></tr>
                        <?php } ?>
                        <?php foreach ($estadisticasMateria as $fila) { ?>
                            <tr>
                                <td data-label="Materia">{{ $fila['materia']->nombre }}</td>
                                <td data-label="Promedio">{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pendiente' }}</td>
                                <td data-label="Aprobados">{{ $fila['aprobados'] }}</td>
                                <td data-label="Reprobados">{{ $fila['reprobados'] }}</td>
                                <td data-label="Pendientes">{{ $fila['pendientes'] }}</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="reports-two-column">
            <section class="panel">
                <div class="reports-section-head">
                    <div>
                        <h2>Docentes por grupos</h2>
                        <p>Docentes y materias asignadas a cada grupo.</p>
                    </div>
                </div>
                <div class="reports-list">
                    <?php if ($docentesPorGrupo->isEmpty()) { ?>
                        <p class="dashboard-empty">No hay docentes asignados a grupos.</p>
                    <?php } ?>
                    <?php foreach ($docentesPorGrupo as $grupo) { ?>
                        <div>
                            <strong>{{ $grupo['grupo'] }}</strong>
                            <?php foreach ($grupo['asignaciones'] as $asignacion) { ?>
                                <span>{{ $asignacion['docente'] }} - {{ $asignacion['materia'] }}</span>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </section>

            <section class="panel">
                <div class="reports-section-head">
                    <div>
                        <h2>Grupos con mayor cantidad de aprobados</h2>
                        <p>Ordenados de mayor a menor cantidad de aprobados.</p>
                    </div>
                </div>
                <div class="reports-list">
                    <?php if ($gruposConAprobados->isEmpty()) { ?>
                        <p class="dashboard-empty">No hay grupos para comparar.</p>
                    <?php } ?>
                    <?php foreach ($gruposConAprobados as $fila) { ?>
                        <div>
                            <strong>{{ $fila['grupo']->nombre_grupo }}</strong>
                            <span>{{ $fila['aprobados'] }} aprobados de {{ $fila['postulantes'] }} postulantes</span>
                        </div>
                    <?php } ?>
                </div>
            </section>
        </div>
    </div>
@endsection
