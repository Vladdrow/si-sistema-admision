<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\GrupoHorario;
use App\Models\ParametroAdmision;
use App\Models\Persona;
use App\Models\PlantillaHorario;
use App\Models\Postulante;
use App\Models\PostulanteGrupo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CU09 - Gestionar Grupo.
 *
 * Permite crear grupos progresivamente, cerrar inscripciones, consultar
 * grupos y sus estudiantes, y asignar horarios (plantilla + docente + aula).
 */
class GrupoController extends Controller
{
    public function index(): View
    {
        $grupos = Grupo::with(['semestre', 'postulanteGrupos'])
            ->withCount('postulanteGrupos')
            ->orderBy('id_grupo')
            ->paginate(15);

        $parametro = $this->parametroVigente();
        $pendientesAsignar = $this->contarPendientesAsignar();
        $totalGrupos = Grupo::count();
        $totalAsignados = PostulanteGrupo::distinct('id_postulante')->count();
        $inscripcionesAbiertas = $this->inscripcionesAbiertas($parametro);

        return view('grupos.index', compact(
            'grupos', 'parametro', 'pendientesAsignar',
            'totalGrupos', 'totalAsignados', 'inscripcionesAbiertas'
        ));
    }

    /**
     * Muestra los estudiantes de un grupo.
     */
    public function show(Grupo $grupo): View
    {
        $grupo->load([
            'semestre',
            'postulanteGrupos.postulante.persona',
            'postulanteGrupos.postulante.persona.credencial',
            'grupoHorarios.detalle.materia',
            'grupoHorarios.docente.persona',
            'grupoHorarios.aula',
        ]);

        return view('grupos.show', compact('grupo'));
    }

    /**
     * Crea grupos progresivamente: asigna postulantes pagados sin grupo.
     */
    public function crearGrupos(): RedirectResponse
    {
        $parametro = $this->parametroVigente();

        if (! $parametro) {
            return redirect()->route('grupos.index')
                ->with('status', 'No hay parametros de admision configurados.');
        }

        $maxPorGrupo = (int) ($parametro->max_estudiante_grupo ?: 70);
        $pendientes = $this->obtenerPendientesAsignar();

        if ($pendientes->isEmpty()) {
            return redirect()->route('grupos.index')
                ->with('status', 'No hay postulantes pagados pendientes de asignar.');
        }

        $gruposCreados = 0;
        $asignados = 0;

        DB::transaction(function () use ($pendientes, $maxPorGrupo, $parametro, &$gruposCreados, &$asignados): void {
            $grupo = Grupo::where('id_semestre', $parametro->id_semestre)
                ->where('cantidad_estudiantes', '<', $maxPorGrupo)
                ->orderBy('id_grupo')
                ->first();

            foreach ($pendientes as $postulante) {
                if (! $grupo || $grupo->cantidad_estudiantes >= $maxPorGrupo) {
                    $numero = Grupo::where('id_semestre', $parametro->id_semestre)->count() + 1;
                    $grupo = Grupo::create([
                        'nombre_grupo' => "Grupo {$numero}",
                        'cantidad_estudiantes' => 0,
                        'id_semestre' => $parametro->id_semestre,
                    ]);
                    $gruposCreados++;
                }

                PostulanteGrupo::create([
                    'id_grupo' => $grupo->id_grupo,
                    'id_postulante' => $postulante->id_postulante,
                ]);

                $grupo->increment('cantidad_estudiantes');
                $asignados++;
            }
        });

        return redirect()->route('grupos.index')
            ->with('status', "Grupos nuevos: {$gruposCreados}. Postulantes asignados: {$asignados}.");
    }

    /**
     * Cierra inscripciones y asigna todos los postulantes pendientes a grupos.
     */
    public function cerrarInscripciones(): RedirectResponse
    {
        $parametro = $this->parametroVigente();

        if (! $parametro) {
            return redirect()->route('grupos.index')
                ->with('status', 'No hay parametros de admision configurados.');
        }

        $parametro->update([
            'fecha_cierre_inscripcion' => now(),
        ]);

        return $this->crearGrupos();
    }

