<?php

namespace App\Http\Controllers;

use App\Models\Showcase;
use App\Models\ShowcaseCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowcaseController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = ShowcaseCategory::query()
            ->withCount('showcases')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'sort_order']);

        $activeCategorySlug = $request->query('category');
        $activeCategory = $categories->firstWhere('slug', $activeCategorySlug);

        return Inertia::render('showcases', [
            'categories' => $categories,
            'active_category' => $activeCategory?->slug,
            'showcases' => Showcase::query()
                ->when(
                    $activeCategory,
                    fn ($query) => $query->where('category_id', $activeCategory->getKey()),
                )
                ->orderBy('id')
                ->paginate(16, ['id', 'link', 'image_url', 'category_id'])
                ->withQueryString(),
        ]);
    }
}
