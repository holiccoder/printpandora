<?php

namespace App\Services;

use App\Models\DesignServiceRequest;
use App\Models\Order;
use App\Models\ProductDesignRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductDesignCheckoutService
{
    /**
     * Create product design requests only when checkout submits the files.
     *
     * The storefront keeps the files in IndexedDB until this method receives
     * the checkout multipart request. The client id is retained in the JSON
     * payload so retries of a payment request remain idempotent.
     */
    public function attach(Request $request, Order $order, Cart $cart): void
    {
        $rawDrafts = $request->input('pending_product_designs');

        if ($rawDrafts === null || $rawDrafts === '') {
            if ($request->isMethod('get')) {
                return;
            }

            $this->ensurePendingDesignsWereSubmitted($order, $cart);

            return;
        }

        if (! is_string($rawDrafts)) {
            $this->fail('pending_product_designs', 'The pending design payload is invalid.');
        }

        try {
            $decodedDrafts = json_decode($rawDrafts, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->fail('pending_product_designs', 'The pending design payload is invalid.');
        }

        $drafts = Validator::make(
            ['drafts' => $decodedDrafts],
            ['drafts' => ['required', 'array', 'max:50']],
        )->validate()['drafts'];

        $uploadedFiles = $request->file('pending_files', []);

        if (! is_array($uploadedFiles)) {
            $this->fail('pending_files', 'The pending design files are invalid.');
        }

        $uploadedFiles = array_values($uploadedFiles);
        Validator::make(
            ['files' => $uploadedFiles],
            [
                'files' => ['array', 'max:100'],
                'files.*' => [
                    'file',
                    'max:76800',
                    'mimes:jpg,jpeg,png,webp,pdf,svg,ai,eps,psd,tiff',
                ],
            ],
        )->validate();

        $cartItemsByDesignId = $this->cartItemsByDesignId($cart);
        $existingRequests = $order->productDesignRequests()
            ->get()
            ->filter(fn (ProductDesignRequest $design): bool => is_string(data_get($design->desgin, 'client_id')))
            ->keyBy(fn (ProductDesignRequest $design): string => (string) data_get($design->desgin, 'client_id'));
        $submittedIds = [];
        $seenIds = [];
        $normalizedDrafts = [];

        foreach ($drafts as $index => $draft) {
            if (! is_array($draft)) {
                $this->fail("pending_product_designs.{$index}", 'The pending design entry is invalid.');
            }

            $validatedDraft = Validator::make($draft, [
                'client_id' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
                'mode' => ['required', Rule::in(['upload', 'design-for-you', 'canva'])],
                'product_id' => ['required', 'integer', 'min:1'],
                'email' => ['nullable', 'email', 'max:255'],
                'order_name' => ['nullable', 'string', 'max:255'],
                'business_name' => ['nullable', 'string', 'max:255'],
                'card_info' => ['nullable', 'string', 'max:5000'],
                'business_card_type' => ['nullable', 'string', 'max:255'],
                'design_service_code' => [
                    'nullable',
                    'string',
                    Rule::in(array_keys(DesignServiceRequest::DESIGN_SERVICE_FEES)),
                ],
                'terms_accepted' => ['nullable', 'boolean'],
                'file_indexes' => ['required', 'array'],
            ])->validate();

            $clientId = (string) $validatedDraft['client_id'];

            if (isset($seenIds[$clientId])) {
                $this->fail(
                    "pending_product_designs.{$index}.client_id",
                    'The same pending design was submitted more than once.',
                );
            }

            $seenIds[$clientId] = true;
            $submittedIds[$clientId] = true;

            if (isset($existingRequests[$clientId])) {
                continue;
            }

            $cartItem = $cartItemsByDesignId[$clientId] ?? null;

            if ($cartItem === null) {
                $this->fail(
                    "pending_product_designs.{$index}.client_id",
                    'This product design is no longer present in the cart.',
                );
            }

            if ((int) $validatedDraft['product_id'] !== (int) ($cartItem['product_id'] ?? 0)) {
                $this->fail(
                    "pending_product_designs.{$index}.product_id",
                    'The product design does not match the cart item.',
                );
            }

            $mode = (string) $validatedDraft['mode'];

            if ($mode !== 'canva') {
                Validator::make($draft, [
                    'email' => ['required', 'email', 'max:255'],
                    'business_name' => [
                        $mode === 'design-for-you' ? 'required' : 'nullable',
                        'string',
                        'max:255',
                    ],
                    'business_card_type' => ['required', 'string', 'max:255'],
                    'terms_accepted' => ['required', 'boolean', 'accepted'],
                ])->validate();
            }

            $fileIndexes = $validatedDraft['file_indexes'];
            $files = [
                'design_file' => $this->filesForIndexes(
                    $fileIndexes['design_file'] ?? [],
                    $uploadedFiles,
                    "pending_product_designs.{$index}.file_indexes.design_file",
                ),
                'logo_file' => $this->filesForIndexes(
                    $fileIndexes['logo_file'] ?? [],
                    $uploadedFiles,
                    "pending_product_designs.{$index}.file_indexes.logo_file",
                ),
                'example_files' => $this->filesForIndexes(
                    $fileIndexes['example_files'] ?? [],
                    $uploadedFiles,
                    "pending_product_designs.{$index}.file_indexes.example_files",
                ),
            ];

            if (in_array($mode, ['upload', 'canva'], true) && $files['design_file'] === []) {
                $this->fail(
                    "pending_product_designs.{$index}.file_indexes.design_file",
                    'At least one design file is required.',
                );
            }

            $this->validateRoleFiles($files, $mode, $index);
            $normalizedDrafts[] = [
                'draft' => $validatedDraft,
                'cart_item' => $cartItem,
                'files' => $files,
            ];
        }

        foreach (array_keys($cartItemsByDesignId) as $clientId) {
            if (! isset($submittedIds[$clientId]) && ! isset($existingRequests[$clientId])) {
                $this->fail(
                    'pending_product_designs',
                    'Please keep all selected design files ready before placing the order.',
                );
            }
        }

        foreach ($normalizedDrafts as $normalizedDraft) {
            /** @var array<string, mixed> $draft */
            $draft = $normalizedDraft['draft'];
            /** @var array<string, mixed> $cartItem */
            $cartItem = $normalizedDraft['cart_item'];
            /** @var array<string, array<int, UploadedFile>> $files */
            $files = $normalizedDraft['files'];
            $mode = (string) $draft['mode'];

            $designPaths = $mode === 'canva'
                ? $this->storeFiles($files['design_file'], 'product-designs/canva')
                : ($mode === 'upload'
                    ? $this->storeFiles($files['design_file'], 'product-designs/designs')
                    : []);
            $logoPaths = $this->storeFiles($files['logo_file'], 'product-designs/logos');
            $examplePaths = $this->storeFiles($files['example_files'], 'product-designs/examples');

            $payload = [
                'source' => 'product-page',
                'mode' => $mode,
                'product_id' => (int) $cartItem['product_id'],
                'product_name' => (string) ($cartItem['name'] ?? $draft['product_name'] ?? ''),
                'product_slug' => (string) ($cartItem['slug'] ?? $draft['product_slug'] ?? ''),
                'client_id' => (string) $draft['client_id'],
                'email' => $draft['email'] ?? null,
                'order_name' => ($draft['order_name'] ?? '') !== '' ? $draft['order_name'] : null,
                'business_name' => $draft['business_name'] ?? null,
                'card_info' => $draft['card_info'] ?? null,
                'business_card_type' => $draft['business_card_type'] ?? null,
                'design_service_code' => ($draft['design_service_code'] ?? '') !== ''
                    ? $draft['design_service_code']
                    : null,
                'terms_accepted' => (bool) ($draft['terms_accepted'] ?? false),
                'design_path' => $this->pathValue($designPaths),
                'logo_path' => $this->pathValue($logoPaths),
                'example_paths' => $examplePaths,
            ];

            $order->productDesignRequests()->create(['desgin' => $payload]);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function cartItemsByDesignId(Cart $cart): array
    {
        $items = [];

        foreach ($cart->selectedItems() as $item) {
            $clientId = $item['pending_design_id'] ?? null;

            if (is_string($clientId) && $clientId !== '') {
                $items[$clientId] = $item;
            }
        }

        return $items;
    }

    private function ensurePendingDesignsWereSubmitted(Order $order, Cart $cart): void
    {
        $existingIds = $order->productDesignRequests()
            ->get()
            ->map(fn (ProductDesignRequest $design): mixed => data_get($design->desgin, 'client_id'))
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->all();

        foreach (array_keys($this->cartItemsByDesignId($cart)) as $clientId) {
            if (! in_array($clientId, $existingIds, true)) {
                $this->fail(
                    'pending_product_designs',
                    'Please keep all selected design files ready before placing the order.',
                );
            }
        }
    }

    /**
     * @param  array<int, mixed>  $uploadedFiles
     * @return array<int, UploadedFile>
     */
    private function filesForIndexes(mixed $indexes, array $uploadedFiles, string $attribute): array
    {
        if ($indexes === null) {
            return [];
        }

        if (! is_array($indexes)) {
            $this->fail($attribute, 'The file list is invalid.');
        }

        $files = [];

        foreach ($indexes as $index) {
            if (filter_var($index, FILTER_VALIDATE_INT) === false) {
                $this->fail($attribute, 'The file list is invalid.');
            }

            $fileIndex = (int) $index;
            $file = $uploadedFiles[$fileIndex] ?? null;

            if (! $file instanceof UploadedFile) {
                $this->fail($attribute, 'A submitted design file is missing.');
            }

            $files[] = $file;
        }

        return $files;
    }

    /**
     * @param  array<string, array<int, UploadedFile>>  $files
     */
    private function validateRoleFiles(array $files, string $mode, int|string $draftIndex): void
    {
        foreach ($files as $role => $roleFiles) {
            foreach ($roleFiles as $fileIndex => $file) {
                $maxKb = $role === 'design_file' && $mode === 'upload'
                    ? 76800
                    : 20480;
                Validator::make(
                    ['file' => $file],
                    [
                        'file' => [
                            'required',
                            'file',
                            "max:{$maxKb}",
                            'mimes:jpg,jpeg,png,webp,pdf,svg,ai,eps,psd,tiff',
                        ],
                    ],
                )->validate();
            }
        }
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    private function storeFiles(array $files, string $directory): array
    {
        $paths = [];

        foreach ($files as $file) {
            $path = $file->store($directory, 'public');

            if ($path === false) {
                throw new \RuntimeException('Unable to store a submitted design file.');
            }

            $paths[] = $path;
        }

        return $paths;
    }

    /**
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

    private function fail(string $attribute, string $message): never
    {
        throw ValidationException::withMessages([$attribute => $message]);
    }
}
