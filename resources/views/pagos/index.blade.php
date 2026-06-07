@extends('layouts.app')

@section('title', 'Consultar Pagos')
@section('subtitle', 'Visualiza, filtra y consulta los pagos realizados por los postulantes.')

@section('content')
    <div class="panel">
        <div id="page-message" class="inline-message"></div>

        <section class="template-workbench" id="pago-detail-workspace" hidden>
            <div class="template-workbench-header">
                <div>
                    <h2>Detalle del pago</h2>
                    <p class="subtitle">Informacion completa del pago seleccionado.</p>
                </div>
                <button class="ghost" type="button" data-pago-detail-close>Cerrar</button>
            </div>

            <div class="app-modal-grid">
                <div class="field">
                    <label>Codigo de orden</label>
                    <input id="detail-codigo-orden" disabled>
                </div>
                <div class="field">
                    <label>Monto</label>
                    <input id="detail-monto" disabled>
                </div>
                <div class="field">
                    <label>Fecha de pago</label>
                    <input id="detail-fecha-pago" disabled>
                </div>
                <div class="field">
                    <label>Estado</label>
                    <input id="detail-estado" disabled>
                </div>
                <div class="field">
                    <label>Numero de transaccion</label>
                    <input id="detail-numero-transaccion" disabled>
                </div>
                <div class="field">
                    <label>Metodo de pago</label>
                    <input id="detail-metodo-pago" disabled>
                </div>
                <div class="field">
                    <label>Mensaje de error</label>
                    <input id="detail-mensaje-error" disabled>
                </div>
                <div class="field">
                    <label>Postulante</label>
                    <input id="detail-postulante" disabled>
                </div>
                <div class="field">
                    <label>CI</label>
                    <input id="detail-postulante-ci" disabled>
                </div>
                <div class="field">
                    <label>Registro</label>
                    <input id="detail-postulante-registro" disabled>
                </div>
                <div class="field">
                    <label>Codigo libreta</label>
                    <input id="detail-postulante-libreta" disabled>
                </div>
                <div class="field">
                    <label>Codigo titulo</label>
                    <input id="detail-postulante-titulo" disabled>
                </div>
            </div>
        </section>

        <x-filter-panel :action="route('pagos.index')">
            <div>
                <label for="buscar">Buscar por registro, CI, nombre, orden o transaccion</label>
                <input id="buscar" name="buscar" value="{{ $search }}" data-filter-field placeholder="Ej. 12345678, Perez o ORD-001">
            </div>
            <div>
                <label for="estado">Estado</label>
                <select id="estado" name="estado" data-filter-field>
                    <option value="">Todos</option>
                    @foreach ($statuses as $item)
                        <option value="{{ $item }}" @selected($status === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <x-slot:actions>
                <a href="{{ route('pagos.index') }}" class="button secondary" data-clear-filters>Limpiar</a>
            </x-slot:actions>
        </x-filter-panel>

        <div data-results>
            @include('pagos.partials.table', ['pagos' => $pagos])
        </div>
    </div>
@endsection
