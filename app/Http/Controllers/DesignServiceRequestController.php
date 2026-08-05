<?php

namespace App\Http\Controllers;

use App\Models\DesignServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'design_service_code' => [
                'nullable',
                'string',
                Rule::in(array_keys(DesignServiceRequest::DESIGN_SERVICE_FEES)),
            ],
            'return_to' => 'nullable|string|max:255',
            'terms_accepted' => 'required|boolean|accepted',
        ]);

        $designServiceCode = $validated['design_service_code'] ?? null;

        DesignServiceRequest::create([
            'email' => $validated['email'],
            'business_name' => $validated['business_name'],
            'card_info' => $validated['card_info'] ?? null,
            'business_card_type' => $validated['business_card_type'],
            'design_service_code' => $designServiceCode,
            'design_service_fee' => $designServiceCode !== null
                ? DesignServiceRequest::DESIGN_SERVICE_FEES[$designServiceCode]
                : null,
            'terms_accepted' => $validated['terms_accepted'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Product-page modals pass return_to so the shopper lands back on the
        // product detail page instead of the standalone design service page.
        $returnTo = $validated['return_to'] ?? null;

        if ($returnTo !== null && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return redirect()
                ->to($returnTo)
                ->with('success', 'Thanks — our design team will contact you by email shortly.');
        }

        return redirect()
            ->route('business-card-design-service')
            ->with('success', 'Thanks — our design team will contact you by email shortly.');
    }
}
