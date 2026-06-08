<?php

namespace App\Http\Controllers;

use App\Models\Examen;
use App\Models\Grupo;
use App\Models\GrupoHorario;
use App\Models\Nota;
use App\Models\ParametroAdmision;
use App\Models\PostulanteGrupo;
use App\Models\Semestre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CU12 - Gestionar Nota.
 *
 * Permite al docente registrar y modificar las calificaciones de los
 * examenes de los postulantes en los grupos que tiene asignados.
 * Calcula automaticamente promedios y estado al completar 3 examenes.
 */
class NotaController extends Controller
{
    public function index(Request $request): View
    {
        // CU12 Detalle, flujo 1-2: el docente entra a Gestionar Nota
        // y ve solo los grupos que tiene asignados.
        $docenteId = (int) auth()->user()->id_persona;
        $semestres = Semestre::orderByDesc('nombre')->get();
        $semestreId = $this->semestreSeleccionado($request, $semestres);

        $gruposIds = GrupoHorario::where('id_docente', $docenteId)
            ->distinct('id_grupo')
            ->pluck('id_grupo');

        $grupos = Grupo::with('semestre')
            ->whereIn('id_grupo', $gruposIds)
            ->where('id_semestre', $semestreId)
            ->orderBy('id_grupo')
            ->get();

        return view('notas.index', compact('grupos', 'semestres', 'semestreId'));
    }

    /**
     * CU12 - Muestra los postulantes de un grupo con sus notas para una materia.
     */
    public function show(Request $request, Grupo $grupo): View
    {
        // CU12 Detalle, flujo 3-5: muestra postulantes del grupo,
        // materias del docente y examen a registrar/modificar.
        $docenteId = (int) auth()->user()->id_persona;
        $materiaId = (int) $request->query('materia', 0);
        $examenSolicitado = (int) $request->query('examen', 0);

        $materias = $this->materiasDelDocenteEnGrupo($docenteId, $grupo->id_grupo);

        if ($materiaId === 0 && $materias->isNotEmpty()) {
            $materiaId = (int) $materias->first()->id_materia;
        }

        $materia = $materias->firstWhere('id_materia', $materiaId);
        $postulantes = collect();
        $notasPorPostulante = [];
        $examenes = collect();
        $notaMinima = 60;
        $examenActual = 1;
        $examenActualModel = null;

        if ($materia) {
            $parametro = ParametroAdmision::query()
                ->whereHas('semestre', fn ($q) => $q->where('id_semestre', $grupo->id_semestre))
                ->first();

            $notaMinima = (float) ($parametro?->nota_minima_aprobacion ?: 60);

            $examenes = Examen::where('id_semestre', $grupo->id_semestre)
                ->orderBy('numero_examen')
                ->get();

            $postulantes = PostulanteGrupo::with('postulante.persona')
                ->where('id_grupo', $grupo->id_grupo)
                ->get()
                ->map(fn ($pg) => $pg->postulante)
                ->filter();

            $notasExistentes = Nota::where('id_materia', $materiaId)
                ->whereIn('id_postulante', $postulantes->pluck('id_postulante'))
                ->get()
                ->groupBy('id_postulante');

            foreach ($postulantes as $postulante) {
                // CU12 Detalle, flujo 4a: detecta automaticamente el
                // siguiente examen pendiente segun notas existentes.
                $notasDelPostulante = $notasExistentes->get($postulante->id_postulante, collect());
                $notasPorPostulante[$postulante->id_postulante] = [];

                foreach ($examenes as $examen) {
                    $nota = $notasDelPostulante->firstWhere('id_examen', $examen->id_examen);
                    $notasPorPostulante[$postulante->id_postulante][$examen->id_examen] = $nota;
                }

                $examenesCompletados = $notasDelPostulante->count();
                if ($examenesCompletados < $examenes->count() && $examenActual <= $examenesCompletados + 1) {
                    $examenActual = $examenesCompletados + 1;
                }
            }

            $examenActualModel = $examenSolicitado > 0
                ? $examenes->firstWhere('id_examen', $examenSolicitado)
                : null;

            $examenActualModel ??= $examenes->firstWhere('numero_examen', $examenActual) ?? $examenes->first();
        }

        $cerrado = $this->notasCerradas($grupo);

        return view('notas.show', compact(
            'grupo', 'materias', 'materia', 'materiaId',
            'postulantes', 'notasPorPostulante', 'examenes',
            'notaMinima', 'examenActual', 'examenActualModel', 'cerrado'
        ));
    }

