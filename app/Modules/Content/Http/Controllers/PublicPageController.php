<?php

namespace App\Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Models\PublicPage;
use Illuminate\Contracts\View\View;

class PublicPageController extends Controller
{
    public function show(string $page): View
    {
        abort_unless(in_array($page, PublicPage::FIXED_KEYS, true), 404);

        $publicPage = PublicPage::query()
            ->published()
            ->where('key', $page)
            ->firstOrFail();

        return view('pages.public.site.public-page', [
            'page' => $publicPage,
            'title' => $publicPage->meta_title ?: $publicPage->title,
            'metaDescription' => $publicPage->meta_description,
        ]);
    }
}
