<x-data-table
    :columns="['Grupo', 'Semestre', 'Estudiantes', 'Estado', 'Acciones']"
    :paginator="$grupos"
    empty="No se encontraron grupos."
>
    @foreach ($grupos as $grupo)
        @php
            $horarios = $horariosPorGrupo[$grupo->id_grupo] ?? collect();
        @endphp
        <tr class="grupo-row" data-grupo-id="{{ $grupo->id_grupo }}">
            <td data-label="Grupo"><strong>{{ $grupo->nombre_grupo }}</strong></td>
            <td data-label="Semestre">{{ $grupo->semestre?->nombre ?? 'Sin semestre' }}</td>
            <td data-label="Estudiantes">
                <span class="badge neutral">{{ $grupo->postulante_grupos_count ?? 0 }} estudiantes</span>
            </td>
            <td data-label="Estado">
                @if ($horarios->isNotEmpty())
                    <span class="badge ok">Horario asignado</span>
                @else
                    <span class="badge off">Sin horario</span>
                @endif
            </td>
            <td data-label="Acciones">
                <div class="actions">
                    @if ($horarios->isNotEmpty())
                        <button class="secondary" type="button" data-toggle-horario data-grupo-id="{{ $grupo->id_grupo }}">Ver horario</button>
                    @else
                        <span class="muted">Sin horario</span>
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
</x-data-table>

@foreach ($grupos as $grupo)
    @php
        $horarios = $horariosPorGrupo[$grupo->id_grupo] ?? collect();
        $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'];
        $materiaColors = ['#dbeafe', '#dcfce7', '#fef3c7', '#fce7f3', '#e0e7ff', '#ccfbf1', '#f5f5f4', '#ffedd5'];
    @endphp
    @if ($horarios->isNotEmpty())
        <div id="horario-{{ $grupo->id_grupo }}" style="display:none; background:#fff; border:1px solid #e2e8f0; border-radius:0 0 8px 8px; padding:16px; margin-top:-1px; margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h3 style="margin:0;">{{ $grupo->nombre_grupo }} — {{ $grupo->semestre?->nombre }}</h3>
                <button class="ghost" type="button" data-toggle-horario data-grupo-id="{{ $grupo->id_grupo }}">Ocultar horario</button>
            </div>
            <div class="week-planner">
                @foreach ($dias as $num => $nombre)
                    <div class="day-column">
                        <h4>{{ $nombre }}</h4>
                        @php $diaHorarios = $horarios->filter(fn($h) => (int) $h->detalle->dia === $num); @endphp
                        @forelse ($diaHorarios as $h)
                            @php $color = $h->detalle->id_materia ? $materiaColors[$h->detalle->id_materia % count($materiaColors)] : ''; @endphp
                            <div class="schedule-block" style="{{ $color ? "border-left:4px solid {$color}; background:{$color};" : '' }}">
                                <strong>{{ substr((string) $h->detalle->hora_inicio, 0, 5) }} - {{ substr((string) $h->detalle->hora_fin, 0, 5) }}</strong>
                                <span>{{ $h->detalle->materia?->nombre ?? 'Sin materia' }} / {{ $h->detalle->modalidad }}</span>
                                <small>{{ $h->docente?->persona?->nombre_completo ?? 'Sin docente' }} — {{ $h->aula?->nombre ?? 'Sin aula' }}</small>
                            </div>
                        @empty
                            <span class="muted">Sin clases</span>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
