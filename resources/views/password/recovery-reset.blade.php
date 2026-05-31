<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer contrasena</title>
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
                        <span>Restablecer acceso</span>
                    </div>
                </div>

                <h1>Restablecer contrasena</h1>
                <p>Use el codigo temporal para definir una nueva contrasena.</p>

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

                <form method="POST" action="{{ route('password.recovery.update') }}">
                    @csrf
                    <div class="field">
                        <label for="registro">Registro</label>
                        <input id="registro" name="registro" value="{{ old('registro', $registro) }}" required>
                    </div>

                    <div class="field">
                        <label for="codigo_recuperacion">Codigo</label>
                        <input id="codigo_recuperacion" name="codigo_recuperacion" maxlength="6" required autofocus>
                    </div>

                    <div class="field">
                        <label for="nueva_contrasena">Nueva contrasena</label>
                        <input id="nueva_contrasena" name="nueva_contrasena" type="password" minlength="8" required>
                    </div>

                    <div class="field">
                        <label for="nueva_contrasena_confirmation">Confirmar nueva contrasena</label>
                        <input id="nueva_contrasena_confirmation" name="nueva_contrasena_confirmation" type="password" minlength="8" required>
                    </div>

                    <button type="submit">Cambiar contrasena</button>
                </form>

                <div class="auth-links">
                    <a href="{{ route('password.recovery.request') }}">Generar otro codigo</a>
                    <a href="{{ route('login') }}">Iniciar sesion</a>
                </div>
            </div>
        </section>
        <section class="auth-visual">
            <h2>Nuevo acceso, mismo proceso.</h2>
            <p>Valide el codigo recibido y vuelva al sistema con una contrasena segura.</p>
        </section>
    </main>
</body>
</html>
