<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU05 - Gestionar Docente.
 *
 * Permite registrar, modificar, eliminar, buscar y listar docentes con sus
 * datos personales y profesionales, validando duplicados de CI, correo y RDA.
 */
class DocenteController extends Controller
{
    public function index(Request $request): View
    {
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = $isAsync ? trim((string) $request->query('buscar')) : '';
        $degree = $isAsync ? (string) $request->query('grado', '') : '';

        if (! in_array($degree, ['maestria', 'diplomado'], true)) {
            $degree = '';
        }

        $docentes = Docente::with('persona')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('codigo_rda', 'like', "%{$search}%")
                        ->orWhere('titulo_profesional', 'like', "%{$search}%")
                        ->orWhereHas('persona', function ($personQuery) use ($search): void {
                            $personQuery->where('ci', 'like', "%{$search}%")
                                ->orWhere('nombres', 'like', "%{$search}%")
                                ->orWhere('apellido_paterno', 'like', "%{$search}%")
                                ->orWhere('apellido_materno', 'like', "%{$search}%")
                                ->orWhere('correo', 'like', "%{$search}%");
                        });
                });
            })
            ->when($degree === 'maestria', function ($query): void {
                $query->where('tiene_maestria', true);
            })
            ->when($degree === 'diplomado', function ($query): void {
                $query->where('tiene_diplomado', true);
            })
            ->join('persona', 'persona.id_persona', '=', 'docente.id_docente')
            ->orderBy('persona.apellido_paterno')
            ->orderBy('persona.nombres')
            ->select('docente.*')
            ->paginate(15)
            ->withQueryString();

        if ($isAsync) {
            return view('docentes.partials.table', compact('docentes'));
        }

        return view('docentes.index', compact('docentes', 'search', 'degree'));
    }

    /**
     * CU05 - Registrar docente con datos personales y profesionales.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);

        $docente = DB::transaction(function () use ($data): Docente {
            $persona = Persona::create($this->personData($data));

            return Docente::create($this->teacherData($data, $persona->id_persona));
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Docente registrado correctamente.',
                'docente' => $this->payload($docente->load('persona')),
            ], 201);
        }

        return redirect()->route('docentes.index')->with('status', 'Docente registrado correctamente.');
    }

    /**
     * CU05 - Modificar docente manteniendo sincronizados persona y docente.
     */
    public function update(Request $request, Docente $docente): RedirectResponse|JsonResponse
    {
        $docente->load('persona');
        $data = $this->validatedData($request, $docente);

        DB::transaction(function () use ($data, $docente): void {
            $docente->persona?->update($this->personData($data));
            $docente->update($this->teacherData($data, $docente->id_docente));
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Docente actualizado correctamente.',
                'docente' => $this->payload($docente->fresh('persona')),
            ]);
        }

        return redirect()->route('docentes.index')->with('status', 'Docente actualizado correctamente.');
    }

    /**
     * CU05 - Eliminar docente junto con su registro base de persona.
     */
    public function destroy(Request $request, Docente $docente): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($docente): void {
            $docente->load('persona');
            $docente->persona?->delete();
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Docente eliminado correctamente.']);
        }

        return redirect()->route('docentes.index')->with('status', 'Docente eliminado correctamente.');
    }

    private function validatedData(Request $request, ?Docente $docente = null): array
    {
        $personaId = $docente?->id_docente;

        return $request->validate([
            'ci' => ['required', 'string', 'max:20', Rule::unique('persona', 'ci')->ignore($personaId, 'id_persona')],
            'nombres' => ['required', 'string', 'max:50'],
            'apellido_paterno' => ['required', 'string', 'max:50'],
            'apellido_materno' => ['nullable', 'string', 'max:50'],
            'fecha_nacimiento' => ['required', 'date'],
            'sexo' => ['required', 'in:M,F'],
            'direccion' => ['nullable', 'string', 'max:70'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:50', Rule::unique('persona', 'correo')->ignore($personaId, 'id_persona')],
            'titulo_profesional' => ['required', 'string', 'max:50'],
            'codigo_rda' => ['required', 'string', 'max:15', Rule::unique('docente', 'codigo_rda')->ignore($personaId, 'id_docente')],
            'tiene_maestria' => ['nullable', 'boolean'],
            'tiene_diplomado' => ['nullable', 'boolean'],
        ]);
    }

    private function personData(array $data): array
    {
        return [
            'ci' => $data['ci'],
            'nombres' => $data['nombres'],
            'apellido_paterno' => $data['apellido_paterno'],
            'apellido_materno' => $data['apellido_materno'] ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'sexo' => $data['sexo'],
            'direccion' => $data['direccion'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'correo' => $data['correo'],
        ];
    }

    private function teacherData(array $data, int $personId): array
    {
        return [
            'id_docente' => $personId,
            'titulo_profesional' => $data['titulo_profesional'],
            'codigo_rda' => $data['codigo_rda'],
            'tiene_maestria' => (bool) ($data['tiene_maestria'] ?? false),
            'tiene_diplomado' => (bool) ($data['tiene_diplomado'] ?? false),
        ];
    }

    private function payload(Docente $docente): array
    {
        return [
            'id' => $docente->id_docente,
            'nombre' => $docente->persona?->nombre_completo,
            'ci' => $docente->persona?->ci,
            'correo' => $docente->persona?->correo,
            'titulo_profesional' => $docente->titulo_profesional,
            'codigo_rda' => $docente->codigo_rda,
            'tiene_maestria' => (bool) $docente->tiene_maestria,
            'tiene_diplomado' => (bool) $docente->tiene_diplomado,
        ];
    }
}
