<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;

use App\Models\Materia;
use App\Models\PlantillaHorario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * CU10 - Gestionar Plantilla de Horario.
 *
 * Crea, modifica, elimina y consulta plantillas reutilizables para grupos.
 * Cada plantilla guarda turno y bloques por dia/hora/materia/modalidad, sin docentes ni aulas.
 */
class PlantillaHorarioController extends Controller
{
    public function index(Request $request): View
    {
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = $isAsync ? trim((string) $request->query('buscar')) : '';
        $shift = $isAsync ? (string) $request->query('turno', '') : '';
        $shifts = ['Mañana', 'Tarde', 'Noche'];

        if (! in_array($shift, $shifts, true)) {
            $shift = '';
        }

        $materias = Materia::orderBy('nombre')->get();

        $plantillas = PlantillaHorario::with('detalles.materia')
            ->withCount('detalles')
            ->when($search !== '', fn ($query) => $query->where('nombre', 'like', "%{$search}%"))
            ->when($shift !== '', fn ($query) => $query->where('turno', $shift))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        if ($isAsync) {
            return view('academico.plantillas.partials.table', compact('plantillas'));
        }

        return view('academico.plantillas.index', compact('plantillas', 'search', 'shift', 'shifts', 'materias'));
    }

    /**
     * CU10 - Registrar una plantilla con sus bloques de horario.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data): void {
            $this->syncPlantillaSerialSequences();

            $plantilla = PlantillaHorario::create([
                'nombre' => $data['nombre'],
                'turno' => $data['turno'],
            ]);

            $this->syncDetails($plantilla, $data['detalles'] ?? []);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Plantilla registrada correctamente.'], 201);
        }

        return redirect()->route('plantillas.index')->with('status', 'Plantilla registrada correctamente.');
    }

    /**
     * CU10 - Modificar nombre, turno y bloques de una plantilla existente.
     */
    public function update(Request $request, PlantillaHorario $plantilla): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request, $plantilla);

        DB::transaction(function () use ($data, $plantilla): void {
            $plantilla->update([
                'nombre' => $data['nombre'],
                'turno' => $data['turno'],
            ]);

            $this->syncPlantillaSerialSequences();
            $this->syncDetails($plantilla, $data['detalles'] ?? []);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Plantilla actualizada correctamente.']);
        }

        return redirect()->route('plantillas.index')->with('status', 'Plantilla actualizada correctamente.');
    }

    /**
     * CU10 - Eliminar la plantilla y sus detalles asociados.
     *
     * No se permite eliminar si la plantilla esta siendo utilizada por
     * algun grupo a traves de grupo_horario.
     */
    public function destroy(Request $request, PlantillaHorario $plantilla): RedirectResponse|JsonResponse
    {
        $enUso = DB::table('grupo_horario')
            ->join('detalle_plantilla_horario', 'detalle_plantilla_horario.id_detalle', '=', 'grupo_horario.id_detalle')
            ->where('detalle_plantilla_horario.id_plantilla', $plantilla->id_plantilla)
            ->exists();

        if ($enUso) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No se puede eliminar la plantilla porque esta siendo utilizada por al menos un grupo.',
                    'errors' => ['plantilla' => ['No se puede eliminar una plantilla que esta siendo utilizada por un grupo.']],
                ], 422);
            }

            return back()->withErrors(['plantilla' => 'No se puede eliminar una plantilla que esta siendo utilizada por un grupo.']);
        }

        $plantilla->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Plantilla eliminada correctamente.']);
        }

        return redirect()->route('plantillas.index')->with('status', 'Plantilla eliminada correctamente.');
    }

    private function validatedData(Request $request, ?PlantillaHorario $plantilla = null): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:30'],
            'turno' => ['required', 'in:Mañana,Tarde,Noche'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.dia' => ['required_with:detalles', 'integer', 'between:1,7'],
            'detalles.*.hora_inicio' => ['required_with:detalles', 'date_format:H:i'],
            'detalles.*.hora_fin' => ['required_with:detalles', 'date_format:H:i'],
            'detalles.*.id_materia' => ['required_with:detalles', 'integer', 'exists:materia,id_materia'],
            'detalles.*.modalidad' => ['required_with:detalles', 'in:Presencial,Virtual'],
        ]);

        $existingName = PlantillaHorario::query()
            ->whereRaw('LOWER(nombre) = ?', [strtolower($data['nombre'])])
            ->when($plantilla, fn ($query) => $query->whereKeyNot($plantilla->getKey()))
            ->exists();

        if ($existingName) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe una plantilla registrada con ese nombre.',
            ]);
        }

        $detailsByDay = [];

        foreach (($data['detalles'] ?? []) as $index => $detail) {
            $start = $this->minutesFromTime($detail['hora_inicio'] ?? '');
            $end = $this->minutesFromTime($detail['hora_fin'] ?? '');

            if ($end <= $start) {
                throw ValidationException::withMessages([
                    "detalles.{$index}.hora_fin" => 'La hora fin debe ser mayor que la hora inicio.',
                ]);
            }

            $day = (int) $detail['dia'];

            foreach ($detailsByDay[$day] ?? [] as $stored) {
                // CU10 exige que no existan cruces de horario dentro del mismo dia.
                if ($start < $stored['end'] && $end > $stored['start']) {
                    throw ValidationException::withMessages([
                        "detalles.{$index}.hora_inicio" => "El bloque {$detail['hora_inicio']}-{$detail['hora_fin']} se solapa con {$stored['label']} en el mismo dia.",
                    ]);
                }
            }

            $detailsByDay[$day][] = [
                'start' => $start,
                'end' => $end,
                'label' => "{$detail['hora_inicio']}-{$detail['hora_fin']}",
            ];
        }

        return $data;
    }

    private function minutesFromTime(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    private function syncDetails(PlantillaHorario $plantilla, array $details): void
    {
        $plantilla->detalles()->delete();

        foreach ($details as $detail) {
            $plantilla->detalles()->create([
                'dia' => $detail['dia'],
                'hora_inicio' => $detail['hora_inicio'],
                'hora_fin' => $detail['hora_fin'],
                'modalidad' => $detail['modalidad'],
                'id_materia' => $detail['id_materia'],
            ]);
        }
    }

    private function syncPlantillaSerialSequences(): void
    {
        DB::statement("SELECT setval(pg_get_serial_sequence('plantilla_horario', 'id_plantilla'), COALESCE(MAX(id_plantilla), 1), MAX(id_plantilla) IS NOT NULL) FROM plantilla_horario");
        DB::statement("SELECT setval(pg_get_serial_sequence('detalle_plantilla_horario', 'id_detalle'), COALESCE(MAX(id_detalle), 1), MAX(id_detalle) IS NOT NULL) FROM detalle_plantilla_horario");
    }
}
