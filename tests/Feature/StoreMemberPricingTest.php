<?php

use App\Livewire\Store\CoffeeStore;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\CreateOrder;
use App\Modules\Store\Actions\QuoteCart;
use App\Modules\Store\Actions\ResolveCart;
use App\Modules\Store\Enums\PricingChannel;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function memberPricedOption(array $attributes = []): ProductOption
{
    $product = Product::factory()->active()->for(Category::factory())->create(['name' => 'Member Coffee']);
    $option = $product->defaultOption()->firstOrFail();
    $option->update(array_merge([
        'name' => 'Standard',
        'sku' => 'MEMBER-'.Str::upper(Str::random(8)),
        'price_baisa' => 1600,
        'member_price_baisa' => 1100,
    ], $attributes));

    return $option->load('product.category');
}

function memberPricingCart(ProductOption $option, ?int $customerId = null, ?int $minorProfileId = null): Cart
{
    $cart = Cart::factory()->create([
        'customer_id' => $customerId,
        'minor_profile_id' => $minorProfileId,
    ]);
    app(AddCartItem::class)->execute($cart, $option, 1);

    return $cart;
}

test('member price must be positive and lower than the regular price', function (int $memberPrice): void {
    $option = memberPricedOption();

    expect(fn (): bool => $option->update(['member_price_baisa' => $memberPrice]))
        ->toThrow(ValidationException::class);
})->with([0, 1600, 1700]);

test('guest customer and minor carts receive the correct server-side price and VAT', function (): void {
    $option = memberPricedOption();
    $customer = Customer::factory()->create();
    $minor = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();

    $guestQuote = app(QuoteCart::class)->execute(memberPricingCart($option));
    $customerQuote = app(QuoteCart::class)->execute(memberPricingCart($option, $customer->id));
    $minorQuote = app(QuoteCart::class)->execute(memberPricingCart($option, $customer->id, $minor->id));

    expect($guestQuote)
        ->pricing_tier->toBe('standard')
        ->regular_total_baisa->toBe(1600)
        ->discount_baisa->toBe(0)
        ->total_baisa->toBe(1600)
        ->vat_baisa->toBe(76)
        ->and($customerQuote)
        ->pricing_tier->toBe('member')
        ->regular_total_baisa->toBe(1600)
        ->discount_baisa->toBe(500)
        ->total_baisa->toBe(1100)
        ->subtotal_baisa->toBe(1048)
        ->vat_baisa->toBe(52)
        ->and($minorQuote['total_baisa'])->toBe(1100)
        ->and($minorQuote['items'][0]['regular_unit_price_baisa'])->toBe(1600)
        ->and($minorQuote['items'][0]['unit_price_baisa'])->toBe(1100)
        ->and($minorQuote['items'][0]['line_discount_baisa'])->toBe(500);
});

test('claiming a guest cart for a customer reprices it as a member cart', function (): void {
    $option = memberPricedOption();
    $customer = Customer::factory()->create();
    $guestCart = memberPricingCart($option);

    expect(app(QuoteCart::class)->execute($guestCart))
        ->pricing_tier->toBe('standard')
        ->total_baisa->toBe(1600);

    $claimedCart = app(ResolveCart::class)->execute($guestCart->token, $customer->id, false);

    expect($claimedCart->customer_id)->toBe($customer->id)
        ->and(app(QuoteCart::class)->execute($claimedCart))
        ->pricing_tier->toBe('member')
        ->total_baisa->toBe(1100)
        ->discount_baisa->toBe(500);
});

test('empty cart quotes preserve channel and member eligibility', function (): void {
    $customer = Customer::factory()->create();
    $guestCart = Cart::factory()->create(['customer_id' => null]);
    $memberCart = Cart::factory()->create(['customer_id' => $customer->id]);

    expect(app(QuoteCart::class)->executeOrEmpty($guestCart, PricingChannel::Api))
        ->pricing_tier->toBe('standard')
        ->items->toBe([])
        ->total_baisa->toBe(0)
        ->and(app(QuoteCart::class)->executeOrEmpty($memberCart, PricingChannel::Uchat))
        ->pricing_tier->toBe('member')
        ->items->toBe([])
        ->discount_baisa->toBe(0);
});

