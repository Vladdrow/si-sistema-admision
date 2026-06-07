@php
    $days = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'];
@endphp

<x-data-table
    :columns="['Plantilla', 'Turno', 'Detalles', 'Resumen', 'Acciones']"
    :paginator="$plantillas"
    empty="No se encontraron plantillas."
>
    @foreach ($plantillas as $plantilla)
        @php
            $detailsJson = $plantilla->detalles->map(fn ($detalle) => [
                'dia' => $detalle->dia,
                'hora_inicio' => substr((string) $detalle->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $detalle->hora_fin, 0, 5),
                'id_materia' => $detalle->id_materia,
                'materia_nombre' => $detalle->materia?->nombre,
                'modalidad' => $detalle->modalidad,
            ])->values()->toJson();
        @endphp
        <tr
            class="template-row"
            data-id="{{ $plantilla->id_plantilla }}"
            data-nombre="{{ $plantilla->nombre }}"
            data-turno="{{ $plantilla->turno }}"
            data-detalles='{{ $detailsJson }}'
            data-update-url="{{ route('plantillas.update', $plantilla->id_plantilla) }}"
            data-delete-url="{{ route('plantillas.destroy', $plantilla->id_plantilla) }}"
        >
            <td data-label="Plantilla"><strong>{{ $plantilla->nombre }}</strong></td>
            <td data-label="Turno"><span class="badge neutral">{{ $plantilla->turno }}</span></td>
            <td data-label="Detalles">{{ $plantilla->detalles_count }} bloques</td>
            <td data-label="Resumen">
                <span class="person-line">
                    @forelse ($plantilla->detalles->take(2) as $detalle)
                        <span>{{ $days[$detalle->dia] ?? $detalle->dia }} {{ substr((string) $detalle->hora_inicio, 0, 5) }}-{{ substr((string) $detalle->hora_fin, 0, 5) }} {{ $detalle->materia?->nombre ?? 'Sin materia' }} / {{ $detalle->modalidad }}</span>
                    @empty
                        <span class="muted">Sin bloques definidos</span>
                    @endforelse
                </span>
            </td>
            <td data-label="Acciones">
                <div class="actions">
                    <button class="secondary" type="button" data-template-action="edit">Modificar</button>
                    <button class="danger" type="button" data-template-action="delete">Eliminar</button>
                </div>
            </td>
        </tr>
    @endforeach
</x-data-table>
