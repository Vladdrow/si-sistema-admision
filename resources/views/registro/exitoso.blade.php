<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro exitoso</title>
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
                        <span>Curso Preuniversitario</span>
                    </div>
                </div>

                <h1>Registro exitoso</h1>
                <p>Su pago ha sido confirmado. Sus credenciales de acceso son:</p>

                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:16px; margin:16px 0;">
                    <div style="margin-bottom:8px;"><strong>Numero de registro:</strong> {{ $registro }}</div>
                    <div><strong>Contrasena:</strong> su CI</div>
                </div>

                <p>Se ha enviado un correo a <strong>{{ $correo }}</strong> con sus credenciales e instrucciones de acceso.</p>

                <div style="margin-top:20px;">
                    <a href="{{ route('login') }}" class="button" style="display:inline-flex; background:#1f6feb; color:#fff; border-radius:7px; padding:10px 14px; text-decoration:none; font-weight:800;">Iniciar sesion</a>
                </div>
            </div>
        </section>

        <section class="auth-visual">
            <h2>Bienvenido al Sistema de Admision FICCT-UAGRM.</h2>
            <p>Revise su correo electronico para obtener su numero de registro y contrasena inicial.</p>
            <div class="auth-stats">
                <div class="auth-stat"><strong>Acceso inmediato</strong><span>Use su numero de registro y CI para ingresar.</span></div>
                <div class="auth-stat"><strong>Panel personal</strong><span>Consulte horarios, notas y estado de admision.</span></div>
            </div>
        </section>
    </main>
</body>
</html>
