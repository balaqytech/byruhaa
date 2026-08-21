<?php

namespace App\Modules\Content\Models;

use App\Modules\Content\Enums\PublicPageStatus;
use Database\Factories\PublicPageFactory;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Slimani\MediaManager\Form\RichEditor\MediaManagerRichContentPlugin;

/**
 * @property int $id
 * @property string $key
 * @property string $title
 * @property string $content
 * @property PublicPageStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $effective_at
 * @property int $version
 * @property string|null $meta_title
 * @property string|null $meta_description
 */
#[Fillable(['key', 'title', 'content', 'status', 'published_at', 'effective_at', 'version', 'meta_title', 'meta_description'])]
class PublicPage extends Model implements HasRichContent
{
    /** @var array<int, string> */
    public const FIXED_KEYS = [
        'refund-cancellation',
        'privacy',
        'terms',
        'pickup',
        'student-accounts',
        'faq',
        'allergens',
        'affiliate-terms',
    ];

    /** @use HasFactory<PublicPageFactory> */
    use HasFactory;

    use InteractsWithRichContent;

    protected static function newFactory(): PublicPageFactory
    {
        return PublicPageFactory::new();
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => PublicPageStatus::Draft,
        'version' => 1,
    ];

    public function setUpRichContent(): void
    {
        $this->registerRichContent('content')
            ->plugins([
                MediaManagerRichContentPlugin::make()
                    ->acceptedFileTypes(['image/*']),
            ]);
    }

    /**
     * @param  Builder<PublicPage>  $query
     * @return Builder<PublicPage>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PublicPageStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === PublicPageStatus::Published
            && $this->published_at?->isPast() === true;
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PublicPageStatus::class,
            'published_at' => 'datetime',
            'effective_at' => 'datetime',
            'version' => 'integer',
        ];
    }
}
