<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CredencialController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParametroAdmisionController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PersonalAdministrativoController;
use App\Http\Controllers\PlantillaHorarioController;
use App\Http\Controllers\PostulanteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    // CU16 - Iniciar Sesion: muestra el formulario y valida registro/contrasena.
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

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

    // CU18 - Consultar Bitacora: solo administrador, con filtros en el controlador.
    Route::get('/bitacora', [BitacoraController::class, 'index'])
        ->name('bitacora.index')
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

    // CU10 - Gestionar Plantilla de Horario.
    Route::resource('plantillas', PlantillaHorarioController::class)
        ->parameters(['plantillas' => 'plantilla'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('role:Administrador,PersonalAdministrativo');

    // CU05 - Gestionar Docente.
    Route::resource('docentes', DocenteController::class)
        ->parameters(['docentes' => 'docente'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('role:Administrador,PersonalAdministrativo');

    // CU06 - Gestionar Personal Administrativo.
    Route::resource('personal', PersonalAdministrativoController::class)
        ->parameters(['personal' => 'personal'])
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('admin');

    // CU03 - Gestionar Postulante: admin/personal solo modifica, elimina, busca y lista.
    // El registro inicial queda fuera de este recurso porque lo realiza el propio postulante.
    Route::resource('postulantes', PostulanteController::class)
        ->parameters(['postulantes' => 'postulante'])
        ->only(['index', 'update', 'destroy'])
        ->middleware('role:Administrador,PersonalAdministrativo');
});
