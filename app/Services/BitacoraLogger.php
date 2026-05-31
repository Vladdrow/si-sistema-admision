<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Credencial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BitacoraLogger
{
    public function registrar(Request $request, Credencial $credencial, string $accion, string $modulo, ?string $descripcion = null): void
    {
        if (! $credencial->id_persona) {
            return;
        }

        try {
            Bitacora::create([
                'accion' => substr($accion, 0, 50),
                'modulo' => substr($modulo, 0, 50),
                'descripcion' => $descripcion,
                'ip_origen' => $request->ip(),
                'id_persona' => $credencial->id_persona,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('No se pudo registrar la bitacora.', [
                'error' => $exception->getMessage(),
                'accion' => $accion,
                'modulo' => $modulo,
            ]);
        }
    }
}
