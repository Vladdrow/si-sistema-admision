<?php

namespace App\Services;

class LibelulaService
{
    /**
     * Genera una URL de pago simulada de Libelula para la orden dada.
     */
    public function urlDePago(string $codigoOrden, float $monto): string
    {
        return route('pago.libelula', [
            'codigo_orden' => $codigoOrden,
            'monto' => $monto,
        ]);
    }

    /**
     * Valida que el callback recibido corresponda a una orden legitima.
     * En produccion verificaria la firma digital de Libelula.
     */
    public function validarCallback(string $codigoOrden, array $datos): bool
    {
        return ! empty($codigoOrden) && ! empty($datos['estado'] ?? null);
    }
}
