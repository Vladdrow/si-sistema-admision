<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\PersonalAdministrativo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU06 - Gestionar Personal Administrativo.
 *
 * Administra el registro, modificacion, eliminacion, busqueda y listado del
 * personal administrativo, guardando datos comunes en persona y cargo propio.
 */
class PersonalAdministrativoController extends Controller
{
    public function index(Request $request): View
    {
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = $isAsync ? trim((string) $request->query('buscar')) : '';
        $position = $isAsync ? (string) $request->query('cargo', '') : '';
        $positions = PersonalAdministrativo::query()->select('cargo')->distinct()->orderBy('cargo')->pluck('cargo')->all();

        if (! in_array($position, $positions, true)) {
            $position = '';
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
                        });
                });
            })
            ->when($position !== '', fn ($query) => $query->where('cargo', $position))
            ->join('persona', 'persona.id_persona', '=', 'personal_administrativo.id_personal')
            ->orderBy('persona.apellido_paterno')
            ->orderBy('persona.nombres')
            ->select('personal_administrativo.*')
            ->paginate(15)
            ->withQueryString();

        if ($isAsync) {
            return view('personal.partials.table', compact('personal'));
        }

        return view('personal.index', compact('personal', 'search', 'position', 'positions'));
    }

    /**
     * CU06 - Registrar personal administrativo.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);

        $staff = DB::transaction(function () use ($data): PersonalAdministrativo {
            $persona = Persona::create($this->personData($data));

            return PersonalAdministrativo::create([
                'id_personal' => $persona->id_persona,
                'cargo' => $data['cargo'],
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Personal administrativo registrado correctamente.', 'personal' => $staff->load('persona')], 201);
        }

        return redirect()->route('personal.index')->with('status', 'Personal administrativo registrado correctamente.');
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
     * CU06 - Eliminar personal administrativo junto con su persona base.
     */
    public function destroy(Request $request, PersonalAdministrativo $personal): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($personal): void {
            $personal->load('persona');
            $personal->persona?->delete();
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Personal administrativo eliminado correctamente.']);
        }

        return redirect()->route('personal.index')->with('status', 'Personal administrativo eliminado correctamente.');
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
