<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Credencial;
use App\Models\Pago;
use App\Models\ParametroAdmision;
use App\Models\Persona;
use App\Models\Postulante;
use App\Services\LibelulaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * CU04 - Registrar Postulante.
 *
 * Flujo publico de registro: formulario inicial, redireccion a pasarela
 * de pago Libelula, callback de confirmacion, activacion de cuenta y
 * envio de credenciales por correo.
 */
class RegistroPostulanteController extends Controller
{
    public function __construct(private readonly LibelulaService $libelula)
    {
    }

    /**
     * Muestra el formulario publico de registro.
     */
    public function create(): View
    {
        $carreras = Carrera::orderBy('nombre')->get();
        $parametro = $this->parametroVigente();
        $inscripcionesAbiertas = $this->inscripcionesAbiertas($parametro);

        return view('registro.create', compact('carreras', 'parametro', 'inscripcionesAbiertas'));
    }

    /**
     * Procesa el registro inicial: valida, crea o reutiliza persona + postulante
     * si el pago anterior fue rechazado o expiro, y redirige a Libelula.
     */
    public function store(Request $request): RedirectResponse
    {
        $parametro = $this->parametroVigente();

        if (! $this->inscripcionesAbiertas($parametro)) {
            return back()->withInput()->with('status', $this->mensajeCierre($parametro));
        }

        if (! $parametro || (float) $parametro->monto_pago <= 0) {
            return back()->withInput()->with('status', 'Sistema cerrado para inscripciones. El monto del pago no esta configurado.');
        }

        $personaExistente = Persona::where('ci', $request->input('ci'))->first();
        $reusar = $personaExistente && $this->puedeReusar($personaExistente);

        if ($personaExistente && ! $reusar) {
            return back()->withInput()->with('status', 'Usted ya se encuentra registrado en el sistema.');
        }

        $data = $this->validarDatos($request, $reusar ? $personaExistente : null);

        $result = DB::transaction(function () use ($data, $parametro, $reusar, $personaExistente): array {
            if ($reusar) {
                $personaExistente->update($this->personData($data));
                $postulante = $personaExistente->postulante;
                $postulante->update($this->postulanteData($data));
            } else {
                $personaExistente = Persona::create($this->personData($data));
                $postulante = Postulante::create($this->postulanteData($data, $personaExistente->id_persona));
            }

            $codigoOrden = $this->generateCodigoOrden();

            Pago::create([
                'monto' => (float) $parametro->monto_pago,
                'estado' => 'Pendiente',
                'codigo_orden' => $codigoOrden,
                'id_postulante' => $postulante->id_postulante,
            ]);

            return [
                'codigo_orden' => $codigoOrden,
                'monto' => (float) $parametro->monto_pago,
            ];
        });

        return redirect()->to(
            $this->libelula->urlDePago($result['codigo_orden'], $result['monto'])
        );
    }

    /**
     * Muestra la pagina de pago simulada de Libelula.
     */
    public function showPago(Request $request): View
    {
        $codigoOrden = (string) $request->query('codigo_orden', '');
        $monto = (float) $request->query('monto', 0);

        $pago = Pago::with('postulante.persona')
            ->where('codigo_orden', $codigoOrden)
            ->first();

        if (! $pago || $pago->estado !== 'Pendiente') {
            return view('registro.fallido', [
                'mensaje' => $pago
                    ? "Este pago ya fue procesado (estado: {$pago->estado})."
                    : 'Orden de pago no encontrada.',
            ]);
        }

        return view('pago.libelula', compact('pago', 'monto'));
    }

    /**
     * Procesa el callback de Libelula: registra el resultado del pago.
     */
    public function callback(Request $request): RedirectResponse|View
    {
        $datos = $request->validate([
            'codigo_orden' => ['required', 'string'],
            'estado' => ['required', 'in:exitoso,rechazado'],
            'numero_transaccion' => ['nullable', 'string'],
            'mensaje_error' => ['nullable', 'string'],
        ]);

        $pago = Pago::with('postulante.persona')
            ->where('codigo_orden', $datos['codigo_orden'])
            ->first();

        if (! $pago || $pago->estado !== 'Pendiente') {
            return redirect()->route('registro.create')
                ->with('status', 'La orden de pago no es valida o ya fue procesada.');
        }

        if (! $this->libelula->validarCallback($datos['codigo_orden'], $datos)) {
            return redirect()->route('registro.create')
                ->with('status', 'Error al validar la respuesta de la pasarela de pago.');
        }

        if ($datos['estado'] === 'exitoso') {
            return $this->procesarPagoExitoso($pago, $datos);
        }

        return $this->procesarPagoRechazado($pago, $datos);
    }

