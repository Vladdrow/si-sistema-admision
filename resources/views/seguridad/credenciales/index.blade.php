@extends('layouts.app')

@section('title', 'Gestionar Credenciales')
@section('subtitle', 'Administra estado, correo y reseteo de contrasenas sin salir de esta pantalla.')

@section('content')
    <div class="panel">
        <div id="page-message" class="inline-message"></div>

        <section class="template-workbench" id="credential-workspace">
            <div class="template-workbench-header">
                <div>
                    <h2 id="credential-modal-title">Registrar credencial</h2>
                    <p class="subtitle" id="credential-modal-subtitle">Seleccione una persona sin credencial o modifique una existente desde la tabla.</p>
                </div>
                <button class="ghost" type="button" data-close-modal>Limpiar</button>
            </div>

            <form id="credential-form" data-store-url="{{ route('credenciales.store') }}">
                <div id="modal-message" class="inline-message"></div>
                <input type="hidden" name="id" id="credential-id">

                <div class="app-modal-grid">
                    <div class="field" id="credential-person-field">
                        <label for="credential-persona">Persona sin credencial</label>
                        <select id="credential-persona" name="id_persona" required>
                            <option value="">Seleccione una persona</option>
                            @foreach ($personasSinCredencial as $persona)
                                @php
                                    $rolSugerido = $persona->docente
                                        ? 'Docente'
                                        : ($persona->postulante
                                            ? 'Postulante'
                                            : ($persona->personalAdministrativo ? 'PersonalAdministrativo' : 'Administrador'));
                                @endphp
                                <option
                                    value="{{ $persona->id_persona }}"
                                    data-registro="{{ $persona->ci }}"
                                    data-ci="{{ $persona->ci }}"
                                    data-nombre="{{ $persona->nombre_completo }}"
                                    data-correo="{{ $persona->correo }}"
                                    data-rol="{{ $rolSugerido }}"
                                >
                                    {{ $persona->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="credential-registro">Registro</label>
                        <input id="credential-registro" name="registro" maxlength="15" disabled placeholder="Se genera automaticamente">
                    </div>

                    <div class="field">
                        <label for="credential-ci">CI</label>
                        <input id="credential-ci" disabled>
                    </div>

                    <div class="field">
                        <label for="credential-nombre">Persona</label>
                        <input id="credential-nombre" disabled>
                    </div>

                    <div class="field">
                        <label for="credential-correo">Correo</label>
                        <input id="credential-correo" name="correo" type="email" maxlength="50" required>
                    </div>

                    <div class="field">
                        <label for="credential-rol">Rol</label>
                        <select id="credential-rol" name="rol" required>
                            @foreach (['Administrador', 'PersonalAdministrativo', 'Docente', 'Postulante'] as $rol)
                                <option value="{{ $rol }}">{{ $rol }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="credential-estado">Estado</label>
                        <select id="credential-estado" name="estado" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="credential-password">Nueva contrasena</label>
                        <input id="credential-password" name="nueva_contrasena" type="password" autocomplete="new-password" minlength="8" pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="Minimo 8 caracteres, una mayuscula, un numero y un caracter especial." placeholder="Min. 8, mayuscula, numero y especial" required>
                    </div>

                    <div class="field">
                        <label for="credential-password-confirmation">Confirmar contrasena</label>
                        <input id="credential-password-confirmation" name="nueva_contrasena_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                </div>

                <div class="password-guidance">
                    <strong>Politica de contrasena</strong>
                    <span>Debe tener al menos 8 caracteres, una mayuscula, un numero y un caracter especial.</span>
                </div>

                <div class="template-workbench-footer">
                    <button class="secondary" type="button" data-close-modal>Limpiar</button>
                    <button type="submit" id="save-credential">Guardar credencial</button>
                </div>
            </form>
        </section>

        <x-filter-panel :action="route('credenciales.index')">
            <div>
                <label for="buscar">Buscar por registro, CI, nombre o correo</label>
                <input id="buscar" name="buscar" value="{{ $search }}" data-filter-field placeholder="Ej. 12345678 o admin@sistema">
            </div>
            <div>
                <label for="rol">Rol</label>
                <select id="rol" name="rol" data-filter-field>
                    <option value="">Todos</option>
                    @foreach ($validRoles as $validRole)
                        <option value="{{ $validRole }}" @selected($role === $validRole)>{{ $validRole }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="estado">Estado</label>
                <select id="estado" name="estado" data-filter-field>
                    <option value="">Todos</option>
                    <option value="1" @selected($status === '1')>Activo</option>
                    <option value="0" @selected($status === '0')>Inactivo</option>
                </select>
            </div>
            <x-slot:actions>
                <a href="{{ route('credenciales.index') }}" class="button secondary" data-clear-filters>Limpiar</a>
            </x-slot:actions>
        </x-filter-panel>

        <div id="credentials-results" data-results>
            @include('seguridad.credenciales.partials.table', ['credenciales' => $credenciales])
        </div>
    </div>
@endsection
