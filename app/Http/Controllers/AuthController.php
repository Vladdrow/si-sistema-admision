<?php

namespace App\Http\Controllers;

use App\Models\Credencial;
use App\Services\BitacoraLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * CU16/CU17 - Autenticacion del sistema.
 *
 * CU16 permite iniciar sesion con numero de registro y contrasena,
 * respetando cuenta activa, bloqueo por intentos fallidos y registro en bitacora.
 * CU17 cierra la sesion, invalida el token y deja trazabilidad del cierre.
 */
class AuthController extends Controller
{
    public function __construct(private readonly BitacoraLogger $bitacora)
    {
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * CU16 - Valida credenciales, controla estado/bloqueo y abre la sesion.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'registro' => ['required', 'string'],
            'contrasena' => ['required', 'string'],
        ]);

        $credencial = Credencial::with(['persona.docente', 'persona.postulante', 'persona.personalAdministrativo'])
            ->where('registro', $credentials['registro'])
            ->first();

        if (! $credencial) {
            return back()->withErrors(['registro' => 'Las credenciales no coinciden.'])->onlyInput('registro');
        }

        if (! $credencial->estado) {
            return back()->withErrors(['registro' => 'Usuario desactivado. Contacte al administrador.'])->onlyInput('registro');
        }

        if (! $this->hasConsistentRole($credencial)) {
            return back()
                ->withErrors(['registro' => 'La credencial tiene un rol que no coincide con la persona asignada. Contacte al administrador.'])
                ->onlyInput('registro');
        }

        if ($credencial->fecha_bloqueo && $credencial->fecha_bloqueo->gt(now()->subMinutes(5))) {
            return back()->withErrors(['registro' => 'Cuenta bloqueada por 5 minutos por intentos fallidos.'])->onlyInput('registro');
        }

        $hashInfo = Hash::info($credencial->contrasena);
        $passwordMatches = ($hashInfo['algo'] !== null && Hash::check($credentials['contrasena'], $credencial->contrasena))
            || hash_equals($credencial->contrasena, $credentials['contrasena']);

        if (! $passwordMatches) {
            // El documento define bloqueo temporal al superar 3 intentos fallidos.
            $credencial->forceFill([
                'intentos_fallidos' => $credencial->intentos_fallidos + 1,
                'fecha_bloqueo' => $credencial->intentos_fallidos + 1 >= 3 ? now() : null,
            ])->save();

            return back()->withErrors(['registro' => 'Las credenciales no coinciden.'])->onlyInput('registro');
        }

        $updates = [
            'fecha_ultimo_acceso' => now(),
            'intentos_fallidos' => 0,
            'fecha_bloqueo' => null,
        ];

        if (Hash::needsRehash($credencial->contrasena)) {
            // Normaliza contrasenas antiguas guardadas sin el hash actual de Laravel.
            $updates['contrasena'] = Hash::make($credentials['contrasena']);
        }

        $credencial->forceFill($updates)->save();

        Auth::login($credencial);
        $request->session()->regenerate();
        $this->bitacora->registrar($request, $credencial, 'Iniciar sesion', 'Autenticacion', 'Inicio sesion en el sistema.');

        return redirect()->intended(route('dashboard'));
    }

    private function hasConsistentRole(Credencial $credencial): bool
    {
        return match ($credencial->rol) {
            'Docente' => (bool) $credencial->persona?->docente,
            'Postulante' => (bool) $credencial->persona?->postulante,
            'PersonalAdministrativo' => (bool) $credencial->persona?->personalAdministrativo,
            'Administrador' => true,
            default => false,
        };
    }

    /**
     * CU17 - Cierra la sesion activa y regenera el token para evitar reutilizacion.
     */
    public function logout(Request $request): RedirectResponse
    {
        $credencial = $request->user();

        if ($credencial instanceof Credencial) {
            $this->bitacora->registrar($request, $credencial, 'Cerrar sesion', 'Autenticacion', 'Cerro sesion en el sistema.');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesion cerrada correctamente.');
    }
}
