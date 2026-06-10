<?php

use App\Http\Controllers\Academico\AdmisionController;
use App\Http\Controllers\Academico\GrupoController;
use App\Http\Controllers\Academico\HorarioController;
use App\Http\Controllers\Academico\NotaController;
use App\Http\Controllers\Academico\PlantillaHorarioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Personas\DocenteController;
use App\Http\Controllers\Personas\PagoController;
use App\Http\Controllers\Personas\PersonalAdministrativoController;
use App\Http\Controllers\Personas\PostulanteController;
use App\Http\Controllers\Personas\RegistroPostulanteController;
use App\Http\Controllers\Seguridad\AuthController;
use App\Http\Controllers\Seguridad\BitacoraController;
use App\Http\Controllers\Seguridad\CredencialController;
use App\Http\Controllers\Seguridad\ParametroAdmisionController;
use App\Http\Controllers\Seguridad\PasswordController;
use App\Http\Controllers\Seguridad\ReporteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    // CU16 - Iniciar Sesion: muestra el formulario y valida registro/contrasena.
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');

    // CU04 - Registrar Postulante: formulario publico de registro.
    Route::get('/registro', [RegistroPostulanteController::class, 'create'])->name('registro.create');
    Route::post('/registro', [RegistroPostulanteController::class, 'store'])->name('registro.store');
});

// CU04 - Libelula: pagina de pago simulada y callback de la pasarela.
Route::get('/pago/libelula', [RegistroPostulanteController::class, 'showPago'])->name('pago.libelula');
Route::post('/pago/libelula/callback', [RegistroPostulanteController::class, 'callback'])->name('pago.callback');

// CU02 - Gestionar Contrasena: recuperacion publica mediante codigo enviado al correo registrado.
Route::get('/recuperar-contrasena', [PasswordController::class, 'showRecoveryRequest'])->name('password.recovery.request');
Route::post('/recuperar-contrasena', [PasswordController::class, 'sendRecoveryCode'])->name('password.recovery.send');
Route::get('/restablecer-contrasena', [PasswordController::class, 'showRecoveryReset'])->name('password.recovery.reset');
Route::post('/restablecer-contrasena', [PasswordController::class, 'resetWithCode'])->name('password.recovery.update');

