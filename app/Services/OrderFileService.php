<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductDesignRequest;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

final class OrderFileService
{
    /**
     * @return array{
     *     uploaded_files: array<int, array{
     *         id: string,
     *         filename: string,
     *         label: string,
     *         size: int|null,
     *         uploaded_at: string|null,
     *         download_url: string|null
     *     }>,
     *     awaiting_confirmation: array<int, array{
     *         id: string,
     *         filename: string,
     *         label: string,
     *         size: int|null,
     *         uploaded_at: string|null,
     *         download_url: string|null
     *     }>,
     *     confirmed_files: array<int, array{
     *         id: string,
     *         filename: string,
     *         label: string,
     *         size: int|null,
     *         uploaded_at: string|null,
     *         download_url: string|null
     *     }>
     * }
     */
    public function forOrder(Order $order): array
    {
        $files = [
            'uploaded_files' => [],
            'awaiting_confirmation' => [],
            'confirmed_files' => [],
        ];

        foreach ($this->entriesForOrder($order) as $entry) {
            $section = $entry['section'];
            $files[$section][] = $this->present($order, $entry);
        }

        return $files;
    }

    /**
     * @return array{path: string, filename: string}|null
     */
    public function resolve(Order $order, string $id): ?array
    {
        foreach ($this->entriesForOrder($order) as $entry) {
            if ($entry['id'] !== $id) {
                continue;
            }

            if (! Storage::disk('public')->exists($entry['path'])) {
                return null;
            }

            return [
                'path' => $entry['path'],
                'filename' => $entry['filename'],
            ];
        }

        return null;
    }

    /**
     * @return array<int, array{
     *     id: string,
     *     section: 'uploaded_files'|'awaiting_confirmation'|'confirmed_files',
     *     path: string,
     *     filename: string,
     *     label: string,
     *     uploaded_at: string|null
     * }>
     */
    private function entriesForOrder(Order $order): array
    {
        $entries = [];

        foreach ($this->productDesignRequestsFor($order) as $designRequest) {
            $payload = $designRequest->getAttribute('desgin');

            if (! is_array($payload)) {
                continue;
            }

            $requestId = (int) $designRequest->getKey();
            $uploadedAt = $designRequest->created_at;
            $confirmedDesignPaths = $this->firstPresent($payload, [
                'confirmed_design_paths',
                'confirmed_design_path',
                'confirmed_design',
            ]);

            $this->addEntries(
                $entries,
                'confirmed_files',
                "product-design-{$requestId}-design",
                $confirmedDesignPaths,
                'Confirmed file',
                $uploadedAt,
            );
            if ($confirmedDesignPaths === null) {
                $this->addEntries(
                    $entries,
                    'awaiting_confirmation',
                    "product-design-{$requestId}-design",
                    data_get($payload, 'design_path'),
                    'Design file',
                    $uploadedAt,
                );
            }
            $this->addEntries(
                $entries,
                'uploaded_files',
                "product-design-{$requestId}-logo",
                data_get($payload, 'logo_path'),
                'Company logo',
                $uploadedAt,
            );

            foreach ((array) data_get($payload, 'example_paths', []) as $index => $path) {
                $this->addEntry(
                    $entries,
                    'uploaded_files',
                    "product-design-{$requestId}-example-{$index}",
                    $path,
                    'Example '.((int) $index + 1),
                    $uploadedAt,
                );
            }
        }

        $order->loadMissing('designServiceRequests');

        foreach ($order->designServiceRequests as $designRequest) {
            $requestId = (int) $designRequest->getKey();
            $uploadedAt = $designRequest->created_at;
            $confirmedDesignPaths = $this->firstPresent($designRequest->toArray(), [
                'confirmed_design_paths',
                'confirmed_design_path',
                'confirmed_design',
            ]);

            $this->addEntries(
                $entries,
                'confirmed_files',
                "design-service-{$requestId}-design",
                $confirmedDesignPaths,
                'Confirmed file',
                $uploadedAt,
            );
            if ($confirmedDesignPaths === null) {
                $this->addEntries(
                    $entries,
                    'awaiting_confirmation',
                    "design-service-{$requestId}-design",
                    $designRequest->getAttribute('design_path'),
                    'Design file',
                    $uploadedAt,
                );
            }
            $this->addEntry(
                $entries,
                'uploaded_files',
                "design-service-{$requestId}-logo",
                $designRequest->getAttribute('logo_path'),
                'Logo',
                $uploadedAt,
            );

            foreach ((array) $designRequest->getAttribute('example_paths') as $index => $path) {
                $this->addEntry(
                    $entries,
                    'uploaded_files',
                    "design-service-{$requestId}-example-{$index}",
                    $path,
                    'Example '.((int) $index + 1),
                    $uploadedAt,
                );
            }
        }

        $orderDate = $order->updated_at ?? $order->created_at;
        $this->addEntries(
            $entries,
            'confirmed_files',
            'order-confirmed-design',
            $this->firstPresent($order->getAttributes(), [
                'confirmed_design_paths',
                'confirmed_design_path',
            ]),
            'Confirmed file',
            $orderDate,
        );

        return $entries;
    }

