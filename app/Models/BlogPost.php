<?php

namespace App\Models;

use App\Enums\BlogPostStatus;
use Database\Factories\BlogPostFactory;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;
use Slimani\MediaManager\Form\RichEditor\MediaManagerRichContentPlugin;
use Slimani\MediaManager\Models\File;

/**
 * @property int $id
 * @property int|null $blog_post_category_id
 * @property int|null $featured_image_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $content
 * @property string|null $featured_image_path
 * @property BlogPostStatus $status
 * @property Carbon|null $published_at
 * @property string|null $meta_title
 * @property string|null $meta_description
 */
#[Fillable(['blog_post_category_id', 'title', 'slug', 'excerpt', 'content', 'featured_image_path', 'featured_image_id', 'status', 'published_at', 'meta_title', 'meta_description'])]
class BlogPost extends Model implements HasRichContent
{
    /** @use HasFactory<BlogPostFactory> */
    use HasFactory;

    use InteractsWithMediaFiles;
    use InteractsWithRichContent;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
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
     * @return BelongsTo<BlogPostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogPostCategory::class, 'blog_post_category_id');
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function featuredImage(): BelongsTo
    {
        return $this->mediaFile('featured_image_id');
    }

    /**
     * @param  Builder<BlogPost>  $query
     * @return Builder<BlogPost>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', BlogPostStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BlogPostStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
