@extends('layouts.app')

@section('title', 'Gestionar Plantillas de Horario')
@section('subtitle', 'Define turnos y bloques reutilizables para asignar horarios.')

@section('content')
    <div class="panel">
        <div id="page-message" class="inline-message"></div>

        <section class="template-workbench" id="template-workspace">
            <div class="template-workbench-header">
                <div>
                    <h2 id="template-modal-title">Nueva plantilla</h2>
                    <p class="subtitle" id="template-modal-subtitle"></p>
                </div>
                <button class="ghost" type="button" data-template-close>Limpiar</button>
            </div>
            <form id="template-form" data-store-url="{{ route('plantillas.store') }}">
                <div id="template-modal-message" class="inline-message"></div>
                <div class="app-modal-grid">
                    <div class="field">
                        <label for="template-name">Nombre</label>
                        <input id="template-name" name="nombre" maxlength="30" required>
                    </div>
                    <div class="field">
                        <label for="template-shift">Turno</label>
                        <select id="template-shift" name="turno" required>
                            @foreach ($shifts as $item)
                                <option value="{{ $item }}">{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="section-header">
                    <h3>Bloques de horario</h3>
                </div>
                <div class="schedule-composer">
                    <div class="field schedule-days-field">
                        <label>Dias</label>
                        <div id="template-detail-days" class="day-picker" data-template-input="dias"></div>
                    </div>
                    <div class="field">
                        <label for="template-detail-start">Inicio</label>
                        <input id="template-detail-start" type="time" step="60" data-template-input="hora_inicio">
                    </div>
                    <div class="field">
                        <label for="template-detail-duration">Duracion</label>
                        <select id="template-detail-duration" data-template-input="duracion">
                            <option value="30">30 min</option>
                            <option value="45">45 min</option>
                            <option value="60" selected>1 hora</option>
                            <option value="90">1 hora 30 min</option>
                            <option value="120">2 horas</option>
                            <option value="150">2 horas 30 min</option>
                            <option value="180">3 horas</option>
                            <option value="custom">Manual</option>
                        </select>
                    </div>
                    <div class="field custom-duration-field" hidden>
                        <label for="template-detail-duration-custom">Minutos</label>
                        <input id="template-detail-duration-custom" type="number" min="1" max="720" step="1" data-template-input="duracion_custom" placeholder="Ej. 75">
                    </div>
                    <div class="field">
                        <label for="template-detail-subject">Materia</label>
                        <select id="template-detail-subject" data-template-input="id_materia">
                            @foreach ($materias as $materia)
                                <option value="{{ $materia->id_materia }}">{{ $materia->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="template-detail-mode">Modalidad</label>
                        <select id="template-detail-mode" data-template-input="modalidad">
                            <option value="Presencial">Presencial</option>
                            <option value="Virtual">Virtual</option>
                        </select>
                    </div>
                    <button type="button" data-template-add-detail>Agregar bloque</button>
                </div>
                <div class="schedule-preview" id="template-detail-preview"></div>
                <div id="template-details" class="week-planner"></div>
                <div class="template-workbench-footer">
                    <button class="secondary" type="button" data-template-close>Limpiar</button>
                    <button type="submit" id="save-template">Guardar plantilla</button>
                </div>
            </form>
        </section>

        <x-filter-panel :action="route('plantillas.index')">
            <div>
                <label for="buscar">Buscar por nombre</label>
                <input id="buscar" name="buscar" value="{{ $search }}" data-filter-field placeholder="Ej. Intensivo manana">
            </div>
            <div>
                <label for="turno">Turno</label>
                <select id="turno" name="turno" data-filter-field>
                    <option value="">Todos</option>
                    @foreach ($shifts as $item)
                        <option value="{{ $item }}" @selected($shift === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <x-slot:actions>
                <a href="{{ route('plantillas.index') }}" class="button secondary" data-clear-filters>Limpiar</a>
            </x-slot:actions>
        </x-filter-panel>

        <div data-results>
            @include('academico.plantillas.partials.table', ['plantillas' => $plantillas])
        </div>
    </div>
@endsection
