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
        foreach (['design_file', 'logo_file', 'example_files'] as $field) {
            $this->normalizeFileField($request, $field);
        }

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

        if ($mode === 'canva') {
            Validator::make($designPayload, [
                'email' => ['nullable', 'email', 'max:255'],
                'order_name' => ['nullable', 'string', 'max:255'],
            ])->validate();
        } else {
            $designDetailsRules = [
                'email' => ['required', 'email', 'max:255'],
                'order_name' => ['nullable', 'string', 'max:255'],
                'business_name' => [
                    $mode === 'design-for-you' ? 'required' : 'nullable',
                    'string',
                    'max:255',
                ],
                'card_info' => ['nullable', 'string', 'max:5000'],
                'business_card_type' => ['required', 'string', 'max:255'],
                'design_service_code' => [
                    'nullable',
                    'string',
                    Rule::in(array_keys(DesignServiceRequest::DESIGN_SERVICE_FEES)),
                ],
                'terms_accepted' => ['required', 'boolean', 'accepted'],
            ];

            Validator::make($designPayload, $designDetailsRules)->validate();
        }

        $request->validate($this->fileRules($mode));

        $designPayload['source'] = 'product-page';

        if ($mode === 'canva') {
            $designPaths = $this->storeFiles(
                $request->file('design_file', []),
                'product-designs/canva',
            );

            if ($designPaths !== []) {
                $designPayload['design_path'] = $this->pathValue($designPaths);
            }
        } else {
            if ($mode === 'upload') {
                $designPaths = $this->storeFiles(
                    $request->file('design_file', []),
                    'product-designs/designs',
                );

                if ($designPaths !== []) {
                    $designPayload['design_path'] = $this->pathValue($designPaths);
                }
            }

            $logoPaths = $this->storeFiles(
                $request->file('logo_file', []),
                'product-designs/logos',
            );

            $examplePaths = [];
            $exampleFiles = $request->file('example_files', []);

            foreach ((array) $exampleFiles as $exampleFile) {
                if ($exampleFile instanceof UploadedFile) {
                    $examplePaths[] = $exampleFile->store(
                        'product-designs/examples',
                        'public',
                    );
                }
            }

            $designPayload['logo_path'] = $this->pathValue($logoPaths);
            $designPayload['example_paths'] = $examplePaths;
        }

        $designRequest = ProductDesignRequest::create([
            'desgin' => $designPayload,
        ]);

        $pendingRequestIds = $request->session()->get(
            'pending_product_design_request_ids',
            [],
        );

        if (! is_array($pendingRequestIds)) {
            $pendingRequestIds = [];
        }

        $request->session()->put(
            'pending_product_design_request_ids',
            array_values(array_unique([
                ...array_map('intval', $pendingRequestIds),
                (int) $designRequest->getKey(),
            ])),
        );

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
        $ancillaryFileRule = [
            'file',
            'max:20480',
            'mimes:jpg,jpeg,png,webp,pdf,svg,ai,eps,psd',
        ];
        $designFileRule = $mode === 'upload'
            ? [
                'file',
                'max:76800',
                'mimes:jpg,jpeg,png,psd,ai,eps,pdf,svg,tiff',
            ]
            : $ancillaryFileRule;

        return [
            'logo_file' => ['nullable', 'array', 'max:10'],
            'logo_file.*' => $ancillaryFileRule,
            'example_files' => ['nullable', 'array', 'max:10'],
            'example_files.*' => $ancillaryFileRule,
            'design_file' => [
                in_array($mode, ['canva', 'upload'], true)
                    ? 'required'
                    : 'nullable',
                'array',
                'max:10',
            ],
            'design_file.*' => $designFileRule,
        ];
    }

    private function normalizeFileField(Request $request, string $field): void
    {
        $files = $request->files->get($field);

        if ($files !== null && ! is_array($files)) {
            $request->files->set($field, [$files]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function storeFiles(mixed $files, string $directory): array
    {
        $paths = [];

        foreach ((array) $files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $file->store($directory, 'public');
            }
        }

        return $paths;
    }

    /**
     * Preserve the existing single-file payload shape while allowing new
     * submissions to retain every selected file.
     *
     * @param  array<int, string>  $paths
     * @return array<int, string>|string|null
     */
    private function pathValue(array $paths): array|string|null
    {
        return match (count($paths)) {
            0 => null,
            1 => $paths[0],
            default => $paths,
        };
    }
}
