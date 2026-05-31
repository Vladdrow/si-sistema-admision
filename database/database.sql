-- Active: 1780052698035@@127.0.0.1@5432@sistema_admision
CREATE DATABASE sistema_admision;
-- 1. TABLA PERSONA
CREATE TABLE persona (
    id_persona SERIAL PRIMARY KEY,
    ci VARCHAR(20) NOT NULL UNIQUE,
    nombres VARCHAR(50) NOT NULL,
    apellido_paterno VARCHAR(50) NOT NULL,
    apellido_materno VARCHAR(50),
    fecha_nacimiento DATE NOT NULL,
    sexo CHAR(1) NOT NULL CHECK (sexo IN ('M', 'F')),
    direccion VARCHAR(70),
    telefono VARCHAR(20),
    correo VARCHAR(50) NOT NULL UNIQUE
);

-- 2. TABLA PERSONAL_ADMINISTRATIVO
CREATE TABLE personal_administrativo (
    id_personal INTEGER PRIMARY KEY REFERENCES persona(id_persona) ON DELETE CASCADE,
    cargo VARCHAR(50) NOT NULL
);

-- ALTER TABLE personal_administrativo 
-- ALTER COLUMN cargo TYPE VARCHAR(50),
-- ALTER COLUMN cargo SET NOT NULL;
-- 3. TABLA DOCENTE
CREATE TABLE docente (
    id_docente INTEGER PRIMARY KEY REFERENCES persona(id_persona) ON DELETE CASCADE,
    titulo_profesional VARCHAR(50) NOT NULL,
    tiene_maestria BOOLEAN NOT NULL DEFAULT FALSE,
    tiene_diplomado BOOLEAN NOT NULL DEFAULT FALSE,
    codigo_rda VARCHAR(15) NOT NULL UNIQUE
);

