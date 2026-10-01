<?php

namespace App\Livewire\Store;

use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\BrowseProduct;
use App\Modules\Store\Actions\ResolveCart;
use App\Modules\Store\Enums\PricingChannel;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Services\PricingContextResolver;
use App\Modules\Store\Services\StorePricing;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Slimani\MediaManager\Models\File;

class ProductDetail extends Component
{
    protected BrowseProduct $browseProduct;

    protected PricingContextResolver $pricingContexts;

    protected StorePricing $pricing;

    protected StoreSettings $settings;

    #[Locked]
    public string $slug;

    public ?int $selectedOptionId = null;

    public int|string $quantity = 1;

    public ?string $cartToken = null;

    public ?string $feedback = null;

    public function boot(
        BrowseProduct $browseProduct,
        PricingContextResolver $pricingContexts,
        StorePricing $pricing,
        StoreSettings $settings,
    ): void {
        $this->browseProduct = $browseProduct;
        $this->pricingContexts = $pricingContexts;
        $this->pricing = $pricing;
        $this->settings = $settings;
    }

    public function mount(Product $product): void
    {
        $this->slug = $product->slug;
        $this->selectedOptionId = $product->options->firstWhere('is_default', true)?->id
            ?? $product->options->first()?->id;
        $this->cartToken = session('store_cart_token');
    }

    public function addToCart(AddCartItem $addCartItem, ResolveCart $resolveCart): void
    {
        $validated = $this->validate(['quantity' => ['required', 'integer', 'between:1,99']]);

        try {
            $product = $this->browseProduct->execute($this->slug);
            $option = $product->options->firstWhere('id', $this->selectedOptionId);

            if ($option === null) {
                throw ValidationException::withMessages(['selectedOptionId' => 'هذا الخيار غير متاح حاليًا.']);
            }

            $cart = $resolveCart->execute($this->cartToken, $this->customerId(), true, $this->minorProfileId());
            $addCartItem->execute($cart, $option, (int) $validated['quantity']);

            $this->cartToken = (string) $cart->token;
            session()->put('store_cart_token', $this->cartToken);
            session()->forget('store_checkout_idempotency_keys.'.hash('sha256', $this->cartToken));
            $this->feedback = 'أضيف المنتج إلى السلة.';
            $this->resetValidation();
            $this->dispatch('store-cart-updated');
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, (string) ($messages[0] ?? 'تعذر إضافة المنتج.'));
            }
        }
    }

    public function render(): View
    {
        $product = $this->browseProduct->execute($this->slug);
        $selectedOption = $product->options->firstWhere('id', $this->selectedOptionId)
            ?? $product->options->firstWhere('is_default', true)
            ?? $product->options->first();
        $pricingContext = $this->pricingContexts->forIdentity($this->customerId(), $this->minorProfileId(), PricingChannel::Storefront);
        $prices = $product->options->mapWithKeys(fn ($option): array => [$option->id => $this->pricing->forOption($option, $pricingContext)]);

        $galleryIds = collect($product->gallery_image_ids ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->reject(fn (int $id): bool => $id === (int) $product->featured_image_id)
            ->values();
        $galleryFiles = File::query()->with('media')->whereIn('id', $galleryIds)->get()->keyBy('id');
        $media = [];

        if ($videoUrl = $product->video?->getUrl()) {
            $media[] = ['type' => 'video', 'url' => $videoUrl, 'label' => 'فيديو '.$product->name];
        }

        if ($imageUrl = $product->featuredImage?->getUrl()) {
            $media[] = ['type' => 'image', 'url' => $imageUrl, 'label' => $product->name];
        }

        foreach ($galleryIds as $imageId) {
            $file = $galleryFiles->get($imageId);

            if ($imageUrl = $file?->getUrl()) {
                $media[] = ['type' => 'image', 'url' => $imageUrl, 'label' => $file->alt_text ?: $product->name];
            }
        }

        return view('livewire.store.product-detail', [
            'product' => $product,
            'selectedOption' => $selectedOption,
            'prices' => $prices,
            'media' => $media,
            'posterUrl' => $product->featuredImage?->getUrl(),
            'orderingEnabled' => $this->settings->ordering_enabled,
            'memberPricingEligible' => $pricingContext->isMember(),
        ]);
    }

    private function customerId(): ?int
    {
        $minorProfile = auth('minor-profile')->user();

        if ($minorProfile !== null) {
            $minorProfile->loadMissing('familyMember');

            return is_numeric($minorProfile->familyMember?->customer_id)
                ? (int) $minorProfile->familyMember->customer_id
                : null;
        }

        $identifier = auth('customer')->user()?->getAuthIdentifier();

        return is_numeric($identifier) ? (int) $identifier : null;
    }

    private function minorProfileId(): ?int
    {
        $identifier = auth('minor-profile')->user()?->getAuthIdentifier();

        return is_numeric($identifier) ? (int) $identifier : null;
    }
}
