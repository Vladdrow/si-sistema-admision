<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Persona;
use App\Models\Postulante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU03 - Gestionar Postulante.
 *
 * Implementa busqueda/listado, modificacion y eliminacion (baja logica)
 * de postulantes ya registrados. La ruta admin no expone store porque el
 * registro inicial pertenece al flujo del postulante.
 * La baja logica desactiva la credencial (estado=false), sin borrar datos.
 */
class PostulanteController extends Controller
{
    public function index(Request $request): View
    {
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = $isAsync ? trim((string) $request->query('buscar')) : '';
        $admisionStatus = $isAsync ? (string) $request->query('estado', '') : '';
        $career = $isAsync ? (string) $request->query('carrera', '') : '';
        $acceso = $isAsync ? (string) $request->query('acceso', '1') : '1';
        $statuses = ['Pendiente', 'Admitido', 'No Admitido'];
        $careers = Carrera::orderBy('nombre')->get();

        if (! in_array($admisionStatus, $statuses, true)) {
            $admisionStatus = '';
        }

        if ($career !== '' && ! $careers->contains('id_carrera', (int) $career)) {
            $career = '';
        }

        if (! in_array($acceso, ['0', '1', ''], true)) {
            $acceso = '1';
        }

        $postulantes = Postulante::with(['persona.credencial', 'carreraPrimera', 'carreraSegunda', 'carreraAdmitido'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('codigo_libreta', 'like', "%{$search}%")
                        ->orWhere('codigo_titulo', 'like', "%{$search}%")
                        ->orWhere('colegio_procedencia', 'like', "%{$search}%")
                        ->orWhere('ciudad', 'like', "%{$search}%")
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
            ->when($admisionStatus !== '', fn ($query) => $query->where('estado_admision', $admisionStatus))
            ->when($career !== '', function ($query) use ($career): void {
                $query->where(function ($query) use ($career): void {
                    $query->where('id_carrera_primera_opc', $career)
                        ->orWhere('id_carrera_segunda_opc', $career)
                        ->orWhere('id_carrera_admitido', $career);
                });
            })
            ->when($acceso === '1', function ($query): void {
                $query->whereHas('persona.credencial', function ($q): void {
                    $q->where('estado', true);
                });
            })
            ->when($acceso === '0', function ($query): void {
                $query->where(function ($q): void {
                    $q->whereHas('persona.credencial', function ($sq): void {
                        $sq->where('estado', false);
                    })->orWhereDoesntHave('persona.credencial');
                });
            })
            ->join('persona', 'persona.id_persona', '=', 'postulante.id_postulante')
            ->orderBy('persona.apellido_paterno')
            ->orderBy('persona.nombres')
            ->select('postulante.*')
            ->paginate(15)
            ->withQueryString();

        if ($isAsync) {
            return view('postulantes.partials.table', compact('postulantes'));
        }

        return view('postulantes.index', compact('postulantes', 'search', 'admisionStatus', 'career', 'acceso', 'statuses', 'careers'));
    }

    /**
     * CU03 - Modificar datos personales y academicos del postulante.
     */
    public function update(Request $request, Postulante $postulante): RedirectResponse|JsonResponse
    {
        $postulante->load('persona');
        $data = $this->validatedData($request, $postulante);

        DB::transaction(function () use ($data, $postulante): void {
            $postulante->persona?->update($this->personData($data));
            $postulante->update($this->applicantData($data, $postulante->id_postulante));
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Postulante actualizado correctamente.']);
        }

        return redirect()->route('postulantes.index')->with('status', 'Postulante actualizado correctamente.');
    }

    /**
     * CU03 - Baja logica: desactiva la credencial del postulante sin borrar datos.
     */
    public function destroy(Request $request, Postulante $postulante): RedirectResponse|JsonResponse
    {
        $postulante->load('persona.credencial');

        if (! $postulante->persona?->credencial) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'El postulante no tiene credencial asociada.',
                    'errors' => ['postulante' => ['El postulante no tiene una credencial para desactivar.']],
                ], 422);
            }

            return back()->withErrors(['postulante' => 'El postulante no tiene una credencial para desactivar.']);
        }

