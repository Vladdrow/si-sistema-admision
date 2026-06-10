<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Bitacora - Sistema de Admision FICCT-UAGRM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; padding: 20px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .subtitle { font-size: 11px; color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; text-align: left; padding: 6px 8px; border-bottom: 2px solid #cbd5e1; font-size: 10px; text-transform: uppercase; }
        td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 16px; font-size: 9px; color: #94a3b8; text-align: center; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
    <h1>Bitacora del Sistema</h1>
    <p class="subtitle">
        FICCT - UAGRM
        @if ($search) | Busqueda: {{ $search }} @endif
        @if ($module) | Modulo: {{ $module }} @endif
        @if ($action) | Accion: {{ $action }} @endif
        | {{ $registros->count() }} registros
    </p>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>CI</th>
                <th>Accion</th>
                <th>Modulo</th>
                <th>Descripcion</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $registro)
                <tr>
                    <td>{{ $registro->fecha_hora?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $registro->persona?->nombre_completo ?? 'Sin usuario' }}</td>
                    <td>{{ $registro->persona?->ci }}</td>
                    <td>{{ $registro->accion }}</td>
                    <td>{{ $registro->modulo }}</td>
                    <td>{{ $registro->descripcion ?? '' }}</td>
                    <td>{{ $registro->ip_origen ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:16px;">No se encontraron registros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i:s') }} — Sistema de Admision FICCT-UAGRM
    </div>

    <script>window.onload = () => window.print();</script>
</body>
</html>
