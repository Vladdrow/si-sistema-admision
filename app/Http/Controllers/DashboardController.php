<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Credencial;
use App\Models\Docente;
use App\Models\ParametroAdmision;
use App\Models\Persona;
use App\Models\PersonalAdministrativo;
use App\Models\PlantillaHorario;
use App\Models\Postulante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $isAdmin = $user?->esAdministrador() ?? false;
        $isPostulante = $user?->esPostulante() ?? false;
        $isDocente = $user?->esDocente() ?? false;

        if ($isPostulante) {
            return view('dashboard', [
                'isAdmin' => false,
                'isPostulante' => true,
                'isDocente' => false,
                ...$this->postulanteDashboard((int) $user->id_persona),
            ]);
        }

        if ($isDocente) {
            return view('dashboard', [
                'isAdmin' => false,
                'isPostulante' => false,
                'isDocente' => true,
                ...$this->docenteDashboard((int) $user->id_persona),
            ]);
        }

        $postulantesPorEstado = Postulante::query()
            ->selectRaw('estado_admision, COUNT(*) as total')
            ->groupBy('estado_admision')
            ->orderBy('estado_admision')
            ->pluck('total', 'estado_admision');

        $parametroVigente = ParametroAdmision::with('semestre')
            ->where('fecha_inicio_inscripcion', '<=', now())
            ->where('fecha_cierre_inscripcion', '>=', now())
            ->join('semestre', 'semestre.id_semestre', '=', 'parametro_admision.id_semestre')
            ->orderByDesc('semestre.nombre')
            ->select('parametro_admision.*')
            ->first();

        $ultimoParametro = ParametroAdmision::with('semestre')
            ->join('semestre', 'semestre.id_semestre', '=', 'parametro_admision.id_semestre')
            ->orderByDesc('semestre.nombre')
            ->select('parametro_admision.*')
            ->first();

        $credencialesActivas = Credencial::where('estado', true)->count();
        $credencialesInactivas = Credencial::where('estado', false)->count();

        return view('dashboard', [
            'isAdmin' => $isAdmin,
            'isPostulante' => false,
            'isDocente' => false,
            'resumen' => [
                'postulantes' => Postulante::count(),
                'docentes' => Docente::count(),
                'personal' => PersonalAdministrativo::count(),
                'personas' => Persona::count(),
                'plantillas' => PlantillaHorario::count(),
                'credenciales_activas' => $credencialesActivas,
                'credenciales_inactivas' => $credencialesInactivas,
            ],
            'postulantesPorEstado' => $postulantesPorEstado,
            'parametroVigente' => $parametroVigente,
            'ultimoParametro' => $ultimoParametro,
            'actividadReciente' => $isAdmin
                ? Bitacora::with('persona')->orderByDesc('fecha_hora')->limit(6)->get()
                : collect(),
        ]);
    }

    private function postulanteDashboard(int $postulanteId): array
    {
        $postulante = Postulante::with(['persona', 'carreraPrimera', 'carreraSegunda', 'carreraAdmitido'])
            ->find($postulanteId);

        $grupo = null;
        $horario = collect();

        if (
            Schema::hasTable('postulante_grupo')
            && Schema::hasTable('grupo')
            && Schema::hasTable('grupo_horario')
            && Schema::hasTable('detalle_plantilla_horario')
        ) {
            $grupo = DB::table('postulante_grupo as pg')
                ->join('grupo as g', 'g.id_grupo', '=', 'pg.id_grupo')
                ->leftJoin('semestre as s', 's.id_semestre', '=', 'g.id_semestre')
                ->where('pg.id_postulante', $postulanteId)
                ->orderByDesc('pg.fecha_asignacion')
                ->select('g.id_grupo', 'g.nombre_grupo', 's.nombre as semestre')
                ->first();

            if ($grupo) {
                $horarioQuery = DB::table('grupo_horario as gh')
                    ->join('detalle_plantilla_horario as d', 'd.id_detalle', '=', 'gh.id_detalle')
                    ->leftJoin('plantilla_horario as p', 'p.id_plantilla', '=', 'd.id_plantilla')
                    ->where('gh.id_grupo', $grupo->id_grupo)
                    ->orderBy('d.dia')
                    ->orderBy('d.hora_inicio')
                    ->select(
                        'd.dia',
                        'd.hora_inicio',
                        'd.hora_fin',
                        'd.modalidad',
                        'p.nombre as plantilla',
                        'p.turno'
                    );

                if (Schema::hasTable('aula')) {
                    $horarioQuery->leftJoin('aula as a', 'a.id_aula', '=', 'gh.id_aula')
                        ->addSelect('a.nombre as aula');
                } else {
                    $horarioQuery->addSelect(DB::raw('NULL as aula'));
                }

                if (Schema::hasTable('docente') && Schema::hasTable('persona')) {
                    $horarioQuery->leftJoin('docente as doc', 'doc.id_docente', '=', 'gh.id_docente')
                        ->leftJoin('persona as per_doc', 'per_doc.id_persona', '=', 'doc.id_docente')
                        ->addSelect(DB::raw("TRIM(CONCAT(per_doc.nombres, ' ', per_doc.apellido_paterno)) as docente"));
                } else {
                    $horarioQuery->addSelect(DB::raw('NULL as docente'));
                }

                $horario = $horarioQuery->get()
                    ->groupBy('dia')
                    ->map(fn ($items) => $items->values());
            }
        }

        $materias = collect();
        $notaMinima = ParametroAdmision::query()
            ->join('semestre', 'semestre.id_semestre', '=', 'parametro_admision.id_semestre')
            ->orderByDesc('semestre.nombre')
            ->value('nota_minima_aprobacion') ?? 60;

        if (Schema::hasTable('materia')) {
            $materiasBase = DB::table('materia')
                ->orderBy('nombre')
                ->select('id_materia', 'codigo', 'nombre')
                ->get();

            $notas = collect();

            if (Schema::hasTable('nota') && Schema::hasTable('examen')) {
                $notas = DB::table('nota as n')
                    ->join('examen as e', 'e.id_examen', '=', 'n.id_examen')
                    ->where('n.id_postulante', $postulanteId)
                    ->orderBy('e.numero_examen')
                    ->select('n.id_materia', 'n.nota', 'e.numero_examen', 'e.ponderacion')
                    ->get()
                    ->groupBy('id_materia');
            }

            $materias = $materiasBase->map(function ($materia) use ($notas, $notaMinima) {
                $detalleNotas = ($notas[$materia->id_materia] ?? collect())->values();
                $promedio = $detalleNotas->isNotEmpty() ? round((float) $detalleNotas->avg('nota'), 2) : null;

                return [
                    'codigo' => $materia->codigo,
                    'nombre' => $materia->nombre,
                    'notas' => $detalleNotas,
                    'promedio' => $promedio,
                    'estado' => $promedio === null ? 'Pendiente' : ($promedio >= (float) $notaMinima ? 'Aprobado' : 'En riesgo'),
                ];
            });
        }

        return [
            'postulante' => $postulante,
            'grupo' => $grupo,
            'horario' => $horario,
            'materias' => $materias,
            'notaMinima' => (float) $notaMinima,
        ];
    }

    private function docenteDashboard(int $docenteId): array
    {
        $docente = Docente::with('persona')->find($docenteId);
        $horario = collect();
        $grupos = collect();

        if (
            Schema::hasTable('grupo_horario')
            && Schema::hasTable('grupo')
            && Schema::hasTable('detalle_plantilla_horario')
        ) {
            $horarioQuery = DB::table('grupo_horario as gh')
                ->join('grupo as g', 'g.id_grupo', '=', 'gh.id_grupo')
                ->join('detalle_plantilla_horario as d', 'd.id_detalle', '=', 'gh.id_detalle')
                ->leftJoin('plantilla_horario as p', 'p.id_plantilla', '=', 'd.id_plantilla')
                ->leftJoin('semestre as s', 's.id_semestre', '=', 'g.id_semestre')
                ->where('gh.id_docente', $docenteId)
                ->orderBy('d.dia')
                ->orderBy('d.hora_inicio')
                ->select(
                    'g.id_grupo',
                    'g.nombre_grupo',
                    's.nombre as semestre',
                    'd.dia',
                    'd.hora_inicio',
                    'd.hora_fin',
                    'd.modalidad',
                    'p.turno',
                    'p.nombre as plantilla'
                );

            if (Schema::hasTable('aula')) {
                $horarioQuery->leftJoin('aula as a', 'a.id_aula', '=', 'gh.id_aula')
                    ->addSelect('a.nombre as aula');
            } else {
                $horarioQuery->addSelect(DB::raw('NULL as aula'));
            }

            $bloques = $horarioQuery->get();
            $horario = $bloques->groupBy('dia')->map(fn ($items) => $items->values());

            $studentCounts = collect();

            if (Schema::hasTable('postulante_grupo')) {
                $studentCounts = DB::table('postulante_grupo')
                    ->select('id_grupo', DB::raw('COUNT(*) as total'))
                    ->groupBy('id_grupo')
                    ->pluck('total', 'id_grupo');
            }

            $grupos = $bloques
                ->groupBy('id_grupo')
                ->map(function ($items, $groupId) use ($studentCounts) {
                    $first = $items->first();

                    return [
                        'id' => $groupId,
                        'nombre' => $first->nombre_grupo,
                        'semestre' => $first->semestre,
                        'turno' => $first->turno,
                        'bloques' => $items->count(),
                        'postulantes' => (int) ($studentCounts[$groupId] ?? 0),
                    ];
                })
                ->values();
        }

        return [
            'docente' => $docente,
            'horario' => $horario,
            'grupos' => $grupos,
        ];
    }
}
