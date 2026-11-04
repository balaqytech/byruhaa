<?php

use App\Livewire\Store\CoffeeStore;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Actions\ValidateCategoryImage;
use App\Modules\Store\Filament\Resources\Categories\Pages\CreateCategory;
use App\Modules\Store\Filament\Resources\Categories\Pages\EditCategory;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Slimani\MediaManager\Models\File;

function categoryMediaFile(string $name, string $mimeType, int $size = 1024): File
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

test('category image is stored and rendered without external stock images', function (): void {
    expect(Schema::hasColumn('store_categories', 'image_id'))->toBeTrue();

    $image = categoryMediaFile('category-card.webp', 'image/webp');
    $category = Category::factory()->create(['image_id' => $image->id]);
    $product = Product::factory()->active()->create(['category_id' => $category->id]);
    $product->defaultOption()->update(['price_baisa' => 1500]);

    expect($category->fresh()->image?->is($image))->toBeTrue();

    Livewire::test(CoffeeStore::class)
        ->assertSee('category-card.webp', false)
        ->assertDontSee('images.unsplash.com', false);
});

test('category editor accepts a media image and validates existing media choices', function (): void {
    $category = Category::factory()->create();
    $image = categoryMediaFile('category-card.jpg', 'image/jpeg');
    $video = categoryMediaFile('category-video.mp4', 'video/mp4');
    $oversizedImage = categoryMediaFile('large-category.png', 'image/png', 6 * 1024 * 1024);

    expect(fn () => app(ValidateCategoryImage::class)->execute(['image_id' => $video->id]))
        ->toThrow(ValidationException::class);
    expect(fn () => app(ValidateCategoryImage::class)->execute(['image_id' => $oversizedImage->id]))
        ->toThrow(ValidationException::class);

    $this->actingAs(User::factory()->create(), 'web');

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->assertFormFieldExists('image_id')
        ->fillForm(['image_id' => (string) $image->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->fresh()->image_id)->toBe($image->id);

    Livewire::test(CreateCategory::class)
        ->assertFormFieldExists('image_id')
        ->fillForm([
            'name' => 'New Category',
            'slug' => 'new-category',
            'image_id' => (string) $image->id,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Category::query()->where('slug', 'new-category')->firstOrFail()->image_id)->toBe($image->id);
});
