<?php

namespace App\Modules\Content\Models;

use Database\Factories\BlogPostCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_visible', 'sort_order'])]
class BlogPostCategory extends Model
{
    /** @use HasFactory<BlogPostCategoryFactory> */
    use HasFactory;

    protected static function newFactory(): BlogPostCategoryFactory
    {
        return BlogPostCategoryFactory::new();
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_visible' => true,
        'sort_order' => 0,
    ];

    /**
     * @return HasMany<BlogPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    /**
     * @param  Builder<BlogPostCategory>  $query
     * @return Builder<BlogPostCategory>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
