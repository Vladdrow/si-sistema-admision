<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Libelula - Pasarela de pago</title>
    <style>
        :root { color-scheme: light; font-family: Inter, Arial, Helvetica, sans-serif; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f0f4f8; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); max-width: 440px; width: 100%; padding: 28px; }
        h2 { font-size: 20px; margin-bottom: 8px; }
        .brand { color: #6366f1; font-size: 13px; font-weight: 700; margin-bottom: 16px; }
        .info { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px; }
        .info-row span:last-child { font-weight: 600; }
        .actions { display: flex; gap: 10px; }
        .button { align-items: center; border: none; border-radius: 8px; cursor: pointer; display: inline-flex; font-size: 14px; font-weight: 700; justify-content: center; min-height: 44px; padding: 10px 18px; }
        .button.primary { background: #6366f1; color: #fff; }
        .button.danger { background: #fee2e2; color: #dc2626; }
        form { display: contents; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">LIBELULA</div>
        <h2>Pago de inscripcion</h2>
        <p style="color:#64748b;font-size:14px;margin-bottom:16px;">Curso Preuniversitario FICCT-UAGRM</p>

        <div class="info">
            <div class="info-row"><span>Orden</span><span>{{ $pago->codigo_orden }}</span></div>
            <div class="info-row"><span>Postulante</span><span>{{ $pago->postulante?->persona?->nombre_completo ?? 'Sin nombre' }}</span></div>
            <div class="info-row"><span>CI</span><span>{{ $pago->postulante?->persona?->ci }}</span></div>
            <div class="info-row"><span>Monto a pagar</span><span>Bs {{ number_format((float) $monto, 2) }}</span></div>
        </div>

        <div class="actions">
            <form method="POST" action="{{ route('pago.callback') }}">
                @csrf
                <input type="hidden" name="codigo_orden" value="{{ $pago->codigo_orden }}">
                <input type="hidden" name="estado" value="exitoso">
                <input type="hidden" name="numero_transaccion" value="LIB-{{ now()->format('YmdHis') }}-{{ random_int(100, 999) }}">
                <button type="submit" class="button primary">Pagar con Libelula</button>
            </form>

            <form method="POST" action="{{ route('pago.callback') }}">
                @csrf
                <input type="hidden" name="codigo_orden" value="{{ $pago->codigo_orden }}">
                <input type="hidden" name="estado" value="rechazado">
                <input type="hidden" name="mensaje_error" value="Pago rechazado por fondos insuficientes.">
                <button type="submit" class="button danger">Simular rechazo</button>
            </form>
        </div>
    </div>
</body>
</html>
