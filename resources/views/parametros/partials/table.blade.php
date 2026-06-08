<x-data-table
    :columns="['Semestre', 'Inscripcion', 'Notas', 'Pago', 'Reglas', 'Acciones']"
    :paginator="$parametros"
    empty="No se encontraron parametros."
>
    @foreach ($parametros as $parametro)
        @php
            $cuposJson = $parametro->cuposCarrera->map(fn ($cupo) => [
                'id_carrera' => $cupo->id_carrera,
                'cantidad_cupos' => $cupo->cantidad_cupos,
                'cantidad_estudiantes' => $cupo->cantidad_estudiantes,
            ])->values()->toJson();

            $ponderacionesJson = $parametro->examenes->map(fn ($examen) => [
                'numero_examen' => $examen->numero_examen,
                'ponderacion' => $examen->ponderacion,
            ])->values()->toJson();

            $cerrado = $parametro->fecha_cierre_inscripcion && now()->gt($parametro->fecha_cierre_inscripcion);
        @endphp
        <tr
            class="parameter-row"
            data-id="{{ $parametro->id_parametro }}"
            data-id-semestre="{{ $parametro->id_semestre }}"
            data-semestre-nombre="{{ $parametro->semestre?->nombre }}"
            data-fecha-inicio-inscripcion="{{ $parametro->fecha_inicio_inscripcion?->format('Y-m-d\TH:i') }}"
            data-fecha-cierre-inscripcion="{{ $parametro->fecha_cierre_inscripcion?->format('Y-m-d\TH:i') }}"
            data-fecha-cierre-notas="{{ $parametro->fecha_cierre_notas?->format('Y-m-d\TH:i') }}"
            data-monto-pago="{{ $parametro->monto_pago }}"
            data-max-estudiante-grupo="{{ $parametro->max_estudiante_grupo }}"
            data-nota-minima-aprobacion="{{ $parametro->nota_minima_aprobacion }}"
            data-max-grupos-docente="{{ $parametro->max_grupos_docente }}"
            data-tiempo-expiracion-pago="{{ $parametro->tiempo_expiracion_pago }}"
            data-cupos="{{ $cuposJson }}"
            data-ponderaciones="{{ $ponderacionesJson }}"
            data-update-url="{{ route('parametros.update', $parametro->id_parametro) }}"
            data-delete-url="{{ route('parametros.destroy', $parametro->id_parametro) }}"
        >
            <td data-label="Semestre"><strong>{{ $parametro->semestre?->nombre }}</strong></td>
            <td data-label="Inscripcion">
                <span class="person-line">
                    <span>{{ $parametro->fecha_inicio_inscripcion?->format('d/m/Y H:i') }}</span>
                    <span class="muted">{{ $parametro->fecha_cierre_inscripcion?->format('d/m/Y H:i') }}</span>
                </span>
            </td>
            <td data-label="Notas">{{ $parametro->fecha_cierre_notas?->format('d/m/Y H:i') ?? 'Sin cierre' }}</td>
            <td data-label="Pago">Bs {{ $parametro->monto_pago }} / {{ $parametro->tiempo_expiracion_pago }} min</td>
            <td data-label="Reglas">
                <span class="person-line">
                    <span>{{ $parametro->max_estudiante_grupo }} estudiantes/grupo</span>
                    <span class="muted">{{ $parametro->max_grupos_docente }} grupos/docente, nota {{ $parametro->nota_minima_aprobacion }}</span>
                    <span class="muted">{{ $parametro->cuposCarrera->sum('cantidad_cupos') }} cupos, ponderacion {{ $parametro->examenes->sum('ponderacion') }}%</span>
                </span>
            </td>
            <td data-label="Acciones">
                <div class="actions">
                    <button class="ghost" type="button" data-simple-action="view">Consultar</button>
                    @if ($cerrado)
                        <span class="badge off">Cerrado</span>
                    @else
                        <button class="secondary" type="button" data-simple-action="edit">Modificar</button>
                        <button class="danger" type="button" data-simple-action="delete">Eliminar</button>
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
</x-data-table>
