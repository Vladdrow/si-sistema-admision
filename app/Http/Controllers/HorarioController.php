<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
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
        $user = auth()->user();
        $rol = $user?->rol;

        if ($rol === 'Postulante') {
            return $this->horarioPostulante($user);
        }

        if ($rol === 'Docente') {
            return $this->horarioDocente($user);
        }

        return $this->horarioAdmin();
    }

    private function horarioPostulante($user): View
    {
        $postulante = \App\Models\Postulante::with(['persona', 'postulanteGrupo.grupo'])->find($user->id_persona);
        $grupo = $postulante?->postulanteGrupo->first()?->grupo;

        if (! $grupo) {
            return view('horarios.index', ['postulante' => $postulante, 'grupo' => null, 'horarios' => collect(), 'grupos' => collect()]);
        }

        $horarios = $this->cargarHorariosGrupo($grupo);

        return view('horarios.index', [
            'postulante' => $postulante,
            'grupo' => $grupo,
            'horarios' => $horarios,
            'grupos' => collect(),
        ]);
    }

    private function horarioDocente($user): View
    {
        $gruposIds = \App\Models\GrupoHorario::where('id_docente', $user->id_persona)
            ->distinct('id_grupo')
            ->pluck('id_grupo');

        $grupos = Grupo::with('semestre')->whereIn('id_grupo', $gruposIds)->get();
        $horariosPorGrupo = [];

        foreach ($grupos as $grupo) {
            $horariosPorGrupo[$grupo->id_grupo] = $this->cargarHorariosGrupo($grupo);
        }

        return view('horarios.index', [
            'docente' => \App\Models\Docente::with('persona')->find($user->id_persona),
            'grupos' => $grupos,
            'horariosPorGrupo' => $horariosPorGrupo,
            'horarios' => collect(),
        ]);
    }

    private function horarioAdmin(): View
    {
        $isAsync = false;
        $search = '';
        $status = '';

        if (request()->ajax() || request()->expectsJson()) {
            $isAsync = true;
            $search = trim((string) request()->query('buscar', ''));
            $status = (string) request()->query('estado', '');
        }

        if (! in_array($status, ['con', 'sin', ''], true)) {
            $status = '';
        }

        $grupos = Grupo::with('semestre')
            ->withCount('postulanteGrupos')
            ->withCount('grupoHorarios')
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
            return view('horarios.partials.table', compact('grupos', 'horariosPorGrupo'));
        }

        return view('horarios.index', [
            'grupos' => $grupos,
            'horariosPorGrupo' => $horariosPorGrupo,
            'horarios' => collect(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    private function cargarHorariosGrupo(Grupo $grupo): \Illuminate\Support\Collection
    {
        $grupo->loadMissing(['grupoHorarios.detalle.materia', 'grupoHorarios.docente.persona', 'grupoHorarios.aula']);

        return $grupo->grupoHorarios->sortBy('detalle.dia')->values();
    }
}
