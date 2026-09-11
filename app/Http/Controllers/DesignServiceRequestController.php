<?php

namespace App\Http\Controllers;

use App\Models\DesignServiceRequest;
use App\Services\Cart;
use App\Support\DesignServiceProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DesignServiceRequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('business-card-design-service');
    }

    public function store(Request $request, Cart $cart): RedirectResponse
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
            'logo_file' => [
                'nullable',
                'file',
                'max:20480',
                'mimes:jpg,jpeg,png,webp,pdf,svg,ai,eps,psd',
            ],
            'example_files' => ['nullable', 'array', 'max:10'],
            'example_files.*' => [
                'file',
                'max:20480',
                'mimes:jpg,jpeg,png,webp,pdf,svg,ai,eps,psd',
            ],
            'return_to' => 'nullable|string|max:255',
            'terms_accepted' => 'required|boolean|accepted',
        ]);

        $designServiceCode = $validated['design_service_code'] ?? null;
        $logoPath = null;
        $logoFile = $request->file('logo_file');

        if ($logoFile instanceof UploadedFile) {
            $logoPath = $logoFile->store('design-service/logos', 'public');
        }

        $examplePaths = [];
        $exampleFiles = $request->file('example_files', []);

        if (is_array($exampleFiles)) {
            foreach ($exampleFiles as $exampleFile) {
                if ($exampleFile instanceof UploadedFile) {
                    $examplePaths[] = $exampleFile->store(
                        'design-service/examples',
                        'public',
                    );
                }
            }
        }

        $designServiceRequest = DesignServiceRequest::create([
            'email' => $validated['email'],
            'business_name' => $validated['business_name'],
            'card_info' => $validated['card_info'] ?? null,
            'business_card_type' => $validated['business_card_type'],
            'design_service_code' => $designServiceCode,
            'design_service_fee' => $designServiceCode !== null
                ? DesignServiceRequest::DESIGN_SERVICE_FEES[$designServiceCode]
                : null,
            'terms_accepted' => $validated['terms_accepted'],
            'logo_path' => $logoPath,
            'example_paths' => $examplePaths === [] ? null : $examplePaths,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($designServiceCode !== null) {
            $cart->add(
                DesignServiceProduct::resolve()->getKey(),
                [
                    'design_service' => $designServiceCode,
                    'design_service_request_id' => (int) $designServiceRequest->getKey(),
                ],
            );

            $pendingRequestIds = $request->session()->get(
                'pending_design_service_request_ids',
                [],
            );

            if (! is_array($pendingRequestIds)) {
                $pendingRequestIds = [];
            }

            $request->session()->put(
                'pending_design_service_request_ids',
                array_values(array_unique([
                    ...array_map('intval', $pendingRequestIds),
                    (int) $designServiceRequest->getKey(),
                ])),
            );
        }

        // Product-page modals pass return_to so the shopper lands back on the
        // product detail page. The standalone page passes /checkout after a
        // paid design service has been added to the cart.
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