        if (! $postulante->persona->credencial->estado) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La credencial del postulante ya esta inactiva.',
                    'errors' => ['postulante' => ['La credencial del postulante ya ha sido desactivada.']],
                ], 422);
            }

            return back()->withErrors(['postulante' => 'La credencial del postulante ya esta inactiva.']);
        }

        $postulante->persona->credencial->update(['estado' => false]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Postulante desactivado correctamente.']);
        }

        return redirect()->route('postulantes.index')->with('status', 'Postulante desactivado correctamente.');
    }

    /**
     * CU03 - Restaurar la credencial de un postulante inactivo.
     */
    public function restore(Request $request, Postulante $postulante): RedirectResponse|JsonResponse
    {
        $postulante->load('persona.credencial');

        if (! $postulante->persona?->credencial) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'El postulante no tiene credencial asociada.',
                    'errors' => ['postulante' => ['El postulante no tiene una credencial para restaurar.']],
                ], 422);
            }

            return back()->withErrors(['postulante' => 'El postulante no tiene una credencial para restaurar.']);
        }

        if ((bool) $postulante->persona->credencial->estado) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La credencial del postulante ya esta activa.',
                    'errors' => ['postulante' => ['La credencial del postulante ya esta activa.']],
                ], 422);
            }

            return back()->withErrors(['postulante' => 'La credencial del postulante ya esta activa.']);
        }

        $postulante->persona->credencial->update([
            'estado' => true,
            'intentos_fallidos' => 0,
            'fecha_bloqueo' => null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Postulante restaurado correctamente.']);
        }

        return redirect()->route('postulantes.index')->with('status', 'Postulante restaurado correctamente.');
    }

    private function validatedData(Request $request, ?Postulante $postulante = null): array
    {
        $personId = $postulante?->id_postulante;

        return $request->validate([
            'ci' => ['required', 'string', 'max:20', Rule::unique('persona', 'ci')->ignore($personId, 'id_persona')],
            'nombres' => ['required', 'string', 'max:50'],
            'apellido_paterno' => ['required', 'string', 'max:50'],
            'apellido_materno' => ['nullable', 'string', 'max:50'],
            'fecha_nacimiento' => ['required', 'date'],
            'sexo' => ['required', 'in:M,F'],
            'direccion' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:50', Rule::unique('persona', 'correo')->ignore($personId, 'id_persona')],
            'colegio_procedencia' => ['nullable', 'string', 'max:100'],
            'ciudad' => ['nullable', 'string', 'max:50'],
            'estado_admision' => ['required', 'in:Pendiente,Admitido,No Admitido'],
            'codigo_libreta' => ['required', 'string', 'max:20', Rule::unique('postulante', 'codigo_libreta')->ignore($personId, 'id_postulante')],
            'codigo_titulo' => ['required', 'string', 'max:20', Rule::unique('postulante', 'codigo_titulo')->ignore($personId, 'id_postulante')],
            'id_carrera_primera_opc' => ['nullable', 'integer', 'exists:carrera,id_carrera'],
            'id_carrera_segunda_opc' => ['nullable', 'integer', 'exists:carrera,id_carrera'],
            'id_carrera_admitido' => ['nullable', 'integer', 'exists:carrera,id_carrera'],
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

    private function applicantData(array $data, int $personId): array
    {
        return [
            'id_postulante' => $personId,
            'colegio_procedencia' => $data['colegio_procedencia'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
            'estado_admision' => $data['estado_admision'],
            'codigo_libreta' => $data['codigo_libreta'],
            'codigo_titulo' => $data['codigo_titulo'],
            'id_carrera_primera_opc' => $data['id_carrera_primera_opc'] ?? null,
            'id_carrera_segunda_opc' => $data['id_carrera_segunda_opc'] ?? null,
            'id_carrera_admitido' => $data['id_carrera_admitido'] ?? null,
        ];
    }
}
