<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pago no completado</title>
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

                <h1>Pago no completado</h1>

                <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:16px; margin:16px 0;">
                    <p style="margin:0;">{{ $mensaje ?? 'El pago no pudo ser procesado. Debe reiniciar el registro.' }}</p>
                </div>

                <div style="margin-top:20px;">
                    <a href="{{ route('registro.create') }}" class="button" style="display:inline-flex; background:#1f6feb; color:#fff; border-radius:7px; padding:10px 14px; text-decoration:none; font-weight:800;">Reiniciar registro</a>
                </div>
            </div>
        </section>

        <section class="auth-visual">
            <h2>El pago no fue completado.</h2>
            <p>Puede reiniciar el proceso de registro e intentar nuevamente el pago.</p>
        </section>
    </main>
</body>
</html>
