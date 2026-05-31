<?php

namespace App\Http\Controllers;

use App\Models\Credencial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

/**
 * CU02 - Gestionar Contrasena.
 *
 * Implementa los dos flujos del documento: cambio de contrasena para usuarios
 * autenticados y recuperacion mediante codigo temporal enviado al correo.
 */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('password.edit');
    }

    public function showRecoveryRequest(): View
    {
        return view('password.recovery-request');
    }

    /**
     * CU02 - Genera un codigo de 6 digitos y lo envia al correo asociado.
     */
    public function sendRecoveryCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registro' => ['required', 'string'],
        ]);

        $credencial = Credencial::where('registro', $data['registro'])->first();

        if (! $credencial) {
            // Respuesta generica para no revelar si un registro existe.
            return back()
                ->with('status', 'Si el registro existe, se envio un codigo de recuperacion al correo registrado.')
                ->withInput();
        }

        $code = (string) random_int(100000, 999999);
        $credencial->loadMissing('persona');

        $credencial->forceFill([
            'codigo_recuperacion' => $code,
            'fecha_expiracion_codigo' => now()->addMinutes(15),
        ])->save();

        if (! $credencial->persona?->correo) {
            return back()
                ->withErrors(['registro' => 'La cuenta no tiene un correo registrado para enviar el codigo.'])
                ->withInput();
        }

        try {
            Mail::raw(
                "Codigo de recuperacion FICCT-UAGRM: {$code}\n\nEste codigo vence en 15 minutos.",
                fn ($message) => $message
                    ->to($credencial->persona->correo)
                    ->subject('Codigo de recuperacion - Sistema de Admision FICCT')
            );
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar el codigo de recuperacion.', [
                'registro' => $credencial->registro,
                'correo' => $credencial->persona->correo,
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors(['registro' => 'No se pudo enviar el codigo. Revise la configuracion del correo emisor.'])
                ->withInput();
        }

        $status = 'Si el registro existe, se envio un codigo de recuperacion al correo registrado.';

        if (Auth::check()) {
            return redirect()->route('password.edit')->with('status', $status);
        }

        return redirect()
            ->route('password.recovery.reset', ['registro' => $credencial->registro])
            ->with('status', $status);
    }

    public function showRecoveryReset(Request $request): View
    {
        return view('password.recovery-reset', [
            'registro' => (string) $request->query('registro', ''),
        ]);
    }

    /**
     * CU02 - Verifica el codigo vigente y permite definir una nueva contrasena.
     */
    public function resetWithCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registro' => ['required', 'string'],
            'codigo_recuperacion' => ['required', 'string', 'size:6'],
            'nueva_contrasena' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $credencial = Credencial::where('registro', $data['registro'])->first();

        if (! $credencial || ! hash_equals((string) $credencial->codigo_recuperacion, $data['codigo_recuperacion'])) {
            return back()->withErrors(['codigo_recuperacion' => 'El codigo de recuperacion no es valido.'])->withInput();
        }

        if (! $credencial->fecha_expiracion_codigo || $credencial->fecha_expiracion_codigo->isPast()) {
            return back()->withErrors(['codigo_recuperacion' => 'El codigo de recuperacion ha expirado.'])->withInput();
        }

        $credencial->forceFill([
            'contrasena' => Hash::make($data['nueva_contrasena']),
            'codigo_recuperacion' => null,
            'fecha_expiracion_codigo' => null,
            'intentos_fallidos' => 0,
            'fecha_bloqueo' => null,
        ])->save();

        if (Auth::check()) {
            return redirect()->route('password.edit')->with('status', 'Contrasena recuperada correctamente.');
        }

        return redirect()->route('login')->with('status', 'Contrasena recuperada correctamente. Ya puede iniciar sesion.');
    }

    /**
     * CU02 - Cambio directo cuando el usuario ya esta autenticado.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contrasena_actual' => ['required', 'string'],
            'nueva_contrasena' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $credencial = $request->user();

        if (! $credencial instanceof Credencial || ! $this->passwordMatches($credencial, $data['contrasena_actual'])) {
            return back()->withErrors(['contrasena_actual' => 'La contrasena actual no coincide.']);
        }

        $credencial->forceFill([
            'contrasena' => Hash::make($data['nueva_contrasena']),
            'intentos_fallidos' => 0,
            'fecha_bloqueo' => null,
        ])->save();

        return back()->with('status', 'Contrasena actualizada correctamente.');
    }

    private function passwordMatches(Credencial $credencial, string $password): bool
    {
        $hashInfo = Hash::info($credencial->contrasena);

        return ($hashInfo['algo'] !== null && Hash::check($password, $credencial->contrasena))
            || hash_equals($credencial->contrasena, $password);
    }
}