Route::middleware(['auth', 'activity'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // CU17 - Cerrar Sesion: invalida la sesion activa y vuelve al login.
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // CU02 - Gestionar Contrasena: cambio de contrasena para usuarios autenticados.
    Route::get('/contrasena', [PasswordController::class, 'edit'])->name('password.edit');
    Route::patch('/contrasena', [PasswordController::class, 'update'])->name('password.update');

    // CU18 - Consultar Bitacora: solo administrador, con filtros y exportacion.
    Route::get('/bitacora', [BitacoraController::class, 'index'])
        ->name('bitacora.index')
        ->middleware('admin');

    Route::get('/bitacora/exportar', [BitacoraController::class, 'export'])
        ->name('bitacora.export')
        ->middleware('admin');

    // CU01 - Gestionar Credenciales: restauracion de credenciales desactivadas.
    Route::patch('/credenciales/{credencial}/restaurar', [CredencialController::class, 'restore'])
        ->name('credenciales.restore')
        ->middleware('admin');

    // CU01 - Gestionar Credenciales: buscar/listar, registrar, modificar y desactivar.
    // El flujo de registrar credencial fue agregado al CU01 actualizado del proyecto.
    Route::resource('credenciales', CredencialController::class)
        ->parameters(['credenciales' => 'credencial'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('admin');

    // CU07 - Configurar Parametros del Proceso de Admision.
    Route::resource('parametros', ParametroAdmisionController::class)
        ->parameters(['parametros' => 'parametro'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('admin');

    // CU13 - Consultar Nota: solo lectura para admin y personal administrativo.
    Route::get('/notas/consulta', [NotaController::class, 'consulta'])->name('notas.consulta')->middleware('role:Administrador,PersonalAdministrativo');
    Route::get('/notas/consulta/{grupo}', [NotaController::class, 'consultaGrupo'])->name('notas.consulta-grupo')->middleware('role:Administrador,PersonalAdministrativo');

    // CU14 - Ejecutar Admision: asignacion automatica de cupos por orden de merito.
    Route::get('/admision', [AdmisionController::class, 'index'])->name('admision.index')->middleware('role:Administrador,PersonalAdministrativo');
    Route::post('/admision/ejecutar', [AdmisionController::class, 'ejecutar'])->name('admision.ejecutar')->middleware('role:Administrador,PersonalAdministrativo');

    // CU15 - Generar Reportes: consolida postulantes, notas, grupos y docentes.
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index')->middleware('role:Administrador,PersonalAdministrativo');
    Route::get('/reportes/exportar', [ReporteController::class, 'export'])->name('reportes.export')->middleware('role:Administrador,PersonalAdministrativo');

    // CU12 - Gestionar Nota: solo docentes, por grupo y materia.
    Route::get('/notas', [NotaController::class, 'index'])->name('notas.index')->middleware('role:Docente');
    Route::get('/notas/{grupo}', [NotaController::class, 'show'])->name('notas.show')->middleware('role:Docente');
    Route::post('/notas/{grupo}/guardar', [NotaController::class, 'store'])->name('notas.store')->middleware('role:Docente');

    // CU11 - Consultar Horario: vista por rol (postulante, docente, admin).
    Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');

    // CU10 - Gestionar Plantilla de Horario.
    Route::resource('plantillas', PlantillaHorarioController::class)
        ->parameters(['plantillas' => 'plantilla'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('role:Administrador,PersonalAdministrativo');

    // CU08 - Consultar Pagos: listar, filtrar y generar comprobantes.
    Route::get('/pagos', [PagoController::class, 'index'])
        ->name('pagos.index')
        ->middleware('role:Administrador,PersonalAdministrativo');

    Route::get('/pagos/{pago}/comprobante', [PagoController::class, 'comprobante'])
        ->name('pagos.comprobante')
        ->middleware('role:Administrador,PersonalAdministrativo');

    // CU09 - Gestionar Grupo: crear, cerrar inscripciones, consultar, asignar horario.
    Route::middleware('role:Administrador,PersonalAdministrativo')->group(function (): void {
        Route::get('/grupos', [GrupoController::class, 'index'])->name('grupos.index');
        Route::get('/grupos/{grupo}', [GrupoController::class, 'show'])->name('grupos.show');
        Route::post('/grupos/crear', [GrupoController::class, 'crearGrupos'])->name('grupos.crear');
        Route::post('/grupos/cerrar', [GrupoController::class, 'cerrarInscripciones'])->name('grupos.cerrar');
        Route::get('/grupos/{grupo}/asignar-horario', [GrupoController::class, 'showAsignarHorario'])->name('grupos.asignar-horario');
        Route::post('/grupos/{grupo}/asignar-horario', [GrupoController::class, 'asignarHorario'])->name('grupos.asignar-horario.store');
        Route::get('/grupos/{grupo}/validar-asignacion', [GrupoController::class, 'validarAsignacion'])->name('grupos.validar-asignacion');
    });

    // CU05 - Gestionar Docente: baja logica y restauracion.
    Route::patch('/docentes/{docente}/restaurar', [DocenteController::class, 'restore'])
        ->name('docentes.restore')
        ->middleware('role:Administrador,PersonalAdministrativo');

    Route::resource('docentes', DocenteController::class)
        ->parameters(['docentes' => 'docente'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('role:Administrador,PersonalAdministrativo');

    // CU06 - Gestionar Personal Administrativo: baja logica y restauracion.
    Route::patch('/personal/{personal}/restaurar', [PersonalAdministrativoController::class, 'restore'])
        ->name('personal.restore')
        ->middleware('admin');

    Route::resource('personal', PersonalAdministrativoController::class)
        ->parameters(['personal' => 'personal'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('admin');

    // CU03 - Gestionar Postulante: admin/personal solo modifica, elimina (baja logica), busca y lista.
    Route::patch('/postulantes/{postulante}/restaurar', [PostulanteController::class, 'restore'])
        ->name('postulantes.restore')
        ->middleware('role:Administrador,PersonalAdministrativo');

    Route::resource('postulantes', PostulanteController::class)
        ->parameters(['postulantes' => 'postulante'])
        ->only(['index', 'update', 'destroy'])
        ->middleware('role:Administrador,PersonalAdministrativo');
});
