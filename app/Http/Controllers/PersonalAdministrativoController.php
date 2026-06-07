<?php

namespace App\Http\Controllers;

use App\Models\Credencial;
use App\Models\Persona;
use App\Models\PersonalAdministrativo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU06 - Gestionar Personal Administrativo.
 *
 * Permite registrar, modificar, eliminar (baja logica), buscar y listar
 * personal administrativo. Al registrar genera automaticamente credenciales
 * con registro de 10 digitos y contrasena = CI.
 * La baja logica desactiva la credencial (estado=false), sin borrar datos.
 */
class PersonalAdministrativoController extends Controller
{
    public function index(Request $request): View
    {
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = $isAsync ? trim((string) $request->query('buscar')) : '';
        $position = $isAsync ? (string) $request->query('cargo', '') : '';
        $status = $isAsync ? (string) $request->query('estado', '1') : '1';
        $positions = PersonalAdministrativo::query()->select('cargo')->distinct()->orderBy('cargo')->pluck('cargo')->all();

        if (! in_array($position, $positions, true)) {
            $position = '';
        }

        if (! in_array($status, ['0', '1', ''], true)) {
            $status = '1';
        }

        $personal = PersonalAdministrativo::with('persona.credencial')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('cargo', 'like', "%{$search}%")
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
            ->when($position !== '', fn ($query) => $query->where('cargo', $position))
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
            ->join('persona', 'persona.id_persona', '=', 'personal_administrativo.id_personal')
            ->orderBy('persona.apellido_paterno')
            ->orderBy('persona.nombres')
            ->select('personal_administrativo.*')
            ->paginate(15)
            ->withQueryString();

        if ($isAsync) {
            return view('personal.partials.table', compact('personal'));
        }

        return view('personal.index', compact('personal', 'search', 'position', 'positions', 'status'));
    }

    /**
     * CU06 - Registrar personal administrativo.
     *
     * Genera automaticamente una credencial con registro de 10 digitos
     * y contrasena igual al CI.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);

        $result = DB::transaction(function () use ($data): array {
            $persona = Persona::create($this->personData($data));

            $staff = PersonalAdministrativo::create([
                'id_personal' => $persona->id_persona,
                'cargo' => $data['cargo'],
            ]);

            $registro = Credencial::generateUniqueRegistro();

            Credencial::create([
                'id_persona' => $persona->id_persona,
                'registro' => $registro,
                'contrasena' => Hash::make($data['ci']),
                'rol' => 'PersonalAdministrativo',
                'estado' => true,
                'intentos_fallidos' => 0,
            ]);

            return [
                'staff' => $staff->load('persona'),
                'registro' => $registro,
            ];
        });

        $message = "Personal administrativo registrado correctamente. Numero de registro: {$result['registro']}. Contrasena: su CI.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'personal' => $result['staff']->load('persona'),
                'registro' => $result['registro'],
            ], 201);
        }

        return redirect()->route('personal.index')->with('status', $message);
    }

    /**
     * CU06 - Modificar datos personales y cargo administrativo.
     */
    public function update(Request $request, PersonalAdministrativo $personal): RedirectResponse|JsonResponse
    {
        $personal->load('persona');
        $data = $this->validatedData($request, $personal);

        DB::transaction(function () use ($data, $personal): void {
            $personal->persona?->update($this->personData($data));
            $personal->update(['cargo' => $data['cargo']]);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Personal administrativo actualizado correctamente.']);
        }

        return redirect()->route('personal.index')->with('status', 'Personal administrativo actualizado correctamente.');
    }

    /**
     * CU06 - Baja logica: desactiva la credencial sin borrar datos.
     */
    public function destroy(Request $request, PersonalAdministrativo $personal): RedirectResponse|JsonResponse
    {
        $personal->load('persona.credencial');

        if (! $personal->persona?->credencial) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'El personal no tiene credencial asociada.',
                    'errors' => ['personal' => ['El personal no tiene una credencial para desactivar.']],
                ], 422);
            }

            return back()->withErrors(['personal' => 'El personal no tiene una credencial para desactivar.']);
        }

        if (! $personal->persona->credencial->estado) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La credencial del personal ya esta inactiva.',
                    'errors' => ['personal' => ['La credencial del personal ya ha sido desactivada.']],
                ], 422);
            }

            return back()->withErrors(['personal' => 'La credencial del personal ya esta inactiva.']);
        }

        if ($personal->persona->credencial->rol === 'Administrador'
            && Credencial::where('rol', 'Administrador')->where('estado', true)->count() <= 1) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se puede desactivar al unico Administrador del sistema.',
                    'errors' => ['personal' => ['No se puede eliminar al unico Administrador del sistema.']],
                ], 422);
            }

            return back()->withErrors(['personal' => 'No se puede eliminar al unico Administrador del sistema.']);
        }

        $personal->persona->credencial->update(['estado' => false]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Personal administrativo desactivado correctamente.']);
        }

        return redirect()->route('personal.index')->with('status', 'Personal administrativo desactivado correctamente.');
    }

    /**
     * CU06 - Restaurar la credencial de un personal inactivo.
     */
    public function restore(Request $request, PersonalAdministrativo $personal): RedirectResponse|JsonResponse
    {
        $personal->load('persona.credencial');

        if (! $personal->persona?->credencial) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'El personal no tiene credencial asociada.',
                    'errors' => ['personal' => ['El personal no tiene una credencial para restaurar.']],
                ], 422);
            }

            return back()->withErrors(['personal' => 'El personal no tiene una credencial para restaurar.']);
        }

        if ((bool) $personal->persona->credencial->estado) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La credencial del personal ya esta activa.',
                    'errors' => ['personal' => ['La credencial del personal ya esta activa.']],
                ], 422);
            }

            return back()->withErrors(['personal' => 'La credencial del personal ya esta activa.']);
        }

        $personal->persona->credencial->update([
            'estado' => true,
            'intentos_fallidos' => 0,
            'fecha_bloqueo' => null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Personal administrativo restaurado correctamente.']);
        }

        return redirect()->route('personal.index')->with('status', 'Personal administrativo restaurado correctamente.');
    }

    private function validatedData(Request $request, ?PersonalAdministrativo $personal = null): array
    {
        $personId = $personal?->id_personal;

        return $request->validate([
            'ci' => ['required', 'string', 'max:20', Rule::unique('persona', 'ci')->ignore($personId, 'id_persona')],
            'nombres' => ['required', 'string', 'max:50'],
            'apellido_paterno' => ['required', 'string', 'max:50'],
            'apellido_materno' => ['nullable', 'string', 'max:50'],
            'fecha_nacimiento' => ['required', 'date'],
            'sexo' => ['required', 'in:M,F'],
            'direccion' => ['nullable', 'string', 'max:70'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:50', Rule::unique('persona', 'correo')->ignore($personId, 'id_persona')],
            'cargo' => ['required', 'string', 'max:25'],
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
}
