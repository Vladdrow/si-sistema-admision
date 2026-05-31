<?php

namespace App\Http\Controllers;

use App\Models\ParametroAdmision;
use App\Models\Semestre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU07 - Configurar Parametros del Proceso de Admision.
 *
 * Centraliza semestre, fechas, monto, cupos, nota minima, limites de grupo y
 * expiracion de pago. Estos datos alimentan otros procesos de admision.
 */
class ParametroAdmisionController extends Controller
{
    public function index(Request $request): View
    {
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = $isAsync ? trim((string) $request->query('buscar')) : '';
        $semester = $isAsync ? (string) $request->query('semestre', '') : '';
        $semestres = Semestre::orderBy('nombre')->get();

        if ($semester !== '' && ! $semestres->contains('id_semestre', (int) $semester)) {
            $semester = '';
        }

        $parametros = ParametroAdmision::with('semestre')
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('semestre', fn ($semesterQuery) => $semesterQuery->where('nombre', 'like', "%{$search}%"));
            })
            ->when($semester !== '', fn ($query) => $query->where('id_semestre', $semester))
            ->join('semestre', 'semestre.id_semestre', '=', 'parametro_admision.id_semestre')
            ->orderByDesc('semestre.nombre')
            ->select('parametro_admision.*')
            ->paginate(15)
            ->withQueryString();

        if ($isAsync) {
            return view('parametros.partials.table', compact('parametros'));
        }

        return view('parametros.index', compact('parametros', 'search', 'semester', 'semestres'));
    }

    /**
     * CU07 - Registrar la configuracion inicial de un semestre de admision.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);

        $parametro = DB::transaction(function () use ($data): ParametroAdmision {
            $semestre = Semestre::create([
                'nombre' => $data['semestre_nombre'],
            ]);

            unset($data['semestre_nombre']);
            $data['id_semestre'] = $semestre->id_semestre;

            return ParametroAdmision::create($data);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Parametro configurado correctamente.', 'parametro' => $parametro->load('semestre')], 201);
        }

        return redirect()->route('parametros.index')->with('status', 'Parametro configurado correctamente.');
    }

    /**
     * CU07 - Modificar parametros existentes validando rangos y semestre unico.
     */
    public function update(Request $request, ParametroAdmision $parametro): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request, $parametro);

        DB::transaction(function () use ($data, $parametro): void {
            $parametro->semestre?->update([
                'nombre' => $data['semestre_nombre'],
            ]);

            unset($data['semestre_nombre']);
            $data['id_semestre'] = $parametro->id_semestre;
            $parametro->update($data);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Parametro actualizado correctamente.']);
        }

        return redirect()->route('parametros.index')->with('status', 'Parametro actualizado correctamente.');
    }

    /**
     * CU07 - Eliminar la configuracion junto con su semestre asociado.
     */
    public function destroy(Request $request, ParametroAdmision $parametro): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($parametro): void {
            $parametro->load('semestre');
            $semestre = $parametro->semestre;
            $parametro->delete();
            $semestre?->delete();
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Parametro eliminado correctamente.']);
        }

        return redirect()->route('parametros.index')->with('status', 'Parametro eliminado correctamente.');
    }

    private function validatedData(Request $request, ?ParametroAdmision $parametro = null): array
    {
        return $request->validate([
            'fecha_inicio_inscripcion' => ['required', 'date'],
            'fecha_cierre_inscripcion' => ['required', 'date', 'after:fecha_inicio_inscripcion'],
            'fecha_cierre_notas' => ['nullable', 'date', 'after_or_equal:fecha_cierre_inscripcion'],
            'monto_pago' => ['required', 'numeric', 'gt:0'],
            'max_estudiante_grupo' => ['required', 'integer', 'gt:0'],
            'nota_minima_aprobacion' => ['required', 'numeric', 'between:0,100'],
            'max_grupos_docente' => ['required', 'integer', 'gt:0'],
            'tiempo_expiracion_pago' => ['required', 'integer', 'gt:0'],
            'semestre_nombre' => [
                'required',
                'string',
                'max:20',
                Rule::unique('semestre', 'nombre')->ignore($parametro?->id_semestre, 'id_semestre'),
            ],
        ]);
    }
}
