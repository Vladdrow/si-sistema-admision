<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reportes - Sistema de Admision FICCT-UAGRM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #1e293b; padding: 20px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        h2 { font-size: 12px; margin: 18px 0 8px; }
        .subtitle, .footer { color: #64748b; font-size: 10px; }
        .metrics { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin: 14px 0; }
        .metric { border: 1px solid #cbd5e1; padding: 8px; }
        .metric span { color: #64748b; display: block; font-size: 9px; }
        .metric strong { display: block; font-size: 14px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #f1f5f9; text-align: left; padding: 5px 6px; border-bottom: 2px solid #cbd5e1; font-size: 8px; text-transform: uppercase; }
        td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 16px; text-align: center; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
    <h1>Reportes del Proceso de Admision</h1>
    <p class="subtitle">
        FICCT - UAGRM | Semestre: {{ $parametro?->semestre?->nombre ?? 'Sin semestre' }}
        | Generado el {{ now()->format('d/m/Y H:i:s') }}
    </p>

    <div class="metrics">
        <div class="metric"><span>Total inscritos</span><strong>{{ $postulantesReporte->count() }}</strong></div>
        <div class="metric"><span>Aprobados</span><strong>{{ $aprobados->count() }}</strong></div>
        <div class="metric"><span>Reprobados</span><strong>{{ $reprobados->count() }}</strong></div>
        <div class="metric"><span>Promedio general</span><strong>{{ $promedioGeneral !== null ? number_format($promedioGeneral, 2) : 'N/A' }}</strong></div>
        <div class="metric"><span>Grupos</span><strong>{{ $grupos->count() }}</strong></div>
    </div>

    <h2>Lista general de postulantes</h2>
    <table>
        <thead>
            <tr>
                <th>Postulante</th>
                <th>CI</th>
                <th>Grupo</th>
                <th>1ra opcion</th>
                <th>2da opcion</th>
                <th>Admitido en</th>
                <th>Prom.</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($postulantesReporte->isEmpty()) { ?>
                <tr><td colspan="8" style="text-align:center;">No hay postulantes.</td></tr>
            <?php } ?>
            <?php foreach ($postulantesReporte as $fila) { ?>
                <tr>
                    <td>{{ $fila['nombre'] }}</td>
                    <td>{{ $fila['ci'] }}</td>
                    <td>{{ $fila['grupo'] }}</td>
                    <td>{{ $fila['primera_opcion'] }}</td>
                    <td>{{ $fila['segunda_opcion'] }}</td>
                    <td>{{ $fila['carrera_admitida'] }}</td>
                    <td>{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pend.' }}</td>
                    <td>{{ $fila['estado_academico'] }}</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <h2>Estadisticas por materia</h2>
    <table>
        <thead>
            <tr>
                <th>Materia</th>
                <th>Promedio</th>
                <th>Aprobados</th>
                <th>Reprobados</th>
                <th>Pendientes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estadisticasMateria as $fila) { ?>
                <tr>
                    <td>{{ $fila['materia']->nombre }}</td>
                    <td>{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pendiente' }}</td>
                    <td>{{ $fila['aprobados'] }}</td>
                    <td>{{ $fila['reprobados'] }}</td>
                    <td>{{ $fila['pendientes'] }}</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <h2>Grupos habilitados y aprobados</h2>
    <table>
        <thead>
            <tr>
                <th>Grupo</th>
                <th>Postulantes</th>
                <th>Aprobados</th>
                <th>Capacidad</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gruposConAprobados as $fila) { ?>
                <tr>
                    <td>{{ $fila['grupo']->nombre_grupo }}</td>
                    <td>{{ $fila['postulantes'] }}</td>
                    <td>{{ $fila['aprobados'] }}</td>
                    <td>{{ $capacidadGrupo ?: 'N/A' }}</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <h2>Docentes por grupos</h2>
    <table>
        <thead>
            <tr>
                <th>Grupo</th>
                <th>Docente</th>
                <th>Materia</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($docentesPorGrupo->isEmpty()) { ?>
                <tr><td colspan="3" style="text-align:center;">No hay docentes asignados.</td></tr>
            <?php } ?>
            <?php foreach ($docentesPorGrupo as $grupo) { ?>
                <?php foreach ($grupo['asignaciones'] as $asignacion) { ?>
                    <tr>
                        <td>{{ $grupo['grupo'] }}</td>
                        <td>{{ $asignacion['docente'] }}</td>
                        <td>{{ $asignacion['materia'] }}</td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>

    <div class="footer">Sistema de Admision FICCT-UAGRM</div>
    <script>window.onload = () => window.print();</script>
</body>
</html>
