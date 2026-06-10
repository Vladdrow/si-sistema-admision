<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contrasena</title>
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
                        <span>Recuperacion de acceso</span>
                    </div>
                </div>

                <h1>Recuperar contrasena</h1>
                <p>Ingrese su registro para generar un codigo temporal.</p>

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

                <form method="POST" action="{{ route('password.recovery.send') }}">
                    @csrf
                    <div class="field">
                        <label for="registro">Registro</label>
                        <input id="registro" name="registro" value="{{ old('registro', auth()->user()?->registro) }}" required autofocus>
                    </div>

                    <button type="submit">Generar codigo</button>
                </form>

                <div class="auth-links">
                    <a href="{{ route('password.recovery.reset') }}">Ya tengo un codigo</a>
                    <a href="{{ auth()->check() ? route('password.edit') : route('login') }}">Volver</a>
                </div>
            </div>
        </section>
        <section class="auth-visual">
            <h2>Recupere su acceso sin perder continuidad.</h2>
            <p>El codigo temporal permite restablecer la contrasena y desbloquear intentos fallidos cuando corresponda.</p>
        </section>
    </main>
</body>
</html>
