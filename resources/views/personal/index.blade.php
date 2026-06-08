@extends('layouts.app')

@section('title', 'Gestionar Personal Administrativo')
@section('subtitle', 'Registra, lista, modifica, desactiva y restaura personal administrativo. Al registrar se genera automaticamente un numero de registro y la contrasena sera el CI.')

@section('content')
    <div class="panel">
        <div id="page-message" class="inline-message"></div>

        <section class="template-workbench" id="person-workspace" data-person-modal data-kind="staff">
            <div class="template-workbench-header">
                <div>
                    <h2 id="person-modal-title">Registrar personal</h2>
                    <p class="subtitle" id="person-modal-subtitle"></p>
                </div>
                <button class="ghost" type="button" data-person-close>Limpiar</button>
            </div>

            <form id="person-form" data-store-url="{{ route('personal.store') }}">
                <div id="person-modal-message" class="inline-message"></div>
                <input type="hidden" data-person-field="id">
                @include('personas.partials.form')
                <div class="app-modal-grid">
                    <div class="field">
                        <label for="person-cargo">Cargo</label>
                        <input id="person-cargo" name="cargo" maxlength="25" required data-person-field="cargo">
                    </div>
                </div>
                <div class="template-workbench-footer">
                    <button class="secondary" type="button" data-person-close>Limpiar</button>
                    <button type="submit" id="save-person">Guardar personal</button>
                </div>
            </form>
        </section>

        <x-filter-panel :action="route('personal.index')">
            <div>
                <label for="buscar">Buscar por nombre, CI, correo, registro o cargo</label>
                <input id="buscar" name="buscar" value="{{ $search }}" data-filter-field placeholder="Ej. Lopez, secretaria">
            </div>
            <div>
                <label for="cargo">Cargo</label>
                <select id="cargo" name="cargo" data-filter-field>
                    <option value="">Todos</option>
                    @foreach ($positions as $item)
                        <option value="{{ $item }}" @selected($position === $item)>{{ $item }}</option>
                    @endforeach
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
                <a href="{{ route('personal.index') }}" class="button secondary" data-clear-filters>Limpiar</a>
            </x-slot:actions>
        </x-filter-panel>

        <div data-results>
            @include('personal.partials.table', ['personal' => $personal])
        </div>
    </div>
@endsection
