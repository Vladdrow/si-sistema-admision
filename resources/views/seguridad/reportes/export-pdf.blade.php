<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reportOptions[$reportType] ?? 'Reporte' }} - Sistema de Admision FICCT-UAGRM</title>
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
    <h1>{{ $reportOptions[$reportType] ?? 'Reporte del Proceso de Admision' }}</h1>
    <p class="subtitle">
        FICCT - UAGRM | Semestre: {{ $reportSemesterLabel }}
        | Generado el {{ now()->format('d/m/Y H:i:s') }}
    </p>

    <div class="metrics">
        <div class="metric"><span>Total inscritos</span><strong>{{ $postulantesReporte->count() }}</strong></div>
        <div class="metric"><span>Aprobados</span><strong>{{ $aprobados->count() }}</strong></div>
        <div class="metric"><span>Reprobados</span><strong>{{ $reprobados->count() }}</strong></div>
        <div class="metric"><span>Promedio general</span><strong>{{ $promedioGeneral !== null ? number_format($promedioGeneral, 2) : 'N/A' }}</strong></div>
        <div class="metric"><span>Grupos</span><strong>{{ $grupos->count() }}</strong></div>
    </div>

    @if (in_array($reportType, ['general', 'aprobados', 'reprobados'], true))
        <h2>{{ $reportOptions[$reportType] }}</h2>
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
                @forelse ($postulantesSeleccionados as $fila)
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
                @empty
                    <tr><td colspan="8" style="text-align:center;">No hay postulantes.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'promedios')
        <h2>Promedios por postulante</h2>
        <table>
            <thead>
                <tr>
                    <th>Postulante</th>
                    <th>CI</th>
                    <th>Grupo</th>
                    <th>Promedio</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($postulantesReporte as $fila)
                    <tr>
                        <td>{{ $fila['nombre'] }}</td>
                        <td>{{ $fila['ci'] }}</td>
                        <td>{{ $fila['grupo'] }}</td>
                        <td>{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pendiente' }}</td>
                        <td>{{ $fila['estado_academico'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;">No hay promedios.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'materias')
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
                @forelse ($estadisticasMateria as $fila)
                    <tr>
                        <td>{{ $fila['materia']->nombre }}</td>
                        <td>{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pendiente' }}</td>
                        <td>{{ $fila['aprobados'] }}</td>
                        <td>{{ $fila['reprobados'] }}</td>
                        <td>{{ $fila['pendientes'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;">No hay estadisticas.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'grupos')
        <h2>Grupos habilitados</h2>
        <table>
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Postulantes</th>
                    <th>Capacidad</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grupos as $grupo)
                    <tr>
                        <td>{{ $grupo->nombre_grupo }}</td>
                        <td>{{ $grupo->postulanteGrupos()->count() }}</td>
                        <td>{{ $capacidadGrupo ?: 'N/A' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;">No hay grupos.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'docentes')
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
                @forelse ($docentesPorGrupo as $grupo)
                    @foreach ($grupo['asignaciones'] as $asignacion)
                        <tr>
                            <td>{{ $grupo['grupo'] }}</td>
                            <td>{{ $asignacion['docente'] }}</td>
                            <td>{{ $asignacion['materia'] }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="3" style="text-align:center;">No hay docentes asignados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'grupos_aprobados')
        <h2>Grupos con mayor cantidad de aprobados</h2>
        <table>
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Aprobados</th>
                    <th>Postulantes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gruposConAprobados as $fila)
                    <tr>
                        <td>{{ $fila['grupo']->nombre_grupo }}</td>
                        <td>{{ $fila['aprobados'] }}</td>
                        <td>{{ $fila['postulantes'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;">No hay grupos.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'ranking_admision')
        <h2>Ranking de admision</h2>
        <table>
            <thead>
                <tr>
                    <th>Carrera</th>
                    <th>Pos.</th>
                    <th>Postulante</th>
                    <th>CI</th>
                    <th>Prom.</th>
                    <th>Ingreso por</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rankingAdmision as $fila)
                    <tr>
                        <td>{{ $fila['carrera'] }}</td>
                        <td>{{ $fila['posicion'] }}</td>
                        <td>{{ $fila['nombre'] }}</td>
                        <td>{{ $fila['ci'] }}</td>
                        <td>{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pend.' }}</td>
                        <td>{{ $fila['ingreso_por'] }}</td>
                        <td>{{ $fila['estado_admision'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;">No hay admitidos para ranking.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'distribucion_carrera')
        <h2>Distribucion por carrera</h2>
        <table>
            <thead>
                <tr>
                    <th>Carrera</th>
                    <th>1ra opcion</th>
                    <th>2da opcion</th>
                    <th>Admitidos</th>
                    <th>Cupos</th>
                    <th>Estudiantes actuales</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($distribucionCarrera as $fila)
                    <tr>
                        <td>{{ $fila['carrera'] }}</td>
                        <td>{{ $fila['primera_opcion'] }}</td>
                        <td>{{ $fila['segunda_opcion'] }}</td>
                        <td>{{ $fila['admitidos'] }}</td>
                        <td>{{ $fila['cupos'] }}</td>
                        <td>{{ $fila['estudiantes_actuales'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;">No hay carreras registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'comparativa_gestiones')
        <h2>Comparativa de gestiones</h2>
        <table>
            <thead>
                <tr>
                    <th>Semestre</th>
                    <th>Estado</th>
                    <th>Inscritos</th>
                    <th>Aprobados</th>
                    <th>Reprobados</th>
                    <th>Admitidos</th>
                    <th>% ingreso</th>
                    <th>Prom.</th>
                    <th>Grupos</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($comparativaGestiones as $fila)
                    <tr>
                        <td>{{ $fila['semestre'] }}</td>
                        <td>{{ $fila['estado'] }}</td>
                        <td>{{ $fila['inscritos'] }}</td>
                        <td>{{ $fila['aprobados'] }}</td>
                        <td>{{ $fila['reprobados'] }}</td>
                        <td>{{ $fila['admitidos'] }}</td>
                        <td>{{ number_format((float) $fila['porcentaje_ingreso'], 2) }}%</td>
                        <td>{{ $fila['promedio_general'] !== null ? number_format((float) $fila['promedio_general'], 2) : 'N/A' }}</td>
                        <td>{{ $fila['grupos'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align:center;">No hay semestres para comparar.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($reportType === 'final_admitidos')
        <h2>Reporte final de admitidos</h2>
        <table>
            <thead>
                <tr>
                    <th>Carrera admitida</th>
                    <th>Pos.</th>
                    <th>Postulante</th>
                    <th>CI</th>
                    <th>Prom.</th>
                    <th>1ra opcion</th>
                    <th>2da opcion</th>
                    <th>Ingreso por</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($finalAdmitidos as $fila)
                    <tr>
                        <td>{{ $fila['carrera'] }}</td>
                        <td>{{ $fila['posicion'] }}</td>
                        <td>{{ $fila['nombre'] }}</td>
                        <td>{{ $fila['ci'] }}</td>
                        <td>{{ $fila['promedio'] !== null ? number_format((float) $fila['promedio'], 2) : 'Pend.' }}</td>
                        <td>{{ $fila['primera_opcion'] }}</td>
                        <td>{{ $fila['segunda_opcion'] }}</td>
                        <td>{{ $fila['ingreso_por'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center;">No hay admitidos finales.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div class="footer">Sistema de Admision FICCT-UAGRM</div>
    <script>window.onload = () => window.print();</script>
</body>
</html>
