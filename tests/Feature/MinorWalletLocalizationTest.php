<?php

use App\Livewire\Store\Checkout;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\CreateOrder;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\States\Order\Confirmed;
use App\Modules\Store\States\Order\PendingPayment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('minor and order status labels follow the current locale without changing their values', function (): void {
    $order = new Order;
    $pending = new PendingPayment($order);
    $confirmed = new Confirmed($order);

    app()->setLocale('ar');
    expect(MinorProfileStatus::Active->label())->toBe('نشط')
        ->and($pending->label())->toBe('بانتظار الدفع')
        ->and($confirmed->getLabel())->toBe('مؤكد')
        ->and($pending->getValue())->toBe('pending_payment');

    app()->setLocale('en');
    expect(MinorProfileStatus::Active->label())->toBe('Active')
        ->and($pending->label())->toBe('Pending payment')
        ->and($confirmed->getLabel())->toBe('Confirmed');
});

test('checkout localizes domain errors and leaves the order attempt unpaid', function (string $locale, string $message): void {
    app()->setLocale($locale);
    $product = Product::factory()->active()->create(['category_id' => Category::factory()->create()->id]);
    $option = $product->defaultOption()->firstOrFail();
    $option->update(['price_baisa' => 1500]);
    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $option, 1);
    session()->put('store_cart_token', $cart->token);
    $this->mock(CreateOrder::class)->shouldReceive('execute')->once()
        ->andThrow(ValidationException::withMessages(['payment' => 'The wallet balance is insufficient.']));

    Livewire::test(Checkout::class)
        ->set('customerName', 'Test Customer')
        ->set('customerPhone', '+96891234567')
        ->call('placeOrder')
        ->assertHasErrors('payment')
        ->assertSee($message)
        ->assertSet('submitting', false);

    expect($cart->items()->count())->toBe(1)
        ->and(Order::query()->count())->toBe(0);
})->with([
    'Arabic' => ['ar', 'الرصيد المتاح في المحفظة غير كافٍ. يرجى طلب شحنها من وليّ الأمر.'],
    'English' => ['en', 'The wallet balance is insufficient.'],
]);

test('minor and wallet forms have Arabic validation field names', function (): void {
    app()->setLocale('ar');
    $validator = Validator::make([], [
        'member_code' => ['required'],
        'amount_omr' => ['required'],
        'token' => ['required'],
        'password_confirmation' => ['required'],
    ]);

    expect($validator->errors()->first('member_code'))->toBe('حقل رمز الدخول مطلوب.')
        ->and($validator->errors()->first('amount_omr'))->toBe('حقل مبلغ الشحن بالريال العُماني مطلوب.')
        ->and($validator->errors()->first('token'))->toBe('حقل رمز التفعيل مطلوب.')
        ->and($validator->errors()->first('password_confirmation'))->toBe('حقل تأكيد كلمة المرور مطلوب.');
});

test('prior minor payment and verification errors have Arabic translations without changing English fallback', function (string $message): void {
    app()->setLocale('ar');
    expect(__($message))->not->toBe($message)->toMatch('/[\x{0600}-\x{06FF}]/u');
    app()->setLocale('en');
    expect(__($message))->toBe($message);
})->with([
    'The inventory reservation is no longer available.',
    'This order must be paid by the guardian.',
    'Wallet spending is not currently authorized.',
    'The verification code is incorrect.',
    'The verification code has expired.',
    'Verify the guardian phone before adding wallet funds.',
    'Empty or cancel the child wallet before requesting account deletion.',
]);
