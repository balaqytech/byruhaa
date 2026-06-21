<?php

use App\Support\Money\MoneyFactory;
use App\Support\MoneyFormatter;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

test('it creates money from minor units', function () {
    $money = MoneyFactory::fromMinor(10500);

    expect($money)
        ->toBeInstanceOf(Money::class)
        ->and($money->getCurrency()->getCurrencyCode())->toBe('OMR')
        ->and($money->getMinorAmount()->toInt())->toBe(10500)
        ->and((string) $money->getAmount())->toBe('10.500');
});

test('it converts user entered omr strings to baisa', function (string $amount, int $baisa) {
    expect(MoneyFactory::omrStringToBaisa($amount))->toBe($baisa);
})->with([
    'one rial' => ['1.000', 1000],
    'ten rial and five hundred baisa' => ['10.500', 10500],
    'two hundred fifty baisa' => ['0.250', 250],
    'one baisa' => ['0.001', 1],
    'plain major unit' => ['1', 1000],
    'currency prefix' => ['OMR 1.250', 1250],
    'currency suffix' => ['1.250 OMR', 1250],
]);

test('it formats omr with three decimals without float math', function (int $baisa, string $formatted) {
    expect(MoneyFactory::formatOmr($baisa))->toBe($formatted);
})->with([
    'one rial' => [1000, 'OMR 1.000'],
    'ten rial and five hundred baisa' => [10500, 'OMR 10.500'],
    'two hundred fifty baisa' => [250, 'OMR 0.250'],
    'one baisa' => [1, 'OMR 0.001'],
    'negative one baisa' => [-1, 'OMR -0.001'],
]);

test('money formatter defaults missing currencies to omr', function () {
    expect(MoneyFormatter::baisa(2500, null))->toBe('OMR 2.500');
});

test('it requires explicit rounding for more than three omr decimals', function () {
    expect(fn () => MoneyFactory::omrStringToBaisa('1.0005'))
        ->toThrow(RoundingNecessaryException::class);

    expect(MoneyFactory::omrStringToBaisa('1.0005', RoundingMode::HalfUp))->toBe(1001)
        ->and(MoneyFactory::omrStringToBaisa('1.0005', RoundingMode::Down))->toBe(1000);
});
