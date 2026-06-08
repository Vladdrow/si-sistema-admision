<x-data-table
    :columns="['Registro', 'Postulante', 'Contacto', 'Est. Adm.', 'Codigos', 'Carreras', 'Acceso', 'Acciones']"
    :paginator="$postulantes"
    empty="No se encontraron postulantes."
>
    @foreach ($postulantes as $postulante)
        @php
            $credencial = $postulante->persona?->credencial;
            $activo = (bool) ($credencial?->estado ?? false);
        @endphp
        <tr
            class="applicant-row"
            data-id="{{ $postulante->id_postulante }}"
            data-ci="{{ $postulante->persona?->ci }}"
            data-nombres="{{ $postulante->persona?->nombres }}"
            data-apellido-paterno="{{ $postulante->persona?->apellido_paterno }}"
            data-apellido-materno="{{ $postulante->persona?->apellido_materno }}"
            data-fecha-nacimiento="{{ $postulante->persona?->fecha_nacimiento?->format('Y-m-d') }}"
            data-sexo="{{ $postulante->persona?->sexo }}"
            data-direccion="{{ $postulante->persona?->direccion }}"
            data-telefono="{{ $postulante->persona?->telefono }}"
            data-correo="{{ $postulante->persona?->correo }}"
            data-colegio-procedencia="{{ $postulante->colegio_procedencia }}"
            data-ciudad="{{ $postulante->ciudad }}"
            data-estado-admision="{{ $postulante->estado_admision }}"
            data-codigo-libreta="{{ $postulante->codigo_libreta }}"
            data-codigo-titulo="{{ $postulante->codigo_titulo }}"
            data-id-carrera-primera-opc="{{ $postulante->id_carrera_primera_opc }}"
            data-id-carrera-segunda-opc="{{ $postulante->id_carrera_segunda_opc }}"
            data-id-carrera-admitido="{{ $postulante->id_carrera_admitido }}"
            data-update-url="{{ route('postulantes.update', $postulante->id_postulante) }}"
            data-delete-url="{{ route('postulantes.destroy', $postulante->id_postulante) }}"
            data-restore-url="{{ route('postulantes.restore', $postulante->id_postulante) }}"
        >
            <td data-label="Registro">
                <span class="muted">{{ $credencial?->registro ?? 'Sin registro' }}</span>
            </td>
            <td data-label="Postulante">
                <span class="person-line">
                    <strong>{{ $postulante->persona?->nombre_completo ?? 'Sin nombre' }}</strong>
                    <span class="muted">CI {{ $postulante->persona?->ci }}</span>
                </span>
            </td>
            <td data-label="Contacto">
                <span class="person-line">
                    <span>{{ $postulante->persona?->correo }}</span>
                    <span class="muted">{{ $postulante->ciudad ?? 'Sin ciudad' }}</span>
                </span>
            </td>
            <td data-label="Est. Adm."><span class="badge neutral">{{ $postulante->estado_admision }}</span></td>
            <td data-label="Codigos">
                <span class="person-line">
                    <span>Libreta: {{ $postulante->codigo_libreta }}</span>
                    <span class="muted">Titulo: {{ $postulante->codigo_titulo }}</span>
                </span>
            </td>
            <td data-label="Carreras">
                <span class="person-line">
                    <span>{{ $postulante->carreraPrimera?->nombre ?? 'Primera opcion sin definir' }}</span>
                    <span class="muted">{{ $postulante->carreraSegunda?->nombre ?? 'Segunda opcion sin definir' }}</span>
                </span>
            </td>
            <td data-label="Acceso">
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
