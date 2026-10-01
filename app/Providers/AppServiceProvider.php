<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('checador-pin', function (Request $request) {
            $sessionKey = hash('sha256', (string) $request->header('X-Checador-Token'));

            return [
                Limit::perMinute(8)->by('checador-minute:' . $sessionKey),
                Limit::perHour(30)->by('checador-hour:' . $sessionKey),
            ];
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
            $expiration = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);
            $data = [
                'appName' => config('app.name'),
                'logoUrl' => asset('assets/logos/logoLB.png'),
                'name' => $notifiable->name,
                'resetUrl' => $resetUrl,
                'expiration' => $expiration,
            ];

            return (new MailMessage)
                ->subject('Restablece tu contraseña de ' . config('app.name'))
                ->view('emails.auth.password-reset', $data)
                ->text('emails.auth.password-reset-text', $data);
        });
    }
}
