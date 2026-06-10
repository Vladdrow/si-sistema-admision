<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;

use App\Models\CarreraSemestre;
use App\Models\Examen;
use App\Models\Materia;
use App\Models\Nota;
use App\Models\ParametroAdmision;
use App\Models\Postulante;
use App\Models\Semestre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CU14 - Ejecutar Admision.
 *
 * Asigna cupos a postulantes aprobados por orden de merito, respetando
 * primera opcion, segunda opcion y, como excepcion, la carrera con menor
 * cantidad actual de estudiantes.
 */
class AdmisionController extends Controller
{
    public function index(Request $request): View
    {
        // CU14 Detalle, flujo 1-3: muestra parametros, cupos,
        // cantidad actual de estudiantes, aprobados y bloqueos del boton.
        $parametros = $this->parametrosDisponibles();
        $semestreId = $request->integer('semestre') ?: null;
        $parametro = $this->parametroSeleccionado($semestreId, $parametros);
        $materias = Materia::orderBy('nombre')->get();
        $examenes = collect();
        $cupos = collect();
        $postulantes = collect();
        $resumenAcademico = collect();
        $aprobados = collect();
        $admitidosPorCarrera = collect();

        if ($parametro) {
            $examenes = Examen::where('id_semestre', $parametro->id_semestre)
                ->orderBy('numero_examen')
                ->get();

            $cupos = CarreraSemestre::with('carrera')
                ->where('id_semestre', $parametro->id_semestre)
                ->get()
                ->sortBy(fn (CarreraSemestre $cupo) => $cupo->carrera?->nombre ?? '')
                ->values();

            $postulantes = $this->postulantesDelSemestre((int) $parametro->id_semestre);
            $resumenAcademico = $this->resumenAcademico($postulantes, $materias, $examenes, (float) $parametro->nota_minima_aprobacion);
            $aprobados = $resumenAcademico
                ->where('estado', 'Aprobado')
                ->sortByDesc('promedio')
                ->values();

            $admitidosPorCarrera = $postulantes
                ->filter(fn (Postulante $postulante) => $postulante->id_carrera_admitido !== null)
                ->groupBy('id_carrera_admitido');
        }

        $todosConNotas = $postulantes->isNotEmpty()
            && $resumenAcademico->isNotEmpty()
            && $resumenAcademico->every(fn (array $item) => $item['notas_completas']);

        $bloqueos = $this->bloqueosEjecucion($parametro, $cupos, $postulantes, $materias, $examenes, $aprobados, $todosConNotas);
        $puedeEjecutar = empty($bloqueos);

        return view('academico.admision.index', compact(
            'parametros',
            'semestreId',
            'parametro',
            'materias',
            'examenes',
            'cupos',
            'postulantes',
            'resumenAcademico',
            'aprobados',
            'admitidosPorCarrera',
            'todosConNotas',
            'bloqueos',
            'puedeEjecutar'
        ));
    }

