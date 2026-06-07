<x-data-table
    :columns="['Grupo', 'Semestre', 'Estudiantes', 'Horario', 'Acciones']"
    :paginator="$grupos"
    empty="No se encontraron grupos."
>
    @foreach ($grupos as $grupo)
        <tr>
            <td data-label="Grupo"><strong>{{ $grupo->nombre_grupo }}</strong></td>
            <td data-label="Semestre">{{ $grupo->semestre?->nombre ?? 'Sin semestre' }}</td>
            <td data-label="Estudiantes">
                <span class="badge {{ $grupo->postulante_grupos_count > 0 ? 'ok' : 'neutral' }}">
                    {{ $grupo->postulante_grupos_count }} asignados
                </span>
            </td>
            <td data-label="Horario">
                @if ($grupo->grupoHorarios()->exists())
                    <span class="badge ok">Asignado</span>
                @else
                    <span class="muted">Sin horario</span>
                @endif
            </td>
            <td data-label="Acciones">
                <div class="actions">
                    <a href="{{ route('grupos.show', $grupo->id_grupo) }}" class="button secondary">Ver estudiantes</a>
                    <a href="{{ route('grupos.asignar-horario', $grupo->id_grupo) }}" class="button secondary">Asignar horario</a>
                </div>
            </td>
        </tr>
    @endforeach
</x-data-table>
