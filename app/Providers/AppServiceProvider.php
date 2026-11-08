<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\Flights\DuffelFlightProvider;
use App\Services\Flights\FlightProvider;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FlightProvider::class, DuffelFlightProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePasswordResetEmail();
    }

    /**
     * Send the "reset your password" email in SkyRoute's words instead of Laravel's default.
     */
    protected function configurePasswordResetEmail(): void
    {
        ResetPassword::toMailUsing(function (CanResetPassword $notifiable, string $token): MailMessage {
            $business = Setting::get('business_name');
            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject("Reset your {$business} password")
                ->greeting('Reset your password')
                ->line("We got a request to reset the password for your {$business} account.")
                ->action('Choose a new password', route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]))
                ->line("This link works for {$minutes} minutes.")
                ->line("If you didn't ask for this, you can ignore this email. Your password won't change.")
                ->salutation("Safe travels,\nThe {$business} team");
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
