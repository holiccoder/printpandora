<?php

namespace App\Http\Controllers;

use App\Models\Showcase;
use Inertia\Inertia;
use Inertia\Response;

class ShowcaseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('showcases', [
            'showcases' => Showcase::query()
                ->orderBy('id')
                ->paginate(16, ['id', 'link', 'image_url'])
                ->withQueryString(),
        ]);
    }
}