    private function procesarPagoExitoso(Pago $pago, array $datos): RedirectResponse|View
    {
        $postulante = $pago->postulante;
        $persona = $postulante->persona;
        $registro = null;

        if (! $persona) {
            return redirect()->route('registro.create')
                ->with('status', 'Error interno: datos del postulante no encontrados.');
        }

        DB::transaction(function () use ($pago, $datos, $persona, &$registro): void {
            $pago->update([
                'estado' => 'Pagado',
                'fecha_pago' => now(),
                'numero_transaccion' => $datos['numero_transaccion'] ?? null,
                'metodo_pago' => 'Libelula',
            ]);

            $credencial = Credencial::create([
                'id_persona' => $persona->id_persona,
                'registro' => Credencial::generateUniqueRegistro(),
                'contrasena' => \Illuminate\Support\Facades\Hash::make($persona->ci),
                'rol' => 'Postulante',
                'estado' => true,
                'intentos_fallidos' => 0,
            ]);

            $registro = $credencial->registro;
        });

        try {
            Mail::raw(
                "Bienvenido al Sistema de Admision FICCT-UAGRM.\n\n"
                . "Su registro ha sido completado exitosamente.\n"
                . "Numero de registro: {$registro}\n"
                . "Contrasena: su CI\n\n"
                . "Ingrese al sistema: " . route('login'),
                fn ($message) => $message
                    ->to($persona->correo)
                    ->subject('Registro exitoso - Sistema de Admision FICCT')
            );
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar el correo de registro.', [
                'registro' => $registro,
                'correo' => $persona->correo,
                'error' => $exception->getMessage(),
            ]);
        }

        return view('registro.exitoso', [
            'registro' => $registro,
            'correo' => $persona->correo,
        ]);
    }

    private function procesarPagoRechazado(Pago $pago, array $datos): RedirectResponse
    {
        DB::transaction(function () use ($pago, $datos): void {
            $pago->update([
                'estado' => 'Rechazado',
                'mensaje_error' => $datos['mensaje_error'] ?? 'El pago fue rechazado por la pasarela.',
            ]);

            $pago->postulante?->update(['estado_admision' => 'No Admitido']);
        });

        return redirect()->route('registro.create')
            ->with('status', 'Pago no completado. Debe reiniciar el registro.');
    }

    private function generateCodigoOrden(): string
    {
        return 'ORD-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Determina si una persona existente puede reusarse para un nuevo
     * intento de registro (pago anterior rechazado o expirado).
     */
    private function puedeReusar(Persona $persona): bool
    {
        $persona->loadMissing('postulante', 'credencial');

        if (! $persona->postulante) {
            return false;
        }

        if ($persona->credencial) {
            return false;
        }

        if ($persona->postulante->estado_admision === 'Admitido') {
            return false;
        }

        $ultimoPago = Pago::where('id_postulante', $persona->postulante->id_postulante)
            ->latest('id_pago')
            ->first();

        if ($ultimoPago && $ultimoPago->estado === 'Pagado') {
            return false;
        }

        return true;
    }

    private function validarDatos(Request $request, ?Persona $personaExistente = null): array
    {
        $personaId = $personaExistente?->id_persona;
        $postulanteId = $personaExistente?->id_persona; // misma PK

        return $request->validate([
            'ci' => ['required', 'string', 'max:20', Rule::unique('persona', 'ci')->ignore($personaId, 'id_persona')],
            'nombres' => ['required', 'string', 'max:50'],
            'apellido_paterno' => ['required', 'string', 'max:50'],
            'apellido_materno' => ['nullable', 'string', 'max:50'],
            'fecha_nacimiento' => ['required', 'date'],
            'sexo' => ['required', 'in:M,F'],
            'direccion' => ['nullable', 'string', 'max:70'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:50', Rule::unique('persona', 'correo')->ignore($personaId, 'id_persona')],
            'codigo_libreta' => ['required', 'string', 'max:20', Rule::unique('postulante', 'codigo_libreta')->ignore($postulanteId, 'id_postulante')],
            'codigo_titulo' => ['required', 'string', 'max:20', Rule::unique('postulante', 'codigo_titulo')->ignore($postulanteId, 'id_postulante')],
            'id_carrera_primera_opc' => ['required', 'integer', 'exists:carrera,id_carrera'],
            'id_carrera_segunda_opc' => ['required', 'integer', 'exists:carrera,id_carrera', 'different:id_carrera_primera_opc'],
            'colegio_procedencia' => ['nullable', 'string', 'max:100'],
            'ciudad' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function personData(array $data): array
    {
        return [
            'ci' => $data['ci'],
            'nombres' => $data['nombres'],
            'apellido_paterno' => $data['apellido_paterno'],
            'apellido_materno' => $data['apellido_materno'] ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'sexo' => $data['sexo'],
            'direccion' => $data['direccion'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'correo' => $data['correo'],
        ];
    }

    private function postulanteData(array $data, ?int $personaId = null): array
    {
        $result = [
            'colegio_procedencia' => $data['colegio_procedencia'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
            'estado_admision' => 'Pendiente',
            'codigo_libreta' => $data['codigo_libreta'],
            'codigo_titulo' => $data['codigo_titulo'],
            'id_carrera_primera_opc' => $data['id_carrera_primera_opc'],
            'id_carrera_segunda_opc' => $data['id_carrera_segunda_opc'],
        ];

        if ($personaId !== null) {
            $result['id_postulante'] = $personaId;
        }

        return $result;
    }

    private function parametroVigente(): ?ParametroAdmision
    {
        return ParametroAdmision::with('semestre')
            ->join('semestre', 'semestre.id_semestre', '=', 'parametro_admision.id_semestre')
            ->orderByDesc('semestre.nombre')
            ->select('parametro_admision.*')
            ->first();
    }

    private function inscripcionesAbiertas(?ParametroAdmision $parametro): bool
    {
        if (! $parametro) {
            return false;
        }

        $ahora = now();

        return $ahora->gte($parametro->fecha_inicio_inscripcion)
            && $ahora->lte($parametro->fecha_cierre_inscripcion);
    }

    private function mensajeCierre(?ParametroAdmision $parametro): string
    {
        if (! $parametro) {
            return 'Sistema cerrado para inscripciones. No hay un periodo de admision configurado.';
        }

        if (now()->lt($parametro->fecha_inicio_inscripcion)) {
            return 'Las inscripciones aun no han iniciado. Vuelva a partir del '
                . $parametro->fecha_inicio_inscripcion->format('d/m/Y H:i') . '.';
        }

        return 'Las inscripciones ya han cerrado. El periodo finalizo el '
            . $parametro->fecha_cierre_inscripcion->format('d/m/Y H:i') . '.';
    }
}
