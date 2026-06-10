@extends('layouts.app')

@section('title', 'Configurar Parametros de Admision')
@section('subtitle', 'Define fechas, pagos, notas y limites operativos del proceso.')

@section('content')
    <div class="panel">
        <div id="page-message" class="inline-message"></div>

        <section
            class="template-workbench"
            id="simple-workspace"
            data-simple-crud
            data-kind="parametro"
            data-active-semester="{{ $existeSemestreActivo ? '1' : '0' }}"
        >
            <div class="template-workbench-header">
                <div>
                    <h2 id="simple-modal-title">Nuevo parametro</h2>
                    <p class="subtitle" id="simple-modal-subtitle">Configure el semestre, fechas, cupos, ponderaciones y limites del proceso.</p>
                </div>
                <button class="ghost" type="button" data-simple-close>Limpiar</button>
            </div>
            <form id="simple-form" data-store-url="{{ route('parametros.store') }}">
                <div id="simple-modal-message" class="inline-message"></div>
                <div class="app-modal-grid">
                    <div class="field">
                        <label for="param-semestre-nombre">Nuevo semestre</label>
                        <input
                            id="param-semestre-nombre"
                            name="semestre_nombre"
                            maxlength="6"
                            pattern="[12]-[0-9]{4}"
                            required
                            data-simple-field="semestreNombre"
                            placeholder="Ej. 1-2026"
                            title="Use el formato 1-2026 o 2-2026"
                        >
                    </div>
                    <div class="field">
                        <label for="param-monto-pago">Monto pago</label>
                        <input id="param-monto-pago" name="monto_pago" type="number" min="0.01" step="0.01" required data-simple-field="montoPago">
                    </div>
                    <div class="field">
                        <label for="param-fecha-inicio">Inicio inscripcion</label>
                        <input id="param-fecha-inicio" name="fecha_inicio_inscripcion" type="datetime-local" required data-simple-field="fechaInicioInscripcion">
                    </div>
                    <div class="field">
                        <label for="param-fecha-cierre">Cierre inscripcion</label>
                        <input id="param-fecha-cierre" name="fecha_cierre_inscripcion" type="datetime-local" required data-simple-field="fechaCierreInscripcion">
                    </div>
                    <div class="field">
                        <label for="param-cierre-notas">Cierre notas</label>
                        <input id="param-cierre-notas" name="fecha_cierre_notas" type="datetime-local" required data-simple-field="fechaCierreNotas">
                    </div>
                    <div class="field">
                        <label for="param-max-estudiantes">Max estudiantes por grupo</label>
                        <input id="param-max-estudiantes" name="max_estudiante_grupo" type="number" min="1" required data-simple-field="maxEstudianteGrupo">
                    </div>
                    <div class="field">
                        <label for="param-nota-minima">Nota minima aprobacion</label>
                        <input id="param-nota-minima" name="nota_minima_aprobacion" type="number" min="0" max="100" step="0.01" required data-simple-field="notaMinimaAprobacion">
                    </div>
                    <div class="field">
                        <label for="param-max-grupos">Max grupos por docente</label>
                        <input id="param-max-grupos" name="max_grupos_docente" type="number" min="1" required data-simple-field="maxGruposDocente">
                    </div>
                    <div class="field">
                        <label for="param-expiracion">Expiracion pago (minutos)</label>
                        <input id="param-expiracion" name="tiempo_expiracion_pago" type="number" min="1" required data-simple-field="tiempoExpiracionPago">
                    </div>
                </div>

                <div class="parameter-rules-grid">
                    <section class="parameter-rule">
                        <div class="section-header">
                            <h3>Cupos por carrera</h3>
                        </div>
                        <div class="parameter-rule-list">
                            <div class="parameter-rule-row parameter-career-row parameter-rule-head">
                                <span>Carrera</span>
                                <span>Cupos</span>
                                <span>Actuales</span>
                            </div>
                            @foreach ($carreras as $carrera)
                                <div class="parameter-rule-row parameter-career-row">
                                    <span>{{ $carrera->nombre }}</span>
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        required
                                        data-cupo-input
                                        data-career-id="{{ $carrera->id_carrera }}"
                                        data-default-value="0"
                                        aria-label="Cupos para {{ $carrera->nombre }}"
                                    >
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        required
                                        data-current-students-input
                                        data-career-id="{{ $carrera->id_carrera }}"
                                        data-default-value="0"
                                        aria-label="Cantidad actual de estudiantes para {{ $carrera->nombre }}"
                                    >
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="parameter-rule">
                        <div class="section-header">
                            <h3>Ponderaciones</h3>
                        </div>
                        <div class="parameter-rule-list">
                            @foreach ([1 => 30, 2 => 30, 3 => 40] as $numeroExamen => $default)
                                <div class="parameter-rule-row">
                                    <span>Examen {{ $numeroExamen }}</span>
                                    <input
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        required
                                        data-ponderacion-input
                                        data-exam-number="{{ $numeroExamen }}"
                                        data-default-value="{{ $default }}"
                                        aria-label="Ponderacion examen {{ $numeroExamen }}"
                                    >
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="template-workbench-footer">
                    <button class="secondary" type="button" data-simple-close>Limpiar</button>
                    <button type="submit" id="save-simple">Guardar parametro</button>
                </div>
            </form>
        </section>

        <x-filter-panel :action="route('parametros.index')">
            <div>
                <label for="buscar">Buscar por semestre</label>
                <input id="buscar" name="buscar" value="{{ $search }}" data-filter-field placeholder="Ej. 1/2026">
            </div>
            <div>
                <label for="semestre">Semestre</label>
                <select id="semestre" name="semestre" data-filter-field>
                    <option value="">Todos</option>
                    @foreach ($semestres as $item)
                        <option value="{{ $item->id_semestre }}" @selected($semester === (string) $item->id_semestre)>{{ $item->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <x-slot:actions>
                <a href="{{ route('parametros.index') }}" class="button secondary" data-clear-filters>Limpiar</a>
            </x-slot:actions>
        </x-filter-panel>

        <div data-results>
            @include('seguridad.parametros.partials.table', ['parametros' => $parametros])
        </div>
    </div>
@endsection
