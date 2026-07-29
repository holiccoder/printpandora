<?php

namespace App\Http\Controllers;

use App\Models\DesignServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignServiceRequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('business-card-design-service');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'business_name' => 'required|string|max:255',
            'card_info' => 'nullable|string|max:5000',
            'business_card_type' => 'required|string|max:255',
            'terms_accepted' => 'required|boolean|accepted',
        ]);

        DesignServiceRequest::create([
            'email' => $validated['email'],
            'business_name' => $validated['business_name'],
            'card_info' => $validated['card_info'] ?? null,
            'business_card_type' => $validated['business_card_type'],
            'terms_accepted' => $validated['terms_accepted'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('business-card-design-service')
            ->with('success', 'Thanks — our design team will contact you by email shortly.');
    }
}
