-- Active: 1780052698035@@127.0.0.1@5432@sistema_admision
-- =============================================
-- INSERT DE PERSONAS (con CI de 7 dígitos)
-- =============================================

-- Primer grupo (IDs 1 al 10)
INSERT INTO persona (ci, nombres, apellido_paterno, apellido_materno, fecha_nacimiento, sexo, direccion, telefono, correo) VALUES
('1000001', 'Damian', 'Perez', 'Gomez', '1990-01-01', 'M', 'Calle Falsa 123', '69206199', 'damian.perez@example.com'),
('1000002', 'Maria', 'Lopez', 'Fernandez', '1992-03-15', 'F', 'Av. Libertad 456', '70123456', 'maria.lopez@example.com'),
('1000003', 'Carlos', 'Rodriguez', 'Suarez', '1988-07-22', 'M', 'Barrio Norte 789', '71234567', 'carlos.rodriguez@example.com'),
('1000004', 'Ana', 'Martinez', 'Rojas', '1995-11-10', 'F', 'Calle Comercio 321', '72345678', 'ana.martinez@example.com'),
('1000005', 'Luis', 'Vargas', 'Mendoza', '1991-05-05', 'M', 'Zona Sur 654', '73456789', 'luis.vargas@example.com'),
('1000006', 'Sofia', 'Torrez', 'Castro', '1998-08-18', 'F', 'Av. Principal 987', '74567890', 'sofia.torrez@example.com'),
('1000007', 'Jorge', 'Flores', 'Gutierrez', '1987-12-30', 'M', 'Calle Central 147', '75678901', 'jorge.flores@example.com'),
('1000008', 'Valeria', 'Ramos', 'Quispe', '1996-06-25', 'F', 'Barrio Este 258', '76789012', 'valeria.ramos@example.com'),
('1000009', 'Miguel', 'Herrera', 'Salazar', '1993-09-12', 'M', 'Av. Siempre Viva 369', '77890123', 'miguel.herrera@example.com'),
('1000010', 'Lucia', 'Navarro', 'Paz', '1999-02-28', 'F', 'Zona Centro 741', '78901234', 'lucia.navarro@example.com');

