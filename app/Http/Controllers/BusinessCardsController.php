<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class BusinessCardsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('shop/business-cards');
    }
}