    /**
     * Muestra el formulario para asignar horario a un grupo.
     */
    public function showAsignarHorario(Grupo $grupo): View
    {
        $grupo->load('semestre');
        $plantillas = PlantillaHorario::with('detalles.materia')->orderBy('nombre')->get();
        $docentes = Docente::with('persona.credencial')
            ->whereHas('persona.credencial', fn ($q) => $q->where('estado', true))
            ->join('persona', 'persona.id_persona', '=', 'docente.id_docente')
            ->orderBy('persona.apellido_paterno')
            ->select('docente.*')
            ->get();
        $aulas = Aula::orderBy('nombre')->get();

        return view('grupos.asignar-horario', compact('grupo', 'plantillas', 'docentes', 'aulas'));
    }

    /**
     * Asigna horario a un grupo: plantilla, un docente por materia y un aula por bloque.
     */
    public function asignarHorario(Request $request, Grupo $grupo): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'id_plantilla' => ['required', 'integer', 'exists:plantilla_horario,id_plantilla'],
            'docentes' => ['required', 'array'],
            'docentes.*' => ['required', 'integer', 'exists:docente,id_docente'],
            'asignaciones' => ['required', 'array', 'min:1'],
            'asignaciones.*.id_detalle' => ['required', 'integer', 'exists:detalle_plantilla_horario,id_detalle'],
            'asignaciones.*.id_aula' => ['required', 'integer', 'exists:aula,id_aula'],
        ]);

        $detalles = \App\Models\DetallePlantillaHorario::whereIn('id_detalle', array_column($data['asignaciones'], 'id_detalle'))
            ->get()
            ->keyBy('id_detalle');

        $parametro = $this->parametroVigente();
        $maxGruposDocente = (int) ($parametro?->max_grupos_docente ?: 5);
        $plantillaId = (int) $data['id_plantilla'];

        // Validar que cada detalle pertenezca a la plantilla seleccionada
        foreach ($detalles as $detalle) {
            if ((int) $detalle->id_plantilla !== $plantillaId) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Uno de los bloques no pertenece a la plantilla seleccionada.',
                    ], 422);
                }
                return back()->withErrors(['plantilla' => 'Bloque invalido para esta plantilla.']);
            }
        }

        DB::transaction(function () use ($data, $grupo, $detalles, $maxGruposDocente): void {
            GrupoHorario::where('id_grupo', $grupo->id_grupo)->delete();

            foreach ($data['asignaciones'] as $asignacion) {
                $idDetalle = (int) $asignacion['id_detalle'];
                $idAula = (int) $asignacion['id_aula'];
                $detalle = $detalles[$idDetalle] ?? null;

                if (! $detalle) {
                    throw new \RuntimeException('Bloque de horario no encontrado.');
                }

                $idMateria = (int) ($detalle->id_materia ?: 0);
                $idDocente = (int) ($data['docentes'][$idMateria] ?? 0);

                if (! $idDocente) {
                    throw new \RuntimeException('Falta asignar docente para la materia del bloque.');
                }

                $gruposDocente = GrupoHorario::where('id_docente', $idDocente)
                    ->where('id_grupo', '!=', $grupo->id_grupo)
                    ->distinct('id_grupo')
                    ->count('id_grupo');

                if ($gruposDocente >= $maxGruposDocente) {
                    throw new \RuntimeException("El docente ya alcanzo el maximo de {$maxGruposDocente} grupos permitidos.");
                }

                $cruces = GrupoHorario::where('id_docente', $idDocente)
                    ->where('id_grupo', '!=', $grupo->id_grupo)
                    ->where('id_detalle', $idDetalle)
                    ->exists();

                if ($cruces) {
                    throw new \RuntimeException('El docente ya tiene un bloque asignado en ese horario en otro grupo.');
                }

                $aulaOcupada = GrupoHorario::where('id_aula', $idAula)
                    ->where('id_grupo', '!=', $grupo->id_grupo)
                    ->where('id_detalle', $idDetalle)
                    ->exists();

                if ($aulaOcupada) {
                    throw new \RuntimeException('El aula ya esta ocupada en ese bloque horario.');
                }

                GrupoHorario::create([
                    'id_grupo' => $grupo->id_grupo,
                    'id_detalle' => $idDetalle,
                    'id_docente' => $idDocente,
                    'id_aula' => $idAula,
                ]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Horario asignado correctamente.']);
        }

        return redirect()->route('grupos.show', $grupo->id_grupo)
            ->with('status', 'Horario asignado correctamente.');
    }

    /**
     * Valida via AJAX si un docente o aula estan disponibles para los bloques de una plantilla.
     */
    public function validarAsignacion(Request $request, Grupo $grupo): JsonResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:docente,aula'],
            'id' => ['required', 'integer'],
            'id_plantilla' => ['required', 'integer', 'exists:plantilla_horario,id_plantilla'],
        ]);

        $tipo = $data['tipo'];
        $id = (int) $data['id'];
        $plantillaId = (int) $data['id_plantilla'];

        $detallesIds = \App\Models\DetallePlantillaHorario::where('id_plantilla', $plantillaId)
            ->pluck('id_detalle')
            ->all();

        $conflictos = [];

        if ($tipo === 'docente') {
            $cruces = GrupoHorario::where('id_docente', $id)
                ->where('id_grupo', '!=', $grupo->id_grupo)
                ->whereIn('id_detalle', $detallesIds)
                ->with(['detalle', 'grupo'])
                ->get();

            foreach ($cruces as $cruce) {
                $conflictos[] = [
                    'grupo' => $cruce->grupo->nombre_grupo,
                    'dia' => (int) $cruce->detalle->dia,
                    'hora' => substr((string) $cruce->detalle->hora_inicio, 0, 5) . ' - ' . substr((string) $cruce->detalle->hora_fin, 0, 5),
                ];
            }
        }

        if ($tipo === 'aula') {
            $cruces = GrupoHorario::where('id_aula', $id)
                ->where('id_grupo', '!=', $grupo->id_grupo)
                ->whereIn('id_detalle', $detallesIds)
                ->with(['detalle', 'grupo'])
                ->get();

            foreach ($cruces as $cruce) {
                $conflictos[] = [
                    'grupo' => $cruce->grupo->nombre_grupo,
                    'dia' => (int) $cruce->detalle->dia,
                    'hora' => substr((string) $cruce->detalle->hora_inicio, 0, 5) . ' - ' . substr((string) $cruce->detalle->hora_fin, 0, 5),
                ];
            }
        }

        return response()->json([
            'disponible' => empty($conflictos),
            'conflictos' => $conflictos,
        ]);
    }

    private function parametroVigente(): ?ParametroAdmision
    {
        return ParametroAdmision::with('semestre')
            ->join('semestre', 'semestre.id_semestre', '=', 'parametro_admision.id_semestre')
            ->orderByDesc('semestre.nombre')
            ->select('parametro_admision.*')
            ->first();
    }

    private function inscripcionesAbiertas(?ParametroAdmision $parametro): bool
    {
        if (! $parametro) {
            return false;
        }

        $ahora = now();

        return $ahora->gte($parametro->fecha_inicio_inscripcion)
            && $ahora->lte($parametro->fecha_cierre_inscripcion);
    }

    private function contarPendientesAsignar(): int
    {
        return $this->obtenerPendientesAsignar()->count();
    }

    private function obtenerPendientesAsignar()
    {
        return Postulante::whereHas('persona.credencial', fn ($q) => $q->where('estado', true))
            ->whereDoesntHave('postulanteGrupo')
            ->get();
    }
}
