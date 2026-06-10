<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;

use App\Models\Grupo;
use App\Models\Semestre;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU11 - Consultar Horario.
 *
 * Permite visualizar horarios segun el rol: postulante ve su grupo,
 * docente ve sus asignaciones, y admin/personal ve todos los grupos.
 */
class HorarioController extends Controller
{
    public function index(Request $request): View
    {
        // CU11 Detalle: decide el flujo de consulta de horario segun rol.
        $user = auth()->user();
        $rol = $user?->rol;

        if ($rol === 'Postulante') {
            return $this->horarioPostulante($user);
        }

        if ($rol === 'Docente') {
            return $this->horarioDocente($user);
        }

        return $this->horarioAdmin($request);
    }

    private function horarioPostulante($user): View
    {
        // CU11 Detalle, actor Postulante: identifica su grupo y carga
        // el horario completo de ese grupo.
        $postulante = \App\Models\Postulante::with(['persona', 'postulanteGrupo.grupo.semestre'])->find($user->id_persona);
        $grupo = ($postulante?->postulanteGrupo ?? collect())
            ->sortByDesc(fn ($item) => strtolower((string) $item->grupo?->semestre?->estado) === 'activo' ? 1 : 0)
            ->first()?->grupo;

        if (! $grupo) {
            return view('academico.horarios.index', ['postulante' => $postulante, 'grupo' => null, 'horarios' => collect(), 'grupos' => collect()]);
        }

        $horarios = $this->cargarHorariosGrupo($grupo);

        return view('academico.horarios.index', [
            'postulante' => $postulante,
            'grupo' => $grupo,
            'horarios' => $horarios,
            'grupos' => collect(),
        ]);
    }

    private function horarioDocente($user): View
    {
        // CU11 Detalle, actor Docente: obtiene los grupos donde el docente
        // tiene asignaciones horarias en el semestre seleccionado.
        $semestres = Semestre::orderByDesc('nombre')->get();
        $semestreId = $this->semestreSeleccionado(request(), $semestres);

        $gruposIds = \App\Models\GrupoHorario::where('id_docente', $user->id_persona)
            ->distinct('id_grupo')
            ->pluck('id_grupo');

        $grupos = Grupo::with('semestre')
            ->whereIn('id_grupo', $gruposIds)
            ->where('id_semestre', $semestreId)
            ->orderBy('id_grupo')
            ->get();
        $horariosPorGrupo = [];

        foreach ($grupos as $grupo) {
            $horariosPorGrupo[$grupo->id_grupo] = $this->cargarHorariosGrupo($grupo);
        }

        return view('academico.horarios.index', [
            'docente' => \App\Models\Docente::with('persona')->find($user->id_persona),
            'grupos' => $grupos,
            'horariosPorGrupo' => $horariosPorGrupo,
            'horarios' => collect(),
            'semestres' => $semestres,
            'semestreId' => $semestreId,
        ]);
    }

    private function horarioAdmin(Request $request): View
    {
        // CU11 Detalle, actor Administrativo/Administrador: lista grupos
        // y permite ver el horario de cada grupo.
        $isAsync = $request->ajax() || $request->expectsJson();
        $search = trim((string) $request->query('buscar', ''));
        $status = (string) $request->query('estado', '');
        $semestres = Semestre::orderByDesc('nombre')->get();
        $semestreId = $this->semestreSeleccionado($request, $semestres);

        if (! in_array($status, ['con', 'sin', ''], true)) {
            $status = '';
        }

        $grupos = Grupo::with('semestre')
            ->withCount('postulanteGrupos')
            ->withCount('grupoHorarios')
            ->where('id_semestre', $semestreId)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('nombre_grupo', 'like', "%{$search}%")
                    ->orWhereHas('semestre', fn ($q) => $q->where('nombre', 'like', "%{$search}%"));
            })
            ->when($status === 'con', fn ($q) => $q->having('grupo_horarios_count', '>', 0))
            ->when($status === 'sin', fn ($q) => $q->having('grupo_horarios_count', '=', 0))
            ->orderBy('id_grupo')
            ->paginate(15)
            ->withQueryString();

        $horariosPorGrupo = [];

        foreach ($grupos as $grupo) {
            $horariosPorGrupo[$grupo->id_grupo] = $this->cargarHorariosGrupo($grupo);
        }

        if ($isAsync) {
            return view('academico.horarios.partials.table', compact('grupos', 'horariosPorGrupo'));
        }

        return view('academico.horarios.index', [
            'grupos' => $grupos,
            'horariosPorGrupo' => $horariosPorGrupo,
            'horarios' => collect(),
            'search' => $search,
            'status' => $status,
            'semestres' => $semestres,
            'semestreId' => $semestreId,
        ]);
    }

    private function cargarHorariosGrupo(Grupo $grupo): \Illuminate\Support\Collection
    {
        // CU11 Detalle, salida: dias, horas, materia, modalidad,
        // docente y aula ordenados para la vista.
        $grupo->loadMissing(['grupoHorarios.detalle.materia', 'grupoHorarios.docente.persona', 'grupoHorarios.aula']);

        return $grupo->grupoHorarios->sortBy('detalle.dia')->values();
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
