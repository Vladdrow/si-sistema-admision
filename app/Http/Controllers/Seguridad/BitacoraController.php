<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CU18 - Consultar Bitacora.
 *
 * Permite al administrador revisar eventos del sistema con filtros por texto,
 * modulo y accion, y exportar los resultados a CSV o PDF.
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

        $registros = $this->buildQuery($search, $module, $action)
            ->paginate(15)
            ->withQueryString();

        if ($isAsync) {
            return view('seguridad.bitacora.partials.table', compact('registros'));
        }

        return view('seguridad.bitacora.index', compact('registros', 'search', 'module', 'action', 'modules', 'actions'));
    }

    /**
     * CU18 - Exportar bitacora en formato CSV o PDF respetando los filtros.
     */
    public function export(Request $request): StreamedResponse|View
    {
        $search = trim((string) $request->query('buscar', ''));
        $module = (string) $request->query('modulo', '');
        $action = (string) $request->query('accion', '');
        $format = (string) $request->query('formato', 'csv');

        $registros = $this->buildQuery($search, $module, $action)->get();

        if ($format === 'pdf') {
            return view('seguridad.bitacora.export-pdf', compact('registros', 'search', 'module', 'action'));
        }

        return response()->streamDownload(function () use ($registros): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Fecha', 'Usuario', 'CI', 'Accion', 'Modulo', 'Descripcion', 'IP']);

            foreach ($registros as $registro) {
                fputcsv($handle, [
                    $registro->fecha_hora?->format('d/m/Y H:i:s'),
                    $registro->persona?->nombre_completo ?? 'Sin usuario',
                    $registro->persona?->ci,
                    $registro->accion,
                    $registro->modulo,
                    $registro->descripcion ?? '',
                    $registro->ip_origen ?? '',
                ]);
            }

            fclose($handle);
        }, 'bitacora.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildQuery(string $search, string $module, string $action): \Illuminate\Database\Eloquent\Builder
    {
        return Bitacora::with('persona')
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
            ->latest('fecha_hora');
    }
}
