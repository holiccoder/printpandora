<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductImageService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Http\UploadedFile;
use Livewire\WithFileUploads;

class ManageProductImages extends Page
{
    use InteractsWithRecord;
    use WithFileUploads;

    protected static string $resource = ProductResource::class;

    protected string $view = 'filament.resources.product-resource.pages.manage-product-images';

    /**
     * @var array<int, array{
     *     key: string,
     *     gallery_id: string,
     *     gallery_label: string,
     *     gallery_index: int,
     *     image_index: int,
     *     source_path: string,
     *     current_url: string,
     *     is_default: bool,
     *     is_overridden: bool
     * }>
     */
    public array $imageSlots = [];

    /** @var array<int, array<string, mixed>> */
    public array $galleryGroups = [];

    /** @var array<string, UploadedFile|null> */
    public array $uploads = [];

    public ?string $featuredImage = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        abort_unless(app(ProductImageService::class)->supportsBusinessCard($this->getProduct()), 404);

        $this->refreshImageState();
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    public function getTitle(): string
    {
        return "Manage images: {$this->getProduct()->name}";
    }

    public function getBreadcrumb(): string
    {
        return 'Images';
    }

    public function replaceImage(string $key): void
    {
        $slot = $this->findSlot($key);

        abort_unless($slot !== null, 404);

        $this->validate([
            "uploads.{$key}" => ['required', 'image', 'max:10240'],
        ]);

        $upload = $this->uploads[$key] ?? null;

        if (! $upload instanceof UploadedFile) {
            $this->addError("uploads.{$key}", 'Please choose an image first.');

            return;
        }

        app(ProductImageService::class)->replaceGalleryImage($this->getProduct(), $slot, $upload);

        unset($this->uploads[$key]);
        $this->resetValidation("uploads.{$key}");
        $this->refreshImageState();

        Notification::make()
            ->title('Gallery image replaced')
            ->success()
            ->send();
    }

    public function resetImage(string $key): void
    {
        $slot = $this->findSlot($key);

        abort_unless($slot !== null, 404);

        app(ProductImageService::class)->resetGalleryImage($this->getProduct(), $slot);

        $this->refreshImageState();

        Notification::make()
            ->title('Image restored to the original')
            ->success()
            ->send();
    }

    public function replaceFeaturedImage(): void
    {
        $this->validate([
            'uploads.featured' => ['required', 'image', 'max:10240'],
        ]);

        $upload = $this->uploads['featured'] ?? null;

        if (! $upload instanceof UploadedFile) {
            $this->addError('uploads.featured', 'Please choose an image first.');

            return;
        }

        app(ProductImageService::class)->replaceFeaturedImage($this->getProduct(), $upload);

        unset($this->uploads['featured']);
        $this->resetValidation('uploads.featured');
        $this->refreshImageState();

        Notification::make()
            ->title('Featured image replaced')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit product')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => ProductResource::getUrl('edit', ['record' => $this->getProduct()])),
            Actions\Action::make('back')
                ->label('All products')
                ->icon('heroicon-o-arrow-left')
                ->url(ProductResource::getUrl('index')),
        ];
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }

    protected function refreshImageState(): void
    {
        $service = app(ProductImageService::class);
        $product = $this->getProduct();

        $this->imageSlots = $service->gallerySlots($product);
        $this->featuredImage = $service->featuredImageUrl($product);

        $groups = [];

        foreach ($this->imageSlots as $slot) {
            $groupKey = (string) $slot['gallery_index'];

            $groups[$groupKey] ??= [
                'label' => $slot['gallery_label'],
                'is_default' => $slot['is_default'],
                'images' => [],
            ];

            $groups[$groupKey]['images'][] = $slot;
        }

        $this->galleryGroups = array_values($groups);
    }

    /**
     * @return array{
     *     key: string,
     *     gallery_id: string,
     *     gallery_label: string,
     *     gallery_index: int,
     *     image_index: int,
     *     source_path: string,
     *     current_url: string,
     *     is_default: bool,
     *     is_overridden: bool
     * }|null
     */
    protected function findSlot(string $key): ?array
    {
        foreach ($this->imageSlots as $slot) {
            if ($slot['key'] === $key) {
                return $slot;
            }
        }

        return null;
    }

    protected function getProduct(): Product
    {
        /** @var Product $product */
        $product = $this->getRecord();

        return $product;
    }
}