    public function ejecutar(Request $request): RedirectResponse
    {
        // CU14 Detalle, flujo 4-7: ejecuta la asignacion de admision
        // para el semestre seleccionado si no existen bloqueos.
        $request->validate([
            'id_semestre' => ['required', 'integer', 'exists:semestre,id_semestre'],
        ]);

        $parametro = ParametroAdmision::with('semestre')
            ->where('id_semestre', $request->integer('id_semestre'))
            ->first();

        if (! $parametro) {
            return back()->withErrors(['admision' => 'No hay parametros de admision configurados.']);
        }

        $materias = Materia::orderBy('nombre')->get();
        $examenes = Examen::where('id_semestre', $parametro->id_semestre)->orderBy('numero_examen')->get();
        $cupos = CarreraSemestre::with('carrera')->where('id_semestre', $parametro->id_semestre)->get();
        $postulantes = $this->postulantesDelSemestre((int) $parametro->id_semestre);
        $resumenAcademico = $this->resumenAcademico($postulantes, $materias, $examenes, (float) $parametro->nota_minima_aprobacion);
        $aprobados = $resumenAcademico
            ->where('estado', 'Aprobado')
            ->sortByDesc('promedio')
            ->values();

        $todosConNotas = $postulantes->isNotEmpty()
            && $resumenAcademico->isNotEmpty()
            && $resumenAcademico->every(fn (array $item) => $item['notas_completas']);

        $bloqueos = $this->bloqueosEjecucion($parametro, $cupos, $postulantes, $materias, $examenes, $aprobados, $todosConNotas);
        if ($bloqueos !== []) {
            return back()->withErrors(['admision' => $bloqueos[0]]);
        }

        $asignados = DB::transaction(function () use ($parametro, $aprobados, $postulantes): array {
            $postulanteIds = $postulantes->pluck('id_postulante')->all();

            $cuposBloqueados = CarreraSemestre::with('carrera')
                ->where('id_semestre', $parametro->id_semestre)
                ->lockForUpdate()
                ->get();

            $admitidosPrevios = Postulante::whereIn('id_postulante', $postulanteIds)
                ->whereNotNull('id_carrera_admitido')
                ->select('id_carrera_admitido', DB::raw('COUNT(*) as total'))
                ->groupBy('id_carrera_admitido')
                ->pluck('total', 'id_carrera_admitido');

            $estadoCarreras = [];
            foreach ($cuposBloqueados as $cupo) {
                $previos = (int) ($admitidosPrevios[$cupo->id_carrera] ?? 0);
                $estadoCarreras[$cupo->id_carrera] = [
                    'modelo' => $cupo,
                    'nombre' => $cupo->carrera?->nombre ?? '',
                    'cupos' => (int) $cupo->cantidad_cupos,
                    'actual' => max(0, (int) $cupo->cantidad_estudiantes - $previos),
                ];
            }

            Postulante::whereIn('id_postulante', $postulanteIds)->update([
                'estado_admision' => 'No Admitido',
                'id_carrera_admitido' => null,
            ]);

            $resultado = [
                'primera' => 0,
                'segunda' => 0,
                'excepcion' => 0,
            ];

            foreach ($aprobados as $aprobado) {
                // CU14 Detalle, asignacion por orden de merito:
                // primera opcion, segunda opcion y luego excepcion.
                $postulante = $aprobado['postulante'];
                $carreraId = null;
                $tipo = 'excepcion';

                if ($this->tieneCupo($estadoCarreras, $postulante->id_carrera_primera_opc)) {
                    $carreraId = (int) $postulante->id_carrera_primera_opc;
                    $tipo = 'primera';
                } elseif ($this->tieneCupo($estadoCarreras, $postulante->id_carrera_segunda_opc)) {
                    $carreraId = (int) $postulante->id_carrera_segunda_opc;
                    $tipo = 'segunda';
                } else {
                    $carreraId = $this->carreraConMenorCantidad($estadoCarreras);
                }

                if (! $carreraId) {
                    continue;
                }

                $estadoCarreras[$carreraId]['actual']++;
                $resultado[$tipo]++;

                Postulante::where('id_postulante', $postulante->id_postulante)->update([
                    'estado_admision' => 'Admitido',
                    'id_carrera_admitido' => $carreraId,
                ]);
            }

            foreach ($estadoCarreras as $estado) {
                $estado['modelo']->update(['cantidad_estudiantes' => $estado['actual']]);
            }

            Semestre::where('id_semestre', $parametro->id_semestre)->update([
                'estado' => 'Finalizado',
            ]);

            return $resultado;
        });

        $total = array_sum($asignados);

        return redirect()
            ->route('admision.index', ['semestre' => $parametro->id_semestre])
            ->with('status', "Admision ejecutada: {$total} postulantes admitidos ({$asignados['primera']} primera opcion, {$asignados['segunda']} segunda opcion, {$asignados['excepcion']} excepcion).");
    }

    private function parametrosDisponibles(): Collection
    {
        return ParametroAdmision::with('semestre')
            ->join('semestre', 'semestre.id_semestre', '=', 'parametro_admision.id_semestre')
            ->orderByDesc('semestre.nombre')
            ->select('parametro_admision.*')
            ->get();
    }

    private function parametroSeleccionado(?int $semestreId, Collection $parametros): ?ParametroAdmision
    {
        if ($semestreId) {
            return $parametros->firstWhere('id_semestre', $semestreId);
        }

        return $parametros->first(
            fn (ParametroAdmision $parametro) => strtolower((string) $parametro->semestre?->estado) !== 'finalizado'
        ) ?? $parametros->first();
    }

    private function postulantesDelSemestre(int $semestreId): Collection
    {
        return Postulante::with(['persona', 'carreraPrimera', 'carreraSegunda', 'carreraAdmitido'])
            ->whereHas('postulanteGrupo.grupo', fn ($query) => $query->where('id_semestre', $semestreId))
            ->orderBy('id_postulante')
            ->get();
    }

