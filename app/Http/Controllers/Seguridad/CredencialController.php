<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;

use App\Models\Credencial;
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
 * CU01 - Gestionar Credenciales.
 *
 * Cubre listar/buscar, modificar, eliminar por baja logica y restaurar
 * credenciales. Ademas incorpora el flujo actualizado de registrar credencial
 * para personas existentes que aun no tienen acceso al sistema.
 */
class CredencialController extends Controller
{
    public function index(Request $request): View
    {
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = $isAsync ? trim((string) $request->query('buscar')) : '';
        $role = $isAsync ? (string) $request->query('rol', '') : '';
        $status = $isAsync ? (string) $request->query('estado', '') : '';
        $validRoles = ['Administrador', 'PersonalAdministrativo', 'Docente', 'Postulante'];

        if (!in_array($role, $validRoles, true)) {
            $role = '';
        }

        if (!in_array($status, ['0', '1'], true)) {
            $status = '';
        }

        $credenciales = Credencial::with('persona')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('registro', 'like', "%{$search}%")
                        ->orWhereHas('persona', function ($personQuery) use ($search): void {
                            $personQuery->where('ci', 'like', "%{$search}%")
                                ->orWhere('nombres', 'like', "%{$search}%")
                                ->orWhere('apellido_paterno', 'like', "%{$search}%")
                                ->orWhere('apellido_materno', 'like', "%{$search}%")
                                ->orWhere('correo', 'like', "%{$search}%");
                        });
                });
            })
            ->when($role !== '', function ($query) use ($role): void {
                $query->where('rol', $role);
            })
            ->when($status !== '', function ($query) use ($status): void {
                $query->where('estado', $status === '1');
            })
            ->orderBy('registro')
            ->paginate(15)
            ->withQueryString();

        if ($isAsync) {
            return view('seguridad.credenciales.partials.table', compact('credenciales'));
        }

        // Flujo nuevo del CU01: elegir personas sin credencial para registrarles acceso.
        $personasSinCredencial = Persona::query()
            ->with(['docente', 'postulante', 'personalAdministrativo'])
            ->whereDoesntHave('credencial')
            ->orderBy('nombres')
            ->orderBy('apellido_paterno')
            ->get();

        return view('seguridad.credenciales.index', compact('credenciales', 'personasSinCredencial', 'search', 'role', 'status', 'validRoles'));
    }

    /**
     * CU01 - Registrar credencial para una persona existente sin acceso.
     *
     * El registro y la contrasena se generan automaticamente: registro de
     * 10 digitos y contrasena igual al CI de la persona (si no se provee otra).
     * El rol se infiere del tipo de persona; el estado inicia activo.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'id_persona' => ['required', 'integer', Rule::exists('persona', 'id_persona'), Rule::unique('credencial', 'id_persona')],
            'correo' => ['required', 'email', 'max:50', Rule::unique('persona', 'correo')->ignore($request->integer('id_persona'), 'id_persona')],
            'nueva_contrasena' => $this->passwordRules(),
        ], $this->passwordMessages());

        $persona = Persona::with(['docente', 'postulante', 'personalAdministrativo'])->findOrFail($data['id_persona']);

        $rol = match (true) {
            (bool) $persona->docente => 'Docente',
            (bool) $persona->postulante => 'Postulante',
            (bool) $persona->personalAdministrativo => 'PersonalAdministrativo',
            default => 'Administrador',
        };

        $registro = Credencial::generateUniqueRegistro();
        $contrasena = Hash::make($data['nueva_contrasena']);

        $credencial = DB::transaction(function () use ($data, $persona, $registro, $rol, $contrasena): Credencial {
            $this->syncCredencialSerialSequence();

            $credencial = Credencial::create([
                'id_persona' => $data['id_persona'],
                'registro' => $registro,
                'rol' => $rol,
                'estado' => true,
                'contrasena' => $contrasena,
                'intentos_fallidos' => 0,
            ]);

            $persona->update([
                'correo' => $data['correo'],
            ]);

            return $credencial;
        });

        $message = "Credencial registrada correctamente. Numero de registro: {$registro}.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'credencial' => $this->payload($credencial->fresh('persona')),
            ], 201);
        }

        return redirect()->route('credenciales.index')->with('status', $message);
    }

    /**
     * CU01 - Modificar rol, estado, correo o reiniciar contrasena.
     */
    public function update(Request $request, Credencial $credencial): RedirectResponse|JsonResponse
    {
        $credencial->load('persona');
        $credencial->persona?->load(['docente', 'postulante', 'personalAdministrativo']);

        $data = $request->validate([
            'rol' => ['required', 'in:Administrador,PersonalAdministrativo,Docente,Postulante'],
            'estado' => ['required', 'boolean'],
            'correo' => ['required', 'email', 'max:50', Rule::unique('persona', 'correo')->ignore($credencial->id_persona, 'id_persona')],
            'nueva_contrasena' => $this->passwordRules(nullable: true),
        ], $this->passwordMessages());

        $isActive = (bool) $data['estado'];
        $this->ensureCompatibleRole($credencial->persona, $data['rol']);

        if ($credencial->is($request->user()) && (!$isActive || $data['rol'] !== 'Administrador')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No puede quitarse su propio acceso de administrador.',
                    'errors' => ['estado' => ['No puede quitarse su propio acceso de administrador.']],
                ], 422);
            }

            return back()->withErrors(['estado' => 'No puede quitarse su propio acceso de administrador.']);
        }

        if ($credencial->rol === 'Administrador' && (bool) $credencial->estado
            && ($data['rol'] !== 'Administrador' || ! $isActive)
            && Credencial::where('rol', 'Administrador')->where('estado', true)->count() <= 1) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se puede modificar la credencial del unico Administrador del sistema.',
                    'errors' => ['rol' => ['No se puede quitar el acceso al unico Administrador del sistema.']],
                ], 422);
            }

            return back()->withErrors(['rol' => 'No se puede quitar el acceso al unico Administrador del sistema.']);
        }

        $credencial->rol = $data['rol'];
        $credencial->estado = $isActive;

        if (!empty($data['nueva_contrasena'])) {
            $credencial->contrasena = Hash::make($data['nueva_contrasena']);
            $credencial->intentos_fallidos = 0;
            $credencial->fecha_bloqueo = null;
        }

        $credencial->save();

        $credencial->persona?->forceFill([
            'correo' => $data['correo'],
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Credencial actualizada.',
                'credencial' => $this->payload($credencial->fresh('persona')),
            ]);
        }

        return redirect()->route('credenciales.index')->with('status', 'Credencial actualizada.');
    }

    /**
     * CU01 - Eliminar credencial como baja logica, manteniendo trazabilidad.
     */
    public function destroy(Request $request, Credencial $credencial): RedirectResponse|JsonResponse
    {
        // Evita que el administrador se quite su propio acceso por accidente.
        if ($credencial->is($request->user())) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No puede eliminar su propia credencial.',
                    'errors' => ['credencial' => ['No puede eliminar su propia credencial.']],
                ], 422);
            }

            return back()->withErrors(['credencial' => 'No puede eliminar su propia credencial.']);
        }

        // Si ya estaba inactiva, la baja logica ya fue aplicada.
        if (! $credencial->estado) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La credencial ya está inactiva.',
                    'errors' => ['credencial' => ['La credencial ya ha sido desactivada.']],
                ], 422);
            }

            return back()->withErrors(['credencial' => 'La credencial ya está inactiva.']);
        }

        // CU01 exige que no se elimine al unico Administrador activo del sistema.
        if ($credencial->rol === 'Administrador'
            && Credencial::where('rol', 'Administrador')->where('estado', true)->count() <= 1) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se puede eliminar la credencial del unico Administrador del sistema.',
                    'errors' => ['credencial' => ['No se puede eliminar al unico Administrador del sistema.']],
                ], 422);
            }

            return back()->withErrors(['credencial' => 'No se puede eliminar al unico Administrador del sistema.']);
        }

        // Baja logica: cambiar estado a inactivo sin borrar el historial.
        $credencial->update(['estado' => false]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Credencial desactivada correctamente.']);
        }

        return redirect()->route('credenciales.index')
            ->with('status', 'Credencial desactivada correctamente.');
    }

    /**
     * CU01 - Restaurar una credencial desactivada por baja logica.
     */
    public function restore(Request $request, Credencial $credencial): RedirectResponse|JsonResponse
    {
        if ((bool) $credencial->estado) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La credencial ya esta activa.',
                    'errors' => ['credencial' => ['La credencial ya esta activa.']],
                ], 422);
            }

            return back()->withErrors(['credencial' => 'La credencial ya esta activa.']);
        }

        $credencial->update([
            'estado' => true,
            'intentos_fallidos' => 0,
            'fecha_bloqueo' => null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Credencial restaurada correctamente.']);
        }

        return redirect()->route('credenciales.index')
            ->with('status', 'Credencial restaurada correctamente.');
    }

    private function payload(Credencial $credencial): array
    {
        return [
            'id' => $credencial->id_credencial,
            'registro' => $credencial->registro,
            'rol' => $credencial->rol,
            'estado' => (bool) $credencial->estado,
            'estado_texto' => $credencial->estado ? 'Activo' : 'Inactivo',
            'ultimo_acceso' => $credencial->fecha_ultimo_acceso?->format('d/m/Y H:i') ?? 'Sin acceso',
            'persona' => [
                'nombre' => $credencial->persona?->nombre_completo ?? 'Sin persona',
                'ci' => $credencial->persona?->ci,
                'correo' => $credencial->persona?->correo,
            ],
        ];
    }

    private function ensureCompatibleRole(?Persona $persona, string $role): void
    {
        if (! $persona) {
            return;
        }

        $expectedRole = match (true) {
            (bool) $persona->docente => 'Docente',
            (bool) $persona->postulante => 'Postulante',
            (bool) $persona->personalAdministrativo => 'PersonalAdministrativo',
            default => null,
        };

        if ($expectedRole !== null && $role !== $expectedRole) {
            throw ValidationException::withMessages([
                'rol' => "El rol seleccionado no corresponde a la persona. Debe ser {$expectedRole}.",
            ]);
        }
    }

    private function syncCredencialSerialSequence(): void
    {
        DB::statement("SELECT setval(pg_get_serial_sequence('credencial', 'id_credencial'), COALESCE(MAX(id_credencial), 1), MAX(id_credencial) IS NOT NULL) FROM credencial");
    }

    private function passwordRules(bool $nullable = false): array
    {
        return [
            $nullable ? 'nullable' : 'required',
            'string',
            'min:8',
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
            'regex:/[^A-Za-z0-9]/',
            'confirmed',
        ];
    }

    private function passwordMessages(): array
    {
        return [
            'nueva_contrasena.required' => 'Debe ingresar una contrasena.',
            'nueva_contrasena.min' => 'La contrasena debe tener al menos 8 caracteres.',
            'nueva_contrasena.regex' => 'La contrasena debe incluir al menos una mayuscula, un numero y un caracter especial.',
            'nueva_contrasena.confirmed' => 'La confirmacion de la contrasena no coincide.',
        ];
    }
}