-- Segundo grupo (IDs 11 al 40)
INSERT INTO persona (ci, nombres, apellido_paterno, apellido_materno, fecha_nacimiento, sexo, direccion, telefono, correo) VALUES
('1000011','Juan','Perez','Gomez','1990-01-15','M','Av. Banzer 101','70000001','juan.perez1@example.com'),
('1000012','Maria','Lopez','Fernandez','1991-02-20','F','Av. Alemana 102','70000002','maria.lopez2@example.com'),
('1000013','Carlos','Rodriguez','Suarez','1989-03-10','M','Av. Busch 103','70000003','carlos.rodriguez3@example.com'),
('1000014','Ana','Martinez','Rojas','1993-04-25','F','Calle 4 Oeste 104','70000004','ana.martinez4@example.com'),
('1000015','Luis','Vargas','Mendoza','1988-05-18','M','Zona Norte 105','70000005','luis.vargas5@example.com'),
('1000016','Sofia','Torrez','Castro','1995-06-12','F','Av. Santos Dumont 106','70000006','sofia.torrez6@example.com'),
('1000017','Jorge','Flores','Gutierrez','1992-07-09','M','Barrio Equipetrol 107','70000007','jorge.flores7@example.com'),
('1000018','Valeria','Ramos','Quispe','1994-08-14','F','Av. Paragua 108','70000008','valeria.ramos8@example.com'),
('1000019','Miguel','Herrera','Salazar','1987-09-21','M','Av. Mutualista 109','70000009','miguel.herrera9@example.com'),
('1000020','Lucia','Navarro','Paz','1996-10-11','F','Calle Aroma 110','70000010','lucia.navarro10@example.com'),
('1000021','Diego','Molina','Perez','1991-11-05','M','Zona Sur 111','70000011','diego.molina11@example.com'),
('1000022','Paola','Reyes','Lozano','1990-12-22','F','Av. Virgen de Cotoca 112','70000012','paola.reyes12@example.com'),
('1000023','Fernando','Ortiz','Rivera','1986-01-30','M','Barrio Hamacas 113','70000013','fernando.ortiz13@example.com'),
('1000024','Gabriela','Rojas','Flores','1997-02-17','F','Av. Beni 114','70000014','gabriela.rojas14@example.com'),
('1000025','Ricardo','Vega','Morales','1985-03-08','M','Calle Libertad 115','70000015','ricardo.vega15@example.com'),
('1000026','Carla','Sanchez','Mendez','1998-04-16','F','Av. Piraí 116','70000016','carla.sanchez16@example.com'),
('1000027','Andres','Cruz','Torrez','1993-05-27','M','Zona Central 117','70000017','andres.cruz17@example.com'),
('1000028','Natalia','Ruiz','Vargas','1992-06-06','F','Av. Grigotá 118','70000018','natalia.ruiz18@example.com'),
('1000029','Mario','Castro','Aguilera','1989-07-13','M','Barrio Urbarí 119','70000019','mario.castro19@example.com'),
('1000030','Daniela','Mendoza','Rojas','1995-08-02','F','Av. Busch 120','70000020','daniela.mendoza20@example.com'),
('1000031','Roberto','Salinas','Flores','1988-09-19','M','Av. Cristo Redentor 121','70000021','roberto.salinas21@example.com'),
('1000032','Patricia','Quispe','Lopez','1994-10-24','F','Zona Oeste 122','70000022','patricia.quispe22@example.com'),
('1000033','Hector','Paz','Navarro','1987-11-29','M','Av. Cumavi 123','70000023','hector.paz23@example.com'),
('1000034','Elena','Morales','Soto','1996-12-07','F','Barrio Guaracal 124','70000024','elena.morales24@example.com'),
('1000035','Cristian','Rojas','Molina','1991-01-18','M','Av. Doble Vía La Guardia 125','70000025','cristian.rojas25@example.com'),
('1000036','Veronica','Salazar','Perez','1993-02-28','F','Zona Este 126','70000026','veronica.salazar26@example.com'),
('1000037','Oscar','Fernandez','Vega','1986-03-15','M','Av. Alemania 127','70000027','oscar.fernandez27@example.com'),
('1000038','Camila','Gutierrez','Castro','1998-04-20','F','Barrio El Trompillo 128','70000028','camila.gutierrez28@example.com'),
('1000039','Alberto','Lozano','Ruiz','1990-05-09','M','Av. Roca y Coronado 129','70000029','alberto.lozano29@example.com'),
('1000040','Fernanda','Torrez','Mendoza','1997-06-26','F','Zona Norte 130','70000030','fernanda.torrez30@example.com');

-- =============================================
-- INSERT DE CREDENCIALES (registro de 10 dígitos)
-- El registro coincide con el CI de la persona
-- =============================================
SELECT * FROM credencial;

-- Primer grupo (IDs persona 1 al 10)
INSERT INTO credencial (registro, contrasena, rol, estado, id_persona) VALUES
('2025000001', 'lzms1218', 'Administrador', true, 1),
('2025000002', 'clave123', 'PersonalAdministrativo', true, 2),
('2025000003', 'admin2025', 'Docente', true, 3),
('2025000004', 'pass456', 'Docente', true, 4),
('2025000005', 'user789', 'PersonalAdministrativo', true, 5),
('2025000006', 'sofia998', 'Postulante', true, 6),
('2025000007', 'jorge321', 'Docente', true, 7),
('2025000008', 'vale654', 'Postulante', true, 8),
('2025000009', 'miguel777', 'Administrador', true, 9),
('2025000010', 'lucia888', 'Postulante', true, 10);

