<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;

use App\Models\Carrera;
use App\Models\CarreraSemestre;
use App\Models\Examen;
use App\Models\Grupo;
use App\Models\GrupoHorario;
use App\Models\Materia;
use App\Models\Nota;
use App\Models\ParametroAdmision;
use App\Models\Postulante;
use App\Models\PostulanteGrupo;
use App\Models\Semestre;
use Illuminate\Pagination\LengthAwarePaginator;
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
    private const REPORT_TYPES = [
        'general' => 'Lista general de postulantes',
        'aprobados' => 'Postulantes aprobados',
        'reprobados' => 'Postulantes reprobados',
        'promedios' => 'Promedios generales',
        'grupos' => 'Cantidad de grupos habilitados',
        'materias' => 'Estadisticas por materia',
        'docentes' => 'Docentes por grupos',
        'grupos_aprobados' => 'Grupos con mayor cantidad de aprobados',
        'ranking_admision' => 'Ranking de admision',
        'distribucion_carrera' => 'Distribucion por carrera',
        'comparativa_gestiones' => 'Comparativa de gestiones',
        'final_admitidos' => 'Reporte final de admitidos',
    ];

    public function index(Request $request): View
    {
        // CU15 Detalle, flujo 1-6: genera en pantalla los reportes
        // obligatorios con filtros del semestre seleccionado.
        $data = $this->reportData($request, paginate: true);

        return view('seguridad.reportes.index', $data);
    }

    public function export(Request $request): StreamedResponse|View
    {
        // CU15 Detalle, flujo 7: exporta el reporte visible a CSV o PDF.
        $data = $this->reportData($request, paginate: false);
        $format = (string) $request->query('formato', 'csv');

        if ($format === 'pdf') {
            return view('seguridad.reportes.export-pdf', $data);
        }

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Reporte', $data['reportOptions'][$data['reportType']] ?? 'CU15 - Generar Reportes']);
            fputcsv($handle, ['Semestre', $data['reportSemesterLabel']]);
            fputcsv($handle, ['Total inscritos', $data['postulantesReporte']->count()]);
            fputcsv($handle, ['Aprobados', $data['aprobados']->count()]);
            fputcsv($handle, ['Reprobados', $data['reprobados']->count()]);
            fputcsv($handle, ['Promedio general', $data['promedioGeneral'] ?? 'N/A']);
            fputcsv($handle, []);

            $this->writeCsvReport($handle, $data);

            fclose($handle);
        }, 'reporte-' . $data['reportType'] . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function reportData(Request $request, bool $paginate): array
    {
        // CU15 Detalle, flujo 2-4: aplica filtro por semestre y obtiene
        // datos base de postulantes, notas, grupos, materias y docentes.
        $reportType = (string) $request->query('reporte', 'general');
        if (! array_key_exists($reportType, self::REPORT_TYPES)) {
            $reportType = 'general';
        }

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
        $postulantesSeleccionados = match ($reportType) {
            'aprobados' => $aprobados,
            'reprobados' => $reprobados,
            default => $postulantesReporte,
        };
        $estadisticasMateria = $this->estadisticasPorMateria($postulantes, $materias, $examenes, (float) ($parametro?->nota_minima_aprobacion ?? 60));
        $docentesPorGrupo = $this->docentesPorGrupo($semestreId);
        $gruposConAprobados = $this->gruposConMayorCantidadAprobados($grupos, $resumenAcademico);
        $rankingAdmision = $this->rankingAdmision($postulantesReporte);
        $distribucionCarrera = $this->distribucionPorCarrera($postulantes, $semestreId);
        $comparativaGestiones = $this->comparativaGestiones($semestres, $materias);
        $finalAdmitidos = $this->reporteFinalAdmitidos($postulantesReporte);

        $promediosValidos = $resumenAcademico->pluck('promedio')->filter(fn ($promedio) => $promedio !== null);
        $capacidadGrupo = (int) ($parametro?->max_estudiante_grupo ?? 0);
        $gruposCalculados = $capacidadGrupo > 0 ? (int) ceil($postulantes->count() / $capacidadGrupo) : 0;
        $reportRows = $this->rowsForReportType(
            $reportType,
            $postulantesSeleccionados,
            $postulantesReporte,
            $grupos,
            $estadisticasMateria,
            $docentesPorGrupo,
            $gruposConAprobados,
            $rankingAdmision,
            $distribucionCarrera,
            $comparativaGestiones,
            $finalAdmitidos
        );

        if ($paginate) {
            $reportRows = $this->paginateCollection($reportRows, $request);
        }

        return [
            'semestres' => $semestres,
            'semestreId' => $semestreId,
            'reportType' => $reportType,
            'reportOptions' => self::REPORT_TYPES,
            'reportSemesterLabel' => $reportType === 'comparativa_gestiones'
                ? 'Todas las gestiones'
                : ($parametro?->semestre?->nombre ?? 'Sin semestre'),
            'parametro' => $parametro,
            'materias' => $materias,
            'examenes' => $examenes,
            'postulantesReporte' => $postulantesReporte,
            'postulantesSeleccionados' => $postulantesSeleccionados,
            'aprobados' => $aprobados,
            'reprobados' => $reprobados,
            'promedioGeneral' => $promediosValidos->isNotEmpty() ? round((float) $promediosValidos->avg(), 2) : null,
            'grupos' => $grupos,
            'gruposCalculados' => $gruposCalculados,
            'capacidadGrupo' => $capacidadGrupo,
            'estadisticasMateria' => $estadisticasMateria,
            'docentesPorGrupo' => $docentesPorGrupo,
            'gruposConAprobados' => $gruposConAprobados,
            'rankingAdmision' => $rankingAdmision,
            'distribucionCarrera' => $distribucionCarrera,
            'comparativaGestiones' => $comparativaGestiones,
            'finalAdmitidos' => $finalAdmitidos,
            'reportRows' => $reportRows,
        ];
    }

    private function rowsForReportType(
        string $reportType,
        Collection $postulantesSeleccionados,
        Collection $postulantesReporte,
        Collection $grupos,
        Collection $estadisticasMateria,
        Collection $docentesPorGrupo,
        Collection $gruposConAprobados,
        Collection $rankingAdmision,
        Collection $distribucionCarrera,
        Collection $comparativaGestiones,
        Collection $finalAdmitidos
    ): Collection {
        return match ($reportType) {
            'promedios' => $postulantesReporte,
            'grupos' => $grupos,
            'materias' => $estadisticasMateria,
            'docentes' => $docentesPorGrupo,
            'grupos_aprobados' => $gruposConAprobados,
            'ranking_admision' => $rankingAdmision,
            'distribucion_carrera' => $distribucionCarrera,
            'comparativa_gestiones' => $comparativaGestiones,
            'final_admitidos' => $finalAdmitidos,
            default => $postulantesSeleccionados,
        };
    }

    private function paginateCollection(Collection $rows, Request $request): LengthAwarePaginator
    {
        $perPage = 25;
        $page = max(1, $request->integer('page', 1));

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function writeCsvReport($handle, array $data): void
    {
        match ($data['reportType']) {
            'materias' => $this->writeMateriasCsv($handle, $data['estadisticasMateria']),
            'grupos' => $this->writeGruposCsv($handle, $data['grupos'], $data['capacidadGrupo']),
            'docentes' => $this->writeDocentesCsv($handle, $data['docentesPorGrupo']),
            'grupos_aprobados' => $this->writeGruposAprobadosCsv($handle, $data['gruposConAprobados']),
            'ranking_admision' => $this->writeRankingAdmisionCsv($handle, $data['rankingAdmision']),
            'distribucion_carrera' => $this->writeDistribucionCarreraCsv($handle, $data['distribucionCarrera']),
            'comparativa_gestiones' => $this->writeComparativaGestionesCsv($handle, $data['comparativaGestiones']),
            'final_admitidos' => $this->writeFinalAdmitidosCsv($handle, $data['finalAdmitidos']),
            'promedios' => $this->writePromediosCsv($handle, $data['postulantesReporte']),
            default => $this->writePostulantesCsv($handle, $data['postulantesSeleccionados']),
        };
    }

    private function writePostulantesCsv($handle, Collection $postulantes): void
    {
        fputcsv($handle, ['Postulante', 'CI', 'Grupo', 'Primera opcion', 'Segunda opcion', 'Admitido en', 'Promedio', 'Estado academico', 'Estado admision']);
        foreach ($postulantes as $fila) {
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
    }

    private function writePromediosCsv($handle, Collection $postulantes): void
    {
        fputcsv($handle, ['Postulante', 'CI', 'Grupo', 'Promedio', 'Estado academico']);
        foreach ($postulantes as $fila) {
            fputcsv($handle, [$fila['nombre'], $fila['ci'], $fila['grupo'], $fila['promedio'] ?? 'Pendiente', $fila['estado_academico']]);
        }
    }

    private function writeMateriasCsv($handle, Collection $materias): void
    {
        fputcsv($handle, ['Materia', 'Promedio', 'Aprobados', 'Reprobados', 'Pendientes']);
        foreach ($materias as $fila) {
            fputcsv($handle, [$fila['materia']->nombre, $fila['promedio'] ?? 'Pendiente', $fila['aprobados'], $fila['reprobados'], $fila['pendientes']]);
        }
    }

    private function writeGruposCsv($handle, Collection $grupos, int $capacidadGrupo): void
    {
        fputcsv($handle, ['Grupo', 'Postulantes', 'Capacidad configurada']);
        foreach ($grupos as $grupo) {
            fputcsv($handle, [$grupo->nombre_grupo, $grupo->postulanteGrupos()->count(), $capacidadGrupo ?: 'N/A']);
        }
    }

    private function writeDocentesCsv($handle, Collection $docentesPorGrupo): void
    {
        fputcsv($handle, ['Grupo', 'Docente', 'Materia']);
        foreach ($docentesPorGrupo as $grupo) {
            foreach ($grupo['asignaciones'] as $asignacion) {
                fputcsv($handle, [$grupo['grupo'], $asignacion['docente'], $asignacion['materia']]);
            }
        }
    }

    private function writeGruposAprobadosCsv($handle, Collection $gruposConAprobados): void
    {
        fputcsv($handle, ['Grupo', 'Aprobados', 'Postulantes']);
        foreach ($gruposConAprobados as $fila) {
            fputcsv($handle, [$fila['grupo']->nombre_grupo, $fila['aprobados'], $fila['postulantes']]);
        }
    }

    private function writeRankingAdmisionCsv($handle, Collection $ranking): void
    {
        fputcsv($handle, ['Carrera', 'Posicion', 'Postulante', 'CI', 'Promedio', 'Ingreso por', 'Estado admision']);
        foreach ($ranking as $fila) {
            fputcsv($handle, [$fila['carrera'], $fila['posicion'], $fila['nombre'], $fila['ci'], $fila['promedio'] ?? 'Pendiente', $fila['ingreso_por'], $fila['estado_admision']]);
        }
    }

    private function writeDistribucionCarreraCsv($handle, Collection $distribucion): void
    {
        fputcsv($handle, ['Carrera', 'Primera opcion', 'Segunda opcion', 'Admitidos', 'Cupos', 'Estudiantes actuales']);
        foreach ($distribucion as $fila) {
            fputcsv($handle, [$fila['carrera'], $fila['primera_opcion'], $fila['segunda_opcion'], $fila['admitidos'], $fila['cupos'], $fila['estudiantes_actuales']]);
        }
    }

    private function writeComparativaGestionesCsv($handle, Collection $comparativa): void
    {
        fputcsv($handle, ['Semestre', 'Estado', 'Inscritos', 'Aprobados', 'Reprobados', 'Admitidos', 'Porcentaje ingreso', 'Promedio general', 'Grupos']);
        foreach ($comparativa as $fila) {
            fputcsv($handle, [$fila['semestre'], $fila['estado'], $fila['inscritos'], $fila['aprobados'], $fila['reprobados'], $fila['admitidos'], $fila['porcentaje_ingreso'] . '%', $fila['promedio_general'] ?? 'N/A', $fila['grupos']]);
        }
    }

    private function writeFinalAdmitidosCsv($handle, Collection $admitidos): void
    {
        fputcsv($handle, ['Carrera admitida', 'Posicion', 'Postulante', 'CI', 'Promedio', 'Primera opcion', 'Segunda opcion', 'Ingreso por']);
        foreach ($admitidos as $fila) {
            fputcsv($handle, [$fila['carrera'], $fila['posicion'], $fila['nombre'], $fila['ci'], $fila['promedio'] ?? 'Pendiente', $fila['primera_opcion'], $fila['segunda_opcion'], $fila['ingreso_por']]);
        }
    }

    private function rankingAdmision(Collection $postulantesReporte): Collection
    {
        return $postulantesReporte
            ->filter(fn (array $fila) => $fila['estado_admision'] === 'Admitido' && $fila['carrera_admitida'] !== 'Sin asignar')
            ->groupBy('carrera_admitida')
            ->sortKeys()
            ->flatMap(function (Collection $items, string $carrera): Collection {
                return $items
                    ->sortByDesc(fn (array $fila) => (float) ($fila['promedio'] ?? 0))
                    ->values()
                    ->map(fn (array $fila, int $index): array => [
                        'carrera' => $carrera,
                        'posicion' => $index + 1,
                        'nombre' => $fila['nombre'],
                        'ci' => $fila['ci'],
                        'promedio' => $fila['promedio'],
                        'primera_opcion' => $fila['primera_opcion'],
                        'segunda_opcion' => $fila['segunda_opcion'],
                        'ingreso_por' => $this->ingresoPorOpcion($fila),
                        'estado_admision' => $fila['estado_admision'],
                    ]);
            })
            ->values();
    }

    private function reporteFinalAdmitidos(Collection $postulantesReporte): Collection
    {
        return $this->rankingAdmision($postulantesReporte)
            ->map(fn (array $fila): array => [
                'carrera' => $fila['carrera'],
                'posicion' => $fila['posicion'],
                'nombre' => $fila['nombre'],
                'ci' => $fila['ci'],
                'promedio' => $fila['promedio'],
                'primera_opcion' => $fila['primera_opcion'],
                'segunda_opcion' => $fila['segunda_opcion'],
                'ingreso_por' => $fila['ingreso_por'],
            ])
            ->values();
    }

    private function ingresoPorOpcion(array $fila): string
    {
        if ($fila['carrera_admitida'] === $fila['primera_opcion']) {
            return 'Primera opcion';
        }

        if ($fila['carrera_admitida'] === $fila['segunda_opcion']) {
            return 'Segunda opcion';
        }

        return 'Carrera con cupo disponible';
    }

    private function distribucionPorCarrera(Collection $postulantes, int $semestreId): Collection
    {
        $cupos = $semestreId > 0
            ? CarreraSemestre::where('id_semestre', $semestreId)->get()->keyBy('id_carrera')
            : collect();

        return Carrera::orderBy('nombre')->get()->map(function (Carrera $carrera) use ($postulantes, $cupos): array {
            $cupo = $cupos->get($carrera->id_carrera);

            return [
                'carrera' => $carrera->nombre,
                'primera_opcion' => $postulantes->where('id_carrera_primera_opc', $carrera->id_carrera)->count(),
                'segunda_opcion' => $postulantes->where('id_carrera_segunda_opc', $carrera->id_carrera)->count(),
                'admitidos' => $postulantes->where('id_carrera_admitido', $carrera->id_carrera)->count(),
                'cupos' => $cupo?->cantidad_cupos ?? 0,
                'estudiantes_actuales' => $cupo?->cantidad_estudiantes ?? 0,
            ];
        });
    }

    private function comparativaGestiones(Collection $semestres, Collection $materias): Collection
    {
        return $semestres
            ->sortBy('nombre')
            ->values()
            ->map(function (Semestre $semestre) use ($materias): array {
                $parametro = ParametroAdmision::where('id_semestre', $semestre->id_semestre)->first();
                $examenes = Examen::where('id_semestre', $semestre->id_semestre)->orderBy('numero_examen')->get();
                $postulantes = $this->postulantesDelSemestre((int) $semestre->id_semestre);
                $resumen = $this->resumenAcademico($postulantes, $materias, $examenes, (float) ($parametro?->nota_minima_aprobacion ?? 60));
                $promediosValidos = $resumen->pluck('promedio')->filter(fn ($promedio) => $promedio !== null);
                $aprobados = $resumen->where('estado', 'Aprobado')->count();
                $reprobados = $resumen->where('estado', 'Reprobado')->count();
                $admitidos = $postulantes->where('estado_admision', 'Admitido')->count();
                $inscritos = $postulantes->count();

                return [
                    'semestre' => $semestre->nombre,
                    'estado' => $semestre->estado,
                    'inscritos' => $inscritos,
                    'aprobados' => $aprobados,
                    'reprobados' => $reprobados,
                    'admitidos' => $admitidos,
                    'porcentaje_ingreso' => $inscritos > 0 ? round(($admitidos / $inscritos) * 100, 2) : 0,
                    'promedio_general' => $promediosValidos->isNotEmpty() ? round((float) $promediosValidos->avg(), 2) : null,
                    'grupos' => Grupo::where('id_semestre', $semestre->id_semestre)->count(),
                ];
            });
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
