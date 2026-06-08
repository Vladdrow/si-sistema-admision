@extends('layouts.app')

@section('title', 'Consultar bitacora')
@section('subtitle', 'Revisa las acciones generales realizadas por los usuarios dentro del sistema.')

@section('content')
    <div class="panel">
        <x-filter-panel :action="route('bitacora.index')">
            <div>
                <label for="buscar">Buscar por usuario, CI, descripcion o IP</label>
                <input id="buscar" name="buscar" value="{{ $search }}" data-filter-field placeholder="Ej. administrador, 127.0.0.1">
            </div>
            <div>
                <label for="modulo">Modulo</label>
                <select id="modulo" name="modulo" data-filter-field>
                    <option value="">Todos</option>
                    @foreach ($modules as $item)
                        <option value="{{ $item }}" @selected($module === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="accion">Accion</label>
                <select id="accion" name="accion" data-filter-field>
                    <option value="">Todas</option>
                    @foreach ($actions as $item)
                        <option value="{{ $item }}" @selected($action === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <x-slot:actions>
                <a href="{{ route('bitacora.index') }}" class="button secondary" data-clear-filters>Limpiar</a>
            </x-slot:actions>
        </x-filter-panel>

        <div class="export-bar">
            <span>Exportar resultados con los filtros actuales:</span>
            <a href="{{ route('bitacora.export', ['formato' => 'csv', 'buscar' => $search, 'modulo' => $module, 'accion' => $action]) }}" class="button secondary" data-export="csv">CSV (Excel)</a>
            <a href="{{ route('bitacora.export', ['formato' => 'pdf', 'buscar' => $search, 'modulo' => $module, 'accion' => $action]) }}" class="button secondary" data-export="pdf" target="_blank">PDF (imprimir)</a>
        </div>

        <div data-results>
            @include('bitacora.partials.table', ['registros' => $registros])
        </div>
    </div>
@endsection
