@extends('layouts.app')

@section('title', 'Gestionar Postulantes')
@section('subtitle', 'Lista, modifica, desactiva y restaura postulantes. El registro inicial lo realiza el propio postulante.')

@section('content')
    <div class="panel">
        <div id="page-message" class="inline-message"></div>

        <section class="template-workbench" id="person-workspace" data-person-modal data-kind="applicant">
            <div class="template-workbench-header">
                <div>
                    <h2 id="person-modal-title">Modificar postulante</h2>
                    <p class="subtitle" id="person-modal-subtitle">Seleccione un postulante de la tabla para editarlo.</p>
                </div>
                <button class="ghost" type="button" data-person-close>Limpiar</button>
            </div>

            <form id="person-form">
                <div id="person-modal-message" class="inline-message"></div>
                <input type="hidden" data-person-field="id">
                @include('personas.partials.form')
                <div class="app-modal-grid">
                        <div class="field">
                            <label for="person-colegio">Colegio procedencia</label>
                            <input id="person-colegio" name="colegio_procedencia" maxlength="100" data-person-field="colegioProcedencia">
                        </div>
                        <div class="field">
                            <label for="person-ciudad">Ciudad</label>
                            <input id="person-ciudad" name="ciudad" maxlength="50" data-person-field="ciudad">
                        </div>
                        <div class="field">
                            <label for="person-estado-admision">Estado admision</label>
                            <select id="person-estado-admision" name="estado_admision" required data-person-field="estadoAdmision">
                                @foreach ($statuses as $item)
                                    <option value="{{ $item }}">{{ $item }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="person-codigo-libreta">Codigo libreta</label>
                            <input id="person-codigo-libreta" name="codigo_libreta" maxlength="20" required data-person-field="codigoLibreta">
                        </div>
                        <div class="field">
                            <label for="person-codigo-titulo">Codigo titulo</label>
                            <input id="person-codigo-titulo" name="codigo_titulo" maxlength="20" required data-person-field="codigoTitulo">
                        </div>
                        @foreach ([
                            'idCarreraPrimeraOpc' => ['id_carrera_primera_opc', 'Primera opcion'],
                            'idCarreraSegundaOpc' => ['id_carrera_segunda_opc', 'Segunda opcion'],
                            'idCarreraAdmitido' => ['id_carrera_admitido', 'Carrera admitido'],
                        ] as $field => [$name, $label])
                            <div class="field">
                                <label for="person-{{ $name }}">{{ $label }}</label>
                                <select id="person-{{ $name }}" name="{{ $name }}" data-person-field="{{ $field }}">
                                    <option value="">Sin definir</option>
                                    @foreach ($careers as $item)
                                        <option value="{{ $item->id_carrera }}">{{ $item->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                </div>
                <div class="template-workbench-footer">
                    <button class="secondary" type="button" data-person-close>Limpiar</button>
                    <button type="submit" id="save-person">Guardar cambios</button>
                </div>
            </form>
        </section>

        <x-filter-panel :action="route('postulantes.index')">
            <div>
                <label for="buscar">Buscar por nombre, CI, registro, colegio, ciudad o codigo</label>
                <input id="buscar" name="buscar" value="{{ $search }}" data-filter-field placeholder="Ej. Perez, libreta o ciudad">
            </div>
            <div>
                <label for="estado">Estado admision</label>
                <select id="estado" name="estado" data-filter-field>
                    <option value="">Todos</option>
                    @foreach ($statuses as $item)
                        <option value="{{ $item }}" @selected($admisionStatus === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="acceso">Acceso</label>
                <select id="acceso" name="acceso" data-filter-field>
                    <option value="1" @selected($acceso === '1')>Activos</option>
                    <option value="0" @selected($acceso === '0')>Inactivos</option>
                    <option value="" @selected($acceso === '')>Todos</option>
                </select>
            </div>
            <div>
                <label for="carrera">Carrera</label>
                <select id="carrera" name="carrera" data-filter-field>
                    <option value="">Todas</option>
                    @foreach ($careers as $item)
                        <option value="{{ $item->id_carrera }}" @selected($career === (string) $item->id_carrera)>{{ $item->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <x-slot:actions>
                <a href="{{ route('postulantes.index') }}" class="button secondary" data-clear-filters>Limpiar</a>
            </x-slot:actions>
        </x-filter-panel>

        <div data-results>
            @include('personas.postulantes.partials.table', ['postulantes' => $postulantes])
        </div>
    </div>
@endsection
