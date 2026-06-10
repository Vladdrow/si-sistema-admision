@extends('layouts.app')

@section('title', 'Gestionar Contrasena')
@section('subtitle', 'Actualiza tu contrasena de acceso al sistema.')

@section('content')
    <div class="panel password-layout">
        <section class="template-workbench password-workbench">
            <div class="template-workbench-header">
                <div>
                    <h2>Cambio de contrasena</h2>
                    <p class="subtitle">Confirme su contrasena actual y defina una nueva clave de acceso.</p>
                </div>
                <span class="badge neutral">Seguridad de cuenta</span>
            </div>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PATCH')

                <div class="app-modal-grid password-grid">
                    <div class="field">
                        <label for="contrasena_actual">Contrasena actual</label>
                        <input id="contrasena_actual" name="contrasena_actual" type="password" autocomplete="current-password" required>
                    </div>

                    <div class="field">
                        <label for="nueva_contrasena">Nueva contrasena</label>
                        <input id="nueva_contrasena" name="nueva_contrasena" type="password" autocomplete="new-password" minlength="8" pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="Minimo 8 caracteres, una mayuscula, un numero y un caracter especial." required>
                    </div>

                    <div class="field">
                        <label for="nueva_contrasena_confirmation">Confirmar nueva contrasena</label>
                        <input id="nueva_contrasena_confirmation" name="nueva_contrasena_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                </div>

                <div class="password-guidance">
                    <strong>Recomendacion</strong>
                    <span>Use al menos 8 caracteres, una mayuscula, un numero y un caracter especial.</span>
                </div>

                <div class="template-workbench-footer">
                    <button type="submit">Guardar contrasena</button>
                </div>
            </form>
        </section>

        <section class="template-workbench password-workbench">
            <div class="template-workbench-header">
                <div>
                    <h2>Recuperacion por correo</h2>
                    <p class="subtitle">Solicite un codigo temporal enviado al correo registrado en su cuenta.</p>
                </div>
                <span class="badge neutral">Codigo temporal</span>
            </div>

            <form method="POST" action="{{ route('password.recovery.send') }}">
                @csrf
                <div class="app-modal-grid password-grid compact">
                    <div class="field">
                        <label for="registro_recuperacion">Registro</label>
                        <input id="registro_recuperacion" name="registro" value="{{ old('registro', auth()->user()?->registro) }}" required>
                    </div>
                </div>
                <div class="template-workbench-footer">
                    <button type="submit" class="secondary">Enviar codigo al correo</button>
                </div>
            </form>

            <form method="POST" action="{{ route('password.recovery.update') }}" class="password-recovery-inline">
                @csrf
                <div class="app-modal-grid password-grid">
                    <div class="field">
                        <label for="registro_codigo">Registro</label>
                        <input id="registro_codigo" name="registro" value="{{ old('registro', auth()->user()?->registro) }}" required>
                    </div>
                    <div class="field">
                        <label for="codigo_recuperacion">Codigo</label>
                        <input id="codigo_recuperacion" name="codigo_recuperacion" maxlength="6" required>
                    </div>
                    <div class="field">
                        <label for="nueva_contrasena_codigo">Nueva contrasena</label>
                        <input id="nueva_contrasena_codigo" name="nueva_contrasena" type="password" autocomplete="new-password" minlength="8" pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="Minimo 8 caracteres, una mayuscula, un numero y un caracter especial." required>
                    </div>
                    <div class="field">
                        <label for="nueva_contrasena_codigo_confirmation">Confirmar nueva contrasena</label>
                        <input id="nueva_contrasena_codigo_confirmation" name="nueva_contrasena_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                </div>
                <div class="password-guidance">
                    <strong>Politica de contrasena</strong>
                    <span>Debe tener al menos 8 caracteres, una mayuscula, un numero y un caracter especial.</span>
                </div>
                <div class="template-workbench-footer">
                    <button type="submit">Cambiar con codigo</button>
                </div>
            </form>
        </section>
    </div>
@endsection