    private function resumenAcademico(Collection $postulantes, Collection $materias, Collection $examenes, float $notaMinima): Collection
    {
        // CU14 Detalle, precondicion: calcula estado final solo si todas
        // las notas de todas las materias estan completas.
        if ($postulantes->isEmpty() || $materias->isEmpty() || $examenes->isEmpty()) {
            return collect();
        }

        $notas = Nota::whereIn('id_postulante', $postulantes->pluck('id_postulante'))
            ->whereIn('id_materia', $materias->pluck('id_materia'))
            ->whereIn('id_examen', $examenes->pluck('id_examen'))
            ->get()
            ->groupBy('id_postulante');

        $totalPonderacion = (float) $examenes->sum(fn (Examen $examen) => (float) $examen->ponderacion);

        return $postulantes->map(function (Postulante $postulante) use ($materias, $examenes, $notas, $notaMinima, $totalPonderacion): array {
            $notasPostulante = $notas->get($postulante->id_postulante, collect());
            $promediosMateria = [];
            $notasCompletas = true;

            foreach ($materias as $materia) {
                $sumaPonderada = 0.0;
                $notasMateria = [];

                foreach ($examenes as $examen) {
                    $nota = $notasPostulante
                        ->where('id_materia', $materia->id_materia)
                        ->firstWhere('id_examen', $examen->id_examen);

                    if (! $nota) {
                        $notasCompletas = false;
                        continue;
                    }

                    $notasMateria[] = (float) $nota->nota;
                    $sumaPonderada += (float) $nota->nota * (float) $examen->ponderacion;
                }

                if (count($notasMateria) === $examenes->count()) {
                    $promediosMateria[] = $totalPonderacion > 0
                        ? round($sumaPonderada / $totalPonderacion, 2)
                        : round(array_sum($notasMateria) / count($notasMateria), 2);
                }
            }

            $promedio = count($promediosMateria) > 0
                ? round(array_sum($promediosMateria) / count($promediosMateria), 2)
                : null;

            $todasAprobadas = $notasCompletas
                && count($promediosMateria) === $materias->count()
                && collect($promediosMateria)->every(fn (float $promedioMateria) => $promedioMateria >= $notaMinima);

            // CU14 Detalle: solo aprueba si todas las materias alcanzan
            // la nota minima configurada en CU07.
            return [
                'postulante' => $postulante,
                'promedio' => $promedio,
                'notas_completas' => $notasCompletas && count($promediosMateria) === $materias->count(),
                'estado' => $todasAprobadas ? 'Aprobado' : ($notasCompletas ? 'Reprobado' : 'Pendiente'),
            ];
        });
    }

    private function bloqueosEjecucion(
        ?ParametroAdmision $parametro,
        Collection $cupos,
        Collection $postulantes,
        Collection $materias,
        Collection $examenes,
        Collection $aprobados,
        bool $todosConNotas
    ): array {
        // CU14 Detalle, excepciones: estas condiciones deshabilitan
        // el boton "Ejecutar Admision".
        $bloqueos = [];

        if (! $parametro) {
            $bloqueos[] = 'No hay parametros de admision configurados.';
        }

        if ($parametro && strtolower((string) $parametro->semestre?->estado) === 'finalizado') {
            $bloqueos[] = 'El semestre seleccionado ya esta finalizado.';
        }

        if ($cupos->isEmpty()) {
            $bloqueos[] = 'No se han definido cupos por carrera.';
        }

        if ($materias->isEmpty() || $examenes->isEmpty()) {
            $bloqueos[] = 'No hay materias o examenes configurados para calcular el resultado final.';
        }

        if ($postulantes->isEmpty()) {
            $bloqueos[] = 'No hay postulantes asignados al semestre actual.';
        }

        if ($aprobados->isEmpty()) {
            $bloqueos[] = 'No hay postulantes aprobados para la admision.';
        }

        if (! $todosConNotas) {
            $bloqueos[] = 'Aun existen postulantes con notas incompletas.';
        }

        return $bloqueos;
    }

    private function tieneCupo(array $estadoCarreras, ?int $carreraId): bool
    {
        return $carreraId
            && isset($estadoCarreras[$carreraId])
            && $estadoCarreras[$carreraId]['actual'] < $estadoCarreras[$carreraId]['cupos'];
    }

    private function carreraConMenorCantidad(array $estadoCarreras): ?int
    {
        if ($estadoCarreras === []) {
            return null;
        }

        uasort($estadoCarreras, function (array $a, array $b): int {
            return [$a['actual'], $a['nombre']] <=> [$b['actual'], $b['nombre']];
        });

        return (int) array_key_first($estadoCarreras);
    }
}
