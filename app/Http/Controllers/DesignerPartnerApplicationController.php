<?php

namespace App\Http\Controllers;

use App\Models\DesignerPartnerApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DesignerPartnerApplicationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_or_company' => 'required|string|max:255',
            'country_or_region' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'website' => 'required|url|max:2048',
            'portfolio_links' => 'required|string|max:5000',
            'design_field' => 'required|string|max:255',
            'expected_products_finishes' => 'required|string|max:5000',
        ]);

        DesignerPartnerApplication::create([
            ...$validated,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->to(route('designer-partner-program').'#application-requirements-form')
            ->with(
                'success',
                'Thanks for applying. Our team will review your details and get back to you soon.',
            );
    }
}
