<?php

namespace App\Http\Controllers;

use App\Models\DesignServiceRequest;
use App\Models\ProductDesignRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductDesignRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'desgin' => ['required', 'json'],
            'return_to' => ['nullable', 'string', 'max:255'],
        ]);

        $designPayload = json_decode(
            (string) $validated['desgin'],
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        if (! is_array($designPayload)) {
            return back()
                ->withErrors(['desgin' => 'The design payload must be a JSON object.'])
                ->withInput();
        }

        $validatedDesign = Validator::make($designPayload, [
            'source' => ['required', Rule::in(['product-page'])],
            'mode' => ['required', Rule::in(['upload', 'design-for-you', 'canva'])],
            'product_id' => ['nullable', 'integer'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'product_slug' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $mode = (string) $validatedDesign['mode'];

        if ($mode !== 'canva') {
            Validator::make($designPayload, [
                'email' => ['required', 'email', 'max:255'],
                'business_name' => ['required', 'string', 'max:255'],
                'card_info' => ['nullable', 'string', 'max:5000'],
                'business_card_type' => ['required', 'string', 'max:255'],
                'design_service_code' => [
                    'nullable',
                    'string',
                    Rule::in(array_keys(DesignServiceRequest::DESIGN_SERVICE_FEES)),
                ],
                'terms_accepted' => ['required', 'boolean', 'accepted'],
            ])->validate();
        }

        $request->validate($this->fileRules($mode));

        $designPayload['source'] = 'product-page';

        if ($mode === 'canva') {
            $designFile = $request->file('design_file');

            if ($designFile instanceof UploadedFile) {
                $designPayload['design_path'] = $designFile->store(
                    'product-designs/canva',
                    'public',
                );
            }
        } else {
            $logoPath = null;
            $logoFile = $request->file('logo_file');

            if ($logoFile instanceof UploadedFile) {
                $logoPath = $logoFile->store('product-designs/logos', 'public');
            }

            $examplePaths = [];
            $exampleFiles = $request->file('example_files', []);

            if (is_array($exampleFiles)) {
                foreach ($exampleFiles as $exampleFile) {
                    $examplePaths[] = $exampleFile->store(
                        'product-designs/examples',
                        'public',
                    );
                }
            }

            $designPayload['logo_path'] = $logoPath;
            $designPayload['example_paths'] = $examplePaths;
        }

        ProductDesignRequest::create([
            'desgin' => $designPayload,
        ]);

        $returnTo = $validated['return_to'] ?? null;

        if ($returnTo !== null && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return redirect()
                ->to($returnTo)
                ->with('success', 'Thanks — your design submission has been received.');
        }

        return redirect()
            ->route('home')
            ->with('success', 'Thanks — your design submission has been received.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function fileRules(string $mode): array
    {
        $fileRule = [
            'file',
            'max:20480',
            'mimes:jpg,jpeg,png,webp,pdf,svg,ai,eps,psd',
        ];

        return [
            'logo_file' => ['nullable', ...$fileRule],
            'example_files' => ['nullable', 'array', 'max:10'],
            'example_files.*' => $fileRule,
            'design_file' => [
                $mode === 'canva' ? 'required' : 'nullable',
                ...$fileRule,
            ],
        ];
    }
}
