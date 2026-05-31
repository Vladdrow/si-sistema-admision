<x-data-table
    :columns="['Fecha', 'Usuario', 'Accion', 'Modulo', 'Descripcion', 'IP']"
    :paginator="$registros"
    empty="No se encontraron registros de bitacora."
>
            @foreach ($registros as $registro)
                <tr>
                    <td data-label="Fecha">{{ $registro->fecha_hora?->format('d/m/Y H:i:s') }}</td>
                    <td data-label="Usuario">
                        <span class="person-line">
                            <strong>{{ $registro->persona?->nombre_completo ?? 'Sin usuario' }}</strong>
                            <span class="muted">{{ $registro->persona?->ci }}</span>
                        </span>
                    </td>
                    <td data-label="Accion">
                        <span class="badge neutral">{{ $registro->accion }}</span>
                    </td>
                    <td data-label="Modulo">{{ $registro->modulo }}</td>
                    <td data-label="Descripcion">{{ $registro->descripcion ?? 'Sin descripcion' }}</td>
                    <td data-label="IP">{{ $registro->ip_origen ?? 'Sin IP' }}</td>
                </tr>
            @endforeach
</x-data-table>
