<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU08 - Consultar Pago.
 *
 * Permite al Personal Administrativo visualizar, filtrar y consultar los
 * pagos realizados por los postulantes, asi como generar comprobantes.
 */
class PagoController extends Controller
{
    public function index(Request $request): View
    {
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = $isAsync ? trim((string) $request->query('buscar')) : '';
        $status = $isAsync ? (string) $request->query('estado', '') : '';
        $statuses = ['Pendiente', 'Pagado', 'Rechazado', 'Expirado'];

        if (! in_array($status, $statuses, true)) {
            $status = '';
        }

        $pagos = Pago::with(['postulante.persona', 'postulante.persona.credencial'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('codigo_orden', 'like', "%{$search}%")
                        ->orWhere('numero_transaccion', 'like', "%{$search}%")
                        ->orWhereHas('postulante', function ($q) use ($search): void {
                            $q->where('codigo_libreta', 'like', "%{$search}%")
                                ->orWhere('codigo_titulo', 'like', "%{$search}%");
                        })
                        ->orWhereHas('postulante.persona', function ($q) use ($search): void {
                            $q->where('ci', 'like', "%{$search}%")
                                ->orWhere('nombres', 'like', "%{$search}%")
                                ->orWhere('apellido_paterno', 'like', "%{$search}%")
                                ->orWhere('apellido_materno', 'like', "%{$search}%")
                                ->orWhere('correo', 'like', "%{$search}%");
                        })
                        ->orWhereHas('postulante.persona.credencial', function ($q) use ($search): void {
                            $q->where('registro', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('estado', $status))
            ->join('postulante', 'postulante.id_postulante', '=', 'pago.id_postulante')
            ->join('persona', 'persona.id_persona', '=', 'postulante.id_postulante')
            ->orderByDesc('pago.fecha_pago')
            ->orderByDesc('pago.id_pago')
            ->select('pago.*')
            ->paginate(15)
            ->withQueryString();

        if ($isAsync) {
            return view('pagos.partials.table', compact('pagos'));
        }

        return view('pagos.index', compact('pagos', 'search', 'status', 'statuses'));
    }

    /**
     * CU08 - Genera un comprobante de pago en formato imprimible.
     */
    public function comprobante(Pago $pago): View
    {
        $pago->load(['postulante.persona', 'postulante.carreraPrimera', 'postulante.carreraSegunda']);

        return view('pagos.comprobante', compact('pago'));
    }
}
