<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Admision FICCT-UAGRM</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, Arial, Helvetica, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: #f5f7fb;
            color: #172033;
            margin: 0;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .hero {
            background:
                linear-gradient(90deg, rgba(15, 23, 42, .9) 0%, rgba(15, 23, 42, .74) 40%, rgba(15, 23, 42, .18) 100%),
                url("{{ asset('images/ficct-hero.png') }}") center / cover;
            color: #fff;
            min-height: 88vh;
            padding: 26px clamp(18px, 5vw, 72px) 42px;
        }

        .nav {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin: 0 auto;
            max-width: 1180px;
        }

        .brand {
            align-items: center;
            display: flex;
            gap: 10px;
            font-weight: 900;
        }

        .brand-mark {
            align-items: center;
            background: #fff;
            border-radius: 8px;
            color: #172033;
            display: inline-flex;
            height: 40px;
            justify-content: center;
            width: 40px;
        }

        .nav-actions {
            display: flex;
            gap: 10px;
        }

        .button {
            align-items: center;
            background: #1f6feb;
            border-radius: 7px;
            color: #fff;
            display: inline-flex;
            font-size: 14px;
            font-weight: 800;
            justify-content: center;
            min-height: 40px;
            padding: 10px 14px;
        }

        .button.secondary {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .24);
        }

        .hero-content {
            display: grid;
            gap: 34px;
            grid-template-columns: minmax(0, 680px) minmax(260px, 360px);
            margin: 13vh auto 0;
            max-width: 1180px;
        }

        h1 {
            font-size: clamp(42px, 7vw, 78px);
            line-height: .98;
            margin: 0 0 18px;
            max-width: 760px;
        }

        .lead {
            color: rgba(255, 255, 255, .84);
            font-size: clamp(16px, 2vw, 20px);
            line-height: 1.55;
            margin: 0;
            max-width: 620px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .process-card {
            align-self: end;
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(255, 255, 255, .5);
            border-radius: 8px;
            color: #172033;
            padding: 18px;
        }

        .process-card h2 {
            font-size: 17px;
            margin: 0 0 14px;
        }

        .steps {
            display: grid;
            gap: 10px;
        }

        .step {
            border: 1px solid #dce4ef;
            border-radius: 7px;
            padding: 11px;
        }

        .step strong {
            display: block;
            font-size: 14px;
        }

        .step span {
            color: #667085;
            display: block;
            font-size: 13px;
            margin-top: 4px;
        }

        .section {
            margin: 0 auto;
            max-width: 1180px;
            padding: 46px clamp(18px, 5vw, 72px);
        }

        .section h2 {
            font-size: clamp(26px, 4vw, 40px);
            margin: 0 0 20px;
        }

        .modules {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .module {
            background: #fff;
            border: 1px solid #e3e8ef;
            border-radius: 8px;
            box-shadow: 0 12px 26px rgba(16, 24, 40, .045);
            padding: 18px;
        }

        .module strong {
            display: block;
            margin-bottom: 7px;
        }

        .module span {
            color: #667085;
            font-size: 14px;
            line-height: 1.45;
        }

        @media (max-width: 900px) {
            .hero-content,
            .modules {
                grid-template-columns: 1fr;
            }

            .hero {
                min-height: auto;
            }

            .hero-content {
                margin-top: 70px;
            }
        }

        @media (max-width: 560px) {
            .nav {
                align-items: flex-start;
                flex-direction: column;
                gap: 16px;
            }

            .nav-actions,
            .hero-actions {
                width: 100%;
            }

            .button {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <main class="hero">
        <nav class="nav">
            <div class="brand">
                <span class="brand-mark">SA</span>
                <span>Sistema de Admision FICCT-UAGRM</span>
            </div>
            <div class="nav-actions">
                @auth
                    <a class="button" href="{{ route('dashboard') }}">Ir al panel</a>
                @else
                    <a class="button secondary" href="{{ route('password.recovery.request') }}">Recuperar acceso</a>
                    <a class="button" href="{{ route('login') }}">Iniciar sesion</a>
                @endauth
            </div>
        </nav>

        <section class="hero-content">
            <div>
                <h1>Admision FICCT-UAGRM organizada de principio a fin.</h1>
                <p class="lead">Centraliza postulantes, parametros, cupos, horarios, notas y credenciales para la Facultad de Ingenieria en Ciencias de la Computacion y Telecomunicaciones.</p>
                <div class="hero-actions">
                    @auth
                        <a class="button" href="{{ route('dashboard') }}">Continuar gestionando</a>
                    @else
                        <a class="button" href="{{ route('login') }}">Entrar al sistema</a>
                    @endauth
                </div>
            </div>

            <aside class="process-card">
                <h2>Flujo del proceso</h2>
                <div class="steps">
                    <div class="step"><strong>1. Configuracion</strong><span>Semestre, fechas, cupos y parametros.</span></div>
                    <div class="step"><strong>2. Admision</strong><span>Postulantes, pagos, grupos y horarios.</span></div>
                    <div class="step"><strong>3. Evaluacion</strong><span>Notas, seguimiento y control por roles.</span></div>
                </div>
            </aside>
        </section>
    </main>

    <section class="section">
        <h2>Herramientas para cada etapa</h2>
        <div class="modules">
            <div class="module"><strong>Credenciales</strong><span>Roles, acceso, recuperacion y control de estado.</span></div>
            <div class="module"><strong>Postulantes</strong><span>Informacion academica, opciones de carrera y admision.</span></div>
            <div class="module"><strong>Horarios</strong><span>Plantillas, grupos, docentes y aulas sin choques.</span></div>
            <div class="module"><strong>Bitacora</strong><span>Registro de actividad para auditoria administrativa.</span></div>
        </div>
    </section>
</body>
</html>
