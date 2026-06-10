<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;

use App\Models\Carrera;
use App\Models\CarreraSemestre;
use App\Models\Examen;
use App\Models\ParametroAdmision;
use App\Models\Semestre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $carreras = Carrera::orderBy('nombre')->get();
        $existeSemestreActivo = Semestre::query()
            ->whereRaw('LOWER(estado) = ?', ['activo'])
            ->exists();

        if ($semester !== '' && ! $semestres->contains('id_semestre', (int) $semester)) {
            $semester = '';
        }

        $parametros = ParametroAdmision::with(['semestre', 'cuposCarrera.carrera', 'examenes'])
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
            return view('seguridad.parametros.partials.table', compact('parametros'));
        }

        return view('seguridad.parametros.index', compact('parametros', 'search', 'semester', 'semestres', 'carreras', 'existeSemestreActivo'));
    }

    /**
     * CU07 - Registrar la configuracion inicial de un semestre de admision.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);

        if (Semestre::query()->whereRaw('LOWER(estado) = ?', ['activo'])->exists()) {
            throw ValidationException::withMessages([
                'semestre_nombre' => 'No se puede registrar otro parametro mientras exista un semestre activo.',
            ]);
        }

        $parametro = DB::transaction(function () use ($data): ParametroAdmision {
            $this->syncParametroSerialSequences();

            $semestre = Semestre::create([
                'nombre' => $data['semestre_nombre'],
                'estado' => 'Activo',
            ]);

            $coreData = $this->parameterData($data);
            $coreData['id_semestre'] = $semestre->id_semestre;

            $parametro = ParametroAdmision::create($coreData);
            $this->syncAdmissionRules($semestre->id_semestre, $data['cupos'], $data['ponderaciones']);

            return $parametro;
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Parametro configurado correctamente.', 'parametro' => $parametro->load(['semestre', 'cuposCarrera', 'examenes'])], 201);
        }

        return redirect()->route('parametros.index')->with('status', 'Parametro configurado correctamente.');
    }

    /**
     * CU07 - Modificar parametros existentes validando rangos y semestre unico.
     *
     * Si la fecha actual ya supero el inicio de inscripciones, no se permite
     * modificar el monto del pago. Si el proceso ya cerro no se permite modificar.
     */
    public function update(Request $request, ParametroAdmision $parametro): RedirectResponse|JsonResponse
    {
        $parametro->loadMissing('semestre');

        if ($this->semestreFinalizado($parametro)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se puede modificar un parametro de un semestre finalizado.',
                ], 422);
            }

            return back()->withErrors(['parametro' => 'No se puede modificar un parametro de un semestre finalizado.']);
        }

        if ($parametro->fecha_cierre_inscripcion && now()->gt($parametro->fecha_cierre_inscripcion)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se puede modificar un parametro cuyo proceso de admision ya cerro.',
                ], 422);
            }

            return back()->withErrors(['parametro' => 'No se puede modificar un parametro cuyo proceso de admision ya cerro.']);
        }
        $data = $this->validatedData($request, $parametro);

        $montoBloqueado = $parametro->fecha_inicio_inscripcion
            && now()->gte($parametro->fecha_inicio_inscripcion)
            && isset($data['monto_pago'])
            && (float) $data['monto_pago'] !== (float) $parametro->monto_pago;

        if ($montoBloqueado) {
            throw ValidationException::withMessages([
                'monto_pago' => 'No se puede modificar el monto despues de iniciadas las inscripciones.',
            ]);
        }

        DB::transaction(function () use ($data, $parametro): void {
            $parametro->semestre?->update([
                'nombre' => $data['semestre_nombre'],
            ]);

            $coreData = $this->parameterData($data);
            $coreData['id_semestre'] = $parametro->id_semestre;
            $parametro->update($coreData);
            $this->syncAdmissionRules($parametro->id_semestre, $data['cupos'], $data['ponderaciones']);
        });

        $message = 'Parametro actualizado correctamente.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('parametros.index')->with('status', $message);
    }

    private function syncParametroSerialSequences(): void
    {
        DB::statement("SELECT setval(pg_get_serial_sequence('semestre', 'id_semestre'), COALESCE(MAX(id_semestre), 1), MAX(id_semestre) IS NOT NULL) FROM semestre");
        DB::statement("SELECT setval(pg_get_serial_sequence('parametro_admision', 'id_parametro'), COALESCE(MAX(id_parametro), 1), MAX(id_parametro) IS NOT NULL) FROM parametro_admision");
        DB::statement("SELECT setval(pg_get_serial_sequence('carrera_semestre', 'id_carrera_semestre'), COALESCE(MAX(id_carrera_semestre), 1), MAX(id_carrera_semestre) IS NOT NULL) FROM carrera_semestre");
        DB::statement("SELECT setval(pg_get_serial_sequence('examen', 'id_examen'), COALESCE(MAX(id_examen), 1), MAX(id_examen) IS NOT NULL) FROM examen");
    }

    /**
     * CU07 - Eliminar la configuracion junto con su semestre asociado.
     *
     * No se permite eliminar si el proceso de admision ya cerro.
     */
    public function destroy(Request $request, ParametroAdmision $parametro): RedirectResponse|JsonResponse
    {
        $parametro->loadMissing('semestre');

        if ($this->semestreFinalizado($parametro)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se puede eliminar un parametro de un semestre finalizado.',
                ], 422);
            }

            return back()->withErrors(['parametro' => 'No se puede eliminar un parametro de un semestre finalizado.']);
        }

        if ($parametro->fecha_cierre_inscripcion && now()->gt($parametro->fecha_cierre_inscripcion)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se puede eliminar un parametro cuyo proceso de admision ya cerro.',
                ], 422);
            }

            return back()->withErrors(['parametro' => 'No se puede eliminar un parametro cuyo proceso de admision ya cerro.']);
        }
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
        $data = $request->validate([
            'fecha_inicio_inscripcion' => ['required', 'date'],
            'fecha_cierre_inscripcion' => ['required', 'date', 'after:fecha_inicio_inscripcion'],
            'fecha_cierre_notas' => ['required', 'date', 'after:fecha_cierre_inscripcion'],
            'monto_pago' => ['required', 'numeric', 'gt:0'],
            'max_estudiante_grupo' => ['required', 'integer', 'gt:0'],
            'nota_minima_aprobacion' => ['required', 'numeric', 'between:0,100'],
            'max_grupos_docente' => ['required', 'integer', 'gt:0'],
            'tiempo_expiracion_pago' => ['required', 'integer', 'gt:0'],
            'semestre_nombre' => [
                'required',
                'string',
                'regex:/^[12]-[0-9]{4}$/',
                Rule::unique('semestre', 'nombre')->ignore($parametro?->id_semestre, 'id_semestre'),
            ],
            'cupos' => ['required', 'array'],
            'cupos.*.id_carrera' => ['required', 'integer', 'distinct', Rule::exists('carrera', 'id_carrera')],
            'cupos.*.cantidad_cupos' => ['required', 'integer', 'min:0'],
            'cupos.*.cantidad_estudiantes' => ['required', 'integer', 'min:0'],
            'ponderaciones' => ['required', 'array', 'size:3'],
            'ponderaciones.*.numero_examen' => ['required', 'integer', 'between:1,3', 'distinct'],
            'ponderaciones.*.ponderacion' => ['required', 'numeric', 'between:0,100'],
        ], [
            'fecha_cierre_inscripcion.after' => 'La fecha de cierre de inscripciones debe ser posterior a la fecha de inicio.',
            'fecha_cierre_notas.required' => 'Debe indicar la fecha de cierre de notas.',
            'fecha_cierre_notas.after' => 'La fecha de cierre de notas debe ser posterior a la fecha de cierre de inscripciones.',
            'monto_pago.gt' => 'El monto de pago debe ser mayor a 0.',
            'max_estudiante_grupo.gt' => 'El maximo de estudiantes por grupo debe ser mayor a 0.',
            'nota_minima_aprobacion.between' => 'La nota minima de aprobacion debe estar entre 0 y 100.',
            'max_grupos_docente.gt' => 'El maximo de grupos por docente debe ser mayor a 0.',
            'tiempo_expiracion_pago.gt' => 'El tiempo de expiracion del pago debe ser mayor a 0.',
            'semestre_nombre.regex' => 'El semestre debe tener el formato 1-2026 o 2-2026.',
            'cupos.*.cantidad_cupos.min' => 'Los cupos por carrera no pueden ser negativos.',
            'cupos.*.cantidad_estudiantes.min' => 'La cantidad actual de estudiantes no puede ser negativa.',
            'ponderaciones.size' => 'Debe configurar exactamente 3 ponderaciones de examen.',
            'ponderaciones.*.numero_examen.between' => 'El numero de examen debe estar entre 1 y 3.',
            'ponderaciones.*.ponderacion.between' => 'Cada ponderacion debe estar entre 0 y 100.',
        ]);

        $careerIds = Carrera::query()->pluck('id_carrera')->sort()->values()->all();
        $submittedCareerIds = collect($data['cupos'])
            ->pluck('id_carrera')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($careerIds !== $submittedCareerIds) {
            throw ValidationException::withMessages([
                'cupos' => 'Debe configurar cupos para todas las carreras registradas.',
            ]);
        }

        $totalPonderacion = collect($data['ponderaciones'])->sum(fn ($item) => (float) $item['ponderacion']);

        if (round($totalPonderacion, 2) !== 100.00) {
            throw ValidationException::withMessages([
                'ponderaciones' => 'La suma de las ponderaciones debe ser 100%.',
            ]);
        }

        return $data;
    }

    private function semestreFinalizado(ParametroAdmision $parametro): bool
    {
        return strtolower((string) $parametro->semestre?->estado) === 'finalizado';
    }

    private function parameterData(array $data): array
    {
        return [
            'fecha_inicio_inscripcion' => $data['fecha_inicio_inscripcion'],
            'fecha_cierre_inscripcion' => $data['fecha_cierre_inscripcion'],
            'fecha_cierre_notas' => $data['fecha_cierre_notas'] ?? null,
            'monto_pago' => $data['monto_pago'],
            'max_estudiante_grupo' => $data['max_estudiante_grupo'],
            'nota_minima_aprobacion' => $data['nota_minima_aprobacion'],
            'max_grupos_docente' => $data['max_grupos_docente'],
            'tiempo_expiracion_pago' => $data['tiempo_expiracion_pago'],
        ];
    }

    private function syncAdmissionRules(int $semesterId, array $cupos, array $ponderaciones): void
    {
        foreach ($cupos as $cupo) {
            CarreraSemestre::updateOrCreate(
                [
                    'id_semestre' => $semesterId,
                    'id_carrera' => $cupo['id_carrera'],
                ],
                [
                    'cantidad_cupos' => $cupo['cantidad_cupos'],
                    'cantidad_estudiantes' => $cupo['cantidad_estudiantes'],
                ]
            );
        }

        foreach ($ponderaciones as $ponderacion) {
            Examen::updateOrCreate(
                [
                    'id_semestre' => $semesterId,
                    'numero_examen' => $ponderacion['numero_examen'],
                ],
                [
                    'ponderacion' => $ponderacion['ponderacion'],
                ]
            );
        }
    }
}
