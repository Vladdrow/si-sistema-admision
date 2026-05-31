<x-data-table
    :columns="['Registro', 'Persona', 'Rol', 'Estado', 'Ultimo acceso', 'Acciones']"
    :paginator="$credenciales"
    empty="No se encontraron credenciales."
>
            @foreach ($credenciales as $credencial)
                <tr
                    class="credential-row"
                    data-id="{{ $credencial->id_credencial }}"
                    data-registro="{{ $credencial->registro }}"
                    data-nombre="{{ $credencial->persona?->nombre_completo ?? 'Sin persona' }}"
                    data-ci="{{ $credencial->persona?->ci }}"
                    data-correo="{{ $credencial->persona?->correo }}"
                    data-rol="{{ $credencial->rol }}"
                    data-estado="{{ $credencial->estado ? '1' : '0' }}"
                    data-update-url="{{ route('credenciales.update', $credencial) }}"
                    data-delete-url="{{ route('credenciales.destroy', $credencial) }}"
                    data-restore-url="{{ route('credenciales.restore', $credencial) }}"
                >
                    <td data-label="Registro" data-cell="registro">{{ $credencial->registro }}</td>
                    <td data-label="Persona">
                        <span class="person-line">
                            <strong data-cell="nombre">{{ $credencial->persona?->nombre_completo ?? 'Sin persona' }}</strong>
                            <span class="muted" data-cell="ci">{{ $credencial->persona?->ci }}</span>
                            <span class="email-line" data-cell="correo">{{ $credencial->persona?->correo }}</span>
                        </span>
                    </td>
                    <td data-label="Rol" data-cell="rol">{{ $credencial->rol }}</td>
                    <td data-label="Estado">
                        <span class="badge {{ $credencial->estado ? 'ok' : 'off' }}" data-cell="estado">
                            {{ $credencial->estado ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td data-label="Ultimo acceso" data-cell="ultimo_acceso">{{ $credencial->fecha_ultimo_acceso?->format('d/m/Y H:i') ?? 'Sin acceso' }}</td>
                    <td data-label="Acciones">
                        <div class="actions">
                            <button class="secondary" type="button" data-action="edit">Modificar</button>
                            @if ($credencial->estado)
                                <button class="danger" type="button" data-action="delete">Eliminar</button>
                            @else
                                <button class="success" type="button" data-action="restore">Restaurar</button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
</x-data-table>