-- 4. TABLA CARRERA
CREATE TABLE carrera (
    id_carrera SERIAL PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- 5. TABLA SEMESTRE
CREATE TABLE semestre (
    id_semestre SERIAL PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE
);

-- 6. TABLA PARAMETRO_ADMISION
CREATE TABLE parametro_admision (
    id_parametro SERIAL PRIMARY KEY,
    fecha_inicio_inscripcion TIMESTAMP NOT NULL,
    fecha_cierre_inscripcion TIMESTAMP NOT NULL,
    fecha_cierre_notas TIMESTAMP NULL,
    monto_pago DECIMAL(10,2) NOT NULL CHECK (monto_pago > 0),
    max_estudiante_grupo INTEGER NOT NULL CHECK (max_estudiante_grupo > 0),
    nota_minima_aprobacion DECIMAL(5,2) NOT NULL CHECK (nota_minima_aprobacion BETWEEN 0 AND 100),
    max_grupos_docente INTEGER NOT NULL CHECK (max_grupos_docente > 0),
    tiempo_expiracion_pago INTEGER NOT NULL CHECK (tiempo_expiracion_pago > 0),
    id_semestre INTEGER NOT NULL UNIQUE REFERENCES semestre(id_semestre) ON DELETE CASCADE
);

-- 7. TABLA CARRERA_SEMESTRE
CREATE TABLE carrera_semestre (
    id_carrera_semestre SERIAL PRIMARY KEY,
    cantidad_cupos INTEGER NOT NULL CHECK (cantidad_cupos >= 0),
    cantidad_estudiantes INTEGER NOT NULL CHECK (cantidad_estudiantes >= 0),
    id_carrera INTEGER NOT NULL REFERENCES carrera(id_carrera) ON DELETE CASCADE,
    id_semestre INTEGER NOT NULL REFERENCES semestre(id_semestre) ON DELETE CASCADE,
    UNIQUE (id_carrera, id_semestre)
);

-- 8. TABLA EXAMEN
CREATE TABLE examen (
    id_examen SERIAL PRIMARY KEY,
    numero_examen INTEGER NOT NULL CHECK (numero_examen > 0),
    ponderacion DECIMAL(5,2) NOT NULL CHECK (ponderacion BETWEEN 0 AND 100),
    id_semestre INTEGER NOT NULL REFERENCES semestre(id_semestre) ON DELETE CASCADE,
    UNIQUE (id_semestre, numero_examen)
);

-- 9. TABLA POSTULANTE
CREATE TABLE postulante (
    id_postulante INTEGER PRIMARY KEY REFERENCES persona(id_persona) ON DELETE CASCADE,
    colegio_procedencia VARCHAR(100),
    ciudad VARCHAR(50),
    estado_admision VARCHAR(20) NOT NULL DEFAULT 'Pendiente' CHECK (estado_admision IN ('Pendiente', 'Admitido', 'No Admitido')),
    codigo_libreta VARCHAR(20) NOT NULL UNIQUE,
    codigo_titulo VARCHAR(20) NOT NULL UNIQUE,
    id_carrera_primera_opc INTEGER REFERENCES carrera(id_carrera) ON DELETE SET NULL,
    id_carrera_segunda_opc INTEGER REFERENCES carrera(id_carrera) ON DELETE SET NULL,
    id_carrera_admitido INTEGER REFERENCES carrera(id_carrera) ON DELETE SET NULL
);

-- 10. TABLA PAGO
CREATE TABLE pago (
    id_pago SERIAL PRIMARY KEY,
    monto DECIMAL(10,2) NOT NULL CHECK (monto > 0),
    fecha_pago TIMESTAMP,
    estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente' CHECK (estado IN ('Pendiente', 'Pagado', 'Rechazado', 'Expirado')),
    numero_transaccion VARCHAR(100),
    codigo_orden VARCHAR(20) NOT NULL,
    metodo_pago VARCHAR(20),
    mensaje_error TEXT,
    id_postulante INTEGER NOT NULL REFERENCES postulante(id_postulante) ON DELETE CASCADE
);

-- 11. TABLA CREDENCIAL
CREATE TABLE credencial (
    id_credencial SERIAL PRIMARY KEY,
    registro VARCHAR(15) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol VARCHAR(50) NOT NULL CHECK (rol IN ('Administrador', 'PersonalAdministrativo', 'Docente', 'Postulante')),
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_ultimo_acceso TIMESTAMP,
    intentos_fallidos INTEGER NOT NULL DEFAULT 0 CHECK (intentos_fallidos >= 0),
    fecha_bloqueo TIMESTAMP,
    codigo_recuperacion VARCHAR(10),
    fecha_expiracion_codigo TIMESTAMP,
    id_persona INTEGER NOT NULL UNIQUE REFERENCES persona(id_persona) ON DELETE CASCADE
);

-- 12. TABLA GRUPO
CREATE TABLE grupo (
    id_grupo SERIAL PRIMARY KEY,
    nombre_grupo VARCHAR(30) NOT NULL,
    cantidad_estudiantes INTEGER NOT NULL DEFAULT 0 CHECK (cantidad_estudiantes >= 0),
    id_semestre INTEGER NOT NULL REFERENCES semestre(id_semestre) ON DELETE CASCADE
);

-- 13. TABLA POSTULANTE_GRUPO
CREATE TABLE postulante_grupo (
    id_postulante_grupo SERIAL PRIMARY KEY,
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_grupo INTEGER NOT NULL REFERENCES grupo(id_grupo) ON DELETE CASCADE,
    id_postulante INTEGER NOT NULL REFERENCES postulante(id_postulante) ON DELETE CASCADE,
    UNIQUE (id_grupo, id_postulante)
);

-- 14. TABLA MATERIA
CREATE TABLE materia (
    id_materia SERIAL PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- 15. TABLA NOTA
CREATE TABLE nota (
    id_nota SERIAL PRIMARY KEY,
    nota DECIMAL(5,2) NOT NULL CHECK (nota BETWEEN 0 AND 100),
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_postulante INTEGER NOT NULL REFERENCES postulante(id_postulante) ON DELETE CASCADE,
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente) ON DELETE CASCADE,
    id_materia INTEGER NOT NULL REFERENCES materia(id_materia) ON DELETE CASCADE,
    id_examen INTEGER NOT NULL REFERENCES examen(id_examen) ON DELETE CASCADE,
    UNIQUE (id_postulante, id_materia, id_examen)
);

-- 16. TABLA PLANTILLA_HORARIO
CREATE TABLE plantilla_horario (
    id_plantilla SERIAL PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE,
    turno VARCHAR(20) NOT NULL CHECK (turno IN ('Mañana', 'Tarde', 'Noche'))
);

-- 17. TABLA DETALLE_PLANTILLA_HORARIO
CREATE TABLE detalle_plantilla_horario (
    id_detalle SERIAL PRIMARY KEY,
    dia INTEGER NOT NULL CHECK (dia BETWEEN 1 AND 7), -- 1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado, 7=Domingo
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    modalidad VARCHAR(20) NOT NULL CHECK (modalidad IN ('Presencial', 'Virtual')),
    id_plantilla INTEGER NOT NULL REFERENCES plantilla_horario(id_plantilla) ON DELETE CASCADE
);

-- 18. TABLA AULA
CREATE TABLE aula (
    id_aula SERIAL PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL,
    capacidad INTEGER NOT NULL CHECK (capacidad > 0),
    ubicacion VARCHAR(25),
    estado VARCHAR(20) NOT NULL DEFAULT 'Disponible' CHECK (estado IN ('Disponible', 'En mantenimiento', 'Ocupado'))
);

-- 19. TABLA GRUPO_HORARIO
CREATE TABLE grupo_horario (
    id_grupo_horario SERIAL PRIMARY KEY,
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_grupo INTEGER NOT NULL REFERENCES grupo(id_grupo) ON DELETE CASCADE,
    id_detalle INTEGER NOT NULL REFERENCES detalle_plantilla_horario(id_detalle) ON DELETE CASCADE,
    id_docente INTEGER NOT NULL REFERENCES docente(id_docente) ON DELETE CASCADE,
    id_aula INTEGER NOT NULL REFERENCES aula(id_aula) ON DELETE CASCADE,
    UNIQUE (id_grupo, id_detalle)
);

-- 20. TABLA BITACORA
CREATE TABLE bitacora (
    id_bitacora SERIAL PRIMARY KEY,
    fecha_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accion VARCHAR(50) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    descripcion TEXT,
    ip_origen VARCHAR(45),
    id_persona INTEGER NOT NULL REFERENCES persona(id_persona) ON DELETE CASCADE
);

-- =============================================
-- ÍNDICES SUGERIDOS PARA MEJORAR RENDIMIENTO
-- =============================================

-- Índices para búsquedas frecuentes en POSTULANTE
CREATE INDEX idx_postulante_estado ON postulante(estado_admision);
CREATE INDEX idx_postulante_ciudad ON postulante(ciudad);
CREATE INDEX idx_postulante_carrera_primera ON postulante(id_carrera_primera_opc);
CREATE INDEX idx_postulante_carrera_segunda ON postulante(id_carrera_segunda_opc);
CREATE INDEX idx_postulante_carrera_admitido ON postulante(id_carrera_admitido);

-- Índices para PAGO (consultas por estado y postulante)
CREATE INDEX idx_pago_estado ON pago(estado);
CREATE INDEX idx_pago_postulante ON pago(id_postulante);
CREATE INDEX idx_pago_fecha ON pago(fecha_pago);

-- Índices para NOTA (consultas por postulante, docente, materia)
CREATE INDEX idx_nota_postulante ON nota(id_postulante);
CREATE INDEX idx_nota_docente ON nota(id_docente);
CREATE INDEX idx_nota_materia ON nota(id_materia);
CREATE INDEX idx_nota_examen ON nota(id_examen);
CREATE INDEX idx_nota_fecha ON nota(fecha_registro);

-- Índices para GRUPO_HORARIO (consultas por grupo, docente, aula)
CREATE INDEX idx_grupo_horario_grupo ON grupo_horario(id_grupo);
CREATE INDEX idx_grupo_horario_docente ON grupo_horario(id_docente);
CREATE INDEX idx_grupo_horario_aula ON grupo_horario(id_aula);

-- Índices para POSTULANTE_GRUPO
CREATE INDEX idx_postulante_grupo_postulante ON postulante_grupo(id_postulante);
CREATE INDEX idx_postulante_grupo_grupo ON postulante_grupo(id_grupo);

-- Índices para CREDENCIAL (búsqueda por registro y persona)
CREATE INDEX idx_credencial_registro ON credencial(registro);
CREATE INDEX idx_credencial_persona ON credencial(id_persona);
CREATE INDEX idx_credencial_rol ON credencial(rol);

-- Índices para BITACORA (consultas por fecha, persona, módulo)
CREATE INDEX idx_bitacora_fecha ON bitacora(fecha_hora);
CREATE INDEX idx_bitacora_persona ON bitacora(id_persona);
CREATE INDEX idx_bitacora_modulo ON bitacora(modulo);
CREATE INDEX idx_bitacora_accion ON bitacora(accion);

-- Índices para CARRERA_SEMESTRE
CREATE INDEX idx_carrera_semestre_carrera ON carrera_semestre(id_carrera);
CREATE INDEX idx_carrera_semestre_semestre ON carrera_semestre(id_semestre);

-- Índices para EXAMEN
CREATE INDEX idx_examen_semestre ON examen(id_semestre);

-- Índices para GRUPO
CREATE INDEX idx_grupo_semestre ON grupo(id_semestre);

-- Índices para DETALLE_PLANTILLA_HORARIO
CREATE INDEX idx_detalle_plantilla ON detalle_plantilla_horario(id_plantilla);
CREATE INDEX idx_detalle_plantilla_dia ON detalle_plantilla_horario(dia);

-- Índices para PERSONA (búsquedas comunes)
CREATE INDEX idx_persona_ci ON persona(ci);
CREATE INDEX idx_persona_correo ON persona(correo);
CREATE INDEX idx_persona_apellidos ON persona(apellido_paterno, apellido_materno);