test('orders preserve member pricing snapshots after the catalog price changes', function (): void {
    $settings = app(StoreSettings::class);
    $settings->ordering_enabled = true;
    $settings->save();

    $customer = Customer::factory()->create();
    $option = memberPricedOption();
    $cart = memberPricingCart($option, $customer->id);

    $order = app(CreateOrder::class)->execute($cart, [
        'idempotency_key' => (string) Str::uuid(),
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_phone' => $customer->phone_number,
        'customer_email' => $customer->email,
        'pickup_type' => 'immediate',
        'payment_method' => 'thawani',
    ]);

    $option->update(['member_price_baisa' => 900]);
    $order->refresh()->load('items');

    expect($order)
        ->pricing_tier->toBe('member')
        ->regular_total_baisa->toBe(1600)
        ->discount_baisa->toBe(500)
        ->total_baisa->toBe(1100)
        ->and($order->items->sole())
        ->regular_unit_price_baisa->toBe(1600)
        ->unit_price_baisa->toBe(1100)
        ->unit_discount_baisa->toBe(500)
        ->line_discount_baisa->toBe(500);
});

test('catalog shows both prices to guests and advertises member login', function (): void {
    $option = memberPricedOption();

    Livewire::test(CoffeeStore::class)
        ->assertSee('سجّل الدخول لتحصل على سعر الأعضاء تلقائيًا')
        ->assertSee(route('login'), false)
        ->assertSee('1.600')
        ->assertSee('1.100');
});

test('UChat exposes both catalog prices and applies member pricing only to known accounts', function (): void {
    $originalApiToken = config('byruhaa.uchat.api_token');
    $originalOwnerSecret = config('byruhaa.uchat.owner_key_secret');
    config([
        'byruhaa.uchat.api_token' => 'member-pricing-secret',
        'byruhaa.uchat.owner_key_secret' => 'member-owner-secret',
    ]);
    try {
        $option = memberPricedOption();
        Customer::factory()->create(['phone_number' => '+96891234567']);
        $knownHeaders = ['Authorization' => 'Bearer member-pricing-secret', 'X-WhatsApp-Phone' => '91234567'];
        $guestHeaders = ['Authorization' => 'Bearer member-pricing-secret', 'X-WhatsApp-Phone' => '92345678'];

        $this->withHeaders($knownHeaders)
            ->getJson('/api/v1/integrations/uchat/store/catalog')
            ->assertSuccessful()
            ->assertJsonPath('pricing_tier', 'member')
            ->assertJsonPath('data.0.products.0.options.0.price_baisa', 1600)
            ->assertJsonPath('data.0.products.0.options.0.regular_price_baisa', 1600)
            ->assertJsonPath('data.0.products.0.options.0.member_price_baisa', 1100);

        $this->withHeaders($knownHeaders)
            ->postJson('/api/v1/integrations/uchat/store/cart/items', ['sku' => $option->sku, 'quantity' => 1])
            ->assertSuccessful()
            ->assertJsonPath('data.quote.pricing_tier', 'member')
            ->assertJsonPath('data.quote.total_baisa', 1100)
            ->assertJsonPath('data.quote.discount_baisa', 500);

        $this->withHeaders($guestHeaders)
            ->postJson('/api/v1/integrations/uchat/store/cart/items', ['sku' => $option->sku, 'quantity' => 1])
            ->assertSuccessful()
            ->assertJsonPath('data.quote.pricing_tier', 'standard')
            ->assertJsonPath('data.quote.total_baisa', 1600)
            ->assertJsonPath('data.quote.discount_baisa', 0);
    } finally {
        config([
            'byruhaa.uchat.api_token' => $originalApiToken,
            'byruhaa.uchat.owner_key_secret' => $originalOwnerSecret,
        ]);
    }
});
