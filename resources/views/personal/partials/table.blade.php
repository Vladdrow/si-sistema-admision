<x-data-table
    :columns="['Registro', 'Personal', 'Contacto', 'Rol', 'Cargo', 'Direccion', 'Estado', 'Acciones']"
    :paginator="$personal"
    empty="No se encontro personal administrativo."
>
    @foreach ($personal as $staff)
        @php
            $credencial = $staff->persona?->credencial;
            $activo = (bool) ($credencial?->estado ?? false);
        @endphp
        <tr
            class="staff-row"
            data-id="{{ $staff->id_personal }}"
            data-ci="{{ $staff->persona?->ci }}"
            data-nombres="{{ $staff->persona?->nombres }}"
            data-apellido-paterno="{{ $staff->persona?->apellido_paterno }}"
            data-apellido-materno="{{ $staff->persona?->apellido_materno }}"
            data-fecha-nacimiento="{{ $staff->persona?->fecha_nacimiento?->format('Y-m-d') }}"
            data-sexo="{{ $staff->persona?->sexo }}"
            data-direccion="{{ $staff->persona?->direccion }}"
            data-telefono="{{ $staff->persona?->telefono }}"
            data-correo="{{ $staff->persona?->correo }}"
            data-rol="{{ $credencial?->rol ?? 'Sin credencial' }}"
            data-cargo="{{ $staff->cargo }}"
            data-update-url="{{ route('personal.update', $staff->id_personal) }}"
            data-delete-url="{{ route('personal.destroy', $staff->id_personal) }}"
            data-restore-url="{{ route('personal.restore', $staff->id_personal) }}"
        >
            <td data-label="Registro">
                <span class="muted">{{ $credencial?->registro ?? 'Sin registro' }}</span>
            </td>
            <td data-label="Personal">
                <span class="person-line">
                    <strong>{{ $staff->persona?->nombre_completo ?? 'Sin nombre' }}</strong>
                    <span class="muted">CI {{ $staff->persona?->ci }}</span>
                </span>
            </td>
            <td data-label="Contacto">
                <span class="person-line">
                    <span>{{ $staff->persona?->correo }}</span>
                    <span class="muted">{{ $staff->persona?->telefono ?? 'Sin telefono' }}</span>
                </span>
            </td>
            <td data-label="Rol">
                <span class="badge neutral">{{ $credencial?->rol ?? 'Sin credencial' }}</span>
            </td>
            <td data-label="Cargo">{{ $staff->cargo }}</td>
            <td data-label="Direccion">{{ $staff->persona?->direccion ?? 'Sin direccion' }}</td>
            <td data-label="Estado">
                <span class="badge {{ $activo ? 'ok' : 'off' }}">{{ $activo ? 'Activo' : 'Inactivo' }}</span>
            </td>
            <td data-label="Acciones">
                <div class="actions">
                    <button class="secondary" type="button" data-person-action="edit">Modificar</button>
                    @if ($activo)
                        <button class="danger" type="button" data-person-action="delete">Desactivar</button>
                    @else
                        <button class="secondary" type="button" data-person-action="restore">Restaurar</button>
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
</x-data-table>
