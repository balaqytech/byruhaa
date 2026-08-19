<?php

namespace App\Modules\Identity\Providers;

use App\Modules\Identity\Actions\Fortify\CreateNewUser;
use App\Modules\Identity\Actions\Fortify\ResetUserPassword;
use App\Modules\Identity\Contracts\CustomerIdentityResolver;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Services\PhoneCustomerIdentityResolver;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class IdentityServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap Identity services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'App\\Models\\Customer' => Customer::class,
        ]);

        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::loginView(fn () => view('pages::customer.auth.login'));
        Fortify::confirmPasswordView(fn () => view('pages::customer.auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::customer.auth.register'));
        Fortify::resetPasswordView(fn () => view('pages::customer.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::customer.auth.forgot-password'));

        $phoneNumberNormalizer = $this->app->make(PhoneNumberNormalizer::class);

        Fortify::authenticateUsing(function (Request $request) use ($phoneNumberNormalizer): ?Customer {
            $login = (string) $request->input('login');
            $customer = str_contains($login, '@')
                ? Customer::where('email', $login)->first()
                : Customer::where('phone_number', $phoneNumberNormalizer->normalize($login))->first();

            if ($customer && Hash::check((string) $request->input('password'), $customer->password)) {
                return $customer;
            }

            return null;
        });

        RateLimiter::for('two-factor', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request): Limit {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }

    public function register(): void
    {
        $this->app->bind(CustomerIdentityResolver::class, PhoneCustomerIdentityResolver::class);
    }
}
