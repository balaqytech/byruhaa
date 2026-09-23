<?php

use App\Modules\Identity\Models\Customer;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = Customer::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, CustomerResetPasswordNotification::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = Customer::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, CustomerResetPasswordNotification::class, function ($notification) {
        $response = $this->get(route('password.reset', $notification->token));

        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = Customer::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, CustomerResetPasswordNotification::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        return true;
    });
});

test('reset password email uses the Byruhaa branded Arabic template', function () {
    $user = Customer::factory()->make(['name' => 'مريم']);
    $notification = new CustomerResetPasswordNotification('reset-token');

    $message = $notification->toMail($user);
    $html = $message->render();

    expect($message->subject)->toBe('إعادة تعيين كلمة مرور حسابك في بيرحاء')
        ->and($html)->toContain('مرحبًا مريم')
        ->and($html)->toContain('تعيين كلمة مرور جديدة')
        ->and($html)->toContain('reset-token')
        ->and($html)->toContain('فريق بيرحاء');
});
