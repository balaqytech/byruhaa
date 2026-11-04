<?php

use App\Livewire\Store\CoffeeStore;
use App\Livewire\Store\ProductDetail;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Actions\BrowseProduct;
use App\Modules\Store\Actions\ValidateProductMedia;
use App\Modules\Store\Filament\Resources\Products\Pages\EditProduct;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Slimani\MediaManager\Models\File;

function productMediaFile(string $name, string $mimeType, int $size = 1024): File
{
    $file = File::query()->create([
        'name' => $name,
        'alt_text' => $name,
        'mime_type' => $mimeType,
        'size' => $size,
        'extension' => substr($name, strrpos($name, '.') + 1),
    ]);

    $file->media()->create([
        'collection_name' => 'default',
        'name' => $name,
        'file_name' => $name,
        'mime_type' => $mimeType,
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => $size,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
    ]);

    return $file;
}

test('products store an optional video and ordered gallery with media validation', function (): void {
    expect(Schema::hasColumns('store_products', ['video_id', 'gallery_image_ids']))->toBeTrue();
    expect(config('livewire.temporary_file_upload.rules'))->toContain('max:51200');

    $video = productMediaFile('product.mp4', 'video/mp4');
    $featured = productMediaFile('featured.jpg', 'image/jpeg');
    $gallery = productMediaFile('gallery.jpg', 'image/jpeg');
    $product = Product::factory()->create([
        'video_id' => $video->id,
        'featured_image_id' => $featured->id,
        'gallery_image_ids' => [$gallery->id, $featured->id],
    ]);

    expect($product->fresh()->video->id)->toBe($video->id)
        ->and($product->fresh()->gallery_image_ids)->toBe([$gallery->id, $featured->id])
        ->and(app(ValidateProductMedia::class)->execute([
            'video_id' => $video->id,
            'gallery_image_ids' => [$gallery->id],
        ]))->toBeArray();
});

test('product media validation rejects wrong file types and oversized uploads', function (): void {
    $image = productMediaFile('image.jpg', 'image/jpeg');
    $largeVideo = productMediaFile('large.mp4', 'video/mp4', 51 * 1024 * 1024);
    $video = productMediaFile('video.webm', 'video/webm');

    expect(fn () => app(ValidateProductMedia::class)->execute(['video_id' => $image->id]))
        ->toThrow(ValidationException::class);
    expect(fn () => app(ValidateProductMedia::class)->execute(['video_id' => $largeVideo->id]))
        ->toThrow(ValidationException::class);
    expect(fn () => app(ValidateProductMedia::class)->execute(['gallery_image_ids' => [$video->id]]))
        ->toThrow(ValidationException::class);
    expect(fn () => app(ValidateProductMedia::class)->execute(['gallery_image_ids' => array_fill(0, 9, $image->id)]))
        ->toThrow(ValidationException::class);
});

test('product page leads with video then featured image and gallery while cards use lazy video', function (): void {
    $video = productMediaFile('coffee.mp4', 'video/mp4');
    $featured = productMediaFile('featured.jpg', 'image/jpeg');
    $gallery = productMediaFile('extra.jpg', 'image/jpeg');
    $product = Product::factory()->active()->create([
        'name' => 'قهوة الاختبار',
        'video_id' => $video->id,
        'featured_image_id' => $featured->id,
        'gallery_image_ids' => [$gallery->id],
    ]);
    $product->defaultOption()->update(['price_baisa' => 1500]);

    $this->get(route('coffee.product', ['slug' => $product->slug]))
        ->assertSuccessful()
        ->assertSeeInOrder(['data-product-main-video', 'alt="قهوة الاختبار"', 'alt="extra.jpg"'], false)
        ->assertSee('وسائط المنتج');

    Livewire::test(CoffeeStore::class)
        ->assertSee('data-product-card-video', false)
        ->assertSee('data-src=', false)
        ->assertSee(route('coffee.product', ['slug' => $product->slug]), false)
        ->assertDontSee('product-details-title', false);
});

test('product page hides draft and unavailable products', function (): void {
    $draft = Product::factory()->create();
    $product = Product::factory()->active()->create();
    $product->defaultOption()->update(['price_baisa' => 1500]);

    $this->get(route('coffee.product', ['slug' => $draft->slug]))->assertNotFound();
    $this->get(route('coffee.product', ['slug' => $product->slug]))->assertSuccessful();

    $product->defaultOption()->update(['is_available' => false]);
    $this->get(route('coffee.product', ['slug' => $product->slug]))->assertNotFound();
});

test('product page validates quantity and adds the chosen option to the existing cart', function (): void {
    $product = Product::factory()->active()->create();
    $option = $product->defaultOption()->firstOrFail();
    $option->update(['price_baisa' => 1500]);
    $settings = app(StoreSettings::class);
    $settings->ordering_enabled = true;
    $settings->save();

    Livewire::test(ProductDetail::class, ['product' => app(BrowseProduct::class)->execute($product->slug)])
        ->set('quantity', 0)
        ->call('addToCart')
        ->assertHasErrors('quantity')
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertHasNoErrors()
        ->assertDispatched('store-cart-updated');

    expect(Cart::query()->firstOrFail()->items()->sole()->quantity)->toBe(2);
});

test('product edit form exposes video upload and reorderable gallery', function (): void {
    $product = Product::factory()->create();
    $product->defaultOption()->update(['price_baisa' => 1000]);
    $video = productMediaFile('admin-coffee.mp4', 'video/mp4');
    $galleryImage = productMediaFile('admin-coffee.jpg', 'image/jpeg');
    $this->actingAs(User::factory()->create(), 'web');

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertFormFieldExists('video_id')
        ->assertFormFieldExists('gallery_image_ids')
        ->fillForm([
            'video_id' => (string) $video->id,
            'gallery_image_ids' => [(string) $galleryImage->id],
        ])
        ->set('data.options.record-'.$product->defaultOption()->firstOrFail()->id.'.price', '1.000')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->video_id)->toBe($video->id)
        ->and($product->fresh()->gallery_image_ids)->toBe([(string) $galleryImage->id]);
});

test('editing a product keeps its existing option prices without re-entry', function (): void {
    $product = Product::factory()->create();
    $option = $product->defaultOption()->firstOrFail();
    $option->update(['price_baisa' => 1600, 'member_price_baisa' => 1200]);
    $this->actingAs(User::factory()->create(), 'web');

    $component = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()]);
    $optionState = $component->get('data')['options']['record-'.$option->id];

    expect($optionState['price'])->toBe('1.600')
        ->and($optionState['member_price'])->toBe('1.200');

    $component->call('save')->assertHasNoFormErrors();

    expect($option->fresh()->price_baisa)->toBe(1600)
        ->and($option->fresh()->member_price_baisa)->toBe(1200);
});
