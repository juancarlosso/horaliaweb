<?php

namespace App\Http\Controllers;

use App\Services\RegisterCompanyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CompanyRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.company-register');
    }

    public function store(Request $request, RegisterCompanyService $registration)
    {
        $siteKey = config('services.cloudflare.turnstile_sitekey');
        $secretKey = config('services.cloudflare.turnstile_secretkey');

        if ($siteKey || $secretKey) {
            $request->validate([
                'cf-turnstile-response' => ['required', 'string'],
            ], ['cf-turnstile-response.required' => 'Completa la verificación de seguridad.']);

            if (!$siteKey || !$secretKey || !$this->verifyTurnstile($request, $secretKey)) {
                return back()->withInput($request->except('password', 'password_confirmation', 'cf-turnstile-response'))
                    ->withErrors(['cf-turnstile-response' => 'No se pudo validar la verificación de seguridad. Inténtalo de nuevo.']);
            }
        }

        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'rfc' => strtoupper(trim((string) $request->input('rfc'))),
        ]);

        $data = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', Rule::unique('empresas', 'rfc')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'rfc.regex' => 'Escribe un RFC válido de persona moral o física, o deja el campo vacío para generar uno provisional.',
            'terms.accepted' => 'Debes aceptar los términos y condiciones y el aviso de privacidad.',
        ]);

        $registration->register($data);

        $request->session()->flash('registro_confirmacion_autorizada', true);
        $request->session()->flash('registro_event_id', (string) Str::uuid());

        return redirect()->route('registro.completado');
    }

    public function completed(Request $request)
    {
        if (!$request->session()->pull('registro_confirmacion_autorizada')) {
            return redirect()->route('login');
        }

        $eventId = $request->session()->pull('registro_event_id');

        return response()->view('auth.registration-completed', [
            'eventId' => $eventId,
        ])->header('Cache-Control', 'no-store, private');
    }

    private function verifyTurnstile(Request $request, string $secretKey): bool
    {
        try {
            $response = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secretKey,
                'response' => $request->input('cf-turnstile-response'),
                'remoteip' => $request->ip(),
            ]);

            return $response->successful() && $response->json('success') === true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
