@extends('layouts.app')

@section('title', 'Gestionar Notas')
@section('subtitle', 'Seleccione un grupo asignado para registrar o modificar calificaciones.')

@section('content')
    <div class="panel">
        <form method="GET" action="{{ route('notas.index') }}" class="reports-filter">
            <div>
                <label for="semestre">Semestre</label>
                <select id="semestre" name="semestre" onchange="this.form.submit()">
                    @foreach ($semestres as $semestre)
                        <option value="{{ $semestre->id_semestre }}" @selected((int) $semestreId === (int) $semestre->id_semestre)>
                            {{ $semestre->nombre }} - {{ $semestre->estado }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        @if ($grupos->isEmpty())
            <p class="dashboard-empty">No tiene grupos asignados en el semestre actual.</p>
        @else
            <div class="notes-card-grid">
                @foreach ($grupos as $grupo)
                    @php
                        $materiasGrupo = \App\Models\GrupoHorario::where('id_docente', auth()->user()->id_persona)
                            ->where('id_grupo', $grupo->id_grupo)
                            ->with('detalle.materia')
                            ->get()
                            ->map(fn ($gh) => $gh->detalle->materia)
                            ->filter()
                            ->unique('id_materia');
                    @endphp
                    <article class="notes-group-card">
                        <div class="notes-card-header">
                            <div>
                                <h3>{{ $grupo->nombre_grupo }}</h3>
                                <span>{{ $grupo->semestre?->nombre ?? 'Sin semestre' }}</span>
                            </div>
                            <span class="badge neutral">{{ $materiasGrupo->count() }} {{ $materiasGrupo->count() === 1 ? 'materia' : 'materias' }}</span>
                        </div>

                        <div class="notes-chip-list">
                            @forelse ($materiasGrupo as $materia)
                                <span>{{ $materia->nombre }}</span>
                            @empty
                                <span class="muted">Sin materias asignadas</span>
                            @endforelse
                        </div>

                        <a href="{{ route('notas.show', $grupo->id_grupo) }}" class="button primary notes-card-action">Gestionar notas</a>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection
