@extends('layouts.app')

@section('title', "Grupo: {$grupo->nombre_grupo}")
@section('subtitle', "Semestre: {$grupo->semestre?->nombre} — {$grupo->postulanteGrupos->count()} estudiantes asignados.")

@section('content')
    <div class="panel">
        <a href="{{ route('grupos.index') }}" class="button secondary" style="margin-bottom:16px;">Volver a grupos</a>

        @if ($grupo->postulanteGrupos->isEmpty())
            <p class="dashboard-empty">No hay estudiantes asignados a este grupo.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Registro</th>
                        <th>Estudiante</th>
                        <th>CI</th>
                        <th>Correo</th>
                        <th>Fecha asignacion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grupo->postulanteGrupos as $pg)
                        @php $p = $pg->postulante; @endphp
                        <tr>
                            <td>{{ $p?->persona?->credencial?->registro ?? 'Sin registro' }}</td>
                            <td><strong>{{ $p?->persona?->nombre_completo ?? 'Sin nombre' }}</strong></td>
                            <td>{{ $p?->persona?->ci }}</td>
                            <td>{{ $p?->persona?->correo }}</td>
                            <td>{{ $pg->fecha_asignacion?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($grupo->grupoHorarios->isNotEmpty())
            <h3 style="margin-top:24px;">Horario asignado</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Dia</th>
                        <th>Hora</th>
                        <th>Materia</th>
                        <th>Modalidad</th>
                        <th>Docente</th>
                        <th>Aula</th>
                    </tr>
                </thead>
                <tbody>
                    @php $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo']; @endphp
                    @foreach ($grupo->grupoHorarios->sortBy('detalle.dia') as $gh)
                        <tr>
                            <td>{{ $dias[$gh->detalle->dia] ?? $gh->detalle->dia }}</td>
                            <td>{{ substr((string) $gh->detalle->hora_inicio, 0, 5) }} - {{ substr((string) $gh->detalle->hora_fin, 0, 5) }}</td>
                            <td>{{ $gh->detalle->materia?->nombre ?? 'Sin materia' }}</td>
                            <td>{{ $gh->detalle->modalidad }}</td>
                            <td>{{ $gh->docente?->persona?->nombre_completo ?? 'Sin docente' }}</td>
                            <td>{{ $gh->aula?->nombre ?? 'Sin aula' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
