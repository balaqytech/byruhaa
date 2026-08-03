<?php

use App\Modules\Events\Models\Event;
use App\Modules\Finance\Models\Payment;
use App\Support\Money\MoneyFactory;
use Brick\Money\Money;

test('money baisa casts store decimal strings as integer baisa', function () {
    $event = Event::factory()->create([
        'price' => '10.500',
        'currency' => 'OMR',
    ]);

    expect($event->price_baisa)->toBe(10500)
        ->and($event->price)->toBeInstanceOf(Money::class)
        ->and(MoneyFactory::formatMoneyAmount($event->price))->toBe('10.500');
});

test('money baisa casts store money objects as integer baisa', function () {
    $payment = Payment::factory()->create([
        'amount' => MoneyFactory::fromMinor(250, 'OMR'),
        'currency' => 'OMR',
    ]);

    expect($payment->amount_baisa)->toBe(250)
        ->and($payment->amount)->toBeInstanceOf(Money::class)
        ->and(MoneyFactory::formatMoneyAmount($payment->amount))->toBe('0.250');
});