    /**
     * Guarda las notas de un examen para todos los postulantes del grupo.
     */
    public function store(Request $request, Grupo $grupo): RedirectResponse
    {
        // CU12 Detalle, Registrar/Modificar nota: valida rango 0-100,
        // pertenencia del postulante al grupo y materia asignada al docente.
        $docenteId = (int) auth()->user()->id_persona;

        if ($this->notasCerradas($grupo)) {
            return back()->with('status', 'No se pueden registrar notas. El semestre esta finalizado o la fecha de cierre de notas ya paso.');
        }

        $data = $request->validate([
            'id_materia' => ['required', 'integer', 'exists:materia,id_materia'],
            'id_examen' => ['required', 'integer', 'exists:examen,id_examen'],
            'notas' => ['required', 'array'],
            'notas.*' => ['required', 'numeric', 'between:0,100'],
        ]);

        $materiaId = (int) $data['id_materia'];
        $examenId = (int) $data['id_examen'];

        if (! $this->materiasDelDocenteEnGrupo($docenteId, $grupo->id_grupo)->contains('id_materia', $materiaId)) {
            return back()->with('status', 'La materia seleccionada no esta asignada a este docente en el grupo.');
        }

        $examenValido = Examen::where('id_examen', $examenId)
            ->where('id_semestre', $grupo->id_semestre)
            ->exists();

        if (! $examenValido) {
            return back()->with('status', 'El examen seleccionado no corresponde al semestre del grupo.');
        }

        $postulantesIds = PostulanteGrupo::where('id_grupo', $grupo->id_grupo)
            ->pluck('id_postulante')->all();

        $notasGuardadas = 0;

        DB::transaction(function () use ($data, $docenteId, $materiaId, $examenId, $postulantesIds, &$notasGuardadas): void {
            $this->syncNotaSerialSequence();

            foreach ($data['notas'] as $postulanteId => $notaValor) {
                $postulanteId = (int) $postulanteId;

                if (! in_array($postulanteId, $postulantesIds, true)) {
                    continue;
                }

                Nota::updateOrCreate(
                    // CU12 Detalle, flujo 8a/8b: si ya existe nota,
                    // se modifica; si no existe, se registra.
                    [
                        'id_postulante' => $postulanteId,
                        'id_materia' => $materiaId,
                        'id_examen' => $examenId,
                    ],
                    [
                        'nota' => (float) $notaValor,
                        'fecha_registro' => now(),
                        'id_docente' => $docenteId,
                    ]
                );

                $notasGuardadas++;
            }
        });

        return redirect()->route('notas.show', ['grupo' => $grupo->id_grupo, 'materia' => $materiaId, 'examen' => $examenId])
            ->with('status', "{$notasGuardadas} notas guardadas correctamente.");
    }

    private function materiasDelDocenteEnGrupo(int $docenteId, int $grupoId)
    {
        return GrupoHorario::where('id_docente', $docenteId)
            ->where('id_grupo', $grupoId)
            ->with('detalle.materia')
            ->get()
            ->map(fn ($gh) => $gh->detalle->materia)
            ->filter()
            ->unique('id_materia')
            ->values();
    }

    private function syncNotaSerialSequence(): void
    {
        DB::statement("SELECT setval(pg_get_serial_sequence('nota', 'id_nota'), COALESCE(MAX(id_nota), 1), MAX(id_nota) IS NOT NULL) FROM nota");
    }