    /**
     * @return Collection<int, ProductDesignRequest>
     */
    private function productDesignRequestsFor(Order $order): Collection
    {
        $order->loadMissing('items');

        $requests = $order->productDesignRequests()
            ->latest()
            ->get();
        $productIds = $order->items
            ->pluck('product_id')
            ->map(static fn (mixed $productId): int => (int) $productId)
            ->filter(static fn (int $productId): bool => $productId > 0)
            ->unique()
            ->values();
        $email = trim((string) $order->customer_email);

        if ($productIds->isEmpty() || $email === '') {
            return $requests;
        }

        $legacyRequests = ProductDesignRequest::query()
            ->whereNull('order_id')
            ->where('desgin->email', $email)
            ->whereIn('desgin->product_id', $productIds->all())
            ->get();

        return $requests
            ->merge($legacyRequests)
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * @param  array<int, array{
     *     id: string,
     *     section: 'uploaded_files'|'awaiting_confirmation'|'confirmed_files',
     *     path: string,
     *     filename: string,
     *     label: string,
     *     uploaded_at: string|null
     * }>  $entries
     * @param  'uploaded_files'|'awaiting_confirmation'|'confirmed_files'  $section
     */
    private function addEntries(
        array &$entries,
        string $section,
        string $idPrefix,
        mixed $paths,
        string $label,
        ?CarbonInterface $uploadedAt,
    ): void {
        $paths = array_values((array) $paths);

        foreach ($paths as $index => $path) {
            $this->addEntry(
                $entries,
                $section,
                "{$idPrefix}-{$index}",
                $path,
                count($paths) > 1 ? $label.' '.((int) $index + 1) : $label,
                $uploadedAt,
            );
        }
    }

    /**
     * @param  array<int, array{
     *     id: string,
     *     section: 'uploaded_files'|'awaiting_confirmation'|'confirmed_files',
     *     path: string,
     *     filename: string,
     *     label: string,
     *     uploaded_at: string|null
     * }>  $entries
     * @param  'uploaded_files'|'awaiting_confirmation'|'confirmed_files'  $section
     */
    private function addEntry(
        array &$entries,
        string $section,
        string $id,
        mixed $path,
        string $label,
        ?CarbonInterface $uploadedAt,
    ): void {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        $path = trim($path);

        $entries[] = [
            'id' => $id,
            'section' => $section,
            'path' => $path,
            'filename' => basename($path),
            'label' => $label,
            'uploaded_at' => $uploadedAt?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $keys
     */
    private function firstPresent(array $values, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = $values[$key] ?? null;

            if ($value !== null && $value !== '' && $value !== []) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array{
     *     id: string,
     *     section: 'uploaded_files'|'awaiting_confirmation'|'confirmed_files',
     *     path: string,
     *     filename: string,
     *     label: string,
     *     uploaded_at: string|null
     * }  $entry
     * @return array{
     *     id: string,
     *     filename: string,
     *     label: string,
     *     size: int|null,
     *     uploaded_at: string|null,
     *     download_url: string|null
     * }
     */
    private function present(Order $order, array $entry): array
    {
        $disk = Storage::disk('public');
        $available = $disk->exists($entry['path']);

        return [
            'id' => $entry['id'],
            'filename' => $entry['filename'],
            'label' => $entry['label'],
            'size' => $available ? (int) $disk->size($entry['path']) : null,
            'uploaded_at' => $entry['uploaded_at'],
            'download_url' => $available
                ? route('dashboard.orders.file', [
                    'id' => $order->getKey(),
                    'file' => $entry['id'],
                ])
                : null,
        ];
    }
}