-- Segundo grupo (IDs persona 11 al 40)
INSERT INTO credencial (registro, contrasena, rol, estado, id_persona) VALUES
('2025000011','clave001','Administrador',true,11),
('2025000012','clave002','PersonalAdministrativo',true,12),
('2025000013','clave003','Docente',true,13),
('2025000014','clave004','Docente',true,14),
('2025000015','clave005','Postulante',true,15),
('2025000016','clave006','Postulante',true,16),
('2025000017','clave007','Docente',true,17),
('2025000018','clave008','Postulante',true,18),
('2025000019','clave009','Administrador',true,19),
('2025000020','clave010','Postulante',true,20),
('2025000021','clave011','Docente',true,21),
('2025000022','clave012','PersonalAdministrativo',true,22),
('2025000023','clave013','Administrador',true,23),
('2025000024','clave014','Docente',true,24),
('2025000025','clave015','Postulante',true,25),
('2025000027','clave017','Docente',true,26),
('2025000028','clave018','PersonalAdministrativo',true,27),
('2025000029','clave019','Docente',true,28),
('2025000030','clave020','Postulante',true,29),
('2025000031','clave021','Administrador',true,30),
('2025000032','clave022','Postulante',true,31),
('2025000033','clave023','Docente',true,32),
('2025000034','clave024','PersonalAdministrativo',true,33),
('2025000035','clave025','Docente',true,34),
('2025000036','clave026','Postulante',true,35),
('2025000037','clave027','Administrador',true,36),
('2025000038','clave028','Docente',true,37),
('2025000039','clave029','Postulante',true,38),
('2025000040','clave030','PersonalAdministrativo',true,39);

-- =============================================
-- DOCENTES (id_docente = id_persona)
-- =============================================
INSERT INTO docente (id_docente, titulo_profesional, tiene_maestria, tiene_diplomado, codigo_rda) VALUES
(3, 'Licenciatura en Matemáticas', TRUE, TRUE, 'RDA2024001'),
(4, 'Licenciatura en Física', TRUE, TRUE, 'RDA2024002'),
(7, 'Ingeniería en Sistemas', TRUE, TRUE, 'RDA2024003'),
(13, 'Licenciatura en Literatura', TRUE, TRUE, 'RDA2024004'),
(14, 'Licenciatura en Historia', TRUE, TRUE, 'RDA2024005'),
(17, 'Ingeniería Civil', TRUE, TRUE, 'RDA2024006'),
(21, 'Licenciatura en Biología', TRUE, TRUE, 'RDA2024007'),
(24, 'Licenciatura en Química', TRUE, TRUE, 'RDA2024008'),
(26, 'Ingeniería Industrial', TRUE, TRUE, 'RDA2024009'),
(28, 'Licenciatura en Filosofía', TRUE, TRUE, 'RDA2024010'),
(32, 'Licenciatura en Geografía', TRUE, TRUE, 'RDA2024011'),
(34, 'Ingeniería Eléctrica', TRUE, TRUE, 'RDA2024012'),
(37, 'Licenciatura en Psicología', TRUE, TRUE, 'RDA2024013');

-- =============================================
-- PERSONAL ADMINISTRATIVO
-- =============================================
INSERT INTO personal_administrativo (id_personal, cargo) VALUES
(2, 'Secretario Académico'),
(5, 'Jefe de Unidad'),
(12, 'Coordinador Administrativo'),
(22, 'Analista de Admisión'),
(27, 'Asistente de Dirección'),
(33, 'Técnico de Sistemas'),
(39, 'Auxiliar de Registros');

-- =============================================
-- CARRERAS
-- =============================================
INSERT INTO carrera (id_carrera, codigo, nombre) VALUES
(1, '187-3', 'Ingeniería en Sistemas'),
(2, '187-4', 'Ingeniería en Informática'),
(3, '187-5', 'Ingeniería en Redes y Telecomunicaciones'),
(4, '187-6', 'Ingeniería en Robótica');

