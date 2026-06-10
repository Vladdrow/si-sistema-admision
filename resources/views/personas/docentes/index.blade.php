@extends('layouts.app')

@section('title', 'Gestionar Docentes')
@section('subtitle', 'Registra, lista, modifica, desactiva y restaura docentes. Al registrar se genera automaticamente un numero de registro y la contrasena sera el CI.')

@section('content')
    <div class="panel">
        <div id="page-message" class="inline-message"></div>

        <section class="template-workbench" id="teacher-workspace">
            <div class="template-workbench-header">
                <div>
                    <h2 id="teacher-modal-title">Registrar docente</h2>
                    <p class="subtitle" id="teacher-modal-subtitle"></p>
                </div>
                <button class="ghost" type="button" data-teacher-close>Limpiar</button>
            </div>

            <form id="teacher-form" data-store-url="{{ route('docentes.store') }}">
                <div id="teacher-modal-message" class="inline-message"></div>
                <input type="hidden" id="teacher-id">

                <div class="app-modal-grid">
                        <div class="field">
                            <label for="teacher-ci">CI</label>
                            <input id="teacher-ci" name="ci" maxlength="20" required>
                        </div>
                        <div class="field">
                            <label for="teacher-nombres">Nombres</label>
                            <input id="teacher-nombres" name="nombres" maxlength="50" required>
                        </div>
                        <div class="field">
                            <label for="teacher-apellido-paterno">Apellido paterno</label>
                            <input id="teacher-apellido-paterno" name="apellido_paterno" maxlength="50" required>
                        </div>
                        <div class="field">
                            <label for="teacher-apellido-materno">Apellido materno</label>
                            <input id="teacher-apellido-materno" name="apellido_materno" maxlength="50">
                        </div>
                        <div class="field">
                            <label for="teacher-fecha-nacimiento">Fecha de nacimiento</label>
                            <input id="teacher-fecha-nacimiento" name="fecha_nacimiento" type="date" required>
                        </div>
                        <div class="field">
                            <label for="teacher-sexo">Sexo</label>
                            <select id="teacher-sexo" name="sexo" required>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="teacher-correo">Correo</label>
                            <input id="teacher-correo" name="correo" type="email" maxlength="50" required>
                        </div>
                        <div class="field">
                            <label for="teacher-telefono">Telefono</label>
                            <input id="teacher-telefono" name="telefono" maxlength="20">
                        </div>
                        <div class="field">
                            <label for="teacher-direccion">Direccion</label>
                            <input id="teacher-direccion" name="direccion" maxlength="100">
                        </div>
                        <div class="field">
                            <label for="teacher-titulo-profesional">Titulo profesional</label>
                            <select id="teacher-titulo-profesional" name="titulo_profesional" required>
                                @foreach ($titulos as $titulo)
                                    <option value="{{ $titulo }}">{{ $titulo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="teacher-codigo-rda">Codigo RDA</label>
                            <input id="teacher-codigo-rda" name="codigo_rda" maxlength="15" required>
                        </div>
                        <div class="field">
                            <label>Materias habilitadas</label>
                            <div class="check-grid">
                                @foreach ($materias as $materia)
                                    <label>
                                        <input type="checkbox" name="materias_habilitadas[]" value="{{ $materia->id_materia }}" data-teacher-subject>
                                        {{ $materia->nombre }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="field">
                            <label for="teacher-certificacion-institucion">Certificacion</label>
                            <input id="teacher-certificacion-institucion" name="certificacion_institucion" maxlength="80" placeholder="Ej. Cambridge English">
                        </div>
                        <div class="field">
                            <label for="teacher-certificacion-nivel">Nivel certificacion</label>
                            <input id="teacher-certificacion-nivel" name="certificacion_nivel" maxlength="20" placeholder="Ej. B2">
                        </div>
                        <div class="field checkbox-field">
                            <label>
                                <input id="teacher-tiene-maestria" name="tiene_maestria" type="checkbox" value="1">
                                Tiene maestria
                            </label>
                            <label>
                                <input id="teacher-tiene-diplomado" name="tiene_diplomado" type="checkbox" value="1">
                                Tiene diplomado
                            </label>
                        </div>
                </div>

                <div class="template-workbench-footer">
                    <button class="secondary" type="button" data-teacher-close>Limpiar</button>
                    <button type="submit" id="save-teacher">Guardar docente</button>
                </div>
            </form>
        </section>

        <x-filter-panel :action="route('docentes.index')">
            <div>
                <label for="buscar">Buscar por nombre, CI, correo, registro, titulo o RDA</label>
                <input id="buscar" name="buscar" value="{{ $search }}" data-filter-field placeholder="Ej. Perez, 34567890 o RDA-001">
            </div>
            <div>
                <label for="grado">Formacion</label>
                <select id="grado" name="grado" data-filter-field>
                    <option value="">Todos</option>
                    <option value="maestria" @selected($degree === 'maestria')>Con maestria</option>
                    <option value="diplomado" @selected($degree === 'diplomado')>Con diplomado</option>
                </select>
            </div>
            <div>
                <label for="estado">Estado</label>
                <select id="estado" name="estado" data-filter-field>
                    <option value="1" @selected($status === '1')>Activos</option>
                    <option value="0" @selected($status === '0')>Inactivos</option>
                    <option value="" @selected($status === '')>Todos</option>
                </select>
            </div>
            <x-slot:actions>
                <a href="{{ route('docentes.index') }}" class="button secondary" data-clear-filters>Limpiar</a>
            </x-slot:actions>
        </x-filter-panel>

        <div data-results>
            @include('personas.docentes.partials.table', ['docentes' => $docentes])
        </div>
    </div>
@endsection
