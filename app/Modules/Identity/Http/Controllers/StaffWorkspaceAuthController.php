<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Http\Requests\StaffWorkspaceLoginRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StaffWorkspaceAuthController
{
    public function cashierLoginPage(): View|RedirectResponse
    {
        return $this->loginPage('cashier', 'Sell:Pos', 'cashier.terminal');
    }

    public function baristaLoginPage(): View|RedirectResponse
    {
        return $this->loginPage('barista', 'View:BaristaBoard', 'barista.orders');
    }

    public function pickupLoginPage(): View|RedirectResponse
    {
        return $this->loginPage('pickup', 'View:PickupBoard', 'pickup.orders');
    }

    public function cashierLogin(StaffWorkspaceLoginRequest $request): RedirectResponse
    {
        return $this->login($request, 'cashier', 'Sell:Pos', 'cashier.terminal');
    }

    public function baristaLogin(StaffWorkspaceLoginRequest $request): RedirectResponse
    {
        return $this->login($request, 'barista', 'View:BaristaBoard', 'barista.orders');
    }

    public function pickupLogin(StaffWorkspaceLoginRequest $request): RedirectResponse
    {
        return $this->login($request, 'pickup', 'View:PickupBoard', 'pickup.orders');
    }

    public function cashierLogout(Request $request): RedirectResponse
    {
        return $this->logout($request, 'cashier', 'cashier.login');
    }

    public function baristaLogout(Request $request): RedirectResponse
    {
        return $this->logout($request, 'barista', 'barista.login');
    }

    public function pickupLogout(Request $request): RedirectResponse
    {
        return $this->logout($request, 'pickup', 'pickup.login');
    }

    private function loginPage(string $guard, string $permission, string $destination): View|RedirectResponse
    {
        $user = Auth::guard($guard)->user();

        if ($user instanceof User && $user->can($permission)) {
            return redirect()->route($destination);
        }

        return view('pages.staff.auth.login', ['workspace' => $guard]);
    }

    private function login(StaffWorkspaceLoginRequest $request, string $guard, string $permission, string $destination): RedirectResponse
    {
        $credentials = $request->validated();
        $credentials['email'] = mb_strtolower(trim($credentials['email']));

        if (! Auth::guard($guard)->attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة.']);
        }

        $user = Auth::guard($guard)->user();

        if (! $user instanceof User || ! $user->can($permission)) {
            Auth::guard($guard)->logout();

            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة.']);
        }

        $request->session()->regenerate();

        return redirect()->route($destination);
    }

    private function logout(Request $request, string $guard, string $loginRoute): RedirectResponse
    {
        Auth::guard($guard)->logout();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute);
    }
}
