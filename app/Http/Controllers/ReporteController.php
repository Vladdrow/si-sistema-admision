<?php

namespace App\Http\Controllers;

use App\Models\Examen;
use App\Models\Grupo;
use App\Models\GrupoHorario;
use App\Models\Materia;
use App\Models\Nota;
use App\Models\ParametroAdmision;
use App\Models\Postulante;
use App\Models\PostulanteGrupo;
use App\Models\Semestre;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CU15 - Generar Reportes.
 *
 * Consolida los reportes obligatorios del proceso de admision usando los
 * datos reales de postulantes, notas, grupos, docentes y cupos.
 */
class ReporteController extends Controller
{
    public function index(Request $request): View
    {
        // CU15 Detalle, flujo 1-6: genera en pantalla los reportes
        // obligatorios con filtros del semestre seleccionado.
        $data = $this->reportData($request);

        return view('reportes.index', $data);
    }

    public function export(Request $request): StreamedResponse|View
    {
        // CU15 Detalle, flujo 7: exporta el reporte visible a CSV o PDF.
        $data = $this->reportData($request);
        $format = (string) $request->query('formato', 'csv');

        if ($format === 'pdf') {
            return view('reportes.export-pdf', $data);
        }

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Reporte', 'CU15 - Generar Reportes']);
            fputcsv($handle, ['Semestre', $data['parametro']?->semestre?->nombre ?? 'Sin semestre']);
            fputcsv($handle, ['Total inscritos', $data['postulantesReporte']->count()]);
            fputcsv($handle, ['Aprobados', $data['aprobados']->count()]);
            fputcsv($handle, ['Reprobados', $data['reprobados']->count()]);
            fputcsv($handle, ['Promedio general', $data['promedioGeneral'] ?? 'N/A']);
            fputcsv($handle, []);

            fputcsv($handle, ['Lista general de postulantes']);
            fputcsv($handle, ['Postulante', 'CI', 'Grupo', 'Primera opcion', 'Segunda opcion', 'Admitido en', 'Promedio', 'Estado academico', 'Estado admision']);
            foreach ($data['postulantesReporte'] as $fila) {
                fputcsv($handle, [
                    $fila['nombre'],
                    $fila['ci'],
                    $fila['grupo'],
                    $fila['primera_opcion'],
                    $fila['segunda_opcion'],
                    $fila['carrera_admitida'],
                    $fila['promedio'] ?? 'Pendiente',
                    $fila['estado_academico'],
                    $fila['estado_admision'],
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Estadisticas por materia']);
            fputcsv($handle, ['Materia', 'Promedio', 'Aprobados', 'Reprobados', 'Pendientes']);
            foreach ($data['estadisticasMateria'] as $fila) {
                fputcsv($handle, [
                    $fila['materia']->nombre,
                    $fila['promedio'] ?? 'Pendiente',
                    $fila['aprobados'],
                    $fila['reprobados'],
                    $fila['pendientes'],
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Grupos habilitados']);
            fputcsv($handle, ['Grupo', 'Postulantes', 'Capacidad configurada']);
            foreach ($data['grupos'] as $grupo) {
                fputcsv($handle, [
                    $grupo->nombre_grupo,
                    $grupo->postulanteGrupos()->count(),
                    $data['capacidadGrupo'] ?: 'N/A',
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Docentes por grupos']);
            fputcsv($handle, ['Grupo', 'Docente', 'Materia']);
            foreach ($data['docentesPorGrupo'] as $grupo) {
                foreach ($grupo['asignaciones'] as $asignacion) {
                    fputcsv($handle, [$grupo['grupo'], $asignacion['docente'], $asignacion['materia']]);
                }
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Grupos con mayor cantidad de aprobados']);
            fputcsv($handle, ['Grupo', 'Aprobados', 'Postulantes']);
            foreach ($data['gruposConAprobados'] as $fila) {
                fputcsv($handle, [$fila['grupo']->nombre_grupo, $fila['aprobados'], $fila['postulantes']]);
            }

            fclose($handle);
        }, 'reportes-admision.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function reportData(Request $request): array
    {
        // CU15 Detalle, flujo 2-4: aplica filtro por semestre y obtiene
        // datos base de postulantes, notas, grupos, materias y docentes.
        $semestres = Semestre::orderByDesc('nombre')->get();
        $semestreId = $request->integer('semestre') ?: (int) (
            $semestres->first(fn (Semestre $semestre) => strtolower((string) $semestre->estado) === 'activo')?->id_semestre
            ?? $semestres->first()?->id_semestre
            ?? 0
        );

        if ($semestreId > 0 && ! $semestres->contains('id_semestre', $semestreId)) {
            $semestreId = (int) ($semestres->first()?->id_semestre ?? 0);
        }

        $parametro = $semestreId > 0
            ? ParametroAdmision::with('semestre')->where('id_semestre', $semestreId)->first()
            : null;

        $materias = Materia::orderBy('nombre')->get();
        $examenes = $semestreId > 0
            ? Examen::where('id_semestre', $semestreId)->orderBy('numero_examen')->get()
            : collect();

        $postulantes = $semestreId > 0 ? $this->postulantesDelSemestre($semestreId) : collect();
        $grupos = $semestreId > 0
            ? Grupo::with('semestre')->where('id_semestre', $semestreId)->orderBy('nombre_grupo')->get()
            : collect();

        $resumenAcademico = $this->resumenAcademico(
            $postulantes,
            $materias,
            $examenes,
            (float) ($parametro?->nota_minima_aprobacion ?? 60)
        );

        $gruposPorPostulante = $this->gruposPorPostulante($semestreId);
        $postulantesReporte = $this->postulantesReporte($postulantes, $resumenAcademico, $gruposPorPostulante);
        $aprobados = $postulantesReporte->where('estado_academico', 'Aprobado')->values();
        $reprobados = $postulantesReporte->where('estado_academico', 'Reprobado')->values();
        $estadisticasMateria = $this->estadisticasPorMateria($postulantes, $materias, $examenes, (float) ($parametro?->nota_minima_aprobacion ?? 60));
        $docentesPorGrupo = $this->docentesPorGrupo($semestreId);
        $gruposConAprobados = $this->gruposConMayorCantidadAprobados($grupos, $resumenAcademico);

        $promediosValidos = $resumenAcademico->pluck('promedio')->filter(fn ($promedio) => $promedio !== null);
        $capacidadGrupo = (int) ($parametro?->max_estudiante_grupo ?? 0);
        $gruposCalculados = $capacidadGrupo > 0 ? (int) ceil($postulantes->count() / $capacidadGrupo) : 0;

        return [
            'semestres' => $semestres,
            'semestreId' => $semestreId,
            'parametro' => $parametro,
            'materias' => $materias,
            'examenes' => $examenes,
            'postulantesReporte' => $postulantesReporte,
            'aprobados' => $aprobados,
            'reprobados' => $reprobados,
            'promedioGeneral' => $promediosValidos->isNotEmpty() ? round((float) $promediosValidos->avg(), 2) : null,
            'grupos' => $grupos,
            'gruposCalculados' => $gruposCalculados,
            'capacidadGrupo' => $capacidadGrupo,
            'estadisticasMateria' => $estadisticasMateria,
            'docentesPorGrupo' => $docentesPorGrupo,
            'gruposConAprobados' => $gruposConAprobados,
        ];
    }

    private function postulantesDelSemestre(int $semestreId): Collection
    {
        return Postulante::with(['persona', 'carreraPrimera', 'carreraSegunda', 'carreraAdmitido'])
            ->whereHas('postulanteGrupo.grupo', fn ($query) => $query->where('id_semestre', $semestreId))
            ->orderBy('id_postulante')
            ->get();
    }

    private function gruposPorPostulante(int $semestreId): Collection
    {
        if ($semestreId === 0) {
            return collect();
        }

        return PostulanteGrupo::with('grupo')
            ->whereHas('grupo', fn ($query) => $query->where('id_semestre', $semestreId))
            ->get()
            ->groupBy('id_postulante')
            ->map(fn (Collection $items) => $items->pluck('grupo.nombre_grupo')->filter()->implode(', '));
    }

    private function postulantesReporte(Collection $postulantes, Collection $resumenAcademico, Collection $gruposPorPostulante): Collection
    {
        return $postulantes->map(function (Postulante $postulante) use ($resumenAcademico, $gruposPorPostulante): array {
            $resumen = $resumenAcademico->get($postulante->id_postulante, [
                'promedio' => null,
                'estado' => 'Pendiente',
            ]);

            return [
                'postulante' => $postulante,
                'nombre' => $postulante->persona?->nombre_completo ?? 'Sin nombre',
                'ci' => $postulante->persona?->ci ?? 'Sin CI',
                'grupo' => $gruposPorPostulante->get($postulante->id_postulante, 'Sin grupo'),
                'primera_opcion' => $postulante->carreraPrimera?->nombre ?? 'Sin definir',
                'segunda_opcion' => $postulante->carreraSegunda?->nombre ?? 'Sin definir',
                'carrera_admitida' => $postulante->carreraAdmitido?->nombre ?? 'Sin asignar',
                'estado_admision' => $postulante->estado_admision,
                'promedio' => $resumen['promedio'],
                'estado_academico' => $resumen['estado'],
            ];
        });
    }

    private function resumenAcademico(Collection $postulantes, Collection $materias, Collection $examenes, float $notaMinima): Collection
    {
        // CU15 Detalle: calcula promedios y estados academicos para
        // reportes de aprobados, reprobados y promedio general.
        if ($postulantes->isEmpty() || $materias->isEmpty() || $examenes->isEmpty()) {
            return collect();
        }

        $notas = Nota::whereIn('id_postulante', $postulantes->pluck('id_postulante'))
            ->whereIn('id_materia', $materias->pluck('id_materia'))
            ->whereIn('id_examen', $examenes->pluck('id_examen'))
            ->get()
            ->groupBy('id_postulante');

        $totalPonderacion = (float) $examenes->sum(fn (Examen $examen) => (float) $examen->ponderacion);

        return $postulantes->mapWithKeys(function (Postulante $postulante) use ($materias, $examenes, $notas, $notaMinima, $totalPonderacion): array {
            $notasPostulante = $notas->get($postulante->id_postulante, collect());
            $promediosMateria = [];
            $notasCompletas = true;

            foreach ($materias as $materia) {
                $promedioMateria = $this->promedioMateria($notasPostulante, $materia->id_materia, $examenes, $totalPonderacion);

                if ($promedioMateria === null) {
                    $notasCompletas = false;
                    continue;
                }

                $promediosMateria[] = $promedioMateria;
            }

            $promedio = count($promediosMateria) > 0
                ? round(array_sum($promediosMateria) / count($promediosMateria), 2)
                : null;

            $estado = 'Pendiente';
            if ($notasCompletas && count($promediosMateria) === $materias->count()) {
                $estado = collect($promediosMateria)->every(fn (float $item) => $item >= $notaMinima)
                    ? 'Aprobado'
                    : 'Reprobado';
            }

            return [$postulante->id_postulante => [
                'promedio' => $promedio,
                'estado' => $estado,
                'notas_completas' => $notasCompletas && count($promediosMateria) === $materias->count(),
            ]];
        });
    }

    private function estadisticasPorMateria(Collection $postulantes, Collection $materias, Collection $examenes, float $notaMinima): Collection
    {
        // CU15 Detalle: genera estadisticas por materia
        // (promedio, aprobados, reprobados y pendientes).
        if ($postulantes->isEmpty() || $materias->isEmpty() || $examenes->isEmpty()) {
            return collect();
        }

        $notasPorPostulante = Nota::whereIn('id_postulante', $postulantes->pluck('id_postulante'))
            ->whereIn('id_materia', $materias->pluck('id_materia'))
            ->whereIn('id_examen', $examenes->pluck('id_examen'))
            ->get()
            ->groupBy('id_postulante');

        $totalPonderacion = (float) $examenes->sum(fn (Examen $examen) => (float) $examen->ponderacion);

        return $materias->map(function (Materia $materia) use ($postulantes, $notasPorPostulante, $examenes, $notaMinima, $totalPonderacion): array {
            $promedios = [];
            $aprobados = 0;
            $reprobados = 0;
            $pendientes = 0;

            foreach ($postulantes as $postulante) {
                $promedio = $this->promedioMateria(
                    $notasPorPostulante->get($postulante->id_postulante, collect()),
                    $materia->id_materia,
                    $examenes,
                    $totalPonderacion
                );

                if ($promedio === null) {
                    $pendientes++;
                    continue;
                }

                $promedios[] = $promedio;
                $promedio >= $notaMinima ? $aprobados++ : $reprobados++;
            }

            return [
                'materia' => $materia,
                'promedio' => count($promedios) > 0 ? round(array_sum($promedios) / count($promedios), 2) : null,
                'aprobados' => $aprobados,
                'reprobados' => $reprobados,
                'pendientes' => $pendientes,
            ];
        });
    }

    private function promedioMateria(Collection $notasPostulante, int $materiaId, Collection $examenes, float $totalPonderacion): ?float
    {
        $notasMateria = [];
        $sumaPonderada = 0.0;

        foreach ($examenes as $examen) {
            $nota = $notasPostulante
                ->where('id_materia', $materiaId)
                ->firstWhere('id_examen', $examen->id_examen);

            if (! $nota) {
                return null;
            }

            $notasMateria[] = (float) $nota->nota;
            $sumaPonderada += (float) $nota->nota * (float) $examen->ponderacion;
        }

        return $totalPonderacion > 0
            ? round($sumaPonderada / $totalPonderacion, 2)
            : round(array_sum($notasMateria) / count($notasMateria), 2);
    }

    private function docentesPorGrupo(int $semestreId): Collection
    {
        // CU15 Detalle: reporte de docentes asignados por grupo y materia.
        if ($semestreId === 0) {
            return collect();
        }

        return GrupoHorario::with(['grupo', 'docente.persona', 'detalle.materia'])
            ->whereHas('grupo', fn ($query) => $query->where('id_semestre', $semestreId))
            ->get()
            ->groupBy('id_grupo')
            ->map(function (Collection $horarios): array {
                $grupo = $horarios->first()?->grupo;
                $asignaciones = $horarios
                    ->map(fn (GrupoHorario $horario) => [
                        'docente' => $horario->docente?->persona?->nombre_completo ?? 'Sin docente',
                        'materia' => $horario->detalle?->materia?->nombre ?? 'Sin materia',
                    ])
                    ->unique(fn (array $item) => $item['docente'] . '|' . $item['materia'])
                    ->values();

                return [
                    'grupo' => $grupo?->nombre_grupo ?? 'Sin grupo',
                    'asignaciones' => $asignaciones,
                ];
            })
            ->values();
    }

    private function gruposConMayorCantidadAprobados(Collection $grupos, Collection $resumenAcademico): Collection
    {
        // CU15 Detalle: ranking de grupos con mayor cantidad de aprobados.
        return $grupos->map(function (Grupo $grupo) use ($resumenAcademico): array {
            $postulantesIds = $grupo->postulanteGrupos()->pluck('id_postulante');
            $aprobados = $postulantesIds->filter(fn (int $id) => ($resumenAcademico[$id]['estado'] ?? 'Pendiente') === 'Aprobado')->count();

            return [
                'grupo' => $grupo,
                'aprobados' => $aprobados,
                'postulantes' => $postulantesIds->count(),
            ];
        })
            ->sortByDesc('aprobados')
            ->values();
    }
}
