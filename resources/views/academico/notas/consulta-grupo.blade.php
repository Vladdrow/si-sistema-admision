@extends('layouts.app')

@section('title', "Notas: {$grupo->nombre_grupo}")
@section('subtitle', "Semestre: {$grupo->semestre?->nombre} — Nota minima: {$notaMinima}")

@section('content')
    <div class="panel">
        <a href="{{ route('notas.consulta') }}" class="button secondary" style="margin-bottom:16px;">Volver a grupos</a>

        @php
            $total = count($resumen);
            $aprobados = collect($resumen)->where('estado_general', 'Aprobado')->count();
            $reprobados = collect($resumen)->where('estado_general', 'Reprobado')->count();
            $pendientes = $total - $aprobados - $reprobados;
        @endphp

        @if ($total > 0)
            <section class="dashboard-metrics" style="margin-bottom:20px;">
                <article class="metric">
                    <span>Postulantes</span>
                    <strong>{{ $total }}</strong>
                    <small>En este grupo</small>
                </article>
                <article class="metric">
                    <span>Aprobados</span>
                    <strong style="color:#16a34a;">{{ $aprobados }}</strong>
                    <small>Promedio ≥ {{ $notaMinima }}</small>
                </article>
                <article class="metric">
                    <span>Reprobados</span>
                    <strong style="color:#dc2626;">{{ $reprobados }}</strong>
                    <small>Alguna materia < {{ $notaMinima }}</small>
                </article>
                <article class="metric">
                    <span>Pendientes</span>
                    <strong>{{ $pendientes }}</strong>
                    <small>Notas incompletas</small>
                </article>
            </section>
        @endif

        @if (empty($resumen))
            <p class="dashboard-empty">No hay postulantes en este grupo.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Postulante</th>
                        @foreach ($materias as $materia)
                            <th style="text-align:center;">{{ $materia->nombre }}</th>
                        @endforeach
                        <th style="text-align:center;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resumen as $i => $data)
                        @php $p = $data['postulante']; @endphp
                        <tr style="{{ $data['estado_general'] === 'Aprobado' ? 'background:#f0fdf4;' : ($data['estado_general'] === 'Reprobado' ? 'background:#fef2f2;' : '') }}">
                            <td style="color:#94a3b8;">{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $p->persona?->nombre_completo ?? 'Sin nombre' }}</strong>
                                <br><small class="muted">{{ $p->persona?->credencial?->registro ?? 'Sin registro' }}</small>
                            </td>
                            @foreach ($materias as $materia)
                                @php $m = $data['materias'][$materia->id_materia] ?? null; @endphp
                                <td style="text-align:center;">
                                    @if ($m && $m['promedio'] !== null)
                                        <strong>{{ number_format($m['promedio'], 1) }}</strong>
                                        <br>
                                        <span class="badge {{ $m['estado'] === 'Aprobado' ? 'ok' : ($m['estado'] === 'Reprobado' ? 'off' : 'neutral') }}" style="font-size:10px;">
                                            {{ $m['estado'] }}
                                        </span>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>
                            @endforeach
                            <td style="text-align:center;">
                                <span class="badge {{ $data['estado_general'] === 'Aprobado' ? 'ok' : ($data['estado_general'] === 'Reprobado' ? 'off' : 'neutral') }}">
                                    {{ $data['estado_general'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
