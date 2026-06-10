@extends('layouts.app')

@section('title', 'Ejecutar Admision')
@section('subtitle', 'Asignacion automatica de cupos por promedio, opciones de carrera y cantidad actual de estudiantes.')

@section('topbar')
    <form method="POST" action="{{ route('admision.ejecutar') }}">
        @csrf
        <input type="hidden" name="id_semestre" value="{{ $parametro?->id_semestre }}">
        <button type="submit" class="button success" @disabled(! $puedeEjecutar)>
            <x-app-icon name="check" />
            <span>Ejecutar admision</span>
        </button>
    </form>
@endsection

@section('content')
    <div class="admission-layout">
        <section class="panel">
            <form method="GET" action="{{ route('admision.index') }}" class="reports-filter">
                <div>
                    <label for="semestre">Semestre</label>
                    <select id="semestre" name="semestre" onchange="this.form.submit()">
                        <?php foreach ($parametros as $item) { ?>
                            <option value="{{ $item->id_semestre }}" @selected((int) ($parametro?->id_semestre ?? 0) === (int) $item->id_semestre)>
                                {{ $item->semestre?->nombre ?? 'Sin semestre' }} - {{ $item->semestre?->estado ?? 'Sin estado' }}
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </form>
        </section>

        <section class="grid">
            <div class="metric">
                <span>Semestre seleccionado</span>
                <strong>{{ $parametro?->semestre?->nombre ?? 'Sin parametro' }}</strong>
                <small>Parametro usado para cupos, notas y ponderaciones.</small>
            </div>
            <div class="metric">
                <span>Total postulantes</span>
                <strong>{{ $postulantes->count() }}</strong>
                <small>Postulantes asignados a grupos del semestre.</small>
            </div>
            <div class="metric">
                <span>Aprobados</span>
                <strong>{{ $aprobados->count() }}</strong>
                <small>Ordenados por promedio mayor a menor.</small>
            </div>
            <div class="metric">
                <span>Estado del proceso</span>
                <strong>{{ $parametro?->semestre?->estado ?? ($puedeEjecutar ? 'Listo' : 'Pendiente') }}</strong>
                <small>{{ $todosConNotas ? 'Notas completas.' : 'Faltan validaciones.' }}</small>
            </div>
        </section>

        @if (! $puedeEjecutar)
            <section class="panel admission-warning">
                <h2>Validaciones pendientes</h2>
                <ul>
                    <?php foreach ($bloqueos as $bloqueo) { ?>
                        <li>{{ $bloqueo }}</li>
                    <?php } ?>
                </ul>
            </section>
        @endif

        <section class="panel">
            <div class="admission-section-head">
                <div>
                    <h2>Cupos por carrera</h2>
                    <p>Cantidad configurada en CU07 y cantidad actual usada para la asignacion.</p>
                </div>
                <span class="badge neutral">{{ $cupos->count() }} carreras</span>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Carrera</th>
                            <th>Cupos</th>
                            <th>Cantidad actual</th>
                            <th>Disponibles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cupos->isEmpty()) { ?>
                            <tr>
                                <td colspan="4" data-label="Cupos">No se han definido cupos por carrera.</td>
                            </tr>
                        <?php } ?>

                        <?php foreach ($cupos as $cupo) { ?>
                            <?php $disponibles = max(0, (int) $cupo->cantidad_cupos - (int) $cupo->cantidad_estudiantes); ?>
                            <tr>
                                <td data-label="Carrera">{{ $cupo->carrera?->nombre ?? 'Sin carrera' }}</td>
                                <td data-label="Cupos">{{ $cupo->cantidad_cupos }}</td>
                                <td data-label="Cantidad actual">{{ $cupo->cantidad_estudiantes ?? 0 }}</td>
                                <td data-label="Disponibles">
                                    <span class="badge {{ $disponibles > 0 ? 'ok' : 'off' }}">{{ $disponibles }}</span>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="admission-section-head">
                <div>
                    <h2>Orden de merito</h2>
                    <p>Postulantes aprobados con notas completas, ordenados por promedio final.</p>
                </div>
                <span class="badge neutral">Nota minima {{ $parametro?->nota_minima_aprobacion ?? 'N/A' }}</span>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Postulante</th>
                            <th>Promedio</th>
                            <th>Primera opcion</th>
                            <th>Segunda opcion</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($aprobados->isEmpty()) { ?>
                            <tr>
                                <td colspan="6" data-label="Orden de merito">No hay postulantes aprobados para la admision.</td>
                            </tr>
                        <?php } ?>

                        <?php foreach ($aprobados as $index => $item) { ?>
                            <?php $postulante = $item['postulante']; ?>
                            <tr>
                                <td data-label="#">{{ $index + 1 }}</td>
                                <td data-label="Postulante">{{ $postulante->persona?->nombre_completo ?? 'Sin nombre' }}</td>
                                <td data-label="Promedio">{{ number_format((float) $item['promedio'], 2) }}</td>
                                <td data-label="Primera opcion">{{ $postulante->carreraPrimera?->nombre ?? 'Sin definir' }}</td>
                                <td data-label="Segunda opcion">{{ $postulante->carreraSegunda?->nombre ?? 'Sin definir' }}</td>
                                <td data-label="Estado"><span class="badge ok">Aprobado</span></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="admission-section-head">
                <div>
                    <h2>Resultados de admision</h2>
                    <p>Postulantes admitidos agrupados por carrera luego de ejecutar el proceso.</p>
                </div>
                <span class="badge neutral">{{ $admitidosPorCarrera->flatten(1)->count() }} admitidos</span>
            </div>

            <div class="admission-results">
                <?php if ($cupos->isEmpty()) { ?>
                    <p class="dashboard-empty">Ejecute la admision cuando las validaciones esten completas.</p>
                <?php } ?>

                <?php foreach ($cupos as $cupo) { ?>
                    <?php $admitidos = $admitidosPorCarrera->get($cupo->id_carrera, collect()); ?>
                    <article class="admission-result-card">
                        <header>
                            <h3>{{ $cupo->carrera?->nombre ?? 'Sin carrera' }}</h3>
                            <span class="badge neutral">{{ $admitidos->count() }} admitidos</span>
                        </header>

                        <div class="admission-result-list">
                            <?php if ($admitidos->isEmpty()) { ?>
                                <p class="dashboard-empty">Sin admitidos en esta carrera.</p>
                            <?php } ?>

                            <?php foreach ($admitidos as $postulante) { ?>
                                <?php
                                    $tipo = 'Excepcion';
                                    if ((int) $postulante->id_carrera_admitido === (int) $postulante->id_carrera_primera_opc) {
                                        $tipo = 'Primera opcion';
                                    } elseif ((int) $postulante->id_carrera_admitido === (int) $postulante->id_carrera_segunda_opc) {
                                        $tipo = 'Segunda opcion';
                                    }
                                ?>
                                <div>
                                    <strong>{{ $postulante->persona?->nombre_completo ?? 'Sin nombre' }}</strong>
                                    <span>{{ $tipo }}</span>
                                </div>
                            <?php } ?>
                        </div>
                    </article>
                <?php } ?>
            </div>
        </section>

        <form method="POST" action="{{ route('admision.ejecutar') }}" class="admission-mobile-action">
            @csrf
            <input type="hidden" name="id_semestre" value="{{ $parametro?->id_semestre }}">
            <button type="submit" class="button success" @disabled(! $puedeEjecutar)>
                <x-app-icon name="check" />
                <span>Ejecutar admision</span>
            </button>
        </form>
    </div>
@endsection
