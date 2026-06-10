<x-data-table
    :columns="['Orden', 'Postulante', 'Contacto', 'Monto', 'Fecha', 'Estado', 'Transaccion', 'Acciones']"
    :paginator="$pagos"
    empty="No se encontraron pagos."
>
    @foreach ($pagos as $pago)
        @php
            $postulante = $pago->postulante;
        @endphp
        <tr
            class="pago-row"
            data-id="{{ $pago->id_pago }}"
            data-codigo-orden="{{ $pago->codigo_orden }}"
            data-monto="{{ $pago->monto }}"
            data-fecha-pago="{{ $pago->fecha_pago?->format('d/m/Y H:i') }}"
            data-estado="{{ $pago->estado }}"
            data-numero-transaccion="{{ $pago->numero_transaccion }}"
            data-metodo-pago="{{ $pago->metodo_pago }}"
            data-mensaje-error="{{ $pago->mensaje_error }}"
            data-postulante="{{ $postulante?->persona?->nombre_completo }}"
            data-postulante-ci="{{ $postulante?->persona?->ci }}"
            data-postulante-registro="{{ $postulante?->persona?->credencial?->registro }}"
            data-postulante-libreta="{{ $postulante?->codigo_libreta }}"
            data-postulante-titulo="{{ $postulante?->codigo_titulo }}"
            data-comprobante-url="{{ route('pagos.comprobante', $pago->id_pago) }}"
        >
            <td data-label="Orden"><strong>{{ $pago->codigo_orden }}</strong></td>
            <td data-label="Postulante">
                <span class="person-line">
                    <strong>{{ $postulante?->persona?->nombre_completo ?? 'Sin nombre' }}</strong>
                    <span class="muted">CI {{ $postulante?->persona?->ci }}</span>
                </span>
            </td>
            <td data-label="Contacto">
                <span class="person-line">
                    <span>{{ $postulante?->persona?->correo }}</span>
                    <span class="muted">{{ $postulante?->persona?->credencial?->registro ?? 'Sin registro' }}</span>
                </span>
            </td>
            <td data-label="Monto">Bs {{ number_format((float) $pago->monto, 2) }}</td>
            <td data-label="Fecha">{{ $pago->fecha_pago?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
            <td data-label="Estado">
                <span class="badge {{ match($pago->estado) { 'Pagado' => 'ok', 'Rechazado', 'Expirado' => 'off', default => 'neutral' } }}">
                    {{ $pago->estado }}
                </span>
            </td>
            <td data-label="Transaccion">
                <span class="muted">{{ $pago->numero_transaccion ?? 'Sin transaccion' }}</span>
            </td>
            <td data-label="Acciones">
                <div class="actions">
                    <button class="ghost" type="button" data-pago-action="detail">Ver detalle</button>
                    <a href="{{ route('pagos.comprobante', $pago->id_pago) }}" class="button secondary" target="_blank">Comprobante</a>
                </div>
            </td>
        </tr>
    @endforeach
</x-data-table>
