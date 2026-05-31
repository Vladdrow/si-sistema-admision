<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU18 - Consultar Bitacora.
 *
 * Permite al administrador revisar eventos del sistema con filtros por texto,
 * modulo y accion. La vista parcial se reutiliza para busquedas asincronas.
 */
class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = $isAsync ? trim((string) $request->query('buscar')) : '';
        $module = $isAsync ? (string) $request->query('modulo', '') : '';
        $action = $isAsync ? (string) $request->query('accion', '') : '';

        $modules = Bitacora::query()
            ->select('modulo')
            ->distinct()
            ->orderBy('modulo')
            ->pluck('modulo')
            ->all();

        $actions = Bitacora::query()
            ->select('accion')
            ->distinct()
            ->orderBy('accion')
            ->pluck('accion')
            ->all();

        if (! in_array($module, $modules, true)) {
            $module = '';
        }

        if (! in_array($action, $actions, true)) {
            $action = '';
        }

        $registros = Bitacora::with('persona')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('ip_origen', 'like', "%{$search}%")
                        ->orWhereHas('persona', function ($personQuery) use ($search): void {
                            $personQuery->where('ci', 'like', "%{$search}%")
                                ->orWhere('nombres', 'like', "%{$search}%")
                                ->orWhere('apellido_paterno', 'like', "%{$search}%")
                                ->orWhere('apellido_materno', 'like', "%{$search}%");
                        });
                });
            })
            ->when($module !== '', function ($query) use ($module): void {
                $query->where('modulo', $module);
            })
            ->when($action !== '', function ($query) use ($action): void {
                $query->where('accion', $action);
            })
            ->latest('fecha_hora')
            ->paginate(15)
            ->withQueryString();

        if ($isAsync) {
            return view('bitacora.partials.table', compact('registros'));
        }

        return view('bitacora.index', compact('registros', 'search', 'module', 'action', 'modules', 'actions'));
    }
}