    private function notasCerradas(Grupo $grupo): bool
    {
        // CU12 Detalle, excepcion: bloquea registro/modificacion si paso
        // la fecha de cierre de notas o el semestre fue finalizado.
        $grupo->loadMissing('semestre');
        if (strtolower((string) $grupo->semestre?->estado) === 'finalizado') {
            return true;
        }

        $parametro = ParametroAdmision::query()
            ->whereHas('semestre', fn ($q) => $q->where('id_semestre', $grupo->id_semestre))
            ->first();

        return $parametro && $parametro->fecha_cierre_notas && now()->gt($parametro->fecha_cierre_notas);
    }

    /**
     * CU13 - Consulta de notas para admin/personal: listado de grupos.
     */
    public function consulta(Request $request): View
    {
        // CU13 Detalle, actor Administrativo/Administrador: lista grupos
        // del semestre para consultar notas en modo solo lectura.
        $semestres = Semestre::orderByDesc('nombre')->get();
        $semestreId = $this->semestreSeleccionado($request, $semestres);

        $grupos = Grupo::with('semestre')
            ->where('id_semestre', $semestreId)
            ->orderBy('id_grupo')
            ->get();

        return view('notas.consulta', compact('grupos', 'semestres', 'semestreId'));
    }

    /**
     * CU13 - Consulta de notas para admin/personal: postulantes del grupo.
     */
    public function consultaGrupo(Request $request, Grupo $grupo): View
    {
        // CU13 Detalle: muestra notas por materia y estado general
        // del grupo seleccionado, sin permitir modificaciones.
        $materias = \App\Models\Materia::orderBy('nombre')->get();
        $postulantes = PostulanteGrupo::with('postulante.persona.credencial')
            ->where('id_grupo', $grupo->id_grupo)
            ->get()
            ->map(fn ($pg) => $pg->postulante)
            ->filter();

        $examenes = Examen::where('id_semestre', $grupo->id_semestre)
            ->orderBy('numero_examen')
            ->get();

        $notas = Nota::whereIn('id_postulante', $postulantes->pluck('id_postulante'))
            ->with('materia')
            ->get()
            ->groupBy('id_postulante');

        $parametro = ParametroAdmision::query()
            ->whereHas('semestre', fn ($q) => $q->where('id_semestre', $grupo->id_semestre))
            ->first();

        $notaMinima = (float) ($parametro?->nota_minima_aprobacion ?: 60);

        $resumen = [];
        foreach ($postulantes as $postulante) {
            $notasPostulante = $notas->get($postulante->id_postulante, collect());
            $porMateria = [];

            foreach ($materias as $materia) {
                $notasMateria = $notasPostulante->where('id_materia', $materia->id_materia);
                $promedio = $notasMateria->isNotEmpty() ? round((float) $notasMateria->avg('nota'), 2) : null;
                $estado = $promedio === null ? 'Pendiente' : ($promedio >= $notaMinima ? 'Aprobado' : 'Reprobado');

                $porMateria[$materia->id_materia] = [
                    'promedio' => $promedio,
                    'estado' => $estado,
                    'notas' => $notasMateria,
                ];
            }

            $todasAprobadas = collect($porMateria)->every(fn ($m) => $m['estado'] === 'Aprobado');
            $algunaReprobada = collect($porMateria)->contains(fn ($m) => $m['estado'] === 'Reprobado');
            // CU13 Detalle: estado general es Reprobado si alguna materia
            // esta reprobada, aunque el promedio global pudiera ser alto.
            $estadoGeneral = $algunaReprobada ? 'Reprobado' : ($todasAprobadas ? 'Aprobado' : 'Pendiente');

            $resumen[$postulante->id_postulante] = [
                'postulante' => $postulante,
                'materias' => $porMateria,
                'estado_general' => $estadoGeneral,
            ];
        }

        return view('notas.consulta-grupo', compact(
            'grupo', 'materias', 'resumen', 'examenes', 'notaMinima'
        ));
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
}
