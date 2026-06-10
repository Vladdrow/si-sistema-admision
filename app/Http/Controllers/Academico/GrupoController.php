<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;

use App\Models\Aula;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\GrupoHorario;
use App\Models\ParametroAdmision;
use App\Models\Persona;
use App\Models\PlantillaHorario;
use App\Models\Postulante;
use App\Models\PostulanteGrupo;
use App\Models\Semestre;
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
    public function index(Request $request): View
    {
        // CU09 Detalle, flujo 1 y 2c: muestra el modulo de grupos,
        // resumen del semestre y lista de grupos para consulta.
        $isAsync = $request->ajax() || $request->expectsJson();
        $semestres = Semestre::orderByDesc('nombre')->get();
        $semestreId = $this->semestreSeleccionado($request, $semestres);

        $grupos = Grupo::with(['semestre', 'postulanteGrupos'])
            ->withCount('postulanteGrupos')
            ->where('id_semestre', $semestreId)
            ->orderBy('id_grupo')
            ->paginate(15);

        $parametro = $this->parametroPorSemestre($semestreId);
        $pendientesAsignar = $this->contarPendientesAsignar();
        $totalGrupos = Grupo::where('id_semestre', $semestreId)->count();
        $totalAsignados = PostulanteGrupo::whereHas('grupo', fn ($q) => $q->where('id_semestre', $semestreId))
            ->distinct('id_postulante')
            ->count('id_postulante');
        $inscripcionesAbiertas = $this->inscripcionesAbiertas($parametro);

        if ($isAsync) {
            return view('academico.grupos.partials.table', compact('grupos'));
        }

        return view('academico.grupos.index', compact(
            'grupos', 'parametro', 'pendientesAsignar',
            'totalGrupos', 'totalAsignados', 'inscripcionesAbiertas',
            'semestres', 'semestreId'
        ));
    }

    /**
     * Muestra los estudiantes de un grupo.
     */
    public function show(Grupo $grupo): View
    {
        // CU09 Detalle, opcion Consultar grupos y estudiantes:
        // carga el grupo, estudiantes asignados y horario relacionado.
        $grupo->load([
            'semestre',
            'postulanteGrupos.postulante.persona',
            'postulanteGrupos.postulante.persona.credencial',
            'grupoHorarios.detalle.materia',
            'grupoHorarios.docente.persona',
            'grupoHorarios.aula',
        ]);

        return view('academico.grupos.show', compact('grupo'));
    }

    /**
     * Crea grupos progresivamente: asigna postulantes pagados sin grupo.
     */
    public function crearGrupos(): RedirectResponse
    {
        // CU09 Detalle, opcion Crear grupos progresivo/final:
        // obtiene postulantes pagados sin grupo y los asigna por cupo.
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
            $this->syncGrupoSerialSequences();

            $grupo = Grupo::where('id_semestre', $parametro->id_semestre)
                ->where('cantidad_estudiantes', '<', $maxPorGrupo)
                ->orderBy('id_grupo')
                ->first();

            foreach ($pendientes as $postulante) {
                // CU09 Detalle, flujo 3a/4b: busca grupo con cupo disponible;
                // si no existe, crea uno nuevo antes de asignar al postulante.
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
        // CU09 Detalle, opcion Cerrar inscripciones: bloquea nuevos registros
        // actualizando la fecha de cierre y luego crea/asigna grupos.
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
        // CU09 Detalle, opcion Asignar horario: carga plantillas, docentes
        // habilitados y aulas para el grupo seleccionado.
        $grupo->load('semestre');
        if ($this->semestreFinalizado($grupo)) {
            abort(403, 'No se puede asignar horario a un grupo de un semestre finalizado.');
        }

        $plantillas = PlantillaHorario::with('detalles.materia')->orderBy('nombre')->get();
        $docentes = Docente::with(['persona.credencial', 'materiasHabilitadas'])
            ->whereHas('persona.credencial', fn ($q) => $q->where('estado', true))
            ->join('persona', 'persona.id_persona', '=', 'docente.id_docente')
            ->orderBy('persona.apellido_paterno')
            ->select('docente.*')
            ->get();
        $aulas = Aula::orderBy('nombre')->get();

        return view('academico.grupos.asignar-horario', compact('grupo', 'plantillas', 'docentes', 'aulas'));
    }

    /**
     * Asigna horario a un grupo: plantilla, un docente por materia y un aula por bloque.
     */
    public function asignarHorario(Request $request, Grupo $grupo): RedirectResponse|JsonResponse
    {
        // CU09 Detalle, opcion Asignar horario: valida plantilla,
        // docente por materia y aula por bloque.
        $grupo->loadMissing('semestre');
        if ($this->semestreFinalizado($grupo)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No se puede asignar horario a un grupo de un semestre finalizado.'], 422);
            }

            return back()->withErrors(['grupo' => 'No se puede asignar horario a un grupo de un semestre finalizado.']);
        }

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
        $semestreId = (int) $grupo->id_semestre;

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

        try {
            DB::transaction(function () use ($data, $grupo, $detalles, $maxGruposDocente, $semestreId): void {
                $this->syncGrupoHorarioSerialSequence();

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

                    $habilitado = DB::table('docente_materia_habilitada')
                        ->where('id_docente', $idDocente)
                        ->where('id_materia', $idMateria)
                        ->exists();

                    // CU09 Detalle, validacion de docente: el docente debe
                    // estar habilitado para la materia del bloque.
                    if (! $habilitado) {
                        throw new \RuntimeException('El docente seleccionado no esta habilitado para dictar la materia del bloque.');
                    }

                    $gruposDocente = GrupoHorario::where('id_docente', $idDocente)
                        ->where('id_grupo', '!=', $grupo->id_grupo)
                        ->whereHas('grupo', fn ($query) => $query->where('id_semestre', $semestreId))
                        ->distinct('id_grupo')
                        ->count('id_grupo');

                    if ($gruposDocente >= $maxGruposDocente) {
                        throw new \RuntimeException("El docente ya alcanzo el maximo de {$maxGruposDocente} grupos permitidos.");
                    }

                    // CU09 Detalle, validacion de docente/aula: no permite
                    // cruces de horario dentro del mismo semestre.
                    $cruces = GrupoHorario::where('id_docente', $idDocente)
                        ->where('id_grupo', '!=', $grupo->id_grupo)
                        ->where('id_detalle', $idDetalle)
                        ->whereHas('grupo', fn ($query) => $query->where('id_semestre', $semestreId))
                        ->exists();

                    if ($cruces) {
                        throw new \RuntimeException('El docente ya tiene un bloque asignado en ese horario en otro grupo.');
                    }

                    $aulaOcupada = GrupoHorario::where('id_aula', $idAula)
                        ->where('id_grupo', '!=', $grupo->id_grupo)
                        ->where('id_detalle', $idDetalle)
                        ->whereHas('grupo', fn ($query) => $query->where('id_semestre', $semestreId))
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
        } catch (\RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['horario' => $exception->getMessage()])->withInput();
        }

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
        // CU09 Detalle, validaciones previas por pantalla: informa si docente
        // o aula ya estan ocupados para la plantilla seleccionada.
        $grupo->loadMissing('semestre');
        if ($this->semestreFinalizado($grupo)) {
            return response()->json([
                'disponible' => false,
                'conflictos' => [],
                'message' => 'No se puede asignar horario a un grupo de un semestre finalizado.',
            ], 422);
        }

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
                ->whereHas('grupo', fn ($query) => $query->where('id_semestre', $grupo->id_semestre))
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
                ->whereHas('grupo', fn ($query) => $query->where('id_semestre', $grupo->id_semestre))
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
            ->whereRaw('LOWER(semestre.estado) = ?', ['activo'])
            ->orderByDesc('semestre.nombre')
            ->select('parametro_admision.*')
            ->first();
    }

    private function parametroPorSemestre(int $semestreId): ?ParametroAdmision
    {
        return ParametroAdmision::with('semestre')
            ->where('id_semestre', $semestreId)
            ->first();
    }

    private function semestreSeleccionado(Request $request, \Illuminate\Support\Collection $semestres): int
    {
        $requested = $request->integer('semestre');
        if ($requested && $semestres->contains('id_semestre', $requested)) {
            return $requested;
        }

        return (int) (
            $semestres->first(fn (Semestre $semestre) => strtolower((string) $semestre->estado) === 'activo')?->id_semestre
            ?? $semestres->first()?->id_semestre
            ?? 0
        );
    }

    private function semestreFinalizado(Grupo $grupo): bool
    {
        return strtolower((string) $grupo->semestre?->estado) === 'finalizado';
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
        // CU09 Detalle, precondicion: solo postulantes pagados y sin grupo
        // entran al proceso de creacion/asignacion de grupos.
        return Postulante::whereHas('persona.credencial', fn ($q) => $q->where('estado', true))
            ->whereHas('pagos', fn ($q) => $q->where('estado', 'Pagado'))
            ->whereDoesntHave('postulanteGrupo')
            ->get();
    }

    private function syncGrupoSerialSequences(): void
    {
        DB::statement("SELECT setval(pg_get_serial_sequence('grupo', 'id_grupo'), COALESCE(MAX(id_grupo), 1), MAX(id_grupo) IS NOT NULL) FROM grupo");
        DB::statement("SELECT setval(pg_get_serial_sequence('postulante_grupo', 'id_postulante_grupo'), COALESCE(MAX(id_postulante_grupo), 1), MAX(id_postulante_grupo) IS NOT NULL) FROM postulante_grupo");
    }

    private function syncGrupoHorarioSerialSequence(): void
    {
        DB::statement("SELECT setval(pg_get_serial_sequence('grupo_horario', 'id_grupo_horario'), COALESCE(MAX(id_grupo_horario), 1), MAX(id_grupo_horario) IS NOT NULL) FROM grupo_horario");
    }
}
