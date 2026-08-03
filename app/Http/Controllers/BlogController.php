<?php

namespace App\Http\Controllers;

use App\Modules\Content\Models\BlogPost;
use App\Modules\Content\Models\BlogPostCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('pages.public.blog.index', [
            'categories' => $this->visibleCategories(),
            'posts' => BlogPost::query()
                ->publiclyVisible()
                ->with(['category', 'featuredImage'])
                ->latest('published_at')
                ->paginate(9),
            'title' => __('ui.blog.heading'),
            'metaDescription' => __('ui.blog.subheading'),
        ]);
    }

    public function show(BlogPost $post): View
    {
        abort_unless(BlogPost::query()->publiclyVisible()->whereKey($post)->exists(), 404);

        $post->loadMissing(['category', 'featuredImage', 'socialShareImage']);

        return view('pages.public.blog.show', [
            'categories' => $this->visibleCategories(),
            'post' => $post,
            'title' => $post->meta_title ?: $post->title,
            'metaDescription' => $post->meta_description ?: $post->excerpt,
            'metaImage' => $post->socialShareImageUrl(),
        ]);
    }

    public function category(BlogPostCategory $category): View
    {
        abort_unless($category->is_visible, 404);

        return view('pages.public.blog.index', [
            'categories' => $this->visibleCategories(),
            'currentCategory' => $category,
            'posts' => BlogPost::query()
                ->publiclyVisible()
                ->whereBelongsTo($category, 'category')
                ->with(['category', 'featuredImage'])
                ->latest('published_at')
                ->paginate(9),
            'title' => $category->name,
            'metaDescription' => $category->description,
        ]);
    }

    /**
     * @return Collection<int, BlogPostCategory>
     */
    private function visibleCategories(): Collection
    {
        return BlogPostCategory::query()
            ->visible()
            ->withCount([
                'posts' => fn (Builder $query): Builder => $query->publiclyVisible(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
