@extends('layouts.app')

@section('title', 'Gestionar Grupos')
@section('subtitle', 'Crea grupos, asigna postulantes y configura horarios para el proceso de admision.')

@section('content')
    <div class="panel">
        <div id="page-message" class="inline-message"></div>

        <section class="dashboard-metrics">
            <article class="metric">
                <span>Grupos creados</span>
                <strong>{{ $totalGrupos }}</strong>
                <small>En el semestre actual</small>
            </article>
            <article class="metric">
                <span>Postulantes asignados</span>
                <strong>{{ $totalAsignados }}</strong>
                <small>Con grupo definido</small>
            </article>
            <article class="metric">
                <span>Pendientes de asignar</span>
                <strong>{{ $pendientesAsignar }}</strong>
                <small>Pagados sin grupo</small>
            </article>
            <article class="metric">
                <span>Inscripciones</span>
                <strong>{{ $inscripcionesAbiertas ? 'Abiertas' : 'Cerradas' }}</strong>
                <small>{{ $parametro?->semestre?->nombre ?? 'Sin semestre' }}</small>
            </article>
        </section>

        <div class="actions" style="margin-bottom:16px;">
            @if ($inscripcionesAbiertas)
                <form method="POST" action="{{ route('grupos.crear') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="button primary">Crear grupos (progresivo)</button>
                </form>
                <form method="POST" action="{{ route('grupos.cerrar') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="button danger">Cerrar inscripciones y crear grupos</button>
                </form>
            @else
                <form method="POST" action="{{ route('grupos.crear') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="button primary">Crear grupos finales</button>
                </form>
            @endif
        </div>

        <div data-results>
            @include('grupos.partials.table', ['grupos' => $grupos])
        </div>
    </div>
@endsection
