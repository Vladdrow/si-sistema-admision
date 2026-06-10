<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de pago - {{ $pago->codigo_orden }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1e293b; padding: 30px; }
        .header { text-align: center; margin-bottom: 24px; }
        .header h1 { font-size: 16px; margin-bottom: 4px; }
        .header p { font-size: 11px; color: #64748b; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .info-group { margin-bottom: 8px; }
        .info-group label { font-size: 9px; text-transform: uppercase; color: #94a3b8; display: block; }
        .info-group span { font-size: 12px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f1f5f9; text-align: left; padding: 6px 8px; border-bottom: 2px solid #cbd5e1; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        .total { text-align: right; font-size: 14px; font-weight: bold; margin-top: 8px; }
        .footer { margin-top: 30px; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        .estado { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .estado.pagado { background: #dcfce7; color: #16a34a; }
        .estado.pendiente { background: #fef9c3; color: #ca8a04; }
        .estado.rechazado, .estado.expirado { background: #fee2e2; color: #dc2626; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Comprobante de Pago</h1>
        <p>Sistema de Admision FICCT-UAGRM</p>
    </div>

    <div class="info-grid">
        <div>
            <div class="info-group">
                <label>Codigo de orden</label>
                <span>{{ $pago->codigo_orden }}</span>
            </div>
            <div class="info-group">
                <label>Fecha de pago</label>
                <span>{{ $pago->fecha_pago?->format('d/m/Y H:i') ?? 'Pendiente' }}</span>
            </div>
            <div class="info-group">
                <label>Metodo de pago</label>
                <span>{{ $pago->metodo_pago ?? 'No especificado' }}</span>
            </div>
            <div class="info-group">
                <label>Numero de transaccion</label>
                <span>{{ $pago->numero_transaccion ?? 'Sin transaccion' }}</span>
            </div>
        </div>
        <div>
            <div class="info-group">
                <label>Estado</label>
                <span class="estado {{ strtolower($pago->estado) }}">{{ $pago->estado }}</span>
            </div>
            <div class="info-group">
                <label>Postulante</label>
                <span>{{ $pago->postulante?->persona?->nombre_completo ?? 'Sin nombre' }}</span>
            </div>
            <div class="info-group">
                <label>CI</label>
                <span>{{ $pago->postulante?->persona?->ci }}</span>
            </div>
            <div class="info-group">
                <label>Registro</label>
                <span>{{ $pago->postulante?->persona?->credencial?->registro ?? 'Sin registro' }}</span>
            </div>
        </div>
    </div>

    @if ($pago->postulante)
        <div class="info-grid">
            <div>
                <div class="info-group">
                    <label>Libreta de colegio</label>
                    <span>{{ $pago->postulante->codigo_libreta }}</span>
                </div>
                <div class="info-group">
                    <label>Titulo de bachiller</label>
                    <span>{{ $pago->postulante->codigo_titulo }}</span>
                </div>
            </div>
            <div>
                <div class="info-group">
                    <label>Primera opcion</label>
                    <span>{{ $pago->postulante->carreraPrimera?->nombre ?? 'Sin definir' }}</span>
                </div>
                <div class="info-group">
                    <label>Segunda opcion</label>
                    <span>{{ $pago->postulante->carreraSegunda?->nombre ?? 'Sin definir' }}</span>
                </div>
            </div>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th style="text-align:right;">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Pago de inscripcion — Curso Preuniversitario FICCT</td>
                <td style="text-align:right;">Bs {{ number_format((float) $pago->monto, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total">Total: Bs {{ number_format((float) $pago->monto, 2) }}</div>

    @if ($pago->mensaje_error)
        <p style="color:#dc2626; margin-top:12px; font-size:11px;">Error registrado: {{ $pago->mensaje_error }}</p>
    @endif

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i:s') }} — Sistema de Admision FICCT-UAGRM
    </div>

    <script>window.onload = () => window.print();</script>
</body>
</html>
