<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de postulante</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-panel" style="overflow-y:auto;">
            <div class="card" style="max-width:520px; padding:24px;">
                <div class="auth-brand">
                    <span class="brand-mark">SA</span>
                    <div>
                        <strong>Sistema de Admision FICCT-UAGRM</strong>
                        <span>Curso Preuniversitario</span>
                    </div>
                </div>

                <h1>Registro de postulante</h1>
                <p>Complete sus datos personales y academicos para iniciar el proceso de admision.</p>

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

                @if (! $inscripcionesAbiertas)
                    <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:16px; margin:16px 0;">
                        <p style="margin:0; font-weight:700; color:#c2410c;">
                            {{ $parametro
                                ? (now()->lt($parametro->fecha_inicio_inscripcion)
                                    ? 'Las inscripciones aun no han iniciado. Vuelva a partir del ' . $parametro->fecha_inicio_inscripcion->format('d/m/Y H:i') . '.'
                                    : 'Las inscripciones ya han cerrado. El periodo finalizo el ' . $parametro->fecha_cierre_inscripcion->format('d/m/Y H:i') . '.')
                                : 'Sistema cerrado para inscripciones. No hay un periodo de admision configurado.' }}
                        </p>
                    </div>

                    <div class="auth-links">
                        <a href="{{ route('login') }}">Iniciar sesion</a>
                        <a href="{{ url('/') }}">Volver al inicio</a>
                    </div>
                @else

                <form method="POST" action="{{ route('registro.store') }}">
                    @csrf
                    <div class="field">
                        <label for="ci">CI <span class="muted">(obligatorio)</span></label>
                        <input id="ci" name="ci" value="{{ old('ci') }}" maxlength="20" required autofocus>
                    </div>

                    <div class="field">
                        <label for="nombres">Nombres</label>
                        <input id="nombres" name="nombres" value="{{ old('nombres') }}" maxlength="50" required>
                    </div>

                    <div class="field">
                        <label for="apellido_paterno">Apellido paterno</label>
                        <input id="apellido_paterno" name="apellido_paterno" value="{{ old('apellido_paterno') }}" maxlength="50" required>
                    </div>

                    <div class="field">
                        <label for="apellido_materno">Apellido materno</label>
                        <input id="apellido_materno" name="apellido_materno" value="{{ old('apellido_materno') }}" maxlength="50">
                    </div>

                    <div class="field">
                        <label for="fecha_nacimiento">Fecha de nacimiento</label>
                        <input id="fecha_nacimiento" name="fecha_nacimiento" type="date" value="{{ old('fecha_nacimiento') }}" required>
                    </div>

                    <div class="field">
                        <label for="sexo">Sexo</label>
                        <select id="sexo" name="sexo" required>
                            <option value="M" @selected(old('sexo') === 'M')>Masculino</option>
                            <option value="F" @selected(old('sexo') === 'F')>Femenino</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="correo">Correo electronico</label>
                        <input id="correo" name="correo" type="email" value="{{ old('correo') }}" maxlength="50" required>
                    </div>

                    <div class="field">
                        <label for="telefono">Telefono</label>
                        <input id="telefono" name="telefono" value="{{ old('telefono') }}" maxlength="20">
                    </div>

                    <div class="field">
                        <label for="direccion">Direccion</label>
                        <input id="direccion" name="direccion" value="{{ old('direccion') }}" maxlength="70">
                    </div>

                    <div class="field">
                        <label for="colegio_procedencia">Colegio de procedencia</label>
                        <input id="colegio_procedencia" name="colegio_procedencia" value="{{ old('colegio_procedencia') }}" maxlength="100">
                    </div>

                    <div class="field">
                        <label for="ciudad">Ciudad</label>
                        <input id="ciudad" name="ciudad" value="{{ old('ciudad') }}" maxlength="50">
                    </div>

                    <div class="field">
                        <label for="codigo_libreta">Codigo de libreta de colegio <span class="muted">(obligatorio)</span></label>
                        <input id="codigo_libreta" name="codigo_libreta" value="{{ old('codigo_libreta') }}" maxlength="20" required placeholder="Ej. LIB1234">
                    </div>

                    <div class="field">
                        <label for="codigo_titulo">Codigo de titulo de bachiller <span class="muted">(obligatorio)</span></label>
                        <input id="codigo_titulo" name="codigo_titulo" value="{{ old('codigo_titulo') }}" maxlength="20" required placeholder="Ej. TIT2345">
                    </div>

                    <div class="field">
                        <label for="id_carrera_primera_opc">Primera opcion de carrera</label>
                        <select id="id_carrera_primera_opc" name="id_carrera_primera_opc" required>
                            <option value="">Seleccione una carrera</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id_carrera }}" @selected(old('id_carrera_primera_opc') == $carrera->id_carrera)>{{ $carrera->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="id_carrera_segunda_opc">Segunda opcion de carrera</label>
                        <select id="id_carrera_segunda_opc" name="id_carrera_segunda_opc" required>
                            <option value="">Seleccione una carrera</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id_carrera }}" @selected(old('id_carrera_segunda_opc') == $carrera->id_carrera)>{{ $carrera->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" style="margin-top:12px;">Continuar al pago</button>
                </form>

                <div class="auth-links">
                    <a href="{{ route('login') }}">Ya tengo una cuenta</a>
                    <a href="{{ url('/') }}">Volver al inicio</a>
                </div>
                @endif
            </div>
        </section>

        <section class="auth-visual">
            <h2>Complete el formulario y realice el pago para quedar registrado.</h2>
            <p>Al finalizar recibira su numero de registro y contrasena en el correo electronico.</p>
            <div class="auth-stats">
                <div class="auth-stat"><strong>Datos personales</strong><span>CI, nombres, correo y contacto.</span></div>
                <div class="auth-stat"><strong>Datos academicos</strong><span>Libreta, titulo y opciones de carrera.</span></div>
                <div class="auth-stat"><strong>Pago unico</strong><span>Pasarela segura. Credenciales al instante.</span></div>
            </div>
        </section>
    </main>
</body>
</html>
