<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\DesignServiceRequest;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderFileAudit;
use App\Models\ProductDesignRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class OrderFileService
{
    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';

    public const STATUS_CONFIRMED = 'confirmed';

    /**
     * @return array{
     *     uploaded_files: array<int, array<string, mixed>>,
     *     awaiting_confirmation: array<int, array<string, mixed>>,
     *     confirmed_files: array<int, array<string, mixed>>
     * }
     */
    public function forOrder(Order $order, string $audience = 'customer'): array
    {
        $this->synchronizeLegacyFiles($order);

        $files = [
            'uploaded_files' => [],
            'awaiting_confirmation' => [],
            'confirmed_files' => [],
        ];

        foreach ($this->recordsForOrder($order) as $file) {
            $presented = $this->present($order, $file, $audience);

            if ($file->status === self::STATUS_CONFIRMED) {
                $files['confirmed_files'][] = $presented;
            } else {
                $files['awaiting_confirmation'][] = $presented;
            }

            // Keep this legacy alias for older dashboard consumers. The
            // customer-facing file modal now renders only the two workflow
            // sections above.
            if (
                $file->source === 'legacy'
                && in_array($file->origin_field, ['logo_path', 'example_paths'], true)
            ) {
                $files['uploaded_files'][] = $presented;
            }
        }

        return $files;
    }

    /**
     * @return Collection<int, OrderFile>
     */
    public function recordsForOrder(Order $order): Collection
    {
        $this->synchronizeLegacyFiles($order);

        return OrderFile::query()
            ->where('order_id', $order->getKey())
            ->orderByDesc('version')
            ->orderBy('id')
            ->get();
    }

    public function canCustomerManage(Order $order): bool
    {
        return in_array($order->status, [
            Order::STATUS_PENDING_REVIEW,
            Order::STATUS_PENDING_CONFIRMATION,
            Order::STATUS_NEEDS_REUPLOAD,
        ], true);
    }

    public function canCustomerConfirm(Order $order, ?Authenticatable $customer = null): bool
    {
        return $customer instanceof User
            && $order->status === Order::STATUS_PENDING_CONFIRMATION
            && $this->hasAwaitingFiles($order)
            && $this->hasCustomerDownloadedAwaitingFile($order, $customer);
    }

    public function hasAwaitingFiles(Order $order): bool
    {
        return $this->recordsForOrder($order)
            ->contains(fn (OrderFile $file): bool => $file->status === self::STATUS_AWAITING_CONFIRMATION);
    }

    public function hasCustomerDownloadedAwaitingFile(
        Order $order,
        User $customer,
    ): bool {
        $awaitingFileIds = $this->recordsForOrder($order)
            ->where('status', self::STATUS_AWAITING_CONFIRMATION)
            ->modelKeys();

        if ($awaitingFileIds === []) {
            return false;
        }

        return OrderFileAudit::query()
            ->where('order_id', $order->getKey())
            ->whereIn('order_file_id', $awaitingFileIds)
            ->where('actor_type', 'customer')
            ->where('actor_id', $customer->getAuthIdentifier())
            ->where('action', 'downloaded')
            ->exists();
    }

    /**
     * Store customer- or admin-uploaded files in the current pending version.
     * The caller controls the status transition because administrators upload
     * several files before explicitly submitting the set for confirmation.
     *
     * @param  array<int, mixed>  $files
     * @return Collection<int, OrderFile>
     */
    public function upload(
        Order $order,
        array $files,
        string $source,
        ?Authenticatable $actor = null,
    ): Collection {
        if (! in_array($source, ['customer', 'admin'], true)) {
            throw new \InvalidArgumentException('Unsupported order file source.');
        }

        $uploadedFiles = array_values(array_filter(
            $files,
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        ));

        if ($uploadedFiles === []) {
            throw ValidationException::withMessages([
                'files' => 'Please select at least one file.',
            ]);
        }

        return DB::transaction(function () use ($order, $uploadedFiles, $source, $actor): Collection {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->whereKey($order->getKey())
                ->firstOrFail();
            $version = $this->nextUploadVersion($lockedOrder);
            $created = new Collection;

            foreach ($uploadedFiles as $uploadedFile) {
                /** @var UploadedFile $uploadedFile */
                $path = $uploadedFile->store(
                    'order-files/'.$lockedOrder->getKey(),
                    'public',
                );
                $isCustomerUpload = $source === 'customer';
                $isAdminUpload = $source === 'admin';

                $file = OrderFile::create([
                    'order_id' => $lockedOrder->getKey(),
                    'path' => $path,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'label' => $uploadedFile->getClientOriginalName(),
                    'source' => $source,
                    'status' => self::STATUS_AWAITING_CONFIRMATION,
                    'version' => $version,
                    'is_current' => false,
                    'uploaded_by_user_id' => $isCustomerUpload ? $actor?->getAuthIdentifier() : null,
                    'uploaded_by_admin_id' => $isAdminUpload ? $actor?->getAuthIdentifier() : null,
                ]);

                $created->push($file);
                $this->recordAudit(
                    $lockedOrder,
                    'uploaded',
                    $actor,
                    $file,
                    [
                        'source' => $source,
                        'filename' => $file->original_name,
                        'version' => $version,
                    ],
                );
            }

            return $created;
        });
    }

    public function delete(
        Order $order,
        string $identifier,
        ?Authenticatable $actor = null,
    ): void {
        DB::transaction(function () use ($order, $identifier, $actor): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->whereKey($order->getKey())
                ->firstOrFail();
            $this->synchronizeLegacyFiles($lockedOrder);
            $file = $this->findFile($lockedOrder, $identifier);

            if (! $file) {
                throw (new ModelNotFoundException)->setModel(OrderFile::class, [$identifier]);
            }

            if ($actor instanceof User && $file->status === self::STATUS_CONFIRMED) {
                throw ValidationException::withMessages([
                    'file' => 'Confirmed file history cannot be deleted by the customer.',
                ]);
            }

            $wasCurrentConfirmedFile = $file->status === self::STATUS_CONFIRMED
                && $file->is_current;

            $this->recordAudit(
                $lockedOrder,
                'deleted',
                $actor,
                $file,
                [
                    'filename' => $file->original_name,
                    'version' => $file->version,
                    'source' => $file->source,
                ],
            );

            $this->removeLegacyReference($file);
            Storage::disk('public')->delete($file->path);
            $file->delete();

            if (
                $actor instanceof User
                && $lockedOrder->status === Order::STATUS_PENDING_CONFIRMATION
            ) {
                $lockedOrder->update(['status' => Order::STATUS_PENDING_REVIEW]);
            }

            if ($actor instanceof Admin && $wasCurrentConfirmedFile) {
                // A legacy order-level path is updated through a separate
                // model instance in removeLegacyReference(). Refresh the
                // locked order before synchronizing legacy files again so a
                // physically deleted path is not mirrored back into the new
                // table.
                $lockedOrder->refresh();
                $lockedOrder->update([
                    'status' => $this->hasAwaitingFiles($lockedOrder)
                        ? Order::STATUS_PENDING_CONFIRMATION
                        : Order::STATUS_PENDING_REVIEW,
                ]);
            }
        });
    }

    public function submitForConfirmation(Order $order, ?Authenticatable $actor = null): Order
    {
        return DB::transaction(function () use ($order, $actor): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->whereKey($order->getKey())
                ->firstOrFail();

            if (! $this->hasAwaitingFiles($lockedOrder)) {
                throw ValidationException::withMessages([
                    'files' => '提交客户确认前，至少需要一个文件。',
                ]);
            }

            $lockedOrder->update(['status' => Order::STATUS_PENDING_CONFIRMATION]);
            $this->recordAudit(
                $lockedOrder,
                'submitted_for_confirmation',
                $actor,
                null,
                ['version' => $this->awaitingVersion($lockedOrder)],
            );

            return $lockedOrder->fresh();
        });
    }

    public function confirmForCustomer(Order $order, User $customer): Order
    {
        return DB::transaction(function () use ($order, $customer): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->whereKey($order->getKey())
                ->firstOrFail();
            $this->synchronizeLegacyFiles($lockedOrder);

            if ($lockedOrder->status !== Order::STATUS_PENDING_CONFIRMATION) {
                throw ValidationException::withMessages([
                    'order' => 'This order is not waiting for customer confirmation.',
                ]);
            }

            if (! $this->hasCustomerDownloadedAwaitingFile($lockedOrder, $customer)) {
                throw ValidationException::withMessages([
                    'files' => 'Please download and review the files before confirming them.',
                ]);
            }

            return $this->confirmAwaitingFiles(
                $lockedOrder,
                $customer,
                'customer_confirmed',
                (int) $customer->getAuthIdentifier(),
            );
        });
    }

    public function confirmForAdmin(Order $order, Admin $admin): Order
    {
        return DB::transaction(function () use ($order, $admin): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->whereKey($order->getKey())
                ->firstOrFail();
            $this->synchronizeLegacyFiles($lockedOrder);

            if (! in_array($lockedOrder->status, [
                Order::STATUS_PENDING_REVIEW,
                Order::STATUS_PENDING_CONFIRMATION,
            ], true)) {
                throw ValidationException::withMessages([
                    'order' => '当前订单状态不能替客户确认文件。',
                ]);
            }

            return $this->confirmAwaitingFiles(
                $lockedOrder,
                $admin,
                'admin_confirmed_for_customer',
            );
        });
    }

    public function rejectForReupload(Order $order, Admin $admin, string $reason): Order
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => '请填写审核不通过的理由。',
            ]);
        }

        return DB::transaction(function () use ($order, $admin, $reason): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->whereKey($order->getKey())
                ->firstOrFail();
            $this->synchronizeLegacyFiles($lockedOrder);

            if (! in_array($lockedOrder->status, [
                Order::STATUS_PENDING_REVIEW,
                Order::STATUS_PENDING_CONFIRMATION,
            ], true)) {
                throw ValidationException::withMessages([
                    'order' => '当前订单状态不能审核不通过。',
                ]);
            }

            $version = $this->awaitingVersion($lockedOrder);

            if ($version === null) {
                throw ValidationException::withMessages([
                    'files' => '当前订单没有等待审核的文件。',
                ]);
            }

            $lockedOrder->update(['status' => Order::STATUS_NEEDS_REUPLOAD]);
            $this->recordAudit(
                $lockedOrder,
                'review_rejected',
                $admin,
                null,
                [
                    'version' => $version,
                    'reason' => $reason,
                ],
            );

            return $lockedOrder->fresh();
        });
    }

    /**
     * @return array{path: string, filename: string}|null
     */
    public function resolve(
        Order $order,
        string $identifier,
        ?Authenticatable $actor = null,
    ): ?array {
        $this->synchronizeLegacyFiles($order);
        $file = $this->findFile($order, $identifier);

        if (! $file || ! Storage::disk('public')->exists($file->path)) {
            return null;
        }

        $this->recordAudit(
            $order,
            'downloaded',
            $actor,
            $file,
            [
                'filename' => $file->original_name,
                'version' => $file->version,
            ],
        );

        return [
            'path' => $file->path,
            'filename' => $file->original_name,
        ];
    }

    public function recordZipDownload(Order $order, ?Authenticatable $actor = null): void
    {
        $this->recordAudit($order, 'downloaded_zip', $actor, null, [
            'file_count' => $this->recordsForOrder($order)->count(),
        ]);
    }

    public function identifier(OrderFile $file): string
    {
        return $file->identifier ?: 'order-file-'.$file->getKey();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordAudit(
        Order $order,
        string $action,
        ?Authenticatable $actor = null,
        ?OrderFile $file = null,
        array $metadata = [],
    ): OrderFileAudit {
        $actorType = match (true) {
            $actor instanceof Admin => 'admin',
            $actor instanceof User => 'customer',
            default => 'system',
        };

        return OrderFileAudit::create([
            'order_id' => $order->getKey(),
            'order_file_id' => $file?->getKey(),
            'actor_type' => $actorType,
            'actor_id' => $actor?->getAuthIdentifier(),
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Legacy product-page submissions predate the order_files table. Mirror
     * them into the new table lazily so existing orders gain the same workflow
     * without changing the original JSON contract used by checkout.
     */
    public function synchronizeLegacyFiles(Order $order): void
    {
        foreach ($this->legacyDefinitionsFor($order) as $definition) {
            $file = OrderFile::query()
                ->where('order_id', $order->getKey())
                ->where('identifier', $definition['identifier'])
                ->first();

            if (! $file) {
                OrderFile::create([
                    'order_id' => $order->getKey(),
                    'identifier' => $definition['identifier'],
                    'path' => $definition['path'],
                    'original_name' => basename($definition['path']),
                    'label' => $definition['label'],
                    'source' => 'legacy',
                    'status' => $definition['status'],
                    'version' => 1,
                    'is_current' => $definition['status'] === self::STATUS_CONFIRMED,
                    'uploaded_by_user_id' => $order->user_id,
                    'origin_type' => $definition['origin_type'],
                    'origin_id' => $definition['origin_id'],
                    'origin_field' => $definition['origin_field'],
                    'origin_index' => $definition['origin_index'],
                ]);

                continue;
            }

            $updates = [];

            if ($file->path !== $definition['path']) {
                $updates = [
                    'path' => $definition['path'],
                    'original_name' => basename($definition['path']),
                    'label' => $definition['label'],
                    'origin_type' => $definition['origin_type'],
                    'origin_id' => $definition['origin_id'],
                    'origin_field' => $definition['origin_field'],
                    'origin_index' => $definition['origin_index'],
                ];
            }

            if ($file->source === 'legacy'
                && $file->uploaded_by_user_id === null
                && $order->user_id !== null
            ) {
                $updates['uploaded_by_user_id'] = $order->user_id;
            }

            if ($updates !== []) {
                $file->update($updates);
            }
        }
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
     * @return array<int, array<string, mixed>>
     */
    private function legacyDefinitionsFor(Order $order): array
    {
        $definitions = [];

        foreach ($this->productDesignRequestsFor($order) as $designRequest) {
            $payload = $designRequest->getAttribute('desgin');

            if (! is_array($payload)) {
                continue;
            }

            $requestId = (int) $designRequest->getKey();
            $confirmedPaths = $this->firstPresent($payload, [
                'confirmed_design_paths',
                'confirmed_design_path',
                'confirmed_design',
            ]);

            $this->addLegacyDefinitions(
                $definitions,
                "product-design-{$requestId}-design",
                $confirmedPaths !== null
                    ? $confirmedPaths
                    : data_get($payload, 'design_path'),
                $confirmedPaths !== null ? 'Confirmed file' : 'Design file',
                'product_design',
                $requestId,
                $confirmedPaths !== null ? 'confirmed_design_paths' : 'design_path',
                $confirmedPaths !== null ? self::STATUS_CONFIRMED : self::STATUS_AWAITING_CONFIRMATION,
            );
            $this->addLegacyDefinitions(
                $definitions,
                "product-design-{$requestId}-logo",
                data_get($payload, 'logo_path'),
                'Company logo',
                'product_design',
                $requestId,
                'logo_path',
                self::STATUS_AWAITING_CONFIRMATION,
            );

            foreach ((array) data_get($payload, 'example_paths', []) as $index => $path) {
                $this->addLegacyDefinition(
                    $definitions,
                    "product-design-{$requestId}-example-{$index}",
                    $path,
                    'Example '.((int) $index + 1),
                    'product_design',
                    $requestId,
                    'example_paths',
                    (int) $index,
                    self::STATUS_AWAITING_CONFIRMATION,
                );
            }
        }

        $order->loadMissing('designServiceRequests');

        foreach ($order->designServiceRequests as $designRequest) {
            $requestId = (int) $designRequest->getKey();
            $confirmedPaths = $this->firstPresent($designRequest->toArray(), [
                'confirmed_design_paths',
                'confirmed_design_path',
                'confirmed_design',
            ]);

            $this->addLegacyDefinitions(
                $definitions,
                "design-service-{$requestId}-design",
                $confirmedPaths !== null
                    ? $confirmedPaths
                    : $designRequest->getAttribute('design_path'),
                $confirmedPaths !== null ? 'Confirmed file' : 'Design file',
                'design_service',
                $requestId,
                $confirmedPaths !== null ? 'confirmed_design_paths' : 'design_path',
                $confirmedPaths !== null ? self::STATUS_CONFIRMED : self::STATUS_AWAITING_CONFIRMATION,
            );
            $this->addLegacyDefinitions(
                $definitions,
                "design-service-{$requestId}-logo",
                $designRequest->getAttribute('logo_path'),
                'Logo',
                'design_service',
                $requestId,
                'logo_path',
                self::STATUS_AWAITING_CONFIRMATION,
            );

            foreach ((array) $designRequest->getAttribute('example_paths') as $index => $path) {
                $this->addLegacyDefinition(
                    $definitions,
                    "design-service-{$requestId}-example-{$index}",
                    $path,
                    'Example '.((int) $index + 1),
                    'design_service',
                    $requestId,
                    'example_paths',
                    (int) $index,
                    self::STATUS_AWAITING_CONFIRMATION,
                );
            }
        }

        $confirmedPaths = $this->firstPresent($order->getAttributes(), [
            'confirmed_design_paths',
            'confirmed_design_path',
            'confirmed_design',
        ]);

        $this->addLegacyDefinitions(
            $definitions,
            'order-confirmed-design',
            $confirmedPaths,
            'Confirmed file',
            'order',
            (int) $order->getKey(),
            $confirmedPaths !== null ? $this->firstPresentKey($order->getAttributes(), [
                'confirmed_design_paths',
                'confirmed_design_path',
                'confirmed_design',
            ]) : 'confirmed_design_paths',
            self::STATUS_CONFIRMED,
        );

        return $definitions;
    }

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     */
    private function addLegacyDefinitions(
        array &$definitions,
        string $idPrefix,
        mixed $paths,
        string $label,
        string $originType,
        int $originId,
        string $originField,
        string $status,
    ): void {
        foreach (array_values((array) $paths) as $index => $path) {
            $this->addLegacyDefinition(
                $definitions,
                "{$idPrefix}-{$index}",
                $path,
                count((array) $paths) > 1 ? $label.' '.((int) $index + 1) : $label,
                $originType,
                $originId,
                $originField,
                (int) $index,
                $status,
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     */
    private function addLegacyDefinition(
        array &$definitions,
        string $identifier,
        mixed $path,
        string $label,
        string $originType,
        int $originId,
        string $originField,
        int $originIndex,
        string $status,
    ): void {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        $definitions[] = [
            'identifier' => $identifier,
            'path' => trim($path),
            'label' => $label,
            'status' => $status,
            'origin_type' => $originType,
            'origin_id' => $originId,
            'origin_field' => $originField,
            'origin_index' => $originIndex,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Order $order, OrderFile $file, string $audience): array
    {
        $disk = Storage::disk('public');
        $available = $disk->exists($file->path);
        $identifier = $this->identifier($file);
        $isCustomer = $audience !== 'admin';
        $downloadRoute = $audience === 'admin'
            ? 'admin.orders.file'
            : 'dashboard.orders.file';
        $uploaderType = null;
        $uploaderId = null;

        if ($audience === 'admin') {
            if ($file->source === 'admin' && $file->uploaded_by_admin_id !== null) {
                $uploaderType = 'admin';
                $uploaderId = $file->uploaded_by_admin_id;
            } elseif (in_array($file->source, ['customer', 'legacy'], true)) {
                $customerUploaderId = $file->uploaded_by_user_id ?? $order->user_id;

                if ($customerUploaderId !== null) {
                    $uploaderType = 'customer';
                    $uploaderId = $customerUploaderId;
                }
            }
        }

        return [
            'id' => $identifier,
            'filename' => $file->original_name,
            'label' => $this->labelForAudience($file->label ?: $file->original_name, $audience),
            'size' => $available ? (int) $disk->size($file->path) : null,
            'uploaded_at' => $file->created_at?->toIso8601String(),
            'download_url' => $available
                ? route($downloadRoute, [
                    'id' => $order->getKey(),
                    'file' => $identifier,
                ])
                : null,
            'delete_url' => $isCustomer && $this->canCustomerManage($order)
                ? route('dashboard.orders.files.destroy', [
                    'id' => $order->getKey(),
                    'file' => $identifier,
                ])
                : null,
            'can_delete' => $isCustomer
                ? $this->canCustomerManage($order)
                    && $file->status !== self::STATUS_CONFIRMED
                : true,
            'version' => $file->version,
            'is_current' => $file->is_current,
            'source' => $file->source,
            'status' => $file->status,
            'uploader_type' => $uploaderType,
            'uploader_id' => $uploaderId,
        ];
    }

    private function labelForAudience(string $label, string $audience): string
    {
        if ($audience !== 'admin') {
            return $label;
        }

        if (preg_match('/^(Confirmed file|Design file|Company logo|Logo|Example)(?: (\d+))?$/', $label, $matches) !== 1) {
            return $label;
        }

        $translatedLabel = match ($matches[1]) {
            'Confirmed file' => '已确认文件',
            'Design file' => '设计文件',
            'Company logo', 'Logo' => '公司标志',
            'Example' => '示例',
        };

        return isset($matches[2]) ? $translatedLabel.' '.$matches[2] : $translatedLabel;
    }

    private function findFile(Order $order, string $identifier): ?OrderFile
    {
        $query = OrderFile::query()->where('order_id', $order->getKey());

        if (str_starts_with($identifier, 'order-file-')) {
            $id = substr($identifier, strlen('order-file-'));

            if (ctype_digit($id)) {
                $query->whereKey((int) $id);
            } else {
                return null;
            }
        } else {
            $query->where('identifier', $identifier);
        }

        return $query->first();
    }

    private function nextUploadVersion(Order $order): int
    {
        $pendingVersion = OrderFile::query()
            ->where('order_id', $order->getKey())
            ->where('status', self::STATUS_AWAITING_CONFIRMATION)
            ->max('version');

        if ($pendingVersion !== null) {
            return (int) $pendingVersion;
        }

        return ((int) OrderFile::query()
            ->where('order_id', $order->getKey())
            ->max('version')) + 1;
    }

    private function awaitingVersion(Order $order): ?int
    {
        $version = OrderFile::query()
            ->where('order_id', $order->getKey())
            ->where('status', self::STATUS_AWAITING_CONFIRMATION)
            ->max('version');

        return $version === null ? null : (int) $version;
    }

    private function confirmAwaitingFiles(
        Order $lockedOrder,
        Authenticatable $actor,
        string $auditAction,
        ?int $confirmedByUserId = null,
    ): Order {
        $awaitingFiles = OrderFile::query()
            ->where('order_id', $lockedOrder->getKey())
            ->where('status', self::STATUS_AWAITING_CONFIRMATION)
            ->lockForUpdate()
            ->get();

        if ($awaitingFiles->isEmpty()) {
            throw ValidationException::withMessages([
                'files' => 'There are no files waiting for confirmation.',
            ]);
        }

        OrderFile::query()
            ->where('order_id', $lockedOrder->getKey())
            ->where('status', self::STATUS_CONFIRMED)
            ->update(['is_current' => false]);

        $now = now();
        foreach ($awaitingFiles as $file) {
            $file->update([
                'status' => self::STATUS_CONFIRMED,
                'is_current' => true,
                'confirmed_by_user_id' => $confirmedByUserId,
                'confirmed_at' => $now,
            ]);
        }

        $lockedOrder->update(['status' => Order::STATUS_CONFIRMED]);
        $this->recordAudit(
            $lockedOrder,
            $auditAction,
            $actor,
            null,
            [
                'version' => $awaitingFiles->max('version'),
                'file_ids' => $awaitingFiles->modelKeys(),
            ],
        );

        return $lockedOrder->fresh();
    }

    private function removeLegacyReference(OrderFile $file): void
    {
        if ($file->source !== 'legacy' || ! $file->origin_type || ! $file->origin_id || ! $file->origin_field) {
            return;
        }

        if ($file->origin_type === 'product_design') {
            $request = ProductDesignRequest::query()->find($file->origin_id);

            if (! $request) {
                return;
            }

            $payload = $request->getAttribute('desgin');
            if (! is_array($payload)) {
                return;
            }

            $payload[$file->origin_field] = $this->removePath(
                $payload[$file->origin_field] ?? null,
                $file->origin_index ?? 0,
                $file->path,
            );
            $request->forceFill(['desgin' => $payload])->save();

            return;
        }

        if ($file->origin_type === 'design_service') {
            $request = DesignServiceRequest::query()->find($file->origin_id);

            if (! $request) {
                return;
            }

            $request->setAttribute(
                $file->origin_field,
                $this->removePath(
                    $request->getAttribute($file->origin_field),
                    $file->origin_index ?? 0,
                    $file->path,
                ),
            );
            $request->save();

            return;
        }

        if ($file->origin_type === 'order') {
            $order = Order::query()->find($file->origin_id);

            if (! $order) {
                return;
            }

            $order->forceFill([
                $file->origin_field => $this->removePath(
                    $order->getAttribute($file->origin_field),
                    $file->origin_index ?? 0,
                    $file->path,
                ),
            ])->save();
        }
    }

    private function removePath(mixed $value, int $index, string $path): mixed
    {
        if (! is_array($value)) {
            return $value === $path ? null : $value;
        }

        $remaining = [];
        foreach ($value as $key => $candidate) {
            if ((int) $key === $index || $candidate === $path) {
                continue;
            }

            $remaining[] = $candidate;
        }

        return $remaining;
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
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $keys
     */
    private function firstPresentKey(array $values, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $values[$key] ?? null;

            if ($value !== null && $value !== '' && $value !== []) {
                return $key;
            }
        }

        return null;
    }
}
