<?php

namespace App\Http\Controllers;

use App\Models\Credencial;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * CU05 - Gestionar Docente.
 *
 * Permite registrar, modificar, eliminar (baja logica), buscar y listar
 * docentes con sus datos personales y profesionales. Al registrar genera
 * automaticamente credenciales con registro de 10 digitos y contrasena = CI.
 * La baja logica desactiva la credencial (estado=false), sin borrar datos.
 */
class DocenteController extends Controller
{
    private const TITULOS_PROFESIONALES = [
        'Licenciatura en Computacion',
        'Licenciatura en Matematica',
        'Licenciatura en Idiomas',
        'Licenciatura en Inglés',
        'Licenciatura en Fisica',
        'Ingenieria Informatica',
        'Ingenieria en Sistemas',
        'Ingenieria en Redes y Telecomunicaciones',
    ];

    public function index(Request $request): View
    {
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = $isAsync ? trim((string) $request->query('buscar')) : '';
        $degree = $isAsync ? (string) $request->query('grado', '') : '';
        $status = $isAsync ? (string) $request->query('estado', '1') : '1';

        if (! in_array($degree, ['maestria', 'diplomado'], true)) {
            $degree = '';
        }

        if (! in_array($status, ['0', '1', ''], true)) {
            $status = '1';
        }

        $docentes = Docente::with(['persona.credencial', 'materiasHabilitadas', 'certificaciones'])
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
                        })
                        ->orWhereHas('persona.credencial', function ($credQuery) use ($search): void {
                            $credQuery->where('registro', 'like', "%{$search}%");
                        });
                });
            })
            ->when($degree === 'maestria', function ($query): void {
                $query->where('tiene_maestria', true);
            })
            ->when($degree === 'diplomado', function ($query): void {
                $query->where('tiene_diplomado', true);
            })
            ->when($status === '1', function ($query): void {
                $query->whereHas('persona.credencial', function ($q): void {
                    $q->where('estado', true);
                });
            })
            ->when($status === '0', function ($query): void {
                $query->where(function ($q): void {
                    $q->whereHas('persona.credencial', function ($sq): void {
                        $sq->where('estado', false);
                    })->orWhereDoesntHave('persona.credencial');
                });
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

        $materias = Materia::orderBy('nombre')->get();
        $titulos = self::TITULOS_PROFESIONALES;

        return view('docentes.index', compact('docentes', 'search', 'degree', 'status', 'materias', 'titulos'));
    }

    /**
     * CU05 - Registrar docente con datos personales y profesionales.
     *
     * Genera automaticamente una credencial con registro de 10 digitos
     * y contrasena igual al CI del docente.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);

        $result = DB::transaction(function () use ($data): array {
            $this->syncDocenteSerialSequences();

            $persona = Persona::create($this->personData($data));

            $docente = Docente::create($this->teacherData($data, $persona->id_persona));
            $this->syncTeacherProfile($docente, $data);

            $registro = Credencial::generateUniqueRegistro();

            Credencial::create([
                'id_persona' => $persona->id_persona,
                'registro' => $registro,
                'contrasena' => Hash::make($data['ci']),
                'rol' => 'Docente',
                'estado' => true,
                'intentos_fallidos' => 0,
            ]);

            return [
                'docente' => $docente->load(['persona.credencial', 'materiasHabilitadas', 'certificaciones']),
                'registro' => $registro,
            ];
        });

        $message = "Docente registrado correctamente. Numero de registro: {$result['registro']}. Contrasena: su CI.";
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'docente' => $this->payload($result['docente']),
                'registro' => $result['registro'],
            ], 201);
        }

        return redirect()->route('docentes.index')->with('status', $message);
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
            $this->syncTeacherProfile($docente, $data);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Docente actualizado correctamente.',
                'docente' => $this->payload($docente->fresh(['persona.credencial', 'materiasHabilitadas', 'certificaciones'])),
            ]);
        }

        return redirect()->route('docentes.index')->with('status', 'Docente actualizado correctamente.');
    }

    private function syncDocenteSerialSequences(): void
    {
        DB::statement("SELECT setval(pg_get_serial_sequence('persona', 'id_persona'), COALESCE(MAX(id_persona), 1), MAX(id_persona) IS NOT NULL) FROM persona");
        DB::statement("SELECT setval(pg_get_serial_sequence('credencial', 'id_credencial'), COALESCE(MAX(id_credencial), 1), MAX(id_credencial) IS NOT NULL) FROM credencial");
        DB::statement("SELECT setval(pg_get_serial_sequence('certificacion_docente', 'id_certificacion'), COALESCE(MAX(id_certificacion), 1), MAX(id_certificacion) IS NOT NULL) FROM certificacion_docente");
    }

    /**
     * CU05 - Baja logica: desactiva la credencial del docente sin borrar datos.
     */
    public function destroy(Request $request, Docente $docente): RedirectResponse|JsonResponse
    {
        $docente->load('persona.credencial');

        if (! $docente->persona?->credencial) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'El docente no tiene credencial asociada.',
                    'errors' => ['docente' => ['El docente no tiene una credencial para desactivar.']],
                ], 422);
            }

            return back()->withErrors(['docente' => 'El docente no tiene una credencial para desactivar.']);
        }

        if (! $docente->persona->credencial->estado) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La credencial del docente ya esta inactiva.',
                    'errors' => ['docente' => ['La credencial del docente ya ha sido desactivada.']],
                ], 422);
            }

            return back()->withErrors(['docente' => 'La credencial del docente ya esta inactiva.']);
        }

        $docente->persona->credencial->update(['estado' => false]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Docente desactivado correctamente.']);
        }

        return redirect()->route('docentes.index')->with('status', 'Docente desactivado correctamente.');
    }

    /**
     * CU05 - Restaurar la credencial de un docente inactivo.
     */
    public function restore(Request $request, Docente $docente): RedirectResponse|JsonResponse
    {
        $docente->load('persona.credencial');

        if (! $docente->persona?->credencial) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'El docente no tiene credencial asociada.',
                    'errors' => ['docente' => ['El docente no tiene una credencial para restaurar.']],
                ], 422);
            }

            return back()->withErrors(['docente' => 'El docente no tiene una credencial para restaurar.']);
        }

        if ((bool) $docente->persona->credencial->estado) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La credencial del docente ya esta activa.',
                    'errors' => ['docente' => ['La credencial del docente ya esta activa.']],
                ], 422);
            }

            return back()->withErrors(['docente' => 'La credencial del docente ya esta activa.']);
        }

        $docente->persona->credencial->update([
            'estado' => true,
            'intentos_fallidos' => 0,
            'fecha_bloqueo' => null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Docente restaurado correctamente.']);
        }

        return redirect()->route('docentes.index')->with('status', 'Docente restaurado correctamente.');
    }

    private function validatedData(Request $request, ?Docente $docente = null): array
    {
        $personaId = $docente?->id_docente;

        $data = $request->validate([
            'ci' => ['required', 'string', 'max:20', Rule::unique('persona', 'ci')->ignore($personaId, 'id_persona')],
            'nombres' => ['required', 'string', 'max:50'],
            'apellido_paterno' => ['required', 'string', 'max:50'],
            'apellido_materno' => ['nullable', 'string', 'max:50'],
            'fecha_nacimiento' => ['required', 'date'],
            'sexo' => ['required', 'in:M,F'],
            'direccion' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:50', Rule::unique('persona', 'correo')->ignore($personaId, 'id_persona')],
            'titulo_profesional' => ['required', 'string', 'max:80', Rule::in(self::TITULOS_PROFESIONALES)],
            'codigo_rda' => ['required', 'string', 'max:15', Rule::unique('docente', 'codigo_rda')->ignore($personaId, 'id_docente')],
            'tiene_maestria' => ['nullable', 'boolean'],
            'tiene_diplomado' => ['nullable', 'boolean'],
            'materias_habilitadas' => ['required', 'array', 'min:1'],
            'materias_habilitadas.*' => ['integer', 'exists:materia,id_materia'],
            'certificacion_institucion' => ['nullable', 'string', 'max:80'],
            'certificacion_nivel' => ['nullable', 'string', 'max:20'],
        ]);

        $this->validateTeachingProfile($data);

        return $data;
    }

    private function validateTeachingProfile(array $data): void
    {
        $materias = Materia::whereIn('id_materia', $data['materias_habilitadas'])->pluck('nombre');
        $habilitaIngles = $materias->contains(fn (string $nombre) => in_array($nombre, ['Inglés', 'Ingles'], true));
        $tituloIdioma = in_array($data['titulo_profesional'], ['Licenciatura en Inglés', 'Licenciatura en Idiomas'], true);
        $tieneCertificacion = ! empty($data['certificacion_institucion']) && ! empty($data['certificacion_nivel']);

        if ($habilitaIngles && ! $tituloIdioma) {
            throw ValidationException::withMessages([
                'materias_habilitadas' => 'Solo un docente con titulo profesional en Ingles o Idiomas puede ser habilitado para la materia Ingles.',
            ]);
        }

        if ($tituloIdioma && ! $tieneCertificacion) {
            throw ValidationException::withMessages([
                'certificacion_institucion' => 'Los docentes del area de Ingles o Idiomas deben registrar institucion y nivel de certificacion.',
            ]);
        }
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

    private function syncTeacherProfile(Docente $docente, array $data): void
    {
        $docente->materiasHabilitadas()->sync($data['materias_habilitadas']);

        $docente->certificaciones()->delete();

        $tituloIdioma = in_array($data['titulo_profesional'], ['Licenciatura en Inglés', 'Licenciatura en Idiomas'], true);

        if ($tituloIdioma && (! empty($data['certificacion_institucion']) || ! empty($data['certificacion_nivel']))) {
            $docente->certificaciones()->create([
                'institucion' => $data['certificacion_institucion'] ?? null,
                'nivel' => $data['certificacion_nivel'] ?? null,
            ]);
        }
    }

    private function payload(Docente $docente): array
    {
        return [
            'id' => $docente->id_docente,
            'nombre' => $docente->persona?->nombre_completo,
            'ci' => $docente->persona?->ci,
            'correo' => $docente->persona?->correo,
            'registro' => $docente->persona?->credencial?->registro,
            'titulo_profesional' => $docente->titulo_profesional,
            'codigo_rda' => $docente->codigo_rda,
            'tiene_maestria' => (bool) $docente->tiene_maestria,
            'tiene_diplomado' => (bool) $docente->tiene_diplomado,
            'materias_habilitadas' => $docente->materiasHabilitadas->pluck('id_materia')->values(),
            'certificaciones' => $docente->certificaciones->map(fn ($certificacion) => [
                'institucion' => $certificacion->institucion,
                'nivel' => $certificacion->nivel,
            ])->values(),
            'activo' => (bool) ($docente->persona?->credencial?->estado ?? false),
        ];
    }
}