-- =============================================
-- POSTULANTES (id_postulante = id_persona)
-- =============================================
INSERT INTO postulante (
    id_postulante,
    colegio_procedencia,
    ciudad,
    estado_admision,
    codigo_libreta,
    codigo_titulo,
    id_carrera_primera_opc,
    id_carrera_segunda_opc,
    id_carrera_admitido
) VALUES
(6, 'Unidad Educativa San José', 'Santa Cruz', 'Pendiente', 'LIB2024001', 'TIT2024001', 1, 2, NULL),
(8, 'Colegio Alemán', 'Santa Cruz', 'Pendiente', 'LIB2024002', 'TIT2024002', 2, 1, NULL),
(10, 'Unidad Educativa Bolívar', 'La Paz', 'Pendiente', 'LIB2024003', 'TIT2024003', 3, 4, NULL),
(15, 'Colegio San Ignacio', 'Cochabamba', 'Pendiente', 'LIB2024004', 'TIT2024004', 1, 4, NULL),
(16, 'Unidad Educativa Santo Tomás', 'Santa Cruz', 'Pendiente', 'LIB2024005', 'TIT2024005', 1, 2, NULL),
(18, 'Colegio La Salle', 'La Paz', 'Pendiente', 'LIB2024006', 'TIT2024006', 2, 3, NULL),
(20, 'Unidad Educativa Alemán', 'Santa Cruz', 'Pendiente', 'LIB2024007', 'TIT2024007', 2, 3, NULL),
(25, 'Colegio Santa Ana', 'Cochabamba', 'Pendiente', 'LIB2024008', 'TIT2024008', 2, 4, NULL),
(29, 'Unidad Educativa Juan XXIII', 'Santa Cruz', 'Pendiente', 'LIB2024009', 'TIT2024009', 1, 4, NULL),
(31, 'Colegio San Patricio', 'La Paz', 'Pendiente', 'LIB2024010', 'TIT2024010', 3, 4, NULL),
(35, 'Unidad Educativa Cristo Rey', 'Santa Cruz', 'Pendiente', 'LIB2024011', 'TIT2024011', 2, 3, NULL),
(38, 'Colegio San Agustín', 'Cochabamba', 'Pendiente', 'LIB2024012', 'TIT2024012', 4, 3, NULL);

-- =============================================
-- SEMESTRES
-- =============================================
INSERT INTO semestre (id_semestre, nombre) VALUES
(1, '1-2024'),
(2, '2-2024'),
(3, '1-2025'),
(4, '2-2025');

-- Reiniciar secuencia de semestre (PostgreSQL)
SELECT setval('semestre_id_semestre_seq', (SELECT MAX(id_semestre) FROM semestre));

-- =============================================
-- CARRERA_SEMESTRE (cupos por carrera y semestre)
-- =============================================
INSERT INTO carrera_semestre (cantidad_cupos, cantidad_estudiantes, id_carrera, id_semestre) VALUES
-- Semestre 1
(40, 0, 1, 1),
(35, 0, 2, 1),
(30, 0, 3, 1),
(25, 0, 4, 1),
-- Semestre 2
(40, 0, 1, 2),
(35, 0, 2, 2),
(30, 0, 3, 2),
(25, 0, 4, 2);


INSERT INTO materia (codigo, nombre) VALUES
('INF001','Computación'),
('MAT001','Matemáticas'),
('FIS001','Fisica'),
('ING001','Inglés');


-- AULAS DEL 11 AL 17 (Piso 1)
INSERT INTO aula (nombre, capacidad, ubicacion) VALUES
('Aula 11', 80, 'Piso 1'),
('Aula 12', 75, 'Piso 1'),
('Aula 13', 90, 'Piso 1'),
('Aula 14', 70, 'Piso 1'),
('Aula 15', 85, 'Piso 1'),
('Aula 16', 95, 'Piso 1'),
('Aula 17', 78, 'Piso 1'),

-- AULAS DEL 21 AL 27 (Piso 2)
('Aula 21', 82, 'Piso 2'),
('Aula 22', 88, 'Piso 2'),
('Aula 23', 72, 'Piso 2'),
('Aula 24', 92, 'Piso 2'),
('Aula 25', 76, 'Piso 2'),
('Aula 26', 84, 'Piso 2'),
('Aula 27', 98, 'Piso 2'),

-- AULAS DEL 31 AL 37 (Piso 3)
('Aula 31', 86, 'Piso 3'),
('Aula 32', 74, 'Piso 3'),
('Aula 33', 94, 'Piso 3'),
('Aula 34', 68, 'Piso 3'),
('Aula 35', 100, 'Piso 3'),
('Aula 36', 79, 'Piso 3'),
('Aula 37', 83, 'Piso 3'),

-- AULAS DEL 41 AL 47 (Piso 4)
('Aula 41', 91, 'Piso 4'),
('Aula 42', 77, 'Piso 4'),
('Aula 43', 87, 'Piso 4'),
('Aula 44', 73, 'Piso 4'),
('Aula 45', 96, 'Piso 4'),
('Aula 46', 81, 'Piso 4'),
('Aula 47', 89, 'Piso 4');
