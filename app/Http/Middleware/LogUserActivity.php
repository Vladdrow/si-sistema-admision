<?php

namespace App\Http\Middleware;

use App\Models\Credencial;
use App\Services\BitacoraLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soporte transversal para CU18 - Consultar Bitacora.
 *
 * Traduce las rutas de los casos de uso implementados a eventos legibles
 * para que el administrador pueda auditar acciones desde la bitacora.
 */
class LogUserActivity
{
    public function __construct(private readonly BitacoraLogger $logger)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();

        if (! $user instanceof Credencial || $response->getStatusCode() >= 400) {
            return $response;
        }

        if ($request->ajax() && $request->isMethod('GET')) {
            return $response;
        }

        $entry = $this->entryFor($request);

        if ($entry !== null) {
            $this->logger->registrar($request, $user, $entry['accion'], $entry['modulo'], $entry['descripcion']);
        }

        return $response;
    }

    private function entryFor(Request $request): ?array
    {
        // Mapa de rutas a acciones de auditoria: CU01, CU02, CU03, CU05, CU06, CU07, CU10 y CU18.
        return match ($request->route()?->getName()) {
            'dashboard' => $this->entry('Consultar', 'Panel principal', 'Ingreso al panel principal.'),
            'credenciales.index' => $this->entry('Consultar', 'Credenciales', 'Consulto el listado de credenciales.'),
            'credenciales.store' => $this->entry('Registrar', 'Credenciales', 'Registro una credencial.'),
            'credenciales.update' => $this->entry('Modificar', 'Credenciales', 'Modifico una credencial.'),
            'credenciales.destroy' => $this->entry('Desactivar', 'Credenciales', 'Desactivo una credencial.'),
            'credenciales.restore' => $this->entry('Restaurar', 'Credenciales', 'Restauro una credencial.'),
            'bitacora.index' => $this->entry('Consultar', 'Bitacora', 'Consulto la bitacora del sistema.'),
            'parametros.index' => $this->entry('Consultar', 'Parametros de admision', 'Consulto parametros de admision.'),
            'parametros.store' => $this->entry('Registrar', 'Parametros de admision', 'Registro parametros de admision.'),
            'parametros.update' => $this->entry('Modificar', 'Parametros de admision', 'Modifico parametros de admision.'),
            'parametros.destroy' => $this->entry('Eliminar', 'Parametros de admision', 'Elimino parametros de admision.'),
            'plantillas.index' => $this->entry('Consultar', 'Plantillas de horario', 'Consulto plantillas de horario.'),
            'plantillas.store' => $this->entry('Registrar', 'Plantillas de horario', 'Registro una plantilla de horario.'),
            'plantillas.update' => $this->entry('Modificar', 'Plantillas de horario', 'Modifico una plantilla de horario.'),
            'plantillas.destroy' => $this->entry('Eliminar', 'Plantillas de horario', 'Elimino una plantilla de horario.'),
            'password.edit' => $this->entry('Consultar', 'Contrasena', 'Consulto la gestion de contrasena.'),
            'password.update' => $this->entry('Modificar', 'Contrasena', 'Actualizo su contrasena.'),
            'docentes.index' => $this->entry('Consultar', 'Docentes', 'Consulto el listado de docentes.'),
            'docentes.store' => $this->entry('Registrar', 'Docentes', 'Registro un docente.'),
            'docentes.update' => $this->entry('Modificar', 'Docentes', 'Modifico un docente.'),
            'docentes.destroy' => $this->entry('Desactivar', 'Docentes', 'Desactivo un docente.'),
            'docentes.restore' => $this->entry('Restaurar', 'Docentes', 'Restauro un docente.'),
            'personal.index' => $this->entry('Consultar', 'Personal administrativo', 'Consulto el listado de personal administrativo.'),
            'personal.store' => $this->entry('Registrar', 'Personal administrativo', 'Registro personal administrativo.'),
            'personal.update' => $this->entry('Modificar', 'Personal administrativo', 'Modifico personal administrativo.'),
            'personal.destroy' => $this->entry('Desactivar', 'Personal administrativo', 'Desactivo personal administrativo.'),
            'personal.restore' => $this->entry('Restaurar', 'Personal administrativo', 'Restauro personal administrativo.'),
            'postulantes.index' => $this->entry('Consultar', 'Postulantes', 'Consulto el listado de postulantes.'),
            'postulantes.update' => $this->entry('Modificar', 'Postulantes', 'Modifico un postulante.'),
            'postulantes.destroy' => $this->entry('Desactivar', 'Postulantes', 'Desactivo un postulante.'),
            'postulantes.restore' => $this->entry('Restaurar', 'Postulantes', 'Restauro un postulante.'),
            'pagos.index' => $this->entry('Consultar', 'Pagos', 'Consulto el listado de pagos.'),
            'pagos.comprobante' => $this->entry('Generar comprobante', 'Pagos', 'Genero un comprobante de pago.'),
            'grupos.index' => $this->entry('Consultar', 'Grupos', 'Consulto el listado de grupos.'),
            'grupos.show' => $this->entry('Consultar', 'Grupos', 'Consulto el detalle de un grupo.'),
            'grupos.crear' => $this->entry('Crear grupos', 'Grupos', 'Creo grupos para postulantes pendientes.'),
            'grupos.cerrar' => $this->entry('Cerrar inscripciones', 'Grupos', 'Cerro inscripciones y creo grupos finales.'),
            'grupos.asignar-horario' => $this->entry('Consultar', 'Grupos', 'Consulto la asignacion de horario de un grupo.'),
            'grupos.asignar-horario.store' => $this->entry('Asignar horario', 'Grupos', 'Asigno horario a un grupo.'),
            'horarios.index' => $this->entry('Consultar', 'Horarios', 'Consulto los horarios del sistema.'),
            'admision.index' => $this->entry('Consultar', 'Admision', 'Consulto el estado del proceso de admision.'),
            'admision.ejecutar' => $this->entry('Ejecutar', 'Admision', 'Ejecuto la asignacion automatica de cupos.'),
            'reportes.index' => $this->entry('Consultar', 'Reportes', 'Consulto los reportes del proceso de admision.'),
            'reportes.export' => $this->entry('Exportar', 'Reportes', 'Exporto reportes del proceso de admision.'),
            'bitacora.export' => $this->entry('Exportar', 'Bitacora', 'Exporto la bitacora del sistema.'),
            'logout' => null,
            default => $this->genericEntry($request),
        };
    }

    private function entry(string $accion, string $modulo, string $descripcion): array
    {
        return compact('accion', 'modulo', 'descripcion');
    }

    private function genericEntry(Request $request): array
    {
        $accion = match ($request->method()) {
            'GET' => 'Consultar',
            'POST' => 'Registrar',
            'PUT', 'PATCH' => 'Modificar',
            'DELETE' => 'Eliminar',
            default => 'Acceder',
        };
        $segment = $request->segment(1) ?: 'Sistema';
        $modulo = str($segment)->replace('-', ' ')->title()->toString();

        return $this->entry($accion, $modulo, "{$accion} en {$modulo}.");
    }
}
