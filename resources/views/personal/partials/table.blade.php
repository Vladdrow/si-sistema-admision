<x-data-table
    :columns="['Personal', 'Contacto', 'Rol', 'Cargo', 'Direccion', 'Acciones']"
    :paginator="$personal"
    empty="No se encontro personal administrativo."
>
    @foreach ($personal as $staff)
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
            data-rol="{{ $staff->persona?->credencial?->rol ?? 'Sin credencial' }}"
            data-cargo="{{ $staff->cargo }}"
            data-update-url="{{ route('personal.update', $staff->id_personal) }}"
            data-delete-url="{{ route('personal.destroy', $staff->id_personal) }}"
        >
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
                <span class="badge neutral">{{ $staff->persona?->credencial?->rol ?? 'Sin credencial' }}</span>
            </td>
            <td data-label="Cargo">{{ $staff->cargo }}</td>
            <td data-label="Direccion">{{ $staff->persona?->direccion ?? 'Sin direccion' }}</td>
            <td data-label="Acciones">
                <div class="actions">
                    <button class="secondary" type="button" data-person-action="edit">Modificar</button>
                    <button class="danger" type="button" data-person-action="delete">Eliminar</button>
                </div>
            </td>
        </tr>
    @endforeach
</x-data-table>
