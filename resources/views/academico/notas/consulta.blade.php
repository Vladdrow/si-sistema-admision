@extends('layouts.app')

@section('title', 'Consultar Notas')
@section('subtitle', 'Seleccione un grupo para ver las calificaciones de sus postulantes.')

@section('content')
    <div class="panel">
        <form method="GET" action="{{ route('notas.consulta') }}" class="reports-filter">
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
            <p class="dashboard-empty">No hay grupos registrados.</p>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:16px;">
                @foreach ($grupos as $grupo)
                    @php
                        $totalPostulantes = $grupo->postulanteGrupos()->count();
                        $notasRegistradas = \App\Models\Nota::whereIn('id_postulante', 
                            $grupo->postulanteGrupos()->pluck('id_postulante')
                        )->count();
                    @endphp
                    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:18px; display:flex; flex-direction:column; gap:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:start;">
                            <div>
                                <h3 style="margin:0; font-size:16px;">{{ $grupo->nombre_grupo }}</h3>
                                <span style="color:#64748b; font-size:13px;">{{ $grupo->semestre?->nombre ?? 'Sin semestre' }}</span>
                            </div>
                        </div>
                        <div style="display:flex; gap:16px;">
                            <div>
                                <span style="font-size:12px; color:#94a3b8;">Postulantes</span>
                                <strong style="display:block;">{{ $totalPostulantes }}</strong>
                            </div>
                            <div>
                                <span style="font-size:12px; color:#94a3b8;">Notas registradas</span>
                                <strong style="display:block;">{{ $notasRegistradas }}</strong>
                            </div>
                        </div>
                        <a href="{{ route('notas.consulta-grupo', $grupo->id_grupo) }}" class="button primary" style="width:100%; justify-content:center;">Ver notas</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
