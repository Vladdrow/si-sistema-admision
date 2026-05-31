<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesion</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-panel">
            <div class="card">
                <div class="auth-brand">
                    <span class="brand-mark">SA</span>
                    <div>
                        <strong>Sistema de Admision FICCT-UAGRM</strong>
                        <span>FICCT - UAGRM</span>
                    </div>
                </div>

                <h1>Iniciar sesion</h1>
                <p>Ingrese con su numero de registro y contrasena para continuar.</p>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="field">
                        <label for="registro">Registro</label>
                        <input id="registro" name="registro" value="{{ old('registro') }}" required autofocus>
                    </div>

                    <div class="field">
                        <label for="contrasena">Contrasena</label>
                        <input id="contrasena" name="contrasena" type="password" required>
                    </div>

                    <button type="submit">Ingresar</button>
                </form>

                <div class="auth-links">
                    <a href="{{ route('password.recovery.request') }}">Olvide mi contrasena</a>
                    <a href="{{ url('/') }}">Volver al inicio</a>
                </div>
            </div>
        </section>

        <section class="auth-visual">
            <h2>Admision FICCT-UAGRM ordenada desde el primer registro.</h2>
            <p>Centraliza credenciales, postulantes, parametros, horarios y seguimiento academico en un solo entorno.</p>
            <div class="auth-stats">
                <div class="auth-stat"><strong>Roles</strong><span>Administrador, docente y postulante</span></div>
                <div class="auth-stat"><strong>Proceso</strong><span>Inscripcion, grupos y notas</span></div>
                <div class="auth-stat"><strong>Control</strong><span>Bitacora y seguridad</span></div>
            </div>
        </section>
    </main>
</body>
</html>
