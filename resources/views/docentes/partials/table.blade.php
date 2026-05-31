<x-data-table
    :columns="['Docente', 'Contacto', 'Titulo', 'Codigo RDA', 'Formacion', 'Acciones']"
    :paginator="$docentes"
    empty="No se encontraron docentes."
>
            @foreach ($docentes as $docente)
                <tr
                    class="teacher-row"
                    data-id="{{ $docente->id_docente }}"
                    data-ci="{{ $docente->persona?->ci }}"
                    data-nombres="{{ $docente->persona?->nombres }}"
                    data-apellido-paterno="{{ $docente->persona?->apellido_paterno }}"
                    data-apellido-materno="{{ $docente->persona?->apellido_materno }}"
                    data-fecha-nacimiento="{{ $docente->persona?->fecha_nacimiento?->format('Y-m-d') }}"
                    data-sexo="{{ $docente->persona?->sexo }}"
                    data-direccion="{{ $docente->persona?->direccion }}"
                    data-telefono="{{ $docente->persona?->telefono }}"
                    data-correo="{{ $docente->persona?->correo }}"
                    data-titulo-profesional="{{ $docente->titulo_profesional }}"
                    data-codigo-rda="{{ $docente->codigo_rda }}"
                    data-tiene-maestria="{{ $docente->tiene_maestria ? '1' : '0' }}"
                    data-tiene-diplomado="{{ $docente->tiene_diplomado ? '1' : '0' }}"
                    data-update-url="{{ route('docentes.update', $docente->id_docente) }}"
                    data-delete-url="{{ route('docentes.destroy', $docente->id_docente) }}"
                >
                    <td data-label="Docente">
                        <span class="person-line">
                            <strong>{{ $docente->persona?->nombre_completo ?? 'Sin nombre' }}</strong>
                            <span class="muted">CI {{ $docente->persona?->ci }}</span>
                        </span>
                    </td>
                    <td data-label="Contacto">
                        <span class="person-line">
                            <span>{{ $docente->persona?->correo }}</span>
                            <span class="muted">{{ $docente->persona?->telefono ?? 'Sin telefono' }}</span>
                        </span>
                    </td>
                    <td data-label="Titulo">{{ $docente->titulo_profesional }}</td>
                    <td data-label="Codigo RDA">{{ $docente->codigo_rda }}</td>
                    <td data-label="Formacion">
                        <div class="actions">
                            @if ($docente->tiene_maestria)
                                <span class="badge ok">Maestria</span>
                            @endif
                            @if ($docente->tiene_diplomado)
                                <span class="badge neutral">Diplomado</span>
                            @endif
                            @if (! $docente->tiene_maestria && ! $docente->tiene_diplomado)
                                <span class="muted">Sin posgrado</span>
                            @endif
                        </div>
                    </td>
                    <td data-label="Acciones">
                        <div class="actions">
                            <button class="secondary" type="button" data-teacher-action="edit">Modificar</button>
                            <button class="danger" type="button" data-teacher-action="delete">Eliminar</button>
                        </div>
                    </td>
                </tr>
            @endforeach
</x-data-table>
