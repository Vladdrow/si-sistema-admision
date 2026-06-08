<x-data-table
    :columns="['Registro', 'Docente', 'Contacto', 'Titulo', 'Materias', 'Formacion', 'Estado', 'Acciones']"
    :paginator="$docentes"
    empty="No se encontraron docentes."
>
            @foreach ($docentes as $docente)
                @php
                    $credencial = $docente->persona?->credencial;
                    $activo = (bool) ($credencial?->estado ?? false);
                @endphp
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
                    data-materias-habilitadas="{{ $docente->materiasHabilitadas->pluck('id_materia')->implode(',') }}"
                    data-certificacion-institucion="{{ $docente->certificaciones->first()?->institucion }}"
                    data-certificacion-nivel="{{ $docente->certificaciones->first()?->nivel }}"
                    data-update-url="{{ route('docentes.update', $docente->id_docente) }}"
                    data-delete-url="{{ route('docentes.destroy', $docente->id_docente) }}"
                    data-restore-url="{{ route('docentes.restore', $docente->id_docente) }}"
                >
                    <td data-label="Registro">
                        <span class="muted">{{ $credencial?->registro ?? 'Sin registro' }}</span>
                    </td>
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
                    <td data-label="Materias">
                        <span class="person-line">
                            <span>{{ $docente->materiasHabilitadas->pluck('nombre')->join(', ') ?: 'Sin materias' }}</span>
                            <span class="muted">RDA {{ $docente->codigo_rda }}</span>
                        </span>
                    </td>
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
                            @if ($docente->certificaciones->isNotEmpty())
                                <span class="badge neutral">{{ $docente->certificaciones->first()->nivel ?? 'Cert.' }}</span>
                            @endif
                        </div>
                    </td>
                    <td data-label="Estado">
                        <span class="badge {{ $activo ? 'ok' : 'off' }}">{{ $activo ? 'Activo' : 'Inactivo' }}</span>
                    </td>
                    <td data-label="Acciones">
                        <div class="actions">
                            @if ($activo)
                                <button class="secondary" type="button" data-teacher-action="edit">Modificar</button>
                                <button class="danger" type="button" data-teacher-action="delete">Desactivar</button>
                            @else
                                <button class="secondary" type="button" data-teacher-action="restore">Restaurar</button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
</x-data-table>
