<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Credencial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BitacoraLogger
{
    public function registrar(Request $request, Credencial $credencial, string $accion, string $modulo, ?string $descripcion = null): void
    {
        if (! $credencial->id_persona) {
            return;
        }

        try {
            $this->syncBitacoraSerialSequence();

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

    private function syncBitacoraSerialSequence(): void
    {
        DB::statement("SELECT setval(pg_get_serial_sequence('bitacora', 'id_bitacora'), COALESCE(MAX(id_bitacora), 1), MAX(id_bitacora) IS NOT NULL) FROM bitacora");
    }
}
